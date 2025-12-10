<?php

namespace App\Services;

use App\Models\User;
use App\Models\Player;
use App\Models\Team;
use App\Models\Club;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Carbon\Carbon;

class UserService
{
    /**
     * Create a new user.
     */
    public function createUser(array $data): User
    {
        DB::beginTransaction();

        try {
            // Generate password if not provided
            $generatedPassword = null;
            if (!isset($data['password'])) {
                $generatedPassword = Str::random(12);
                $data['password'] = Hash::make($generatedPassword);
            } else {
                $data['password'] = Hash::make($data['password']);
            }

            // Create user record
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'phone' => $data['phone'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? $data['birth_date'] ?? null,
                'gender' => $data['gender'] ?? null,
                'address_street' => $data['address_street'] ?? $data['address'] ?? null,
                'address_city' => $data['address_city'] ?? $data['city'] ?? null,
                'address_state' => $data['address_state'] ?? $data['state'] ?? null,
                'address_zip' => $data['address_zip'] ?? $data['postal_code'] ?? null,
                'address_country' => $data['address_country'] ?? $data['country'] ?? 'DE',
                'language' => $data['language'] ?? 'de',
                'timezone' => $data['timezone'] ?? 'Europe/Berlin',
                'is_active' => $data['is_active'] ?? true,
                'email_verified_at' => $data['email_verified_at'] ?? null,
                'last_login_at' => null,
                'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
                'emergency_contact_relationship' => $data['emergency_contact_relationship'] ?? null,
                'allergies' => $data['allergies'] ?? null,
                'medications' => $data['medications'] ?? null,
                'marketing_consent' => $data['marketing_consent'] ?? $data['consent_marketing'] ?? false,
                'gdpr_consent' => $data['gdpr_consent'] ?? $data['consent_data_processing'] ?? true,
                'medical_consent' => $data['medical_consent'] ?? $data['consent_medical_info_sharing'] ?? false,
                'two_factor_confirmed_at' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_secret' => null,
            ]);

            // Assign roles if provided
            if (isset($data['roles'])) {
                $user->syncRoles($data['roles']);
            }

            // Create player profile if user has player role
            if ($user->hasRole('player') && isset($data['player_data'])) {
                $this->createPlayerProfile($user, $data['player_data']);
            }

            DB::commit();

            // Log user creation
            Log::info("User created successfully", [
                'user_id' => $user->id,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->toArray(),
                'password_generated' => $generatedPassword !== null
            ]);

            // Store generated password for potential notification
            if ($generatedPassword) {
                $user->generated_password = $generatedPassword;
            }

            return $user->fresh(['roles', 'playerProfile']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to create user", [
                'error' => $e->getMessage(),
                'data' => array_merge($data, ['password' => '[HIDDEN]'])
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing user.
     */
    public function updateUser(User $user, array $data): User
    {
        DB::beginTransaction();

        try {
            // Handle password update
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            $user->update($data);

            // Update roles if provided
            if (isset($data['roles'])) {
                $oldRoles = $user->roles->pluck('name')->toArray();
                $user->syncRoles($data['roles']);
                $newRoles = $user->fresh(['roles'])->roles->pluck('name')->toArray();
                
                Log::info("User roles updated", [
                    'user_id' => $user->id,
                    'old_roles' => $oldRoles,
                    'new_roles' => $newRoles
                ]);
            }

            // Update player profile if provided
            if (isset($data['player_data'])) {
                if ($user->playerProfile) {
                    $user->playerProfile->update($data['player_data']);
                } elseif ($user->hasRole('player')) {
                    $this->createPlayerProfile($user, $data['player_data']);
                }
            }

            DB::commit();

            Log::info("User updated successfully", [
                'user_id' => $user->id,
                'updated_fields' => array_keys($data),
                'password_changed' => isset($data['password'])
            ]);

            return $user->fresh(['roles', 'playerProfile']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to update user", [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'data' => array_merge($data, ['password' => '[HIDDEN]'])
            ]);
            throw $e;
        }
    }

    /**
     * Delete a user.
     */
    public function deleteUser(User $user): bool
    {
        DB::beginTransaction();

        try {
            // Check if user has critical dependencies
            $hasActivePlayerProfile = $user->playerProfile && $user->playerProfile->status === 'active';
            $hasActiveTeams = $user->coachedTeams()->where('is_active', true)->exists() ||
                             $user->assistantCoachedTeams()->where('is_active', true)->exists();
            $hasActiveClubMemberships = $user->clubs()->whereNull('club_user.left_at')->exists();

            if ($hasActivePlayerProfile || $hasActiveTeams || $hasActiveClubMemberships) {
                Log::info("User soft deleted due to active dependencies", [
                    'user_id' => $user->id,
                    'has_active_player_profile' => $hasActivePlayerProfile,
                    'has_active_teams' => $hasActiveTeams,
                    'has_active_club_memberships' => $hasActiveClubMemberships
                ]);

                // Soft delete and deactivate
                $user->update(['is_active' => false]);
                $user->delete();
            } else {
                // Hard delete if no critical dependencies
                // Safely delete social accounts if they exist
                if (method_exists($user, 'socialAccounts') && $user->socialAccounts()->exists()) {
                    try {
                        $user->socialAccounts()->delete();
                    } catch (\Exception $e) {
                        Log::warning('Could not delete social accounts', [
                            'user_id' => $user->id,
                            'error' => $e->getMessage()
                        ]);
                        // Continue with user deletion even if social accounts fail
                    }
                }

                $user->forceDelete();

                Log::info("User hard deleted (no active dependencies)", [
                    'user_id' => $user->id
                ]);
            }

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to delete user", [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Activate a user.
     */
    public function activateUser(User $user): User
    {
        $user->update(['is_active' => true]);

        Log::info("User activated", [
            'user_id' => $user->id,
            'email' => $user->email
        ]);

        return $user;
    }

    /**
     * Deactivate a user.
     */
    public function deactivateUser(User $user): User
    {
        $user->update(['is_active' => false]);

        Log::info("User deactivated", [
            'user_id' => $user->id,
            'email' => $user->email
        ]);

        return $user;
    }

    /**
     * Send password reset link to user.
     */
    public function sendPasswordReset(User $user): string
    {
        $token = Password::createToken($user);
        
        // Send password reset notification
        $user->sendPasswordResetNotification($token);

        Log::info("Password reset requested", [
            'user_id' => $user->id,
            'email' => $user->email
        ]);

        return $token;
    }

    /**
     * Get system-wide user statistics.
     */
    public function getUserStatistics(): array
    {
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();
        $verifiedUsers = User::whereNotNull('email_verified_at')->count();
        $twoFactorUsers = User::whereNotNull('two_factor_confirmed_at')->count();

        // Role distribution
        $roleStats = [];
        $roles = \Spatie\Permission\Models\Role::withCount('users')->get();
        foreach ($roles as $role) {
            $roleStats[$role->name] = $role->users_count;
        }

        // Activity stats
        $newUsersThisMonth = User::whereBetween('created_at', [now()->startOfMonth(), now()])->count();
        $activeUsersThisMonth = User::where('last_login_at', '>=', now()->startOfMonth())->count();

        // Geographic distribution
        $countryStats = User::selectRaw('address_country, COUNT(*) as count')
            ->whereNotNull('address_country')
            ->groupBy('address_country')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->pluck('count', 'address_country')
            ->toArray();

        // Age distribution for users with date_of_birth
        $ageGroups = [
            'under_18' => User::whereNotNull('date_of_birth')
                ->where('date_of_birth', '>', now()->subYears(18))
                ->count(),
            '18_25' => User::whereNotNull('date_of_birth')
                ->whereBetween('date_of_birth', [now()->subYears(25), now()->subYears(18)])
                ->count(),
            '26_35' => User::whereNotNull('date_of_birth')
                ->whereBetween('date_of_birth', [now()->subYears(35), now()->subYears(26)])
                ->count(),
            '36_50' => User::whereNotNull('date_of_birth')
                ->whereBetween('date_of_birth', [now()->subYears(50), now()->subYears(36)])
                ->count(),
            'over_50' => User::whereNotNull('date_of_birth')
                ->where('date_of_birth', '<', now()->subYears(50))
                ->count(),
        ];

        return [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'inactive_users' => $totalUsers - $activeUsers,
            'verified_users' => $verifiedUsers,
            'unverified_users' => $totalUsers - $verifiedUsers,
            'two_factor_enabled' => $twoFactorUsers,
            'verification_rate' => $totalUsers > 0 ? round(($verifiedUsers / $totalUsers) * 100, 1) : 0,
            'activity_rate' => $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 1) : 0,
            'new_users_this_month' => $newUsersThisMonth,
            'active_users_this_month' => $activeUsersThisMonth,
            'role_distribution' => $roleStats,
            'country_distribution' => $countryStats,
            'age_distribution' => $ageGroups,
        ];
    }

    /**
     * Get user's basketball-related statistics.
     */
    public function getUserBasketballStats(User $user): array
    {
        $stats = [
            'roles' => $user->roles->pluck('name')->toArray(),
            'is_player' => $user->isPlayer(),
            'is_coach' => $user->isCoach(),
            'is_admin' => $user->isAdmin(),
        ];

        // Player statistics
        if ($user->isPlayer() && $user->playerProfile) {
            $player = $user->playerProfile;
            $stats['player_stats'] = [
                'team' => $player->team?->name,
                'club' => $player->team?->club?->name,
                'jersey_number' => $player->jersey_number,
                'position' => $player->primary_position,
                'is_captain' => $player->is_captain,
                'is_starter' => $player->is_starter,
                'status' => $player->status,
                'games_played' => $player->games_played,
                'points_per_game' => $player->points_per_game,
                'rebounds_per_game' => $player->rebounds_per_game,
                'assists_per_game' => $player->assists_per_game,
            ];
        }

        // Coach statistics
        if ($user->isCoach()) {
            $coachedTeams = $user->coachedTeams;
            $assistantCoachedTeams = $user->assistantCoachedTeams()->get();
            
            $stats['coach_stats'] = [
                'head_coach_teams' => $coachedTeams->count(),
                'assistant_coach_teams' => $assistantCoachedTeams->count(),
                'total_teams' => $coachedTeams->count() + $assistantCoachedTeams->count(),
                'active_teams' => $coachedTeams->where('is_active', true)->count() + 
                                $assistantCoachedTeams->where('is_active', true)->count(),
                'clubs_involved' => $coachedTeams->merge($assistantCoachedTeams)->pluck('club.name')->unique()->values(),
            ];
        }

        // Admin/Club membership statistics
        $clubMemberships = $user->clubs;
        if ($clubMemberships->count() > 0) {
            $stats['club_memberships'] = $clubMemberships->map(function ($club) {
                return [
                    'club_name' => $club->name,
                    'role' => $club->pivot->role,
                    'joined_at' => $club->pivot->joined_at,
                    'is_active' => $club->pivot->is_active,
                ];
            });
        }

        return $stats;
    }

    /**
     * Get user's associated teams.
     */
    public function getUserTeams(User $user): array
    {
        $teams = collect();

        // Add coached teams
        if ($user->isCoach()) {
            $coachedTeams = $user->coachedTeams()->with('club')->get()->map(function ($team) {
                $team->role_in_team = 'head_coach';
                return $team;
            });
            
            $assistantCoachedTeams = $user->assistantCoachedTeams()->with('club')->get()->map(function ($team) {
                $team->role_in_team = 'assistant_coach';
                return $team;
            });
            
            $teams = $teams->merge($coachedTeams)->merge($assistantCoachedTeams);
        }

        // Add player team
        if ($user->isPlayer() && $user->playerProfile) {
            $playerTeam = $user->playerProfile->team()->with('club')->first();
            if ($playerTeam) {
                $playerTeam->role_in_team = 'player';
                $teams->push($playerTeam);
            }
        }

        return $teams->unique('id')->values()->toArray();
    }

    /**
     * Update user's locale preferences.
     */
    public function updateUserLocale(User $user, array $localeData): User
    {
        $user->update([
            'language' => $localeData['language'] ?? $user->language,
            'timezone' => $localeData['timezone'] ?? $user->timezone,
            'date_format' => $localeData['date_format'] ?? $user->date_format,
            'time_format' => $localeData['time_format'] ?? $user->time_format,
        ]);

        Log::info("User locale preferences updated", [
            'user_id' => $user->id,
            'language' => $user->language,
            'timezone' => $user->timezone
        ]);

        return $user;
    }

    /**
     * Get user's recent activity.
     */
    public function getUserActivities(User $user, int $limit = 50): array
    {
        if (!method_exists($user, 'activities')) {
            return [];
        }

        return $user->activities()
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'subject_type' => $activity->subject_type,
                    'subject_id' => $activity->subject_id,
                    'causer_type' => $activity->causer_type,
                    'causer_id' => $activity->causer_id,
                    'properties' => $activity->properties,
                    'created_at' => $activity->created_at,
                ];
            })
            ->toArray();
    }

    /**
     * Generate comprehensive user report.
     */
    public function generateUserReport(User $user): array
    {
        return [
            'user_info' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'birth_date' => $user->birth_date?->format('Y-m-d'),
                'age' => $user->birth_date?->age,
                'gender' => $user->gender,
                'is_active' => $user->is_active,
                'email_verified_at' => $user->email_verified_at?->format('Y-m-d H:i:s'),
                'last_login_at' => $user->last_login_at?->format('Y-m-d H:i:s'),
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
            ],
            'contact_info' => [
                'address' => $user->address,
                'city' => $user->city,
                'state' => $user->state,
                'postal_code' => $user->postal_code,
                'country' => $user->country,
                'emergency_contact_name' => $user->emergency_contact_name,
                'emergency_contact_phone' => $user->emergency_contact_phone,
                'emergency_contact_relationship' => $user->emergency_contact_relationship,
            ],
            'preferences' => [
                'language' => $user->language,
                'timezone' => $user->timezone,
                'date_format' => $user->date_format,
                'time_format' => $user->time_format,
            ],
            'medical_info' => [
                'medical_notes' => $user->medical_notes,
                'allergies' => $user->allergies,
                'medications' => $user->medications,
            ],
            'consent' => [
                'marketing' => $user->consent_marketing,
                'data_processing' => $user->consent_data_processing,
                'medical_info_sharing' => $user->consent_medical_info_sharing,
            ],
            'security' => [
                'two_factor_enabled' => $user->two_factor_confirmed_at !== null,
                'two_factor_confirmed_at' => $user->two_factor_confirmed_at?->format('Y-m-d H:i:s'),
            ],
            'roles' => $user->roles->pluck('name')->toArray(),
            'basketball_stats' => $this->getUserBasketballStats($user),
            'teams' => $this->getUserTeams($user),
            'club_memberships' => $user->clubs->map(function ($club) {
                return [
                    'club_name' => $club->name,
                    'role' => $club->pivot->role,
                    'joined_at' => $club->pivot->joined_at?->format('Y-m-d'),
                    'is_active' => $club->pivot->is_active,
                ];
            })->toArray(),
            'social_accounts' => $user->socialAccounts->map(function ($account) {
                return [
                    'provider' => $account->provider,
                    'provider_id' => $account->provider_id,
                    'created_at' => $account->created_at->format('Y-m-d'),
                ];
            })->toArray(),
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Create player profile for user.
     */
    private function createPlayerProfile(User $user, array $playerData): Player
    {
        return $user->playerProfile()->create($playerData);
    }
}