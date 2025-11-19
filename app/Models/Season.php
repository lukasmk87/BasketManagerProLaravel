<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Season extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'club_id',
        'name',
        'start_date',
        'end_date',
        'status',
        'is_current',
        'description',
        'settings',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * Relationships
     */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(BasketballTeam::class);
    }

    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    public function seasonStatistics(): HasMany
    {
        return $this->hasMany(SeasonStatistic::class);
    }

    /**
     * Scopes
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }

    public function scopeForClub(Builder $query, int $clubId): Builder
    {
        return $query->where('club_id', $clubId);
    }

    /**
     * Helper Methods
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function canBeActivated(): bool
    {
        return $this->isDraft() && now()->between($this->start_date, $this->end_date);
    }

    public function canBeCompleted(): bool
    {
        return $this->isActive() && now()->isAfter($this->end_date);
    }

    /**
     * Setzt diese Saison als aktuelle Saison für den Club
     */
    public function setAsCurrent(): void
    {
        // Alle anderen Saisons des Clubs auf nicht-current setzen
        self::where('club_id', $this->club_id)
            ->where('id', '!=', $this->id)
            ->update(['is_current' => false]);

        $this->update(['is_current' => true]);
    }

    /**
     * Aktiviert die Saison
     */
    public function activate(): bool
    {
        if (!$this->canBeActivated()) {
            return false;
        }

        $this->update(['status' => 'active']);
        $this->setAsCurrent();

        return true;
    }

    /**
     * Schließt die Saison ab
     */
    public function complete(): bool
    {
        if (!$this->canBeCompleted() && !$this->isActive()) {
            return false;
        }

        $this->update(['status' => 'completed', 'is_current' => false]);

        return true;
    }

    /**
     * Gibt den vollständigen Saison-Namen zurück (z.B. "Saison 2024/25")
     */
    public function getFullNameAttribute(): string
    {
        return "Saison {$this->name}";
    }

    /**
     * Prüft ob die Saison noch läuft (zeitlich)
     */
    public function isOngoing(): bool
    {
        return now()->between($this->start_date, $this->end_date);
    }
}
