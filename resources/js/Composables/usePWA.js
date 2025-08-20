import { ref, computed, onMounted, nextTick } from 'vue'

export function usePWA() {
    const isInstalled = ref(false)
    const isInstallable = ref(false)
    const deferredPrompt = ref(null)
    const isOnline = ref(navigator.onLine)
    const serviceWorkerRegistration = ref(null)
    const updateAvailable = ref(false)

    // Check if app is running in standalone mode (installed)
    const isStandalone = computed(() => {
        return window.matchMedia('(display-mode: standalone)').matches ||
               window.navigator.standalone ||
               document.referrer.includes('android-app://')
    })

    // Check if device is mobile
    const isMobile = computed(() => {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
    })

    // Check if app supports installation
    const canInstall = computed(() => {
        return isInstallable.value && !isStandalone.value
    })

    // Install the app
    const installApp = async () => {
        if (!deferredPrompt.value) {
            return false
        }

        try {
            // Show the install prompt
            deferredPrompt.value.prompt()
            
            // Wait for user choice
            const { outcome } = await deferredPrompt.value.userChoice
            
            if (outcome === 'accepted') {
                console.log('PWA installation accepted')
                isInstalled.value = true
            } else {
                console.log('PWA installation dismissed')
            }
            
            // Clear the deferred prompt
            deferredPrompt.value = null
            isInstallable.value = false
            
            return outcome === 'accepted'
        } catch (error) {
            console.error('PWA installation failed:', error)
            return false
        }
    }

    // Register service worker
    const registerServiceWorker = async () => {
        if ('serviceWorker' in navigator) {
            try {
                const registration = await navigator.serviceWorker.register('/sw-gym.js', {
                    scope: '/'
                })
                
                serviceWorkerRegistration.value = registration
                console.log('Service Worker registered:', registration)

                // Check for updates
                registration.addEventListener('updatefound', () => {
                    const newWorker = registration.installing
                    
                    if (newWorker) {
                        newWorker.addEventListener('statechange', () => {
                            if (newWorker.state === 'installed') {
                                if (navigator.serviceWorker.controller) {
                                    // New update available
                                    updateAvailable.value = true
                                    console.log('New service worker available')
                                } else {
                                    // First install
                                    console.log('Service worker installed for first time')
                                }
                            }
                        })
                    }
                })

                return registration
            } catch (error) {
                console.error('Service Worker registration failed:', error)
                return null
            }
        }
    }

    // Update service worker
    const updateServiceWorker = async () => {
        if (serviceWorkerRegistration.value) {
            try {
                // Skip waiting and claim clients
                const newWorker = serviceWorkerRegistration.value.installing || serviceWorkerRegistration.value.waiting
                
                if (newWorker) {
                    newWorker.postMessage({ type: 'SKIP_WAITING' })
                }
                
                // Reload the page to use new service worker
                window.location.reload()
            } catch (error) {
                console.error('Service worker update failed:', error)
            }
        }
    }

    // Request notification permission
    const requestNotificationPermission = async () => {
        if (!('Notification' in window)) {
            console.warn('This browser does not support notifications')
            return false
        }

        if (Notification.permission === 'granted') {
            return true
        }

        if (Notification.permission === 'denied') {
            return false
        }

        const permission = await Notification.requestPermission()
        return permission === 'granted'
    }

    // Subscribe to push notifications
    const subscribeToPush = async () => {
        if (!serviceWorkerRegistration.value) {
            console.error('Service worker not registered')
            return null
        }

        try {
            // Check if already subscribed
            const existingSubscription = await serviceWorkerRegistration.value.pushManager.getSubscription()
            if (existingSubscription) {
                return existingSubscription
            }

            // Request notification permission
            const hasPermission = await requestNotificationPermission()
            if (!hasPermission) {
                console.warn('Notification permission denied')
                return null
            }

            // Get VAPID public key from server
            const response = await fetch('/api/push/vapid-key')
            const { publicKey } = await response.json()

            // Subscribe to push notifications
            const subscription = await serviceWorkerRegistration.value.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(publicKey)
            })

            // Send subscription to server
            await fetch('/api/push/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify(subscription)
            })

            console.log('Push subscription created:', subscription)
            return subscription
        } catch (error) {
            console.error('Push subscription failed:', error)
            return null
        }
    }

    // Unsubscribe from push notifications
    const unsubscribeFromPush = async () => {
        if (!serviceWorkerRegistration.value) {
            return false
        }

        try {
            const subscription = await serviceWorkerRegistration.value.pushManager.getSubscription()
            
            if (subscription) {
                await subscription.unsubscribe()
                
                // Notify server
                await fetch('/api/push/unsubscribe', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    },
                    body: JSON.stringify({ endpoint: subscription.endpoint })
                })

                console.log('Push subscription removed')
                return true
            }
        } catch (error) {
            console.error('Push unsubscription failed:', error)
            return false
        }
    }

    // Show install prompt
    const showInstallPrompt = () => {
        return new Promise((resolve) => {
            // Create custom install modal/toast
            const modal = document.createElement('div')
            modal.className = 'pwa-install-prompt'
            modal.innerHTML = `
                <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div class="bg-white rounded-lg p-6 max-w-sm w-full">
                        <div class="flex items-center mb-4">
                            <img src="/images/logo-96.png" alt="BasketManager Pro" class="w-12 h-12 mr-3">
                            <div>
                                <h3 class="font-semibold text-gray-900">App installieren</h3>
                                <p class="text-sm text-gray-600">Für beste Erfahrung</p>
                            </div>
                        </div>
                        <p class="text-gray-700 mb-4">
                            Installieren Sie BasketManager Pro für schnelleren Zugriff und Push-Benachrichtigungen.
                        </p>
                        <div class="flex space-x-3">
                            <button class="install-accept flex-1 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                Installieren
                            </button>
                            <button class="install-dismiss flex-1 bg-gray-200 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-300">
                                Später
                            </button>
                        </div>
                    </div>
                </div>
            `
            
            document.body.appendChild(modal)
            
            // Handle button clicks
            modal.querySelector('.install-accept').addEventListener('click', async () => {
                const installed = await installApp()
                document.body.removeChild(modal)
                resolve(installed)
            })
            
            modal.querySelector('.install-dismiss').addEventListener('click', () => {
                document.body.removeChild(modal)
                resolve(false)
            })
        })
    }

    // Add to home screen for iOS
    const showIOSInstallInstructions = () => {
        const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent)
        const isSafari = /Safari/.test(navigator.userAgent) && !/Chrome/.test(navigator.userAgent)
        
        if (isIOS && isSafari && !isStandalone.value) {
            // Show iOS installation instructions
            const toast = document.createElement('div')
            toast.className = 'ios-install-toast'
            toast.innerHTML = `
                <div class="fixed bottom-4 left-4 right-4 bg-blue-600 text-white p-4 rounded-lg shadow-lg z-50">
                    <div class="flex items-start">
                        <div class="flex-1">
                            <p class="font-medium mb-1">App zum Home-Bildschirm hinzufügen</p>
                            <p class="text-sm text-blue-100">
                                Tippen Sie auf das Teilen-Symbol und dann auf "Zum Home-Bildschirm"
                            </p>
                        </div>
                        <button class="ml-3 text-blue-100 hover:text-white" onclick="this.parentElement.parentElement.remove()">
                            ✕
                        </button>
                    </div>
                </div>
            `
            
            document.body.appendChild(toast)
            
            // Auto-hide after 10 seconds
            setTimeout(() => {
                if (document.body.contains(toast)) {
                    document.body.removeChild(toast)
                }
            }, 10000)
        }
    }

    // Cache gym data for offline use
    const cacheGymData = async (data) => {
        if (serviceWorkerRegistration.value) {
            serviceWorkerRegistration.value.active?.postMessage({
                type: 'CACHE_GYM_DATA',
                data: data
            })
        }
    }

    // Clear cached data
    const clearCache = async () => {
        if (serviceWorkerRegistration.value) {
            serviceWorkerRegistration.value.active?.postMessage({
                type: 'CLEAR_CACHE'
            })
        }
    }

    // Background sync
    const scheduleBackgroundSync = async (tag) => {
        if (serviceWorkerRegistration.value && 'sync' in serviceWorkerRegistration.value) {
            try {
                await serviceWorkerRegistration.value.sync.register(tag)
                console.log('Background sync scheduled:', tag)
            } catch (error) {
                console.error('Background sync failed:', error)
            }
        }
    }

    // Utility function to convert VAPID key
    const urlBase64ToUint8Array = (base64String) => {
        const padding = '='.repeat((4 - base64String.length % 4) % 4)
        const base64 = (base64String + padding)
            .replace(/-/g, '+')
            .replace(/_/g, '/')

        const rawData = window.atob(base64)
        const outputArray = new Uint8Array(rawData.length)

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i)
        }
        return outputArray
    }

    // Initialize PWA features
    onMounted(async () => {
        // Register service worker
        await registerServiceWorker()
        
        // Listen for install prompt
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault()
            deferredPrompt.value = e
            isInstallable.value = true
            console.log('PWA install prompt ready')
        })
        
        // Listen for app installed event
        window.addEventListener('appinstalled', () => {
            console.log('PWA installed successfully')
            isInstalled.value = true
            isInstallable.value = false
            deferredPrompt.value = null
        })
        
        // Listen for online/offline events
        window.addEventListener('online', () => {
            isOnline.value = true
            console.log('App is online')
        })
        
        window.addEventListener('offline', () => {
            isOnline.value = false
            console.log('App is offline')
        })
        
        // Show iOS instructions if applicable
        await nextTick(() => {
            showIOSInstallInstructions()
        })
    })

    return {
        // State
        isInstalled,
        isInstallable,
        isOnline,
        updateAvailable,
        serviceWorkerRegistration,

        // Computed
        isStandalone,
        isMobile,
        canInstall,

        // Methods
        installApp,
        updateServiceWorker,
        requestNotificationPermission,
        subscribeToPush,
        unsubscribeFromPush,
        showInstallPrompt,
        cacheGymData,
        clearCache,
        scheduleBackgroundSync
    }
}