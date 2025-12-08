<?php

namespace App\Models;

use App\ValueObjects\Game\GameClock;
use App\ValueObjects\Game\GameFouls;
use App\ValueObjects\Game\GameMediaSettings;
use App\ValueObjects\Game\GameOfficials;
use App\ValueObjects\Game\GameRegistrationSettings;
use App\ValueObjects\Game\GameRules;
use App\ValueObjects\Game\GameSchedule;
use App\ValueObjects\Game\GameScore;
use App\ValueObjects\Game\GameStatistics;
use App\ValueObjects\Game\GameVenue;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Game extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uuid',
        'home_team_id',
        'away_team_id',
        'away_team_name',
        'home_team_name',
        'scheduled_at',
        'actual_start_time',
        'actual_end_time',
        'venue',
        'venue_address',
        'venue_code',
        'gym_hall_id',
        'import_source',
        'external_game_id',
        'import_metadata',
        'external_url',
        'is_home_game',
        'type',
        'season',
        'season_id',
        'league',
        'division',
        'status',
        'home_team_score',
        'away_team_score',
        'period_scores',
        'current_period',
        'total_periods',
        'period_length_minutes',
        'time_remaining_seconds',
        'clock_running',
        'overtime_periods',
        'overtime_length_minutes',
        'referees',
        'scorekeepers',
        'timekeepers',
        'team_stats',
        'player_stats',
        'live_commentary',
        'play_by_play',
        'substitutions',
        'timeouts',
        'team_fouls',
        'technical_fouls',
        'ejections',
        'result',
        'winning_team_id',
        'point_differential',
        'tournament_id',
        'tournament_round',
        'tournament_game_number',
        'weather_conditions',
        'temperature',
        'court_conditions',
        'is_streamed',
        'stream_url',
        'media_links',
        'pre_game_notes',
        'post_game_notes',
        'referee_report',
        'incident_report',
        'attendance',
        'capacity',
        'ticket_prices',
        'game_rules',
        'allow_spectators',
        'allow_media',
        'emergency_contacts',
        'medical_staff_present',
        'allow_recording',
        'allow_photos',
        'allow_streaming',
        'registration_deadline_hours',
        'max_roster_size',
        'min_roster_size',
        'allow_player_registrations',
        'auto_confirm_registrations',
        'lineup_deadline_hours',
        'stats_verified',
        'stats_verified_at',
        'stats_verified_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'uuid' => 'string',
        'scheduled_at' => 'datetime',
        'actual_start_time' => 'datetime',
        'actual_end_time' => 'datetime',
        'import_metadata' => 'array',
        'is_home_game' => 'boolean',
        'home_team_score' => 'integer',
        'away_team_score' => 'integer',
        'period_scores' => 'array',
        'current_period' => 'integer',
        'total_periods' => 'integer',
        'period_length_minutes' => 'integer',
        'time_remaining_seconds' => 'integer',
        'clock_running' => 'boolean',
        'overtime_periods' => 'integer',
        'overtime_length_minutes' => 'integer',
        'referees' => 'array',
        'scorekeepers' => 'array',
        'timekeepers' => 'array',
        'team_stats' => 'array',
        'player_stats' => 'array',
        'play_by_play' => 'array',
        'substitutions' => 'array',
        'timeouts' => 'array',
        'team_fouls' => 'array',
        'technical_fouls' => 'array',
        'ejections' => 'array',
        'point_differential' => 'integer',
        'tournament_game_number' => 'integer',
        'temperature' => 'integer',
        'is_streamed' => 'boolean',
        'media_links' => 'array',
        'attendance' => 'integer',
        'capacity' => 'integer',
        'ticket_prices' => 'array',
        'game_rules' => 'array',
        'allow_spectators' => 'boolean',
        'allow_media' => 'boolean',
        'emergency_contacts' => 'array',
        'allow_recording' => 'boolean',
        'allow_photos' => 'boolean',
        'allow_streaming' => 'boolean',
        'registration_deadline_hours' => 'integer',
        'max_roster_size' => 'integer',
        'min_roster_size' => 'integer',
        'allow_player_registrations' => 'boolean',
        'auto_confirm_registrations' => 'boolean',
        'lineup_deadline_hours' => 'integer',
        'stats_verified' => 'boolean',
        'stats_verified_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($game) {
            if (empty($game->uuid)) {
                $game->uuid = (string) Str::uuid();
            }
        });
    }

    // ============================
    // RELATIONSHIPS
    // ============================

    /**
     * Get the home team.
     */
    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    /**
     * Get the away team.
     */
    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    /**
     * Get the season this game belongs to.
     */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    /**
     * Get the gym hall where this game is played.
     */
    public function gymHall(): BelongsTo
    {
        return $this->belongsTo(GymHall::class);
    }

    /**
     * Get the user who verified the stats.
     */
    public function statsVerifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'stats_verified_by');
    }

    /**
     * Get the referees for this game.
     */
    public function refereeUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'game_referees')
            ->withPivot(['role', 'is_primary'])
            ->withTimestamps();
    }

    /**
     * Get all game actions for this game.
     */
    public function gameActions(): HasMany
    {
        return $this->hasMany(GameAction::class);
    }

    /**
     * Get the live game data for this game.
     */
    public function liveGame(): HasOne
    {
        return $this->hasOne(LiveGame::class);
    }

    /**
     * Get the scorer assignments for this game.
     */
    public function scorekeeperAssignments(): HasMany
    {
        return $this->hasMany(ScorekeeperAssignment::class);
    }

    /**
     * Get all player registrations for this game.
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(GameRegistration::class);
    }

    /**
     * Get all player participations for this game.
     */
    public function participations(): HasMany
    {
        return $this->hasMany(GameParticipation::class);
    }

    /**
     * Get all playbooks assigned to this game for preparation.
     */
    public function playbooks(): BelongsToMany
    {
        return $this->belongsToMany(Playbook::class, 'game_playbooks')
            ->withTimestamps();
    }

    // ============================
    // EXTERNAL TEAM SUPPORT METHODS
    // ============================

    /**
     * Get the display name for the home team.
     */
    public function getHomeTeamDisplayName(): string
    {
        if ($this->home_team_name) {
            return $this->home_team_name;
        }

        return $this->homeTeam ? $this->homeTeam->name : 'Unbekanntes Team';
    }

    /**
     * Get the display name for the away team.
     */
    public function getAwayTeamDisplayName(): string
    {
        if ($this->away_team_name) {
            return $this->away_team_name;
        }

        return $this->awayTeam ? $this->awayTeam->name : 'Unbekanntes Team';
    }

    /**
     * Check if the away team is external (not in our system).
     */
    public function isAwayTeamExternal(): bool
    {
        return $this->away_team_id === null && ! empty($this->away_team_name);
    }

    /**
     * Check if the home team is external (not in our system).
     */
    public function isHomeTeamExternal(): bool
    {
        return $this->home_team_id === null && ! empty($this->home_team_name);
    }

    /**
     * Check if this game involves any external teams.
     */
    public function hasExternalTeams(): bool
    {
        return $this->isAwayTeamExternal() || $this->isHomeTeamExternal();
    }

    /**
     * Check if the game was imported from an external source.
     */
    public function isImported(): bool
    {
        return $this->import_source !== 'manual';
    }

    // ============================
    // SCOPES
    // ============================

    /**
     * Scope a query to only include finished games.
     */
    public function scopeFinished($query)
    {
        return $query->where('status', 'finished');
    }

    /**
     * Scope a query to only include live games.
     */
    public function scopeLive($query)
    {
        return $query->whereIn('status', ['live', 'halftime', 'overtime']);
    }

    /**
     * Scope a query to only include upcoming games.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('status', 'scheduled')
            ->where('scheduled_at', '>', now());
    }

    /**
     * Scope a query to filter by season.
     */
    public function scopeInSeason($query, string $season)
    {
        return $query->where('season', $season);
    }

    /**
     * Scope a query to filter by league.
     */
    public function scopeInLeague($query, string $league)
    {
        return $query->where('league', $league);
    }

    /**
     * Scope a query to filter games for a specific team.
     */
    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('home_team_id', $teamId)
            ->orWhere('away_team_id', $teamId);
    }

    /**
     * Scope a query to only include games with live game data.
     */
    public function scopeWithLiveData($query)
    {
        return $query->whereHas('liveGame');
    }

    /**
     * Scope a query to only include games that are currently being broadcast.
     */
    public function scopeBroadcasting($query)
    {
        return $query->whereHas('liveGame', function ($q) {
            $q->where('is_being_broadcasted', true);
        });
    }

    /**
     * Scope a query to only include games with external teams.
     */
    public function scopeWithExternalTeams($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('away_team_id')->whereNotNull('away_team_name')
                ->orWhere(function ($subQ) {
                    $subQ->whereNull('home_team_id')->whereNotNull('home_team_name');
                });
        });
    }

    /**
     * Scope a query to only include games with internal teams only.
     */
    public function scopeInternalOnly($query)
    {
        return $query->whereNotNull('home_team_id')
            ->whereNotNull('away_team_id');
    }

    /**
     * Scope a query to filter by import source.
     */
    public function scopeFromSource($query, string $source)
    {
        return $query->where('import_source', $source);
    }

    /**
     * Scope a query to only include imported games.
     */
    public function scopeImported($query)
    {
        return $query->where('import_source', '!=', 'manual');
    }

    /**
     * Scope a query to filter games by venue code.
     */
    public function scopeAtVenueCode($query, string $venueCode)
    {
        return $query->where('venue_code', $venueCode);
    }

    // ============================
    // ACCESSORS & MUTATORS
    // ============================

    /**
     * Get the game duration in minutes.
     */
    public function getDurationAttribute(): ?int
    {
        if (! $this->actual_start_time || ! $this->actual_end_time) {
            return null;
        }

        return $this->actual_start_time->diffInMinutes($this->actual_end_time);
    }

    /**
     * Get the winning team.
     */
    public function getWinnerAttribute(): ?Team
    {
        if ($this->status !== 'finished') {
            return null;
        }

        if ($this->home_team_score > $this->away_team_score) {
            return $this->homeTeam;
        } elseif ($this->away_team_score > $this->home_team_score) {
            return $this->awayTeam;
        }

        return null; // Tie game
    }

    /**
     * Get the losing team.
     */
    public function getLoserAttribute(): ?Team
    {
        if ($this->status !== 'finished') {
            return null;
        }

        if ($this->home_team_score < $this->away_team_score) {
            return $this->homeTeam;
        } elseif ($this->away_team_score < $this->home_team_score) {
            return $this->awayTeam;
        }

        return null; // Tie game
    }

    /**
     * Check if the game is a tie.
     */
    public function getIsTieAttribute(): bool
    {
        return $this->status === 'finished' &&
               $this->home_team_score === $this->away_team_score;
    }

    /**
     * Get the current game time remaining as a formatted string.
     */
    public function getFormattedTimeRemainingAttribute(): string
    {
        if (! $this->time_remaining_seconds) {
            return '00:00';
        }

        $minutes = floor($this->time_remaining_seconds / 60);
        $seconds = $this->time_remaining_seconds % 60;

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    /**
     * Get the total score of the game.
     */
    public function getTotalScoreAttribute(): int
    {
        return $this->home_team_score + $this->away_team_score;
    }

    /**
     * Check if the game went to overtime.
     */
    public function getWentToOvertimeAttribute(): bool
    {
        return $this->overtime_periods > 0;
    }

    /**
     * Get the attendance percentage.
     */
    public function getAttendancePercentageAttribute(): ?float
    {
        if (! $this->capacity || ! $this->attendance) {
            return null;
        }

        return round(($this->attendance / $this->capacity) * 100, 1);
    }

    // ============================
    // VALUE OBJECT ACCESSORS
    // ============================

    /**
     * Get the game score as a Value Object.
     */
    public function score(): GameScore
    {
        return GameScore::fromArray($this->attributes);
    }

    /**
     * Get the game clock as a Value Object.
     */
    public function clock(): GameClock
    {
        return GameClock::fromArray($this->attributes);
    }

    /**
     * Get the game venue as a Value Object.
     */
    public function venueInfo(): GameVenue
    {
        return GameVenue::fromArray($this->attributes);
    }

    /**
     * Get the game schedule as a Value Object.
     */
    public function schedule(): GameSchedule
    {
        return GameSchedule::fromArray($this->attributes);
    }

    /**
     * Get the game officials as a Value Object.
     */
    public function officials(): GameOfficials
    {
        return GameOfficials::fromArray($this->attributes);
    }

    /**
     * Get the game rules as a Value Object.
     */
    public function rules(): GameRules
    {
        return GameRules::fromArray($this->attributes);
    }

    /**
     * Get the game statistics as a Value Object.
     */
    public function statistics(): GameStatistics
    {
        return GameStatistics::fromArray($this->attributes);
    }

    /**
     * Get the game fouls as a Value Object.
     */
    public function fouls(): GameFouls
    {
        return GameFouls::fromArray($this->attributes);
    }

    /**
     * Get the registration settings as a Value Object.
     */
    public function registrationSettings(): GameRegistrationSettings
    {
        return GameRegistrationSettings::fromArray($this->attributes);
    }

    /**
     * Get the media settings as a Value Object.
     */
    public function mediaSettings(): GameMediaSettings
    {
        return GameMediaSettings::fromArray($this->attributes);
    }

    // ============================
    // HELPER METHODS
    // ============================

    /**
     * Start the game.
     */
    public function startGame(): void
    {
        $this->update([
            'status' => 'live',
            'actual_start_time' => now(),
            'current_period' => 1,
            'time_remaining_seconds' => $this->period_length_minutes * 60,
            'clock_running' => true,
        ]);
    }

    /**
     * End the current period.
     */
    public function endPeriod(): void
    {
        if ($this->current_period < $this->total_periods) {
            $this->update([
                'current_period' => $this->current_period + 1,
                'time_remaining_seconds' => $this->period_length_minutes * 60,
                'status' => 'halftime',
                'clock_running' => false,
            ]);
        } else {
            $this->checkForOvertime();
        }
    }

    /**
     * Check if overtime is needed and handle accordingly.
     */
    protected function checkForOvertime(): void
    {
        if ($this->home_team_score === $this->away_team_score) {
            // Game is tied, go to overtime
            $this->update([
                'status' => 'overtime',
                'overtime_periods' => $this->overtime_periods + 1,
                'current_period' => $this->total_periods + $this->overtime_periods,
                'time_remaining_seconds' => $this->overtime_length_minutes * 60,
                'clock_running' => true,
            ]);
        } else {
            // Game is finished
            $this->finishGame();
        }
    }

    /**
     * Finish the game.
     */
    public function finishGame(): void
    {
        $winner = $this->winner;
        $result = null;
        $winningTeamId = null;

        if ($winner) {
            $result = $winner->id === $this->home_team_id ? 'home_win' : 'away_win';
            $winningTeamId = $winner->id;
        } else {
            $result = 'tie';
        }

        $this->update([
            'status' => 'finished',
            'actual_end_time' => now(),
            'result' => $result,
            'winning_team_id' => $winningTeamId,
            'point_differential' => abs($this->home_team_score - $this->away_team_score),
            'clock_running' => false,
        ]);

        // Update team statistics (only for teams that exist in the database)
        if ($this->homeTeam) {
            $this->homeTeam->updateStatistics($this);
        }

        if ($this->awayTeam) {
            $this->awayTeam->updateStatistics($this);
        }
    }

    /**
     * Add a score to a team.
     */
    public function addScore(int $teamId, int $points): void
    {
        if ($teamId === $this->home_team_id) {
            $this->increment('home_team_score', $points);
        } elseif ($teamId === $this->away_team_id) {
            $this->increment('away_team_score', $points);
        }

        // Add to play-by-play
        $this->addPlayByPlayEvent([
            'type' => 'score',
            'team_id' => $teamId,
            'points' => $points,
            'period' => $this->current_period,
            'time_remaining' => $this->time_remaining_seconds,
            'timestamp' => now(),
        ]);
    }

    /**
     * Add a play-by-play event.
     */
    public function addPlayByPlayEvent(array $event): void
    {
        $playByPlay = $this->play_by_play ?? [];
        $playByPlay[] = $event;
        $this->update(['play_by_play' => $playByPlay]);
    }

    /**
     * Get game summary.
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'home_team' => [
                'id' => $this->homeTeam?->id,
                'name' => $this->getHomeTeamDisplayName(),
                'score' => $this->home_team_score,
                'is_external' => $this->isHomeTeamExternal(),
            ],
            'away_team' => [
                'id' => $this->awayTeam?->id,
                'name' => $this->getAwayTeamDisplayName(),
                'score' => $this->away_team_score,
                'is_external' => $this->isAwayTeamExternal(),
            ],
            'status' => $this->status,
            'scheduled_at' => $this->scheduled_at,
            'venue' => $this->venue,
            'league' => $this->league,
            'season' => $this->season,
            'result' => $this->result,
            'winner' => $this->winner?->name,
            'point_differential' => $this->point_differential,
            'went_to_overtime' => $this->went_to_overtime,
            'attendance' => $this->attendance,
            'has_external_teams' => $this->hasExternalTeams(),
        ];
    }

    /**
     * Get detailed game statistics.
     */
    public function getDetailedStats(): array
    {
        return [
            'game_info' => $this->getSummary(),
            'team_stats' => $this->team_stats,
            'player_stats' => $this->player_stats,
            'period_scores' => $this->period_scores,
            'play_by_play' => $this->play_by_play,
            'officials' => [
                'referees' => $this->referees,
                'scorekeepers' => $this->scorekeepers,
                'timekeepers' => $this->timekeepers,
            ],
            'fouls_and_violations' => [
                'team_fouls' => $this->team_fouls,
                'technical_fouls' => $this->technical_fouls,
                'ejections' => $this->ejections,
            ],
        ];
    }

    /**
     * Cancel the game.
     */
    public function cancelGame(?string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'post_game_notes' => $reason,
        ]);
    }

    /**
     * Postpone the game.
     */
    public function postponeGame(\DateTime $newDateTime, ?string $reason = null): void
    {
        $this->update([
            'status' => 'postponed',
            'scheduled_at' => $newDateTime,
            'pre_game_notes' => $reason,
        ]);
    }

    /**
     * Check if the game can be scored.
     */
    public function canBeScored(): bool
    {
        return in_array($this->status, ['live', 'halftime', 'overtime']);
    }

    /**
     * Check if the game can be started.
     */
    public function canBeStarted(): bool
    {
        return $this->status === 'scheduled' &&
               $this->scheduled_at <= now()->addMinutes(30);
    }

    /**
     * Get the opponent team for a given team.
     */
    public function getOpponentTeam(Team $team): Team
    {
        if ($this->home_team_id === $team->id) {
            return $this->awayTeam;
        } elseif ($this->away_team_id === $team->id) {
            return $this->homeTeam;
        }

        throw new \InvalidArgumentException('Team is not participating in this game');
    }

    /**
     * Check if a team is the home team.
     */
    public function isHomeTeam(Team $team): bool
    {
        return $this->home_team_id === $team->id;
    }

    /**
     * Check if a team is the away team.
     */
    public function isAwayTeam(Team $team): bool
    {
        return $this->away_team_id === $team->id;
    }

    /**
     * Get the team for a given side.
     */
    public function getTeamForSide(string $side): Team
    {
        return $side === 'home' ? $this->homeTeam : $this->awayTeam;
    }

    // ============================
    // MEDIA LIBRARY
    // ============================

    /**
     * Register media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos')
            ->acceptsMimeTypes(['image/jpeg', 'image/png']);

        $this->addMediaCollection('videos')
            ->acceptsMimeTypes(['video/mp4']);

        $this->addMediaCollection('documents')
            ->acceptsMimeTypes(['application/pdf']);
    }

    /**
     * Register media conversions.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(200)
            ->sharpen(10);
    }

    // ============================
    // REGISTRATION/BOOKING METHODS
    // ============================

    /**
     * Get the registration deadline for this game.
     */
    public function getRegistrationDeadline(): Carbon
    {
        return $this->scheduled_at->subHours($this->registration_deadline_hours ?? 24);
    }

    /**
     * Get the lineup deadline for this game.
     */
    public function getLineupDeadline(): Carbon
    {
        return $this->scheduled_at->subHours($this->lineup_deadline_hours ?? 2);
    }

    /**
     * Check if player registration is still open.
     */
    public function isRegistrationOpen(): bool
    {
        if (! $this->allow_player_registrations) {
            return false;
        }

        if (! in_array($this->status, ['scheduled'])) {
            return false;
        }

        return now()->isBefore($this->getRegistrationDeadline());
    }

    /**
     * Check if lineup changes are still allowed.
     */
    public function isLineupChangesAllowed(): bool
    {
        if (! in_array($this->status, ['scheduled'])) {
            return false;
        }

        return now()->isBefore($this->getLineupDeadline());
    }

    /**
     * Check if roster has capacity for more players.
     */
    public function hasRosterCapacity(?int $additionalPlayers = 1): bool
    {
        $currentParticipants = $this->participations()->count();

        return ($currentParticipants + $additionalPlayers) <= $this->max_roster_size;
    }

    /**
     * Get available roster spots.
     */
    public function getAvailableRosterSpots(): int
    {
        $currentParticipants = $this->participations()->count();

        return max(0, $this->max_roster_size - $currentParticipants);
    }

    /**
     * Get count of confirmed registrations.
     */
    public function getConfirmedRegistrations(): int
    {
        return $this->registrations()->where('registration_status', 'confirmed')->count();
    }

    /**
     * Get count of pending registrations.
     */
    public function getPendingRegistrations(): int
    {
        return $this->registrations()->where('registration_status', 'pending')->count();
    }

    /**
     * Get count of available players.
     */
    public function getAvailablePlayers(): int
    {
        return $this->registrations()->where('availability_status', 'available')->count();
    }

    /**
     * Get count of unavailable players.
     */
    public function getUnavailablePlayers(): int
    {
        return $this->registrations()->where('availability_status', 'unavailable')->count();
    }

    /**
     * Check if minimum roster requirement is met.
     */
    public function hasMinimumRoster(): bool
    {
        $participants = $this->participations()->count();

        return $participants >= $this->min_roster_size;
    }

    /**
     * Check if player is registered for this game.
     */
    public function isPlayerRegistered(int $playerId): bool
    {
        return $this->registrations()
            ->where('player_id', $playerId)
            ->exists();
    }

    /**
     * Get player's registration for this game.
     */
    public function getPlayerRegistration(int $playerId): ?GameRegistration
    {
        return $this->registrations()
            ->where('player_id', $playerId)
            ->first();
    }

    /**
     * Check if player is participating in this game.
     */
    public function isPlayerParticipating(int $playerId): bool
    {
        return $this->participations()
            ->where('player_id', $playerId)
            ->exists();
    }

    /**
     * Get player's participation for this game.
     */
    public function getPlayerParticipation(int $playerId): ?GameParticipation
    {
        return $this->participations()
            ->where('player_id', $playerId)
            ->first();
    }

    /**
     * Register a player for this game.
     */
    public function registerPlayer(int $playerId, string $availabilityStatus = 'available', ?string $notes = null): GameRegistration
    {
        return GameRegistration::createRegistration($this->id, $playerId, $availabilityStatus, $notes);
    }

    /**
     * Add a player to the game roster.
     */
    public function addPlayerToRoster(int $playerId, string $role = 'substitute', ?int $jerseyNumber = null, ?string $position = null): GameParticipation
    {
        return GameParticipation::createParticipation($this->id, $playerId, $role, $jerseyNumber, $position);
    }

    /**
     * Get registration summary.
     */
    public function getRegistrationSummary(): array
    {
        return [
            'total_registrations' => $this->registrations()->count(),
            'confirmed_registrations' => $this->getConfirmedRegistrations(),
            'pending_registrations' => $this->getPendingRegistrations(),
            'available_players' => $this->getAvailablePlayers(),
            'unavailable_players' => $this->getUnavailablePlayers(),
            'current_roster_size' => $this->participations()->count(),
            'max_roster_size' => $this->max_roster_size,
            'min_roster_size' => $this->min_roster_size,
            'available_roster_spots' => $this->getAvailableRosterSpots(),
            'has_roster_capacity' => $this->hasRosterCapacity(),
            'has_minimum_roster' => $this->hasMinimumRoster(),
            'registration_open' => $this->isRegistrationOpen(),
            'lineup_changes_allowed' => $this->isLineupChangesAllowed(),
            'registration_deadline' => $this->getRegistrationDeadline()->format('d.m.Y H:i'),
            'lineup_deadline' => $this->getLineupDeadline()->format('d.m.Y H:i'),
            'hours_until_registration_deadline' => now()->diffInHours($this->getRegistrationDeadline(), false),
            'hours_until_lineup_deadline' => now()->diffInHours($this->getLineupDeadline(), false),
            'allow_player_registrations' => $this->allow_player_registrations,
            'auto_confirm_registrations' => $this->auto_confirm_registrations,
        ];
    }

    /**
     * Get roster lineup organized by role.
     */
    public function getRosterLineup(): array
    {
        $participations = $this->participations()
            ->with('player')
            ->orderByRaw("FIELD(role, 'captain', 'vice_captain', 'starter', 'substitute', 'reserve')")
            ->get();

        return [
            'captains' => $participations->where('role', 'captain')->values(),
            'vice_captains' => $participations->where('role', 'vice_captain')->values(),
            'starters' => $participations->where('role', 'starter')->values(),
            'substitutes' => $participations->where('role', 'substitute')->values(),
            'reserves' => $participations->where('role', 'reserve')->values(),
            'total_count' => $participations->count(),
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
                'status', 'home_team_score', 'away_team_score',
                'scheduled_at', 'venue', 'result',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
