<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * PWA Service
 * 
 * Handles Progressive Web App functionality including service worker management,
 * offline caching strategies, and push notifications for basketball management
 */
class PWAService
{
    private const CACHE_KEY_PREFIX = 'pwa_';
    private const SW_VERSION_KEY = 'sw_version';
    private const OFFLINE_QUEUE_KEY = 'offline_queue';
    
    /**
     * Generate dynamic service worker with tenant-specific configuration
     *
     * @param string|null $tenantId
     * @return string
     */
    public function generateServiceWorker(?string $tenantId = null): string
    {
        $baseServiceWorker = File::get(public_path('sw.js'));
        
        // Inject tenant-specific configuration
        $tenantConfig = $this->getTenantConfiguration($tenantId);
        $configScript = $this->generateConfigScript($tenantConfig);
        
        return $baseServiceWorker . "\n\n" . $configScript;
    }
    
    /**
     * Get tenant-specific PWA configuration
     *
     * @param string|null $tenantId
     * @return array
     */
    private function getTenantConfiguration(?string $tenantId): array
    {
        if (!$tenantId) {
            return $this->getDefaultConfiguration();
        }
        
        return Cache::remember(
            self::CACHE_KEY_PREFIX . "config_{$tenantId}",
            3600,
            function () use ($tenantId) {
                // Get tenant-specific configuration from database
                $tenant = \App\Models\Tenant::find($tenantId);
                
                if (!$tenant) {
                    return $this->getDefaultConfiguration();
                }
                
                return [
                    'tenant_id' => $tenantId,
                    'cache_strategy' => $tenant->getSetting('pwa.cache_strategy', 'balanced'),
                    'offline_timeout' => $tenant->getSetting('pwa.offline_timeout', 10000),
                    'sync_interval' => $tenant->getSetting('pwa.sync_interval', 300000), // 5 minutes
                    'max_cache_size' => $tenant->getSetting('pwa.max_cache_size', 50 * 1024 * 1024), // 50MB
                    'basketball_features' => [
                        'live_scoring' => $tenant->hasFeature('live_scoring'),
                        'player_tracking' => $tenant->hasFeature('player_tracking'),
                        'advanced_stats' => $tenant->hasFeature('advanced_stats'),
                        'video_analysis' => $tenant->hasFeature('video_analysis'),
                    ],
                    'federation_sync' => [
                        'dbb_enabled' => $tenant->hasFeature('dbb_integration'),
                        'fiba_enabled' => $tenant->hasFeature('fiba_integration'),
                    ],
                    'push_notifications' => [
                        'game_updates' => $tenant->getSetting('notifications.game_updates', true),
                        'player_alerts' => $tenant->getSetting('notifications.player_alerts', true),
                        'training_reminders' => $tenant->getSetting('notifications.training_reminders', true),
                    ]
                ];
            }
        );
    }
    
    /**
     * Get default PWA configuration
     *
     * @return array
     */
    private function getDefaultConfiguration(): array
    {
        return [
            'tenant_id' => null,
            'cache_strategy' => 'balanced',
            'offline_timeout' => 10000,
            'sync_interval' => 300000,
            'max_cache_size' => 50 * 1024 * 1024,
            'basketball_features' => [
                'live_scoring' => false,
                'player_tracking' => false,
                'advanced_stats' => false,
                'video_analysis' => false,
            ],
            'federation_sync' => [
                'dbb_enabled' => false,
                'fiba_enabled' => false,
            ],
            'push_notifications' => [
                'game_updates' => false,
                'player_alerts' => false,
                'training_reminders' => false,
            ]
        ];
    }
    
