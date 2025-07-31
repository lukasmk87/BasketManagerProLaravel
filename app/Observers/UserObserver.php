<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // Log user creation
        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->log('User created');
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // Additional logic can be added here
        // Activity logging is already handled by LogsActivity trait
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        // Handle user deletion cleanup
        activity()
            ->performedOn($user)
            ->log('User deleted');
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        activity()
            ->performedOn($user)
            ->log('User restored');
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        // Handle permanent deletion
        activity()
            ->log('User permanently deleted: ' . $user->name);
    }
}