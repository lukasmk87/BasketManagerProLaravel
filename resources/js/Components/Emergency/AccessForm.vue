<template>
    <div class="emergency-access-form">
        <div class="form-header">
            <div class="emergency-icon">
                <ExclamationTriangleIcon class="w-12 h-12 text-red-600" />
            </div>
            <h1 class="form-title">Emergency Access</h1>
            <p class="form-subtitle">{{ teamName }}</p>
        </div>

        <!-- Emergency Instructions -->
        <div v-if="emergencyContact" class="emergency-contact-banner">
            <PhoneIcon class="w-5 h-5 text-red-600" />
            <div class="contact-info">
                <p class="contact-name">Emergency Contact: {{ emergencyContact.person }}</p>
                <a :href="`tel:${emergencyContact.phone}`" class="contact-phone">
                    {{ emergencyContact.phone }}
                </a>
            </div>
        </div>

        <!-- Quick Emergency Numbers -->
        <div class="emergency-numbers">
            <h3 class="section-title">Emergency Numbers</h3>
            <div class="number-grid">
                <a href="tel:112" class="emergency-number ambulance">
                    <div class="number-icon">🚑</div>
                    <div class="number-info">
                        <span class="number">112</span>
                        <span class="label">Ambulance/Fire</span>
                    </div>
                </a>
                <a href="tel:110" class="emergency-number police">
                    <div class="number-icon">👮</div>
                    <div class="number-info">
                        <span class="number">110</span>
                        <span class="label">Police</span>
                    </div>
                </a>
            </div>
        </div>

        <!-- Access Form -->
        <form @submit.prevent="submitAccess" class="access-form">
            <div class="form-group">
                <label for="urgency_level" class="form-label">Urgency Level *</label>
                <select 
                    id="urgency_level" 
                    v-model="form.urgency_level" 
                    class="form-select"
                    required
                    @change="onUrgencyChange"
                >
                    <option value="">Select urgency level</option>
                    <option value="low">Low - General Information</option>
                    <option value="medium">Medium - Minor Incident</option>
                    <option value="high">High - Serious Incident</option>
                    <option value="critical">Critical - Life Threatening</option>
                </select>
            </div>

            <div v-if="requiresReason || form.urgency_level" class="form-group">
                <label for="reason" class="form-label">
                    {{ form.urgency_level === 'critical' ? 'Describe the emergency *' : 'Reason for access' }}
                    {{ requiresReason ? '*' : '' }}
                </label>
                <textarea 
                    id="reason"
                    v-model="form.reason" 
                    class="form-textarea"
                    :required="requiresReason || form.urgency_level === 'critical'"
                    :placeholder="getReasonPlaceholder()"
                    rows="3"
                    maxlength="500"
                ></textarea>
                <div class="char-count">{{ form.reason.length }}/500</div>
            </div>

            <div class="form-group">
                <label for="contact_person" class="form-label">Your Name</label>
                <input 
                    id="contact_person"
                    type="text" 
                    v-model="form.contact_person" 
                    class="form-input"
                    placeholder="Who is requesting access?"
                    maxlength="255"
                />
            </div>

            <div v-if="form.urgency_level === 'critical'" class="critical-warning">
                <ExclamationTriangleIcon class="w-5 h-5 text-red-600" />
                <div class="warning-content">
                    <p class="warning-title">Critical Emergency</p>
                    <p class="warning-text">
                        If this is a life-threatening emergency, call 112 immediately before accessing contacts.
                    </p>
                    <div class="warning-actions">
                        <a href="tel:112" class="emergency-call-btn">
                            <PhoneIcon class="w-4 h-4" />
                            Call 112 Now
                        </a>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="form-actions">
                <button 
                    type="submit" 
                    class="submit-btn"
                    :class="{ 
                        'critical': form.urgency_level === 'critical',
                        'high': form.urgency_level === 'high',
                        'loading': isLoading 
                    }"
                    :disabled="isLoading || !form.urgency_level"
                >
                    <PhoneIcon v-if="!isLoading" class="w-5 h-5" />
                    <div v-else class="loading-spinner"></div>
                    <span>
                        {{ isLoading ? 'Accessing...' : 'Access Emergency Contacts' }}
                    </span>
                </button>
            </div>
        </form>

        <!-- Usage Instructions -->
        <div v-if="usageInstructions" class="usage-instructions">
            <details class="instructions-details">
                <summary class="instructions-summary">Usage Instructions</summary>
                <div class="instructions-content">
                    <p>{{ usageInstructions }}</p>
                </div>
            </details>
        </div>

        <!-- PWA Install Prompt -->
        <div v-if="showPWAPrompt" class="pwa-install-prompt">
            <div class="pwa-prompt-content">
                <SmartphoneIcon class="w-6 h-6 text-blue-600" />
                <div class="pwa-prompt-text">
                    <h4>Install Emergency App</h4>
                    <p>Add to home screen for faster access during emergencies</p>
                </div>
                <button @click="showPWAInstallModal" class="pwa-install-btn">
                    Install
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { 
    ExclamationTriangleIcon, 
    PhoneIcon, 
    SmartphoneIcon 
} from '@heroicons/vue/24/outline'

