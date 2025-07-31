<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            
            // Basic Information
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'birth_date' => $this->birth_date,
            'age' => $this->age,
            'gender' => $this->gender,
            'bio' => $this->bio,
            
            // Localization
            'language' => $this->language,
            'timezone' => $this->timezone,
            'preferred_locale' => $this->preferred_locale,
            
            // Profile & Media
            'profile_photo_path' => $this->profile_photo_path,
            'profile_photo_url' => $this->profile_photo_url,
            'avatar_url' => $this->avatar_url,
            
            // Authentication & Security
            'email_verified_at' => $this->email_verified_at,
            'two_factor_enabled' => $this->two_factor_enabled,
            'two_factor_confirmed_at' => $this->two_factor_confirmed_at,
            'last_login_at' => $this->when(
                $request->user()?->id === $this->id || $request->user()?->hasRole('admin'),
                $this->last_login_at
            ),
            'last_login_ip' => $this->when(
                $request->user()?->id === $this->id || $request->user()?->hasRole('admin'),
                $this->last_login_ip
            ),
            
            // Status
            'is_active' => $this->is_active,
            'player_profile_active' => $this->player_profile_active,
            
            // Basketball-specific
            'coaching_certifications' => $this->coaching_certifications,
            'basketball_stats' => $this->when(
                $request->has('include_basketball_stats'),
                fn() => $this->getBasketballStats()
            ),
            
            // Preferences & Settings
            'preferences' => $this->preferences,
            'notification_settings' => $this->when(
                $request->user()?->id === $this->id || $request->user()?->hasRole('admin'),
                $this->notification_settings
            ),
            
            // Roles & Permissions
            'roles' => $this->when(
                $this->relationLoaded('roles'),
                fn() => $this->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'display_name' => ucfirst(str_replace('_', ' ', $role->name)),
                    ];
                })
            ),
            
            'permissions' => $this->when(
                $this->relationLoaded('roles'),
                fn() => $this->getAllPermissions()->pluck('name')
            ),
            
            // Relationships
            'player_profile' => new PlayerResource($this->whenLoaded('playerProfile')),
            
            'coached_teams' => TeamResource::collection($this->whenLoaded('coachedTeams')),
            'assistant_coached_teams' => TeamResource::collection($this->whenLoaded('assistantCoachedTeams')),
            
            'clubs' => $this->when(
                $this->relationLoaded('clubs'),
                fn() => $this->clubs->map(function ($club) {
                    return [
                        'id' => $club->id,
                        'name' => $club->name,
                        'role' => $club->pivot->role,
                        'joined_at' => $club->pivot->joined_at,
                        'is_active' => $club->pivot->is_active,
                    ];
                })
            ),
            
            'social_accounts' => $this->when(
                $this->relationLoaded('socialAccounts') && 
                ($request->user()?->id === $this->id || $request->user()?->hasRole('admin')),
                fn() => $this->socialAccounts->map(function ($account) {
                    return [
                        'id' => $account->id,
                        'provider' => $account->provider,
                        'created_at' => $account->created_at,
                    ];
                })
            ),
            
            // Helper Methods Results
            'is_coach' => $this->isCoach(),
            'is_player' => $this->isPlayer(),
            'is_admin' => $this->isAdmin(),
            'is_club_admin' => $this->isClubAdmin(),
            'full_name' => $this->full_name,
            'display_name' => $this->name,
            'initials' => $this->getInitials(),
            
            // Primary Team (for quick access)
            'primary_team' => $this->when(
                $this->getPrimaryTeam(),
                new TeamResource($this->getPrimaryTeam())
            ),
            
            // Required Consents (for minors or GDPR)
            'has_required_consents' => $this->hasRequiredConsents(),
            
            // Activity Information (limited access)
            'recent_activities' => $this->when(
                $request->has('include_activities') && 
                ($request->user()?->id === $this->id || $request->user()?->can('view', $this->resource)),
                fn() => $this->activities()->latest()->limit(10)->get()->map(function ($activity) {
                    return [
                        'id' => $activity->id,
                        'description' => $activity->description,
                        'created_at' => $activity->created_at,
                    ];
                })
            ),
            
            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Get user initials.
     */
    private function getInitials(): string
    {
        $nameParts = explode(' ', $this->name);
        return strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
    }
}