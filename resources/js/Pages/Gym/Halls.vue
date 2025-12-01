<template>
    <AppLayout title="Sporthallen">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Sporthallen verwalten
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium text-gray-900">
                                Alle Sporthallen
                            </h3>
                            <Link 
                                :href="route('gym.create-hall')" 
                                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md"
                            >
                                <PlusIcon class="h-5 w-5 mr-2" />
                                Neue Sporthalle
                            </Link>
                        </div>

                        <div v-if="gymHalls.length === 0" class="text-center py-12">
                            <BuildingStorefrontIcon class="mx-auto h-12 w-12 text-gray-400" />
                            <h3 class="mt-2 text-sm font-medium text-gray-900">
                                Keine Sporthallen vorhanden
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Erstellen Sie Ihre erste Sporthalle.
                            </p>
                            <div class="mt-6">
                                <Link 
                                    :href="route('gym.create-hall')" 
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md"
                                >
                                    <PlusIcon class="h-5 w-5 mr-2" />
                                    Neue Sporthalle
                                </Link>
                            </div>
                        </div>

                        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div 
                                v-for="hall in gymHalls" 
                                :key="hall.id"
                                class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-lg transition-shadow"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h4 class="text-lg font-semibold text-gray-900">
                                            {{ hall.name }}
                                        </h4>
                                        <p v-if="hall.description" class="text-sm text-gray-600 mt-1">
                                            {{ hall.description }}
                                        </p>
                                    </div>
                                    <div class="ml-2">
                                        <span 
                                            :class="[
                                                'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                                                hall.is_active 
                                                    ? 'bg-green-100 text-green-800' 
                                                    : 'bg-red-100 text-red-800'
                                            ]"
                                        >
                                            {{ hall.is_active ? 'Aktiv' : 'Inaktiv' }}
                                        </span>
                                    </div>
                                </div>

                                <div class="mt-4 space-y-2">
                                    <div class="flex items-center text-sm text-gray-600">
                                        <MapPinIcon class="h-4 w-4 mr-2" />
                                        <span>{{ getHallAddress(hall) }}</span>
                                    </div>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <ClockIcon class="h-4 w-4 mr-2" />
                                        <span>{{ hall.time_slots_count || 0 }} Zeitslot{{ hall.time_slots_count !== 1 ? 's' : '' }}</span>
                                    </div>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <CalendarIcon class="h-4 w-4 mr-2" />
                                        <span>{{ hall.bookings_count || 0 }} Buchungen</span>
                                    </div>
                                </div>

                                <div class="mt-6 flex space-x-2">
                                    <button
                                        @click="editHall(hall)"
                                        class="flex-1 inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors"
                                    >
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Bearbeiten
                                    </button>
                                    <button
                                        @click="viewSchedule(hall)"
                                        class="flex-1 inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-green-600 bg-green-50 hover:bg-green-100 rounded-md transition-colors"
                                    >
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        Terminplan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gym Hall Modal -->
        <GymHallModal
            :show="showHallModal"
            :gym-hall="selectedHall"
            :current-club="currentClub"
            :available-clubs="availableClubs"
            @close="closeHallModal"
            @updated="refreshData"
        />

        <!-- Schedule Modal -->
        <DialogModal :show="showScheduleModal" @close="closeScheduleModal" max-width="6xl">
            <template #title>
                <div class="flex items-center">
                    <svg class="h-6 w-6 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Terminplan - {{ selectedScheduleHall?.name }}
                </div>
            </template>
            
            <template #content>
                <div v-if="selectedScheduleHall" class="space-y-6">
                    <div class="bg-blue-50 rounded-lg p-4">
                        <h4 class="font-medium text-blue-900 mb-2">Halleninformationen</h4>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-blue-700 font-medium">Kapazität:</span> {{ selectedScheduleHall.capacity || 'Nicht angegeben' }}
                            </div>
                            <div>
                                <span class="text-blue-700 font-medium">Status:</span>
                                <span :class="selectedScheduleHall.is_active ? 'text-green-600' : 'text-red-600'">
                                    {{ selectedScheduleHall.is_active ? 'Aktiv' : 'Inaktiv' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-blue-700 font-medium">Zeitslots:</span> {{ selectedScheduleHall.time_slots_count || 0 }}
                            </div>
                            <div>
                                <span class="text-blue-700 font-medium">Buchungen:</span> {{ selectedScheduleHall.bookings_count || 0 }}
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab Navigation -->
                    <div class="border-b border-gray-200 mb-6">
                        <nav class="-mb-px flex space-x-8">
                            <button
                                @click="activeTab = 'schedule'"
                                :class="[
                                    'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm',
                                    activeTab === 'schedule'
                                        ? 'border-blue-500 text-blue-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                ]"
                            >
                                Öffnungszeiten
                            </button>
                            <button
                                @click="activeTab = 'teams'"
                                :class="[
                                    'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm',
                                    activeTab === 'teams'
                                        ? 'border-blue-500 text-blue-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                ]"
                            >
                                Team-Zuordnungen
                            </button>
                            <button
                                @click="activeTab = 'courts'"
                                :class="[
                                    'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm',
                                    activeTab === 'courts'
                                        ? 'border-blue-500 text-blue-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                ]"
                            >
                                Felder verwalten
                            </button>
                        </nav>
                    </div>

                    <!-- Schedule Tab -->
                    <GymTimeSlotManager
                        v-if="selectedScheduleHall.id && activeTab === 'schedule'"
                        :gym-hall-id="selectedScheduleHall.id"
                        :initial-time-slots="selectedScheduleHall.time_slots || []"
                        :default-open-time="selectedScheduleHall.opening_time || '08:00'"
                        :default-close-time="selectedScheduleHall.closing_time || '22:00'"
                        @updated="refreshData"
                        @error="(error) => console.error('Schedule error:', error)"
                    />

                    <!-- Teams Tab -->
                    <TimeSlotsList
                        v-if="selectedScheduleHall.id && activeTab === 'teams'"
                        :gym-hall-id="selectedScheduleHall.id"
                        @updated="refreshData"
                    />

                    <!-- Courts Tab -->
                    <CourtManagement
                        v-if="selectedScheduleHall.id && activeTab === 'courts'"
                        :gym-hall-id="selectedScheduleHall.id"
                        :gym-hall="selectedScheduleHall"
                        @updated="refreshData"
                    />
                </div>
            </template>
            
            <template #footer>
                <div class="flex justify-end">
                    <button
                        @click="closeScheduleModal"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md"
                    >
                        Schließen
                    </button>
                </div>
            </template>
        </DialogModal>
    </AppLayout>
</template>

<script setup>
import { ref, defineAsyncComponent } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import DialogModal from '@/Components/DialogModal.vue'
// PERF-005: Lazy load heavy modal component (~40 KB savings)
const GymHallModal = defineAsyncComponent(() =>
    import('@/Components/Gym/GymHallModal.vue')
)
import GymTimeSlotManager from '@/Components/Gym/GymTimeSlotManager.vue'
import TimeSlotsList from '@/Components/Gym/TimeSlotsList.vue'
import CourtManagement from '@/Components/Gym/CourtManagement.vue'
import {
    BuildingStorefrontIcon,
    PlusIcon,
    MapPinIcon,
    ClockIcon,
    CalendarIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    gymHalls: Array,
    currentClub: Object,
    availableClubs: {
        type: Array,
        default: () => []
    }
})

// Modal state
const showHallModal = ref(false)
const selectedHall = ref(null)
const showScheduleModal = ref(false)
const selectedScheduleHall = ref(null)
const activeTab = ref('schedule')

// Methods
const editHall = (hall) => {
    selectedHall.value = hall
    showHallModal.value = true
}

const closeHallModal = () => {
    selectedHall.value = null
    showHallModal.value = false
}

const refreshData = () => {
    // Refresh the page data after successful update
    router.reload({ only: ['gymHalls'] })
}

const viewSchedule = (hall) => {
    selectedScheduleHall.value = hall
    showScheduleModal.value = true
}

const closeScheduleModal = () => {
    selectedScheduleHall.value = null
    showScheduleModal.value = false
}

const getHallAddress = (hall) => {
    const addressParts = []
    
    if (hall.address_street) {
        addressParts.push(hall.address_street)
    }
    
    if (hall.address_zip && hall.address_city) {
        addressParts.push(`${hall.address_zip} ${hall.address_city}`)
    } else if (hall.address_city) {
        addressParts.push(hall.address_city)
    }
    
    if (addressParts.length === 0) {
        return 'Keine Adresse angegeben'
    }
    
    return addressParts.join(', ')
}
</script>