const props = defineProps({
    accessKey: {
        type: String,
        required: true
    },
    teamName: {
        type: String,
        required: true
    },
    requiresReason: {
        type: Boolean,
        default: false
    },
    usageInstructions: {
        type: String,
        default: null
    },
    emergencyContact: {
        type: Object,
        default: null
    }
})

const isLoading = ref(false)
const showPWAPrompt = ref(false)

const form = ref({
    urgency_level: '',
    reason: '',
    contact_person: ''
})

const getReasonPlaceholder = () => {
    switch (form.value.urgency_level) {
        case 'critical':
            return 'Describe the life-threatening emergency in detail...'
        case 'high':
            return 'Describe the serious incident requiring immediate attention...'
        case 'medium':
            return 'Describe the incident or situation...'
        case 'low':
            return 'Reason for accessing emergency contact information...'
        default:
            return 'Provide details about why you need access...'
    }
}

const onUrgencyChange = () => {
    // Haptic feedback for critical selection
    if (form.value.urgency_level === 'critical' && navigator.vibrate) {
        navigator.vibrate([200, 100, 200])
    }
    
    // Auto-focus on reason field for critical emergencies
    if (form.value.urgency_level === 'critical') {
        setTimeout(() => {
            const reasonField = document.getElementById('reason')
            reasonField?.focus()
        }, 100)
    }
}

const submitAccess = async () => {
    if (!form.value.urgency_level) return
    
    isLoading.value = true
    
    try {
        // Log access attempt for analytics
        if ('navigator' in window && 'sendBeacon' in navigator) {
            const logData = {
                access_key: props.accessKey,
                urgency_level: form.value.urgency_level,
                has_reason: !!form.value.reason,
                timestamp: new Date().toISOString()
            }
            navigator.sendBeacon('/api/emergency/log-access', JSON.stringify(logData))
        }
        
        // Submit form via Inertia
        router.post(route('emergency.access.process', props.accessKey), form.value, {
            onSuccess: () => {
                // Success - redirect will happen automatically
            },
            onError: (errors) => {
                console.error('Access failed:', errors)
                // Show error message
                alert('Access failed. Please try again or contact emergency services.')
            },
            onFinish: () => {
                isLoading.value = false
            }
        })
        
    } catch (error) {
        console.error('Emergency access error:', error)
        alert('An error occurred. Please try again or call 112 for immediate help.')
        isLoading.value = false
    }
}

const showPWAInstallModal = () => {
    // Navigate to PWA install page
    window.location.href = route('emergency.pwa.install', props.accessKey)
}

onMounted(() => {
    // Check if PWA prompt should be shown
    if ('serviceWorker' in navigator && !window.navigator.standalone) {
        // Show PWA install prompt for mobile devices
        const isMobile = /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
        showPWAPrompt.value = isMobile
    }
    
    // Focus on first form element
    const firstInput = document.getElementById('urgency_level')
    firstInput?.focus()
    
    // Set up geolocation for incident reporting
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                sessionStorage.setItem('emergency_location', JSON.stringify({
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy,
                    timestamp: Date.now()
                }))
            },
            (error) => {
                console.log('Geolocation error:', error)
            },
            { enableHighAccuracy: true, timeout: 10000 }
        )
    }
})
</script>

<style scoped>
.emergency-access-form {
    @apply max-w-md mx-auto p-6 bg-white min-h-screen;
    font-size: 16px; /* Prevent zoom on iOS */
}

.form-header {
    @apply text-center mb-8;
}

.emergency-icon {
    @apply flex justify-center mb-4;
}

.form-title {
    @apply text-3xl font-bold text-gray-900 mb-2;
}

.form-subtitle {
    @apply text-lg text-gray-600 font-medium;
}

.emergency-contact-banner {
    @apply flex items-center gap-3 p-4 bg-red-50 border border-red-200 rounded-lg mb-6;
}

.contact-info {
    @apply flex-1;
}

.contact-name {
    @apply font-medium text-red-900;
}

.contact-phone {
    @apply text-red-700 font-mono text-lg;
    text-decoration: none;
}

.emergency-numbers {
    @apply mb-8;
}

.section-title {
    @apply text-lg font-semibold text-gray-900 mb-4;
}

