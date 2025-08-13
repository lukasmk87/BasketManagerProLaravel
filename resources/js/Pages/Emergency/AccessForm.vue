<template>
    <div class="min-h-screen bg-gradient-to-br from-red-50 to-orange-50">
        <!-- Emergency Header -->
        <div class="emergency-header">
            <div class="container mx-auto px-4 py-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="emergency-icon">
                            <ExclamationTriangleIcon class="w-8 h-8 text-red-600" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Notfallzugriff</h1>
                            <p class="text-sm text-gray-600">{{ teamName }}</p>
                        </div>
                    </div>
                    <div class="emergency-numbers">
                        <button @click="callEmergency('112')" class="emergency-number-btn">
                            <PhoneIcon class="w-5 h-5" />
                            <span>112</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="container mx-auto px-4 py-8">
            <!-- Instructions -->
            <div class="instructions-card mb-6" v-if="!submitted">
                <div class="flex items-start gap-3">
                    <InformationCircleIcon class="w-6 h-6 text-blue-600 mt-1 flex-shrink-0" />
                    <div>
                        <h3 class="font-semibold text-gray-900 mb-2">Wichtige Hinweise</h3>
                        <div class="instruction-text">
                            {{ usageInstructions }}
                        </div>
                        <div class="mt-3 text-sm text-gray-600">
                            <p><strong>Bei lebensbedrohlichen Notfällen sofort 112 anrufen!</strong></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Emergency Contact Info -->
            <div v-if="emergencyContact.person" class="emergency-contact-info mb-6">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="font-semibold text-blue-900 mb-2">Team-Notfallkontakt</h3>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-800 font-medium">{{ emergencyContact.person }}</p>
                            <p class="text-blue-600 text-sm">{{ emergencyContact.phone }}</p>
                        </div>
                        <button 
                            @click="callContact(emergencyContact.phone)"
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"
                        >
                            <PhoneIcon class="w-5 h-5" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Access Form -->
            <div v-if="!submitted" class="access-form-card">
                <form @submit.prevent="submitForm" class="space-y-6">
                    <!-- Urgency Level -->
                    <div class="form-group">
                        <label class="form-label required">
                            Dringlichkeitsstufe
                        </label>
                        <div class="urgency-grid">
                            <label 
                                v-for="level in urgencyLevels" 
                                :key="level.value"
                                class="urgency-option"
                                :class="{ 'selected': form.urgency_level === level.value }"
                            >
                                <input 
                                    type="radio" 
                                    :value="level.value"
                                    v-model="form.urgency_level"
                                    class="sr-only"
                                >
                                <div class="urgency-content">
                                    <div class="urgency-icon" :class="level.color">
                                        <component :is="level.icon" class="w-6 h-6" />
                                    </div>
                                    <div>
                                        <div class="urgency-title">{{ level.title }}</div>
                                        <div class="urgency-description">{{ level.description }}</div>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <div v-if="errors.urgency_level" class="error-text">
                            {{ errors.urgency_level }}
                        </div>
                    </div>

                    <!-- Reason -->
                    <div class="form-group" v-if="requiresReason">
                        <label for="reason" class="form-label">
                            Grund für den Zugriff (optional)
                        </label>
                        <textarea
                            id="reason"
                            v-model="form.reason"
                            class="form-textarea"
                            rows="3"
                            placeholder="Kurze Beschreibung der Situation..."
                        ></textarea>
                        <div v-if="errors.reason" class="error-text">
                            {{ errors.reason }}
                        </div>
                    </div>

                    <!-- Contact Person -->
                    <div class="form-group">
                        <label for="contact_person" class="form-label">
                            Ihr Name (optional)
                        </label>
                        <input
                            type="text"
                            id="contact_person"
                            v-model="form.contact_person"
                            class="form-input"
                            placeholder="Name der Person, die Zugriff benötigt"
                        />
                        <div v-if="errors.contact_person" class="error-text">
                            {{ errors.contact_person }}
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="form-actions">
                        <button 
                            type="submit" 
                            class="submit-btn"
                            :disabled="processing || !form.urgency_level"
                            :class="{ 'processing': processing }"
                        >
                            <span v-if="!processing" class="flex items-center gap-2">
                                <UserGroupIcon class="w-5 h-5" />
                                Notfallkontakte anzeigen
                            </span>
                            <span v-else class="flex items-center gap-2">
                                <div class="animate-spin rounded-full h-5 w-5 border-2 border-white border-t-transparent"></div>
                                Wird verarbeitet...
                            </span>
                        </button>

                        <!-- Direct Access Button for Critical -->
                        <button 
                            v-if="form.urgency_level === 'critical'"
                            @click="directAccess"
                            type="button"
                            class="direct-access-btn"
                        >
                            <span class="flex items-center gap-2">
                                <BoltIcon class="w-5 h-5" />
                                Direktzugriff (Kritischer Notfall)
                            </span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Processing State -->
            <div v-if="submitted && processing" class="processing-card">
                <div class="text-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-4 border-blue-500 border-t-transparent mx-auto mb-4"></div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Zugriff wird verarbeitet...</h3>
                    <p class="text-gray-600">Notfallkontakte werden geladen.</p>
                </div>
            </div>

            <!-- Emergency Numbers -->
            <div class="emergency-numbers-card mt-8">
                <h3 class="font-semibold text-gray-900 mb-4">Notrufnummern</h3>
                <div class="emergency-numbers-grid">
                    <button 
                        v-for="(number, service) in emergencyNumbers"
                        :key="service"
                        @click="callEmergency(number)"
                        class="emergency-number-card"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="service-name">{{ formatServiceName(service) }}</div>
                                <div class="service-number">{{ number }}</div>
                            </div>
                            <PhoneIcon class="w-6 h-6 text-red-600" />
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { 
    ExclamationTriangleIcon,
    InformationCircleIcon,
    PhoneIcon,
    UserGroupIcon,
    BoltIcon,
    FireIcon,
    ExclamationCircleIcon,
    ShieldExclamationIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    accessKey: String,
    teamName: String,
    clubName: String,
    requiresReason: {
        type: Boolean,
        default: false
    },
    usageInstructions: String,
    emergencyContact: {
        type: Object,
        default: () => ({})
    },
    emergencyNumbers: {
        type: Object,
        default: () => ({
            ambulance: '112',
            fire: '112',
            police: '110'
        })
    }
})

