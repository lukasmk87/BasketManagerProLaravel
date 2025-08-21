<template>
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Verfügbarkeitszeiten</h3>
                <p class="text-sm text-gray-600 mt-1">
                    Stellen Sie für jeden Wochentag individuelle Öffnungszeiten ein
                </p>
            </div>
            <div class="flex items-center space-x-3">
                <label class="flex items-center">
                    <input
                        v-model="useCustomTimes"
                        type="checkbox"
                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        @change="toggleCustomTimes"
                    />
                    <span class="ml-2 text-sm text-gray-700">
                        Individuelle Zeiten pro Tag
                    </span>
                </label>
                <button
                    v-if="hasChanges"
                    @click="saveTimeSlots"
                    :disabled="saving"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md disabled:opacity-50"
                >
                    {{ saving ? 'Speichere...' : 'Speichern' }}
                </button>
            </div>
        </div>

        <!-- Default Times (when not using custom times) -->
        <div v-if="!useCustomTimes" class="mb-6">
            <div class="bg-blue-50 rounded-lg p-4">
                <h4 class="font-medium text-blue-900 mb-3">Einheitliche Öffnungszeiten</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-blue-800 mb-1">Von</label>
                        <input
                            v-model="defaultTimes.start_time"
                            type="time"
                            class="w-full border border-blue-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                            @change="onDefaultTimesChange"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-blue-800 mb-1">Bis</label>
                        <input
                            v-model="defaultTimes.end_time"
                            type="time"
                            class="w-full border border-blue-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                            @change="onDefaultTimesChange"
                        />
                    </div>
                </div>
                <p class="text-xs text-blue-700 mt-2">
                    Diese Zeiten gelten für alle Wochentage gleich.
                </p>
            </div>
        </div>

        <!-- Custom Times per Day -->
        <div v-if="useCustomTimes" class="space-y-4">
            <div 
                v-for="day in weekDays" 
                :key="day.key"
                class="border border-gray-200 rounded-lg p-4 hover:border-gray-300 transition-colors"
            >
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center">
                        <div class="flex items-center">
                            <input
                                :id="`${day.key}-enabled`"
                                v-model="dayTimes[day.key].enabled"
                                type="checkbox"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                @change="onDayTimesChange"
                            />
                            <label :for="`${day.key}-enabled`" class="ml-2 font-medium text-gray-900">
                                {{ day.name }}
                            </label>
                        </div>
                        <span v-if="!dayTimes[day.key].enabled" class="ml-3 text-xs text-red-600 bg-red-50 px-2 py-1 rounded">
                            Geschlossen
                        </span>
                    </div>
                    <div v-if="dayTimes[day.key].enabled && dayTimes[day.key].start_time && dayTimes[day.key].end_time" 
                         class="text-sm text-gray-600">
                        {{ dayTimes[day.key].start_time }} - {{ dayTimes[day.key].end_time }}
                    </div>
                </div>

                <div v-if="dayTimes[day.key].enabled" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Öffnung</label>
                        <input
                            v-model="dayTimes[day.key].start_time"
                            type="time"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                            @change="onDayTimesChange"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Schließung</label>
                        <input
                            v-model="dayTimes[day.key].end_time"
                            type="time"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                            @change="onDayTimesChange"
                        />
                    </div>
                </div>

                <!-- Validation Errors for this day -->
                <div v-if="validationErrors[day.key]?.length > 0" class="mt-2">
                    <div class="text-xs text-red-600 bg-red-50 rounded px-2 py-1">
                        <ul class="list-disc list-inside">
                            <li v-for="error in validationErrors[day.key]" :key="error">{{ error }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-between items-center mt-6 pt-4 border-t border-gray-200">
            <div class="text-sm text-gray-500">
                <span v-if="useCustomTimes">
                    Geöffnete Tage: {{ enabledDaysCount }}
                </span>
                <span v-else>
                    Einheitliche Zeiten für alle Tage
                </span>
            </div>
            
            <div class="flex space-x-3">
                <button
                    @click="resetToDefaults"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md"
                >
                    Zurücksetzen
                </button>
                <button
                    @click="applyToAll"
                    v-if="useCustomTimes"
                    class="px-4 py-2 text-sm font-medium text-blue-700 bg-blue-100 hover:bg-blue-200 rounded-md"
                >
                    Zeiten auf alle Tage anwenden
                </button>
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
import { ref, computed, watch, onMounted } from 'vue'

const props = defineProps({
    gymHallId: {
        type: Number,
        required: true
    },
    initialTimeSlots: {
        type: Array,
        default: () => []
    },
    defaultOpenTime: {
        type: String,
        default: '08:00'
    },
    defaultCloseTime: {
        type: String,
        default: '22:00'
    }
})

const emit = defineEmits(['updated', 'error'])

// Reactive data
const useCustomTimes = ref(false)
const saving = ref(false)
const statusMessage = ref(null)
const originalState = ref(null)

const weekDays = [
    { key: 'monday', name: 'Montag' },
    { key: 'tuesday', name: 'Dienstag' },
    { key: 'wednesday', name: 'Mittwoch' },
    { key: 'thursday', name: 'Donnerstag' },
    { key: 'friday', name: 'Freitag' },
    { key: 'saturday', name: 'Samstag' },
    { key: 'sunday', name: 'Sonntag' }
]

const defaultTimes = ref({
    start_time: props.defaultOpenTime,
    end_time: props.defaultCloseTime
})

const dayTimes = ref({})

const validationErrors = ref({})

// Initialize day times
const initializeDayTimes = () => {
    const times = {}
    weekDays.forEach(day => {
        times[day.key] = {
            enabled: false,
            start_time: props.defaultOpenTime,
            end_time: props.defaultCloseTime
        }
    })
    return times
}

// Computed properties
const hasChanges = computed(() => {
    if (!originalState.value) return false
    
    const current = getCurrentState()
    return JSON.stringify(current) !== JSON.stringify(originalState.value)
})

const enabledDaysCount = computed(() => {
    return Object.values(dayTimes.value).filter(day => day.enabled).length
})

// Methods
const getCurrentState = () => {
    return {
        useCustomTimes: useCustomTimes.value,
        defaultTimes: { ...defaultTimes.value },
        dayTimes: JSON.parse(JSON.stringify(dayTimes.value))
    }
}

const saveOriginalState = () => {
    originalState.value = getCurrentState()
}

const loadTimeSlots = () => {
    if (props.initialTimeSlots.length > 0) {
        const slot = props.initialTimeSlots[0]
        
        if (slot.uses_custom_times && slot.custom_times) {
            useCustomTimes.value = true
            
            // Load custom times for each day
            weekDays.forEach(day => {
                if (slot.custom_times[day.key]) {
                    dayTimes.value[day.key] = {
                        enabled: true,
                        start_time: slot.custom_times[day.key].start_time,
                        end_time: slot.custom_times[day.key].end_time
                    }
                }
            })
        } else {
            useCustomTimes.value = false
            if (slot.start_time && slot.end_time) {
                defaultTimes.value = {
                    start_time: slot.start_time,
                    end_time: slot.end_time
                }
            }
        }
    } else {
        // Initialize with default values
        dayTimes.value = initializeDayTimes()
    }
    
    saveOriginalState()
}

const toggleCustomTimes = () => {
    if (!useCustomTimes.value) {
        // Switching to default times
        dayTimes.value = initializeDayTimes()
    } else {
        // Switching to custom times - initialize with default times for working days
        const workingDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']
        workingDays.forEach(day => {
            dayTimes.value[day] = {
                enabled: true,
                start_time: defaultTimes.value.start_time,
                end_time: defaultTimes.value.end_time
            }
        })
    }
    
    clearValidationErrors()
}

const validateTimes = () => {
    validationErrors.value = {}
    let isValid = true
    
    if (!useCustomTimes.value) {
        if (!defaultTimes.value.start_time || !defaultTimes.value.end_time) {
            validationErrors.value.default = ['Start- und Endzeit sind erforderlich']
            isValid = false
        } else if (defaultTimes.value.start_time >= defaultTimes.value.end_time) {
            validationErrors.value.default = ['Startzeit muss vor der Endzeit liegen']
            isValid = false
        }
    } else {
        weekDays.forEach(day => {
            const dayTime = dayTimes.value[day.key]
            if (dayTime.enabled) {
                const errors = []
                
                if (!dayTime.start_time || !dayTime.end_time) {
                    errors.push('Start- und Endzeit sind erforderlich')
                } else if (dayTime.start_time >= dayTime.end_time) {
                    errors.push('Startzeit muss vor der Endzeit liegen')
                }
                
                if (errors.length > 0) {
                    validationErrors.value[day.key] = errors
                    isValid = false
                }
            }
        })
    }
    
    return isValid
}

const clearValidationErrors = () => {
    validationErrors.value = {}
}

const onDefaultTimesChange = () => {
    clearValidationErrors()
}

const onDayTimesChange = () => {
    clearValidationErrors()
}

const saveTimeSlots = async () => {
    if (!validateTimes()) {
        return
    }
    
    saving.value = true
    statusMessage.value = null
    
    try {
        const timeSlots = []
        
        if (!useCustomTimes.value) {
            // Create a single time slot with default times
            timeSlots.push({
                title: 'Standard Öffnungszeiten',
                description: 'Einheitliche Öffnungszeiten für alle Tage',
                uses_custom_times: false,
                start_time: defaultTimes.value.start_time,
                end_time: defaultTimes.value.end_time,
                slot_type: 'training',
                valid_from: new Date().toISOString().split('T')[0],
            })
        } else {
            // Create time slot with custom times
            const customTimes = {}
            let hasAnyTimes = false
            
            weekDays.forEach(day => {
                const dayTime = dayTimes.value[day.key]
                if (dayTime.enabled && dayTime.start_time && dayTime.end_time) {
                    customTimes[day.key] = {
                        start_time: dayTime.start_time,
                        end_time: dayTime.end_time
                    }
                    hasAnyTimes = true
                }
            })
            
            if (hasAnyTimes) {
                timeSlots.push({
                    title: 'Individuelle Öffnungszeiten',
                    description: 'Unterschiedliche Öffnungszeiten pro Wochentag',
                    uses_custom_times: true,
                    custom_times: customTimes,
                    slot_type: 'training',
                    valid_from: new Date().toISOString().split('T')[0],
                })
            }
        }
        
        const response = await window.axios.put(`/api/v2/gym-halls/${props.gymHallId}/time-slots`, {
            time_slots: timeSlots
        })
        
        if (response.data.success) {
            statusMessage.value = {
                type: 'success',
                text: response.data.message || 'Zeitslots erfolgreich gespeichert!'
            }
            
            saveOriginalState()
            emit('updated', response.data.data)
            
            // Clear status message after 3 seconds
            setTimeout(() => {
                statusMessage.value = null
            }, 3000)
        }
    } catch (error) {
        console.error('Error saving time slots:', error)
        statusMessage.value = {
            type: 'error',
            text: error.response?.data?.message || 'Fehler beim Speichern der Zeitslots'
        }
        emit('error', error)
    } finally {
        saving.value = false
    }
}

const resetToDefaults = () => {
    useCustomTimes.value = false
    defaultTimes.value = {
        start_time: props.defaultOpenTime,
        end_time: props.defaultCloseTime
    }
    dayTimes.value = initializeDayTimes()
    clearValidationErrors()
}

const applyToAll = () => {
    if (!useCustomTimes.value) return
    
    // Get the first enabled day's times as template
    const templateDay = weekDays.find(day => dayTimes.value[day.key].enabled)
    if (!templateDay) return
    
    const template = dayTimes.value[templateDay.key]
    
    weekDays.forEach(day => {
        dayTimes.value[day.key] = {
            enabled: true,
            start_time: template.start_time,
            end_time: template.end_time
        }
    })
}

// Lifecycle
onMounted(() => {
    dayTimes.value = initializeDayTimes()
    loadTimeSlots()
})

// Watch for prop changes
watch(() => props.initialTimeSlots, () => {
    loadTimeSlots()
}, { deep: true })
</script>