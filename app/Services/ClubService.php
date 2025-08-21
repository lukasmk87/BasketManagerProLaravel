<?php

namespace App\Services;

use App\Models\Club;
use App\Models\User;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ClubService
{
    /**
     * Create a new club.
     */
    public function createClub(array $data): Club
    {
        DB::beginTransaction();

        try {
            // Generate unique slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = $this->generateUniqueSlug($data['name']);
            }

            // Create club record
            $club = Club::create([
                'name' => $data['name'],
                'short_name' => $data['short_name'] ?? null,
                'slug' => $data['slug'],
                'description' => $data['description'] ?? null,
                'website' => $data['website'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'country' => $data['country'] ?? 'DE',
                'founded_at' => $data['founded_at'] ?? null,
                'logo_url' => $data['logo_url'] ?? null,
                'colors_primary' => $data['colors_primary'] ?? '#000000',
                'colors_secondary' => $data['colors_secondary'] ?? '#ffffff',
                'president_name' => $data['president_name'] ?? null,
                'president_email' => $data['president_email'] ?? null,
                'vice_president_name' => $data['vice_president_name'] ?? null,
                'secretary_name' => $data['secretary_name'] ?? null,
                'treasurer_name' => $data['treasurer_name'] ?? null,
                'facilities' => $data['facilities'] ?? null,
                'membership_fee_annual' => $data['membership_fee_annual'] ?? null,
                'membership_fee_monthly' => $data['membership_fee_monthly'] ?? null,
                'accepts_new_members' => $data['accepts_new_members'] ?? true,
                'offers_youth_programs' => $data['offers_youth_programs'] ?? true,
                'offers_adult_programs' => $data['offers_adult_programs'] ?? true,
                'has_indoor_courts' => $data['has_indoor_courts'] ?? false,
                'has_outdoor_courts' => $data['has_outdoor_courts'] ?? false,
                'court_count' => $data['court_count'] ?? 1,
                'equipment_available' => $data['equipment_available'] ?? null,
                'training_times' => $data['training_times'] ?? null,
                'contact_person_name' => $data['contact_person_name'] ?? null,
                'contact_person_phone' => $data['contact_person_phone'] ?? null,
                'contact_person_email' => $data['contact_person_email'] ?? null,
                'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
                'social_media_facebook' => $data['social_media_facebook'] ?? null,
                'social_media_instagram' => $data['social_media_instagram'] ?? null,
                'social_media_twitter' => $data['social_media_twitter'] ?? null,
                'privacy_policy_url' => $data['privacy_policy_url'] ?? null,
                'terms_of_service_url' => $data['terms_of_service_url'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'is_verified' => $data['is_verified'] ?? false,
                'requires_approval' => $data['requires_approval'] ?? false,
            ]);

            // Add current user as club admin if specified
            if (!empty($data['add_current_user_as_admin']) && $data['add_current_user_as_admin']) {
                $currentUser = auth()->user();
                if ($currentUser) {
                    $this->addMemberToClub($club, $currentUser, 'admin');
                }
            }

            DB::commit();

            Log::info("Club created successfully", [
                'club_id' => $club->id,
                'club_name' => $club->name,
                'slug' => $club->slug
            ]);

            return $club->fresh(['teams', 'users']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to create club", [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing club.
     */
    public function updateClub(Club $club, array $data): Club
    {
        DB::beginTransaction();

        try {
            // Handle slug generation if name changed
            if (isset($data['name']) && $data['name'] !== $club->name && empty($data['slug'])) {
                $data['slug'] = $this->generateUniqueSlug($data['name'], $club->id);
            }

            $club->update($data);

            DB::commit();

            Log::info("Club updated successfully", [
                'club_id' => $club->id,
                'club_name' => $club->name,
                'updated_fields' => array_keys($data)
            ]);

            return $club->fresh(['teams', 'users']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to update club", [
                'club_id' => $club->id,
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Delete a club.
     */
    public function deleteClub(Club $club): bool
    {
        DB::beginTransaction();

        try {
            // Check if club has active teams
            $activeTeams = $club->teams()->active()->count();
            if ($activeTeams > 0) {
                throw new \InvalidArgumentException('Club kann nicht gelöscht werden, da noch aktive Teams vorhanden sind.');
            }

            // Check if club has active players
            $activePlayers = \App\Models\Player::query()
                ->join('player_team', 'player_team.player_id', '=', 'players.id')
                ->join('teams', 'teams.id', '=', 'player_team.team_id')
                ->where('teams.club_id', $club->id)
                ->where('player_team.is_active', true)
                ->where('player_team.status', 'active')
                ->whereNull('players.deleted_at')
                ->whereNull('teams.deleted_at')
                ->count();
            if ($activePlayers > 0) {
                throw new \InvalidArgumentException('Club kann nicht gelöscht werden, da noch aktive Spieler vorhanden sind.');
            }

            // Remove all club memberships
            $club->users()->detach();

            // Soft delete inactive teams
            $club->teams()->delete();

            // Soft delete the club
            $club->delete();

            DB::commit();

            Log::info("Club deleted successfully", [
                'club_id' => $club->id,
                'club_name' => $club->name
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to delete club", [
                'club_id' => $club->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Add a member to the club.
     */
    public function addMemberToClub(Club $club, User $user, string $role): void
    {
        DB::beginTransaction();

        try {
            // Check if user is already a member
            $existingMembership = $club->users()->where('user_id', $user->id)->first();
            
            if ($existingMembership) {
                // Update existing membership role
                $club->users()->updateExistingPivot($user->id, [
                    'role' => $role,
                    'is_active' => true,
                    'updated_at' => now()
                ]);
                
                Log::info("Club membership role updated", [
                    'club_id' => $club->id,
                    'user_id' => $user->id,
                    'old_role' => $existingMembership->pivot->role,
                    'new_role' => $role
                ]);
            } else {
                // Add new membership
                $club->users()->attach($user->id, [
                    'role' => $role,
                    'joined_at' => now(),
                    'is_active' => true
                ]);
                
                Log::info("User added to club", [
                    'club_id' => $club->id,
                    'user_id' => $user->id,
                    'role' => $role
                ]);
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to add member to club", [
                'club_id' => $club->id,
                'user_id' => $user->id,
                'role' => $role,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Remove a member from the club.
     */
    public function removeMemberFromClub(Club $club, User $user): void
    {
        DB::beginTransaction();

        try {
            // Check if user is a member
            $membership = $club->users()->where('user_id', $user->id)->first();
            
            if (!$membership) {
                throw new \InvalidArgumentException('Benutzer ist kein Mitglied dieses Clubs.');
            }

            // Check if user has active roles in teams
            $activeTeamRoles = $user->teams()
                ->where('club_id', $club->id)
                ->wherePivotIn('role', ['head_coach', 'assistant_coach'])
                ->count();

            if ($activeTeamRoles > 0) {
                Log::warning("Removing club member with active team roles", [
                    'club_id' => $club->id,
                    'user_id' => $user->id,
                    'active_team_roles' => $activeTeamRoles
                ]);
            }

            // Remove club membership
            $club->users()->detach($user->id);

            // Deactivate player records if any
            $user->players()->each(function ($player) use ($club) {
                $player->teams()
                    ->where('club_id', $club->id)
                    ->updateExistingPivot($player->teams()->where('club_id', $club->id)->pluck('teams.id'), [
                        'status' => 'inactive',
                        'is_active' => false,
                        'left_at' => now()
                    ]);
            });

            DB::commit();

            Log::info("User removed from club", [
                'club_id' => $club->id,
                'user_id' => $user->id,
                'role' => $membership->pivot->role
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to remove member from club", [
                'club_id' => $club->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get comprehensive club statistics.
     */
    public function getClubStatistics(Club $club): array
    {
        try {
            // Basic counts
        $teamStats = $club->teams()->selectRaw('
            COUNT(*) as total_teams,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_teams,
            COUNT(DISTINCT season) as seasons_active,
            COUNT(DISTINCT league) as leagues_participated
        ')->first();

        $playerStats = \App\Models\Player::query()
            ->selectRaw('
                COUNT(*) as total_players,
                SUM(CASE WHEN player_team.status = "active" THEN 1 ELSE 0 END) as active_players,
                AVG(CASE WHEN users.date_of_birth IS NOT NULL THEN YEAR(CURDATE()) - YEAR(users.date_of_birth) END) as avg_player_age
            ')
            ->join('player_team', 'player_team.player_id', '=', 'players.id')
            ->join('teams', 'teams.id', '=', 'player_team.team_id')
            ->leftJoin('users', 'players.user_id', '=', 'users.id')
            ->where('teams.club_id', $club->id)
            ->where('player_team.is_active', true)
            ->whereNull('players.deleted_at')
            ->whereNull('teams.deleted_at')
            ->first();

        $memberStats = DB::table('club_user')
            ->selectRaw('
                COUNT(*) as total_members,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_members
            ')
            ->where('club_id', $club->id)
            ->first();

        // Game statistics
        $gameStats = $club->teams()
            ->join('games', function ($join) {
                $join->on('teams.id', '=', 'games.home_team_id')
                     ->orOn('teams.id', '=', 'games.away_team_id');
            })
            ->where('games.status', 'finished')
            ->selectRaw('
                COUNT(*) as total_games,
                SUM(CASE 
                    WHEN (teams.id = games.home_team_id AND games.home_team_score > games.away_team_score) 
                      OR (teams.id = games.away_team_id AND games.away_team_score > games.home_team_score) 
                    THEN 1 ELSE 0 END) as total_wins,
                AVG(CASE 
                    WHEN teams.id = games.home_team_id THEN games.home_team_score 
                    ELSE games.away_team_score END) as avg_points_scored,
                AVG(CASE 
                    WHEN teams.id = games.home_team_id THEN games.away_team_score 
                    ELSE games.home_team_score END) as avg_points_allowed
            ')->first();

        // Financial overview
        $financialStats = [
            'total_annual_revenue' => $club->teams->sum(function ($team) {
                return ($team->registration_fee ?? 0) * $team->current_roster_size + 
                       ($team->monthly_fee ?? 0) * 12 * $team->current_roster_size;
            }),
            'membership_fee_annual' => $club->membership_fee_annual,
            'membership_fee_monthly' => $club->membership_fee_monthly,
        ];

        // Recent activity
        $recentActivity = [
            'teams_created_this_month' => $club->teams()
                ->whereBetween('created_at', [now()->startOfMonth(), now()])
                ->count(),
            'players_joined_this_month' => \App\Models\Player::query()
                ->join('player_team', 'player_team.player_id', '=', 'players.id')
                ->join('teams', 'teams.id', '=', 'player_team.team_id')
                ->where('teams.club_id', $club->id)
                ->where('player_team.is_active', true)
                ->whereBetween('player_team.joined_at', [now()->startOfMonth(), now()])
                ->whereNull('players.deleted_at')
                ->whereNull('teams.deleted_at')
                ->count(),
            'games_this_month' => $club->teams()
                ->join('games', function ($join) {
                    $join->on('teams.id', '=', 'games.home_team_id')
                         ->orOn('teams.id', '=', 'games.away_team_id');
                })
                ->whereBetween('games.scheduled_at', [now()->startOfMonth(), now()])
                ->count(),
        ];

        return [
            'basic_stats' => [
                'total_teams' => $teamStats->total_teams ?? 0,
                'active_teams' => $teamStats->active_teams ?? 0,
                'total_players' => $playerStats->total_players ?? 0,
                'active_players' => $playerStats->active_players ?? 0,
                'total_members' => $memberStats->total_members ?? 0,
                'active_members' => $memberStats->active_members ?? 0,
                'seasons_active' => $teamStats->seasons_active ?? 0,
                'leagues_participated' => $teamStats->leagues_participated ?? 0,
                'avg_player_age' => round($playerStats->avg_player_age ?? 0, 1),
            ],
            'game_stats' => [
                'total_games' => $gameStats->total_games ?? 0,
                'total_wins' => $gameStats->total_wins ?? 0,
                'total_losses' => ($gameStats->total_games ?? 0) - ($gameStats->total_wins ?? 0),
                'win_percentage' => $gameStats->total_games > 0 ? round(($gameStats->total_wins / $gameStats->total_games) * 100, 1) : 0,
                'avg_points_scored' => round($gameStats->avg_points_scored ?? 0, 1),
                'avg_points_allowed' => round($gameStats->avg_points_allowed ?? 0, 1),
            ],
            'financial_stats' => $financialStats,
            'recent_activity' => $recentActivity,
            'facilities' => [
                'has_indoor_courts' => $club->has_indoor_courts ?? false,
                'has_outdoor_courts' => $club->has_outdoor_courts ?? false,
                'court_count' => $club->court_count ?? 1,
                'equipment_available' => $club->equipment_available ?? null,
            ],
            'programs' => [
                'offers_youth_programs' => $club->offers_youth_programs ?? true,
                'offers_adult_programs' => $club->offers_adult_programs ?? true,
                'accepts_new_members' => $club->accepts_new_members ?? true,
            ]
        ];

        } catch (\Exception $e) {
            Log::error('Failed to load club statistics', [
                'club_id' => $club->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return basic fallback data structure
            return [
                'basic_stats' => [
                    'total_teams' => 0,
                    'active_teams' => 0,
                    'total_players' => 0,
                    'active_players' => 0,
                    'total_members' => 0,
                    'active_members' => 0,
                    'seasons_active' => 0,
                    'leagues_participated' => 0,
                    'avg_player_age' => 0,
                ],
                'game_stats' => [
                    'total_games' => 0,
                    'total_wins' => 0,
                    'total_losses' => 0,
                    'win_percentage' => 0,
                    'avg_points_scored' => 0,
                    'avg_points_allowed' => 0,
                ],
                'financial_stats' => [
                    'total_annual_revenue' => 0,
                    'membership_fee_annual' => null,
                    'membership_fee_monthly' => null,
                ],
                'recent_activity' => [
                    'teams_created_this_month' => 0,
                    'players_joined_this_month' => 0,
                    'games_this_month' => 0,
                ],
                'facilities' => [
                    'has_indoor_courts' => false,
                    'has_outdoor_courts' => false,
                    'court_count' => 1,
                    'equipment_available' => null,
                ],
                'programs' => [
                    'offers_youth_programs' => true,
                    'offers_adult_programs' => true,
                    'accepts_new_members' => true,
                ],
                'error' => 'Einige Statistiken konnten nicht geladen werden.'
            ];
        }
    }

    /**
     * Verify a club.
     */
    public function verifyClub(Club $club): Club
    {
        $club->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);

        Log::info("Club verified", [
            'club_id' => $club->id,
            'club_name' => $club->name
        ]);

        return $club;
    }

    /**
     * Generate emergency QR code data for club.
     */
    public function generateEmergencyQRData(Club $club): array
    {
        return [
            'type' => 'club_emergency',
            'club_id' => $club->id,
            'club_name' => $club->name,
            'emergency_contact_name' => $club->emergency_contact_name,
            'emergency_contact_phone' => $club->emergency_contact_phone,
            'address' => $club->address,
            'city' => $club->city,
            'contact_person_name' => $club->contact_person_name,
            'contact_person_phone' => $club->contact_person_phone,
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Get club's available seasons.
     */
    public function getClubSeasons(Club $club): array
    {
        return $club->teams()
            ->select('season')
            ->distinct()
            ->orderBy('season', 'desc')
            ->pluck('season')
            ->toArray();
    }

    /**
     * Get detailed club report.
     */
    public function generateClubReport(Club $club): array
    {
        return [
            'club_info' => [
                'id' => $club->id,
                'name' => $club->name,
                'short_name' => $club->short_name,
                'slug' => $club->slug,
                'founded_at' => $club->founded_at?->format('Y-m-d'),
                'website' => $club->website,
                'email' => $club->email,
                'phone' => $club->phone,
                'is_active' => $club->is_active,
                'is_verified' => $club->is_verified,
                'verified_at' => $club->verified_at?->format('Y-m-d'),
            ],
            'location' => [
                'address' => $club->address,
                'city' => $club->city,
                'state' => $club->state,
                'postal_code' => $club->postal_code,
                'country' => $club->country,
            ],
            'leadership' => [
                'president_name' => $club->president_name,
                'president_email' => $club->president_email,
                'vice_president_name' => $club->vice_president_name,
                'secretary_name' => $club->secretary_name,
                'treasurer_name' => $club->treasurer_name,
                'contact_person_name' => $club->contact_person_name,
                'contact_person_phone' => $club->contact_person_phone,
                'contact_person_email' => $club->contact_person_email,
            ],
            'emergency_info' => [
                'emergency_contact_name' => $club->emergency_contact_name,
                'emergency_contact_phone' => $club->emergency_contact_phone,
            ],
            'teams' => $club->teams()->with(['headCoach:id,name', 'players'])->get()->map(function ($team) {
                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'season' => $team->season,
                    'league' => $team->league,
                    'head_coach' => $team->headCoach?->name,
                    'player_count' => $team->players->count(),
                    'is_active' => $team->is_active,
                ];
            }),
            'statistics' => $this->getClubStatistics($club),
            'facilities' => [
                'has_indoor_courts' => $club->has_indoor_courts,
                'has_outdoor_courts' => $club->has_outdoor_courts,
                'court_count' => $club->court_count,
                'equipment_available' => $club->equipment_available,
                'facilities' => $club->facilities,
            ],
            'programs' => [
                'offers_youth_programs' => $club->offers_youth_programs,
                'offers_adult_programs' => $club->offers_adult_programs,
                'accepts_new_members' => $club->accepts_new_members,
                'training_times' => $club->training_times,
            ],
            'financial' => [
                'membership_fee_annual' => $club->membership_fee_annual,
                'membership_fee_monthly' => $club->membership_fee_monthly,
            ],
            'social_media' => [
                'facebook' => $club->social_media_facebook,
                'instagram' => $club->social_media_instagram,
                'twitter' => $club->social_media_twitter,
            ],
            'members' => $club->users()->withPivot('role', 'joined_at', 'is_active')->get()->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->pivot->role,
                    'joined_at' => $user->pivot->joined_at?->format('Y-m-d'),
                    'is_active' => $user->pivot->is_active,
                ];
            }),
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Generate a unique slug for the club.
     */
    private function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (true) {
            $query = Club::where('slug', $slug);
            
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
            
            if (!$query->exists()) {
                break;
            }
            
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}