/**
 * Gym Management Service Worker
 * Handles caching, offline functionality, and background sync for gym management features
 */

const CACHE_NAME = 'basketmanager-gym-v1'
const GYM_DATA_CACHE = 'gym-data-v1'
const OFFLINE_PAGE = '/offline'

// Files to cache for gym functionality
const GYM_ASSETS = [
    '/gym/dashboard',
    '/gym/my-bookings',
    '/gym/available-times',
    '/css/app.css',
    '/js/app.js',
    '/images/logo-192.png',
    '/images/shortcuts/gym.png',
    '/images/shortcuts/bookings.png',
    '/images/shortcuts/available.png',
    '/images/notifications/gym-released.png',
    '/images/notifications/booking-request.png',
    '/images/notifications/booking-approved.png',
    '/images/notifications/booking-rejected.png',
    '/images/notifications/reminder.png',
    OFFLINE_PAGE
]

// API endpoints that should be cached
const CACHEABLE_APIS = [
    '/api/v2/gym-halls',
    '/api/v2/gym-time-slots',
    '/api/v2/gym-bookings',
    '/api/v2/gym-management/stats'
]

// Install event - cache essential assets
self.addEventListener('install', (event) => {
    console.log('[Gym SW] Installing...')
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[Gym SW] Caching gym assets')
                return cache.addAll(GYM_ASSETS)
            })
            .then(() => {
                console.log('[Gym SW] Assets cached, skipping waiting')
                return self.skipWaiting()
            })
            .catch((error) => {
                console.error('[Gym SW] Failed to cache assets:', error)
            })
    )
})

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    console.log('[Gym SW] Activating...')
    
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME && cacheName !== GYM_DATA_CACHE) {
                        console.log('[Gym SW] Deleting old cache:', cacheName)
                        return caches.delete(cacheName)
                    }
                })
            )
        }).then(() => {
            console.log('[Gym SW] Claiming clients')
            return self.clients.claim()
        })
    )
})

// Fetch event - handle network requests
self.addEventListener('fetch', (event) => {
    const request = event.request
    const url = new URL(request.url)

    // Skip non-GET requests
    if (request.method !== 'GET') {
        return
    }

    // Handle gym-related requests
    if (url.pathname.startsWith('/gym/')) {
        event.respondWith(handleGymRequest(request))
        return
    }

    // Handle API requests
    if (url.pathname.startsWith('/api/v2/gym')) {
        event.respondWith(handleGymApiRequest(request))
        return
    }

    // Handle other requests with cache-first strategy
    event.respondWith(
        caches.match(request)
            .then((response) => {
                return response || fetch(request)
            })
            .catch(() => {
                // If offline and requesting a page, return offline page
                if (request.headers.get('accept').includes('text/html')) {
                    return caches.match(OFFLINE_PAGE)
                }
            })
    )
})

// Handle gym page requests
async function handleGymRequest(request) {
    try {
        // Try network first for gym pages to get fresh data
        const networkResponse = await fetch(request)
        
        // Cache successful responses
        if (networkResponse.status === 200) {
            const cache = await caches.open(CACHE_NAME)
            cache.put(request, networkResponse.clone())
        }
        
        return networkResponse
    } catch (error) {
        console.log('[Gym SW] Network failed, trying cache:', request.url)
        
        // Try cache if network fails
        const cachedResponse = await caches.match(request)
        if (cachedResponse) {
            return cachedResponse
        }
        
        // Return offline page if nothing else works
        return caches.match(OFFLINE_PAGE)
    }
}

