<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view users');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // Users can always view their own profile
        if ($user->id === $model->id) {
            return true;
        }

        // Check general permission
        if ($user->can('view users')) {
            return true;
        }

        // Club admins can view users in their clubs
        if ($user->hasRole('club_admin')) {
            $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();
            $modelClubIds = $model->clubs()->pluck('clubs.id')->toArray();
            
            return !empty(array_intersect($userClubIds, $modelClubIds));
        }

        // Coaches can view players in their teams
        if ($user->isCoach() && $model->isPlayer()) {
            $coachTeamIds = $user->coachedTeams()->pluck('id')->toArray();
            $playerTeamId = $model->playerProfile?->team_id;
            
            return $playerTeamId && in_array($playerTeamId, $coachTeamIds);
        }

        // Players can view teammates
        if ($user->isPlayer() && $model->isPlayer()) {
            return $user->playerProfile?->team_id === $model->playerProfile?->team_id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create users');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Users can always update their own profile (basic info)
        if ($user->id === $model->id) {
            return true;
        }

        // Check general permission
        if ($user->can('edit users')) {
            return true;
        }

        // Club admins can edit users in their clubs
        if ($user->hasRole('club_admin')) {
            $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();
            $modelClubIds = $model->clubs()->pluck('clubs.id')->toArray();
            
            return !empty(array_intersect($userClubIds, $modelClubIds));
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Users cannot delete themselves
        if ($user->id === $model->id) {
            return false;
        }

        // Super admins and admins with delete permission can delete
        if ($user->hasAnyRole(['super_admin', 'admin']) && $user->can('delete users')) {
            // Super admins cannot be deleted by regular admins
            if ($model->hasRole('super_admin') && !$user->hasRole('super_admin')) {
                return false;
            }
            return true;
        }

        // Club admins can delete users in their clubs
        if ($user->hasRole('club_admin')) {
            // Club admins cannot delete admins or super admins
            if ($model->hasAnyRole(['super_admin', 'admin', 'club_admin'])) {
                return false;
            }

            // Check if both users share at least one club
            $userClubIds = $user->getAdministeredClubIds();
            $modelClubIds = $model->clubs()->pluck('clubs.id')->toArray();

            return !empty(array_intersect($userClubIds, $modelClubIds));
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return $user->can('delete users');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can impersonate another user.
     */
    public function impersonate(User $user, User $model): bool
    {
        // Users cannot impersonate themselves
        if ($user->id === $model->id) {
            return false;
        }

        // Must have impersonate permission
        if (!$user->can('impersonate users')) {
            return false;
        }

        // Super admins cannot be impersonated
        if ($model->hasRole('super_admin')) {
            return false;
        }

        // Regular admins cannot impersonate other admins
        if ($model->hasRole('admin') && !$user->hasRole('super_admin')) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can manage roles for another user.
     */
    public function manageRoles(User $user, User $model): bool
    {
        // Users cannot manage their own roles
        if ($user->id === $model->id) {
            return false;
        }

        // Must have permission to manage user roles
        if (!$user->can('manage user roles')) {
            return false;
        }

        // Super admins can manage any roles
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Regular admins cannot manage super admin or other admin roles
        if ($model->hasAnyRole(['super_admin', 'admin'])) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can view sensitive information.
     */
    public function viewSensitiveInfo(User $user, User $model): bool
    {
        // Users can view their own sensitive info
        if ($user->id === $model->id) {
            return true;
        }

        // Admins can view sensitive info
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        // Club admins can view sensitive info for users in their clubs
        if ($user->hasRole('club_admin')) {
            $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();
            $modelClubIds = $model->clubs()->pluck('clubs.id')->toArray();
            
            return !empty(array_intersect($userClubIds, $modelClubIds));
        }

        return false;
    }

    /**
     * Determine whether the user can view medical information.
     */
    public function viewMedicalInfo(User $user, User $model): bool
    {
        // Users can view their own medical info
        if ($user->id === $model->id) {
            return true;
        }

        // Must have permission to view player medical info
        if (!$user->can('view player medical info')) {
            return false;
        }

        // Only for player profiles
        if (!$model->isPlayer()) {
            return false;
        }

        // Coaches can view medical info for their players
        if ($user->isCoach()) {
            $coachTeamIds = $user->coachedTeams()->pluck('id')->toArray();
            $playerTeamId = $model->playerProfile?->team_id;
            
            return $playerTeamId && in_array($playerTeamId, $coachTeamIds);
        }

        return false;
    }

    /**
     * Determine whether the user can edit medical information.
     */
    public function editMedicalInfo(User $user, User $model): bool
    {
        // Users can edit their own medical info
        if ($user->id === $model->id) {
            return true;
        }

        // Must have permission to edit player medical info
        if (!$user->can('edit player medical info')) {
            return false;
        }

        // Only for player profiles
        if (!$model->isPlayer()) {
            return false;
        }

        // Coaches can edit medical info for their players
        if ($user->isCoach()) {
            $coachTeamIds = $user->coachedTeams()->pluck('id')->toArray();
            $playerTeamId = $model->playerProfile?->team_id;
            
            return $playerTeamId && in_array($playerTeamId, $coachTeamIds);
        }

        return false;
    }

    /**
     * Determine whether the user can activate/deactivate another user.
     */
    public function changeStatus(User $user, User $model): bool
    {
        // Users cannot change their own status
        if ($user->id === $model->id) {
            return false;
        }

        // Must have edit users permission
        if (!$user->can('edit users')) {
            return false;
        }

        // Super admins cannot be deactivated by regular admins
        if ($model->hasRole('super_admin') && !$user->hasRole('super_admin')) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can view the user's activity log.
     */
    public function viewActivityLog(User $user, User $model): bool
    {
        // Users can view their own activity log
        if ($user->id === $model->id) {
            return true;
        }

        // Must have permission to view activity logs
        return $user->can('view activity logs');
    }

    /**
     * Determine whether the user can export user data (GDPR).
     */
    public function exportData(User $user, User $model): bool
    {
        // Users can export their own data
        if ($user->id === $model->id) {
            return true;
        }

        // Must have permission to export user data
        return $user->can('export user data');
    }

    /**
     * Determine whether the user can handle data deletion requests (GDPR).
     */
    public function deleteData(User $user, User $model): bool
    {
        // Users can request deletion of their own data
        if ($user->id === $model->id) {
            return true;
        }

        // Must have permission to handle data deletion requests
        return $user->can('handle data deletion requests');
    }
}