<?php

namespace App\Casts\Game;

use App\ValueObjects\Game\GameClock;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Cast für GameClock Value Object.
 *
 * Ermöglicht automatische Konvertierung zwischen DB-Spalten und GameClock VO.
 */
class GameClockCast implements CastsAttributes
{
    /**
     * Transform the attribute from the underlying model values.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): GameClock
    {
        return GameClock::fromArray([
            'time_remaining_seconds' => $attributes['time_remaining_seconds'] ?? null,
            'current_period' => $attributes['current_period'] ?? 1,
            'clock_running' => (bool) ($attributes['clock_running'] ?? false),
            'total_periods' => $attributes['total_periods'] ?? 4,
            'period_length_minutes' => $attributes['period_length_minutes'] ?? 10,
            'overtime_periods' => $attributes['overtime_periods'] ?? 0,
            'overtime_length_minutes' => $attributes['overtime_length_minutes'] ?? 5,
        ]);
    }

    /**
     * Transform the attribute to its underlying model values.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if (!$value instanceof GameClock) {
            return [];
        }

        return [
            'time_remaining_seconds' => $value->timeRemainingSeconds(),
            'current_period' => $value->currentPeriod(),
            'clock_running' => $value->isClockRunning(),
            'total_periods' => $value->totalPeriods(),
            'period_length_minutes' => $value->periodLengthMinutes(),
            'overtime_periods' => $value->overtimePeriods(),
            'overtime_length_minutes' => $value->overtimeLengthMinutes(),
        ];
    }
}
