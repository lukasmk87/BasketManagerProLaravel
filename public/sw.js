/**
 * BasketManager Pro Service Worker
 * Provides offline support and caching for PWA functionality
 */

const CACHE_NAME = 'basketmanager-pro-v1.0.0';
const OFFLINE_PAGE = '/offline';
const FALLBACK_IMAGE = '/images/placeholder.svg';

// Cache strategies
const CACHE_STRATEGIES = {
    // Essential app shell files - cache first with fast fallback
    APP_SHELL: 'cache-first',
    // Static assets - cache first with network fallback
    STATIC: 'cache-first',
    // API responses - network first with cache fallback
    API: 'network-first',
    // Images - cache first with network fallback
    IMAGES: 'cache-first',
    // Documents/files - network first
    DOCUMENTS: 'network-first'
};

// Files to cache immediately on install
const PRECACHE_URLS = [
    '/',
    '/manifest.json',
    '/offline',
    '/css/app.css',
    '/js/app.js',
    '/images/logo.svg',
    '/images/placeholder.svg',
    '/fonts/inter-var.woff2'
];

// URL patterns for different cache strategies
const URL_PATTERNS = {
    APP_SHELL: [
        /^\/$/,
        /^\/dashboard/,
        /^\/teams/,
        /^\/players/,
        /^\/games/,
        /^\/training/
    ],
    STATIC: [
        /\.(?:css|js|woff|woff2|ttf|eot)$/,
        /\/css\//,
        /\/js\//,
        /\/fonts\//
    ],
    API: [
        /\/api\//,
        /\/federation\//
    ],
    IMAGES: [
        /\.(?:png|jpg|jpeg|svg|gif|webp|ico)$/,
        /\/images\//,
        /\/storage\/.*\.(png|jpg|jpeg|svg|gif|webp)$/
    ],
    DOCUMENTS: [
        /\/storage\/.*\.(pdf|doc|docx|xls|xlsx)$/
    ]
};

// Basketball-specific data for offline functionality
const BASKETBALL_CACHE_KEYS = {
    GAME_STATS: 'basketball-game-stats',
    PLAYER_PROFILES: 'basketball-players',
    TEAM_ROSTERS: 'basketball-teams',
    TRAINING_DRILLS: 'basketball-training',
    MATCH_SCHEDULES: 'basketball-schedule'
};

/**
 * Service Worker Installation
 */
self.addEventListener('install', event => {
    console.log('[SW] Installing Service Worker');
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('[SW] Precaching app shell');
                return cache.addAll(PRECACHE_URLS);
            })
            .then(() => {
                console.log('[SW] Installation complete');
                return self.skipWaiting();
            })
            .catch(error => {
                console.error('[SW] Installation failed:', error);
            })
    );
});

/**
 * Service Worker Activation
 */
self.addEventListener('activate', event => {
    console.log('[SW] Activating Service Worker');
    
    event.waitUntil(
        caches.keys()
            .then(cacheNames => {
                return Promise.all(
                    cacheNames.map(cacheName => {
                        if (cacheName !== CACHE_NAME && !Object.values(BASKETBALL_CACHE_KEYS).includes(cacheName)) {
                            console.log('[SW] Deleting old cache:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(() => {
                console.log('[SW] Activation complete');
                return self.clients.claim();
            })
    );
});

/**
 * Fetch Event Handler
 */
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);
    
    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }
    
    // Skip cross-origin requests (unless for CDN assets)
    if (url.origin !== self.location.origin && !isCDNAsset(url)) {
        return;
    }
    
    // Determine cache strategy based on URL
    const strategy = getCacheStrategy(request.url);
    
    switch (strategy) {
        case CACHE_STRATEGIES.APP_SHELL:
            event.respondWith(handleAppShell(request));
            break;
        case CACHE_STRATEGIES.STATIC:
            event.respondWith(handleStatic(request));
            break;
        case CACHE_STRATEGIES.API:
            event.respondWith(handleAPI(request));
            break;
        case CACHE_STRATEGIES.IMAGES:
            event.respondWith(handleImages(request));
            break;
        case CACHE_STRATEGIES.DOCUMENTS:
            event.respondWith(handleDocuments(request));
            break;
        default:
            event.respondWith(handleDefault(request));
    }
});

/**
 * Background Sync for offline actions
 */
self.addEventListener('sync', event => {
    console.log('[SW] Background sync:', event.tag);
    
    if (event.tag === 'game-stats-sync') {
        event.waitUntil(syncGameStats());
    } else if (event.tag === 'player-data-sync') {
        event.waitUntil(syncPlayerData());
    } else if (event.tag === 'training-data-sync') {
        event.waitUntil(syncTrainingData());
    }
});

/**
 * Push notification handling
 */
self.addEventListener('push', event => {
    const options = {
        body: event.data ? event.data.text() : 'New basketball update!',
        icon: '/images/logo-192.png',
        badge: '/images/badge-72.png',
        vibrate: [100, 50, 100],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: '1'
        },
        actions: [
            {
                action: 'explore',
                title: 'View Details',
                icon: '/images/checkmark.png'
            },
            {
                action: 'close',
                title: 'Close notification',
                icon: '/images/xmark.png'
            }
        ]
    };
    
    event.waitUntil(
        self.registration.showNotification('BasketManager Pro', options)
    );
});

