/**
 * BasketManager Pro PWA Manager
 * Handles service worker registration, offline functionality, and push notifications
 */

class BasketManagerPWA {
    constructor() {
        this.serviceWorker = null;
        this.isOnline = navigator.onLine;
        this.offlineQueue = [];
        this.pushSubscription = null;
        
        this.init();
    }
    
    /**
     * Initialize PWA functionality
     */
    async init() {
        console.log('[PWA] Initializing BasketManager Pro PWA');
        
        // Check PWA support
        if (!this.isPWASupported()) {
            console.warn('[PWA] PWA not supported in this browser');
            return;
        }
        
        // Register service worker
        await this.registerServiceWorker();
        
        // Setup offline/online event listeners
        this.setupConnectionEvents();
        
        // Setup push notifications
        await this.setupPushNotifications();
        
        // Setup install prompt
        this.setupInstallPrompt();
        
        // Setup basketball-specific features
        this.setupBasketballFeatures();
        
        // Setup emergency features
        this.setupEmergencyFeatures();
        
        console.log('[PWA] PWA initialization complete');
    }
    
    /**
     * Check if PWA is supported
     */
    isPWASupported() {
        return 'serviceWorker' in navigator && 
               'caches' in window && 
               'PushManager' in window;
    }
    
    /**
     * Register service worker
     */
    async registerServiceWorker() {
        if (!('serviceWorker' in navigator)) {
            console.warn('[PWA] Service Workers not supported');
            return;
        }
        
        try {
            const registration = await navigator.serviceWorker.register('/sw.js', {
                scope: '/'
            });
            
            this.serviceWorker = registration;
            
            console.log('[PWA] Service Worker registered:', registration);
            
            // Handle service worker updates
            registration.addEventListener('updatefound', () => {
                const newWorker = registration.installing;
                
                newWorker.addEventListener('statechange', () => {
                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                        this.showUpdateNotification();
                    }
                });
            });
            
            // Listen for messages from service worker
            navigator.serviceWorker.addEventListener('message', this.handleServiceWorkerMessage.bind(this));
            
        } catch (error) {
            console.error('[PWA] Service Worker registration failed:', error);
        }
    }
    
    /**
     * Setup connection event listeners
     */
    setupConnectionEvents() {
        window.addEventListener('online', () => {
            console.log('[PWA] Back online');
            this.isOnline = true;
            this.syncOfflineData();
            this.showConnectionNotification('online');
        });
        
        window.addEventListener('offline', () => {
            console.log('[PWA] Gone offline');
            this.isOnline = false;
            this.showConnectionNotification('offline');
        });
    }
    
    /**
     * Setup push notifications
     */
    async setupPushNotifications() {
        if (!('PushManager' in window)) {
            console.warn('[PWA] Push notifications not supported');
            return;
        }
        
        if (!this.serviceWorker) {
            console.warn('[PWA] Service Worker not registered');
            return;
        }
        
        try {
            // Check if already subscribed
            const existingSubscription = await this.serviceWorker.pushManager.getSubscription();
            
            if (existingSubscription) {
                this.pushSubscription = existingSubscription;
                console.log('[PWA] Already subscribed to push notifications');
            }
        } catch (error) {
            console.error('[PWA] Failed to check push subscription:', error);
        }
    }
    
    /**
     * Setup install prompt
     */
    setupInstallPrompt() {
        let deferredPrompt;
        
        window.addEventListener('beforeinstallprompt', (e) => {
            console.log('[PWA] Install prompt available');
            e.preventDefault();
            deferredPrompt = e;
            this.showInstallButton(deferredPrompt);
        });
        
        window.addEventListener('appinstalled', () => {
            console.log('[PWA] App installed');
            this.hideInstallButton();
            this.trackEvent('pwa_installed');
        });
    }
    
    /**
     * Setup basketball-specific features
     */
    setupBasketballFeatures() {
        // Game statistics offline capture
        this.setupGameStatsCapture();
        
        // Player data offline editing
        this.setupPlayerDataCapture();
        
        // Training session offline tracking
        this.setupTrainingCapture();
        
        // Live scoring offline mode
        this.setupLiveScoring();
    }
    
    /**
     * Setup game statistics capture for offline use
     */
    setupGameStatsCapture() {
        // Listen for game stat events
        document.addEventListener('game-stat-recorded', (event) => {
            const stat = event.detail;
            
            if (!this.isOnline) {
                this.queueOfflineData('game_stats', stat);
                this.showOfflineNotification('Spielstatistik offline gespeichert');
            } else {
                this.syncGameStat(stat);
            }
        });
    }
    
    /**
     * Setup player data capture for offline editing
     */
    setupPlayerDataCapture() {
        document.addEventListener('player-data-updated', (event) => {
            const playerData = event.detail;
            
            if (!this.isOnline) {
                this.queueOfflineData('player_data', playerData);
                this.showOfflineNotification('Spielerdaten offline gespeichert');
            } else {
                this.syncPlayerData(playerData);
            }
        });
    }
    
    /**
     * Setup training session capture
     */
    setupTrainingCapture() {
        document.addEventListener('training-data-recorded', (event) => {
            const trainingData = event.detail;
            
            if (!this.isOnline) {
                this.queueOfflineData('training_data', trainingData);
                this.showOfflineNotification('Trainingsdaten offline gespeichert');
            } else {
                this.syncTrainingData(trainingData);
            }
        });
    }
    
    /**
     * Setup live scoring offline mode
     */
    setupLiveScoring() {
        const liveScoringElements = document.querySelectorAll('[data-live-scoring]');
        
        liveScoringElements.forEach(element => {
            element.addEventListener('click', (e) => {
                if (!this.isOnline) {
                    e.preventDefault();
                    this.showOfflineScoring();
                }
            });
        });
    }
    
    /**
     * Queue data for offline sync
     */
    queueOfflineData(type, data) {
        const queueItem = {
            id: this.generateId(),
            type: type,
            data: data,
            timestamp: new Date().toISOString()
        };
        
        this.offlineQueue.push(queueItem);
        this.saveOfflineQueue();
        
        console.log('[PWA] Data queued for offline sync:', queueItem);
    }
    
    /**
     * Sync offline data when back online
     */
    async syncOfflineData() {
        if (this.offlineQueue.length === 0) {
            return;
        }
        
        console.log('[PWA] Syncing offline data:', this.offlineQueue.length, 'items');
        
        const itemsToSync = [...this.offlineQueue];
        
        for (const item of itemsToSync) {
            try {
                await this.syncOfflineItem(item);
                this.removeFromOfflineQueue(item.id);
            } catch (error) {
                console.error('[PWA] Failed to sync offline item:', error);
            }
        }
        
        this.saveOfflineQueue();
        
        if (itemsToSync.length > 0) {
            this.showConnectionNotification('synced', itemsToSync.length);
        }
    }
    
    /**
     * Sync individual offline item
     */
    async syncOfflineItem(item) {
        const endpoints = {
            game_stats: '/api/sync/game-stats',
            player_data: '/api/sync/player-data',
            training_data: '/api/sync/training-data',
            federation_sync: '/federation/sync'
        };
        
        const endpoint = endpoints[item.type];
        if (!endpoint) {
            throw new Error(`Unknown sync type: ${item.type}`);
        }
        
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify(item.data)
        });
        
        if (!response.ok) {
            throw new Error(`Sync failed: ${response.status}`);
        }
        
        console.log('[PWA] Offline item synced:', item.id);
    }
    
    /**
     * Subscribe to push notifications
     */
    async subscribeToPushNotifications() {
        if (!this.serviceWorker || this.pushSubscription) {
            return;
        }
        
        try {
            const permission = await Notification.requestPermission();
            
            if (permission !== 'granted') {
                throw new Error('Push notification permission denied');
            }
            
            const subscription = await this.serviceWorker.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this.urlBase64ToUint8Array(window.vapidPublicKey || '')
            });
            
            this.pushSubscription = subscription;
            
            // Send subscription to server
            await this.sendSubscriptionToServer(subscription);
            
            console.log('[PWA] Subscribed to push notifications');
            this.showNotification('Push-Benachrichtigungen aktiviert', 'success');
            
        } catch (error) {
            console.error('[PWA] Failed to subscribe to push notifications:', error);
            this.showNotification('Push-Benachrichtigungen konnten nicht aktiviert werden', 'error');
        }
    }
    
    /**
     * Send push subscription to server
     */
    async sendSubscriptionToServer(subscription) {
        const response = await fetch('/pwa/subscribe-push', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({
                endpoint: subscription.endpoint,
                keys: {
                    p256dh: this.arrayBufferToBase64(subscription.getKey('p256dh')),
                    auth: this.arrayBufferToBase64(subscription.getKey('auth'))
                }
            })
        });
        
        if (!response.ok) {
            throw new Error('Failed to send subscription to server');
        }
    }
    
    /**
     * Handle messages from service worker
     */
    handleServiceWorkerMessage(event) {
        const { type, payload } = event.data;
        
        switch (type) {
            case 'CACHE_UPDATED':
                console.log('[PWA] Cache updated:', payload);
                break;
            case 'BACKGROUND_SYNC_SUCCESS':
                console.log('[PWA] Background sync successful:', payload);
                break;
            case 'OFFLINE_READY':
                this.showNotification('App bereit für Offline-Nutzung', 'info');
                break;
        }
    }
    
    /**
     * Show update notification
     */
    showUpdateNotification() {
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification('Update verfügbar', {
                body: 'Eine neue Version von BasketManager Pro ist verfügbar',
                icon: '/images/logo-192.png',
                actions: [
                    { action: 'update', title: 'Jetzt aktualisieren' },
                    { action: 'dismiss', title: 'Später' }
                ]
            });
        } else {
            this.showNotification('Update verfügbar - Seite neu laden für neue Version', 'info');
        }
    }
    
    /**
     * Show install button
     */
    showInstallButton(deferredPrompt) {
        const installButton = document.getElementById('pwa-install-button');
        if (installButton) {
            installButton.style.display = 'block';
            installButton.onclick = async () => {
                deferredPrompt.prompt();
                const choiceResult = await deferredPrompt.userChoice;
                console.log('[PWA] Install choice:', choiceResult.outcome);
                this.trackEvent('pwa_install_prompt', { outcome: choiceResult.outcome });
            };
        }
    }
    
    /**
     * Hide install button
     */
    hideInstallButton() {
        const installButton = document.getElementById('pwa-install-button');
        if (installButton) {
            installButton.style.display = 'none';
        }
    }
    
    /**
     * Show connection notification
     */
    showConnectionNotification(status, count = 0) {
        const messages = {
            online: 'Verbindung wiederhergestellt',
            offline: 'Offline-Modus aktiv',
            synced: `${count} Element(e) synchronisiert`
        };
        
        this.showNotification(messages[status], status === 'offline' ? 'warning' : 'success');
    }
    
    /**
     * Show offline notification
     */
    showOfflineNotification(message) {
        this.showNotification(message, 'info');
    }
    
    /**
     * Show offline scoring interface
     */
    showOfflineScoring() {
        // Implementation would show offline scoring UI
        console.log('[PWA] Showing offline scoring interface');
        this.showNotification('Offline Live-Scoring aktiviert', 'info');
    }
    
    /**
     * Show notification
     */
    showNotification(message, type = 'info') {
        // Use your app's notification system
        console.log(`[PWA] ${type.toUpperCase()}: ${message}`);
        
        // Example implementation for a toast notification
        if (window.showToast) {
            window.showToast(message, type);
        } else if (window.toastr) {
            window.toastr[type](message);
        }
    }
    
    /**
     * Track PWA events
     */
    trackEvent(eventName, data = {}) {
        // Use your analytics system
        console.log('[PWA] Event:', eventName, data);
        
        if (window.gtag) {
            window.gtag('event', eventName, {
                event_category: 'PWA',
                ...data
            });
        }
    }
    
    /**
     * Utility functions
     */
    
    generateId() {
        return Date.now().toString(36) + Math.random().toString(36).substr(2);
    }
    
    saveOfflineQueue() {
        localStorage.setItem('basketmanager_offline_queue', JSON.stringify(this.offlineQueue));
    }
    
    loadOfflineQueue() {
        const saved = localStorage.getItem('basketmanager_offline_queue');
        this.offlineQueue = saved ? JSON.parse(saved) : [];
    }
    
    removeFromOfflineQueue(id) {
        this.offlineQueue = this.offlineQueue.filter(item => item.id !== id);
    }
    
    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/-/g, '+')
            .replace(/_/g, '/');
        
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }
    
    arrayBufferToBase64(buffer) {
        const bytes = new Uint8Array(buffer);
        let binary = '';
        for (let i = 0; i < bytes.byteLength; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return window.btoa(binary);
    }
    
    /**
     * Sync specific data types
     */
    
    async syncGameStat(stat) {
        try {
            const response = await fetch('/api/sync/game-stats', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify(stat)
            });
            
            if (response.ok) {
                console.log('[PWA] Game stat synced in real-time');
            }
        } catch (error) {
            console.error('[PWA] Failed to sync game stat:', error);
            this.queueOfflineData('game_stats', stat);
        }
    }
    
    async syncPlayerData(playerData) {
        try {
            const response = await fetch('/api/sync/player-data', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify(playerData)
            });
            
            if (response.ok) {
                console.log('[PWA] Player data synced in real-time');
            }
        } catch (error) {
            console.error('[PWA] Failed to sync player data:', error);
            this.queueOfflineData('player_data', playerData);
        }
    }
    
    async syncTrainingData(trainingData) {
        try {
            const response = await fetch('/api/sync/training-data', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify(trainingData)
            });
            
            if (response.ok) {
                console.log('[PWA] Training data synced in real-time');
            }
        } catch (error) {
            console.error('[PWA] Failed to sync training data:', error);
            this.queueOfflineData('training_data', trainingData);
        }
    }
    
    /**
     * Setup emergency-specific PWA features
     */
    setupEmergencyFeatures() {
        // Register emergency service worker if on emergency access page
        if (window.location.pathname.includes('/emergency/')) {
            this.registerEmergencyServiceWorker();
        }
        
        // Setup emergency data caching
        this.setupEmergencyDataCaching();
        
        // Setup emergency offline sync
        this.setupEmergencyOfflineSync();
        
        console.log('[PWA] Emergency features initialized');
    }
    
    /**
     * Register emergency service worker
     */
    async registerEmergencyServiceWorker() {
        // Check if this is emergency access with access key
        const accessKey = this.extractAccessKeyFromURL();
        if (!accessKey) return;
        
        try {
            const registration = await navigator.serviceWorker.register(
                `/emergency/pwa/sw/${accessKey}.js`,
                { scope: `/emergency/` }
            );
            
            console.log('[PWA] Emergency service worker registered:', registration);
            
            // Cache emergency data immediately
            this.cacheEmergencyData(accessKey);
            
        } catch (error) {
            console.error('[PWA] Emergency service worker registration failed:', error);
        }
    }
    
    /**
     * Setup emergency data caching
     */
    setupEmergencyDataCaching() {
        // Cache emergency contacts data
        document.addEventListener('emergency-contacts-loaded', (event) => {
            const contactsData = event.detail;
            this.cacheEmergencyContactsData(contactsData);
        });
        
        // Cache emergency instructions
        document.addEventListener('emergency-instructions-loaded', (event) => {
            const instructions = event.detail;
            localStorage.setItem('emergency_instructions', JSON.stringify(instructions));
        });
    }
    
    /**
     * Setup emergency offline sync
     */
    setupEmergencyOfflineSync() {
        // Listen for emergency contact usage
        document.addEventListener('emergency-contact-used', (event) => {
            const contactUsage = event.detail;
            this.queueEmergencyData('contact_usage', contactUsage);
        });
        
        // Listen for emergency incident reports
        document.addEventListener('emergency-incident-reported', (event) => {
            const incident = event.detail;
            this.queueEmergencyData('incident_report', incident);
        });
    }
    
    /**
     * Cache emergency data
     */
    async cacheEmergencyData(accessKey) {
        try {
            const response = await fetch(`/emergency/pwa/cache/${accessKey}`);
            const emergencyData = await response.json();
            
            // Store in localStorage for immediate access
            localStorage.setItem('emergency_contacts_data', JSON.stringify(emergencyData));
            localStorage.setItem('emergency_cache_timestamp', new Date().toISOString());
            
            // Send to service worker for advanced caching
            if (this.serviceWorker) {
                this.serviceWorker.active?.postMessage({
                    type: 'CACHE_EMERGENCY_DATA',
                    payload: emergencyData
                });
            }
            
            console.log('[PWA] Emergency data cached successfully');
            
        } catch (error) {
            console.error('[PWA] Failed to cache emergency data:', error);
        }
    }
    
    /**
     * Cache emergency contacts data
     */
    cacheEmergencyContactsData(contactsData) {
        try {
            localStorage.setItem('emergency_contacts', JSON.stringify(contactsData));
            localStorage.setItem('emergency_contacts_timestamp', new Date().toISOString());
            console.log('[PWA] Emergency contacts cached');
        } catch (error) {
            console.error('[PWA] Failed to cache emergency contacts:', error);
        }
    }
    
    /**
     * Queue emergency data for sync
     */
    queueEmergencyData(type, data) {
        const queueItem = {
            id: this.generateId(),
            type: `emergency_${type}`,
            data: data,
            timestamp: new Date().toISOString(),
            priority: 'high' // Emergency data has high priority
        };
        
        this.offlineQueue.unshift(queueItem); // Add to front of queue
        this.saveOfflineQueue();
        
        console.log('[PWA] Emergency data queued:', queueItem);
        
        // Try to sync immediately if online
        if (this.isOnline) {
            this.syncEmergencyItem(queueItem);
        }
    }
    
    /**
     * Sync emergency data item
     */
    async syncEmergencyItem(item) {
        const endpoints = {
            emergency_contact_usage: '/api/emergency/contact-accessed',
            emergency_incident_report: '/api/emergency/incidents'
        };
        
        const endpoint = endpoints[item.type];
        if (!endpoint) {
            console.error('[PWA] Unknown emergency sync type:', item.type);
            return;
        }
        
        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Emergency-Sync': 'true'
                },
                body: JSON.stringify(item.data)
            });
            
            if (response.ok) {
                this.removeFromOfflineQueue(item.id);
                console.log('[PWA] Emergency item synced:', item.id);
            } else {
                throw new Error(`Sync failed: ${response.status}`);
            }
            
        } catch (error) {
            console.error('[PWA] Failed to sync emergency item:', error);
            // Keep in queue for retry
        }
    }
    
    /**
     * Get cached emergency data
     */
    getCachedEmergencyData() {
        try {
            const cached = localStorage.getItem('emergency_contacts_data');
            const timestamp = localStorage.getItem('emergency_cache_timestamp');
            
            if (!cached || !timestamp) {
                return null;
            }
            
            // Check if cache is still valid (24 hours)
            const cacheTime = new Date(timestamp);
            const now = new Date();
            const hoursDiff = (now - cacheTime) / (1000 * 60 * 60);
            
            if (hoursDiff > 24) {
                console.log('[PWA] Emergency cache expired');
                this.clearEmergencyCache();
                return null;
            }
            
            return JSON.parse(cached);
            
        } catch (error) {
            console.error('[PWA] Failed to get cached emergency data:', error);
            return null;
        }
    }
    
    /**
     * Clear emergency cache
     */
    clearEmergencyCache() {
        localStorage.removeItem('emergency_contacts_data');
        localStorage.removeItem('emergency_cache_timestamp');
        localStorage.removeItem('emergency_contacts');
        localStorage.removeItem('emergency_contacts_timestamp');
        localStorage.removeItem('emergency_instructions');
        console.log('[PWA] Emergency cache cleared');
    }
    
    /**
     * Show emergency notification
     */
    showEmergencyNotification(message, type = 'info', options = {}) {
        const emergencyOptions = {
            requireInteraction: true,
            silent: false,
            vibrate: [200, 100, 200],
            ...options
        };
        
        this.showNotification(`🚨 ${message}`, type);
        
        // Show browser notification if permission granted
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification(`Emergency: ${message}`, {
                icon: '/images/emergency-192.png',
                badge: '/images/emergency-badge.png',
                ...emergencyOptions
            });
        }
    }
    
    /**
     * Extract access key from URL
     */
    extractAccessKeyFromURL() {
        const match = window.location.pathname.match(/\/emergency\/(?:pwa\/)?(?:access\/)?([a-zA-Z0-9]{32})/);
        return match ? match[1] : null;
    }
    
    /**
     * Install emergency PWA
     */
    async installEmergencyPWA() {
        const accessKey = this.extractAccessKeyFromURL();
        if (!accessKey) {
            console.error('[PWA] No access key found for emergency PWA installation');
            return;
        }
        
        try {
            // Navigate to install page
            window.location.href = `/emergency/pwa/install/${accessKey}`;
        } catch (error) {
            console.error('[PWA] Failed to install emergency PWA:', error);
        }
    }
}

