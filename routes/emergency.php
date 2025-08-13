<?php

use App\Http\Controllers\EmergencyAccessController;
use App\Http\Controllers\Api\V2\EmergencyContactController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Emergency Access Routes
|--------------------------------------------------------------------------
|
| Routes for emergency QR code access system. These routes are public
| but protected by access keys and rate limiting for security.
|
*/

// Emergency Access Routes (Public with access key)
Route::prefix('emergency')->name('emergency.')->group(function () {
    
    // Health check endpoint
    Route::get('/health', [EmergencyAccessController::class, 'healthCheck'])
        ->name('health');
    
    // QR Code Access Routes
    Route::middleware(['throttle:emergency-access,10,1'])->group(function () {
        
        // Show emergency access form
        Route::get('/access/{accessKey}', [EmergencyAccessController::class, 'showAccessForm'])
            ->name('access.form')
            ->where('accessKey', '[a-zA-Z0-9]{32}');
        
        // Process emergency access request
        Route::post('/access/{accessKey}', [EmergencyAccessController::class, 'processAccess'])
            ->name('access.process')
            ->where('accessKey', '[a-zA-Z0-9]{32}');
        
        // Direct emergency access (bypasses form for critical situations)
        Route::get('/direct/{accessKey}', [EmergencyAccessController::class, 'showDirectAccess'])
            ->name('access.direct')
            ->where('accessKey', '[a-zA-Z0-9]{32}');
        
        // Printable emergency contact list
        Route::get('/print/{accessKey}', [EmergencyAccessController::class, 'printableView'])
            ->name('access.printable')
            ->where('accessKey', '[a-zA-Z0-9]{32}');
    });
    
    // API Routes for Emergency Access (with CORS for mobile apps)
    Route::prefix('api')->name('api.')->middleware(['throttle:emergency-api,30,1'])->group(function () {
        
        // Get offline emergency data for PWA
        Route::get('/offline/{accessKey}', [EmergencyAccessController::class, 'getOfflineData'])
            ->name('offline.data')
            ->where('accessKey', '[a-zA-Z0-9]{32}');
        
        // Get specific player contacts
        Route::get('/player/{accessKey}/{playerId}', [EmergencyAccessController::class, 'getPlayerContacts'])
            ->name('player.contacts')
            ->where('accessKey', '[a-zA-Z0-9]{32}')
            ->where('playerId', '[0-9]+');
        
        // Record emergency call
        Route::post('/call/{accessKey}', [EmergencyAccessController::class, 'recordEmergencyCall'])
            ->name('record.call')
            ->where('accessKey', '[a-zA-Z0-9]{32}');
        
        // Report incident through emergency interface
        Route::post('/incident/{accessKey}', [EmergencyAccessController::class, 'reportIncident'])
            ->name('report.incident')
            ->where('accessKey', '[a-zA-Z0-9]{32}');
    });
});

// Legacy Emergency Contact API Routes (Protected by Sanctum)
Route::prefix('api/v2/emergency')->name('api.v2.emergency.')->middleware(['auth:sanctum'])->group(function () {
    
    // Emergency contacts CRUD
    Route::apiResource('contacts', EmergencyContactController::class, [
        'names' => [
            'index' => 'contacts.index',
            'store' => 'contacts.store',
            'show' => 'contacts.show',
            'update' => 'contacts.update',
            'destroy' => 'contacts.destroy',
        ]
    ]);
    
    // Additional emergency contact operations
    Route::controller(EmergencyContactController::class)->group(function () {
        // Get contacts by player
        Route::get('/contacts/player/{player}', 'byPlayer')
            ->name('contacts.by-player');
        
        // Set contact as primary
        Route::patch('/contacts/{emergencyContact}/primary', 'setPrimary')
            ->name('contacts.set-primary');
        
        // Update consent
        Route::patch('/contacts/{emergencyContact}/consent', 'updateConsent')
            ->name('contacts.update-consent');
        
        // Generate QR code for contact
        Route::post('/contacts/{emergencyContact}/qr', 'generateQR')
            ->name('contacts.generate-qr');
        
        // Legacy emergency access (deprecated, use new system)
        Route::get('/access/{token}', 'emergencyAccess')
            ->name('legacy.access');
        
        // Contact statistics
        Route::get('/statistics', 'statistics')
            ->name('statistics');
    });
});

