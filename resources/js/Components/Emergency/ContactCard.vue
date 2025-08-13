<template>
    <div class="emergency-contact-card" :class="{ 'primary': contact.is_primary, 'urgent': urgentMode }">
        <!-- Contact Header -->
        <div class="contact-header">
            <div class="contact-info">
                <h3 class="contact-name">
                    {{ contact.name }}
                    <span v-if="contact.is_primary" class="primary-badge">★ Primary</span>
                </h3>
                <p class="contact-relationship">{{ formatRelationship(contact.relationship) }}</p>
                <p class="player-info">For: {{ contact.player_name }}</p>
            </div>
            <div class="contact-priority">
                <span class="priority-badge" :class="`priority-${contact.priority}`">
                    Priority {{ contact.priority }}
                </span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="contact-actions">
            <button 
                @click="callContact(contact.phone)" 
                class="action-btn call-btn"
                :disabled="!contact.phone"
                :class="{ 'pulse': urgentMode }"
            >
                <PhoneIcon class="w-6 h-6" />
                <span>Call Now</span>
            </button>
            
            <button 
                v-if="contact.secondary_phone" 
                @click="callContact(contact.secondary_phone)"
                class="action-btn call-btn secondary"
            >
                <PhoneIcon class="w-5 h-5" />
                <span>Alt. Number</span>
            </button>

            <button 
                @click="sendSMS(contact.phone)"
                class="action-btn sms-btn"
                :disabled="!contact.phone"
            >
                <ChatBubbleLeftIcon class="w-5 h-5" />
                <span>SMS</span>
            </button>

            <button 
                v-if="contact.email"
                @click="sendEmail(contact.email)"
                class="action-btn email-btn"
            >
                <EnvelopeIcon class="w-5 h-5" />
                <span>Email</span>
            </button>
        </div>

        <!-- Contact Details -->
        <div class="contact-details" v-if="showDetails">
            <div class="detail-item" v-if="contact.medical_training">
                <HeartIcon class="w-4 h-4 text-red-500" />
                <span>Has medical training</span>
            </div>
            
            <div class="detail-item" v-if="contact.pickup_authorized">
                <CheckIcon class="w-4 h-4 text-green-500" />
                <span>Authorized for pickup</span>
            </div>
            
            <div class="detail-item" v-if="contact.medical_decisions">
                <DocumentCheckIcon class="w-4 h-4 text-blue-500" />
                <span>Can make medical decisions</span>
            </div>

            <div class="detail-item" v-if="contact.available_24_7">
                <ClockIcon class="w-4 h-4 text-green-500" />
                <span>Available 24/7</span>
            </div>

            <div class="special-instructions" v-if="contact.special_instructions">
                <h4>Special Instructions:</h4>
                <p>{{ contact.special_instructions }}</p>
            </div>
        </div>

        <!-- Toggle Details -->
        <button @click="toggleDetails" class="toggle-details">
            <ChevronDownIcon :class="{ 'rotate-180': showDetails }" class="w-4 h-4" />
            <span>{{ showDetails ? 'Less Info' : 'More Info' }}</span>
        </button>

        <!-- Last Contact Status -->
        <div v-if="showLastContact && lastContactResult" class="last-contact-status">
            <div class="status-indicator" :class="lastContactResult.status">
                <span class="status-dot"></span>
                <span class="status-text">
                    Last contact: {{ formatLastContact(lastContactResult) }}
                </span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { 
    PhoneIcon, 
    ChatBubbleLeftIcon, 
    EnvelopeIcon,
    ChevronDownIcon, 
    CheckIcon, 
    DocumentCheckIcon,
    HeartIcon,
    ClockIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    contact: {
        type: Object,
        required: true
    },
    urgentMode: {
        type: Boolean,
        default: false
    },
    showLastContact: {
        type: Boolean,
        default: false
    },
    lastContactResult: {
        type: Object,
        default: null
    }
})

const emit = defineEmits(['contact-attempted', 'details-toggled'])

const showDetails = ref(false)

const formatRelationship = (relationship) => {
    const relationships = {
        'mother': 'Mutter',
        'father': 'Vater', 
        'parent': 'Elternteil',
        'guardian': 'Vormund',
        'sibling': 'Geschwister',
        'grandparent': 'Großeltern',
        'partner': 'Partner',
        'spouse': 'Ehepartner',
        'friend': 'Freund',
        'other': 'Sonstiges'
    }
    return relationships[relationship] || relationship
}

