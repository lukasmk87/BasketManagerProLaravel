<?php

namespace App\ValueObjects\Game;

/**
 * Value Object für Spieluhr.
 *
 * Kapselt alle Zeit- und Perioden-bezogenen Daten eines Spiels.
 */
final class GameClock
{
    public function __construct(
        private readonly ?int $timeRemainingSeconds = null,
        private readonly int $currentPeriod = 1,
        private readonly bool $clockRunning = false,
        private readonly int $totalPeriods = 4,
        private readonly int $periodLengthMinutes = 10,
        private readonly int $overtimePeriods = 0,
        private readonly int $overtimeLengthMinutes = 5,
    ) {}

    // ============================
    // FACTORY METHODS
    // ============================

    public static function create(
        ?int $timeRemainingSeconds = null,
        int $currentPeriod = 1,
        bool $clockRunning = false,
        int $totalPeriods = 4,
        int $periodLengthMinutes = 10,
    ): self {
        return new self(
            $timeRemainingSeconds,
            $currentPeriod,
            $clockRunning,
            $totalPeriods,
            $periodLengthMinutes
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            timeRemainingSeconds: $data['time_remaining_seconds'] ?? null,
            currentPeriod: $data['current_period'] ?? 1,
            clockRunning: $data['clock_running'] ?? false,
            totalPeriods: $data['total_periods'] ?? 4,
            periodLengthMinutes: $data['period_length_minutes'] ?? 10,
            overtimePeriods: $data['overtime_periods'] ?? 0,
            overtimeLengthMinutes: $data['overtime_length_minutes'] ?? 5,
        );
    }

    public static function forNewGame(int $totalPeriods = 4, int $periodLengthMinutes = 10): self
    {
        return new self(
            timeRemainingSeconds: $periodLengthMinutes * 60,
            currentPeriod: 1,
            clockRunning: false,
            totalPeriods: $totalPeriods,
            periodLengthMinutes: $periodLengthMinutes,
        );
    }

    // ============================
    // ACCESSORS
    // ============================

    public function timeRemainingSeconds(): ?int
    {
        return $this->timeRemainingSeconds;
    }

    public function currentPeriod(): int
    {
        return $this->currentPeriod;
    }

    public function isClockRunning(): bool
    {
        return $this->clockRunning;
    }

    public function totalPeriods(): int
    {
        return $this->totalPeriods;
    }

    public function periodLengthMinutes(): int
    {
        return $this->periodLengthMinutes;
    }

    public function overtimePeriods(): int
    {
        return $this->overtimePeriods;
    }

    public function overtimeLengthMinutes(): int
    {
        return $this->overtimeLengthMinutes;
    }

    // ============================
    // CALCULATED PROPERTIES
    // ============================

    public function isRegulationTime(): bool
    {
        return $this->currentPeriod <= $this->totalPeriods;
    }

    public function isOvertime(): bool
    {
        return $this->currentPeriod > $this->totalPeriods;
    }

    public function wentToOvertime(): bool
    {
        return $this->overtimePeriods > 0;
    }

    public function isLastPeriod(): bool
    {
        return $this->currentPeriod === $this->totalPeriods && !$this->isOvertime();
    }

    public function periodsRemaining(): int
    {
        if ($this->isOvertime()) {
            return 0;
        }
        return max(0, $this->totalPeriods - $this->currentPeriod);
    }

    public function timeRemainingMinutes(): ?float
    {
        if ($this->timeRemainingSeconds === null) {
            return null;
        }
        return $this->timeRemainingSeconds / 60;
    }

    public function formattedTimeRemaining(): string
    {
        if ($this->timeRemainingSeconds === null) {
            return '00:00';
        }

        $minutes = floor($this->timeRemainingSeconds / 60);
        $seconds = $this->timeRemainingSeconds % 60;

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    public function periodDisplay(): string
    {
        if ($this->isOvertime()) {
            $otPeriod = $this->currentPeriod - $this->totalPeriods;
            return "OT{$otPeriod}";
        }
        return "Q{$this->currentPeriod}";
    }

    // ============================
    // IMMUTABLE OPERATIONS
    // ============================

    public function withStartedClock(): self
    {
        return new self(
            $this->timeRemainingSeconds ?? ($this->periodLengthMinutes * 60),
            $this->currentPeriod,
            true,
            $this->totalPeriods,
            $this->periodLengthMinutes,
            $this->overtimePeriods,
            $this->overtimeLengthMinutes,
        );
    }

    public function withStoppedClock(): self
    {
        return new self(
            $this->timeRemainingSeconds,
            $this->currentPeriod,
            false,
            $this->totalPeriods,
            $this->periodLengthMinutes,
            $this->overtimePeriods,
            $this->overtimeLengthMinutes,
        );
    }

    public function withDecrementedTime(int $seconds): self
    {
        $newTime = max(0, ($this->timeRemainingSeconds ?? 0) - $seconds);

        return new self(
            $newTime,
            $this->currentPeriod,
            $this->clockRunning,
            $this->totalPeriods,
            $this->periodLengthMinutes,
            $this->overtimePeriods,
            $this->overtimeLengthMinutes,
        );
    }

    public function withNextPeriod(): self
    {
        $newPeriod = $this->currentPeriod + 1;
        $isOvertime = $newPeriod > $this->totalPeriods;
        $newOvertimePeriods = $isOvertime ? $this->overtimePeriods + 1 : $this->overtimePeriods;
        $newTime = $isOvertime
            ? $this->overtimeLengthMinutes * 60
            : $this->periodLengthMinutes * 60;

        return new self(
            $newTime,
            $newPeriod,
            false,
            $this->totalPeriods,
            $this->periodLengthMinutes,
            $newOvertimePeriods,
            $this->overtimeLengthMinutes,
        );
    }

    public function withTimeRemaining(int $seconds): self
    {
        return new self(
            $seconds,
            $this->currentPeriod,
            $this->clockRunning,
            $this->totalPeriods,
            $this->periodLengthMinutes,
            $this->overtimePeriods,
            $this->overtimeLengthMinutes,
        );
    }

    // ============================
    // SERIALIZATION
    // ============================

    public function toArray(): array
    {
        return [
            'time_remaining_seconds' => $this->timeRemainingSeconds,
            'current_period' => $this->currentPeriod,
            'clock_running' => $this->clockRunning,
            'total_periods' => $this->totalPeriods,
            'period_length_minutes' => $this->periodLengthMinutes,
            'overtime_periods' => $this->overtimePeriods,
            'overtime_length_minutes' => $this->overtimeLengthMinutes,
            'formatted_time' => $this->formattedTimeRemaining(),
            'period_display' => $this->periodDisplay(),
            'is_overtime' => $this->isOvertime(),
        ];
    }
}
