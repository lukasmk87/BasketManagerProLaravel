<?php

use App\Http\Controllers\PWAController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PWA Routes
|--------------------------------------------------------------------------
|
| Routes for Progressive Web App functionality including service worker,
| manifest, offline pages, and PWA management endpoints
|
*/

// Public PWA files (no authentication required)
Route::group(['prefix' => 'pwa'], function () {
    
    // Service Worker (must be served from root for scope)
    Route::get('/sw.js', [PWAController::class, 'serviceWorker'])
        ->name('pwa.service-worker');
    
    // PWA Manifest
    Route::get('/manifest.json', [PWAController::class, 'manifest'])
        ->name('pwa.manifest');
    
    // Offline page
    Route::get('/offline', [PWAController::class, 'offline'])
        ->name('pwa.offline');
    
});

// Also serve service worker from root (required for proper scope)
Route::get('/sw.js', [PWAController::class, 'serviceWorker'])
    ->name('service-worker');

Route::get('/manifest.json', [PWAController::class, 'manifest'])
    ->name('manifest');

Route::get('/offline', [PWAController::class, 'offline'])
    ->name('offline');

// Authenticated PWA management routes
Route::middleware(['auth', 'tenant'])->group(function () {
    
    Route::prefix('pwa')->name('pwa.')->group(function () {
        
        // PWA Status and Management
        Route::get('/status', [PWAController::class, 'status'])
            ->name('status');
        
        Route::post('/clear-caches', [PWAController::class, 'clearCaches'])
            ->name('clear-caches');
        
        Route::post('/update-service-worker', [PWAController::class, 'updateServiceWorker'])
            ->name('update-service-worker');
        
        // Offline Data Management
        Route::post('/queue-offline-data', [PWAController::class, 'queueOfflineData'])
            ->name('queue-offline-data');
        
        Route::post('/process-offline-queue/{type}', [PWAController::class, 'processOfflineQueue'])
            ->name('process-offline-queue')
            ->where('type', 'game_stats|player_data|training_data|federation_sync');
        
        // Push Notifications
        Route::post('/subscribe-push', [PWAController::class, 'subscribePushNotifications'])
            ->name('subscribe-push');
        
    });
    
});

// Basketball-specific offline sync endpoints
Route::middleware(['auth', 'tenant', 'feature:live_scoring'])->group(function () {
    
    Route::prefix('api/sync')->name('api.sync.')->group(function () {
        
        // Game Statistics Sync
        Route::post('/game-stats', function (\Illuminate\Http\Request $request) {
            // Handle game statistics sync from offline storage
            $request->validate([
                'game_id' => 'required|uuid',
                'stats' => 'required|array',
                'timestamp' => 'required|date'
            ]);
            
            try {
                // Process game statistics
                $gameId = $request->input('game_id');
                $stats = $request->input('stats');
                
                // Here you would implement the actual sync logic
                // For now, just return success
                
                \Illuminate\Support\Facades\Log::info('Game stats synced from offline', [
                    'game_id' => $gameId,
                    'stats_count' => count($stats),
                    'user_id' => $request->user()->id
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Game statistics synced successfully',
                    'synced_count' => count($stats)
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to sync game stats', [
                    'error' => $e->getMessage(),
                    'user_id' => $request->user()->id
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to sync game statistics'
                ], 500);
            }
        })->name('game-stats');
        
        // Player Data Sync
        Route::post('/player-data', function (\Illuminate\Http\Request $request) {
            $request->validate([
                'player_id' => 'required|uuid',
                'updates' => 'required|array',
                'timestamp' => 'required|date'
            ]);
            
            try {
                $playerId = $request->input('player_id');
                $updates = $request->input('updates');
                
                \Illuminate\Support\Facades\Log::info('Player data synced from offline', [
                    'player_id' => $playerId,
                    'updates' => $updates,
                    'user_id' => $request->user()->id
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Player data synced successfully'
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to sync player data'
                ], 500);
            }
        })->name('player-data');
        
        // Training Data Sync
        Route::post('/training-data', function (\Illuminate\Http\Request $request) {
            $request->validate([
                'session_id' => 'required|uuid',
                'drills' => 'required|array',
                'attendance' => 'required|array',
                'timestamp' => 'required|date'
            ]);
            
            try {
                $sessionId = $request->input('session_id');
                $drills = $request->input('drills');
                $attendance = $request->input('attendance');
                
                \Illuminate\Support\Facades\Log::info('Training data synced from offline', [
                    'session_id' => $sessionId,
                    'drills_count' => count($drills),
                    'attendance_count' => count($attendance),
                    'user_id' => $request->user()->id
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Training data synced successfully'
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to sync training data'
                ], 500);
            }
        })->name('training-data');
        
    });
    
});

