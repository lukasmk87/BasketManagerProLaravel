<?php

namespace App\Policies;

use App\Models\ClubSubscriptionPlan;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ClubSubscriptionPlanPolicy
{
    /**
     * Determine whether the user can view any club subscription plans.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view club subscription plans');
    }

    /**
     * Determine whether the user can view the club subscription plan.
     */
    public function view(User $user, ClubSubscriptionPlan $plan): bool
    {
        // Check general permission
        if ($user->can('view club subscription plans')) {
            // Super admin and admin can view all plans
            if ($user->hasAnyRole(['super_admin', 'admin'])) {
                return true;
            }

            // Club admins can only view plans from their tenant
            if ($user->hasRole('club_admin')) {
                return $user->tenant_id === $plan->tenant_id;
            }

            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create club subscription plans.
     */
    public function create(User $user): bool
    {
        return $user->can('create club subscription plans');
    }

    /**
     * Determine whether the user can update the club subscription plan.
     */
    public function update(User $user, ClubSubscriptionPlan $plan): bool
    {
        // Check general permission
        if (!$user->can('update club subscription plans')) {
            return false;
        }

        // Super admin and admin can update all plans
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        // Club admins can only update plans from their tenant
        if ($user->hasRole('club_admin')) {
            return $user->tenant_id === $plan->tenant_id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the club subscription plan.
     */
    public function delete(User $user, ClubSubscriptionPlan $plan): bool
    {
        // Check general permission
        if (!$user->can('delete club subscription plans')) {
            return false;
        }

        // Cannot delete plan if it has clubs assigned
        if ($plan->clubs()->exists()) {
            return false;
        }

        // Super admin and admin can delete all plans
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        // Club admins can only delete plans from their tenant
        if ($user->hasRole('club_admin')) {
            return $user->tenant_id === $plan->tenant_id;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the club subscription plan.
     */
    public function restore(User $user, ClubSubscriptionPlan $plan): bool
    {
        return $user->can('delete club subscription plans')
            && $user->hasAnyRole(['super_admin', 'admin']);
    }

    /**
     * Determine whether the user can permanently delete the club subscription plan.
     */
    public function forceDelete(User $user, ClubSubscriptionPlan $plan): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can assign the plan to clubs.
     */
    public function assignToClub(User $user, ClubSubscriptionPlan $plan): bool
    {
        // Must be able to update plans
        if (!$this->update($user, $plan)) {
            return false;
        }

        // Must have permission to update clubs
        return $user->can('edit clubs');
    }

    /**
     * Determine whether the user can view plan usage statistics.
     */
    public function viewStatistics(User $user, ClubSubscriptionPlan $plan): bool
    {
        // Can view if can view the plan
        if (!$this->view($user, $plan)) {
            return false;
        }

        // Must have statistics permission
        return $user->can('view statistics');
    }

    /**
     * Determine whether the user can manage plan features/limits.
     */
    public function manageFeatures(User $user, ClubSubscriptionPlan $plan): bool
    {
        // Must be able to update the plan
        if (!$this->update($user, $plan)) {
            return false;
        }

        // Only admins and super_admins can manage features
        // Club admins have limited control
        return $user->hasAnyRole(['super_admin', 'admin', 'club_admin']);
    }
}
