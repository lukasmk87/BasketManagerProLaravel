<template>
    <DialogModal :show="show" @close="$emit('close')" max-width="xl">
        <template #title>
            <div class="flex items-center">
                <svg class="h-6 w-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ timeSlot ? 'Zeitfenster bearbeiten' : 'Neues Zeitfenster' }}
            </div>
        </template>

        <template #content>
            <form @submit.prevent="submitForm">
                <div class="space-y-6">
                    <!-- Basic Information -->
                    <div>
                        <h4 class="font-medium text-gray-900 mb-4">Grundinformationen</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Titel</label>
                                <input
                                    v-model="form.title"
                                    type="text"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="z.B. U18 Training"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Wochentag</label>
                                <select
                                    v-model="form.day_of_week"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                    required
                                >
                                    <option value="">Wochentag auswählen</option>
                                    <option value="monday">Montag</option>
                                    <option value="tuesday">Dienstag</option>
                                    <option value="wednesday">Mittwoch</option>
                                    <option value="thursday">Donnerstag</option>
                                    <option value="friday">Freitag</option>
                                    <option value="saturday">Samstag</option>
                                    <option value="sunday">Sonntag</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Time Settings -->
                    <div>
                        <h4 class="font-medium text-gray-900 mb-4">Zeiten</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Startzeit</label>
                                <input
                                    v-model="form.start_time"
                                    type="time"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Endzeit</label>
                                <input
                                    v-model="form.end_time"
                                    type="time"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Dauer (Min)</label>
                                <input
                                    :value="calculatedDuration"
                                    type="number"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-gray-50"
                                    readonly
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Team Assignment -->
                    <div>
                        <h4 class="font-medium text-gray-900 mb-4">Team-Zuordnung</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Team auswählen (optional)</label>
                                <select
                                    v-model="form.team_id"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option value="">Kein Team zugeordnet</option>
                                    <option
                                        v-for="team in availableTeams"
                                        :key="team.id"
                                        :value="team.id"
                                    >
                                        {{ team.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Max. Teilnehmer</label>
                                <input
                                    v-model.number="form.max_participants"
                                    type="number"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                    min="1"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Validity Period -->
                    <div>
                        <h4 class="font-medium text-gray-900 mb-4">Gültigkeitszeitraum</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Gültig ab</label>
                                <input
                                    v-model="form.valid_from"
                                    type="date"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Gültig bis (optional)</label>
                                <input
                                    v-model="form.valid_until"
                                    type="date"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Flexible Booking Settings -->
                    <div v-if="selectedHall?.supports_parallel_bookings || selectedHall?.court_count > 1">
                        <h4 class="font-medium text-gray-900 mb-4">Flexible Buchungsoptionen</h4>
                        <div class="space-y-4">
                            <!-- Court Preferences -->
                            <div v-if="availableCourts.length > 1">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Bevorzugte Courts (optional)</label>
                                <div class="space-y-2">
                                    <div
                                        v-for="court in availableCourts"
                                        :key="court.id"
                                        class="flex items-center space-x-3 p-2 border rounded"
                                        :class="[
                                            form.preferred_courts.includes(court.id) 
                                                ? 'border-blue-500 bg-blue-50' 
                                                : 'border-gray-200'
                                        ]"
                                    >
                                        <input
                                            :id="`pref-court-${court.id}`"
                                            v-model="form.preferred_courts"
                                            :value="court.id"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-blue-600 focus:border-blue-300 focus:ring focus:ring-blue-200"
                                        />
                                        <div 
                                            class="w-3 h-3 rounded"
                                            :style="{ backgroundColor: court.color_code }"
                                        ></div>
                                        <label :for="`pref-court-${court.id}`" class="flex-1 cursor-pointer text-sm">
                                            {{ court.court_identifier }} - {{ court.court_name }}
                                        </label>
                                    </div>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">
                                    Teams können bevorzugte Courts automatisch zugewiesen bekommen, wenn verfügbar.
                                </p>
                            </div>

                            <!-- 30-Min Slot Support -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="flex items-center">
                                        <input
                                            v-model="form.supports_30_min_slots"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                                        />
                                        <span class="ml-2 text-sm text-gray-700">30-Minuten-Slots unterstützen</span>
                                    </label>
                                    <p class="mt-1 text-xs text-gray-500 ml-6">
                                        Ermöglicht flexible Buchungen in 30-Min-Schritten
                                    </p>
                                </div>
                                <div>
                                    <label class="flex items-center">
                                        <input
                                            v-model="form.allows_partial_court"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                                        />
                                        <span class="ml-2 text-sm text-gray-700">Teilcourt-Buchungen erlauben</span>
                                    </label>
                                    <p class="mt-1 text-xs text-gray-500 ml-6">
                                        Teams können nur einzelne Courts buchen
                                    </p>
                                </div>
                            </div>

                            <!-- Booking Duration Settings -->
                            <div v-if="form.supports_30_min_slots" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Mindestbuchungsdauer (Min)</label>
                                    <select
                                        v-model.number="form.min_booking_duration_minutes"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                    >
                                        <option :value="15">15 Minuten</option>
                                        <option :value="30">30 Minuten</option>
                                        <option :value="60">60 Minuten</option>
                                        <option :value="90">90 Minuten</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Buchungsraster (Min)</label>
                                    <select
                                        v-model.number="form.booking_increment_minutes"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                    >
                                        <option :value="15">15 Minuten</option>
                                        <option :value="30">30 Minuten</option>
                                        <option :value="60">60 Minuten</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Settings -->
                    <div>
                        <h4 class="font-medium text-gray-900 mb-4">Allgemeine Einstellungen</h4>
                        <div class="space-y-3">
                            <label class="flex items-center">
                                <input
                                    v-model="form.is_recurring"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                />
                                <span class="ml-2 text-sm text-gray-700">Wiederkehrendes Zeitfenster</span>
                            </label>
                            <label class="flex items-center">
                                <input
                                    v-model="form.allows_substitution"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                />
                                <span class="ml-2 text-sm text-gray-700">Vertretungen erlauben</span>
                            </label>
                        </div>
                    </div>

                    <!-- Additional Information -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Beschreibung</label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Zusätzliche Informationen..."
                        ></textarea>
                    </div>

                    <!-- Error Messages -->
                    <div v-if="errors.length > 0" class="bg-red-50 border border-red-200 rounded-md p-4">
                        <div class="flex">
                            <svg class="h-5 w-5 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            <div>
                                <h3 class="text-sm font-medium text-red-800">Fehler beim Speichern:</h3>
                                <ul class="mt-1 text-sm text-red-700 list-disc list-inside">
                                    <li v-for="error in errors" :key="error">{{ error }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </template>

        <template #footer>
            <div class="flex justify-between">
                <div>
                    <button
                        v-if="timeSlot"
                        @click="deleteTimeSlot"
                        type="button"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md"
                        :disabled="submitting"
                    >
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Löschen
                    </button>
                </div>
                <div class="flex space-x-3">
                    <button
                        @click="$emit('close')"
                        type="button"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md"
                        :disabled="submitting"
                    >
                        Abbrechen
                    </button>
                    <button
                        @click="submitForm"
                        type="button"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md"
                        :disabled="submitting"
                    >
                        {{ submitting ? 'Speichere...' : (timeSlot ? 'Aktualisieren' : 'Erstellen') }}
                    </button>
                </div>
            </div>
        </template>
    </DialogModal>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import DialogModal from '@/Components/DialogModal.vue'

const props = defineProps({
    show: Boolean,
    timeSlot: Object,
    gymHall: Object,
    availableCourts: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['close', 'updated'])

// Form data
const form = ref({
    title: '',
    gym_hall_id: null,
    team_id: null,
    day_of_week: '',
    start_time: '',
    end_time: '',
    valid_from: '',
    valid_until: '',
    is_recurring: true,
    allows_substitution: true,
    max_participants: null,
    description: '',
    slot_type: 'training',
    // New flexible booking fields
    preferred_courts: [],
    supports_30_min_slots: true,
    allows_partial_court: false,
    min_booking_duration_minutes: 30,
    booking_increment_minutes: 30
})

const submitting = ref(false)
const errors = ref([])
const page = usePage()

// Computed
const calculatedDuration = computed(() => {
    if (!form.value.start_time || !form.value.end_time) return 0
    
    const start = new Date(`2000-01-01T${form.value.start_time}`)
    const end = new Date(`2000-01-01T${form.value.end_time}`)
    
    if (end <= start) return 0
    
    return Math.round((end - start) / 60000) // minutes
})

const availableTeams = computed(() => {
    return page.props.user?.teams || []
})

const selectedHall = computed(() => {
    return props.gymHall
})

// Methods
const submitForm = async () => {
    if (!validateForm()) return
    
    submitting.value = true
    errors.value = []
    
    try {
        const url = props.timeSlot 
            ? `/api/v2/gym-time-slots/${props.timeSlot.id}`
            : '/api/v2/gym-time-slots'
        
        const method = props.timeSlot ? 'PUT' : 'POST'
        
        const data = {
            ...form.value,
            gym_hall_id: props.gymHall?.id,
            duration_minutes: calculatedDuration.value
        }
        
        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify(data)
        })
        
        if (response.ok) {
            emit('updated')
            emit('close')
            resetForm()
        } else {
            const errorData = await response.json()
            errors.value = Object.values(errorData.errors || {}).flat()
        }
    } catch (error) {
        console.error('Error saving time slot:', error)
        errors.value = ['Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.']
    } finally {
        submitting.value = false
    }
}

const deleteTimeSlot = async () => {
    if (!props.timeSlot) return
    
    const confirmed = confirm('Möchten Sie dieses Zeitfenster wirklich löschen?')
    if (!confirmed) return
    
    try {
        const response = await fetch(`/api/v2/gym-time-slots/${props.timeSlot.id}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            }
        })
        
        if (response.ok) {
            emit('updated')
            emit('close')
        } else {
            const errorData = await response.json()
            errors.value = [errorData.message || 'Fehler beim Löschen']
        }
    } catch (error) {
        console.error('Error deleting time slot:', error)
        errors.value = ['Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.']
    }
}

const validateForm = () => {
    errors.value = []
    
    if (!form.value.title.trim()) {
        errors.value.push('Titel ist erforderlich')
    }
    
    if (!form.value.day_of_week) {
        errors.value.push('Wochentag ist erforderlich')
    }
    
    if (!form.value.start_time || !form.value.end_time) {
        errors.value.push('Start- und Endzeit sind erforderlich')
    }
    
    if (calculatedDuration.value <= 0) {
        errors.value.push('Endzeit muss nach der Startzeit liegen')
    }
    
    if (!form.value.valid_from) {
        errors.value.push('Gültig ab Datum ist erforderlich')
    }
    
    if (form.value.valid_until && form.value.valid_until <= form.value.valid_from) {
        errors.value.push('Gültig bis muss nach Gültig ab liegen')
    }
    
    return errors.value.length === 0
}

const resetForm = () => {
    form.value = {
        title: '',
        gym_hall_id: null,
        team_id: null,
        day_of_week: '',
        start_time: '',
        end_time: '',
        valid_from: '',
        valid_until: '',
        is_recurring: true,
        allows_substitution: true,
        max_participants: null,
        description: '',
        slot_type: 'training',
        preferred_courts: [],
        supports_30_min_slots: true,
        allows_partial_court: false,
        min_booking_duration_minutes: 30,
        booking_increment_minutes: 30
    }
    errors.value = []
}

// Watch for timeSlot changes to populate form
watch(() => props.timeSlot, (newTimeSlot) => {
    if (newTimeSlot) {
        form.value = {
            title: newTimeSlot.title || '',
            gym_hall_id: newTimeSlot.gym_hall_id || props.gymHall?.id,
            team_id: newTimeSlot.team_id || null,
            day_of_week: newTimeSlot.day_of_week || '',
            start_time: newTimeSlot.start_time || '',
            end_time: newTimeSlot.end_time || '',
            valid_from: newTimeSlot.valid_from || '',
            valid_until: newTimeSlot.valid_until || '',
            is_recurring: newTimeSlot.is_recurring ?? true,
            allows_substitution: newTimeSlot.allows_substitution ?? true,
            max_participants: newTimeSlot.max_participants || null,
            description: newTimeSlot.description || '',
            slot_type: newTimeSlot.slot_type || 'training',
            preferred_courts: newTimeSlot.preferred_courts || [],
            supports_30_min_slots: newTimeSlot.supports_30_min_slots ?? true,
            allows_partial_court: newTimeSlot.allows_partial_court ?? false,
            min_booking_duration_minutes: newTimeSlot.min_booking_duration_minutes || 30,
            booking_increment_minutes: newTimeSlot.booking_increment_minutes || 30
        }
    } else {
        resetForm()
        // Set default valid_from to today
        form.value.valid_from = new Date().toISOString().split('T')[0]
        form.value.gym_hall_id = props.gymHall?.id
    }
}, { immediate: true })

// Watch for show prop to reset form when modal opens
watch(() => props.show, (show) => {
    if (show && !props.timeSlot) {
        resetForm()
        form.value.valid_from = new Date().toISOString().split('T')[0]
        form.value.gym_hall_id = props.gymHall?.id
    }
})
</script>