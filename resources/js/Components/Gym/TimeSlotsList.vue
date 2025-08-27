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
                                            <div class="text-xs text-gray-500 font-medium mb-1">Zeitfenster-Zuordnungen:</div>
                                            <div 
                                                v-for="assignment in getSegmentAssignments(timeSlot)" 
                                                :key="assignment.id"
                                                :class="[
                                                    'flex items-center justify-between rounded px-2 py-1',
                                                    getAssignmentColorClass(assignment.duration_minutes || 30)
                                                ]"
                                            >
                                                <div class="flex items-center space-x-2">
                                                    <ClockIcon class="w-3 h-3" :class="getAssignmentIconClass(assignment.duration_minutes || 30)" />
                                                    <span class="text-xs font-medium" :class="getAssignmentTextClass(assignment.duration_minutes || 30)">
                                                        {{ assignment.team_name }}
                                                    </span>
                                                    <span class="text-xs" :class="getAssignmentSubTextClass(assignment.duration_minutes || 30)">
                                                        ({{ assignment.start_time }}-{{ assignment.end_time }})
                                                        {{ assignment.court_name ? `- ${assignment.court_name}` : '' }}
                                                    </span>
                                                    <span :class="[
                                                        'inline-flex px-1.5 py-0.5 text-xs font-semibold rounded-full',
                                                        getDurationBadgeClass(assignment.duration_minutes || 30)
                                                    ]">
                                                        {{ getDurationLabel(assignment.duration_minutes || 30) }}
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
                                <div class="flex flex-col items-end ml-4">
                                    <button
                                        @click="openSegmentAssignment(timeSlot)"
                                        class="px-3 py-1 text-xs font-medium text-purple-600 bg-purple-50 hover:bg-purple-100 rounded-md transition-colors"
                                    >
                                        Team & Zeitfenster zuordnen
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Team Segment Assignment Modal -->
        <div v-if="showSegmentModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-hidden">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Zeitfenster-Zuordnung: {{ selectedTimeSlot?.title }}
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

// Duration-based styling helpers
const getDurationLabel = (minutes) => {
    switch (minutes) {
        case 30:
            return '30m'
        case 60:
            return '1h'
        case 90:
            return '1,5h'
        case 120:
            return '2h'
        default:
            return `${minutes}m`
    }
}

const getAssignmentColorClass = (minutes) => {
    switch (minutes) {
        case 30:
            return 'bg-blue-50'
        case 60:
            return 'bg-green-50'
        case 90:
            return 'bg-orange-50'
        case 120:
            return 'bg-purple-50'
        default:
            return 'bg-gray-50'
    }
}

const getAssignmentIconClass = (minutes) => {
    switch (minutes) {
        case 30:
            return 'text-blue-500'
        case 60:
            return 'text-green-500'
        case 90:
            return 'text-orange-500'
        case 120:
            return 'text-purple-500'
        default:
            return 'text-gray-500'
    }
}

const getAssignmentTextClass = (minutes) => {
    switch (minutes) {
        case 30:
            return 'text-blue-700'
        case 60:
            return 'text-green-700'
        case 90:
            return 'text-orange-700'
        case 120:
            return 'text-purple-700'
        default:
            return 'text-gray-700'
    }
}

const getAssignmentSubTextClass = (minutes) => {
    switch (minutes) {
        case 30:
            return 'text-blue-600'
        case 60:
            return 'text-green-600'
        case 90:
            return 'text-orange-600'
        case 120:
            return 'text-purple-600'
        default:
            return 'text-gray-600'
    }
}

const getDurationBadgeClass = (minutes) => {
    switch (minutes) {
        case 30:
            return 'bg-blue-200 text-blue-800'
        case 60:
            return 'bg-green-200 text-green-800'
        case 90:
            return 'bg-orange-200 text-orange-800'
        case 120:
            return 'bg-purple-200 text-purple-800'
        default:
            return 'bg-gray-200 text-gray-800'
    }
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