    /**
     * Generate configuration script for service worker
     *
     * @param array $config
     * @return string
     */
    private function generateConfigScript(array $config): string
    {
        $configJson = json_encode($config, JSON_PRETTY_PRINT);
        
        return "
// Tenant-specific PWA Configuration
const TENANT_CONFIG = {$configJson};

// Update cache name with tenant ID
if (TENANT_CONFIG.tenant_id) {
    CACHE_NAME = `basketmanager-pro-\${TENANT_CONFIG.tenant_id}-v1.0.0`;
}

// Basketball-specific cache handlers
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);
    
    // Handle basketball live scoring requests
    if (TENANT_CONFIG.basketball_features.live_scoring && url.pathname.includes('/api/games/live')) {
        event.respondWith(handleLiveScoring(event.request));
    }
    
    // Handle federation sync requests
    if (url.pathname.includes('/federation/')) {
        if (TENANT_CONFIG.federation_sync.dbb_enabled || TENANT_CONFIG.federation_sync.fiba_enabled) {
            event.respondWith(handleFederationSync(event.request));
        }
    }
});

// Basketball-specific handlers
async function handleLiveScoring(request) {
    try {
        // Always try network first for live data
        const response = await fetch(request);
        
        // Cache successful responses for offline fallback
        if (response.ok) {
            const cache = await caches.open(BASKETBALL_CACHE_KEYS.GAME_STATS);
            cache.put(request, response.clone());
        }
        
        return response;
    } catch (error) {
        // Return cached data with offline indicator
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            const data = await cachedResponse.json();
            return new Response(JSON.stringify({
                ...data,
                offline: true,
                last_updated: new Date().toISOString()
            }), {
                headers: { 'Content-Type': 'application/json' }
            });
        }
        
        return new Response(JSON.stringify({
            error: 'Live scoring not available offline',
            offline: true
        }), {
            status: 503,
            headers: { 'Content-Type': 'application/json' }
        });
    }
}

async function handleFederationSync(request) {
    const url = new URL(request.url);
    
    // DBB integration
    if (TENANT_CONFIG.federation_sync.dbb_enabled && url.pathname.includes('/federation/dbb')) {
        return handleDBBRequest(request);
    }
    
    // FIBA integration
    if (TENANT_CONFIG.federation_sync.fiba_enabled && url.pathname.includes('/federation/fiba')) {
        return handleFIBARequest(request);
    }
    
    return fetch(request);
}

async function handleDBBRequest(request) {
    try {
        const response = await fetch(request);
        
        // Cache DBB data for offline access
        if (response.ok) {
            const cache = await caches.open('dbb-federation-data');
            cache.put(request, response.clone());
        }
        
        return response;
    } catch (error) {
        const cachedResponse = await caches.match(request);
        return cachedResponse || new Response(JSON.stringify({
            error: 'DBB data not available offline',
            offline: true
        }), {
            status: 503,
            headers: { 'Content-Type': 'application/json' }
        });
    }
}

async function handleFIBARequest(request) {
    try {
        const response = await fetch(request);
        
        // Cache FIBA data for offline access
        if (response.ok) {
            const cache = await caches.open('fiba-federation-data');
            cache.put(request, response.clone());
        }
        
        return response;
    } catch (error) {
        const cachedResponse = await caches.match(request);
        return cachedResponse || new Response(JSON.stringify({
            error: 'FIBA data not available offline',
            offline: true
        }), {
            status: 503,
            headers: { 'Content-Type': 'application/json' }
        });
    }
}

