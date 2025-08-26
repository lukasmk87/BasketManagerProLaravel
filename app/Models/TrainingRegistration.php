<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class TrainingRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_session_id',
        'player_id',
        'status',
        'registered_at',
        'cancelled_at',
        'registration_notes',
        'cancellation_reason',
        'is_late_registration',
        'trainer_notes',
        'confirmed_by_user_id',
        'confirmed_at',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'is_late_registration' => 'boolean',
    ];

    protected $dates = [
        'registered_at',
        'cancelled_at', 
        'confirmed_at',
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

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by_user_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['registered', 'confirmed']);
    }

    public function scopeRegistered($query)
    {
        return $query->where('status', 'registered');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeWaitlisted($query)
    {
        return $query->where('status', 'waitlist');
    }

    public function scopeBySession($query, int $sessionId)
    {
        return $query->where('training_session_id', $sessionId);
    }

    public function scopeByPlayer($query, int $playerId)
    {
        return $query->where('player_id', $playerId);
    }

    public function scopeOrderByRegistrationDate($query, string $direction = 'asc')
    {
        return $query->orderBy('registered_at', $direction);
    }

    // Accessors & Mutators
    public function statusDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                $statuses = [
                    'registered' => 'Angemeldet',
                    'confirmed' => 'Bestätigt',
                    'cancelled' => 'Abgemeldet',
                    'waitlist' => 'Warteliste',
                    'declined' => 'Abgelehnt',
                ];
                
                return $statuses[$this->status] ?? $this->status;
            },
        );
    }

    public function isActive(): Attribute
    {
        return Attribute::make(
            get: fn() => in_array($this->status, ['registered', 'confirmed']),
        );
    }

    public function canBeCancelled(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!in_array($this->status, ['registered', 'confirmed'])) {
                    return false;
                }
                
                return $this->trainingSession->isRegistrationOpen();
            },
        );
    }

    public function isLateRegistration(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->trainingSession) {
                    return false;
                }
                
                $deadline = $this->trainingSession->getRegistrationDeadline();
                return $this->registered_at->isAfter($deadline);
            },
        );
    }

    public function hoursUntilTraining(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->trainingSession) {
                    return null;
                }
                
                return now()->diffInHours($this->trainingSession->scheduled_at, false);
            },
        );
    }

    // Helper Methods
    public function confirm(?int $confirmedByUserId = null, ?string $notes = null): bool
    {
        if (!in_array($this->status, ['registered', 'waitlist'])) {
            return false;
        }

        $this->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'confirmed_by_user_id' => $confirmedByUserId,
            'trainer_notes' => $notes ? ($this->trainer_notes ? $this->trainer_notes . "\n\n" . $notes : $notes) : $this->trainer_notes,
        ]);

        return true;
    }

    public function decline(?int $declinedByUserId = null, ?string $reason = null): bool
    {
        if (!in_array($this->status, ['registered', 'waitlist'])) {
            return false;
        }

        $this->update([
            'status' => 'declined',
            'confirmed_by_user_id' => $declinedByUserId,
            'trainer_notes' => $reason ? ($this->trainer_notes ? $this->trainer_notes . "\n\nDeclined: " . $reason : "Declined: " . $reason) : $this->trainer_notes,
        ]);

        return true;
    }

    public function cancel(?string $reason = null): bool
    {
        if (!$this->can_be_cancelled) {
            return false;
        }

        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        // Check if there are waitlisted players to move up
        $this->promoteFromWaitlist();

        return true;
    }

    public function moveToWaitlist(): bool
    {
        if ($this->status !== 'registered') {
            return false;
        }

        $this->update([
            'status' => 'waitlist',
        ]);

        return true;
    }

    public function addTrainerNotes(string $notes): void
    {
        $existingNotes = $this->trainer_notes;
        $this->update([
            'trainer_notes' => $existingNotes 
                ? $existingNotes . "\n\n" . now()->format('H:i') . ": " . $notes
                : now()->format('H:i') . ": " . $notes,
        ]);
    }

    protected function promoteFromWaitlist(): void
    {
        if (!$this->trainingSession->enable_waitlist) {
            return;
        }

        $nextWaitlisted = TrainingRegistration::where('training_session_id', $this->training_session_id)
            ->where('status', 'waitlist')
            ->orderBy('registered_at')
            ->first();

        if ($nextWaitlisted && $this->trainingSession->hasCapacity()) {
            $nextWaitlisted->update(['status' => 'registered']);
        }
    }

    public function getRegistrationSummary(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status_display,
            'player_name' => $this->player->full_name ?? 'Unknown',
            'registered_at' => $this->registered_at->format('d.m.Y H:i'),
            'is_late' => $this->is_late_registration,
            'can_cancel' => $this->can_be_cancelled,
            'hours_until_training' => $this->hours_until_training,
            'trainer_notes' => $this->trainer_notes,
        ];
    }

    // Static methods
    public static function createRegistration(
        int $trainingSessionId, 
        int $playerId, 
        ?string $notes = null
    ): self {
        $session = TrainingSession::findOrFail($trainingSessionId);
        
        // Check if registration is still open
        if (!$session->isRegistrationOpen()) {
            throw new \Exception('Anmeldefrist ist bereits abgelaufen.');
        }

        // Check if player is already registered
        if (self::where('training_session_id', $trainingSessionId)
               ->where('player_id', $playerId)
               ->exists()) {
            throw new \Exception('Spieler ist bereits für diese Trainingseinheit angemeldet.');
        }

        // Determine status based on capacity
        $status = 'registered';
        if (!$session->hasCapacity()) {
            if ($session->enable_waitlist) {
                $status = 'waitlist';
            } else {
                throw new \Exception('Trainingseinheit ist bereits ausgebucht.');
            }
        }

        $isLate = now()->isAfter($session->getRegistrationDeadline());

        return self::create([
            'training_session_id' => $trainingSessionId,
            'player_id' => $playerId,
            'status' => $status,
            'registered_at' => now(),
            'registration_notes' => $notes,
            'is_late_registration' => $isLate,
        ]);
    }
}