<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Laravel\Scout\Searchable;

class Drill extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia, Searchable;

    protected $fillable = [
        'created_by_user_id',
        'name',
        'description',
        'objectives',
        'instructions',
        'category',
        'sub_category',
        'difficulty_level',
        'age_group',
        'min_players',
        'max_players',
        'optimal_players',
        'estimated_duration',
        'space_required',
        'required_equipment',
        'optional_equipment',
        'requires_full_court',
        'requires_half_court',
        'variations',
        'progressions',
        'regressions',
        'coaching_points',
        'measurable_outcomes',
        'success_criteria',
        'is_competitive',
        'scoring_system',
        'diagram_path',
        'diagram_annotations',
        'has_video',
        'video_duration',
        'usage_count',
        'average_rating',
        'rating_count',
        'is_featured',
        'is_public',
        'tags',
        'search_keywords',
        'source',
        'author',
        'status',
        'reviewed_by_user_id',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'min_players' => 'integer',
        'max_players' => 'integer',
        'optimal_players' => 'integer',
        'estimated_duration' => 'integer',
        'space_required' => 'decimal:2',
        'required_equipment' => 'array',
        'optional_equipment' => 'array',
        'requires_full_court' => 'boolean',
        'requires_half_court' => 'boolean',
        'coaching_points' => 'array',
        'measurable_outcomes' => 'array',
        'success_criteria' => 'array',
        'is_competitive' => 'boolean',
        'diagram_annotations' => 'array',
        'has_video' => 'boolean',
        'video_duration' => 'integer',
        'usage_count' => 'integer',
        'average_rating' => 'decimal:2',
        'rating_count' => 'integer',
        'is_featured' => 'boolean',
        'is_public' => 'boolean',
        'tags' => 'array',
        'reviewed_at' => 'datetime',
    ];

    // Relationships
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function trainingSessions(): BelongsToMany
    {
        return $this->belongsToMany(TrainingSession::class, 'training_drills')
                    ->withPivot([
                        'order_in_session', 'planned_duration', 'actual_duration',
                        'participants_count', 'specific_instructions', 'modifications',
                        'success_metrics', 'drill_rating', 'performance_notes',
                        'status', 'goals_achieved'
                    ])
                    ->withTimestamps();
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(DrillRating::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(DrillFavorite::class);
    }

    public function plays(): BelongsToMany
    {
        return $this->belongsToMany(Play::class, 'drill_plays')
            ->withPivot('order')
            ->withTimestamps()
            ->orderByPivot('order');
    }

    // Scopes
    public function scopePublic($query)
    {
        return $query->where('is_public', true)->where('status', 'approved');
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByDifficulty($query, string $difficulty)
    {
        return $query->where('difficulty_level', $difficulty);
    }

    public function scopeByAgeGroup($query, string $ageGroup)
    {
        return $query->where('age_group', $ageGroup)->orWhere('age_group', 'all');
    }

    public function scopeForPlayerCount($query, int $playerCount)
    {
        return $query->where('min_players', '<=', $playerCount)
                    ->where(function ($q) use ($playerCount) {
                        $q->whereNull('max_players')
                          ->orWhere('max_players', '>=', $playerCount);
                    });
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopePopular($query)
    {
        return $query->orderBy('usage_count', 'desc');
    }

    public function scopeHighlyRated($query)
    {
        return $query->where('rating_count', '>=', 5)
                    ->orderBy('average_rating', 'desc');
    }

    public function scopeAccessibleByUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->whereIn('status', ['pending_review', 'approved'])
              ->orWhere(function ($subQ) use ($userId) {
                  $subQ->where('created_by_user_id', $userId)
                       ->whereIn('status', ['draft', 'pending_review', 'approved', 'rejected']);
              });
        });
    }

    // Accessors
    public function isApproved(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === 'approved',
        );
    }

    public function canBeUsed(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->is_public && $this->is_approved,
        );
    }

    public function displayDuration(): Attribute
    {
        return Attribute::make(
            get: function () {
                $minutes = $this->estimated_duration;
                if ($minutes < 60) {
                    return "{$minutes} Min";
                } else {
                    $hours = floor($minutes / 60);
                    $remainingMinutes = $minutes % 60;
                    return $remainingMinutes > 0 
                        ? "{$hours}h {$remainingMinutes}m" 
                        : "{$hours}h";
                }
            },
        );
    }

    public function playerCountRange(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->max_players) {
                    return "{$this->min_players}-{$this->max_players} Spieler";
                } else {
                    return "{$this->min_players}+ Spieler";
                }
            },
        );
    }

    public function categoryDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                $categories = [
                    'ball_handling' => 'Ballhandling',
                    'shooting' => 'Wurf',
                    'passing' => 'Passen',
                    'defense' => 'Verteidigung',
                    'rebounding' => 'Rebound',
                    'conditioning' => 'Kondition',
                    'agility' => 'Beweglichkeit',
                    'footwork' => 'Beinarbeit',
                    'team_offense' => 'Team-Offense',
                    'team_defense' => 'Team-Defense',
                    'transition' => 'Transition',
                    'set_plays' => 'Spielzüge',
                    'scrimmage' => 'Scrimmage',
                    'warm_up' => 'Aufwärmen',
                    'cool_down' => 'Abwärmen',
                ];
                
                return $categories[$this->category] ?? $this->category;
            },
        );
    }

    // Scout Search
    public function toSearchableArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'objectives' => $this->objectives,
            'category' => $this->category,
            'difficulty_level' => $this->difficulty_level,
            'age_group' => $this->age_group,
            'tags' => $this->tags,
            'search_keywords' => $this->search_keywords,
            'author' => $this->author,
        ];
    }

    // Media Collections
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('diagrams')
              ->singleFile()
              ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/svg+xml']);

        $this->addMediaCollection('videos')
              ->singleFile()
              ->acceptsMimeTypes(['video/mp4', 'video/webm', 'video/quicktime']);

        $this->addMediaCollection('thumbnails')
              ->singleFile()
              ->acceptsMimeTypes(['image/jpeg', 'image/png']);
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
              ->width(300)
              ->height(200)
              ->performOnCollections('diagrams', 'thumbnails');

        $this->addMediaConversion('preview')
              ->width(150)
              ->height(100)
              ->performOnCollections('videos');
    }

    // Helper Methods
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    public function addRating(int $rating, ?string $comment = null, ?int $userId = null): void
    {
        $this->ratings()->create([
            'user_id' => $userId ?? auth()->id(),
            'rating' => $rating,
            'comment' => $comment,
        ]);

        $this->recalculateAverageRating();
    }

    public function recalculateAverageRating(): void
    {
        $ratings = $this->ratings();
        
        $this->update([
            'average_rating' => $ratings->avg('rating'),
            'rating_count' => $ratings->count(),
        ]);
    }

    public function duplicate(?int $userId = null): self
    {
        $duplicate = $this->replicate();
        $duplicate->name = $this->name . ' (Kopie)';
        $duplicate->created_by_user_id = $userId ?? auth()->id();
        $duplicate->status = 'draft';
        $duplicate->is_public = false;
        $duplicate->usage_count = 0;
        $duplicate->average_rating = null;
        $duplicate->rating_count = 0;
        $duplicate->save();

        // Copy media files
        foreach ($this->getMedia() as $media) {
            $media->copy($duplicate);
        }

        return $duplicate;
    }

    public function getSimilarDrills(int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('id', '!=', $this->id)
                  ->where('category', $this->category)
                  ->where('status', 'approved')
                  ->where('is_public', true)
                  ->where('difficulty_level', $this->difficulty_level)
                  ->orderBy('average_rating', 'desc')
                  ->orderBy('usage_count', 'desc')
                  ->limit($limit)
                  ->get();
    }

    public function isApplicableForTeam(Team $team): bool
    {
        // Check age group compatibility
        if ($this->age_group !== 'all') {
            if ($team->category !== $this->age_group) {
                return false;
            }
        }

        // Check player count
        $teamPlayerCount = $team->activePlayers()->count();
        if ($teamPlayerCount < $this->min_players) {
            return false;
        }

        if ($this->max_players && $teamPlayerCount > $this->max_players) {
            return false;
        }

        return true;
    }
}