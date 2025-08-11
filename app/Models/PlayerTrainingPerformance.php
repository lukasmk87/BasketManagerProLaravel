<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class PlayerTrainingPerformance extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_session_id',
        'player_id',
        'drill_id',
        'evaluated_by_user_id',
        'skill_ratings',
        'overall_performance',
        'effort_level',
        'focus_level',
        'attitude_rating',
        'quantitative_metrics',
        'improvement_areas',
        'strengths_demonstrated',
        'goals_for_session',
        'goals_achieved',
        'goal_achievement_percentage',
        'performance_notes',
        'improvement_suggestions',
        'next_session_focus',
        'energy_level',
        'showed_fatigue',
        'physical_observations',
        'leadership_shown',
        'teamwork_rating',
        'coachable',
        'behavioral_notes',
        'skills_improved',
        'skills_regressed',
        'overall_progress_rating',
    ];

    protected $casts = [
        'skill_ratings' => 'array',
        'overall_performance' => 'integer',
        'effort_level' => 'integer',
        'focus_level' => 'integer',
        'attitude_rating' => 'integer',
        'quantitative_metrics' => 'array',
        'improvement_areas' => 'array',
        'strengths_demonstrated' => 'array',
        'goals_for_session' => 'array',
        'goals_achieved' => 'array',
        'goal_achievement_percentage' => 'decimal:2',
        'showed_fatigue' => 'boolean',
        'coachable' => 'boolean',
        'skills_improved' => 'array',
        'skills_regressed' => 'array',
        'overall_progress_rating' => 'decimal:2',
    ];

    // Relationships
    public function trainingSession(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function drill(): BelongsTo
    {
        return $this->belongsTo(Drill::class);
    }

    public function evaluatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluated_by_user_id');
    }

    // Scopes
    public function scopeByPlayer($query, int $playerId)
    {
        return $query->where('player_id', $playerId);
    }

    public function scopeBySession($query, int $sessionId)
    {
        return $query->where('training_session_id', $sessionId);
    }

    public function scopeByDrill($query, int $drillId)
    {
        return $query->where('drill_id', $drillId);
    }

    public function scopeHighPerformance($query)
    {
        return $query->where('overall_performance', '>=', 8);
    }

    public function scopeLowPerformance($query)
    {
        return $query->where('overall_performance', '<=', 4);
    }

    public function scopeShowingImprovement($query)
    {
        return $query->where('overall_progress_rating', '>', 0);
    }

    // Accessors
    public function energyLevelDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                $levels = [
                    'low' => 'Niedrig',
                    'medium' => 'Mittel',
                    'high' => 'Hoch',
                    'excellent' => 'Ausgezeichnet',
                ];
                
                return $levels[$this->energy_level] ?? $this->energy_level;
            },
        );
    }

    public function leadershipDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                $levels = [
                    'none' => 'Keine',
                    'some' => 'Etwas',
                    'good' => 'Gut',
                    'excellent' => 'Ausgezeichnet',
                ];
                
                return $levels[$this->leadership_shown] ?? $this->leadership_shown;
            },
        );
    }

    public function teamworkDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                $levels = [
                    'poor' => 'Schlecht',
                    'average' => 'Durchschnitt',
                    'good' => 'Gut',
                    'excellent' => 'Ausgezeichnet',
                ];
                
                return $levels[$this->teamwork_rating] ?? $this->teamwork_rating;
            },
        );
    }

    public function averageSkillRating(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->skill_ratings || empty($this->skill_ratings)) {
                    return null;
                }
                
                $ratings = array_values($this->skill_ratings);
                $numericRatings = array_filter($ratings, 'is_numeric');
                
                return count($numericRatings) > 0 
                    ? round(array_sum($numericRatings) / count($numericRatings), 1) 
                    : null;
            },
        );
    }

    public function overallRating(): Attribute
    {
        return Attribute::make(
            get: function () {
                $ratings = array_filter([
                    $this->overall_performance,
                    $this->effort_level,
                    $this->focus_level,
                    $this->attitude_rating
                ]);
                
                return count($ratings) > 0 
                    ? round(array_sum($ratings) / count($ratings), 1) 
                    : null;
            },
        );
    }

    public function hasImprovement(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->overall_progress_rating > 0 || 
                       ($this->skills_improved && count($this->skills_improved) > 0),
        );
    }

    public function hasRegression(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->overall_progress_rating < 0 || 
                       ($this->skills_regressed && count($this->skills_regressed) > 0),
        );
    }

    public function performanceLevel(): Attribute
    {
        return Attribute::make(
            get: function () {
                $rating = $this->overall_rating;
                if (!$rating) return 'Nicht bewertet';
                
                if ($rating >= 9) return 'Exzellent';
                if ($rating >= 7) return 'Sehr gut';
                if ($rating >= 5) return 'Gut';
                if ($rating >= 3) return 'Verbesserungswürdig';
                return 'Schwach';
            },
        );
    }

    // Helper Methods
    public function setSkillRating(string $skill, int $rating): void
    {
        $skills = $this->skill_ratings ?? [];
        $skills[$skill] = $rating;
        
        $this->update(['skill_ratings' => $skills]);
    }

    public function getSkillRating(string $skill): ?int
    {
        return $this->skill_ratings[$skill] ?? null;
    }

    public function addQuantitativeMetric(string $metric, $value, string $unit = null): void
    {
        $metrics = $this->quantitative_metrics ?? [];
        $metrics[$metric] = [
            'value' => $value,
            'unit' => $unit,
            'recorded_at' => now()->toISOString(),
        ];
        
        $this->update(['quantitative_metrics' => $metrics]);
    }

    public function addImprovementArea(string $area, string $description = null): void
    {
        $areas = $this->improvement_areas ?? [];
        $areas[] = [
            'area' => $area,
            'description' => $description,
            'identified_at' => now()->toISOString(),
        ];
        
        $this->update(['improvement_areas' => $areas]);
    }

    public function addStrength(string $strength, string $description = null): void
    {
        $strengths = $this->strengths_demonstrated ?? [];
        $strengths[] = [
            'strength' => $strength,
            'description' => $description,
            'demonstrated_at' => now()->toISOString(),
        ];
        
        $this->update(['strengths_demonstrated' => $strengths]);
    }

    public function markGoalAchieved(string $goal): void
    {
        $achieved = $this->goals_achieved ?? [];
        if (!in_array($goal, $achieved)) {
            $achieved[] = $goal;
            $this->update(['goals_achieved' => $achieved]);
            
            // Recalculate achievement percentage
            $this->recalculateGoalAchievementPercentage();
        }
    }

    public function recalculateGoalAchievementPercentage(): void
    {
        $totalGoals = count($this->goals_for_session ?? []);
        $achievedGoals = count($this->goals_achieved ?? []);
        
        $percentage = $totalGoals > 0 ? ($achievedGoals / $totalGoals) * 100 : 0;
        
        $this->update(['goal_achievement_percentage' => $percentage]);
    }

    public function addSkillImprovement(string $skill, string $description = null): void
    {
        $improvements = $this->skills_improved ?? [];
        $improvements[] = [
            'skill' => $skill,
            'description' => $description,
            'noted_at' => now()->toISOString(),
        ];
        
        $this->update(['skills_improved' => $improvements]);
    }

    public function addSkillRegression(string $skill, string $description = null): void
    {
        $regressions = $this->skills_regressed ?? [];
        $regressions[] = [
            'skill' => $skill,
            'description' => $description,
            'noted_at' => now()->toISOString(),
        ];
        
        $this->update(['skills_regressed' => $regressions]);
    }

    public function getPerformanceSummary(): array
    {
        return [
            'overall_rating' => $this->overall_rating,
            'performance_level' => $this->performance_level,
            'average_skill_rating' => $this->average_skill_rating,
            'goal_achievement' => $this->goal_achievement_percentage,
            'has_improvement' => $this->has_improvement,
            'has_regression' => $this->has_regression,
            'energy_level' => $this->energy_level_display,
            'leadership' => $this->leadership_display,
            'teamwork' => $this->teamwork_display,
            'coachable' => $this->coachable,
            'strengths_count' => count($this->strengths_demonstrated ?? []),
            'improvement_areas_count' => count($this->improvement_areas ?? []),
        ];
    }

    public function generateCoachingReport(): array
    {
        return [
            'player' => $this->player->full_name,
            'session' => $this->trainingSession->title,
            'date' => $this->trainingSession->scheduled_at->format('d.m.Y'),
            'overall_performance' => $this->performance_level,
            'key_strengths' => $this->strengths_demonstrated,
            'improvement_areas' => $this->improvement_areas,
            'goals_achieved' => $this->goals_achieved,
            'goal_achievement_rate' => $this->goal_achievement_percentage . '%',
            'next_focus' => $this->next_session_focus,
            'coach_notes' => $this->performance_notes,
            'suggestions' => $this->improvement_suggestions,
        ];
    }
}