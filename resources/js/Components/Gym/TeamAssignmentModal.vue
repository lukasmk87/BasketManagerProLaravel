<template>
    <DialogModal :show="show" @close="$emit('close')" max-width="4xl">
        <template #title>
            <div class="flex items-center">
                <UsersIcon class="h-6 w-6 mr-2 text-blue-600" />
                Team-Zuordnung - {{ timeSlot?.title }}
            </div>
        </template>
        
        <template #content>
            <div v-if="timeSlot" class="space-y-6">
                <!-- Time Slot Info -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 mb-2">Zeitslot-Details</h4>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600">Wochentag:</span>
                            <span class="ml-2 font-medium">{{ getDayName(timeSlot.day_of_week) }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600">Zeit:</span>
                            <span class="ml-2 font-medium">{{ timeSlot.start_time }} - {{ timeSlot.end_time }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600">Typ:</span>
                            <span class="ml-2 font-medium">{{ getSlotTypeName(timeSlot.slot_type) }}</span>
                        </div>
                        <div v-if="timeSlot.team">
                            <span class="text-gray-600">Aktuelles Team:</span>
                            <span class="ml-2 font-medium text-green-600">{{ timeSlot.team.name }}</span>
                        </div>
                    </div>
                </div>

                <!-- Team Assignment -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        Team zuordnen
                    </label>
                    
                    <div class="space-y-4">
                        <!-- Team Selection -->
                        <div>
                            <select 
                                v-model="selectedTeamId" 
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                :disabled="assigningTeam"
                            >
                                <option value="">Team auswählen...</option>
                                <option 
                                    v-for="team in availableTeams" 
                                    :key="team.id" 
                                    :value="team.id"
                                >
                                    {{ team.name }} 
                                    <span v-if="team.age_group" class="text-gray-500">({{ team.age_group }})</span>
                                </option>
                            </select>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex space-x-3">
                            <button
                                v-if="selectedTeamId && !timeSlot.team"
                                @click="assignTeam"
                                :disabled="assigningTeam"
                                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md disabled:opacity-50 flex items-center"
                            >
                                <UserPlusIcon class="w-4 h-4 mr-2" />
                                {{ assigningTeam ? 'Zuordnen...' : 'Team zuordnen' }}
                            </button>
                            
                            <button
                                v-if="selectedTeamId && timeSlot.team && selectedTeamId !== timeSlot.team.id"
                                @click="assignTeam"
                                :disabled="assigningTeam"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md disabled:opacity-50 flex items-center"
                            >
                                <ArrowPathIcon class="w-4 h-4 mr-2" />
                                {{ assigningTeam ? 'Ändern...' : 'Team ändern' }}
                            </button>
                            
                            <button
                                v-if="timeSlot.team"
                                @click="removeTeam"
                                :disabled="removingTeam"
                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md disabled:opacity-50 flex items-center"
                            >
                                <UserMinusIcon class="w-4 h-4 mr-2" />
                                {{ removingTeam ? 'Entfernen...' : 'Team entfernen' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Conflicts Warning -->
                <div v-if="conflicts.length > 0" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <ExclamationTriangleIcon class="h-5 w-5 text-yellow-400 mt-0.5 mr-3" />
                        <div>
                            <h4 class="text-sm font-medium text-yellow-800">Zeitkonflikte gefunden</h4>
                            <div class="mt-2 space-y-2">
                                <div v-for="conflict in conflicts" :key="conflict.conflicting_slot.id" class="text-sm text-yellow-700">
                                    {{ conflict.message }}: 
                                    <strong>{{ conflict.conflicting_slot.title }}</strong>
                                    ({{ getDayName(conflict.conflicting_slot.day_of_week) }}, 
                                    {{ conflict.conflicting_slot.start_time }} - {{ conflict.conflicting_slot.end_time }})
                                    in {{ conflict.conflicting_slot.gym_hall }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading State -->
                <div v-if="loadingTeams" class="flex items-center justify-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <span class="ml-3 text-gray-600">Teams werden geladen...</span>
                </div>
            </div>
        </template>
        
        <template #footer>
            <div class="flex justify-end space-x-3">
                <button
                    @click="$emit('close')"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md"
                >
                    Schließen
                </button>
            </div>
        </template>
    </DialogModal>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import DialogModal from '@/Components/DialogModal.vue'
import {
    UsersIcon,
    UserPlusIcon,
    UserMinusIcon,
    ArrowPathIcon,
    ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'

const emit = defineEmits(['close', 'updated'])

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    timeSlot: {
        type: Object,
        default: null
    }
})

// State
const availableTeams = ref([])
const selectedTeamId = ref('')
const loadingTeams = ref(false)
const assigningTeam = ref(false)
const removingTeam = ref(false)
const conflicts = ref([])

// Computed
const selectedTeam = computed(() => {
    return availableTeams.value.find(team => team.id === selectedTeamId.value)
})

// Watch for modal opening
watch(() => props.show, (show) => {
    if (show) {
        loadAvailableTeams()
        selectedTeamId.value = props.timeSlot?.team?.id || ''
        conflicts.value = []
    }
})

// Methods
const loadAvailableTeams = async () => {
    try {
        loadingTeams.value = true
        const response = await axios.get('/gym-management/available-teams')
        availableTeams.value = response.data.data
    } catch (error) {
        console.error('Fehler beim Laden der Teams:', error)
        alert('Fehler beim Laden der Teams')
    } finally {
        loadingTeams.value = false
    }
}

const assignTeam = async () => {
    if (!selectedTeamId.value) return
    
    try {
        assigningTeam.value = true
        conflicts.value = []
        
        const response = await axios.post(`/gym-management/time-slots/${props.timeSlot.id}/assign-team`, {
            team_id: selectedTeamId.value
        })
        
        if (response.data.success) {
            emit('updated')
            emit('close')
        }
    } catch (error) {
        if (error.response?.status === 422 && error.response.data.conflicts) {
            conflicts.value = error.response.data.conflicts
        } else {
            console.error('Fehler beim Zuordnen des Teams:', error)
            alert(error.response?.data?.message || 'Fehler beim Zuordnen des Teams')
        }
    } finally {
        assigningTeam.value = false
    }
}

const removeTeam = async () => {
    try {
        removingTeam.value = true
        
        const response = await axios.delete(`/gym-management/time-slots/${props.timeSlot.id}/remove-team`)
        
        if (response.data.success) {
            emit('updated')
            emit('close')
        }
    } catch (error) {
        console.error('Fehler beim Entfernen der Team-Zuordnung:', error)
        alert(error.response?.data?.message || 'Fehler beim Entfernen der Team-Zuordnung')
    } finally {
        removingTeam.value = false
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
</script>