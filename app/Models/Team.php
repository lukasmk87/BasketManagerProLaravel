<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Jetstream\Events\TeamCreated;
use Laravel\Jetstream\Events\TeamDeleted;
use Laravel\Jetstream\Events\TeamUpdated;
use Laravel\Jetstream\Jetstream;
use Laravel\Jetstream\Team as JetstreamTeam;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Laravel\Scout\Searchable;

class Team extends JetstreamTeam implements HasMedia
{
    use HasFactory, SoftDeletes, LogsActivity, InteractsWithMedia, Searchable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        // Jetstream fields
        'name',
        'personal_team',
        'user_id',
        
        // Basketball-specific fields
        'uuid',
        'club_id',
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
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
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
            'personal_team' => 'boolean',
        ]);
    }

    /**
     * The event map for the model.
     */
    protected $dispatchesEvents = [
        'created' => TeamCreated::class,
        'updated' => TeamUpdated::class,
        'deleted' => TeamDeleted::class,
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
            if (empty($team->slug) && !empty($team->name)) {
                $team->slug = Str::slug($team->name);
            }
        });
    }

    // ============================
    // BASKETBALL RELATIONSHIPS
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
    public function players(): HasMany
    {
        return $this->hasMany(Player::class, 'team_id');
    }

    /**
     * Get the active players on this team.
     */
    public function activePlayers(): HasMany
    {
        return $this->players()->where('status', 'active');
    }

    /**
     * Get the starting players on this team.
     */
    public function starters(): HasMany
    {
        return $this->players()->where('is_starter', true);
    }

    /**
     * Get the team captains.
     */
    public function captains(): HasMany
    {
        return $this->players()->where('is_captain', true);
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

    /**
     * Get all games for this team (home and away).
     */
    public function games()
    {
        return Game::where('home_team_id', $this->id)
                   ->orWhere('away_team_id', $this->id);
    }

    /**
     * Get all games for this team (home and away) - alias for backward compatibility.
     */
    public function allGames()
    {
        return $this->games();
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

    /**
     * Scope to exclude personal teams.
     */
    public function scopeNotPersonal($query)
    {
        return $query->where('personal_team', false);
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

    /**
     * Get available roster spots.
     */
    public function getPlayersSlotsAvailableAttribute(): int
    {
        return max(0, $this->max_players - $this->current_roster_size);
    }

    /**
     * Get average player age.
     */
    public function getAveragePlayerAgeAttribute(): ?float
    {
        return $this->activePlayers()
                   ->whereNotNull('birth_date')
                   ->get()
                   ->avg(fn($player) => $player->user?->birth_date?->age);
    }

    // ============================
    // HELPER METHODS
    // ============================

    /**
     * Add a player to this team.
     */
    public function addPlayer(User $user, array $playerData = []): Player
    {
        $player = Player::create(array_merge([
            'user_id' => $user->id,
            'team_id' => $this->id,
        ], $playerData));

        return $player;
    }

    /**
     * Remove a player from this team.
     */
    public function removePlayer(Player $player): void
    {
        $player->update(['team_id' => null, 'status' => 'inactive']);
    }

    /**
     * Update player count cache.
     */
    public function updatePlayerCount(): void
    {
        // This method exists for compatibility with observers
        // The current_roster_size is calculated dynamically
    }

    /**
     * Check if team can accept new players.
     */
    public function canAcceptNewPlayer(): bool
    {
        return $this->is_recruiting && 
               $this->is_active && 
               $this->current_roster_size < $this->max_players;
    }

    // ============================
    // JETSTREAM COMPATIBILITY
    // ============================

    /**
     * Determine if the given user belongs to the team.
     */
    public function hasUser($user): bool
    {
        if ($user instanceof User) {
            // Check if user is a player on this team
            if ($user->playerProfile && $user->playerProfile->team_id === $this->id) {
                return true;
            }
            
            // Check if user is coach of this team
            if ($this->head_coach_id === $user->id) {
                return true;
            }
        }

        return parent::hasUser($user);
    }

    /**
     * Get all of the users that belong to the team.
     */
    public function allUsers()
    {
        // Get Jetstream team members
        $jetstreamUsers = parent::allUsers();
        
        // Get basketball team members (players, coaches)
        $basketballUsers = collect();
        
        // Add players
        $players = $this->players()->with('user')->get();
        foreach ($players as $player) {
            if ($player->user) {
                $basketballUsers->push($player->user);
            }
        }
        
        // Add head coach
        if ($this->headCoach) {
            $basketballUsers->push($this->headCoach);
        }
        
        // Merge and deduplicate
        return $jetstreamUsers->merge($basketballUsers)->unique('id');
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
    }

    // ============================
    // SCOUT SEARCH
    // ============================

    /**
     * Get the indexable data array for the model.
     */
    public function toSearchableArray(): array
    {
        return [
            'name' => $this->name,
            'short_name' => $this->short_name,
            'gender' => $this->gender,
            'age_group' => $this->age_group,
            'season' => $this->season,
            'league' => $this->league,
            'club_name' => $this->club?->name,
            'status' => $this->status,
        ];
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