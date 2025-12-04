<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;

class Play extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'created_by_user_id',
        'name',
        'description',
        'court_type',
        'play_data',
        'animation_data',
        'thumbnail_path',
        'category',
        'tags',
        'is_public',
        'is_featured',
        'is_system_template',
        'template_order',
        'status',
        'usage_count',
    ];

    protected $casts = [
        'play_data' => 'array',
        'animation_data' => 'array',
        'tags' => 'array',
        'is_public' => 'boolean',
        'is_featured' => 'boolean',
        'is_system_template' => 'boolean',
        'usage_count' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($play) {
            if (empty($play->uuid)) {
                $play->uuid = (string) Str::uuid();
            }
        });
    }

    // Relationships
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function playbooks(): BelongsToMany
    {
        return $this->belongsToMany(Playbook::class, 'playbook_plays')
            ->withPivot(['order', 'notes'])
            ->withTimestamps()
            ->orderByPivot('order');
    }

    public function drills(): BelongsToMany
    {
        return $this->belongsToMany(Drill::class, 'drill_plays')
            ->withPivot('order')
            ->withTimestamps()
            ->orderByPivot('order');
    }

    public function trainingSessions(): BelongsToMany
    {
        return $this->belongsToMany(TrainingSession::class, 'training_session_plays')
            ->withPivot(['order', 'notes'])
            ->withTimestamps()
            ->orderByPivot('order');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(PlayFavorite::class);
    }

    public function favoritedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'play_favorites')
            ->withPivot(['notes', 'tags', 'favorite_type', 'personal_priority', 'is_quick_access'])
            ->withTimestamps();
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true)->where('status', 'published');
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByCourtType($query, string $courtType)
    {
        return $query->where('court_type', $courtType);
    }

    public function scopeForTenant($query, ?string $tenantId)
    {
        if ($tenantId) {
            return $query->where('tenant_id', $tenantId);
        }
        return $query->whereNull('tenant_id');
    }

    public function scopeAccessibleByUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('is_public', true)
                ->where('status', 'published')
                ->orWhere('created_by_user_id', $userId);
        });
    }

    public function scopeSystemTemplates($query)
    {
        return $query->whereNull('tenant_id')
            ->where('is_system_template', true)
            ->where('status', 'published');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)
            ->where('status', 'published');
    }

    public function scopeTemplates($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('tenant_id')
              ->where('is_system_template', true);
        })->orWhere(function ($q) {
            $q->where('is_public', true)
              ->where('status', 'published');
        });
    }

    // Accessors
    public function isPublished(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status === 'published',
        );
    }

    public function hasAnimation(): Attribute
    {
        return Attribute::make(
            get: fn () => !empty($this->animation_data) && !empty($this->animation_data['keyframes']),
        );
    }

    public function isTemplate(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->is_system_template ||
                ($this->tenant_id === null && $this->is_public && $this->status === 'published'),
        );
    }

    public function categoryDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                $categories = [
                    'offense' => 'Offense',
                    'defense' => 'Defense',
                    'press_break' => 'Press Break',
                    'inbound' => 'Einwurf',
                    'fast_break' => 'Fast Break',
                    'zone' => 'Zonenverteidigung',
                    'man_to_man' => 'Mann-gegen-Mann',
                    'transition' => 'Transition',
                    'special' => 'Spezial',
                ];

                return $categories[$this->category] ?? $this->category;
            },
        );
    }

    public function courtTypeDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                $types = [
                    'half_horizontal' => 'Halbes Feld (horizontal)',
                    'full' => 'Ganzes Feld',
                    'half_vertical' => 'Halbes Feld (vertikal)',
                ];

                return $types[$this->court_type] ?? $this->court_type;
            },
        );
    }

    // Helper Methods
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    public function duplicate(?int $userId = null, ?string $tenantId = null): self
    {
        $duplicate = $this->replicate();
        $duplicate->uuid = (string) Str::uuid();
        $duplicate->name = $this->name . ' (Kopie)';
        $duplicate->created_by_user_id = $userId ?? auth()->id();
        $duplicate->tenant_id = $tenantId ?? auth()->user()?->tenant_id;
        $duplicate->status = 'draft';
        $duplicate->is_public = false;
        $duplicate->is_featured = false;
        $duplicate->is_system_template = false;
        $duplicate->template_order = null;
        $duplicate->usage_count = 0;
        $duplicate->thumbnail_path = null;
        $duplicate->save();

        return $duplicate;
    }

    public function publish(): self
    {
        $this->update(['status' => 'published']);
        return $this;
    }

    public function archive(): self
    {
        $this->update(['status' => 'archived']);
        return $this;
    }

    public function getPlayerCount(): int
    {
        if (!$this->play_data || !isset($this->play_data['elements']['players'])) {
            return 0;
        }
        return count($this->play_data['elements']['players']);
    }

    public function getAnimationDuration(): int
    {
        if (!$this->animation_data || !isset($this->animation_data['duration'])) {
            return 0;
        }
        return (int) $this->animation_data['duration'];
    }
}
