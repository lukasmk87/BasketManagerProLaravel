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
        'league',
        'division',
        'season',
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
     * Get club players through teams.
     */
    public function players()
    {
        return $this->hasManyThrough(Player::class, Team::class);
    }

    /**
     * Get games involving this club's teams.
     */
    public function games()
    {
        return Game::whereIn('home_team_id', $this->teams()->pluck('id'))
            ->orWhereIn('away_team_id', $this->teams()->pluck('id'));
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

    /**
     * Scope a query to filter by league.
     */
    public function scopeInLeague($query, string $league)
    {
        return $query->where('league', $league);
    }

    /**
     * Scope a query to filter by division.
     */
    public function scopeInDivision($query, string $division)
    {
        return $query->where('division', $division);
    }

    /**
     * Scope a query to filter by season.
     */
    public function scopeInSeason($query, string $season)
    {
        return $query->where('season', $season);
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
            'total_games' => $this->games()->count(),
            'games_won' => $this->games()->where('result', 'home_win')->count() + 
                          $this->games()->where('result', 'away_win')->count(),
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
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/svg+xml']);

        $this->addMediaCollection('gallery')
            ->acceptsMimeTypes(['image/jpeg', 'image/png']);

        $this->addMediaCollection('documents')
            ->acceptsMimeTypes(['application/pdf', 'application/msword']);
    }

    /**
     * Register media conversions.
     */
    public function registerMediaConversions(Media $media = null): void
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
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 'short_name', 'email', 'phone', 'website',
                'is_active', 'is_verified', 'league', 'division'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // ============================
    // ROUTE MODEL BINDING
    // ============================

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}