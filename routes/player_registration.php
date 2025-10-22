<?php

use App\Http\Controllers\PlayerRegistrationController;
use App\Http\Controllers\PendingPlayersController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Player Registration Routes
|--------------------------------------------------------------------------
|
| Routes for player self-registration via QR code/link system.
| Three main sections:
| 1. Trainer routes (create/manage invitations)
| 2. Public routes (player registration form)
| 3. Club Admin routes (assign pending players to teams)
|
*/

// ============================================
// TRAINER ROUTES (Auth + Permission Required)
// ============================================

Route::prefix('trainer/player-invitations')
    ->middleware(['auth', 'verified', 'role:trainer|club_admin'])
    ->name('trainer.invitations.')
    ->group(function () {
        // List all invitations
        Route::get('/', [PlayerRegistrationController::class, 'index'])
            ->name('index');

        // Create new invitation form
        Route::get('/create', [PlayerRegistrationController::class, 'create'])
            ->name('create');

        // Store new invitation
        Route::post('/', [PlayerRegistrationController::class, 'store'])
            ->name('store');

        // Show invitation details + statistics
        Route::get('/{invitation}', [PlayerRegistrationController::class, 'show'])
            ->name('show');

        // Deactivate invitation
        Route::delete('/{invitation}', [PlayerRegistrationController::class, 'destroy'])
            ->name('destroy');

        // Download QR code in different formats
        Route::get('/{invitation}/qr/{format}', [PlayerRegistrationController::class, 'downloadQR'])
            ->name('download-qr')
            ->where('format', 'png|svg|pdf');
    });

// ============================================
// PUBLIC ROUTES (No Auth Required)
// ============================================

Route::prefix('register/player')
    ->middleware(['throttle:player-registration'])
    ->name('public.player.')
    ->group(function () {
        // Show registration form
        Route::get('/{token}', [PlayerRegistrationController::class, 'showRegistrationForm'])
            ->name('register')
            ->where('token', '[a-zA-Z0-9]{32}');

        // Submit registration
        Route::post('/{token}', [PlayerRegistrationController::class, 'submitRegistration'])
            ->name('submit')
            ->where('token', '[a-zA-Z0-9]{32}');

        // Success page
        Route::get('/{token}/success', [PlayerRegistrationController::class, 'success'])
            ->name('success')
            ->where('token', '[a-zA-Z0-9]{32}');
    });

// ============================================
// CLUB ADMIN ROUTES (Auth + Role Required)
// ============================================

Route::prefix('club-admin/pending-players')
    ->middleware(['auth', 'verified', 'role:club_admin'])
    ->name('club-admin.pending.')
    ->group(function () {
        // List all pending players
        Route::get('/', [PendingPlayersController::class, 'index'])
            ->name('index');

        // Assign single player to team
        Route::post('/assign', [PendingPlayersController::class, 'assign'])
            ->name('assign');

        // Bulk assign multiple players
        Route::post('/bulk-assign', [PendingPlayersController::class, 'bulkAssign'])
            ->name('bulk-assign');

        // Reject player registration
        Route::delete('/{player}', [PendingPlayersController::class, 'reject'])
            ->name('reject');
    });

/*
|--------------------------------------------------------------------------
| Rate Limiting Configuration
|--------------------------------------------------------------------------
|
| Custom rate limiters for player registration to prevent abuse.
| Configured in FortifyServiceProvider.
|
| Usage: middleware(['throttle:player-registration'])
| NOT:   middleware(['throttle:player-registration,5,1']) - Laravel 12+ syntax!
|
| The rate limiter 'player-registration' is defined in:
| app/Providers/FortifyServiceProvider.php
|
*/
