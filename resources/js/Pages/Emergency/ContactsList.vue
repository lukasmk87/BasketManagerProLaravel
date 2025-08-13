<template>
    <div class="min-h-screen bg-gradient-to-br from-red-50 to-orange-50">
        <!-- Emergency Header -->
        <div class="emergency-header">
            <div class="container mx-auto px-4 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="emergency-icon">
                            <UserGroupIcon class="w-6 h-6 text-red-600" />
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-gray-900">Notfallkontakte</h1>
                            <p class="text-sm text-gray-600">
                                {{ team.name }} 
                                <span v-if="team.club">• {{ team.club.name }}</span>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="access-status">
                            <div class="status-dot"></div>
                            <span class="status-text">Zugriff aktiv</span>
                        </div>
                        <button @click="callEmergency('112')" class="emergency-number-btn">
                            <PhoneIcon class="w-4 h-4" />
                            <span>112</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="container mx-auto px-4 py-6">
            <!-- Access Information -->
            <div class="access-info-card mb-6">
                <div class="flex items-center gap-3 mb-4">
                    <ClockIcon class="w-5 h-5 text-blue-600" />
                    <div>
                        <span class="text-sm text-gray-600">Zugriff seit:</span>
                        <span class="ml-2 font-medium text-gray-900">
                            {{ formatAccessTime(accessInfo.accessed_at) }}
                        </span>
                    </div>
                </div>
                
                <div class="access-details">
                    <div class="urgency-indicator" :class="getUrgencyClass(accessInfo.urgency_level)">
                        <component :is="getUrgencyIcon(accessInfo.urgency_level)" class="w-4 h-4" />
                        <span class="urgency-text">{{ getUrgencyLabel(accessInfo.urgency_level) }}</span>
                    </div>
                    
                    <div v-if="accessInfo.reason" class="reason-text">
                        <span class="text-sm text-gray-600">Grund:</span>
                        <span class="ml-2 text-gray-900">{{ accessInfo.reason }}</span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions-card mb-6">
                <h3 class="font-semibold text-gray-900 mb-3">Schnelle Aktionen</h3>
                <div class="quick-actions-grid">
                    <button @click="callAllPrimary" class="quick-action-btn primary">
                        <PhoneIcon class="w-5 h-5" />
                        <span>Alle Hauptkontakte anrufen</span>
                    </button>
                    
                    <button @click="showPrintableView" class="quick-action-btn">
                        <PrinterIcon class="w-5 h-5" />
                        <span>Druckansicht</span>
                    </button>
                    
                    <button @click="reportIncident" class="quick-action-btn">
                        <DocumentTextIcon class="w-5 h-5" />
                        <span>Vorfall melden</span>
                    </button>
                    
                    <button @click="refreshContacts" class="quick-action-btn" :disabled="refreshing">
                        <ArrowPathIcon class="w-5 h-5" :class="{ 'animate-spin': refreshing }" />
                        <span>Aktualisieren</span>
                    </button>
                </div>
            </div>

            <!-- Search/Filter -->
            <div class="search-filter-card mb-6" v-if="emergencyContacts.length > 5">
                <div class="flex items-center gap-4">
                    <div class="flex-1">
                        <label for="search" class="sr-only">Kontakte suchen</label>
                        <div class="relative">
                            <MagnifyingGlassIcon class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2" />
                            <input
                                id="search"
                                v-model="searchQuery"
                                type="text"
                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                placeholder="Kontakte durchsuchen..."
                            />
                        </div>
                    </div>
                    
                    <div class="filter-buttons">
                        <button 
                            @click="filterBy = 'all'"
                            :class="{ 'active': filterBy === 'all' }"
                            class="filter-btn"
                        >
                            Alle
                        </button>
                        <button 
                            @click="filterBy = 'primary'"
                            :class="{ 'active': filterBy === 'primary' }"
                            class="filter-btn"
                        >
                            Hauptkontakte
                        </button>
                        <button 
                            @click="filterBy = 'medical'"
                            :class="{ 'active': filterBy === 'medical' }"
                            class="filter-btn"
                        >
                            Medizinisch berechtigt
                        </button>
                    </div>
                </div>
            </div>

            <!-- Emergency Contacts List -->
            <div class="contacts-container">
                <div v-if="filteredContacts.length === 0" class="no-contacts-message">
                    <UserGroupIcon class="w-12 h-12 text-gray-400 mx-auto mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Keine Kontakte gefunden</h3>
                    <p class="text-gray-600">
                        <span v-if="searchQuery">Keine Kontakte entsprechen Ihrer Suche.</span>
                        <span v-else>Keine Notfallkontakte verfügbar.</span>
                    </p>
                </div>
                
                <div v-else class="contacts-grid">
                    <div v-for="playerGroup in filteredContacts" :key="playerGroup.player.id" class="player-group">
                        <!-- Player Header -->
                        <div class="player-header">
                            <div class="player-info">
                                <h3 class="player-name">{{ playerGroup.player.name }}</h3>
                                <div class="player-details">
                                    <span v-if="playerGroup.player.jersey_number" class="jersey-number">
                                        #{{ playerGroup.player.jersey_number }}
                                    </span>
                                    <span v-if="playerGroup.player.position" class="position">
                                        {{ playerGroup.player.position }}
                                    </span>
                                </div>
                            </div>
                            <div class="contact-count">
                                {{ playerGroup.contacts.length }} 
                                {{ playerGroup.contacts.length === 1 ? 'Kontakt' : 'Kontakte' }}
                            </div>
                        </div>
                        
                        <!-- Contacts for this player -->
                        <div class="player-contacts">
                            <ContactCard
                                v-for="contact in playerGroup.contacts"
                                :key="contact.id || `${playerGroup.player.id}-${contact.name}`"
                                :contact="{ ...contact, player_name: playerGroup.player.name }"
                                :urgent-mode="accessInfo.urgency_level === 'critical'"
                                @contact-attempted="handleContactAttempt"
                                @details-toggled="handleDetailsToggle"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Emergency Instructions -->
            <div class="emergency-instructions-card mt-8">
                <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <InformationCircleIcon class="w-5 h-5 text-blue-600" />
                    Notfallanweisungen
                </h3>
                
                <div class="instructions-content">
                    <!-- Emergency Numbers -->
                    <div class="emergency-numbers-section">
                        <h4 class="instruction-subtitle">Notrufnummern</h4>
                        <div class="emergency-numbers-list">
                            <div v-for="(number, service) in emergencyInstructions.emergency_numbers" 
                                 :key="service" 
                                 class="emergency-number-item">
                                <button @click="callEmergency(number)" class="emergency-number-button">
                                    <span class="service-name">{{ formatServiceName(service) }}</span>
                                    <span class="service-number">{{ number }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Instructions List -->
                    <div class="instructions-section">
                        <h4 class="instruction-subtitle">Vorgehen bei Notfällen</h4>
                        <ol class="instructions-list">
                            <li v-for="(instruction, index) in emergencyInstructions.instructions" 
                                :key="index" 
                                class="instruction-item">
                                {{ instruction }}
                            </li>
                        </ol>
                    </div>
                    
                    <!-- Venue Information -->
                    <div v-if="emergencyInstructions.team_specific" class="venue-section">
                        <h4 class="instruction-subtitle">Team-spezifische Informationen</h4>
                        <div class="venue-info">
                            <p v-if="emergencyInstructions.team_specific.venue_address" class="venue-item">
                                <strong>Adresse:</strong> {{ emergencyInstructions.team_specific.venue_address }}
                            </p>
                            <p v-if="emergencyInstructions.team_specific.nearest_hospital" class="venue-item">
                                <strong>Nächstes Krankenhaus:</strong> {{ emergencyInstructions.team_specific.nearest_hospital }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Incident Report Modal -->
        <IncidentReportModal 
            v-if="showIncidentModal"
            :team="team"
            :players="getPlayersList()"
            @close="showIncidentModal = false"
            @incident-reported="handleIncidentReported"
        />
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { 
    UserGroupIcon,
    PhoneIcon,
    ClockIcon,
    MagnifyingGlassIcon,
    PrinterIcon,
    DocumentTextIcon,
    ArrowPathIcon,
    InformationCircleIcon,
    ExclamationTriangleIcon,
    ExclamationCircleIcon,
    ShieldExclamationIcon
} from '@heroicons/vue/24/outline'
import ContactCard from '@/Components/Emergency/ContactCard.vue'
import IncidentReportModal from '@/Components/Emergency/IncidentReportModal.vue'

const props = defineProps({
    team: Object,
    emergencyContacts: Array,
    accessInfo: Object,
    emergencyInstructions: Object
})

const searchQuery = ref('')
const filterBy = ref('all')
const refreshing = ref(false)
const showIncidentModal = ref(false)
const contactAttempts = ref([])

const filteredContacts = computed(() => {
    let filtered = props.emergencyContacts

    // Apply search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        filtered = filtered.filter(playerGroup => {
            return playerGroup.player.name.toLowerCase().includes(query) ||
                   playerGroup.contacts.some(contact => 
                       contact.name.toLowerCase().includes(query) ||
                       contact.relationship.toLowerCase().includes(query)
                   )
        })
    }

    // Apply category filter
    if (filterBy.value !== 'all') {
        filtered = filtered.map(playerGroup => ({
            ...playerGroup,
            contacts: playerGroup.contacts.filter(contact => {
                switch (filterBy.value) {
                    case 'primary':
                        return contact.is_primary
                    case 'medical':
                        return contact.medical_training || contact.medical_decisions
                    default:
                        return true
                }
            })
        })).filter(playerGroup => playerGroup.contacts.length > 0)
    }

    return filtered
})

