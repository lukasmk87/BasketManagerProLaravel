<?php

use App\Http\Controllers\Api\DrillController;
use App\Http\Controllers\Api\TrainingRegistrationController;
use App\Http\Controllers\Api\TrainingSessionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Training API Routes
|--------------------------------------------------------------------------
|
| Here are the API routes for the Training Management System.
| These routes are protected by authentication middleware.
|
*/

Route::middleware('auth:sanctum')->group(function () {

    // Training Sessions Routes
    Route::prefix('training-sessions')->name('training-sessions.')->group(function () {
        Route::get('/', [TrainingSessionController::class, 'index'])->name('index');
        Route::post('/', [TrainingSessionController::class, 'store'])->name('store');
        Route::get('/{trainingSession}', [TrainingSessionController::class, 'show'])->name('show');
        Route::put('/{trainingSession}', [TrainingSessionController::class, 'update'])->name('update');
        Route::delete('/{trainingSession}', [TrainingSessionController::class, 'destroy'])->name('destroy');

        // Session Actions
        Route::post('/{trainingSession}/start', [TrainingSessionController::class, 'start'])->name('start');
        Route::post('/{trainingSession}/complete', [TrainingSessionController::class, 'complete'])->name('complete');

        // Drill Management
        Route::get('/{trainingSession}/drills', [TrainingSessionController::class, 'drills'])->name('drills');
        Route::post('/{trainingSession}/drills', [TrainingSessionController::class, 'addDrill'])->name('add-drill');
        Route::delete('/{trainingSession}/drills/{drill}', [TrainingSessionController::class, 'removeDrill'])->name('remove-drill');
        Route::put('/{trainingSession}/drills/reorder', [TrainingSessionController::class, 'reorderDrills'])->name('reorder-drills');
        Route::put('/{trainingSession}/drills/{drill}/performance', [TrainingSessionController::class, 'recordDrillPerformance'])->name('record-drill-performance');

        // Attendance Management
        Route::get('/{trainingSession}/attendance', [TrainingSessionController::class, 'attendance'])->name('attendance');
        Route::post('/{trainingSession}/attendance', [TrainingSessionController::class, 'markAttendance'])->name('mark-attendance');

        // Registration Management
        Route::get('/{trainingSession}/registrations', [TrainingRegistrationController::class, 'index'])->name('registrations');
        Route::post('/{trainingSession}/registrations', [TrainingRegistrationController::class, 'store'])->name('register');
        Route::put('/{trainingSession}/registrations/status', [TrainingRegistrationController::class, 'updateStatus'])->name('update-status');
        Route::post('/{trainingSession}/bulk-register', [TrainingRegistrationController::class, 'bulkRegister'])->name('bulk-register');
        Route::post('/registrations/confirm', [TrainingRegistrationController::class, 'confirm'])->name('confirm-registration');
        Route::get('/player-registrations', [TrainingRegistrationController::class, 'getPlayerRegistrations'])->name('player-registrations');

        // Statistics
        Route::get('/{trainingSession}/statistics', [TrainingSessionController::class, 'statistics'])->name('statistics');
    });

    // Drills Routes
    Route::prefix('drills')->name('drills.')->group(function () {
        Route::get('/', [DrillController::class, 'index'])->name('index');
        Route::post('/', [DrillController::class, 'store'])->name('store');
        Route::get('/categories', [DrillController::class, 'categories'])->name('categories');
        Route::get('/statistics', [DrillController::class, 'statistics'])->name('statistics');
        Route::get('/recommendations', [DrillController::class, 'recommendations'])->name('recommendations');

        Route::get('/{drill}', [DrillController::class, 'show'])->name('show');
        Route::put('/{drill}', [DrillController::class, 'update'])->name('update');
        Route::delete('/{drill}', [DrillController::class, 'destroy'])->name('destroy');

        // Drill Actions
        Route::post('/{drill}/duplicate', [DrillController::class, 'duplicate'])->name('duplicate');
        Route::post('/{drill}/rate', [DrillController::class, 'rate'])->name('rate');
        Route::post('/{drill}/favorites', [DrillController::class, 'addToFavorites'])->name('add-to-favorites');
        Route::delete('/{drill}/favorites', [DrillController::class, 'removeFromFavorites'])->name('remove-from-favorites');
    });

});
