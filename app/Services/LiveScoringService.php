<?php

namespace App\Services;

use App\Models\Game;
use App\Models\GameAction;
use App\Models\LiveGame;
use App\Models\Player;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LiveScoringService
{
    /**
     * Start a game and create live game data.
     */
    public function startGame(Game $game): LiveGame
    {
        if ($game->status !== 'scheduled') {
            throw new \Exception('Das Spiel kann nicht gestartet werden. Status: ' . $game->status);
        }

        return DB::transaction(function () use ($game) {
            // Update game status
            $game->update([
                'status' => 'live',
                'actual_start_time' => now(),
            ]);

            // Create or update live game
            $liveGame = LiveGame::updateOrCreate(
                ['game_id' => $game->id],
                [
                    'current_period' => 1,
                    'period_time_remaining' => sprintf('%02d:00:00', $game->period_length_minutes),
                    'period_is_running' => false,
                    'shot_clock_remaining' => 24, // Standard basketball shot clock
                    'shot_clock_is_running' => false,
                    'current_score_home' => 0,
                    'current_score_away' => 0,
                    'period_scores' => [],
                    'fouls_home_period' => 0,
                    'fouls_away_period' => 0,
                    'fouls_home_total' => 0,
                    'fouls_away_total' => 0,
                    'timeouts_home_remaining' => 5,
                    'timeouts_away_remaining' => 5,
                    'game_phase' => 'pregame',
                    'is_being_broadcasted' => true,
                    'last_update_at' => now(),
                ]
            );

            return $liveGame;
        });
    }

    /**
     * Add a game action.
     */
    public function addGameAction(Game $game, array $actionData): GameAction
    {
        $liveGame = $game->liveGame;
        
        if (!$liveGame) {
            throw new \Exception('Spiel ist nicht live.');
        }

        return DB::transaction(function () use ($game, $liveGame, $actionData) {
            $action = GameAction::create(array_merge($actionData, [
                'game_id' => $game->id,
                'period' => $liveGame->current_period,
                'time_remaining' => $liveGame->period_time_remaining,
                'game_clock_seconds' => $this->calculateGameClockSeconds($liveGame),
                'shot_clock_remaining' => $liveGame->shot_clock_remaining,
                'recorded_by_user_id' => auth()->id(),
                'recorded_from_ip' => request()->ip(),
                'recorded_at' => now(),
            ]));

            // Update action count
            $liveGame->increment('actions_count');
            $liveGame->update([
                'last_action_id' => $action->id,
                'last_action_at' => now(),
                'last_update_at' => now(),
            ]);

            return $action;
        });
    }

    /**
     * Update live game state based on action.
     */
    public function updateLiveGameState(Game $game, GameAction $action): LiveGame
    {
        $liveGame = $game->liveGame;

        return DB::transaction(function () use ($liveGame, $action) {
            // Update score if scoring action
            if ($action->points > 0) {
                $team = $action->team_id === $action->game->home_team_id ? 'home' : 'away';
                $liveGame->increment("current_score_{$team}", $action->points);
            }

            // Update fouls
            if ($action->is_foul) {
                $team = $action->team_id === $action->game->home_team_id ? 'home' : 'away';
                $liveGame->increment("fouls_{$team}_period");
                $liveGame->increment("fouls_{$team}_total");
            }

            // Reset shot clock on certain actions
            if ($this->shouldResetShotClock($action)) {
                $liveGame->update([
                    'shot_clock_remaining' => 24,
                    'shot_clock_started_at' => $liveGame->period_is_running ? now() : null,
                ]);
            }

            $liveGame->touch('last_update_at');
            
            return $liveGame;
        });
    }

    /**
     * Update game score directly.
     */
    public function updateScore(Game $game, string $team, int $points, int $playerId): LiveGame
    {
        $liveGame = $game->liveGame;
        
        if (!$liveGame || !$liveGame->period_is_running) {
            throw new \Exception('Spielstand kann nur während laufendem Spiel aktualisiert werden.');
        }

        return DB::transaction(function () use ($liveGame, $team, $points, $playerId) {
            $liveGame->increment("current_score_{$team}", $points);
            $liveGame->touch('last_update_at');
            
            return $liveGame;
        });
    }

    /**
     * Start a period.
     */
    public function startPeriod(Game $game): LiveGame
    {
        $liveGame = $game->liveGame;
        
        if (!$liveGame) {
            throw new \Exception('Spiel ist nicht live.');
        }

        return DB::transaction(function () use ($game, $liveGame) {
            $liveGame->startPeriod();
            
            // Update game status if first period
            if ($liveGame->current_period === 1 && $game->status === 'scheduled') {
                $game->update(['status' => 'live']);
            }
            
            return $liveGame;
        });
    }

    /**
     * Pause a period.
     */
    public function pausePeriod(Game $game): LiveGame
    {
        $liveGame = $game->liveGame;
        
        if (!$liveGame || !$liveGame->period_is_running) {
            throw new \Exception('Periode läuft nicht und kann nicht pausiert werden.');
        }

        $liveGame->pausePeriod();
        
        return $liveGame;
    }

    /**
     * Resume a period.
     */
    public function resumePeriod(Game $game): LiveGame
    {
        $liveGame = $game->liveGame;
        
        if (!$liveGame || $liveGame->period_is_running) {
            throw new \Exception('Periode läuft bereits oder Spiel ist nicht pausiert.');
        }

        $liveGame->resumePeriod();
        
        return $liveGame;
    }

    /**
     * End the current period.
     */
    public function endPeriod(Game $game): LiveGame
    {
        $liveGame = $game->liveGame;
        
        if (!$liveGame) {
            throw new \Exception('Spiel ist nicht live.');
        }

        return DB::transaction(function () use ($game, $liveGame) {
            // Save period score
            $periodScores = $liveGame->period_scores ?? [];
            $periodScores["period_{$liveGame->current_period}"] = [
                'home' => $liveGame->current_score_home,
                'away' => $liveGame->current_score_away,
            ];
            
            $liveGame->update(['period_scores' => $periodScores]);
            $liveGame->endPeriod();
            
            // Check if game should end or go to next period
            if ($this->shouldEndGame($liveGame)) {
                return $this->finishGame($game);
            } else {
                // Advance to next period
                $liveGame->update([
                    'current_period' => $liveGame->current_period + 1,
                    'period_time_remaining' => sprintf('%02d:00:00', 
                        $liveGame->is_overtime ? $game->overtime_length_minutes : $game->period_length_minutes
                    ),
                    'period_time_elapsed_seconds' => 0,
                ]);
                
                // Update game status for halftime
                if ($liveGame->current_period === 3 && $game->total_periods === 4) {
                    $game->update(['status' => 'halftime']);
                    $liveGame->update(['game_phase' => 'halftime']);
                }
            }
            
            return $liveGame;
        });
    }

    /**
     * Start a timeout.
     */
    public function startTimeout(Game $game, string $team, int $duration = 60): LiveGame
    {
        $liveGame = $game->liveGame;
        
        if (!$liveGame) {
            throw new \Exception('Spiel ist nicht live.');
        }

        if ($team !== 'official' && $liveGame->{\"timeouts_{$team}_remaining\"} <= 0) {
            throw new \Exception("Team {$team} hat keine Timeouts mehr.");
        }

        $liveGame->startTimeout($team, $duration);
        
        return $liveGame;
    }

    /**
     * End a timeout.
     */
    public function endTimeout(Game $game): LiveGame
    {
        $liveGame = $game->liveGame;
        
        if (!$liveGame || !$liveGame->is_in_timeout) {
            throw new \Exception('Kein aktives Timeout.');
        }

        $liveGame->endTimeout();
        
        return $liveGame;
    }

    /**
     * Process player substitution.
     */
    public function processSubstitution(Game $game, string $team, int $playerInId, int $playerOutId, ?string $reason = null): void
    {
        $liveGame = $game->liveGame;
        
        if (!$liveGame) {
            throw new \Exception('Spiel ist nicht live.');
        }

        DB::transaction(function () use ($game, $liveGame, $team, $playerInId, $playerOutId, $reason) {
            // Record substitution out
            GameAction::create([
                'game_id' => $game->id,
                'player_id' => $playerOutId,
                'team_id' => $team === 'home' ? $game->home_team_id : $game->away_team_id,
                'action_type' => 'substitution_out',
                'period' => $liveGame->current_period,
                'time_remaining' => $liveGame->period_time_remaining,
                'substituted_player_id' => $playerInId,
                'substitution_reason' => $reason,
                'recorded_by_user_id' => auth()->id(),
                'recorded_at' => now(),
            ]);

            // Record substitution in
            GameAction::create([
                'game_id' => $game->id,
                'player_id' => $playerInId,
                'team_id' => $team === 'home' ? $game->home_team_id : $game->away_team_id,
                'action_type' => 'substitution_in',
                'period' => $liveGame->current_period,
                'time_remaining' => $liveGame->period_time_remaining,
                'substituted_player_id' => $playerOutId,
                'substitution_reason' => $reason,
                'recorded_by_user_id' => auth()->id(),
                'recorded_at' => now(),
            ]);

            // Update players on court
            $playersOnCourt = $liveGame->{\"players_on_court_{$team}\"} ?? [];
            $playersOnCourt = array_map('intval', $playersOnCourt);
            
            // Remove player out and add player in
            $playersOnCourt = array_diff($playersOnCourt, [$playerOutId]);
            $playersOnCourt[] = $playerInId;
            
            $liveGame->updatePlayersOnCourt($team, array_values($playersOnCourt));
        });
    }

    /**
     * Correct a game action.
     */
    public function correctAction(GameAction $action, array $correctedData, string $reason): GameAction
    {
        return DB::transaction(function () use ($action, $correctedData, $reason) {
            // Mark as corrected
            $action->update([
                'is_corrected' => true,
                'corrected_by_user_id' => auth()->id(),
                'correction_reason' => $reason,
            ]);

            // Update the corrected fields
            $action->update(array_intersect_key($correctedData, array_flip($action->getFillable())));

            // Recalculate live game state if necessary
            if (isset($correctedData['points']) || isset($correctedData['action_type'])) {
                $this->recalculateLiveGameState($action->game);
            }

            return $action;
        });
    }

    /**
     * Delete a game action.
     */
    public function deleteAction(GameAction $action): void
    {
        DB::transaction(function () use ($action) {
            $game = $action->game;
            
            $action->delete();
            
            // Recalculate live game state
            $this->recalculateLiveGameState($game);
        });
    }

    /**
     * Finish a game.
     */
    public function finishGame(Game $game): Game
    {
        return DB::transaction(function () use ($game) {
            $liveGame = $game->liveGame;
            
            if (!$liveGame) {
                throw new \Exception('Spiel ist nicht live.');
            }

            // Update final scores
            $game->update([
                'status' => 'finished',
                'home_team_score' => $liveGame->current_score_home,
                'away_team_score' => $liveGame->current_score_away,
                'period_scores' => $liveGame->period_scores,
                'actual_end_time' => now(),
                'team_fouls' => [
                    'home' => $liveGame->fouls_home_total,
                    'away' => $liveGame->fouls_away_total,
                ],
                'timeouts' => [
                    'home_used' => 5 - $liveGame->timeouts_home_remaining,
                    'away_used' => 5 - $liveGame->timeouts_away_remaining,
                ],
            ]);

            // Calculate duration
            if ($game->actual_start_time && $game->actual_end_time) {
                $duration = $game->actual_start_time->diffInMinutes($game->actual_end_time);
                $game->update(['duration_minutes' => $duration]);
            }

            // Stop broadcasting
            $liveGame->update([
                'is_being_broadcasted' => false,
                'game_phase' => 'postgame',
                'period_is_running' => false,
                'shot_clock_is_running' => false,
            ]);

            return $game;
        });
    }

    /**
     * Calculate total game clock seconds elapsed.
     */
    private function calculateGameClockSeconds(LiveGame $liveGame): int
    {
        $periodLength = $liveGame->game->period_length_minutes * 60;
        $periodsCompleted = $liveGame->current_period - 1;
        $currentPeriodElapsed = $liveGame->period_time_elapsed_seconds;
        
        return ($periodsCompleted * $periodLength) + $currentPeriodElapsed;
    }

    /**
     * Check if shot clock should be reset.
     */
    private function shouldResetShotClock(GameAction $action): bool
    {
        $resetActions = [
            'field_goal_made', 'three_point_made',
            'rebound_offensive', 'foul_personal', 'foul_technical'
        ];
        
        return in_array($action->action_type, $resetActions);
    }

    /**
     * Check if game should end.
     */
    private function shouldEndGame(LiveGame $liveGame): bool
    {
        $game = $liveGame->game;
        
        // Regular time finished
        if ($liveGame->current_period >= $game->total_periods) {
            // Check if tied and overtime enabled
            if ($liveGame->current_score_home === $liveGame->current_score_away) {
                return false; // Go to overtime
            }
            return true; // Game ends
        }
        
        return false;
    }

    /**
     * Recalculate live game state from actions.
     */
    private function recalculateLiveGameState(Game $game): void
    {
        $liveGame = $game->liveGame;
        
        if (!$liveGame) {
            return;
        }

        // Recalculate scores from actions
        $homeScore = $game->gameActions()
            ->where('team_id', $game->home_team_id)
            ->sum('points');
            
        $awayScore = $game->gameActions()
            ->where('team_id', $game->away_team_id)
            ->sum('points');

        // Recalculate fouls
        $homeFouls = $game->gameActions()
            ->where('team_id', $game->home_team_id)
            ->where('action_type', 'like', 'foul_%')
            ->count();
            
        $awayFouls = $game->gameActions()
            ->where('team_id', $game->away_team_id)
            ->where('action_type', 'like', 'foul_%')
            ->count();

        $liveGame->update([
            'current_score_home' => $homeScore,
            'current_score_away' => $awayScore,
            'fouls_home_total' => $homeFouls,
            'fouls_away_total' => $awayFouls,
            'last_update_at' => now(),
        ]);
    }
}