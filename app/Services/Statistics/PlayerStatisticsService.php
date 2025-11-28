<?php

namespace App\Services\Statistics;

use App\Models\Game;
use App\Models\Player;
use App\Models\GameAction;
use Illuminate\Support\Facades\Cache;

/**
 * PlayerStatisticsService
 *
 * Verantwortung: Spieler-spezifische Statistiken inkl. Game-Stats,
 * Season-Stats und Advanced Player Metrics.
 * Verwendet PERF-008 Chunking für memory-effiziente Season-Statistiken.
 */
class PlayerStatisticsService
{
    public function __construct(
        private AdvancedMetricsService $metricsService,
        private StatisticsCacheManager $cacheManager
    ) {}

    /**
     * Get player statistics wrapper method (used by StatisticsController).
     */
    public function getPlayerStatistics(Player $player, ?string $season = null): array
    {
        $season = $season ?? $player->team?->season ?? '2024-25';
        return $this->getPlayerSeasonStats($player, $season);
    }

    /**
     * Get player statistics for a specific game.
     */
    public function getPlayerGameStats(Player $player, Game $game): array
    {
        $cacheKey = $this->cacheManager->buildCacheKey('player_game', [
            'player_id' => $player->id,
            'game_id' => $game->id,
        ]);

        // PERF-007: Dynamic TTL based on game status
        $ttl = $this->cacheManager->getCacheTtlForGame($game);

        return Cache::remember($cacheKey, $ttl, function () use ($player, $game) {
            $actions = GameAction::where('game_id', $game->id)
                ->where('player_id', $player->id)
                ->get();

            return $this->calculatePlayerStatsFromActions($actions);
        });
    }

    /**
     * Get player statistics for a season.
     * PERF-008: Uses chunking for memory optimization with large datasets.
     */
    public function getPlayerSeasonStats(Player $player, string $season): array
    {
        $cacheKey = $this->cacheManager->buildCacheKey('player_season', [
            'player_id' => $player->id,
            'season' => $season,
        ]);

        // PERF-007: Season stats use longer TTL (24 hours)
        return Cache::remember($cacheKey, $this->cacheManager->getSeasonCacheTtl(), function () use ($player, $season) {
            // PERF-008: Use chunking with aggregation instead of loading all actions at once
            $aggregatedStats = $this->initializePlayerStatsArray();
            $gameIds = [];

            // Process actions in chunks of 500
            GameAction::whereHas('game', function ($query) use ($season) {
                    $query->where('season', $season)->where('status', 'finished');
                })
                ->where('player_id', $player->id)
                ->select(['id', 'game_id', 'player_id', 'action_type', 'period', 'points'])
                ->chunkById(500, function ($actions) use (&$aggregatedStats, &$gameIds) {
                    foreach ($actions as $action) {
                        $gameIds[$action->game_id] = true;
                        $this->aggregateActionToStats($action, $aggregatedStats);
                    }
                });

            // Finalize stats calculation
            $this->finalizePlayerStats($aggregatedStats);

            // Add season-specific calculations
            $gamesPlayed = count($gameIds);
            $aggregatedStats['games_played'] = $gamesPlayed;

            // Calculate averages
            if ($gamesPlayed > 0) {
                $aggregatedStats['avg_points'] = round($aggregatedStats['total_points'] / $gamesPlayed, 1);
                $aggregatedStats['avg_rebounds'] = round($aggregatedStats['total_rebounds'] / $gamesPlayed, 1);
                $aggregatedStats['avg_assists'] = round($aggregatedStats['assists'] / $gamesPlayed, 1);
                $aggregatedStats['avg_steals'] = round($aggregatedStats['steals'] / $gamesPlayed, 1);
                $aggregatedStats['avg_blocks'] = round($aggregatedStats['blocks'] / $gamesPlayed, 1);
                $aggregatedStats['avg_turnovers'] = round($aggregatedStats['turnovers'] / $gamesPlayed, 1);
                $aggregatedStats['avg_fouls'] = round($aggregatedStats['personal_fouls'] / $gamesPlayed, 1);
            }

            return $aggregatedStats;
        });
    }