const formatLastContact = (result) => {
    if (!result) return 'Unknown'
    
    const statusMap = {
        'success': 'Erfolgreich',
        'no_answer': 'Keine Antwort',
        'unreachable': 'Nicht erreichbar',
        'invalid': 'Ungültig'
    }
    
    return `${statusMap[result.status] || result.status} (${formatTimeAgo(result.timestamp)})`
}

const formatTimeAgo = (timestamp) => {
    const now = new Date()
    const then = new Date(timestamp)
    const diffInSeconds = Math.floor((now - then) / 1000)
    
    if (diffInSeconds < 60) return 'Gerade eben'
    if (diffInSeconds < 3600) return `vor ${Math.floor(diffInSeconds / 60)} Min`
    if (diffInSeconds < 86400) return `vor ${Math.floor(diffInSeconds / 3600)} Std`
    return `vor ${Math.floor(diffInSeconds / 86400)} Tagen`
}

const callContact = async (phoneNumber) => {
    if (!phoneNumber) return
    
    // Record the contact attempt
    emit('contact-attempted', {
        type: 'phone',
        phone: phoneNumber,
        contact: props.contact.name,
        timestamp: new Date().toISOString()
    })
    
    // Haptic feedback if available
    if (navigator.vibrate) {
        navigator.vibrate([100, 50, 100])
    }
    
    // Make the call
    window.location.href = `tel:${phoneNumber.replace(/\s/g, '')}`
}

const sendSMS = async (phoneNumber) => {
    if (!phoneNumber) return
    
    const message = encodeURIComponent(
        `Notfall bei ${props.contact.player_name}. Bitte melden Sie sich umgehend. Basketball-Team`
    )
    
    emit('contact-attempted', {
        type: 'sms',
        phone: phoneNumber,
        contact: props.contact.name,
        timestamp: new Date().toISOString()
    })
    
    window.location.href = `sms:${phoneNumber.replace(/\s/g, '')}?body=${message}`
}

const sendEmail = async (email) => {
    if (!email) return
    
    const subject = encodeURIComponent('Basketball Notfall - Dringend')
    const body = encodeURIComponent(
        `Notfall bei ${props.contact.player_name}.\n\nBitte melden Sie sich umgehend.\n\nBasketball-Team`
    )
    
    emit('contact-attempted', {
        type: 'email',
        email: email,
        contact: props.contact.name,
        timestamp: new Date().toISOString()
    })
    
    window.location.href = `mailto:${email}?subject=${subject}&body=${body}`
}

const toggleDetails = () => {
    showDetails.value = !showDetails.value
    emit('details-toggled', {
        contact_id: props.contact.id,
        showing_details: showDetails.value
    })
}
</script>

<style scoped>
.emergency-contact-card {
    @apply bg-white rounded-lg shadow-lg border border-gray-200 p-4 mb-4;
    transition: all 0.2s ease;
    min-height: 200px; /* Ensure adequate touch targets */
}

.emergency-contact-card.primary {
    @apply border-yellow-400 bg-yellow-50;
    box-shadow: 0 4px 6px -1px rgba(251, 191, 36, 0.1), 0 2px 4px -1px rgba(251, 191, 36, 0.06);
}

.emergency-contact-card.urgent {
    @apply border-red-500 bg-red-50;
    animation: urgent-pulse 2s infinite;
}

.contact-header {
    @apply flex justify-between items-start mb-4;
}

.contact-name {
    @apply text-lg font-semibold text-gray-900 mb-1;
    font-size: 1.25rem; /* Larger for readability */
}

.primary-badge {
    @apply inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 ml-2;
}

.contact-relationship {
    @apply text-sm text-gray-600 font-medium;
}

.player-info {
    @apply text-sm text-gray-500 italic;
}

.priority-badge {
    @apply inline-flex items-center px-2 py-1 rounded-full text-xs font-medium;
}

.priority-1 { @apply bg-red-100 text-red-800; }
.priority-2 { @apply bg-orange-100 text-orange-800; }
.priority-3 { @apply bg-yellow-100 text-yellow-800; }

.contact-actions {
    @apply grid grid-cols-2 gap-3 mb-4;
}

.action-btn {
    @apply flex items-center justify-center gap-2 px-4 py-4 rounded-lg font-medium text-white transition-all duration-200;
    min-height: 56px; /* Large touch target for accessibility */
    font-size: 0.95rem;
    touch-action: manipulation; /* Prevents zoom on double-tap */
}

