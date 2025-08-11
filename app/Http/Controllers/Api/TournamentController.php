<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournament\CreateTournamentRequest;
use App\Http\Requests\Tournament\UpdateTournamentRequest;
use App\Http\Resources\TournamentResource;
use App\Models\Tournament;
use App\Services\TournamentService;
use App\Services\TournamentAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TournamentController extends Controller
{
    protected TournamentService $tournamentService;
    protected TournamentAnalyticsService $analyticsService;

    public function __construct(
        TournamentService $tournamentService,
        TournamentAnalyticsService $analyticsService
    ) {
        $this->tournamentService = $tournamentService;
        $this->analyticsService = $analyticsService;
    }

    /**
     * Display a listing of tournaments.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Tournament::query();

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('gender')) {
            $query->where('gender', $request->gender);
        }

        if ($request->has('public_only') && $request->boolean('public_only')) {
            $query->public();
        }

        if ($request->has('upcoming') && $request->boolean('upcoming')) {
            $query->upcoming();
        }

        if ($request->has('in_progress') && $request->boolean('in_progress')) {
            $query->inProgress();
        }

        if ($request->has('completed') && $request->boolean('completed')) {
            $query->completed();
        }

        if ($request->has('registration_open') && $request->boolean('registration_open')) {
            $query->registrationOpen();
        }

        if ($request->has('organizer_id')) {
            $query->where('organizer_id', $request->organizer_id);
        }

        if ($request->has('club_id')) {
            $query->where('club_id', $request->club_id);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('primary_venue', 'like', "%{$search}%");
            });
        }

        // Date range filters
        if ($request->has('start_date_from')) {
            $query->where('start_date', '>=', $request->start_date_from);
        }

        if ($request->has('start_date_to')) {
            $query->where('start_date', '<=', $request->start_date_to);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'start_date');
        $sortOrder = $request->get('sort_order', 'desc');
        
        if (in_array($sortBy, ['name', 'start_date', 'end_date', 'registered_teams', 'created_at'])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Load relationships
        $with = ['organizer', 'club'];
        if ($request->has('with_teams')) {
            $with[] = 'tournamentTeams.team';
        }
        if ($request->has('with_officials')) {
            $with[] = 'officials.user';
        }

        $tournaments = $query->with($with)
                            ->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => TournamentResource::collection($tournaments),
            'meta' => [
                'current_page' => $tournaments->currentPage(),
                'last_page' => $tournaments->lastPage(),
                'per_page' => $tournaments->perPage(),
                'total' => $tournaments->total(),
            ],
            'links' => [
                'first' => $tournaments->url(1),
                'last' => $tournaments->url($tournaments->lastPage()),
                'prev' => $tournaments->previousPageUrl(),
                'next' => $tournaments->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Store a newly created tournament.
     */
    public function store(CreateTournamentRequest $request): JsonResponse
    {
        try {
            $tournament = $this->tournamentService->createTournament(
                $request->validated(),
                $request->user()
            );

            return response()->json([
                'message' => 'Turnier erfolgreich erstellt',
                'data' => new TournamentResource($tournament->load(['organizer', 'club'])),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Erstellen des Turniers',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display the specified tournament.
     */
    public function show(Tournament $tournament, Request $request): JsonResponse
    {
        // Load relationships based on request
        $with = ['organizer', 'club'];
        
        if ($request->has('with_teams')) {
            $with[] = 'tournamentTeams.team';
        }
        
        if ($request->has('with_brackets')) {
            $with[] = 'brackets.team1.team';
            $with[] = 'brackets.team2.team';
            $with[] = 'brackets.winnerTeam.team';
        }
        
        if ($request->has('with_officials')) {
            $with[] = 'officials.user';
        }
        
        if ($request->has('with_awards')) {
            $with[] = 'awards.recipientTeam.team';
            $with[] = 'awards.recipientPlayer';
        }

        $tournament->load($with);

        return response()->json([
            'data' => new TournamentResource($tournament),
        ]);
    }

    /**
     * Update the specified tournament.
     */
    public function update(UpdateTournamentRequest $request, Tournament $tournament): JsonResponse
    {
        try {
            $this->authorize('update', $tournament);

            $tournament = $this->tournamentService->updateTournament(
                $tournament,
                $request->validated()
            );

            return response()->json([
                'message' => 'Turnier erfolgreich aktualisiert',
                'data' => new TournamentResource($tournament->load(['organizer', 'club'])),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Aktualisieren des Turniers',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Remove the specified tournament.
     */
    public function destroy(Tournament $tournament): JsonResponse
    {
        try {
            $this->authorize('delete', $tournament);

            $this->tournamentService->deleteTournament($tournament);

            return response()->json([
                'message' => 'Turnier erfolgreich gelöscht',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Löschen des Turniers',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    // Tournament Status Management

    /**
     * Open tournament registration.
     */
    public function openRegistration(Tournament $tournament): JsonResponse
    {
        try {
            $this->authorize('update', $tournament);

            $tournament = $this->tournamentService->openRegistration($tournament);

            return response()->json([
                'message' => 'Anmeldung erfolgreich geöffnet',
                'data' => new TournamentResource($tournament),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Öffnen der Anmeldung',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Close tournament registration.
     */
    public function closeRegistration(Tournament $tournament): JsonResponse
    {
        try {
            $this->authorize('update', $tournament);

            $tournament = $this->tournamentService->closeRegistration($tournament);

            return response()->json([
                'message' => 'Anmeldung erfolgreich geschlossen',
                'data' => new TournamentResource($tournament),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Schließen der Anmeldung',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Start tournament.
     */
    public function start(Tournament $tournament): JsonResponse
    {
        try {
            $this->authorize('update', $tournament);

            $tournament = $this->tournamentService->startTournament($tournament);

            return response()->json([
                'message' => 'Turnier erfolgreich gestartet',
                'data' => new TournamentResource($tournament),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Starten des Turniers',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Complete tournament.
     */
    public function complete(Tournament $tournament): JsonResponse
    {
        try {
            $this->authorize('update', $tournament);

            $tournament = $this->tournamentService->completeTournament($tournament);

            return response()->json([
                'message' => 'Turnier erfolgreich abgeschlossen',
                'data' => new TournamentResource($tournament),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Abschließen des Turniers',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    // Bracket Management

    /**
     * Generate tournament brackets.
     */
    public function generateBrackets(Tournament $tournament): JsonResponse
    {
        try {
            $this->authorize('update', $tournament);

            $success = $this->tournamentService->generateBrackets($tournament);

            if ($success) {
                $tournament->load(['brackets.team1.team', 'brackets.team2.team']);
                
                return response()->json([
                    'message' => 'Brackets erfolgreich generiert',
                    'data' => new TournamentResource($tournament),
                ]);
            }

            return response()->json([
                'message' => 'Fehler beim Generieren der Brackets',
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Generieren der Brackets',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Regenerate tournament brackets.
     */
    public function regenerateBrackets(Tournament $tournament): JsonResponse
    {
        try {
            $this->authorize('update', $tournament);

            $success = $this->tournamentService->regenerateBrackets($tournament);

            if ($success) {
                $tournament->load(['brackets.team1.team', 'brackets.team2.team']);
                
                return response()->json([
                    'message' => 'Brackets erfolgreich neu generiert',
                    'data' => new TournamentResource($tournament),
                ]);
            }

            return response()->json([
                'message' => 'Fehler beim Neu-Generieren der Brackets',
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Neu-Generieren der Brackets',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update team seeding.
     */
    public function updateSeeding(Tournament $tournament, Request $request): JsonResponse
    {
        try {
            $this->authorize('update', $tournament);

            $request->validate([
                'seeding' => 'required|array',
                'seeding.*.team_id' => 'required|integer|exists:teams,id',
                'seeding.*.seed' => 'required|integer|min:1',
            ]);

            $seedingData = collect($request->seeding)
                          ->pluck('seed', 'team_id')
                          ->toArray();

            $tournament = $this->tournamentService->seedTeams($tournament, $seedingData);

            return response()->json([
                'message' => 'Seeding erfolgreich aktualisiert',
                'data' => new TournamentResource($tournament->load(['tournamentTeams.team'])),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Aktualisieren des Seedings',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    // Statistics and Analytics

    /**
     * Get tournament statistics.
     */
    public function statistics(Tournament $tournament): JsonResponse
    {
        $statistics = $this->tournamentService->getTournamentStatistics($tournament);

        return response()->json([
            'data' => $statistics,
        ]);
    }

    /**
     * Get comprehensive tournament analytics.
     */
    public function analytics(Tournament $tournament): JsonResponse
    {
        $analytics = $this->analyticsService->getComprehensiveTournamentReport($tournament);

        return response()->json([
            'data' => $analytics,
        ]);
    }

    /**
     * Get team standings.
     */
    public function standings(Tournament $tournament, Request $request): JsonResponse
    {
        $groupName = $request->get('group');
        $standings = $this->tournamentService->getTeamStandings($tournament, $groupName);

        return response()->json([
            'data' => $standings->map(function ($team) {
                return [
                    'team_id' => $team->team_id,
                    'team_name' => $team->team->name,
                    'position' => $team->final_position,
                    'games_played' => $team->games_played,
                    'wins' => $team->wins,
                    'losses' => $team->losses,
                    'draws' => $team->draws,
                    'win_percentage' => $team->win_percentage,
                    'points_for' => $team->points_for,
                    'points_against' => $team->points_against,
                    'point_differential' => $team->point_differential,
                    'tournament_points' => $team->tournament_points,
                ];
            }),
        ]);
    }

    /**
     * Get upcoming games.
     */
    public function upcomingGames(Tournament $tournament, Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $games = $this->tournamentService->getUpcomingGames($tournament, $limit);

        return response()->json([
            'data' => $games->map(function ($bracket) {
                return [
                    'id' => $bracket->id,
                    'round' => $bracket->round,
                    'round_name' => $bracket->round_name,
                    'scheduled_at' => $bracket->scheduled_at?->toISOString(),
                    'venue' => $bracket->venue,
                    'court' => $bracket->court,
                    'team1' => [
                        'id' => $bracket->team1?->team_id,
                        'name' => $bracket->team1?->team->name,
                        'seed' => $bracket->team1_seed,
                    ],
                    'team2' => [
                        'id' => $bracket->team2?->team_id,
                        'name' => $bracket->team2?->team->name,
                        'seed' => $bracket->team2_seed,
                    ],
                    'status' => $bracket->status,
                    'status_display' => $bracket->status_display,
                ];
            }),
        ]);
    }

    /**
     * Get tournament brackets.
     */
    public function brackets(Tournament $tournament): JsonResponse
    {
        $brackets = $tournament->brackets()
                              ->with(['team1.team', 'team2.team', 'winnerTeam.team'])
                              ->orderBy('round')
                              ->orderBy('position_in_round')
                              ->get();

        return response()->json([
            'data' => $brackets->groupBy(['bracket_type', 'round'])->map(function ($bracketType) {
                return $bracketType->map(function ($roundBrackets) {
                    return $roundBrackets->map(function ($bracket) {
                        return [
                            'id' => $bracket->id,
                            'round' => $bracket->round,
                            'round_name' => $bracket->round_name,
                            'position_in_round' => $bracket->position_in_round,
                            'bracket_type' => $bracket->bracket_type,
                            'status' => $bracket->status,
                            'status_display' => $bracket->status_display,
                            'scheduled_at' => $bracket->scheduled_at?->toISOString(),
                            'venue' => $bracket->venue,
                            'court' => $bracket->court,
                            'team1' => $bracket->team1 ? [
                                'id' => $bracket->team1->team_id,
                                'name' => $bracket->team1->team->name,
                                'seed' => $bracket->team1_seed,
                            ] : null,
                            'team2' => $bracket->team2 ? [
                                'id' => $bracket->team2->team_id,
                                'name' => $bracket->team2->team->name,
                                'seed' => $bracket->team2_seed,
                            ] : null,
                            'winner' => $bracket->winnerTeam ? [
                                'id' => $bracket->winnerTeam->team_id,
                                'name' => $bracket->winnerTeam->team->name,
                            ] : null,
                            'result' => [
                                'team1_score' => $bracket->team1_score,
                                'team2_score' => $bracket->team2_score,
                                'overtime' => $bracket->overtime,
                                'margin' => $bracket->margin_of_victory,
                            ],
                            'matchup_description' => $bracket->matchup_description,
                        ];
                    });
                });
            }),
        ]);
    }

    /**
     * Get tournament awards.
     */
    public function awards(Tournament $tournament): JsonResponse
    {
        $awards = $tournament->awards()
                            ->with(['recipientTeam.team', 'recipientPlayer', 'recipientCoach'])
                            ->orderBy('award_category')
                            ->get();

        return response()->json([
            'data' => $awards->map(function ($award) {
                return [
                    'id' => $award->id,
                    'name' => $award->award_name,
                    'category' => $award->award_category,
                    'category_display' => $award->award_category_display,
                    'type' => $award->award_type,
                    'type_display' => $award->award_type_display,
                    'recipient' => [
                        'name' => $award->recipient_name,
                        'type' => $award->recipientTeam ? 'team' : ($award->recipientPlayer ? 'player' : 'other'),
                    ],
                    'statistics' => $award->statistics,
                    'statistical_value' => $award->statistical_value_display,
                    'presented' => $award->presented,
                    'presentation_date' => $award->presentation_date?->toISOString(),
                    'record_setting' => $award->record_setting,
                ];
            }),
        ]);
    }
}