<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Playbook extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'created_by_user_id',
        'team_id',
        'name',
        'description',
        'category',
        'is_default',
        'status',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($playbook) {
            if (empty($playbook->uuid)) {
                $playbook->uuid = (string) Str::uuid();
            }
        });

        // Ensure only one default playbook per team/category
        static::saving(function ($playbook) {
            if ($playbook->is_default && $playbook->team_id) {
                static::where('team_id', $playbook->team_id)
                    ->where('category', $playbook->category)
                    ->where('id', '!=', $playbook->id ?? 0)
                    ->update(['is_default' => false]);
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

    public function team(): BelongsTo
    {
        return $this->belongsTo(BasketballTeam::class, 'team_id');
    }

    public function plays(): BelongsToMany
    {
        return $this->belongsToMany(Play::class, 'playbook_plays')
            ->withPivot(['order', 'notes'])
            ->withTimestamps()
            ->orderByPivot('order');
    }

    public function games(): BelongsToMany
    {
        return $this->belongsToMany(Game::class, 'game_playbooks')
            ->withTimestamps();
    }

    // Scopes
    public function scopeForTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeForTenant($query, ?string $tenantId)
    {
        if ($tenantId) {
            return $query->where('tenant_id', $tenantId);
        }

        return $query->whereNull('tenant_id');
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    // Accessors
    public function categoryDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                $categories = [
                    'game' => 'Spielvorbereitung',
                    'practice' => 'Training',
                    'situational' => 'Situativ',
                ];

                return $categories[$this->category] ?? $this->category;
            },
        );
    }

    public function playCount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->plays()->count(),
        );
    }

    // Helper Methods
    public function addPlay(Play $play, ?int $order = null, ?string $notes = null): void
    {
        $maxOrder = $this->plays()->max('playbook_plays.order') ?? 0;

        $this->plays()->attach($play->id, [
            'order' => $order ?? ($maxOrder + 1),
            'notes' => $notes,
        ]);
    }

    public function removePlay(Play $play): void
    {
        $this->plays()->detach($play->id);
        $this->reorderPlays();
    }

    public function reorderPlays(?array $playIds = null): void
    {
        if ($playIds) {
            foreach ($playIds as $index => $playId) {
                $this->plays()->updateExistingPivot($playId, ['order' => $index + 1]);
            }
        } else {
            // Reorder based on current order
            $plays = $this->plays()->get();
            foreach ($plays->values() as $index => $play) {
                $this->plays()->updateExistingPivot($play->id, ['order' => $index + 1]);
            }
        }
    }

    public function duplicate(?int $userId = null): self
    {
        $duplicate = $this->replicate();
        $duplicate->uuid = (string) Str::uuid();
        $duplicate->name = $this->name.' (Kopie)';
        $duplicate->created_by_user_id = $userId ?? auth()->id();
        $duplicate->is_default = false;
        $duplicate->save();

        // Copy plays with pivot data
        foreach ($this->plays as $play) {
            $duplicate->plays()->attach($play->id, [
                'order' => $play->pivot->order,
                'notes' => $play->pivot->notes,
            ]);
        }

        return $duplicate;
    }

    public function setAsDefault(): void
    {
        $this->update(['is_default' => true]);
    }
}
