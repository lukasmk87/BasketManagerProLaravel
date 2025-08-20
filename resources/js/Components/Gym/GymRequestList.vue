<template>
    <div class="space-y-3">
        <div
            v-for="request in requests"
            :key="request.id"
            class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors"
        >
            <!-- Request Header -->
            <div class="flex justify-between items-start mb-2">
                <div class="flex-1">
                    <div class="flex items-center space-x-2">
                        <h4 class="font-medium text-gray-900">{{ request.requesting_team?.name }}</h4>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                              :class="getPriorityClass(request.priority)">
                            {{ getPriorityText(request.priority) }}
                        </span>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                              :class="getStatusClass(request.status)">
                            {{ getStatusText(request.status) }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-600">{{ request.requested_by_user?.name }}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500">{{ formatDateTime(request.created_at) }}</p>
                    <p v-if="request.expires_at" class="text-xs text-gray-400">
                        Läuft ab: {{ formatDateTime(request.expires_at) }}
                    </p>
                </div>
            </div>

            <!-- Booking Details -->
            <div class="bg-gray-50 rounded-md p-3 mb-3">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-2 text-sm text-gray-600">
                    <div>
                        <strong>Halle:</strong> {{ request.gym_booking?.gym_time_slot?.gym_hall?.name || 'N/A' }}
                    </div>
                    <div>
                        <strong>Datum:</strong> {{ formatDate(request.gym_booking?.booking_date) }}
                    </div>
                    <div>
                        <strong>Zeit:</strong> {{ formatTimeRange(request.gym_booking) }}
                    </div>
                </div>
            </div>

            <!-- Request Message -->
            <div v-if="request.message" class="mb-3">
                <p class="text-sm text-gray-800 bg-white border border-gray-200 rounded p-3">
                    "{{ request.message }}"
                </p>
            </div>

            <!-- Additional Details -->
            <div v-if="request.purpose || request.expected_participants" class="grid grid-cols-2 gap-4 text-sm text-gray-600 mb-3">
                <div v-if="request.purpose">
                    <strong>Zweck:</strong> {{ request.purpose }}
                </div>
                <div v-if="request.expected_participants">
                    <strong>Teilnehmer:</strong> {{ request.expected_participants }}
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-between items-center pt-3 border-t border-gray-200">
                <div class="text-xs text-gray-500">
                    ID: {{ request.uuid || request.id }}
                </div>
                <div class="flex space-x-2">
                    <button
                        v-if="request.status === 'pending'"
                        @click="showRejectModal(request)"
                        class="inline-flex items-center px-3 py-1 text-xs font-medium text-white bg-red-600 hover:bg-red-700 rounded-md transition-colors"
                    >
                        <svg class="h-3 w-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Ablehnen
                    </button>
                    <button
                        v-if="request.status === 'pending'"
                        @click="showApproveModal(request)"
                        class="inline-flex items-center px-3 py-1 text-xs font-medium text-white bg-green-600 hover:bg-green-700 rounded-md transition-colors"
                    >
                        <svg class="h-3 w-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Genehmigen
                    </button>
                    <button
                        v-if="request.status !== 'pending'"
                        @click="viewDetails(request)"
                        class="inline-flex items-center px-3 py-1 text-xs font-medium text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md transition-colors"
                    >
                        <svg class="h-3 w-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        Details
                    </button>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="requests.length === 0" class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
            <p class="mt-4 text-sm text-gray-500">Keine offenen Anfragen vorhanden</p>
        </div>
    </div>

    <!-- Approve Modal -->
    <div v-if="showApprovalModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Anfrage genehmigen</h3>
            <p class="text-sm text-gray-600 mb-4">
                Möchten Sie die Anfrage von <strong>{{ selectedRequest?.requesting_team?.name }}</strong> genehmigen?
            </p>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Anmerkungen (optional)</label>
                <textarea
                    v-model="approvalNotes"
                    rows="3"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                    placeholder="Zusätzliche Hinweise..."
                ></textarea>
            </div>
            <div class="flex justify-end space-x-3">
                <button
                    @click="closeApproveModal"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md"
                >
                    Abbrechen
                </button>
                <button
                    @click="approveRequest"
                    class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-md"
                >
                    Genehmigen
                </button>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div v-if="showRejectionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Anfrage ablehnen</h3>
            <p class="text-sm text-gray-600 mb-4">
                Möchten Sie die Anfrage von <strong>{{ selectedRequest?.requesting_team?.name }}</strong> ablehnen?
            </p>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Ablehnungsgrund *</label>
                <textarea
                    v-model="rejectionReason"
                    rows="3"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                    placeholder="Bitte geben Sie einen Grund für die Ablehnung an..."
                    required
                ></textarea>
            </div>
            <div class="flex justify-end space-x-3">
                <button
                    @click="closeRejectModal"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md"
                >
                    Abbrechen
                </button>
                <button
                    @click="rejectRequest"
                    :disabled="!rejectionReason.trim()"
                    class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md disabled:opacity-50"
                >
                    Ablehnen
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
    requests: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['approve', 'reject', 'view-details'])

