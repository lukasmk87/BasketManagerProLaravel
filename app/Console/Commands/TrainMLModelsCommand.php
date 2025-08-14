<?php

namespace App\Console\Commands;

use App\Services\ML\MLTrainingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TrainMLModelsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ml:train
                           {task : The ML task type (shot_prediction, player_performance, game_outcome, injury_risk)}
                           {--model=auto : Model type to use (auto, random_forest, xgboost, etc.)}
                           {--samples=10000 : Maximum number of training samples}
                           {--date-from= : Start date for training data (YYYY-MM-DD)}
                           {--date-to= : End date for training data (YYYY-MM-DD)}
                           {--hyperopt : Enable hyperparameter optimization}
                           {--no-features : Disable automatic feature selection}
                           {--cv-folds=5 : Number of cross-validation folds}
                           {--output=table : Output format (table, json)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Train ML models for basketball analytics';

    /**
     * ML Training Service
     */
    private MLTrainingService $mlService;

    /**
     * Create a new command instance.
     */
    public function __construct(MLTrainingService $mlService)
    {
        parent::__construct();
        $this->mlService = $mlService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $taskType = $this->argument('task');
        
        // Validate task type
        $validTasks = ['shot_prediction', 'player_performance', 'game_outcome', 'injury_risk'];
        if (!in_array($taskType, $validTasks)) {
            $this->error("Invalid task type. Must be one of: " . implode(', ', $validTasks));
            return Command::FAILURE;
        }

        $this->info("🏀 Basketball ML Training gestartet");
        $this->info("Task: " . ucfirst(str_replace('_', ' ', $taskType)));
        
        // Prepare parameters
        $parameters = $this->prepareParameters();
        
        $this->displayTrainingConfiguration($taskType, $parameters);
        
        if (!$this->confirm('Möchten Sie mit dem Training fortfahren?')) {
            $this->info('Training abgebrochen.');
            return Command::SUCCESS;
        }

        // Show progress bar
        $this->info("\n🚀 Starte Training...");
        
        $progressBar = $this->output->createProgressBar();
        $progressBar->setFormat('verbose');
        $progressBar->start();

        try {
            // Start training (in real implementation, this would be async)
            $results = $this->mlService->startAutomatedTraining($taskType, $parameters);
            
            $progressBar->finish();
            $this->newLine(2);

            if ($results['success']) {
                $this->displaySuccessResults($results);
                return Command::SUCCESS;
            } else {
                $this->displayErrorResults($results);
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $progressBar->finish();
            $this->newLine(2);
            
            $this->error("❌ Training fehlgeschlagen: " . $e->getMessage());
            Log::error("ML Training Command failed", [
                'error' => $e->getMessage(),
                'task' => $taskType,
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }

    /**
     * Prepare training parameters from command options
     */
    private function prepareParameters(): array
    {
        $parameters = [
            'model_type' => $this->option('model'),
            'sample_limit' => (int) $this->option('samples'),
            'use_auto_features' => !$this->option('no-features'),
            'use_hyperopt' => $this->option('hyperopt'),
            'cv_folds' => (int) $this->option('cv-folds')
        ];

        if ($this->option('date-from')) {
            $parameters['date_from'] = $this->option('date-from');
        }

        if ($this->option('date-to')) {
            $parameters['date_to'] = $this->option('date-to');
        }

        return $parameters;
    }

    /**
     * Display training configuration
     */
    private function displayTrainingConfiguration(string $taskType, array $parameters): void
    {
        $this->info("\n📋 Training Konfiguration:");
        
        $configTable = [
            ['Parameter', 'Wert'],
            ['Task Type', ucfirst(str_replace('_', ' ', $taskType))],
            ['Model Type', $parameters['model_type']],
            ['Max Samples', number_format($parameters['sample_limit'])],
            ['Auto Features', $parameters['use_auto_features'] ? 'Ja' : 'Nein'],
            ['Hyperparameter Opt.', $parameters['use_hyperopt'] ? 'Ja' : 'Nein'],
            ['CV Folds', $parameters['cv_folds']],
            ['Datum von', $parameters['date_from'] ?? 'Nicht festgelegt'],
            ['Datum bis', $parameters['date_to'] ?? 'Nicht festgelegt']
        ];

        $this->table(['Parameter', 'Wert'], array_slice($configTable, 1));
    }

    /**
     * Display successful training results
     */
    private function displaySuccessResults(array $results): void
    {
        $this->info("✅ Training erfolgreich abgeschlossen!\n");
        
        $experiment = $results['experiment'];
        $model = $results['model'];
        $evaluation = $results['evaluation'];

        // Experiment Info
        $this->info("🧪 Experiment Details:");
        $this->line("   ID: {$experiment->id}");
        $this->line("   Name: {$experiment->name}");
        $this->line("   Status: {$experiment->status}");

        // Model Info
        $this->info("\n🤖 Model Details:");
        $this->line("   ID: {$model->id}");
        $this->line("   Type: {$model->type}");
        $this->line("   Algorithm: {$model->algorithm}");
        
        if ($model->cv_score) {
            $this->line("   CV Score: " . number_format($model->cv_score, 4));
        }

        // Performance Metrics
        if (!empty($model->metrics)) {
            $this->info("\n📊 Performance Metriken:");
            
            $metricsTable = [];
            foreach ($model->metrics as $metric => $value) {
                $metricsTable[] = [
                    ucfirst(str_replace('_', ' ', $metric)),
                    is_numeric($value) ? number_format($value, 4) : $value
                ];
            }
            
            $this->table(['Metrik', 'Wert'], $metricsTable);
        }

        // Feature Importance (Top 10)
        if (!empty($model->feature_importance)) {
            $this->info("\n🎯 Top 10 wichtigste Features:");
            
            $features = collect($model->feature_importance)
                ->sortDesc()
                ->take(10)
                ->toArray();
            
            $featureTable = [];
            foreach ($features as $feature => $importance) {
                $featureTable[] = [
                    $feature,
                    number_format($importance, 4)
                ];
            }
            
            $this->table(['Feature', 'Wichtigkeit'], $featureTable);
        }

        // JSON Output option
        if ($this->option('output') === 'json') {
            $this->line("\n📄 JSON Output:");
            $this->line(json_encode([
                'experiment_id' => $experiment->id,
                'model_id' => $model->id,
                'cv_score' => $model->cv_score,
                'metrics' => $model->metrics
            ], JSON_PRETTY_PRINT));
        }

        $this->info("\n🎉 Model erfolgreich trainiert und gespeichert!");
    }

    /**
     * Display error results
     */
    private function displayErrorResults(array $results): void
    {
        $this->error("❌ Training fehlgeschlagen!");
        $this->error("Fehler: " . $results['error']);
        
        $this->info("\n🔧 Mögliche Lösungsansätze:");
        $this->line("   • Prüfen Sie die Verfügbarkeit von Trainingsdaten");
        $this->line("   • Stellen Sie sicher, dass Python-Dependencies installiert sind");
        $this->line("   • Überprüfen Sie die Log-Dateien für Details");
        $this->line("   • Versuchen Sie es mit anderen Parametern");
    }

    /**
     * Show available ML models
     */
    public function showAvailableModels(): void
    {
        $this->info("🤖 Verfügbare ML Models:");
        
        $models = $this->mlService->getAvailableModels();
        
        if ($models->isEmpty()) {
            $this->warn("Keine trainierten Models verfügbar.");
            return;
        }

        $modelTable = [];
        foreach ($models as $model) {
            $modelTable[] = [
                $model->id,
                $model->name,
                $model->type,
                $model->algorithm,
                $model->cv_score ? number_format($model->cv_score, 4) : 'N/A',
                $model->trained_at->format('Y-m-d H:i:s'),
                $model->status
            ];
        }

        $this->table([
            'ID', 'Name', 'Type', 'Algorithm', 'CV Score', 'Trained At', 'Status'
        ], $modelTable);
    }
}

/**
 * Zusätzlicher Command für Model Management
 */
class ManageMLModelsCommand extends Command
{
    protected $signature = 'ml:models
                           {action : Action to perform (list, show, delete)}
                           {--id= : Model ID for show/delete actions}
                           {--task= : Filter by task type for list action}';

    protected $description = 'Manage trained ML models';

    private MLTrainingService $mlService;

    public function __construct(MLTrainingService $mlService)
    {
        parent::__construct();
        $this->mlService = $mlService;
    }

    public function handle(): int
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'list':
                return $this->listModels();
            
            case 'show':
                return $this->showModel();
                
            case 'delete':
                return $this->deleteModel();
                
            default:
                $this->error("Invalid action. Use: list, show, or delete");
                return Command::FAILURE;
        }
    }

    private function listModels(): int
    {
        $taskType = $this->option('task');
        $models = $this->mlService->getAvailableModels($taskType);

        if ($models->isEmpty()) {
            $this->info("Keine Models gefunden" . ($taskType ? " für Task: $taskType" : ""));
            return Command::SUCCESS;
        }

        $this->info("🤖 Verfügbare ML Models" . ($taskType ? " ($taskType)" : "") . ":\n");

        $modelTable = [];
        foreach ($models as $model) {
            $modelTable[] = [
                $model->id,
                $model->name,
                $model->experiment->task_type ?? 'N/A',
                $model->algorithm,
                $model->cv_score ? number_format($model->cv_score, 4) : 'N/A',
                $model->trained_at->format('Y-m-d H:i:s'),
                $model->status
            ];
        }

        $this->table([
            'ID', 'Name', 'Task Type', 'Algorithm', 'CV Score', 'Trained At', 'Status'
        ], $modelTable);

        return Command::SUCCESS;
    }

    private function showModel(): int
    {
        $modelId = $this->option('id');
        
        if (!$modelId) {
            $this->error("Model ID is required for show action. Use --id=<model_id>");
            return Command::FAILURE;
        }

        $model = $this->mlService->loadModelForPrediction($modelId);
        
        if (!$model) {
            $this->error("Model with ID $modelId not found");
            return Command::FAILURE;
        }

        $this->info("🤖 Model Details (ID: $modelId):\n");
        
        $details = [
            ['Attribute', 'Value'],
            ['Name', $model->name],
            ['Type', $model->type],
            ['Algorithm', $model->algorithm],
            ['Status', $model->status],
            ['CV Score', $model->cv_score ? number_format($model->cv_score, 4) : 'N/A'],
            ['Trained At', $model->trained_at->format('Y-m-d H:i:s')],
            ['Version', $model->version],
        ];

        $this->table(['Attribute', 'Value'], array_slice($details, 1));

        // Show metrics if available
        if (!empty($model->metrics)) {
            $this->info("\n📊 Performance Metrics:");
            $metricsTable = [];
            foreach ($model->metrics as $metric => $value) {
                $metricsTable[] = [
                    ucfirst(str_replace('_', ' ', $metric)),
                    is_numeric($value) ? number_format($value, 4) : $value
                ];
            }
            $this->table(['Metric', 'Value'], $metricsTable);
        }

        return Command::SUCCESS;
    }

    private function deleteModel(): int
    {
        $modelId = $this->option('id');
        
        if (!$modelId) {
            $this->error("Model ID is required for delete action. Use --id=<model_id>");
            return Command::FAILURE;
        }

        $model = $this->mlService->loadModelForPrediction($modelId);
        
        if (!$model) {
            $this->error("Model with ID $modelId not found");
            return Command::FAILURE;
        }

        $this->warn("You are about to delete model: {$model->name} (ID: $modelId)");
        
        if (!$this->confirm('Are you sure you want to delete this model?')) {
            $this->info('Deletion cancelled.');
            return Command::SUCCESS;
        }

        try {
            $model->delete();
            $this->info("✅ Model deleted successfully");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Failed to delete model: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}