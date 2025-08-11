<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class MLExperiment extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'name',
        'description',
        'experiment_type',
        'objective',
        'hypothesis',
        'experimental_design',
        'success_criteria',
        'baseline_metrics',
        'target_sample_size',
        'statistical_power',
        'significance_level',
        'model_configurations',
        'hyperparameter_space',
        'feature_sets',
        'data_splits',
        'status',
        'started_at',
        'completed_at',
        'duration_minutes',
        'iterations_completed',
        'total_iterations',
        'computational_resources',
        'total_compute_hours',
        'estimated_cost',
        'infrastructure_details',
        'results_summary',
        'model_performances',
        'statistical_tests',
        'confidence_intervals',
        'conclusions',
        'best_model_id',
        'best_model_metrics',
        'winning_configuration',
        'improvement_over_baseline',
        'statistically_significant',
        'basketball_domain',
        'applicable_positions',
        'game_contexts',
        'season_focus',
        'feature_importance_analysis',
        'data_quality_impact',
        'sample_size_analysis',
        'bias_analysis',
        'cross_validation_results',
        'cv_folds',
        'robustness_tests',
        'out_of_sample_performance',
        'version',
        'parent_experiment_id',
        'version_changes',
        'experiment_artifacts',
        'environment_config',
        'random_seed',
        'code_version',
        'dependency_versions',
        'reproduction_notes',
        'business_metrics',
        'expected_roi',
        'implementation_plan',
        'implementation_status',
        'review_status',
        'reviewed_by_user_id',
        'reviewed_at',
        'review_comments',
        'peer_review_feedback',
        'methodology_notes',
        'visualizations',
        'publication_references',
        'is_publishable',
        'sharing_level',
        'error_count',
        'error_log',
        'debugging_notes',
        'performance_issues',
        'future_work_suggestions',
        'limitations_identified',
        'requires_followup',
        'followup_experiments',
        'team_members',
        'stakeholder_feedback',
        'expert_validation',
        'follows_ml_guidelines',
        'ethical_considerations',
        'compliance_checks',
        'risk_assessment',
        'created_by_user_id',
        'updated_by_user_id'
    ];

    protected $casts = [
        'hypothesis' => 'array',
        'experimental_design' => 'array',
        'success_criteria' => 'array',
        'baseline_metrics' => 'array',
        'target_sample_size' => 'integer',
        'statistical_power' => 'decimal:4',
        'significance_level' => 'decimal:4',
        'model_configurations' => 'array',
        'hyperparameter_space' => 'array',
        'feature_sets' => 'array',
        'data_splits' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration_minutes' => 'integer',
        'iterations_completed' => 'integer',
        'total_iterations' => 'integer',
        'computational_resources' => 'array',
        'total_compute_hours' => 'integer',
        'estimated_cost' => 'decimal:2',
        'infrastructure_details' => 'array',
        'results_summary' => 'array',
        'model_performances' => 'array',
        'statistical_tests' => 'array',
        'confidence_intervals' => 'array',
        'best_model_metrics' => 'array',
        'winning_configuration' => 'array',
        'improvement_over_baseline' => 'decimal:6',
        'statistically_significant' => 'boolean',
        'applicable_positions' => 'array',
        'game_contexts' => 'array',
        'feature_importance_analysis' => 'array',
        'data_quality_impact' => 'array',
        'sample_size_analysis' => 'array',
        'bias_analysis' => 'array',
        'cross_validation_results' => 'array',
        'cv_folds' => 'integer',
        'robustness_tests' => 'array',
        'out_of_sample_performance' => 'array',
        'version_changes' => 'array',
        'experiment_artifacts' => 'array',
        'environment_config' => 'array',
        'random_seed' => 'integer',
        'dependency_versions' => 'array',
        'business_metrics' => 'array',
        'expected_roi' => 'decimal:2',
        'implementation_plan' => 'array',
        'reviewed_at' => 'datetime',
        'peer_review_feedback' => 'array',
        'visualizations' => 'array',
        'publication_references' => 'array',
        'is_publishable' => 'boolean',
        'error_count' => 'integer',
        'error_log' => 'array',
        'performance_issues' => 'array',
        'future_work_suggestions' => 'array',
        'limitations_identified' => 'array',
        'requires_followup' => 'boolean',
        'followup_experiments' => 'array',
        'team_members' => 'array',
        'stakeholder_feedback' => 'array',
        'expert_validation' => 'array',
        'follows_ml_guidelines' => 'boolean',
        'ethical_considerations' => 'array',
        'compliance_checks' => 'array'
    ];

    // Status constants
    public const STATUS_PLANNED = 'planned';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_ANALYZING = 'analyzing';

    // Experiment types
    public const TYPE_MODEL_COMPARISON = 'model_comparison';
    public const TYPE_HYPERPARAMETER_TUNING = 'hyperparameter_tuning';
    public const TYPE_FEATURE_SELECTION = 'feature_selection';
    public const TYPE_DATA_AUGMENTATION = 'data_augmentation';
    public const TYPE_CROSS_VALIDATION = 'cross_validation';
    public const TYPE_AB_TEST = 'ab_test';

    // Implementation status
    public const IMPLEMENTATION_NOT_PLANNED = 'not_planned';
    public const IMPLEMENTATION_PLANNED = 'planned';
    public const IMPLEMENTATION_IN_PROGRESS = 'in_progress';
    public const IMPLEMENTATION_IMPLEMENTED = 'implemented';
    public const IMPLEMENTATION_ROLLED_BACK = 'rolled_back';

    // Review status
    public const REVIEW_UNREVIEWED = 'unreviewed';
    public const REVIEW_UNDER_REVIEW = 'under_review';
    public const REVIEW_APPROVED = 'approved';
    public const REVIEW_REJECTED = 'rejected';
    public const REVIEW_NEEDS_REVISION = 'needs_revision';

    // Sharing levels
    public const SHARING_PRIVATE = 'private';
    public const SHARING_TEAM_ONLY = 'team_only';
    public const SHARING_ORGANIZATION = 'organization';
    public const SHARING_PUBLIC = 'public';

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

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function bestModel(): BelongsTo
    {
        return $this->belongsTo(MLModel::class, 'best_model_id');
    }

    public function parentExperiment(): BelongsTo
    {
        return $this->belongsTo(MLExperiment::class, 'parent_experiment_id');
    }

    public function childExperiments()
    {
        return $this->hasMany(MLExperiment::class, 'parent_experiment_id');
    }

    /**
     * Scopes
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeRunning($query)
    {
        return $query->where('status', self::STATUS_RUNNING);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('experiment_type', $type);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', self::STATUS_COMPLETED)
                    ->whereNotNull('best_model_id');
    }

    public function scopeSignificant($query)
    {
        return $query->where('statistically_significant', true);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeByDomain($query, string $domain)
    {
        return $query->where('basketball_domain', $domain);
    }

    public function scopeNeedsReview($query)
    {
        return $query->where('review_status', self::REVIEW_UNREVIEWED)
                    ->where('status', self::STATUS_COMPLETED);
    }

    public function scopePublishable($query)
    {
        return $query->where('is_publishable', true)
                    ->where('review_status', self::REVIEW_APPROVED);
    }

    /**
     * Business Logic Methods
     */
    
    /**
     * Start the experiment
     */
    public function start(): void
    {
        $this->status = self::STATUS_RUNNING;
        $this->started_at = now();
        $this->save();
    }

    /**
     * Complete the experiment with results
     */
    public function complete(array $results): void
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completed_at = now();
        $this->duration_minutes = $this->started_at 
            ? $this->started_at->diffInMinutes($this->completed_at) 
            : 0;
        
        $this->results_summary = $results['summary'] ?? null;
        $this->model_performances = $results['performances'] ?? null;
        $this->statistical_tests = $results['statistical_tests'] ?? null;
        $this->best_model_id = $results['best_model_id'] ?? null;
        
        // Calculate improvement over baseline
        if (isset($results['improvement'])) {
            $this->improvement_over_baseline = $results['improvement'];
        }
        
        // Determine statistical significance
        if (isset($results['p_value'])) {
            $this->statistically_significant = $results['p_value'] <= $this->significance_level;
        }
        
        $this->save();
    }

    /**
     * Mark experiment as failed with error details
     */
    public function markFailed(string $error, array $errorDetails = []): void
    {
        $this->status = self::STATUS_FAILED;
        $this->completed_at = now();
        $this->error_count++;
        
        $errorLog = $this->error_log ?? [];
        $errorLog[] = [
            'timestamp' => now()->toISOString(),
            'error' => $error,
            'details' => $errorDetails
        ];
        $this->error_log = $errorLog;
        
        $this->save();
    }

    /**
     * Update experiment progress
     */
    public function updateProgress(int $iterationsCompleted, array $intermediateResults = []): void
    {
        $this->iterations_completed = $iterationsCompleted;
        
        if (!empty($intermediateResults)) {
            $this->model_performances = array_merge(
                $this->model_performances ?? [],
                $intermediateResults
            );
        }
        
        $this->save();
    }

    /**
     * Check if experiment achieved its success criteria
     */
    public function achievedSuccessCriteria(): bool
    {
        if (!$this->success_criteria || !$this->results_summary) {
            return false;
        }

        foreach ($this->success_criteria as $criterion) {
            $metric = $criterion['metric'];
            $target = $criterion['target'];
            $operator = $criterion['operator'] ?? '>=';
            
            $actualValue = $this->results_summary[$metric] ?? null;
            if ($actualValue === null) {
                continue;
            }
            
            $success = match ($operator) {
                '>=' => $actualValue >= $target,
                '>' => $actualValue > $target,
                '<=' => $actualValue <= $target,
                '<' => $actualValue < $target,
                '==' => abs($actualValue - $target) < 0.001,
                default => false
            };
            
            if (!$success) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get experiment summary for reporting
     */
    public function getSummaryReport(): array
    {
        return [
            'basic_info' => [
                'name' => $this->name,
                'type' => $this->experiment_type,
                'status' => $this->status,
                'duration' => $this->duration_minutes ? "{$this->duration_minutes} minutes" : null,
                'created_by' => $this->createdBy->name ?? 'Unknown'
            ],
            'objectives' => [
                'primary_objective' => $this->objective,
                'hypothesis' => $this->hypothesis,
                'success_criteria_met' => $this->achievedSuccessCriteria()
            ],
            'results' => [
                'best_model' => $this->bestModel->name ?? null,
                'improvement_over_baseline' => $this->improvement_over_baseline,
                'statistically_significant' => $this->statistically_significant,
                'key_findings' => $this->conclusions
            ],
            'resources' => [
                'iterations_completed' => $this->iterations_completed,
                'total_iterations' => $this->total_iterations,
                'compute_hours' => $this->total_compute_hours,
                'estimated_cost' => $this->estimated_cost
            ],
            'business_impact' => [
                'expected_roi' => $this->expected_roi,
                'implementation_status' => $this->implementation_status,
                'business_metrics' => $this->business_metrics
            ],
            'quality_assurance' => [
                'review_status' => $this->review_status,
                'follows_guidelines' => $this->follows_ml_guidelines,
                'error_count' => $this->error_count
            ]
        ];
    }

    /**
     * Export experiment configuration for replication
     */
    public function exportConfiguration(): array
    {
        return [
            'experiment_setup' => [
                'name' => $this->name,
                'type' => $this->experiment_type,
                'objective' => $this->objective,
                'hypothesis' => $this->hypothesis
            ],
            'data_configuration' => [
                'feature_sets' => $this->feature_sets,
                'data_splits' => $this->data_splits,
                'sample_size' => $this->target_sample_size
            ],
            'model_configuration' => [
                'algorithms' => $this->model_configurations,
                'hyperparameter_space' => $this->hyperparameter_space
            ],
            'experimental_design' => [
                'cv_folds' => $this->cv_folds,
                'statistical_power' => $this->statistical_power,
                'significance_level' => $this->significance_level
            ],
            'environment' => [
                'random_seed' => $this->random_seed,
                'code_version' => $this->code_version,
                'dependencies' => $this->dependency_versions,
                'infrastructure' => $this->infrastructure_details
            ]
        ];
    }

    /**
     * Generate recommendations for future experiments
     */
    public function generateFollowupRecommendations(): array
    {
        $recommendations = [];
        
        // Based on results
        if ($this->status === self::STATUS_COMPLETED) {
            if (!$this->achievedSuccessCriteria()) {
                $recommendations[] = [
                    'type' => 'improvement',
                    'suggestion' => 'Experiment did not meet success criteria. Consider adjusting hyperparameters or trying different algorithms.',
                    'priority' => 'high'
                ];
            }
            
            if ($this->improvement_over_baseline && $this->improvement_over_baseline > 0.1) {
                $recommendations[] = [
                    'type' => 'implementation',
                    'suggestion' => 'Significant improvement achieved. Consider implementing the best model in production.',
                    'priority' => 'high'
                ];
            }
        }
        
        // Based on feature importance
        if ($this->feature_importance_analysis) {
            $lowImportanceFeatures = array_filter(
                $this->feature_importance_analysis,
                fn($feature) => $feature['importance'] < 0.01
            );
            
            if (count($lowImportanceFeatures) > 0) {
                $recommendations[] = [
                    'type' => 'feature_selection',
                    'suggestion' => 'Consider removing low-importance features to reduce model complexity.',
                    'priority' => 'medium',
                    'details' => array_column($lowImportanceFeatures, 'name')
                ];
            }
        }
        
        // Based on bias analysis
        if ($this->bias_analysis && !empty($this->bias_analysis['detected_biases'])) {
            $recommendations[] = [
                'type' => 'bias_mitigation',
                'suggestion' => 'Bias detected in model. Consider bias mitigation techniques.',
                'priority' => 'high',
                'details' => $this->bias_analysis['detected_biases']
            ];
        }
        
        return $recommendations;
    }

    /**
     * Activity Log Configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 'experiment_type', 'status', 'best_model_id',
                'statistically_significant', 'review_status'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Validation rules
     */
    public static function validationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'experiment_type' => 'required|in:' . implode(',', [
                self::TYPE_MODEL_COMPARISON,
                self::TYPE_HYPERPARAMETER_TUNING,
                self::TYPE_FEATURE_SELECTION,
                self::TYPE_DATA_AUGMENTATION,
                self::TYPE_CROSS_VALIDATION,
                self::TYPE_AB_TEST
            ]),
            'objective' => 'required|string|max:255',
            'hypothesis' => 'nullable|array',
            'significance_level' => 'nullable|numeric|between:0.001,0.1',
            'cv_folds' => 'nullable|integer|between:2,20'
        ];
    }
}