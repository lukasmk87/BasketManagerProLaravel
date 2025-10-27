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

        // Teams Management
        Route::get('/teams', [ClubAdminPanelController::class, 'teams'])
            ->name('teams');

        // Players Management
        Route::get('/players', [ClubAdminPanelController::class, 'players'])
            ->name('players');

        // Financial Management
        Route::get('/financial', [ClubAdminPanelController::class, 'financial'])
            ->name('financial');

        // Reports & Statistics
        Route::get('/reports', [ClubAdminPanelController::class, 'reports'])
            ->name('reports');

        // Subscriptions
        Route::get('/subscriptions', [ClubAdminPanelController::class, 'subscriptions'])
            ->name('subscriptions');

        // Note: Pending Players routes are already defined in routes/player_registration.php
        // They are accessible via 'club-admin.pending-players.*' route names
    });
