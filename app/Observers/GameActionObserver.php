<?php

namespace App\Observers;

use App\Models\GameAction;
use App\Services\Statistics\StatisticsService;
use App\Services\BasketballCacheService;
use Illuminate\Support\Facades\Log;

/**
 * PERF-007: GameActionObserver for automatic cache invalidation.
 *
 * This observer automatically invalidates statistics cache when game actions
 * are created, updated, or deleted. This ensures that cached statistics are
 * always up-to-date without requiring manual cache invalidation.
 */
class GameActionObserver
{
    public function __construct(
        private StatisticsService $statisticsService,
        private BasketballCacheService $basketballCacheService
    ) {}

    /**
     * Handle the GameAction "created" event.
     */
    public function created(GameAction $gameAction): void
    {
        $this->invalidateCaches($gameAction);

        Log::debug('GameActionObserver: Cache invalidated for new game action', [
            'game_action_id' => $gameAction->id,
            'game_id' => $gameAction->game_id,
            'player_id' => $gameAction->player_id,
            'action_type' => $gameAction->action_type,
        ]);
    }

    /**
     * Handle the GameAction "updated" event.
     */
    public function updated(GameAction $gameAction): void
    {
        $this->invalidateCaches($gameAction);

        Log::debug('GameActionObserver: Cache invalidated for updated game action', [
            'game_action_id' => $gameAction->id,
            'game_id' => $gameAction->game_id,
            'player_id' => $gameAction->player_id,
            'action_type' => $gameAction->action_type,
        ]);
    }

    /**
     * Handle the GameAction "deleted" event.
     */
    public function deleted(GameAction $gameAction): void
    {
        $this->invalidateCaches($gameAction);

        Log::debug('GameActionObserver: Cache invalidated for deleted game action', [
            'game_action_id' => $gameAction->id,
            'game_id' => $gameAction->game_id,
            'player_id' => $gameAction->player_id,
            'action_type' => $gameAction->action_type,
        ]);
    }

    /**
     * Invalidate all relevant caches for a game action.
     */
    private function invalidateCaches(GameAction $gameAction): void
    {
        // Load relationships if not already loaded
        $game = $gameAction->game;
        $player = $gameAction->player;
        $team = $gameAction->team;

        // 1. Invalidate player cache (if player exists)
        if ($player && $game) {
            $this->statisticsService->clearPlayerCache($player, $game);
        }

        // 2. Invalidate team cache (if team exists)
        if ($team && $game) {
            $this->statisticsService->clearTeamCache($team, $game);
        }

        // 3. Invalidate game cache using BasketballCacheService
        if ($game) {
            $this->basketballCacheService->invalidateGameCache($game->id);
        }

        // 4. For scoring actions, also invalidate leaderboards
        if ($this->isScoringAction($gameAction)) {
            $this->basketballCacheService->invalidateLeaderboards();
        }
    }

    /**
     * Check if the game action is a scoring action.
     */
    private function isScoringAction(GameAction $gameAction): bool
    {
        $scoringActions = [
            'field_goal_made',
            'three_point_made',
            'free_throw_made',
        ];

        return in_array($gameAction->action_type, $scoringActions, true);
    }
}
