<template>
    <AppLayout title="Buchungen">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Hallenbuchungen verwalten
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium text-gray-900">
                                Buchungsübersicht
                            </h3>
                            <div class="flex space-x-3">
                                <select 
                                    v-model="selectedFilter"
                                    class="rounded-md border-gray-300 text-sm"
                                >
                                    <option value="all">Alle Buchungen</option>
                                    <option value="confirmed">Bestätigte</option>
                                    <option value="pending">Ausstehende</option>
                                    <option value="cancelled">Stornierte</option>
                                </select>
                            </div>
                        </div>

                        <div v-if="filteredBookings.length === 0" class="text-center py-12">
                            <CalendarIcon class="mx-auto h-12 w-12 text-gray-400" />
                            <h3 class="mt-2 text-sm font-medium text-gray-900">
                                Keine Buchungen vorhanden
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Es wurden noch keine Hallenbuchungen erstellt.
                            </p>
                        </div>

                        <div v-else class="space-y-4">
                            <div 
                                v-for="booking in filteredBookings" 
                                :key="booking.id"
                                class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-4">
                                            <h4 class="text-lg font-semibold text-gray-900">
                                                {{ booking.gym_hall?.name }}
                                            </h4>
                                            <span 
                                                :class="getStatusClass(booking.status)"
                                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                            >
                                                {{ getStatusText(booking.status) }}
                                            </span>
                                        </div>
                                        
                                        <div class="mt-2 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-600">
                                            <div class="flex items-center">
                                                <UserGroupIcon class="h-4 w-4 mr-2" />
                                                <span>{{ booking.team?.name || 'Kein Team' }}</span>
                                            </div>
                                            <div class="flex items-center">
                                                <CalendarDaysIcon class="h-4 w-4 mr-2" />
                                                <span>{{ formatDate(booking.booking_date) }}</span>
                                            </div>
                                            <div class="flex items-center">
                                                <ClockIcon class="h-4 w-4 mr-2" />
                                                <span>{{ booking.start_time }} - {{ booking.end_time }}</span>
                                            </div>
                                        </div>

                                        <p v-if="booking.purpose" class="mt-2 text-sm text-gray-600">
                                            <strong>Zweck:</strong> {{ booking.purpose }}
                                        </p>
                                    </div>

                                    <div class="ml-4 flex space-x-2">
                                        <button
                                            @click="viewBooking(booking)"
                                            class="px-3 py-2 text-sm font-medium text-blue-600 hover:text-blue-800"
                                        >
                                            Details
                                        </button>
                                        <button
                                            v-if="booking.status === 'confirmed'"
                                            @click="cancelBooking(booking)"
                                            class="px-3 py-2 text-sm font-medium text-red-600 hover:text-red-800"
                                        >
                                            Stornieren
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Pagination -->
                            <div v-if="bookings.links" class="mt-6">
                                <nav class="flex items-center justify-between">
                                    <div class="flex-1 flex justify-between sm:hidden">
                                        <Link
                                            v-if="bookings.prev_page_url"
                                            :href="bookings.prev_page_url"
                                            class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                                        >
                                            Zurück
                                        </Link>
                                        <Link
                                            v-if="bookings.next_page_url"
                                            :href="bookings.next_page_url"
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
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    CalendarIcon,
    CalendarDaysIcon,
    ClockIcon,
    UserGroupIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    bookings: [Array, Object],
})

const selectedFilter = ref('all')

const bookingsData = computed(() => {
    return Array.isArray(props.bookings) ? props.bookings : props.bookings?.data || []
})

const filteredBookings = computed(() => {
    if (selectedFilter.value === 'all') {
        return bookingsData.value
    }
    return bookingsData.value.filter(booking => booking.status === selectedFilter.value)
})

const getStatusClass = (status) => {
    const classes = {
        confirmed: 'bg-green-100 text-green-800',
        pending: 'bg-yellow-100 text-yellow-800',
        cancelled: 'bg-red-100 text-red-800',
        released: 'bg-blue-100 text-blue-800'
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

const getStatusText = (status) => {
    const texts = {
        confirmed: 'Bestätigt',
        pending: 'Ausstehend',
        cancelled: 'Storniert',
        released: 'Freigegeben'
    }
    return texts[status] || status
}

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('de-DE', {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    })
}

const viewBooking = (booking) => {
    console.log('View booking:', booking)
}

const cancelBooking = (booking) => {
    if (confirm('Möchten Sie diese Buchung wirklich stornieren?')) {
        console.log('Cancel booking:', booking)
    }
}
</script>