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
            ->when($user->hasRole('admin') || $user->hasRole('super_admin'), function ($query) {
                // Admin users see all games
                return $query;
            }, function ($query) use ($user) {
                // Other users see games from their teams/clubs
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
            // Tournament fields
            'tournament_id' => 'nullable|string|max:255',
            'tournament_round' => 'nullable|string|max:100',
            'tournament_game_number' => 'nullable|integer|min:1',
            // Game rules
            'total_periods' => 'nullable|integer|min:1|max:8',
            'period_length_minutes' => 'nullable|integer|min:1|max:30',
            'overtime_length_minutes' => 'nullable|integer|min:1|max:15',
            // Event details
            'capacity' => 'nullable|integer|min:0',
            'allow_spectators' => 'nullable|boolean',
            'allow_media' => 'nullable|boolean',
            // Media
            'is_streamed' => 'nullable|boolean',
            'stream_url' => 'nullable|url|max:255',
            'allow_recording' => 'nullable|boolean',
            'allow_photos' => 'nullable|boolean',
            'allow_streaming' => 'nullable|boolean',
            // Safety
            'medical_staff_present' => 'nullable|string|max:255',
            // Weather
            'weather_conditions' => 'nullable|in:sunny,cloudy,rainy,snowy,indoor',
            'temperature' => 'nullable|integer|min:-20|max:50',
            'court_conditions' => 'nullable|in:excellent,good,fair,poor,wet,slippery',
            // Home/Away designation
            'is_home_game' => 'required|boolean',
        ]);

        // Validation: either away_team_id OR away_team_name must be provided
        if (empty($validated['away_team_id']) && empty($validated['away_team_name'])) {
            return back()->withErrors([
                'away_team_id' => 'Entweder ein internes Team oder ein externer Teamname muss angegeben werden.',
                'away_team_name' => 'Entweder ein internes Team oder ein externer Teamname muss angegeben werden.',
            ]);
        }

        // Add import source
        $validated['import_source'] = 'manual';

        $game = Game::create($validated);

        return redirect()->route('web.games.show', $game)
            ->with('success', 'Spiel wurde erfolgreich erstellt.');
    }

    /**
     * Display the specified game.
     */
    public function show(Game $game): Response
    {
        $this->authorize('view', $game);

        // PERF-003: Removed gameActions from eager load - use limited query instead
        $game->load([
            'homeTeam.club',
            'awayTeam.club',
            'liveGame',
            'registrations.player:id,name,user_id',
        ]);

        // PERF-003: Load only recent game actions (not all 100-500+)
        $recentGameActions = $game->gameActions()
            ->with(['player:id,name', 'assistedByPlayer:id,name'])
            ->latest()
            ->limit(20)
            ->get();

        $gameStats = null;
        if ($game->status === 'finished') {
            $gameStats = $this->statisticsService->getGameStatistics($game);
        }

        // Add computed display names for safe frontend access
        $game->home_team_display_name = $game->getHomeTeamDisplayName();
        $game->away_team_display_name = $game->getAwayTeamDisplayName();

        // Get current player's registration if they are a player
        $currentPlayerRegistration = null;
        if (auth()->user()->player) {
            $currentPlayerRegistration = $game->registrations()
                ->where('player_id', auth()->user()->player->id)
                ->first();
        }

        return Inertia::render('Games/Show', [
            'game' => $game,
            'recentGameActions' => $recentGameActions,
            'statistics' => $gameStats,
            'currentPlayerRegistration' => $currentPlayerRegistration,
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
            return redirect()->route('web.games.show', $game)
                ->with('error', 'Nur geplante Spiele können bearbeitet werden.');
        }

        $teams = Team::query()
            ->with('club')
            ->select(['id', 'name', 'club_id'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Games/Edit', [
            'game' => $game->load(['homeTeam', 'awayTeam']),
            'teams' => $teams,
            'can' => [
                'update' => auth()->user()->can('update', $game),
                'delete' => auth()->user()->can('delete', $game),
            ],
        ]);
    }

    /**
     * Update the specified game in storage.
     */
    public function update(Request $request, Game $game)
    {
        $this->authorize('update', $game);

        // Note: Allow editing of metadata for all games, but UI restricts certain fields for non-scheduled games

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
            // Tournament fields
            'tournament_id' => 'nullable|string|max:255',
            'tournament_round' => 'nullable|string|max:100',
            'tournament_game_number' => 'nullable|integer|min:1',
            // Game rules
            'total_periods' => 'nullable|integer|min:1|max:8',
            'period_length_minutes' => 'nullable|integer|min:1|max:30',
            'overtime_length_minutes' => 'nullable|integer|min:1|max:15',
            // Event details
            'capacity' => 'nullable|integer|min:0',
            'allow_spectators' => 'nullable|boolean',
            'allow_media' => 'nullable|boolean',
            // Media
            'is_streamed' => 'nullable|boolean',
            'stream_url' => 'nullable|url|max:255',
            'allow_recording' => 'nullable|boolean',
            'allow_photos' => 'nullable|boolean',
            'allow_streaming' => 'nullable|boolean',
            // Safety
            'medical_staff_present' => 'nullable|string|max:255',
            // Weather
            'weather_conditions' => 'nullable|in:sunny,cloudy,rainy,snowy,indoor',
            'temperature' => 'nullable|integer|min:-20|max:50',
            'court_conditions' => 'nullable|in:excellent,good,fair,poor,wet,slippery',
            // Home/Away designation
            'is_home_game' => 'required|boolean',
        ]);

        // Validation: either away_team_id OR away_team_name must be provided
        if (empty($validated['away_team_id']) && empty($validated['away_team_name'])) {
            return back()->withErrors([
                'away_team_id' => 'Entweder ein internes Team oder ein externer Teamname muss angegeben werden.',
                'away_team_name' => 'Entweder ein internes Team oder ein externer Teamname muss angegeben werden.',
            ]);
        }

        $game->update($validated);

        return redirect()->route('web.games.show', $game)
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
            return redirect()->route('web.games.show', $game)
                ->with('error', 'Nur geplante Spiele können gelöscht werden.');
        }

        $game->delete();

        return redirect()->route('web.games.index')
            ->with('success', 'Spiel wurde erfolgreich gelöscht.');
    }
}
