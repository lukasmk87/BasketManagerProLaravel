<?php

namespace App\Http\Controllers\ClubAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClubAdmin\StoreClubTeamRequest;
use App\Http\Requests\ClubAdmin\UpdateClubTeamRequest;
use App\Models\BasketballTeam;
use App\Services\TeamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ClubTeamAdminController extends Controller
{
    public function __construct(
        private TeamService $teamService
    ) {
        $this->middleware(['auth', 'verified', 'role:club_admin|admin|super_admin']);
    }

    /**
     * Show club teams management page.
     */
    public function index(): Response
    {
        $user = Auth::user();
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();

        $teams = BasketballTeam::where('club_id', $primaryClub->id)
            ->select(['id', 'slug', 'name', 'season', 'league', 'age_group', 'gender', 'head_coach_id', 'is_active', 'created_at', 'club_id'])
            ->with(['headCoach:id,name'])
            ->withCount(['players', 'homeGames', 'awayGames'])
            ->orderBy('is_active', 'desc')
            ->orderBy('name')
            ->get()
            ->map(function ($team) {
                return [
                    'id' => $team->id,
                    'slug' => $team->slug,
                    'name' => $team->name,
                    'season' => $team->season,
                    'league' => $team->league,
                    'age_group' => $team->age_group,
                    'gender' => $team->gender,
                    'head_coach' => [
                        'id' => $team->headCoach?->id,
                        'name' => $team->headCoach?->name,
                    ],
                    'player_count' => $team->players_count,
                    'games_count' => ($team->home_games_count ?? 0) + ($team->away_games_count ?? 0),
                    'is_active' => $team->is_active,
                    'created_at' => $team->created_at,
                ];
            });

        return Inertia::render('ClubAdmin/Teams/Index', [
            'club' => [
                'id' => $primaryClub->id,
                'name' => $primaryClub->name,
            ],
            'teams' => $teams,
        ]);
    }

    /**
     * Show the create team form.
     */
    public function create(): Response
    {
        $user = Auth::user();
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();

        $coaches = $primaryClub->users()
            ->whereHas('roles', function ($query) {
                $query->where('name', 'trainer');
            })
            ->get(['id', 'name']);

        return Inertia::render('ClubAdmin/Teams/Create', [
            'club' => [
                'id' => $primaryClub->id,
                'name' => $primaryClub->name,
            ],
            'coaches' => $coaches,
            'age_groups' => ['U8', 'U10', 'U12', 'U14', 'U16', 'U18', 'U20', 'Senior', 'Sonstige'],
            'genders' => [
                ['value' => 'male', 'label' => 'Männlich'],
                ['value' => 'female', 'label' => 'Weiblich'],
                ['value' => 'mixed', 'label' => 'Gemischt'],
            ],
        ]);
    }

    /**
     * Store a newly created team.
     */
    public function store(StoreClubTeamRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();
        $validated = $request->validated();

        try {
            $team = $this->teamService->createTeam(
                club: $primaryClub,
                name: $validated['name'],
                season: $validated['season'],
                league: $validated['league'] ?? null,
                ageGroup: $validated['age_group'] ?? null,
                gender: $validated['gender'],
                headCoachId: $validated['head_coach_id'] ?? null,
                isActive: $validated['is_active'] ?? true
            );

            Log::info('Club admin created team', [
                'club_admin_id' => $user->id,
                'club_id' => $primaryClub->id,
                'team_id' => $team->id,
            ]);

            return redirect()->route('club-admin.teams.index')
                ->with('success', 'Team wurde erfolgreich erstellt.');
        } catch (\Exception $e) {
            Log::error('Failed to create team', [
                'club_admin_id' => $user->id,
                'club_id' => $primaryClub->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->with('error', 'Fehler beim Erstellen des Teams: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show the edit team form.
     */
    public function edit(BasketballTeam $team): Response
    {
        $user = Auth::user();
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $adminClubIds = $adminClubs->pluck('id')->toArray();

        if (! in_array($team->club_id, $adminClubIds)) {
            abort(403, 'Dieses Team gehört nicht zu einem Ihrer Clubs.');
        }

        $this->authorize('update', $team);

        $teamClub = $team->club;

        $coaches = $teamClub->users()
            ->whereHas('roles', function ($query) {
                $query->where('name', 'trainer');
            })
            ->get(['id', 'name']);

        return Inertia::render('ClubAdmin/Teams/Edit', [
            'club' => [
                'id' => $teamClub->id,
                'name' => $teamClub->name,
            ],
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'season' => $team->season,
                'league' => $team->league,
                'age_group' => $team->age_group,
                'gender' => $team->gender,
                'head_coach_id' => $team->head_coach_id,
                'is_active' => $team->is_active,
            ],
            'coaches' => $coaches,
            'age_groups' => ['U8', 'U10', 'U12', 'U14', 'U16', 'U18', 'U20', 'Senior', 'Sonstige'],
            'genders' => [
                ['value' => 'male', 'label' => 'Männlich'],
                ['value' => 'female', 'label' => 'Weiblich'],
                ['value' => 'mixed', 'label' => 'Gemischt'],
            ],
        ]);
    }

    /**
     * Update an existing team.
     */
    public function update(UpdateClubTeamRequest $request, BasketballTeam $team): RedirectResponse
    {
        $user = Auth::user();
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $adminClubIds = $adminClubs->pluck('id')->toArray();

        if (! in_array($team->club_id, $adminClubIds)) {
            abort(403, 'Dieses Team gehört nicht zu einem Ihrer Clubs.');
        }

        $this->authorize('update', $team);

        $validated = $request->validated();

        try {
            $team->update([
                'name' => $validated['name'],
                'season' => $validated['season'],
                'league' => $validated['league'] ?? null,
                'age_group' => $validated['age_group'] ?? null,
                'gender' => $validated['gender'],
                'head_coach_id' => $validated['head_coach_id'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            Log::info('Club admin updated team', [
                'club_admin_id' => $user->id,
                'club_id' => $team->club_id,
                'team_id' => $team->id,
            ]);

            return redirect()->route('club-admin.teams.index')
                ->with('success', 'Team wurde erfolgreich aktualisiert.');
        } catch (\Exception $e) {
            Log::error('Failed to update team', [
                'club_admin_id' => $user->id,
                'club_id' => $team->club_id,
                'team_id' => $team->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->with('error', 'Fehler beim Aktualisieren des Teams: ' . $e->getMessage())
                ->withInput();
        }
    }
}
