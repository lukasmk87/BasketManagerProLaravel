<?php

namespace App\Services\Statistics;

use App\Models\Game;
use App\Models\Player;
use App\Models\GameAction;
use Illuminate\Support\Facades\Cache;

/**
 * ShotChartService
 *
 * Verantwortung: Shot Chart Daten, Zone-Analyse und Schuss-Visualisierung.
 * Verwaltet Schuss-Positionen, Zonen-Statistiken und Shot Chart Zusammenfassungen.
 */
class ShotChartService
{
    public function __construct(
        private StatisticsCacheManager $cacheManager
    ) {}

    /**
     * Get shot chart data for a player in a game.
     */
    public function getPlayerShotChart(Player $player, Game $game): array
    {
        $cacheKey = $this->cacheManager->buildCacheKey('shot_chart', [
            'player_id' => $player->id,
            'game_id' => $game->id,
        ]);

        // PERF-007: Dynamic TTL based on game status
        $ttl = $this->cacheManager->getCacheTtlForGame($game);

        return Cache::remember($cacheKey, $ttl, function () use ($player, $game) {
            $shots = GameAction::where('game_id', $game->id)
                ->where('player_id', $player->id)
                ->whereIn('action_type', [
                    'field_goal_made', 'field_goal_missed',
                    'three_point_made', 'three_point_missed'
                ])
                ->whereNotNull('shot_x')
                ->whereNotNull('shot_y')
                ->get();

            $shotData = [];
            foreach ($shots as $shot) {
                $shotData[] = [
                    'x' => $shot->shot_x,
                    'y' => $shot->shot_y,
                    'made' => str_contains($shot->action_type, '_made'),
                    'distance' => $shot->shot_distance,
                    'zone' => $shot->shot_zone,
                    'period' => $shot->period,
                    'time' => $shot->time_remaining,
                    'points' => $shot->points,
                ];
            }

            return [
                'shots' => $shotData,
                'zones' => $this->calculateShotZoneStats($shots),
                'summary' => $this->calculateShotChartSummary($shots),
            ];
        });
    }

    /**
     * Calculate shot zone statistics.
     */
    public function calculateShotZoneStats($shots): array
    {
        $zones = [];

        foreach ($shots as $shot) {
            $zone = $shot->shot_zone ?? 'unknown';

            if (!isset($zones[$zone])) {
                $zones[$zone] = [
                    'attempts' => 0,
                    'makes' => 0,
                    'percentage' => 0,
                    'points' => 0
                ];
            }

            $zones[$zone]['attempts']++;
            if (str_contains($shot->action_type, '_made')) {
                $zones[$zone]['makes']++;
                $zones[$zone]['points'] += $shot->points;
            }
        }

        // Calculate percentages
        foreach ($zones as $zone => &$stats) {
            $stats['percentage'] = $stats['attempts'] > 0 ?
                round(($stats['makes'] / $stats['attempts']) * 100, 1) : 0;
        }

        return $zones;
    }

    /**
     * Calculate shot chart summary.
     */
    public function calculateShotChartSummary($shots): array
    {
        $makes = $shots->filter(fn($shot) => str_contains($shot->action_type, '_made'));
        $attempts = $shots->count();

        return [
            'total_attempts' => $attempts,
            'total_makes' => $makes->count(),
            'shooting_percentage' => $attempts > 0 ?
                round(($makes->count() / $attempts) * 100, 1) : 0,
            'average_distance' => $shots->avg('shot_distance') ?? 0,
            'longest_made' => $makes->max('shot_distance') ?? 0,
            'points_from_shots' => $makes->sum('points'),
        ];
    }

    /**
     * Get shot zone from coordinates.
     * Determines which zone a shot came from based on x, y coordinates.
     */
    public function getShotZoneFromCoordinates(float $x, float $y): string
    {
        // Court dimensions (standard NBA/FIBA)
        // x: 0-50 (feet), y: 0-47 (feet)
        // Basket at approximately (25, 4)

        $basketX = 25;
        $basketY = 4;

        // Calculate distance from basket
        $distance = sqrt(pow($x - $basketX, 2) + pow($y - $basketY, 2));

        // Three-point line distance (23.75 ft at top, 22 ft corners)
        $threePointDistance = 23.75;
        $cornerThreeDistance = 22;

        // Determine zone
        if ($distance <= 4) {
            return 'restricted_area';
        }

        if ($distance <= 8) {
            return 'paint';
        }

        if ($y < 14 && ($x < 3 || $x > 47)) {
            // Corner three
            return $distance >= $cornerThreeDistance ? 'corner_three' : 'short_corner';
        }

        if ($distance >= $threePointDistance) {
            // Above the break three
            if ($x < 17) {
                return 'three_left_wing';
            } elseif ($x > 33) {
                return 'three_right_wing';
            } else {
                return 'three_top_key';
            }
        }

        // Mid-range
        if ($x < 17) {
            return 'mid_left';
        } elseif ($x > 33) {
            return 'mid_right';
        } elseif ($y < 19) {
            return 'mid_paint';
        } else {
            return 'mid_elbow';
        }
    }
}
