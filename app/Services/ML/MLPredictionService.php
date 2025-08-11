<?php

namespace App\Services\ML;

use App\Models\MLModel;
use App\Models\MLPrediction;
use App\Models\Player;
use App\Models\Game;
use App\Models\Team;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Exception;

class MLPredictionService
{
    private string $pythonExecutable;
    private string $scriptPath;

    public function __construct()
    {
        $this->pythonExecutable = config('ml.python_executable', '/usr/bin/python3');
        $this->scriptPath = base_path('scripts/ml');
    }

    public function makePrediction(
        MLModel $model, 
        $entity, 
        array $inputData, 
        array $context = []
    ): MLPrediction {
        $startTime = microtime(true);

        try {
            // Validate model can make predictions
            if (!$model->canMakePrediction()) {
                throw new Exception("Model {$model->name} is not ready for predictions");
            }

            // Validate input data
            $validationErrors = $model->validateInputData($inputData);
            if (!empty($validationErrors)) {
                throw new Exception("Input validation failed: " . implode(', ', $validationErrors));
            }

            // Preprocess input data
            $processedData = $this->preprocessInputData($inputData, $model);

            // Make prediction using Python script
            $predictionResult = $this->executePythonPrediction($model, $processedData);

            // Create prediction record
            $prediction = $this->createPredictionRecord(
                $model,
                $entity,
                $inputData,
                $processedData,
                $predictionResult,
                $context,
                microtime(true) - $startTime
            );

            // Check if alert should be triggered
            if ($prediction->shouldTriggerAlert()) {
                $prediction->triggerAlert();
            }

            return $prediction;

        } catch (Exception $e) {
            Log::error("ML Prediction failed: " . $e->getMessage(), [
                'model_id' => $model->id,
                'entity_type' => get_class($entity),
                'entity_id' => $entity->id ?? null,
                'error' => $e->getMessage(),
            ]);

            $model->recordPrediction(false, (microtime(true) - $startTime) * 1000);
            throw $e;
        }
    }

    public function makeBatchPredictions(
        MLModel $model,
        array $entities,
        array $batchInputData,
        array $context = []
    ): array {
        $predictions = [];
        $startTime = microtime(true);

        try {
            // Validate batch data
            if (count($entities) !== count($batchInputData)) {
                throw new Exception("Entity count must match input data count");
            }

            // Process batch through Python
            $batchResults = $this->executeBatchPythonPrediction($model, $batchInputData);

            // Create prediction records
            foreach ($entities as $index => $entity) {
                $inputData = $batchInputData[$index];
                $result = $batchResults[$index];
                $processedData = $result['processed_features'] ?? $inputData;

                $prediction = $this->createPredictionRecord(
                    $model,
                    $entity,
                    $inputData,
                    $processedData,
                    $result,
                    $context,
                    $result['processing_time_ms'] ?? 0
                );

                $predictions[] = $prediction;
            }

            $totalTime = (microtime(true) - $startTime) * 1000;
            $averageTime = $totalTime / count($entities);
            
            // Update model performance
            $model->recordPrediction(true, $averageTime);

            return $predictions;

        } catch (Exception $e) {
            Log::error("Batch ML Prediction failed: " . $e->getMessage(), [
                'model_id' => $model->id,
                'entity_count' => count($entities),
            ]);

            $model->recordPrediction(false, (microtime(true) - $startTime) * 1000);
            throw $e;
        }
    }

    public function predictPlayerPerformance(
        Player $player,
        Game $game,
        array $additionalContext = []
    ): MLPrediction {
        $model = MLModel::active()
            ->byType('player_performance')
            ->where('accuracy', '>=', 0.7)
            ->first();

        if (!$model) {
            throw new Exception("No suitable player performance model available");
        }

        // Gather player statistics and context
        $inputData = $this->gatherPlayerFeatures($player, $game, $additionalContext);

        return $this->makePrediction($model, $player, $inputData, [
            'prediction_context' => 'game',
            'game_id' => $game->id,
            'target_date' => $game->game_datetime,
        ]);
    }

    public function predictInjuryRisk(
        Player $player,
        int $forecastDays = 7,
        array $additionalContext = []
    ): MLPrediction {
        $model = MLModel::active()
            ->byType('injury_risk')
            ->where('accuracy', '>=', 0.75)
            ->first();

        if (!$model) {
            throw new Exception("No suitable injury risk model available");
        }

        // Gather injury risk features
        $inputData = $this->gatherInjuryRiskFeatures($player, $forecastDays, $additionalContext);

        return $this->makePrediction($model, $player, $inputData, [
            'prediction_context' => 'injury_prevention',
            'prediction_horizon_days' => $forecastDays,
            'target_date' => now()->addDays($forecastDays),
            'is_critical' => true,
        ]);
    }

