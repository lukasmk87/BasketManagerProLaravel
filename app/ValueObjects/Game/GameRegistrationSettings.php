<?php

namespace App\ValueObjects\Game;

use Carbon\Carbon;

/**
 * Value Object für Spieler-Registrierungs-Einstellungen.
 *
 * Kapselt alle Registrierungs-bezogenen Einstellungen eines Spiels.
 */
final class GameRegistrationSettings
{
    public function __construct(
        private readonly ?int $registrationDeadlineHours = 24,
        private readonly ?int $lineupDeadlineHours = 2,
        private readonly ?int $maxRosterSize = null,
        private readonly ?int $minRosterSize = null,
        private readonly bool $allowPlayerRegistrations = true,
        private readonly bool $autoConfirmRegistrations = false,
    ) {}

    // ============================
    // FACTORY METHODS
    // ============================

    public static function create(
        int $registrationDeadlineHours = 24,
        int $lineupDeadlineHours = 2,
    ): self {
        return new self($registrationDeadlineHours, $lineupDeadlineHours);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            registrationDeadlineHours: $data['registration_deadline_hours'] ?? 24,
            lineupDeadlineHours: $data['lineup_deadline_hours'] ?? 2,
            maxRosterSize: $data['max_roster_size'] ?? null,
            minRosterSize: $data['min_roster_size'] ?? null,
            allowPlayerRegistrations: $data['allow_player_registrations'] ?? true,
            autoConfirmRegistrations: $data['auto_confirm_registrations'] ?? false,
        );
    }

    public static function defaults(): self
    {
        return new self(
            registrationDeadlineHours: 24,
            lineupDeadlineHours: 2,
            maxRosterSize: 15,
            minRosterSize: 5,
            allowPlayerRegistrations: true,
            autoConfirmRegistrations: false,
        );
    }

    // ============================
    // ACCESSORS
    // ============================

    public function registrationDeadlineHours(): ?int
    {
        return $this->registrationDeadlineHours;
    }

    public function lineupDeadlineHours(): ?int
    {
        return $this->lineupDeadlineHours;
    }

    public function maxRosterSize(): ?int
    {
        return $this->maxRosterSize;
    }

    public function minRosterSize(): ?int
    {
        return $this->minRosterSize;
    }

    public function allowPlayerRegistrations(): bool
    {
        return $this->allowPlayerRegistrations;
    }

    public function autoConfirmRegistrations(): bool
    {
        return $this->autoConfirmRegistrations;
    }

    // ============================
    // DEADLINE CALCULATIONS
    // ============================

    public function getRegistrationDeadline(Carbon $scheduledAt): Carbon
    {
        return $scheduledAt->copy()->subHours($this->registrationDeadlineHours ?? 24);
    }

    public function getLineupDeadline(Carbon $scheduledAt): Carbon
    {
        return $scheduledAt->copy()->subHours($this->lineupDeadlineHours ?? 2);
    }

    public function isRegistrationOpen(Carbon $scheduledAt): bool
    {
        if (!$this->allowPlayerRegistrations) {
            return false;
        }

        return now()->isBefore($this->getRegistrationDeadline($scheduledAt));
    }

    public function isLineupChangesAllowed(Carbon $scheduledAt): bool
    {
        return now()->isBefore($this->getLineupDeadline($scheduledAt));
    }

    public function hoursUntilRegistrationDeadline(Carbon $scheduledAt): float
    {
        $deadline = $this->getRegistrationDeadline($scheduledAt);
        return round(now()->diffInHours($deadline, false), 1);
    }

    public function hoursUntilLineupDeadline(Carbon $scheduledAt): float
    {
        $deadline = $this->getLineupDeadline($scheduledAt);
        return round(now()->diffInHours($deadline, false), 1);
    }

    // ============================
    // ROSTER VALIDATION
    // ============================

    public function hasRosterLimits(): bool
    {
        return $this->maxRosterSize !== null || $this->minRosterSize !== null;
    }

    public function isValidRosterSize(int $size): bool
    {
        if ($this->minRosterSize !== null && $size < $this->minRosterSize) {
            return false;
        }

        if ($this->maxRosterSize !== null && $size > $this->maxRosterSize) {
            return false;
        }

        return true;
    }

    public function hasRosterCapacity(int $currentSize, int $additionalPlayers = 1): bool
    {
        if ($this->maxRosterSize === null) {
            return true;
        }

        return ($currentSize + $additionalPlayers) <= $this->maxRosterSize;
    }

    public function getAvailableRosterSpots(int $currentSize): int
    {
        if ($this->maxRosterSize === null) {
            return PHP_INT_MAX;
        }

        return max(0, $this->maxRosterSize - $currentSize);
    }

    public function hasMinimumRoster(int $currentSize): bool
    {
        if ($this->minRosterSize === null) {
            return true;
        }

        return $currentSize >= $this->minRosterSize;
    }

    public function spotsNeededForMinimum(int $currentSize): int
    {
        if ($this->minRosterSize === null) {
            return 0;
        }

        return max(0, $this->minRosterSize - $currentSize);
    }

    // ============================
    // IMMUTABLE OPERATIONS
    // ============================

    public function withRosterLimits(?int $min, ?int $max): self
    {
        return new self(
            $this->registrationDeadlineHours,
            $this->lineupDeadlineHours,
            $max,
            $min,
            $this->allowPlayerRegistrations,
            $this->autoConfirmRegistrations,
        );
    }

    public function withDeadlines(int $registrationHours, int $lineupHours): self
    {
        return new self(
            $registrationHours,
            $lineupHours,
            $this->maxRosterSize,
            $this->minRosterSize,
            $this->allowPlayerRegistrations,
            $this->autoConfirmRegistrations,
        );
    }

    public function withRegistrationsEnabled(bool $enabled): self
    {
        return new self(
            $this->registrationDeadlineHours,
            $this->lineupDeadlineHours,
            $this->maxRosterSize,
            $this->minRosterSize,
            $enabled,
            $this->autoConfirmRegistrations,
        );
    }

    public function withAutoConfirm(bool $autoConfirm): self
    {
        return new self(
            $this->registrationDeadlineHours,
            $this->lineupDeadlineHours,
            $this->maxRosterSize,
            $this->minRosterSize,
            $this->allowPlayerRegistrations,
            $autoConfirm,
        );
    }

    // ============================
    // SERIALIZATION
    // ============================

    public function toArray(): array
    {
        return [
            'registration_deadline_hours' => $this->registrationDeadlineHours,
            'lineup_deadline_hours' => $this->lineupDeadlineHours,
            'max_roster_size' => $this->maxRosterSize,
            'min_roster_size' => $this->minRosterSize,
            'allow_player_registrations' => $this->allowPlayerRegistrations,
            'auto_confirm_registrations' => $this->autoConfirmRegistrations,
        ];
    }
}
