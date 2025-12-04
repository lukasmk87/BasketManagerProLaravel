<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;

class TacticCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'type',
        'description',
        'color',
        'icon',
        'sort_order',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Relationships

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plays(): HasMany
    {
        return $this->hasMany(Play::class, 'category_id');
    }

    public function drills(): HasMany
    {
        return $this->hasMany(Drill::class, 'category_id');
    }

    // Scopes

    public function scopeForPlays(Builder $query): Builder
    {
        return $query->whereIn('type', ['play', 'both']);
    }

    public function scopeForDrills(Builder $query): Builder
    {
        return $query->whereIn('type', ['drill', 'both']);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('deleted_at');
    }

    public function scopeForTenant(Builder $query, ?string $tenantId): Builder
    {
        return $query->where(function ($q) use ($tenantId) {
            $q->whereNull('tenant_id'); // System-weite Kategorien
            if ($tenantId) {
                $q->orWhere('tenant_id', $tenantId); // Tenant-spezifische Kategorien
            }
        });
    }

    public function scopeSystemWide(Builder $query): Builder
    {
        return $query->whereNull('tenant_id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // Accessors

    protected function typeDisplay(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->type) {
                'play' => 'Spielzüge',
                'drill' => 'Übungen',
                'both' => 'Spielzüge & Übungen',
                default => $this->type,
            }
        );
    }

    protected function isForPlays(): Attribute
    {
        return Attribute::make(
            get: fn () => in_array($this->type, ['play', 'both'])
        );
    }

    protected function isForDrills(): Attribute
    {
        return Attribute::make(
            get: fn () => in_array($this->type, ['drill', 'both'])
        );
    }

    protected function isTenantSpecific(): Attribute
    {
        return Attribute::make(
            get: fn () => !is_null($this->tenant_id)
        );
    }

    // Methods

    public function canBeDeleted(): bool
    {
        return !$this->is_system;
    }

    public function getPlaysCount(): int
    {
        return $this->plays()->count();
    }

    public function getDrillsCount(): int
    {
        return $this->drills()->count();
    }

    public function getTotalUsageCount(): int
    {
        return $this->getPlaysCount() + $this->getDrillsCount();
    }
}
