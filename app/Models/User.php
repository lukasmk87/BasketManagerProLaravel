<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\HasLocalePreference;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Cashier\Billable;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Services\User\UserRoleService;
use App\Services\User\UserApiService;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;
    use HasFactory; 
    use HasLocalePreference;
    use HasProfilePhoto;
    use HasRoles;
    use HasTeams;
    use LogsActivity;
    use Notifiable;
    use SoftDeletes;
    use TwoFactorAuthenticatable;
    use Billable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'phone',
        'date_of_birth',
        'gender',
        'nationality',
        'address_street',
        'address_city',
        'address_state',
        'address_zip',
        'address_country',
        'avatar_path',
        'bio',
        'social_links',
        'basketball_experience',
        'preferred_positions',
        'skill_level',
        'preferences',
        'notification_settings',
        'privacy_settings',
        'language',
        'locale',
        'timezone',
        'date_format',
        'time_format',
        'is_active',
        'is_verified',
        'verified_at',
        'account_type',
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'last_login_at',
        'last_login_ip',
        'login_count',
        'parent_id',
        'is_minor',
        'guardian_consent_date',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'blood_type',
        'medical_conditions',
        'allergies',
        'medications',
        'medical_consent',
        'medical_consent_date',
        'gdpr_consent',
        'gdpr_consent_at',
        'marketing_consent',
        'marketing_consent_at',
        'consent_history',
        'occupation',
        'employer',
        'education_level',
        'coaching_certifications',
        'referee_certifications',
        'background_check_completed',
        'background_check_date',
        'onboarding_completed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birth_date' => 'date',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_enabled' => 'boolean',
            'preferences' => 'array',
            'notification_settings' => 'array',
            'coaching_certifications' => 'array',
            'player_profile_active' => 'boolean',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
            'api_quota_reset_at' => 'datetime',
            'api_access_enabled' => 'boolean',
            'api_key_last_used_at' => 'datetime',
            'rate_limit_cache' => 'array',
            'onboarding_completed_at' => 'datetime',
        ];
    }

    // ============================
    // EMAIL VERIFICATION
    // ============================

    /**
     * Determine if the user has verified their email address.
     * Override Laravel's default implementation to correctly check email_verified_at.
     *
     * @return bool
     */
    public function hasVerifiedEmail(): bool
    {
        return ! is_null($this->email_verified_at);
    }

    /**
     * Mark the given user's email as verified.
     *
     * @return bool
     */
    public function markEmailAsVerified(): bool
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    // ============================
    // RELATIONSHIPS
    // ============================

    /**
     * Get the player profile associated with the user.
     */
    public function playerProfile(): HasOne
    {
        return $this->hasOne(Player::class);
    }

    /**
     * Get the user's current team (for Jetstream compatibility).
     * Returns the current Jetstream team relationship.
     */
    public function team()
    {
        // For Jetstream compatibility, return the currentTeam relationship
        return $this->currentTeam();
    }

    /**
     * Get the teams coached by this user as head coach.
     */
    public function coachedTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'head_coach_id');
    }

    /**
     * Get the teams coached by this user as assistant coach.
     * Returns a query builder to match the behavior of coachedTeams().
     */
    public function assistantCoachedTeams()
    {
        return Team::whereJsonContains('assistant_coaches', $this->id);
    }

    /**
     * Get all teams coached by this user (head + assistant).
     * Returns a collection of teams.
     */
    public function allCoachedTeams()
    {
        return $this->coachedTeams()->get()->merge($this->assistantCoachedTeams()->get());
    }

    /**
     * Get the social accounts linked to this user.
     */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    /**
     * Get the clubs this user is a member of.
     */
    public function clubs(): BelongsToMany
    {
        return $this->belongsToMany(Club::class, 'club_user')
                    ->withPivot('role', 'joined_at', 'is_active')
                    ->withTimestamps();
    }

    /**
     * Get the tenant that the user belongs to.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the parent of this user (if they are a minor).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    /**
     * Get the children of this user (if they are a parent).
     */
    public function children(): HasMany
    {
        return $this->hasMany(User::class, 'parent_id');
    }

    /**
     * Get the user's subscription.
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    /**
     * Get the user's API usage tracking records.
     */
    public function apiUsageTracking(): HasMany
    {
        return $this->hasMany(ApiUsageTracking::class);
    }

    /**
     * Get the user's rate limit exceptions.
     */
    public function rateLimitExceptions(): HasMany
    {
        return $this->hasMany(RateLimitException::class);
    }

    /**
     * Get the user's push notification subscriptions.
     */
    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    /**
     * Get the user's play favorites.
     */
    public function playFavorites(): HasMany
    {
        return $this->hasMany(PlayFavorite::class);
    }

    /**
     * Get the plays favorited by this user.
     */
    public function favoritedPlays(): BelongsToMany
    {
        return $this->belongsToMany(Play::class, 'play_favorites')
            ->withPivot(['notes', 'tags', 'favorite_type', 'personal_priority', 'is_quick_access'])
            ->withTimestamps();
    }

    // ============================
    // SCOPES
    // ============================

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include coaches.
     */
    public function scopeCoaches($query)
    {
        return $query->whereHas('roles', function ($q) {
            $q->whereIn('name', ['trainer', 'club_admin', 'admin']);
        });
    }

    /**
     * Scope a query to only include players.
     */
    public function scopePlayers($query)
    {
        return $query->where('player_profile_active', true)
                    ->whereHas('playerProfile', function($q) {
                        $q->where('status', 'active');
                    });
    }

    /**
     * Scope a query to only include users with active player profiles.
     */
    public function scopeWithActivePlayerProfile($query)
    {
        return $query->where('player_profile_active', true)
                    ->whereHas('playerProfile', function($q) {
                        $q->where('status', 'active');
                    });
    }

    /**
     * Scope a query to users whose player profile is in active teams.
     */
    public function scopeInCurrentTeam($query, $season = null, $league = null)
    {
        return $query->whereHas('playerProfile', function($q) use ($season, $league) {
            $q->where('status', 'active')
              ->whereHas('teams', function($teamQuery) use ($season, $league) {
                  if ($season) {
                      $teamQuery->where('teams.season', $season);
                  }
                  if ($league !== null) {
                      $teamQuery->where('teams.league', $league);
                  }
                  $teamQuery->where('teams.is_active', true)
                           ->wherePivot('status', 'active');
              });
        });
    }

    /**
     * Scope a query by locale preference.
     */
    public function scopeByLocale($query, string $locale)
    {
        return $query->where('language', $locale);
    }

    // ============================
    // ACCESSORS & MUTATORS
    // ============================

    /**
     * Get the user's age.
     */
    public function age(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->birth_date?->age
        );
    }

    /**
     * Get the user's full name.
     */
    public function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->name
        );
    }

    /**
     * Get the user's avatar URL with fallback.
     */
    public function avatarUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->avatar_path 
                ? asset('storage/' . $this->avatar_path)
                : 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=7F9CF5&background=EBF4FF'
        );
    }

    // ============================
    // HELPER METHODS
    // ============================

    /**
     * Check if the user has completed the onboarding wizard.
     */
    public function hasCompletedOnboarding(): bool
    {
        return $this->onboarding_completed_at !== null;
    }

    /**
     * Mark the onboarding as completed.
     */
    public function markOnboardingComplete(): void
    {
        $this->update(['onboarding_completed_at' => now()]);
    }

    /**
     * Check if the user is a coach.
     *
     * @deprecated Use UserRoleService::isCoach() instead
     */
    public function isCoach(): bool
    {
        return app(UserRoleService::class)->isCoach($this);
    }

    /**
     * Check if the user is a player.
     *
     * @deprecated Use UserRoleService::isPlayer() instead
     */
    public function isPlayer(): bool
    {
        return app(UserRoleService::class)->isPlayer($this);
    }

    /**
     * Check if the user is an admin.
     *
     * @deprecated Use UserRoleService::isAdmin() instead
     */
    public function isAdmin(): bool
    {
        return app(UserRoleService::class)->isAdmin($this);
    }

    /**
     * Check if the user is a club admin.
     *
     * @deprecated Use UserRoleService::isClubAdmin() instead
     */
    public function isClubAdmin(): bool
    {
        return app(UserRoleService::class)->isClubAdmin($this);
    }

    /**
     * Check if the user is a super admin.
     *
     * @deprecated Use UserRoleService::isSuperAdmin() instead
     */
    public function isSuperAdmin(): bool
    {
        return app(UserRoleService::class)->isSuperAdmin($this);
    }

    /**
     * Get clubs administered by this user.
     *
     * @deprecated Use UserRoleService::getAdministeredClubs() instead
     * @param bool $asQuery If true, returns query builder. If false, returns collection.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany|\Illuminate\Database\Eloquent\Collection
     */
    public function getAdministeredClubs(bool $asQuery = true)
    {
        return app(UserRoleService::class)->getAdministeredClubs($this, $asQuery);
    }

    /**
     * Get IDs of clubs administered by this user.
     *
     * @deprecated Use UserRoleService::getAdministeredClubIds() instead
     * @return array
     */
    public function getAdministeredClubIds(): array
    {
        return app(UserRoleService::class)->getAdministeredClubIds($this);
    }

    /**
     * Check if the user is a parent.
     *
     * @deprecated Use UserRoleService::isParent() instead
     */
    public function isParent(): bool
    {
        return app(UserRoleService::class)->isParent($this);
    }

    /**
     * Check if the user has access to a specific team.
     *
     * @deprecated Use UserRoleService::hasTeamAccess() instead
     */
    public function hasTeamAccess(Team $team, array $permissions = []): bool
    {
        return app(UserRoleService::class)->hasTeamAccess($this, $team, $permissions);
    }

    /**
     * Check if the user can coach a specific team.
     *
     * @deprecated Use UserRoleService::canCoachTeam() instead
     */
    public function canCoachTeam(Team $team): bool
    {
        return app(UserRoleService::class)->canCoachTeam($this, $team);
    }

    /**
     * Get the user's primary team (as player or coach).
     *
     * @deprecated Use UserRoleService::getPrimaryTeam() instead
     */
    public function getPrimaryTeam(): ?Team
    {
        return app(UserRoleService::class)->getPrimaryTeam($this);
    }

    /**
     * Get the date format for the model.
     */
    public function getDateFormat(): string
    {
        return 'Y-m-d H:i:s';
    }

    /**
     * Get basketball-specific user statistics.
     */
    public function getBasketballStats(): array
    {
        $stats = [
            'is_player' => $this->isPlayer(),
            'is_coach' => $this->isCoach(),
            'teams_count' => 0,
            'coached_teams_count' => 0,
            'seasons_active' => [],
        ];

        if ($this->isPlayer() && $this->playerProfile) {
            $stats['current_team'] = $this->playerProfile->team?->name;
            $stats['position'] = $this->playerProfile->position;
            $stats['jersey_number'] = $this->playerProfile->jersey_number;
        }

        if ($this->isCoach()) {
            $stats['coached_teams_count'] = $this->coachedTeams()->count() + $this->assistantCoachedTeams()->count();
        }

        return $stats;
    }

    /**
     * Update user's last login information.
     */
    public function updateLastLogin(string $ipAddress): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ipAddress,
        ]);
    }

    /**
     * Check if user has all required consents (for minors).
     */
    public function hasRequiredConsents(): bool
    {
        if (!$this->isPlayer() || !$this->playerProfile) {
            return true;
        }

        return $this->playerProfile->hasValidConsents();
    }

    // ============================
    // ACTIVITY LOG
    // ============================

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'is_active', 'language', 'player_profile_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // ============================
    // API & SUBSCRIPTION METHODS
    // ============================

    /**
     * Get user's current subscription or create a free one.
     *
     * @deprecated Use UserApiService::getSubscription() instead
     */
    public function getSubscription(): Subscription
    {
        return app(UserApiService::class)->getSubscription($this);
    }

    /**
     * Get effective API rate limits for this user.
     *
     * @deprecated Use UserApiService::getApiLimits() instead
     */
    public function getApiLimits(): array
    {
        return app(UserApiService::class)->getApiLimits($this);
    }

    /**
     * Check if user has exceeded their API quota.
     *
     * @deprecated Use UserApiService::hasExceededApiQuota() instead
     */
    public function hasExceededApiQuota(): bool
    {
        return app(UserApiService::class)->hasExceededApiQuota($this);
    }

    /**
     * Get current API usage for the user.
     *
     * @deprecated Use UserApiService::getCurrentApiUsage() instead
     */
    public function getCurrentApiUsage(): array
    {
        return app(UserApiService::class)->getCurrentApiUsage($this);
    }

    /**
     * Reset API quota (called hourly).
     *
     * @deprecated Use UserApiService::resetApiQuota() instead
     */
    public function resetApiQuota(): void
    {
        app(UserApiService::class)->resetApiQuota($this);
    }

    /**
     * Generate API key for user.
     *
     * @deprecated Use UserApiService::generateApiKey() instead
     */
    public function generateApiKey(): string
    {
        return app(UserApiService::class)->generateApiKey($this);
    }

    /**
     * Verify API key.
     *
     * @deprecated Use UserApiService::verifyApiKey() instead
     */
    public function verifyApiKey(string $apiKey): bool
    {
        return app(UserApiService::class)->verifyApiKey($this, $apiKey);
    }

    /**
     * Update API key last used timestamp.
     *
     * @deprecated Use UserApiService::updateApiKeyUsage() instead
     */
    public function updateApiKeyUsage(): void
    {
        app(UserApiService::class)->updateApiKeyUsage($this);
    }

    /**
     * Revoke API key.
     *
     * @deprecated Use UserApiService::revokeApiKey() instead
     */
    public function revokeApiKey(): void
    {
        app(UserApiService::class)->revokeApiKey($this);
    }

    /**
     * Get user's API usage statistics.
     *
     * @deprecated Use UserApiService::getApiUsageStats() instead
     */
    public function getApiUsageStats(string $period = 'last_30_days'): array
    {
        return app(UserApiService::class)->getApiUsageStats($this, $period);
    }

    /**
     * Check if user can access a specific API feature.
     *
     * @deprecated Use UserApiService::canAccessApiFeature() instead
     */
    public function canAccessApiFeature(string $feature): bool
    {
        return app(UserApiService::class)->canAccessApiFeature($this, $feature);
    }

    /**
     * Get subscription tier display name.
     *
     * @deprecated Use UserApiService::getSubscriptionTierName() instead
     */
    public function getSubscriptionTierName(): string
    {
        return app(UserApiService::class)->getSubscriptionTierName($this);
    }

    /**
     * Check if user is on premium tier or higher.
     *
     * @deprecated Use UserApiService::isPremiumUser() instead
     */
    public function isPremiumUser(): bool
    {
        return app(UserApiService::class)->isPremiumUser($this);
    }

    /**
     * Check if user is on enterprise tier or higher.
     *
     * @deprecated Use UserApiService::isEnterpriseUser() instead
     */
    public function isEnterpriseUser(): bool
    {
        return app(UserApiService::class)->isEnterpriseUser($this);
    }

    /**
     * Calculate overage costs for current usage.
     *
     * @deprecated Use UserApiService::calculateOverageCosts() instead
     */
    public function calculateOverageCosts(): float
    {
        return app(UserApiService::class)->calculateOverageCosts($this);
    }
}
