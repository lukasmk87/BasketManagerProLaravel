<?php

namespace App\Casts\Game;

use App\ValueObjects\Game\GameVenue;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Cast für GameVenue Value Object.
 *
 * Ermöglicht automatische Konvertierung zwischen DB-Spalten und GameVenue VO.
 */
class GameVenueCast implements CastsAttributes
{
    /**
     * Transform the attribute from the underlying model values.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): GameVenue
    {
        return GameVenue::fromArray([
            'venue' => $attributes['venue'] ?? null,
            'venue_address' => $attributes['venue_address'] ?? null,
            'venue_code' => $attributes['venue_code'] ?? null,
            'attendance' => $attributes['attendance'] ?? null,
            'capacity' => $attributes['capacity'] ?? null,
            'weather_conditions' => $attributes['weather_conditions'] ?? null,
            'temperature' => $attributes['temperature'] ?? null,
            'court_conditions' => $attributes['court_conditions'] ?? null,
        ]);
    }

    /**
     * Transform the attribute to its underlying model values.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if (!$value instanceof GameVenue) {
            return [];
        }

        return [
            'venue' => $value->name(),
            'venue_address' => $value->address(),
            'venue_code' => $value->code(),
            'attendance' => $value->attendance(),
            'capacity' => $value->capacity(),
            'weather_conditions' => $value->weatherConditions(),
            'temperature' => $value->temperature(),
            'court_conditions' => $value->courtConditions(),
        ];
    }
}