const formatAccessTime = (timestamp) => {
    const date = new Date(timestamp)
    return date.toLocaleString('de-DE', {
        hour: '2-digit',
        minute: '2-digit',
        day: '2-digit',
        month: '2-digit'
    })
}

const getUrgencyClass = (urgency) => {
    const classes = {
        'critical': 'bg-red-100 text-red-800',
        'high': 'bg-orange-100 text-orange-800',
        'medium': 'bg-yellow-100 text-yellow-800',
        'low': 'bg-blue-100 text-blue-800'
    }
    return classes[urgency] || classes.medium
}

const getUrgencyIcon = (urgency) => {
    const icons = {
        'critical': ExclamationTriangleIcon,
        'high': ExclamationCircleIcon,
        'medium': ShieldExclamationIcon,
        'low': InformationCircleIcon
    }
    return icons[urgency] || InformationCircleIcon
}

const getUrgencyLabel = (urgency) => {
    const labels = {
        'critical': 'Kritischer Notfall',
        'high': 'Hoher Notfall',
        'medium': 'Mittlerer Notfall',
        'low': 'Niedriger Notfall'
    }
    return labels[urgency] || 'Notfall'
}

const formatServiceName = (service) => {
    const names = {
        ambulance: 'Rettungsdienst',
        fire: 'Feuerwehr',
        police: 'Polizei'
    }
    return names[service] || service
}

