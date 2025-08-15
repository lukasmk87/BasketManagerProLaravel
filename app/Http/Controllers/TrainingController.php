<?php

namespace App\Http\Controllers;

use App\Models\TrainingSession;
use App\Models\Drill;
use App\Services\TrainingService;
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
            ->when(!$user->hasRole('admin') && !$user->hasRole('super-admin'), function ($query) use ($user) {
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
            ->when(!$user->hasRole('admin') && !$user->hasRole('super-admin'), function ($query) use ($user) {
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
            ->when(!$user->hasRole('admin') && !$user->hasRole('super-admin'), function ($query) use ($user) {
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
        $drills = Drill::query()
            ->withAvg('ratings', 'rating')
            ->withCount(['ratings', 'favorites'])
            ->orderBy('name')
            ->paginate(20);

        return Inertia::render('Training/Drills', [
            'drills' => $drills,
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
}