<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class MLTrainingData extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'dataset_name',
        'dataset_version',
        'data_hash',
        'partition_key',
        'source_entity_type',
        'source_entity_id',
        'data_type',
        'data_subtype',
        'features',
        'feature_names',
        'feature_types',
        'feature_metadata',
        'targets',
        'target_names',
        'target_types',
        'target_metadata',
        'data_completeness',
        'missing_values_info',
        'outlier_info',
        'preprocessing_applied',
        'normalization_params',
        'data_start_date',
        'data_end_date',
        'season',
        'games_included',
        'players_included',
        'team_ids',
        'player_positions',
        'game_types',
        'opponent_strength',
        'venue_types',
        'total_samples',
        'positive_samples',
        'negative_samples',
        'class_distribution',
        'feature_statistics',
        'correlation_matrix',
        'partition_type',
        'train_ratio',
        'validation_ratio',
        'test_ratio',
        'split_strategy',
        'source_tables',
        'source_queries',
        'extraction_logic',
        'transformation_log',
        'validation_rules',
        'validation_results',
        'data_approved',
        'approved_at',
        'quality_notes',
        'models_trained',
        'last_used_at',
        'usage_history',
        'storage_path',
        'storage_format',
        'file_size_bytes',
        'compression',
        'access_permissions',
        'bias_analysis',
        'fairness_metrics',
        'demographic_breakdown',
        'ethical_considerations',
        'external_data_sources',
        'weather_data',
        'injury_context',
        'team_dynamics',
        'status',
        'is_active',
        'is_synthetic',
        'expires_at',
        'privacy_settings',
        'retention_policy',
        'legal_notes',
        'anonymized',
        'created_by_user_id',
        'approved_by_user_id',
        'parent_dataset_id'
    ];

    protected $casts = [
        'features' => 'array',
        'feature_names' => 'array',
        'feature_types' => 'array',
        'feature_metadata' => 'array',
        'targets' => 'array',
        'target_names' => 'array',
        'target_types' => 'array',
        'target_metadata' => 'array',
        'data_completeness' => 'decimal:4',
        'missing_values_info' => 'array',
        'outlier_info' => 'array',
        'preprocessing_applied' => 'array',
        'normalization_params' => 'array',
        'data_start_date' => 'datetime',
        'data_end_date' => 'datetime',
        'games_included' => 'integer',
        'players_included' => 'integer',
        'team_ids' => 'array',
        'player_positions' => 'array',
        'game_types' => 'array',
        'opponent_strength' => 'array',
        'venue_types' => 'array',
        'total_samples' => 'integer',
        'positive_samples' => 'integer',
        'negative_samples' => 'integer',
        'class_distribution' => 'array',
        'feature_statistics' => 'array',
        'correlation_matrix' => 'array',
        'train_ratio' => 'decimal:4',
        'validation_ratio' => 'decimal:4',
        'test_ratio' => 'decimal:4',
        'source_tables' => 'array',
        'source_queries' => 'array',
        'transformation_log' => 'array',
        'validation_rules' => 'array',
        'validation_results' => 'array',
        'data_approved' => 'boolean',
        'approved_at' => 'datetime',
        'models_trained' => 'integer',
        'last_used_at' => 'datetime',
        'usage_history' => 'array',
        'file_size_bytes' => 'integer',
        'access_permissions' => 'array',
        'bias_analysis' => 'array',
        'fairness_metrics' => 'array',
        'demographic_breakdown' => 'array',
        'external_data_sources' => 'array',
        'weather_data' => 'array',
        'injury_context' => 'array',
        'team_dynamics' => 'array',
        'is_active' => 'boolean',
        'is_synthetic' => 'boolean',
        'expires_at' => 'datetime',
        'privacy_settings' => 'array',
        'retention_policy' => 'array',
        'anonymized' => 'boolean'
    ];

    // Status constants
    public const STATUS_COLLECTING = 'collecting';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_VALIDATING = 'validating';
    public const STATUS_READY = 'ready';
    public const STATUS_IN_USE = 'in_use';
    public const STATUS_DEPRECATED = 'deprecated';
    public const STATUS_ARCHIVED = 'archived';
    public const STATUS_FAILED = 'failed';

    // Data types
    public const TYPE_PLAYER_PERFORMANCE = 'player_performance';
    public const TYPE_INJURY_DATA = 'injury_data';
    public const TYPE_GAME_OUTCOME = 'game_outcome';
    public const TYPE_SHOT_DATA = 'shot_data';
    public const TYPE_TEAM_STATS = 'team_stats';
    public const TYPE_BIOMETRIC = 'biometric';

    // Partition types
    public const PARTITION_TRAIN = 'train';
    public const PARTITION_VALIDATION = 'validation';
    public const PARTITION_TEST = 'test';
    public const PARTITION_HOLDOUT = 'holdout';

    /**
     * Relationships
     */
    public function sourceEntity(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function parentDataset(): BelongsTo
    {
        return $this->belongsTo(MLTrainingData::class, 'parent_dataset_id');
    }

    public function childDatasets()
    {
        return $this->hasMany(MLTrainingData::class, 'parent_dataset_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeApproved($query)
    {
        return $query->where('data_approved', true);
    }

    public function scopeReady($query)
    {
        return $query->where('status', self::STATUS_READY);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('data_type', $type);
    }

    public function scopeByPartition($query, string $partition)
    {
        return $query->where('partition_type', $partition);
    }

    public function scopeBySeason($query, string $season)
    {
        return $query->where('season', $season);
    }

    public function scopeHighQuality($query, float $minCompleteness = 0.8)
    {
        return $query->where('data_completeness', '>=', $minCompleteness);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeForEntity($query, $entityType, $entityId)
    {
        return $query->where('source_entity_type', $entityType)
                    ->where('source_entity_id', $entityId);
    }

    /**
     * Business Logic Methods
     */
    
    /**
     * Check if dataset is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if dataset has sufficient quality for training
     */
    public function isSufficientQuality(): bool
    {
        // Check data completeness
        if ($this->data_completeness < 0.8) {
            return false;
        }

        // Check minimum sample size
        if ($this->total_samples < 100) {
            return false;
        }

        // Check class balance for classification problems
        if ($this->positive_samples && $this->negative_samples) {
            $minClass = min($this->positive_samples, $this->negative_samples);
            $totalClass = $this->positive_samples + $this->negative_samples;
            $minClassRatio = $minClass / $totalClass;
            
            if ($minClassRatio < 0.1) { // Less than 10% minority class
                return false;
            }
        }

        return true;
    }

    /**
     * Get data quality summary
     */
    public function getQualitySummary(): array
    {
        return [
            'completeness' => $this->data_completeness,
            'total_samples' => $this->total_samples,
            'feature_count' => count($this->features ?? []),
            'missing_data_percentage' => $this->calculateMissingDataPercentage(),
            'outlier_percentage' => $this->calculateOutlierPercentage(),
            'class_balance' => $this->getClassBalance(),
            'data_span_days' => $this->data_start_date && $this->data_end_date 
                ? $this->data_start_date->diffInDays($this->data_end_date) 
                : null,
            'quality_score' => $this->calculateQualityScore()
        ];
    }

    /**
     * Calculate missing data percentage
     */
    private function calculateMissingDataPercentage(): float
    {
        if (!$this->missing_values_info || !$this->total_samples) {
            return 0;
        }

        $totalMissing = array_sum($this->missing_values_info);
        $totalValues = $this->total_samples * count($this->features ?? []);
        
        return $totalValues > 0 ? ($totalMissing / $totalValues) * 100 : 0;
    }

    /**
     * Calculate outlier percentage
     */
    private function calculateOutlierPercentage(): float
    {
        if (!$this->outlier_info || !$this->total_samples) {
            return 0;
        }

        $totalOutliers = array_sum($this->outlier_info);
        return ($totalOutliers / $this->total_samples) * 100;
    }

    /**
     * Get class balance information
     */
    private function getClassBalance(): array
    {
        if (!$this->positive_samples || !$this->negative_samples) {
            return ['balanced' => null, 'ratio' => null];
        }

        $total = $this->positive_samples + $this->negative_samples;
        $positiveRatio = $this->positive_samples / $total;
        $negativeRatio = $this->negative_samples / $total;
        
        $isBalanced = abs($positiveRatio - $negativeRatio) <= 0.2; // Within 20%
        
        return [
            'balanced' => $isBalanced,
            'positive_ratio' => round($positiveRatio, 3),
            'negative_ratio' => round($negativeRatio, 3)
        ];
    }

    /**
     * Calculate overall quality score (0-1)
     */
    private function calculateQualityScore(): float
    {
        $score = 0;
        $factors = 0;

        // Completeness factor (30%)
        if ($this->data_completeness !== null) {
            $score += $this->data_completeness * 0.3;
            $factors += 0.3;
        }

        // Sample size factor (20%)
        if ($this->total_samples) {
            $sampleScore = min(1, $this->total_samples / 1000); // 1000 samples = perfect
            $score += $sampleScore * 0.2;
            $factors += 0.2;
        }

        // Missing data factor (20%)
        $missingPercentage = $this->calculateMissingDataPercentage();
        $missingScore = max(0, 1 - ($missingPercentage / 100));
        $score += $missingScore * 0.2;
        $factors += 0.2;

        // Outlier factor (15%)
        $outlierPercentage = $this->calculateOutlierPercentage();
        $outlierScore = max(0, 1 - ($outlierPercentage / 50)); // 50% outliers = 0 score
        $score += $outlierScore * 0.15;
        $factors += 0.15;

        // Class balance factor (15%)
        $classBalance = $this->getClassBalance();
        if ($classBalance['balanced'] !== null) {
            $balanceScore = $classBalance['balanced'] ? 1 : 0.5;
            $score += $balanceScore * 0.15;
            $factors += 0.15;
        }

        return $factors > 0 ? $score / $factors : 0;
    }

    /**
     * Mark dataset as used for training
     */
    public function markAsUsed(int $modelId): void
    {
        $this->increment('models_trained');
        $this->last_used_at = now();
        
        // Update usage history
        $usageHistory = $this->usage_history ?? [];
        $usageHistory[] = [
            'model_id' => $modelId,
            'used_at' => now()->toISOString(),
            'partition_type' => $this->partition_type
        ];
        $this->usage_history = $usageHistory;
        
        if ($this->status === self::STATUS_READY) {
            $this->status = self::STATUS_IN_USE;
        }
        
        $this->save();
    }

    /**
     * Approve dataset for use
     */
    public function approve(User $approver, string $notes = null): bool
    {
        if (!$this->isSufficientQuality()) {
            return false;
        }

        $this->data_approved = true;
        $this->approved_by_user_id = $approver->id;
        $this->approved_at = now();
        $this->quality_notes = $notes;
        $this->status = self::STATUS_READY;
        
        return $this->save();
    }

    /**
     * Export dataset for Python ML training
     */
    public function exportForTraining(): array
    {
        return [
            'id' => $this->id,
            'dataset_name' => $this->dataset_name,
            'version' => $this->dataset_version,
            'data_type' => $this->data_type,
            'features' => $this->features,
            'feature_names' => $this->feature_names,
            'feature_types' => $this->feature_types,
            'targets' => $this->targets,
            'target_names' => $this->target_names,
            'target_types' => $this->target_types,
            'preprocessing_applied' => $this->preprocessing_applied,
            'normalization_params' => $this->normalization_params,
            'total_samples' => $this->total_samples,
            'partition_type' => $this->partition_type,
            'storage_path' => $this->storage_path,
            'storage_format' => $this->storage_format
        ];
    }

    /**
     * Get feature importance analysis
     */
    public function getFeatureAnalysis(): array
    {
        $analysis = [];
        
        if ($this->feature_statistics && $this->feature_names) {
            foreach ($this->feature_names as $index => $featureName) {
                $stats = $this->feature_statistics[$featureName] ?? null;
                if ($stats) {
                    $analysis[] = [
                        'name' => $featureName,
                        'type' => $this->feature_types[$index] ?? 'unknown',
                        'mean' => $stats['mean'] ?? null,
                        'std' => $stats['std'] ?? null,
                        'min' => $stats['min'] ?? null,
                        'max' => $stats['max'] ?? null,
                        'missing_count' => $this->missing_values_info[$featureName] ?? 0,
                        'outlier_count' => $this->outlier_info[$featureName] ?? 0,
                        'completeness' => $this->total_samples > 0 
                            ? 1 - (($this->missing_values_info[$featureName] ?? 0) / $this->total_samples)
                            : 0
                    ];
                }
            }
        }
        
        return $analysis;
    }

    /**
     * Check if dataset matches basketball domain requirements
     */
    public function meetsBasketballRequirements(): bool
    {
        // Check minimum games requirement
        if ($this->games_included && $this->games_included < 10) {
            return false;
        }

        // Check minimum players requirement
        if ($this->players_included && $this->players_included < 5) {
            return false;
        }

        // Check position diversity
        if ($this->player_positions) {
            $uniquePositions = count(array_unique($this->player_positions));
            if ($uniquePositions < 3) { // Need at least 3 different positions
                return false;
            }
        }

        // Check temporal coverage (at least 1 month of data)
        if ($this->data_start_date && $this->data_end_date) {
            $daysCovered = $this->data_start_date->diffInDays($this->data_end_date);
            if ($daysCovered < 30) {
                return false;
            }
        }

        return true;
    }

    /**
     * Activity Log Configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'dataset_name', 'dataset_version', 'data_type', 'status',
                'data_approved', 'total_samples', 'data_completeness'
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
            'dataset_name' => 'required|string|max:255',
            'dataset_version' => 'required|string|max:20',
            'data_type' => 'required|in:' . implode(',', [
                self::TYPE_PLAYER_PERFORMANCE,
                self::TYPE_INJURY_DATA,
                self::TYPE_GAME_OUTCOME,
                self::TYPE_SHOT_DATA,
                self::TYPE_TEAM_STATS,
                self::TYPE_BIOMETRIC
            ]),
            'features' => 'required|array|min:1',
            'targets' => 'required|array|min:1',
            'total_samples' => 'required|integer|min:1',
            'data_completeness' => 'nullable|numeric|between:0,1',
            'season' => 'nullable|string|max:20'
        ];
    }
}