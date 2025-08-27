<?php

namespace App\Policies;

use App\Models\GymHall;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class GymHallPolicy
{
    /**
     * Determine whether the user can view any gym halls.
     */
    public function viewAny(User $user, $clubId = null): bool
    {
        // Global admins and club admins can view gym halls
        if ($user->hasAnyRole(['admin', 'super_admin', 'club_admin'])) {
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
        // Global admins and club admins can create gym halls
        if ($user->hasAnyRole(['admin', 'super_admin', 'club_admin'])) {
            return true;
        }

        if (!$clubId) {
            return false;
        }

        // Only club admins and owners can create gym halls
        return $user->clubs()
            ->where('clubs.id', $clubId)
            ->wherePivotIn('role', ['admin', 'owner'])
            ->exists();
    }

    /**
     * Determine whether the user can update the gym hall.
     */
    public function update(User $user, GymHall $gymHall): bool
    {
        // Global admins and club admins can update gym halls
        if ($user->hasAnyRole(['admin', 'super_admin', 'club_admin'])) {
            return true;
        }

        // Only club admins and owners can update gym halls
        return $user->clubs()
            ->where('clubs.id', $gymHall->club_id)
            ->wherePivotIn('role', ['admin', 'owner'])
            ->exists();
    }

    /**
     * Determine whether the user can delete the gym hall.
     */
    public function delete(User $user, GymHall $gymHall): bool
    {
        // Global admins and club admins can delete gym halls
        if ($user->hasAnyRole(['admin', 'super_admin', 'club_admin'])) {
            return true;
        }

        // Only club admins and owners can delete gym halls
        return $user->clubs()
            ->where('clubs.id', $gymHall->club_id)
            ->wherePivotIn('role', ['admin', 'owner'])
            ->exists();
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
        // Global admins, club admins, and trainers can manage time slots
        if ($user->hasAnyRole(['admin', 'super_admin', 'club_admin', 'trainer'])) {
            return true;
        }

        // Club admins, owners, and trainers can manage time slots
        return $user->clubs()
            ->where('clubs.id', $gymHall->club_id)
            ->wherePivotIn('role', ['admin', 'owner', 'trainer'])
            ->exists();
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
        // Global admins, club admins, and trainers can view statistics
        if ($user->hasAnyRole(['admin', 'super_admin', 'club_admin', 'trainer'])) {
            return true;
        }

        // Club admins, owners, and trainers can view statistics
        return $user->clubs()
            ->where('clubs.id', $gymHall->club_id)
            ->wherePivotIn('role', ['admin', 'owner', 'trainer'])
            ->exists();
    }
}