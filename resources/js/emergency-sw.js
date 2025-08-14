// Emergency PWA Service Worker
// Handles offline caching, background sync, and push notifications for emergency access

const CACHE_NAME = 'basketball-emergency-v1.0.0';
const CACHE_EXPIRY = 24 * 60 * 60 * 1000; // 24 hours
const MAX_CACHE_SIZE = 50; // Maximum number of items in cache

// Critical resources to cache immediately
const CRITICAL_RESOURCES = [
    '/',
    '/css/app.css',
    '/js/app.js',
    '/images/emergency-192.png',
    '/images/emergency-512.png'
];

// Emergency numbers that should always be available
const EMERGENCY_NUMBERS = {
    ambulance: '112',
    fire: '112',
    police: '110'
};

// IndexedDB database for offline data
let emergencyDB;

/**
 * Service Worker Installation
 * Caches critical resources and sets up offline capabilities
 */
self.addEventListener('install', event => {
    console.log('[SW] Installing emergency service worker');
    
    event.waitUntil(
        Promise.all([
            caches.open(CACHE_NAME).then(cache => {
                console.log('[SW] Caching critical resources');
                return cache.addAll(CRITICAL_RESOURCES.map(url => new Request(url, {cache: 'reload'})));
            }),
            initializeDatabase()
        ])
    );
    
    // Force activation of new service worker
    self.skipWaiting();
});

/**
 * Service Worker Activation
 * Clean up old caches and claim all clients
 */
self.addEventListener('activate', event => {
    console.log('[SW] Activating emergency service worker');
    
    event.waitUntil(
        Promise.all([
            // Clean up old caches
            caches.keys().then(cacheNames => {
                return Promise.all(
                    cacheNames
                        .filter(cacheName => cacheName.startsWith('basketball-emergency-') && cacheName !== CACHE_NAME)
                        .map(cacheName => {
                            console.log('[SW] Deleting old cache:', cacheName);
                            return caches.delete(cacheName);
                        })
                );
            }),
            // Claim all clients immediately
            self.clients.claim()
        ])
    );
});

/**
 * Fetch Event Handler
 * Implements caching strategies for different types of requests
 */
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);
    
    // Handle emergency-specific requests
    if (url.pathname.includes('/emergency/')) {
        event.respondWith(handleEmergencyRequest(event.request));
    }
    // Handle API requests
    else if (url.pathname.startsWith('/api/')) {
        event.respondWith(handleAPIRequest(event.request));
    }
    // Handle static assets
    else if (isStaticAsset(event.request)) {
        event.respondWith(handleStaticAsset(event.request));
    }
    // Handle navigation requests
    else if (event.request.mode === 'navigate') {
        event.respondWith(handleNavigationRequest(event.request));
    }
    // Default: network first with fallback
    else {
        event.respondWith(handleDefaultRequest(event.request));
    }
});

/**
 * Background Sync Handler
 * Syncs offline data when connection is restored
 */
self.addEventListener('sync', event => {
    console.log('[SW] Background sync triggered:', event.tag);
    
    switch (event.tag) {
        case 'emergency-incident-sync':
            event.waitUntil(syncEmergencyIncidents());
            break;
        case 'contact-usage-sync':
            event.waitUntil(syncContactUsage());
            break;
        case 'emergency-logs-sync':
            event.waitUntil(syncEmergencyLogs());
            break;
        default:
            console.log('[SW] Unknown sync tag:', event.tag);
    }
});

/**
 * Push Notification Handler
 * Shows emergency notifications even when app is closed
 */
self.addEventListener('push', event => {
    console.log('[SW] Push notification received');
    
    let notificationData = {
        title: 'Emergency Alert',
        body: 'Emergency notification received',
        icon: '/images/emergency-192.png',
        badge: '/images/emergency-badge.png',
        tag: 'emergency',
        requireInteraction: true,
        silent: false,
        vibrate: [200, 100, 200, 100, 200]
    };
    
    if (event.data) {
        try {
            const data = event.data.json();
            notificationData = { ...notificationData, ...data };
        } catch (e) {
            console.error('[SW] Error parsing push data:', e);
        }
    }
    
    event.waitUntil(
        self.registration.showNotification(notificationData.title, notificationData)
    );
});

/**
 * Notification Click Handler
 * Handles what happens when user clicks on emergency notifications
 */
