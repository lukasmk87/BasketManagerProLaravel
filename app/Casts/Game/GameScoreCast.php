<?php

namespace App\Casts\Game;

use App\ValueObjects\Game\GameScore;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Cast für GameScore Value Object.
 *
 * Ermöglicht automatische Konvertierung zwischen DB-Spalten und GameScore VO.
 */
class GameScoreCast implements CastsAttributes
{
    /**
     * Transform the attribute from the underlying model values.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): GameScore
    {
        return GameScore::fromArray([
            'home_team_score' => $attributes['home_team_score'] ?? 0,
            'away_team_score' => $attributes['away_team_score'] ?? 0,
            'period_scores' => isset($attributes['period_scores'])
                ? json_decode($attributes['period_scores'], true)
                : null,
        ]);
    }

    /**
     * Transform the attribute to its underlying model values.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if (!$value instanceof GameScore) {
            return [];
        }

        return [
            'home_team_score' => $value->homeTeamScore(),
            'away_team_score' => $value->awayTeamScore(),
            'period_scores' => $value->periodScores() ? json_encode($value->periodScores()) : null,
        ];
    }
}
