<template>
    <AppLayout title="Hallenverwaltung">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Hallenverwaltung
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Quick Stats -->
                <div class="mb-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="bg-white overflow-hidden shadow-xl rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <BuildingStorefrontIcon class="h-8 w-8 text-blue-600" />
                                </div>
                                <div class="ml-5">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Sporthallen
                                        </dt>
                                        <dd class="text-2xl font-bold text-gray-900">
                                            {{ stats.total_halls || 0 }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-xl rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <CalendarDaysIcon class="h-8 w-8 text-green-600" />
                                </div>
                                <div class="ml-5">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Aktive Buchungen
                                        </dt>
                                        <dd class="text-2xl font-bold text-gray-900">
                                            {{ stats.active_bookings || 0 }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-xl rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <ClockIcon class="h-8 w-8 text-yellow-600" />
                                </div>
                                <div class="ml-5">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Offene Anfragen
                                        </dt>
                                        <dd class="text-2xl font-bold text-gray-900">
                                            {{ stats.pending_requests || 0 }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-xl rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <ChartBarIcon class="h-8 w-8 text-purple-600" />
                                </div>
                                <div class="ml-5">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Auslastung
                                        </dt>
                                        <dd class="text-2xl font-bold text-gray-900">
                                            {{ stats.utilization_rate || 0 }}%
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Left Column: Calendar View -->
                    <div class="lg:col-span-2">
                        <div class="bg-white shadow-xl rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="flex justify-between items-center mb-6">
                                    <h3 class="text-lg font-medium text-gray-900">
                                        Wochenplan
                                    </h3>
                                    <div class="flex space-x-2">
                                        <button
                                            @click="previousWeek"
                                            class="p-2 rounded-md text-gray-500 hover:text-gray-700"
                                        >
                                            <ChevronLeftIcon class="h-5 w-5" />
                                        </button>
                                        <span class="px-3 py-2 text-sm font-medium text-gray-900">
                                            {{ formatWeekRange }}
                                        </span>
                                        <button
                                            @click="nextWeek"
                                            class="p-2 rounded-md text-gray-500 hover:text-gray-700"
                                        >
                                            <ChevronRightIcon class="h-5 w-5" />
                                        </button>
                                    </div>
                                </div>

                                <GymHallCalendar
                                    :gym-halls="gymHalls"
                                    :current-week="currentWeek"
                                    :bookings="weeklyBookings"
                                    @booking-clicked="openBookingModal"
                                    @time-slot-clicked="openTimeSlotModal"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Actions & Info -->
                    <div class="space-y-6">
                        <!-- Quick Actions -->
                        <div class="bg-white shadow-xl rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">
                                    Schnellaktionen
                                </h3>
                                <div class="space-y-3">
                                    <button
                                        v-if="canManageHalls"
                                        @click="openHallModal"
                                        class="w-full flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md"
                                    >
                                        <PlusIcon class="h-5 w-5 mr-2" />
                                        Neue Sporthalle
                                    </button>
                                    <button
                                        @click="showMyBookings"
                                        class="w-full flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md"
                                    >
                                        <CalendarIcon class="h-5 w-5 mr-2" />
                                        Meine Buchungen
                                    </button>
                                    <button
                                        @click="showAvailableTimes"
                                        class="w-full flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md"
                                    >
                                        <MagnifyingGlassIcon class="h-5 w-5 mr-2" />
                                        Freie Zeiten finden
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Pending Requests -->
                        <div v-if="pendingRequests.length > 0" class="bg-white shadow-xl rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">
                                    Offene Anfragen
                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        {{ pendingRequests.length }}
                                    </span>
                                </h3>
                                <GymRequestList
                                    :requests="pendingRequests"
                                    @approve="approveRequest"
                                    @reject="rejectRequest"
                                />
                            </div>
                        </div>

                        <!-- Recent Activity -->
                        <div class="bg-white shadow-xl rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">
                                    Letzte Aktivitäten
                                </h3>
                                <div class="space-y-3">
                                    <div
                                        v-for="activity in recentActivities"
                                        :key="activity.id"
                                        class="flex items-center text-sm text-gray-600"
                                    >
                                        <div class="flex-shrink-0 w-2 h-2 rounded-full mr-3"
                                             :class="getActivityColorClass(activity.type)"
                                        ></div>
                                        <span>{{ activity.message }}</span>
                                        <span class="ml-auto text-xs text-gray-400">
                                            {{ formatTime(activity.created_at) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <GymBookingModal
            :show="showBookingModal"
            :booking="selectedBooking"
            @close="closeBookingModal"
            @updated="refreshData"
        />

        <GymTimeSlotModal
            :show="showTimeSlotModal"
            :time-slot="selectedTimeSlot"
            :gym-hall="selectedGymHall"
            @close="closeTimeSlotModal"
            @updated="refreshData"
        />

        <GymHallModal
            :show="showHallModal"
            :gym-hall="selectedHall"
            @close="closeHallModal"
            @updated="refreshData"
        />
    </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import GymHallCalendar from '@/Components/Gym/GymHallCalendar.vue'
import GymBookingModal from '@/Components/Gym/GymBookingModal.vue'
import GymTimeSlotModal from '@/Components/Gym/GymTimeSlotModal.vue'
import GymHallModal from '@/Components/Gym/GymHallModal.vue'
import GymRequestList from '@/Components/Gym/GymRequestList.vue'
import {
    BuildingStorefrontIcon,
    CalendarDaysIcon,
    ClockIcon,
    ChartBarIcon,
    PlusIcon,
    CalendarIcon,
    MagnifyingGlassIcon,
    ChevronLeftIcon,
    ChevronRightIcon
} from '@heroicons/vue/24/outline'
import { useGymManagement } from '@/Composables/useGymManagement'
import { useGymNotifications } from '@/Composables/useGymNotifications'

// Props from Inertia
const props = defineProps({
    gymHalls: Array,
    initialStats: Object,
    userPermissions: Object,
})

// Composables
const { 
    stats, 
    weeklyBookings, 
    pendingRequests, 
    recentActivities,
    currentWeek,
    refreshData,
    previousWeek,
    nextWeek,
    approveRequest,
    rejectRequest
} = useGymManagement(props.gymHalls, props.initialStats)

const { subscribeToGymUpdates } = useGymNotifications()

// Reactive state
const selectedBooking = ref(null)
const selectedTimeSlot = ref(null)
const selectedGymHall = ref(null)
const selectedHall = ref(null)
const showBookingModal = ref(false)
const showTimeSlotModal = ref(false)
const showHallModal = ref(false)

// Computed
const canManageHalls = computed(() => {
    return props.userPermissions?.canManageHalls || false
})

const formatWeekRange = computed(() => {
    const start = new Date(currentWeek.value)
    const end = new Date(currentWeek.value)
    end.setDate(end.getDate() + 6)
    
    return `${start.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit' })} - ${end.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' })}`
})

// Methods
const openBookingModal = (booking) => {
    selectedBooking.value = booking
    showBookingModal.value = true
}

const closeBookingModal = () => {
    selectedBooking.value = null
    showBookingModal.value = false
}

const openTimeSlotModal = (timeSlot, gymHall) => {
    selectedTimeSlot.value = timeSlot
    selectedGymHall.value = gymHall
    showTimeSlotModal.value = true
}

const closeTimeSlotModal = () => {
    selectedTimeSlot.value = null
    selectedGymHall.value = null
    showTimeSlotModal.value = false
}

const openHallModal = (hall = null) => {
    selectedHall.value = hall
    showHallModal.value = true
}

const closeHallModal = () => {
    selectedHall.value = null
    showHallModal.value = false
}

const showMyBookings = () => {
    // Navigate to my bookings page
    // This would use Inertia.visit or emit to parent
}

const showAvailableTimes = () => {
    // Navigate to available times page
    // This would use Inertia.visit or emit to parent
}

const getActivityColorClass = (type) => {
    const colors = {
        'booking_created': 'bg-blue-400',
        'booking_released': 'bg-yellow-400',
        'booking_confirmed': 'bg-green-400',
        'booking_cancelled': 'bg-red-400',
        'request_created': 'bg-purple-400',
        'request_approved': 'bg-green-400',
        'request_rejected': 'bg-red-400'
    }
    return colors[type] || 'bg-gray-400'
}

const formatTime = (timestamp) => {
    const date = new Date(timestamp)
    const now = new Date()
    const diffMinutes = Math.floor((now - date) / 60000)
    
    if (diffMinutes < 1) return 'gerade eben'
    if (diffMinutes < 60) return `vor ${diffMinutes} Min`
    if (diffMinutes < 1440) return `vor ${Math.floor(diffMinutes / 60)} Std`
    return `vor ${Math.floor(diffMinutes / 1440)} Tagen`
}

// Lifecycle
onMounted(() => {
    // Subscribe to real-time updates for the user's club
    const page = usePage()
    const clubId = page.props.user?.current_team?.club_id
    
    if (clubId) {
        subscribeToGymUpdates(clubId, {
            onTimeSlotReleased: refreshData,
            onBookingRequested: refreshData,
            onBookingConfirmed: refreshData,
            onScheduleUpdated: refreshData
        })
    }
    
    // Initial data refresh
    refreshData()
})

onUnmounted(() => {
    // Clean up subscriptions handled by composable
})
</script>