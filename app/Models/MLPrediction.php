<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class MLPrediction extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ml_predictions';

    protected $fillable = [
        'ml_model_id',
        'prediction_id',
        'prediction_type',
        'prediction_context',
        'predictable_type',
        'predictable_id',
        'target_identifiers',
        'input_features',
        'processed_features',
        'feature_metadata',
        'prediction_output',
        'prediction_probabilities',
        'confidence_score',
        'prediction_value',
        'prediction_category',
        'performance_metrics',
        'injury_risk_probability',
        'risk_factors',
        'recommended_actions',
        'quality_assessment',
        'quality_indicators',
        'uncertainty_metrics',
        'quality_notes',
        'prediction_date',
        'target_date',
        'prediction_horizon_days',
        'is_historical_prediction',
        'actual_outcome',
        'prediction_error',
        'prediction_correct',
        'outcome_recorded_at',
        'evaluation_status',
        'requested_by',
        'business_context',
        'is_automated',
        'is_critical',
        'processing_time_ms',
        'model_version_used',
        'system_info',
        'game_id',
        'training_session_id',
        'season',
        'game_phase',
        'intervention_applied',
        'interventions',
        'intervention_date',
        'intervention_results',
        'user_feedback',
        'feedback_rating',
        'feedback_comments',
        'use_for_retraining',
        'status',
        'is_active',
        'alert_thresholds',
        'alert_triggered',
        'alert_triggered_at',
        'prediction_pipeline',
        'data_lineage',
        'notes',
        'created_by_user_id',
    ];

    protected $casts = [
        'target_identifiers' => 'array',
        'input_features' => 'array',
        'processed_features' => 'array',
        'feature_metadata' => 'array',
        'prediction_output' => 'array',
        'prediction_probabilities' => 'array',
        'confidence_score' => 'decimal:4',
        'prediction_value' => 'decimal:4',
        'performance_metrics' => 'array',
        'injury_risk_probability' => 'decimal:4',
        'risk_factors' => 'array',
        'recommended_actions' => 'array',
        'quality_indicators' => 'array',
        'uncertainty_metrics' => 'array',
        'prediction_date' => 'datetime',
        'target_date' => 'datetime',
        'prediction_horizon_days' => 'integer',
        'is_historical_prediction' => 'boolean',
        'actual_outcome' => 'array',
        'prediction_error' => 'decimal:6',
        'prediction_correct' => 'boolean',
        'outcome_recorded_at' => 'datetime',
        'business_context' => 'array',
        'is_automated' => 'boolean',
        'is_critical' => 'boolean',
        'processing_time_ms' => 'integer',
        'model_version_used' => 'integer',
        'system_info' => 'array',
        'game_id' => 'integer',
        'training_session_id' => 'integer',
        'intervention_applied' => 'boolean',
        'interventions' => 'array',
        'intervention_date' => 'datetime',
        'intervention_results' => 'array',
        'user_feedback' => 'array',
        'feedback_rating' => 'integer',
        'use_for_retraining' => 'boolean',
        'is_active' => 'boolean',
        'alert_thresholds' => 'array',
        'alert_triggered' => 'boolean',
        'alert_triggered_at' => 'datetime',
        'prediction_pipeline' => 'array',
        'data_lineage' => 'array',
        'created_by_user_id' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (!$model->prediction_id) {
                $model->prediction_id = (string) Str::uuid();
            }
        });
    }

    // Relationships

    public function mlModel(): BelongsTo
    {
        return $this->belongsTo(MLModel::class);
    }

    public function predictable(): MorphTo
    {
        return $this->morphTo();
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function trainingSession(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('prediction_type', $type);
    }

    public function scopeCritical($query)
    {
        return $query->where('is_critical', true);
    }

    public function scopeHighConfidence($query, float $minConfidence = 0.8)
    {
        return $query->where('confidence_score', '>=', $minConfidence);
    }

    public function scopePendingEvaluation($query)
    {
        return $query->where('evaluation_status', 'pending');
    }

    public function scopeNeedsIntervention($query)
    {
        return $query->where('intervention_applied', false)
                    ->whereNotNull('recommended_actions');
    }

    public function scopeForPlayer($query, int $playerId)
    {
        return $query->where('predictable_type', Player::class)
                    ->where('predictable_id', $playerId);
    }

    public function scopeForSeason($query, string $season)
    {
        return $query->where('season', $season);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('prediction_date', '>=', now()->subDays($days));
    }

    // Accessors

    public function getAccuracyScoreAttribute(): ?float
    {
        if ($this->evaluation_status !== 'evaluated' || $this->actual_outcome === null) {
            return null;
        }
        
        if ($this->prediction_correct !== null) {
            return $this->prediction_correct ? 1.0 : 0.0;
        }
        
        // For regression predictions, calculate accuracy based on error
        if ($this->prediction_error !== null) {
            $relativeError = abs($this->prediction_error) / ($this->prediction_value ?: 1);
            return max(0, 1 - $relativeError);
        }
        
        return null;
    }

    public function getIsOutdatedAttribute(): bool
    {
        if (!$this->prediction_date) return true;
        
        $maxAge = match($this->prediction_type) {
            'injury_risk' => 1, // day
            'player_performance' => 7, // days
            'game_outcome' => 0, // Same day only
            default => 3
        };
        
        return $this->prediction_date->lt(now()->subDays($maxAge));
    }

    public function getReliabilityScoreAttribute(): float
    {
        $factors = [];
        
        // Confidence factor
        if ($this->confidence_score) {
            $factors[] = $this->confidence_score * 0.4;
        }
        
        // Model reliability factor
        if ($this->mlModel?->reliability_score) {
            $factors[] = $this->mlModel->reliability_score * 0.3;
        }
        
        // Data quality factor
        if ($this->quality_assessment) {
            $qualityScore = match($this->quality_assessment) {
                'high' => 1.0,
                'medium' => 0.7,
                'low' => 0.4,
                'unreliable' => 0.1,
                default => 0.5
            };
            $factors[] = $qualityScore * 0.2;
        }
        
        // Recency factor (more recent = more reliable)
        $daysSincePrediction = $this->prediction_date->diffInDays(now());
        $recencyFactor = max(0, 1 - ($daysSincePrediction / 30)); // Linear decay over 30 days
        $factors[] = $recencyFactor * 0.1;
        
        return empty($factors) ? 0 : array_sum($factors) / count($factors);
    }

    public function getRiskLevelAttribute(): string
    {
        if ($this->prediction_type !== 'injury_risk' || !$this->injury_risk_probability) {
            return 'unknown';
        }
        
        return match(true) {
            $this->injury_risk_probability >= 0.8 => 'very_high',
            $this->injury_risk_probability >= 0.6 => 'high', 
            $this->injury_risk_probability >= 0.4 => 'medium',
            $this->injury_risk_probability >= 0.2 => 'low',
            default => 'very_low'
        };
    }

    public function getPredictionAccuracyAttribute(): ?string
    {
        if (!$this->accuracy_score) return null;
        
        return match(true) {
            $this->accuracy_score >= 0.9 => 'excellent',
            $this->accuracy_score >= 0.8 => 'good',
            $this->accuracy_score >= 0.7 => 'fair',
            $this->accuracy_score >= 0.6 => 'poor',
            default => 'very_poor'
        };
    }

    // Business Logic Methods

    public function shouldTriggerAlert(): bool
    {
        if ($this->alert_triggered || !$this->alert_thresholds) {
            return false;
        }
        
        foreach ($this->alert_thresholds as $metric => $threshold) {
            $value = $this->getAttribute($metric);
            
            if ($value !== null && $value >= $threshold) {
                return true;
            }
        }
        
        return false;
    }

    public function triggerAlert(): void
    {
        $this->alert_triggered = true;
        $this->alert_triggered_at = now();
        $this->save();
        
        // Here you would typically dispatch an event or notification
        // event(new PredictionAlertTriggered($this));
    }

    public function recordActualOutcome(array $outcome): void
    {
        $this->actual_outcome = $outcome;
        $this->outcome_recorded_at = now();
        $this->evaluation_status = 'evaluated';
        
        // Calculate prediction error
        if ($this->prediction_type === 'player_performance' && isset($outcome['points'])) {
            $predictedPoints = $this->performance_metrics['predicted_points'] ?? 0;
            $this->prediction_error = abs($predictedPoints - $outcome['points']);
        } elseif ($this->prediction_type === 'injury_risk' && isset($outcome['injured'])) {
            $predicted = $this->injury_risk_probability >= 0.5;
            $actual = $outcome['injured'];
            $this->prediction_correct = $predicted === $actual;
        }
        
        $this->save();
        
        // Update model performance metrics
        $this->mlModel->recordPrediction($this->prediction_correct ?? true);
    }

    public function applyIntervention(array $interventions): void
    {
        $this->interventions = $interventions;
        $this->intervention_applied = true;
        $this->intervention_date = now();
        $this->save();
    }

    public function recordInterventionResults(array $results): void
    {
        $this->intervention_results = $results;
        $this->save();
    }

    public function provideFeedback(int $rating, string $comments = null, array $additionalFeedback = []): void
    {
        $this->feedback_rating = $rating;
        $this->feedback_comments = $comments;
        $this->user_feedback = array_merge($this->user_feedback ?: [], $additionalFeedback);
        $this->save();
    }

    public function canBeEvaluated(): bool
    {
        return $this->target_date->isPast() && 
               $this->evaluation_status === 'pending' &&
               $this->actual_outcome === null;
    }

    public function isActionable(): bool
    {
        return !empty($this->recommended_actions) && 
               !$this->intervention_applied &&
               $this->confidence_score >= 0.7;
    }

    public function getRecommendedActionsFormatted(): array
    {
        if (!$this->recommended_actions) return [];
        
        return array_map(function ($action) {
            return [
                'action' => $action['action'] ?? $action,
                'priority' => $action['priority'] ?? 'medium',
                'urgency' => $action['urgency'] ?? 'normal',
                'description' => $action['description'] ?? null,
                'estimated_impact' => $action['estimated_impact'] ?? null,
            ];
        }, $this->recommended_actions);
    }

    public function getConfidenceLevel(): string
    {
        if (!$this->confidence_score) return 'unknown';
        
        return match(true) {
            $this->confidence_score >= 0.9 => 'very_high',
            $this->confidence_score >= 0.8 => 'high',
            $this->confidence_score >= 0.7 => 'medium',
            $this->confidence_score >= 0.6 => 'low',
            default => 'very_low'
        };
    }

    public function getBusinessImpact(): array
    {
        $impact = [
            'financial' => null,
            'performance' => null,
            'health' => null,
            'strategic' => null
        ];
        
        switch ($this->prediction_type) {
            case 'injury_risk':
                $impact['health'] = $this->injury_risk_probability >= 0.7 ? 'high' : 'medium';
                $impact['financial'] = $this->injury_risk_probability >= 0.8 ? 'high' : 'low';
                break;
                
            case 'player_performance':
                $predicted = $this->performance_metrics['predicted_points'] ?? 0;
                $impact['performance'] = $predicted >= 20 ? 'high' : ($predicted >= 15 ? 'medium' : 'low');
                break;
                
            case 'game_outcome':
                $winProb = $this->prediction_probabilities['win'] ?? 0.5;
                $impact['strategic'] = $winProb >= 0.8 ? 'high' : ($winProb >= 0.6 ? 'medium' : 'low');
                break;
        }
        
        return array_filter($impact);
    }

    public function getPredictionSummary(): array
    {
        return [
            'id' => $this->prediction_id,
            'type' => $this->prediction_type,
            'target' => $this->predictable?->name ?? 'Unknown',
            'prediction_date' => $this->prediction_date->toDateString(),
            'target_date' => $this->target_date->toDateString(),
            'confidence' => $this->confidence_score,
            'confidence_level' => $this->getConfidenceLevel(),
            'reliability_score' => $this->reliability_score,
            'quality_assessment' => $this->quality_assessment,
            'is_critical' => $this->is_critical,
            'status' => $this->status,
            'main_prediction' => $this->getMainPredictionValue(),
            'recommended_actions' => count($this->recommended_actions ?: []),
            'intervention_applied' => $this->intervention_applied,
        ];
    }

    private function getMainPredictionValue()
    {
        return match($this->prediction_type) {
            'injury_risk' => [
                'value' => $this->injury_risk_probability,
                'label' => $this->risk_level,
                'unit' => 'probability'
            ],
            'player_performance' => [
                'value' => $this->performance_metrics['predicted_points'] ?? $this->prediction_value,
                'label' => 'Points',
                'unit' => 'points'
            ],
            'game_outcome' => [
                'value' => $this->prediction_probabilities['win'] ?? $this->prediction_value,
                'label' => 'Win Probability',
                'unit' => 'probability'
            ],
            default => [
                'value' => $this->prediction_value,
                'label' => $this->prediction_category,
                'unit' => 'value'
            ]
        };
    }
}