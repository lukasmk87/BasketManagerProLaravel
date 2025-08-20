<template>
    <AppLayout title="Buchungsanfragen">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Buchungsanfragen verwalten
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium text-gray-900">
                                Buchungsanfragen
                            </h3>
                            <div class="flex items-center space-x-3">
                                <select 
                                    v-model="selectedStatus"
                                    class="rounded-md border-gray-300 text-sm"
                                >
                                    <option value="all">Alle Anfragen</option>
                                    <option value="pending">Ausstehend</option>
                                    <option value="approved">Genehmigt</option>
                                    <option value="rejected">Abgelehnt</option>
                                </select>
                                <span class="text-sm text-gray-500">
                                    {{ filteredRequests.length }} von {{ requestsData.length }}
                                </span>
                            </div>
                        </div>

                        <div v-if="filteredRequests.length === 0" class="text-center py-12">
                            <ClockIcon class="mx-auto h-12 w-12 text-gray-400" />
                            <h3 class="mt-2 text-sm font-medium text-gray-900">
                                Keine Anfragen vorhanden
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Es sind aktuell keine Buchungsanfragen vorhanden.
                            </p>
                        </div>

                        <div v-else class="space-y-6">
                            <div 
                                v-for="request in filteredRequests" 
                                :key="request.id"
                                :class="[
                                    'border rounded-lg p-6 transition-all',
                                    request.status === 'pending' 
                                        ? 'border-yellow-200 bg-yellow-50' 
                                        : 'border-gray-200 bg-white'
                                ]"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3 mb-3">
                                            <h4 class="text-lg font-semibold text-gray-900">
                                                {{ request.gym_hall?.name }}
                                            </h4>
                                            <span 
                                                :class="getStatusClass(request.status)"
                                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                            >
                                                {{ getStatusText(request.status) }}
                                            </span>
                                            <span 
                                                :class="getPriorityClass(request.priority)"
                                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                            >
                                                {{ getPriorityText(request.priority) }}
                                            </span>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-sm text-gray-600 mb-4">
                                            <div class="flex items-center">
                                                <UserGroupIcon class="h-4 w-4 mr-2" />
                                                <span>{{ request.team?.name }}</span>
                                            </div>
                                            <div class="flex items-center">
                                                <CalendarDaysIcon class="h-4 w-4 mr-2" />
                                                <span>{{ formatDate(request.requested_date) }}</span>
                                            </div>
                                            <div class="flex items-center">
                                                <ClockIcon class="h-4 w-4 mr-2" />
                                                <span>{{ request.requested_start_time }} - {{ request.requested_end_time }}</span>
                                            </div>
                                            <div class="flex items-center">
                                                <UserIcon class="h-4 w-4 mr-2" />
                                                <span>{{ request.requested_by?.name }}</span>
                                            </div>
                                        </div>

                                        <div v-if="request.purpose" class="mb-4">
                                            <p class="text-sm text-gray-600">
                                                <strong>Zweck:</strong> {{ request.purpose }}
                                            </p>
                                        </div>

                                        <div v-if="request.notes" class="mb-4">
                                            <p class="text-sm text-gray-600">
                                                <strong>Anmerkungen:</strong> {{ request.notes }}
                                            </p>
                                        </div>

                                        <div class="text-xs text-gray-500">
                                            Angefragt am {{ formatDateTime(request.created_at) }}
                                        </div>
                                    </div>

                                    <div v-if="request.status === 'pending'" class="ml-4 flex space-x-2">
                                        <button
                                            @click="approveRequest(request)"
                                            class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-md"
                                        >
                                            Genehmigen
                                        </button>
                                        <button
                                            @click="rejectRequest(request)"
                                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md"
                                        >
                                            Ablehnen
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Pagination -->
                            <div v-if="requests.links" class="mt-6">
                                <nav class="flex items-center justify-between">
                                    <div class="flex-1 flex justify-between sm:hidden">
                                        <Link
                                            v-if="requests.prev_page_url"
                                            :href="requests.prev_page_url"
                                            class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                                        >
                                            Zurück
                                        </Link>
                                        <Link
                                            v-if="requests.next_page_url"
                                            :href="requests.next_page_url"
                                            class="ml-3 relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                                        >
                                            Weiter
                                        </Link>
                                    </div>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    ClockIcon,
    CalendarDaysIcon,
    UserGroupIcon,
    UserIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    requests: [Array, Object],
})

const selectedStatus = ref('all')

const requestsData = computed(() => {
    return Array.isArray(props.requests) ? props.requests : props.requests?.data || []
})

const filteredRequests = computed(() => {
    if (selectedStatus.value === 'all') {
        return requestsData.value
    }
    return requestsData.value.filter(request => request.status === selectedStatus.value)
})

const getStatusClass = (status) => {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800',
        approved: 'bg-green-100 text-green-800',
        rejected: 'bg-red-100 text-red-800'
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

const getStatusText = (status) => {
    const texts = {
        pending: 'Ausstehend',
        approved: 'Genehmigt',
        rejected: 'Abgelehnt'
    }
    return texts[status] || status
}

const getPriorityClass = (priority) => {
    const classes = {
        low: 'bg-blue-100 text-blue-800',
        normal: 'bg-gray-100 text-gray-800',
        high: 'bg-orange-100 text-orange-800',
        urgent: 'bg-red-100 text-red-800'
    }
    return classes[priority] || 'bg-gray-100 text-gray-800'
}

const getPriorityText = (priority) => {
    const texts = {
        low: 'Niedrig',
        normal: 'Normal',
        high: 'Hoch',
        urgent: 'Dringend'
    }
    return texts[priority] || priority
}

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('de-DE', {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    })
}

const formatDateTime = (dateString) => {
    return new Date(dateString).toLocaleString('de-DE', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const approveRequest = (request) => {
    if (confirm('Möchten Sie diese Anfrage wirklich genehmigen?')) {
        router.post(route('api.gym.booking-requests.approve', request.id), {}, {
            onSuccess: () => {
                // Refresh page or show success message
            }
        })
    }
}

const rejectRequest = (request) => {
    const reason = prompt('Grund für die Ablehnung (optional):')
    if (reason !== null) {
        router.post(route('api.gym.booking-requests.reject', request.id), {
            reason: reason
        }, {
            onSuccess: () => {
                // Refresh page or show success message
            }
        })
    }
}
</script>