.action-btn:active {
    transform: scale(0.95);
}

.call-btn {
    @apply bg-green-600 hover:bg-green-700 active:bg-green-800;
    box-shadow: 0 4px 6px -1px rgba(34, 197, 94, 0.2);
}

.call-btn.pulse {
    animation: call-pulse 1.5s infinite;
}

.call-btn.secondary {
    @apply bg-green-500 hover:bg-green-600 text-sm;
    min-height: 48px;
}

.sms-btn {
    @apply bg-blue-600 hover:bg-blue-700 active:bg-blue-800;
    box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
}

.email-btn {
    @apply bg-purple-600 hover:bg-purple-700 active:bg-purple-800;
    box-shadow: 0 4px 6px -1px rgba(147, 51, 234, 0.2);
}

.action-btn:disabled {
    @apply bg-gray-400 cursor-not-allowed;
    opacity: 0.5;
}

.contact-details {
    @apply border-t border-gray-200 pt-3 mb-3;
}

.detail-item {
    @apply flex items-center gap-2 text-sm text-gray-700 mb-2 py-1;
}

.special-instructions {
    @apply mt-3 p-3 bg-amber-50 border border-amber-200 rounded-lg;
}

.special-instructions h4 {
    @apply font-medium text-amber-900 mb-1;
}

.special-instructions p {
    @apply text-sm text-amber-800;
}

.toggle-details {
    @apply flex items-center justify-center gap-1 w-full py-3 text-sm text-gray-600 hover:text-gray-800 transition-colors;
    min-height: 44px; /* Adequate touch target */
}

.rotate-180 {
    transform: rotate(180deg);
}

.last-contact-status {
    @apply mt-3 pt-3 border-t border-gray-200;
}

.status-indicator {
    @apply flex items-center gap-2;
}

.status-dot {
    @apply w-2 h-2 rounded-full;
}

.status-indicator.success .status-dot {
    @apply bg-green-500;
}

.status-indicator.no_answer .status-dot {
    @apply bg-yellow-500;
}

.status-indicator.unreachable .status-dot {
    @apply bg-red-500;
}

.status-indicator.invalid .status-dot {
    @apply bg-gray-500;
}

.status-text {
    @apply text-xs text-gray-600;
}

/* Animations */
@keyframes urgent-pulse {
    0%, 100% {
        box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
    }
    50% {
        box-shadow: 0 0 0 10px rgba(239, 68, 68, 0);
    }
}

@keyframes call-pulse {
    0%, 100% {
        box-shadow: 0 4px 6px -1px rgba(34, 197, 94, 0.2), 0 0 0 0 rgba(34, 197, 94, 0.7);
    }
    50% {
        box-shadow: 0 4px 6px -1px rgba(34, 197, 94, 0.2), 0 0 0 8px rgba(34, 197, 94, 0);
    }
}

/* Dark mode support for emergency situations */
@media (prefers-color-scheme: dark) {
    .emergency-contact-card {
        @apply bg-gray-800 border-gray-700;
    }
    
    .contact-name {
        @apply text-gray-100;
    }
    
    .contact-relationship,
    .player-info {
        @apply text-gray-300;
    }

    .contact-details {
        @apply border-gray-700;
    }
}

/* High contrast mode for emergency situations */
@media (prefers-contrast: high) {
    .emergency-contact-card {
        @apply border-2;
    }
    
    .action-btn {
        @apply border-2 border-current;
    }
}

/* Reduced motion for accessibility */
@media (prefers-reduced-motion: reduce) {
    .emergency-contact-card,
    .action-btn {
        transition: none;
    }
    
    .urgent-pulse,
    .call-pulse {
        animation: none;
    }
}

/* Touch-friendly sizing for mobile */
@media (max-width: 640px) {
    .contact-actions {
        @apply grid-cols-1 gap-2;
    }
    
    .action-btn {
        @apply py-5;
        min-height: 60px;
        font-size: 1rem;
    }
    
    .contact-name {
        font-size: 1.375rem;
    }
}

/* Landscape orientation optimization */
@media (orientation: landscape) and (max-height: 500px) {
    .emergency-contact-card {
        @apply mb-2 p-3;
        min-height: 150px;
    }
    
    .contact-actions {
        @apply grid-cols-4 gap-2;
    }
    
    .action-btn {
        @apply py-2;
        min-height: 44px;
        font-size: 0.875rem;
    }
}
</style>