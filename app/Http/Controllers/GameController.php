<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Team;
use App\Services\StatisticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GameController extends Controller
{
    public function __construct(
        private StatisticsService $statisticsService
    ) {}

    /**
     * Display a listing of games.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        // Get games based on user permissions
        $games = Game::query()
            ->with(['homeTeam.club', 'awayTeam.club'])
            ->when($user->hasRole('admin') || $user->hasRole('super-admin'), function ($query) {
                // Admin users see all games
                return $query;
            }, function ($query) use ($user) {
                // Other users see games from their teams/clubs
                return $query->where(function ($q) use ($user) {
                    $q->whereHas('homeTeam', function ($subQ) use ($user) {
                        $subQ->where('head_coach_id', $user->id)
                             ->orWhere('assistant_coach_id', $user->id)
                             ->orWhereHas('club.users', function ($clubQ) use ($user) {
                                 $clubQ->where('user_id', $user->id);
                             });
                    })->orWhereHas('awayTeam', function ($subQ) use ($user) {
                        $subQ->where('head_coach_id', $user->id)
                             ->orWhere('assistant_coach_id', $user->id)
                             ->orWhereHas('club.users', function ($clubQ) use ($user) {
                                 $clubQ->where('user_id', $user->id);
                             });
                    });
                });
            })
            ->orderBy('scheduled_at', 'desc')
            ->paginate(20);

        return Inertia::render('Games/Index', [
            'games' => $games,
            'can' => [
                'create' => $user->can('create', Game::class),
            ],
        ]);
    }

    /**
     * Show the form for creating a new game.
     */
    public function create(): Response
    {
        $this->authorize('create', Game::class);

        $teams = Team::query()
            ->with('club')
            ->select(['id', 'name', 'club_id'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Games/Create', [
            'teams' => $teams,
        ]);
    }

    /**
     * Store a newly created game in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Game::class);

        $validated = $request->validate([
            'home_team_id' => 'required|exists:teams,id',
            'away_team_id' => 'required|exists:teams,id|different:home_team_id',
            'scheduled_at' => 'required|date|after:now',
            'location' => 'nullable|string|max:255',
            'game_type' => 'required|in:league,friendly,playoff,tournament',
            'season' => 'required|string|max:9',
            'league' => 'nullable|string|max:255',
            'round' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:1000',
        ]);

        $game = Game::create($validated);

        return redirect()->route('games.show', $game)
            ->with('success', 'Spiel wurde erfolgreich erstellt.');
    }

    /**
     * Display the specified game.
     */
    public function show(Game $game): Response
    {
        $this->authorize('view', $game);

        $game->load([
            'homeTeam.club',
            'awayTeam.club',
            'gameActions.player',
            'liveGame'
        ]);

        $gameStats = null;
        if ($game->status === 'finished') {
            $gameStats = $this->statisticsService->getGameStatistics($game);
        }

        return Inertia::render('Games/Show', [
            'game' => $game,
            'statistics' => $gameStats,
            'can' => [
                'update' => auth()->user()->can('update', $game),
                'delete' => auth()->user()->can('delete', $game),
                'startGame' => auth()->user()->can('startGame', $game),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified game.
     */
    public function edit(Game $game): Response
    {
        $this->authorize('update', $game);

        // Only allow editing of scheduled games
        if ($game->status !== 'scheduled') {
            return redirect()->route('games.show', $game)
                ->with('error', 'Nur geplante Spiele können bearbeitet werden.');
        }

        $teams = Team::query()
            ->with('club')
            ->select(['id', 'name', 'club_id'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Games/Edit', [
            'game' => $game,
            'teams' => $teams,
        ]);
    }

    /**
     * Update the specified game in storage.
     */
    public function update(Request $request, Game $game)
    {
        $this->authorize('update', $game);

        // Only allow updating of scheduled games
        if ($game->status !== 'scheduled') {
            return redirect()->route('games.show', $game)
                ->with('error', 'Nur geplante Spiele können bearbeitet werden.');
        }

        $validated = $request->validate([
            'home_team_id' => 'required|exists:teams,id',
            'away_team_id' => 'required|exists:teams,id|different:home_team_id',
            'scheduled_at' => 'required|date',
            'location' => 'nullable|string|max:255',
            'game_type' => 'required|in:league,friendly,playoff,tournament',
            'season' => 'required|string|max:9',
            'league' => 'nullable|string|max:255',
            'round' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:1000',
        ]);

        $game->update($validated);

        return redirect()->route('games.show', $game)
            ->with('success', 'Spiel wurde erfolgreich aktualisiert.');
    }

    /**
     * Remove the specified game from storage.
     */
    public function destroy(Game $game)
    {
        $this->authorize('delete', $game);

        // Only allow deletion of scheduled games
        if ($game->status !== 'scheduled') {
            return redirect()->route('games.show', $game)
                ->with('error', 'Nur geplante Spiele können gelöscht werden.');
        }

        $game->delete();

        return redirect()->route('games.index')
            ->with('success', 'Spiel wurde erfolgreich gelöscht.');
    }
}