// Handle gym API requests
async function handleGymApiRequest(request) {
    const url = new URL(request.url)
    
    try {
        // Always try network first for API requests
        const networkResponse = await fetch(request)
        
        // Cache successful responses for specific endpoints
        if (networkResponse.status === 200 && shouldCacheApi(url.pathname)) {
            const cache = await caches.open(GYM_DATA_CACHE)
            
            // Clone and cache the response
            const responseClone = networkResponse.clone()
            cache.put(request, responseClone)
            
            // Set expiration timestamp
            const now = Date.now()
            const expiry = now + (5 * 60 * 1000) // 5 minutes
            cache.put(`${request.url}:expiry`, new Response(expiry.toString()))
        }
        
        return networkResponse
    } catch (error) {
        console.log('[Gym SW] API network failed, checking cache:', request.url)
        
        // Try to get cached response
        const cache = await caches.open(GYM_DATA_CACHE)
        const cachedResponse = await cache.match(request)
        
        if (cachedResponse) {
            // Check if cache is still valid
            const expiryResponse = await cache.match(`${request.url}:expiry`)
            if (expiryResponse) {
                const expiry = parseInt(await expiryResponse.text())
                if (Date.now() < expiry) {
                    console.log('[Gym SW] Serving fresh cached data')
                    return cachedResponse
                }
            }
            
            // Return stale data with warning header
            const staleResponse = cachedResponse.clone()
            staleResponse.headers.set('SW-Cache-Status', 'stale')
            console.log('[Gym SW] Serving stale cached data')
            return staleResponse
        }
        
        // Return error response
        return new Response(JSON.stringify({
            error: 'Network unavailable',
            message: 'Unable to fetch data. Please check your connection.',
            cached: false
        }), {
            status: 503,
            headers: {
                'Content-Type': 'application/json'
            }
        })
    }
}

// Check if API endpoint should be cached
function shouldCacheApi(pathname) {
    return CACHEABLE_APIS.some(api => pathname.startsWith(api))
}

// Handle push notifications
self.addEventListener('push', (event) => {
    console.log('[Gym SW] Push notification received')
    
    if (!event.data) {
        return
    }
    
    const data = event.data.json()
    console.log('[Gym SW] Push data:', data)
    
    // Handle different notification types
    const options = {
        body: data.body,
        icon: data.icon || '/images/logo-192.png',
        badge: data.badge || '/images/badge-gym.png',
        image: data.image,
        vibrate: data.vibrate || [200, 100, 200],
        data: data.data,
        actions: data.data?.actions || [],
        requireInteraction: data.requireInteraction || false,
        silent: data.silent || false,
        timestamp: Date.now()
    }
    
    event.waitUntil(
        self.registration.showNotification(data.title, options)
    )
})

// Handle notification clicks
self.addEventListener('notificationclick', (event) => {
    console.log('[Gym SW] Notification clicked:', event)
    
    event.notification.close()
    
    const data = event.notification.data
    let url = '/'
    
    // Handle action clicks
    if (event.action) {
        url = handleNotificationAction(event.action, data)
    } else if (data && data.url) {
        url = data.url
    }
    
    // Open or focus window
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                // Try to focus existing window
                for (let client of clientList) {
                    if (client.url.includes(url.split('?')[0]) && 'focus' in client) {
                        return client.focus()
                    }
                }
                
                // Open new window
                if (clients.openWindow) {
                    return clients.openWindow(url)
                }
            })
    )
})

// Handle notification action clicks
function handleNotificationAction(action, data) {
    switch (action) {
        case 'view-available':
            return '/gym/bookings/available'
        case 'request-booking':
            return `/gym/bookings/request?booking_id=${data.booking_id}`
        case 'approve-request':
            return `/gym/requests?action=approve&request_id=${data.request_id}`
        case 'reject-request':
            return `/gym/requests?action=reject&request_id=${data.request_id}`
        case 'view-hall':
            return `/gym/halls/${data.gym_hall_id}`
        case 'start-session':
            return `/training/start?booking_id=${data.booking_id}`
        default:
            return data.url || '/gym/dashboard'
    }
}

// Handle background sync
self.addEventListener('sync', (event) => {
    console.log('[Gym SW] Background sync triggered:', event.tag)
    
    if (event.tag === 'gym-booking-request') {
        event.waitUntil(syncBookingRequests())
    } else if (event.tag === 'gym-data-sync') {
        event.waitUntil(syncGymData())
    }
})