self.addEventListener('notificationclick', event => {
    console.log('[SW] Notification clicked:', event.notification.tag);
    
    event.notification.close();
    
    event.waitUntil(
        self.clients.matchAll().then(clients => {
            // If a client is already open, focus it
            if (clients.length > 0) {
                return clients[0].focus();
            }
            
            // Otherwise, open a new client
            let targetUrl = '/';
            if (event.notification.data && event.notification.data.url) {
                targetUrl = event.notification.data.url;
            }
            
            return self.clients.openWindow(targetUrl);
        })
    );
});

/**
 * Message Handler
 * Handles messages from the main application
 */
self.addEventListener('message', event => {
    console.log('[SW] Message received:', event.data);
    
    switch (event.data.type) {
        case 'CACHE_EMERGENCY_DATA':
            event.waitUntil(cacheEmergencyData(event.data.payload));
            break;
        case 'CLEAR_CACHE':
            event.waitUntil(clearAllCaches());
            break;
        case 'GET_CACHE_STATUS':
            event.waitUntil(getCacheStatus().then(status => {
                event.ports[0].postMessage(status);
            }));
            break;
        case 'REGISTER_BACKGROUND_SYNC':
            event.waitUntil(registerBackgroundSync(event.data.tag));
            break;
        default:
            console.log('[SW] Unknown message type:', event.data.type);
    }
});

// Request Handlers

/**
 * Handle emergency-specific requests with cache-first strategy
 */
async function handleEmergencyRequest(request) {
    try {
        // For emergency contacts, try cache first (critical for offline access)
        if (request.url.includes('/emergency/contacts/') || request.url.includes('/emergency/pwa/')) {
            const cachedResponse = await caches.match(request);
            if (cachedResponse) {
                return cachedResponse;
            }
        }
        
        // Try network request
        const networkResponse = await fetch(request.clone());
        
        if (networkResponse.ok) {
            // Cache successful responses
            const cache = await caches.open(CACHE_NAME);
            await cache.put(request.clone(), networkResponse.clone());
            return networkResponse;
        }
        
        throw new Error(`Network request failed: ${networkResponse.status}`);
        
    } catch (error) {
        console.warn('[SW] Emergency request failed:', error);
        
        // Return cached response if available
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Return offline fallback with emergency numbers
        return new Response(JSON.stringify({
            error: 'Offline',
            emergency_numbers: EMERGENCY_NUMBERS,
            message: 'Sie sind offline. Notfallnummern sind weiterhin verfügbar.',
            cached_at: new Date().toISOString()
        }), {
            headers: { 
                'Content-Type': 'application/json',
                'SW-Fallback': 'true'
            },
            status: 503
        });
    }
}

/**
 * Handle API requests with network-first, cache-fallback strategy
 */
async function handleAPIRequest(request) {
    try {
        const networkResponse = await fetch(request.clone());
        
        if (networkResponse.ok) {
            // Cache GET requests
            if (request.method === 'GET') {
                const cache = await caches.open(CACHE_NAME);
                await cache.put(request.clone(), networkResponse.clone());
            }
            
            return networkResponse;
        }
        
        throw new Error(`API request failed: ${networkResponse.status}`);
        
    } catch (error) {
        // For POST requests (like incident reports), queue for background sync
        if (request.method === 'POST') {
            await queueRequestForSync(request);
            
            return new Response(JSON.stringify({
                success: false,
                queued: true,
                message: 'Request queued for sync when online'
            }), {
                headers: { 'Content-Type': 'application/json' },
                status: 202
            });
        }
        
        // For GET requests, try cache
        if (request.method === 'GET') {
            const cachedResponse = await caches.match(request);
            if (cachedResponse) {
                return cachedResponse;
            }
        }
        
        return new Response(JSON.stringify({
            error: 'Network unavailable',
            message: 'No network connection and no cached data available'
        }), {
            headers: { 'Content-Type': 'application/json' },
            status: 503
        });
    }
}

/**
 * Handle static assets with cache-first strategy
 */
async function handleStaticAsset(request) {
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
        return cachedResponse;
    }
    
    try {
        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            const cache = await caches.open(CACHE_NAME);
            await cache.put(request.clone(), networkResponse.clone());
        }
        return networkResponse;
    } catch (error) {
        // Return a fallback for critical assets
        if (request.url.includes('emergency')) {
            return new Response('/* Emergency CSS fallback */', {
                headers: { 'Content-Type': 'text/css' }
            });
        }
        throw error;
    }
}

