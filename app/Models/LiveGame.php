<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class LiveGame extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'current_period',
        'period_time_remaining',
        'period_time_elapsed_seconds',
        'period_is_running',
        'period_started_at',
        'period_paused_at',
        'shot_clock_remaining',
        'shot_clock_is_running',
        'shot_clock_started_at',
        'current_score_home',
        'current_score_away',
        'period_scores',
        'fouls_home_period',
        'fouls_away_period',
        'fouls_home_total',
        'fouls_away_total',
        'timeouts_home_remaining',
        'timeouts_away_remaining',
        'players_on_court_home',
        'players_on_court_away',
        'game_phase',
        'is_in_timeout',
        'timeout_team',
        'timeout_started_at',
        'timeout_duration_seconds',
        'last_action_id',
        'last_action_at',
        'viewers_count',
        'is_being_broadcasted',
        'broadcast_settings',
        'actions_count',
        'last_update_at',
    ];

    protected $casts = [
        'current_period' => 'integer',
        'period_time_remaining' => 'datetime:H:i:s',
        'period_time_elapsed_seconds' => 'integer',
        'period_is_running' => 'boolean',
        'period_started_at' => 'datetime',
        'period_paused_at' => 'datetime',
        'shot_clock_remaining' => 'integer',
        'shot_clock_is_running' => 'boolean',
        'shot_clock_started_at' => 'datetime',
        'current_score_home' => 'integer',
        'current_score_away' => 'integer',
        'period_scores' => 'array',
        'fouls_home_period' => 'integer',
        'fouls_away_period' => 'integer',
        'fouls_home_total' => 'integer',
        'fouls_away_total' => 'integer',
        'timeouts_home_remaining' => 'integer',
        'timeouts_away_remaining' => 'integer',
        'players_on_court_home' => 'array',
        'players_on_court_away' => 'array',
        'is_in_timeout' => 'boolean',
        'timeout_started_at' => 'datetime',
        'timeout_duration_seconds' => 'integer',
        'last_action_at' => 'datetime',
        'viewers_count' => 'integer',
        'is_being_broadcasted' => 'boolean',
        'broadcast_settings' => 'array',
        'actions_count' => 'integer',
        'last_update_at' => 'datetime',
    ];

    // Relationships
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function lastAction(): BelongsTo
    {
        return $this->belongsTo(GameAction::class, 'last_action_id');
    }

    // Accessors
    public function isHalftime(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->game_phase === 'halftime',
        );
    }

    public function isOvertime(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->current_period > $this->game->total_periods,
        );
    }

    public function currentScoreDifference(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->current_score_home - $this->current_score_away,
        );
    }

    public function leadingTeam(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->current_score_home > $this->current_score_away) {
                    return 'home';
                } elseif ($this->current_score_away > $this->current_score_home) {
                    return 'away';
                } else {
                    return 'tied';
                }
            },
        );
    }

    public function periodProgressPercent(): Attribute
    {
        return Attribute::make(
            get: function () {
                $totalSeconds = $this->game->period_length_minutes * 60;
                $remainingSeconds = Carbon::parse($this->period_time_remaining)->secondsSinceMidnight();
                $elapsedSeconds = $totalSeconds - $remainingSeconds;
                
                return ($elapsedSeconds / $totalSeconds) * 100;
            },
        );
    }

    // Helper Methods
    public function startPeriod(): void
    {
        $this->update([
            'period_is_running' => true,
            'period_started_at' => now(),
            'period_paused_at' => null,
            'game_phase' => 'period',
            'last_update_at' => now(),
        ]);
    }

    public function pausePeriod(): void
    {
        if ($this->period_is_running) {
            $this->calculateElapsedTime();
            
            $this->update([
                'period_is_running' => false,
                'period_paused_at' => now(),
                'last_update_at' => now(),
            ]);
        }
    }

    public function resumePeriod(): void
    {
        $this->update([
            'period_is_running' => true,
            'period_started_at' => now(),
            'period_paused_at' => null,
            'last_update_at' => now(),
        ]);
    }

    public function endPeriod(): void
    {
        $this->update([
            'period_is_running' => false,
            'period_time_remaining' => '00:00:00',
            'game_phase' => $this->determineNextPhase(),
            'fouls_home_period' => 0,
            'fouls_away_period' => 0,
            'last_update_at' => now(),
        ]);
    }

    public function startTimeout(string $team, int $duration = 60): void
    {
        $this->pausePeriod();
        
        $this->update([
            'is_in_timeout' => true,
            'timeout_team' => $team,
            'timeout_started_at' => now(),
            'timeout_duration_seconds' => $duration,
            'game_phase' => 'timeout',
        ]);
        
        if (in_array($team, ['home', 'away'])) {
            $timeoutsField = "timeouts_{$team}_remaining";
            $this->decrement($timeoutsField);
        }
    }

    public function endTimeout(): void
    {
        $this->update([
            'is_in_timeout' => false,
            'timeout_team' => null,
            'timeout_started_at' => null,
            'timeout_duration_seconds' => 60,
            'game_phase' => 'period',
        ]);
        
        $this->resumePeriod();
    }

    public function updateScore(string $team, int $points): void
    {
        $scoreField = "current_score_{$team}";
        $this->increment($scoreField, $points);
        $this->touch('last_update_at');
    }

    public function resetShotClock(int $seconds = null): void
    {
        $seconds = $seconds ?? 24; // Default shot clock
        
        $this->update([
            'shot_clock_remaining' => $seconds,
            'shot_clock_started_at' => $this->period_is_running ? now() : null,
            'shot_clock_is_running' => $this->period_is_running,
        ]);
    }

    public function addFoul(string $team): void
    {
        $this->increment("fouls_{$team}_period");
        $this->increment("fouls_{$team}_total");
        $this->touch('last_update_at');
    }

    public function updatePlayersOnCourt(string $team, array $playerIds): void
    {
        $this->update([
            "players_on_court_{$team}" => $playerIds,
            'last_update_at' => now(),
        ]);
    }

    public function incrementViewers(): void
    {
        $this->increment('viewers_count');
    }

    public function decrementViewers(): void
    {
        $this->decrement('viewers_count');
    }

    private function calculateElapsedTime(): void
    {
        if ($this->period_started_at) {
            $elapsed = $this->period_started_at->diffInSeconds(now());
            $this->increment('period_time_elapsed_seconds', $elapsed);
            
            // Update time remaining
            $totalSeconds = $this->game->period_length_minutes * 60;
            $remainingSeconds = max(0, $totalSeconds - $this->period_time_elapsed_seconds);
            
            $this->update([
                'period_time_remaining' => gmdate('H:i:s', $remainingSeconds)
            ]);
        }
    }

    private function determineNextPhase(): string
    {
        if ($this->current_period < $this->game->total_periods) {
            return $this->current_period == 2 ? 'halftime' : 'break';
        } elseif ($this->current_score_home === $this->current_score_away) {
            return 'overtime';
        } else {
            return 'postgame';
        }
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'period_progress_percent' => $this->period_progress_percent,
            'leading_team' => $this->leading_team,
            'current_score_difference' => $this->current_score_difference,
            'is_halftime' => $this->is_halftime,
            'is_overtime' => $this->is_overtime,
        ]);
    }
}