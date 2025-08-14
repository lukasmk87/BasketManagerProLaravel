<template>
    <div class="emergency-offline-interface">
        <!-- Header -->
        <div class="offline-header">
            <div class="connection-status" :class="{ 'offline': !isOnline, 'online': isOnline }">
                <div class="status-indicator">
                    <div class="status-dot"></div>
                    <span class="status-text">
                        {{ isOnline ? 'Online' : 'Offline Mode' }}
                    </span>
                </div>
            </div>
            
            <div class="team-info">
                <h1 class="team-name">{{ team.name }}</h1>
                <p class="emergency-mode">Emergency Access - Offline Ready</p>
            </div>
        </div>

        <!-- Critical Emergency Section -->
        <div class="critical-emergency-section">
            <h2 class="section-title emergency">
                <ExclamationTriangleIcon class="w-6 h-6" />
                Critical Emergency
            </h2>
            <div class="emergency-numbers-grid">
                <a 
                    v-for="(number, service) in emergencyNumbers" 
                    :key="service"
                    :href="`tel:${number}`" 
                    class="emergency-number"
                    :class="service"
                    @click="logEmergencyCall(service, number)"
                >
                    <div class="number-icon">
                        {{ getEmergencyIcon(service) }}
                    </div>
                    <div class="number-info">
                        <span class="number">{{ number }}</span>
                        <span class="service">{{ formatServiceName(service) }}</span>
                    </div>
                </a>
            </div>
        </div>

        <!-- Contact Search -->
        <div class="contact-search-section">
            <div class="search-header">
                <h2 class="section-title">
                    <UsersIcon class="w-6 h-6" />
                    Emergency Contacts
                </h2>
                <div class="contact-count">
                    {{ filteredContacts.length }} contacts available offline
                </div>
            </div>
            
            <div class="search-controls">
                <div class="search-input-container">
                    <MagnifyingGlassIcon class="search-icon w-5 h-5" />
                    <input 
                        v-model="searchQuery"
                        type="search"
                        class="search-input"
                        placeholder="Search players or contacts..."
                        @input="performSearch"
                    />
                </div>
                
                <div class="filter-buttons">
                    <button 
                        @click="filterBy('all')"
                        class="filter-btn"
                        :class="{ 'active': currentFilter === 'all' }"
                    >
                        All
                    </button>
                    <button 
                        @click="filterBy('primary')"
                        class="filter-btn"
                        :class="{ 'active': currentFilter === 'primary' }"
                    >
                        Primary
                    </button>
                    <button 
                        @click="filterBy('medical')"
                        class="filter-btn"
                        :class="{ 'active': currentFilter === 'medical' }"
                    >
                        Medical
                    </button>
                </div>
            </div>
        </div>

        <!-- Contacts List -->
        <div class="contacts-section">
            <div v-if="filteredContacts.length === 0" class="no-contacts">
                <UsersIcon class="w-12 h-12 text-gray-400" />
                <h3>No contacts found</h3>
                <p>{{ searchQuery ? 'Try a different search term' : 'No emergency contacts available offline' }}</p>
            </div>
            
            <div v-else class="contacts-list">
                <div 
                    v-for="playerData in filteredContacts" 
                    :key="playerData.player_id"
                    class="player-contacts-group"
                >
                    <div class="player-header">
                        <div class="player-info">
                            <h3 class="player-name">{{ playerData.player_name }}</h3>
                            <div class="player-meta">
                                <span class="jersey-number">#{{ playerData.jersey_number }}</span>
                                <span v-if="playerData.position" class="position">{{ playerData.position }}</span>
                            </div>
                        </div>
                        <div class="contact-count-badge">
                            {{ playerData.contacts.length }}
                        </div>
                    </div>
                    
                    <div class="player-contacts">
                        <ContactCard
                            v-for="contact in playerData.contacts"
                            :key="contact.id"
                            :contact="contact"
                            :urgent-mode="urgentMode"
                            @contact-attempted="logContactAttempt"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions-section">
            <h2 class="section-title">
                <BoltIcon class="w-6 h-6" />
                Quick Actions
            </h2>
            
            <div class="action-buttons-grid">
                <button @click="reportIncident" class="action-btn incident">
                    <DocumentTextIcon class="w-6 h-6" />
                    <span>Report Incident</span>
                </button>
                
                <button @click="shareLocation" class="action-btn location">
                    <MapPinIcon class="w-6 h-6" />
                    <span>Share Location</span>
                </button>
                
                <button @click="toggleUrgentMode" class="action-btn urgent">
                    <ExclamationTriangleIcon class="w-6 h-6" />
                    <span>{{ urgentMode ? 'Exit' : 'Enter' }} Urgent Mode</span>
                </button>
                
                <button @click="syncWhenOnline" class="action-btn sync" :disabled="isOnline">
                    <ArrowPathIcon class="w-6 h-6" :class="{ 'animate-spin': isSyncing }" />
                    <span>{{ isSyncing ? 'Syncing...' : 'Sync Data' }}</span>
                </button>
            </div>
        </div>

        <!-- Offline Status -->
        <div class="offline-status-section">
            <div class="status-card">
                <div class="status-info">
                    <WifiIcon v-if="isOnline" class="w-5 h-5 text-green-500" />
                    <NoSymbolIcon v-else class="w-5 h-5 text-orange-500" />
                    <div class="status-details">
                        <p class="status-label">{{ isOnline ? 'Connected' : 'Offline Mode Active' }}</p>
                        <p class="status-description">
                            {{ isOnline ? 'All features available' : 'Emergency contacts cached for offline use' }}
                        </p>
                    </div>
                </div>
                
                <div class="cache-info">
                    <p class="cache-time">
                        Last updated: {{ formatCacheTime(lastCacheUpdate) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Install PWA Prompt -->
        <div v-if="showInstallPrompt" class="pwa-install-section">
            <div class="install-card">
                <SmartphoneIcon class="w-6 h-6 text-blue-600" />
                <div class="install-content">
                    <h4>Install Emergency App</h4>
                    <p>Add to home screen for instant access during emergencies</p>
                </div>
                <button @click="showInstallInstructions" class="install-btn">
                    Install
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { 
    ExclamationTriangleIcon,
    UsersIcon,
    MagnifyingGlassIcon,
    BoltIcon,
    DocumentTextIcon,
    MapPinIcon,
    ArrowPathIcon,
    WifiIcon,
    NoSymbolIcon,
    SmartphoneIcon
} from '@heroicons/vue/24/outline'
import ContactCard from './ContactCard.vue'

const props = defineProps({
    team: {
        type: Object,
        required: true
    },
    emergencyContacts: {
        type: Array,
        default: () => []
    },
    emergencyNumbers: {
        type: Object,
        default: () => ({
            ambulance: '112',
            fire: '112',
            police: '110'
        })
    },
    accessKey: {
        type: String,
        required: true
    },
    lastCacheUpdate: {
        type: String,
        default: null
    }
})

// Reactive data
const isOnline = ref(navigator.onLine)
const searchQuery = ref('')
const currentFilter = ref('all')
const urgentMode = ref(false)
const isSyncing = ref(false)
const showInstallPrompt = ref(false)

// Computed properties
const filteredContacts = computed(() => {
    let contacts = props.emergencyContacts

    // Apply search filter
    if (searchQuery.value.trim()) {
        const query = searchQuery.value.toLowerCase().trim()
        contacts = contacts.filter(playerData => {
            const playerMatch = playerData.player_name.toLowerCase().includes(query) ||
                              playerData.jersey_number.toString().includes(query)
            
            const contactMatch = playerData.contacts.some(contact =>
                contact.name.toLowerCase().includes(query) ||
                contact.relationship.toLowerCase().includes(query)
            )
            
            return playerMatch || contactMatch
        })
    }

    // Apply type filter
    if (currentFilter.value !== 'all') {
        contacts = contacts.map(playerData => ({
            ...playerData,
            contacts: playerData.contacts.filter(contact => {
                switch (currentFilter.value) {
                    case 'primary':
                        return contact.is_primary
                    case 'medical':
                        return contact.medical_training || contact.medical_decisions
                    default:
                        return true
                }
            })
        })).filter(playerData => playerData.contacts.length > 0)
    }

    return contacts
})

// Methods
const performSearch = () => {
    // Debouncing handled by v-model reactivity
}

const filterBy = (filterType) => {
    currentFilter.value = filterType
}

const getEmergencyIcon = (service) => {
    const icons = {
        ambulance: '🚑',
        fire: '🚒', 
        police: '👮'
    }
    return icons[service] || '📞'
}

const formatServiceName = (service) => {
    const names = {
        ambulance: 'Ambulance',
        fire: 'Fire',
        police: 'Police'
    }
    return names[service] || service
}

const logEmergencyCall = (service, number) => {
    // Log emergency call attempt
    const logData = {
        type: 'emergency_call',
        service: service,
        number: number,
        timestamp: new Date().toISOString(),
        access_key: props.accessKey
    }
    
    // Store in localStorage for offline sync
    const logs = JSON.parse(localStorage.getItem('emergency_logs') || '[]')
    logs.push(logData)
    localStorage.setItem('emergency_logs', JSON.stringify(logs))
    
    // Haptic feedback
    if (navigator.vibrate) {
        navigator.vibrate([200, 100, 200])
    }
}

const logContactAttempt = (contactData) => {
    // Log contact attempt
    const logData = {
        type: 'contact_attempted',
        contact_id: contactData.contact_id,
        action: contactData.type,
        timestamp: contactData.timestamp,
        access_key: props.accessKey
    }
    
    // Store for offline sync
    const logs = JSON.parse(localStorage.getItem('emergency_logs') || '[]')
    logs.push(logData)
    localStorage.setItem('emergency_logs', JSON.stringify(logs))
    
    // Sync immediately if online
    if (isOnline.value) {
        syncLogToServer(logData)
    }
}

const reportIncident = () => {
    // Open incident reporting modal/form
    // This would be implemented as a separate component
    console.log('Report incident clicked')
    
    // For now, just show alert
    const description = prompt('Describe the incident briefly:')
    if (description) {
        const incident = {
            type: 'incident_report',
            description: description,
            timestamp: new Date().toISOString(),
            location: getCurrentLocation(),
            access_key: props.accessKey
        }
        
        // Store for offline sync
        const incidents = JSON.parse(localStorage.getItem('emergency_incidents') || '[]')
        incidents.push(incident)
        localStorage.setItem('emergency_incidents', JSON.stringify(incidents))
        
        alert('Incident reported and saved offline. Will sync when connection is restored.')
    }
}

const shareLocation = () => {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const location = {
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy,
                    timestamp: new Date().toISOString()
                }
                
                // Create shareable location message
                const message = `Emergency location: https://maps.google.com/?q=${location.latitude},${location.longitude}`
                
                if (navigator.share) {
                    navigator.share({
                        title: 'Emergency Location',
                        text: message
                    })
                } else {
                    // Fallback - copy to clipboard
                    navigator.clipboard.writeText(message).then(() => {
                        alert('Location copied to clipboard!')
                    })
                }
            },
            (error) => {
                alert('Could not get current location')
                console.error('Geolocation error:', error)
            },
            { enableHighAccuracy: true, timeout: 10000 }
        )
    } else {
        alert('Geolocation not available on this device')
    }
}