.number-grid {
    @apply grid grid-cols-2 gap-4;
}

.emergency-number {
    @apply flex items-center gap-3 p-4 bg-red-600 text-white rounded-lg transition-transform;
    text-decoration: none;
    min-height: 72px;
}

.emergency-number:active {
    transform: scale(0.95);
}

.emergency-number.ambulance {
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

.label {
    @apply text-sm opacity-90;
}

.access-form {
    @apply space-y-6;
}

.form-group {
    @apply space-y-2;
}

.form-label {
    @apply block text-sm font-medium text-gray-700;
}

.form-select,
.form-input,
.form-textarea {
    @apply w-full px-4 py-3 border border-gray-300 rounded-lg;
    @apply focus:ring-2 focus:ring-red-500 focus:border-red-500;
    font-size: 16px; /* Prevent zoom on iOS */
    min-height: 48px;
}

.form-textarea {
    @apply resize-none;
    min-height: 80px;
}

.char-count {
    @apply text-xs text-gray-500 text-right;
}

.critical-warning {
    @apply flex gap-3 p-4 bg-red-50 border border-red-200 rounded-lg;
}

.warning-content {
    @apply flex-1;
}

.warning-title {
    @apply font-semibold text-red-900 mb-1;
}

.warning-text {
    @apply text-red-800 text-sm mb-3;
}

.warning-actions {
    @apply flex;
}

.emergency-call-btn {
    @apply flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg;
    @apply hover:bg-red-700 transition-colors;
    text-decoration: none;
    font-weight: 600;
}

.form-actions {
    @apply mt-8;
}

.submit-btn {
    @apply w-full flex items-center justify-center gap-3 px-6 py-4;
    @apply bg-gray-600 text-white rounded-lg font-semibold;
    @apply transition-all duration-200;
    min-height: 56px;
    font-size: 1.1rem;
}

.submit-btn:not(:disabled):hover {
    @apply bg-gray-700;
}

.submit-btn.high {
    @apply bg-orange-600 hover:bg-orange-700;
}

.submit-btn.critical {
    @apply bg-red-600 hover:bg-red-700;
    animation: critical-pulse 2s infinite;
}

.submit-btn:disabled {
    @apply bg-gray-400 cursor-not-allowed;
}

.submit-btn.loading {
    @apply bg-gray-500;
}

.loading-spinner {
    @apply w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin;
}

.usage-instructions {
    @apply mt-8 border-t border-gray-200 pt-6;
}

.instructions-details {
    @apply cursor-pointer;
}

.instructions-summary {
    @apply font-medium text-gray-700 hover:text-gray-900;
}

.instructions-content {
    @apply mt-3 text-sm text-gray-600 leading-relaxed;
}

.pwa-install-prompt {
    @apply mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg;
}

.pwa-prompt-content {
    @apply flex items-center gap-3;
}

.pwa-prompt-text {
    @apply flex-1;
}

.pwa-prompt-text h4 {
    @apply font-medium text-blue-900;
}

.pwa-prompt-text p {
    @apply text-sm text-blue-700;
}

.pwa-install-btn {
    @apply px-3 py-2 bg-blue-600 text-white text-sm rounded-lg;
    @apply hover:bg-blue-700 transition-colors;
}

/* Animations */
@keyframes critical-pulse {
    0%, 100% {
        box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.7);
    }
    50% {
        box-shadow: 0 0 0 8px rgba(220, 38, 38, 0);
    }
}

/* Dark mode */
@media (prefers-color-scheme: dark) {
    .emergency-access-form {
        @apply bg-gray-900 text-white;
    }
    
    .form-title {
        @apply text-white;
    }
    
    .form-subtitle {
        @apply text-gray-300;
    }
    
    .section-title {
        @apply text-white;
    }
    
    .form-select,
    .form-input,
    .form-textarea {
        @apply bg-gray-800 border-gray-600 text-white;
        @apply focus:ring-red-400 focus:border-red-400;
    }
}

/* High contrast mode */
@media (prefers-contrast: high) {
    .emergency-number,
    .submit-btn,
    .emergency-call-btn {
        @apply border-2 border-current;
    }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
    .emergency-number,
    .submit-btn {
        transition: none;
    }
    
    .critical-pulse {
        animation: none;
    }
}

/* Mobile optimizations */
@media (max-width: 640px) {
    .emergency-access-form {
        @apply p-4;
    }
    
    .form-title {
        @apply text-2xl;
    }
    
    .number-grid {
        @apply grid-cols-1 gap-3;
    }
    
    .emergency-number {
        @apply p-5;
        min-height: 80px;
    }
    
    .submit-btn {
        @apply py-5;
        min-height: 64px;
        font-size: 1.2rem;
    }
}
</style>