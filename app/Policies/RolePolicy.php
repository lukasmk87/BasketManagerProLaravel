<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\AuthorizesUsers;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    use AuthorizesUsers;
    /**
     * Determine whether the user can view any roles.
     */
    public function viewAny(User $user): bool
    {
        // Only super admins can manage roles
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can view the role.
     */
    public function view(User $user, Role $role): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can create roles.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can update the role.
     */
    public function update(User $user, Role $role): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can delete the role.
     */
    public function delete(User $user, Role $role): bool
    {
        // Super admins can delete roles
        if (!$user->hasRole('super_admin')) {
            return false;
        }

        // Prevent deletion of system roles
        $systemRoles = ['super_admin', 'admin', 'club_admin'];
        if (in_array($role->name, $systemRoles)) {
            return false;
        }

        return true;
    }
}