const getCurrentLocation = () => {
    const stored = sessionStorage.getItem('emergency_location')
    return stored ? JSON.parse(stored) : null
}

const toggleUrgentMode = () => {
    urgentMode.value = !urgentMode.value
    
    // Visual feedback
    if (urgentMode.value) {
        document.body.classList.add('urgent-mode')
        if (navigator.vibrate) {
            navigator.vibrate([100, 50, 100, 50, 100])
        }
    } else {
        document.body.classList.remove('urgent-mode')
    }
}

const syncWhenOnline = async () => {
    if (!isOnline.value) return
    
    isSyncing.value = true
    
    try {
        // Sync logs
        const logs = JSON.parse(localStorage.getItem('emergency_logs') || '[]')
        const incidents = JSON.parse(localStorage.getItem('emergency_incidents') || '[]')
        
        // Send logs to server
        for (const log of logs) {
            await syncLogToServer(log)
        }
        
        // Send incidents to server
        for (const incident of incidents) {
            await syncIncidentToServer(incident)
        }
        
        // Clear synced data
        localStorage.removeItem('emergency_logs')
        localStorage.removeItem('emergency_incidents')
        
        alert('All offline data synced successfully!')
        
    } catch (error) {
        console.error('Sync failed:', error)
        alert('Sync failed. Data will be retried later.')
    } finally {
        isSyncing.value = false
    }
}

