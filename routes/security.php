<?php

use App\Http\Controllers\SecurityController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Security Routes
|--------------------------------------------------------------------------
|
| These routes handle security monitoring, dashboard, and security event
| management functionality for the application.
|
*/

Route::middleware(['auth', 'verified'])->group(function () {
    // Security Dashboard
    Route::get('/security', [SecurityController::class, 'dashboard'])
        ->name('security.dashboard')
        ->middleware('permission:view-security-dashboard');

    // Security Events Management
    Route::prefix('security')->name('security.')->group(function () {
        // Events listing and filtering
        Route::get('/events', [SecurityController::class, 'events'])
            ->name('events.index')
            ->middleware('permission:manage-security');

        // Individual event details
        Route::get('/events/{securityEvent}', [SecurityController::class, 'show'])
            ->name('events.show')
            ->middleware('permission:manage-security');

        // Update event status
        Route::patch('/events/{securityEvent}', [SecurityController::class, 'updateEvent'])
            ->name('events.update')
            ->middleware('permission:manage-security');

        // Generate security reports
        Route::post('/reports', [SecurityController::class, 'generateReport'])
            ->name('reports.generate')
            ->middleware('permission:manage-security');
    });

    // Security API endpoints
    Route::prefix('api/security')->name('api.security.')->middleware('throttle:120,1')->group(function () {
        // Real-time metrics for dashboard widgets
        Route::get('/metrics', [SecurityController::class, 'metrics'])
            ->name('metrics')
            ->middleware('permission:view-security-dashboard');

        // Event status updates (for AJAX requests)
        Route::patch('/events/{securityEvent}/status', [SecurityController::class, 'updateEvent'])
            ->name('events.update')
            ->middleware('permission:manage-security');
    });
});

/*
|--------------------------------------------------------------------------
| Security Monitoring Integration Routes
|--------------------------------------------------------------------------
|
| These routes may be used by other parts of the application to trigger
| security monitoring or access security-related functionality.
|
*/

Route::middleware(['auth', 'verified'])->group(function () {
    // Security incident reporting endpoint (for use by other controllers)
    Route::post('/security/incidents/report', function () {
        // This could be used by other parts of the app to manually report security incidents
        return response()->json(['message' => 'Security incident reporting endpoint']);
    })->name('security.incidents.report')->middleware('permission:report-security-incidents');
});