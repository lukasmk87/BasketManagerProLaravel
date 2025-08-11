<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournament\RegisterTeamRequest;
use App\Http\Requests\Tournament\UpdateRegistrationRequest;
use App\Http\Resources\TournamentTeamResource;
use App\Models\Tournament;
use App\Models\TournamentTeam;
use App\Models\Team;
use App\Services\TournamentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TournamentTeamController extends Controller
{
    protected TournamentService $tournamentService;

    public function __construct(TournamentService $tournamentService)
    {
        $this->tournamentService = $tournamentService;
    }

    /**
     * Get all teams registered for a tournament.
     */
    public function index(Tournament $tournament, Request $request): JsonResponse
    {
        $query = $tournament->tournamentTeams()->with(['team', 'registeredBy']);

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('group')) {
            $query->where('group_name', $request->group);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'registered_at');
        $sortOrder = $request->get('sort_order', 'asc');
        
        if (in_array($sortBy, ['registered_at', 'seed', 'wins', 'losses', 'tournament_points'])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $teams = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => TournamentTeamResource::collection($teams),
            'meta' => [
                'current_page' => $teams->currentPage(),
                'last_page' => $teams->lastPage(),
                'per_page' => $teams->perPage(),
                'total' => $teams->total(),
            ],
        ]);
    }

    /**
     * Register a team for the tournament.
     */
    public function store(Tournament $tournament, RegisterTeamRequest $request): JsonResponse
    {
        try {
            $team = Team::findOrFail($request->team_id);
            
            $tournamentTeam = $this->tournamentService->registerTeam(
                $tournament,
                $team,
                $request->user(),
                $request->validated()
            );

            return response()->json([
                'message' => 'Team erfolgreich angemeldet',
                'data' => new TournamentTeamResource($tournamentTeam->load(['team', 'registeredBy'])),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler bei der Team-Anmeldung',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get specific tournament team registration.
     */
    public function show(Tournament $tournament, TournamentTeam $tournamentTeam): JsonResponse
    {
        if ($tournamentTeam->tournament_id !== $tournament->id) {
            return response()->json(['message' => 'Team gehört nicht zu diesem Turnier'], 404);
        }

        return response()->json([
            'data' => new TournamentTeamResource(
                $tournamentTeam->load(['team', 'registeredBy', 'awards'])
            ),
        ]);
    }

    /**
     * Update tournament team registration.
     */
    public function update(
        Tournament $tournament, 
        TournamentTeam $tournamentTeam,
        UpdateRegistrationRequest $request
    ): JsonResponse {
        try {
            if ($tournamentTeam->tournament_id !== $tournament->id) {
                return response()->json(['message' => 'Team gehört nicht zu diesem Turnier'], 404);
            }

            $this->authorize('update', $tournamentTeam);

            $tournamentTeam->update($request->validated());

            return response()->json([
                'message' => 'Anmeldung erfolgreich aktualisiert',
                'data' => new TournamentTeamResource($tournamentTeam->load(['team', 'registeredBy'])),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Aktualisieren der Anmeldung',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Remove team from tournament.
     */
    public function destroy(Tournament $tournament, TournamentTeam $tournamentTeam): JsonResponse
    {
        try {
            if ($tournamentTeam->tournament_id !== $tournament->id) {
                return response()->json(['message' => 'Team gehört nicht zu diesem Turnier'], 404);
            }

            $this->authorize('delete', $tournamentTeam);

            $this->tournamentService->withdrawTeam($tournamentTeam, 'Durch Nutzer zurückgezogen');

            return response()->json([
                'message' => 'Team erfolgreich zurückgezogen',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Zurückziehen des Teams',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    // Registration Management

    /**
     * Approve team registration.
     */
    public function approve(Tournament $tournament, TournamentTeam $tournamentTeam): JsonResponse
    {
        try {
            if ($tournamentTeam->tournament_id !== $tournament->id) {
                return response()->json(['message' => 'Team gehört nicht zu diesem Turnier'], 404);
            }

            $this->authorize('update', $tournament);

            $tournamentTeam = $this->tournamentService->approveTeamRegistration($tournamentTeam);

            return response()->json([
                'message' => 'Team-Anmeldung erfolgreich genehmigt',
                'data' => new TournamentTeamResource($tournamentTeam->load(['team', 'registeredBy'])),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Genehmigen der Team-Anmeldung',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Reject team registration.
     */
    public function reject(Tournament $tournament, TournamentTeam $tournamentTeam, Request $request): JsonResponse
    {
        try {
            if ($tournamentTeam->tournament_id !== $tournament->id) {
                return response()->json(['message' => 'Team gehört nicht zu diesem Turnier'], 404);
            }

            $this->authorize('update', $tournament);

            $request->validate([
                'reason' => 'sometimes|string|max:500',
            ]);

            $tournamentTeam = $this->tournamentService->rejectTeamRegistration(
                $tournamentTeam,
                $request->get('reason')
            );

            return response()->json([
                'message' => 'Team-Anmeldung erfolgreich abgelehnt',
                'data' => new TournamentTeamResource($tournamentTeam->load(['team', 'registeredBy'])),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Ablehnen der Team-Anmeldung',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Withdraw team registration.
     */
    public function withdraw(Tournament $tournament, TournamentTeam $tournamentTeam, Request $request): JsonResponse
    {
        try {
            if ($tournamentTeam->tournament_id !== $tournament->id) {
                return response()->json(['message' => 'Team gehört nicht zu diesem Turnier'], 404);
            }

            $this->authorize('update', $tournamentTeam);

            $request->validate([
                'reason' => 'sometimes|string|max:500',
            ]);

            $tournamentTeam = $this->tournamentService->withdrawTeam(
                $tournamentTeam,
                $request->get('reason')
            );

            return response()->json([
                'message' => 'Team erfolgreich zurückgezogen',
                'data' => new TournamentTeamResource($tournamentTeam->load(['team', 'registeredBy'])),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Zurückziehen des Teams',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    // Performance and Statistics

    /**
     * Get team performance in tournament.
     */
    public function performance(Tournament $tournament, TournamentTeam $tournamentTeam): JsonResponse
    {
        if ($tournamentTeam->tournament_id !== $tournament->id) {
            return response()->json(['message' => 'Team gehört nicht zu diesem Turnier'], 404);
        }

        // Get all games for this team
        $homeBrackets = $tournament->brackets()
                                 ->where('team1_id', $tournamentTeam->id)
                                 ->where('status', 'completed')
                                 ->get();

        $awayBrackets = $tournament->brackets()
                                 ->where('team2_id', $tournamentTeam->id)
                                 ->where('status', 'completed')
                                 ->get();

        $allGames = $homeBrackets->concat($awayBrackets)->sortBy('actual_start_time');

        // Calculate performance metrics
        $performance = [
            'overall_stats' => [
                'games_played' => $tournamentTeam->games_played,
                'wins' => $tournamentTeam->wins,
                'losses' => $tournamentTeam->losses,
                'draws' => $tournamentTeam->draws,
                'win_percentage' => $tournamentTeam->win_percentage,
                'points_for' => $tournamentTeam->points_for,
                'points_against' => $tournamentTeam->points_against,
                'point_differential' => $tournamentTeam->point_differential,
                'average_points_for' => $tournamentTeam->average_points_for,
                'average_points_against' => $tournamentTeam->average_points_against,
            ],
            'tournament_position' => [
                'current_seed' => $tournamentTeam->seed,
                'current_position' => $tournamentTeam->final_position,
                'group_name' => $tournamentTeam->group_name,
                'elimination_round' => $tournamentTeam->elimination_round,
                'eliminated_at' => $tournamentTeam->eliminated_at?->toISOString(),
                'is_still_active' => $tournamentTeam->is_still_active,
            ],
            'game_by_game' => $allGames->map(function ($bracket) use ($tournamentTeam) {
                $isHome = $bracket->team1_id === $tournamentTeam->id;
                $teamScore = $isHome ? $bracket->team1_score : $bracket->team2_score;
                $opponentScore = $isHome ? $bracket->team2_score : $bracket->team1_score;
                $opponent = $isHome ? $bracket->team2 : $bracket->team1;

                return [
                    'game_id' => $bracket->id,
                    'round' => $bracket->round,
                    'round_name' => $bracket->round_name,
                    'opponent' => [
                        'id' => $opponent?->team_id,
                        'name' => $opponent?->team->name,
                        'seed' => $isHome ? $bracket->team2_seed : $bracket->team1_seed,
                    ],
                    'result' => [
                        'team_score' => $teamScore,
                        'opponent_score' => $opponentScore,
                        'margin' => $teamScore - $opponentScore,
                        'outcome' => $teamScore > $opponentScore ? 'win' : 
                                   ($teamScore < $opponentScore ? 'loss' : 'draw'),
                    ],
                    'game_details' => [
                        'overtime' => $bracket->overtime,
                        'duration' => $bracket->actual_duration,
                        'played_at' => $bracket->actual_start_time?->toISOString(),
                    ],
                ];
            }),
            'trends' => $this->calculatePerformanceTrends($allGames, $tournamentTeam),
        ];

        return response()->json([
            'data' => $performance,
        ]);
    }

    /**
     * Get team's upcoming games.
     */
    public function upcomingGames(Tournament $tournament, TournamentTeam $tournamentTeam): JsonResponse
    {
        if ($tournamentTeam->tournament_id !== $tournament->id) {
            return response()->json(['message' => 'Team gehört nicht zu diesem Turnier'], 404);
        }

        $upcomingGames = $tournament->brackets()
                                   ->where(function ($query) use ($tournamentTeam) {
                                       $query->where('team1_id', $tournamentTeam->id)
                                             ->orWhere('team2_id', $tournamentTeam->id);
                                   })
                                   ->whereIn('status', ['pending', 'scheduled'])
                                   ->orderBy('scheduled_at')
                                   ->with(['team1.team', 'team2.team'])
                                   ->get();

        return response()->json([
            'data' => $upcomingGames->map(function ($bracket) use ($tournamentTeam) {
                $isHome = $bracket->team1_id === $tournamentTeam->id;
                $opponent = $isHome ? $bracket->team2 : $bracket->team1;

                return [
                    'game_id' => $bracket->id,
                    'round' => $bracket->round,
                    'round_name' => $bracket->round_name,
                    'scheduled_at' => $bracket->scheduled_at?->toISOString(),
                    'venue' => $bracket->venue,
                    'court' => $bracket->court,
                    'is_home' => $isHome,
                    'opponent' => [
                        'id' => $opponent?->team_id,
                        'name' => $opponent?->team->name ?? 'TBD',
                        'seed' => $isHome ? $bracket->team2_seed : $bracket->team1_seed,
                    ],
                    'status' => $bracket->status,
                    'status_display' => $bracket->status_display,
                ];
            }),
        ]);
    }

    // Helper Methods
    protected function calculatePerformanceTrends($games, $tournamentTeam): array
    {
        if ($games->isEmpty()) {
            return [];
        }

        $trends = [];
        $recentGames = $games->take(-5); // Last 5 games

        // Scoring trends
        $scoringTrend = $recentGames->map(function ($bracket) use ($tournamentTeam) {
            $isHome = $bracket->team1_id === $tournamentTeam->id;
            return $isHome ? $bracket->team1_score : $bracket->team2_score;
        });

        $trends['scoring'] = [
            'recent_average' => $scoringTrend->avg(),
            'tournament_average' => $tournamentTeam->average_points_for,
            'trend_direction' => $this->calculateTrendDirection($scoringTrend),
        ];

        // Win/Loss streaks
        $results = $games->map(function ($bracket) use ($tournamentTeam) {
            $isHome = $bracket->team1_id === $tournamentTeam->id;
            $teamScore = $isHome ? $bracket->team1_score : $bracket->team2_score;
            $opponentScore = $isHome ? $bracket->team2_score : $bracket->team1_score;
            
            return $teamScore > $opponentScore ? 'W' : 'L';
        });

        $trends['streaks'] = [
            'current_streak' => $this->calculateCurrentStreak($results),
            'longest_win_streak' => $this->calculateLongestStreak($results, 'W'),
            'longest_loss_streak' => $this->calculateLongestStreak($results, 'L'),
        ];

        return $trends;
    }

    protected function calculateTrendDirection($values): string
    {
        if ($values->count() < 2) return 'stable';
        
        $first = $values->take(2)->avg();
        $last = $values->take(-2)->avg();
        
        if ($last > $first * 1.1) return 'improving';
        if ($last < $first * 0.9) return 'declining';
        return 'stable';
    }

    protected function calculateCurrentStreak($results): array
    {
        if ($results->isEmpty()) return ['type' => 'none', 'count' => 0];
        
        $latest = $results->last();
        $count = 1;
        
        for ($i = $results->count() - 2; $i >= 0; $i--) {
            if ($results->get($i) === $latest) {
                $count++;
            } else {
                break;
            }
        }
        
        return [
            'type' => $latest === 'W' ? 'win' : 'loss',
            'count' => $count,
        ];
    }

    protected function calculateLongestStreak($results, $type): int
    {
        $maxStreak = 0;
        $currentStreak = 0;
        
        foreach ($results as $result) {
            if ($result === $type) {
                $currentStreak++;
                $maxStreak = max($maxStreak, $currentStreak);
            } else {
                $currentStreak = 0;
            }
        }
        
        return $maxStreak;
    }
}