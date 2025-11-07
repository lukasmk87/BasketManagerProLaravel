<?php

namespace App\Policies;

use App\Models\GymHall;
use App\Models\User;
use App\Policies\Concerns\AuthorizesUsers;
use Illuminate\Auth\Access\Response;

class GymHallPolicy
{
    use AuthorizesUsers;
    /**
     * Determine whether the user can view any gym halls.
     */
    public function viewAny(User $user, $clubId = null): bool
    {
        // Super admins and admins can view all gym halls
        if ($user->hasAnyRole(['admin', 'super_admin'])) {
            return true;
        }

        // If club ID is provided, check if user belongs to that club
        if ($clubId) {
            return $user->clubs()->where('clubs.id', $clubId)->exists();
        }

        // Users can view gym halls for clubs they belong to
        return $user->clubs()->exists();
    }

    /**
     * Determine whether the user can view the gym hall.
     */
    public function view(User $user, GymHall $gymHall): bool
    {
        // Admin users can view all gym halls
        if ($user->hasAnyRole(['admin', 'super_admin'])) {
            return true;
        }

        // Users can view gym halls in their club
        return $user->clubs()->where('clubs.id', $gymHall->club_id)->exists();
    }

    /**
     * Determine whether the user can create gym halls.
     */
    public function create(User $user, $clubId = null): bool
    {
        // Super admins and admins can create gym halls for any club
        if ($user->hasAnyRole(['admin', 'super_admin'])) {
            return true;
        }

        if (!$clubId) {
            return false;
        }

        // Club admins can only create gym halls for clubs they administer
        $administeredClubIds = $user->getAdministeredClubIds();
        return in_array($clubId, $administeredClubIds);
    }

    /**
     * Determine whether the user can update the gym hall.
     */
    public function update(User $user, GymHall $gymHall): bool
    {
        // Super admins and admins can update any gym hall
        if ($user->hasAnyRole(['admin', 'super_admin'])) {
            return true;
        }

        // Club admins can only update gym halls for clubs they administer
        $administeredClubIds = $user->getAdministeredClubIds();
        return in_array($gymHall->club_id, $administeredClubIds);
    }

    /**
     * Determine whether the user can delete the gym hall.
     */
    public function delete(User $user, GymHall $gymHall): bool
    {
        // Super admins and admins can delete any gym hall
        if ($user->hasAnyRole(['admin', 'super_admin'])) {
            return true;
        }

        // Club admins can only delete gym halls for clubs they administer
        $administeredClubIds = $user->getAdministeredClubIds();
        return in_array($gymHall->club_id, $administeredClubIds);
    }

    /**
     * Determine whether the user can restore the gym hall.
     */
    public function restore(User $user, GymHall $gymHall): bool
    {
        return $this->delete($user, $gymHall);
    }

    /**
     * Determine whether the user can permanently delete the gym hall.
     */
    public function forceDelete(User $user, GymHall $gymHall): bool
    {
        return $this->delete($user, $gymHall);
    }

    /**
     * Determine whether the user can manage time slots for the gym hall.
     */
    public function manageTimeSlots(User $user, GymHall $gymHall): bool
    {
        // Super admins and admins can manage time slots for any gym hall
        if ($user->hasAnyRole(['admin', 'super_admin'])) {
            return true;
        }

        // Club admins can manage time slots for clubs they administer
        if ($user->hasRole('club_admin')) {
            $administeredClubIds = $user->getAdministeredClubIds();
            return in_array($gymHall->club_id, $administeredClubIds);
        }

        // Trainers can manage time slots for their club
        if ($user->hasRole('trainer')) {
            return $user->clubs()
                ->where('clubs.id', $gymHall->club_id)
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can view bookings for the gym hall.
     */
    public function viewBookings(User $user, GymHall $gymHall): bool
    {
        // All club members can view bookings
        return $user->clubs()->where('clubs.id', $gymHall->club_id)->exists();
    }

    /**
     * Determine whether the user can view statistics for the gym hall.
     */
    public function viewStatistics(User $user, GymHall $gymHall): bool
    {
        // Super admins and admins can view statistics for any gym hall
        if ($user->hasAnyRole(['admin', 'super_admin'])) {
            return true;
        }

        // Club admins can view statistics for clubs they administer
        if ($user->hasRole('club_admin')) {
            $administeredClubIds = $user->getAdministeredClubIds();
            return in_array($gymHall->club_id, $administeredClubIds);
        }

        // Trainers can view statistics for their club
        if ($user->hasRole('trainer')) {
            return $user->clubs()
                ->where('clubs.id', $gymHall->club_id)
                ->exists();
        }

        return false;
    }
}