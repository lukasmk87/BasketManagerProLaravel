<?php

namespace App\Http\Controllers\Api\V2;

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
    public function show(Team $team): TeamResource
    {
        $this->authorize('view', $team);

        $team->load([
            'club',
            'headCoach:id,name,email',
            'players.user:id,name,birth_date',
            'players' => function ($query) {
                $query->where('status', 'active')
                      ->orderBy('jersey_number');
            },
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

        return response()->json($statistics);
    }

    /**
     * Get team roster.
     */
    public function roster(Team $team): JsonResponse
    {
        $this->authorize('view', $team);

        $players = $team->players()
            ->with(['user:id,name,birth_date'])
            ->where('status', 'active')
            ->orderBy('jersey_number')
            ->get()
            ->map(function ($player) {
                return [
                    'id' => $player->id,
                    'name' => $player->user?->name ?? $player->full_name,
                    'jersey_number' => $player->jersey_number,
                    'position' => $player->primary_position,
                    'age' => $player->user?->birth_date?->age,
                    'is_captain' => $player->is_captain,
                    'is_starter' => $player->is_starter,
                    'status' => $player->status,
                ];
            });

        return response()->json([
            'players' => $players,
            'total_players' => $players->count(),
            'captains' => $players->where('is_captain', true)->values(),
            'starters' => $players->where('is_starter', true)->values(),
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
     * Add player to team.
     */
    public function addPlayer(Team $team, StoreTeamRequest $request): JsonResponse
    {
        $this->authorize('update', $team);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'jersey_number' => 'nullable|integer|min:0|max:99|unique:players,jersey_number,NULL,id,team_id,' . $team->id,
            'position' => 'nullable|string|in:PG,SG,SF,PF,C',
            'is_starter' => 'boolean',
            'is_captain' => 'boolean',
        ]);

        if (!$team->canAcceptNewPlayer()) {
            return response()->json([
                'message' => 'Team kann keine neuen Spieler aufnehmen.',
            ], 422);
        }

        $user = \App\Models\User::findOrFail($validated['user_id']);
        $player = $team->addPlayer($user, $validated);

        return response()->json([
            'message' => 'Spieler erfolgreich zum Team hinzugefügt.',
            'player' => $player->load('user'),
        ]);
    }

    /**
     * Remove player from team.
     */
    public function removePlayer(Team $team, \App\Models\Player $player): JsonResponse
    {
        $this->authorize('update', $team);

        if ($player->team_id !== $team->id) {
            return response()->json([
                'message' => 'Spieler gehört nicht zu diesem Team.',
            ], 422);
        }

        $team->removePlayer($player);

        return response()->json([
            'message' => 'Spieler erfolgreich vom Team entfernt.',
        ]);
    }

    /**
     * Toggle team recruitment status.
     */
    public function toggleRecruitment(Team $team): TeamResource
    {
        $this->authorize('update', $team);

        $team->update(['is_recruiting' => !$team->is_recruiting]);

        return new TeamResource($team);
    }

    /**
     * Get team's training schedule.
     */
    public function trainingSchedule(Team $team): JsonResponse
    {
        $this->authorize('view', $team);

        return response()->json([
            'training_schedule' => $team->training_schedule,
            'practice_times' => $team->practice_times,
            'home_venue' => $team->home_venue,
            'venue_details' => $team->venue_details,
        ]);
    }

    /**
     * Update team's training schedule.
     */
    public function updateTrainingSchedule(Team $team, UpdateTeamRequest $request): TeamResource
    {
        $this->authorize('update', $team);

        $validated = $request->validate([
            'training_schedule' => 'array',
            'practice_times' => 'array',
            'home_venue' => 'nullable|string',
            'venue_details' => 'nullable|array',
        ]);

        $team->update($validated);

        return new TeamResource($team);
    }
}