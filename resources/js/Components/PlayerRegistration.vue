<template>
    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
        <div class="p-6">
            <h3 class="text-lg font-semibold mb-4">Anmeldung</h3>
            
            <!-- Current Status -->
            <div v-if="currentRegistration" class="mb-4 p-4 rounded-lg" :class="getStatusClasses(currentRegistration.availability_status)">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium">{{ getStatusLabel(currentRegistration.availability_status) }}</p>
                        <p v-if="currentRegistration.player_notes" class="text-sm mt-1">
                            {{ currentRegistration.player_notes }}
                        </p>
                    </div>
                    <div class="text-sm text-gray-500">
                        {{ formatDateTime(currentRegistration.registered_at) }}
                    </div>
                </div>
            </div>

            <!-- Registration Form -->
            <div v-if="canRegister">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                    <button
                        @click="updateStatus('available')"
                        :disabled="processing || isDeadlinePassed"
                        class="flex items-center justify-center px-4 py-2 rounded-lg font-medium transition-colors"
                        :class="getButtonClasses('available')"
                    >
                        <CheckCircleIcon class="w-5 h-5 mr-2" />
                        Zusagen
                    </button>
                    <button
                        @click="updateStatus('maybe')"
                        :disabled="processing || isDeadlinePassed"
                        class="flex items-center justify-center px-4 py-2 rounded-lg font-medium transition-colors"
                        :class="getButtonClasses('maybe')"
                    >
                        <QuestionMarkCircleIcon class="w-5 h-5 mr-2" />
                        Unsicher
                    </button>
                    <button
                        @click="updateStatus('unavailable')"
                        :disabled="processing || isDeadlinePassed"
                        class="flex items-center justify-center px-4 py-2 rounded-lg font-medium transition-colors"
                        :class="getButtonClasses('unavailable')"
                    >
                        <XCircleIcon class="w-5 h-5 mr-2" />
                        Absagen
                    </button>
                </div>

                <!-- Notes Input -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Notizen (optional)
                    </label>
                    <textarea
                        v-model="form.notes"
                        :disabled="processing || isDeadlinePassed"
                        rows="2"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Grund für Absage oder zusätzliche Informationen..."
                    ></textarea>
                </div>

                <!-- Deadline Warning -->
                <div v-if="deadline && !isDeadlinePassed" class="text-sm text-amber-600 mb-2">
                    Anmeldeschluss: {{ formatDateTime(deadline) }}
                </div>
                <div v-if="isDeadlinePassed" class="text-sm text-red-600 mb-2">
                    Der Anmeldeschluss ist bereits verstrichen.
                </div>
            </div>

            <!-- No Permission Message -->
            <div v-else-if="!userPlayer" class="text-center text-gray-500 py-4">
                <p>Sie sind nicht als Spieler registriert.</p>
            </div>

            <!-- Error Message -->
            <div v-if="errorMessage" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-md">
                <p class="text-sm text-red-600">{{ errorMessage }}</p>
            </div>

            <!-- Success Message -->
            <div v-if="successMessage" class="mt-4 p-3 bg-green-50 border border-green-200 rounded-md">
                <p class="text-sm text-green-600">{{ successMessage }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import { CheckCircleIcon, XCircleIcon, QuestionMarkCircleIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    type: {
        type: String,
        required: true,
        validator: value => ['game', 'training'].includes(value)
    },
    entityId: {
        type: [String, Number],
        required: true
    },
    currentRegistration: {
        type: Object,
        default: null
    },
    deadline: {
        type: String,
        default: null
    }
})

const emit = defineEmits(['registration-updated'])

const page = usePage()
const processing = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const form = useForm({
    availability_status: props.currentRegistration?.availability_status || 'available',
    notes: props.currentRegistration?.player_notes || ''
})

// Get user's player information
const userPlayer = computed(() => {
    return page.props.auth?.user?.player || null
})

const canRegister = computed(() => {
    return userPlayer.value !== null
})

