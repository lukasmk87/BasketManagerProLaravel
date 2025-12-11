<?php

namespace App\Http\Controllers\ClubAdmin;

use App\Http\Controllers\Controller;
use App\Models\Season;
use App\Services\SeasonService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ClubSeasonController extends Controller
{
    public function __construct(
        private SeasonService $seasonService
    ) {
        $this->middleware(['auth', 'verified', 'role:club_admin|admin|super_admin']);
    }

    /**
     * Get the primary club for the authenticated user.
     */
    private function getPrimaryClub()
    {
        $user = Auth::user();
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        return $adminClubs->first();
    }

    /**
     * Display a listing of seasons.
     */
    public function index(): Response
    {
        $club = $this->getPrimaryClub();

        $seasons = Season::where('club_id', $club->id)
            ->withCount(['teams', 'games'])
            ->orderByDesc('is_current')
            ->orderByDesc('start_date')
            ->paginate(10);

        return Inertia::render('ClubAdmin/Seasons/Index', [
            'club' => [
                'id' => $club->id,
                'name' => $club->name,
            ],
            'seasons' => $seasons,
        ]);
    }

    /**
     * Show the form for creating a new season.
     */
    public function create(): Response
    {
        $club = $this->getPrimaryClub();

        return Inertia::render('ClubAdmin/Seasons/Wizard/Index', [
            'club' => [
                'id' => $club->id,
                'name' => $club->name,
            ],
        ]);
    }

    /**
     * Store a newly created season.
     */
    public function store(Request $request): RedirectResponse
    {
        $club = $this->getPrimaryClub();

        $validated = $request->validate([
            'name' => 'required|string|max:20',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'description' => 'nullable|string|max:1000',
            'settings' => 'nullable|array',
            'selected_teams' => 'nullable|array',
            'selected_teams.*' => 'exists:teams,id',
            'auto_activate' => 'nullable|boolean',
        ]);

        try {
            $season = $this->seasonService->createNewSeason(
                $club,
                $validated['name'],
                $validated['start_date'],
                $validated['end_date'],
                $validated['description'] ?? null,
                $validated['settings'] ?? []
            );

            // Teams der Saison zuordnen
            if (! empty($validated['selected_teams'])) {
                $this->seasonService->assignTeamsToSeason(
                    $season,
                    $validated['selected_teams']
                );
            }

            // Optional automatisch aktivieren
            if ($validated['auto_activate'] ?? false) {
                $this->seasonService->activateSeason($season);
            }

            return redirect()
                ->route('club-admin.seasons.show', $season)
                ->with('success', 'Saison erfolgreich erstellt.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Fehler beim Erstellen der Saison: '.$e->getMessage());
        }
    }

    /**
     * Display the specified season.
     */
    public function show(Season $season): Response
    {
        $club = $this->getPrimaryClub();

        // Verify the season belongs to the club
        if ($season->club_id !== $club->id) {
            abort(403, 'Diese Saison gehört nicht zu Ihrem Club.');
        }

        $season->load(['teams', 'games']);
        $season->loadCount(['teams', 'games']);

        return Inertia::render('ClubAdmin/Seasons/Show', [
            'club' => [
                'id' => $club->id,
                'name' => $club->name,
            ],
            'season' => $season,
        ]);
    }

    /**
     * Show the form for editing the specified season.
     */
    public function edit(Season $season): Response
    {
        $club = $this->getPrimaryClub();

        // Verify the season belongs to the club
        if ($season->club_id !== $club->id) {
            abort(403, 'Diese Saison gehört nicht zu Ihrem Club.');
        }

        return Inertia::render('ClubAdmin/Seasons/Edit', [
            'club' => [
                'id' => $club->id,
                'name' => $club->name,
            ],
            'season' => $season,
        ]);
    }

    /**
     * Update the specified season.
     */
    public function update(Request $request, Season $season): RedirectResponse
    {
        $club = $this->getPrimaryClub();

        // Verify the season belongs to the club
        if ($season->club_id !== $club->id) {
            abort(403, 'Diese Saison gehört nicht zu Ihrem Club.');
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:20',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'description' => 'nullable|string|max:1000',
            'settings' => 'nullable|array',
        ]);

        try {
            $season->update($validated);

            return redirect()
                ->route('club-admin.seasons.show', $season)
                ->with('success', 'Saison erfolgreich aktualisiert.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Fehler beim Aktualisieren der Saison: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified season.
     */
    public function destroy(Season $season): RedirectResponse
    {
        $club = $this->getPrimaryClub();

        // Verify the season belongs to the club
        if ($season->club_id !== $club->id) {
            abort(403, 'Diese Saison gehört nicht zu Ihrem Club.');
        }

        // Prevent deletion of active season
        if ($season->is_current) {
            return back()->with('error', 'Die aktuelle Saison kann nicht gelöscht werden.');
        }

        try {
            $season->delete();

            return redirect()
                ->route('club-admin.seasons.index')
                ->with('success', 'Saison erfolgreich gelöscht.');
        } catch (\Exception $e) {
            return back()->with('error', 'Fehler beim Löschen der Saison: '.$e->getMessage());
        }
    }

    /**
     * Activate the specified season.
     */
    public function activate(Season $season): RedirectResponse
    {
        $club = $this->getPrimaryClub();

        // Verify the season belongs to the club
        if ($season->club_id !== $club->id) {
            abort(403, 'Diese Saison gehört nicht zu Ihrem Club.');
        }

        try {
            $this->seasonService->activateSeason($season);

            return back()->with('success', 'Saison erfolgreich aktiviert.');
        } catch (\Exception $e) {
            return back()->with('error', 'Fehler beim Aktivieren der Saison: '.$e->getMessage());
        }
    }

    /**
     * Complete the specified season.
     */
    public function complete(Request $request, Season $season): RedirectResponse
    {
        $club = $this->getPrimaryClub();

        // Verify the season belongs to the club
        if ($season->club_id !== $club->id) {
            abort(403, 'Diese Saison gehört nicht zu Ihrem Club.');
        }

        $createSnapshots = $request->boolean('create_snapshots', true);

        try {
            $this->seasonService->completeSeason($season, $createSnapshots);

            return back()->with('success', 'Saison erfolgreich abgeschlossen.');
        } catch (\Exception $e) {
            return back()->with('error', 'Fehler beim Abschließen der Saison: '.$e->getMessage());
        }
    }
}
