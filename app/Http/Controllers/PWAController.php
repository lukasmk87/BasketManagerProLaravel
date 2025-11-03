<?php

namespace App\Http\Controllers;

use App\Services\PWAService;
use App\Services\EmergencyAccessService;
use App\Models\TeamEmergencyAccess;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * PWA Controller
 * 
 * Handles Progressive Web App requests including service worker delivery,
 * offline page rendering, and PWA status endpoints
 */
class PWAController extends Controller
{
    private PWAService $pwaService;
    private EmergencyAccessService $emergencyAccessService;

    public function __construct(PWAService $pwaService, EmergencyAccessService $emergencyAccessService)
    {
        $this->pwaService = $pwaService;
        $this->emergencyAccessService = $emergencyAccessService;
    }

    /**
     * Serve the service worker with tenant-specific configuration
     *
     * @param Request $request
     * @return Response
     */
    public function serviceWorker(Request $request): Response
    {
        $tenantId = $request->user()?->tenant_id ?? 
                   session('tenant_id') ?? 
                   $request->header('X-Tenant-ID');

        $serviceWorkerContent = $this->pwaService->generateServiceWorker($tenantId);

        return response($serviceWorkerContent)
            ->header('Content-Type', 'application/javascript')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Serve the PWA manifest with tenant-specific branding
     *
     * @param Request $request
     * @return Response
     */
    public function manifest(Request $request): Response
    {
        $tenantId = $request->user()?->tenant_id ?? 
                   session('tenant_id') ?? 
                   $request->header('X-Tenant-ID');

        $manifest = $this->generateTenantManifest($tenantId);

        return response()->json($manifest)
            ->header('Content-Type', 'application/manifest+json')
            ->header('Cache-Control', 'public, max-age=3600'); // Cache for 1 hour
    }

    /**
     * Show the offline page
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function offline(Request $request)
    {
        $tenantId = $request->user()?->tenant_id ?? 
                   session('tenant_id') ?? 
                   $request->header('X-Tenant-ID');

        $tenant = null;
        if ($tenantId) {
            $tenant = \App\Models\Tenant::find($tenantId);
        }

        return view('offline', [
            'tenant' => $tenant,
            'features' => $this->getOfflineFeatures($tenant)
        ]);
    }

    /**
     * Get PWA installation status
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function status(Request $request)
    {
        $tenantId = $request->user()?->tenant_id ?? 
                   session('tenant_id') ?? 
                   $request->header('X-Tenant-ID');

        $status = $this->pwaService->getInstallationStatus($tenantId);

        return response()->json([
            'pwa_ready' => $this->isPWAReady($status),
            'details' => $status,
            'service_worker_version' => $this->pwaService->getServiceWorkerVersion(),
            'tenant_id' => $tenantId
        ]);
    }

    /**
     * Queue offline data for background sync
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function queueOfflineData(Request $request)
    {
        $request->validate([
            'type' => 'required|string|in:game_stats,player_data,training_data,federation_sync',
            'data' => 'required|array'
        ]);

        $tenantId = $request->user()?->tenant_id ?? 
                   session('tenant_id') ?? 
                   $request->header('X-Tenant-ID');

        $success = $this->pwaService->queueOfflineData(
            $request->input('type'),
            $request->input('data'),
            $tenantId
        );

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Data queued for sync when online'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to queue offline data'
        ], 500);
    }

    /**
     * Process offline data queue
     *
     * @param Request $request
     * @param string $type
     * @return \Illuminate\Http\JsonResponse
     */
    public function processOfflineQueue(Request $request, string $type)
    {
        $tenantId = $request->user()?->tenant_id ?? 
                   session('tenant_id') ?? 
                   $request->header('X-Tenant-ID');

        $result = $this->pwaService->processOfflineQueue($type, $tenantId);

        return response()->json([
            'success' => true,
            'processed' => count($result['processed']),
            'failed' => count($result['failed']),
            'remaining' => $result['remaining']
        ]);
    }

    /**
     * Clear PWA caches
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearCaches(Request $request)
    {
        $tenantId = $request->user()?->tenant_id ?? 
                   session('tenant_id') ?? 
                   $request->header('X-Tenant-ID');

        $success = $this->pwaService->clearCaches($tenantId);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Caches cleared successfully' : 'Failed to clear caches'
        ], $success ? 200 : 500);
    }

    /**
     * Update service worker version (force refresh)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateServiceWorker(Request $request)
    {
        $newVersion = $this->pwaService->updateServiceWorkerVersion();

        return response()->json([
            'success' => true,
            'new_version' => $newVersion,
            'message' => 'Service worker version updated'
        ]);
    }

    /**
     * Handle push notification subscription
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function subscribePushNotifications(Request $request)
    {
        $request->validate([
            'endpoint' => 'required|url',
            'keys' => 'required|array',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string'
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        try {
            // Store push subscription in database
            $user->pushSubscriptions()->updateOrCreate(
                ['endpoint' => $request->input('endpoint')],
                [
                    'p256dh_key' => $request->input('keys.p256dh'),
                    'auth_token' => $request->input('keys.auth'),
                    'user_agent' => $request->userAgent(),
                    'is_active' => true
                ]
            );

            Log::info('PWA: Push notification subscription created', [
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'endpoint' => $request->input('endpoint')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Push notification subscription saved'
            ]);
        } catch (\Exception $e) {
            Log::error('PWA: Failed to save push subscription', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save subscription'
            ], 500);
        }
    }

    /**
     * Generate tenant-specific manifest
     *
     * @param string|null $tenantId
     * @return array
     */
    private function generateTenantManifest(?string $tenantId): array
    {
        $defaultManifest = [
            'name' => app_name(),
            'short_name' => 'BasketManager',
            'description' => 'Professional Basketball Club Management System',
            'start_url' => '/',
            'display' => 'standalone',
            'background_color' => '#1a202c',
            'theme_color' => '#2d3748',
            'orientation' => 'portrait-primary'
        ];

        if (!$tenantId) {
            return $defaultManifest;
        }

        try {
            $tenant = \App\Models\Tenant::find($tenantId);
            if (!$tenant) {
                return $defaultManifest;
            }

            $branding = $tenant->branding ?? [];
            
            return array_merge($defaultManifest, [
                'name' => $branding['app_name'] ?? ($tenant->name . ' - ' . app_name()),
                'short_name' => $branding['short_name'] ?? $tenant->name,
                'description' => $branding['description'] ?? "Basketball management for {$tenant->name}",
                'theme_color' => $branding['primary_color'] ?? '#2d3748',
                'background_color' => $branding['background_color'] ?? '#1a202c',
                'start_url' => $tenant->getUrl() ?: '/',
                'scope' => $tenant->getUrl() ?: '/',
                'icons' => $this->getTenantIcons($tenant)
            ]);
        } catch (\Exception $e) {
            Log::error('PWA: Failed to generate tenant manifest', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);
            
            return $defaultManifest;
        }
    }

    /**
     * Get tenant-specific icons
     *
     * @param \App\Models\Tenant $tenant
     * @return array
     */
    private function getTenantIcons(\App\Models\Tenant $tenant): array
    {
        $defaultIcons = [
            ['src' => '/images/logo-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
            ['src' => '/images/logo-512.png', 'sizes' => '512x512', 'type' => 'image/png']
        ];

        $branding = $tenant->branding ?? [];
        if (empty($branding['icons'])) {
            return $defaultIcons;
        }

        return $branding['icons'];
    }

    /**
     * Get offline features for tenant
     *
     * @param \App\Models\Tenant|null $tenant
     * @return array
     */
    private function getOfflineFeatures(?\App\Models\Tenant $tenant): array
    {
        $defaultFeatures = [
            'Spielstatistiken einsehen',
            'Spielerprofil verwalten', 
            'Trainingseinheiten planen',
            'Taktische Übungen durchführen'
        ];

        if (!$tenant) {
            return $defaultFeatures;
        }

        $features = $defaultFeatures;

        if ($tenant->hasFeature('live_scoring')) {
            $features[] = 'Live-Scoring (offline erfassung)';
        }

        if ($tenant->hasFeature('advanced_stats')) {
            $features[] = 'Erweiterte Statistiken';
        }

        if ($tenant->hasFeature('video_analysis')) {
            $features[] = 'Video-Analyse (cached videos)';
        }

        if ($tenant->hasFeature('player_tracking')) {
            $features[] = 'Spieler-Tracking';
        }

        return $features;
    }

    /**
     * Check if PWA is ready for installation
     *
     * @param array $status
     * @return bool
     */
    private function isPWAReady(array $status): bool
    {
        return $status['service_worker_registered'] &&
               $status['manifest_valid'] &&
               $status['offline_page_available'];
    }

    // EMERGENCY PWA FEATURES

    /**
     * Generate emergency PWA manifest for offline access
     *
     * @param Request $request
     * @param string $accessKey
     * @return Response
     */
    public function emergencyManifest(Request $request, string $accessKey): Response
    {
        $access = TeamEmergencyAccess::where('access_key', $accessKey)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->with(['team.players.emergencyContacts' => function ($query) {
                $query->active()->withConsent()->byPriority();
            }])
            ->first();

        if (!$access) {
            return response()->json(['error' => 'Access not found'], 404);
        }

        $manifest = $this->generateEmergencyManifest($access);

        return response()->json($manifest)
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    /**
     * Serve emergency service worker
     *
     * @param Request $request
     * @param string $accessKey
     * @return Response
     */
    public function emergencyServiceWorker(Request $request, string $accessKey): Response
    {
        $access = TeamEmergencyAccess::where('access_key', $accessKey)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->first();

        if (!$access) {
            return response()->json(['error' => 'Access not found'], 404);
        }

        $serviceWorkerCode = $this->generateEmergencyServiceWorker($access);
        
        return response($serviceWorkerCode)
            ->header('Content-Type', 'application/javascript')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    /**
     * Show PWA install prompt for emergency access
     *
     * @param Request $request
     * @param string $accessKey
     * @return \Illuminate\View\View
     */
    public function emergencyInstallPrompt(Request $request, string $accessKey)
    {
        $access = TeamEmergencyAccess::where('access_key', $accessKey)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->with('team')
            ->first();

        if (!$access) {
            abort(404, 'Emergency access not found or expired');
        }

        return view('emergency.pwa-install', [
            'accessKey' => $accessKey,
            'team' => $access->team,
            'installInstructions' => $this->getEmergencyInstallInstructions(),
            'features' => [
                'Offline-Zugriff auf Notfallkontakte',
                'Ein-Tipp-Anruf-Funktionalität',
                'Schnelle Incident-Meldung',
                'GPS-Standortfreigabe',
                'Kritische medizinische Informationen',
            ],
        ]);
    }

    /**
     * Show offline emergency interface
     *
     * @param Request $request
     * @param string $accessKey
     * @return \Illuminate\View\View
     */
    public function emergencyOfflineInterface(Request $request, string $accessKey)
    {
        $access = TeamEmergencyAccess::where('access_key', $accessKey)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->with('team')
            ->first();

        if (!$access) {
            abort(404, 'Emergency access not found or expired');
        }

        return view('emergency.offline-interface', [
            'accessKey' => $accessKey,
            'team' => $access->team,
            'emergencyNumbers' => [
                'ambulance' => '112',
                'fire' => '112',
                'police' => '110',
            ],
            'lastCacheUpdate' => Cache::get("emergency_cache_time_{$accessKey}", now()),
        ])->withHeaders([
            'Cache-Control' => 'public, max-age=31536000', // 1 year
            'Service-Worker-Allowed' => '/',
        ]);
    }

    /**
     * Cache emergency data for offline access
     *
     * @param Request $request
     * @param string $accessKey
     * @return \Illuminate\Http\JsonResponse
     */
    public function cacheEmergencyData(Request $request, string $accessKey)
    {
        $access = TeamEmergencyAccess::where('access_key', $accessKey)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->with(['team.players.emergencyContacts' => function ($query) {
                $query->active()->withConsent()->byPriority();
            }])
            ->first();

        if (!$access) {
            return response()->json(['error' => 'Access not found'], 404);
        }

        $offlineData = $this->emergencyAccessService->getOfflineEmergencyData($access);
        $cacheKey = "emergency_offline_data_{$accessKey}";
        
        Cache::put($cacheKey, $offlineData, 86400); // 24 hours
        Cache::put("emergency_cache_time_{$accessKey}", now(), 86400);

        return response()->json([
            'success' => true,
            'data' => $offlineData,
            'cached_at' => now()->toISOString(),
            'expires_at' => now()->addDay()->toISOString(),
        ]);
    }

    /**
     * Generate emergency-specific manifest
     *
     * @param TeamEmergencyAccess $access
     * @return array
     */
    private function generateEmergencyManifest(TeamEmergencyAccess $access): array
    {
        $emergencyContacts = $access->team->players
            ->filter(fn($player) => $player->emergencyContacts->isNotEmpty())
            ->map(function ($player) {
                return [
                    'player_id' => $player->id,
                    'player_name' => $player->full_name,
                    'jersey_number' => $player->jersey_number,
                    'contacts' => $player->emergencyContacts->map(function ($contact) {
                        return [
                            'id' => $contact->id,
                            'name' => $contact->contact_name,
                            'phone' => $contact->display_phone_number,
                            'relationship' => $contact->relationship,
                            'is_primary' => $contact->is_primary,
                            'priority' => $contact->priority,
                            'medical_training' => $contact->has_medical_training,
                            'pickup_authorized' => $contact->emergency_pickup_authorized,
                            'medical_decisions' => $contact->medical_decisions_authorized,
                            'special_instructions' => $contact->special_instructions,
                        ];
                    })->toArray(),
                ];
            })->toArray();

        return [
            'name' => $access->team->name . ' - Emergency Access',
            'short_name' => 'Emergency',
            'description' => 'Emergency contact access for ' . $access->team->name,
            'start_url' => route('emergency.pwa.offline', ['accessKey' => $access->access_key]),
            'display' => 'standalone',
            'background_color' => '#dc2626',
            'theme_color' => '#991b1b',
            'orientation' => 'portrait-primary',
            'icons' => [
                ['src' => '/images/emergency-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any maskable'],
                ['src' => '/images/emergency-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable']
            ],
            'version' => '1.0.0',
            'generated_at' => now()->toISOString(),
            'access_key' => $access->access_key,
            'team' => [
                'id' => $access->team->id,
                'name' => $access->team->name,
                'club_name' => $access->team->club->name,
            ],
            'emergency_contacts' => $emergencyContacts,
            'emergency_instructions' => $this->getEmergencyInstructions(),
            'offline_capabilities' => [
                'contact_list_access' => true,
                'phone_calling' => true,
                'incident_reporting' => true,
                'gps_location' => true,
                'offline_sync' => true,
            ],
            'cache_strategy' => [
                'contacts_cache_duration' => 86400, // 24 hours
                'emergency_numbers_cache_duration' => 604800, // 1 week
                'instructions_cache_duration' => 604800, // 1 week
            ],
        ];
    }

    /**
     * Generate emergency service worker code
     *
     * @param TeamEmergencyAccess $access
     * @return string
     */
    private function generateEmergencyServiceWorker(TeamEmergencyAccess $access): string
    {
        return <<<'JS'
const CACHE_NAME = 'basketball-emergency-v1';
const OFFLINE_URL = '/emergency/offline';

// Emergency numbers that should always be available
const EMERGENCY_NUMBERS = {
    ambulance: '112',
    fire: '112',
    police: '110'
};

// Install event - cache critical resources
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            return cache.addAll([
                '/',
                '/emergency/offline',
                '/css/emergency.css',
                '/js/emergency.js',
                '/images/emergency-192.png',
                '/images/emergency-512.png'
            ]);
        })
    );
    self.skipWaiting();
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.filter(cacheName => {
                    return cacheName.startsWith('basketball-emergency-') && 
                           cacheName !== CACHE_NAME;
                }).map(cacheName => {
                    return caches.delete(cacheName);
                })
            );
        })
    );
    self.clients.claim();
});

