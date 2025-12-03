<?php

namespace App\Policies;

use App\Models\Playbook;
use App\Models\User;
use App\Policies\Concerns\AuthorizesUsers;

class PlaybookPolicy
{
    use AuthorizesUsers;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view tactic board') ||
            $user->hasRole(['trainer', 'assistant_coach', 'club_admin', 'admin', 'super_admin']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Playbook $playbook): bool
    {
        // Check general permission
        if ($user->can('manage tactic board')) {
            return true;
        }

        // Creator can always view their own playbooks
        if ($playbook->created_by_user_id === $user->id) {
            return true;
        }

        // Team members can view their team's playbooks
        if ($playbook->team_id) {
            $userTeamIds = $user->teams()->pluck('basketball_teams.id')->toArray();
            if (in_array($playbook->team_id, $userTeamIds)) {
                return true;
            }
        }

        // Trainers, club admins and admins can view all playbooks
        if ($user->hasRole(['trainer', 'assistant_coach', 'club_admin', 'admin', 'super_admin'])) {
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
        if ($user->can('manage playbooks')) {
            return true;
        }

        // Check if user has one of the required roles
        if ($user->hasRole(['trainer', 'club_admin', 'admin', 'super_admin'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Playbook $playbook): bool
    {
        // Check general permission
        if ($user->can('manage tactic board')) {
            return true;
        }

        // Creator can edit their own playbooks
        if ($playbook->created_by_user_id === $user->id) {
            return true;
        }

        // Admins can edit all playbooks
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Club admins can edit playbooks created by users from their clubs
        if ($user->hasRole('club_admin')) {
            $playbookCreator = User::find($playbook->created_by_user_id);
            if ($playbookCreator) {
                $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();
                $creatorClubIds = $playbookCreator->clubs()->pluck('clubs.id')->toArray();
                return !empty(array_intersect($userClubIds, $creatorClubIds));
            }
        }

        // Trainers can edit their team's playbooks
        if ($user->hasRole('trainer') && $playbook->team_id) {
            $userTeamIds = $user->teams()->where('role', 'trainer')->pluck('basketball_teams.id')->toArray();
            return in_array($playbook->team_id, $userTeamIds);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Playbook $playbook): bool
    {
        // Check general permission
        if ($user->can('manage tactic board')) {
            return true;
        }

        // Creator can delete their own playbooks (if not default)
        if ($playbook->created_by_user_id === $user->id && !$playbook->is_default) {
            return true;
        }

        // Admins can delete all playbooks
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Club admins can delete playbooks created by users from their clubs
        if ($user->hasRole('club_admin')) {
            $playbookCreator = User::find($playbook->created_by_user_id);
            if ($playbookCreator) {
                $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();
                $creatorClubIds = $playbookCreator->clubs()->pluck('clubs.id')->toArray();
                return !empty(array_intersect($userClubIds, $creatorClubIds));
            }
        }

        return false;
    }

    /**
     * Determine whether the user can manage plays within the playbook.
     */
    public function managePlays(User $user, Playbook $playbook): bool
    {
        return $this->update($user, $playbook);
    }

    /**
     * Determine whether the user can duplicate the playbook.
     */
    public function duplicate(User $user, Playbook $playbook): bool
    {
        // Must be able to view the playbook
        if (!$this->view($user, $playbook)) {
            return false;
        }

        // Must be able to create playbooks
        return $this->create($user);
    }

    /**
     * Determine whether the user can export the playbook.
     */
    public function export(User $user, Playbook $playbook): bool
    {
        // Check export permission
        if ($user->can('export plays')) {
            return $this->view($user, $playbook);
        }

        // Trainers and above can export
        if ($user->hasRole(['trainer', 'assistant_coach', 'club_admin', 'admin', 'super_admin'])) {
            return $this->view($user, $playbook);
        }

        return false;
    }

    /**
     * Determine whether the user can attach playbook to games.
     */
    public function attachToGame(User $user, Playbook $playbook): bool
    {
        // Must be able to view the playbook
        if (!$this->view($user, $playbook)) {
            return false;
        }

        // Trainers and above can attach playbooks to games
        return $user->hasRole(['trainer', 'club_admin', 'admin', 'super_admin']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Playbook $playbook): bool
    {
        return $user->can('manage tactic board') || $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Playbook $playbook): bool
    {
        return $user->hasRole('super_admin');
    }
}
