<?php

namespace App\Casts\Game;

use App\ValueObjects\Game\GameFouls;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Cast für GameFouls Value Object.
 *
 * Ermöglicht automatische Konvertierung zwischen DB-Spalten und GameFouls VO.
 */
class GameFoulsCast implements CastsAttributes
{
    /**
     * Transform the attribute from the underlying model values.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): GameFouls
    {
        return GameFouls::fromArray([
            'team_fouls' => isset($attributes['team_fouls'])
                ? json_decode($attributes['team_fouls'], true)
                : null,
            'technical_fouls' => isset($attributes['technical_fouls'])
                ? json_decode($attributes['technical_fouls'], true)
                : null,
            'ejections' => isset($attributes['ejections'])
                ? json_decode($attributes['ejections'], true)
                : null,
        ]);
    }

    /**
     * Transform the attribute to its underlying model values.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if (!$value instanceof GameFouls) {
            return [];
        }

        return [
            'team_fouls' => $value->teamFouls() ? json_encode($value->teamFouls()) : null,
            'technical_fouls' => $value->technicalFouls() ? json_encode($value->technicalFouls()) : null,
            'ejections' => $value->ejections() ? json_encode($value->ejections()) : null,
        ];
    }
}