// Emergency PWA utilities
window.EmergencyPWA = {
    /**
     * Initialize emergency PWA features
     */
    init() {
        if (window.basketManagerPWA) {
            window.basketManagerPWA.setupEmergencyFeatures();
        }
    },
    
    /**
     * Cache emergency data manually
     */
    async cacheData(accessKey) {
        if (window.basketManagerPWA) {
            await window.basketManagerPWA.cacheEmergencyData(accessKey);
        }
    },
    
    /**
     * Get offline emergency data
     */
    getOfflineData() {
        if (window.basketManagerPWA) {
            return window.basketManagerPWA.getCachedEmergencyData();
        }
        return null;
    },
    
    /**
     * Report emergency incident offline
     */
    reportIncident(incidentData) {
        if (window.basketManagerPWA) {
            window.basketManagerPWA.queueEmergencyData('incident_report', incidentData);
            return true;
        }
        return false;
    },
    
    /**
     * Log contact usage
     */
    logContactUsage(contactData) {
        if (window.basketManagerPWA) {
            window.basketManagerPWA.queueEmergencyData('contact_usage', contactData);
        }
    },
    
    /**
     * Check if emergency data is cached
     */
    hasOfflineData() {
        return !!localStorage.getItem('emergency_contacts_data');
    },
    
    /**
     * Clear emergency cache
     */
    clearCache() {
        if (window.basketManagerPWA) {
            window.basketManagerPWA.clearEmergencyCache();
        }
    }
};

// Initialize PWA when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.basketManagerPWA = new BasketManagerPWA();
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = BasketManagerPWA;
}