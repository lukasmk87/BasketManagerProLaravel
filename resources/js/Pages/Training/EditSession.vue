<template>
    <AppLayout :title="`Trainingseinheit bearbeiten: ${form.title}`">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Trainingseinheit bearbeiten
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <form @submit.prevent="submitForm" class="p-6">
                        <div class="space-y-6">
                            <!-- Basic Information -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Grundinformationen</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="title" class="block text-sm font-medium text-gray-700">
                                            Titel der Trainingseinheit *
                                        </label>
                                        <input
                                            id="title"
                                            v-model="form.title"
                                            type="text"
                                            required
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                            :class="{ 'border-red-500': errors.title }"
                                        />
                                        <p v-if="errors.title" class="mt-1 text-sm text-red-600">{{ errors.title }}</p>
                                    </div>

                                    <div>
                                        <label for="team_id" class="block text-sm font-medium text-gray-700">
                                            Team *
                                        </label>
                                        <select
                                            id="team_id"
                                            v-model="form.team_id"
                                            required
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                            :class="{ 'border-red-500': errors.team_id }"
                                        >
                                            <option value="">Team wählen</option>
                                            <option v-for="team in teams" :key="team.id" :value="team.id">
                                                {{ team.name }} ({{ team.club.name }})
                                            </option>
                                        </select>
                                        <p v-if="errors.team_id" class="mt-1 text-sm text-red-600">{{ errors.team_id }}</p>
                                    </div>

                                    <div>
                                        <label for="scheduled_at" class="block text-sm font-medium text-gray-700">
                                            Datum und Uhrzeit *
                                        </label>
                                        <input
                                            id="scheduled_at"
                                            v-model="form.scheduled_at"
                                            type="datetime-local"
                                            required
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                            :class="{ 'border-red-500': errors.scheduled_at }"
                                        />
                                        <p v-if="errors.scheduled_at" class="mt-1 text-sm text-red-600">{{ errors.scheduled_at }}</p>
                                    </div>

                                    <div>
                                        <label for="planned_duration" class="block text-sm font-medium text-gray-700">
                                            Geplante Dauer (Minuten) *
                                        </label>
                                        <input
                                            id="planned_duration"
                                            v-model.number="form.planned_duration"
                                            type="number"
                                            min="15"
                                            max="300"
                                            required
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                            :class="{ 'border-red-500': errors.planned_duration }"
                                        />
                                        <p v-if="errors.planned_duration" class="mt-1 text-sm text-red-600">{{ errors.planned_duration }}</p>
                                    </div>

                                    <div>
                                        <label for="session_type" class="block text-sm font-medium text-gray-700">
                                            Trainingstyp *
                                        </label>
                                        <select
                                            id="session_type"
                                            v-model="form.session_type"
                                            required
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                            :class="{ 'border-red-500': errors.session_type }"
                                        >
                                            <option value="">Typ wählen</option>
                                            <option value="training">Training</option>
                                            <option value="scrimmage">Testspiel</option>
                                            <option value="conditioning">Kondition</option>
                                            <option value="tactical">Taktik</option>
                                            <option value="individual">Individualtraining</option>
                                            <option value="team_building">Teambuilding</option>
                                            <option value="recovery">Regeneration</option>
                                        </select>
                                        <p v-if="errors.session_type" class="mt-1 text-sm text-red-600">{{ errors.session_type }}</p>
                                    </div>

                                    <div>
                                        <label for="intensity_level" class="block text-sm font-medium text-gray-700">
                                            Intensitätslevel *
                                        </label>
                                        <select
                                            id="intensity_level"
                                            v-model="form.intensity_level"
                                            required
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                            :class="{ 'border-red-500': errors.intensity_level }"
                                        >
                                            <option value="">Intensität wählen</option>
                                            <option value="low">Niedrig</option>
                                            <option value="medium">Mittel</option>
                                            <option value="high">Hoch</option>
                                            <option value="maximum">Maximum</option>
                                        </select>
                                        <p v-if="errors.intensity_level" class="mt-1 text-sm text-red-600">{{ errors.intensity_level }}</p>
                                    </div>
                                </div>

                                <div class="mt-6">
                                    <label for="description" class="block text-sm font-medium text-gray-700">
                                        Beschreibung
                                    </label>
                                    <textarea
                                        id="description"
                                        v-model="form.description"
                                        rows="3"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        :class="{ 'border-red-500': errors.description }"
                                    ></textarea>
                                    <p v-if="errors.description" class="mt-1 text-sm text-red-600">{{ errors.description }}</p>
                                </div>
                            </div>

                            <!-- Location Information -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Ort und Umgebung</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="venue" class="block text-sm font-medium text-gray-700">
                                            Veranstaltungsort *
                                        </label>
                                        <input
                                            id="venue"
                                            v-model="form.venue"
                                            type="text"
                                            required
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                            :class="{ 'border-red-500': errors.venue }"
                                        />
                                        <p v-if="errors.venue" class="mt-1 text-sm text-red-600">{{ errors.venue }}</p>
                                    </div>

                                    <div>
                                        <label for="court_type" class="block text-sm font-medium text-gray-700">
                                            Platztyp
                                        </label>
                                        <select
                                            id="court_type"
                                            v-model="form.court_type"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        >
                                            <option value="">Auswählen</option>
                                            <option value="indoor">Halle</option>
                                            <option value="outdoor">Außenplatz</option>
                                            <option value="gym">Turnhalle</option>
                                        </select>
                                    </div>

                                    <div class="md:col-span-2">
                                        <label for="venue_address" class="block text-sm font-medium text-gray-700">
                                            Adresse
                                        </label>
                                        <input
                                            id="venue_address"
                                            v-model="form.venue_address"
                                            type="text"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Training Details -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Trainingsdetails</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="trainer_id" class="block text-sm font-medium text-gray-700">
                                            Haupttrainer *
                                        </label>
                                        <select
                                            id="trainer_id"
                                            v-model="form.trainer_id"
                                            required
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                            :class="{ 'border-red-500': errors.trainer_id }"
                                        >
                                            <option value="">Trainer wählen</option>
                                            <option value="current_user">Ich selbst</option>
                                        </select>
                                        <p v-if="errors.trainer_id" class="mt-1 text-sm text-red-600">{{ errors.trainer_id }}</p>
                                    </div>

                                    <div>
                                        <label for="max_participants" class="block text-sm font-medium text-gray-700">
                                            Maximale Teilnehmer
                                        </label>
                                        <input
                                            id="max_participants"
                                            v-model.number="form.max_participants"
                                            type="number"
                                            min="1"
                                            max="50"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                    </div>
                                </div>

                                <div class="mt-6">
                                    <label for="focus_areas" class="block text-sm font-medium text-gray-700">
                                        Schwerpunkte (mit Komma getrennt)
                                    </label>
                                    <input
                                        id="focus_areas"
                                        v-model="focusAreasText"
                                        type="text"
                                        placeholder="z.B. Ballhandling, Wurf, Verteidigung"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    />
                                </div>

                                <div class="mt-6">
                                    <label for="required_equipment" class="block text-sm font-medium text-gray-700">
                                        Benötigte Ausrüstung (mit Komma getrennt)
                                    </label>
                                    <input
                                        id="required_equipment"
                                        v-model="equipmentText"
                                        type="text"
                                        placeholder="z.B. Basketbälle, Hütchen, Leibchen"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    />
                                </div>
                            </div>

                            <!-- Drill Management -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <DrillSelector
                                    :drills="drills"
                                    :initial-selected-drills="existingDrills"
                                    @update:selected-drills="handleDrillsUpdate"
                                />
                            </div>

                            <!-- Options -->
                            <div class="space-y-4">
                                <h3 class="text-lg font-medium text-gray-900">Einstellungen</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <label class="flex items-center">
                                        <input
                                            v-model="form.is_mandatory"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-offset-0 focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                        <span class="ml-2 text-sm text-gray-700">Pflichttermin</span>
                                    </label>

                                    <label class="flex items-center">
                                        <input
                                            v-model="form.allows_late_arrival"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-offset-0 focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                        <span class="ml-2 text-sm text-gray-700">Verspätetes Erscheinen erlaubt</span>
                                    </label>

                                    <label class="flex items-center">
                                        <input
                                            v-model="form.requires_medical_clearance"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-offset-0 focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                        <span class="ml-2 text-sm text-gray-700">Ärztliche Freigabe erforderlich</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex justify-between pt-6 border-t border-gray-200">
                                <SecondaryButton @click="goBack">
                                    Abbrechen
                                </SecondaryButton>

                                <PrimaryButton
                                    type="submit"
                                    :disabled="processing"
                                    :class="{ 'opacity-25': processing }"
                                >
                                    <span v-if="processing">Wird aktualisiert...</span>
                                    <span v-else>Änderungen speichern</span>
                                </PrimaryButton>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DrillSelector from '@/Components/Training/DrillSelector.vue'

