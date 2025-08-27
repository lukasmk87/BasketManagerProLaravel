<template>
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Team-Zeitfenster Zuordnung</h3>
                <p class="text-sm text-gray-600 mt-1">
                    Ordnen Sie Teams in flexiblen Zeitspannen zu
                </p>
            </div>
            <div class="flex items-center space-x-3">
                <select
                    v-model="selectedDay"
                    class="rounded border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                    @change="loadSegments"
                >
                    <option value="">Tag auswählen</option>
                    <option v-for="day in weekDays" :key="day.key" :value="day.key">
                        {{ day.name }}
                    </option>
                </select>
                
                <!-- Parallel Bookings Status -->
                <div v-if="selectedDay" class="flex items-center px-3 py-1 rounded-full text-xs font-medium"
                     :class="supportsParallelBookings 
                         ? 'bg-green-100 text-green-800' 
                         : 'bg-orange-100 text-orange-800'">
                    <div class="w-2 h-2 rounded-full mr-2"
                         :class="supportsParallelBookings ? 'bg-green-500' : 'bg-orange-500'">
                    </div>
                    {{ supportsParallelBookings ? 'Parallel-Buchungen erlaubt' : 'Nur ein Team erlaubt' }}
                </div>
                <select
                    v-model="selectedDuration"
                    class="rounded border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                    @change="loadSegments"
                >
                    <option v-for="duration in availableDurations" :key="duration.minutes" :value="duration.minutes">
                        {{ duration.label }}
                    </option>
                </select>
                <button
                    @click="loadSegments"
                    :disabled="loading || !selectedDay"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md disabled:opacity-50"
                >
                    {{ loading ? 'Laden...' : 'Aktualisieren' }}
                </button>
            </div>
        </div>

        <div v-if="!selectedDay" class="text-center py-12">
            <ClockIcon class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-2 text-sm font-medium text-gray-900">
                Wochentag auswählen
            </h3>
            <p class="mt-1 text-sm text-gray-500">
                Wählen Sie einen Wochentag aus, um die Zeitfenster anzuzeigen.
            </p>
        </div>

        <div v-else-if="loading" class="flex items-center justify-center py-12">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <span class="ml-3 text-gray-600">Zeitfenster werden geladen...</span>
        </div>

        <div v-else-if="segments.length === 0" class="text-center py-12">
            <ExclamationTriangleIcon class="mx-auto h-12 w-12 text-yellow-400" />
            <h3 class="mt-2 text-sm font-medium text-gray-900">
                Keine Zeitfenster verfügbar
            </h3>
            <p class="mt-1 text-sm text-gray-500">
                Für den ausgewählten Tag sind keine Zeitfenster definiert.
            </p>
        </div>

        <div v-else class="space-y-4">
            <!-- Time Grid -->
            <div class="grid gap-2">
                <div 
                    v-for="segment in segments" 
                    :key="segment.segment_id"
                    :class="[
                        'flex items-center justify-between p-3 rounded-lg border-2 transition-all',
                        segment.is_available 
                            ? 'border-gray-200 hover:border-blue-300 cursor-pointer bg-green-50' 
                            : 'border-orange-200 bg-orange-50'
                    ]"
                    @click="segment.is_available && openTeamSelection(segment)"
                >
                    <div class="flex items-center space-x-3">
                        <div class="flex items-center space-x-1">
                            <ClockIcon class="w-4 h-4 text-gray-600" />
                            <span class="font-medium text-gray-900">
                                {{ segment.start_time }} - {{ segment.end_time }}
                            </span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-500">
                                ({{ segment.duration_minutes }} Min.)
                            </span>
                            <span :class="[
                                'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                                getDurationColorClass(segment.duration_minutes)
                            ]">
                                {{ getDurationLabel(segment.duration_minutes) }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-3">
                        <div v-if="segment.is_available" class="flex items-center space-x-1">
                            <CheckCircleIcon class="w-4 h-4 text-green-500" />
                            <span class="text-sm text-green-700">Verfügbar</span>
                        </div>
                        <div v-else class="flex flex-col items-end">
                            <div v-for="team in segment.assigned_teams" :key="team.id" class="flex items-center space-x-1 mb-1">
                                <UsersIcon class="w-4 h-4 text-orange-500" />
                                <span class="text-sm text-orange-700 font-medium">{{ team.team_name }}</span>
                                <button
                                    @click.stop="removeAssignment(team.id)"
                                    class="ml-2 text-red-500 hover:text-red-700"
                                >
                                    <XMarkIcon class="w-3 h-3" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Team Assignment Form -->
            <div v-if="showAssignmentForm" class="border-t pt-4 mt-6">
                <h4 class="font-medium text-gray-900 mb-3">
                    Team zuordnen: {{ selectedSegment.start_time }} - {{ selectedSegment.end_time }}
                </h4>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Team</label>
                        <select
                            v-model="assignmentForm.team_id"
                            class="w-full rounded border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">Team auswählen</option>
                            <option v-for="team in availableTeams" :key="team.id" :value="team.id">
                                {{ team.name }}
                            </option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Feld {{ supportsParallelBookings ? '(optional)' : '(nicht verfügbar)' }}
                        </label>
                        <select
                            v-model="assignmentForm.gym_court_id"
                            :disabled="!supportsParallelBookings"
                            class="w-full rounded border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100 disabled:text-gray-500"
                        >
                            <option value="">{{ supportsParallelBookings ? 'Kein Feld auswählen' : 'Feld-Auswahl nicht möglich' }}</option>
                            <option v-for="court in availableCourts" :key="court.id" :value="court.id" v-if="supportsParallelBookings">
                                {{ court.name }}{{ court.is_main_court ? ' ⭐ (Hauptplatz)' : '' }}
                            </option>
                        </select>
                        <p class="text-xs mt-1" :class="supportsParallelBookings ? 'text-gray-500' : 'text-orange-600'">
                            {{ getCourtSelectionHelpText() }}
                        </p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notizen (optional)</label>
                        <input
                            v-model="assignmentForm.notes"
                            type="text"
                            placeholder="z.B. Trainingstyp, Besonderheiten..."
                            class="w-full rounded border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-4">
                    <button
                        @click="cancelAssignment"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md"
                    >
                        Abbrechen
                    </button>
                    <button
                        @click="saveAssignment"
                        :disabled="!assignmentForm.team_id || saving"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md disabled:opacity-50"
                    >
                        {{ saving ? 'Speichere...' : 'Zuordnen' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Status Messages -->
        <div v-if="statusMessage" class="mt-4">
            <div :class="[
                'p-3 rounded-md text-sm',
                statusMessage.type === 'success' 
                    ? 'bg-green-50 text-green-800 border border-green-200' 
                    : 'bg-red-50 text-red-800 border border-red-200'
            ]">
                {{ statusMessage.text }}
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import {
    ClockIcon,
    UsersIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    timeSlotId: {
        type: Number,
        required: true
    },
    gymHallId: {
        type: Number,
        required: true
    }
})

const emit = defineEmits(['updated', 'error'])

// State
const selectedDay = ref('')
const selectedDuration = ref(30)
const segments = ref([])
const availableTeams = ref([])
const availableCourts = ref([])
const loading = ref(false)
const gymHallData = ref(null)
const saving = ref(false)
const statusMessage = ref(null)
const showAssignmentForm = ref(false)
const selectedSegment = ref(null)

const weekDays = [
    { key: 'monday', name: 'Montag' },
    { key: 'tuesday', name: 'Dienstag' },
    { key: 'wednesday', name: 'Mittwoch' },
    { key: 'thursday', name: 'Donnerstag' },
    { key: 'friday', name: 'Freitag' },
    { key: 'saturday', name: 'Samstag' },
    { key: 'sunday', name: 'Sonntag' }
]

const availableDurations = [
    { minutes: 30, label: '30 Minuten' },
    { minutes: 60, label: '60 Minuten' },
    { minutes: 90, label: '90 Minuten' },
    { minutes: 120, label: '120 Minuten' }
]

const assignmentForm = ref({
    team_id: '',
    gym_court_id: '',
    notes: ''
})

// Computed
const supportsParallelBookings = computed(() => {
    if (!selectedDay.value || !gymHallData.value || !gymHallData.value.operating_hours) {
        return false
    }
    
    const daySettings = gymHallData.value.operating_hours[selectedDay.value]
    if (daySettings && daySettings.supports_parallel_bookings !== undefined) {
        return daySettings.supports_parallel_bookings
    }
    
    // Fallback to global setting
    return gymHallData.value.supports_parallel_bookings || false
})

const hasMainCourt = computed(() => {
    return availableCourts.value.some(court => court.is_main_court)
})

// Methods
const loadSegments = async () => {
    if (!selectedDay.value || !props.timeSlotId) return
    
    try {
        loading.value = true
        
        const response = await axios.get(`/api/v2/time-slots/${props.timeSlotId}/segments`, {
            params: {
                day_of_week: selectedDay.value,
                increment_minutes: selectedDuration.value
            }
        })
        
        if (response.data.success) {
            segments.value = response.data.data
        }
    } catch (error) {
        console.error('Fehler beim Laden der Zeitfenster:', error)
        showError('Fehler beim Laden der Zeitfenster')
    } finally {
        loading.value = false
    }
}

const loadAvailableTeams = async () => {
    try {
        const response = await axios.get('/gym-management/teams')
        
        if (response.data.success) {
            availableTeams.value = response.data.data
        }
    } catch (error) {
        console.error('Fehler beim Laden der Teams:', error)
    }
}

const loadAvailableCourts = async () => {
    try {
        const response = await axios.get(`/gym-management/halls/${props.gymHallId}/courts`)
        
        if (response.data.success) {
            availableCourts.value = response.data.data.filter(court => court.is_active)
        }
    } catch (error) {
        console.error('Fehler beim Laden der Felder:', error)
    }
}

const loadGymHallData = async () => {
    try {
        // Load gym hall data to get operating hours
        const response = await axios.get(`/api/v2/gym-halls/${props.gymHallId}`)
        
        if (response.data.success) {
            gymHallData.value = response.data.data
        }
    } catch (error) {
        console.error('Fehler beim Laden der Hallendaten:', error)
    }
}

const openTeamSelection = (segment) => {
    selectedSegment.value = segment
    assignmentForm.value = {
        team_id: '',
        gym_court_id: '',
        notes: ''
    }
    showAssignmentForm.value = true
}

const cancelAssignment = () => {
    selectedSegment.value = null
    showAssignmentForm.value = false
    assignmentForm.value = {
        team_id: '',
        gym_court_id: '',
        notes: ''
    }
}

const saveAssignment = async () => {
    if (!assignmentForm.value.team_id || !selectedSegment.value) return
    
    try {
        saving.value = true
        
        const response = await axios.post('/api/v2/time-slots/assign-team-segment', {
            gym_time_slot_id: props.timeSlotId,
            team_id: assignmentForm.value.team_id,
            gym_court_id: assignmentForm.value.gym_court_id || null,
            day_of_week: selectedDay.value,
            start_time: selectedSegment.value.start_time,
            end_time: selectedSegment.value.end_time,
            notes: assignmentForm.value.notes
        })
        
        if (response.data.success) {
            showSuccess(response.data.message)
            cancelAssignment()
            await loadSegments()
            emit('updated')
        }
    } catch (error) {
        console.error('Fehler beim Zuordnen:', error)
        
        let errorMessage = 'Fehler beim Zuordnen des Teams'
        if (error.response?.data?.message) {
            errorMessage = error.response.data.message
        } else if (error.response?.data?.errors) {
            errorMessage = Object.values(error.response.data.errors).flat().join(', ')
        }
        
        showError(errorMessage)
    } finally {
        saving.value = false
    }
}

const removeAssignment = async (assignmentId) => {
    if (!confirm('Möchten Sie diese Team-Zuordnung wirklich entfernen?')) {
        return
    }
    
    try {
        const response = await axios.delete(`/api/v2/team-assignments/${assignmentId}`)
        
        if (response.data.success) {
            showSuccess(response.data.message)
            await loadSegments()
            emit('updated')
        }
    } catch (error) {
        console.error('Fehler beim Entfernen:', error)
        showError('Fehler beim Entfernen der Team-Zuordnung')
    }
}

const showSuccess = (message) => {
    statusMessage.value = { type: 'success', text: message }
    setTimeout(() => {
        statusMessage.value = null
    }, 3000)
}

const showError = (message) => {
    statusMessage.value = { type: 'error', text: message }
    setTimeout(() => {
        statusMessage.value = null
    }, 5000)
}

// UI Helper Functions
const getDurationLabel = (minutes) => {
    switch (minutes) {
        case 30:
            return '30 Min'
        case 60:
            return '1 Std'
        case 90:
            return '1,5 Std'
        case 120:
            return '2 Std'
        default:
            return `${minutes} Min`
    }
}

const getDurationColorClass = (minutes) => {
    switch (minutes) {
        case 30:
            return 'bg-blue-100 text-blue-800'
        case 60:
            return 'bg-green-100 text-green-800'
        case 90:
            return 'bg-orange-100 text-orange-800'
        case 120:
            return 'bg-purple-100 text-purple-800'
        default:
            return 'bg-gray-100 text-gray-800'
    }
}

const getCourtSelectionHelpText = () => {
    if (!supportsParallelBookings.value) {
        return 'Parallel-Buchungen für diesen Tag deaktiviert - nur ein Team erlaubt'
    }
    
    if (hasMainCourt.value) {
        return 'Optional: Spezifisches Feld zuordnen. ⚠️ Hauptplatz blockiert andere Felder'
    }
    
    return 'Optional: Spezifisches Feld zuordnen für Parallel-Buchungen'
}

// Lifecycle
onMounted(() => {
    loadAvailableTeams()
    loadAvailableCourts()
    loadGymHallData()
})

// Watch for changes
watch(() => props.timeSlotId, () => {
    if (selectedDay.value) {
        loadSegments()
    }
})
</script>