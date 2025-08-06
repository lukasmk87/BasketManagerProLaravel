<?php

use App\Models\Game;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Game-specific channels for live scoring
Broadcast::channel('game.{gameId}', function (User $user, int $gameId) {
    $game = Game::find($gameId);
    
    // Allow if game is public or user has permission
    return $game && ($game->is_public || $user->can('view', $game));
});

// Team-specific channels
Broadcast::channel('team.{teamId}', function (User $user, int $teamId) {
    // Check if user is associated with this team
    return $user->teams()->where('teams.id', $teamId)->exists() ||
           $user->can('view teams');
});

// Live games channel for public viewing
Broadcast::channel('live-games', function (User $user) {
    // Anyone can listen to live games updates
    return true;
});

// Club-specific channels
Broadcast::channel('club.{clubId}', function (User $user, int $clubId) {
    // Check if user is member of this club
    return $user->clubs()->where('clubs.id', $clubId)->exists() ||
           $user->can('view clubs');
});

// Admin channels for system-wide notifications
Broadcast::channel('admin-notifications', function (User $user) {
    return $user->can('admin dashboard');
});

// Scorer-specific channels for live scoring interface
Broadcast::channel('scorer.game.{gameId}', function (User $user, int $gameId) {
    $game = Game::find($gameId);
    
    return $game && $user->can('score', $game);
});