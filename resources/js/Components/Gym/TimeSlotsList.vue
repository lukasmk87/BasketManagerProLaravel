<template>
    <div class="bg-white rounded-lg border border-gray-200">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Trainingszeiten & Team-Zuordnungen</h3>
                    <p class="text-sm text-gray-600 mt-1">
                        Verwalten Sie die Zeitslots und ordnen Sie Teams zu
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

            <div v-if="loading && !timeSlots.length" class="flex items-center justify-center py-12">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <span class="ml-3 text-gray-600">Zeitslots werden geladen...</span>
            </div>

            <div v-else-if="timeSlots.length === 0" class="text-center py-12">
                <ClockIcon class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-medium text-gray-900">
                    Keine Zeitslots vorhanden
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    Erstellen Sie zuerst Zeitslots für diese Halle.
                </p>
            </div>

            <div v-else class="space-y-4">
                <!-- Time Slots Grid -->
                <div class="grid grid-cols-1 gap-4">
                    <div 
                        v-for="slot in groupedTimeSlots" 
                        :key="slot.day"
                        class="border border-gray-200 rounded-lg"
                    >
                        <!-- Day Header -->
                        <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                            <h4 class="font-medium text-gray-900">{{ getDayName(slot.day) }}</h4>
                        </div>
                        
                        <!-- Slots for this day -->
                        <div class="p-4 space-y-3">
                            <div 
                                v-for="timeSlot in slot.slots" 
                                :key="timeSlot.id"
                                class="flex items-center justify-between p-3 bg-white border border-gray-200 rounded-lg hover:border-gray-300 transition-colors"
                            >
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3">
                                        <div class="flex-shrink-0">
                                            <div class="flex items-center space-x-1">
                                                <ClockIcon class="w-4 h-4 text-gray-400" />
                                                <span class="text-sm font-medium text-gray-900">
                                                    {{ timeSlot.start_time }} - {{ timeSlot.end_time }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate">
                                                {{ timeSlot.title }}
                                            </p>
                                            <p v-if="timeSlot.description" class="text-sm text-gray-500 truncate">
                                                {{ timeSlot.description }}
                                            </p>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <span :class="[
                                                'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                                                getSlotTypeClass(timeSlot.slot_type)
                                            ]">
                                                {{ getSlotTypeName(timeSlot.slot_type) }}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- Team Assignment Info -->
                                    <div class="mt-2 flex items-center space-x-3">
                                        <div v-if="timeSlot.team" class="flex items-center space-x-1">
                                            <UsersIcon class="w-4 h-4 text-green-500" />
                                            <span class="text-sm text-green-700 font-medium">
                                                {{ timeSlot.team.name }}
                                            </span>
                                        </div>
                                        <div v-else class="flex items-center space-x-1">
                                            <UserIcon class="w-4 h-4 text-gray-400" />
                                            <span class="text-sm text-gray-500">
                                                Kein Team zugeordnet
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Actions -->
                                <div class="flex items-center space-x-2 ml-4">
                                    <button
                                        @click="openTeamAssignment(timeSlot)"
                                        class="px-3 py-1 text-xs font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-md transition-colors"
                                    >
                                        {{ timeSlot.team ? 'Team ändern' : 'Team zuordnen' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Team Assignment Modal -->
        <TeamAssignmentModal
            :show="showTeamModal"
            :time-slot="selectedTimeSlot"
            @close="closeTeamModal"
            @updated="handleTeamUpdated"
        />
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import TeamAssignmentModal from './TeamAssignmentModal.vue'
import {
    ClockIcon,
    UsersIcon,
    UserIcon,
    ArrowPathIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    gymHallId: {
        type: Number,
        required: true
    }
})

const emit = defineEmits(['updated'])

// State
const timeSlots = ref([])
const loading = ref(false)
const showTeamModal = ref(false)
const selectedTimeSlot = ref(null)

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

// Methods
const refreshTimeSlots = async () => {
    if (!props.gymHallId) {
        console.warn('TimeSlotsList: No gymHallId provided')
        return
    }
    
    try {
        loading.value = true
        console.log('Loading time slots for hall:', props.gymHallId)
        
        const response = await axios.get(`/gym-management/halls/${props.gymHallId}/time-slots`)
        console.log('Time slots response:', response.data)
        
        if (response.data.success && response.data.data) {
            timeSlots.value = response.data.data
        } else {
            timeSlots.value = response.data.data || []
        }
    } catch (error) {
        console.error('Fehler beim Laden der Zeitslots:', error)
        timeSlots.value = []
    } finally {
        loading.value = false
    }
}

const openTeamAssignment = (timeSlot) => {
    selectedTimeSlot.value = timeSlot
    showTeamModal.value = true
}

const closeTeamModal = () => {
    selectedTimeSlot.value = null
    showTeamModal.value = false
}

const handleTeamUpdated = () => {
    refreshTimeSlots()
    emit('updated')
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

// Lifecycle
onMounted(() => {
    if (props.gymHallId) {
        refreshTimeSlots()
    }
})

// Watch for gymHallId changes
watch(() => props.gymHallId, (newId, oldId) => {
    if (newId && newId !== oldId) {
        refreshTimeSlots()
    }
})
</script>