/**
 * Handle navigation requests
 */
async function handleNavigationRequest(request) {
    try {
        return await fetch(request);
    } catch (error) {
        // Return offline page for navigation failures
        const cache = await caches.open(CACHE_NAME);
        const offlinePage = await cache.match('/emergency/offline');
        return offlinePage || new Response('Offline - Emergency numbers: 112, 110', {
            headers: { 'Content-Type': 'text/html' }
        });
    }
}

/**
 * Default request handler
 */
async function handleDefaultRequest(request) {
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok && request.method === 'GET') {
            const cache = await caches.open(CACHE_NAME);
            await cache.put(request.clone(), networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        throw error;
    }
}

// Utility Functions

/**
 * Check if request is for a static asset
 */
function isStaticAsset(request) {
    const url = new URL(request.url);
    const staticExtensions = ['.css', '.js', '.png', '.jpg', '.jpeg', '.gif', '.svg', '.woff', '.woff2'];
    return staticExtensions.some(ext => url.pathname.endsWith(ext));
}

/**
 * Initialize IndexedDB for offline data storage
 */
async function initializeDatabase() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('EmergencyDB', 1);
        
        request.onerror = () => reject(request.error);
        request.onsuccess = () => {
            emergencyDB = request.result;
            resolve();
        };
        
        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            
            // Create object stores
            if (!db.objectStoreNames.contains('incident_reports')) {
                const incidentStore = db.createObjectStore('incident_reports', { keyPath: 'id', autoIncrement: true });
                incidentStore.createIndex('timestamp', 'timestamp', { unique: false });
                incidentStore.createIndex('access_key', 'access_key', { unique: false });
            }
            
            if (!db.objectStoreNames.contains('contact_usage')) {
                const usageStore = db.createObjectStore('contact_usage', { keyPath: 'id', autoIncrement: true });
                usageStore.createIndex('timestamp', 'timestamp', { unique: false });
                usageStore.createIndex('contact_id', 'contact_id', { unique: false });
            }
            
            if (!db.objectStoreNames.contains('emergency_logs')) {
                const logStore = db.createObjectStore('emergency_logs', { keyPath: 'id', autoIncrement: true });
                logStore.createIndex('timestamp', 'timestamp', { unique: false });
                logStore.createIndex('type', 'type', { unique: false });
            }
        };
    });
}

/**
 * Cache emergency data for offline access
 */
async function cacheEmergencyData(data) {
    try {
        const cache = await caches.open(CACHE_NAME);
        const response = new Response(JSON.stringify(data), {
            headers: { 
                'Content-Type': 'application/json',
                'SW-Cache-Time': new Date().toISOString()
            }
        });
        
        await cache.put('/emergency/cached-data', response);
        console.log('[SW] Emergency data cached successfully');
    } catch (error) {
        console.error('[SW] Failed to cache emergency data:', error);
    }
}

/**
 * Queue request for background sync
 */
async function queueRequestForSync(request) {
    if (!emergencyDB) {
        await initializeDatabase();
    }
    
    try {
        const requestData = {
            url: request.url,
            method: request.method,
            headers: {},
            body: null,
            timestamp: new Date().toISOString()
        };
        
        // Copy headers
        for (const [key, value] of request.headers.entries()) {
            requestData.headers[key] = value;
        }
        
        // Copy body for POST requests
        if (request.method === 'POST') {
            requestData.body = await request.text();
        }
        
        const transaction = emergencyDB.transaction(['emergency_logs'], 'readwrite');
        const store = transaction.objectStore('emergency_logs');
        await store.add({ type: 'queued_request', data: requestData, timestamp: new Date().toISOString() });
        
        // Register for background sync
        await self.registration.sync.register('emergency-logs-sync');
        
        console.log('[SW] Request queued for sync:', request.url);
    } catch (error) {
        console.error('[SW] Failed to queue request:', error);
    }
}

/**
 * Sync emergency incidents when back online
 */