// Fetch event - serve from cache when offline
self.addEventListener('fetch', event => {
    // Handle emergency contact requests
    if (event.request.url.includes('/emergency/contacts/')) {
        event.respondWith(
            caches.match(event.request).then(cachedResponse => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                
                return fetch(event.request).then(response => {
                    if (response.ok) {
                        const responseClone = response.clone();
                        caches.open(CACHE_NAME).then(cache => {
                            cache.put(event.request, responseClone);
                        });
                    }
                    return response;
                });
            }).catch(() => {
                // Return offline fallback with emergency numbers
                return new Response(JSON.stringify({
                    error: 'Offline',
                    emergency_numbers: EMERGENCY_NUMBERS,
                    message: 'Sie sind offline. Notfallnummern sind weiterhin verfügbar.'
                }), {
                    headers: { 'Content-Type': 'application/json' }
                });
            })
        );
    }
    
    // Handle other requests with network-first strategy
    event.respondWith(
        fetch(event.request).catch(() => {
            return caches.match(event.request).then(cachedResponse => {
                return cachedResponse || caches.match(OFFLINE_URL);
            });
        })
    );
});

// Background sync for incident reports
self.addEventListener('sync', event => {
    if (event.tag === 'emergency-incident-report') {
        event.waitUntil(syncIncidentReports());
    }
});

