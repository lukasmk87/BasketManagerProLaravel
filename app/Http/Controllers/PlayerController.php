<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\Team;
use App\Services\PlayerService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlayerController extends Controller
{
    public function __construct(
        private PlayerService $playerService
    ) {}

    /**
     * Display a listing of players.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        // Get players based on user permissions
        $players = Player::query()
            ->with(['team.club', 'user'])
            ->join('users', 'players.user_id', '=', 'users.id')
            ->when($user->hasRole('admin') || $user->hasRole('super-admin'), function ($query) {
                // Admin users see all players
                return $query;
            }, function ($query) use ($user) {
                // Other users see players from their teams/clubs
                return $query->whereHas('team', function ($q) use ($user) {
                    $q->where('head_coach_id', $user->id)
                      ->orWhere('assistant_coach_id', $user->id)
                      ->orWhereHas('club.users', function ($subQ) use ($user) {
                          $subQ->where('user_id', $user->id);
                      });
                });
            })
            ->orderBy('jersey_number')
            ->orderBy('users.name')
            ->select('players.*')
            ->paginate(20);

        return Inertia::render('Players/Index', [
            'players' => $players,
            'can' => [
                'create' => $user->can('create', Player::class),
            ],
        ]);
    }

    /**
     * Show the form for creating a new player.
     */
    public function create(): Response
    {
        $this->authorize('create', Player::class);

        $teams = Team::query()
            ->with('club')
            ->select(['id', 'name', 'club_id'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Players/Create', [
            'teams' => $teams,
        ]);
    }

    /**
     * Store a newly created player in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Player::class);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'team_id' => 'required|exists:teams,id',
            'jersey_number' => 'required|integer|min:0|max:99',
            'primary_position' => 'required|in:PG,SG,SF,PF,C',
            'secondary_position' => 'nullable|in:PG,SG,SF,PF,C',
            'height' => 'nullable|integer|min:100|max:250',
            'weight' => 'nullable|integer|min:30|max:200',
            'birth_date' => 'nullable|date|before:today',
            'nationality' => 'nullable|string|max:2',
            'is_captain' => 'boolean',
            'is_starter' => 'boolean',
            'status' => 'required|in:active,inactive,injured,suspended',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Check jersey number uniqueness within team
        $existingPlayer = Player::where('team_id', $validated['team_id'])
            ->where('jersey_number', $validated['jersey_number'])
            ->first();

        if ($existingPlayer) {
            return back()->withErrors([
                'jersey_number' => 'Diese Rückennummer ist bereits im Team vergeben.'
            ]);
        }

        $player = $this->playerService->createPlayer($validated);

        return redirect()->route('players.show', $player)
            ->with('success', 'Spieler wurde erfolgreich erstellt.');
    }

    /**
     * Display the specified player.
     */
    public function show(Player $player): Response
    {
        $this->authorize('view', $player);

        $player->load([
            'team.club',
            'user',
            'gameActions.game'
        ]);

        $playerStats = $this->playerService->getPlayerStatistics($player, $player->team->season);

        return Inertia::render('Players/Show', [
            'player' => $player,
            'statistics' => $playerStats,
            'can' => [
                'update' => auth()->user()->can('update', $player),
                'delete' => auth()->user()->can('delete', $player),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified player.
     */
    public function edit(Player $player): Response
    {
        $this->authorize('update', $player);

        $teams = Team::query()
            ->with('club')
            ->select(['id', 'name', 'club_id'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Players/Edit', [
            'player' => $player,
            'teams' => $teams,
        ]);
    }

    /**
     * Update the specified player in storage.
     */
    public function update(Request $request, Player $player)
    {
        $this->authorize('update', $player);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'team_id' => 'required|exists:teams,id',
            'jersey_number' => 'required|integer|min:0|max:99',
            'primary_position' => 'required|in:PG,SG,SF,PF,C',
            'secondary_position' => 'nullable|in:PG,SG,SF,PF,C',
            'height' => 'nullable|integer|min:100|max:250',
            'weight' => 'nullable|integer|min:30|max:200',
            'birth_date' => 'nullable|date|before:today',
            'nationality' => 'nullable|string|max:2',
            'is_captain' => 'boolean',
            'is_starter' => 'boolean',
            'status' => 'required|in:active,inactive,injured,suspended',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Check jersey number uniqueness within team (excluding current player)
        $existingPlayer = Player::where('team_id', $validated['team_id'])
            ->where('jersey_number', $validated['jersey_number'])
            ->where('id', '!=', $player->id)
            ->first();

        if ($existingPlayer) {
            return back()->withErrors([
                'jersey_number' => 'Diese Rückennummer ist bereits im Team vergeben.'
            ]);
        }

        $this->playerService->updatePlayer($player, $validated);

        return redirect()->route('players.show', $player)
            ->with('success', 'Spieler wurde erfolgreich aktualisiert.');
    }

    /**
     * Remove the specified player from storage.
     */
    public function destroy(Player $player)
    {
        $this->authorize('delete', $player);

        $this->playerService->deletePlayer($player);

        return redirect()->route('players.index')
            ->with('success', 'Spieler wurde erfolgreich gelöscht.');
    }
}