    public function predictGameOutcome(
        Game $game,
        array $additionalContext = []
    ): MLPrediction {
        $model = MLModel::active()
            ->byType('game_outcome')
            ->where('accuracy', '>=', 0.65)
            ->first();

        if (!$model) {
            throw new Exception("No suitable game outcome model available");
        }

        // Gather game features
        $inputData = $this->gatherGameFeatures($game, $additionalContext);

        return $this->makePrediction($model, $game, $inputData, [
            'prediction_context' => 'pre_game',
            'game_id' => $game->id,
            'target_date' => $game->game_datetime,
        ]);
    }

    private function executePythonPrediction(MLModel $model, array $processedData): array
    {
        $inputFile = $this->createTempInputFile($processedData);
        $outputFile = tempnam(sys_get_temp_dir(), 'ml_prediction_output_');

        try {
            $command = [
                $this->pythonExecutable,
                $this->scriptPath . '/predict.py',
                '--model-path', Storage::path($model->file_path),
                '--input-file', $inputFile,
                '--output-file', $outputFile,
                '--model-type', $model->type,
                '--model-algorithm', $model->algorithm,
            ];

            $result = Process::run(implode(' ', array_map('escapeshellarg', $command)));

            if (!$result->successful()) {
                throw new Exception("Python prediction failed: " . $result->errorOutput());
            }

            $output = json_decode(file_get_contents($outputFile), true);
            
            if (!$output) {
                throw new Exception("Invalid prediction output from Python script");
            }

            return $output;

        } finally {
            @unlink($inputFile);
            @unlink($outputFile);
        }
    }

    private function executeBatchPythonPrediction(MLModel $model, array $batchData): array
    {
        $inputFile = $this->createTempInputFile(['batch_data' => $batchData]);
        $outputFile = tempnam(sys_get_temp_dir(), 'ml_batch_prediction_output_');

        try {
            $command = [
                $this->pythonExecutable,
                $this->scriptPath . '/batch_predict.py',
                '--model-path', Storage::path($model->file_path),
                '--input-file', $inputFile,
                '--output-file', $outputFile,
                '--model-type', $model->type,
            ];

            $result = Process::run(implode(' ', array_map('escapeshellarg', $command)));

            if (!$result->successful()) {
                throw new Exception("Python batch prediction failed: " . $result->errorOutput());
            }

            $output = json_decode(file_get_contents($outputFile), true);
            return $output['predictions'] ?? [];

        } finally {
            @unlink($inputFile);
            @unlink($outputFile);
        }
    }

    private function preprocessInputData(array $inputData, MLModel $model): array
    {
        $preprocessingConfig = $model->preprocessing_config ?? [];

        // Apply preprocessing steps
        $processedData = $inputData;

        // Handle missing values
        if (isset($preprocessingConfig['fill_missing'])) {
            $processedData = $this->fillMissingValues($processedData, $preprocessingConfig['fill_missing']);
        }

        // Feature scaling
        if (isset($preprocessingConfig['scaling'])) {
            $processedData = $this->applyFeatureScaling($processedData, $preprocessingConfig['scaling']);
        }

        // Feature engineering
        if (isset($preprocessingConfig['feature_engineering'])) {
            $processedData = $this->applyFeatureEngineering($processedData, $preprocessingConfig['feature_engineering']);
        }

        return $processedData;
    }

    private function gatherPlayerFeatures(Player $player, Game $game, array $additionalContext): array
    {
        // Recent performance metrics (last 10 games)
        $recentStats = $player->getRecentGameStats(10);

        // Season statistics
        $seasonStats = $player->getSeasonStatistics($game->season);

        // Opponent analysis
        $opponentTeam = $game->home_team_id === $player->team_id ? $game->awayTeam : $game->homeTeam;
        $vsOpponentStats = $player->getStatsAgainstTeam($opponentTeam->id);

        // Game context
        $gameContext = [
            'is_home_game' => $game->home_team_id === $player->team_id,
            'days_rest' => $player->getDaysRestBeforeGame($game),
            'game_importance' => $this->calculateGameImportance($game),
            'opponent_defensive_rating' => $opponentTeam->getDefensiveRating(),
        ];

        // Physical and biographical features
        $playerFeatures = [
            'age' => $player->age,
            'position' => $player->position,
            'experience_years' => $player->getExperienceYears(),
            'minutes_per_game' => $seasonStats['minutes_per_game'] ?? 0,
        ];

        return array_merge($recentStats, $seasonStats, $vsOpponentStats, $gameContext, $playerFeatures, $additionalContext);
    }

