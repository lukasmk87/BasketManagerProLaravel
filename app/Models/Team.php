<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
     * Get the assistant coaches of this team.
     */
    public function assistantCoaches()
    {
        if (!$this->assistant_coaches) {
            return collect();
        }

        return User::whereIn('id', $this->assistant_coaches)->get();
    }

    /**
     * Get all coaches from the dedicated team_coaches table.
     *
     * This relationship supports multi-role users
     * (e.g., a player-coach can be both a player and a coach).
     */
    public function teamCoaches(): HasMany
    {
        return $this->hasMany(TeamCoach::class, 'team_id');
    }

    /**
     * Get the head coach from the team_coaches table.
     */
    public function teamHeadCoach(): HasOne
    {
        return $this->hasOne(TeamCoach::class, 'team_id')
            ->where('role', 'head_coach')
            ->where('is_active', true);
    }

    /**
     * Get assistant coaches from the team_coaches table.
     */
    public function teamAssistantCoaches(): HasMany
    {
        return $this->hasMany(TeamCoach::class, 'team_id')
            ->where('role', 'assistant_coach')
            ->where('is_active', true);
    }

    /**
     * Get all members of this team (Jetstream compatibility).
     * This includes players, coaches, and staff.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_user')
            ->withPivot([
                'role',
                'joined_at',
                'left_at',
                'is_active',
                'jersey_number',
                'is_starter',
                'is_captain',
                'coaching_license',
                'coaching_certifications',
                'coaching_specialties',
                'responsibilities',
                'access_permissions',
                'contract_start',
                'contract_end',
                'salary',
                'contract_terms',
                'performance_metrics',
                'notes',
                'performance_rating',
                'emergency_contact_name',
                'emergency_contact_phone'
            ])
            ->withTimestamps();
    }

    /**
     * Get active members of this team.
     */
    public function activeMembers(): BelongsToMany
    {
        return $this->members()->wherePivot('is_active', true);
    }

    /**
     * Get members by role.
     */
    public function membersByRole(string $role): BelongsToMany
    {
        return $this->members()->wherePivot('role', $role);
    }

    /**
     * Get all coaches (head coach and assistant coaches) through members relationship.
     */
    public function coachMembers(): BelongsToMany
    {
        return $this->members()->whereIn('role', ['head_coach', 'assistant_coach']);
    }

    /**
     * Get all players through members relationship.
     */
    public function playerMembers(): BelongsToMany
    {
        return $this->members()->wherePivot('role', 'player');
    }

    /**
     * Get the players on this team.
     */
    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'player_team', 'team_id', 'player_id')
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
        return $this->players()->wherePivot('is_starter', true);
    }

    /**
     * Get the team captains.
     */
    public function captains(): BelongsToMany
    {
        return $this->players()->wherePivot('is_captain', true);
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
     * Get all games for this team (returns a collection).
     */
    public function getAllGames()
    {
        return Game::where(function ($query) {
            $query->where('home_team_id', $this->id)
                  ->orWhere('away_team_id', $this->id);
        });
    }

    /**
     * Get all games for this team as a proper relationship.
     */
    public function allGames()
    {
        return $this->getAllGames();
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
     * Get the total games count (home + away).
     */
    public function getGamesCountAttribute(): int
    {
        return ($this->home_games_count ?? 0) + ($this->away_games_count ?? 0);
    }

    /**
     * Get average player age.
     */
    public function getAveragePlayerAgeAttribute(): ?float
    {
        return $this->activePlayers()
                   ->with('user')
                   ->get()
                   ->filter(fn($player) => $player->user?->date_of_birth)
                   ->avg(fn($player) => $player->user->date_of_birth->age ?? null);
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

    /**
     * Get detailed reasons why team cannot accept new players.
     */
    public function getCannotAcceptPlayerReasons(): array
    {
        $reasons = [];
        
        if (!$this->is_recruiting) {
            $reasons[] = 'Team nimmt derzeit keine neuen Spieler auf';
        }
        
        if (!$this->is_active) {
            $reasons[] = 'Team ist nicht aktiv';
        }
        
        if ($this->current_roster_size >= $this->max_players) {
            $reasons[] = "Team ist voll ({$this->current_roster_size}/{$this->max_players} Spieler)";
        }
        
        return $reasons;
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
            // Check through members relationship
            if ($this->activeMembers()->where('user_id', $user->id)->exists()) {
                return true;
            }
            
            // Check if user is a player on this team (backward compatibility)
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
     * This method integrates both Jetstream team members and basketball-specific members.
     */
    public function allUsers()
    {
        // Get Jetstream team members (if any)
        $jetstreamUsers = parent::allUsers();
        
        // Get basketball team members through the members relationship
        $basketballUsers = $this->activeMembers()->get();
        
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