const isDeadlinePassed = computed(() => {
    if (!props.deadline) return false
    return new Date(props.deadline) < new Date()
})

const updateStatus = async (status) => {
    if (processing.value || isDeadlinePassed.value) return

    processing.value = true
    errorMessage.value = ''
    successMessage.value = ''
    form.availability_status = status

    try {
        if (props.type === 'game') {
            await updateGameRegistration(status)
        } else {
            await updateTrainingRegistration(status)
        }
        
        successMessage.value = 'Anmeldung erfolgreich aktualisiert!'
        emit('registration-updated')
        
        // Clear success message after 3 seconds
        setTimeout(() => {
            successMessage.value = ''
        }, 3000)
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Ein Fehler ist aufgetreten.'
    } finally {
        processing.value = false
    }
}

const updateGameRegistration = async (status) => {
    const url = props.currentRegistration 
        ? `/api/games/${props.entityId}/registrations/availability`
        : `/api/games/${props.entityId}/registrations`
    
    const method = props.currentRegistration ? 'put' : 'post'
    const data = {
        player_id: userPlayer.value.id,
        availability_status: status,
        notes: form.notes
    }

    const response = await axios[method](url, data)
    return response.data
}

const updateTrainingRegistration = async (status) => {
    const url = props.currentRegistration 
        ? `/api/training-sessions/${props.entityId}/registrations/status`
        : `/api/training-sessions/${props.entityId}/registrations`
    
    const method = props.currentRegistration ? 'put' : 'post'
    const data = {
        player_id: userPlayer.value.id,
        status: status === 'available' ? 'registered' : 'cancelled',
        registration_notes: form.notes,
        cancellation_reason: status === 'unavailable' ? form.notes : null
    }

    const response = await axios[method](url, data)
    return response.data
}

const getStatusLabel = (status) => {
    const labels = {
        'available': 'Zugesagt',
        'unavailable': 'Abgesagt',
        'maybe': 'Unsicher',
        'injured': 'Verletzt',
        'suspended': 'Gesperrt',
        'registered': 'Angemeldet',
        'cancelled': 'Abgesagt',
        'confirmed': 'Bestätigt'
    }
    return labels[status] || status
}

const getStatusClasses = (status) => {
    const classes = {
        'available': 'bg-green-50 border border-green-200',
        'unavailable': 'bg-red-50 border border-red-200',
        'maybe': 'bg-yellow-50 border border-yellow-200',
        'injured': 'bg-orange-50 border border-orange-200',
        'suspended': 'bg-red-50 border border-red-200',
        'registered': 'bg-blue-50 border border-blue-200',
        'cancelled': 'bg-red-50 border border-red-200',
        'confirmed': 'bg-green-50 border border-green-200'
    }
    return classes[status] || 'bg-gray-50 border border-gray-200'
}

const getButtonClasses = (status) => {
    const current = props.currentRegistration?.availability_status || 
                   (props.currentRegistration?.status === 'registered' ? 'available' : 'unavailable')
    
    if (current === status) {
        if (status === 'available') return 'bg-green-600 text-white hover:bg-green-700'
        if (status === 'maybe') return 'bg-yellow-500 text-white hover:bg-yellow-600'
        if (status === 'unavailable') return 'bg-red-600 text-white hover:bg-red-700'
    }
    
    return 'bg-gray-200 text-gray-700 hover:bg-gray-300'
}

const formatDateTime = (dateString) => {
    if (!dateString) return ''
    const date = new Date(dateString)
    return date.toLocaleString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

// Update form when currentRegistration changes
watch(() => props.currentRegistration, (newRegistration) => {
    if (newRegistration) {
        form.availability_status = newRegistration.availability_status || 
                                  (newRegistration.status === 'registered' ? 'available' : 'unavailable')
        form.notes = newRegistration.player_notes || newRegistration.registration_notes || ''
    }
}, { immediate: true })
</script>