async function syncIncidentReports() {
    const reports = await getStoredIncidentReports();
    
    for (const report of reports) {
        try {
            const response = await fetch('/api/emergency/incidents', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(report)
            });
            
            if (response.ok) {
                await removeStoredIncidentReport(report.id);
            }
        } catch (error) {
            console.log('Failed to sync incident report:', error);
        }
    }
}

async function getStoredIncidentReports() {
    return new Promise((resolve) => {
        const request = indexedDB.open('EmergencyDB', 1);
        
        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction(['incident_reports'], 'readonly');
            const store = transaction.objectStore('incident_reports');
            const getAllRequest = store.getAll();
            
            getAllRequest.onsuccess = () => {
                resolve(getAllRequest.result || []);
            };
        };
    });
}

async function removeStoredIncidentReport(reportId) {
    return new Promise((resolve) => {
        const request = indexedDB.open('EmergencyDB', 1);
        
        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction(['incident_reports'], 'readwrite');
            const store = transaction.objectStore('incident_reports');
            const deleteRequest = store.delete(reportId);
            
            deleteRequest.onsuccess = () => {
                resolve();
            };
        };
    });
}
JS;
    }

    /**
     * Get emergency installation instructions
     *
     * @return array
     */
    private function getEmergencyInstallInstructions(): array
    {
        return [
            'chrome_android' => [
                'Öffnen Sie die Notfallzugangsseite',
                'Tippen Sie auf das Menü (drei Punkte)',
                'Wählen Sie "Zum Startbildschirm hinzufügen"',
                'Tippen Sie auf "Hinzufügen" zur Bestätigung',
                'Die Notfall-App erscheint auf Ihrem Startbildschirm',
            ],
            'safari_ios' => [
                'Öffnen Sie die Notfallzugangsseite in Safari',
                'Tippen Sie auf das Teilen-Symbol (Quadrat mit Pfeil)',
                'Scrollen Sie nach unten und tippen Sie auf "Zum Home-Bildschirm"',
                'Tippen Sie rechts oben auf "Hinzufügen"',
                'Die Notfall-App erscheint auf Ihrem Home-Bildschirm',
            ],
            'firefox_android' => [
                'Öffnen Sie die Notfallzugangsseite',
                'Tippen Sie auf das Menü (drei Linien)',
                'Wählen Sie "Installieren"',
                'Tippen Sie auf "Zum Startbildschirm hinzufügen"',
                'Die Notfall-App erscheint auf Ihrem Startbildschirm',
            ],
            'general' => [
                'Suchen Sie nach "App installieren" oder "Zum Startbildschirm hinzufügen"',
                'Dies erstellt eine Schnellzugriff-Notfall-App',
                'Funktioniert offline für kritische Situationen',
                'Ein-Tipp-Zugriff auf Notfallkontakte',
            ],
        ];
    }

    /**
     * Get emergency instructions
     *
     * @return array
     */
    private function getEmergencyInstructions(): array
    {
        return [
            'immediate_emergency' => [
                'title' => 'Lebensbedrohlicher Notfall',
                'steps' => [
                    'Sofort 112 anrufen',
                    'Genaue Standortangabe machen',
                    'Notfall klar beschreiben',
                    'Anweisungen der Leitstelle befolgen',
                    'Notfallkontakte der Person benachrichtigen',
                    'Bei der Person bleiben bis Hilfe eintrifft',
                ],
                'phone_numbers' => [
                    'ambulance' => '112',
                    'fire' => '112',
                    'police' => '110',
                ],
            ],
            'injury_assessment' => [
                'title' => 'Verletzungsbewertung',
                'steps' => [
                    'Erst die Sicherheit der Umgebung prüfen',
                    'Prüfen ob Person bei Bewusstsein ist',
                    'Nach offensichtlichen Verletzungen schauen',
                    'Atmung und Puls kontrollieren',
                    'Person nur bewegen wenn unbedingt nötig',
                    'Person ruhig halten und warm halten',
                ],
            ],
            'contact_protocol' => [
                'title' => 'Notfallkontakt-Protokoll',
                'steps' => [
                    'Mit primärem Kontakt beginnen (★ markiert)',
                    'Bei keiner Antwort, sekundäre Kontakte versuchen',
                    'Klare, ruhige Nachricht hinterlassen',
                    'Name, Standort, Situation mitteilen',
                    'Rückrufnummer angeben',
                    'Alle Kontakte versuchen bevor aufgeben',
                ],
            ],
            'information_to_provide' => [
                'title' => 'Zu übermittelnde Informationen',
                'details' => [
                    'Ihr Name und Funktion',
                    'Name und Team des Spielers',
                    'Genaue Adresse des Standorts',
                    'Art des Notfalls/der Verletzung',
                    'Aktueller Zustand des Spielers',
                    'Welche Hilfe wurde gerufen',
                    'Rückruf-Telefonnummer',
                ],
            ],
        ];
    }
}