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
            ->with(['club', 'headCoach', 'assistantCoach'])
            ->withCount(['players', 'games'])
            ->when($user->hasRole('admin') || $user->hasRole('super-admin'), function ($query) {
                // Admin users see all teams
                return $query;
            }, function ($query) use ($user) {
                // Other users see teams from their clubs or teams they coach
                return $query->where(function ($q) use ($user) {
                    $q->whereHas('club.users', function ($subQ) use ($user) {
                        $subQ->where('user_id', $user->id);
                    })->orWhere('head_coach_id', $user->id)
                    ->orWhere('assistant_coach_id', $user->id);
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
        $this->authorize('create', Team::class);

        $clubs = Club::query()
            ->select(['id', 'name'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Teams/Create', [
            'clubs' => $clubs,
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
            'clubs' => $clubs,
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
}