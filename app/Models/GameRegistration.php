<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class GameRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'player_id',
        'availability_status',
        'registration_status',
        'registered_at',
        'response_deadline',
        'player_notes',
        'unavailability_reason',
        'is_late_registration',
        'trainer_notes',
        'confirmed_by_user_id',
        'confirmed_at',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'response_deadline' => 'datetime',
        'confirmed_at' => 'datetime',
        'is_late_registration' => 'boolean',
    ];

    protected $dates = [
        'registered_at',
        'response_deadline',
        'confirmed_at',
    ];

    // Relationships
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
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
    public function scopeAvailable($query)
    {
        return $query->where('availability_status', 'available');
    }

    public function scopeUnavailable($query)
    {
        return $query->where('availability_status', 'unavailable');
    }

    public function scopeMaybe($query)
    {
        return $query->where('availability_status', 'maybe');
    }

    public function scopeInjured($query)
    {
        return $query->where('availability_status', 'injured');
    }

    public function scopePending($query)
    {
        return $query->where('registration_status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('registration_status', 'confirmed');
    }

    public function scopeDeclined($query)
    {
        return $query->where('registration_status', 'declined');
    }

    public function scopeCancelled($query)
    {
        return $query->where('registration_status', 'cancelled');
    }

    public function scopeByGame($query, int $gameId)
    {
        return $query->where('game_id', $gameId);
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
    public function availabilityStatusDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                $statuses = [
                    'available' => 'Verfügbar',
                    'unavailable' => 'Nicht verfügbar',
                    'maybe' => 'Unsicher',
                    'injured' => 'Verletzt',
                    'suspended' => 'Gesperrt',
                ];
                
                return $statuses[$this->availability_status] ?? $this->availability_status;
            },
        );
    }

    public function registrationStatusDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                $statuses = [
                    'pending' => 'Wartend',
                    'confirmed' => 'Bestätigt',
                    'declined' => 'Abgelehnt',
                    'cancelled' => 'Abgesagt',
                ];
                
                return $statuses[$this->registration_status] ?? $this->registration_status;
            },
        );
    }

    public function isAvailable(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->availability_status === 'available',
        );
    }

    public function isConfirmed(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->registration_status === 'confirmed',
        );
    }

    public function canChangeAvailability(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->game) {
                    return false;
                }
                
                return $this->game->isRegistrationOpen();
            },
        );
    }

    public function isLateRegistration(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->game) {
                    return false;
                }
                
                $deadline = $this->game->getRegistrationDeadline();
                return $this->registered_at->isAfter($deadline);
            },
        );
    }

    public function hoursUntilGame(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->game) {
                    return null;
                }
                
                return now()->diffInHours($this->game->scheduled_at, false);
            },
        );
    }

    public function hoursUntilDeadline(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->response_deadline) {
                    return null;
                }
                
                return now()->diffInHours($this->response_deadline, false);
            },
        );
    }

    // Helper Methods
    public function confirm(?int $confirmedByUserId = null, ?string $notes = null): bool
    {
        if ($this->registration_status === 'confirmed') {
            return true;
        }

        if (!in_array($this->registration_status, ['pending'])) {
            return false;
        }

        $this->update([
            'registration_status' => 'confirmed',
            'confirmed_at' => now(),
            'confirmed_by_user_id' => $confirmedByUserId,
            'trainer_notes' => $notes ? ($this->trainer_notes ? $this->trainer_notes . "\n\n" . $notes : $notes) : $this->trainer_notes,
        ]);

        return true;
    }

    public function decline(?int $declinedByUserId = null, ?string $reason = null): bool
    {
        if (!in_array($this->registration_status, ['pending', 'confirmed'])) {
            return false;
        }

        $this->update([
            'registration_status' => 'declined',
            'confirmed_by_user_id' => $declinedByUserId,
            'trainer_notes' => $reason ? ($this->trainer_notes ? $this->trainer_notes . "\n\nDeclined: " . $reason : "Declined: " . $reason) : $this->trainer_notes,
        ]);

        return true;
    }

    public function updateAvailability(string $status, ?string $reason = null): bool
    {
        if (!$this->can_change_availability) {
            return false;
        }

        $validStatuses = ['available', 'unavailable', 'maybe', 'injured', 'suspended'];
        if (!in_array($status, $validStatuses)) {
            return false;
        }

        $updateData = ['availability_status' => $status];
        
        if ($status === 'unavailable' && $reason) {
            $updateData['unavailability_reason'] = $reason;
        }

        // If player becomes unavailable, decline their registration
        if ($status === 'unavailable' && $this->registration_status === 'confirmed') {
            $updateData['registration_status'] = 'cancelled';
        }

        $this->update($updateData);

        return true;
    }

    public function addPlayerNotes(string $notes): void
    {
        $existingNotes = $this->player_notes;
        $this->update([
            'player_notes' => $existingNotes 
                ? $existingNotes . "\n\n" . now()->format('H:i') . ": " . $notes
                : now()->format('H:i') . ": " . $notes,
        ]);
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

    public function getRegistrationSummary(): array
    {
        return [
            'id' => $this->id,
            'availability_status' => $this->availability_status_display,
            'registration_status' => $this->registration_status_display,
            'player_name' => $this->player->full_name ?? 'Unknown',
            'registered_at' => $this->registered_at->format('d.m.Y H:i'),
            'is_late' => $this->is_late_registration,
            'is_available' => $this->is_available,
            'is_confirmed' => $this->is_confirmed,
            'can_change' => $this->can_change_availability,
            'hours_until_game' => $this->hours_until_game,
            'hours_until_deadline' => $this->hours_until_deadline,
            'unavailability_reason' => $this->unavailability_reason,
            'player_notes' => $this->player_notes,
            'trainer_notes' => $this->trainer_notes,
        ];
    }

    // Static methods
    public static function createRegistration(
        int $gameId, 
        int $playerId, 
        string $availabilityStatus = 'available',
        ?string $notes = null
    ): self {
        $game = Game::findOrFail($gameId);
        
        // Check if registration is still open
        if (!$game->isRegistrationOpen()) {
            throw new \Exception('Anmeldefrist für dieses Spiel ist bereits abgelaufen.');
        }

        // Check if player is already registered
        if (self::where('game_id', $gameId)
               ->where('player_id', $playerId)
               ->exists()) {
            throw new \Exception('Spieler ist bereits für dieses Spiel angemeldet.');
        }

        $isLate = now()->isAfter($game->getRegistrationDeadline());

        // Set response deadline
        $responseDeadline = $game->getLineupDeadline();

        return self::create([
            'game_id' => $gameId,
            'player_id' => $playerId,
            'availability_status' => $availabilityStatus,
            'registration_status' => 'pending',
            'registered_at' => now(),
            'response_deadline' => $responseDeadline,
            'player_notes' => $notes,
            'is_late_registration' => $isLate,
        ]);
    }
}