    private function gatherInjuryRiskFeatures(Player $player, int $forecastDays, array $additionalContext): array
    {
        // Training load metrics
        $trainingLoad = $player->getRecentTrainingLoad(14); // Last 2 weeks

        // Game load metrics  
        $gameLoad = $player->getRecentGameLoad(10); // Last 10 games

        // Injury history
        $injuryHistory = $player->getInjuryHistory();

        // Physical metrics
        $physicalMetrics = [
            'age' => $player->age,
            'weight' => $player->weight,
            'height' => $player->height,
            'bmi' => $player->getBMI(),
        ];

        // Fatigue indicators
        $fatigueIndicators = [
            'minutes_last_7_days' => $player->getMinutesLastNDays(7),
            'games_last_7_days' => $player->getGamesLastNDays(7),
            'back_to_back_games' => $player->hasBackToBackGames(),
        ];

        // Recovery metrics
        $recoveryMetrics = [
            'days_since_last_game' => $player->getDaysSinceLastGame(),
            'sleep_quality' => $additionalContext['sleep_quality'] ?? null,
            'wellness_score' => $additionalContext['wellness_score'] ?? null,
        ];

        return array_merge(
            $trainingLoad, 
            $gameLoad, 
            $injuryHistory, 
            $physicalMetrics, 
            $fatigueIndicators, 
            $recoveryMetrics,
            $additionalContext
        );
    }

    private function gatherGameFeatures(Game $game, array $additionalContext): array
    {
        $homeTeam = $game->homeTeam;
        $awayTeam = $game->awayTeam;

        // Team statistics
        $homeStats = $homeTeam->getSeasonStatistics($game->season);
        $awayStats = $awayTeam->getSeasonStatistics($game->season);

        // Head-to-head record
        $headToHead = $homeTeam->getHeadToHeadRecord($awayTeam->id);

        // Recent form
        $homeForm = $homeTeam->getRecentForm(5); // Last 5 games
        $awayForm = $awayTeam->getRecentForm(5);

        // Game context
        $gameContext = [
            'game_importance' => $this->calculateGameImportance($game),
            'rest_days_home' => $homeTeam->getDaysRestBeforeGame($game),
            'rest_days_away' => $awayTeam->getDaysRestBeforeGame($game),
            'is_rivalry_game' => $this->isRivalryGame($homeTeam, $awayTeam),
        ];

        // Player availability
        $playerAvailability = [
            'home_injured_players' => $homeTeam->getInjuredPlayersCount(),
            'away_injured_players' => $awayTeam->getInjuredPlayersCount(),
            'home_key_players_available' => $homeTeam->areKeyPlayersAvailable($game),
            'away_key_players_available' => $awayTeam->areKeyPlayersAvailable($game),
        ];

        return array_merge(
            $this->prefixKeys($homeStats, 'home_'),
            $this->prefixKeys($awayStats, 'away_'),
            $headToHead,
            $this->prefixKeys($homeForm, 'home_recent_'),
            $this->prefixKeys($awayForm, 'away_recent_'),
            $gameContext,
            $playerAvailability,
            $additionalContext
        );
    }

    private function createPredictionRecord(
        MLModel $model,
        $entity,
        array $inputData,
        array $processedData,
        array $predictionResult,
        array $context,
        float $processingTime
    ): MLPrediction {
        // Determine quality assessment
        $qualityAssessment = $this->assessPredictionQuality(
            $predictionResult['confidence'] ?? 0,
            $inputData,
            $model
        );

        // Extract basketball-specific predictions
        $performanceMetrics = null;
        $injuryRiskProbability = null;
        $riskFactors = null;
        $recommendedActions = null;

        if ($model->type === 'player_performance') {
            $performanceMetrics = $predictionResult['performance_metrics'] ?? null;
        } elseif ($model->type === 'injury_risk') {
            $injuryRiskProbability = $predictionResult['injury_probability'] ?? null;
            $riskFactors = $predictionResult['risk_factors'] ?? null;
            $recommendedActions = $predictionResult['recommendations'] ?? null;
        }

        return MLPrediction::create([
            'ml_model_id' => $model->id,
            'prediction_type' => $model->type,
            'prediction_context' => $context['prediction_context'] ?? 'general',
            'predictable_type' => get_class($entity),
            'predictable_id' => $entity->id,
            'input_features' => $inputData,
            'processed_features' => $processedData,
            'prediction_output' => $predictionResult,
            'prediction_probabilities' => $predictionResult['probabilities'] ?? null,
            'confidence_score' => $predictionResult['confidence'] ?? null,
            'prediction_value' => $predictionResult['prediction'] ?? null,
            'prediction_category' => $predictionResult['category'] ?? null,
            'performance_metrics' => $performanceMetrics,
            'injury_risk_probability' => $injuryRiskProbability,
            'risk_factors' => $riskFactors,
            'recommended_actions' => $recommendedActions,
            'quality_assessment' => $qualityAssessment,
            'quality_indicators' => $this->calculateQualityIndicators($inputData, $model),
            'prediction_date' => now(),
            'target_date' => $context['target_date'] ?? now()->addDay(),
            'prediction_horizon_days' => $context['prediction_horizon_days'] ?? 1,
            'is_critical' => $context['is_critical'] ?? false,
            'processing_time_ms' => (int)($processingTime * 1000),
            'game_id' => $context['game_id'] ?? null,
            'training_session_id' => $context['training_session_id'] ?? null,
            'season' => $context['season'] ?? null,
            'is_automated' => true,
            'status' => 'generated',
            'created_by_user_id' => auth()->id(),
        ]);
    }

