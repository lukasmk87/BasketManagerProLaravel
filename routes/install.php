<?php

use App\Http\Controllers\InstallController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Installation Routes
|--------------------------------------------------------------------------
|
| These routes handle the web-based installation wizard for BasketManager Pro.
| They are protected by PreventInstalledAccess middleware to prevent access
| after installation is complete.
|
*/

Route::middleware(['web', 'guest', 'throttle:60,1', 'prevent.installed'])
    ->prefix('install')
    ->name('install.')
    ->group(function () {
        // Step 0: Language Selection
        Route::get('/', [InstallController::class, 'index'])->name('index');
        Route::post('/language', [InstallController::class, 'setLanguage'])->name('language');

        // Step 1: Welcome Screen
        Route::get('/welcome', [InstallController::class, 'welcome'])->name('welcome');

        // Step 2: Server Requirements Check
        Route::get('/requirements', [InstallController::class, 'requirements'])->name('requirements');

        // Step 3: Folder Permissions Check
        Route::get('/permissions', [InstallController::class, 'permissions'])->name('permissions');

        // Step 4: Environment Configuration
        Route::get('/environment', [InstallController::class, 'environment'])->name('environment');
        Route::post('/environment', [InstallController::class, 'saveEnvironment'])->name('environment.save');
        Route::post('/environment/test-database', [InstallController::class, 'testDatabase'])->name('environment.test-database');
        Route::post('/environment/test-stripe', [InstallController::class, 'testStripe'])->name('environment.test-stripe');

        // Step 5: Database Setup & Migrations
        Route::get('/database', [InstallController::class, 'database'])->name('database');
        Route::post('/database/migrate', [InstallController::class, 'runMigrations'])->name('database.migrate');

        // Step 6: Super Admin Creation
        Route::get('/admin', [InstallController::class, 'admin'])->name('admin');
        Route::post('/admin', [InstallController::class, 'createAdmin'])->name('admin.create');

        // Step 7: Installation Complete
        Route::get('/complete', [InstallController::class, 'complete'])->name('complete');
    });