const props = defineProps({
    session: Object,
    teams: Array,
    drills: Array,
})

const processing = ref(false)
const errors = ref({})
const focusAreasText = ref('')
const equipmentText = ref('')
const selectedDrills = ref([])
const existingDrills = ref([])

// Form with existing session data
const form = reactive({
    title: '',
    description: '',
    team_id: '',
    trainer_id: '',
    assistant_trainer_id: '',
    scheduled_at: '',
    planned_duration: 90,
    venue: '',
    venue_address: '',
    court_type: '',
    session_type: '',
    focus_areas: [],
    intensity_level: 'medium',
    max_participants: '',
    weather_conditions: '',
    temperature: '',
    weather_appropriate: true,
    required_equipment: [],
    special_requirements: '',
    safety_notes: '',
    is_mandatory: false,
    allows_late_arrival: true,
    requires_medical_clearance: false,
    notification_settings: {
        send_reminders: true,
        reminder_times: [24, 2]
    },
    drills: []
})

// Load existing session data
onMounted(() => {
    if (props.session) {
        // Fill form with existing data
        form.title = props.session.title || ''
        form.description = props.session.description || ''
        form.team_id = props.session.team_id
        form.trainer_id = props.session.trainer_id
        form.assistant_trainer_id = props.session.assistant_trainer_id || ''
        form.planned_duration = props.session.planned_duration || 90
        form.venue = props.session.venue || ''
        form.venue_address = props.session.venue_address || ''
        form.court_type = props.session.court_type || ''
        form.session_type = props.session.session_type || ''
        form.intensity_level = props.session.intensity_level || 'medium'
        form.max_participants = props.session.max_participants || ''
        form.weather_conditions = props.session.weather_conditions || ''
        form.temperature = props.session.temperature || ''
        form.weather_appropriate = props.session.weather_appropriate ?? true
        form.special_requirements = props.session.special_requirements || ''
        form.safety_notes = props.session.safety_notes || ''
        form.is_mandatory = props.session.is_mandatory || false
        form.allows_late_arrival = props.session.allows_late_arrival ?? true
        form.requires_medical_clearance = props.session.requires_medical_clearance || false

        // Handle focus areas
        if (props.session.focus_areas) {
            form.focus_areas = Array.isArray(props.session.focus_areas) 
                ? props.session.focus_areas 
                : JSON.parse(props.session.focus_areas)
            focusAreasText.value = form.focus_areas.join(', ')
        }

        // Handle required equipment
        if (props.session.required_equipment) {
            form.required_equipment = Array.isArray(props.session.required_equipment)
                ? props.session.required_equipment
                : JSON.parse(props.session.required_equipment)
            equipmentText.value = form.required_equipment.join(', ')
        }

        // Handle notification settings
        if (props.session.notification_settings) {
            form.notification_settings = typeof props.session.notification_settings === 'string'
                ? JSON.parse(props.session.notification_settings)
                : props.session.notification_settings
        }

        // Format scheduled_at for datetime-local input
        if (props.session.scheduled_at) {
            const date = new Date(props.session.scheduled_at)
            form.scheduled_at = date.toISOString().slice(0, 16)
        }

        // Load existing drills
        if (props.session.drills && props.session.drills.length > 0) {
            existingDrills.value = props.session.drills.map(drill => ({
                ...drill,
                planned_duration: drill.pivot?.planned_duration || drill.estimated_duration,
                specific_instructions: drill.pivot?.specific_instructions || '',
                participants_count: drill.pivot?.participants_count || null,
                order_in_session: drill.pivot?.order_in_session || 1,
                isEditing: false
            }))
        }
    }
})