// Modal states
const showApprovalModal = ref(false)
const showRejectionModal = ref(false)
const selectedRequest = ref(null)
const approvalNotes = ref('')
const rejectionReason = ref('')

// Methods
const showApproveModal = (request) => {
    selectedRequest.value = request
    approvalNotes.value = ''
    showApprovalModal.value = true
}

const closeApproveModal = () => {
    showApprovalModal.value = false
    selectedRequest.value = null
    approvalNotes.value = ''
}

const showRejectModal = (request) => {
    selectedRequest.value = request
    rejectionReason.value = ''
    showRejectionModal.value = true
}

const closeRejectModal = () => {
    showRejectionModal.value = false
    selectedRequest.value = null
    rejectionReason.value = ''
}

const approveRequest = () => {
    if (selectedRequest.value) {
        emit('approve', selectedRequest.value.id, approvalNotes.value)
        closeApproveModal()
    }
}

const rejectRequest = () => {
    if (selectedRequest.value && rejectionReason.value.trim()) {
        emit('reject', selectedRequest.value.id, rejectionReason.value)
        closeRejectModal()
    }
}

const viewDetails = (request) => {
    emit('view-details', request)
}

// Utility methods
const formatDateTime = (dateTimeString) => {
    if (!dateTimeString) return 'N/A'
    
    try {
        const date = new Date(dateTimeString)
        return date.toLocaleString('de-DE', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        })
    } catch (error) {
        return 'N/A'
    }
}

const formatDate = (dateString) => {
    if (!dateString) return 'N/A'
    
    try {
        const date = new Date(dateString)
        return date.toLocaleDateString('de-DE', {
            weekday: 'short',
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        })
    } catch (error) {
        return 'N/A'
    }
}

const formatTimeRange = (booking) => {
    if (!booking || !booking.start_time || !booking.end_time) return 'N/A'
    
    try {
        const startTime = new Date(`2000-01-01T${booking.start_time}`)
        const endTime = new Date(`2000-01-01T${booking.end_time}`)
        
        const formatTime = (time) => time.toLocaleTimeString('de-DE', { 
            hour: '2-digit', 
            minute: '2-digit',
            hour12: false
        })
        
        return `${formatTime(startTime)} - ${formatTime(endTime)}`
    } catch (error) {
        return 'N/A'
    }
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

const getStatusText = (status) => {
    const statusMap = {
        'pending': 'Wartend',
        'approved': 'Genehmigt',
        'rejected': 'Abgelehnt',
        'cancelled': 'Storniert',
        'expired': 'Abgelaufen'
    }
    return statusMap[status] || status
}

const getStatusClass = (status) => {
    const classMap = {
        'pending': 'bg-yellow-100 text-yellow-800',
        'approved': 'bg-green-100 text-green-800',
        'rejected': 'bg-red-100 text-red-800',
        'cancelled': 'bg-gray-100 text-gray-800',
        'expired': 'bg-gray-100 text-gray-800'
    }
    return classMap[status] || 'bg-gray-100 text-gray-800'
}
</script>