const syncLogToServer = async (logData) => {
    if (!isOnline.value) return
    
    try {
        await fetch('/api/emergency/contact-accessed', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(logData)
        })
    } catch (error) {
        console.error('Failed to sync log:', error)
    }
}

const syncIncidentToServer = async (incident) => {
    if (!isOnline.value) return
    
    try {
        await fetch('/api/emergency/incidents', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(incident)
        })
    } catch (error) {
        console.error('Failed to sync incident:', error)
    }
}

const formatCacheTime = (timestamp) => {
    if (!timestamp) return 'Unknown'
    
    const date = new Date(timestamp)
    const now = new Date()
    const diffInMinutes = Math.floor((now - date) / 60000)
    
    if (diffInMinutes < 60) {
        return `${diffInMinutes} minutes ago`
    } else if (diffInMinutes < 1440) {
        return `${Math.floor(diffInMinutes / 60)} hours ago`
    } else {
        return date.toLocaleDateString()
    }
}

const showInstallInstructions = () => {
    window.location.href = `/emergency/pwa/install/${props.accessKey}`
}

const handleOnlineStatusChange = () => {
    isOnline.value = navigator.onLine
    
    if (isOnline.value) {
        // Auto-sync when coming back online
        setTimeout(() => {
            syncWhenOnline()
        }, 1000)
    }
}

