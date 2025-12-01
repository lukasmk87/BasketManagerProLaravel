<?php

namespace App\Casts\Game;

use App\ValueObjects\Game\GameSchedule;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Cast für GameSchedule Value Object.
 *
 * Ermöglicht automatische Konvertierung zwischen DB-Spalten und GameSchedule VO.
 */
class GameScheduleCast implements CastsAttributes
{
    /**
     * Transform the attribute from the underlying model values.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): GameSchedule
    {
        return GameSchedule::fromArray([
            'scheduled_at' => $attributes['scheduled_at'] ?? null,
            'actual_start_time' => $attributes['actual_start_time'] ?? null,
            'actual_end_time' => $attributes['actual_end_time'] ?? null,
            'registration_deadline_hours' => $attributes['registration_deadline_hours'] ?? 24,
            'lineup_deadline_hours' => $attributes['lineup_deadline_hours'] ?? 2,
        ]);
    }

    /**
     * Transform the attribute to its underlying model values.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if (!$value instanceof GameSchedule) {
            return [];
        }

        return [
            'scheduled_at' => $value->scheduledAt()?->format('Y-m-d H:i:s'),
            'actual_start_time' => $value->actualStartTime()?->format('Y-m-d H:i:s'),
            'actual_end_time' => $value->actualEndTime()?->format('Y-m-d H:i:s'),
            'registration_deadline_hours' => $value->registrationDeadlineHours(),
            'lineup_deadline_hours' => $value->lineupDeadlineHours(),
        ];
    }
}
