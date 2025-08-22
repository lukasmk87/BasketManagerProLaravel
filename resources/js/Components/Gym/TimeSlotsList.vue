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
                                    <div class="mt-2">
                                        <!-- Legacy team assignment (full slot) -->
                                        <div v-if="timeSlot.team" class="flex items-center space-x-1 mb-2">
                                            <UsersIcon class="w-4 h-4 text-green-500" />
                                            <span class="text-sm text-green-700 font-medium">
                                                {{ timeSlot.team.name }} (Komplett)
                                            </span>
                                        </div>
                                        
                                        <!-- Segment-based assignments -->
                                        <div v-if="getSegmentAssignments(timeSlot).length > 0" class="space-y-1">
                                            <div class="text-xs text-gray-500 font-medium mb-1">30-Min-Zuordnungen:</div>
                                            <div 
                                                v-for="assignment in getSegmentAssignments(timeSlot)" 
                                                :key="assignment.id"
                                                class="flex items-center justify-between bg-blue-50 rounded px-2 py-1"
                                            >
                                                <div class="flex items-center space-x-2">
                                                    <ClockIcon class="w-3 h-3 text-blue-500" />
                                                    <span class="text-xs text-blue-700 font-medium">
                                                        {{ assignment.team_name }}
                                                    </span>
                                                    <span class="text-xs text-blue-600">
                                                        ({{ assignment.start_time }}-{{ assignment.end_time }})
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- No assignments -->
                                        <div v-if="!timeSlot.team && getSegmentAssignments(timeSlot).length === 0" class="flex items-center space-x-1">
                                            <UserIcon class="w-4 h-4 text-gray-400" />
                                            <span class="text-sm text-gray-500">
                                                Kein Team zugeordnet
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Actions -->
                                <div class="flex flex-col items-end space-y-2 ml-4">
                                    <button
                                        @click="openTeamAssignment(timeSlot)"
                                        class="px-3 py-1 text-xs font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-md transition-colors"
                                    >
                                        {{ timeSlot.team ? 'Team ändern' : 'Team zuordnen' }}
                                    </button>
                                    <button
                                        @click="openSegmentAssignment(timeSlot)"
                                        class="px-3 py-1 text-xs font-medium text-purple-600 bg-purple-50 hover:bg-purple-100 rounded-md transition-colors"
                                    >
                                        30-Min-Slots
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

        <!-- Team Segment Assignment Modal -->
        <div v-if="showSegmentModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-hidden">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">
                            30-Minuten-Zeitfenster: {{ selectedTimeSlot?.title }}
                        </h3>
                        <button
                            @click="closeSegmentModal"
                            class="text-gray-400 hover:text-gray-600"
                        >
                            <XMarkIcon class="w-6 h-6" />
                        </button>
                    </div>
                </div>
                <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                    <TeamTimeSegmentAssignment
                        v-if="selectedTimeSlot"
                        :time-slot-id="selectedTimeSlot.id"
                        :gym-hall-id="gymHallId"
                        @updated="handleSegmentUpdated"
                        @error="handleSegmentError"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import TeamAssignmentModal from './TeamAssignmentModal.vue'
import TeamTimeSegmentAssignment from './TeamTimeSegmentAssignment.vue'
import {
    ClockIcon,
    UsersIcon,
    UserIcon,
    ArrowPathIcon,
    XMarkIcon
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
const showSegmentModal = ref(false)
const selectedTimeSlot = ref(null)
const segmentAssignments = ref({})

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
            // Load segment assignments for all time slots
            setTimeout(() => loadAllSegmentAssignments(), 100)
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

const openSegmentAssignment = (timeSlot) => {
    selectedTimeSlot.value = timeSlot
    showSegmentModal.value = true
    loadSegmentAssignments(timeSlot.id)
}

const closeSegmentModal = () => {
    selectedTimeSlot.value = null
    showSegmentModal.value = false
}

const handleSegmentUpdated = () => {
    if (selectedTimeSlot.value) {
        loadSegmentAssignments(selectedTimeSlot.value.id)
    }
    refreshTimeSlots()
    emit('updated')
}

const handleSegmentError = (error) => {
    console.error('Segment assignment error:', error)
}

const loadSegmentAssignments = async (timeSlotId) => {
    try {
        const response = await axios.get(`/api/v2/time-slots/${timeSlotId}/team-assignments`)
        
        if (response.data.success) {
            segmentAssignments.value[timeSlotId] = response.data.data
        }
    } catch (error) {
        console.error('Error loading segment assignments:', error)
    }
}

const getSegmentAssignments = (timeSlot) => {
    const assignments = segmentAssignments.value[timeSlot.id]
    if (!assignments) return []
    
    return Object.values(assignments).flat()
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
        loadAllSegmentAssignments()
    }
})

const loadAllSegmentAssignments = async () => {
    for (const timeSlot of timeSlots.value) {
        await loadSegmentAssignments(timeSlot.id)
    }
}

// Watch for gymHallId changes
watch(() => props.gymHallId, (newId, oldId) => {
    if (newId && newId !== oldId) {
        refreshTimeSlots()
    }
})
</script>