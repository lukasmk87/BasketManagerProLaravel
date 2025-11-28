<?php

namespace App\Services\Statistics;

use App\Models\Game;
use App\Models\Team;
use App\Models\GameAction;
use Illuminate\Support\Facades\Cache;

/**
 * TeamStatisticsService
 *
 * Verantwortung: Team-spezifische Statistiken inkl. Game-Stats,
 * Season-Stats, Pace, Analytics, Splits und Trends.
 * Verwendet PERF-008 Chunking für memory-effiziente Season-Statistiken.
 */
class TeamStatisticsService
{
    public function __construct(
        private AdvancedMetricsService $metricsService,
        private StatisticsCacheManager $cacheManager
    ) {}

    /**
     * Get team statistics wrapper method (used by StatisticsController).
     */
    public function getTeamStatistics(Team $team): array
    {
        $season = $team->season ?? '2024-25';
        return $this->getTeamSeasonStats($team, $season);
    }

    /**
     * Get team statistics for a specific game.
     */
    public function getTeamGameStats(Team $team, Game $game): array
    {
        $cacheKey = $this->cacheManager->buildCacheKey('team_game', [
            'team_id' => $team->id,
            'game_id' => $game->id,
        ]);

        // PERF-007: Dynamic TTL based on game status
        $ttl = $this->cacheManager->getCacheTtlForGame($game);

        return Cache::remember($cacheKey, $ttl, function () use ($team, $game) {
            $actions = GameAction::where('game_id', $game->id)
                ->where('team_id', $team->id)
                ->with('player')
                ->get();

            $stats = $this->calculateTeamStatsFromActions($actions);

            // Add game-specific info
            $stats['opponent'] = $game->getOpponentTeam($team)->name;
            $stats['final_score'] = $game->isHomeTeam($team) ? $game->home_team_score : $game->away_team_score;
            $stats['opponent_score'] = $game->isHomeTeam($team) ? $game->away_team_score : $game->home_team_score;
            $stats['is_win'] = $stats['final_score'] > $stats['opponent_score'];
            $stats['margin'] = $stats['final_score'] - $stats['opponent_score'];

            return $stats;
        });
    }

    /**
     * Get team statistics for a season.
     * PERF-008: Uses chunking for memory optimization with large datasets.
     */
    public function getTeamSeasonStats(Team $team, string $season): array
    {
        $cacheKey = $this->cacheManager->buildCacheKey('team_season', [
            'team_id' => $team->id,
            'season' => $season,
        ]);

        // PERF-007: Season stats use longer TTL (24 hours)
        return Cache::remember($cacheKey, $this->cacheManager->getSeasonCacheTtl(), function () use ($team, $season) {
            $stats = [
                'games_played' => 0,
                'wins' => 0,
                'losses' => 0,
                'points_for' => 0,
                'points_against' => 0,
                'total_rebounds' => 0,
                'assists' => 0,
                'steals' => 0,
                'blocks' => 0,
                'turnovers' => 0,
                'personal_fouls' => 0,
                'field_goals_made' => 0,
                'field_goals_attempted' => 0,
                'three_points_made' => 0,
                'three_points_attempted' => 0,
                'free_throws_made' => 0,
                'free_throws_attempted' => 0,
            ];

            // PERF-008: Process games in chunks of 50 (with eager-loaded actions per game)
            Game::where('season', $season)
                ->where('status', 'finished')
                ->where(function ($query) use ($team) {
                    $query->where('home_team_id', $team->id)
                          ->orWhere('away_team_id', $team->id);
                })
                ->select(['id', 'home_team_id', 'away_team_id', 'home_team_score', 'away_team_score'])
                ->chunkById(50, function ($games) use ($team, &$stats) {
                    // Load game actions for this chunk only
                    $gameIds = $games->pluck('id');
                    $allActions = GameAction::whereIn('game_id', $gameIds)
                        ->where('team_id', $team->id)
                        ->select(['id', 'game_id', 'action_type'])
                        ->get()
                        ->groupBy('game_id');

                    foreach ($games as $game) {
                        $stats['games_played']++;

                        $isHome = $game->home_team_id === $team->id;
                        $teamScore = $isHome ? $game->home_team_score : $game->away_team_score;
                        $opponentScore = $isHome ? $game->away_team_score : $game->home_team_score;

                        $stats['points_for'] += $teamScore;
                        $stats['points_against'] += $opponentScore;

                        if ($teamScore > $opponentScore) {
                            $stats['wins']++;
                        } else {
                            $stats['losses']++;
                        }

                        // Aggregate team stats from game actions
                        $gameActions = $allActions->get($game->id) ?? collect();
                        $gameStats = $this->calculateTeamStatsFromActions($gameActions);

                        $stats['total_rebounds'] += $gameStats['total_rebounds'];
                        $stats['assists'] += $gameStats['assists'];
                        $stats['steals'] += $gameStats['steals'];
                        $stats['blocks'] += $gameStats['blocks'];
                        $stats['turnovers'] += $gameStats['turnovers'];
                        $stats['personal_fouls'] += $gameStats['personal_fouls'];
                        $stats['field_goals_made'] += $gameStats['field_goals_made'];
                        $stats['field_goals_attempted'] += $gameStats['field_goals_attempted'];
                        $stats['three_points_made'] += $gameStats['three_points_made'];
                        $stats['three_points_attempted'] += $gameStats['three_points_attempted'];
                        $stats['free_throws_made'] += $gameStats['free_throws_made'];
                        $stats['free_throws_attempted'] += $gameStats['free_throws_attempted'];
                    }
                });

            // Calculate percentages and averages
            $stats = $this->calculateAdvancedTeamStats($stats);

            return $stats;
        });
    }

