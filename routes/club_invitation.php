<?php

use App\Http\Controllers\ClubInvitationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Club Invitation Routes
|--------------------------------------------------------------------------
|
| Routes for club member registration via QR code/link system.
| Three main sections:
| 1. Club Admin routes (create/manage invitations)
| 2. Public routes (member registration form)
|
*/

// ============================================
// CLUB ADMIN ROUTES (Auth + Permission Required)
// ============================================

Route::prefix('club-admin/invitations')
    ->middleware(['auth', 'verified', 'role:super_admin|admin|club_admin'])
    ->name('club-admin.invitations.')
    ->group(function () {
        // List all invitations
        Route::get('/', [ClubInvitationController::class, 'index'])
            ->name('index');

        // Create new invitation form
        Route::get('/create', [ClubInvitationController::class, 'create'])
            ->name('create');

        // Store new invitation
        Route::post('/', [ClubInvitationController::class, 'store'])
            ->name('store');

        // Show invitation details + statistics
        Route::get('/{invitation}', [ClubInvitationController::class, 'show'])
            ->name('show');

        // Deactivate invitation
        Route::delete('/{invitation}', [ClubInvitationController::class, 'destroy'])
            ->name('destroy');

        // Download QR code in different formats
        Route::get('/{invitation}/qr/{format}', [ClubInvitationController::class, 'downloadQR'])
            ->name('download-qr')
            ->where('format', 'png|svg|pdf');
    });

// ============================================
// PUBLIC ROUTES (No Auth Required)
// ============================================

Route::prefix('register/club')
    ->middleware(['throttle:club-registration'])
    ->name('public.club.')
    ->group(function () {
        // Show registration form
        Route::get('/{token}', [ClubInvitationController::class, 'showRegistrationForm'])
            ->name('register')
            ->where('token', '[a-zA-Z0-9]{32}');

        // Submit registration
        Route::post('/{token}', [ClubInvitationController::class, 'submitRegistration'])
            ->name('submit')
            ->where('token', '[a-zA-Z0-9]{32}');

        // Success page
        Route::get('/{token}/success', [ClubInvitationController::class, 'success'])
            ->name('success')
            ->where('token', '[a-zA-Z0-9]{32}');
    });

/*
|--------------------------------------------------------------------------
| Rate Limiting Configuration
|--------------------------------------------------------------------------
|
| Custom rate limiters for club registration to prevent abuse.
| Configured in FortifyServiceProvider.
|
| Usage: middleware(['throttle:club-registration'])
|
| The rate limiter 'club-registration' is defined in:
| app/Providers/FortifyServiceProvider.php
|
*/
