<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Services\StatisticsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GameController extends Controller
{
    public function __construct(
        private StatisticsService $statisticsService
    ) {}

    /**
     * Display a listing of games.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $games = Game::query()
            ->with(['homeTeam.club', 'awayTeam.club'])
            ->when($user->hasRole('admin') || $user->hasRole('super_admin'), function ($query) {
                return $query;
            }, function ($query) use ($user) {
                return $query->where(function ($q) use ($user) {
                    $q->whereHas('homeTeam', function ($subQ) use ($user) {
                        $subQ->where('head_coach_id', $user->id)
                            ->orWhereJsonContains('assistant_coaches', $user->id)
                            ->orWhereHas('club.users', function ($clubQ) use ($user) {
                                $clubQ->where('user_id', $user->id);
                            });
                    })->orWhereHas('awayTeam', function ($subQ) use ($user) {
                        $subQ->where('head_coach_id', $user->id)
                            ->orWhereJsonContains('assistant_coaches', $user->id)
                            ->orWhereHas('club.users', function ($clubQ) use ($user) {
                                $clubQ->where('user_id', $user->id);
                            });
                    });
                });
            })
            ->orderBy('scheduled_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $games,
        ]);
    }

    /**
     * Store a newly created game in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Game::class);

        $validated = $request->validate([
            'home_team_id' => 'required|exists:teams,id',
            'away_team_id' => 'nullable|exists:teams,id|different:home_team_id',
            'away_team_name' => 'nullable|string|max:255',
            'home_team_name' => 'nullable|string|max:255',
            'scheduled_at' => 'required|date|after:now',
            'venue' => 'nullable|string|max:255',
            'venue_address' => 'nullable|string|max:500',
            'venue_code' => 'nullable|string|max:50',
            'type' => 'required|in:regular_season,playoff,championship,friendly,tournament,scrimmage',
            'season' => 'required|string|max:9',
            'league' => 'nullable|string|max:255',
            'division' => 'nullable|string|max:255',
            'pre_game_notes' => 'nullable|string|max:1000',
        ]);

        // Validation: either away_team_id OR away_team_name must be provided
        if (empty($validated['away_team_id']) && empty($validated['away_team_name'])) {
            return response()->json([
                'success' => false,
                'message' => 'Either an internal team or external team name must be provided.',
                'errors' => [
                    'away_team_id' => ['Either an internal team or external team name must be provided.'],
                    'away_team_name' => ['Either an internal team or external team name must be provided.'],
                ]
            ], 422);
        }

        $validated['import_source'] = 'manual';
        $validated['is_home_game'] = true;

        $game = Game::create($validated);
        $game->load(['homeTeam.club', 'awayTeam.club']);

        return response()->json([
            'success' => true,
            'message' => 'Game created successfully.',
            'data' => $game,
        ], 201);
    }

    /**
     * Display the specified game.
     */
    public function show(Game $game): JsonResponse
    {
        $this->authorize('view', $game);

        $game->load([
            'homeTeam.club',
            'awayTeam.club',
            'gameActions.player',
            'liveGame',
        ]);

        $gameStats = null;
        if ($game->status === 'finished') {
            $gameStats = $this->statisticsService->getGameStatistics($game);
        }

        // Add computed display names for safe frontend access
        $game->home_team_display_name = $game->getHomeTeamDisplayName();
        $game->away_team_display_name = $game->getAwayTeamDisplayName();

        return response()->json([
            'success' => true,
            'data' => $game,
            'statistics' => $gameStats,
        ]);
    }

    /**
     * Update the specified game in storage.
     */
    public function update(Request $request, Game $game): JsonResponse
    {
        $this->authorize('update', $game);

        $validated = $request->validate([
            'home_team_id' => 'required|exists:teams,id',
            'away_team_id' => 'nullable|exists:teams,id|different:home_team_id',
            'away_team_name' => 'nullable|string|max:255',
            'home_team_name' => 'nullable|string|max:255',
            'scheduled_at' => 'required|date',
            'venue' => 'nullable|string|max:255',
            'venue_address' => 'nullable|string|max:500',
            'venue_code' => 'nullable|string|max:50',
            'type' => 'required|in:regular_season,playoff,championship,friendly,tournament,scrimmage',
            'season' => 'required|string|max:9',
            'league' => 'nullable|string|max:255',
            'division' => 'nullable|string|max:255',
            'pre_game_notes' => 'nullable|string|max:1000',
            'status' => 'required|in:scheduled,live,halftime,overtime,finished,cancelled,postponed,forfeited',
        ]);

        // Validation: either away_team_id OR away_team_name must be provided
        if (empty($validated['away_team_id']) && empty($validated['away_team_name'])) {
            return response()->json([
                'success' => false,
                'message' => 'Either an internal team or external team name must be provided.',
                'errors' => [
                    'away_team_id' => ['Either an internal team or external team name must be provided.'],
                    'away_team_name' => ['Either an internal team or external team name must be provided.'],
                ]
            ], 422);
        }

        $game->update($validated);
        $game->load(['homeTeam.club', 'awayTeam.club']);

        return response()->json([
            'success' => true,
            'message' => 'Game updated successfully.',
            'data' => $game,
        ]);
    }

    /**
     * Get live score for a game.
     */
    public function liveScore(Game $game): JsonResponse
    {
        $this->authorize('view', $game);

        $game->load([
            'homeTeam:id,name',
            'awayTeam:id,name',
            'liveGame'
        ]);

        $liveData = [
            'id' => $game->id,
            'status' => $game->status,
            'home_team' => [
                'id' => $game->homeTeam?->id,
                'name' => $game->getHomeTeamDisplayName(),
                'score' => $game->home_team_score,
            ],
            'away_team' => [
                'id' => $game->awayTeam?->id,
                'name' => $game->getAwayTeamDisplayName(), 
                'score' => $game->away_team_score,
            ],
            'current_period' => $game->current_period,
            'total_periods' => $game->total_periods,
            'time_remaining_seconds' => $game->time_remaining_seconds,
            'clock_running' => $game->clock_running,
            'period_scores' => $game->period_scores,
            'scheduled_at' => $game->scheduled_at,
            'actual_start_time' => $game->actual_start_time,
        ];

        return response()->json([
            'success' => true,
            'data' => $liveData,
        ]);
    }
}