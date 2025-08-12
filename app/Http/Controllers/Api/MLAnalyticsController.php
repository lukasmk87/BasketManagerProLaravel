<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MLModel;
use App\Models\MLPrediction;
use App\Models\MLExperiment;
use App\Models\Player;
use App\Models\Team;
use App\Models\Game;
use App\Services\ML\MLPredictionService;
use App\Services\ML\PlayerPerformancePredictionService;
use App\Services\ML\InjuryRiskPredictionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class MLAnalyticsController extends Controller
{
    private MLPredictionService $mlService;
    private PlayerPerformancePredictionService $performanceService;
    private InjuryRiskPredictionService $injuryService;

    public function __construct(
        MLPredictionService $mlService,
        PlayerPerformancePredictionService $performanceService,
        InjuryRiskPredictionService $injuryService
    ) {
        $this->mlService = $mlService;
        $this->performanceService = $performanceService;
        $this->injuryService = $injuryService;
    }

    /**
     * Get dashboard overview with key metrics
     */
    public function getDashboardOverview(): JsonResponse
    {
        $overview = [
            'models_summary' => $this->getModelsOverview(),
            'recent_predictions' => $this->getRecentPredictions(),
            'performance_metrics' => $this->getPerformanceMetrics(),
            'system_health' => $this->getSystemHealth(),
        ];

        return response()->json($overview);
    }

    /**
     * Get player performance predictions dashboard
     */
    public function getPlayerPerformanceDashboard(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'team_id' => 'nullable|exists:teams,id',
            'player_ids' => 'nullable|array',
            'player_ids.*' => 'exists:players,id',
            'date_range' => 'nullable|in:7,14,30,90',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $teamId = $request->get('team_id');
        $playerIds = $request->get('player_ids', []);
        $dateRange = $request->get('date_range', 30);

        $dashboard = [
            'team_overview' => $this->getTeamPerformanceOverview($teamId),
            'top_performers' => $this->getTopPredictedPerformers($teamId, $playerIds),
            'breakout_candidates' => $this->getBreakoutCandidates($teamId, $playerIds),
            'performance_trends' => $this->getPerformanceTrends($playerIds, $dateRange),
            'upcoming_predictions' => $this->getUpcomingPerformancePredictions($teamId, $playerIds),
        ];

        return response()->json($dashboard);
    }

    /**
     * Get injury risk dashboard
     */
    public function getInjuryRiskDashboard(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'team_id' => 'nullable|exists:teams,id',
            'risk_threshold' => 'nullable|numeric|between:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $teamId = $request->get('team_id');
        $riskThreshold = $request->get('risk_threshold', 0.6);

        $dashboard = [
            'team_risk_overview' => $this->getTeamInjuryRiskOverview($teamId),
            'high_risk_players' => $this->getHighRiskPlayers($teamId, $riskThreshold),
            'risk_factors_analysis' => $this->getRiskFactorsAnalysis($teamId),
            'workload_analysis' => $this->getWorkloadAnalysis($teamId),
            'injury_prevention_recommendations' => $this->getInjuryPreventionRecommendations($teamId),
        ];

        return response()->json($dashboard);
    }

    /**
     * Get ML experiment dashboard
     */
    public function getExperimentDashboard(): JsonResponse
    {
        $dashboard = [
            'recent_experiments' => $this->getRecentExperiments(),
            'experiment_success_rates' => $this->getExperimentSuccessRates(),
            'model_performance_comparison' => $this->getModelPerformanceComparison(),
            'running_experiments' => $this->getRunningExperiments(),
            'experiment_insights' => $this->getExperimentInsights(),
        ];

        return response()->json($dashboard);
    }

    /**
     * Get predictions timeline
     */
    public function getPredictionsTimeline(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'prediction_type' => 'nullable|in:player_performance,injury_risk,game_outcome',
            'days' => 'nullable|integer|min:1|max:365',
            'team_id' => 'nullable|exists:teams,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $predictionType = $request->get('prediction_type');
        $days = $request->get('days', 30);
        $teamId = $request->get('team_id');

        $timeline = $this->buildPredictionsTimeline($predictionType, $days, $teamId);

        return response()->json($timeline);
    }

    /**
     * Get model accuracy metrics
     */
    public function getModelAccuracy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'model_id' => 'nullable|exists:ml_models,id',
            'model_type' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $modelId = $request->get('model_id');
        $modelType = $request->get('model_type');

        $accuracy = $this->calculateModelAccuracy($modelId, $modelType);

        return response()->json($accuracy);
    }

    // Private helper methods

    private function getModelsOverview(): array
    {
        $models = MLModel::select('model_type', 'status')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('AVG(accuracy_score) as avg_accuracy')
            ->selectRaw('AVG(confidence_score) as avg_confidence')
            ->groupBy('model_type', 'status')
            ->get();

        $totalModels = MLModel::count();
        $activeModels = MLModel::where('is_active', true)->count();

        return [
            'total_models' => $totalModels,
            'active_models' => $activeModels,
            'models_by_type' => $models->groupBy('model_type'),
            'overall_accuracy' => MLModel::where('is_active', true)->avg('accuracy_score') ?? 0,
        ];
    }

    private function getRecentPredictions(): array
    {
        $recentPredictions = MLPrediction::with(['model', 'predictable'])
            ->where('created_at', '>=', now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        $predictionStats = MLPrediction::where('created_at', '>=', now()->subDays(7))
            ->selectRaw('prediction_type, COUNT(*) as count')
            ->selectRaw('AVG(confidence_score) as avg_confidence')
            ->groupBy('prediction_type')
            ->get();

        return [
            'recent_predictions' => $recentPredictions,
            'prediction_stats' => $predictionStats,
            'daily_volume' => $this->getDailyPredictionVolume(),
        ];
    }

    private function getPerformanceMetrics(): array
    {
        $totalPredictions = MLPrediction::count();
        $accuratePredictions = MLPrediction::where('was_accurate', true)->count();
        
        return [
            'total_predictions' => $totalPredictions,
            'overall_accuracy' => $totalPredictions > 0 ? ($accuratePredictions / $totalPredictions) : 0,
            'accuracy_by_type' => $this->getAccuracyByType(),
            'confidence_distribution' => $this->getConfidenceDistribution(),
        ];
    }

    private function getSystemHealth(): array
    {
        $lastPrediction = MLPrediction::latest()->first();
        $failedPredictions = MLPrediction::where('created_at', '>=', now()->subHour())
            ->whereNotNull('error_details')
            ->count();
        $modelErrors = MLModel::where('error_count', '>', 0)->count();

        return [
            'status' => $failedPredictions < 5 ? 'healthy' : 'degraded',
            'last_prediction' => $lastPrediction?->created_at,
            'failed_predictions_last_hour' => $failedPredictions,
            'models_with_errors' => $modelErrors,
            'system_load' => $this->calculateSystemLoad(),
        ];
    }

    private function getTeamPerformanceOverview($teamId): array
    {
        if (!$teamId) {
            return ['error' => 'Team ID required'];
        }

        $players = Player::where('team_id', $teamId)->get();
        $recentPredictions = MLPrediction::where('prediction_type', 'player_performance')
            ->whereIn('predictable_id', $players->pluck('id'))
            ->where('created_at', '>=', now()->subDays(7))
            ->get();

        return [
            'team_id' => $teamId,
            'total_players' => $players->count(),
            'recent_predictions' => $recentPredictions->count(),
            'avg_predicted_performance' => $recentPredictions->avg('prediction_value'),
            'performance_distribution' => $this->getPerformanceDistribution($recentPredictions),
        ];
    }

    private function getTopPredictedPerformers($teamId, $playerIds): array
    {
        $query = MLPrediction::where('prediction_type', 'player_performance')
            ->where('created_at', '>=', now()->subDays(7));

        if ($teamId) {
            $playerIdsFromTeam = Player::where('team_id', $teamId)->pluck('id');
            $query->whereIn('predictable_id', $playerIdsFromTeam);
        } elseif (!empty($playerIds)) {
            $query->whereIn('predictable_id', $playerIds);
        }

        $topPerformers = $query->with('predictable')
            ->orderBy('prediction_value', 'desc')
            ->limit(10)
            ->get();

        return $topPerformers->map(function ($prediction) {
            return [
                'player' => $prediction->predictable,
                'predicted_points' => $prediction->prediction_value,
                'confidence' => $prediction->confidence_score,
                'prediction_date' => $prediction->created_at,
            ];
        })->toArray();
    }

    private function getBreakoutCandidates($teamId, $playerIds): array
    {
        $playerQuery = Player::query();
        
        if ($teamId) {
            $playerQuery->where('team_id', $teamId);
        } elseif (!empty($playerIds)) {
            $playerQuery->whereIn('id', $playerIds);
        }

        $players = $playerQuery->get();
        $candidates = [];

        foreach ($players as $player) {
            try {
                $breakoutAnalysis = $this->performanceService->analyzePerformanceTrends($player, 30);
                
                if ($breakoutAnalysis['trend'] === 'improving' && $breakoutAnalysis['confidence'] > 0.6) {
                    $candidates[] = [
                        'player' => $player,
                        'trend_analysis' => $breakoutAnalysis,
                        'breakout_probability' => min(1.0, $breakoutAnalysis['confidence'] * 1.2),
                    ];
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        usort($candidates, fn($a, $b) => $b['breakout_probability'] <=> $a['breakout_probability']);

        return array_slice($candidates, 0, 5);
    }

    private function getPerformanceTrends($playerIds, $dateRange): array
    {
        if (empty($playerIds)) {
            return [];
        }

        $trends = [];
        foreach ($playerIds as $playerId) {
            $player = Player::find($playerId);
            if (!$player) continue;

            try {
                $trendAnalysis = $this->performanceService->analyzePerformanceTrends($player, $dateRange);
                $trends[] = [
                    'player' => $player,
                    'trend_analysis' => $trendAnalysis,
                ];
            } catch (\Exception $e) {
                continue;
            }
        }

        return $trends;
    }

    private function getUpcomingPerformancePredictions($teamId, $playerIds): array
    {
        $upcomingGames = Game::where('game_datetime', '>', now())
            ->where('game_datetime', '<=', now()->addDays(7))
            ->when($teamId, function ($query) use ($teamId) {
                $query->where(function ($q) use ($teamId) {
                    $q->where('home_team_id', $teamId)
                      ->orWhere('away_team_id', $teamId);
                });
            })
            ->orderBy('game_datetime')
            ->get();

        $predictions = [];
        foreach ($upcomingGames as $game) {
            $teamPlayers = Player::where('team_id', $teamId ?? $game->home_team_id)
                ->when(!empty($playerIds), function ($query) use ($playerIds) {
                    $query->whereIn('id', $playerIds);
                })
                ->get();

            foreach ($teamPlayers as $player) {
                try {
                    $prediction = $this->performanceService->predictGamePerformance($player, $game);
                    $predictions[] = [
                        'game' => $game,
                        'player' => $player,
                        'prediction' => $prediction,
                    ];
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        return $predictions;
    }

    private function getTeamInjuryRiskOverview($teamId): array
    {
        if (!$teamId) {
            return ['error' => 'Team ID required'];
        }

        try {
            $riskAssessment = $this->injuryService->assessTeamInjuryRisk($teamId);
            return $riskAssessment;
        } catch (\Exception $e) {
            return [
                'error' => 'Failed to assess team injury risk',
                'message' => $e->getMessage(),
            ];
        }
    }

    private function getHighRiskPlayers($teamId, $riskThreshold): array
    {
        $highRiskPlayers = [];
        
        $players = Player::where('team_id', $teamId)->get();
        
        foreach ($players as $player) {
            try {
                $riskPrediction = $this->injuryService->predictInjuryRisk($player);
                
                if ($riskPrediction->injury_risk_probability >= $riskThreshold) {
                    $highRiskPlayers[] = [
                        'player' => $player,
                        'risk_prediction' => $riskPrediction,
                        'risk_factors' => $this->injuryService->identifyRiskFactors($player),
                    ];
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        usort($highRiskPlayers, fn($a, $b) => 
            $b['risk_prediction']->injury_risk_probability <=> $a['risk_prediction']->injury_risk_probability
        );

        return $highRiskPlayers;
    }

    private function getRiskFactorsAnalysis($teamId): array
    {
        $players = Player::where('team_id', $teamId)->get();
        $allRiskFactors = [];
        $factorFrequency = [];

        foreach ($players as $player) {
            try {
                $riskFactors = $this->injuryService->identifyRiskFactors($player);
                $allRiskFactors = array_merge($allRiskFactors, $riskFactors);
                
                foreach ($riskFactors as $factor) {
                    $factorName = $factor['factor'];
                    if (!isset($factorFrequency[$factorName])) {
                        $factorFrequency[$factorName] = 0;
                    }
                    $factorFrequency[$factorName]++;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        arsort($factorFrequency);

        return [
            'common_risk_factors' => array_slice($factorFrequency, 0, 10, true),
            'total_risk_factors_identified' => count($allRiskFactors),
            'players_analyzed' => $players->count(),
        ];
    }

    private function getWorkloadAnalysis($teamId): array
    {
        return [
            'team_average_minutes' => 0,
            'high_workload_players' => [],
            'workload_trends' => [],
            'recommendations' => [],
        ];
    }

    private function getInjuryPreventionRecommendations($teamId): array
    {
        return [
            'load_management' => [
                'description' => 'Monitor player minutes and implement rotation',
                'priority' => 'high',
                'affected_players' => [],
            ],
            'recovery_protocols' => [
                'description' => 'Enhance recovery protocols between games',
                'priority' => 'medium',
                'implementation' => 'team-wide',
            ],
            'monitoring' => [
                'description' => 'Increase frequency of medical assessments',
                'priority' => 'medium',
                'focus_areas' => ['fatigue', 'biomechanics'],
            ],
        ];
    }

    private function getRecentExperiments(): array
    {
        return MLExperiment::with(['createdBy', 'bestModel'])
            ->recent(30)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($experiment) {
                return [
                    'id' => $experiment->id,
                    'name' => $experiment->name,
                    'type' => $experiment->experiment_type,
                    'status' => $experiment->status,
                    'improvement' => $experiment->improvement_over_baseline,
                    'significant' => $experiment->statistically_significant,
                    'duration' => $experiment->duration_minutes,
                    'created_by' => $experiment->createdBy?->name,
                    'created_at' => $experiment->created_at,
                ];
            })
            ->toArray();
    }

    private function getExperimentSuccessRates(): array
    {
        $total = MLExperiment::completed()->count();
        $successful = MLExperiment::successful()->count();
        $significant = MLExperiment::significant()->count();

        return [
            'total_experiments' => $total,
            'successful_experiments' => $successful,
            'significant_results' => $significant,
            'success_rate' => $total > 0 ? ($successful / $total) : 0,
            'significance_rate' => $total > 0 ? ($significant / $total) : 0,
        ];
    }

    private function getModelPerformanceComparison(): array
    {
        return MLExperiment::completed()
            ->whereNotNull('best_model_metrics')
            ->get()
            ->groupBy('experiment_type')
            ->map(function ($experiments, $type) {
                return [
                    'experiment_type' => $type,
                    'experiments_count' => $experiments->count(),
                    'avg_improvement' => $experiments->avg('improvement_over_baseline'),
                    'best_improvement' => $experiments->max('improvement_over_baseline'),
                ];
            })
            ->values()
            ->toArray();
    }

    private function getRunningExperiments(): array
    {
        return MLExperiment::running()
            ->with('createdBy')
            ->get()
            ->map(function ($experiment) {
                return [
                    'id' => $experiment->id,
                    'name' => $experiment->name,
                    'type' => $experiment->experiment_type,
                    'progress' => $experiment->iterations_completed / ($experiment->total_iterations ?: 1),
                    'started_at' => $experiment->started_at,
                    'estimated_completion' => $this->estimateExperimentCompletion($experiment),
                ];
            })
            ->toArray();
    }

    private function getExperimentInsights(): array
    {
        return [
            'most_successful_algorithm' => $this->getMostSuccessfulAlgorithm(),
            'optimal_hyperparameters' => $this->getOptimalHyperparameters(),
            'feature_importance_insights' => $this->getFeatureImportanceInsights(),
            'recommendations' => $this->getExperimentRecommendations(),
        ];
    }

    // Additional helper methods
    
    private function getDailyPredictionVolume(): array
    {
        return MLPrediction::where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();
    }

    private function getAccuracyByType(): array
    {
        return MLPrediction::selectRaw('prediction_type')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN was_accurate = 1 THEN 1 ELSE 0 END) as accurate')
            ->selectRaw('AVG(CASE WHEN was_accurate = 1 THEN 1 ELSE 0 END) as accuracy_rate')
            ->groupBy('prediction_type')
            ->get()
            ->keyBy('prediction_type')
            ->toArray();
    }

    private function getConfidenceDistribution(): array
    {
        return [
            'high' => MLPrediction::where('confidence_score', '>=', 0.8)->count(),
            'medium' => MLPrediction::whereBetween('confidence_score', [0.6, 0.8])->count(),
            'low' => MLPrediction::where('confidence_score', '<', 0.6)->count(),
        ];
    }

    private function calculateSystemLoad(): float
    {
        $recentPredictions = MLPrediction::where('created_at', '>=', now()->subHour())->count();
        return min(1.0, $recentPredictions / 100);
    }

    private function getPerformanceDistribution($predictions): array
    {
        $values = $predictions->pluck('prediction_value')->filter()->toArray();
        
        if (empty($values)) {
            return ['ranges' => [], 'distribution' => []];
        }

        $ranges = [
            'excellent' => count(array_filter($values, fn($v) => $v >= 25)),
            'very_good' => count(array_filter($values, fn($v) => $v >= 20 && $v < 25)),
            'good' => count(array_filter($values, fn($v) => $v >= 15 && $v < 20)),
            'average' => count(array_filter($values, fn($v) => $v >= 10 && $v < 15)),
            'below_average' => count(array_filter($values, fn($v) => $v < 10)),
        ];

        return [
            'ranges' => $ranges,
            'total_predictions' => count($values),
        ];
    }

    private function buildPredictionsTimeline($predictionType, $days, $teamId): array
    {
        $query = MLPrediction::where('created_at', '>=', now()->subDays($days));
        
        if ($predictionType) {
            $query->where('prediction_type', $predictionType);
        }
        
        if ($teamId) {
            $playerIds = Player::where('team_id', $teamId)->pluck('id');
            $query->whereIn('predictable_id', $playerIds);
        }
        
        $predictions = $query->orderBy('created_at')
            ->get()
            ->groupBy(function ($prediction) {
                return $prediction->created_at->format('Y-m-d');
            });

        return [
            'timeline' => $predictions->map(function ($dayPredictions, $date) {
                return [
                    'date' => $date,
                    'count' => $dayPredictions->count(),
                    'avg_confidence' => $dayPredictions->avg('confidence_score'),
                    'types' => $dayPredictions->countBy('prediction_type'),
                ];
            })->values(),
            'summary' => [
                'total_predictions' => $query->count(),
                'date_range' => [
                    'start' => now()->subDays($days)->format('Y-m-d'),
                    'end' => now()->format('Y-m-d'),
                ],
            ],
        ];
    }

    private function calculateModelAccuracy($modelId, $modelType): array
    {
        $query = MLPrediction::query();
        
        if ($modelId) {
            $query->where('model_id', $modelId);
        } elseif ($modelType) {
            $query->where('prediction_type', $modelType);
        }
        
        $predictions = $query->whereNotNull('was_accurate')->get();
        
        if ($predictions->isEmpty()) {
            return [
                'overall_accuracy' => 0,
                'total_predictions' => 0,
                'message' => 'No validated predictions found',
            ];
        }
        
        $accurate = $predictions->where('was_accurate', true)->count();
        $total = $predictions->count();
        
        return [
            'overall_accuracy' => $total > 0 ? ($accurate / $total) : 0,
            'total_predictions' => $total,
            'accurate_predictions' => $accurate,
            'confidence_vs_accuracy' => $this->analyzeConfidenceVsAccuracy($predictions),
        ];
    }

    private function analyzeConfidenceVsAccuracy($predictions): array
    {
        $confidenceBuckets = [
            'high' => $predictions->where('confidence_score', '>=', 0.8),
            'medium' => $predictions->whereBetween('confidence_score', [0.6, 0.8]),
            'low' => $predictions->where('confidence_score', '<', 0.6),
        ];
        
        return collect($confidenceBuckets)->map(function ($bucket, $level) {
            $total = $bucket->count();
            $accurate = $bucket->where('was_accurate', true)->count();
            
            return [
                'confidence_level' => $level,
                'total_predictions' => $total,
                'accurate_predictions' => $accurate,
                'accuracy_rate' => $total > 0 ? ($accurate / $total) : 0,
            ];
        })->values()->toArray();
    }

    // Placeholder methods for experiment insights
    private function getMostSuccessfulAlgorithm(): array
    {
        return ['algorithm' => 'random_forest', 'success_rate' => 0.85];
    }

    private function getOptimalHyperparameters(): array
    {
        return ['n_estimators' => 100, 'max_depth' => 10];
    }

    private function getFeatureImportanceInsights(): array
    {
        return ['top_features' => ['recent_performance', 'age', 'position']];
    }

    private function getExperimentRecommendations(): array
    {
        return [
            'Try ensemble methods for better performance',
            'Increase training data size for injury risk models',
            'Consider feature engineering for player position interactions'
        ];
    }

    private function estimateExperimentCompletion($experiment): ?string
    {
        if (!$experiment->started_at || !$experiment->total_iterations || !$experiment->iterations_completed) {
            return null;
        }
        
        $elapsed = $experiment->started_at->diffInMinutes(now());
        $progress = $experiment->iterations_completed / $experiment->total_iterations;
        
        if ($progress <= 0) {
            return null;
        }
        
        $totalEstimated = $elapsed / $progress;
        $remaining = $totalEstimated - $elapsed;
        
        return now()->addMinutes($remaining)->toISOString();
    }
}