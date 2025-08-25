<?php

namespace App\Policies;

use App\Models\Drill;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DrillPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('manage training drills') || $user->hasRole(['trainer', 'club_admin', 'admin', 'super_admin']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Drill $drill): bool
    {
        // Check general permission
        if ($user->can('manage training drills')) {
            return true;
        }

        // Public drills can be viewed by authenticated users
        if ($drill->is_public) {
            return true;
        }

        // Creator can always view their own drills
        if ($drill->created_by_user_id === $user->id) {
            return true;
        }

        // Trainers, club admins and admins can view all drills
        if ($user->hasRole(['trainer', 'club_admin', 'admin', 'super_admin'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('manage training drills') || $user->hasRole(['trainer', 'club_admin', 'admin', 'super_admin']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Drill $drill): bool
    {
        // Check general permission
        if ($user->can('manage training drills')) {
            return true;
        }

        // Creator can edit their own drills (unless it's archived or approved)
        if ($drill->created_by_user_id === $user->id && !in_array($drill->status, ['archived', 'approved'])) {
            return true;
        }

        // Admins can edit all drills
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Club admins can edit drills created by users from their clubs
        if ($user->hasRole('club_admin')) {
            $drillCreator = User::find($drill->created_by_user_id);
            if ($drillCreator) {
                $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();
                $creatorClubIds = $drillCreator->clubs()->pluck('clubs.id')->toArray();
                return !empty(array_intersect($userClubIds, $creatorClubIds));
            }
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Drill $drill): bool
    {
        // Check general permission
        if ($user->can('manage training drills')) {
            return true;
        }

        // Creator can delete their own drills (unless it's archived or approved)
        if ($drill->created_by_user_id === $user->id && !in_array($drill->status, ['archived', 'approved'])) {
            return true;
        }

        // Admins can delete all drills
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Club admins can delete drills created by users from their clubs
        if ($user->hasRole('club_admin')) {
            $drillCreator = User::find($drill->created_by_user_id);
            if ($drillCreator) {
                $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();
                $creatorClubIds = $drillCreator->clubs()->pluck('clubs.id')->toArray();
                return !empty(array_intersect($userClubIds, $creatorClubIds));
            }
        }

        return false;
    }

    /**
     * Determine whether the user can review drills.
     */
    public function review(User $user, Drill $drill): bool
    {
        // Only users with review permission can review drills
        if (!$user->can('manage training drills')) {
            return false;
        }

        // Admins and super admins can review all drills
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Club admins can review drills from their clubs
        if ($user->hasRole('club_admin')) {
            $drillCreator = User::find($drill->created_by_user_id);
            if ($drillCreator) {
                $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();
                $creatorClubIds = $drillCreator->clubs()->pluck('clubs.id')->toArray();
                return !empty(array_intersect($userClubIds, $creatorClubIds));
            }
        }

        return false;
    }

    /**
     * Determine whether the user can rate the drill.
     */
    public function rate(User $user, Drill $drill): bool
    {
        // Can't rate your own drill
        if ($drill->created_by_user_id === $user->id) {
            return false;
        }

        // Must be able to view the drill
        if (!$this->view($user, $drill)) {
            return false;
        }

        // Trainers and coaches can rate drills
        return $user->hasRole(['trainer', 'club_admin', 'admin', 'super_admin']);
    }

    /**
     * Determine whether the user can favorite the drill.
     */
    public function favorite(User $user, Drill $drill): bool
    {
        // Must be able to view the drill
        if (!$this->view($user, $drill)) {
            return false;
        }

        // Trainers and coaches can favorite drills
        return $user->hasRole(['trainer', 'club_admin', 'admin', 'super_admin']);
    }

    /**
     * Determine whether the user can use the drill in training sessions.
     */
    public function useInTraining(User $user, Drill $drill): bool
    {
        // Must be able to view the drill
        if (!$this->view($user, $drill)) {
            return false;
        }

        // Must be approved or created by user for use in training
        if ($drill->status !== 'approved' && $drill->created_by_user_id !== $user->id) {
            return false;
        }

        // Trainers can use drills in training sessions
        return $user->hasRole(['trainer', 'club_admin', 'admin', 'super_admin']);
    }

    /**
     * Determine whether the user can export drill data.
     */
    public function export(User $user, Drill $drill): bool
    {
        // Must have export permission
        if (!$user->can('export statistics')) {
            return false;
        }

        // Must be able to view the drill
        return $this->view($user, $drill);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Drill $drill): bool
    {
        return $user->can('manage training drills');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Drill $drill): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can view the drill's activity log.
     */
    public function viewActivityLog(User $user, Drill $drill): bool
    {
        // Must have activity log permission
        if (!$user->can('view activity logs')) {
            return false;
        }

        // Must be able to view the drill
        return $this->view($user, $drill);
    }

    /**
     * Determine whether the user can duplicate the drill.
     */
    public function duplicate(User $user, Drill $drill): bool
    {
        // Must be able to view the drill
        if (!$this->view($user, $drill)) {
            return false;
        }

        // Must be able to create drills
        return $this->create($user);
    }
}