// Lifecycle
onMounted(() => {
    // Listen for online/offline events
    window.addEventListener('online', handleOnlineStatusChange)
    window.addEventListener('offline', handleOnlineStatusChange)
    
    // Check if PWA install prompt should be shown
    const isMobile = /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
    const isStandalone = window.navigator.standalone || window.matchMedia('(display-mode: standalone)').matches
    showInstallPrompt.value = isMobile && !isStandalone
    
    // Register service worker if not already registered
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register(`/emergency/pwa/sw/${props.accessKey}.js`)
            .then(registration => {
                console.log('SW registered:', registration)
            })
            .catch(error => {
                console.log('SW registration failed:', error)
            })
    }
})

onUnmounted(() => {
    window.removeEventListener('online', handleOnlineStatusChange)
    window.removeEventListener('offline', handleOnlineStatusChange)
    document.body.classList.remove('urgent-mode')
})
</script>

<style scoped>
.emergency-offline-interface {
    @apply min-h-screen bg-gray-50 pb-safe;
}

.offline-header {
    @apply bg-white border-b border-gray-200 p-4;
}

.connection-status {
    @apply flex justify-end mb-3;
}

.status-indicator {
    @apply flex items-center gap-2;
}

.status-dot {
    @apply w-3 h-3 rounded-full;
}

.connection-status.online .status-dot {
    @apply bg-green-500;
}

.connection-status.offline .status-dot {
    @apply bg-orange-500 animate-pulse;
}

.status-text {
    @apply text-sm font-medium;
}

.connection-status.online .status-text {
    @apply text-green-700;
}

.connection-status.offline .status-text {
    @apply text-orange-700;
}

.team-info {
    @apply text-center;
}

.team-name {
    @apply text-2xl font-bold text-gray-900;
}

.emergency-mode {
    @apply text-red-600 font-medium;
}

.critical-emergency-section {
    @apply bg-red-50 border-b-4 border-red-200 p-4;
}

.section-title {
    @apply flex items-center gap-2 text-lg font-semibold mb-4;
}

.section-title.emergency {
    @apply text-red-800;
}

.emergency-numbers-grid {
    @apply grid grid-cols-2 gap-3;
}

.emergency-number {
    @apply flex items-center gap-3 p-4 rounded-lg text-white font-semibold;
    @apply transition-transform;
    text-decoration: none;
    min-height: 72px;
}

.emergency-number:active {
    transform: scale(0.95);
}

.emergency-number.ambulance,
.emergency-number.fire {
    @apply bg-red-600 hover:bg-red-700;
}

.emergency-number.police {
    @apply bg-blue-600 hover:bg-blue-700;
}

.number-icon {
    @apply text-2xl;
}

.number-info {
    @apply flex flex-col;
}

.number {
    @apply text-2xl font-bold;
}

.service {
    @apply text-sm opacity-90;
}

.contact-search-section {
    @apply bg-white border-b border-gray-200 p-4;
}

.search-header {
    @apply flex justify-between items-center mb-4;
}

.contact-count {
    @apply text-sm text-gray-600 bg-gray-100 px-3 py-1 rounded-full;
}

.search-controls {
    @apply space-y-3;
}

.search-input-container {
    @apply relative;
}

.search-icon {
    @apply absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400;
}

