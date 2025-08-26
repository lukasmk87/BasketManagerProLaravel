<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class GameParticipation extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'player_id',
        'role',
        'participation_status',
        'jersey_number',
        'playing_position',
        'entered_game_at',
        'left_game_at',
        'minutes_played',
        'coach_notes',
        'performance_notes',
        'selected_by_user_id',
        'selected_at',
    ];

    protected $casts = [
        'entered_game_at' => 'datetime',
        'left_game_at' => 'datetime',
        'selected_at' => 'datetime',
        'minutes_played' => 'integer',
        'jersey_number' => 'integer',
    ];

    protected $dates = [
        'entered_game_at',
        'left_game_at',
        'selected_at',
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

    public function selectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'selected_by_user_id');
    }

    // Scopes
    public function scopeStarters($query)
    {
        return $query->where('role', 'starter');
    }

    public function scopeSubstitutes($query)
    {
        return $query->where('role', 'substitute');
    }

    public function scopeReserves($query)
    {
        return $query->where('role', 'reserve');
    }

    public function scopeCaptains($query)
    {
        return $query->whereIn('role', ['captain', 'vice_captain']);
    }

    public function scopePlaying($query)
    {
        return $query->where('participation_status', 'playing');
    }

    public function scopeBenched($query)
    {
        return $query->where('participation_status', 'benched');
    }

    public function scopeByGame($query, int $gameId)
    {
        return $query->where('game_id', $gameId);
    }

    public function scopeByPlayer($query, int $playerId)
    {
        return $query->where('player_id', $playerId);
    }

    public function scopeByPosition($query, string $position)
    {
        return $query->where('playing_position', $position);
    }

    public function scopeOrderByRole($query)
    {
        return $query->orderByRaw("FIELD(role, 'captain', 'vice_captain', 'starter', 'substitute', 'reserve')");
    }

    // Accessors & Mutators
    public function roleDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                $roles = [
                    'starter' => 'Startspieler',
                    'substitute' => 'Ersatzspieler',
                    'reserve' => 'Reserve',
                    'captain' => 'Kapitän',
                    'vice_captain' => 'Vize-Kapitän',
                ];
                
                return $roles[$this->role] ?? $this->role;
            },
        );
    }

    public function participationStatusDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                $statuses = [
                    'selected' => 'Ausgewählt',
                    'playing' => 'Spielt',
                    'benched' => 'Auf Bank',
                    'injured' => 'Verletzt',
                    'ejected' => 'Rausgeschmissen',
                    'substituted_in' => 'Eingewechselt',
                    'substituted_out' => 'Ausgewechselt',
                ];
                
                return $statuses[$this->participation_status] ?? $this->participation_status;
            },
        );
    }

    public function positionDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                $positions = [
                    'PG' => 'Point Guard',
                    'SG' => 'Shooting Guard',
                    'SF' => 'Small Forward',
                    'PF' => 'Power Forward',
                    'C' => 'Center',
                    'G' => 'Guard',
                    'F' => 'Forward',
                    'UTIL' => 'Utility Player',
                ];
                
                return $positions[$this->playing_position] ?? $this->playing_position;
            },
        );
    }

    public function isStarter(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->role === 'starter',
        );
    }

    public function isCaptain(): Attribute
    {
        return Attribute::make(
            get: fn() => in_array($this->role, ['captain', 'vice_captain']),
        );
    }

    public function isPlaying(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->participation_status === 'playing',
        );
    }

    public function isOnBench(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->participation_status === 'benched',
        );
    }

    public function totalPlayingTime(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->entered_game_at && $this->left_game_at) {
                    return $this->entered_game_at->diffInMinutes($this->left_game_at);
                }
                
                if ($this->entered_game_at && $this->participation_status === 'playing') {
                    return $this->entered_game_at->diffInMinutes(now());
                }
                
                return $this->minutes_played;
            },
        );
    }

    public function displayName(): Attribute
    {
        return Attribute::make(
            get: function () {
                $playerName = $this->player->display_name ?? $this->player->full_name ?? 'Unknown';
                $jersey = $this->jersey_number ? "#{$this->jersey_number}" : '';
                
                return trim("{$playerName} {$jersey}");
            },
        );
    }

    // Helper Methods
    public function enterGame(): bool
    {
        if ($this->participation_status === 'playing') {
            return true;
        }

        if (!in_array($this->participation_status, ['selected', 'benched'])) {
            return false;
        }

        $this->update([
            'participation_status' => 'playing',
            'entered_game_at' => now(),
        ]);

        return true;
    }

    public function exitGame(): bool
    {
        if (!$this->is_playing) {
            return false;
        }

        $playingTime = $this->entered_game_at ? $this->entered_game_at->diffInMinutes(now()) : 0;

        $this->update([
            'participation_status' => 'benched',
            'left_game_at' => now(),
            'minutes_played' => $this->minutes_played + $playingTime,
        ]);

        return true;
    }

    public function substituteIn(): bool
    {
        $this->update([
            'participation_status' => 'substituted_in',
            'entered_game_at' => now(),
        ]);

        return true;
    }

    public function substituteOut(): bool
    {
        if (!$this->is_playing) {
            return false;
        }

        $playingTime = $this->entered_game_at ? $this->entered_game_at->diffInMinutes(now()) : 0;

        $this->update([
            'participation_status' => 'substituted_out',
            'left_game_at' => now(),
            'minutes_played' => $this->minutes_played + $playingTime,
        ]);

        return true;
    }

    public function markInjured(?string $notes = null): void
    {
        $updateData = ['participation_status' => 'injured'];
        
        if ($this->is_playing && $this->entered_game_at) {
            $playingTime = $this->entered_game_at->diffInMinutes(now());
            $updateData['left_game_at'] = now();
            $updateData['minutes_played'] = $this->minutes_played + $playingTime;
        }

        if ($notes) {
            $updateData['coach_notes'] = $this->coach_notes ? $this->coach_notes . "\n\nInjury: " . $notes : "Injury: " . $notes;
        }

        $this->update($updateData);
    }

    public function eject(?string $reason = null): void
    {
        $updateData = ['participation_status' => 'ejected'];
        
        if ($this->is_playing && $this->entered_game_at) {
            $playingTime = $this->entered_game_at->diffInMinutes(now());
            $updateData['left_game_at'] = now();
            $updateData['minutes_played'] = $this->minutes_played + $playingTime;
        }

        if ($reason) {
            $updateData['coach_notes'] = $this->coach_notes ? $this->coach_notes . "\n\nEjection: " . $reason : "Ejection: " . $reason;
        }

        $this->update($updateData);
    }

    public function changeRole(string $newRole): bool
    {
        $validRoles = ['starter', 'substitute', 'reserve', 'captain', 'vice_captain'];
        if (!in_array($newRole, $validRoles)) {
            return false;
        }

        $this->update(['role' => $newRole]);
        return true;
    }

    public function assignJersey(int $number): bool
    {
        // Check if jersey number is already taken in this game
        $existing = GameParticipation::where('game_id', $this->game_id)
            ->where('jersey_number', $number)
            ->where('id', '!=', $this->id)
            ->exists();

        if ($existing) {
            return false;
        }

        $this->update(['jersey_number' => $number]);
        return true;
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

    public function addPerformanceNotes(string $notes): void
    {
        $existingNotes = $this->performance_notes;
        $this->update([
            'performance_notes' => $existingNotes 
                ? $existingNotes . "\n\n" . now()->format('H:i') . ": " . $notes
                : now()->format('H:i') . ": " . $notes,
        ]);
    }

    public function getParticipationSummary(): array
    {
        return [
            'id' => $this->id,
            'player_name' => $this->display_name,
            'role' => $this->role_display,
            'status' => $this->participation_status_display,
            'position' => $this->position_display,
            'jersey_number' => $this->jersey_number,
            'is_starter' => $this->is_starter,
            'is_captain' => $this->is_captain,
            'is_playing' => $this->is_playing,
            'minutes_played' => $this->total_playing_time,
            'entered_at' => $this->entered_game_at?->format('H:i'),
            'left_at' => $this->left_game_at?->format('H:i'),
            'coach_notes' => $this->coach_notes,
            'performance_notes' => $this->performance_notes,
        ];
    }

    // Static methods
    public static function createParticipation(
        int $gameId,
        int $playerId,
        string $role = 'substitute',
        ?int $jerseyNumber = null,
        ?string $position = null
    ): self {
        // Check if player is already participating in this game
        if (self::where('game_id', $gameId)->where('player_id', $playerId)->exists()) {
            throw new \Exception('Spieler ist bereits für dieses Spiel aufgestellt.');
        }

        // Check jersey number availability
        if ($jerseyNumber && self::where('game_id', $gameId)->where('jersey_number', $jerseyNumber)->exists()) {
            throw new \Exception("Trikotnummer {$jerseyNumber} ist bereits vergeben.");
        }

        return self::create([
            'game_id' => $gameId,
            'player_id' => $playerId,
            'role' => $role,
            'participation_status' => 'selected',
            'jersey_number' => $jerseyNumber,
            'playing_position' => $position,
            'selected_at' => now(),
        ]);
    }
}