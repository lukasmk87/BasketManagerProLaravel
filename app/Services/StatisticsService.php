<?php

namespace App\Services;

use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use App\Models\GameAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class StatisticsService
{
    private string $cachePrefix = 'basketball:stats:';
    private int $defaultCacheTtl = 3600; // 1 hour

    /**
     * Get player statistics for a specific game.
     */
    public function getPlayerGameStats(Player $player, Game $game): array
    {
        $cacheKey = $this->cachePrefix . "player:{$player->id}:game:{$game->id}";
        
        return Cache::remember($cacheKey, $this->defaultCacheTtl, function () use ($player, $game) {
            $actions = GameAction::where('game_id', $game->id)
                ->where('player_id', $player->id)
                ->get();

            return $this->calculatePlayerStatsFromActions($actions);
        });
    }

    /**
     * Get player statistics for a season.
     */
    public function getPlayerSeasonStats(Player $player, string $season): array
    {
        $cacheKey = $this->cachePrefix . "player:{$player->id}:season:{$season}";
        
        return Cache::remember($cacheKey, $this->defaultCacheTtl, function () use ($player, $season) {
            $actions = GameAction::whereHas('game', function ($query) use ($season) {
                    $query->where('season', $season)->where('status', 'finished');
                })
                ->where('player_id', $player->id)
                ->with('game')
                ->get();

            $stats = $this->calculatePlayerStatsFromActions($actions);
            
            // Add season-specific calculations
            $gamesPlayed = $actions->groupBy('game_id')->count();
            $stats['games_played'] = $gamesPlayed;
            
            // Calculate averages
            if ($gamesPlayed > 0) {
                $stats['avg_points'] = round($stats['total_points'] / $gamesPlayed, 1);
                $stats['avg_rebounds'] = round($stats['total_rebounds'] / $gamesPlayed, 1);
                $stats['avg_assists'] = round($stats['assists'] / $gamesPlayed, 1);
                $stats['avg_steals'] = round($stats['steals'] / $gamesPlayed, 1);
                $stats['avg_blocks'] = round($stats['blocks'] / $gamesPlayed, 1);
                $stats['avg_turnovers'] = round($stats['turnovers'] / $gamesPlayed, 1);
                $stats['avg_fouls'] = round($stats['personal_fouls'] / $gamesPlayed, 1);
            }

            return $stats;
        });
    }

    /**
     * Get team statistics for a specific game.
     */
    public function getTeamGameStats(Team $team, Game $game): array
    {
        $cacheKey = $this->cachePrefix . "team:{$team->id}:game:{$game->id}";
        
        return Cache::remember($cacheKey, $this->defaultCacheTtl, function () use ($team, $game) {
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
     */
    public function getTeamSeasonStats(Team $team, string $season): array
    {
        $cacheKey = $this->cachePrefix . "team:{$team->id}:season:{$season}";
        
        return Cache::remember($cacheKey, $this->defaultCacheTtl, function () use ($team, $season) {
            // Get all games for this team in the season
            $games = Game::where('season', $season)
                ->where('status', 'finished')
                ->where(function ($query) use ($team) {
                    $query->where('home_team_id', $team->id)
                          ->orWhere('away_team_id', $team->id);
                })
                ->with(['gameActions' => function ($query) use ($team) {
                    $query->where('team_id', $team->id);
                }])
                ->get();

            $stats = [
                'games_played' => $games->count(),
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

            foreach ($games as $game) {
                $teamScore = $game->isHomeTeam($team) ? $game->home_team_score : $game->away_team_score;
                $opponentScore = $game->isHomeTeam($team) ? $game->away_team_score : $game->home_team_score;
                
                $stats['points_for'] += $teamScore;
                $stats['points_against'] += $opponentScore;
                
                if ($teamScore > $opponentScore) {
                    $stats['wins']++;
                } else {
                    $stats['losses']++;
                }

                // Aggregate team stats from game actions
                $gameStats = $this->calculateTeamStatsFromActions($game->gameActions);
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

            // Calculate percentages and averages
            $stats = $this->calculateAdvancedTeamStats($stats);

            return $stats;
        });
    }

    /**
     * Get current game statistics (for live games).
     */
    public function getCurrentGameStats(Game $game): array
    {
        if (!$game->liveGame) {
            return [];
        }

        $homeStats = $this->getTeamGameStats($game->homeTeam, $game);
        $awayStats = $this->getTeamGameStats($game->awayTeam, $game);

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
     * Calculate player statistics from game actions.
     */
    private function calculatePlayerStatsFromActions($actions): array
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
        $stats['true_shooting_percentage'] = $this->calculateTrueShootingPercentage(
            $stats['total_points'],
            $stats['field_goals_attempted'],
            $stats['free_throws_attempted']
        );

        $stats['player_efficiency_rating'] = $this->calculatePlayerEfficiencyRating($stats);

        return $stats;
    }

    /**
     * Calculate team statistics from game actions.
     */
    private function calculateTeamStatsFromActions($actions): array
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
    private function calculateAdvancedTeamStats(array $stats): array
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
            $stats['offensive_rating'] = $this->calculateOffensiveRating($stats);
            $stats['defensive_rating'] = $this->calculateDefensiveRating($stats);
            $stats['net_rating'] = $stats['offensive_rating'] - $stats['defensive_rating'];
        }

        return $stats;
    }

    /**
     * Calculate True Shooting Percentage.
     */
    private function calculateTrueShootingPercentage(int $points, int $fga, int $fta): float
    {
        $tsa = $fga + (0.44 * $fta); // True Shot Attempts
        
        return $tsa > 0 ? round(($points / (2 * $tsa)) * 100, 1) : 0;
    }

    /**
     * Calculate Player Efficiency Rating (simplified).
     */
    private function calculatePlayerEfficiencyRating(array $stats): float
    {
        // Simplified PER calculation
        $per = ($stats['total_points'] + $stats['total_rebounds'] + $stats['assists'] 
                + $stats['steals'] + $stats['blocks'] - $stats['turnovers'] 
                - $stats['personal_fouls']);
                
        return round($per, 1);
    }

    /**
     * Calculate Offensive Rating.
     */
    private function calculateOffensiveRating(array $stats): float
    {
        $possessions = $this->estimatePossessions($stats);
        
        return $possessions > 0 ? round(($stats['points_for'] / $possessions) * 100, 1) : 0;
    }

    /**
     * Calculate Defensive Rating.
     */
    private function calculateDefensiveRating(array $stats): float
    {
        $possessions = $this->estimatePossessions($stats);
        
        return $possessions > 0 ? round(($stats['points_against'] / $possessions) * 100, 1) : 0;
    }

    /**
     * Estimate possessions (simplified).
     */
    private function estimatePossessions(array $stats): float
    {
        return $stats['field_goals_attempted'] + ($stats['turnovers'] * 0.8) + ($stats['free_throws_attempted'] * 0.44);
    }

    /**
     * Invalidate player statistics cache.
     */
    public function invalidatePlayerStats(Player $player): void
    {
        $pattern = $this->cachePrefix . "player:{$player->id}:*";
        $this->deleteCacheByPattern($pattern);
        
        // Also invalidate team stats
        if ($player->team) {
            $this->invalidateTeamStats($player->team);
        }
    }

    /**
     * Invalidate team statistics cache.
     */
    public function invalidateTeamStats(Team $team): void
    {
        $pattern = $this->cachePrefix . "team:{$team->id}:*";
        $this->deleteCacheByPattern($pattern);
    }

    /**
     * Invalidate game statistics cache.
     */
    public function invalidateGameStats(Game $game): void
    {
        $pattern = $this->cachePrefix . "*:game:{$game->id}";
        $this->deleteCacheByPattern($pattern);
    }

    /**
     * Get shot chart data for a player in a game.
     */
    public function getPlayerShotChart(Player $player, Game $game): array
    {
        $cacheKey = $this->cachePrefix . "shotchart:player:{$player->id}:game:{$game->id}";
        
        return Cache::remember($cacheKey, $this->defaultCacheTtl, function () use ($player, $game) {
            $shots = GameAction::where('game_id', $game->id)
                ->where('player_id', $player->id)
                ->whereIn('action_type', [
                    'field_goal_made', 'field_goal_missed',
                    'three_point_made', 'three_point_missed'
                ])
                ->whereNotNull('shot_x')
                ->whereNotNull('shot_y')
                ->get();

            $shotData = [];
            foreach ($shots as $shot) {
                $shotData[] = [
                    'x' => $shot->shot_x,
                    'y' => $shot->shot_y,
                    'made' => str_contains($shot->action_type, '_made'),
                    'distance' => $shot->shot_distance,
                    'zone' => $shot->shot_zone,
                    'period' => $shot->period,
                    'time' => $shot->time_remaining,
                    'points' => $shot->points,
                ];
            }

            return [
                'shots' => $shotData,
                'zones' => $this->calculateShotZoneStats($shots),
                'summary' => $this->calculateShotChartSummary($shots),
            ];
        });
    }

    /**
     * Get advanced player metrics.
     */
    public function getAdvancedPlayerStats(Player $player, Game $game): array
    {
        $stats = $this->getPlayerGameStats($player, $game);
        $teamStats = $this->getTeamGameStats($player->team, $game);
        
        // Calculate advanced metrics
        return [
            'usage_rate' => $this->calculateUsageRate($player, $game),
            'player_impact_estimate' => $this->calculatePlayerImpact($stats),
            'plus_minus' => $this->calculatePlusMinus($player, $game),
            'effective_field_goal_percentage' => $this->calculateEffectiveFGPercentage($stats),
            'assist_to_turnover_ratio' => $this->calculateAssistToTurnoverRatio($stats),
            'steal_percentage' => $this->calculateStealPercentage($player, $game),
            'block_percentage' => $this->calculateBlockPercentage($player, $game),
            'rebound_percentage' => $this->calculateReboundPercentage($player, $game),
            'points_per_possession' => $this->calculatePointsPerPossession($stats),
            'game_score' => $this->calculateGameScore($stats),
        ];
    }

    /**
     * Calculate Usage Rate - percentage of team plays used by player.
     */
    private function calculateUsageRate(Player $player, Game $game): float
    {
        $playerActions = GameAction::where('game_id', $game->id)
            ->where('player_id', $player->id)
            ->whereIn('action_type', [
                'field_goal_made', 'field_goal_missed',
                'three_point_made', 'three_point_missed',
                'free_throw_made', 'free_throw_missed',
                'turnover'
            ])
            ->count();

        $teamActions = GameAction::where('game_id', $game->id)
            ->where('team_id', $player->team_id)
            ->whereIn('action_type', [
                'field_goal_made', 'field_goal_missed',
                'three_point_made', 'three_point_missed',
                'free_throw_made', 'free_throw_missed',
                'turnover'
            ])
            ->count();

        return $teamActions > 0 ? round(($playerActions / $teamActions) * 100, 1) : 0;
    }

    /**
     * Calculate Plus/Minus for a player.
     */
    private function calculatePlusMinus(Player $player, Game $game): int
    {
        // This is simplified - real plus/minus requires tracking when player is on court
        $isHome = $player->team_id === $game->home_team_id;
        $teamScore = $isHome ? $game->home_team_score : $game->away_team_score;
        $opponentScore = $isHome ? $game->away_team_score : $game->home_team_score;
        
        return $teamScore - $opponentScore;
    }

    /**
     * Calculate Effective Field Goal Percentage.
     */
    private function calculateEffectiveFGPercentage(array $stats): float
    {
        $fga = $stats['field_goals_attempted'] + $stats['three_points_attempted'];
        if ($fga === 0) return 0;
        
        $fgm = $stats['field_goals_made'] + $stats['three_points_made'];
        $threeMade = $stats['three_points_made'];
        
        return round((($fgm + (0.5 * $threeMade)) / $fga) * 100, 1);
    }

    /**
     * Calculate Assist to Turnover Ratio.
     */
    private function calculateAssistToTurnoverRatio(array $stats): float
    {
        return $stats['turnovers'] > 0 ? 
            round($stats['assists'] / $stats['turnovers'], 1) : 
            $stats['assists'];
    }

    /**
     * Calculate Player Impact Estimate (simplified).
     */
    private function calculatePlayerImpact(array $stats): float
    {
        $positive = $stats['total_points'] + $stats['total_rebounds'] + $stats['assists'] 
                   + $stats['steals'] + $stats['blocks'];
        $negative = $stats['turnovers'] + $stats['personal_fouls'];
        
        return round($positive - $negative, 1);
    }

    /**
     * Calculate various percentage stats.
     */
    private function calculateStealPercentage(Player $player, Game $game): float
    {
        // Simplified calculation
        return 0; // Would need opponent possessions data
    }

    private function calculateBlockPercentage(Player $player, Game $game): float
    {
        // Simplified calculation
        return 0; // Would need opponent shot attempts data
    }

    private function calculateReboundPercentage(Player $player, Game $game): float
    {
        $playerRebs = GameAction::where('game_id', $game->id)
            ->where('player_id', $player->id)
            ->whereIn('action_type', ['rebound_offensive', 'rebound_defensive'])
            ->count();

        $totalRebs = GameAction::where('game_id', $game->id)
            ->whereIn('action_type', ['rebound_offensive', 'rebound_defensive'])
            ->count();

        return $totalRebs > 0 ? round(($playerRebs / $totalRebs) * 100, 1) : 0;
    }

    /**
     * Calculate Points Per Possession.
     */
    private function calculatePointsPerPossession(array $stats): float
    {
        $possessions = $stats['field_goals_attempted'] + $stats['three_points_attempted'] 
                      + ($stats['free_throws_attempted'] * 0.44) + $stats['turnovers'];
        
        return $possessions > 0 ? round($stats['total_points'] / $possessions, 2) : 0;
    }

    /**
     * Calculate Game Score (John Hollinger's metric).
     */
    private function calculateGameScore(array $stats): float
    {
        return round(
            $stats['total_points'] + 
            (0.4 * ($stats['field_goals_made'] + $stats['three_points_made'])) - 
            (0.7 * ($stats['field_goals_attempted'] + $stats['three_points_attempted'] - $stats['field_goals_made'] - $stats['three_points_made'])) - 
            (0.4 * ($stats['free_throws_attempted'] - $stats['free_throws_made'])) + 
            (0.7 * $stats['rebounds_offensive']) + 
            (0.3 * $stats['rebounds_defensive']) + 
            $stats['steals'] + 
            (0.7 * $stats['assists']) + 
            (0.7 * $stats['blocks']) - 
            (0.4 * $stats['personal_fouls']) - 
            $stats['turnovers'], 1
        );
    }

    /**
     * Calculate shot zone statistics.
     */
    private function calculateShotZoneStats($shots): array
    {
        $zones = [];
        
        foreach ($shots as $shot) {
            $zone = $shot->shot_zone ?? 'unknown';
            
            if (!isset($zones[$zone])) {
                $zones[$zone] = [
                    'attempts' => 0,
                    'makes' => 0,
                    'percentage' => 0,
                    'points' => 0
                ];
            }
            
            $zones[$zone]['attempts']++;
            if (str_contains($shot->action_type, '_made')) {
                $zones[$zone]['makes']++;
                $zones[$zone]['points'] += $shot->points;
            }
        }
        
        // Calculate percentages
        foreach ($zones as $zone => &$stats) {
            $stats['percentage'] = $stats['attempts'] > 0 ? 
                round(($stats['makes'] / $stats['attempts']) * 100, 1) : 0;
        }
        
        return $zones;
    }

    /**
     * Calculate shot chart summary.
     */
    private function calculateShotChartSummary($shots): array
    {
        $makes = $shots->filter(fn($shot) => str_contains($shot->action_type, '_made'));
        $attempts = $shots->count();
        
        return [
            'total_attempts' => $attempts,
            'total_makes' => $makes->count(),
            'shooting_percentage' => $attempts > 0 ? 
                round(($makes->count() / $attempts) * 100, 1) : 0,
            'average_distance' => $shots->avg('shot_distance') ?? 0,
            'longest_made' => $makes->max('shot_distance') ?? 0,
            'points_from_shots' => $makes->sum('points'),
        ];
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

    /**
     * Get comprehensive team analytics.
     */
    public function getTeamAnalytics(Team $team, string $season): array
    {
        $seasonStats = $this->getTeamSeasonStats($team, $season);
        
        return array_merge($seasonStats, [
            'four_factors' => $this->calculateFourFactors($team, $season),
            'clutch_stats' => $this->getClutchStats($team, $season),
            'home_away_splits' => $this->getHomeAwaySplits($team, $season),
            'monthly_trends' => $this->getMonthlyTrends($team, $season),
        ]);
    }

    /**
     * Calculate the Four Factors of Basketball Success.
     */
    private function calculateFourFactors(Team $team, string $season): array
    {
        $stats = $this->getTeamSeasonStats($team, $season);
        
        return [
            'effective_fg_percentage' => $this->calculateTeamEffectiveFG($stats),
            'turnover_rate' => $this->calculateTurnoverRate($stats),
            'offensive_rebounding_percentage' => $this->calculateOffensiveReboundingRate($team, $season),
            'free_throw_rate' => $this->calculateFreeThrowRate($stats),
        ];
    }

    /**
     * Get clutch time statistics (last 5 minutes, score within 5 points).
     */
    private function getClutchStats(Team $team, string $season): array
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
    private function getHomeAwaySplits(Team $team, string $season): array
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
    private function getMonthlyTrends(Team $team, string $season): array
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
     * Helper methods for Four Factors.
     */
    private function calculateTeamEffectiveFG(array $stats): float
    {
        $fga = $stats['field_goals_attempted'];
        if ($fga === 0) return 0;
        
        $fgm = $stats['field_goals_made'];
        $threeMade = $stats['three_points_made'];
        
        return round((($fgm + (0.5 * $threeMade)) / $fga) * 100, 1);
    }

    private function calculateTurnoverRate(array $stats): float
    {
        $possessions = $this->estimatePossessions($stats);
        return $possessions > 0 ? 
            round(($stats['turnovers'] / $possessions) * 100, 1) : 0;
    }

    private function calculateOffensiveReboundingRate(Team $team, string $season): float
    {
        // Simplified - would need opponent data
        return 0;
    }

    private function calculateFreeThrowRate(array $stats): float
    {
        $fga = $stats['field_goals_attempted'];
        return $fga > 0 ? 
            round($stats['free_throws_attempted'] / $fga, 2) : 0;
    }

    /**
     * Delete cache by pattern (simplified for now).
     */
    private function deleteCacheByPattern(string $pattern): void
    {
        // This would be implemented based on your cache driver
        // For Redis, you could use SCAN and DEL commands
        // For file cache, you might need a different approach
        Cache::flush(); // Simplified for now
    }
}