<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameAction;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ShotChartController extends Controller
{
    /**
     * Get shot chart data for a game
     *
     * @param Request $request
     * @param Game $game
     * @return JsonResponse
     */
    public function getGameShotChart(Request $request, Game $game): JsonResponse
    {
        $request->validate([
            'player_id' => 'sometimes|exists:players,id',
            'team_id' => 'sometimes|exists:teams,id',
            'period' => 'sometimes|integer|min:1|max:10',
            'shot_type' => 'sometimes|in:all,field_goal,three_point,free_throw',
        ]);

        // Base query für Schüsse
        $query = GameAction::query()
            ->where('game_id', $game->id)
            ->where(function($q) {
                $q->where('action_type', 'like', '%_made')
                  ->orWhere('action_type', 'like', '%_missed');
            })
            ->with(['player:id,first_name,last_name,jersey_number', 'team:id,name']);

        // Filter anwenden
        if ($request->has('player_id')) {
            $query->where('player_id', $request->player_id);
        }

        if ($request->has('team_id')) {
            $query->where('team_id', $request->team_id);
        }

        if ($request->has('period')) {
            $query->where('period', $request->period);
        }

        if ($request->has('shot_type') && $request->shot_type !== 'all') {
            $query->where('action_type', 'like', $request->shot_type . '%');
        }

        $shots = $query->orderBy('period')
                      ->orderBy('game_clock_seconds', 'desc')
                      ->get()
                      ->map(function ($shot) {
                          return [
                              'id' => $shot->id,
                              'player_id' => $shot->player_id,
                              'player_name' => $shot->player ? $shot->player->first_name . ' ' . $shot->player->last_name : 'Unbekannt',
                              'jersey_number' => $shot->player ? $shot->player->jersey_number : null,
                              'team_id' => $shot->team_id,
                              'team_name' => $shot->team ? $shot->team->name : 'Unbekannt',
                              'action_type' => $shot->action_type,
                              'period' => $shot->period,
                              'time_remaining' => $shot->time_remaining,
                              'display_time' => $shot->display_time,
                              'shot_x' => $shot->shot_x,
                              'shot_y' => $shot->shot_y,
                              'shot_distance' => $shot->shot_distance,
                              'shot_zone' => $shot->shot_zone,
                              'is_successful' => $shot->is_successful,
                              'is_shot' => true,
                              'is_three_pointer' => $shot->is_three_pointer,
                              'is_free_throw' => $shot->is_free_throw,
                              'points' => $shot->getPointValue(),
                              'description' => $shot->action_description,
                              'recorded_at' => $shot->recorded_at,
                          ];
                      });

        // Statistiken berechnen
        $statistics = $this->calculateShotStatistics($shots);

        return response()->json([
            'game_id' => $game->id,
            'shots' => $shots,
            'statistics' => $statistics,
            'filters_applied' => $request->only(['player_id', 'team_id', 'period', 'shot_type']),
            'total_shots' => $shots->count(),
        ]);
    }

    /**
     * Get shot chart data for a player across multiple games
     *
     * @param Request $request
     * @param Player $player
     * @return JsonResponse
     */
    public function getPlayerShotChart(Request $request, Player $player): JsonResponse
    {
        $request->validate([
            'game_ids' => 'sometimes|array',
            'game_ids.*' => 'exists:games,id',
            'season' => 'sometimes|string',
            'limit' => 'sometimes|integer|min:1|max:1000',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
        ]);

        $query = GameAction::query()
            ->where('player_id', $player->id)
            ->where(function($q) {
                $q->where('action_type', 'like', '%_made')
                  ->orWhere('action_type', 'like', '%_missed');
            })
            ->with(['game:id,played_at,home_team_id,away_team_id']);

        // Filter anwenden
        if ($request->has('game_ids')) {
            $query->whereIn('game_id', $request->game_ids);
        }

        if ($request->has('date_from')) {
            $query->whereHas('game', function($q) use ($request) {
                $q->whereDate('played_at', '>=', $request->date_from);
            });
        }

        if ($request->has('date_to')) {
            $query->whereHas('game', function($q) use ($request) {
                $q->whereDate('played_at', '<=', $request->date_to);
            });
        }

        if ($request->has('limit')) {
            $query->limit($request->limit);
        }

        $shots = $query->orderBy('recorded_at', 'desc')
                      ->get()
                      ->map(function ($shot) {
                          return [
                              'id' => $shot->id,
                              'game_id' => $shot->game_id,
                              'game_date' => $shot->game ? $shot->game->played_at->format('Y-m-d') : null,
                              'action_type' => $shot->action_type,
                              'period' => $shot->period,
                              'time_remaining' => $shot->time_remaining,
                              'display_time' => $shot->display_time,
                              'shot_x' => $shot->shot_x,
                              'shot_y' => $shot->shot_y,
                              'shot_distance' => $shot->shot_distance,
                              'shot_zone' => $shot->shot_zone,
                              'is_successful' => $shot->is_successful,
                              'is_shot' => true,
                              'is_three_pointer' => $shot->is_three_pointer,
                              'is_free_throw' => $shot->is_free_throw,
                              'points' => $shot->getPointValue(),
                              'description' => $shot->action_description,
                              'recorded_at' => $shot->recorded_at,
                          ];
                      });

        // Erweiterte Statistiken für Spieler
        $statistics = $this->calculatePlayerShotStatistics($shots);

        return response()->json([
            'player_id' => $player->id,
            'player_name' => $player->first_name . ' ' . $player->last_name,
            'shots' => $shots,
            'statistics' => $statistics,
            'filters_applied' => $request->only(['game_ids', 'season', 'date_from', 'date_to']),
            'total_shots' => $shots->count(),
        ]);
    }

    /**
     * Get shot chart data for a team
     *
     * @param Request $request
     * @param Team $team
     * @return JsonResponse
     */
    public function getTeamShotChart(Request $request, Team $team): JsonResponse
    {
        $request->validate([
            'game_id' => 'sometimes|exists:games,id',
            'player_id' => 'sometimes|exists:players,id',
            'period' => 'sometimes|integer|min:1|max:10',
            'shot_type' => 'sometimes|in:all,field_goal,three_point,free_throw',
        ]);

        $query = GameAction::query()
            ->where('team_id', $team->id)
            ->where(function($q) {
                $q->where('action_type', 'like', '%_made')
                  ->orWhere('action_type', 'like', '%_missed');
            })
            ->with(['player:id,first_name,last_name,jersey_number', 'game:id,played_at']);

        // Filter anwenden
        if ($request->has('game_id')) {
            $query->where('game_id', $request->game_id);
        }

        if ($request->has('player_id')) {
            $query->where('player_id', $request->player_id);
        }

        if ($request->has('period')) {
            $query->where('period', $request->period);
        }

        if ($request->has('shot_type') && $request->shot_type !== 'all') {
            $query->where('action_type', 'like', $request->shot_type . '%');
        }

        $shots = $query->orderBy('recorded_at', 'desc')
                      ->get()
                      ->map(function ($shot) {
                          return [
                              'id' => $shot->id,
                              'player_id' => $shot->player_id,
                              'player_name' => $shot->player ? $shot->player->first_name . ' ' . $shot->player->last_name : 'Unbekannt',
                              'jersey_number' => $shot->player ? $shot->player->jersey_number : null,
                              'game_id' => $shot->game_id,
                              'action_type' => $shot->action_type,
                              'period' => $shot->period,
                              'time_remaining' => $shot->time_remaining,
                              'display_time' => $shot->display_time,
                              'shot_x' => $shot->shot_x,
                              'shot_y' => $shot->shot_y,
                              'shot_distance' => $shot->shot_distance,
                              'shot_zone' => $shot->shot_zone,
                              'is_successful' => $shot->is_successful,
                              'is_shot' => true,
                              'is_three_pointer' => $shot->is_three_pointer,
                              'is_free_throw' => $shot->is_free_throw,
                              'points' => $shot->getPointValue(),
                              'description' => $shot->action_description,
                              'recorded_at' => $shot->recorded_at,
                          ];
                      });

        // Team-Statistiken
        $statistics = $this->calculateTeamShotStatistics($shots);

        return response()->json([
            'team_id' => $team->id,
            'team_name' => $team->name,
            'shots' => $shots,
            'statistics' => $statistics,
            'filters_applied' => $request->only(['game_id', 'player_id', 'period', 'shot_type']),
            'total_shots' => $shots->count(),
        ]);
    }

    /**
     * Calculate shot statistics
     *
     * @param \Illuminate\Support\Collection $shots
     * @return array
     */
    private function calculateShotStatistics($shots): array
    {
        $total = $shots->count();
        $made = $shots->where('is_successful', true)->count();
        $missed = $total - $made;

        // Nach Wurf-Typ aufteilen
        $fieldGoals = $shots->filter(fn($shot) => 
            str_contains($shot['action_type'], 'field_goal')
        );
        $threePointers = $shots->where('is_three_pointer', true);
        $freeThrows = $shots->where('is_free_throw', true);

        // Zonen-Statistiken
        $zones = $this->calculateZoneStatistics($shots);

        return [
            'total_shots' => $total,
            'made_shots' => $made,
            'missed_shots' => $missed,
            'field_goal_percentage' => $total > 0 ? round(($made / $total) * 100, 1) : 0,
            'field_goals' => [
                'made' => $fieldGoals->where('is_successful', true)->count(),
                'attempted' => $fieldGoals->count(),
                'percentage' => $fieldGoals->count() > 0 ? round(($fieldGoals->where('is_successful', true)->count() / $fieldGoals->count()) * 100, 1) : 0,
            ],
            'three_pointers' => [
                'made' => $threePointers->where('is_successful', true)->count(),
                'attempted' => $threePointers->count(),
                'percentage' => $threePointers->count() > 0 ? round(($threePointers->where('is_successful', true)->count() / $threePointers->count()) * 100, 1) : 0,
            ],
            'free_throws' => [
                'made' => $freeThrows->where('is_successful', true)->count(),
                'attempted' => $freeThrows->count(),
                'percentage' => $freeThrows->count() > 0 ? round(($freeThrows->where('is_successful', true)->count() / $freeThrows->count()) * 100, 1) : 0,
            ],
            'zones' => $zones,
            'average_distance' => $total > 0 ? round($shots->avg('shot_distance'), 1) : 0,
            'total_points' => $shots->sum('points'),
        ];
    }

    /**
     * Calculate player-specific shot statistics
     *
     * @param \Illuminate\Support\Collection $shots
     * @return array
     */
    private function calculatePlayerShotStatistics($shots): array
    {
        $baseStats = $this->calculateShotStatistics($shots);

        // Zusätzliche Spieler-Statistiken
        $gamesPlayed = $shots->groupBy('game_id')->count();
        $averageShotsPerGame = $gamesPlayed > 0 ? round($shots->count() / $gamesPlayed, 1) : 0;
        $averagePointsPerGame = $gamesPlayed > 0 ? round($shots->sum('points') / $gamesPlayed, 1) : 0;

        // Hot Zones (Zonen mit >40% FG%)
        $hotZones = collect($baseStats['zones'])
            ->filter(fn($zone) => $zone['percentage'] >= 40 && $zone['attempted'] >= 3)
            ->keys()
            ->toArray();

        // Cold Zones (Zonen mit <30% FG%)
        $coldZones = collect($baseStats['zones'])
            ->filter(fn($zone) => $zone['percentage'] < 30 && $zone['attempted'] >= 3)
            ->keys()
            ->toArray();

        return array_merge($baseStats, [
            'games_played' => $gamesPlayed,
            'average_shots_per_game' => $averageShotsPerGame,
            'average_points_per_game' => $averagePointsPerGame,
            'hot_zones' => $hotZones,
            'cold_zones' => $coldZones,
            'shooting_efficiency' => $this->calculateShootingEfficiency($shots),
        ]);
    }

    /**
     * Calculate team-specific shot statistics
     *
     * @param \Illuminate\Support\Collection $shots
     * @return array
     */
    private function calculateTeamShotStatistics($shots): array
    {
        $baseStats = $this->calculateShotStatistics($shots);

        // Team-spezifische Metriken
        $playerStats = $shots->groupBy('player_id')
            ->map(function ($playerShots, $playerId) {
                $playerName = $playerShots->first()['player_name'] ?? 'Unbekannt';
                $made = $playerShots->where('is_successful', true)->count();
                $attempted = $playerShots->count();
                
                return [
                    'player_id' => $playerId,
                    'player_name' => $playerName,
                    'made' => $made,
                    'attempted' => $attempted,
                    'percentage' => $attempted > 0 ? round(($made / $attempted) * 100, 1) : 0,
                    'points' => $playerShots->sum('points'),
                ];
            })
            ->sortByDesc('points')
            ->values()
            ->toArray();

        return array_merge($baseStats, [
            'player_statistics' => $playerStats,
            'top_scorer' => $playerStats[0] ?? null,
            'team_shooting_distribution' => $this->calculateTeamShootingDistribution($shots),
        ]);
    }

    /**
     * Calculate zone-based statistics
     *
     * @param \Illuminate\Support\Collection $shots
     * @return array
     */
    private function calculateZoneStatistics($shots): array
    {
        $zones = [
            'paint' => $shots->filter(fn($shot) => $this->isInPaint($shot)),
            'mid_range' => $shots->filter(fn($shot) => $this->isMidRange($shot)),
            'three_point' => $shots->where('is_three_pointer', true),
            'free_throw' => $shots->where('is_free_throw', true),
        ];

        $zoneStats = [];
        foreach ($zones as $zoneName => $zoneShots) {
            $made = $zoneShots->where('is_successful', true)->count();
            $attempted = $zoneShots->count();
            
            $zoneStats[$zoneName] = [
                'made' => $made,
                'attempted' => $attempted,
                'percentage' => $attempted > 0 ? round(($made / $attempted) * 100, 1) : 0,
                'points' => $zoneShots->sum('points'),
            ];
        }

        return $zoneStats;
    }

    /**
     * Check if shot is in paint area
     *
     * @param array $shot
     * @return bool
     */
    private function isInPaint($shot): bool
    {
        if (!$shot['shot_x'] || !$shot['shot_y']) return false;
        
        $basketX = 14; // Korb X-Position
        $basketY = 7.5; // Korb Y-Position
        $paintHalfWidth = 2.45; // Halbe Paint-Breite
        $paintLength = 5.8; // Paint-Länge
        
        return abs($shot['shot_x'] - $basketX) <= $paintHalfWidth &&
               $shot['shot_y'] <= $paintLength;
    }

    /**
     * Check if shot is mid-range
     *
     * @param array $shot
     * @return bool
     */
    private function isMidRange($shot): bool
    {
        return !$shot['is_three_pointer'] && 
               !$shot['is_free_throw'] && 
               !$this->isInPaint($shot);
    }

    /**
     * Calculate shooting efficiency metrics
     *
     * @param \Illuminate\Support\Collection $shots
     * @return array
     */
    private function calculateShootingEfficiency($shots): array
    {
        $totalPoints = $shots->sum('points');
        $totalShots = $shots->count();
        
        // Effective Field Goal Percentage (berücksichtigt 3-Punkte-Würfe)
        $fieldGoals = $shots->filter(fn($shot) => !$shot['is_free_throw']);
        $fieldGoalsMade = $fieldGoals->where('is_successful', true);
        $threePointersMade = $fieldGoalsMade->where('is_three_pointer', true)->count();
        $totalFieldGoalsMade = $fieldGoalsMade->count();
        $totalFieldGoalsAttempted = $fieldGoals->count();
        
        $effectiveFgPercent = $totalFieldGoalsAttempted > 0 
            ? round((($totalFieldGoalsMade + 0.5 * $threePointersMade) / $totalFieldGoalsAttempted) * 100, 1) 
            : 0;

        // True Shooting Percentage
        $freeThrowsAttempted = $shots->where('is_free_throw', true)->count();
        $trueShotAttempts = $totalFieldGoalsAttempted + (0.44 * $freeThrowsAttempted);
        $trueShootingPercent = $trueShotAttempts > 0 
            ? round(($totalPoints / (2 * $trueShotAttempts)) * 100, 1)
            : 0;

        return [
            'points_per_shot' => $totalShots > 0 ? round($totalPoints / $totalShots, 2) : 0,
            'effective_field_goal_percentage' => $effectiveFgPercent,
            'true_shooting_percentage' => $trueShootingPercent,
        ];
    }

    /**
     * Calculate team shooting distribution
     *
     * @param \Illuminate\Support\Collection $shots
     * @return array
     */
    private function calculateTeamShootingDistribution($shots): array
    {
        $total = $shots->count();
        
        return [
            'paint_percentage' => $total > 0 ? round(($shots->filter(fn($shot) => $this->isInPaint($shot))->count() / $total) * 100, 1) : 0,
            'mid_range_percentage' => $total > 0 ? round(($shots->filter(fn($shot) => $this->isMidRange($shot))->count() / $total) * 100, 1) : 0,
            'three_point_percentage' => $total > 0 ? round(($shots->where('is_three_pointer', true)->count() / $total) * 100, 1) : 0,
            'free_throw_percentage' => $total > 0 ? round(($shots->where('is_free_throw', true)->count() / $total) * 100, 1) : 0,
        ];
    }
}