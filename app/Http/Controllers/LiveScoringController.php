<?php

namespace App\Http\Controllers;

use App\Events\GameActionAdded;
use App\Events\GameActionCorrected;
use App\Events\GameActionDeleted;
use App\Events\GameClockUpdated;
use App\Events\GameFinished;
use App\Events\GameScoreUpdated;
use App\Events\GameStarted;
use App\Events\GameTimeoutEnded;
use App\Events\GameTimeoutStarted;
use App\Http\Requests\AddGameActionRequest;
use App\Http\Requests\UpdateGameScoreRequest;
use App\Jobs\GenerateFinalGameStatistics;
use App\Jobs\UpdateGameStatistics;
use App\Models\Game;
use App\Models\GameAction;
use App\Services\LiveScoringService;
use App\Services\Statistics\StatisticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LiveScoringController extends Controller
{
    public function __construct(
        private LiveScoringService $liveScoringService,
        private StatisticsService $statisticsService
    ) {
        $this->middleware(['auth', 'can:score games']);
    }

    /**
     * Helper method to return appropriate response based on request type.
     * For Inertia requests (web), returns redirect with flash message.
     * For API requests, returns JSON response.
     */
    private function respondWithLiveGame(
        string $message,
        array $data = [],
        bool $success = true,
        int $statusCode = 200
    ): RedirectResponse|JsonResponse {
        // For Inertia requests (web), redirect back with flash message
        if (request()->header('X-Inertia')) {
            return back()->with('flash', [
                'success' => $success,
                'message' => $message,
            ]);
        }

        // For API requests, return JSON
        return response()->json(array_merge([
            'success' => $success,
            'message' => $message,
        ], $data), $statusCode);
    }

    /**
     * Show the live scoring interface.
     */
    public function show(Game $game): Response
    {
        $this->authorize('score', $game);

        $game->load([
            'homeTeam.activePlayers',
            'awayTeam.activePlayers',
            'liveGame',
            'gameActions' => function ($query) {
                $query->with(['player', 'assistedByPlayer'])
                    ->latest()
                    ->limit(20);
            },
        ]);

        return Inertia::render('Games/LiveScoring', [
            'game' => $game,
            'liveGame' => $game->liveGame,
            'recentActions' => $game->gameActions,
            'homeRoster' => $game->homeTeam?->activePlayers ?? collect(),
            'awayRoster' => $game->awayTeam?->activePlayers ?? collect(),
            'hasExternalTeams' => $game->hasExternalTeams(),
            'isHomeTeamExternal' => $game->isHomeTeamExternal(),
            'isAwayTeamExternal' => $game->isAwayTeamExternal(),
            'canControl' => auth()->user()->can('controlGame', $game),
        ]);
    }

    /**
     * Start a game.
     */
    public function startGame(Game $game): RedirectResponse|JsonResponse
    {
        $this->authorize('controlGame', $game);

        try {
            $liveGame = $this->liveScoringService->startGame($game);

            broadcast(new GameStarted($game, $liveGame));

            return $this->respondWithLiveGame('Spiel gestartet.', [
                'liveGame' => $liveGame,
            ]);
        } catch (\Exception $e) {
            return $this->respondWithLiveGame(
                'Spiel konnte nicht gestartet werden: '.$e->getMessage(),
                [],
                false,
                400
            );
        }
    }

    /**
     * Add a game action.
     */
    public function addAction(AddGameActionRequest $request, Game $game): RedirectResponse|JsonResponse
    {
        $this->authorize('score', $game);

        try {
            DB::beginTransaction();

            $action = $this->liveScoringService->addGameAction($game, $request->validated());

            // Update live game state
            $liveGame = $this->liveScoringService->updateLiveGameState($game, $action);

            // Broadcast the action
            broadcast(new GameActionAdded($game, $action, $liveGame));

            // Update statistics asynchronously
            UpdateGameStatistics::dispatch($game)->onQueue('high');

            DB::commit();

            return $this->respondWithLiveGame('Aktion hinzugefügt.', [
                'action' => $action->load(['player', 'assistedByPlayer']),
                'liveGame' => $liveGame,
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return $this->respondWithLiveGame(
                'Aktion konnte nicht hinzugefügt werden: '.$e->getMessage(),
                [],
                false,
                400
            );
        }
    }

    /**
     * Update game score.
     */
    public function updateScore(UpdateGameScoreRequest $request, Game $game): RedirectResponse|JsonResponse
    {
        $this->authorize('score', $game);

        try {
            $liveGame = $this->liveScoringService->updateScore(
                $game,
                $request->team,
                $request->points,
                $request->player_id
            );

            broadcast(new GameScoreUpdated($game, $liveGame));

            return $this->respondWithLiveGame('Spielstand aktualisiert.', [
                'liveGame' => $liveGame,
            ]);
        } catch (\Exception $e) {
            return $this->respondWithLiveGame(
                'Spielstand konnte nicht aktualisiert werden: '.$e->getMessage(),
                [],
                false,
                400
            );
        }
    }

    /**
     * Control game clock.
     */
    public function controlClock(Request $request, Game $game): RedirectResponse|JsonResponse
    {
        $this->authorize('controlGame', $game);

        $request->validate([
            'action' => 'required|in:start,pause,resume,end_period',
        ]);

        try {
            $liveGame = match ($request->action) {
                'start' => $this->liveScoringService->startPeriod($game),
                'pause' => $this->liveScoringService->pausePeriod($game),
                'resume' => $this->liveScoringService->resumePeriod($game),
                'end_period' => $this->liveScoringService->endPeriod($game),
            };

            broadcast(new GameClockUpdated($game, $liveGame, $request->action));

            return $this->respondWithLiveGame('Spielzeit aktualisiert.', [
                'liveGame' => $liveGame,
            ]);
        } catch (\Exception $e) {
            return $this->respondWithLiveGame(
                'Spielzeit konnte nicht aktualisiert werden: '.$e->getMessage(),
                [],
                false,
                400
            );
        }
    }

    /**
     * Start a timeout.
     */
    public function timeout(Request $request, Game $game): RedirectResponse|JsonResponse
    {
        $this->authorize('controlGame', $game);

        $request->validate([
            'team' => 'required|in:home,away,official',
            'duration' => 'integer|min:30|max:300',
        ]);

        try {
            $liveGame = $this->liveScoringService->startTimeout(
                $game,
                $request->team,
                $request->duration ?? 60
            );

            broadcast(new GameTimeoutStarted($game, $liveGame, $request->team));

            return $this->respondWithLiveGame('Timeout gestartet.', [
                'liveGame' => $liveGame,
            ]);
        } catch (\Exception $e) {
            return $this->respondWithLiveGame(
                'Timeout konnte nicht gestartet werden: '.$e->getMessage(),
                [],
                false,
                400
            );
        }
    }

    /**
     * End a timeout.
     */
    public function endTimeout(Game $game): RedirectResponse|JsonResponse
    {
        $this->authorize('controlGame', $game);

        try {
            $liveGame = $this->liveScoringService->endTimeout($game);

            broadcast(new GameTimeoutEnded($game, $liveGame));

            return $this->respondWithLiveGame('Timeout beendet.', [
                'liveGame' => $liveGame,
            ]);
        } catch (\Exception $e) {
            return $this->respondWithLiveGame(
                'Timeout konnte nicht beendet werden: '.$e->getMessage(),
                [],
                false,
                400
            );
        }
    }

    /**
     * Handle player substitution.
     */
    public function substitution(Request $request, Game $game): RedirectResponse|JsonResponse
    {
        $this->authorize('score', $game);

        $request->validate([
            'team' => 'required|in:home,away',
            'player_in_id' => 'required|exists:players,id',
            'player_out_id' => 'required|exists:players,id',
            'reason' => 'string|max:255',
        ]);

        try {
            $this->liveScoringService->processSubstitution(
                $game,
                $request->team,
                $request->player_in_id,
                $request->player_out_id,
                $request->reason
            );

            return $this->respondWithLiveGame('Auswechslung durchgeführt.');
        } catch (\Exception $e) {
            return $this->respondWithLiveGame(
                'Auswechslung konnte nicht durchgeführt werden: '.$e->getMessage(),
                [],
                false,
                400
            );
        }
    }

    /**
     * Correct a game action.
     */
    public function correctAction(Request $request, GameAction $action): RedirectResponse|JsonResponse
    {
        $this->authorize('correctAction', $action);

        $request->validate([
            'correction_reason' => 'required|string|max:500',
            'corrected_data' => 'array',
        ]);

        try {
            $correctedAction = $this->liveScoringService->correctAction(
                $action,
                $request->corrected_data ?? [],
                $request->correction_reason
            );

            broadcast(new GameActionCorrected($action->game, $correctedAction));

            return $this->respondWithLiveGame('Aktion korrigiert.', [
                'action' => $correctedAction,
            ]);
        } catch (\Exception $e) {
            return $this->respondWithLiveGame(
                'Aktion konnte nicht korrigiert werden: '.$e->getMessage(),
                [],
                false,
                400
            );
        }
    }

    /**
     * Delete a game action.
     */
    public function deleteAction(GameAction $action): RedirectResponse|JsonResponse
    {
        $this->authorize('deleteAction', $action);

        try {
            $game = $action->game;

            $this->liveScoringService->deleteAction($action);

            broadcast(new GameActionDeleted($game, $action->id));

            return $this->respondWithLiveGame('Aktion gelöscht.');
        } catch (\Exception $e) {
            return $this->respondWithLiveGame(
                'Aktion konnte nicht gelöscht werden: '.$e->getMessage(),
                [],
                false,
                400
            );
        }
    }

    /**
     * Finish a game.
     */
    public function finishGame(Game $game): RedirectResponse|JsonResponse
    {
        $this->authorize('controlGame', $game);

        try {
            $finishedGame = $this->liveScoringService->finishGame($game);

            broadcast(new GameFinished($finishedGame));

            // Generate final statistics
            GenerateFinalGameStatistics::dispatch($finishedGame);

            return $this->respondWithLiveGame('Spiel beendet.', [
                'game' => $finishedGame,
            ]);
        } catch (\Exception $e) {
            return $this->respondWithLiveGame(
                'Spiel konnte nicht beendet werden: '.$e->getMessage(),
                [],
                false,
                400
            );
        }
    }

    /**
     * Get live game data.
     */
    public function getLiveData(Game $game): JsonResponse
    {
        $liveGame = $game->liveGame;

        if (! $liveGame) {
            return response()->json([
                'success' => false,
                'message' => 'Spiel ist nicht live.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'liveGame' => $liveGame,
                'recentActions' => $game->gameActions()
                    ->with(['player', 'assistedByPlayer'])
                    ->latest()
                    ->limit(10)
                    ->get(),
                'currentStats' => $this->statisticsService->getCurrentGameStats($game),
            ],
        ]);
    }

    /**
     * Get game statistics.
     */
    public function getGameStatistics(Game $game): JsonResponse
    {
        $this->authorize('view', $game);

        try {
            $homeStats = $game->homeTeam
                ? $this->statisticsService->getTeamGameStats($game->homeTeam, $game)
                : null;
            $awayStats = $game->awayTeam
                ? $this->statisticsService->getTeamGameStats($game->awayTeam, $game)
                : null;

            return response()->json([
                'success' => true,
                'data' => [
                    'game' => $game->getSummary(),
                    'statistics' => [
                        'home' => $homeStats,
                        'away' => $awayStats,
                    ],
                    'hasExternalTeams' => $game->hasExternalTeams(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Statistiken konnten nicht geladen werden: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reset shot clock.
     */
    public function resetShotClock(Request $request, Game $game): RedirectResponse|JsonResponse
    {
        $this->authorize('controlGame', $game);

        $request->validate([
            'seconds' => 'integer|min:1|max:30',
        ]);

        try {
            $liveGame = $game->liveGame;
            if (! $liveGame) {
                throw new \Exception('Spiel ist nicht live.');
            }

            $liveGame->resetShotClock($request->seconds);

            broadcast(new GameClockUpdated($game, $liveGame, 'shot_clock_reset'));

            return $this->respondWithLiveGame('24-Sekunden-Uhr zurückgesetzt.', [
                'liveGame' => $liveGame,
            ]);
        } catch (\Exception $e) {
            return $this->respondWithLiveGame(
                '24-Sekunden-Uhr konnte nicht zurückgesetzt werden: '.$e->getMessage(),
                [],
                false,
                400
            );
        }
    }

    /**
     * Update players on court.
     */
    public function updatePlayersOnCourt(Request $request, Game $game): RedirectResponse|JsonResponse
    {
        $this->authorize('score', $game);

        $request->validate([
            'team' => 'required|in:home,away',
            'player_ids' => 'required|array|size:5',
            'player_ids.*' => 'exists:players,id',
        ]);

        try {
            $liveGame = $game->liveGame;
            if (! $liveGame) {
                throw new \Exception('Spiel ist nicht live.');
            }

            $liveGame->updatePlayersOnCourt($request->team, $request->player_ids);

            return $this->respondWithLiveGame('Aufstellung aktualisiert.', [
                'liveGame' => $liveGame,
            ]);
        } catch (\Exception $e) {
            return $this->respondWithLiveGame(
                'Aufstellung konnte nicht aktualisiert werden: '.$e->getMessage(),
                [],
                false,
                400
            );
        }
    }
}
