<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class DrillRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'drill_id',
        'user_id',
        'rating',
        'comment',
        'pros',
        'cons',
        'context',
        'team_id',
        'age_group_used',
        'session_count',
        'effectiveness_rating',
        'engagement_rating',
        'difficulty_rating',
        'would_recommend',
        'suggested_modifications',
        'improvement_suggestions',
    ];

    protected $casts = [
        'rating' => 'integer',
        'session_count' => 'integer',
        'effectiveness_rating' => 'integer',
        'engagement_rating' => 'integer',
        'difficulty_rating' => 'integer',
        'would_recommend' => 'boolean',
        'improvement_suggestions' => 'array',
    ];

    // Relationships
    public function drill(): BelongsTo
    {
        return $this->belongsTo(Drill::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    // Scopes
    public function scopePositive($query)
    {
        return $query->where('rating', '>=', 4);
    }

    public function scopeNegative($query)
    {
        return $query->where('rating', '<=', 2);
    }

    public function scopeRecent($query)
    {
        return $query->where('created_at', '>=', now()->subDays(30));
    }

    public function scopeByContext($query, string $context)
    {
        return $query->where('context', $context);
    }

    public function scopeRecommended($query)
    {
        return $query->where('would_recommend', true);
    }

    // Accessors
    public function contextDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                $contexts = [
                    'after_training' => 'Nach Training',
                    'planning_session' => 'Trainingsplanung',
                    'drill_review' => 'Übungsbeurteilung',
                    'team_evaluation' => 'Team-Bewertung',
                    'general_review' => 'Allgemeine Bewertung',
                ];
                
                return $contexts[$this->context] ?? $this->context;
            },
        );
    }

    public function ratingDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                $stars = str_repeat('★', $this->rating) . str_repeat('☆', 5 - $this->rating);
                return "{$stars} ({$this->rating}/5)";
            },
        );
    }

    public function overallScore(): Attribute
    {
        return Attribute::make(
            get: function () {
                $ratings = array_filter([
                    $this->rating,
                    $this->effectiveness_rating,
                    $this->engagement_rating,
                ]);
                
                return count($ratings) > 0 
                    ? round(array_sum($ratings) / count($ratings), 1) 
                    : $this->rating;
            },
        );
    }

    public function isPositive(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->rating >= 4,
        );
    }

    public function isNegative(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->rating <= 2,
        );
    }

    public function difficultyDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->difficulty_rating) return null;
                
                $levels = [
                    1 => 'Sehr einfach',
                    2 => 'Einfach',
                    3 => 'Moderat',
                    4 => 'Schwierig',
                    5 => 'Sehr schwierig',
                ];
                
                return $levels[$this->difficulty_rating] ?? "Stufe {$this->difficulty_rating}";
            },
        );
    }

    // Helper Methods
    public function updateRating(int $rating, ?string $comment = null): void
    {
        $this->update([
            'rating' => $rating,
            'comment' => $comment,
        ]);

        // Trigger recalculation of drill's average rating
        $this->drill->recalculateAverageRating();
    }

    public function addProsAndCons(array $pros, array $cons): void
    {
        $this->update([
            'pros' => implode('; ', $pros),
            'cons' => implode('; ', $cons),
        ]);
    }

    public function setEffectivenessRating(int $rating, ?string $notes = null): void
    {
        $this->update([
            'effectiveness_rating' => $rating,
            'improvement_suggestions' => $this->improvement_suggestions 
                ? array_merge($this->improvement_suggestions, [$notes])
                : [$notes],
        ]);
    }

    public function addModificationSuggestion(string $suggestion): void
    {
        $current = $this->suggested_modifications;
        $updated = $current ? $current . "\n• " . $suggestion : "• " . $suggestion;
        
        $this->update(['suggested_modifications' => $updated]);
    }

    public function getDetailedRating(): array
    {
        return [
            'overall_rating' => $this->rating,
            'rating_display' => $this->rating_display,
            'overall_score' => $this->overall_score,
            'effectiveness' => $this->effectiveness_rating,
            'engagement' => $this->engagement_rating,
            'difficulty' => [
                'rating' => $this->difficulty_rating,
                'display' => $this->difficulty_display,
            ],
            'recommendation' => $this->would_recommend,
            'context' => $this->context_display,
            'usage_info' => [
                'session_count' => $this->session_count,
                'age_group' => $this->age_group_used,
                'team' => $this->team?->name,
            ],
            'feedback' => [
                'pros' => $this->pros,
                'cons' => $this->cons,
                'comment' => $this->comment,
                'suggestions' => $this->suggested_modifications,
            ],
        ];
    }

    public function isHelpful(): bool
    {
        // A rating is considered helpful if it has detailed feedback
        return !empty($this->comment) || 
               !empty($this->pros) || 
               !empty($this->cons) || 
               !empty($this->suggested_modifications);
    }

    public function getUsageContext(): array
    {
        return [
            'context' => $this->context_display,
            'team' => $this->team?->name,
            'age_group' => $this->age_group_used,
            'sessions_used' => $this->session_count,
            'rated_at' => $this->created_at->format('d.m.Y'),
        ];
    }
}