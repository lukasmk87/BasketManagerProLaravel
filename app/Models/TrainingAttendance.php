<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class TrainingAttendance extends Model
{
    use HasFactory;

    protected $table = 'training_attendance';

    protected $fillable = [
        'training_session_id',
        'player_id',
        'recorded_by_user_id',
        'status',
        'arrival_time',
        'departure_time',
        'minutes_late',
        'full_participation',
        'participation_notes',
        'participation_level',
        'has_injury_concern',
        'injury_notes',
        'medical_clearance',
        'effort_rating',
        'attitude_rating',
        'coach_notes',
        'absence_reason',
        'excused_absence',
        'notes',
    ];

    protected $casts = [
        'arrival_time' => 'datetime',
        'departure_time' => 'datetime',
        'minutes_late' => 'integer',
        'full_participation' => 'boolean',
        'has_injury_concern' => 'boolean',
        'medical_clearance' => 'boolean',
        'effort_rating' => 'integer',
        'attitude_rating' => 'integer',
        'excused_absence' => 'boolean',
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

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    // Scopes
    public function scopePresent($query)
    {
        return $query->where('status', 'present');
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', 'absent');
    }

    public function scopeLate($query)
    {
        return $query->where('status', 'late');
    }

    public function scopeExcused($query)
    {
        return $query->where('status', 'excused');
    }

    public function scopeByPlayer($query, int $playerId)
    {
        return $query->where('player_id', $playerId);
    }

    public function scopeBySession($query, int $sessionId)
    {
        return $query->where('training_session_id', $sessionId);
    }

    // Accessors
    public function statusDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                $statuses = [
                    'present' => 'Anwesend',
                    'absent' => 'Abwesend',
                    'late' => 'Verspätet',
                    'excused' => 'Entschuldigt',
                    'injured' => 'Verletzt',
                    'unknown' => 'Unbekannt',
                ];
                
                return $statuses[$this->status] ?? $this->status;
            },
        );
    }

    public function participationLevelDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                $levels = [
                    'full' => 'Vollständig',
                    'limited' => 'Eingeschränkt',
                    'observation_only' => 'Nur Beobachtung',
                    'none' => 'Keine Teilnahme',
                ];
                
                return $levels[$this->participation_level] ?? $this->participation_level;
            },
        );
    }

    public function isPresent(): Attribute
    {
        return Attribute::make(
            get: fn() => in_array($this->status, ['present', 'late']),
        );
    }

    public function isPunctual(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === 'present' && $this->minutes_late === 0,
        );
    }

    public function sessionDuration(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->arrival_time || !$this->departure_time) {
                    return null;
                }
                
                return $this->arrival_time->diffInMinutes($this->departure_time);
            },
        );
    }

    public function averageRating(): Attribute
    {
        return Attribute::make(
            get: function () {
                $ratings = array_filter([$this->effort_rating, $this->attitude_rating]);
                return count($ratings) > 0 ? round(array_sum($ratings) / count($ratings), 1) : null;
            },
        );
    }

    // Helper Methods
    public function markPresent(?string $notes = null): void
    {
        $this->update([
            'status' => 'present',
            'arrival_time' => now(),
            'minutes_late' => 0,
            'notes' => $notes,
        ]);
    }

    public function markLate(int $minutesLate, ?string $notes = null): void
    {
        $this->update([
            'status' => 'late',
            'arrival_time' => now(),
            'minutes_late' => $minutesLate,
            'notes' => $notes,
        ]);
    }

    public function markAbsent(string $reason, bool $excused = false): void
    {
        $this->update([
            'status' => $excused ? 'excused' : 'absent',
            'absence_reason' => $reason,
            'excused_absence' => $excused,
        ]);
    }

    public function recordDeparture(?string $notes = null): void
    {
        $this->update([
            'departure_time' => now(),
            'notes' => $this->notes . ($notes ? "\n\nDeparture: " . $notes : ''),
        ]);
    }

    public function addCoachNotes(string $notes): void
    {
        $existingNotes = $this->coach_notes;
        $this->update([
            'coach_notes' => $existingNotes 
                ? $existingNotes . "\n\n" . now()->format('H:i') . ": " . $notes
                : now()->format('H:i') . ": " . $notes,
        ]);
    }

    public function flagInjuryConcern(string $injuryNotes): void
    {
        $this->update([
            'has_injury_concern' => true,
            'injury_notes' => $injuryNotes,
            'medical_clearance' => false,
            'participation_level' => 'limited',
        ]);
    }

    public function clearMedically(): void
    {
        $this->update([
            'medical_clearance' => true,
            'has_injury_concern' => false,
            'participation_level' => 'full',
        ]);
    }

    public function getAttendanceSummary(): array
    {
        return [
            'status' => $this->status_display,
            'participation_level' => $this->participation_level_display,
            'duration' => $this->session_duration,
            'punctual' => $this->is_punctual,
            'minutes_late' => $this->minutes_late,
            'effort_rating' => $this->effort_rating,
            'attitude_rating' => $this->attitude_rating,
            'average_rating' => $this->average_rating,
            'has_injury_concern' => $this->has_injury_concern,
            'medical_clearance' => $this->medical_clearance,
        ];
    }
}