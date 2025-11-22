<?php

use App\Http\Controllers\ClubAdminPanelController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Club Admin Routes
|--------------------------------------------------------------------------
|
| Routes for club administrators to manage their clubs, teams, players,
| and club-related resources. All routes require authentication and
| club_admin role.
|
*/

Route::prefix('club-admin')
    ->name('club-admin.')
    ->middleware(['auth', 'verified', 'role:club_admin|admin|super_admin'])
    ->group(function () {

        // Dashboard
        Route::get('/', [ClubAdminPanelController::class, 'dashboard'])
            ->name('dashboard');

        // Settings
        Route::get('/settings', [ClubAdminPanelController::class, 'settings'])
            ->name('settings');
        Route::put('/settings', [ClubAdminPanelController::class, 'updateSettings'])
            ->name('settings.update');

        // Members Management
        Route::get('/members', [ClubAdminPanelController::class, 'members'])
            ->name('members');
        Route::get('/members/create', [ClubAdminPanelController::class, 'createMember'])
            ->name('members.create');
        Route::post('/members', [ClubAdminPanelController::class, 'storeMember'])
            ->name('members.store');
        Route::get('/members/{user}/edit', [ClubAdminPanelController::class, 'editMember'])
            ->name('members.edit');
        Route::put('/members/{user}', [ClubAdminPanelController::class, 'updateMember'])
            ->name('members.update');
        Route::post('/members/{user}/send-password-reset', [ClubAdminPanelController::class, 'sendPasswordReset'])
            ->name('members.send-password-reset');

        // Teams Management
        Route::get('/teams', [ClubAdminPanelController::class, 'teams'])
            ->name('teams');
        Route::get('/teams/create', [ClubAdminPanelController::class, 'createTeam'])
            ->name('teams.create');
        Route::post('/teams', [ClubAdminPanelController::class, 'storeTeam'])
            ->name('teams.store');
        Route::get('/teams/{team}/edit', [ClubAdminPanelController::class, 'editTeam'])
            ->name('teams.edit');
        Route::put('/teams/{team}', [ClubAdminPanelController::class, 'updateTeam'])
            ->name('teams.update');

        // Players Management
        Route::get('/players', [ClubAdminPanelController::class, 'players'])
            ->name('players');
        Route::get('/players/create', [ClubAdminPanelController::class, 'createPlayer'])
            ->name('players.create');
        Route::post('/players', [ClubAdminPanelController::class, 'storePlayer'])
            ->name('players.store');
        Route::get('/players/{player}/edit', [ClubAdminPanelController::class, 'editPlayer'])
            ->name('players.edit');
        Route::put('/players/{player}', [ClubAdminPanelController::class, 'updatePlayer'])
            ->name('players.update');

        // Financial Management
        Route::get('/financial', [ClubAdminPanelController::class, 'financial'])
            ->name('financial');

        // Reports & Statistics
        Route::get('/reports', [ClubAdminPanelController::class, 'reports'])
            ->name('reports');

        // Subscriptions
        Route::get('/subscriptions', [ClubAdminPanelController::class, 'subscriptions'])
            ->name('subscriptions');
        Route::put('/subscriptions', [ClubAdminPanelController::class, 'updateSubscription'])
            ->name('subscriptions.update');

        // Note: Pending Players routes are already defined in routes/player_registration.php
        // They are accessible via 'club-admin.pending-players.*' route names
    });
