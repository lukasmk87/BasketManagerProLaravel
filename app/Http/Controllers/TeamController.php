<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Club;
use App\Services\TeamService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function __construct(
        private TeamService $teamService
    ) {}

    /**
     * Display a listing of teams.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        // Get teams based on user permissions
        $teams = Team::query()
            ->with(['club', 'headCoach'])
            ->withCount(['players', 'homeGames', 'awayGames'])
            ->when($user->hasRole('admin') || $user->hasRole('super_admin'), function ($query) {
                // Admin users see all teams
                return $query;
            }, function ($query) use ($user) {
                // Other users see teams from their clubs or teams they coach
                return $query->where(function ($q) use ($user) {
                    $q->whereHas('club.users', function ($subQ) use ($user) {
                        $subQ->where('user_id', $user->id);
                    })->orWhere('head_coach_id', $user->id)
                    ->orWhereJsonContains('assistant_coaches', $user->id);
                });
            })
            ->orderBy('name')
            ->paginate(20);

        return Inertia::render('Teams/Index', [
            'teams' => $teams,
            'can' => [
                'create' => $user->can('create', Team::class),
            ],
        ]);
    }

    /**
     * Show the form for creating a new team.
     */
    public function create(): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        try {
            $this->authorize('create', Team::class);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            // Log the authorization failure with detailed info
            \Log::warning('Teams Create - Authorization failed', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'user_roles' => auth()->user()?->getRoleNames()->toArray() ?? [],
                'user_permissions' => auth()->user()?->getAllPermissions()->pluck('name')->toArray() ?? [],
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('dashboard')->withErrors([
                'authorization' => 'Sie haben keine Berechtigung, Teams zu erstellen. Bitte wenden Sie sich an den Administrator.'
            ]);
        }

        $clubs = Club::query()
            ->select(['id', 'name'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Convert to simple array
        $clubsArray = $clubs->map(function ($club) {
            return [
                'id' => $club->id,
                'name' => $club->name,
            ];
        })->toArray();

        // Debug logging
        \Log::info('Teams Create - Clubs loaded', [
            'clubs_count' => count($clubsArray),
            'clubs' => $clubsArray,
            'user_id' => auth()->id(),
            'user_roles' => auth()->user()->getRoleNames()->toArray(),
            'user_permissions' => auth()->user()->getAllPermissions()->pluck('name')->toArray()
        ]);

        return Inertia::render('Teams/Create', [
            'clubs' => $clubsArray,
        ]);
    }

    /**
     * Store a newly created team in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Team::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'club_id' => 'required|exists:clubs,id',
            'season' => 'required|string|max:9',
            'league' => 'nullable|string|max:255',
            'division' => 'nullable|string|max:255',
            'age_group' => 'nullable|string|max:50',
            'gender' => 'required|in:male,female,mixed',
            'is_active' => 'boolean',
            'training_schedule' => 'nullable|json',
            'description' => 'nullable|string|max:1000',
        ]);

        $team = $this->teamService->createTeam($validated);

        return redirect()->route('teams.show', $team)
            ->with('success', 'Team wurde erfolgreich erstellt.');
    }

    /**
     * Display the specified team.
     */
    public function show(Team $team): Response
    {
        $this->authorize('view', $team);

        $team->load([
            'club',
            'headCoach',
            'assistantCoach',
            'players.user',
            'homeGames.awayTeam',
            'awayGames.homeTeam'
        ]);

        $teamStats = $this->teamService->getTeamStatistics($team);

        return Inertia::render('Teams/Show', [
            'team' => $team,
            'statistics' => $teamStats,
            'can' => [
                'update' => auth()->user()->can('update', $team),
                'delete' => auth()->user()->can('delete', $team),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified team.
     */
    public function edit(Team $team): Response
    {
        $this->authorize('update', $team);

        $clubs = Club::query()
            ->select(['id', 'name'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Teams/Edit', [
            'team' => $team,
            'clubs' => $clubs->toArray(),
        ]);
    }

    /**
     * Update the specified team in storage.
     */
    public function update(Request $request, Team $team)
    {
        $this->authorize('update', $team);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'club_id' => 'required|exists:clubs,id',
            'season' => 'required|string|max:9',
            'league' => 'nullable|string|max:255',
            'division' => 'nullable|string|max:255',
            'age_group' => 'nullable|string|max:50',
            'gender' => 'required|in:male,female,mixed',
            'is_active' => 'boolean',
            'training_schedule' => 'nullable|json',
            'description' => 'nullable|string|max:1000',
        ]);

        $this->teamService->updateTeam($team, $validated);

        return redirect()->route('teams.show', $team)
            ->with('success', 'Team wurde erfolgreich aktualisiert.');
    }

    /**
     * Remove the specified team from storage.
     */
    public function destroy(Team $team)
    {
        $this->authorize('delete', $team);

        $this->teamService->deleteTeam($team);

        return redirect()->route('teams.index')
            ->with('success', 'Team wurde erfolgreich gelöscht.');
    }

    /**
     * Get players for a specific team.
     */
    public function players(Team $team)
    {
        $this->authorize('view', $team);

        $players = $team->players()
            ->with('user')
            ->orderBy('jersey_number')
            ->orderBy('is_starter', 'desc')
            ->get();

        return response()->json([
            'players' => $players->map(function ($player) {
                return [
                    'id' => $player->id,
                    'user' => $player->user,
                    'pivot' => [
                        'jersey_number' => $player->pivot->jersey_number,
                        'primary_position' => $player->pivot->primary_position,
                        'secondary_positions' => $player->pivot->secondary_positions,
                        'is_active' => $player->pivot->is_active,
                        'is_starter' => $player->pivot->is_starter,
                        'is_captain' => $player->pivot->is_captain,
                        'status' => $player->pivot->status,
                        'joined_at' => $player->pivot->joined_at,
                        'notes' => $player->pivot->notes,
                    ]
                ];
            })
        ]);
    }

    /**
     * Attach a player to a team with pivot data.
     */
    public function attachPlayer(Request $request, Team $team)
    {
        $this->authorize('update', $team);

        $validated = $request->validate([
            'player_ids' => 'required|array',
            'player_ids.*' => 'exists:players,id',
            'jersey_number' => 'nullable|integer|between:0,99',
            'primary_position' => 'nullable|in:PG,SG,SF,PF,C',
            'secondary_positions' => 'nullable|array',
            'secondary_positions.*' => 'in:PG,SG,SF,PF,C',
            'is_starter' => 'boolean',
            'is_captain' => 'boolean',
            'status' => 'in:active,inactive,injured,suspended,on_loan',
            'notes' => 'nullable|string|max:1000',
        ]);

        $playerIds = $validated['player_ids'];
        unset($validated['player_ids']);

        $pivotData = array_merge($validated, [
            'joined_at' => now(),
            'is_active' => true,
        ]);

        foreach ($playerIds as $playerId) {
            // Check if player is already in the team
            if ($team->players()->where('player_id', $playerId)->exists()) {
                continue;
            }

            // Check jersey number uniqueness within team
            if (isset($validated['jersey_number'])) {
                $existingJersey = $team->players()
                    ->wherePivot('jersey_number', $validated['jersey_number'])
                    ->wherePivot('is_active', true)
                    ->exists();

                if ($existingJersey) {
                    return response()->json([
                        'error' => "Trikotnummer {$validated['jersey_number']} ist bereits vergeben."
                    ], 422);
                }
            }

            $team->players()->attach($playerId, $pivotData);
        }

        return response()->json([
            'message' => 'Spieler wurde(n) erfolgreich zum Team hinzugefügt.',
        ]);
    }

    /**
     * Update player's pivot data in the team.
     */
    public function updatePlayer(Request $request, Team $team, Player $player)
    {
        $this->authorize('update', $team);

        // Check if player is actually in this team
        if (!$team->players()->where('player_id', $player->id)->exists()) {
            return response()->json(['error' => 'Spieler ist nicht in diesem Team.'], 404);
        }

        $validated = $request->validate([
            'jersey_number' => 'nullable|integer|between:0,99',
            'primary_position' => 'nullable|in:PG,SG,SF,PF,C',
            'secondary_positions' => 'nullable|array',
            'secondary_positions.*' => 'in:PG,SG,SF,PF,C',
            'is_active' => 'boolean',
            'is_starter' => 'boolean',
            'is_captain' => 'boolean',
            'status' => 'in:active,inactive,injured,suspended,on_loan',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Check jersey number uniqueness within team (exclude current player)
        if (isset($validated['jersey_number'])) {
            $existingJersey = $team->players()
                ->wherePivot('jersey_number', $validated['jersey_number'])
                ->wherePivot('is_active', true)
                ->where('player_id', '!=', $player->id)
                ->exists();

            if ($existingJersey) {
                return response()->json([
                    'error' => "Trikotnummer {$validated['jersey_number']} ist bereits vergeben."
                ], 422);
            }
        }

        $team->players()->updateExistingPivot($player->id, $validated);

        return response()->json([
            'message' => 'Spielerdaten wurden erfolgreich aktualisiert.',
        ]);
    }

    /**
     * Detach a player from a team.
     */
    public function detachPlayer(Team $team, Player $player)
    {
        $this->authorize('update', $team);

        if (!$team->players()->where('player_id', $player->id)->exists()) {
            return response()->json(['error' => 'Spieler ist nicht in diesem Team.'], 404);
        }

        $team->players()->detach($player->id);

        return response()->json([
            'message' => 'Spieler wurde erfolgreich vom Team entfernt.',
        ]);
    }
}