const getPlayersList = () => {
    return props.emergencyContacts.map(group => group.player)
}

const callEmergency = (number) => {
    if (navigator.vibrate) {
        navigator.vibrate([200, 100, 200])
    }
    window.location.href = `tel:${number}`
}

const callAllPrimary = () => {
    const primaryContacts = props.emergencyContacts
        .flatMap(group => group.contacts)
        .filter(contact => contact.is_primary)
    
    if (primaryContacts.length === 0) {
        alert('Keine Hauptkontakte verfügbar.')
        return
    }
    
    // For multiple contacts, we'll call the first one and show others
    if (primaryContacts.length === 1) {
        window.location.href = `tel:${primaryContacts[0].phone.replace(/\s/g, '')}`
    } else {
        // Show confirmation dialog with all primary contacts
        const contactList = primaryContacts
            .map((contact, index) => `${index + 1}. ${contact.name}: ${contact.phone}`)
            .join('\n')
        
        if (confirm(`${primaryContacts.length} Hauptkontakte gefunden:\n\n${contactList}\n\nErsten Kontakt anrufen?`)) {
            window.location.href = `tel:${primaryContacts[0].phone.replace(/\s/g, '')}`
        }
    }
}

const showPrintableView = () => {
    const printUrl = route('emergency.access.printable', { accessKey: props.accessKey })
    window.open(printUrl, '_blank')
}

const reportIncident = () => {
    showIncidentModal.value = true
}

const refreshContacts = () => {
    refreshing.value = true
    // Simulate refresh
    setTimeout(() => {
        refreshing.value = false
        window.location.reload()
    }, 2000)
}

const handleContactAttempt = (attempt) => {
    contactAttempts.value.push({
        ...attempt,
        id: Date.now()
    })
    
    // Log the contact attempt if we have an API endpoint
    // This would be sent to the server for tracking
    console.log('Contact attempt:', attempt)
}

const handleDetailsToggle = (event) => {
    // Track details toggles for analytics
    console.log('Details toggled:', event)
}

