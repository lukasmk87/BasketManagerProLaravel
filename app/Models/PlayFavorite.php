<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class PlayFavorite extends Model
{
    use HasFactory;

    protected $fillable = [
        'play_id',
        'user_id',
        'notes',
        'tags',
        'favorite_type',
        'team_id',
        'use_cases',
        'category_override',
        'personal_priority',
        'is_quick_access',
    ];

    protected $casts = [
        'tags' => 'array',
        'use_cases' => 'array',
        'personal_priority' => 'integer',
        'is_quick_access' => 'boolean',
    ];

    // Relationships
    public function play(): BelongsTo
    {
        return $this->belongsTo(Play::class);
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
    public function scopeQuickAccess($query)
    {
        return $query->where('is_quick_access', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('favorite_type', $type);
    }

    public function scopeHighPriority($query)
    {
        return $query->where('personal_priority', '>=', 8);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeWithTag($query, string $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('personal_priority', 'desc')
                    ->orderBy('created_at', 'desc');
    }

    // Accessors
    public function favoriteTypeDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                $types = [
                    'personal' => 'Persönlich',
                    'team_specific' => 'Team-spezifisch',
                    'training' => 'Training',
                    'game_prep' => 'Spielvorbereitung',
                ];

                return $types[$this->favorite_type] ?? $this->favorite_type;
            },
        );
    }

    public function priorityDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                $priority = $this->personal_priority;

                if ($priority >= 9) return 'Höchste Priorität';
                if ($priority >= 7) return 'Hohe Priorität';
                if ($priority >= 4) return 'Mittlere Priorität';
                if ($priority >= 1) return 'Niedrige Priorität';

                return 'Keine Priorität';
            },
        );
    }

    public function categoryToUse(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->category_override ?? $this->play->category,
        );
    }

    public function isTeamSpecific(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->favorite_type === 'team_specific' && !is_null($this->team_id),
        );
    }

    public function hasUseCases(): Attribute
    {
        return Attribute::make(
            get: fn() => !empty($this->use_cases),
        );
    }

    public function tagsDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->tags || empty($this->tags)) {
                    return null;
                }

                return implode(', ', $this->tags);
            },
        );
    }

    // Helper Methods
    public function addTag(string $tag): void
    {
        $tags = $this->tags ?? [];
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->update(['tags' => $tags]);
        }
    }

    public function removeTag(string $tag): void
    {
        $tags = $this->tags ?? [];
        $tags = array_filter($tags, fn($t) => $t !== $tag);
        $this->update(['tags' => array_values($tags)]);
    }

    public function addUseCase(string $useCase): void
    {
        $useCases = $this->use_cases ?? [];
        if (!in_array($useCase, $useCases)) {
            $useCases[] = $useCase;
            $this->update(['use_cases' => $useCases]);
        }
    }

    public function removeUseCase(string $useCase): void
    {
        $useCases = $this->use_cases ?? [];
        $useCases = array_filter($useCases, fn($uc) => $uc !== $useCase);
        $this->update(['use_cases' => array_values($useCases)]);
    }

    public function setPriority(int $priority): void
    {
        $priority = max(1, min(10, $priority));
        $this->update(['personal_priority' => $priority]);
    }

    public function toggleQuickAccess(): void
    {
        $this->update(['is_quick_access' => !$this->is_quick_access]);
    }

    public function updateNotes(string $notes): void
    {
        $this->update(['notes' => $notes]);
    }

    public function setCategory(string $category): void
    {
        $this->update(['category_override' => $category]);
    }

    public function clearCategoryOverride(): void
    {
        $this->update(['category_override' => null]);
    }

    public function getFavoriteContext(): array
    {
        return [
            'type' => $this->favorite_type_display,
            'priority' => [
                'level' => $this->personal_priority,
                'display' => $this->priority_display,
            ],
            'quick_access' => $this->is_quick_access,
            'team' => $this->team?->name,
            'category' => $this->category_to_use,
            'tags' => $this->tags,
            'use_cases' => $this->use_cases,
            'notes' => $this->notes,
            'favorited_at' => $this->created_at->format('d.m.Y'),
        ];
    }

    public function isApplicableFor(array $criteria = []): bool
    {
        if (isset($criteria['team_id']) && $this->is_team_specific) {
            if ($this->team_id !== $criteria['team_id']) {
                return false;
            }
        }

        if (isset($criteria['use_case']) && $this->has_use_cases) {
            if (!in_array($criteria['use_case'], $this->use_cases)) {
                return false;
            }
        }

        if (isset($criteria['tags']) && !empty($this->tags)) {
            $hasMatchingTag = !empty(array_intersect($criteria['tags'], $this->tags));
            if (!$hasMatchingTag) {
                return false;
            }
        }

        return true;
    }

    public function getSimilarFavorites(int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('user_id', $this->user_id)
                  ->where('play_id', '!=', $this->play_id)
                  ->where(function ($query) {
                      $query->where('favorite_type', $this->favorite_type)
                            ->orWhere(function ($q) {
                                if ($this->tags) {
                                    foreach ($this->tags as $tag) {
                                        $q->orWhereJsonContains('tags', $tag);
                                    }
                                }
                            })
                            ->orWhere('team_id', $this->team_id);
                  })
                  ->with('play')
                  ->orderBy('personal_priority', 'desc')
                  ->limit($limit)
                  ->get();
    }
}
