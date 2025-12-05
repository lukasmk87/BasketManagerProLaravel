<?php

namespace App\Policies;

use App\Models\Play;
use App\Models\User;
use App\Policies\Concerns\AuthorizesUsers;

class PlayPolicy
{
    use AuthorizesUsers;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view tactic board') ||
            $user->hasAnyRole(['trainer', 'assistant_coach', 'club_admin', 'tenant_admin', 'super_admin']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Play $play): bool
    {
        // Check general permission
        if ($user->can('manage tactic board')) {
            return true;
        }

        // Public and published plays can be viewed by authenticated users
        if ($play->is_public && $play->status === 'published') {
            return true;
        }

        // Creator can always view their own plays
        if ($play->created_by_user_id === $user->id) {
            return true;
        }

        // Trainers, club admins and admins can view all plays
        if ($user->hasAnyRole(['trainer', 'assistant_coach', 'club_admin', 'tenant_admin', 'super_admin'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Check if user has the specific permission
        if ($user->can('create plays')) {
            return true;
        }

        // Check if user has one of the required roles
        if ($user->hasAnyRole(['trainer', 'assistant_coach', 'club_admin', 'tenant_admin', 'super_admin'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Play $play): bool
    {
        // Check general permission
        if ($user->can('manage tactic board')) {
            return true;
        }

        // Creator can edit their own plays (unless it's archived)
        if ($play->created_by_user_id === $user->id && $play->status !== 'archived') {
            return true;
        }

        // Admins can edit all plays
        if ($user->hasAnyRole(['tenant_admin', 'super_admin'])) {
            return true;
        }

        // Club admins can edit plays created by users from their clubs
        if ($user->hasRole('club_admin')) {
            $playCreator = User::find($play->created_by_user_id);
            if ($playCreator) {
                $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();
                $creatorClubIds = $playCreator->clubs()->pluck('clubs.id')->toArray();
                return !empty(array_intersect($userClubIds, $creatorClubIds));
            }
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Play $play): bool
    {
        // Check general permission
        if ($user->can('manage tactic board')) {
            return true;
        }

        // Creator can delete their own plays (unless it's archived)
        if ($play->created_by_user_id === $user->id && $play->status !== 'archived') {
            return true;
        }

        // Admins can delete all plays
        if ($user->hasAnyRole(['tenant_admin', 'super_admin'])) {
            return true;
        }

        // Club admins can delete plays created by users from their clubs
        if ($user->hasRole('club_admin')) {
            $playCreator = User::find($play->created_by_user_id);
            if ($playCreator) {
                $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();
                $creatorClubIds = $playCreator->clubs()->pluck('clubs.id')->toArray();
                return !empty(array_intersect($userClubIds, $creatorClubIds));
            }
        }

        return false;
    }

    /**
     * Determine whether the user can duplicate the play.
     */
    public function duplicate(User $user, Play $play): bool
    {
        // Must be able to view the play
        if (!$this->view($user, $play)) {
            return false;
        }

        // Must be able to create plays
        return $this->create($user);
    }

    /**
     * Determine whether the user can export the play.
     */
    public function export(User $user, Play $play): bool
    {
        // Check export permission
        if ($user->can('export plays')) {
            return $this->view($user, $play);
        }

        // Trainers and above can export
        if ($user->hasAnyRole(['trainer', 'assistant_coach', 'club_admin', 'tenant_admin', 'super_admin'])) {
            return $this->view($user, $play);
        }

        return false;
    }

    /**
     * Determine whether the user can attach plays to drills or training sessions.
     */
    public function attach(User $user, Play $play): bool
    {
        // Must be able to view the play
        if (!$this->view($user, $play)) {
            return false;
        }

        // Must be published or owned by user
        if ($play->status !== 'published' && $play->created_by_user_id !== $user->id) {
            return false;
        }

        // Trainers can attach plays
        return $user->hasAnyRole(['trainer', 'assistant_coach', 'club_admin', 'tenant_admin', 'super_admin']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Play $play): bool
    {
        return $user->can('manage tactic board') || $user->hasAnyRole(['tenant_admin', 'super_admin']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Play $play): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can manage system templates.
     */
    public function manageTemplates(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can feature a play.
     */
    public function feature(User $user, Play $play): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can favorite a play.
     */
    public function favorite(User $user, Play $play): bool
    {
        // Can favorite any play they can view
        return $this->view($user, $play);
    }
}
