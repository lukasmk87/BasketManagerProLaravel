<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Club extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, LogsActivity, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uuid',
        'club_subscription_plan_id',
        'name',
        'short_name',
        'slug',
        'description',
        'logo_path',
        'website',
        'email',
        'phone',
        'address_street',
        'address_city',
        'address_state',
        'address_zip',
        'address_country',
        'primary_color',
        'secondary_color',
        'accent_color',
        'settings',
        'preferences',
        'is_active',
        'is_verified',
        'verified_at',
        'founded_at',
        'default_language',
        'supported_languages',
        'facilities',
        'social_links',
        'membership_fee',
        'currency',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_email',
        'privacy_policy_updated_at',
        'terms_updated_at',
        'gdpr_compliant',
        // New attributes from migration
        'president_name',
        'president_email',
        'vice_president_name',
        'secretary_name',
        'treasurer_name',
        'contact_person_name',
        'contact_person_phone',
        'contact_person_email',
        'has_indoor_courts',
        'has_outdoor_courts',
        'court_count',
        'equipment_available',
        'training_times',
        'offers_youth_programs',
        'offers_adult_programs',
        'accepts_new_members',
        'requires_approval',
        'membership_fee_annual',
        'membership_fee_monthly',
        'social_media_facebook',
        'social_media_instagram',
        'social_media_twitter',
        'privacy_policy_url',
        'terms_of_service_url',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'uuid' => 'string',
        'settings' => 'array',
        'preferences' => 'array',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'founded_at' => 'datetime',
        'supported_languages' => 'array',
        'facilities' => 'array',
        'social_links' => 'array',
        'membership_fee' => 'decimal:2',
        'privacy_policy_updated_at' => 'datetime',
        'terms_updated_at' => 'datetime',
        'gdpr_compliant' => 'boolean',
        // New casts from migration
        'has_indoor_courts' => 'boolean',
        'has_outdoor_courts' => 'boolean',
        'court_count' => 'integer',
        'training_times' => 'array',
        'offers_youth_programs' => 'boolean',
        'offers_adult_programs' => 'boolean',
        'accepts_new_members' => 'boolean',
        'requires_approval' => 'boolean',
        'membership_fee_annual' => 'decimal:2',
        'membership_fee_monthly' => 'decimal:2',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        // Sensitive information
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($club) {
            if (empty($club->uuid)) {
                $club->uuid = (string) Str::uuid();
            }
            if (empty($club->slug)) {
                $club->slug = Str::slug($club->name);
            }
        });
    }

    // ============================
    // RELATIONSHIPS
    // ============================

    /**
     * Get the teams belonging to this club.
     */
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    /**
     * Get the users that belong to this club.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot([
                'role', 'joined_at', 'membership_expires_at', 'is_active',
                'is_verified', 'membership_number', 'membership_type',
                'membership_fee_paid', 'last_payment_date', 'payment_status',
                'permissions', 'restricted_areas', 'receive_newsletters',
                'receive_game_notifications', 'receive_emergency_alerts',
                'notes', 'metadata'
            ])
            ->withTimestamps();
    }

    /**
     * Get active users of this club.
     */
    public function activeUsers(): BelongsToMany
    {
        return $this->users()->wherePivot('is_active', true);
    }

    /**
     * Get club administrators.
     */
    public function administrators(): BelongsToMany
    {
        return $this->users()->wherePivot('role', 'admin');
    }

    /**
     * Get club owners.
     */
    public function owners(): BelongsToMany
    {
        return $this->users()->wherePivot('role', 'owner');
    }

    /**
     * Get club coaches.
     */
    public function coaches(): BelongsToMany
    {
        return $this->users()->whereIn('role', ['coach', 'assistant_coach']);
    }

    /**
     * Get club players through teams via pivot table.
     */
    public function players()
    {
        return $this->hasManyThrough(
            Player::class,
            Team::class,
            'club_id',     // Foreign key on teams table
            'id',          // Foreign key on players table  
            'id',          // Local key on clubs table
            'id'           // Local key on teams table
        )->join('player_team', function ($join) {
            $join->on('players.id', '=', 'player_team.player_id')
                 ->on('teams.id', '=', 'player_team.team_id');
        })->where('player_team.is_active', true);
    }

    /**
     * Get games involving this club's teams.
     */
    public function getGames()
    {
        $teamIds = $this->teams()->pluck('id')->toArray();
        if (empty($teamIds)) {
            return Game::whereRaw('1 = 0'); // Empty result set
        }
        return Game::whereIn('home_team_id', $teamIds)
            ->orWhereIn('away_team_id', $teamIds);
    }

    /**
     * Get gym halls belonging to this club.
     */
    public function gymHalls(): HasMany
    {
        return $this->hasMany(GymHall::class);
    }

    /**
     * Get active gym halls belonging to this club.
     */
    public function activeGymHalls(): HasMany
    {
        return $this->gymHalls()->where('is_active', true);
    }

    /**
     * Get the subscription plan for this club.
     */
    public function subscriptionPlan()
    {
        return $this->belongsTo(ClubSubscriptionPlan::class, 'club_subscription_plan_id');
    }

    /**
     * Get the tenant this club belongs to.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    // ============================
    // SCOPES
    // ============================

    /**
     * Scope a query to only include active clubs.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include verified clubs.
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }


    // ============================
    // ACCESSORS & MUTATORS
    // ============================

    /**
     * Get the club's full address.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address_street,
            $this->address_zip . ' ' . $this->address_city,
            $this->address_state,
            $this->address_country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get the club's display name (short name if available, otherwise full name).
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->short_name ?: $this->name;
    }

    /**
     * Get the club's logo URL.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if ($this->logo_path) {
            return asset('storage/' . $this->logo_path);
        }

        // Try to get logo from media library
        $logoMedia = $this->getFirstMedia('logo');
        return $logoMedia ? $logoMedia->getUrl() : null;
    }

    /**
     * Check if the club is GDPR compliant.
     */
    public function getIsGdprCompliantAttribute(): bool
    {
        return $this->gdpr_compliant && 
               $this->privacy_policy_updated_at && 
               $this->terms_updated_at;
    }

    /**
     * Get the club's age in years.
     */
    public function getAgeAttribute(): ?int
    {
        return $this->founded_at ? $this->founded_at->diffInYears(now()) : null;
    }

    /**
     * Get the total number of teams.
     */
    public function getTeamsCountAttribute(): int
    {
        return $this->teams()->count();
    }

    /**
     * Get the total number of active players.
     */
    public function getPlayersCountAttribute(): int
    {
        return $this->players()->whereHas('user', function ($query) {
            $query->where('is_active', true);
        })->count();
    }

    /**
     * Set the slug attribute.
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;
        if (empty($this->attributes['slug'])) {
            $this->attributes['slug'] = Str::slug($value);
        }
    }

    // ============================
    // HELPER METHODS
    // ============================

    /**
     * Check if a user is a member of this club.
     */
    public function hasMember(User $user): bool
    {
        return $this->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Check if a user has a specific role in this club.
     */
    public function userHasRole(User $user, string $role): bool
    {
        return $this->users()
            ->where('user_id', $user->id)
            ->wherePivot('role', $role)
            ->exists();
    }

    /**
     * Add a user to this club with a specific role.
     */
    public function addMember(User $user, string $role = 'member', array $additionalData = []): void
    {
        $data = array_merge([
            'role' => $role,
            'joined_at' => now(),
            'is_active' => true,
        ], $additionalData);

        $this->users()->syncWithoutDetaching([$user->id => $data]);
    }

    /**
     * Remove a user from this club.
     */
    public function removeMember(User $user): void
    {
        $this->users()->detach($user->id);
    }

    /**
     * Get club statistics.
     */
    public function getStatistics(): array
    {
        return [
            'total_teams' => $this->teams_count,
            'total_players' => $this->players_count,
            'total_coaches' => $this->coaches()->count(),
            'total_games' => $this->getGames()->count(),
            'games_won' => $this->getGames()->where('result', 'home_win')->count() + 
                          $this->getGames()->where('result', 'away_win')->count(),
            'founded_years_ago' => $this->age,
            'is_active' => $this->is_active,
            'is_verified' => $this->is_verified,
        ];
    }

    /**
     * Check if club can accept new members.
     */
    public function canAcceptNewMembers(): bool
    {
        return $this->is_active && $this->is_verified;
    }

    /**
     * Generate QR code data for emergency access.
     */
    public function generateEmergencyQRData(): array
    {
        return [
            'type' => 'club_emergency',
            'club_id' => $this->id,
            'club_name' => $this->name,
            'emergency_contact' => $this->emergency_contact_name,
            'emergency_phone' => $this->emergency_contact_phone,
            'generated_at' => now()->toISOString(),
        ];
    }

    // ============================
    // MEDIA LIBRARY
    // ============================

    /**
     * Register media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->singleFile()
            ->useDisk('public')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/svg+xml', 'image/jpg']);

        $this->addMediaCollection('gallery')
            ->useDisk('public')
            ->acceptsMimeTypes(['image/jpeg', 'image/png']);

        $this->addMediaCollection('documents')
            ->useDisk('public')
            ->acceptsMimeTypes(['application/pdf', 'application/msword']);
    }

    /**
     * Register media conversions.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(150)
            ->height(150)
            ->sharpen(10);

        $this->addMediaConversion('logo-small')
            ->width(100)
            ->height(100)
            ->performOnCollections('logo');
    }

    // ============================
    // ACTIVITY LOG
    // ============================

    /**
     * Get the date format for the model.
     */
    public function getDateFormat(): string
    {
        return 'Y-m-d H:i:s';
    }

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 'short_name', 'email', 'phone', 'website',
                'is_active', 'is_verified'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // ============================
    // LEGACY COMPATIBILITY ACCESSORS
    // ============================
    
    /**
     * Legacy compatibility accessors for ClubService
     */
    public function getCityAttribute(): ?string
    {
        return $this->address_city;
    }
    
    public function getStateAttribute(): ?string
    {
        return $this->address_state;
    }
    
    public function getPostalCodeAttribute(): ?string
    {
        return $this->address_zip;
    }
    
    public function getCountryAttribute(): ?string
    {
        return $this->address_country;
    }
    
    public function getAddressAttribute(): ?string
    {
        return $this->address_street;
    }
    
    public function getColorsPrimaryAttribute(): ?string
    {
        return $this->primary_color;
    }
    
    public function getColorsSecondaryAttribute(): ?string
    {
        return $this->secondary_color;
    }

    // ============================
    // SUBSCRIPTION & FEATURE METHODS
    // ============================

    /**
     * Check if club has a specific feature (considering tenant hierarchy).
     */
    public function hasFeature(string $feature): bool
    {
        // Get tenant (try relationship first, fallback to app tenant)
        $tenant = $this->tenant ?? (app()->bound('tenant') ? app('tenant') : null);

        // 1. Tenant must have the feature
        if (!$tenant || !$tenant->hasFeature($feature)) {
            return false;
        }

        // 2. If club has no subscription plan -> inherit tenant features
        if (!$this->club_subscription_plan_id || !$this->subscriptionPlan) {
            return true;
        }

        // 3. If club has a plan -> check club plan features
        return $this->subscriptionPlan->hasFeature($feature);
    }

    /**
     * Get limit for a specific metric (considering tenant hierarchy).
     */
    public function getLimit(string $metric): int
    {
        $tenant = $this->tenant ?? (app()->bound('tenant') ? app('tenant') : null);

        if (!$tenant) {
            return 0;
        }

        $tenantLimit = $tenant->getTierLimits()[$metric] ?? -1;

        // If tenant has unlimited
        if ($tenantLimit === -1) {
            if (!$this->subscriptionPlan) {
                return -1;
            }
            return $this->subscriptionPlan->getLimit($metric);
        }

        // If club has no plan -> use tenant limit
        if (!$this->subscriptionPlan) {
            return $tenantLimit;
        }

        // Return minimum of tenant and club limits
        $clubLimit = $this->subscriptionPlan->getLimit($metric);
        return min($tenantLimit, $clubLimit === -1 ? $tenantLimit : $clubLimit);
    }

    /**
     * Check if club can use a resource based on limits.
     */
    public function canUse(string $metric, int $amount = 1): bool
    {
        $limit = $this->getLimit($metric);

        if ($limit === -1) {
            return true; // Unlimited
        }

        $currentUsage = $this->getCurrentUsage($metric);
        return ($currentUsage + $amount) <= $limit;
    }

    /**
     * Get current usage for a metric.
     */
    protected function getCurrentUsage(string $metric): int
    {
        return match($metric) {
            'max_teams' => $this->teams()->count(),
            'max_players' => $this->players()->count(),
            'max_storage_gb' => $this->calculateStorageUsage(),
            default => 0,
        };
    }

    /**
     * Calculate storage usage in GB (placeholder - implement based on your needs).
     */
    protected function calculateStorageUsage(): float
    {
        // TODO: Implement actual storage calculation
        // This could sum up media file sizes, etc.
        return 0.0;
    }

    /**
     * Get subscription limits for this club.
     */
    public function getSubscriptionLimits(): array
    {
        $metrics = ['max_teams', 'max_players', 'max_storage_gb', 'max_games_per_month'];
        $limits = [];

        foreach ($metrics as $metric) {
            $limit = $this->getLimit($metric);
            $limits[$metric] = [
                'limit' => $limit,
                'current' => $this->getCurrentUsage($metric),
                'unlimited' => $limit === -1,
                'percentage' => $limit > 0 ? min(100, ($this->getCurrentUsage($metric) / $limit) * 100) : 0,
            ];
        }

        return $limits;
    }

    /**
     * Assign a subscription plan to this club.
     */
    public function assignPlan(ClubSubscriptionPlan $plan): void
    {
        // Validate that plan belongs to same tenant
        if ($plan->tenant_id !== $this->tenant_id) {
            throw new \Exception("Plan does not belong to club's tenant");
        }

        $this->update(['club_subscription_plan_id' => $plan->id]);
    }

    /**
     * Remove subscription plan (club will use tenant features).
     */
    public function removePlan(): void
    {
        $this->update(['club_subscription_plan_id' => null]);
    }

    // ============================
    // ROUTE MODEL BINDING
    // ============================
}