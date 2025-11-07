<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait AuthorizesUsers
{
    /**
     * Perform pre-authorization checks.
     *
     * This method is called before all other policy methods.
     * Super Admins are automatically authorized for all actions.
     *
     * @param  \App\Models\User  $user
     * @param  string  $ability
     * @return bool|null
     */
    public function before(User $user, string $ability): ?bool
    {
        // Super Admins bypass all authorization checks
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Return null to continue with normal authorization flow
        return null;
    }
}
