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
    booking: Object
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
    priority: 'normal'
})

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
        ['trainer', 'assistant_trainer'].includes(team.pivot?.role)
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
        ['trainer', 'assistant_trainer'].includes(team.pivot?.role)
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