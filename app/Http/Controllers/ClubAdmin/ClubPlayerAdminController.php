<?php

namespace App\Http\Controllers\ClubAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClubAdmin\StoreClubPlayerRequest;
use App\Http\Requests\ClubAdmin\UpdateClubPlayerRequest;
use App\Models\BasketballTeam;
use App\Models\Player;
use App\Models\User;
use App\Services\PlayerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ClubPlayerAdminController extends Controller
{
    public function __construct(
        private PlayerService $playerService
    ) {
        $this->middleware(['auth', 'verified', 'role:club_admin|admin|super_admin']);
    }

    /**
     * Show club players management page.
     */
    public function index(): Response
    {
        $user = Auth::user();
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();

        $players = Player::whereHas('teams', function ($query) use ($primaryClub) {
            $query->where('club_id', $primaryClub->id);
        })
            ->select(['players.id', 'players.user_id', 'players.status', 'players.created_at'])
            ->with([
                'user:id,name,email,birth_date',
                'teams' => fn ($q) => $q->where('club_id', $primaryClub->id)
                    ->select(['basketball_teams.id', 'basketball_teams.name'])
                    ->withPivot('jersey_number', 'primary_position'),
            ])
            ->get()
            ->map(function ($player) {
                return [
                    'id' => $player->id,
                    'name' => $player->user?->name ?? $player->full_name,
                    'email' => $player->user?->email,
                    'status' => $player->status,
                    'birth_date' => $player->user?->birth_date,
                    'teams' => $player->teams->map(fn ($team) => [
                        'id' => $team->id,
                        'name' => $team->name,
                        'jersey_number' => $team->pivot->jersey_number,
                        'position' => $team->pivot->primary_position,
                    ]),
                    'created_at' => $player->created_at,
                ];
            });

        $pendingPlayersCount = Player::where('pending_team_assignment', true)
            ->whereHas('registeredViaInvitation', function ($q) use ($primaryClub) {
                $q->where('club_id', $primaryClub->id);
            })
            ->count();

        return Inertia::render('ClubAdmin/Players/Index', [
            'club' => [
                'id' => $primaryClub->id,
                'name' => $primaryClub->name,
            ],
            'players' => $players,
            'pending_players_count' => $pendingPlayersCount,
        ]);
    }

    /**
     * Show the create player form.
     */
    public function create(): Response
    {
        $user = Auth::user();
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();

        $teams = $primaryClub->teams()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'season', 'age_group']);

        return Inertia::render('ClubAdmin/Players/Create', [
            'club' => [
                'id' => $primaryClub->id,
                'name' => $primaryClub->name,
            ],
            'teams' => $teams,
            'positions' => ['PG', 'SG', 'SF', 'PF', 'C'],
        ]);
    }

    /**
     * Store a newly created player.
     */
    public function store(StoreClubPlayerRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();
        $validated = $request->validated();

        try {
            $playerUser = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => bcrypt($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'birth_date' => $validated['birth_date'] ?? null,
                'is_active' => true,
            ]);

            $playerUser->assignRole('player');

            $primaryClub->users()->attach($playerUser->id, [
                'role' => 'player',
                'joined_at' => now(),
                'is_active' => true,
            ]);

            $player = $this->playerService->createPlayer(
                user: $playerUser,
                status: 'active'
            );

            if (! empty($validated['team_id'])) {
                $team = BasketballTeam::find($validated['team_id']);
                if ($team && $team->club_id === $primaryClub->id) {
                    $player->teams()->attach($team->id, [
                        'jersey_number' => $validated['jersey_number'] ?? null,
                        'primary_position' => $validated['primary_position'] ?? null,
                        'joined_at' => now(),
                        'is_active' => true,
                    ]);
                }
            }

            Log::info('Club admin created player', [
                'club_admin_id' => $user->id,
                'club_id' => $primaryClub->id,
                'player_id' => $player->id,
                'user_id' => $playerUser->id,
            ]);

            return redirect()->route('club-admin.players.index')
                ->with('success', 'Spieler wurde erfolgreich erstellt.');
        } catch (\Exception $e) {
            Log::error('Failed to create player', [
                'club_admin_id' => $user->id,
                'club_id' => $primaryClub->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->with('error', 'Fehler beim Erstellen des Spielers: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show the edit player form.
     */
    public function edit(Player $player): Response
    {
        $user = Auth::user();
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();

        $playerTeam = $player->teams()
            ->where('club_id', $primaryClub->id)
            ->with('club:id,name')
            ->first();

        if (! $playerTeam) {
            abort(403, 'Dieser Spieler gehört nicht zu Ihrem Club.');
        }

        $this->authorize('update', $player);

        $teams = $primaryClub->teams()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'season', 'age_group']);

        return Inertia::render('ClubAdmin/Players/Edit', [
            'club' => [
                'id' => $primaryClub->id,
                'name' => $primaryClub->name,
            ],
            'player' => [
                'id' => $player->id,
                'name' => $player->user?->name ?? $player->full_name,
                'email' => $player->user?->email,
                'phone' => $player->user?->phone,
                'birth_date' => $player->user?->birth_date,
                'status' => $player->status,
                'team_id' => $playerTeam?->id,
                'jersey_number' => $playerTeam?->pivot->jersey_number,
                'primary_position' => $playerTeam?->pivot->primary_position,
            ],
            'teams' => $teams,
            'positions' => ['PG', 'SG', 'SF', 'PF', 'C'],
        ]);
    }

    /**
     * Update an existing player.
     */
    public function update(UpdateClubPlayerRequest $request, Player $player): RedirectResponse
    {
        $user = Auth::user();
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();

        $playerTeam = $player->teams()->where('club_id', $primaryClub->id)->first();
        if (! $playerTeam) {
            abort(403, 'Dieser Spieler gehört nicht zu Ihrem Club.');
        }

        $this->authorize('update', $player);

        $validated = $request->validated();

        try {
            if ($player->user) {
                $player->user->update([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'] ?? null,
                    'birth_date' => $validated['birth_date'] ?? null,
                ]);
            }

            $player->update([
                'status' => $validated['status'],
            ]);

            if (! empty($validated['team_id'])) {
                $team = BasketballTeam::find($validated['team_id']);
                if ($team && $team->club_id === $primaryClub->id) {
                    $player->teams()->wherePivot('club_id', $primaryClub->id)->detach();
                    $player->teams()->attach($team->id, [
                        'jersey_number' => $validated['jersey_number'] ?? null,
                        'primary_position' => $validated['primary_position'] ?? null,
                        'joined_at' => now(),
                        'is_active' => true,
                    ]);
                }
            }

            Log::info('Club admin updated player', [
                'club_admin_id' => $user->id,
                'club_id' => $primaryClub->id,
                'player_id' => $player->id,
            ]);

            return redirect()->route('club-admin.players.index')
                ->with('success', 'Spieler wurde erfolgreich aktualisiert.');
        } catch (\Exception $e) {
            Log::error('Failed to update player', [
                'club_admin_id' => $user->id,
                'club_id' => $primaryClub->id,
                'player_id' => $player->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->with('error', 'Fehler beim Aktualisieren des Spielers: ' . $e->getMessage())
                ->withInput();
        }
    }
}
