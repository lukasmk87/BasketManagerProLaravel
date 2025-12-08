<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlayerAbsence extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'player_id',
        'type',
        'start_date',
        'end_date',
        'notes',
        'reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    // ========================================
    // Relationships
    // ========================================

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    // ========================================
    // Scopes
    // ========================================

    /**
     * Scope für aktuelle Abwesenheiten (heute liegt im Zeitraum)
     */
    public function scopeCurrent($query)
    {
        $today = now()->toDateString();

        return $query->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today);
    }

    /**
     * Scope für zukünftige Abwesenheiten
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>', now()->toDateString());
    }

    /**
     * Scope für Abwesenheiten die ein bestimmtes Datum überlappen
     */
    public function scopeOverlapping($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
                ->orWhereBetween('end_date', [$startDate, $endDate])
                ->orWhere(function ($q2) use ($startDate, $endDate) {
                    $q2->where('start_date', '<=', $startDate)
                        ->where('end_date', '>=', $endDate);
                });
        });
    }

    /**
     * Scope für Abwesenheiten die ein bestimmtes Datum abdecken
     */
    public function scopeCoversDate($query, $date)
    {
        $dateString = $date instanceof Carbon ? $date->toDateString() : $date;

        return $query->where('start_date', '<=', $dateString)
            ->where('end_date', '>=', $dateString);
    }

    /**
     * Scope für einen bestimmten Spieler
     */
    public function scopeByPlayer($query, int $playerId)
    {
        return $query->where('player_id', $playerId);
    }

    /**
     * Scope für einen bestimmten Typ
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope für Abwesenheiten sortiert nach Startdatum
     */
    public function scopeOrderByStartDate($query, string $direction = 'asc')
    {
        return $query->orderBy('start_date', $direction);
    }

    // ========================================
    // Accessors & Mutators
    // ========================================

    /**
     * Deutscher Anzeigename für den Typ
     */
    public function typeDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                $types = [
                    'vacation' => 'Urlaub',
                    'illness' => 'Krankheit',
                    'injury' => 'Verletzung',
                    'personal' => 'Persönlich',
                    'other' => 'Sonstiges',
                ];

                return $types[$this->type] ?? $this->type;
            },
        );
    }

    /**
     * Icon für den Typ (für Frontend)
     */
    public function typeIcon(): Attribute
    {
        return Attribute::make(
            get: function () {
                $icons = [
                    'vacation' => 'sun',
                    'illness' => 'thermometer',
                    'injury' => 'bandage',
                    'personal' => 'user',
                    'other' => 'info-circle',
                ];

                return $icons[$this->type] ?? 'info-circle';
            },
        );
    }

    /**
     * Farbe für den Typ (für Frontend)
     */
    public function typeColor(): Attribute
    {
        return Attribute::make(
            get: function () {
                $colors = [
                    'vacation' => 'blue',
                    'illness' => 'yellow',
                    'injury' => 'red',
                    'personal' => 'purple',
                    'other' => 'gray',
                ];

                return $colors[$this->type] ?? 'gray';
            },
        );
    }

    /**
     * Prüft ob die Abwesenheit aktuell aktiv ist
     */
    public function isCurrent(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->coversDate(now()),
        );
    }

    /**
     * Prüft ob die Abwesenheit in der Zukunft liegt
     */
    public function isUpcoming(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->start_date->isFuture(),
        );
    }

    /**
     * Prüft ob die Abwesenheit in der Vergangenheit liegt
     */
    public function isPast(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->end_date->isPast(),
        );
    }

    /**
     * Dauer in Tagen
     */
    public function durationDays(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->start_date->diffInDays($this->end_date) + 1,
        );
    }

    /**
     * Formatierter Zeitraum
     */
    public function periodDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->start_date->isSameDay($this->end_date)) {
                    return $this->start_date->format('d.m.Y');
                }

                return $this->start_date->format('d.m.Y').' - '.$this->end_date->format('d.m.Y');
            },
        );
    }

    // ========================================
    // Helper Methods
    // ========================================

    /**
     * Prüft ob ein bestimmtes Datum von dieser Abwesenheit abgedeckt wird
     */
    public function coversDate(Carbon $date): bool
    {
        return $date->between($this->start_date->startOfDay(), $this->end_date->endOfDay());
    }

    /**
     * Gibt alle Daten zurück, die von dieser Abwesenheit abgedeckt werden
     */
    public function getCoveredDates(): array
    {
        $dates = [];
        $current = $this->start_date->copy();

        while ($current->lte($this->end_date)) {
            $dates[] = $current->copy();
            $current->addDay();
        }

        return $dates;
    }

    /**
     * Gibt ein zusammenfassendes Array für API-Responses zurück
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_display' => $this->type_display,
            'type_icon' => $this->type_icon,
            'type_color' => $this->type_color,
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date->toDateString(),
            'period_display' => $this->period_display,
            'duration_days' => $this->duration_days,
            'reason' => $this->reason,
            'is_current' => $this->is_current,
            'is_upcoming' => $this->is_upcoming,
            'is_past' => $this->is_past,
            'player_id' => $this->player_id,
            'player_name' => $this->player?->full_name,
        ];
    }

    /**
     * Statische Methode zum Erstellen einer Abwesenheit
     */
    public static function createAbsence(
        int $playerId,
        string $type,
        Carbon $startDate,
        Carbon $endDate,
        ?string $reason = null,
        ?string $notes = null
    ): self {
        // Validiere den Typ
        $validTypes = ['vacation', 'illness', 'injury', 'personal', 'other'];
        if (! in_array($type, $validTypes)) {
            throw new \InvalidArgumentException("Ungültiger Abwesenheitstyp: {$type}");
        }

        // Validiere die Daten
        if ($endDate->lt($startDate)) {
            throw new \InvalidArgumentException('Das Enddatum muss nach dem Startdatum liegen.');
        }

        return self::create([
            'player_id' => $playerId,
            'type' => $type,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => $reason,
            'notes' => $notes,
        ]);
    }
}
