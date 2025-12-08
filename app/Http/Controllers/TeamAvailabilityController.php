<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Team;
use App\Models\TrainingSession;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamAvailabilityController extends Controller
{
    /**
     * Show team availability overview for trainers.
     */
    public function index(Request $request, Team $team): Response
    {
        // Authorize: user must be trainer for this team or club admin
        $user = $request->user();

        if (! $user->hasRole('super_admin') && ! $user->hasRole('tenant_admin')) {
            $coachTeamIds = $user->coachedTeams()->pluck('id')->toArray();
            $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();

            if (! in_array($team->id, $coachTeamIds) && ! in_array($team->club_id, $userClubIds)) {
                abort(403, 'Keine Berechtigung für dieses Team.');
            }
        }

        // Get upcoming events
        $now = now();
        $endDate = now()->addDays(30);

        $games = Game::where(function ($query) use ($team) {
            $query->where('home_team_id', $team->id)
                ->orWhere('away_team_id', $team->id);
        })
            ->where('scheduled_at', '>=', $now)
            ->where('scheduled_at', '<=', $endDate)
            ->whereNotIn('status', ['cancelled', 'finished', 'forfeited'])
            ->orderBy('scheduled_at')
            ->get()
            ->map(fn ($game) => [
                'type' => 'game',
                'id' => $game->id,
                'title' => ($game->home_team_name ?? 'Home').' vs '.($game->away_team_name ?? 'Away'),
                'scheduled_at' => $game->scheduled_at->toIso8601String(),
                'scheduled_at_formatted' => $game->scheduled_at->format('d.m.Y H:i'),
            ]);

        $trainings = TrainingSession::where('team_id', $team->id)
            ->where('scheduled_at', '>=', $now)
            ->where('scheduled_at', '<=', $endDate)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->orderBy('scheduled_at')
            ->get()
            ->map(fn ($training) => [
                'type' => 'training',
                'id' => $training->id,
                'title' => $training->title ?: 'Training',
                'scheduled_at' => $training->scheduled_at->toIso8601String(),
                'scheduled_at_formatted' => $training->scheduled_at->format('d.m.Y H:i'),
            ]);

        $upcomingEvents = $games->concat($trainings)
            ->sortBy('scheduled_at')
            ->values()
            ->toArray();

        return Inertia::render('Teams/Availability', [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'club_id' => $team->club_id,
            ],
            'upcomingEvents' => $upcomingEvents,
        ]);
    }
}
