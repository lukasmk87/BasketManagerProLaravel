<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LiveGameResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'game_id' => $this->game_id,
            
            // Game Time Management
            'time_info' => [
                'current_period' => $this->current_period,
                'period_time_remaining' => $this->period_time_remaining,
                'period_time_elapsed_seconds' => $this->period_time_elapsed_seconds,
                'period_is_running' => $this->period_is_running,
                'period_started_at' => $this->period_started_at,
                'period_paused_at' => $this->period_paused_at,
                'period_progress_percent' => $this->period_progress_percent,
            ],
            
            // Shot Clock
            'shot_clock' => [
                'remaining' => $this->shot_clock_remaining,
                'is_running' => $this->shot_clock_is_running,
                'started_at' => $this->shot_clock_started_at,
            ],
            
            // Current Scores
            'scores' => [
                'home' => $this->current_score_home,
                'away' => $this->current_score_away,
                'difference' => $this->current_score_difference,
                'leading_team' => $this->leading_team,
            ],
            
            // Period Scores History
            'period_scores' => $this->period_scores,
            
            // Team Status
            'team_status' => [
                'fouls' => [
                    'home_period' => $this->fouls_home_period,
                    'away_period' => $this->fouls_away_period,
                    'home_total' => $this->fouls_home_total,
                    'away_total' => $this->fouls_away_total,
                ],
                'timeouts' => [
                    'home_remaining' => $this->timeouts_home_remaining,
                    'away_remaining' => $this->timeouts_away_remaining,
                ],
            ],
            
            // Players on Court
            'players_on_court' => [
                'home' => $this->players_on_court_home,
                'away' => $this->players_on_court_away,
            ],
            
            // Game Flow Control
            'game_flow' => [
                'phase' => $this->game_phase,
                'is_halftime' => $this->is_halftime,
                'is_overtime' => $this->is_overtime,
            ],
            
            // Timeout Management
            'timeout_info' => [
                'is_in_timeout' => $this->is_in_timeout,
                'timeout_team' => $this->timeout_team,
                'timeout_started_at' => $this->timeout_started_at,
                'timeout_duration_seconds' => $this->timeout_duration_seconds,
            ],
            
            // Last Action Reference
            'last_action' => [
                'id' => $this->last_action_id,
                'at' => $this->last_action_at,
                'action' => new GameActionResource($this->whenLoaded('lastAction')),
            ],
            
            // Broadcasting & Viewers
            'broadcast_info' => [
                'is_being_broadcasted' => $this->is_being_broadcasted,
                'viewers_count' => $this->viewers_count,
                'broadcast_settings' => $this->broadcast_settings,
            ],
            
            // Performance Tracking
            'performance' => [
                'actions_count' => $this->actions_count,
                'last_update_at' => $this->last_update_at,
            ],
            
            // Game State Helpers
            'state_flags' => [
                'can_start_period' => !$this->period_is_running && $this->game_phase === 'pregame',
                'can_pause_period' => $this->period_is_running,
                'can_resume_period' => !$this->period_is_running && in_array($this->game_phase, ['period', 'break']),
                'can_end_period' => $this->period_is_running,
                'can_start_timeout' => $this->period_is_running && !$this->is_in_timeout,
                'can_end_timeout' => $this->is_in_timeout,
                'can_substitute' => !$this->period_is_running || $this->is_in_timeout,
            ],
            
            // Quick Stats
            'quick_stats' => [
                'total_game_time_elapsed' => $this->calculateTotalElapsedTime(),
                'estimated_remaining_time' => $this->calculateEstimatedRemainingTime(),
                'game_pace' => $this->calculateGamePace(),
            ],
            
            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Calculate total elapsed game time in seconds.
     */
    private function calculateTotalElapsedTime(): int
    {
        if (!$this->resource->game) {
            return 0;
        }

        $periodLength = $this->resource->game->period_length_minutes * 60;
        $completedPeriods = max(0, $this->current_period - 1);
        $currentPeriodElapsed = $this->period_time_elapsed_seconds;
        
        return ($completedPeriods * $periodLength) + $currentPeriodElapsed;
    }

    /**
     * Calculate estimated remaining game time in seconds.
     */
    private function calculateEstimatedRemainingTime(): int
    {
        if (!$this->resource->game || $this->game_phase === 'postgame') {
            return 0;
        }

        $periodLength = $this->resource->game->period_length_minutes * 60;
        $totalPeriods = $this->resource->game->total_periods;
        $remainingPeriods = max(0, $totalPeriods - $this->current_period);
        
        // Current period remaining time
        $currentPeriodRemaining = $this->period_time_elapsed_seconds > 0 
            ? $periodLength - $this->period_time_elapsed_seconds 
            : $periodLength;
        
        return ($remainingPeriods * $periodLength) + $currentPeriodRemaining;
    }

    /**
     * Calculate game pace (actions per minute).
     */
    private function calculateGamePace(): float
    {
        $totalElapsed = $this->calculateTotalElapsedTime();
        
        if ($totalElapsed <= 0) {
            return 0;
        }
        
        $elapsedMinutes = $totalElapsed / 60;
        return round($this->actions_count / $elapsedMinutes, 2);
    }

    /**
     * Get additional data that should be returned with the resource array.
     */
    public function with($request)
    {
        return [
            'meta' => [
                'real_time' => true,
                'last_refresh' => now()->toISOString(),
                'refresh_interval' => 1, // seconds
            ],
        ];
    }
}