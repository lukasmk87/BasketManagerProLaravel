<?php

namespace App\Policies;

use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TrainingSessionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view training sessions');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TrainingSession $trainingSession): bool
    {
        // Check general permission
        if ($user->can('view training sessions')) {
            return true;
        }

        // Coaches can view sessions for teams they coach
        if ($user->isCoach()) {
            $coachTeamIds = $user->coachedTeams()->pluck('id')->toArray();
            return in_array($trainingSession->team_id, $coachTeamIds);
        }

        // Players can view sessions for their team
        if ($user->isPlayer() && $user->playerProfile?->team_id === $trainingSession->team_id) {
            return true;
        }

        // Parents can view sessions for their child's team
        if ($user->isParent()) {
            $childTeamIds = $user->children()
                ->with('playerProfile')
                ->get()
                ->pluck('playerProfile.team_id')
                ->filter()
                ->toArray();
            return in_array($trainingSession->team_id, $childTeamIds);
        }

        // Club members can view sessions in their clubs
        if ($user->clubs()->whereHas('teams', function ($query) use ($trainingSession) {
            $query->where('id', $trainingSession->team_id);
        })->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create training sessions') || $user->hasRole(['trainer', 'club_admin', 'admin', 'super_admin']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TrainingSession $trainingSession): bool
    {
        // Check general permission
        if ($user->can('edit training sessions')) {
            return true;
        }

        // Club admins can edit sessions in their clubs
        if ($user->hasRole('club_admin')) {
            $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();
            $sessionClubId = $trainingSession->team->club_id ?? null;
            return $sessionClubId && in_array($sessionClubId, $userClubIds);
        }

        // Trainers can edit sessions for teams they coach
        if ($user->hasRole('trainer')) {
            $coachTeamIds = $user->coachedTeams()->pluck('id')->toArray();
            return in_array($trainingSession->team_id, $coachTeamIds);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TrainingSession $trainingSession): bool
    {
        // Only users with delete permission can delete sessions
        if (!$user->can('delete training sessions')) {
            return false;
        }

        // Club admins can delete sessions in their clubs
        if ($user->hasRole('club_admin')) {
            $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();
            $sessionClubId = $trainingSession->team->club_id ?? null;
            return $sessionClubId && in_array($sessionClubId, $userClubIds);
        }

        // Trainers can delete sessions for teams they coach
        if ($user->hasRole('trainer')) {
            $coachTeamIds = $user->coachedTeams()->pluck('id')->toArray();
            return in_array($trainingSession->team_id, $coachTeamIds);
        }

        return true; // For admins and super_admins
    }

    /**
     * Determine whether the user can manage session drills.
     */
    public function manageDrills(User $user, TrainingSession $trainingSession): bool
    {
        // Must be able to update the session
        return $this->update($user, $trainingSession);
    }

    /**
     * Determine whether the user can manage attendance.
     */
    public function manageAttendance(User $user, TrainingSession $trainingSession): bool
    {
        // Must be able to update the session
        return $this->update($user, $trainingSession);
    }

    /**
     * Determine whether the user can start/complete sessions.
     */
    public function controlSession(User $user, TrainingSession $trainingSession): bool
    {
        // Check general permission
        if ($user->can('control training sessions')) {
            return true;
        }

        // Trainers can control sessions for teams they coach
        if ($user->hasRole('trainer')) {
            $coachTeamIds = $user->coachedTeams()->pluck('id')->toArray();
            return in_array($trainingSession->team_id, $coachTeamIds);
        }

        return false;
    }

    /**
     * Determine whether the user can view session statistics.
     */
    public function viewStatistics(User $user, TrainingSession $trainingSession): bool
    {
        // Check general permission
        if ($user->can('view training statistics')) {
            return true;
        }

        // Anyone who can view the session can view basic statistics
        return $this->view($user, $trainingSession);
    }

    /**
     * Determine whether the user can export session data.
     */
    public function exportData(User $user, TrainingSession $trainingSession): bool
    {
        // Must have export permission
        if (!$user->can('export statistics')) {
            return false;
        }

        // Must be able to view the session
        return $this->view($user, $trainingSession);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TrainingSession $trainingSession): bool
    {
        return $user->can('delete training sessions');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TrainingSession $trainingSession): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can view session's activity log.
     */
    public function viewActivityLog(User $user, TrainingSession $trainingSession): bool
    {
        // Must have activity log permission
        if (!$user->can('view activity logs')) {
            return false;
        }

        // Must be able to view the session
        return $this->view($user, $trainingSession);
    }
}