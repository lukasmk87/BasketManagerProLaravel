<?php

namespace App\Listeners;

use App\Services\BasketballCacheService;
use App\Events\{
    GameActionAdded,
    GameFinished,
    GameStarted,
    GameScoreUpdated,
    StatisticsUpdated
};
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Basketball Cache Invalidation Listener
 * 
 * Automatische Cache-Invalidierung basierend auf Basketball-Events
 */
class CacheInvalidationListener implements ShouldQueue
{
    use InteractsWithQueue;

    private BasketballCacheService $cacheService;

    /**
     * Create the event listener.
     */
    public function __construct(BasketballCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Handle game action added events
     */
    public function handleGameActionAdded(GameActionAdded $event): void
    {
        Log::info('Handling GameActionAdded cache invalidation', [
            'game_id' => $event->gameAction->game_id,
            'player_id' => $event->gameAction->player_id,
            'action_type' => $event->gameAction->action_type
        ]);

        try {
            $gameAction = $event->gameAction;
            
            // Invalidate live game cache
            $this->cacheService->invalidateGameCache($gameAction->game_id);
            
            // Invalidate player statistics
            $this->cacheService->invalidatePlayerCache($gameAction->player_id);
            
            // Invalidate team statistics
            $this->cacheService->invalidateTeamCache($gameAction->team_id);
            
            // If it's a shot, invalidate shot chart
            if ($this->isShotAction($gameAction->action_type)) {
                $this->invalidateShotChartCache($gameAction->player_id);
            }
            
            // If it's a scoring action, invalidate leaderboards
            if ($this->isScoringAction($gameAction->action_type)) {
                $this->cacheService->invalidateLeaderboards();
            }

            Log::info('GameActionAdded cache invalidation completed');

        } catch (\Exception $e) {
            Log::error('Failed to invalidate cache for GameActionAdded', [
                'error' => $e->getMessage(),
                'game_id' => $event->gameAction->game_id ?? null
            ]);
        }
    }

    /**
     * Handle game finished events
     */
    public function handleGameFinished(GameFinished $event): void
    {
        Log::info('Handling GameFinished cache invalidation', [
            'game_id' => $event->game->id
        ]);

        try {
            $game = $event->game;
            
            // Invalidate live game cache
            $this->cacheService->invalidateGameCache($game->id);
            
            // Invalidate team caches for both teams
            $this->cacheService->invalidateTeamCache($game->home_team_id);
            $this->cacheService->invalidateTeamCache($game->away_team_id);
            
            // Invalidate all player caches for players in this game
            $this->invalidateGamePlayerCaches($game->id);
            
            // Invalidate leaderboards (final stats might affect rankings)
            $this->cacheService->invalidateLeaderboards();
            
            // Cache final game summary
            $this->cacheGameSummary($game);

            Log::info('GameFinished cache invalidation completed');

        } catch (\Exception $e) {
            Log::error('Failed to invalidate cache for GameFinished', [
                'error' => $e->getMessage(),
                'game_id' => $event->game->id ?? null
            ]);
        }
    }

    /**
     * Handle game started events
     */
    public function handleGameStarted(GameStarted $event): void
    {
        Log::info('Handling GameStarted cache invalidation', [
            'game_id' => $event->game->id
        ]);

        try {
            $game = $event->game;
            
            // Initialize live game cache
            $liveData = [
                'game_id' => $game->id,
                'home_score' => 0,
                'away_score' => 0,
                'period' => 1,
                'time_remaining' => '12:00',
                'status' => 'live',
                'home_stats' => [],
                'away_stats' => [],
                'last_action' => null
            ];
            
            $this->cacheService->cacheLiveGameData($game->id, $liveData);
            
            // Invalidate team schedules
            $this->cacheService->invalidateTeamCache($game->home_team_id);
            $this->cacheService->invalidateTeamCache($game->away_team_id);

            Log::info('GameStarted cache setup completed');

        } catch (\Exception $e) {
            Log::error('Failed to setup cache for GameStarted', [
                'error' => $e->getMessage(),
                'game_id' => $event->game->id ?? null
            ]);
        }
    }

    /**
     * Handle game score updated events
     */
    public function handleGameScoreUpdated(GameScoreUpdated $event): void
    {
        try {
            $liveGame = $event->liveGame;
            $game = $liveGame->game;
            
            // Update live game cache with new score
            $liveData = [
                'game_id' => $game->id,
                'home_score' => $liveGame->current_score_home,
                'away_score' => $liveGame->current_score_away,
                'period' => $liveGame->current_period,
                'time_remaining' => $liveGame->period_time_remaining,
                'status' => 'live',
                'home_stats' => $this->getTeamLiveStats($game->id, $game->home_team_id),
                'away_stats' => $this->getTeamLiveStats($game->id, $game->away_team_id),
                'last_action' => $event->lastAction ?? null
            ];
            
            $this->cacheService->cacheLiveGameData($game->id, $liveData);

        } catch (\Exception $e) {
            Log::error('Failed to update live game cache', [
                'error' => $e->getMessage(),
                'game_id' => $event->liveGame->game_id ?? null
            ]);
        }
    }

    /**
     * Handle statistics updated events
     */
    public function handleStatisticsUpdated(StatisticsUpdated $event): void
    {
        Log::info('Handling StatisticsUpdated cache invalidation', [
            'entity_type' => $event->entityType,
            'entity_id' => $event->entityId
        ]);

        try {
            switch ($event->entityType) {
                case 'player':
                    $this->cacheService->invalidatePlayerCache($event->entityId);
                    break;
                    
                case 'team':
                    $this->cacheService->invalidateTeamCache($event->entityId);
                    break;
                    
                case 'game':
                    $this->cacheService->invalidateGameCache($event->entityId);
                    break;
            }
            
            // Always invalidate leaderboards when statistics are updated
            $this->cacheService->invalidateLeaderboards();

            Log::info('StatisticsUpdated cache invalidation completed');

        } catch (\Exception $e) {
            Log::error('Failed to invalidate cache for StatisticsUpdated', [
                'error' => $e->getMessage(),
                'entity_type' => $event->entityType ?? null,
                'entity_id' => $event->entityId ?? null
            ]);
        }
    }

    /**
     * Handle multiple event types
     */
    public function handle($event): void
    {
        match (get_class($event)) {
            GameActionAdded::class => $this->handleGameActionAdded($event),
            GameFinished::class => $this->handleGameFinished($event),
            GameStarted::class => $this->handleGameStarted($event),
            GameScoreUpdated::class => $this->handleGameScoreUpdated($event),
            StatisticsUpdated::class => $this->handleStatisticsUpdated($event),
            default => Log::info('Unknown event type for cache invalidation: ' . get_class($event))
        };
    }

    /**
     * Determine if the job should be retried
     */
    public function shouldRetry(\Throwable $exception): bool
    {
        // Retry on Redis connection issues, but not on logic errors
        return str_contains($exception->getMessage(), 'Redis') || 
               str_contains($exception->getMessage(), 'Connection');
    }

    /**
     * Handle job failure
     */
    public function failed($event, \Throwable $exception): void
    {
        Log::error('Cache invalidation job failed permanently', [
            'event' => get_class($event),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }

    // Private helper methods

    /**
     * Check if action is a shot
     */
    private function isShotAction(string $actionType): bool
    {
        return in_array($actionType, [
            'field_goal_made', 'field_goal_missed',
            'three_point_made', 'three_point_missed',
            'free_throw_made', 'free_throw_missed'
        ]);
    }

    /**
     * Check if action affects scoring
     */
    private function isScoringAction(string $actionType): bool
    {
        return in_array($actionType, [
            'field_goal_made', 'three_point_made', 'free_throw_made'
        ]);
    }

    /**
     * Invalidate shot chart cache for player
     */
    private function invalidateShotChartCache(int $playerId): void
    {
        // Pattern für alle Shot Chart Varianten dieses Spielers
        $pattern = "shot_chart:{$playerId}:*";
        $this->cacheService->bulkInvalidate([$pattern]);
    }

    /**
     * Invalidate all player caches for a game
     */
    private function invalidateGamePlayerCaches(int $gameId): void
    {
        try {
            // Get all unique player IDs from game actions
            $playerIds = \DB::table('game_actions')
                ->where('game_id', $gameId)
                ->distinct()
                ->pluck('player_id');

            foreach ($playerIds as $playerId) {
                $this->cacheService->invalidatePlayerCache($playerId);
            }

        } catch (\Exception $e) {
            Log::warning('Failed to invalidate game player caches', [
                'game_id' => $gameId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Cache final game summary
     */
    private function cacheGameSummary($game): void
    {
        try {
            $summary = [
                'id' => $game->id,
                'home_team_id' => $game->home_team_id,
                'away_team_id' => $game->away_team_id,
                'home_score' => $game->home_team_score,
                'away_score' => $game->away_team_score,
                'played_at' => $game->played_at,
                'status' => $game->status,
                'summary_stats' => $this->calculateGameSummaryStats($game->id),
                'generated_at' => now()
            ];

            $this->cacheService->cacheGameSummary($game->id, $summary);

        } catch (\Exception $e) {
            Log::warning('Failed to cache game summary', [
                'game_id' => $game->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get team live statistics
     */
    private function getTeamLiveStats(int $gameId, int $teamId): array
    {
        try {
            return \DB::table('game_actions')
                ->where('game_id', $gameId)
                ->where('team_id', $teamId)
                ->selectRaw('
                    SUM(CASE WHEN action_type LIKE "%_made" THEN points ELSE 0 END) as points,
                    SUM(CASE WHEN action_type IN ("rebound_offensive", "rebound_defensive") THEN 1 ELSE 0 END) as rebounds,
                    SUM(CASE WHEN action_type = "assist" THEN 1 ELSE 0 END) as assists,
                    SUM(CASE WHEN action_type = "turnover" THEN 1 ELSE 0 END) as turnovers,
                    SUM(CASE WHEN action_type LIKE "field_goal_%" THEN 1 ELSE 0 END) as field_goal_attempts,
                    SUM(CASE WHEN action_type = "field_goal_made" THEN 1 ELSE 0 END) as field_goal_made
                ')
                ->first() ?? [];

        } catch (\Exception $e) {
            Log::warning('Failed to get team live stats', [
                'game_id' => $gameId,
                'team_id' => $teamId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Calculate game summary statistics
     */
    private function calculateGameSummaryStats(int $gameId): array
    {
        try {
            $stats = \DB::table('game_actions')
                ->where('game_id', $gameId)
                ->selectRaw('
                    team_id,
                    SUM(CASE WHEN action_type LIKE "%_made" THEN points ELSE 0 END) as points,
                    SUM(CASE WHEN action_type IN ("rebound_offensive", "rebound_defensive") THEN 1 ELSE 0 END) as rebounds,
                    SUM(CASE WHEN action_type = "assist" THEN 1 ELSE 0 END) as assists,
                    SUM(CASE WHEN action_type = "steal" THEN 1 ELSE 0 END) as steals,
                    SUM(CASE WHEN action_type = "block" THEN 1 ELSE 0 END) as blocks,
                    SUM(CASE WHEN action_type = "turnover" THEN 1 ELSE 0 END) as turnovers,
                    SUM(CASE WHEN action_type LIKE "field_goal_%" THEN 1 ELSE 0 END) as field_goal_attempts,
                    SUM(CASE WHEN action_type = "field_goal_made" THEN 1 ELSE 0 END) as field_goal_made,
                    SUM(CASE WHEN action_type LIKE "three_point_%" THEN 1 ELSE 0 END) as three_point_attempts,
                    SUM(CASE WHEN action_type = "three_point_made" THEN 1 ELSE 0 END) as three_point_made
                ')
                ->groupBy('team_id')
                ->get()
                ->keyBy('team_id')
                ->toArray();

            return $stats;

        } catch (\Exception $e) {
            Log::warning('Failed to calculate game summary stats', [
                'game_id' => $gameId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}