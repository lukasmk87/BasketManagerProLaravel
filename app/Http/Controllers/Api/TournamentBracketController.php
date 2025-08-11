<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournament\RecordGameResultRequest;
use App\Http\Requests\Tournament\ScheduleGameRequest;
use App\Http\Resources\TournamentBracketResource;
use App\Models\Tournament;
use App\Models\TournamentBracket;
use App\Models\TournamentTeam;
use App\Services\TournamentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TournamentBracketController extends Controller
{
    protected TournamentService $tournamentService;

    public function __construct(TournamentService $tournamentService)
    {
        $this->tournamentService = $tournamentService;
    }

    /**
     * Get tournament brackets.
     */
    public function index(Tournament $tournament, Request $request): JsonResponse
    {
        $query = $tournament->brackets()
                           ->with(['team1.team', 'team2.team', 'winnerTeam.team', 'loserTeam.team']);

        // Filters
        if ($request->has('bracket_type')) {
            $query->where('bracket_type', $request->bracket_type);
        }

        if ($request->has('round')) {
            $query->where('round', $request->round);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('group')) {
            $query->where('group_name', $request->group);
        }

        // Sorting
        $query->orderBy('bracket_type')
              ->orderBy('round')
              ->orderBy('position_in_round');

        $brackets = $query->get();

        // Group brackets for better structure
        $groupedBrackets = $brackets->groupBy(['bracket_type', 'round']);

        return response()->json([
            'data' => $groupedBrackets->map(function ($bracketType) {
                return $bracketType->map(function ($roundBrackets) {
                    return TournamentBracketResource::collection($roundBrackets);
                });
            }),
            'meta' => [
                'total_brackets' => $brackets->count(),
                'completed_brackets' => $brackets->where('status', 'completed')->count(),
                'scheduled_brackets' => $brackets->where('status', 'scheduled')->count(),
                'pending_brackets' => $brackets->where('status', 'pending')->count(),
            ],
        ]);
    }

    /**
     * Get specific bracket.
     */
    public function show(Tournament $tournament, TournamentBracket $bracket): JsonResponse
    {
        if ($bracket->tournament_id !== $tournament->id) {
            return response()->json(['message' => 'Bracket gehört nicht zu diesem Turnier'], 404);
        }

        $bracket->load([
            'team1.team',
            'team2.team',
            'winnerTeam.team',
            'loserTeam.team',
            'game',
            'primaryReferee',
            'secondaryReferee'
        ]);

        return response()->json([
            'data' => new TournamentBracketResource($bracket),
        ]);
    }

    /**
     * Schedule a bracket game.
     */
    public function schedule(
        Tournament $tournament,
        TournamentBracket $bracket,
        ScheduleGameRequest $request
    ): JsonResponse {
        try {
            if ($bracket->tournament_id !== $tournament->id) {
                return response()->json(['message' => 'Bracket gehört nicht zu diesem Turnier'], 404);
            }

            $this->authorize('update', $tournament);

            $bracket = $this->tournamentService->scheduleGame(
                $bracket,
                new \DateTime($request->scheduled_at),
                $request->only(['venue', 'court'])
            );

            return response()->json([
                'message' => 'Spiel erfolgreich angesetzt',
                'data' => new TournamentBracketResource($bracket->load(['team1.team', 'team2.team'])),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Ansetzen des Spiels',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Start a bracket game.
     */
    public function start(Tournament $tournament, TournamentBracket $bracket): JsonResponse
    {
        try {
            if ($bracket->tournament_id !== $tournament->id) {
                return response()->json(['message' => 'Bracket gehört nicht zu diesem Turnier'], 404);
            }

            $this->authorize('update', $tournament);

            if (!$bracket->can_start) {
                return response()->json(['message' => 'Spiel kann nicht gestartet werden'], 422);
            }

            $bracket->start();

            return response()->json([
                'message' => 'Spiel erfolgreich gestartet',
                'data' => new TournamentBracketResource($bracket->load(['team1.team', 'team2.team'])),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Starten des Spiels',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Record game result.
     */
    public function recordResult(
        Tournament $tournament,
        TournamentBracket $bracket,
        RecordGameResultRequest $request
    ): JsonResponse {
        try {
            if ($bracket->tournament_id !== $tournament->id) {
                return response()->json(['message' => 'Bracket gehört nicht zu diesem Turnier'], 404);
            }

            $this->authorize('update', $tournament);

            $success = $this->tournamentService->recordGameResult($bracket, $request->validated());

            if ($success) {
                $tournament->refresh();
                
                return response()->json([
                    'message' => 'Spielergebnis erfolgreich eingetragen',
                    'data' => new TournamentBracketResource($bracket->load(['team1.team', 'team2.team', 'winnerTeam.team'])),
                    'tournament_updated' => true,
                ]);
            }

            return response()->json([
                'message' => 'Fehler beim Eintragen des Spielergebnisses',
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Eintragen des Spielergebnisses',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Record a forfeit.
     */
    public function forfeit(Tournament $tournament, TournamentBracket $bracket, Request $request): JsonResponse
    {
        try {
            if ($bracket->tournament_id !== $tournament->id) {
                return response()->json(['message' => 'Bracket gehört nicht zu diesem Turnier'], 404);
            }

            $this->authorize('update', $tournament);

            $request->validate([
                'forfeit_team_id' => 'required|exists:tournament_teams,id',
                'reason' => 'sometimes|string|max:500',
            ]);

            $forfeitTeam = TournamentTeam::findOrFail($request->forfeit_team_id);

            // Verify the team is in this bracket
            if ($forfeitTeam->id !== $bracket->team1_id && $forfeitTeam->id !== $bracket->team2_id) {
                return response()->json(['message' => 'Team ist nicht Teil dieses Spiels'], 422);
            }

            $success = $this->tournamentService->forfeitGame(
                $bracket,
                $forfeitTeam,
                $request->get('reason')
            );

            if ($success) {
                return response()->json([
                    'message' => 'Aufgabe erfolgreich eingetragen',
                    'data' => new TournamentBracketResource($bracket->load(['team1.team', 'team2.team', 'winnerTeam.team'])),
                ]);
            }

            return response()->json([
                'message' => 'Fehler beim Eintragen der Aufgabe',
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Eintragen der Aufgabe',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Assign officials to a bracket.
     */
    public function assignOfficials(Tournament $tournament, TournamentBracket $bracket, Request $request): JsonResponse
    {
        try {
            if ($bracket->tournament_id !== $tournament->id) {
                return response()->json(['message' => 'Bracket gehört nicht zu diesem Turnier'], 404);
            }

            $this->authorize('update', $tournament);

            $request->validate([
                'primary_referee_id' => 'sometimes|exists:users,id',
                'secondary_referee_id' => 'sometimes|exists:users,id',
                'scorekeeper' => 'sometimes|string|max:255',
                'officials_fee' => 'sometimes|numeric|min:0',
            ]);

            $bracket->update($request->only([
                'primary_referee_id',
                'secondary_referee_id',
                'scorekeeper',
            ]));

            // Update fee in tournament game if exists
            if ($bracket->game && $request->has('officials_fee')) {
                $bracket->game->tournamentGame?->update([
                    'officials_fee' => $request->officials_fee,
                ]);
            }

            return response()->json([
                'message' => 'Officials erfolgreich zugewiesen',
                'data' => new TournamentBracketResource($bracket->load(['primaryReferee', 'secondaryReferee'])),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Zuweisen der Officials',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get bracket progression tree.
     */
    public function progression(Tournament $tournament, TournamentBracket $bracket): JsonResponse
    {
        if ($bracket->tournament_id !== $tournament->id) {
            return response()->json(['message' => 'Bracket gehört nicht zu diesem Turnier'], 404);
        }

        // Get the complete progression tree for this bracket
        $progression = [
            'current' => new TournamentBracketResource($bracket->load(['team1.team', 'team2.team'])),
            'feeds_from' => $this->getFeedsFromBrackets($bracket),
            'feeds_to' => $this->getFeedsToBrackets($bracket),
        ];

        return response()->json([
            'data' => $progression,
        ]);
    }

    /**
     * Get upcoming brackets (scheduled but not started).
     */
    public function upcoming(Tournament $tournament, Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        
        $upcomingBrackets = $tournament->brackets()
                                     ->where('status', 'scheduled')
                                     ->whereNotNull('scheduled_at')
                                     ->orderBy('scheduled_at')
                                     ->with(['team1.team', 'team2.team'])
                                     ->limit($limit)
                                     ->get();

        return response()->json([
            'data' => TournamentBracketResource::collection($upcomingBrackets),
        ]);
    }

    /**
     * Get completed brackets.
     */
    public function completed(Tournament $tournament, Request $request): JsonResponse
    {
        $query = $tournament->brackets()
                           ->where('status', 'completed')
                           ->with(['team1.team', 'team2.team', 'winnerTeam.team'])
                           ->orderBy('actual_end_time', 'desc');

        if ($request->has('round')) {
            $query->where('round', $request->round);
        }

        if ($request->has('bracket_type')) {
            $query->where('bracket_type', $request->bracket_type);
        }

        $brackets = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => TournamentBracketResource::collection($brackets),
            'meta' => [
                'current_page' => $brackets->currentPage(),
                'last_page' => $brackets->lastPage(),
                'per_page' => $brackets->perPage(),
                'total' => $brackets->total(),
            ],
        ]);
    }

    /**
     * Get brackets by round.
     */
    public function byRound(Tournament $tournament, int $round): JsonResponse
    {
        $brackets = $tournament->brackets()
                              ->where('round', $round)
                              ->with(['team1.team', 'team2.team', 'winnerTeam.team'])
                              ->orderBy('position_in_round')
                              ->get();

        if ($brackets->isEmpty()) {
            return response()->json(['message' => 'Keine Brackets für diese Runde gefunden'], 404);
        }

        $roundInfo = [
            'round_number' => $round,
            'round_name' => $brackets->first()->round_name,
            'total_brackets' => $brackets->count(),
            'completed_brackets' => $brackets->where('status', 'completed')->count(),
            'brackets' => TournamentBracketResource::collection($brackets),
        ];

        return response()->json([
            'data' => $roundInfo,
        ]);
    }

    // Helper Methods
    protected function getFeedsFromBrackets(TournamentBracket $bracket): array
    {
        $feedsFrom = [];

        // Find brackets that feed into this one (winner advances to this bracket)
        $winnerSources = TournamentBracket::where('winner_advances_to', $bracket->id)
                                        ->with(['team1.team', 'team2.team', 'winnerTeam.team'])
                                        ->get();

        foreach ($winnerSources as $source) {
            $feedsFrom[] = [
                'type' => 'winner',
                'bracket' => new TournamentBracketResource($source),
            ];
        }

        // Find brackets that feed into this one (loser advances to this bracket)
        $loserSources = TournamentBracket::where('loser_advances_to', $bracket->id)
                                       ->with(['team1.team', 'team2.team', 'loserTeam.team'])
                                       ->get();

        foreach ($loserSources as $source) {
            $feedsFrom[] = [
                'type' => 'loser',
                'bracket' => new TournamentBracketResource($source),
            ];
        }

        return $feedsFrom;
    }

    protected function getFeedsToBrackets(TournamentBracket $bracket): array
    {
        $feedsTo = [];

        // Winner advances to
        if ($bracket->winner_advances_to) {
            $winnerTarget = TournamentBracket::find($bracket->winner_advances_to);
            if ($winnerTarget) {
                $feedsTo[] = [
                    'type' => 'winner_to',
                    'bracket' => new TournamentBracketResource(
                        $winnerTarget->load(['team1.team', 'team2.team'])
                    ),
                ];
            }
        }

        // Loser advances to
        if ($bracket->loser_advances_to) {
            $loserTarget = TournamentBracket::find($bracket->loser_advances_to);
            if ($loserTarget) {
                $feedsTo[] = [
                    'type' => 'loser_to',
                    'bracket' => new TournamentBracketResource(
                        $loserTarget->load(['team1.team', 'team2.team'])
                    ),
                ];
            }
        }

        return $feedsTo;
    }
}