/**
 * Cache Strategy Handlers
 */

// App Shell: Cache first with fast network fallback
async function handleAppShell(request) {
    try {
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        const networkResponse = await fetch(request);
        const cache = await caches.open(CACHE_NAME);
        cache.put(request, networkResponse.clone());
        return networkResponse;
    } catch (error) {
        console.log('[SW] App shell fallback to offline page');
        return caches.match(OFFLINE_PAGE);
    }
}

// Static assets: Cache first with network fallback
async function handleStatic(request) {
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
        return cachedResponse;
    }
    
    try {
        const networkResponse = await fetch(request);
        const cache = await caches.open(CACHE_NAME);
        cache.put(request, networkResponse.clone());
        return networkResponse;
    } catch (error) {
        console.log('[SW] Static asset not available offline:', request.url);
        throw error;
    }
}

// API: Network first with cache fallback for basketball data
async function handleAPI(request) {
    const url = new URL(request.url);
    
    try {
        const networkResponse = await fetch(request);
        
        // Cache successful basketball API responses
        if (networkResponse.status === 200 && isBasketballData(url)) {
            const cache = await caches.open(getBasketballCacheKey(url));
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        console.log('[SW] API request failed, checking cache:', request.url);
        
        // For basketball data, try basketball-specific cache first
        if (isBasketballData(url)) {
            const basketballCache = await caches.open(getBasketballCacheKey(url));
            const cachedResponse = await basketballCache.match(request);
            if (cachedResponse) {
                return cachedResponse;
            }
        }
        
        // Fallback to main cache
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Return offline API response
        return new Response(
            JSON.stringify({
                error: 'offline',
                message: 'This data is not available offline',
                cached_data: await getOfflineBasketballData(url)
            }),
            {
                status: 503,
                headers: { 'Content-Type': 'application/json' }
            }
        );
    }
}

// Images: Cache first with network fallback
async function handleImages(request) {
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
        return cachedResponse;
    }
    
    try {
        const networkResponse = await fetch(request);
        const cache = await caches.open(CACHE_NAME);
        cache.put(request, networkResponse.clone());
        return networkResponse;
    } catch (error) {
        console.log('[SW] Image not available, using fallback');
        return caches.match(FALLBACK_IMAGE);
    }
}

// Documents: Network first (no offline fallback for documents)
async function handleDocuments(request) {
    try {
        const networkResponse = await fetch(request);
        return networkResponse;
    } catch (error) {
        return new Response('Document not available offline', {
            status: 503,
            headers: { 'Content-Type': 'text/plain' }
        });
    }
}

// Default handler
async function handleDefault(request) {
    try {
        return await fetch(request);
    } catch (error) {
        const cachedResponse = await caches.match(request);
        return cachedResponse || caches.match(OFFLINE_PAGE);
    }
}

/**
 * Helper Functions
 */

function getCacheStrategy(url) {
    // Check URL patterns to determine strategy
    for (const [strategy, patterns] of Object.entries(URL_PATTERNS)) {
        for (const pattern of patterns) {
            if (pattern.test(url)) {
                return CACHE_STRATEGIES[strategy];
            }
        }
    }
    return CACHE_STRATEGIES.STATIC; // Default strategy
}

function isCDNAsset(url) {
    const cdnDomains = ['cdn.jsdelivr.net', 'unpkg.com', 'fonts.googleapis.com', 'fonts.gstatic.com'];
    return cdnDomains.some(domain => url.hostname.includes(domain));
}

function isBasketballData(url) {
    const basketballPaths = ['/api/games', '/api/players', '/api/teams', '/api/training', '/api/stats'];
    return basketballPaths.some(path => url.pathname.startsWith(path));
}

function getBasketballCacheKey(url) {
    if (url.pathname.startsWith('/api/games')) return BASKETBALL_CACHE_KEYS.GAME_STATS;
    if (url.pathname.startsWith('/api/players')) return BASKETBALL_CACHE_KEYS.PLAYER_PROFILES;
    if (url.pathname.startsWith('/api/teams')) return BASKETBALL_CACHE_KEYS.TEAM_ROSTERS;
    if (url.pathname.startsWith('/api/training')) return BASKETBALL_CACHE_KEYS.TRAINING_DRILLS;
    if (url.pathname.startsWith('/api/schedule')) return BASKETBALL_CACHE_KEYS.MATCH_SCHEDULES;
    return CACHE_NAME;
}

