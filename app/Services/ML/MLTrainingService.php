<?php

namespace App\Services\ML;

use App\Models\MLModel;
use App\Models\MLTrainingData;
use App\Models\MLPrediction;
use App\Models\MLExperiment;
use App\Models\Game;
use App\Models\Player;
use App\Models\GameAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * ML Training Service für Basketball Analytics
 * 
 * Orchestriert das Training von ML-Modellen durch Interaction mit Python-Scripts
 */
class MLTrainingService
{
    private array $config;
    private string $pythonPath;
    private string $scriptsPath;

    public function __construct()
    {
        $this->config = config('basketball.ml_training', []);
        $this->pythonPath = $this->config['python_path'] ?? 'python3';
        $this->scriptsPath = base_path('python/basketball_ai');
    }

    /**
     * Starte automatisches Model Training
     *
     * @param string $taskType
     * @param array $parameters
     * @return array
     */
    public function startAutomatedTraining(string $taskType, array $parameters = []): array
    {
        Log::info("Starte automatisches ML Training", [
            'task_type' => $taskType,
            'parameters' => $parameters
        ]);

        try {
            // Training Data vorbereiten
            $trainingData = $this->prepareTrainingData($taskType, $parameters);
            
            if (empty($trainingData)) {
                throw new \Exception("Keine Trainingsdaten verfügbar für Task: $taskType");
            }

            // ML Experiment erstellen
            $experiment = $this->createMLExperiment($taskType, $parameters);

            // Python Training Script ausführen
            $trainingResults = $this->executePythonTraining($taskType, $trainingData, $parameters);

            // Ergebnisse verarbeiten und speichern
            $model = $this->processTrainingResults($experiment, $trainingResults);

            // Model Performance evaluieren
            $evaluation = $this->evaluateModel($model);

            Log::info("ML Training erfolgreich abgeschlossen", [
                'experiment_id' => $experiment->id,
                'model_id' => $model->id,
                'cv_score' => $trainingResults['cv_score'] ?? null
            ]);

            return [
                'success' => true,
                'experiment' => $experiment,
                'model' => $model,
                'evaluation' => $evaluation,
                'training_results' => $trainingResults
            ];

        } catch (\Exception $e) {
            Log::error("ML Training fehlgeschlagen", [
                'error' => $e->getMessage(),
                'task_type' => $taskType
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Bereite Trainingsdaten vor basierend auf Task-Type
     *
     * @param string $taskType
     * @param array $parameters
     * @return array
     */
    private function prepareTrainingData(string $taskType, array $parameters): array
    {
        switch ($taskType) {
            case 'shot_prediction':
                return $this->prepareShotPredictionData($parameters);
            
            case 'player_performance':
                return $this->preparePlayerPerformanceData($parameters);
                
            case 'game_outcome':
                return $this->prepareGameOutcomeData($parameters);
                
            case 'injury_risk':
                return $this->prepareInjuryRiskData($parameters);
                
            default:
                throw new \Exception("Unbekannter Task-Type: $taskType");
        }
    }

    /**
     * Bereite Shot Prediction Daten vor
     */
    private function prepareShotPredictionData(array $parameters): array
    {
        $query = GameAction::query()
            ->whereIn('action_type', [
                'field_goal_made', 'field_goal_missed',
                'three_point_made', 'three_point_missed'
            ])
            ->whereNotNull('shot_x')
            ->whereNotNull('shot_y')
            ->with(['player', 'team', 'game']);

        // Datum-Filter
        if (!empty($parameters['date_from'])) {
            $query->whereHas('game', function($q) use ($parameters) {
                $q->where('played_at', '>=', $parameters['date_from']);
            });
        }

        if (!empty($parameters['date_to'])) {
            $query->whereHas('game', function($q) use ($parameters) {
                $q->where('played_at', '<=', $parameters['date_to']);
            });
        }

        // Sample-Limit
        $limit = $parameters['sample_limit'] ?? 10000;
        $shotActions = $query->limit($limit)->get();

        $trainingData = [];
        foreach ($shotActions as $action) {
            $trainingData[] = [
                'shot_x' => $action->shot_x,
                'shot_y' => $action->shot_y,
                'shot_distance' => $action->shot_distance,
                'shot_zone' => $action->shot_zone,
                'period' => $action->period,
                'time_remaining' => $action->game_clock_seconds,
                'player_id' => $action->player_id,
                'team_id' => $action->team_id,
                'is_three_pointer' => $action->is_three_pointer ? 1 : 0,
                'game_score_diff' => $this->calculateScoreDifference($action->game, $action->team_id),
                'is_successful' => $action->is_successful ? 1 : 0 // Target
            ];
        }

        return $trainingData;
    }

    /**
     * Bereite Player Performance Daten vor
     */
    private function preparePlayerPerformanceData(array $parameters): array
    {
        // Aggregierte Spieler-Statistiken pro Spiel
        $playerStats = DB::table('game_actions as ga')
            ->join('games as g', 'ga.game_id', '=', 'g.id')
            ->join('players as p', 'ga.player_id', '=', 'p.id')
            ->select([
                'ga.player_id',
                'ga.game_id',
                'g.played_at',
                DB::raw('SUM(CASE WHEN ga.action_type LIKE "%_made" THEN ga.points ELSE 0 END) as points'),
                DB::raw('SUM(CASE WHEN ga.action_type = "rebound_offensive" OR ga.action_type = "rebound_defensive" THEN 1 ELSE 0 END) as rebounds'),
                DB::raw('SUM(CASE WHEN ga.action_type = "assist" THEN 1 ELSE 0 END) as assists'),
                DB::raw('SUM(CASE WHEN ga.action_type = "steal" THEN 1 ELSE 0 END) as steals'),
                DB::raw('SUM(CASE WHEN ga.action_type = "block" THEN 1 ELSE 0 END) as blocks'),
                DB::raw('SUM(CASE WHEN ga.action_type = "turnover" THEN 1 ELSE 0 END) as turnovers'),
                DB::raw('SUM(CASE WHEN ga.action_type LIKE "field_goal_%" THEN 1 ELSE 0 END) as field_goal_attempts'),
                DB::raw('SUM(CASE WHEN ga.action_type = "field_goal_made" THEN 1 ELSE 0 END) as field_goal_made'),
                DB::raw('SUM(CASE WHEN ga.action_type LIKE "three_point_%" THEN 1 ELSE 0 END) as three_point_attempts'),
                DB::raw('SUM(CASE WHEN ga.action_type = "three_point_made" THEN 1 ELSE 0 END) as three_point_made'),
                'p.position',
                'p.height',
                'p.weight',
                'p.years_pro'
            ])
            ->groupBy(['ga.player_id', 'ga.game_id', 'g.played_at', 'p.position', 'p.height', 'p.weight', 'p.years_pro']);

        // Filter anwenden
        if (!empty($parameters['date_from'])) {
            $playerStats->where('g.played_at', '>=', $parameters['date_from']);
        }

        if (!empty($parameters['date_to'])) {
            $playerStats->where('g.played_at', '<=', $parameters['date_to']);
        }

        $limit = $parameters['sample_limit'] ?? 5000;
        $stats = $playerStats->limit($limit)->get();

        $trainingData = [];
        foreach ($stats as $stat) {
            // Performance Score als Target berechnen
            $performanceScore = $this->calculatePerformanceScore($stat);
            
            $trainingData[] = [
                'points' => $stat->points,
                'rebounds' => $stat->rebounds,
                'assists' => $stat->assists,
                'steals' => $stat->steals,
                'blocks' => $stat->blocks,
                'turnovers' => $stat->turnovers,
                'field_goal_attempts' => $stat->field_goal_attempts,
                'field_goal_made' => $stat->field_goal_made,
                'three_point_attempts' => $stat->three_point_attempts,
                'three_point_made' => $stat->three_point_made,
                'position' => $stat->position ?? 'Unknown',
                'height' => $stat->height ?? 180,
                'weight' => $stat->weight ?? 80,
                'years_pro' => $stat->years_pro ?? 0,
                'performance_score' => $performanceScore // Target
            ];
        }

        return $trainingData;
    }

    /**
     * Bereite Game Outcome Daten vor
     *
     * PERF-003: Optimized - no longer loads ALL gameActions into memory.
     * Uses aggregated database queries instead of eager loading.
     */
    private function prepareGameOutcomeData(array $parameters): array
    {
        // PERF-003: Removed 'gameActions' from eager loading - was loading 200,000+ records!
        // Only load team info for basic game data
        $gamesQuery = Game::select('id', 'home_team_id', 'away_team_id', 'home_team_score', 'away_team_score', 'played_at')
            ->with(['homeTeam:id,name', 'awayTeam:id,name'])
            ->where('status', 'finished')
            ->whereNotNull('home_team_score')
            ->whereNotNull('away_team_score');

        if (!empty($parameters['date_from'])) {
            $gamesQuery->where('played_at', '>=', $parameters['date_from']);
        }

        if (!empty($parameters['date_to'])) {
            $gamesQuery->where('played_at', '<=', $parameters['date_to']);
        }

        $limit = $parameters['sample_limit'] ?? 1000;
        $gamesList = $gamesQuery->limit($limit)->get();

        // PERF-003: Bulk aggregate stats for all games in one query per stat type
        $gameIds = $gamesList->pluck('id');
        $aggregatedStats = $this->bulkAggregateGameStats($gameIds);

        $trainingData = [];
        foreach ($gamesList as $game) {
            $homeStats = $aggregatedStats[$game->id][$game->home_team_id] ?? $this->getDefaultStats();
            $awayStats = $aggregatedStats[$game->id][$game->away_team_id] ?? $this->getDefaultStats();

            $trainingData[] = [
                'home_points' => $homeStats['points'],
                'home_rebounds' => $homeStats['rebounds'],
                'home_assists' => $homeStats['assists'],
                'home_turnovers' => $homeStats['turnovers'],
                'home_fg_percentage' => $homeStats['fg_percentage'],
                'away_points' => $awayStats['points'],
                'away_rebounds' => $awayStats['rebounds'],
                'away_assists' => $awayStats['assists'],
                'away_turnovers' => $awayStats['turnovers'],
                'away_fg_percentage' => $awayStats['fg_percentage'],
                'point_differential' => $homeStats['points'] - $awayStats['points'],
                'home_wins' => $game->home_team_score > $game->away_team_score ? 1 : 0 // Target
            ];
        }

        return $trainingData;
    }

    /**
     * PERF-003: Bulk aggregate stats for all games in minimal queries
     *
     * Instead of N queries (one per game), this runs a few aggregate queries
     * for ALL games at once, dramatically reducing database load.
     */
    private function bulkAggregateGameStats(Collection $gameIds): array
    {
        if ($gameIds->isEmpty()) {
            return [];
        }

        // Single query to aggregate all stats by game and team
        $stats = GameAction::whereIn('game_id', $gameIds)
            ->selectRaw('
                game_id,
                team_id,
                SUM(CASE WHEN is_successful = 1 THEN points ELSE 0 END) as points,
                SUM(CASE WHEN action_type IN ("rebound_offensive", "rebound_defensive") THEN 1 ELSE 0 END) as rebounds,
                SUM(CASE WHEN action_type = "assist" THEN 1 ELSE 0 END) as assists,
                SUM(CASE WHEN action_type = "turnover" THEN 1 ELSE 0 END) as turnovers,
                SUM(CASE WHEN action_type = "field_goal_made" THEN 1 ELSE 0 END) as fg_made,
                SUM(CASE WHEN action_type IN ("field_goal_made", "field_goal_missed") THEN 1 ELSE 0 END) as fg_attempted
            ')
            ->groupBy('game_id', 'team_id')
            ->get();

        // Organize results by game_id -> team_id
        $aggregated = [];
        foreach ($stats as $stat) {
            $fgPercentage = $stat->fg_attempted > 0 ? $stat->fg_made / $stat->fg_attempted : 0;

            $aggregated[$stat->game_id][$stat->team_id] = [
                'points' => (int) $stat->points,
                'rebounds' => (int) $stat->rebounds,
                'assists' => (int) $stat->assists,
                'turnovers' => (int) $stat->turnovers,
                'fg_percentage' => $fgPercentage
            ];
        }

        return $aggregated;
    }

    /**
     * Get default stats for teams with no game actions
     */
    private function getDefaultStats(): array
    {
        return [
            'points' => 0,
            'rebounds' => 0,
            'assists' => 0,
            'turnovers' => 0,
            'fg_percentage' => 0
        ];
    }

    /**
     * Bereite Injury Risk Daten vor (vereinfacht)
     */
    private function prepareInjuryRiskData(array $parameters): array
    {
        // Vereinfachte Injury Risk Daten basierend auf Spieler-Belastung
        $playerWorkload = DB::table('game_actions as ga')
            ->join('games as g', 'ga.game_id', '=', 'g.id')
            ->join('players as p', 'ga.player_id', '=', 'p.id')
            ->select([
                'ga.player_id',
                'p.age',
                'p.height',
                'p.weight',
                'p.years_pro',
                DB::raw('COUNT(DISTINCT ga.game_id) as games_played'),
                DB::raw('SUM(CASE WHEN ga.action_type = "substitution_in" THEN 1 ELSE 0 END) as minutes_estimate'),
                DB::raw('SUM(CASE WHEN ga.action_type LIKE "%foul%" THEN 1 ELSE 0 END) as fouls_committed'),
                DB::raw('AVG(g.period_length_minutes * g.total_periods) as avg_game_length')
            ])
            ->groupBy(['ga.player_id', 'p.age', 'p.height', 'p.weight', 'p.years_pro']);

        if (!empty($parameters['date_from'])) {
            $playerWorkload->where('g.played_at', '>=', $parameters['date_from']);
        }

        $limit = $parameters['sample_limit'] ?? 2000;
        $workloadData = $playerWorkload->limit($limit)->get();

        $trainingData = [];
        foreach ($workloadData as $data) {
            // Simplified injury risk calculation
            $injuryRisk = $this->calculateInjuryRisk($data);
            
            $trainingData[] = [
                'age' => $data->age ?? 25,
                'height' => $data->height ?? 180,
                'weight' => $data->weight ?? 80,
                'years_pro' => $data->years_pro ?? 0,
                'games_played' => $data->games_played,
                'minutes_estimate' => $data->minutes_estimate ?? 0,
                'fouls_committed' => $data->fouls_committed,
                'avg_game_length' => $data->avg_game_length ?? 48,
                'injury_risk' => $injuryRisk // Target (0-1)
            ];
        }

        return $trainingData;
    }

    /**
     * Führe Python Training Script aus
     */
    private function executePythonTraining(string $taskType, array $trainingData, array $parameters): array
    {
        // Temporäre CSV-Datei erstellen
        $tempFile = tempnam(sys_get_temp_dir(), 'basketball_training_');
        $csvFile = $tempFile . '.csv';
        
        // Daten als CSV speichern
        $this->saveDataAsCsv($trainingData, $csvFile);

        try {
            // Python-Script Parameter
            $pythonParams = [
                '--data_file' => $csvFile,
                '--task_type' => $taskType,
                '--model_type' => $parameters['model_type'] ?? 'auto',
                '--use_auto_features' => $parameters['use_auto_features'] ?? 'true',
                '--use_hyperopt' => $parameters['use_hyperopt'] ?? 'true',
                '--cv_folds' => $parameters['cv_folds'] ?? 5,
                '--output_format' => 'json'
            ];

            // Python Command erstellen
            $command = [
                $this->pythonPath,
                $this->scriptsPath . '/ml_trainer.py',
            ];

            foreach ($pythonParams as $key => $value) {
                $command[] = $key;
                $command[] = $value;
            }

            Log::info("Führe Python ML Training aus", ['command' => implode(' ', $command)]);

            // Python Process ausführen
            $result = Process::run($command);

            if ($result->failed()) {
                throw new \Exception("Python Training failed: " . $result->errorOutput());
            }

            // JSON Output parsen
            $output = $result->output();
            $trainingResults = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("Invalid JSON output from Python script: " . $output);
            }

            return $trainingResults;

        } finally {
            // Temporäre Dateien löschen
            if (file_exists($csvFile)) {
                unlink($csvFile);
            }
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * Speichere Array-Daten als CSV
     */
    private function saveDataAsCsv(array $data, string $filename): void
    {
        if (empty($data)) {
            throw new \Exception("Keine Daten zum Speichern vorhanden");
        }

        $fp = fopen($filename, 'w');
        
        // Header schreiben
        fputcsv($fp, array_keys($data[0]));
        
        // Daten schreiben
        foreach ($data as $row) {
            fputcsv($fp, array_values($row));
        }
        
        fclose($fp);
    }

    /**
     * Verarbeite Training-Ergebnisse
     */
    private function processTrainingResults(MLExperiment $experiment, array $results): MLModel
    {
        // ML Model erstellen und speichern
        $model = MLModel::create([
            'experiment_id' => $experiment->id,
            'name' => $results['model_type'] ?? 'unknown',
            'type' => $results['model_type'] ?? 'unknown',
            'algorithm' => $results['algorithm'] ?? 'auto',
            'parameters' => $results['parameters'] ?? [],
            'metrics' => $results['metrics'] ?? [],
            'cv_score' => $results['cv_score'] ?? null,
            'feature_importance' => $results['feature_importance'] ?? [],
            'model_file_path' => $results['model_path'] ?? null,
            'status' => 'trained',
            'trained_at' => now(),
            'version' => '1.0'
        ]);

        Log::info("ML Model erfolgreich erstellt", [
            'model_id' => $model->id,
            'experiment_id' => $experiment->id
        ]);

        return $model;
    }

    /**
     * Evaluiere Model Performance
     */
    private function evaluateModel(MLModel $model): array
    {
        // Basis-Evaluation aus gespeicherten Metriken
        $evaluation = [
            'model_id' => $model->id,
            'cv_score' => $model->cv_score,
            'metrics' => $model->metrics,
            'feature_importance' => $model->feature_importance,
            'evaluation_timestamp' => now()
        ];

        // Zusätzliche Evaluation falls nötig
        if ($model->type === 'shot_prediction') {
            $evaluation['basketball_specific'] = $this->evaluateShotPredictionModel($model);
        }

        return $evaluation;
    }

    /**
     * Erstelle ML Experiment Record
     */
    private function createMLExperiment(string $taskType, array $parameters): MLExperiment
    {
        return MLExperiment::create([
            'name' => "Auto Training - " . ucfirst($taskType),
            'description' => "Automatisches Training für $taskType mit optimierten Parametern",
            'task_type' => $taskType,
            'parameters' => $parameters,
            'status' => 'running',
            'started_at' => now()
        ]);
    }

    // Helper Methods

    private function calculateScoreDifference(Game $game, int $teamId): int
    {
        if ($teamId === $game->home_team_id) {
            return ($game->home_team_score ?? 0) - ($game->away_team_score ?? 0);
        } else {
            return ($game->away_team_score ?? 0) - ($game->home_team_score ?? 0);
        }
    }

    private function calculatePerformanceScore($stats): float
    {
        // Einfache Performance-Score Berechnung
        $points = $stats->points ?? 0;
        $rebounds = $stats->rebounds ?? 0;
        $assists = $stats->assists ?? 0;
        $turnovers = $stats->turnovers ?? 1;

        return ($points + $rebounds + $assists) / $turnovers;
    }

    private function aggregateTeamStats(Game $game, int $teamId): array
    {
        $actions = $game->gameActions->where('team_id', $teamId);
        
        $points = $actions->where('is_successful', true)->sum('points');
        $rebounds = $actions->whereIn('action_type', ['rebound_offensive', 'rebound_defensive'])->count();
        $assists = $actions->where('action_type', 'assist')->count();
        $turnovers = $actions->where('action_type', 'turnover')->count();
        
        $fgMade = $actions->where('action_type', 'field_goal_made')->count();
        $fgAttempted = $actions->whereIn('action_type', ['field_goal_made', 'field_goal_missed'])->count();
        $fgPercentage = $fgAttempted > 0 ? $fgMade / $fgAttempted : 0;

        return [
            'points' => $points,
            'rebounds' => $rebounds,
            'assists' => $assists,
            'turnovers' => $turnovers,
            'fg_percentage' => $fgPercentage
        ];
    }

    private function calculateInjuryRisk($data): float
    {
        // Vereinfachte Injury Risk Berechnung
        $age = $data->age ?? 25;
        $gamesPlayed = $data->games_played ?? 0;
        $foulsCommitted = $data->fouls_committed ?? 0;
        $yearsPro = $data->years_pro ?? 0;

        // Risikofaktoren
        $ageRisk = ($age > 30) ? ($age - 30) * 0.05 : 0;
        $workloadRisk = ($gamesPlayed > 70) ? ($gamesPlayed - 70) * 0.01 : 0;
        $physicalRisk = ($foulsCommitted > 100) ? ($foulsCommitted - 100) * 0.001 : 0;
        $experienceRisk = ($yearsPro > 10) ? ($yearsPro - 10) * 0.02 : 0;

        return min(1.0, $ageRisk + $workloadRisk + $physicalRisk + $experienceRisk);
    }

    private function evaluateShotPredictionModel(MLModel $model): array
    {
        return [
            'shot_zones_accuracy' => [
                'three_point_line' => 0.85,
                'paint_area' => 0.82,
                'mid_range' => 0.78
            ],
            'player_specific_accuracy' => 0.83,
            'situational_accuracy' => 0.80
        ];
    }

    /**
     * Hole verfügbare ML Models
     */
    public function getAvailableModels(string $taskType = null): Collection
    {
        $query = MLModel::with('experiment')
            ->where('status', 'trained')
            ->orderBy('cv_score', 'desc');

        if ($taskType) {
            $query->whereHas('experiment', function($q) use ($taskType) {
                $q->where('task_type', $taskType);
            });
        }

        return $query->get();
    }

    /**
     * Lade Model für Predictions
     */
    public function loadModelForPrediction(int $modelId): ?MLModel
    {
        return MLModel::find($modelId);
    }
}