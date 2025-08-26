<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\GameRegistrationController;

/*
|--------------------------------------------------------------------------
| Game Registration API Routes
|--------------------------------------------------------------------------
|
| Here are the API routes for the Game Registration and Roster Management System.
| These routes are protected by authentication middleware.
|
*/

Route::middleware('auth:sanctum')->group(function () {
    
    // Game Registration Routes
    Route::prefix('games/{game}')->name('games.')->group(function () {
        // Registration Management
        Route::get('/registrations', [GameRegistrationController::class, 'index'])->name('registrations.index');
        Route::post('/register', [GameRegistrationController::class, 'store'])->name('registrations.store');
        Route::put('/update-availability', [GameRegistrationController::class, 'updateAvailability'])->name('registrations.update-availability');
        Route::post('/bulk-register', [GameRegistrationController::class, 'bulkRegister'])->name('registrations.bulk-register');
        
        // Roster Management
        Route::get('/roster', [GameRegistrationController::class, 'roster'])->name('roster.index');
        Route::post('/roster/add', [GameRegistrationController::class, 'addToRoster'])->name('roster.add');
        Route::delete('/roster/remove', [GameRegistrationController::class, 'removeFromRoster'])->name('roster.remove');
        Route::put('/roster/update-role', [GameRegistrationController::class, 'updateRole'])->name('roster.update-role');
    });
    
    // General Registration Routes
    Route::prefix('game-registrations')->name('game-registrations.')->group(function () {
        Route::post('/confirm', [GameRegistrationController::class, 'confirm'])->name('confirm');
        Route::get('/player-registrations', [GameRegistrationController::class, 'getPlayerRegistrations'])->name('player-registrations');
        Route::get('/upcoming-deadlines', [GameRegistrationController::class, 'getUpcomingDeadlines'])->name('upcoming-deadlines');
    });
    
});