// Watch for changes in focus areas text and convert to array
watch(focusAreasText, (newValue) => {
    form.focus_areas = newValue ? newValue.split(',').map(item => item.trim()).filter(item => item) : []
})

// Watch for changes in equipment text and convert to array
watch(equipmentText, (newValue) => {
    form.required_equipment = newValue ? newValue.split(',').map(item => item.trim()).filter(item => item) : []
})

function handleDrillsUpdate(drills) {
    selectedDrills.value = drills
    form.drills = drills.map(drill => ({
        drill_id: drill.id,
        order_in_session: drill.order_in_session,
        planned_duration: drill.planned_duration || drill.estimated_duration,
        specific_instructions: drill.specific_instructions || '',
        participants_count: drill.participants_count || null,
        status: 'planned'
    }))
}

function submitForm() {
    processing.value = true
    errors.value = {}

    // Prepare the data - convert current_user to actual user ID
    const submitData = { ...form }
    if (submitData.trainer_id === 'current_user') {
        delete submitData.trainer_id // Let the backend set the current user
    }

    // Ensure CSRF token is fresh before submitting
    const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.content;
    if (!csrfToken) {
        console.error('CSRF token not found, refreshing page');
        window.location.reload();
        return;
    }

    router.put(`/training/sessions/${props.session.id}`, submitData, {
        preserveScroll: true,
        onSuccess: () => {
            // Form submitted successfully, redirect will be handled by controller
        },
        onError: (errorResponse) => {
            console.error('Form submission error:', errorResponse);
            errors.value = errorResponse;
            
            // If it's a CSRF error (419), try to refresh the token
            if (errorResponse.status === 419 || (typeof errorResponse === 'object' && errorResponse['419'])) {
                console.warn('CSRF token error detected, attempting to refresh');
                if (window.updateCsrfToken) {
                    window.updateCsrfToken();
                }
            }
        },
        onFinish: () => {
            processing.value = false
        }
    })
}

function goBack() {
    router.get('/training/sessions')
}
</script>