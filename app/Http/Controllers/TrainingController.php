<?php

namespace App\Http\Controllers;

use App\Models\TrainingSession;
use App\Models\Drill;
use App\Services\TrainingService;
use App\Http\Requests\Drill\CreateDrillRequest;
use App\Http\Requests\Drill\UpdateDrillRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
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
            ->when(!$user->hasRole('admin') && !$user->hasRole('super_admin'), function ($query) use ($user) {
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
            ->when(!$user->hasRole('admin') && !$user->hasRole('super_admin'), function ($query) use ($user) {
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
            ->when(!$user->hasRole('admin') && !$user->hasRole('super_admin'), function ($query) use ($user) {
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

        return Inertia::render('Training/Sessions', [
            'sessions' => $sessions,
        ]);
    }

    /**
     * Display drills.
     */
    public function drills(Request $request): Response
    {
        $user = $request->user();
        
        $drills = Drill::query()
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
            'attendance.player.user'
        ]);

        return Inertia::render('Training/ShowSession', [
            'session' => $session,
        ]);
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
        $this->authorize('create', Drill::class);

        $drill = new Drill($request->validated());
        $drill->created_by_user_id = $request->user()->id;
        $drill->status = 'draft';
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
        $this->authorize('update', $drill);

        return Inertia::render('Training/EditDrill', [
            'drill' => $drill,
        ]);
    }

    /**
     * Update the specified drill.
     */
    public function updateDrill(UpdateDrillRequest $request, Drill $drill): RedirectResponse
    {
        $this->authorize('update', $drill);

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
        $this->authorize('delete', $drill);

        $drill->delete();

        return redirect()
            ->route('training.drills')
            ->with('success', 'Drill wurde erfolgreich gelöscht.');
    }
}