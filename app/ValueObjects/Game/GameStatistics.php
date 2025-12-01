<?php

namespace App\ValueObjects\Game;

/**
 * Value Object für Spielstatistiken.
 *
 * Kapselt alle Statistik-bezogenen Daten eines Spiels.
 */
final class GameStatistics
{
    public function __construct(
        private readonly ?array $teamStats = null,
        private readonly ?array $playerStats = null,
        private readonly ?array $playByPlay = null,
        private readonly ?array $substitutions = null,
        private readonly ?array $timeouts = null,
        private readonly ?string $liveCommentary = null,
        private readonly ?bool $statsVerified = false,
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
            teamStats: $data['team_stats'] ?? null,
            playerStats: $data['player_stats'] ?? null,
            playByPlay: $data['play_by_play'] ?? null,
            substitutions: $data['substitutions'] ?? null,
            timeouts: $data['timeouts'] ?? null,
            liveCommentary: $data['live_commentary'] ?? null,
            statsVerified: $data['stats_verified'] ?? false,
        );
    }

    // ============================
    // ACCESSORS
    // ============================

    public function teamStats(): ?array
    {
        return $this->teamStats;
    }

    public function playerStats(): ?array
    {
        return $this->playerStats;
    }

    public function playByPlay(): ?array
    {
        return $this->playByPlay;
    }

    public function substitutions(): ?array
    {
        return $this->substitutions;
    }

    public function timeouts(): ?array
    {
        return $this->timeouts;
    }

    public function liveCommentary(): ?string
    {
        return $this->liveCommentary;
    }

    public function isStatsVerified(): bool
    {
        return $this->statsVerified ?? false;
    }

    // ============================
    // CALCULATED PROPERTIES
    // ============================

    public function hasTeamStats(): bool
    {
        return !empty($this->teamStats);
    }

    public function hasPlayerStats(): bool
    {
        return !empty($this->playerStats);
    }

    public function hasPlayByPlay(): bool
    {
        return !empty($this->playByPlay);
    }

    public function playByPlayCount(): int
    {
        return count($this->playByPlay ?? []);
    }

    public function substitutionCount(): int
    {
        return count($this->substitutions ?? []);
    }

    public function timeoutCount(): int
    {
        return count($this->timeouts ?? []);
    }

    public function getTeamStat(string $teamSide, string $statKey, mixed $default = null): mixed
    {
        if (!$this->teamStats) {
            return $default;
        }

        return $this->teamStats[$teamSide][$statKey] ?? $default;
    }

    public function getPlayerStat(int $playerId, string $statKey, mixed $default = null): mixed
    {
        if (!$this->playerStats) {
            return $default;
        }

        return $this->playerStats[$playerId][$statKey] ?? $default;
    }

    public function getLatestPlayByPlayEvents(int $count = 10): array
    {
        if (empty($this->playByPlay)) {
            return [];
        }

        return array_slice($this->playByPlay, -$count);
    }

    public function getPlayByPlayForPeriod(int $period): array
    {
        if (empty($this->playByPlay)) {
            return [];
        }

        return array_filter($this->playByPlay, function ($event) use ($period) {
            return ($event['period'] ?? null) === $period;
        });
    }

    public function getTimeoutsForTeam(string $teamSide): array
    {
        if (empty($this->timeouts)) {
            return [];
        }

        return array_filter($this->timeouts, function ($timeout) use ($teamSide) {
            return ($timeout['team'] ?? $timeout['team_side'] ?? null) === $teamSide;
        });
    }

    public function getTimeoutsRemainingForTeam(string $teamSide, int $maxTimeouts = 5): int
    {
        $used = count($this->getTimeoutsForTeam($teamSide));
        return max(0, $maxTimeouts - $used);
    }

    // ============================
    // IMMUTABLE OPERATIONS
    // ============================

    public function withPlayByPlayEvent(array $event): self
    {
        $playByPlay = $this->playByPlay ?? [];
        $playByPlay[] = $event;

        return new self(
            $this->teamStats,
            $this->playerStats,
            $playByPlay,
            $this->substitutions,
            $this->timeouts,
            $this->liveCommentary,
            $this->statsVerified,
        );
    }

    public function withSubstitution(array $substitution): self
    {
        $substitutions = $this->substitutions ?? [];
        $substitutions[] = $substitution;

        return new self(
            $this->teamStats,
            $this->playerStats,
            $this->playByPlay,
            $substitutions,
            $this->timeouts,
            $this->liveCommentary,
            $this->statsVerified,
        );
    }

    public function withTimeout(array $timeout): self
    {
        $timeouts = $this->timeouts ?? [];
        $timeouts[] = $timeout;

        return new self(
            $this->teamStats,
            $this->playerStats,
            $this->playByPlay,
            $this->substitutions,
            $timeouts,
            $this->liveCommentary,
            $this->statsVerified,
        );
    }

    public function withTeamStats(array $teamStats): self
    {
        return new self(
            $teamStats,
            $this->playerStats,
            $this->playByPlay,
            $this->substitutions,
            $this->timeouts,
            $this->liveCommentary,
            $this->statsVerified,
        );
    }

    public function withPlayerStats(array $playerStats): self
    {
        return new self(
            $this->teamStats,
            $playerStats,
            $this->playByPlay,
            $this->substitutions,
            $this->timeouts,
            $this->liveCommentary,
            $this->statsVerified,
        );
    }

    public function withVerified(bool $verified = true): self
    {
        return new self(
            $this->teamStats,
            $this->playerStats,
            $this->playByPlay,
            $this->substitutions,
            $this->timeouts,
            $this->liveCommentary,
            $verified,
        );
    }

    // ============================
    // SERIALIZATION
    // ============================

    public function toArray(): array
    {
        return [
            'team_stats' => $this->teamStats,
            'player_stats' => $this->playerStats,
            'play_by_play' => $this->playByPlay,
            'substitutions' => $this->substitutions,
            'timeouts' => $this->timeouts,
            'live_commentary' => $this->liveCommentary,
            'stats_verified' => $this->statsVerified,
            'play_by_play_count' => $this->playByPlayCount(),
            'substitution_count' => $this->substitutionCount(),
        ];
    }
}
