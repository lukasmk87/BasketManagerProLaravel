<?php

namespace App\ValueObjects\Game;

/**
 * Value Object für Spielregeln.
 *
 * Kapselt alle Regeln-bezogenen Daten eines Spiels.
 */
final class GameRules
{
    public function __construct(
        private readonly ?array $rules = null,
        private readonly int $periodLengthMinutes = 10,
        private readonly int $totalPeriods = 4,
        private readonly int $overtimeLengthMinutes = 5,
        private readonly ?int $maxRosterSize = null,
        private readonly ?int $minRosterSize = null,
        private readonly ?bool $allowSpectators = true,
        private readonly ?bool $allowMedia = true,
        private readonly ?bool $allowRecording = false,
        private readonly ?bool $allowPhotos = true,
        private readonly ?bool $allowStreaming = false,
    ) {}

    // ============================
    // FACTORY METHODS
    // ============================

    public static function create(
        int $periodLengthMinutes = 10,
        int $totalPeriods = 4,
        int $overtimeLengthMinutes = 5,
    ): self {
        return new self(
            periodLengthMinutes: $periodLengthMinutes,
            totalPeriods: $totalPeriods,
            overtimeLengthMinutes: $overtimeLengthMinutes,
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            rules: $data['game_rules'] ?? $data['rules'] ?? null,
            periodLengthMinutes: $data['period_length_minutes'] ?? 10,
            totalPeriods: $data['total_periods'] ?? 4,
            overtimeLengthMinutes: $data['overtime_length_minutes'] ?? 5,
            maxRosterSize: $data['max_roster_size'] ?? null,
            minRosterSize: $data['min_roster_size'] ?? null,
            allowSpectators: $data['allow_spectators'] ?? true,
            allowMedia: $data['allow_media'] ?? true,
            allowRecording: $data['allow_recording'] ?? false,
            allowPhotos: $data['allow_photos'] ?? true,
            allowStreaming: $data['allow_streaming'] ?? false,
        );
    }

    public static function forFIBA(): self
    {
        return new self(
            periodLengthMinutes: 10,
            totalPeriods: 4,
            overtimeLengthMinutes: 5,
        );
    }

    public static function forNBA(): self
    {
        return new self(
            periodLengthMinutes: 12,
            totalPeriods: 4,
            overtimeLengthMinutes: 5,
        );
    }

    public static function forCollege(): self
    {
        return new self(
            periodLengthMinutes: 20,
            totalPeriods: 2,
            overtimeLengthMinutes: 5,
        );
    }

    // ============================
    // ACCESSORS
    // ============================

    public function rules(): ?array
    {
        return $this->rules;
    }

    public function periodLengthMinutes(): int
    {
        return $this->periodLengthMinutes;
    }

    public function totalPeriods(): int
    {
        return $this->totalPeriods;
    }

    public function overtimeLengthMinutes(): int
    {
        return $this->overtimeLengthMinutes;
    }

    public function maxRosterSize(): ?int
    {
        return $this->maxRosterSize;
    }

    public function minRosterSize(): ?int
    {
        return $this->minRosterSize;
    }

    public function allowSpectators(): ?bool
    {
        return $this->allowSpectators;
    }

    public function allowMedia(): ?bool
    {
        return $this->allowMedia;
    }

    public function allowRecording(): ?bool
    {
        return $this->allowRecording;
    }

    public function allowPhotos(): ?bool
    {
        return $this->allowPhotos;
    }

    public function allowStreaming(): ?bool
    {
        return $this->allowStreaming;
    }

    // ============================
    // CALCULATED PROPERTIES
    // ============================

    public function totalRegulationMinutes(): int
    {
        return $this->periodLengthMinutes * $this->totalPeriods;
    }

    public function periodLengthSeconds(): int
    {
        return $this->periodLengthMinutes * 60;
    }

    public function overtimeLengthSeconds(): int
    {
        return $this->overtimeLengthMinutes * 60;
    }

    public function isHalfFormat(): bool
    {
        return $this->totalPeriods === 2;
    }

    public function isQuarterFormat(): bool
    {
        return $this->totalPeriods === 4;
    }

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

    public function getRule(string $key, mixed $default = null): mixed
    {
        if (!$this->rules) {
            return $default;
        }

        return $this->rules[$key] ?? $default;
    }

    public function formatDescription(): string
    {
        if ($this->isQuarterFormat()) {
            return "{$this->totalPeriods} x {$this->periodLengthMinutes} min Viertel";
        }

        return "{$this->totalPeriods} x {$this->periodLengthMinutes} min Halbzeiten";
    }

    // ============================
    // IMMUTABLE OPERATIONS
    // ============================

    public function withRosterLimits(?int $min, ?int $max): self
    {
        return new self(
            $this->rules,
            $this->periodLengthMinutes,
            $this->totalPeriods,
            $this->overtimeLengthMinutes,
            $max,
            $min,
            $this->allowSpectators,
            $this->allowMedia,
            $this->allowRecording,
            $this->allowPhotos,
            $this->allowStreaming,
        );
    }

    public function withMediaSettings(bool $spectators, bool $media, bool $recording, bool $photos, bool $streaming): self
    {
        return new self(
            $this->rules,
            $this->periodLengthMinutes,
            $this->totalPeriods,
            $this->overtimeLengthMinutes,
            $this->maxRosterSize,
            $this->minRosterSize,
            $spectators,
            $media,
            $recording,
            $photos,
            $streaming,
        );
    }

    // ============================
    // SERIALIZATION
    // ============================

    public function toArray(): array
    {
        return [
            'game_rules' => $this->rules,
            'period_length_minutes' => $this->periodLengthMinutes,
            'total_periods' => $this->totalPeriods,
            'overtime_length_minutes' => $this->overtimeLengthMinutes,
            'max_roster_size' => $this->maxRosterSize,
            'min_roster_size' => $this->minRosterSize,
            'allow_spectators' => $this->allowSpectators,
            'allow_media' => $this->allowMedia,
            'allow_recording' => $this->allowRecording,
            'allow_photos' => $this->allowPhotos,
            'allow_streaming' => $this->allowStreaming,
            'total_regulation_minutes' => $this->totalRegulationMinutes(),
            'format_description' => $this->formatDescription(),
        ];
    }
}