const handleIncidentReported = (incident) => {
    showIncidentModal.value = false
    // Show success message or redirect
    alert(`Vorfall erfolgreich gemeldet. Vorfall-ID: ${incident.incident_id}`)
}

onMounted(() => {
    // Focus search input if many contacts
    if (props.emergencyContacts.length > 10) {
        setTimeout(() => {
            const searchInput = document.getElementById('search')
            if (searchInput) {
                searchInput.focus()
            }
        }, 500)
    }
})
</script>

<style scoped>
.emergency-header {
    @apply bg-white border-b border-red-200 sticky top-0 z-10;
    box-shadow: 0 2px 4px rgba(239, 68, 68, 0.1);
}

.emergency-icon {
    @apply w-10 h-10 bg-red-100 rounded-full flex items-center justify-center;
}

.access-status {
    @apply flex items-center gap-2 text-sm;
}

.status-dot {
    @apply w-2 h-2 bg-green-500 rounded-full animate-pulse;
}

.status-text {
    @apply text-gray-600 font-medium;
}

.emergency-number-btn {
    @apply bg-red-600 text-white px-3 py-2 rounded-lg hover:bg-red-700 transition-colors flex items-center gap-2 font-semibold;
}

.access-info-card,
.quick-actions-card,
.search-filter-card,
.emergency-instructions-card {
    @apply bg-white border border-gray-200 rounded-lg p-4 shadow-sm;
}

.access-details {
    @apply flex flex-wrap items-center gap-4;
}

.urgency-indicator {
    @apply flex items-center gap-2 px-3 py-1 rounded-full text-sm font-medium;
}

.reason-text {
    @apply text-sm;
}

.quick-actions-grid {
    @apply grid grid-cols-2 md:grid-cols-4 gap-3;
}

.quick-action-btn {
    @apply flex items-center justify-center gap-2 px-4 py-3 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors text-sm font-medium;
    min-height: 48px;
}

.quick-action-btn.primary {
    @apply bg-green-100 hover:bg-green-200 text-green-800;
}

.filter-buttons {
    @apply flex gap-2;
}

.filter-btn {
    @apply px-3 py-2 text-sm font-medium rounded-lg transition-colors;
}

.filter-btn:not(.active) {
    @apply bg-gray-100 text-gray-700 hover:bg-gray-200;
}

.filter-btn.active {
    @apply bg-blue-100 text-blue-800;
}

.no-contacts-message {
    @apply text-center py-12;
}

.contacts-grid {
    @apply space-y-6;
}

.player-group {
    @apply bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm;
}

.player-header {
    @apply bg-gray-50 px-4 py-3 border-b border-gray-200 flex items-center justify-between;
}

.player-name {
    @apply font-semibold text-gray-900;
}

.player-details {
    @apply flex items-center gap-2 text-sm text-gray-600;
}

.jersey-number {
    @apply bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-medium;
}

.position {
    @apply text-gray-500;
}

.contact-count {
    @apply text-sm text-gray-500 font-medium;
}

.player-contacts {
    @apply p-4;
}

.emergency-numbers-section,
.instructions-section,
.venue-section {
    @apply mb-6;
}

.instruction-subtitle {
    @apply font-semibold text-gray-900 mb-3;
}

.emergency-numbers-list {
    @apply grid grid-cols-1 md:grid-cols-3 gap-3;
}

.emergency-number-button {
    @apply bg-red-50 hover:bg-red-100 border border-red-200 p-3 rounded-lg transition-colors text-left;
}

.service-name {
    @apply block text-sm text-gray-700 font-medium;
}

.service-number {
    @apply block text-xl font-bold text-red-600;
}

.instructions-list {
    @apply list-decimal list-inside space-y-2;
}

.instruction-item {
    @apply text-gray-700 leading-relaxed;
}

.venue-info {
    @apply space-y-2;
}

.venue-item {
    @apply text-sm text-gray-700;
}

/* Mobile optimizations */
@media (max-width: 640px) {
    .quick-actions-grid {
        @apply grid-cols-1 gap-2;
    }
    
    .emergency-numbers-list {
        @apply grid-cols-1;
    }
    
    .player-header {
        @apply flex-col items-start gap-2;
    }
    
    .filter-buttons {
        @apply flex-wrap;
    }
    
    .access-details {
        @apply flex-col items-start gap-2;
    }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
    .status-dot {
        @apply animate-none;
    }
}
</style>