    /**
     * Get team's pace (possessions per 48 minutes).
     */
    public function getTeamPace(Team $team, Game $game): float
    {
        $minutes = $game->duration_minutes ?? 40;
        $possessions = $this->estimateTeamPossessions($team, $game);

        return $minutes > 0 ? round(($possessions / $minutes) * 48, 1) : 0;
    }

    /**
     * Get comprehensive team analytics.
     */
    public function getTeamAnalytics(Team $team, string $season): array
    {
        $seasonStats = $this->getTeamSeasonStats($team, $season);

        return array_merge($seasonStats, [
            'four_factors' => $this->metricsService->calculateFourFactors($seasonStats),
            'clutch_stats' => $this->getClutchStats($team, $season),
            'home_away_splits' => $this->getHomeAwaySplits($team, $season),
            'monthly_trends' => $this->getMonthlyTrends($team, $season),
        ]);
    }

    /**
     * Calculate team statistics from game actions.
     */
    public function calculateTeamStatsFromActions($actions): array
    {
        $stats = [
            'total_rebounds' => 0,
            'assists' => 0,
            'steals' => 0,
            'blocks' => 0,
            'turnovers' => 0,
            'personal_fouls' => 0,
            'field_goals_made' => 0,
            'field_goals_attempted' => 0,
            'three_points_made' => 0,
            'three_points_attempted' => 0,
            'free_throws_made' => 0,
            'free_throws_attempted' => 0,
        ];

        foreach ($actions as $action) {
            switch ($action->action_type) {
                case 'field_goal_made':
                    $stats['field_goals_made']++;
                    break;
                case 'field_goal_missed':
                    $stats['field_goals_attempted']++;
                    break;
                case 'three_point_made':
                    $stats['three_points_made']++;
                    break;
                case 'three_point_missed':
                    $stats['three_points_attempted']++;
                    break;
                case 'free_throw_made':
                    $stats['free_throws_made']++;
                    break;
                case 'free_throw_missed':
                    $stats['free_throws_attempted']++;
                    break;
                case 'rebound_offensive':
                case 'rebound_defensive':
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
            }
        }

        // Adjust attempted stats
        $stats['field_goals_attempted'] += $stats['field_goals_made'];
        $stats['three_points_attempted'] += $stats['three_points_made'];
        $stats['free_throws_attempted'] += $stats['free_throws_made'];

        return $stats;
    }

