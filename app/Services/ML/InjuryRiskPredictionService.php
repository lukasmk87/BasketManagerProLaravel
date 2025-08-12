<?php

namespace App\Services\ML;

use App\Models\Player;
use App\Models\Game;
use App\Models\GameStatistic;
use App\Models\TrainingSession;
use App\Models\MLModel;
use App\Models\MLPrediction;
use App\Models\MLTrainingData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class InjuryRiskPredictionService
{
    private MLPredictionService $mlService;

    public function __construct(MLPredictionService $mlService)
    {
        $this->mlService = $mlService;
    }

    public function predictInjuryRisk(Player $player, int $forecastDays = 7): MLPrediction
    {
        // Build comprehensive injury risk features
        $features = $this->buildInjuryRiskFeatures($player, $forecastDays);
        
        return $this->mlService->predictInjuryRisk($player, $forecastDays, $features);
    }

    public function assessTeamInjuryRisk(int $teamId, int $forecastDays = 7): array
    {
        $players = Player::where('team_id', $teamId)->get();
        $assessments = [];
        $teamRiskLevels = [
            'very_high' => 0,
            'high' => 0,
            'medium' => 0,
            'low' => 0,
            'very_low' => 0,
        ];

        foreach ($players as $player) {
            try {
                $prediction = $this->predictInjuryRisk($player, $forecastDays);
                
                $assessment = [
                    'player' => $player,
                    'prediction' => $prediction,
                    'risk_level' => $prediction->risk_level,
                    'injury_probability' => $prediction->injury_risk_probability,
                    'primary_risk_factors' => $this->getPrimaryRiskFactors($prediction->risk_factors ?? []),
                    'recommended_actions' => $prediction->recommended_actions ?? [],
                ];
                
                $assessments[] = $assessment;
                $teamRiskLevels[$prediction->risk_level]++;
                
            } catch (Exception $e) {
                Log::warning("Failed to assess injury risk for player {$player->id}: " . $e->getMessage());
            }
        }

        // Sort by risk level (highest first)
        usort($assessments, function($a, $b) {
            return $b['injury_probability'] <=> $a['injury_probability'];
        });

        return [
            'team_id' => $teamId,
            'assessment_date' => now()->toDateString(),
            'forecast_days' => $forecastDays,
            'player_assessments' => $assessments,
            'team_risk_distribution' => $teamRiskLevels,
            'high_risk_players' => array_filter($assessments, fn($a) => in_array($a['risk_level'], ['very_high', 'high'])),
            'team_overall_risk' => $this->calculateTeamOverallRisk($assessments),
        ];
    }

    public function identifyRiskFactors(Player $player): array
    {
        $riskFactors = [];
        
        // Age-related risk
        $age = $player->age ?? 25;
        if ($age > 30) {
            $riskFactors[] = [
                'factor' => 'Advanced Age',
                'category' => 'demographic',
                'value' => $age,
                'impact' => $age > 35 ? 'high' : 'medium',
                'description' => 'Older players have higher injury risk due to accumulated wear',
                'mitigation' => ['Increase recovery time', 'Reduce training intensity', 'Enhanced medical monitoring'],
            ];
        }

        // Physical characteristics
        $bmi = $this->calculateBMI($player);
        if ($bmi > 27 || $bmi < 20) {
            $riskFactors[] = [
                'factor' => 'BMI Outside Optimal Range',
                'category' => 'physical',
                'value' => $bmi,
                'impact' => 'medium',
                'description' => 'BMI significantly above or below optimal range increases injury risk',
                'mitigation' => ['Nutrition consultation', 'Targeted fitness program'],
            ];
        }

        // Workload analysis
        $workloadFactors = $this->analyzeWorkloadRisk($player);
        $riskFactors = array_merge($riskFactors, $workloadFactors);

        // Performance decline indicators
        $performanceFactors = $this->analyzePerformanceDeclineRisk($player);
        $riskFactors = array_merge($riskFactors, $performanceFactors);

        // Historical injury patterns
        $historyFactors = $this->analyzeInjuryHistoryRisk($player);
        $riskFactors = array_merge($riskFactors, $historyFactors);

        return $riskFactors;
    }

    public function generateRecommendations(MLPrediction $prediction): array
    {
        $recommendations = [];
        $riskLevel = $prediction->risk_level;
        $riskFactors = $prediction->risk_factors ?? [];

        // Risk level based recommendations
        switch ($riskLevel) {
            case 'very_high':
                $recommendations[] = [
                    'action' => 'Immediate Medical Evaluation',
                    'priority' => 'urgent',
                    'urgency' => 'immediate',
                    'description' => 'Schedule comprehensive medical assessment within 24 hours',
                    'estimated_impact' => 'high',
                ];
                $recommendations[] = [
                    'action' => 'Rest/Reduce Minutes',
                    'priority' => 'urgent',
                    'urgency' => 'immediate', 
                    'description' => 'Consider resting player or significantly reducing playing time',
                    'estimated_impact' => 'high',
                ];
                break;

            case 'high':
                $recommendations[] = [
                    'action' => 'Enhanced Medical Monitoring',
                    'priority' => 'high',
                    'urgency' => 'within_24h',
                    'description' => 'Increase frequency of medical check-ups and monitoring',
                    'estimated_impact' => 'medium',
                ];
                $recommendations[] = [
                    'action' => 'Modified Training Program',
                    'priority' => 'high', 
                    'urgency' => 'within_24h',
                    'description' => 'Adjust training intensity and focus on injury prevention',
                    'estimated_impact' => 'medium',
                ];
                break;

            case 'medium':
                $recommendations[] = [
                    'action' => 'Preventive Measures',
                    'priority' => 'medium',
                    'urgency' => 'within_week',
                    'description' => 'Implement targeted injury prevention exercises',
                    'estimated_impact' => 'medium',
                ];
                break;
        }

        // Factor-specific recommendations
        foreach ($riskFactors as $factor) {
            $factorRecommendations = $this->getFactorSpecificRecommendations($factor);
            $recommendations = array_merge($recommendations, $factorRecommendations);
        }

        // Remove duplicates and prioritize
        $recommendations = $this->deduplicateAndPrioritizeRecommendations($recommendations);

        return $recommendations;
    }

    public function generateTrainingData(string $season): MLTrainingData
    {
        $trainingRecords = [];
        
        // Get all players with sufficient data
        $players = Player::whereHas('gameStatistics', function($query) use ($season) {
            $query->whereHas('game', function($gameQuery) use ($season) {
                $gameQuery->where('season', $season);
            });
        }, '>=', 5)->get(); // At least 5 games

        foreach ($players as $player) {
            $playerRecords = $this->generatePlayerInjuryTrainingRecords($player, $season);
            $trainingRecords = array_merge($trainingRecords, $playerRecords);
        }

        return MLTrainingData::create([
            'dataset_name' => "injury_risk_{$season}",
            'dataset_version' => '1.0.0',
            'description' => "Injury risk training data for {$season} season",
            'data_type' => 'injury_risk_features',
            'source_system' => 'BasketManager Pro',
            'dataset_purpose' => 'training',
            'target_model_type' => 'injury_risk',
            'raw_data' => $trainingRecords,
            'feature_columns' => $this->getInjuryFeatureColumns(),
            'target_columns' => ['injury_occurred', 'days_to_injury', 'injury_severity'],
            'record_count' => count($trainingRecords),
            'feature_count' => count($this->getInjuryFeatureColumns()),
            'season' => $season,
            'collected_at' => now(),
            'is_processed' => false,
            'extraction_method' => 'automated',
            'created_by_user_id' => auth()->id() ?? 1,
        ]);
    }

    private function buildInjuryRiskFeatures(Player $player, int $forecastDays): array
    {
        $features = [];
        
        // Player demographics and physical characteristics
        $features['age'] = $player->age ?? 25;
        $features['height'] = $player->height ?? 200;
        $features['weight'] = $player->weight ?? 90;
        $features['bmi'] = $this->calculateBMI($player);
        $features['position'] = $this->encodePosition($player->position);
        $features['experience_years'] = $player->getExperienceYears();

        // Workload metrics (last 7, 14, 30 days)
        $workloadMetrics = $this->calculateWorkloadMetrics($player);
        $features = array_merge($features, $workloadMetrics);

        // Performance and fatigue indicators
        $fatigueMetrics = $this->calculateFatigueIndicators($player);
        $features = array_merge($features, $fatigueMetrics);

        // Injury history
        $injuryHistory = $this->calculateInjuryHistoryFeatures($player);
        $features = array_merge($features, $injuryHistory);

        // Recovery and wellness metrics
        $recoveryMetrics = $this->calculateRecoveryMetrics($player);
        $features = array_merge($features, $recoveryMetrics);

        // Game and training context
        $contextMetrics = $this->calculateContextualFeatures($player, $forecastDays);
        $features = array_merge($features, $contextMetrics);

        // Biomechanical and performance efficiency
        $biomechanicalMetrics = $this->calculateBiomechanicalFeatures($player);
        $features = array_merge($features, $biomechanicalMetrics);

        return $features;
    }

    private function calculateWorkloadMetrics(Player $player): array
    {
        $metrics = [];
        
        // Game-based workload
        foreach ([7, 14, 30] as $days) {
            $cutoffDate = now()->subDays($days);
            
            $recentStats = GameStatistic::where('player_id', $player->id)
                ->whereHas('game', function($query) use ($cutoffDate) {
                    $query->where('game_datetime', '>=', $cutoffDate);
                })
                ->get();

            $totalMinutes = $recentStats->sum('minutes_played');
            $gameCount = $recentStats->count();
            
            $metrics["minutes_last_{$days}_days"] = $totalMinutes;
            $metrics["games_last_{$days}_days"] = $gameCount;
            $metrics["avg_minutes_per_game_last_{$days}_days"] = $gameCount > 0 ? $totalMinutes / $gameCount : 0;
            
            // High-intensity actions (sprints, jumps approximated by certain stats)
            $highIntensityActions = $recentStats->sum('steals') + $recentStats->sum('blocks') + 
                                  ($recentStats->sum('rebounds') * 0.5); // Rebounds as proxy for jumps
            $metrics["high_intensity_actions_last_{$days}_days"] = $highIntensityActions;
        }

        // Training-based workload
        $trainingLoad = $this->calculateTrainingLoad($player);
        $metrics = array_merge($metrics, $trainingLoad);

        return $metrics;
    }

    private function calculateTrainingLoad(Player $player): array
    {
        $cutoffDate = now()->subDays(14);
        
        $trainingSessions = TrainingSession::where('team_id', $player->team_id)
            ->where('session_date', '>=', $cutoffDate)
            ->get();

        $totalTrainingMinutes = 0;
        $highIntensityTraining = 0;
        $sessionCount = $trainingSessions->count();

        foreach ($trainingSessions as $session) {
            $totalTrainingMinutes += $session->duration_minutes ?? 60; // Default 60 min
            
            // Count high-intensity drills (simplified)
            $drillsCount = $session->drills()->count();
            $highIntensityTraining += $drillsCount * 0.3; // Approximate high intensity factor
        }

        return [
            'training_sessions_last_14_days' => $sessionCount,
            'total_training_minutes_last_14_days' => $totalTrainingMinutes,
            'high_intensity_training_last_14_days' => $highIntensityTraining,
            'avg_training_minutes_per_session' => $sessionCount > 0 ? $totalTrainingMinutes / $sessionCount : 0,
        ];
    }

    private function calculateFatigueIndicators(Player $player): array
    {
        $recentStats = GameStatistic::where('player_id', $player->id)
            ->whereHas('game', function($query) {
                $query->where('game_datetime', '>=', now()->subDays(10));
            })
            ->orderBy('created_at', 'desc')
            ->get();

        if ($recentStats->isEmpty()) {
            return [
                'performance_decline_trend' => 0,
                'efficiency_decline' => 0,
                'fatigue_score' => 0,
            ];
        }

        // Calculate performance trends as fatigue indicators
        $points = $recentStats->pluck('points')->toArray();
        $efficiency = $recentStats->map(function($stat) {
            $minutes = $stat->minutes_played ?: 1;
            return ($stat->points + $stat->assists + $stat->rebounds) / $minutes;
        })->toArray();

        return [
            'performance_decline_trend' => $this->calculateDeclineTrend($points),
            'efficiency_decline' => $this->calculateDeclineTrend($efficiency),
            'fatigue_score' => $this->calculateFatigueScore($recentStats),
            'back_to_back_games_last_7_days' => $this->countBackToBackGames($player, 7),
        ];
    }

    private function calculateInjuryHistoryFeatures(Player $player): array
    {
        // This would integrate with an injury tracking system
        // For now, return placeholder values
        return [
            'total_previous_injuries' => 0, // Would be calculated from injury records
            'days_since_last_injury' => 365, // Placeholder
            'recurring_injury_pattern' => 0, // Boolean: has recurring injuries
            'injury_severity_history' => 1, // Average severity (1-5 scale)
            'similar_injury_risk' => 0.1, // Risk of similar injury recurring
        ];
    }

    private function calculateRecoveryMetrics(Player $player): array
    {
        $lastGame = Game::where(function($query) use ($player) {
            $query->where('home_team_id', $player->team_id)
                  ->orWhere('away_team_id', $player->team_id);
        })
        ->where('game_datetime', '<', now())
        ->orderBy('game_datetime', 'desc')
        ->first();

        $daysSinceLastGame = $lastGame ? $lastGame->game_datetime->diffInDays(now()) : 7;

        return [
            'days_since_last_game' => $daysSinceLastGame,
            'adequate_rest' => $daysSinceLastGame >= 2 ? 1 : 0,
            'recovery_time_ratio' => min(1.0, $daysSinceLastGame / 3), // Normalized recovery time
            
            // These would come from wellness tracking systems
            'sleep_quality_score' => 0.8, // Placeholder (0-1 scale)
            'wellness_rating' => 0.75, // Placeholder (0-1 scale)
            'hydration_status' => 0.85, // Placeholder (0-1 scale)
        ];
    }

    private function calculateContextualFeatures(Player $player, int $forecastDays): array
    {
        // Upcoming game schedule analysis
        $upcomingGames = Game::where(function($query) use ($player) {
            $query->where('home_team_id', $player->team_id)
                  ->orWhere('away_team_id', $player->team_id);
        })
        ->where('game_datetime', '>', now())
        ->where('game_datetime', '<=', now()->addDays($forecastDays))
        ->orderBy('game_datetime')
        ->get();

        $gameCount = $upcomingGames->count();
        $hasBackToBack = $this->hasUpcomingBackToBack($upcomingGames);
        $travelGames = $upcomingGames->where('home_team_id', '!=', $player->team_id)->count();

        return [
            'upcoming_games_count' => $gameCount,
            'games_per_week_upcoming' => ($gameCount / $forecastDays) * 7,
            'has_upcoming_back_to_back' => $hasBackToBack ? 1 : 0,
            'upcoming_travel_games' => $travelGames,
            'schedule_intensity' => $this->calculateScheduleIntensity($upcomingGames),
            
            // Season context
            'season_progress' => $this->calculateSeasonProgress($player),
            'playoff_proximity' => $this->isPlayoffTime() ? 1 : 0,
        ];
    }

    private function calculateBiomechanicalFeatures(Player $player): array
    {
        // These would come from advanced tracking systems (load management devices)
        // For now, estimate based on position and playing style
        $position = $player->position;
        
        return [
            'position_injury_risk_factor' => $this->getPositionInjuryRisk($position),
            'playing_style_risk' => $this->getPlayingStyleRisk($player),
            
            // Estimated biomechanical load (would be measured in practice)
            'estimated_jump_frequency' => $this->estimateJumpFrequency($player),
            'estimated_cutting_frequency' => $this->estimateCuttingFrequency($player),
            'estimated_contact_level' => $this->estimateContactLevel($player),
        ];
    }

    private function generatePlayerInjuryTrainingRecords(Player $player, string $season): array
    {
        $records = [];
        
        // Get all games for the player in the season
        $games = Game::where(function($query) use ($player) {
            $query->where('home_team_id', $player->team_id)
                  ->orWhere('away_team_id', $player->team_id);
        })
        ->where('season', $season)
        ->where('game_datetime', '<=', now())
        ->orderBy('game_datetime')
        ->get();

        foreach ($games as $game) {
            // Create training record for 7 days before each game
            $predictionDate = $game->game_datetime->subDays(7);
            
            $features = $this->buildInjuryRiskFeatures($player, 7);
            
            // Determine if injury occurred in the next 7 days (placeholder logic)
            $injuryOccurred = 0; // Would be determined from injury records
            $daysToInjury = null;
            $injurySeverity = 0;
            
            $record = array_merge($features, [
                'player_id' => $player->id,
                'prediction_date' => $predictionDate->toDateString(),
                'injury_occurred' => $injuryOccurred,
                'days_to_injury' => $daysToInjury,
                'injury_severity' => $injurySeverity,
            ]);
            
            $records[] = $record;
        }

        return $records;
    }

    private function getPrimaryRiskFactors(array $riskFactors): array
    {
        // Sort risk factors by impact and return top 3
        usort($riskFactors, function($a, $b) {
            $impactOrder = ['high' => 3, 'medium' => 2, 'low' => 1];
            $aImpact = $impactOrder[$a['impact']] ?? 1;
            $bImpact = $impactOrder[$b['impact']] ?? 1;
            return $bImpact <=> $aImpact;
        });
        
        return array_slice($riskFactors, 0, 3);
    }

    private function calculateTeamOverallRisk(array $assessments): array
    {
        $totalPlayers = count($assessments);
        $highRiskCount = count(array_filter($assessments, fn($a) => in_array($a['risk_level'], ['very_high', 'high'])));
        
        $avgRiskProbability = collect($assessments)->avg('injury_probability');
        
        return [
            'overall_risk_level' => $avgRiskProbability > 0.6 ? 'high' : ($avgRiskProbability > 0.3 ? 'medium' : 'low'),
            'high_risk_percentage' => $totalPlayers > 0 ? ($highRiskCount / $totalPlayers) * 100 : 0,
            'average_risk_probability' => $avgRiskProbability,
            'players_at_risk' => $highRiskCount,
        ];
    }

    // Helper methods
    private function calculateBMI(Player $player): float
    {
        $height = $player->height ?? 200; // cm
        $weight = $player->weight ?? 90;  // kg
        
        return $weight / (($height / 100) ** 2);
    }

    private function analyzeWorkloadRisk(Player $player): array
    {
        $riskFactors = [];
        
        $minutesLast7 = GameStatistic::where('player_id', $player->id)
            ->whereHas('game', function($query) {
                $query->where('game_datetime', '>=', now()->subDays(7));
            })
            ->sum('minutes_played');

        if ($minutesLast7 > 240) { // More than 240 minutes in last 7 days
            $riskFactors[] = [
                'factor' => 'High Recent Minutes Load',
                'category' => 'workload',
                'value' => $minutesLast7,
                'impact' => $minutesLast7 > 300 ? 'high' : 'medium',
                'description' => 'Excessive playing time increases fatigue and injury risk',
                'mitigation' => ['Reduce minutes', 'Increase rest periods', 'Load management'],
            ];
        }

        return $riskFactors;
    }

    private function analyzePerformanceDeclineRisk(Player $player): array
    {
        // This would analyze recent performance trends
        return []; // Simplified for now
    }

    private function analyzeInjuryHistoryRisk(Player $player): array
    {
        // This would analyze injury history patterns
        return []; // Simplified for now
    }

    private function getFactorSpecificRecommendations(array $factor): array
    {
        return $factor['mitigation'] ?? [];
    }

    private function deduplicateAndPrioritizeRecommendations(array $recommendations): array
    {
        // Remove duplicates and sort by priority
        $unique = [];
        foreach ($recommendations as $rec) {
            $key = is_array($rec) ? ($rec['action'] ?? '') : $rec;
            if (!isset($unique[$key])) {
                $unique[$key] = $rec;
            }
        }
        
        // Sort by priority
        $priorityOrder = ['urgent' => 1, 'high' => 2, 'medium' => 3, 'low' => 4];
        uasort($unique, function($a, $b) use ($priorityOrder) {
            $aPriority = $priorityOrder[$a['priority'] ?? 'medium'] ?? 3;
            $bPriority = $priorityOrder[$b['priority'] ?? 'medium'] ?? 3;
            return $aPriority <=> $bPriority;
        });
        
        return array_values($unique);
    }

    private function calculateDeclineTrend(array $data): float
    {
        if (count($data) < 2) return 0;
        
        // Simple linear regression slope
        $n = count($data);
        $x = range(0, $n - 1);
        $sumX = array_sum($x);
        $sumY = array_sum($data);
        $sumXY = 0;
        $sumX2 = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $data[$i];
            $sumX2 += $x[$i] * $x[$i];
        }
        
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        
        return -$slope; // Negative slope indicates decline
    }

    private function calculateFatigueScore($recentStats): float
    {
        if ($recentStats->isEmpty()) return 0;
        
        // Simple fatigue score based on minutes and game frequency
        $totalMinutes = $recentStats->sum('minutes_played');
        $gameCount = $recentStats->count();
        $daySpan = $recentStats->first()->created_at->diffInDays($recentStats->last()->created_at) ?: 1;
        
        $averageMinutesPerDay = $totalMinutes / $daySpan;
        
        return min(1.0, $averageMinutesPerDay / 40); // Normalized fatigue score
    }

    private function countBackToBackGames(Player $player, int $days): int
    {
        $games = Game::where(function($query) use ($player) {
            $query->where('home_team_id', $player->team_id)
                  ->orWhere('away_team_id', $player->team_id);
        })
        ->where('game_datetime', '>=', now()->subDays($days))
        ->orderBy('game_datetime')
        ->get();

        $backToBackCount = 0;
        for ($i = 1; $i < $games->count(); $i++) {
            $daysDiff = $games[$i-1]->game_datetime->diffInDays($games[$i]->game_datetime);
            if ($daysDiff <= 1) {
                $backToBackCount++;
            }
        }

        return $backToBackCount;
    }

    private function hasUpcomingBackToBack($upcomingGames): bool
    {
        for ($i = 1; $i < $upcomingGames->count(); $i++) {
            $daysDiff = $upcomingGames[$i-1]->game_datetime->diffInDays($upcomingGames[$i]->game_datetime);
            if ($daysDiff <= 1) {
                return true;
            }
        }
        return false;
    }

    private function calculateScheduleIntensity($games): float
    {
        if ($games->isEmpty()) return 0;
        
        $gameCount = $games->count();
        $daySpan = $games->first()->game_datetime->diffInDays($games->last()->game_datetime) ?: 1;
        
        return $gameCount / $daySpan; // Games per day
    }

    private function calculateSeasonProgress(Player $player): float
    {
        // This would calculate how far into the season we are
        return 0.5; // Placeholder: mid-season
    }

    private function isPlayoffTime(): bool
    {
        // Check if current date is in playoff period
        return now()->month >= 4; // Simplified: April onwards
    }

    private function getPositionInjuryRisk(string $position): float
    {
        return match($position) {
            'Center', 'C' => 0.8, // Higher contact, more physical
            'Power Forward', 'PF' => 0.7,
            'Small Forward', 'SF' => 0.6,
            'Point Guard', 'PG' => 0.5, // More cutting, less contact
            'Shooting Guard', 'SG' => 0.5,
            default => 0.6,
        };
    }

    private function getPlayingStyleRisk(Player $player): float
    {
        // This would be based on playing style analysis
        return 0.5; // Placeholder
    }

    private function estimateJumpFrequency(Player $player): float
    {
        $position = $player->position;
        return match($position) {
            'Center', 'C' => 0.8,
            'Power Forward', 'PF' => 0.7,
            'Small Forward', 'SF' => 0.6,
            default => 0.5,
        };
    }

    private function estimateCuttingFrequency(Player $player): float
    {
        $position = $player->position;
        return match($position) {
            'Point Guard', 'PG' => 0.8,
            'Shooting Guard', 'SG' => 0.7,
            'Small Forward', 'SF' => 0.6,
            default => 0.4,
        };
    }

    private function estimateContactLevel(Player $player): float
    {
        $position = $player->position;
        return match($position) {
            'Center', 'C' => 0.9,
            'Power Forward', 'PF' => 0.8,
            'Small Forward', 'SF' => 0.6,
            'Shooting Guard', 'SG' => 0.5,
            'Point Guard', 'PG' => 0.4,
            default => 0.6,
        };
    }

    private function encodePosition(string $position): int
    {
        return match($position) {
            'Point Guard', 'PG' => 1,
            'Shooting Guard', 'SG' => 2,
            'Small Forward', 'SF' => 3,
            'Power Forward', 'PF' => 4,
            'Center', 'C' => 5,
            default => 3,
        };
    }

    private function getInjuryFeatureColumns(): array
    {
        return [
            'age', 'height', 'weight', 'bmi', 'position', 'experience_years',
            'minutes_last_7_days', 'games_last_7_days', 'minutes_last_14_days', 'games_last_14_days',
            'training_sessions_last_14_days', 'total_training_minutes_last_14_days',
            'performance_decline_trend', 'efficiency_decline', 'fatigue_score',
            'days_since_last_game', 'adequate_rest',
            'upcoming_games_count', 'has_upcoming_back_to_back',
            'position_injury_risk_factor', 'estimated_jump_frequency',
            'total_previous_injuries', 'days_since_last_injury',
        ];
    }
}