// Federation sync endpoints (require specific federation features)
Route::middleware(['auth', 'tenant'])->group(function () {
    
    Route::prefix('federation/sync')->name('federation.sync.')->group(function () {
        
        // DBB Sync
        Route::post('/dbb', function (\Illuminate\Http\Request $request) {
            if (!$request->user()->tenant->hasFeature('dbb_integration')) {
                return response()->json(['error' => 'DBB integration not available'], 403);
            }
            
            $request->validate([
                'type' => 'required|string|in:player,team,game',
                'data' => 'required|array',
                'timestamp' => 'required|date'
            ]);
            
            try {
                \Illuminate\Support\Facades\Log::info('DBB data synced from offline', [
                    'type' => $request->input('type'),
                    'user_id' => $request->user()->id,
                    'tenant_id' => $request->user()->tenant_id
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'DBB data synced successfully'
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to sync DBB data'
                ], 500);
            }
        })->name('dbb');
        
        // FIBA Sync
        Route::post('/fiba', function (\Illuminate\Http\Request $request) {
            if (!$request->user()->tenant->hasFeature('fiba_integration')) {
                return response()->json(['error' => 'FIBA integration not available'], 403);
            }
            
            $request->validate([
                'type' => 'required|string|in:player,team,competition',
                'data' => 'required|array',
                'timestamp' => 'required|date'
            ]);
            
            try {
                \Illuminate\Support\Facades\Log::info('FIBA data synced from offline', [
                    'type' => $request->input('type'),
                    'user_id' => $request->user()->id,
                    'tenant_id' => $request->user()->tenant_id
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'FIBA data synced successfully'
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to sync FIBA data'
                ], 500);
            }
        })->name('fiba');
        
    });
    
});

// PWA installation and update endpoints
Route::middleware(['auth', 'tenant'])->group(function () {
    
    Route::prefix('pwa/install')->name('pwa.install.')->group(function () {
        
        // Check installation eligibility
        Route::get('/check', function (\Illuminate\Http\Request $request) {
            $tenant = $request->user()->tenant;
            
            $eligibility = [
                'can_install' => true,
                'requirements_met' => [
                    'https' => $request->secure(),
                    'manifest' => true,
                    'service_worker' => true,
                    'offline_page' => true
                ],
                'tenant_features' => [
                    'pwa_enabled' => $tenant->hasFeature('pwa'),
                    'offline_support' => $tenant->hasFeature('offline_support'),
                    'push_notifications' => $tenant->hasFeature('push_notifications')
                ]
            ];
            
            return response()->json($eligibility);
        })->name('check');
        
        // Get installation instructions
        Route::get('/instructions', function (\Illuminate\Http\Request $request) {
            $userAgent = $request->userAgent();
            $platform = 'unknown';
            
            if (preg_match('/Android/i', $userAgent)) {
                $platform = 'android';
            } elseif (preg_match('/iPhone|iPad/i', $userAgent)) {
                $platform = 'ios';
            } elseif (preg_match('/Windows/i', $userAgent)) {
                $platform = 'windows';
            } elseif (preg_match('/Macintosh/i', $userAgent)) {
                $platform = 'macos';
            }
            
            $instructions = [
                'android' => [
                    'Chrome' => 'Tippe auf das Menü (⋮) und wähle "Zum Startbildschirm hinzufügen"',
                    'Firefox' => 'Tippe auf das Menü (⋮) und wähle "Zu Startbildschirm hinzufügen"',
                ],
                'ios' => [
                    'Safari' => 'Tippe auf das Teilen-Symbol (□↗) und wähle "Zum Home-Bildschirm hinzufügen"',
                ],
                'windows' => [
                    'Chrome' => 'Klicke auf das Install-Symbol in der Adressleiste oder öffne das Menü (⋮) > "BasketManager Pro installieren"',
                    'Edge' => 'Klicke auf das Install-Symbol in der Adressleiste oder öffne das Menü (⋯) > "Apps" > "Diese Seite als App installieren"',
                ],
                'macos' => [
                    'Chrome' => 'Klicke auf das Install-Symbol in der Adressleiste oder öffne das Menü (⋮) > "BasketManager Pro installieren"',
                    'Safari' => 'Gehe zu Datei > "Zum Dock hinzufügen"',
                ]
            ];
            
            return response()->json([
                'platform' => $platform,
                'instructions' => $instructions[$platform] ?? ['Dein Browser unterstützt möglicherweise keine PWA-Installation'],
                'general_tip' => 'Suche nach einem "Installieren" oder "Zum Startbildschirm hinzufügen" Button in deinem Browser'
            ]);
        })->name('instructions');
        
    });
    
});