.search-input {
    @apply w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg;
    @apply focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
    font-size: 16px;
}

.filter-buttons {
    @apply flex gap-2;
}

.filter-btn {
    @apply px-4 py-2 text-sm font-medium rounded-lg;
    @apply border border-gray-300 bg-white text-gray-700;
    @apply hover:bg-gray-50 transition-colors;
}

.filter-btn.active {
    @apply bg-blue-600 text-white border-blue-600;
}

.contacts-section {
    @apply flex-1 p-4;
}

.no-contacts {
    @apply text-center py-12;
}

.no-contacts h3 {
    @apply text-lg font-medium text-gray-900 mt-4;
}

.no-contacts p {
    @apply text-gray-600 mt-2;
}

.contacts-list {
    @apply space-y-6;
}

.player-contacts-group {
    @apply bg-white rounded-lg border border-gray-200 overflow-hidden;
}

.player-header {
    @apply flex justify-between items-center p-4 bg-gray-50 border-b border-gray-200;
}

.player-info {
    @apply flex-1;
}

.player-name {
    @apply text-lg font-semibold text-gray-900;
}

.player-meta {
    @apply flex gap-3 mt-1;
}

.jersey-number {
    @apply text-sm font-mono bg-blue-100 text-blue-800 px-2 py-1 rounded;
}

.position {
    @apply text-sm text-gray-600;
}

.contact-count-badge {
    @apply bg-blue-600 text-white text-sm font-medium px-3 py-1 rounded-full;
}

.player-contacts {
    @apply p-4 space-y-4;
}

.quick-actions-section {
    @apply bg-white border-t border-gray-200 p-4;
}

.action-buttons-grid {
    @apply grid grid-cols-2 gap-3;
}

.action-btn {
    @apply flex flex-col items-center gap-2 p-4 rounded-lg;
    @apply border-2 border-gray-200 bg-white;
    @apply hover:bg-gray-50 transition-colors;
    min-height: 80px;
}

.action-btn.incident {
    @apply border-orange-200 hover:bg-orange-50;
}

.action-btn.location {
    @apply border-green-200 hover:bg-green-50;
}

.action-btn.urgent {
    @apply border-red-200 hover:bg-red-50;
}

.action-btn.sync {
    @apply border-blue-200 hover:bg-blue-50;
}

.action-btn:disabled {
    @apply opacity-50 cursor-not-allowed;
}

.action-btn span {
    @apply text-sm font-medium text-center;
}

.offline-status-section {
    @apply p-4;
}

.status-card {
    @apply bg-white rounded-lg border border-gray-200 p-4;
}

.status-info {
    @apply flex items-start gap-3;
}

.status-details {
    @apply flex-1;
}

.status-label {
    @apply font-medium text-gray-900;
}

.status-description {
    @apply text-sm text-gray-600 mt-1;
}

.cache-info {
    @apply mt-3 pt-3 border-t border-gray-200;
}

.cache-time {
    @apply text-xs text-gray-500;
}

.pwa-install-section {
    @apply p-4;
}

.install-card {
    @apply flex items-center gap-3 p-4 bg-blue-50 border border-blue-200 rounded-lg;
}

.install-content {
    @apply flex-1;
}

.install-content h4 {
    @apply font-medium text-blue-900;
}

.install-content p {
    @apply text-sm text-blue-700 mt-1;
}

.install-btn {
    @apply px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg;
    @apply hover:bg-blue-700 transition-colors;
}

/* Urgent mode styles */
:global(.urgent-mode) .emergency-offline-interface {
    @apply bg-red-50;
}

:global(.urgent-mode) .player-contacts-group {
    @apply border-red-200;
}

/* Mobile optimizations */
@media (max-width: 640px) {
    .emergency-numbers-grid {
        @apply grid-cols-1 gap-4;
    }
    
    .emergency-number {
        @apply p-5;
        min-height: 80px;
    }
    
    .action-buttons-grid {
        @apply grid-cols-1 gap-4;
    }
    
    .action-btn {
        @apply flex-row gap-4 text-left;
        min-height: 60px;
    }
}

/* PWA safe areas */
@supports (padding: max(0px)) {
    .emergency-offline-interface {
        padding-left: max(16px, env(safe-area-inset-left));
        padding-right: max(16px, env(safe-area-inset-right));
        padding-bottom: max(16px, env(safe-area-inset-bottom));
    }
}
</style>