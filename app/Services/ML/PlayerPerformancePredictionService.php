<?php

namespace App\Services\ML;

use App\Models\Player;
use App\Models\Game;
use App\Models\GameStatistic;
use App\Models\MLModel;
use App\Models\MLPrediction;
use App\Models\MLTrainingData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class PlayerPerformancePredictionService
{
    private MLPredictionService $mlService;

    public function __construct(MLPredictionService $mlService)
    {
        $this->mlService = $mlService;
    }

    public function predictGamePerformance(Player $player, Game $game): MLPrediction
    {
        // Gather comprehensive features for prediction
        $features = $this->buildGamePerformanceFeatures($player, $game);
        
        return $this->mlService->predictPlayerPerformance($player, $game, $features);
    }

    public function predictSeasonPerformance(Player $player, string $season): array
    {
        $predictions = [];
        
        // Get remaining games in the season
        $remainingGames = Game::where('season', $season)
            ->where(function($query) use ($player) {
                $query->where('home_team_id', $player->team_id)
                      ->orWhere('away_team_id', $player->team_id);
            })
            ->where('game_datetime', '>', now())
            ->orderBy('game_datetime')
            ->get();

        foreach ($remainingGames as $game) {
            try {
                $prediction = $this->predictGamePerformance($player, $game);
                $predictions[] = $prediction;
            } catch (Exception $e) {
                Log::warning("Failed to predict performance for game {$game->id}: " . $e->getMessage());
            }
        }

        return $predictions;
    }

    public function analyzePerformanceTrends(Player $player, int $lookbackDays = 30): array
    {
        $cutoffDate = now()->subDays($lookbackDays);
        
        // Get recent game statistics
        $recentStats = GameStatistic::where('player_id', $player->id)
            ->whereHas('game', function($query) use ($cutoffDate) {
                $query->where('game_datetime', '>=', $cutoffDate);
            })
            ->with('game')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($recentStats->isEmpty()) {
            return [
                'trend' => 'insufficient_data',
                'confidence' => 0.0,
                'analysis' => 'Not enough recent games for trend analysis'
            ];
        }

        // Calculate trend metrics
        $pointsData = $recentStats->pluck('points')->toArray();
        $assistsData = $recentStats->pluck('assists')->toArray();
        $reboundsData = $recentStats->pluck('rebounds')->toArray();
        $minutesData = $recentStats->pluck('minutes_played')->toArray();

        $trends = [
            'points' => $this->calculateTrend($pointsData),
            'assists' => $this->calculateTrend($assistsData),
            'rebounds' => $this->calculateTrend($reboundsData),
            'minutes' => $this->calculateTrend($minutesData),
            'efficiency' => $this->calculateEfficiencyTrend($recentStats),
        ];

        // Determine overall trend
        $overallTrend = $this->determineOverallTrend($trends);
        
        return [
            'trend' => $overallTrend['direction'],
            'confidence' => $overallTrend['confidence'],
            'analysis' => $overallTrend['analysis'],
            'detailed_trends' => $trends,
            'sample_size' => count($recentStats),
            'period_days' => $lookbackDays,
        ];
    }

    public function identifyBreakoutCandidates(array $playerIds, string $season): array
    {
        $candidates = [];
        
        foreach ($playerIds as $playerId) {
            $player = Player::find($playerId);
            if (!$player) continue;
            
            $breakoutScore = $this->calculateBreakoutPotential($player, $season);
            
            if ($breakoutScore['score'] >= 0.6) {
                $candidates[] = [
                    'player' => $player,
                    'breakout_score' => $breakoutScore['score'],
                    'factors' => $breakoutScore['factors'],
                    'predicted_improvement' => $breakoutScore['predicted_improvement'],
                ];
            }
        }
        
        // Sort by breakout score descending
        usort($candidates, function($a, $b) {
            return $b['breakout_score'] <=> $a['breakout_score'];
        });
        
        return $candidates;
    }

    public function generateTrainingData(string $season, int $minGames = 10): MLTrainingData
    {
        $trainingRecords = [];
        
        // Get all players with sufficient games
        $players = Player::whereHas('gameStatistics', function($query) use ($season, $minGames) {
            $query->whereHas('game', function($gameQuery) use ($season) {
                $gameQuery->where('season', $season);
            });
        }, '>=', $minGames)->get();

        foreach ($players as $player) {
            $playerRecords = $this->generatePlayerTrainingRecords($player, $season);
            $trainingRecords = array_merge($trainingRecords, $playerRecords);
        }

        // Create training dataset
        return MLTrainingData::create([
            'dataset_name' => "player_performance_{$season}",
            'dataset_version' => '1.0.0',
            'description' => "Player performance training data for {$season} season",
            'data_type' => 'player_performance_features',
            'source_system' => app_name(),
            'dataset_purpose' => 'training',
            'target_model_type' => 'player_performance',
            'raw_data' => $trainingRecords,
            'feature_columns' => $this->getFeatureColumnDefinitions(),
            'target_columns' => ['points', 'assists', 'rebounds', 'steals', 'blocks'],
            'record_count' => count($trainingRecords),
            'feature_count' => count($this->getFeatureColumnDefinitions()),
            'season' => $season,
            'collected_at' => now(),
            'is_processed' => false,
            'extraction_method' => 'automated',
            'created_by_user_id' => auth()->id() ?? 1,
        ]);
    }

    private function buildGamePerformanceFeatures(Player $player, Game $game): array
    {
        $features = [];
        
        // Player characteristics
        $features['player_age'] = $player->age ?? 25;
        $features['player_height'] = $player->height ?? 200;
        $features['player_weight'] = $player->weight ?? 90;
        $features['player_position'] = $this->encodePosition($player->position);
        $features['player_experience'] = $player->getExperienceYears();
        
        // Recent performance (last 10 games)
        $recentStats = $this->getRecentPerformanceStats($player, 10);
        $features = array_merge($features, $this->prefixKeys($recentStats, 'recent_'));
        
        // Season averages
        $seasonStats = $this->getSeasonAverageStats($player, $game->season);
        $features = array_merge($features, $this->prefixKeys($seasonStats, 'season_'));
        
        // Game context
        $features['is_home_game'] = $game->home_team_id === $player->team_id ? 1 : 0;
        $features['days_rest'] = $this->getDaysRest($player, $game);
        $features['back_to_back'] = $this->isBackToBack($player, $game) ? 1 : 0;
        
        // Opponent analysis
        $opponent = $game->home_team_id === $player->team_id ? $game->awayTeam : $game->homeTeam;
        $opponentStats = $this->getOpponentDefensiveStats($opponent, $game->season);
        $features = array_merge($features, $this->prefixKeys($opponentStats, 'opponent_'));
        
        // Historical matchup
        $matchupStats = $this->getPlayerVsTeamStats($player, $opponent);
        $features = array_merge($features, $this->prefixKeys($matchupStats, 'matchup_'));
        
        // Team context
        $teamStats = $this->getTeamContextStats($player->team, $game->season);
        $features = array_merge($features, $this->prefixKeys($teamStats, 'team_'));
        
        // Situational factors
        $features['game_importance'] = $this->calculateGameImportance($game);
        $features['month_of_season'] = $game->game_datetime->month;
        $features['day_of_week'] = $game->game_datetime->dayOfWeek;
        
        return $features;
    }

    private function getRecentPerformanceStats(Player $player, int $gameCount): array
    {
        $recentStats = GameStatistic::where('player_id', $player->id)
            ->whereHas('game', function($query) {
                $query->where('game_datetime', '<=', now());
            })
            ->with('game')
            ->orderBy('created_at', 'desc')
            ->limit($gameCount)
            ->get();
            
        if ($recentStats->isEmpty()) {
            return $this->getDefaultStats();
        }
        
        return [
            'avg_points' => $recentStats->avg('points'),
            'avg_assists' => $recentStats->avg('assists'),
            'avg_rebounds' => $recentStats->avg('rebounds'),
            'avg_steals' => $recentStats->avg('steals'),
            'avg_blocks' => $recentStats->avg('blocks'),
            'avg_minutes' => $recentStats->avg('minutes_played'),
            'avg_fg_pct' => $recentStats->avg('field_goal_percentage'),
            'avg_three_pct' => $recentStats->avg('three_point_percentage'),
            'avg_ft_pct' => $recentStats->avg('free_throw_percentage'),
            'avg_turnovers' => $recentStats->avg('turnovers'),
            'games_count' => $recentStats->count(),
            'trend_points' => $this->calculateSimpleTrend($recentStats->pluck('points')->toArray()),
            'consistency_points' => $this->calculateConsistency($recentStats->pluck('points')->toArray()),
        ];
    }

    private function getSeasonAverageStats(Player $player, string $season): array
    {
        $seasonStats = GameStatistic::where('player_id', $player->id)
            ->whereHas('game', function($query) use ($season) {
                $query->where('season', $season)
                      ->where('game_datetime', '<=', now());
            })
            ->get();
            
        if ($seasonStats->isEmpty()) {
            return $this->getDefaultStats();
        }
        
        return [
            'avg_points' => $seasonStats->avg('points'),
            'avg_assists' => $seasonStats->avg('assists'),
            'avg_rebounds' => $seasonStats->avg('rebounds'),
            'avg_steals' => $seasonStats->avg('steals'),
            'avg_blocks' => $seasonStats->avg('blocks'),
            'avg_minutes' => $seasonStats->avg('minutes_played'),
            'avg_fg_pct' => $seasonStats->avg('field_goal_percentage'),
            'avg_three_pct' => $seasonStats->avg('three_point_percentage'),
            'avg_ft_pct' => $seasonStats->avg('free_throw_percentage'),
            'games_played' => $seasonStats->count(),
            'total_points' => $seasonStats->sum('points'),
            'best_game_points' => $seasonStats->max('points'),
            'worst_game_points' => $seasonStats->min('points'),
        ];
    }

    private function getDaysRest(Player $player, Game $game): int
    {
        $lastGame = Game::where(function($query) use ($player) {
            $query->where('home_team_id', $player->team_id)
                  ->orWhere('away_team_id', $player->team_id);
        })
        ->where('game_datetime', '<', $game->game_datetime)
        ->orderBy('game_datetime', 'desc')
        ->first();
        
        if (!$lastGame) {
            return 7; // Default to a week if no previous game
        }
        
        return $lastGame->game_datetime->diffInDays($game->game_datetime);
    }

    private function isBackToBack(Player $player, Game $game): bool
    {
        return $this->getDaysRest($player, $game) <= 1;
    }

    private function getOpponentDefensiveStats(mixed $opponent, string $season): array
    {
        // Calculate opponent's defensive statistics
        $opponentGames = Game::where(function($query) use ($opponent) {
            $query->where('home_team_id', $opponent->id)
                  ->orWhere('away_team_id', $opponent->id);
        })
        ->where('season', $season)
        ->where('game_datetime', '<=', now())
        ->with(['gameStatistics'])
        ->get();
        
        if ($opponentGames->isEmpty()) {
            return [
                'def_rating' => 100.0,
                'opp_avg_points_allowed' => 100.0,
                'opp_avg_assists_allowed' => 25.0,
                'opp_avg_rebounds_allowed' => 45.0,
            ];
        }
        
        $totalPointsAllowed = 0;
        $totalAssistsAllowed = 0;
        $totalReboundsAllowed = 0;
        $gameCount = 0;
        
        foreach ($opponentGames as $gameItem) {
            $opponentStats = $gameItem->gameStatistics()
                ->whereHas('player', function($query) use ($opponent) {
                    $query->where('team_id', '!=', $opponent->id);
                })->get();
            
            if ($opponentStats->isNotEmpty()) {
                $totalPointsAllowed += $opponentStats->sum('points');
                $totalAssistsAllowed += $opponentStats->sum('assists');
                $totalReboundsAllowed += $opponentStats->sum('rebounds');
                $gameCount++;
            }
        }
        
        return [
            'def_rating' => $gameCount > 0 ? ($totalPointsAllowed / $gameCount) : 100.0,
            'opp_avg_points_allowed' => $gameCount > 0 ? ($totalPointsAllowed / $gameCount) : 100.0,
            'opp_avg_assists_allowed' => $gameCount > 0 ? ($totalAssistsAllowed / $gameCount) : 25.0,
            'opp_avg_rebounds_allowed' => $gameCount > 0 ? ($totalReboundsAllowed / $gameCount) : 45.0,
        ];
    }

    private function getPlayerVsTeamStats(Player $player, mixed $opponent): array
    {
        $vsStats = GameStatistic::where('player_id', $player->id)
            ->whereHas('game', function($query) use ($opponent) {
                $query->where(function($subQuery) use ($opponent) {
                    $subQuery->where('home_team_id', $opponent->id)
                             ->orWhere('away_team_id', $opponent->id);
                });
            })
            ->get();
            
        if ($vsStats->isEmpty()) {
            return $this->getDefaultStats();
        }
        
        return [
            'avg_points' => $vsStats->avg('points'),
            'avg_assists' => $vsStats->avg('assists'),
            'avg_rebounds' => $vsStats->avg('rebounds'),
            'games_played' => $vsStats->count(),
            'best_performance' => $vsStats->max('points'),
        ];
    }

    private function getTeamContextStats(mixed $team, string $season): array
    {
        // Team's recent form and statistics
        $teamGames = Game::where(function($query) use ($team) {
            $query->where('home_team_id', $team->id)
                  ->orWhere('away_team_id', $team->id);
        })
        ->where('season', $season)
        ->where('game_datetime', '<=', now())
        ->orderBy('game_datetime', 'desc')
        ->limit(10)
        ->get();
        
        if ($teamGames->isEmpty()) {
            return [
                'recent_wins' => 5,
                'recent_losses' => 5,
                'win_percentage' => 0.5,
                'avg_team_points' => 100.0,
                'avg_team_assists' => 25.0,
            ];
        }
        
        $wins = 0;
        $totalPoints = 0;
        $totalAssists = 0;
        
        foreach ($teamGames as $gameItem) {
            // Determine if team won (simplified)
            $teamStats = $gameItem->gameStatistics()
                ->whereHas('player', function($query) use ($team) {
                    $query->where('team_id', $team->id);
                })->get();
            
            $teamPoints = $teamStats->sum('points');
            $totalPoints += $teamPoints;
            $totalAssists += $teamStats->sum('assists');
            
            // Simple win determination (would need proper game results)
            if ($teamPoints > 100) { // Simplified assumption
                $wins++;
            }
        }
        
        return [
            'recent_wins' => $wins,
            'recent_losses' => count($teamGames) - $wins,
            'win_percentage' => count($teamGames) > 0 ? $wins / count($teamGames) : 0.5,
            'avg_team_points' => count($teamGames) > 0 ? $totalPoints / count($teamGames) : 100.0,
            'avg_team_assists' => count($teamGames) > 0 ? $totalAssists / count($teamGames) : 25.0,
        ];
    }

    private function calculateBreakoutPotential(Player $player, string $season): array
    {
        $factors = [];
        $score = 0.0;
        
        // Age factor (younger players have higher breakout potential)
        $age = $player->age ?? 25;
        if ($age <= 23) {
            $factors['age'] = ['value' => $age, 'impact' => 0.3];
            $score += 0.3;
        } elseif ($age <= 26) {
            $factors['age'] = ['value' => $age, 'impact' => 0.2];
            $score += 0.2;
        }
        
        // Experience factor (2-4 years is prime breakout range)
        $experience = $player->getExperienceYears();
        if ($experience >= 2 && $experience <= 4) {
            $factors['experience'] = ['value' => $experience, 'impact' => 0.25];
            $score += 0.25;
        }
        
        // Performance trend
        $trends = $this->analyzePerformanceTrends($player, 20);
        if ($trends['trend'] === 'improving') {
            $factors['trend'] = ['value' => $trends['confidence'], 'impact' => 0.3];
            $score += 0.3 * $trends['confidence'];
        }
        
        // Minutes increase (more opportunity)
        $recentMinutes = $this->getRecentPerformanceStats($player, 5)['avg_minutes'];
        $seasonMinutes = $this->getSeasonAverageStats($player, $season)['avg_minutes'];
        if ($recentMinutes > $seasonMinutes * 1.1) {
            $factors['minutes_increase'] = ['value' => $recentMinutes - $seasonMinutes, 'impact' => 0.15];
            $score += 0.15;
        }
        
        return [
            'score' => min(1.0, $score),
            'factors' => $factors,
            'predicted_improvement' => $this->predictImprovementRange($score),
        ];
    }

    private function predictImprovementRange(float $breakoutScore): array
    {
        $baseImprovement = $breakoutScore * 0.3; // Up to 30% improvement
        
        return [
            'points_increase' => [
                'min' => $baseImprovement * 0.5 * 20, // Minimum points increase
                'max' => $baseImprovement * 1.5 * 20, // Maximum points increase
            ],
            'overall_improvement' => [
                'min' => $baseImprovement * 0.5,
                'max' => $baseImprovement * 1.5,
            ],
        ];
    }

    private function generatePlayerTrainingRecords(Player $player, string $season): array
    {
        $records = [];
        
        $games = Game::where(function($query) use ($player) {
            $query->where('home_team_id', $player->team_id)
                  ->orWhere('away_team_id', $player->team_id);
        })
        ->where('season', $season)
        ->where('game_datetime', '<=', now())
        ->with(['gameStatistics' => function($query) use ($player) {
            $query->where('player_id', $player->id);
        }])
        ->orderBy('game_datetime')
        ->get();
        
        foreach ($games as $game) {
            $stat = $game->gameStatistics->first();
            if (!$stat) continue;
            
            $features = $this->buildGamePerformanceFeatures($player, $game);
            $target = [
                'points' => $stat->points,
                'assists' => $stat->assists,
                'rebounds' => $stat->rebounds,
                'steals' => $stat->steals,
                'blocks' => $stat->blocks,
                'minutes' => $stat->minutes_played,
                'fg_pct' => $stat->field_goal_percentage,
                'three_pct' => $stat->three_point_percentage,
            ];
            
            $records[] = array_merge($features, $target, [
                'player_id' => $player->id,
                'game_id' => $game->id,
                'game_date' => $game->game_datetime->toDateString(),
            ]);
        }
        
        return $records;
    }

    private function getFeatureColumnDefinitions(): array
    {
        return [
            'player_age', 'player_height', 'player_weight', 'player_position', 'player_experience',
            'recent_avg_points', 'recent_avg_assists', 'recent_avg_rebounds', 'recent_avg_minutes',
            'season_avg_points', 'season_avg_assists', 'season_avg_rebounds', 'season_games_played',
            'is_home_game', 'days_rest', 'back_to_back', 'game_importance',
            'opponent_def_rating', 'opponent_opp_avg_points_allowed',
            'matchup_avg_points', 'matchup_games_played',
            'team_win_percentage', 'team_avg_team_points',
        ];
    }

    private function getDefaultStats(): array
    {
        return [
            'avg_points' => 10.0,
            'avg_assists' => 3.0,
            'avg_rebounds' => 5.0,
            'avg_steals' => 1.0,
            'avg_blocks' => 0.5,
            'avg_minutes' => 25.0,
            'avg_fg_pct' => 0.45,
            'avg_three_pct' => 0.35,
            'avg_ft_pct' => 0.75,
            'games_played' => 0,
        ];
    }

    private function calculateTrend(array $data): array
    {
        if (count($data) < 3) {
            return ['direction' => 'insufficient_data', 'slope' => 0, 'confidence' => 0];
        }
        
        // Simple linear regression
        $n = count($data);
        $x = range(1, $n);
        
        $sumX = array_sum($x);
        $sumY = array_sum($data);
        $sumXY = 0;
        $sumX2 = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $data[$i];
            $sumX2 += $x[$i] * $x[$i];
        }
        
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        
        $direction = $slope > 0.1 ? 'improving' : ($slope < -0.1 ? 'declining' : 'stable');
        $confidence = min(1.0, abs($slope) * 10); // Rough confidence measure
        
        return [
            'direction' => $direction,
            'slope' => $slope,
            'confidence' => $confidence,
        ];
    }

    private function calculateEfficiencyTrend($recentStats): array
    {
        $efficiencyData = $recentStats->map(function($stat) {
            $minutes = $stat->minutes_played ?: 1;
            return ($stat->points + $stat->assists + $stat->rebounds) / $minutes;
        })->toArray();
        
        return $this->calculateTrend($efficiencyData);
    }

    private function determineOverallTrend(array $trends): array
    {
        $improvingCount = 0;
        $decliningCount = 0;
        $totalConfidence = 0;
        
        foreach ($trends as $trend) {
            if ($trend['direction'] === 'improving') $improvingCount++;
            if ($trend['direction'] === 'declining') $decliningCount++;
            $totalConfidence += $trend['confidence'];
        }
        
        $avgConfidence = $totalConfidence / count($trends);
        
        if ($improvingCount > $decliningCount) {
            return [
                'direction' => 'improving',
                'confidence' => $avgConfidence,
                'analysis' => "Player showing improvement in {$improvingCount} key areas",
            ];
        } elseif ($decliningCount > $improvingCount) {
            return [
                'direction' => 'declining',
                'confidence' => $avgConfidence,
                'analysis' => "Player showing decline in {$decliningCount} key areas",
            ];
        } else {
            return [
                'direction' => 'stable',
                'confidence' => $avgConfidence,
                'analysis' => 'Player performance is relatively stable',
            ];
        }
    }

    private function calculateSimpleTrend(array $data): float
    {
        if (count($data) < 2) return 0;
        
        $first = array_slice($data, -3, 3); // Last 3 games
        $second = array_slice($data, 0, 3); // First 3 games
        
        $firstAvg = array_sum($first) / count($first);
        $secondAvg = array_sum($second) / count($second);
        
        return $firstAvg - $secondAvg;
    }

    private function calculateConsistency(array $data): float
    {
        if (count($data) < 2) return 0;
        
        $mean = array_sum($data) / count($data);
        $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $data)) / count($data);
        
        return $variance > 0 ? 1 / (1 + sqrt($variance)) : 1;
    }

    private function encodePosition(string $position): int
    {
        return match($position) {
            'Point Guard', 'PG' => 1,
            'Shooting Guard', 'SG' => 2,
            'Small Forward', 'SF' => 3,
            'Power Forward', 'PF' => 4,
            'Center', 'C' => 5,
            default => 3, // Default to forward
        };
    }

    private function calculateGameImportance(Game $game): float
    {
        $importance = 0.5; // Base importance
        
        // Playoff games are more important
        if ($game->is_playoff ?? false) {
            $importance += 0.3;
        }
        
        // Late season games are more important
        $gameDate = $game->game_datetime;
        if ($gameDate->month >= 3) { // March onwards
            $importance += 0.2;
        }
        
        return min(1.0, $importance);
    }

    private function prefixKeys(array $array, string $prefix): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $result[$prefix . $key] = $value;
        }
        return $result;
    }
}