// Admin Routes for Emergency System Management
Route::prefix('admin/emergency')->name('admin.emergency.')->middleware(['auth', 'verified', 'role:admin'])->group(function () {
    
    // Team Emergency Access Management
    Route::controller(\App\Http\Controllers\Admin\EmergencyAccessController::class)->group(function () {
        // List all team access keys
        Route::get('/access', 'index')->name('access.index');
        
        // Create new access key
        Route::post('/access', 'store')->name('access.store');
        
        // Show access key details
        Route::get('/access/{access}', 'show')->name('access.show');
        
        // Update access key
        Route::patch('/access/{access}', 'update')->name('access.update');
        
        // Deactivate access key
        Route::delete('/access/{access}', 'destroy')->name('access.destroy');
        
        // Generate new QR code
        Route::post('/access/{access}/regenerate-qr', 'regenerateQR')->name('access.regenerate-qr');
        
        // Bulk operations
        Route::post('/access/bulk-create', 'bulkCreate')->name('access.bulk-create');
        Route::post('/access/bulk-deactivate', 'bulkDeactivate')->name('access.bulk-deactivate');
    });
    
    // Emergency Incidents Management
    Route::controller(\App\Http\Controllers\Admin\EmergencyIncidentsController::class)->group(function () {
        Route::get('/incidents', 'index')->name('incidents.index');
        Route::get('/incidents/{incident}', 'show')->name('incidents.show');
        Route::patch('/incidents/{incident}', 'update')->name('incidents.update');
        Route::post('/incidents/{incident}/resolve', 'resolve')->name('incidents.resolve');
    });
    
    // Emergency System Analytics
    Route::controller(\App\Http\Controllers\Admin\EmergencyAnalyticsController::class)->group(function () {
        Route::get('/analytics', 'dashboard')->name('analytics.dashboard');
        Route::get('/analytics/usage', 'usageReport')->name('analytics.usage');
        Route::get('/analytics/incidents', 'incidentsReport')->name('analytics.incidents');
        Route::get('/analytics/export', 'exportData')->name('analytics.export');
    });
});

// Webhook Routes for Emergency Notifications
Route::prefix('webhooks/emergency')->name('webhooks.emergency.')->group(function () {
    
    // Generic emergency webhook
    Route::post('/notification', [\App\Http\Controllers\Webhooks\EmergencyWebhookController::class, 'handleNotification'])
        ->name('notification')
        ->middleware('verify-webhook-signature');
    
    // SMS status webhooks (Twilio, etc.)
    Route::post('/sms-status', [\App\Http\Controllers\Webhooks\EmergencyWebhookController::class, 'handleSMSStatus'])
        ->name('sms-status');
    
    // Call status webhooks
    Route::post('/call-status', [\App\Http\Controllers\Webhooks\EmergencyWebhookController::class, 'handleCallStatus'])
        ->name('call-status');
});

// Service Worker Route for PWA
Route::get('/emergency-sw.js', function () {
    return response()
        ->view('emergency.service-worker')
        ->header('Content-Type', 'application/javascript')
        ->header('Service-Worker-Allowed', '/')
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
})->name('emergency.service-worker');

// PWA Manifest Route
Route::get('/emergency-manifest.json', function () {
    return response()->json([
        'name' => 'Basketball Emergency Access',
        'short_name' => 'Emergency',
        'description' => 'Emergency contact access for basketball teams',
        'start_url' => '/',
        'display' => 'standalone',
        'background_color' => '#ffffff',
        'theme_color' => '#dc2626',
        'icons' => [
            [
                'src' => '/images/emergency-icon-192.png',
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any maskable'
            ],
            [
                'src' => '/images/emergency-icon-512.png',
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any maskable'
            ]
        ],
        'categories' => ['health', 'sports', 'emergency'],
        'orientation' => 'portrait',
        'scope' => '/',
        'lang' => 'de-DE'
    ])->header('Cache-Control', 'public, max-age=3600');
})->name('emergency.manifest');

// Fallback Route for Emergency Access (catches malformed URLs)
Route::fallback(function () {
    if (request()->is('emergency/*')) {
        return response()->view('emergency.not-found', [], 404);
    }
    
    // Let other fallbacks handle non-emergency routes
    return response('Page not found', 404);
});

/*
|--------------------------------------------------------------------------
| Rate Limiting Configuration
|--------------------------------------------------------------------------
*/

// Register custom rate limiters for emergency access
Route::middleware('throttle:emergency-form,5,1')->group(function () {
    // Form submission rate limiting (5 attempts per minute)
});

Route::middleware('throttle:emergency-critical,20,1')->group(function () {
    // Critical emergency access (higher limits)
});

/*
|--------------------------------------------------------------------------
| Security Middleware Configuration
|--------------------------------------------------------------------------
*/

// CORS configuration for emergency API endpoints
Route::middleware('cors')->group(function () {
    // Emergency API endpoints that need CORS for mobile apps
});

// Security headers for emergency access
Route::middleware(['emergency-security-headers'])->group(function () {
    // All emergency routes get additional security headers
});

/*
|--------------------------------------------------------------------------
| Monitoring and Logging
|--------------------------------------------------------------------------
*/

// All emergency routes are logged for security monitoring
Route::middleware(['emergency-audit-log'])->group(function () {
    // Comprehensive logging for all emergency access
});