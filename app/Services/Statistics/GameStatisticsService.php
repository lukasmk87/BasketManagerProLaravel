<?php

namespace App\Services\Statistics;

use App\Models\Game;
use App\Models\GameAction;

/**
 * GameStatisticsService
 *
 * Verantwortung: Game-Level Statistiken inkl. Gesamtübersicht,
 * aktuelle Spielstände und Action-Summaries.
 */
class GameStatisticsService
{
    public function __construct(
        private TeamStatisticsService $teamStatsService,
        private StatisticsCacheManager $cacheManager
    ) {}

    /**
     * Get game statistics wrapper method (used by StatisticsController).
     */
    public function getGameStatistics(Game $game): array
    {
        // PERF-003: Combined 3 separate count() queries into 1 aggregated query
        $actionCounts = GameAction::where('game_id', $game->id)
            ->selectRaw('team_id, COUNT(*) as count')
            ->groupBy('team_id')
            ->pluck('count', 'team_id');

        $homeTeamActions = $actionCounts->get($game->home_team_id, 0);
        $awayTeamActions = $actionCounts->get($game->away_team_id, 0);
        $totalActions = $actionCounts->sum();

        return [
            'total_actions' => $totalActions,
            'home_team_actions' => $homeTeamActions,
            'away_team_actions' => $awayTeamActions,
            'duration' => $game->duration_minutes ?? 0,
            'finished_at' => $game->finished_at,
            'actions_summary' => $this->getGameActionsSummary($game),
        ];
    }

    /**
     * Get current game statistics (for live games).
     */
    public function getCurrentGameStats(Game $game): array
    {
        if (!$game->liveGame) {
            return [];
        }

        $homeStats = $this->teamStatsService->getTeamGameStats($game->homeTeam, $game);
        $awayStats = $this->teamStatsService->getTeamGameStats($game->awayTeam, $game);

        return [
            'home' => array_merge($homeStats, [
                'current_score' => $game->liveGame->current_score_home,
                'fouls' => $game->liveGame->fouls_home_total,
                'timeouts_remaining' => $game->liveGame->timeouts_home_remaining,
            ]),
            'away' => array_merge($awayStats, [
                'current_score' => $game->liveGame->current_score_away,
                'fouls' => $game->liveGame->fouls_away_total,
                'timeouts_remaining' => $game->liveGame->timeouts_away_remaining,
            ]),
            'game_info' => [
                'period' => $game->liveGame->current_period,
                'time_remaining' => $game->liveGame->period_time_remaining,
                'is_running' => $game->liveGame->period_is_running,
                'phase' => $game->liveGame->game_phase,
            ]
        ];
    }

    /**
     * Get game actions summary.
     */
    public function getGameActionsSummary(Game $game): array
    {
        $actions = $game->gameActions()
            ->selectRaw('action_type, COUNT(*) as count')
            ->groupBy('action_type')
            ->pluck('count', 'action_type')
            ->toArray();

        return $actions;
    }
}