    private function assessPredictionQuality(float $confidence, array $inputData, MLModel $model): string
    {
        // Calculate data completeness
        $completeness = $this->calculateDataCompleteness($inputData, $model);

        if ($confidence >= 0.9 && $completeness >= 0.9) {
            return 'high';
        } elseif ($confidence >= 0.7 && $completeness >= 0.8) {
            return 'medium';
        } elseif ($confidence >= 0.5 && $completeness >= 0.6) {
            return 'low';
        }

        return 'unreliable';
    }

    private function calculateQualityIndicators(array $inputData, MLModel $model): array
    {
        return [
            'data_completeness' => $this->calculateDataCompleteness($inputData, $model),
            'feature_coverage' => $this->calculateFeatureCoverage($inputData, $model),
            'data_recency' => $this->calculateDataRecency($inputData),
            'sample_size_adequacy' => $this->calculateSampleSizeAdequacy($inputData, $model),
        ];
    }

    private function calculateDataCompleteness(array $inputData, MLModel $model): float
    {
        if (!$model->features) return 1.0;

        $requiredFeatures = $model->features;
        $completeFeatures = 0;

        foreach ($requiredFeatures as $feature) {
            if (isset($inputData[$feature]) && $inputData[$feature] !== null && $inputData[$feature] !== '') {
                $completeFeatures++;
            }
        }

        return count($requiredFeatures) > 0 ? $completeFeatures / count($requiredFeatures) : 0;
    }

    private function calculateFeatureCoverage(array $inputData, MLModel $model): float
    {
        if (!$model->features) return 1.0;

        $providedFeatures = array_keys(array_filter($inputData, fn($value) => $value !== null));
        $requiredFeatures = $model->features;

        $intersection = array_intersect($providedFeatures, $requiredFeatures);
        return count($requiredFeatures) > 0 ? count($intersection) / count($requiredFeatures) : 0;
    }

    private function calculateDataRecency(array $inputData): float
    {
        // This would analyze timestamps in the data to determine how recent it is
        // For now, return a default value
        return 0.9;
    }

    private function calculateSampleSizeAdequacy(array $inputData, MLModel $model): float
    {
        // This would check if we have adequate sample size for reliable predictions
        // For now, return a default value based on model's minimum requirements
        return 0.8;
    }

    private function createTempInputFile(array $data): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'ml_input_');
        file_put_contents($tempFile, json_encode($data, JSON_PRETTY_PRINT));
        return $tempFile;
    }

    private function fillMissingValues(array $data, array $fillConfig): array
    {
        foreach ($fillConfig as $field => $fillValue) {
            if (!isset($data[$field]) || $data[$field] === null) {
                $data[$field] = $fillValue;
            }
        }
        return $data;
    }

    private function applyFeatureScaling(array $data, array $scalingConfig): array
    {
        // This would apply scaling transformations
        // Implementation depends on the scaling method (StandardScaler, MinMaxScaler, etc.)
        return $data;
    }

    private function applyFeatureEngineering(array $data, array $engineeringConfig): array
    {
        // This would create derived features based on configuration
        return $data;
    }

    private function calculateGameImportance(Game $game): float
    {
        // Calculate importance based on playoffs, rivalry, standings, etc.
        $importance = 0.5; // Base importance

        if ($game->is_playoff) {
            $importance += 0.3;
        }

        // Add other importance factors...

        return min(1.0, $importance);
    }

    private function isRivalryGame(Team $team1, Team $team2): bool
    {
        // This would check if teams are rivals based on historical data
        return false; // Placeholder
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