// Sync pending booking requests
async function syncBookingRequests() {
    try {
        console.log('[Gym SW] Syncing booking requests...')
        
        // Get pending requests from IndexedDB
        const pendingRequests = await getPendingRequests()
        
        for (const request of pendingRequests) {
            try {
                const response = await fetch('/api/v2/gym-booking-requests', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(request.data)
                })
                
                if (response.ok) {
                    // Remove from pending requests
                    await removePendingRequest(request.id)
                    console.log('[Gym SW] Booking request synced:', request.id)
                }
            } catch (error) {
                console.error('[Gym SW] Failed to sync booking request:', error)
            }
        }
    } catch (error) {
        console.error('[Gym SW] Background sync failed:', error)
    }
}

// Sync gym data
async function syncGymData() {
    try {
        console.log('[Gym SW] Syncing gym data...')
        
        // Refresh cached gym data
        const cache = await caches.open(GYM_DATA_CACHE)
        
        // Clear old data
        const keys = await cache.keys()
        await Promise.all(keys.map(key => cache.delete(key)))
        
        // Fetch fresh data
        const endpoints = [
            '/api/v2/gym-halls',
            '/api/v2/gym-management/stats',
            '/api/v2/gym-bookings?status=upcoming'
        ]
        
        for (const endpoint of endpoints) {
            try {
                const response = await fetch(endpoint)
                if (response.ok) {
                    await cache.put(endpoint, response)
                }
            } catch (error) {
                console.error(`[Gym SW] Failed to sync ${endpoint}:`, error)
            }
        }
        
        console.log('[Gym SW] Gym data sync complete')
    } catch (error) {
        console.error('[Gym SW] Gym data sync failed:', error)
    }
}

// IndexedDB helpers for offline storage
function openDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('BasketManagerGym', 1)
        
        request.onerror = () => reject(request.error)
        request.onsuccess = () => resolve(request.result)
        
        request.onupgradeneeded = (event) => {
            const db = event.target.result
            
            // Create stores
            if (!db.objectStoreNames.contains('pendingRequests')) {
                db.createObjectStore('pendingRequests', { keyPath: 'id' })
            }
            
            if (!db.objectStoreNames.contains('offlineBookings')) {
                db.createObjectStore('offlineBookings', { keyPath: 'id' })
            }
        }
    })
}

async function getPendingRequests() {
    try {
        const db = await openDB()
        const transaction = db.transaction(['pendingRequests'], 'readonly')
        const store = transaction.objectStore('pendingRequests')
        
        return new Promise((resolve, reject) => {
            const request = store.getAll()
            request.onsuccess = () => resolve(request.result)
            request.onerror = () => reject(request.error)
        })
    } catch (error) {
        console.error('[Gym SW] Failed to get pending requests:', error)
        return []
    }
}

async function removePendingRequest(id) {
    try {
        const db = await openDB()
        const transaction = db.transaction(['pendingRequests'], 'readwrite')
        const store = transaction.objectStore('pendingRequests')
        
        return new Promise((resolve, reject) => {
            const request = store.delete(id)
            request.onsuccess = () => resolve()
            request.onerror = () => reject(request.error)
        })
    } catch (error) {
        console.error('[Gym SW] Failed to remove pending request:', error)
    }
}

// Handle service worker messages
self.addEventListener('message', (event) => {
    console.log('[Gym SW] Message received:', event.data)
    
    if (event.data && event.data.type) {
        switch (event.data.type) {
            case 'SKIP_WAITING':
                self.skipWaiting()
                break
            case 'CACHE_GYM_DATA':
                event.waitUntil(cacheGymData(event.data.data))
                break
            case 'CLEAR_CACHE':
                event.waitUntil(clearGymCache())
                break
        }
    }
})

async function cacheGymData(data) {
    try {
        const cache = await caches.open(GYM_DATA_CACHE)
        
        for (const [url, responseData] of Object.entries(data)) {
            const response = new Response(JSON.stringify(responseData), {
                headers: { 'Content-Type': 'application/json' }
            })
            await cache.put(url, response)
        }
        
        console.log('[Gym SW] Gym data cached')
    } catch (error) {
        console.error('[Gym SW] Failed to cache gym data:', error)
    }
}

async function clearGymCache() {
    try {
        await caches.delete(GYM_DATA_CACHE)
        console.log('[Gym SW] Gym cache cleared')
    } catch (error) {
        console.error('[Gym SW] Failed to clear gym cache:', error)
    }
}

console.log('[Gym SW] Service worker loaded')