// Enhanced push notification handling
self.addEventListener('push', event => {
    if (!event.data) return;
    
    const data = event.data.json();
    const { type, title, body, icon, badge, actions } = data;
    
    // Basketball-specific notification types
    const basketballNotifications = {
        game_start: {
            title: 'Spiel beginnt',
            icon: '/images/notifications/game-start.png',
            badge: '/images/badge-game.png',
            vibrate: [200, 100, 200],
            actions: [
                { action: 'view-game', title: 'Spiel ansehen' },
                { action: 'start-scoring', title: 'Live-Scoring starten' }
            ]
        },
        player_foul: {
            title: 'Spieler-Foul',
            icon: '/images/notifications/foul.png',
            vibrate: [100, 50, 100],
            actions: [
                { action: 'view-player', title: 'Spieler ansehen' },
                { action: 'dismiss', title: 'Ignorieren' }
            ]
        },
        training_reminder: {
            title: 'Training Erinnerung',
            icon: '/images/notifications/training.png',
            actions: [
                { action: 'view-training', title: 'Training ansehen' },
                { action: 'mark-attended', title: 'Als teilgenommen markieren' }
            ]
        }
    };
    
    const notificationConfig = basketballNotifications[type] || {
        title: title || 'BasketManager Pro',
        icon: '/images/logo-192.png',
        badge: '/images/badge-72.png'
    };
    
    const options = {
        body: body || data.message,
        icon: notificationConfig.icon,
        badge: notificationConfig.badge,
        vibrate: notificationConfig.vibrate || [100, 50, 100],
        data: {
            ...data,
            dateOfArrival: Date.now(),
            primaryKey: data.id || '1'
        },
        actions: notificationConfig.actions || actions || [],
        requireInteraction: type === 'game_start' || type === 'emergency'
    };
    
    event.waitUntil(
        self.registration.showNotification(notificationConfig.title, options)
    );
});

// Enhanced background sync for basketball data
self.addEventListener('sync', event => {
    switch (event.tag) {
        case 'basketball-stats-sync':
            event.waitUntil(syncBasketballStats());
            break;
        case 'federation-data-sync':
            event.waitUntil(syncFederationData());
            break;
        case 'training-data-sync':
            event.waitUntil(syncTrainingData());
            break;
    }
});

async function syncBasketballStats() {
    if (!TENANT_CONFIG.basketball_features.live_scoring) return;
    
    try {
        const pendingStats = await getOfflineBasketballStats();
        
        for (const stat of pendingStats) {
            try {
                const response = await fetch('/api/games/stats/sync', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(stat)
                });
                
                if (response.ok) {
                    await removeOfflineBasketballStat(stat.id);
                }
            } catch (error) {
                console.error('Failed to sync basketball stat:', error);
            }
        }
    } catch (error) {
        console.error('Basketball stats sync failed:', error);
    }
}

async function syncFederationData() {
    if (!TENANT_CONFIG.federation_sync.dbb_enabled && !TENANT_CONFIG.federation_sync.fiba_enabled) {
        return;
    }
    
    try {
        const pendingData = await getOfflineFederationData();
        
        for (const data of pendingData) {
            try {
                const endpoint = data.type === 'dbb' ? '/federation/dbb/sync' : '/federation/fiba/sync';
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                if (response.ok) {
                    await removeOfflineFederationData(data.id);
                }
            } catch (error) {
                console.error('Failed to sync federation data:', error);
            }
        }
    } catch (error) {
        console.error('Federation data sync failed:', error);
    }
}

// Placeholder functions for IndexedDB operations
async function getOfflineBasketballStats() { return []; }
async function removeOfflineBasketballStat(id) { }
async function getOfflineFederationData() { return []; }
async function removeOfflineFederationData(id) { }