async function getOfflineBasketballData(url) {
    // Return basic offline data structure for basketball entities
    if (url.pathname.includes('/players')) {
        return { players: [], offline: true };
    } else if (url.pathname.includes('/games')) {
        return { games: [], stats: [], offline: true };
    } else if (url.pathname.includes('/teams')) {
        return { teams: [], rosters: [], offline: true };
    } else if (url.pathname.includes('/training')) {
        return { drills: [], sessions: [], offline: true };
    }
    return { data: null, offline: true };
}

/**
 * Background Sync Functions
 */

async function syncGameStats() {
    try {
        // Get pending game stats from IndexedDB
        const pendingStats = await getPendingGameStats();
        
        for (const stat of pendingStats) {
            try {
                const response = await fetch('/api/games/stats', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(stat)
                });
                
                if (response.ok) {
                    await removePendingGameStat(stat.id);
                    console.log('[SW] Game stat synced:', stat.id);
                }
            } catch (error) {
                console.error('[SW] Failed to sync game stat:', error);
            }
        }
    } catch (error) {
        console.error('[SW] Game stats sync failed:', error);
    }
}

async function syncPlayerData() {
    try {
        const pendingUpdates = await getPendingPlayerUpdates();
        
        for (const update of pendingUpdates) {
            try {
                const response = await fetch(`/api/players/${update.playerId}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(update.data)
                });
                
                if (response.ok) {
                    await removePendingPlayerUpdate(update.id);
                    console.log('[SW] Player data synced:', update.playerId);
                }
            } catch (error) {
                console.error('[SW] Failed to sync player data:', error);
            }
        }
    } catch (error) {
        console.error('[SW] Player data sync failed:', error);
    }
}

async function syncTrainingData() {
    try {
        const pendingTraining = await getPendingTrainingData();
        
        for (const training of pendingTraining) {
            try {
                const response = await fetch('/api/training', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(training)
                });
                
                if (response.ok) {
                    await removePendingTrainingData(training.id);
                    console.log('[SW] Training data synced:', training.id);
                }
            } catch (error) {
                console.error('[SW] Failed to sync training data:', error);
            }
        }
    } catch (error) {
        console.error('[SW] Training data sync failed:', error);
    }
}

/**
 * IndexedDB helper functions (simplified - would need full implementation)
 */

async function getPendingGameStats() {
    // Implementation would use IndexedDB to get pending stats
    return [];
}

async function removePendingGameStat(id) {
    // Implementation would remove synced stat from IndexedDB
}

async function getPendingPlayerUpdates() {
    // Implementation would use IndexedDB to get pending player updates
    return [];
}

async function removePendingPlayerUpdate(id) {
    // Implementation would remove synced update from IndexedDB
}

async function getPendingTrainingData() {
    // Implementation would use IndexedDB to get pending training data
    return [];
}

async function removePendingTrainingData(id) {
    // Implementation would remove synced training data from IndexedDB
}

/**
 * Message handling for client communication
 */
self.addEventListener('message', event => {
    const { type, payload } = event.data;
    
    switch (type) {
        case 'SKIP_WAITING':
            self.skipWaiting();
            break;
        case 'CACHE_BASKETBALL_DATA':
            cacheBasketballData(payload);
            break;
        case 'CLEAR_CACHE':
            clearCache(payload.cacheKey);
            break;
        case 'GET_CACHE_SIZE':
            getCacheSize().then(size => {
                event.ports[0].postMessage({ size });
            });
            break;
    }
});

async function cacheBasketballData(data) {
    const cache = await caches.open(BASKETBALL_CACHE_KEYS[data.type]);
    await cache.put(data.url, new Response(JSON.stringify(data.payload)));
}

async function clearCache(cacheKey) {
    if (cacheKey) {
        await caches.delete(cacheKey);
    } else {
        const cacheNames = await caches.keys();
        await Promise.all(cacheNames.map(name => caches.delete(name)));
    }
}

async function getCacheSize() {
    const cacheNames = await caches.keys();
    let totalSize = 0;
    
    for (const cacheName of cacheNames) {
        const cache = await caches.open(cacheName);
        const keys = await cache.keys();
        
        for (const key of keys) {
            const response = await cache.match(key);
            if (response) {
                const blob = await response.blob();
                totalSize += blob.size;
            }
        }
    }
    
    return totalSize;
}

console.log('[SW] Service Worker loaded successfully');