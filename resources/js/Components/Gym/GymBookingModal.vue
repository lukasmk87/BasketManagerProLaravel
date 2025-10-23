<template>
    <DialogModal :show="show" @close="$emit('close')" max-width="2xl">
        <template #title>
            <div class="flex items-center">
                <CalendarIcon class="h-6 w-6 mr-2 text-blue-600" />
                Buchung verwalten
            </div>
        </template>

        <template #content>
            <div v-if="booking" class="space-y-6">
                <!-- Booking Info -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">Buchungsdetails</h4>
                            <div class="space-y-1 text-sm text-gray-600">
                                <div><strong>Datum:</strong> {{ formatDate(booking.booking_date) }}</div>
                                <div><strong>Zeit:</strong> {{ formatTime(booking.start_time) }} - {{ formatTime(booking.end_time) }}</div>
                                <div><strong>Dauer:</strong> {{ booking.duration_minutes }} Minuten</div>
                                <div><strong>Halle:</strong> {{ booking.gym_time_slot?.gym_hall?.name }}</div>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">Team & Status</h4>
                            <div class="space-y-1 text-sm text-gray-600">
                                <div><strong>Team:</strong> {{ booking.team?.name }}</div>
                                <div>
                                    <strong>Status:</strong> 
                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                          :class="getStatusBadgeClass(booking.status)">
                                        {{ getStatusText(booking.status) }}
                                    </span>
                                </div>
                                <div v-if="booking.booked_by_user">
                                    <strong>Gebucht von:</strong> {{ booking.booked_by_user.name }}
                                </div>
                                <!-- Court Information -->
                                <div v-if="booking.court_names">
                                    <strong>Courts:</strong> {{ booking.court_names }}
                                </div>
                                <div v-if="booking.is_partial_court">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                        Teilcourt ({{ booking.court_percentage }}%)
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Release Information (if released) -->
                <div v-if="booking.status === 'released' && booking.release_reason" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <h4 class="font-medium text-yellow-800 mb-2 flex items-center">
                        <ExclamationTriangleIcon class="h-5 w-5 mr-2" />
                        Freigabe-Information
                    </h4>
                    <p class="text-sm text-yellow-700">{{ booking.release_reason }}</p>
                    <div class="mt-2 text-xs text-yellow-600">
                        Freigegeben am {{ formatDateTime(booking.released_at) }} 
                        von {{ booking.released_by_user?.name }}
                    </div>
                </div>

                <!-- Pending Requests (if any) -->
                <div v-if="booking.requests && booking.requests.length > 0" class="space-y-4">
                    <h4 class="font-medium text-gray-900">Offene Anfragen ({{ booking.requests.length }})</h4>
                    <div class="space-y-3">
                        <div
                            v-for="request in booking.requests"
                            :key="request.id"
                            class="bg-white border rounded-lg p-4"
                        >
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2">
                                        <span class="font-medium">{{ request.requesting_team?.name }}</span>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                              :class="getPriorityClass(request.priority)">
                                            {{ getPriorityText(request.priority) }}
                                        </span>
                                    </div>
                                    <div class="text-sm text-gray-600 mt-1">
                                        Angefragt von: {{ request.requested_by_user?.name }}
                                    </div>
                                    <div v-if="request.message" class="text-sm text-gray-800 mt-2 p-2 bg-gray-50 rounded">
                                        {{ request.message }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-2">
                                        {{ formatDateTime(request.created_at) }}
                                    </div>
                                </div>
                                <div v-if="canApproveRequests" class="flex space-x-2 ml-4">
                                    <button
                                        @click="approveRequest(request)"
                                        class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md text-white bg-green-600 hover:bg-green-700"
                                    >
                                        <CheckIcon class="h-4 w-4 mr-1" />
                                        Genehmigen
                                    </button>
                                    <button
                                        @click="rejectRequest(request)"
                                        class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md text-white bg-red-600 hover:bg-red-700"
                                    >
                                        <XMarkIcon class="h-4 w-4 mr-1" />
                                        Ablehnen
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Request Booking Form (for released bookings) -->
                <div v-if="booking.status === 'released' && canRequestBooking" class="border-t pt-6">
                    <h4 class="font-medium text-gray-900 mb-4">Buchung anfragen</h4>
                    <form @submit.prevent="submitBookingRequest">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Team auswählen</label>
                                <select
                                    v-model="requestForm.requesting_team_id"
                                    class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                    required
                                >
                                    <option value="">Wählen Sie ein Team aus</option>
                                    <option
                                        v-for="team in availableTeams"
                                        :key="team.id"
                                        :value="team.id"
                                    >
                                        {{ team.name }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nachricht (optional)</label>
                                <textarea
                                    v-model="requestForm.message"
                                    rows="3"
                                    class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Grund für die Anfrage..."
                                ></textarea>
                            </div>

                            <!-- Court Selection (if multi-court hall) -->
                            <div v-if="availableCourts.length > 1">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Courts auswählen</label>
                                <div class="space-y-2">
                                    <div
                                        v-for="court in availableCourts"
                                        :key="court.id"
                                        class="flex items-center space-x-3 p-3 border rounded-lg"
                                        :class="[
                                            requestForm.court_ids.includes(court.id) 
                                                ? 'border-blue-500 bg-blue-50' 
                                                : 'border-gray-200 hover:border-gray-300'
                                        ]"
                                    >
                                        <input
                                            :id="`court-${court.id}`"
                                            v-model="requestForm.court_ids"
                                            :value="court.id"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-blue-600 focus:border-blue-300 focus:ring focus:ring-blue-200"
                                        />
                                        <div 
                                            class="w-4 h-4 rounded"
                                            :style="{ backgroundColor: court.color_code }"
                                        ></div>
                                        <label :for="`court-${court.id}`" class="flex-1 cursor-pointer">
                                            <div class="font-medium text-gray-900">{{ court.court_identifier }} - {{ court.court_name }}</div>
                                            <div class="text-sm text-gray-500">{{ court.court_type_label }}</div>
                                        </label>
                                        <div v-if="court.max_capacity" class="text-xs text-gray-400">
                                            Max. {{ court.max_capacity }}
                                        </div>
                                    </div>
                                </div>
                                <p class="mt-2 text-xs text-gray-500">
                                    Wählen Sie die Courts aus, die Sie benötigen. Leer = alle verfügbaren Courts.
                                </p>
                            </div>

                            <!-- Flexible Time Selection -->
                            <div v-if="supportsFlexibleBooking">
                                <h5 class="font-medium text-gray-900 mb-3">Flexible Zeitauswahl</h5>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Startzeit</label>
                                        <select
                                            v-model="requestForm.start_time"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                        >
                                            <option value="">Original-Zeit verwenden</option>
                                            <option
                                                v-for="timeSlot in availableTimeSlots"
                                                :key="timeSlot.start_time"
                                                :value="timeSlot.start_time"
                                            >
                                                {{ timeSlot.start_time }} - {{ timeSlot.end_time }}
                                            </option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Dauer (Minuten)</label>
                                        <select
                                            v-model="requestForm.duration_minutes"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                        >
                                            <option :value="booking.duration_minutes">{{ booking.duration_minutes }} Min (Original)</option>
                                            <option v-for="duration in availableDurations" :key="duration" :value="duration">
                                                {{ duration }} Min
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Priorität</label>
                                <select
                                    v-model="requestForm.priority"
                                    class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option value="normal">Normal</option>
                                    <option value="high">Hoch</option>
                                    <option value="urgent">Dringend</option>
                                </select>
                            </div>

                            <!-- Booking Summary -->
                            <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                                <h6 class="font-medium text-gray-900">Anfrage-Zusammenfassung</h6>
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-500">Courts:</span>
                                        <span class="ml-2 font-medium">{{ selectedCourtNames }}</span>
                                    </div>
                                    <div v-if="requestForm.start_time">
                                        <span class="text-gray-500">Zeit:</span>
                                        <span class="ml-2 font-medium">{{ requestForm.start_time }}</span>
                                    </div>
                                    <div v-if="requestForm.duration_minutes">
                                        <span class="text-gray-500">Dauer:</span>
                                        <span class="ml-2 font-medium">{{ requestForm.duration_minutes }} Min</span>
                                    </div>
                                    <div v-if="estimatedCost">
                                        <span class="text-gray-500">Geschätzte Kosten:</span>
                                        <span class="ml-2 font-medium">{{ estimatedCost }}€</span>
                                    </div>
                                </div>
                                <div v-if="isPartialCourtRequest" class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                        Teilcourt-Buchung
                                    </span>
                                    <span class="text-xs text-gray-500">
                                        {{ Math.round((requestForm.court_ids.length / props.availableCourts.length) * 100) }}% der Halle
                                    </span>
                                </div>
                            </div>

                            <div class="flex justify-end space-x-3">
                                <button
                                    type="button"
                                    @click="$emit('close')"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md"
                                >
                                    Abbrechen
                                </button>
                                <button
                                    type="submit"
                                    :disabled="submitting"
                                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md disabled:opacity-50"
                                >
                                    Anfrage senden
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Loading State -->
            <div v-else class="flex justify-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            </div>
        </template>

        <template #footer>
            <div class="flex justify-between">
                <div>
                    <!-- Available Actions for Booking Owner/Trainers -->
                    <div v-if="availableActions.length > 0" class="flex space-x-3">
                        <button
                            v-if="availableActions.includes('release')"
                            @click="showReleaseModal = true"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-yellow-600 hover:bg-yellow-700 rounded-md"
                        >
                            <ShareIcon class="h-4 w-4 mr-2" />
                            Zeit freigeben
                        </button>
                        <button
                            v-if="availableActions.includes('cancel')"
                            @click="showCancelModal = true"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md"
                        >
                            <XMarkIcon class="h-4 w-4 mr-2" />
                            Stornieren
                        </button>
                    </div>
                </div>
                <div class="flex space-x-3">
                    <button
                        @click="$emit('close')"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md"
                    >
                        Schließen
                    </button>
                </div>
            </div>
        </template>
    </DialogModal>

    <!-- Release Confirmation Modal -->
    <ConfirmationModal
        :show="showReleaseModal"
        @close="showReleaseModal = false"
        @confirm="releaseBooking"
    >
        <template #title>Zeit freigeben</template>
        <template #content>
            <div class="mb-4">
                Möchten Sie diese Hallenzeit wirklich freigeben? Andere Teams können dann eine Anfrage stellen.
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Grund der Freigabe</label>
                <textarea
                    v-model="releaseReason"
                    rows="3"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                    placeholder="Warum geben Sie die Zeit frei?"
                ></textarea>
            </div>
        </template>
    </ConfirmationModal>

    <!-- Cancel Confirmation Modal -->
    <ConfirmationModal
        :show="showCancelModal"
        @close="showCancelModal = false"
        @confirm="cancelBooking"
    >
        <template #title>Buchung stornieren</template>
        <template #content>
            <div class="mb-4">
                Möchten Sie diese Buchung wirklich stornieren?
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Stornierungsgrund</label>
                <textarea
                    v-model="cancelReason"
                    rows="3"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                    placeholder="Grund der Stornierung..."
                ></textarea>
            </div>
        </template>
    </ConfirmationModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import DialogModal from '@/Components/DialogModal.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'
import {
    CalendarIcon,
    ExclamationTriangleIcon,
    CheckIcon,
    XMarkIcon,
    ShareIcon
} from '@heroicons/vue/24/outline'
import { useGymBookings } from '@/Composables/useGymBookings'

const props = defineProps({
    show: Boolean,
    booking: Object,
    availableCourts: {
        type: Array,
        default: () => []
    },
    availableTimeSlots: {
        type: Array,
        default: () => []
    },
    availableDurations: {
        type: Array,
        default: () => [30, 60, 90, 120]
    },
    supportsFlexibleBooking: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['close', 'updated'])

// Composables
const { approveBookingRequest, rejectBookingRequest, releaseBooking: releaseBookingAction, cancelBooking: cancelBookingAction } = useGymBookings()
const page = usePage()

// Reactive state
const showReleaseModal = ref(false)
const showCancelModal = ref(false)
const releaseReason = ref('')
const cancelReason = ref('')
const submitting = ref(false)

// Request form
const requestForm = useForm({
    requesting_team_id: '',
    message: '',
    priority: 'normal',
    court_ids: [],
    start_time: '',
    duration_minutes: null
})

// Initialize form with booking data
watch(() => props.booking, (newBooking) => {
    if (newBooking) {
        requestForm.duration_minutes = newBooking.duration_minutes
        requestForm.court_ids = newBooking.court_ids || []
    }
}, { immediate: true })

// Computed
const canRequestBooking = computed(() => {
    if (!props.booking) return false
    
    const userTeams = page.props.user?.teams || []
    const originalTeamId = props.booking.original_team_id
    
    return userTeams.some(team => team.id !== originalTeamId)
})

const canApproveRequests = computed(() => {
    if (!props.booking) return false
    
    const userTeams = page.props.user?.teams || []
    const originalTeamId = props.booking.original_team_id || props.booking.team_id
    
    return userTeams.some(team =>
        team.id === originalTeamId &&
        ['coach', 'assistant_coach'].includes(team.pivot?.role)
    )
})

const availableTeams = computed(() => {
    const userTeams = page.props.user?.teams || []
    const originalTeamId = props.booking?.original_team_id
    
    return userTeams.filter(team => team.id !== originalTeamId)
})

const availableActions = computed(() => {
    if (!props.booking) return []
    
    const userTeams = page.props.user?.teams || []
    const bookingTeamId = props.booking.team_id
    
    const isTeamTrainer = userTeams.some(team =>
        team.id === bookingTeamId &&
        ['coach', 'assistant_coach'].includes(team.pivot?.role)
    )
    
    if (!isTeamTrainer) return []
    
    const actions = []
    
    if (props.booking.can_be_released) {
        actions.push('release')
    }
    
    if (props.booking.can_be_cancelled) {
        actions.push('cancel')
    }
    
    return actions
})

const selectedCourtNames = computed(() => {
    if (!requestForm.court_ids.length) return 'Alle verfügbaren Courts'
    
    const selectedCourts = props.availableCourts.filter(court => 
        requestForm.court_ids.includes(court.id)
    )
    return selectedCourts.map(court => `${court.court_identifier} - ${court.court_name}`).join(', ')
})

const isPartialCourtRequest = computed(() => {
    if (!props.availableCourts.length) return false
    return requestForm.court_ids.length > 0 && requestForm.court_ids.length < props.availableCourts.length
})

const estimatedCost = computed(() => {
    if (!props.booking?.cost) return null
    
    let multiplier = 1
    if (isPartialCourtRequest.value && requestForm.court_ids.length > 0) {
        multiplier = requestForm.court_ids.length / props.availableCourts.length
    }
    
    if (requestForm.duration_minutes && props.booking.duration_minutes) {
        multiplier *= requestForm.duration_minutes / props.booking.duration_minutes
    }
    
    return (props.booking.cost * multiplier).toFixed(2)
})

// Methods
const submitBookingRequest = async () => {
    if (!props.booking) return
    
    submitting.value = true
    
    try {
        await axios.post(`/api/v2/gym-bookings/${props.booking.id}/request`, requestForm.data())
        
        // Reset form
        requestForm.reset()
        
        // Notify parent and close
        emit('updated')
        emit('close')
        
        // Show success message
        // This would typically be handled by a toast notification system
    } catch (error) {
        console.error('Error submitting booking request:', error)
    } finally {
        submitting.value = false
    }
}

const approveRequest = async (request) => {
    try {
        await approveBookingRequest(request.id)
        emit('updated')
        emit('close')
    } catch (error) {
        console.error('Error approving request:', error)
    }
}

const rejectRequest = async (request) => {
    const reason = prompt('Grund der Ablehnung:')
    if (!reason) return
    
    try {
        await rejectBookingRequest(request.id, reason)
        emit('updated')
        emit('close')
    } catch (error) {
        console.error('Error rejecting request:', error)
    }
}

const releaseBooking = async () => {
    try {
        await releaseBookingAction(props.booking.id, releaseReason.value)
        showReleaseModal.value = false
        releaseReason.value = ''
        emit('updated')
        emit('close')
    } catch (error) {
        console.error('Error releasing booking:', error)
    }
}

const cancelBooking = async () => {
    try {
        await cancelBookingAction(props.booking.id, cancelReason.value)
        showCancelModal.value = false
        cancelReason.value = ''
        emit('updated')
        emit('close')
    } catch (error) {
        console.error('Error cancelling booking:', error)
    }
}

// Utility methods
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('de-DE', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

const formatTime = (timeString) => {
    if (!timeString) return ''
    try {
        const time = new Date(`2000-01-01T${timeString}`)
        return time.toLocaleTimeString('de-DE', { 
            hour: '2-digit', 
            minute: '2-digit',
            hour12: false
        })
    } catch (error) {
        return timeString
    }
}

const formatDateTime = (dateTimeString) => {
    return new Date(dateTimeString).toLocaleString('de-DE')
}

const getStatusText = (status) => {
    const statusMap = {
        'reserved': 'Reserviert',
        'released': 'Freigegeben',
        'requested': 'Angefragt',
        'confirmed': 'Bestätigt',
        'cancelled': 'Storniert',
        'completed': 'Abgeschlossen',
        'no_show': 'Nicht erschienen'
    }
    return statusMap[status] || status
}

const getStatusBadgeClass = (status) => {
    const classMap = {
        'reserved': 'bg-blue-100 text-blue-800',
        'released': 'bg-yellow-100 text-yellow-800',
        'requested': 'bg-purple-100 text-purple-800',
        'confirmed': 'bg-green-100 text-green-800',
        'cancelled': 'bg-red-100 text-red-800',
        'completed': 'bg-gray-100 text-gray-800',
        'no_show': 'bg-red-100 text-red-800'
    }
    return classMap[status] || 'bg-gray-100 text-gray-800'
}

const getPriorityText = (priority) => {
    const priorityMap = {
        'low': 'Niedrig',
        'normal': 'Normal',
        'high': 'Hoch',
        'urgent': 'Dringend'
    }
    return priorityMap[priority] || priority
}

const getPriorityClass = (priority) => {
    const classMap = {
        'low': 'bg-gray-100 text-gray-800',
        'normal': 'bg-blue-100 text-blue-800',
        'high': 'bg-orange-100 text-orange-800',
        'urgent': 'bg-red-100 text-red-800'
    }
    return classMap[priority] || 'bg-gray-100 text-gray-800'
}

// Watch for booking changes to reset form
watch(() => props.booking, (newBooking) => {
    if (newBooking) {
        requestForm.reset()
    }
})
</script>