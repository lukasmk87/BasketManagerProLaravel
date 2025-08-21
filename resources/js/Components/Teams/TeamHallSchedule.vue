<template>
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Zugeordnete Hallenzeiten</h3>
                <p class="text-sm text-gray-600 mt-1">
                    Übersicht der dem Team zugeordneten Trainings- und Spielzeiten
                </p>
            </div>
            <button
                @click="refreshTimeSlots"
                :disabled="loading"
                class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-md disabled:opacity-50 flex items-center"
            >
                <ArrowPathIcon class="w-4 h-4 mr-2" />
                {{ loading ? 'Laden...' : 'Aktualisieren' }}
            </button>
        </div>

        <!-- Loading State -->
        <div v-if="loading && !timeSlots.length" class="flex items-center justify-center py-12">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <span class="ml-3 text-gray-600">Hallenzeiten werden geladen...</span>
        </div>

        <!-- Empty State -->
        <div v-else-if="timeSlots.length === 0" class="text-center py-12">
            <CalendarIcon class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-2 text-sm font-medium text-gray-900">
                Keine Hallenzeiten zugeordnet
            </h3>
            <p class="mt-1 text-sm text-gray-500">
                Diesem Team sind noch keine Hallenzeiten zugeordnet.
            </p>
            <div class="mt-6">
                <p class="text-sm text-gray-600">
                    <strong>Tipp:</strong> Gehen Sie zur Hallenverwaltung, um Zeitslots zu erstellen und Teams zuzuordnen.
                </p>
            </div>
        </div>

        <!-- Time Slots List -->
        <div v-else class="space-y-4">
            <!-- Weekly Schedule Grid -->
            <div class="grid grid-cols-1 gap-4">
                <div 
                    v-for="group in groupedTimeSlots" 
                    :key="group.day"
                    class="border border-gray-200 rounded-lg"
                >
                    <!-- Day Header -->
                    <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                        <h4 class="font-medium text-gray-900">{{ getDayName(group.day) }}</h4>
                    </div>
                    
                    <!-- Time Slots for this day -->
                    <div class="p-4 space-y-3">
                        <div 
                            v-for="slot in group.slots" 
                            :key="slot.id"
                            class="flex items-center justify-between p-4 bg-white border border-gray-200 rounded-lg hover:border-gray-300 transition-colors"
                        >
                            <div class="flex-1">
                                <div class="flex items-center space-x-4">
                                    <div class="flex-shrink-0">
                                        <div class="flex items-center space-x-2">
                                            <ClockIcon class="w-5 h-5 text-gray-400" />
                                            <span class="text-base font-medium text-gray-900">
                                                {{ slot.start_time }} - {{ slot.end_time }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center space-x-3">
                                            <p class="text-sm font-medium text-gray-900">
                                                {{ slot.title }}
                                            </p>
                                            <span :class="[
                                                'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                                                getSlotTypeClass(slot.slot_type)
                                            ]">
                                                {{ getSlotTypeName(slot.slot_type) }}
                                            </span>
                                        </div>
                                        <div class="mt-1 flex items-center space-x-4 text-sm text-gray-500">
                                            <div class="flex items-center">
                                                <BuildingStorefrontIcon class="w-4 h-4 mr-1" />
                                                <span>{{ slot.gym_hall.name }}</span>
                                            </div>
                                            <div v-if="slot.gym_hall.address" class="flex items-center">
                                                <MapPinIcon class="w-4 h-4 mr-1" />
                                                <span>{{ slot.gym_hall.address }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Duration Badge -->
                            <div class="flex-shrink-0 ml-4">
                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-blue-50 text-blue-700 rounded-md">
                                    {{ formatDuration(slot.start_time, slot.end_time) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Statistics -->
            <div class="mt-8 bg-gray-50 rounded-lg p-4">
                <h4 class="font-medium text-gray-900 mb-3">Zusammenfassung</h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600">{{ timeSlots.length }}</div>
                        <div class="text-xs text-gray-600">Zeitslots</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">{{ trainingSlots.length }}</div>
                        <div class="text-xs text-gray-600">Trainingszeiten</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600">{{ gameSlots.length }}</div>
                        <div class="text-xs text-gray-600">Spielzeiten</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-orange-600">{{ totalWeeklyHours }}</div>
                        <div class="text-xs text-gray-600">Std./Woche</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import {
    CalendarIcon,
    ClockIcon,
    BuildingStorefrontIcon,
    MapPinIcon,
    ArrowPathIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    teamId: {
        type: Number,
        required: true
    }
})

// State
const timeSlots = ref([])
const loading = ref(false)

// Computed
const groupedTimeSlots = computed(() => {
    const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']
    const grouped = []
    
    days.forEach(day => {
        const slotsForDay = timeSlots.value.filter(slot => slot.day_of_week === day)
        if (slotsForDay.length > 0) {
            grouped.push({
                day,
                slots: slotsForDay.sort((a, b) => a.start_time.localeCompare(b.start_time))
            })
        }
    })
    
    return grouped
})

const trainingSlots = computed(() => {
    return timeSlots.value.filter(slot => slot.slot_type === 'training')
})

const gameSlots = computed(() => {
    return timeSlots.value.filter(slot => slot.slot_type === 'game')
})

const totalWeeklyHours = computed(() => {
    const totalMinutes = timeSlots.value.reduce((total, slot) => {
        const duration = calculateDuration(slot.start_time, slot.end_time)
        return total + duration
    }, 0)
    
    return Math.round((totalMinutes / 60) * 10) / 10 // Round to 1 decimal place
})

// Methods
const refreshTimeSlots = async () => {
    try {
        loading.value = true
        const response = await axios.get(`/gym-management/teams/${props.teamId}/time-slots`)
        timeSlots.value = response.data.data
    } catch (error) {
        console.error('Fehler beim Laden der Team-Zeitslots:', error)
        alert('Fehler beim Laden der Hallenzeiten')
    } finally {
        loading.value = false
    }
}

// Helper methods
const getDayName = (dayKey) => {
    const days = {
        'monday': 'Montag',
        'tuesday': 'Dienstag', 
        'wednesday': 'Mittwoch',
        'thursday': 'Donnerstag',
        'friday': 'Freitag',
        'saturday': 'Samstag',
        'sunday': 'Sonntag'
    }
    return days[dayKey] || dayKey
}

const getSlotTypeName = (type) => {
    const types = {
        'training': 'Training',
        'game': 'Spiel',
        'event': 'Event',
        'maintenance': 'Wartung'
    }
    return types[type] || type
}

const getSlotTypeClass = (type) => {
    const classes = {
        'training': 'bg-blue-100 text-blue-800',
        'game': 'bg-green-100 text-green-800',
        'event': 'bg-purple-100 text-purple-800',
        'maintenance': 'bg-orange-100 text-orange-800'
    }
    return classes[type] || 'bg-gray-100 text-gray-800'
}

const calculateDuration = (startTime, endTime) => {
    const start = new Date(`2000-01-01 ${startTime}`)
    const end = new Date(`2000-01-01 ${endTime}`)
    return (end - start) / (1000 * 60) // Duration in minutes
}

const formatDuration = (startTime, endTime) => {
    const minutes = calculateDuration(startTime, endTime)
    const hours = Math.floor(minutes / 60)
    const remainingMinutes = minutes % 60
    
    if (hours > 0) {
        return remainingMinutes > 0 
            ? `${hours}h ${remainingMinutes}min`
            : `${hours}h`
    }
    return `${minutes}min`
}

// Lifecycle
onMounted(() => {
    refreshTimeSlots()
})
</script>