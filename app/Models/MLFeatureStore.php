<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Carbon\Carbon;

class MLFeatureStore extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'feature_name',
        'feature_group',
        'description',
        'data_type',
        'calculation_method',
        'entity_type',
        'entity_id',
        'entity_metadata',
        'current_value',
        'historical_values',
        'min_value',
        'max_value',
        'mean_value',
        'median_value',
        'std_deviation',
        'calculation_date',
        'effective_date',
        'time_period',
        'lookback_days',
        'aggregation_method',
        'season',
        'competition_level',
        'situation_context',
        'dependencies',
        'transformation_pipeline',
        'is_derived',
        'derivation_formula',
        'source_system',
        'completeness_score',
        'reliability_score',
        'sample_size',
        'confidence_interval_lower',
        'confidence_interval_upper',
        'validation_status',
        'validation_rules',
        'validation_results',
        'validation_notes',
        'model_usage',
        'average_importance',
        'max_importance',
        'usage_frequency',
        'prediction_correlation',
        'information_gain',
        'mutual_information',
        'feature_interaction_effects',
        'basketball_category',
        'position_relevance',
        'is_rate_stat',
        'is_cumulative',
        'drift_score',
        'drift_analysis',
        'last_drift_check',
        'drift_alert_triggered',
        'version',
        'parent_feature_id',
        'version_changes',
        'is_deprecated',
        'deprecated_at',
        'access_level',
        'is_sensitive',
        'privacy_tags',
        'usage_restrictions',
        'refresh_frequency',
        'next_refresh_at',
        'last_refreshed_at',
        'refresh_duration_ms',
        'auto_refresh_enabled',
        'computation_errors',
        'last_error_at',
        'last_error_message',
        'debug_information',
        'business_definition',
        'use_cases',
        'business_value_score',
        'interpretation_guide',
        'status',
        'is_featured',
        'priority',
        'created_by_user_id',
        'updated_by_user_id',
        'change_history'
    ];

    protected $casts = [
        'entity_metadata' => 'array',
        'current_value' => 'array',
        'historical_values' => 'array',
        'min_value' => 'decimal:6',
        'max_value' => 'decimal:6',
        'mean_value' => 'decimal:6',
        'median_value' => 'decimal:6',
        'std_deviation' => 'decimal:6',
        'calculation_date' => 'datetime',
        'effective_date' => 'datetime',
        'lookback_days' => 'integer',
        'dependencies' => 'array',
        'transformation_pipeline' => 'array',
        'is_derived' => 'boolean',
        'derivation_formula' => 'array',
        'completeness_score' => 'decimal:4',
        'reliability_score' => 'decimal:4',
        'sample_size' => 'integer',
        'confidence_interval_lower' => 'decimal:6',
        'confidence_interval_upper' => 'decimal:6',
        'validation_rules' => 'array',
        'validation_results' => 'array',
        'model_usage' => 'array',
        'average_importance' => 'decimal:4',
        'max_importance' => 'decimal:4',
        'usage_frequency' => 'integer',
        'prediction_correlation' => 'array',
        'information_gain' => 'decimal:6',
        'mutual_information' => 'decimal:6',
        'feature_interaction_effects' => 'array',
        'position_relevance' => 'array',
        'is_rate_stat' => 'boolean',
        'is_cumulative' => 'boolean',
        'drift_score' => 'decimal:4',
        'drift_analysis' => 'array',
        'last_drift_check' => 'datetime',
        'drift_alert_triggered' => 'boolean',
        'version_changes' => 'array',
        'is_deprecated' => 'boolean',
        'deprecated_at' => 'datetime',
        'is_sensitive' => 'boolean',
        'privacy_tags' => 'array',
        'next_refresh_at' => 'datetime',
        'last_refreshed_at' => 'datetime',
        'refresh_duration_ms' => 'integer',
        'auto_refresh_enabled' => 'boolean',
        'computation_errors' => 'integer',
        'last_error_at' => 'datetime',
        'debug_information' => 'array',
        'use_cases' => 'array',
        'business_value_score' => 'decimal:4',
        'is_featured' => 'boolean',
        'priority' => 'integer',
        'change_history' => 'array'
    ];

    // Data type constants
    public const DATA_TYPE_NUMERICAL = 'numerical';
    public const DATA_TYPE_CATEGORICAL = 'categorical';
    public const DATA_TYPE_BOOLEAN = 'boolean';
    public const DATA_TYPE_DATETIME = 'datetime';

    // Basketball categories
    public const CATEGORY_OFFENSIVE = 'offensive_stats';
    public const CATEGORY_DEFENSIVE = 'defensive_stats';
    public const CATEGORY_SHOOTING = 'shooting_metrics';
    public const CATEGORY_PASSING = 'passing_metrics';
    public const CATEGORY_REBOUNDING = 'rebounding_stats';
    public const CATEGORY_EFFICIENCY = 'efficiency_metrics';
    public const CATEGORY_ADVANCED = 'advanced_analytics';
    public const CATEGORY_CONTEXTUAL = 'contextual_factors';
    public const CATEGORY_PHYSICAL = 'physical_metrics';
    public const CATEGORY_TEAM_DYNAMICS = 'team_dynamics';

    // Status constants
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_EXPERIMENTAL = 'experimental';
    public const STATUS_DEPRECATED = 'deprecated';
    public const STATUS_ARCHIVED = 'archived';

    // Validation status
    public const VALIDATION_VALID = 'valid';
    public const VALIDATION_INVALID = 'invalid';
    public const VALIDATION_SUSPICIOUS = 'suspicious';
    public const VALIDATION_PENDING = 'pending_review';

    // Access levels
    public const ACCESS_PUBLIC = 'public';
    public const ACCESS_TEAM_ONLY = 'team_only';
    public const ACCESS_COACHING_STAFF = 'coaching_staff';
    public const ACCESS_ADMIN_ONLY = 'admin_only';

    // Refresh frequencies
    public const REFRESH_REAL_TIME = 'real_time';
    public const REFRESH_HOURLY = 'hourly';
    public const REFRESH_DAILY = 'daily';
    public const REFRESH_WEEKLY = 'weekly';
    public const REFRESH_MONTHLY = 'monthly';
    public const REFRESH_SEASONAL = 'seasonal';
    public const REFRESH_MANUAL = 'manual';

    /**
     * Relationships
     */
    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function parentFeature(): BelongsTo
    {
        return $this->belongsTo(MLFeatureStore::class, 'parent_feature_id');
    }

    public function childFeatures()
    {
        return $this->hasMany(MLFeatureStore::class, 'parent_feature_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeByGroup($query, string $group)
    {
        return $query->where('feature_group', $group);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('basketball_category', $category);
    }

    public function scopeForEntity($query, $entityType, $entityId)
    {
        return $query->where('entity_type', $entityType)
                    ->where('entity_id', $entityId);
    }

    public function scopeHighImportance($query, float $minImportance = 0.1)
    {
        return $query->where('average_importance', '>=', $minImportance);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeNeedsRefresh($query)
    {
        return $query->where('next_refresh_at', '<=', now())
                    ->where('auto_refresh_enabled', true);
    }

    public function scopeWithDrift($query, float $maxDrift = 0.1)
    {
        return $query->where('drift_score', '>', $maxDrift);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('calculation_date', '>=', now()->subDays($days));
    }

    public function scopeForSeason($query, string $season)
    {
        return $query->where('season', $season);
    }

    /**
     * Business Logic Methods
     */
    
    /**
     * Check if feature needs refresh
     */
    public function needsRefresh(): bool
    {
        if (!$this->auto_refresh_enabled || $this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        return $this->next_refresh_at && $this->next_refresh_at->isPast();
    }

    /**
     * Calculate next refresh time based on frequency
     */
    public function calculateNextRefresh(): Carbon
    {
        $now = now();
        
        return match ($this->refresh_frequency) {
            self::REFRESH_REAL_TIME => $now->addMinutes(5),
            self::REFRESH_HOURLY => $now->addHour(),
            self::REFRESH_DAILY => $now->addDay(),
            self::REFRESH_WEEKLY => $now->addWeek(),
            self::REFRESH_MONTHLY => $now->addMonth(),
            self::REFRESH_SEASONAL => $now->addMonths(3),
            default => $now->addDay()
        };
    }

    /**
     * Update feature value and statistics
     */
    public function updateValue($newValue, array $metadata = []): void
    {
        // Store previous value in historical values
        if ($this->current_value !== null) {
            $historical = $this->historical_values ?? [];
            $historical[] = [
                'value' => $this->current_value,
                'date' => $this->effective_date->toISOString(),
                'metadata' => $this->entity_metadata
            ];
            
            // Keep only last 100 historical values
            $this->historical_values = array_slice($historical, -100);
        }

        $this->current_value = is_array($newValue) ? $newValue : ['value' => $newValue];
        $this->entity_metadata = $metadata;
        $this->effective_date = now();
        $this->calculation_date = now();
        $this->last_refreshed_at = now();
        
        // Update statistical measures
        $this->updateStatistics();
        
        // Schedule next refresh
        $this->next_refresh_at = $this->calculateNextRefresh();
        
        $this->save();
    }

    /**
     * Update statistical measures based on historical values
     */
    private function updateStatistics(): void
    {
        if (!$this->historical_values || count($this->historical_values) < 2) {
            return;
        }

        $values = [];
        foreach ($this->historical_values as $historical) {
            if (isset($historical['value']['value']) && is_numeric($historical['value']['value'])) {
                $values[] = (float) $historical['value']['value'];
            }
        }

        if (empty($values)) {
            return;
        }

        $this->min_value = min($values);
        $this->max_value = max($values);
        $this->mean_value = array_sum($values) / count($values);
        
        // Calculate median
        sort($values);
        $count = count($values);
        $middle = floor($count / 2);
        
        if ($count % 2) {
            $this->median_value = $values[$middle];
        } else {
            $this->median_value = ($values[$middle - 1] + $values[$middle]) / 2;
        }

        // Calculate standard deviation
        $variance = 0;
        foreach ($values as $value) {
            $variance += pow($value - $this->mean_value, 2);
        }
        $this->std_deviation = sqrt($variance / count($values));
    }

    /**
     * Detect data drift compared to historical values
     */
    public function detectDrift(): float
    {
        if (!$this->historical_values || count($this->historical_values) < 10) {
            return 0;
        }

        $recentValues = array_slice($this->historical_values, -10);
        $olderValues = array_slice($this->historical_values, 0, -10);

        if (empty($olderValues)) {
            return 0;
        }

        $recentMean = $this->calculateMean($recentValues);
        $olderMean = $this->calculateMean($olderValues);
        
        if ($olderMean == 0) {
            $driftScore = $recentMean == 0 ? 0 : 1;
        } else {
            $driftScore = abs($recentMean - $olderMean) / abs($olderMean);
        }

        $this->drift_score = min(1, $driftScore);
        $this->last_drift_check = now();
        
        // Trigger alert if drift is significant
        if ($this->drift_score > 0.2) {
            $this->drift_alert_triggered = true;
        }

        $this->save();
        
        return $this->drift_score;
    }

    /**
     * Calculate mean from historical values array
     */
    private function calculateMean(array $historicalValues): float
    {
        $values = [];
        foreach ($historicalValues as $historical) {
            if (isset($historical['value']['value']) && is_numeric($historical['value']['value'])) {
                $values[] = (float) $historical['value']['value'];
            }
        }

        return empty($values) ? 0 : array_sum($values) / count($values);
    }

    /**
     * Get human-readable feature description
     */
    public function getHumanDescription(): string
    {
        if ($this->description) {
            return $this->description;
        }

        // Generate description based on feature name and type
        $name = str_replace('_', ' ', $this->feature_name);
        $name = ucwords($name);

        switch ($this->basketball_category) {
            case self::CATEGORY_OFFENSIVE:
                return "Offensive Statistik: {$name}";
            case self::CATEGORY_DEFENSIVE:
                return "Defensive Statistik: {$name}";
            case self::CATEGORY_SHOOTING:
                return "Wurf-Metrik: {$name}";
            case self::CATEGORY_PASSING:
                return "Pass-Metrik: {$name}";
            case self::CATEGORY_REBOUNDING:
                return "Rebound-Statistik: {$name}";
            case self::CATEGORY_EFFICIENCY:
                return "Effizienz-Metrik: {$name}";
            default:
                return "Basketball-Metrik: {$name}";
        }
    }

    /**
     * Get feature value for display
     */
    public function getDisplayValue(): string
    {
        if (!$this->current_value) {
            return 'N/A';
        }

        $value = $this->current_value['value'] ?? $this->current_value;

        switch ($this->data_type) {
            case self::DATA_TYPE_BOOLEAN:
                return $value ? 'Ja' : 'Nein';
            case self::DATA_TYPE_NUMERICAL:
                if ($this->is_rate_stat) {
                    return number_format($value * 100, 1) . '%';
                }
                return number_format($value, 2);
            case self::DATA_TYPE_CATEGORICAL:
                return (string) $value;
            case self::DATA_TYPE_DATETIME:
                return Carbon::parse($value)->format('d.m.Y H:i');
            default:
                return (string) $value;
        }
    }

    /**
     * Check if feature value is within normal range
     */
    public function isValueNormal(): bool
    {
        if (!$this->current_value || !$this->mean_value || !$this->std_deviation) {
            return true; // Assume normal if no historical data
        }

        $currentValue = $this->current_value['value'] ?? $this->current_value;
        if (!is_numeric($currentValue)) {
            return true;
        }

        // Check if value is within 2 standard deviations
        $zScore = abs($currentValue - $this->mean_value) / $this->std_deviation;
        return $zScore <= 2;
    }

    /**
     * Get feature usage summary for models
     */
    public function getUsageSummary(): array
    {
        return [
            'models_using' => count($this->model_usage ?? []),
            'usage_frequency' => $this->usage_frequency,
            'average_importance' => $this->average_importance,
            'max_importance' => $this->max_importance,
            'business_value' => $this->business_value_score,
            'last_used' => $this->updated_at->diffForHumans()
        ];
    }

    /**
     * Export feature for ML model training
     */
    public function exportForTraining(): array
    {
        return [
            'name' => $this->feature_name,
            'value' => $this->current_value,
            'data_type' => $this->data_type,
            'calculation_date' => $this->calculation_date,
            'metadata' => $this->entity_metadata,
            'completeness' => $this->completeness_score,
            'reliability' => $this->reliability_score
        ];
    }

    /**
     * Activity Log Configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'feature_name', 'current_value', 'status', 'validation_status',
                'drift_score', 'average_importance'
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
            'feature_name' => 'required|string|max:255',
            'feature_group' => 'required|string|max:255',
            'data_type' => 'required|in:' . implode(',', [
                self::DATA_TYPE_NUMERICAL,
                self::DATA_TYPE_CATEGORICAL,
                self::DATA_TYPE_BOOLEAN,
                self::DATA_TYPE_DATETIME
            ]),
            'basketball_category' => 'nullable|in:' . implode(',', [
                self::CATEGORY_OFFENSIVE,
                self::CATEGORY_DEFENSIVE,
                self::CATEGORY_SHOOTING,
                self::CATEGORY_PASSING,
                self::CATEGORY_REBOUNDING,
                self::CATEGORY_EFFICIENCY,
                self::CATEGORY_ADVANCED,
                self::CATEGORY_CONTEXTUAL,
                self::CATEGORY_PHYSICAL,
                self::CATEGORY_TEAM_DYNAMICS
            ]),
            'refresh_frequency' => 'required|in:' . implode(',', [
                self::REFRESH_REAL_TIME,
                self::REFRESH_HOURLY,
                self::REFRESH_DAILY,
                self::REFRESH_WEEKLY,
                self::REFRESH_MONTHLY,
                self::REFRESH_SEASONAL,
                self::REFRESH_MANUAL
            ]),
            'access_level' => 'required|in:' . implode(',', [
                self::ACCESS_PUBLIC,
                self::ACCESS_TEAM_ONLY,
                self::ACCESS_COACHING_STAFF,
                self::ACCESS_ADMIN_ONLY
            ])
        ];
    }
}