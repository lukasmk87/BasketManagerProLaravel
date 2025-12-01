<?php

namespace App\ValueObjects\Game;

/**
 * Value Object für Fouls und Verstöße.
 *
 * Kapselt alle Foul-bezogenen Daten eines Spiels.
 */
final class GameFouls
{
    public function __construct(
        private readonly ?array $teamFouls = null,
        private readonly ?array $technicalFouls = null,
        private readonly ?array $ejections = null,
    ) {}

    // ============================
    // FACTORY METHODS
    // ============================

    public static function create(): self
    {
        return new self();
    }

    public static function fromArray(array $data): self
    {
        return new self(
            teamFouls: $data['team_fouls'] ?? null,
            technicalFouls: $data['technical_fouls'] ?? null,
            ejections: $data['ejections'] ?? null,
        );
    }

    public static function initial(): self
    {
        return new self(
            teamFouls: ['home' => 0, 'away' => 0],
            technicalFouls: [],
            ejections: [],
        );
    }

    // ============================
    // ACCESSORS
    // ============================

    public function teamFouls(): ?array
    {
        return $this->teamFouls;
    }

    public function technicalFouls(): ?array
    {
        return $this->technicalFouls;
    }

    public function ejections(): ?array
    {
        return $this->ejections;
    }

    // ============================
    // CALCULATED PROPERTIES
    // ============================

    public function getTeamFoulCount(string $teamSide): int
    {
        if (!$this->teamFouls) {
            return 0;
        }

        return $this->teamFouls[$teamSide] ?? 0;
    }

    public function homeFouls(): int
    {
        return $this->getTeamFoulCount('home');
    }

    public function awayFouls(): int
    {
        return $this->getTeamFoulCount('away');
    }

    public function totalFouls(): int
    {
        return $this->homeFouls() + $this->awayFouls();
    }

    public function technicalFoulCount(): int
    {
        return count($this->technicalFouls ?? []);
    }

    public function ejectionCount(): int
    {
        return count($this->ejections ?? []);
    }

    public function isInBonus(string $teamSide, int $bonusThreshold = 5): bool
    {
        $opponentSide = $teamSide === 'home' ? 'away' : 'home';
        return $this->getTeamFoulCount($opponentSide) >= $bonusThreshold;
    }

    public function isHomeInBonus(int $bonusThreshold = 5): bool
    {
        return $this->isInBonus('home', $bonusThreshold);
    }

    public function isAwayInBonus(int $bonusThreshold = 5): bool
    {
        return $this->isInBonus('away', $bonusThreshold);
    }

    public function getTechnicalFoulsForTeam(string $teamSide): array
    {
        if (empty($this->technicalFouls)) {
            return [];
        }

        return array_filter($this->technicalFouls, function ($foul) use ($teamSide) {
            return ($foul['team'] ?? $foul['team_side'] ?? null) === $teamSide;
        });
    }

    public function getTechnicalFoulsForPlayer(int $playerId): array
    {
        if (empty($this->technicalFouls)) {
            return [];
        }

        return array_filter($this->technicalFouls, function ($foul) use ($playerId) {
            return ($foul['player_id'] ?? null) === $playerId;
        });
    }

    public function getEjectionsForTeam(string $teamSide): array
    {
        if (empty($this->ejections)) {
            return [];
        }

        return array_filter($this->ejections, function ($ejection) use ($teamSide) {
            return ($ejection['team'] ?? $ejection['team_side'] ?? null) === $teamSide;
        });
    }

    public function isPlayerEjected(int $playerId): bool
    {
        if (empty($this->ejections)) {
            return false;
        }

        foreach ($this->ejections as $ejection) {
            if (($ejection['player_id'] ?? null) === $playerId) {
                return true;
            }
        }

        return false;
    }

    public function hasAnyEjections(): bool
    {
        return !empty($this->ejections);
    }

    public function hasAnyTechnicalFouls(): bool
    {
        return !empty($this->technicalFouls);
    }

    // ============================
    // IMMUTABLE OPERATIONS
    // ============================

    public function withTeamFoul(string $teamSide): self
    {
        $teamFouls = $this->teamFouls ?? ['home' => 0, 'away' => 0];
        $teamFouls[$teamSide] = ($teamFouls[$teamSide] ?? 0) + 1;

        return new self(
            $teamFouls,
            $this->technicalFouls,
            $this->ejections,
        );
    }

    public function withTechnicalFoul(array $foul): self
    {
        $technicalFouls = $this->technicalFouls ?? [];
        $technicalFouls[] = $foul;

        return new self(
            $this->teamFouls,
            $technicalFouls,
            $this->ejections,
        );
    }

    public function withEjection(array $ejection): self
    {
        $ejections = $this->ejections ?? [];
        $ejections[] = $ejection;

        return new self(
            $this->teamFouls,
            $this->technicalFouls,
            $ejections,
        );
    }

    public function withResetForPeriod(): self
    {
        // Team fouls typically reset per quarter/half
        return new self(
            ['home' => 0, 'away' => 0],
            $this->technicalFouls,
            $this->ejections,
        );
    }

    // ============================
    // SERIALIZATION
    // ============================

    public function toArray(): array
    {
        return [
            'team_fouls' => $this->teamFouls,
            'technical_fouls' => $this->technicalFouls,
            'ejections' => $this->ejections,
            'home_fouls' => $this->homeFouls(),
            'away_fouls' => $this->awayFouls(),
            'technical_foul_count' => $this->technicalFoulCount(),
            'ejection_count' => $this->ejectionCount(),
            'home_in_bonus' => $this->isHomeInBonus(),
            'away_in_bonus' => $this->isAwayInBonus(),
        ];
    }
}
