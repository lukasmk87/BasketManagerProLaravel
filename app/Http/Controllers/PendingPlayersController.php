<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\BasketballTeam;
use App\Services\PlayerRegistrationService;
use App\Http\Requests\AssignPlayerToTeamRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class PendingPlayersController extends Controller
{
    protected PlayerRegistrationService $registrationService;

    public function __construct(PlayerRegistrationService $registrationService)
    {
        $this->registrationService = $registrationService;
    }

    /**
     * Display a listing of pending players for the club admin's clubs.
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();

        // Get clubs the user is admin of
        $userClubIds = $user->clubs()->wherePivot('role', 'club_admin')->pluck('clubs.id')->toArray();

        if (empty($userClubIds)) {
            return Inertia::render('ClubAdmin/PendingPlayers/Index', [
                'pendingPlayers' => [],
                'teams' => [],
                'message' => __('You are not a club administrator.'),
            ]);
        }

        // Get pending players
        $query = Player::with([
                'user',
                'registeredViaInvitation.club',
                'registeredViaInvitation.targetTeam',
                'registeredViaInvitation.creator',
            ])
            ->where('pending_team_assignment', true)
            ->whereHas('registeredViaInvitation', function ($q) use ($userClubIds) {
                $q->whereIn('club_id', $userClubIds);
            })
            ->orderBy('registration_completed_at', 'desc');

        // Filter by club
        if ($request->has('club_id')) {
            $clubId = $request->get('club_id');
            $query->whereHas('registeredViaInvitation', function ($q) use ($clubId) {
                $q->where('club_id', $clubId);
            });
        }

        // Search by name
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $pendingPlayers = $query->paginate(20)->withQueryString();

        // Get teams for the clubs (for assignment dropdown)
        $teams = BasketballTeam::whereIn('club_id', $userClubIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'club_id', 'age_group', 'gender']);

        // Get clubs for filter
        $clubs = \App\Models\Club::whereIn('id', $userClubIds)->get(['id', 'name']);

        return Inertia::render('ClubAdmin/PendingPlayers/Index', [
            'pendingPlayers' => $pendingPlayers,
            'teams' => $teams,
            'clubs' => $clubs,
            'filters' => [
                'club_id' => $request->get('club_id'),
                'search' => $request->get('search'),
            ],
        ]);
    }

    /**
     * Assign a single player to a team.
     */
    public function assign(AssignPlayerToTeamRequest $request): RedirectResponse
    {
        // Authorization and validation are handled by the Form Request
        $playerId = $request->validated('player_id');
        $teamId = $request->validated('team_id');
        $teamData = $request->validated('team_data', []);

        // Assign player to team
        $result = $this->registrationService->assignPlayerToTeam(
            $playerId,
            $teamId,
            Auth::id(),
            $teamData
        );

        if (!$result['success']) {
            return back()->with('error', $result['error']);
        }

        return back()->with('success', __('Player successfully assigned to team! They can now log in.'));
    }

    /**
     * Assign multiple players to teams at once.
     */
    public function bulkAssign(Request $request): RedirectResponse
    {
        $assignments = $request->validate([
            'assignments' => 'required|array|min:1',
            'assignments.*.player_id' => 'required|exists:players,id',
            'assignments.*.team_id' => 'required|exists:teams,id',
            'assignments.*.team_data' => 'sometimes|array',
        ]);

        $user = Auth::user();
        $userClubIds = $user->clubs()->wherePivot('role', 'club_admin')->pluck('clubs.id')->toArray();

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($assignments['assignments'] as $assignment) {
                $player = Player::find($assignment['player_id']);

                // Verify player belongs to admin's club
                $playerClubId = $player->registeredViaInvitation?->club_id;

                if (!in_array($playerClubId, $userClubIds)) {
                    $errorCount++;
                    $errors[] = __('Player :name: Permission denied', ['name' => $player->user->name ?? 'Unknown']);
                    continue;
                }

                // Assign player
                $result = $this->registrationService->assignPlayerToTeam(
                    $assignment['player_id'],
                    $assignment['team_id'],
                    Auth::id(),
                    $assignment['team_data'] ?? []
                );

                if ($result['success']) {
                    $successCount++;
                } else {
                    $errorCount++;
                    $errors[] = __('Player :name: :error', [
                        'name' => $player->user->name ?? 'Unknown',
                        'error' => $result['error']
                    ]);
                }
            }

            DB::commit();

            $message = __(':success players assigned successfully.', ['success' => $successCount]);
            if ($errorCount > 0) {
                $message .= ' ' . __(':error failed.', ['error' => $errorCount]);
            }

            return back()->with('success', $message)->with('errors', $errors);
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', __('Bulk assignment failed: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Reject a player registration (soft delete).
     */
    public function reject(Player $player): RedirectResponse
    {
        // Verify player is pending
        if (!$player->pending_team_assignment) {
            return back()->with('error', __('Player is not pending assignment.'));
        }

        // Verify player belongs to admin's club
        $user = Auth::user();
        $userClubIds = $user->clubs()->wherePivot('role', 'club_admin')->pluck('clubs.id')->toArray();

        $playerClubId = $player->registeredViaInvitation?->club_id;

        if (!in_array($playerClubId, $userClubIds)) {
            return back()->with('error', __('You do not have permission to reject this player.'));
        }

        try {
            DB::beginTransaction();

            // Soft delete player and user
            $player->user->delete(); // Soft delete user
            $player->delete(); // Soft delete player

            DB::commit();

            return back()->with('success', __('Player registration rejected.'));
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', __('Failed to reject player: :error', ['error' => $e->getMessage()]));
        }
    }
}
