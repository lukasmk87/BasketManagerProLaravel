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
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Game extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, LogsActivity, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uuid',
        'home_team_id',
        'away_team_id',
        'scheduled_at',
        'actual_start_time',
        'actual_end_time',
        'venue',
        'venue_address',
        'type',
        'season',
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
        return $this->belongsTo(BasketballTeam::class, 'home_team_id');
    }

    /**
     * Get the away team.
     */
    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(BasketballTeam::class, 'away_team_id');
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

    // ============================
    // ACCESSORS & MUTATORS
    // ============================

    /**
     * Get the game duration in minutes.
     */
    public function getDurationAttribute(): ?int
    {
        if (!$this->actual_start_time || !$this->actual_end_time) {
            return null;
        }

        return $this->actual_start_time->diffInMinutes($this->actual_end_time);
    }

    /**
     * Get the winning team.
     */
    public function getWinnerAttribute(): ?BasketballTeam
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
    public function getLoserAttribute(): ?BasketballTeam
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
        if (!$this->time_remaining_seconds) {
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
        if (!$this->capacity || !$this->attendance) {
            return null;
        }

        return round(($this->attendance / $this->capacity) * 100, 1);
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

        // Update team statistics
        $this->homeTeam->updateStatistics($this);
        $this->awayTeam->updateStatistics($this);
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
                'id' => $this->homeTeam->id,
                'name' => $this->homeTeam->name,
                'score' => $this->home_team_score,
            ],
            'away_team' => [
                'id' => $this->awayTeam->id,
                'name' => $this->awayTeam->name,
                'score' => $this->away_team_score,
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
    public function cancelGame(string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'post_game_notes' => $reason,
        ]);
    }

    /**
     * Postpone the game.
     */
    public function postponeGame(\DateTime $newDateTime, string $reason = null): void
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
    public function getOpponentTeam(BasketballTeam $team): BasketballTeam
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
    public function isHomeTeam(BasketballTeam $team): bool
    {
        return $this->home_team_id === $team->id;
    }

    /**
     * Check if a team is the away team.
     */
    public function isAwayTeam(BasketballTeam $team): bool
    {
        return $this->away_team_id === $team->id;
    }

    /**
     * Get the team for a given side.
     */
    public function getTeamForSide(string $side): BasketballTeam
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
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(200)
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
                'status', 'home_team_score', 'away_team_score',
                'scheduled_at', 'venue', 'result'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}