    /**
     * Calculate advanced team statistics.
     */
    public function calculateAdvancedTeamStats(array $stats): array
    {
        $gamesPlayed = $stats['games_played'];

        if ($gamesPlayed > 0) {
            // Averages per game
            $stats['avg_points_for'] = round($stats['points_for'] / $gamesPlayed, 1);
            $stats['avg_points_against'] = round($stats['points_against'] / $gamesPlayed, 1);
            $stats['avg_rebounds'] = round($stats['total_rebounds'] / $gamesPlayed, 1);
            $stats['avg_assists'] = round($stats['assists'] / $gamesPlayed, 1);

            // Win percentage
            $stats['win_percentage'] = round(($stats['wins'] / $gamesPlayed) * 100, 1);

            // Shooting percentages
            $stats['field_goal_percentage'] = $stats['field_goals_attempted'] > 0
                ? round(($stats['field_goals_made'] / $stats['field_goals_attempted']) * 100, 1)
                : 0;

            $stats['three_point_percentage'] = $stats['three_points_attempted'] > 0
                ? round(($stats['three_points_made'] / $stats['three_points_attempted']) * 100, 1)
                : 0;

            $stats['free_throw_percentage'] = $stats['free_throws_attempted'] > 0
                ? round(($stats['free_throws_made'] / $stats['free_throws_attempted']) * 100, 1)
                : 0;

            // Advanced metrics
            $stats['offensive_rating'] = $this->metricsService->calculateOffensiveRating($stats);
            $stats['defensive_rating'] = $this->metricsService->calculateDefensiveRating($stats);
            $stats['net_rating'] = $stats['offensive_rating'] - $stats['defensive_rating'];
        }

        return $stats;
    }

    /**
     * Get clutch time statistics (last 5 minutes, score within 5 points).
     */
    public function getClutchStats(Team $team, string $season): array
    {
        // This would require more complex queries to identify clutch situations
        return [
            'clutch_games' => 0,
            'clutch_wins' => 0,
            'clutch_fg_percentage' => 0,
            'clutch_points_per_game' => 0,
        ];
    }

    /**
     * Get home vs away performance splits.
     */
    public function getHomeAwaySplits(Team $team, string $season): array
    {
        $homeGames = Game::where('season', $season)
            ->where('status', 'finished')
            ->where('home_team_id', $team->id)
            ->get();

        $awayGames = Game::where('season', $season)
            ->where('status', 'finished')
            ->where('away_team_id', $team->id)
            ->get();

        return [
            'home' => [
                'games' => $homeGames->count(),
                'wins' => $homeGames->where('winning_team_id', $team->id)->count(),
                'avg_points' => $homeGames->avg('home_team_score'),
                'avg_allowed' => $homeGames->avg('away_team_score'),
            ],
            'away' => [
                'games' => $awayGames->count(),
                'wins' => $awayGames->where('winning_team_id', $team->id)->count(),
                'avg_points' => $awayGames->avg('away_team_score'),
                'avg_allowed' => $awayGames->avg('home_team_score'),
            ],
        ];
    }

    /**
     * Get monthly performance trends.
     */
    public function getMonthlyTrends(Team $team, string $season): array
    {
        $games = Game::where('season', $season)
            ->where('status', 'finished')
            ->where(function ($query) use ($team) {
                $query->where('home_team_id', $team->id)
                      ->orWhere('away_team_id', $team->id);
            })
            ->get();

        $trends = [];
        foreach ($games->groupBy(fn($game) => $game->scheduled_at->format('Y-m')) as $month => $monthGames) {
            $wins = $monthGames->where('winning_team_id', $team->id)->count();
            $trends[$month] = [
                'games' => $monthGames->count(),
                'wins' => $wins,
                'losses' => $monthGames->count() - $wins,
                'win_percentage' => $monthGames->count() > 0 ?
                    round(($wins / $monthGames->count()) * 100, 1) : 0,
            ];
        }

        return $trends;
    }

    /**
     * Estimate team possessions for a game.
     */
    private function estimateTeamPossessions(Team $team, Game $game): int
    {
        $teamActions = GameAction::where('game_id', $game->id)
            ->where('team_id', $team->id)
            ->get();

        $fga = $teamActions->whereIn('action_type', [
            'field_goal_made', 'field_goal_missed',
            'three_point_made', 'three_point_missed'
        ])->count();

        $fta = $teamActions->whereIn('action_type', [
            'free_throw_made', 'free_throw_missed'
        ])->count();

        $turnovers = $teamActions->where('action_type', 'turnover')->count();

        return $fga + ($fta * 0.44) + $turnovers;
    }
}
