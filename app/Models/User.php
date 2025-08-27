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
        'name',
        'email',
        'password',
        'phone',
        'birth_date',
        'gender',
        'bio',
        'timezone',
        'language',
        'avatar_path',
        'preferences',
        'notification_settings',
        'player_profile_active',
        'coaching_certifications',
        'last_login_at',
        'last_login_ip',
        'is_active',
        'subscription_tier',
        'api_quota_reset_at',
        'current_api_usage',
        'api_key_hash',
        'api_access_enabled',
        'api_key_last_used_at',
        'rate_limit_cache',
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
        ];
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
                    ->whereHas('playerProfile');
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
     * Check if the user is a coach.
     */
    public function isCoach(): bool
    {
        return $this->hasAnyRole(['trainer', 'club_admin', 'admin']);
    }

    /**
     * Check if the user is a player.
     */
    public function isPlayer(): bool
    {
        return $this->player_profile_active && $this->playerProfile()->exists();
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if the user is a club admin.
     */
    public function isClubAdmin(): bool
    {
        return $this->hasRole('club_admin');
    }

    /**
     * Check if the user is a parent.
     */
    public function isParent(): bool
    {
        // Check if user has the parent role OR has children
        return $this->hasRole('parent') || $this->children()->exists();
    }

    /**
     * Check if the user has access to a specific team.
     */
    public function hasTeamAccess(Team $team, array $permissions = []): bool
    {
        // Admin has access to everything
        if ($this->hasRole('admin')) {
            return true;
        }

        // Club admin has access to all teams in their clubs
        if ($this->hasRole('club_admin')) {
            return $this->clubs()->where('clubs.id', $team->club_id)->exists();
        }

        // Coach has access to their teams
        if ($team->head_coach_id === $this->id || in_array($this->id, $team->assistant_coaches ?? [])) {
            return true;
        }

        // Player has access to their own team
        if ($this->playerProfile && $this->playerProfile->team_id === $team->id) {
            return true;
        }

        return false;
    }

    /**
     * Check if the user can coach a specific team.
     */
    public function canCoachTeam(Team $team): bool
    {
        return $this->isCoach() && $this->hasTeamAccess($team);
    }

    /**
     * Get the user's primary team (as player or coach).
     */
    public function getPrimaryTeam(): ?Team
    {
        // If user is a player, return their team
        if ($this->isPlayer()) {
            return $this->playerProfile->team;
        }

        // If user is a coach, return their first coached team
        if ($this->isCoach()) {
            return $this->coachedTeams()->first() ?? $this->assistantCoachedTeams()->first();
        }

        return null;
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
     * Get user's current subscription or create a free one
     */
    public function getSubscription(): Subscription
    {
        $subscription = $this->subscription;
        
        if (!$subscription) {
            $subscription = $this->subscription()->create([
                'tier' => 'free',
                'plan_name' => 'Free Plan',
                'status' => 'active',
                'api_requests_limit' => 1000,
                'burst_limit' => 100,
                'concurrent_requests_limit' => 10,
            ]);
        }
        
        return $subscription;
    }

    /**
     * Get effective API rate limits for this user
     */
    public function getApiLimits(): array
    {
        $subscription = $this->getSubscription();
        $baseLimits = $subscription->getApiLimits();
        
        // Check for active rate limit exceptions
        $exceptions = RateLimitException::findActiveFor('user', $this->id);
        
        foreach ($exceptions as $exception) {
            $baseLimits = $exception->getEffectiveLimits($baseLimits);
        }
        
        return $baseLimits;
    }

    /**
     * Check if user has exceeded their API quota
     */
    public function hasExceededApiQuota(): bool
    {
        $limits = $this->getApiLimits();
        $usage = $this->getCurrentApiUsage();
        
        return $usage['total_requests'] >= $limits['requests_per_hour'];
    }

    /**
     * Get current API usage for the user
     */
    public function getCurrentApiUsage(): array
    {
        return ApiUsageTracking::getCurrentWindowUsage($this->id, null, 'hourly');
    }

    /**
     * Reset API quota (called hourly)
     */
    public function resetApiQuota(): void
    {
        $this->current_api_usage = 0;
        $this->api_quota_reset_at = now()->addHour();
        $this->save();
    }

    /**
     * Generate API key for user
     */
    public function generateApiKey(): string
    {
        $apiKey = 'bmp_' . bin2hex(random_bytes(20)); // BasketManager Pro prefix
        $this->api_key_hash = hash('sha256', $apiKey);
        $this->api_access_enabled = true;
        $this->save();
        
        return $apiKey;
    }

    /**
     * Verify API key
     */
    public function verifyApiKey(string $apiKey): bool
    {
        if (!$this->api_access_enabled) {
            return false;
        }
        
        $hashedKey = hash('sha256', $apiKey);
        return $this->api_key_hash === $hashedKey;
    }

    /**
     * Update API key last used timestamp
     */
    public function updateApiKeyUsage(): void
    {
        $this->api_key_last_used_at = now();
        $this->save();
    }

    /**
     * Revoke API key
     */
    public function revokeApiKey(): void
    {
        $this->api_key_hash = null;
        $this->api_access_enabled = false;
        $this->api_key_last_used_at = null;
        $this->save();
    }

    /**
     * Get user's API usage statistics
     */
    public function getApiUsageStats(string $period = 'last_30_days'): array
    {
        $start = match($period) {
            'today' => now()->startOfDay(),
            'yesterday' => now()->subDay()->startOfDay(),
            'last_7_days' => now()->subDays(7),
            'last_30_days' => now()->subDays(30),
            'current_month' => now()->startOfMonth(),
            'last_month' => now()->subMonth()->startOfMonth(),
            default => now()->subDays(30),
        };
        
        $end = match($period) {
            'yesterday' => now()->subDay()->endOfDay(),
            'last_month' => now()->subMonth()->endOfMonth(),
            default => now(),
        };
        
        return ApiUsageTracking::getUserUsageSummary($this->id, $start, $end);
    }

    /**
     * Check if user can access a specific API feature
     */
    public function canAccessApiFeature(string $feature): bool
    {
        $subscription = $this->getSubscription();
        return $subscription->hasFeature($feature);
    }

    /**
     * Get subscription tier display name
     */
    public function getSubscriptionTierName(): string
    {
        return match($this->subscription_tier) {
            'free' => 'Free',
            'basic' => 'Basic',
            'premium' => 'Premium', 
            'enterprise' => 'Enterprise',
            'unlimited' => 'Unlimited',
            default => 'Free',
        };
    }

    /**
     * Check if user is on premium tier or higher
     */
    public function isPremiumUser(): bool
    {
        return in_array($this->subscription_tier, ['premium', 'enterprise', 'unlimited']);
    }

    /**
     * Check if user is on enterprise tier or higher
     */
    public function isEnterpriseUser(): bool
    {
        return in_array($this->subscription_tier, ['enterprise', 'unlimited']);
    }

    /**
     * Calculate overage costs for current usage
     */
    public function calculateOverageCosts(): float
    {
        $limits = $this->getApiLimits();
        $usage = $this->getCurrentApiUsage();
        
        $excessRequests = max(0, $usage['total_requests'] - $limits['requests_per_hour']);
        
        if ($excessRequests > 0) {
            $subscription = $this->getSubscription();
            return $subscription->calculateOverageCost($excessRequests);
        }
        
        return 0.0;
    }
}