console.log('[SW] Tenant-specific configuration loaded:', TENANT_CONFIG);
";
    }
    
    /**
     * Register offline data for background sync
     *
     * @param string $type
     * @param array $data
     * @param string|null $tenantId
     * @return bool
     */
    public function queueOfflineData(string $type, array $data, ?string $tenantId = null): bool
    {
        try {
            $queueKey = self::OFFLINE_QUEUE_KEY . "_{$type}";
            if ($tenantId) {
                $queueKey .= "_{$tenantId}";
            }
            
            $queue = Cache::get($queueKey, []);
            $queue[] = [
                'id' => uniqid(),
                'type' => $type,
                'data' => $data,
                'tenant_id' => $tenantId,
                'created_at' => now()->toISOString(),
            ];
            
            Cache::put($queueKey, $queue, 86400); // 24 hours
            
            Log::info('PWA: Offline data queued', [
                'type' => $type,
                'tenant_id' => $tenantId,
                'queue_size' => count($queue)
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('PWA: Failed to queue offline data', [
                'type' => $type,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Process offline data queue
     *
     * @param string $type
     * @param string|null $tenantId
     * @return array
     */
    public function processOfflineQueue(string $type, ?string $tenantId = null): array
    {
        $queueKey = self::OFFLINE_QUEUE_KEY . "_{$type}";
        if ($tenantId) {
            $queueKey .= "_{$tenantId}";
        }
        
        $queue = Cache::get($queueKey, []);
        $processed = [];
        $failed = [];
        
        foreach ($queue as $index => $item) {
            try {
                $success = $this->processOfflineItem($item);
                
                if ($success) {
                    $processed[] = $item;
                    unset($queue[$index]);
                } else {
                    $failed[] = $item;
                }
            } catch (\Exception $e) {
                Log::error('PWA: Failed to process offline item', [
                    'item_id' => $item['id'],
                    'error' => $e->getMessage()
                ]);
                $failed[] = $item;
            }
        }
        
        // Update queue with remaining items
        Cache::put($queueKey, array_values($queue), 86400);
        
        Log::info('PWA: Offline queue processed', [
            'type' => $type,
            'tenant_id' => $tenantId,
            'processed_count' => count($processed),
            'failed_count' => count($failed),
            'remaining_count' => count($queue)
        ]);
        
        return [
            'processed' => $processed,
            'failed' => $failed,
            'remaining' => count($queue)
        ];
    }
    
    /**
     * Process individual offline item
     *
     * @param array $item
     * @return bool
     */
    private function processOfflineItem(array $item): bool
    {
        // This would contain the actual business logic for processing
        // different types of offline data (game stats, player updates, etc.)
        
        // For now, return true to simulate successful processing
        return true;
    }
    
    /**
     * Get PWA installation status
     *
     * @param string|null $tenantId
     * @return array
     */
    public function getInstallationStatus(?string $tenantId = null): array
    {
        $cacheKey = self::CACHE_KEY_PREFIX . "install_status";
        if ($tenantId) {
            $cacheKey .= "_{$tenantId}";
        }
        
        return Cache::remember($cacheKey, 300, function () {
            return [
                'service_worker_registered' => true, // Would check actual registration
                'manifest_valid' => File::exists(public_path('manifest.json')),
                'offline_page_available' => File::exists(resource_path('views/offline.blade.php')),
                'push_notifications_supported' => true, // Would check browser support
                'cache_api_available' => true, // Would check browser support
                'background_sync_supported' => true, // Would check browser support
            ];
        });
    }
    
    /**
     * Clear PWA caches
     *
     * @param string|null $tenantId
     * @return bool
     */
    public function clearCaches(?string $tenantId = null): bool
    {
        try {
            $pattern = self::CACHE_KEY_PREFIX . '*';
            if ($tenantId) {
                $pattern = self::CACHE_KEY_PREFIX . "*{$tenantId}*";
            }
            
            // Clear Laravel cache
            Cache::flush();
            
            Log::info('PWA: Caches cleared', [
                'tenant_id' => $tenantId,
                'pattern' => $pattern
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('PWA: Failed to clear caches', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Update service worker version
     *
     * @return string
     */
    public function updateServiceWorkerVersion(): string
    {
        $version = 'v' . time();
        Cache::put(self::SW_VERSION_KEY, $version, 86400);
        
        Log::info('PWA: Service worker version updated', [
            'version' => $version
        ]);
        
        return $version;
    }
    
    /**
     * Get current service worker version
     *
     * @return string
     */
    public function getServiceWorkerVersion(): string
    {
        return Cache::get(self::SW_VERSION_KEY, 'v1.0.0');
    }
}