    /**
     * Get advanced player metrics.
     */
    public function getAdvancedPlayerStats(Player $player, Game $game): array
    {
        $stats = $this->getPlayerGameStats($player, $game);

        return [
            'usage_rate' => $this->metricsService->calculateUsageRate($player, $game),
            'player_impact_estimate' => $this->metricsService->calculatePlayerImpact($stats),
            'plus_minus' => $this->metricsService->calculatePlusMinus($player, $game),
            'effective_field_goal_percentage' => $this->metricsService->calculateEffectiveFGPercentage($stats),
            'assist_to_turnover_ratio' => $this->metricsService->calculateAssistToTurnoverRatio($stats),
            'steal_percentage' => $this->metricsService->calculateStealPercentage($player, $game),
            'block_percentage' => $this->metricsService->calculateBlockPercentage($player, $game),
            'rebound_percentage' => $this->metricsService->calculateReboundPercentage($player, $game),
            'points_per_possession' => $this->metricsService->calculatePointsPerPossession($stats),
            'game_score' => $this->metricsService->calculateGameScore($stats),
        ];
    }

    /**
     * Calculate player statistics from game actions.
     */
    public function calculatePlayerStatsFromActions($actions): array
    {
        $stats = [
            'total_points' => 0,
            'field_goals_made' => 0,
            'field_goals_attempted' => 0,
            'three_points_made' => 0,
            'three_points_attempted' => 0,
            'free_throws_made' => 0,
            'free_throws_attempted' => 0,
            'rebounds_offensive' => 0,
            'rebounds_defensive' => 0,
            'total_rebounds' => 0,
            'assists' => 0,
            'steals' => 0,
            'blocks' => 0,
            'turnovers' => 0,
            'personal_fouls' => 0,
            'technical_fouls' => 0,
        ];

        foreach ($actions as $action) {
            switch ($action->action_type) {
                case 'field_goal_made':
                    $stats['field_goals_made']++;
                    $stats['total_points'] += 2;
                    break;
                case 'field_goal_missed':
                    $stats['field_goals_attempted']++;
                    break;
                case 'three_point_made':
                    $stats['three_points_made']++;
                    $stats['total_points'] += 3;
                    break;
                case 'three_point_missed':
                    $stats['three_points_attempted']++;
                    break;
                case 'free_throw_made':
                    $stats['free_throws_made']++;
                    $stats['total_points'] += 1;
                    break;
                case 'free_throw_missed':
                    $stats['free_throws_attempted']++;
                    break;
                case 'rebound_offensive':
                    $stats['rebounds_offensive']++;
                    $stats['total_rebounds']++;
                    break;
                case 'rebound_defensive':
                    $stats['rebounds_defensive']++;
                    $stats['total_rebounds']++;
                    break;
                case 'assist':
                    $stats['assists']++;
                    break;
                case 'steal':
                    $stats['steals']++;
                    break;
                case 'block':
                    $stats['blocks']++;
                    break;
                case 'turnover':
                    $stats['turnovers']++;
                    break;
                case 'foul_personal':
                    $stats['personal_fouls']++;
                    break;
                case 'foul_technical':
                    $stats['technical_fouls']++;
                    break;
            }
        }

        // Adjust attempted stats to include made shots
        $stats['field_goals_attempted'] += $stats['field_goals_made'];
        $stats['three_points_attempted'] += $stats['three_points_made'];
        $stats['free_throws_attempted'] += $stats['free_throws_made'];

        // Calculate shooting percentages
        $stats['field_goal_percentage'] = $stats['field_goals_attempted'] > 0
            ? round(($stats['field_goals_made'] / $stats['field_goals_attempted']) * 100, 1)
            : 0;

        $stats['three_point_percentage'] = $stats['three_points_attempted'] > 0
            ? round(($stats['three_points_made'] / $stats['three_points_attempted']) * 100, 1)
            : 0;

        $stats['free_throw_percentage'] = $stats['free_throws_attempted'] > 0
            ? round(($stats['free_throws_made'] / $stats['free_throws_attempted']) * 100, 1)
            : 0;

        // Calculate advanced stats
        $stats['true_shooting_percentage'] = $this->metricsService->calculateTrueShootingPercentage(
            $stats['total_points'],
            $stats['field_goals_attempted'],
            $stats['free_throws_attempted']
        );

        $stats['player_efficiency_rating'] = $this->metricsService->calculatePlayerEfficiencyRating($stats);

        return $stats;
    }

