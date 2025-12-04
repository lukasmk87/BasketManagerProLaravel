<?php

namespace App\Policies;

use App\Models\PlayFavorite;
use App\Models\User;

class PlayFavoritePolicy
{
    /**
     * Determine whether the user can view the favorite.
     */
    public function view(User $user, PlayFavorite $favorite): bool
    {
        return $favorite->user_id === $user->id;
    }

    /**
     * Determine whether the user can update the favorite.
     */
    public function update(User $user, PlayFavorite $favorite): bool
    {
        return $favorite->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the favorite.
     */
    public function delete(User $user, PlayFavorite $favorite): bool
    {
        return $favorite->user_id === $user->id;
    }
}
