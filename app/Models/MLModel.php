<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class MLModel extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'name',
        'type',
        'algorithm',
        'description',
        'version',
        'parameters',
        'features',
        'target_variables',
        'preprocessing_config',
        'training_samples',
        'validation_samples',
        'test_samples',
        'training_metrics',
        'validation_metrics',
        'test_metrics',
        'accuracy',
        'precision',
        'recall',
        'f1_score',
        'auc_score',
        'rmse',
        'mae',
        'file_path',
        'file_size',
        'storage_format',
        'dependencies',
        'status',
        'is_active',
        'auto_retrain',
        'last_trained_at',
        'last_prediction_at',
        'deployed_at',
        'feature_importance',
        'model_interpretation',
        'interpretability_notes',
        'prediction_count',
        'successful_predictions',
        'failed_predictions',
        'average_prediction_time',
        'drift_score',
        'applicable_positions',
        'applicable_scenarios',
        'min_games_required',
        'optimal_sample_size',
        'data_requirements',
        'min_data_completeness',
        'min_historical_days',
        'training_log',
        'validation_results',
        'bias_assessment',
        'ethical_considerations',
        'created_by_user_id',
        'updated_by_user_id',
        'parent_model_id'
    ];

    protected $casts = [
        'parameters' => 'array',
        'features' => 'array',
        'target_variables' => 'array',
        'preprocessing_config' => 'array',
        'training_metrics' => 'array',
        'validation_metrics' => 'array',
        'test_metrics' => 'array',
        'accuracy' => 'decimal:4',
        'precision' => 'decimal:4',
        'recall' => 'decimal:4',
        'f1_score' => 'decimal:4',
        'auc_score' => 'decimal:4',
        'rmse' => 'decimal:6',
        'mae' => 'decimal:6',
        'file_size' => 'integer',
        'dependencies' => 'array',
        'is_active' => 'boolean',
        'auto_retrain' => 'boolean',
        'last_trained_at' => 'datetime',
        'last_prediction_at' => 'datetime',
        'deployed_at' => 'datetime',
        'feature_importance' => 'array',
        'model_interpretation' => 'array',
        'prediction_count' => 'integer',
        'successful_predictions' => 'integer',
        'failed_predictions' => 'integer',
        'average_prediction_time' => 'decimal:3',
        'drift_score' => 'decimal:4',
        'applicable_positions' => 'array',
        'applicable_scenarios' => 'array',
        'min_games_required' => 'integer',
        'optimal_sample_size' => 'integer',
        'data_requirements' => 'array',
        'min_data_completeness' => 'decimal:4',
        'min_historical_days' => 'integer',
        'training_log' => 'array',
        'validation_results' => 'array',
        'created_by_user_id' => 'integer',
        'updated_by_user_id' => 'integer',
        'parent_model_id' => 'integer'
    ];

    protected $dates = [
        'last_trained_at',
        'last_prediction_at', 
        'deployed_at'
    ];

    // Model status constants
    public const STATUS_TRAINING = 'training';
    public const STATUS_TRAINED = 'trained';
    public const STATUS_VALIDATING = 'validating';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_DEPLOYED = 'deployed';
    public const STATUS_DEPRECATED = 'deprecated';
    public const STATUS_FAILED = 'failed';
    public const STATUS_ARCHIVED = 'archived';

    // Model types
    public const TYPE_PLAYER_PERFORMANCE = 'player_performance';
    public const TYPE_INJURY_RISK = 'injury_risk';
    public const TYPE_GAME_OUTCOME = 'game_outcome';
    public const TYPE_SHOT_SUCCESS = 'shot_success';
    public const TYPE_TEAM_CHEMISTRY = 'team_chemistry';
    public const TYPE_TACTICAL_ANALYSIS = 'tactical_analysis';

    // Basketball algorithms
    public const ALGORITHM_RANDOM_FOREST = 'random_forest';
    public const ALGORITHM_GRADIENT_BOOST = 'gradient_boost';
    public const ALGORITHM_LINEAR_REGRESSION = 'linear_regression';
    public const ALGORITHM_LOGISTIC_REGRESSION = 'logistic_regression';
    public const ALGORITHM_NEURAL_NETWORK = 'neural_network';
    public const ALGORITHM_SVM = 'svm';
    public const ALGORITHM_XGBOOST = 'xgboost';

    /**
     * Relationships
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function parentModel(): BelongsTo
    {
        return $this->belongsTo(MLModel::class, 'parent_model_id');
    }

    public function childModels(): HasMany
    {
        return $this->hasMany(MLModel::class, 'parent_model_id');
    }

    public function predictions(): HasMany
    {
        return $this->hasMany(MLPrediction::class);
    }

    public function experiments(): HasMany
    {
        return $this->hasMany(MLExperiment::class, 'best_model_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDeployed($query)
    {
        return $query->where('status', self::STATUS_DEPLOYED);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByAlgorithm($query, string $algorithm)
    {
        return $query->where('algorithm', $algorithm);
    }

    public function scopeHighPerformance($query, float $minAccuracy = 0.8)
    {
        return $query->where('accuracy', '>=', $minAccuracy);
    }

    public function scopeRecentlyTrained($query, int $days = 30)
    {
        return $query->where('last_trained_at', '>=', now()->subDays($days));
    }

    /**
     * Business Logic Methods
     */
    
    /**
     * Check if model needs retraining based on drift and performance
     */
    public function needsRetraining(): bool
    {
        if (!$this->is_active || $this->status !== self::STATUS_DEPLOYED) {
            return false;
        }

        // Check data drift
        if ($this->drift_score && $this->drift_score > 0.1) {
            return true;
        }

        // Check if auto-retrain is enabled and enough time has passed
        if ($this->auto_retrain && $this->last_trained_at) {
            $daysSinceTraining = $this->last_trained_at->diffInDays(now());
            if ($daysSinceTraining > 90) { // Retrain every 3 months
                return true;
            }
        }

        // Check prediction accuracy degradation
        $recentPredictions = $this->predictions()
            ->where('created_at', '>=', now()->subDays(7))
            ->whereNotNull('actual_outcome')
            ->get();

        if ($recentPredictions->count() > 10) {
            $recentAccuracy = $recentPredictions->where('prediction_correct', true)->count() 
                            / $recentPredictions->count();
            
            if ($this->accuracy && $recentAccuracy < ($this->accuracy - 0.1)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get model performance summary
     */
    public function getPerformanceSummary(): array
    {
        $totalPredictions = $this->prediction_count;
        $successRate = $totalPredictions > 0 
            ? ($this->successful_predictions / $totalPredictions) 
            : 0;

        return [
            'accuracy' => $this->accuracy,
            'precision' => $this->precision,
            'recall' => $this->recall,
            'f1_score' => $this->f1_score,
            'total_predictions' => $totalPredictions,
            'success_rate' => round($successRate, 4),
            'average_prediction_time' => $this->average_prediction_time,
            'drift_score' => $this->drift_score,
            'days_since_training' => $this->last_trained_at 
                ? $this->last_trained_at->diffInDays(now()) 
                : null,
            'needs_retraining' => $this->needsRetraining()
        ];
    }

    /**
     * Update prediction statistics
     */
    public function updatePredictionStats(bool $successful, float $predictionTimeMs): void
    {
        $this->increment('prediction_count');
        
        if ($successful) {
            $this->increment('successful_predictions');
        } else {
            $this->increment('failed_predictions');
        }

        // Update average prediction time (running average)
        if ($this->average_prediction_time) {
            $this->average_prediction_time = (
                ($this->average_prediction_time * ($this->prediction_count - 1)) + $predictionTimeMs
            ) / $this->prediction_count;
        } else {
            $this->average_prediction_time = $predictionTimeMs;
        }

        $this->last_prediction_at = now();
        $this->save();
    }

    /**
     * Check if model is suitable for given context
     */
    public function isSuitableForContext(array $context): bool
    {
        // Check applicable positions
        if (isset($context['player_position']) && $this->applicable_positions) {
            if (!in_array($context['player_position'], $this->applicable_positions)) {
                return false;
            }
        }

        // Check applicable scenarios
        if (isset($context['scenario']) && $this->applicable_scenarios) {
            if (!in_array($context['scenario'], $this->applicable_scenarios)) {
                return false;
            }
        }

        // Check minimum games requirement
        if (isset($context['games_played']) && $this->min_games_required) {
            if ($context['games_played'] < $this->min_games_required) {
                return false;
            }
        }

        // Check data completeness
        if (isset($context['data_completeness']) && $this->min_data_completeness) {
            if ($context['data_completeness'] < $this->min_data_completeness) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get feature importance in readable format
     */
    public function getReadableFeatureImportance(): array
    {
        if (!$this->feature_importance || !$this->features) {
            return [];
        }

        $importance = [];
        foreach ($this->feature_importance as $feature => $score) {
            $importance[] = [
                'feature' => $feature,
                'importance' => round($score, 4),
                'description' => $this->getFeatureDescription($feature)
            ];
        }

        // Sort by importance descending
        usort($importance, function($a, $b) {
            return $b['importance'] <=> $a['importance'];
        });

        return $importance;
    }

    /**
     * Get human-readable feature description
     */
    private function getFeatureDescription(string $feature): string
    {
        $descriptions = [
            'points_per_game' => 'Punkte pro Spiel',
            'assists_per_game' => 'Assists pro Spiel',
            'rebounds_per_game' => 'Rebounds pro Spiel',
            'field_goal_percentage' => 'Feldwurf-Quote',
            'three_point_percentage' => 'Dreier-Quote',
            'free_throw_percentage' => 'Freiwurf-Quote',
            'minutes_played' => 'Spielminuten',
            'turnovers' => 'Ballverluste',
            'steals' => 'Ballgewinne',
            'blocks' => 'Blocks',
            'plus_minus' => 'Plus/Minus',
            'usage_rate' => 'Usage Rate',
            'true_shooting_percentage' => 'True Shooting %',
            'player_efficiency_rating' => 'Player Efficiency Rating',
            'win_shares' => 'Win Shares',
            'box_plus_minus' => 'Box Plus/Minus'
        ];

        return $descriptions[$feature] ?? ucfirst(str_replace('_', ' ', $feature));
    }

    /**
     * Export model configuration for Python training
     */
    public function exportForTraining(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'algorithm' => $this->algorithm,
            'parameters' => $this->parameters,
            'features' => $this->features,
            'target_variables' => $this->target_variables,
            'preprocessing_config' => $this->preprocessing_config,
            'data_requirements' => $this->data_requirements,
            'min_data_completeness' => $this->min_data_completeness,
            'min_historical_days' => $this->min_historical_days
        ];
    }

    /**
     * Activity Log Configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 'type', 'algorithm', 'version', 'status', 
                'is_active', 'accuracy', 'precision', 'recall', 'f1_score'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Model validation rules
     */
    public static function validationRules(bool $isUpdate = false): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|in:' . implode(',', [
                self::TYPE_PLAYER_PERFORMANCE,
                self::TYPE_INJURY_RISK,
                self::TYPE_GAME_OUTCOME,
                self::TYPE_SHOT_SUCCESS,
                self::TYPE_TEAM_CHEMISTRY,
                self::TYPE_TACTICAL_ANALYSIS
            ]),
            'algorithm' => 'required|in:' . implode(',', [
                self::ALGORITHM_RANDOM_FOREST,
                self::ALGORITHM_GRADIENT_BOOST,
                self::ALGORITHM_LINEAR_REGRESSION,
                self::ALGORITHM_LOGISTIC_REGRESSION,
                self::ALGORITHM_NEURAL_NETWORK,
                self::ALGORITHM_SVM,
                self::ALGORITHM_XGBOOST
            ]),
            'description' => 'nullable|string|max:1000',
            'version' => 'nullable|string|max:20',
            'parameters' => 'nullable|array',
            'features' => 'nullable|array',
            'target_variables' => 'nullable|array',
            'min_data_completeness' => 'nullable|numeric|between:0,1',
            'min_historical_days' => 'nullable|integer|min:1|max:365'
        ];
    }
}