const form = reactive({
    urgency_level: '',
    reason: '',
    contact_person: ''
})

const errors = ref({})
const processing = ref(false)
const submitted = ref(false)

const urgencyLevels = [
    {
        value: 'critical',
        title: 'Kritisch',
        description: 'Lebensbedrohliche Situation',
        icon: ExclamationTriangleIcon,
        color: 'text-red-600 bg-red-100'
    },
    {
        value: 'high',
        title: 'Hoch',
        description: 'Schwere Verletzung/Notfall',
        icon: ExclamationCircleIcon,
        color: 'text-orange-600 bg-orange-100'
    },
    {
        value: 'medium',
        title: 'Mittel',
        description: 'Medizinische Aufmerksamkeit nötig',
        icon: ShieldExclamationIcon,
        color: 'text-yellow-600 bg-yellow-100'
    },
    {
        value: 'low',
        title: 'Niedrig',
        description: 'Kontaktaufnahme erforderlich',
        icon: InformationCircleIcon,
        color: 'text-blue-600 bg-blue-100'
    }
]

const submitForm = async () => {
    if (!form.urgency_level) {
        errors.value = { urgency_level: 'Bitte wählen Sie eine Dringlichkeitsstufe aus.' }
        return
    }

    processing.value = true
    submitted.value = true
    errors.value = {}

    try {
        router.post(route('emergency.access.process', props.accessKey), form, {
            onSuccess: () => {
                // Will be handled by Inertia navigation
            },
            onError: (errorResponse) => {
                errors.value = errorResponse
                processing.value = false
                submitted.value = false
            }
        })
    } catch (error) {
        console.error('Emergency access error:', error)
        processing.value = false
        submitted.value = false
        errors.value = { general: 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.' }
    }
}

const directAccess = () => {
    // For critical emergencies, go directly to contacts
    window.location.href = route('emergency.access.direct', props.accessKey)
}

const callEmergency = (number) => {
    // Haptic feedback if available
    if (navigator.vibrate) {
        navigator.vibrate([200, 100, 200])
    }
    
    window.location.href = `tel:${number}`
}

const callContact = (phone) => {
    if (!phone) return
    
    if (navigator.vibrate) {
        navigator.vibrate([100, 50, 100])
    }
    
    window.location.href = `tel:${phone.replace(/\s/g, '')}`
}

const formatServiceName = (service) => {
    const names = {
        ambulance: 'Rettungsdienst',
        fire: 'Feuerwehr',
        police: 'Polizei'
    }
    return names[service] || service
}
</script>

<style scoped>
.emergency-header {
    @apply bg-white border-b border-red-200;
    box-shadow: 0 2px 4px rgba(239, 68, 68, 0.1);
}

.emergency-icon {
    @apply w-12 h-12 bg-red-100 rounded-full flex items-center justify-center;
}

.emergency-number-btn {
    @apply bg-red-600 text-white px-3 py-2 rounded-lg hover:bg-red-700 transition-colors flex items-center gap-2 font-semibold;
    min-height: 44px;
}

.instructions-card {
    @apply bg-blue-50 border border-blue-200 rounded-lg p-6;
}

.instruction-text {
    @apply text-sm text-blue-800 leading-relaxed;
}

.emergency-contact-info {
    @apply bg-white border border-gray-200 rounded-lg p-4 shadow-sm;
}

.access-form-card {
    @apply bg-white border border-gray-200 rounded-lg p-6 shadow-lg;
}

.form-group {
    @apply space-y-2;
}

.form-label {
    @apply block text-sm font-medium text-gray-700;
}

.form-label.required::after {
    content: ' *';
    @apply text-red-500;
}

.urgency-grid {
    @apply grid gap-3;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
}

.urgency-option {
    @apply border border-gray-200 rounded-lg p-4 cursor-pointer hover:border-blue-300 transition-colors;
}

.urgency-option.selected {
    @apply border-blue-500 bg-blue-50;
}

.urgency-content {
    @apply flex items-start gap-3;
}

.urgency-icon {
    @apply w-10 h-10 rounded-full flex items-center justify-center;
}

.urgency-title {
    @apply font-semibold text-gray-900;
}

.urgency-description {
    @apply text-sm text-gray-600;
}

.form-input,
.form-textarea {
    @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
    min-height: 44px;
}

.form-textarea {
    @apply resize-none;
    min-height: 88px;
}

.error-text {
    @apply text-sm text-red-600;
}

.form-actions {
    @apply space-y-3;
}

.submit-btn {
    @apply w-full bg-blue-600 text-white py-4 px-6 rounded-lg hover:bg-blue-700 transition-colors font-semibold text-lg;
    min-height: 56px;
}

.submit-btn:disabled {
    @apply bg-gray-400 cursor-not-allowed;
}

.submit-btn.processing {
    @apply bg-blue-500;
}

.direct-access-btn {
    @apply w-full bg-red-600 text-white py-3 px-6 rounded-lg hover:bg-red-700 transition-colors font-semibold;
    min-height: 48px;
}

.processing-card {
    @apply bg-white border border-gray-200 rounded-lg p-8 shadow-lg;
}

.emergency-numbers-card {
    @apply bg-white border border-red-200 rounded-lg p-6 shadow-lg;
}

.emergency-numbers-grid {
    @apply grid gap-3;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
}

.emergency-number-card {
    @apply bg-red-50 border border-red-200 p-4 rounded-lg hover:bg-red-100 transition-colors;
    min-height: 80px;
}

.service-name {
    @apply font-semibold text-gray-900;
}

.service-number {
    @apply text-2xl font-bold text-red-600;
}

/* Mobile optimizations */
@media (max-width: 640px) {
    .urgency-grid {
        @apply grid-cols-1;
    }
    
    .emergency-numbers-grid {
        @apply grid-cols-1;
    }
    
    .submit-btn {
        @apply text-base py-5;
        min-height: 60px;
    }
    
    .access-form-card {
        @apply p-4;
    }
}

/* Landscape orientation */
@media (orientation: landscape) and (max-height: 500px) {
    .container {
        @apply py-4;
    }
    
    .instructions-card {
        @apply p-4;
    }
    
    .urgency-grid {
        @apply grid-cols-2;
    }
    
    .form-group {
        @apply space-y-1;
    }
}

/* High contrast mode */
@media (prefers-contrast: high) {
    .urgency-option {
        @apply border-2;
    }
    
    .submit-btn,
    .direct-access-btn,
    .emergency-number-btn {
        @apply border-2 border-current;
    }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
    * {
        transition: none !important;
        animation: none !important;
    }
}
</style>