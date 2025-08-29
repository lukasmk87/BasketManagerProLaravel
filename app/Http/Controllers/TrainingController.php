<?php

namespace App\Http\Controllers;

use App\Http\Requests\Drill\CreateDrillRequest;
use App\Http\Requests\Drill\UpdateDrillRequest;
use App\Models\Drill;
use App\Models\TrainingSession;
use App\Services\TrainingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrainingController extends Controller
{
    public function __construct(
        private TrainingService $trainingService
    ) {}

    /**
     * Display training overview.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $upcomingSessions = TrainingSession::query()
            ->with(['team.club'])
            ->where('scheduled_at', '>', now())
            ->when(! $user->hasRole('admin') && ! $user->hasRole('super_admin'), function ($query) use ($user) {
                return $query->whereHas('team', function ($q) use ($user) {
                    $q->where('head_coach_id', $user->id)
                        ->orWhereJsonContains('assistant_coaches', $user->id)
                        ->orWhereHas('club.users', function ($subQ) use ($user) {
                            $subQ->where('user_id', $user->id);
                        });
                });
            })
            ->orderBy('scheduled_at')
            ->limit(10)
            ->get();

        $recentSessions = TrainingSession::query()
            ->with(['team.club'])
            ->where('scheduled_at', '<', now())
            ->when(! $user->hasRole('admin') && ! $user->hasRole('super_admin'), function ($query) use ($user) {
                return $query->whereHas('team', function ($q) use ($user) {
                    $q->where('head_coach_id', $user->id)
                        ->orWhereJsonContains('assistant_coaches', $user->id)
                        ->orWhereHas('club.users', function ($subQ) use ($user) {
                            $subQ->where('user_id', $user->id);
                        });
                });
            })
            ->orderBy('scheduled_at', 'desc')
            ->limit(10)
            ->get();

        return Inertia::render('Training/Index', [
            'upcomingSessions' => $upcomingSessions,
            'recentSessions' => $recentSessions,
        ]);
    }

    /**
     * Display training sessions.
     */
    public function sessions(Request $request): Response
    {
        $user = $request->user();

        $sessions = TrainingSession::query()
            ->with(['team.club', 'drills'])
            ->when(! $user->hasRole('admin') && ! $user->hasRole('super_admin'), function ($query) use ($user) {
                return $query->whereHas('team', function ($q) use ($user) {
                    $q->where('head_coach_id', $user->id)
                        ->orWhereJsonContains('assistant_coaches', $user->id)
                        ->orWhereHas('club.users', function ($subQ) use ($user) {
                            $subQ->where('user_id', $user->id);
                        });
                });
            })
            ->orderBy('scheduled_at', 'desc')
            ->paginate(20);

        // Get teams and coaches for filters
        $teams = $user->hasRole(['admin', 'super_admin'])
            ? \App\Models\Team::with('club')->orderBy('name')->get()
            : $user->coachedTeams()->with('club')->orderBy('name')->get();

        $coaches = $user->hasRole(['admin', 'super_admin'])
            ? \App\Models\User::whereHas('roles', function ($q) {
                $q->where('name', 'trainer');
            })->orderBy('name')->get()
            : collect([$user]);

        return Inertia::render('Training/Sessions', [
            'sessions' => $sessions,
            'teams' => $teams,
            'coaches' => $coaches,
            'can' => [
                'create' => $user->can('create', TrainingSession::class),
                'update' => $user->can('updateAny', TrainingSession::class),
                'delete' => $user->can('deleteAny', TrainingSession::class),
            ],
        ]);
    }

    /**
     * Display drills.
     */
    public function drills(Request $request): Response
    {
        $user = $request->user();

        $drills = Drill::query()
            ->accessibleByUser($user->id)
            ->withAvg('ratings', 'rating')
            ->withCount(['ratings', 'favorites'])
            ->orderBy('name')
            ->paginate(20);

        return Inertia::render('Training/Drills', [
            'drills' => $drills,
            'can' => [
                'create' => $user->can('create', Drill::class),
                'update' => $user->can('updateAny', Drill::class),
                'delete' => $user->can('deleteAny', Drill::class),
            ],
        ]);
    }

    /**
     * Show specific training session.
     */
    public function showSession(TrainingSession $session): Response
    {
        $session->load([
            'team.club',
            'drills',
            'attendance.player.user',
            'registrations.player',
        ]);

        // Get current player's registration if they are a player
        $currentPlayerRegistration = null;
        if (auth()->user()->player) {
            $currentPlayerRegistration = $session->registrations()
                ->where('player_id', auth()->user()->player->id)
                ->first();
        }

        return Inertia::render('Training/ShowSession', [
            'session' => $session,
            'currentPlayerRegistration' => $currentPlayerRegistration,
            'can' => [
                'update' => auth()->user()->can('update', $session),
                'delete' => auth()->user()->can('delete', $session),
            ],
        ]);
    }

    /**
     * Show the form for creating a new training session.
     */
    public function createSession(): Response
    {
        $this->authorize('create', TrainingSession::class);

        $user = auth()->user();

        // Get teams that the user can create sessions for
        $teams = $user->hasRole(['admin', 'super_admin'])
            ? \App\Models\Team::with('club')->orderBy('name')->get()
            : $user->coachedTeams()->with('club')->orderBy('name')->get();

        // Get available drills (active drills + user's own draft drills)
        $drills = \App\Models\Drill::accessibleByUser($user->id)
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'estimated_duration', 'status', 'created_by_user_id']);

        return Inertia::render('Training/CreateSession', [
            'teams' => $teams,
            'drills' => $drills,
        ]);
    }

    /**
     * Store a newly created training session.
     */
    public function storeSession(\App\Http\Requests\TrainingSession\CreateTrainingSessionRequest $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->can('create', TrainingSession::class)) {
            abort(403, 'Sie haben keine Berechtigung, Trainingseinheiten zu erstellen.');
        }

        try {
            $session = $this->trainingService->createTrainingSession($request->validated());

            return redirect()
                ->route('training.sessions.show', $session)
                ->with('success', 'Trainingseinheit wurde erfolgreich erstellt.');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Fehler beim Erstellen der Trainingseinheit: '.$e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Show the form for editing a training session.
     */
    public function editSession(TrainingSession $session): Response
    {
        $user = auth()->user();

        if (! $user->can('update', $session)) {
            abort(403, 'Sie haben keine Berechtigung, diese Trainingseinheit zu bearbeiten.');
        }

        $session->load(['team.club', 'drills']);

        // Get teams that the user can assign sessions to
        $teams = $user->hasRole(['admin', 'super_admin'])
            ? \App\Models\Team::with('club')->orderBy('name')->get()
            : $user->coachedTeams()->with('club')->orderBy('name')->get();

        // Get available drills (active drills + user's own draft drills)
        $drills = \App\Models\Drill::accessibleByUser($user->id)
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'estimated_duration', 'status', 'created_by_user_id']);

        return Inertia::render('Training/EditSession', [
            'session' => $session,
            'teams' => $teams,
            'drills' => $drills,
        ]);
    }

    /**
     * Update the specified training session.
     */
    public function updateSession(\App\Http\Requests\TrainingSession\UpdateTrainingSessionRequest $request, TrainingSession $session): RedirectResponse
    {
        $user = $request->user();

        if (! $user->can('update', $session)) {
            abort(403, 'Sie haben keine Berechtigung, diese Trainingseinheit zu bearbeiten.');
        }

        try {
            $updatedSession = $this->trainingService->updateTrainingSession($session, $request->validated());

            return redirect()
                ->route('training.sessions.show', $updatedSession)
                ->with('success', 'Trainingseinheit wurde erfolgreich aktualisiert.');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Fehler beim Aktualisieren der Trainingseinheit: '.$e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Show the form for creating a new drill.
     */
    public function createDrill(): Response
    {
        $this->authorize('create', Drill::class);

        return Inertia::render('Training/CreateDrill');
    }

    /**
     * Store a newly created drill.
     */
    public function storeDrill(CreateDrillRequest $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->can('create', Drill::class)) {
            abort(403, 'Sie haben keine Berechtigung, Trainingsübungen zu erstellen.');
        }

        $drill = new Drill($request->validated());
        $drill->created_by_user_id = $user->id;
        $drill->save();

        return redirect()
            ->route('training.drills')
            ->with('success', 'Drill wurde erfolgreich erstellt.');
    }

    /**
     * Display the specified drill.
     */
    public function showDrill(Drill $drill): Response
    {
        $drill->load(['createdBy', 'ratings.user', 'favorites']);

        return Inertia::render('Training/ShowDrill', [
            'drill' => $drill,
            'can' => [
                'edit' => auth()->user()->can('update', $drill),
                'delete' => auth()->user()->can('delete', $drill),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified drill.
     */
    public function editDrill(Drill $drill): Response
    {
        $user = auth()->user();

        if (! $user->can('update', $drill)) {
            abort(403, 'Sie haben keine Berechtigung, diese Trainingsübung zu bearbeiten.');
        }

        return Inertia::render('Training/EditDrill', [
            'drill' => $drill,
        ]);
    }

    /**
     * Update the specified drill.
     */
    public function updateDrill(UpdateDrillRequest $request, Drill $drill): RedirectResponse
    {
        $user = $request->user();

        if (! $user->can('update', $drill)) {
            abort(403, 'Sie haben keine Berechtigung, diese Trainingsübung zu bearbeiten.');
        }

        $drill->update($request->validated());

        return redirect()
            ->route('training.drills.show', $drill)
            ->with('success', 'Drill wurde erfolgreich aktualisiert.');
    }

    /**
     * Remove the specified drill.
     */
    public function destroyDrill(Drill $drill): RedirectResponse
    {
        $user = auth()->user();

        if (! $user->can('delete', $drill)) {
            abort(403, 'Sie haben keine Berechtigung, diese Trainingsübung zu löschen.');
        }

        $drill->delete();

        return redirect()
            ->route('training.drills')
            ->with('success', 'Drill wurde erfolgreich gelöscht.');
    }
}
