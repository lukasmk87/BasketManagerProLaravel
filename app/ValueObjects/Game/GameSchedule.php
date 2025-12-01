<?php

namespace App\ValueObjects\Game;

use Carbon\Carbon;

/**
 * Value Object für Spieltermin.
 *
 * Kapselt alle Termin-bezogenen Daten eines Spiels.
 */
final class GameSchedule
{
    public function __construct(
        private readonly ?Carbon $scheduledAt = null,
        private readonly ?Carbon $actualStartTime = null,
        private readonly ?Carbon $actualEndTime = null,
        private readonly ?int $registrationDeadlineHours = 24,
        private readonly ?int $lineupDeadlineHours = 2,
    ) {}

    // ============================
    // FACTORY METHODS
    // ============================

    public static function create(
        ?Carbon $scheduledAt = null,
        ?Carbon $actualStartTime = null,
        ?Carbon $actualEndTime = null,
    ): self {
        return new self($scheduledAt, $actualStartTime, $actualEndTime);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            scheduledAt: isset($data['scheduled_at']) ? Carbon::parse($data['scheduled_at']) : null,
            actualStartTime: isset($data['actual_start_time']) ? Carbon::parse($data['actual_start_time']) : null,
            actualEndTime: isset($data['actual_end_time']) ? Carbon::parse($data['actual_end_time']) : null,
            registrationDeadlineHours: $data['registration_deadline_hours'] ?? 24,
            lineupDeadlineHours: $data['lineup_deadline_hours'] ?? 2,
        );
    }

    public static function forScheduledGame(Carbon $scheduledAt): self
    {
        return new self(scheduledAt: $scheduledAt);
    }

    // ============================
    // ACCESSORS
    // ============================

    public function scheduledAt(): ?Carbon
    {
        return $this->scheduledAt;
    }

    public function actualStartTime(): ?Carbon
    {
        return $this->actualStartTime;
    }

    public function actualEndTime(): ?Carbon
    {
        return $this->actualEndTime;
    }

    public function registrationDeadlineHours(): ?int
    {
        return $this->registrationDeadlineHours;
    }

    public function lineupDeadlineHours(): ?int
    {
        return $this->lineupDeadlineHours;
    }

    // ============================
    // CALCULATED PROPERTIES
    // ============================

    public function isScheduled(): bool
    {
        return $this->scheduledAt !== null;
    }

    public function hasStarted(): bool
    {
        return $this->actualStartTime !== null;
    }

    public function hasEnded(): bool
    {
        return $this->actualEndTime !== null;
    }

    public function durationMinutes(): ?int
    {
        if (!$this->actualStartTime || !$this->actualEndTime) {
            return null;
        }

        return $this->actualStartTime->diffInMinutes($this->actualEndTime);
    }

    public function delayMinutes(): ?int
    {
        if (!$this->scheduledAt || !$this->actualStartTime) {
            return null;
        }

        $delay = $this->scheduledAt->diffInMinutes($this->actualStartTime, false);
        return $delay > 0 ? $delay : null;
    }

    public function wasDelayed(): bool
    {
        return $this->delayMinutes() !== null && $this->delayMinutes() > 0;
    }

    public function isUpcoming(): bool
    {
        if (!$this->scheduledAt) {
            return false;
        }

        return $this->scheduledAt->isFuture();
    }

    public function isPast(): bool
    {
        if (!$this->scheduledAt) {
            return false;
        }

        return $this->scheduledAt->isPast();
    }

    public function isToday(): bool
    {
        if (!$this->scheduledAt) {
            return false;
        }

        return $this->scheduledAt->isToday();
    }

    public function minutesUntilStart(): ?int
    {
        if (!$this->scheduledAt || $this->scheduledAt->isPast()) {
            return null;
        }

        return now()->diffInMinutes($this->scheduledAt);
    }

    public function hoursUntilStart(): ?float
    {
        if (!$this->scheduledAt || $this->scheduledAt->isPast()) {
            return null;
        }

        return round(now()->diffInMinutes($this->scheduledAt) / 60, 1);
    }

    public function registrationDeadline(): ?Carbon
    {
        if (!$this->scheduledAt) {
            return null;
        }

        return $this->scheduledAt->copy()->subHours($this->registrationDeadlineHours ?? 24);
    }

    public function lineupDeadline(): ?Carbon
    {
        if (!$this->scheduledAt) {
            return null;
        }

        return $this->scheduledAt->copy()->subHours($this->lineupDeadlineHours ?? 2);
    }

    public function isRegistrationOpen(): bool
    {
        $deadline = $this->registrationDeadline();
        if (!$deadline) {
            return false;
        }

        return now()->isBefore($deadline);
    }

    public function isLineupChangesAllowed(): bool
    {
        $deadline = $this->lineupDeadline();
        if (!$deadline) {
            return false;
        }

        return now()->isBefore($deadline);
    }

    public function hoursUntilRegistrationDeadline(): ?float
    {
        $deadline = $this->registrationDeadline();
        if (!$deadline) {
            return null;
        }

        return round(now()->diffInHours($deadline, false), 1);
    }

    public function hoursUntilLineupDeadline(): ?float
    {
        $deadline = $this->lineupDeadline();
        if (!$deadline) {
            return null;
        }

        return round(now()->diffInHours($deadline, false), 1);
    }

    public function canStartGame(): bool
    {
        if (!$this->scheduledAt) {
            return false;
        }

        // Game can be started 30 minutes before scheduled time
        return $this->scheduledAt->subMinutes(30)->isPast() && !$this->hasStarted();
    }

    // ============================
    // FORMATTING
    // ============================

    public function formattedScheduledAt(): ?string
    {
        return $this->scheduledAt?->format('d.m.Y H:i');
    }

    public function formattedDate(): ?string
    {
        return $this->scheduledAt?->format('d.m.Y');
    }

    public function formattedTime(): ?string
    {
        return $this->scheduledAt?->format('H:i');
    }

    public function formattedDuration(): ?string
    {
        $duration = $this->durationMinutes();
        if (!$duration) {
            return null;
        }

        $hours = floor($duration / 60);
        $minutes = $duration % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d h', $hours, $minutes);
        }

        return sprintf('%d min', $minutes);
    }

    public function dayOfWeek(): ?string
    {
        return $this->scheduledAt?->format('l');
    }

    public function dayOfWeekGerman(): ?string
    {
        if (!$this->scheduledAt) {
            return null;
        }

        $days = [
            'Monday' => 'Montag',
            'Tuesday' => 'Dienstag',
            'Wednesday' => 'Mittwoch',
            'Thursday' => 'Donnerstag',
            'Friday' => 'Freitag',
            'Saturday' => 'Samstag',
            'Sunday' => 'Sonntag',
        ];

        return $days[$this->scheduledAt->format('l')] ?? null;
    }

    // ============================
    // IMMUTABLE OPERATIONS
    // ============================

    public function withActualStart(Carbon $startTime): self
    {
        return new self(
            $this->scheduledAt,
            $startTime,
            $this->actualEndTime,
            $this->registrationDeadlineHours,
            $this->lineupDeadlineHours,
        );
    }

    public function withActualEnd(Carbon $endTime): self
    {
        return new self(
            $this->scheduledAt,
            $this->actualStartTime,
            $endTime,
            $this->registrationDeadlineHours,
            $this->lineupDeadlineHours,
        );
    }

    public function withRescheduled(Carbon $newScheduledAt): self
    {
        return new self(
            $newScheduledAt,
            null,
            null,
            $this->registrationDeadlineHours,
            $this->lineupDeadlineHours,
        );
    }

    // ============================
    // SERIALIZATION
    // ============================

    public function toArray(): array
    {
        return [
            'scheduled_at' => $this->scheduledAt?->toIso8601String(),
            'actual_start_time' => $this->actualStartTime?->toIso8601String(),
            'actual_end_time' => $this->actualEndTime?->toIso8601String(),
            'registration_deadline_hours' => $this->registrationDeadlineHours,
            'lineup_deadline_hours' => $this->lineupDeadlineHours,
            'duration_minutes' => $this->durationMinutes(),
            'is_upcoming' => $this->isUpcoming(),
            'is_past' => $this->isPast(),
            'registration_deadline' => $this->registrationDeadline()?->format('d.m.Y H:i'),
            'lineup_deadline' => $this->lineupDeadline()?->format('d.m.Y H:i'),
        ];
    }
}
