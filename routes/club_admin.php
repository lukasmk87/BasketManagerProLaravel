<?php

use App\Http\Controllers\ClubAdmin\ClubAdminDashboardController;
use App\Http\Controllers\ClubAdmin\ClubFinancialController;
use App\Http\Controllers\ClubAdmin\ClubMemberController;
use App\Http\Controllers\ClubAdmin\ClubPlayerAdminController;
use App\Http\Controllers\ClubAdmin\ClubReportsController;
use App\Http\Controllers\ClubAdmin\ClubSeasonController;
use App\Http\Controllers\ClubAdmin\ClubSettingsController;
use App\Http\Controllers\ClubAdmin\ClubSubscriptionAdminController;
use App\Http\Controllers\ClubAdmin\ClubTeamAdminController;
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
| REFACTOR-007: Split from single ClubAdminPanelController into 8
| focused controllers for better maintainability and single responsibility.
|
*/

Route::prefix('club-admin')
    ->name('club-admin.')
    ->middleware(['auth', 'verified', 'role:club_admin|tenant_admin|super_admin'])
    ->group(function () {

        // Dashboard (Single Action Controller)
        Route::get('/', ClubAdminDashboardController::class)
            ->name('dashboard');

        // Settings
        Route::get('/settings', [ClubSettingsController::class, 'index'])
            ->name('settings');
        Route::put('/settings', [ClubSettingsController::class, 'update'])
            ->name('settings.update');

        // Members Management
        Route::prefix('members')->name('members.')->group(function () {
            Route::get('/', [ClubMemberController::class, 'index'])
                ->name('index');
            Route::get('/create', [ClubMemberController::class, 'create'])
                ->name('create');
            Route::post('/', [ClubMemberController::class, 'store'])
                ->name('store');
            Route::get('/{member}/edit', [ClubMemberController::class, 'edit'])
                ->name('edit');
            Route::put('/{member}', [ClubMemberController::class, 'update'])
                ->name('update');
            Route::post('/{member}/send-password-reset', [ClubMemberController::class, 'sendPasswordReset'])
                ->name('send-password-reset');
        });

        // Teams Management
        Route::prefix('teams')->name('teams.')->group(function () {
            Route::get('/', [ClubTeamAdminController::class, 'index'])
                ->name('index');
            Route::get('/create', [ClubTeamAdminController::class, 'create'])
                ->name('create');
            Route::post('/', [ClubTeamAdminController::class, 'store'])
                ->name('store');
            Route::get('/{team}/edit', [ClubTeamAdminController::class, 'edit'])
                ->name('edit');
            Route::put('/{team}', [ClubTeamAdminController::class, 'update'])
                ->name('update');
        });

        // Seasons Management
        Route::prefix('seasons')->name('seasons.')->group(function () {
            Route::get('/', [ClubSeasonController::class, 'index'])
                ->name('index');
            Route::get('/create', [ClubSeasonController::class, 'create'])
                ->name('create');
            Route::post('/', [ClubSeasonController::class, 'store'])
                ->name('store');
            Route::get('/{season}', [ClubSeasonController::class, 'show'])
                ->name('show');
            Route::get('/{season}/edit', [ClubSeasonController::class, 'edit'])
                ->name('edit');
            Route::put('/{season}', [ClubSeasonController::class, 'update'])
                ->name('update');
            Route::delete('/{season}', [ClubSeasonController::class, 'destroy'])
                ->name('destroy');
            Route::post('/{season}/activate', [ClubSeasonController::class, 'activate'])
                ->name('activate');
            Route::post('/{season}/complete', [ClubSeasonController::class, 'complete'])
                ->name('complete');
        });

        // Players Management
        Route::prefix('players')->name('players.')->group(function () {
            Route::get('/', [ClubPlayerAdminController::class, 'index'])
                ->name('index');
            Route::get('/create', [ClubPlayerAdminController::class, 'create'])
                ->name('create');
            Route::post('/', [ClubPlayerAdminController::class, 'store'])
                ->name('store');
            Route::get('/{player}/edit', [ClubPlayerAdminController::class, 'edit'])
                ->name('edit');
            Route::put('/{player}', [ClubPlayerAdminController::class, 'update'])
                ->name('update');
        });

        // Financial Management
        Route::prefix('financial')->name('financial.')->group(function () {
            Route::get('/', [ClubFinancialController::class, 'index'])
                ->name('index');
            Route::get('/create', [ClubFinancialController::class, 'create'])
                ->name('create');
            Route::post('/', [ClubFinancialController::class, 'store'])
                ->name('store');
            Route::get('/export', [ClubFinancialController::class, 'export'])
                ->name('export');
            Route::get('/yearly-report', [ClubFinancialController::class, 'yearlyReport'])
                ->name('yearly-report');
            Route::get('/{transaction}', [ClubFinancialController::class, 'show'])
                ->name('show');
            Route::delete('/{transaction}', [ClubFinancialController::class, 'destroy'])
                ->name('destroy');
        });

        // Reports & Statistics (Single Action Controller)
        Route::get('/reports', ClubReportsController::class)
            ->name('reports');

        // Subscriptions
        Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
            Route::get('/', [ClubSubscriptionAdminController::class, 'index'])
                ->name('index');
            Route::put('/', [ClubSubscriptionAdminController::class, 'update'])
                ->name('update');
        });

        // Note: Pending Players routes are already defined in routes/player_registration.php
        // They are accessible via 'club-admin.pending-players.*' route names

        // Backward compatibility aliases for old route names (REFACTOR-007)
        // These can be removed after updating all Vue components
        Route::get('/members', [ClubMemberController::class, 'index'])
            ->name('members');
        Route::get('/teams', [ClubTeamAdminController::class, 'index'])
            ->name('teams');
        Route::get('/players', [ClubPlayerAdminController::class, 'index'])
            ->name('players');
        Route::get('/financial', [ClubFinancialController::class, 'index'])
            ->name('financial');
        Route::get('/subscriptions', [ClubSubscriptionAdminController::class, 'index'])
            ->name('subscriptions');
    });
