<?php

namespace App\Casts\Game;

use App\ValueObjects\Game\GameStatistics;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Cast für GameStatistics Value Object.
 *
 * Ermöglicht automatische Konvertierung zwischen DB-Spalten und GameStatistics VO.
 */
class GameStatisticsCast implements CastsAttributes
{
    /**
     * Transform the attribute from the underlying model values.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): GameStatistics
    {
        return GameStatistics::fromArray([
            'team_stats' => isset($attributes['team_stats'])
                ? json_decode($attributes['team_stats'], true)
                : null,
            'player_stats' => isset($attributes['player_stats'])
                ? json_decode($attributes['player_stats'], true)
                : null,
            'play_by_play' => isset($attributes['play_by_play'])
                ? json_decode($attributes['play_by_play'], true)
                : null,
            'substitutions' => isset($attributes['substitutions'])
                ? json_decode($attributes['substitutions'], true)
                : null,
            'timeouts' => isset($attributes['timeouts'])
                ? json_decode($attributes['timeouts'], true)
                : null,
            'live_commentary' => $attributes['live_commentary'] ?? null,
            'stats_verified' => (bool) ($attributes['stats_verified'] ?? false),
        ]);
    }

    /**
     * Transform the attribute to its underlying model values.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if (!$value instanceof GameStatistics) {
            return [];
        }

        return [
            'team_stats' => $value->teamStats() ? json_encode($value->teamStats()) : null,
            'player_stats' => $value->playerStats() ? json_encode($value->playerStats()) : null,
            'play_by_play' => $value->playByPlay() ? json_encode($value->playByPlay()) : null,
            'substitutions' => $value->substitutions() ? json_encode($value->substitutions()) : null,
            'timeouts' => $value->timeouts() ? json_encode($value->timeouts()) : null,
            'live_commentary' => $value->liveCommentary(),
            'stats_verified' => $value->isStatsVerified(),
        ];
    }
}