async function syncEmergencyIncidents() {
    if (!emergencyDB) {
        await initializeDatabase();
    }
    
    try {
        const transaction = emergencyDB.transaction(['incident_reports'], 'readonly');
        const store = transaction.objectStore('incident_reports');
        const incidents = await getAllFromStore(store);
        
        for (const incident of incidents) {
            try {
                const response = await fetch('/api/emergency/incidents', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(incident)
                });
                
                if (response.ok) {
                    // Remove synced incident
                    const deleteTransaction = emergencyDB.transaction(['incident_reports'], 'readwrite');
                    const deleteStore = deleteTransaction.objectStore('incident_reports');
                    await deleteStore.delete(incident.id);
                    console.log('[SW] Incident synced and removed:', incident.id);
                }
            } catch (error) {
                console.error('[SW] Failed to sync incident:', incident.id, error);
            }
        }
    } catch (error) {
        console.error('[SW] Failed to sync emergency incidents:', error);
    }
}

/**
 * Sync contact usage data
 */
async function syncContactUsage() {
    if (!emergencyDB) {
        await initializeDatabase();
    }
    
    try {
        const transaction = emergencyDB.transaction(['contact_usage'], 'readonly');
        const store = transaction.objectStore('contact_usage');
        const usageData = await getAllFromStore(store);
        
        for (const usage of usageData) {
            try {
                const response = await fetch('/api/emergency/contact-accessed', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(usage)
                });
                
                if (response.ok) {
                    // Remove synced usage data
                    const deleteTransaction = emergencyDB.transaction(['contact_usage'], 'readwrite');
                    const deleteStore = deleteTransaction.objectStore('contact_usage');
                    await deleteStore.delete(usage.id);
                    console.log('[SW] Usage data synced and removed:', usage.id);
                }
            } catch (error) {
                console.error('[SW] Failed to sync usage data:', usage.id, error);
            }
        }
    } catch (error) {
        console.error('[SW] Failed to sync contact usage:', error);
    }
}

/**
 * Sync emergency logs
 */
async function syncEmergencyLogs() {
    if (!emergencyDB) {
        await initializeDatabase();
    }
    
    try {
        const transaction = emergencyDB.transaction(['emergency_logs'], 'readonly');
        const store = transaction.objectStore('emergency_logs');
        const logs = await getAllFromStore(store);
        
        for (const log of logs) {
            try {
                if (log.type === 'queued_request') {
                    // Replay queued request
                    const requestData = log.data;
                    const response = await fetch(requestData.url, {
                        method: requestData.method,
                        headers: requestData.headers,
                        body: requestData.body
                    });
                    
                    if (response.ok) {
                        // Remove synced log
                        const deleteTransaction = emergencyDB.transaction(['emergency_logs'], 'readwrite');
                        const deleteStore = deleteTransaction.objectStore('emergency_logs');
                        await deleteStore.delete(log.id);
                        console.log('[SW] Queued request synced:', requestData.url);
                    }
                }
            } catch (error) {
                console.error('[SW] Failed to sync log:', log.id, error);
            }
        }
    } catch (error) {
        console.error('[SW] Failed to sync emergency logs:', error);
    }
}

/**
 * Helper function to get all items from an IndexedDB store
 */
function getAllFromStore(store) {
    return new Promise((resolve, reject) => {
        const request = store.getAll();
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

/**
 * Register background sync
 */
async function registerBackgroundSync(tag) {
    try {
        await self.registration.sync.register(tag);
        console.log('[SW] Background sync registered:', tag);
    } catch (error) {
        console.error('[SW] Background sync registration failed:', error);
    }
}

/**
 * Clear all caches
 */
async function clearAllCaches() {
    try {
        const cacheNames = await caches.keys();
        await Promise.all(
            cacheNames
                .filter(name => name.startsWith('basketball-emergency-'))
                .map(name => caches.delete(name))
        );
        console.log('[SW] All caches cleared');
    } catch (error) {
        console.error('[SW] Failed to clear caches:', error);
    }
}

/**
 * Get cache status information
 */
async function getCacheStatus() {
    try {
        const cache = await caches.open(CACHE_NAME);
        const keys = await cache.keys();
        
        return {
            cacheName: CACHE_NAME,
            cacheSize: keys.length,
            lastUpdated: new Date().toISOString(),
            isOnline: self.navigator.onLine
        };
    } catch (error) {
        console.error('[SW] Failed to get cache status:', error);
        return {
            cacheName: CACHE_NAME,
            cacheSize: 0,
            lastUpdated: null,
            isOnline: false,
            error: error.message
        };
    }
}

// Log service worker lifecycle events
console.log('[SW] Emergency Service Worker loaded');