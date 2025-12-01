<?php

namespace App\Casts\Game;

use App\ValueObjects\Game\GameOfficials;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Cast für GameOfficials Value Object.
 *
 * Ermöglicht automatische Konvertierung zwischen DB-Spalten und GameOfficials VO.
 */
class GameOfficialsCast implements CastsAttributes
{
    /**
     * Transform the attribute from the underlying model values.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): GameOfficials
    {
        return GameOfficials::fromArray([
            'referees' => isset($attributes['referees'])
                ? json_decode($attributes['referees'], true)
                : null,
            'scorekeepers' => isset($attributes['scorekeepers'])
                ? json_decode($attributes['scorekeepers'], true)
                : null,
            'timekeepers' => isset($attributes['timekeepers'])
                ? json_decode($attributes['timekeepers'], true)
                : null,
            'medical_staff_present' => $attributes['medical_staff_present'] ?? null,
        ]);
    }

    /**
     * Transform the attribute to its underlying model values.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if (!$value instanceof GameOfficials) {
            return [];
        }

        return [
            'referees' => $value->referees() ? json_encode($value->referees()) : null,
            'scorekeepers' => $value->scorekeepers() ? json_encode($value->scorekeepers()) : null,
            'timekeepers' => $value->timekeepers() ? json_encode($value->timekeepers()) : null,
            'medical_staff_present' => $value->isMedicalStaffPresent(),
        ];
    }
}
