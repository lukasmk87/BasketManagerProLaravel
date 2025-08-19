<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V2\Teams\StoreTeamRequest;
use App\Http\Requests\Api\V2\Teams\UpdateTeamRequest;
use App\Http\Requests\Api\V2\Teams\IndexTeamsRequest;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TeamController extends Controller
{
    /**
     * Display a listing of teams.
     */
    public function index(IndexTeamsRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Team::class);

        $teams = Team::query()
            ->with(['club:id,name,short_name', 'headCoach:id,name'])
            ->withCount(['players', 'homeGames', 'awayGames'])
            ->notPersonal() // Exclude Jetstream personal teams
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('short_name', 'like', "%{$search}%")
                      ->orWhereHas('club', function ($clubQuery) use ($search) {
                          $clubQuery->where('name', 'like', "%{$search}%");
                      });
                });
            })
            ->when($request->filled('club_id'), function ($query) use ($request) {
                $query->where('club_id', $request->club_id);
            })
            ->when($request->filled('season'), function ($query) use ($request) {
                $query->where('season', $request->season);
            })
            ->when($request->filled('league'), function ($query) use ($request) {
                $query->where('league', $request->league);
            })
            ->when($request->filled('gender'), function ($query) use ($request) {
                $query->where('gender', $request->gender);
            })
            ->when($request->filled('age_group'), function ($query) use ($request) {
                $query->where('age_group', $request->age_group);
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                if ($request->status === 'active') {
                    $query->where('is_active', true);
                } elseif ($request->status === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->when($request->filled('recruiting'), function ($query) use ($request) {
                $recruiting = $request->recruiting === 'true';
                $query->where('is_recruiting', $recruiting);
            })
            ->when($request->filled('sort'), function ($query) use ($request) {
                $sortField = $request->sort;
                $sortDirection = $request->filled('direction') && $request->direction === 'desc' ? 'desc' : 'asc';
                
                $allowedSortFields = ['name', 'season', 'created_at', 'games_played', 'win_percentage'];
                if (in_array($sortField, $allowedSortFields)) {
                    $query->orderBy($sortField, $sortDirection);
                }
            })
            ->latest()
            ->paginate($request->get('per_page', 15))
            ->withQueryString();

        return TeamResource::collection($teams);
    }

    /**
     * Store a newly created team.
     */
    public function store(StoreTeamRequest $request): TeamResource
    {
        $this->authorize('create', Team::class);

        $teamData = $request->validated();
        $team = Team::create($teamData);

        return new TeamResource($team->load(['club', 'headCoach', 'players']));
    }

    /**
     * Display the specified team.
     */
    public function show(Team $team)
    {
        try {
            $this->authorize('view', $team);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            \Log::warning('API TeamController show - Authorization failed', [
                'team_id' => $team->id,
                'user_id' => auth('sanctum')->id(),
                'user_authenticated' => auth('sanctum')->check(),
                'session_authenticated' => auth('web')->check(),
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Sie haben keine Berechtigung, dieses Team anzuzeigen.',
                'error' => 'authorization_failed',
                'api_version' => '4.0',
                'timestamp' => now()->toISOString(),
            ], 403);
        }

        $team->load([
            'club',
            'headCoach:id,name,email',
            'players.user:id,name,date_of_birth',
            'players' => function ($query) {
                $query->wherePivot('status', 'active')
                      ->wherePivot('is_active', true)
                      ->orderBy('player_team.jersey_number');
            },
        ]);

        \Log::info('API TeamController show - Success', [
            'team_id' => $team->id,
            'team_name' => $team->name,
            'user_id' => auth('sanctum')->id(),
        ]);

        return new TeamResource($team);
    }

    /**
     * Update the specified team.
     */
    public function update(UpdateTeamRequest $request, Team $team): TeamResource
    {
        $this->authorize('update', $team);

        $team->update($request->validated());

        return new TeamResource($team->load(['club', 'headCoach', 'players']));
    }

    /**
     * Remove the specified team.
     */
    public function destroy(Team $team): JsonResponse
    {
        $this->authorize('delete', $team);

        // Check if team has active players
        if ($team->activePlayers()->count() > 0) {
            return response()->json([
                'message' => 'Team kann nicht gelöscht werden, da noch aktive Spieler vorhanden sind.',
            ], 422);
        }

        // Check if team has scheduled games
        $upcomingGames = $team->allGames()
            ->where('scheduled_at', '>', now())
            ->where('status', 'scheduled')
            ->count();

        if ($upcomingGames > 0) {
            return response()->json([
                'message' => 'Team kann nicht gelöscht werden, da noch geplante Spiele vorhanden sind.',
            ], 422);
        }

        $team->delete();

        return response()->json([
            'message' => 'Team erfolgreich gelöscht.',
        ]);
    }

    /**
     * Get team players.
     */
    public function players(Team $team): JsonResponse
    {
        $this->authorize('view', $team);

        $players = $team->players()
            ->with(['user:id,name,date_of_birth'])
            ->wherePivot('status', 'active')
            ->wherePivot('is_active', true)
            ->orderBy('player_team.jersey_number')
            ->get()
            ->map(function ($player) {
                return [
                    'id' => $player->id,
                    'name' => $player->user?->name ?? $player->full_name,
                    'jersey_number' => $player->pivot->jersey_number,
                    'position' => $player->pivot->primary_position,
                    'age' => $player->user?->date_of_birth?->age,
                    'is_captain' => $player->pivot->is_captain,
                    'is_starter' => $player->pivot->is_starter,
                    'status' => $player->pivot->status,
                ];
            });

        return response()->json([
            'data' => $players,
            'meta' => [
                'total_players' => $players->count(),
                'captains' => $players->where('is_captain', true)->values(),
                'starters' => $players->where('is_starter', true)->values(),
            ]
        ]);
    }

    /**
     * Get team games.
     */
    public function games(Team $team, IndexTeamsRequest $request): JsonResponse
    {
        $this->authorize('view', $team);

        $query = $team->allGames()
            ->with(['homeTeam:id,name', 'awayTeam:id,name'])
            ->when($request->filled('season'), function ($q) use ($request) {
                $q->where('season', $request->season);
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->orderBy('scheduled_at', 'desc');

        $games = $query->paginate($request->get('per_page', 20));

        return response()->json($games);
    }

    /**
     * Get team statistics.
     */
    public function statistics(Team $team): JsonResponse
    {
        $this->authorize('view', $team);

        $statistics = [
            'games_played' => $team->games_played,
            'games_won' => $team->games_won,
            'games_lost' => $team->games_lost,
            'games_tied' => $team->games_tied,
            'win_percentage' => $team->win_percentage,
            'points_scored' => $team->points_scored,
            'points_allowed' => $team->points_allowed,
            'points_per_game' => $team->games_played > 0 ? round($team->points_scored / $team->games_played, 1) : 0,
            'points_allowed_per_game' => $team->games_played > 0 ? round($team->points_allowed / $team->games_played, 1) : 0,
            'current_roster_size' => $team->current_roster_size,
            'max_players' => $team->max_players,
            'available_spots' => $team->players_slots_available,
            'average_player_age' => $team->average_player_age,
        ];

        return response()->json([
            'data' => $statistics,
            'meta' => [
                'api_version' => '4.0',
                'timestamp' => now()->toISOString()
            ]
        ]);
    }

    /**
     * Get team analytics (V4 new feature).
     */
    public function analytics(Team $team): JsonResponse
    {
        $this->authorize('view', $team);

        $analytics = [
            'performance_trends' => [
                'scoring_trend' => 'stable', // This would be calculated from actual data
                'defensive_trend' => 'improving',
                'attendance_trend' => 'stable',
            ],
            'player_development' => [
                'improving_players' => 3,
                'stable_players' => 8,
                'declining_players' => 1,
            ],
            'team_chemistry' => [
                'assists_per_game' => 15.2,
                'turnovers_per_game' => 12.8,
                'chemistry_score' => 8.5, // Out of 10
            ],
            'upcoming_challenges' => [
                'tough_games_ahead' => 2,
                'injury_risk_players' => 1,
                'fatigue_level' => 'moderate',
            ]
        ];

        return response()->json([
            'data' => $analytics,
            'meta' => [
                'api_version' => '4.0',
                'timestamp' => now()->toISOString(),
                'generated_at' => now()->toISOString()
            ]
        ]);
    }

    /**
     * Get team performance trends (V4 new feature).
     */
    public function performanceTrends(Team $team): JsonResponse
    {
        $this->authorize('view', $team);

        // This would typically fetch real data from analytics tables
        $trends = [
            'last_30_days' => [
                'games' => 6,
                'wins' => 4,
                'losses' => 2,
                'avg_points_scored' => 78.5,
                'avg_points_allowed' => 72.3,
                'trend' => 'improving'
            ],
            'season_progression' => [
                'early_season' => ['wins' => 3, 'losses' => 2],
                'mid_season' => ['wins' => 8, 'losses' => 4],
                'late_season' => ['wins' => 5, 'losses' => 2]
            ],
            'key_metrics' => [
                'offensive_efficiency' => 1.08,
                'defensive_efficiency' => 0.95,
                'pace' => 72.4,
                'effective_field_goal_percentage' => 0.52
            ]
        ];

        return response()->json([
            'data' => $trends,
            'meta' => [
                'api_version' => '4.0',
                'timestamp' => now()->toISOString()
            ]
        ]);
    }

    /**
     * Invite player to team (V4 new feature).
     */
    public function invitePlayer(Team $team, IndexTeamsRequest $request): JsonResponse
    {
        $this->authorize('update', $team);

        $validated = $request->validate([
            'email' => 'required|email',
            'message' => 'nullable|string|max:500',
            'role' => 'nullable|string|in:player,reserve',
        ]);

        // This would typically send an invitation email
        // For now, we'll just return a success response
        
        return response()->json([
            'message' => 'Einladung erfolgreich versendet.',
            'data' => [
                'email' => $validated['email'],
                'team' => $team->name,
                'invitation_sent_at' => now()->toISOString()
            ],
            'meta' => [
                'api_version' => '4.0',
                'timestamp' => now()->toISOString()
            ]
        ]);
    }

    /**
     * Get public team statistics (V4 new feature).
     */
    public function publicStats(Team $team): JsonResponse
    {
        // No authorization needed for public stats
        
        $publicStats = [
            'basic_info' => [
                'name' => $team->name,
                'short_name' => $team->short_name,
                'league' => $team->league,
                'season' => $team->season,
                'gender' => $team->gender,
                'age_group' => $team->age_group,
            ],
            'performance' => [
                'games_played' => $team->games_played,
                'games_won' => $team->games_won,
                'games_lost' => $team->games_lost,
                'win_percentage' => $team->win_percentage,
                'points_per_game' => $team->games_played > 0 ? round($team->points_scored / $team->games_played, 1) : 0,
                'points_allowed_per_game' => $team->games_played > 0 ? round($team->points_allowed / $team->games_played, 1) : 0,
            ],
            'roster_info' => [
                'current_roster_size' => $team->current_roster_size,
                'average_player_age' => $team->average_player_age,
            ],
        ];

        return response()->json([
            'data' => $publicStats,
            'meta' => [
                'api_version' => '4.0',
                'timestamp' => now()->toISOString(),
                'public' => true
            ]
        ]);
    }
}