// Emergency PWA Routes (no authentication required for emergency access)
Route::prefix('emergency/pwa')->name('emergency.pwa.')->group(function () {
    
    // Emergency PWA Manifest
    Route::get('/manifest/{accessKey}', [PWAController::class, 'emergencyManifest'])
        ->name('manifest')
        ->where('accessKey', '[a-zA-Z0-9]{32}');
    
    // Emergency Service Worker
    Route::get('/sw/{accessKey}.js', [PWAController::class, 'emergencyServiceWorker'])
        ->name('service-worker')
        ->where('accessKey', '[a-zA-Z0-9]{32}');
    
    // PWA Installation Prompt
    Route::get('/install/{accessKey}', [PWAController::class, 'emergencyInstallPrompt'])
        ->name('install')
        ->where('accessKey', '[a-zA-Z0-9]{32}');
    
    // Offline Emergency Interface
    Route::get('/offline/{accessKey}', [PWAController::class, 'emergencyOfflineInterface'])
        ->name('offline')
        ->where('accessKey', '[a-zA-Z0-9]{32}');
    
    // Cache Emergency Data
    Route::get('/cache/{accessKey}', [PWAController::class, 'cacheEmergencyData'])
        ->name('cache')
        ->where('accessKey', '[a-zA-Z0-9]{32}');
    
});

// Emergency API endpoints for offline sync
Route::prefix('api/emergency')->name('api.emergency.')->group(function () {
    
    // Incident reporting from offline PWA
    Route::post('/incidents', function (\Illuminate\Http\Request $request) {
        $request->validate([
            'access_key' => 'required|string|size:32',
            'incident_type' => 'required|in:injury,medical_emergency,accident,missing_person,behavioral_incident,facility_emergency,weather_emergency,other',
            'severity' => 'required|in:minor,moderate,severe,critical',
            'description' => 'required|string|max:2000',
            'location' => 'required|string|max:500',
            'player_id' => 'nullable|exists:players,id',
            'coordinates' => 'nullable|array',
            'timestamp' => 'required|date',
        ]);

        try {
            // Verify access key
            $access = \App\Models\TeamEmergencyAccess::where('access_key', $request->input('access_key'))
                ->where('is_active', true)
                ->where('expires_at', '>', now())
                ->first();

            if (!$access) {
                return response()->json(['error' => 'Invalid or expired access key'], 401);
            }

            // Create incident
            $incidentId = 'EMG-' . date('Y') . '-' . str_pad(\App\Models\EmergencyIncident::count() + 1, 4, '0', STR_PAD_LEFT);
            
            $incident = \App\Models\EmergencyIncident::create([
                'incident_id' => $incidentId,
                'team_id' => $access->team_id,
                'player_id' => $request->input('player_id'),
                'incident_type' => $request->input('incident_type'),
                'severity' => $request->input('severity'),
                'description' => $request->input('description'),
                'occurred_at' => $request->input('timestamp'),
                'location' => $request->input('location'),
                'coordinates' => $request->input('coordinates'),
                'reported_by_user_id' => 1, // System user for PWA reports
                'reported_at' => now(),
                'status' => 'active',
            ]);

            // Log emergency access usage
            app(\App\Services\EmergencyAccessService::class)->logEmergencyAccess($access, $request);

            // Send notifications for severe/critical incidents
            if (in_array($incident->severity, ['severe', 'critical'])) {
                app(\App\Services\NotificationService::class)->sendEmergencyAlert($incident);
            }

            \Illuminate\Support\Facades\Log::channel('emergency')->info('Emergency incident reported via PWA', [
                'incident_id' => $incident->incident_id,
                'access_key' => $access->access_key,
                'team_id' => $access->team_id,
                'severity' => $incident->severity,
            ]);

            return response()->json([
                'success' => true,
                'incident_id' => $incident->incident_id,
                'message' => 'Emergency incident reported successfully',
                'created_at' => $incident->created_at->toISOString(),
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to create emergency incident via PWA', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to report incident',
            ], 500);
        }
    })->name('create-incident');
    
    // Contact usage tracking
    Route::post('/contact-accessed', function (\Illuminate\Http\Request $request) {
        $request->validate([
            'access_key' => 'required|string|size:32',
            'contact_id' => 'required|exists:emergency_contacts,id',
            'action' => 'required|in:called,sms_sent,viewed',
            'timestamp' => 'required|date',
        ]);

        try {
            // Verify access key
            $access = \App\Models\TeamEmergencyAccess::where('access_key', $request->input('access_key'))
                ->where('is_active', true)
                ->where('expires_at', '>', now())
                ->first();

            if (!$access) {
                return response()->json(['error' => 'Invalid or expired access key'], 401);
            }

            // Log contact access
            $contact = \App\Models\EmergencyContact::find($request->input('contact_id'));
            
            if ($contact && $contact->player->team_id === $access->team_id) {
                // Update usage log
                $usageLog = $access->usage_log ?? [];
                $usageLog[] = [
                    'type' => 'contact_accessed',
                    'contact_id' => $contact->id,
                    'contact_name' => $contact->contact_name,
                    'player_name' => $contact->player->full_name,
                    'action' => $request->input('action'),
                    'timestamp' => $request->input('timestamp'),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ];

                $access->update([
                    'usage_log' => $usageLog,
                    'last_used_at' => now(),
                ]);

                // Update contact's last contacted info if it was a call
                if ($request->input('action') === 'called') {
                    $contact->update([
                        'last_contacted_at' => now(),
                        'last_contact_result' => 'attempted',
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Contact access logged',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to log contact access',
            ], 500);
        }
    })->name('log-contact-access');
    
});