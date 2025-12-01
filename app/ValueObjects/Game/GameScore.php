<?php

namespace App\ValueObjects\Game;

/**
 * Value Object für Spielstand.
 *
 * Kapselt alle Score-bezogenen Daten eines Spiels.
 */
final class GameScore
{
    public function __construct(
        private readonly int $homeTeamScore = 0,
        private readonly int $awayTeamScore = 0,
        private readonly ?array $periodScores = null,
    ) {}

    // ============================
    // FACTORY METHODS
    // ============================

    public static function create(int $homeScore = 0, int $awayScore = 0, ?array $periodScores = null): self
    {
        return new self($homeScore, $awayScore, $periodScores);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            homeTeamScore: $data['home_team_score'] ?? 0,
            awayTeamScore: $data['away_team_score'] ?? 0,
            periodScores: $data['period_scores'] ?? null,
        );
    }

    // ============================
    // ACCESSORS
    // ============================

    public function homeTeamScore(): int
    {
        return $this->homeTeamScore;
    }

    public function awayTeamScore(): int
    {
        return $this->awayTeamScore;
    }

    public function periodScores(): ?array
    {
        return $this->periodScores;
    }

    // ============================
    // CALCULATED PROPERTIES
    // ============================

    public function totalScore(): int
    {
        return $this->homeTeamScore + $this->awayTeamScore;
    }

    public function pointDifferential(): int
    {
        return abs($this->homeTeamScore - $this->awayTeamScore);
    }

    public function isHomeLeading(): bool
    {
        return $this->homeTeamScore > $this->awayTeamScore;
    }

    public function isAwayLeading(): bool
    {
        return $this->awayTeamScore > $this->homeTeamScore;
    }

    public function isTied(): bool
    {
        return $this->homeTeamScore === $this->awayTeamScore;
    }

    public function leadingTeamSide(): ?string
    {
        if ($this->isHomeLeading()) {
            return 'home';
        }
        if ($this->isAwayLeading()) {
            return 'away';
        }
        return null;
    }

    public function getScoreForPeriod(int $period): ?array
    {
        if (!$this->periodScores || !isset($this->periodScores[$period])) {
            return null;
        }

        return $this->periodScores[$period];
    }

    // ============================
    // IMMUTABLE OPERATIONS
    // ============================

    public function withAddedHomeScore(int $points): self
    {
        return new self(
            $this->homeTeamScore + $points,
            $this->awayTeamScore,
            $this->periodScores
        );
    }

    public function withAddedAwayScore(int $points): self
    {
        return new self(
            $this->homeTeamScore,
            $this->awayTeamScore + $points,
            $this->periodScores
        );
    }

    public function withPeriodScore(int $period, int $homeScore, int $awayScore): self
    {
        $periodScores = $this->periodScores ?? [];
        $periodScores[$period] = [
            'home' => $homeScore,
            'away' => $awayScore,
        ];

        return new self(
            $this->homeTeamScore,
            $this->awayTeamScore,
            $periodScores
        );
    }

    // ============================
    // FORMATTING
    // ============================

    public function formatted(): string
    {
        return "{$this->homeTeamScore} : {$this->awayTeamScore}";
    }

    public function formattedReversed(): string
    {
        return "{$this->awayTeamScore} : {$this->homeTeamScore}";
    }

    // ============================
    // SERIALIZATION
    // ============================

    public function toArray(): array
    {
        return [
            'home_team_score' => $this->homeTeamScore,
            'away_team_score' => $this->awayTeamScore,
            'period_scores' => $this->periodScores,
            'total_score' => $this->totalScore(),
            'point_differential' => $this->pointDifferential(),
            'is_tied' => $this->isTied(),
        ];
    }
}
