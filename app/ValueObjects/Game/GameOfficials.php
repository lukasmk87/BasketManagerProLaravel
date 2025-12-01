<?php

namespace App\ValueObjects\Game;

/**
 * Value Object für Spieloffizielle.
 *
 * Kapselt alle Schiedsrichter- und Offizielle-bezogenen Daten eines Spiels.
 */
final class GameOfficials
{
    public function __construct(
        private readonly ?array $referees = null,
        private readonly ?array $scorekeepers = null,
        private readonly ?array $timekeepers = null,
        private readonly ?bool $medicalStaffPresent = null,
    ) {}

    // ============================
    // FACTORY METHODS
    // ============================

    public static function create(
        ?array $referees = null,
        ?array $scorekeepers = null,
        ?array $timekeepers = null,
    ): self {
        return new self($referees, $scorekeepers, $timekeepers);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            referees: $data['referees'] ?? null,
            scorekeepers: $data['scorekeepers'] ?? null,
            timekeepers: $data['timekeepers'] ?? null,
            medicalStaffPresent: $data['medical_staff_present'] ?? null,
        );
    }

    // ============================
    // ACCESSORS
    // ============================

    public function referees(): ?array
    {
        return $this->referees;
    }

    public function scorekeepers(): ?array
    {
        return $this->scorekeepers;
    }

    public function timekeepers(): ?array
    {
        return $this->timekeepers;
    }

    public function isMedicalStaffPresent(): ?bool
    {
        return $this->medicalStaffPresent;
    }

    // ============================
    // CALCULATED PROPERTIES
    // ============================

    public function hasReferees(): bool
    {
        return !empty($this->referees);
    }

    public function hasScorekeepers(): bool
    {
        return !empty($this->scorekeepers);
    }

    public function hasTimekeepers(): bool
    {
        return !empty($this->timekeepers);
    }

    public function refereeCount(): int
    {
        return count($this->referees ?? []);
    }

    public function scorekeeperCount(): int
    {
        return count($this->scorekeepers ?? []);
    }

    public function timekeeperCount(): int
    {
        return count($this->timekeepers ?? []);
    }

    public function totalOfficialsCount(): int
    {
        return $this->refereeCount() + $this->scorekeeperCount() + $this->timekeeperCount();
    }

    public function isFullyStaffed(int $minReferees = 2, int $minScorekeepers = 1): bool
    {
        return $this->refereeCount() >= $minReferees && $this->scorekeeperCount() >= $minScorekeepers;
    }

    public function getPrimaryReferee(): ?array
    {
        if (empty($this->referees)) {
            return null;
        }

        foreach ($this->referees as $referee) {
            if (is_array($referee) && ($referee['is_primary'] ?? false)) {
                return $referee;
            }
        }

        return $this->referees[0] ?? null;
    }

    public function getRefereeNames(): array
    {
        if (empty($this->referees)) {
            return [];
        }

        return array_map(function ($referee) {
            if (is_array($referee)) {
                return $referee['name'] ?? $referee['full_name'] ?? 'Unknown';
            }
            return (string) $referee;
        }, $this->referees);
    }

    // ============================
    // IMMUTABLE OPERATIONS
    // ============================

    public function withReferee(array $referee): self
    {
        $referees = $this->referees ?? [];
        $referees[] = $referee;

        return new self(
            $referees,
            $this->scorekeepers,
            $this->timekeepers,
            $this->medicalStaffPresent,
        );
    }

    public function withScorekeeper(array $scorekeeper): self
    {
        $scorekeepers = $this->scorekeepers ?? [];
        $scorekeepers[] = $scorekeeper;

        return new self(
            $this->referees,
            $scorekeepers,
            $this->timekeepers,
            $this->medicalStaffPresent,
        );
    }

    public function withTimekeeper(array $timekeeper): self
    {
        $timekeepers = $this->timekeepers ?? [];
        $timekeepers[] = $timekeeper;

        return new self(
            $this->referees,
            $this->scorekeepers,
            $timekeepers,
            $this->medicalStaffPresent,
        );
    }

    public function withMedicalStaff(bool $present): self
    {
        return new self(
            $this->referees,
            $this->scorekeepers,
            $this->timekeepers,
            $present,
        );
    }

    // ============================
    // SERIALIZATION
    // ============================

    public function toArray(): array
    {
        return [
            'referees' => $this->referees,
            'scorekeepers' => $this->scorekeepers,
            'timekeepers' => $this->timekeepers,
            'medical_staff_present' => $this->medicalStaffPresent,
            'referee_count' => $this->refereeCount(),
            'scorekeeper_count' => $this->scorekeeperCount(),
            'timekeeper_count' => $this->timekeeperCount(),
            'is_fully_staffed' => $this->isFullyStaffed(),
        ];
    }
}
