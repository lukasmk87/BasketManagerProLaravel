<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class BasketballTeam extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, LogsActivity, InteractsWithMedia;

    protected $table = 'teams';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uuid',
        'club_id',
        'name',
        'short_name',
        'slug',
        'description',
        'logo_path',
        'gender',
        'age_group',
        'division',
        'league',
        'season',
        'season_start',
        'season_end',
        'primary_color',
        'secondary_color',
        'jersey_home_color',
        'jersey_away_color',
        'max_players',
        'min_players',
        'training_schedule',
        'practice_times',
        'head_coach_id',
        'assistant_coaches',
        'games_played',
        'games_won',
        'games_lost',
        'games_tied',
        'points_scored',
        'points_allowed',
        'preferences',
        'settings',
        'is_active',
        'is_recruiting',
        'status',
        'home_venue',
        'home_venue_address',
        'venue_details',
        'registration_number',
        'is_certified',
        'certified_at',
        'emergency_contacts',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'uuid' => 'string',
        'season_start' => 'date',
        'season_end' => 'date',
        'max_players' => 'integer',
        'min_players' => 'integer',
        'training_schedule' => 'array',
        'practice_times' => 'array',
        'assistant_coaches' => 'array',
        'games_played' => 'integer',
        'games_won' => 'integer',
        'games_lost' => 'integer',
        'games_tied' => 'integer',
        'points_scored' => 'integer',
        'points_allowed' => 'integer',
        'preferences' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
        'is_recruiting' => 'boolean',
        'venue_details' => 'array',
        'is_certified' => 'boolean',
        'certified_at' => 'datetime',
        'emergency_contacts' => 'array',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($team) {
            if (empty($team->uuid)) {
                $team->uuid = (string) Str::uuid();
            }
            if (empty($team->slug)) {
                $team->slug = Str::slug($team->name);
            }
        });
    }

    // ============================
    // RELATIONSHIPS
    // ============================

    /**
     * Get the club that owns this team.
     */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * Get the head coach of this team.
     */
    public function headCoach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_coach_id');
    }

    /**
     * Get the players on this team.
     */
    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'player_team')
            ->withPivot([
                'jersey_number', 'primary_position', 'secondary_positions',
                'is_active', 'is_starter', 'is_captain', 'status',
                'joined_at', 'left_at', 'contract_start', 'contract_end',
                'registration_number', 'is_registered', 'registered_at',
                'games_played', 'games_started', 'minutes_played', 'points_scored',
                'notes', 'metadata'
            ])
            ->withTimestamps();
    }

    /**
     * Get the active players on this team.
     */
    public function activePlayers(): BelongsToMany
    {
        return $this->players()->wherePivot('is_active', true);
    }

    /**
     * Get the starting players on this team.
     */
    public function starters(): BelongsToMany
    {
        return $this->players()->wherePivot('is_starter', true)->wherePivot('is_active', true);
    }

    /**
     * Get the team captains.
     */
    public function captains(): BelongsToMany
    {
        return $this->players()->wherePivot('is_captain', true)->wherePivot('is_active', true);
    }

    /**
     * Get all users associated with this team.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_user')
            ->withPivot([
                'role', 'joined_at', 'left_at', 'is_active',
                'jersey_number', 'is_starter', 'is_captain',
                'coaching_license', 'coaching_certifications', 'coaching_specialties',
                'responsibilities', 'access_permissions',
                'contract_start', 'contract_end', 'salary', 'contract_terms',
                'performance_metrics', 'notes', 'performance_rating',
                'emergency_contact_name', 'emergency_contact_phone'
            ])
            ->withTimestamps();
    }

    /**
     * Get coaches associated with this team.
     */
    public function coaches(): BelongsToMany
    {
        return $this->users()->whereIn('role', ['head_coach', 'assistant_coach']);
    }

    /**
     * Get assistant coaches.
     */
    public function assistantCoaches(): BelongsToMany
    {
        return $this->users()->wherePivot('role', 'assistant_coach');
    }

    /**
     * Get team staff (managers, trainers, etc.).
     */
    public function staff(): BelongsToMany
    {
        return $this->users()->whereIn('role', ['manager', 'trainer', 'scout', 'statistician']);
    }

    /**
     * Get home games for this team.
     */
    public function homeGames(): HasMany
    {
        return $this->hasMany(Game::class, 'home_team_id');
    }

    /**
     * Get away games for this team.
     */
    public function awayGames(): HasMany
    {
        return $this->hasMany(Game::class, 'away_team_id');
    }

    // ============================
    // SCOPES
    // ============================

    /**
     * Scope a query to only include active teams.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by gender.
     */
    public function scopeByGender($query, string $gender)
    {
        return $query->where('gender', $gender);
    }

    /**
     * Scope a query to filter by age group.
     */
    public function scopeByAgeGroup($query, string $ageGroup)
    {
        return $query->where('age_group', $ageGroup);
    }

    /**
     * Scope a query to filter by league.
     */
    public function scopeInLeague($query, string $league)
    {
        return $query->where('league', $league);
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
     * Get the team's display name.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->short_name ?: $this->name;
    }

    /**
     * Get the team's win percentage.
     */
    public function getWinPercentageAttribute(): float
    {
        if ($this->games_played === 0) {
            return 0.0;
        }

        return round(($this->games_won / $this->games_played) * 100, 1);
    }

    /**
     * Get the current roster size.
     */
    public function getCurrentRosterSizeAttribute(): int
    {
        return $this->activePlayers()->count();
    }

    // ============================
    // HELPER METHODS
    // ============================

    /**
     * Add a player to this team.
     */
    public function addPlayer(Player $player, array $pivotData = []): void
    {
        // Set default pivot values
        $defaultPivotData = [
            'is_active' => true,
            'joined_at' => now(),
            'status' => 'active',
        ];

        // Merge with provided data
        $pivotData = array_merge($defaultPivotData, $pivotData);

        // Attach player to team with pivot data
        $this->players()->attach($player->id, $pivotData);
    }

    /**
     * Remove a player from this team.
     */
    public function removePlayer(Player $player): void
    {
        // Update pivot to mark as inactive and set left_at date
        $this->players()->updateExistingPivot($player->id, [
            'is_active' => false,
            'left_at' => now(),
            'status' => 'inactive',
        ]);
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
                'name', 'status', 'is_active', 'head_coach_id',
                'league', 'division', 'season'
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