    /**
     * Initialize empty player stats array for chunked aggregation.
     * PERF-008: Used by getPlayerSeasonStats() for memory-efficient processing.
     */
    public function initializePlayerStatsArray(): array
    {
        return [
            'total_points' => 0,
            'field_goals_made' => 0,
            'field_goals_attempted' => 0,
            'three_points_made' => 0,
            'three_points_attempted' => 0,
            'free_throws_made' => 0,
            'free_throws_attempted' => 0,
            'rebounds_offensive' => 0,
            'rebounds_defensive' => 0,
            'total_rebounds' => 0,
            'assists' => 0,
            'steals' => 0,
            'blocks' => 0,
            'turnovers' => 0,
            'personal_fouls' => 0,
            'technical_fouls' => 0,
        ];
    }

    /**
     * Aggregate a single game action to stats array.
     * PERF-008: Used by getPlayerSeasonStats() for chunked processing.
     */
    public function aggregateActionToStats(GameAction $action, array &$stats): void
    {
        switch ($action->action_type) {
            case 'field_goal_made':
                $stats['field_goals_made']++;
                $stats['total_points'] += 2;
                break;
            case 'field_goal_missed':
                $stats['field_goals_attempted']++;
                break;
            case 'three_point_made':
                $stats['three_points_made']++;
                $stats['total_points'] += 3;
                break;
            case 'three_point_missed':
                $stats['three_points_attempted']++;
                break;
            case 'free_throw_made':
                $stats['free_throws_made']++;
                $stats['total_points'] += 1;
                break;
            case 'free_throw_missed':
                $stats['free_throws_attempted']++;
                break;
            case 'rebound_offensive':
                $stats['rebounds_offensive']++;
                $stats['total_rebounds']++;
                break;
            case 'rebound_defensive':
                $stats['rebounds_defensive']++;
                $stats['total_rebounds']++;
                break;
            case 'assist':
                $stats['assists']++;
                break;
            case 'steal':
                $stats['steals']++;
                break;
            case 'block':
                $stats['blocks']++;
                break;
            case 'turnover':
                $stats['turnovers']++;
                break;
            case 'foul_personal':
                $stats['personal_fouls']++;
                break;
            case 'foul_technical':
                $stats['technical_fouls']++;
                break;
        }
    }

    /**
     * Finalize player stats by calculating percentages and advanced metrics.
     * PERF-008: Used after chunked aggregation is complete.
     */
    public function finalizePlayerStats(array &$stats): void
    {
        // Adjust attempted stats to include made shots
        $stats['field_goals_attempted'] += $stats['field_goals_made'];
        $stats['three_points_attempted'] += $stats['three_points_made'];
        $stats['free_throws_attempted'] += $stats['free_throws_made'];

        // Calculate shooting percentages
        $stats['field_goal_percentage'] = $stats['field_goals_attempted'] > 0
            ? round(($stats['field_goals_made'] / $stats['field_goals_attempted']) * 100, 1)
            : 0;

        $stats['three_point_percentage'] = $stats['three_points_attempted'] > 0
            ? round(($stats['three_points_made'] / $stats['three_points_attempted']) * 100, 1)
            : 0;

        $stats['free_throw_percentage'] = $stats['free_throws_attempted'] > 0
            ? round(($stats['free_throws_made'] / $stats['free_throws_attempted']) * 100, 1)
            : 0;

        // Calculate advanced stats
        $stats['true_shooting_percentage'] = $this->metricsService->calculateTrueShootingPercentage(
            $stats['total_points'],
            $stats['field_goals_attempted'],
            $stats['free_throws_attempted']
        );

        $stats['player_efficiency_rating'] = $this->metricsService->calculatePlayerEfficiencyRating($stats);
    }
}
