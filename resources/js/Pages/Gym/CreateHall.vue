<template>
    <AppLayout title="Neue Sporthalle">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Neue Sporthalle erstellen
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center">
                            <svg class="h-6 w-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H3m2 0h4M9 7h6m-6 4h6m-6 4h6" />
                            </svg>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">
                                    Neue Sporthalle
                                </h3>
                                <p class="mt-1 text-sm text-gray-600">
                                    Geben Sie die grundlegenden Informationen für die neue Sporthalle ein.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Navigation -->
                    <div class="border-b border-gray-200 px-6">
                        <nav class="-mb-px flex space-x-8">
                            <button
                                @click="activeTab = 'details'"
                                type="button"
                                :class="[
                                    activeTab === 'details'
                                        ? 'border-blue-500 text-blue-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
                                ]"
                            >
                                Grunddaten
                            </button>
                            <button
                                @click="activeTab = 'schedule'"
                                type="button"
                                :disabled="!createdHallId"
                                :class="[
                                    activeTab === 'schedule'
                                        ? 'border-blue-500 text-blue-600'
                                        : createdHallId ? 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' : 'border-transparent text-gray-400 cursor-not-allowed',
                                    'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
                                ]"
                            >
                                Öffnungszeiten
                                <span v-if="!createdHallId" class="ml-1 text-xs">(erst nach Erstellung)</span>
                            </button>
                        </nav>
                    </div>

                    <div class="p-6">
                        <!-- Details Tab -->
                        <div v-if="activeTab === 'details'">
                            <form @submit.prevent="submitForm">
                                <div class="space-y-6">
                                    <!-- Basic Information -->
                                    <div>
                                        <h4 class="font-medium text-gray-900 mb-4">Grundinformationen</h4>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                                                <input
                                                    v-model="form.name"
                                                    type="text"
                                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                                    placeholder="z.B. Sporthalle Nord"
                                                    required
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Kapazität</label>
                                                <input
                                                    v-model.number="form.capacity"
                                                    type="number"
                                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                                    placeholder="z.B. 200"
                                                    min="1"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Address -->
                                    <div>
                                        <h4 class="font-medium text-gray-900 mb-4">Adresse</h4>
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Straße & Hausnummer</label>
                                                <input
                                                    v-model="form.address_street"
                                                    type="text"
                                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                                    placeholder="z.B. Sportstraße 123"
                                                />
                                            </div>
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">PLZ</label>
                                                    <input
                                                        v-model="form.address_zip"
                                                        type="text"
                                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                                        placeholder="12345"
                                                    />
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">Stadt</label>
                                                    <input
                                                        v-model="form.address_city"
                                                        type="text"
                                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                                        placeholder="z.B. München"
                                                    />
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">Land</label>
                                                    <input
                                                        v-model="form.address_country"
                                                        type="text"
                                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                                        placeholder="Deutschland"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </div>


                                    <!-- Facilities & Equipment -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <h4 class="font-medium text-gray-900 mb-4">Ausstattung</h4>
                                            <div class="space-y-2">
                                                <label class="flex items-center">
                                                    <input
                                                        v-model="facilitiesCheckboxes.changing_rooms"
                                                        type="checkbox"
                                                        class="rounded border-gray-300 text-blue-600"
                                                    />
                                                    <span class="ml-2 text-sm text-gray-700">Umkleidekabinen</span>
                                                </label>
                                                <label class="flex items-center">
                                                    <input
                                                        v-model="facilitiesCheckboxes.showers"
                                                        type="checkbox"
                                                        class="rounded border-gray-300 text-blue-600"
                                                    />
                                                    <span class="ml-2 text-sm text-gray-700">Duschen</span>
                                                </label>
                                                <label class="flex items-center">
                                                    <input
                                                        v-model="facilitiesCheckboxes.parking"
                                                        type="checkbox"
                                                        class="rounded border-gray-300 text-blue-600"
                                                    />
                                                    <span class="ml-2 text-sm text-gray-700">Parkplatz</span>
                                                </label>
                                                <label class="flex items-center">
                                                    <input
                                                        v-model="facilitiesCheckboxes.wheelchair_accessible"
                                                        type="checkbox"
                                                        class="rounded border-gray-300 text-blue-600"
                                                    />
                                                    <span class="ml-2 text-sm text-gray-700">Rollstuhlgerecht</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div>
                                            <h4 class="font-medium text-gray-900 mb-4">Sportgeräte</h4>
                                            <div class="space-y-2">
                                                <label class="flex items-center">
                                                    <input
                                                        v-model="equipmentCheckboxes.basketballs"
                                                        type="checkbox"
                                                        class="rounded border-gray-300 text-blue-600"
                                                    />
                                                    <span class="ml-2 text-sm text-gray-700">Basketbälle</span>
                                                </label>
                                                <label class="flex items-center">
                                                    <input
                                                        v-model="equipmentCheckboxes.scoreboard"
                                                        type="checkbox"
                                                        class="rounded border-gray-300 text-blue-600"
                                                    />
                                                    <span class="ml-2 text-sm text-gray-700">Anzeigetafel</span>
                                                </label>
                                                <label class="flex items-center">
                                                    <input
                                                        v-model="equipmentCheckboxes.shot_clock"
                                                        type="checkbox"
                                                        class="rounded border-gray-300 text-blue-600"
                                                    />
                                                    <span class="ml-2 text-sm text-gray-700">Wurfuhr</span>
                                                </label>
                                                <label class="flex items-center">
                                                    <input
                                                        v-model="equipmentCheckboxes.sound_system"
                                                        type="checkbox"
                                                        class="rounded border-gray-300 text-blue-600"
                                                    />
                                                    <span class="ml-2 text-sm text-gray-700">Soundanlage</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Contact Information -->
                                    <div>
                                        <h4 class="font-medium text-gray-900 mb-4">Kontaktinformationen</h4>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Kontaktperson</label>
                                                <input
                                                    v-model="form.contact_name"
                                                    type="text"
                                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                                    placeholder="Max Mustermann"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Telefon</label>
                                                <input
                                                    v-model="form.contact_phone"
                                                    type="tel"
                                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                                    placeholder="+49 123 456789"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">E-Mail</label>
                                                <input
                                                    v-model="form.contact_email"
                                                    type="email"
                                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                                    placeholder="kontakt@sporthalle.de"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Court Configuration -->
                                    <div>
                                        <h4 class="font-medium text-gray-900 mb-4">Platz-Konfiguration</h4>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Hallentyp</label>
                                                <select
                                                    v-model="form.hall_type"
                                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                                >
                                                    <option value="single">Einfachhalle (1 Platz)</option>
                                                    <option value="double">Doppelhalle (2 Plätze)</option>
                                                    <option value="triple">Dreifachhalle (3 Plätze)</option>
                                                    <option value="multi">Mehrfachhalle (4+ Plätze)</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Anzahl Plätze</label>
                                                <input
                                                    v-model.number="form.court_count"
                                                    type="number"
                                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                                    placeholder="1"
                                                    min="1"
                                                    max="10"
                                                />
                                            </div>
                                            <div class="flex items-center pt-6">
                                                <label class="flex items-center">
                                                    <input
                                                        v-model="form.supports_parallel_bookings"
                                                        type="checkbox"
                                                        class="rounded border-gray-300 text-blue-600"
                                                    />
                                                    <span class="ml-2 text-sm text-gray-700">Parallel-Buchungen</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Booking Settings -->
                                    <div>
                                        <h4 class="font-medium text-gray-900 mb-4">Buchungseinstellungen</h4>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Mindestbuchungsdauer (Min.)</label>
                                                <select
                                                    v-model.number="form.min_booking_duration_minutes"
                                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                                >
                                                    <option :value="15">15 Minuten</option>
                                                    <option :value="30">30 Minuten</option>
                                                    <option :value="60">60 Minuten</option>
                                                    <option :value="90">90 Minuten</option>
                                                    <option :value="120">120 Minuten</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Buchungsintervall (Min.)</label>
                                                <select
                                                    v-model.number="form.booking_increment_minutes"
                                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                                >
                                                    <option :value="15">15 Minuten</option>
                                                    <option :value="30">30 Minuten</option>
                                                    <option :value="60">60 Minuten</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Stundensatz (€)</label>
                                                <input
                                                    v-model.number="form.hourly_rate"
                                                    type="number"
                                                    step="0.01"
                                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                                    placeholder="25.00"
                                                    min="0"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Additional Settings -->
                                    <div>
                                        <h4 class="font-medium text-gray-900 mb-4">Weitere Einstellungen</h4>
                                        <div class="flex items-center space-x-4">
                                            <label class="flex items-center">
                                                <input
                                                    v-model="form.is_active"
                                                    type="checkbox"
                                                    class="rounded border-gray-300 text-blue-600"
                                                />
                                                <span class="ml-2 text-sm text-gray-700">Aktiv</span>
                                            </label>
                                            <label class="flex items-center">
                                                <input
                                                    v-model="form.requires_key"
                                                    type="checkbox"
                                                    class="rounded border-gray-300 text-blue-600"
                                                />
                                                <span class="ml-2 text-sm text-gray-700">Schlüssel erforderlich</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Description & Special Rules -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Beschreibung</label>
                                            <textarea
                                                v-model="form.description"
                                                rows="4"
                                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                                placeholder="Zusätzliche Informationen zur Sporthalle..."
                                            ></textarea>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Besondere Regeln</label>
                                            <textarea
                                                v-model="form.special_rules"
                                                rows="4"
                                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                                placeholder="z.B. Keine Straßenschuhe, Anmeldung erforderlich..."
                                            ></textarea>
                                        </div>
                                    </div>

                                    <!-- Error Messages -->
                                    <div v-if="errors.length > 0" class="bg-red-50 border border-red-200 rounded-md p-4">
                                        <div class="flex">
                                            <svg class="h-5 w-5 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                            </svg>
                                            <div>
                                                <h3 class="text-sm font-medium text-red-800">Fehler beim Erstellen:</h3>
                                                <ul class="mt-1 text-sm text-red-700 list-disc list-inside">
                                                    <li v-for="error in errors" :key="error">{{ error }}</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Schedule Tab -->
                        <div v-if="activeTab === 'schedule' && createdHallId">
                            <div class="mb-4">
                                <div class="bg-green-50 border border-green-200 rounded-md p-4">
                                    <div class="flex">
                                        <svg class="h-5 w-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                        <div>
                                            <h3 class="text-sm font-medium text-green-800">Sporthalle erfolgreich erstellt!</h3>
                                            <p class="mt-1 text-sm text-green-700">
                                                Konfigurieren Sie jetzt die Öffnungszeiten für Ihre neue Sporthalle.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <GymTimeSlotManager
                                :gym-hall-id="createdHallId"
                                :initial-time-slots="[]"
                                :default-open-time="form.opening_time"
                                :default-close-time="form.closing_time"
                                @updated="onTimeSlotsUpdated"
                                @error="onTimeSlotsError"
                            />
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                        <div class="flex justify-between">
                            <div></div>
                            <div class="flex space-x-3">
                                <Link
                                    :href="route('gym.halls')"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md"
                                >
                                    {{ createdHallId ? 'Zur Übersicht' : 'Abbrechen' }}
                                </Link>
                                <button
                                    v-if="activeTab === 'details'"
                                    @click="submitForm"
                                    type="button"
                                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md disabled:opacity-50 disabled:cursor-not-allowed"
                                    :disabled="isFormBusy"
                                >
                                    <span v-if="initializingSession">Initialisiere Sitzung...</span>
                                    <span v-else-if="submitting">Erstelle...</span>
                                    <span v-else>Sporthalle erstellen</span>
                                </button>
                                <button
                                    v-if="activeTab === 'schedule' && createdHallId"
                                    @click="finishCreation"
                                    type="button"
                                    class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-md"
                                >
                                    Fertigstellen
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import GymTimeSlotManager from '@/Components/Gym/GymTimeSlotManager.vue'

const props = defineProps({
    currentClub: Object
})

const page = usePage()

// State
const activeTab = ref('details')
const submitting = ref(false)
const initializingSession = ref(false)
const errors = ref([])
const createdHallId = ref(null)
const createdHall = ref(null)

// Form data
const form = ref({
    club_id: props.currentClub?.id || null,
    name: '',
    description: '',
    address_street: '',
    address_city: '',
    address_zip: '',
    address_country: 'Deutschland',
    capacity: null,
    hall_type: 'single',
    court_count: 1,
    supports_parallel_bookings: false,
    min_booking_duration_minutes: 30,
    booking_increment_minutes: 30,
    opening_time: '08:00',
    closing_time: '22:00',
    hourly_rate: null,
    contact_name: '',
    contact_phone: '',
    contact_email: '',
    is_active: true,
    requires_key: false,
    special_rules: ''
})

// Checkbox states for facilities and equipment
const facilitiesCheckboxes = ref({
    changing_rooms: false,
    showers: false,
    parking: false,
    wheelchair_accessible: false
})

const equipmentCheckboxes = ref({
    basketballs: false,
    scoreboard: false,
    shot_clock: false,
    sound_system: false
})

// Computed
const facilitiesArray = computed(() => {
    return Object.keys(facilitiesCheckboxes.value).filter(key => facilitiesCheckboxes.value[key])
})

const equipmentArray = computed(() => {
    return Object.keys(equipmentCheckboxes.value).filter(key => equipmentCheckboxes.value[key])
})

const isFormBusy = computed(() => {
    return submitting.value || initializingSession.value
})

// Methods
const submitForm = async () => {
    if (!validateForm()) return
    
    submitting.value = true
    errors.value = []
    
    try {
        // First, ensure CSRF cookie is set
        initializingSession.value = true
        await window.axios.get('/sanctum/csrf-cookie')
        initializingSession.value = false
        
        // Get club_id from multiple possible sources
        let clubId = null
        
        if (props.currentClub?.id) {
            clubId = props.currentClub.id
        } else if (page.props.auth?.user?.current_team?.club_id) {
            clubId = page.props.auth.user.current_team.club_id
        } else if (page.props.user?.current_team?.club_id) {
            clubId = page.props.user.current_team.club_id
        }
        
        if (!clubId) {
            errors.value = ['Vereins-ID konnte nicht ermittelt werden. Bitte wenden Sie sich an den Administrator.']
            return
        }

        const data = {
            ...form.value,
            club_id: clubId,
            facilities: facilitiesArray.value,
            equipment: equipmentArray.value
        }
        
        const response = await window.axios.post('/api/v2/gym-halls', data, {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            withCredentials: true
        })
        
        if (response.status >= 200 && response.status < 300) {
            createdHallId.value = response.data.data?.id || response.data.id
            createdHall.value = response.data.data || response.data
            
            // Switch to schedule tab after creation
            activeTab.value = 'schedule'
            
            // Show success message
            showSuccess('Sporthalle erfolgreich erstellt! Sie können jetzt die Öffnungszeiten konfigurieren.')
        }
    } catch (error) {
        console.error('Error creating gym hall:', error)
        
        // Handle 419 CSRF token mismatch errors specifically
        if (error.response?.status === 419) {
            handleSessionTimeout()
            return
        } else if (error.response?.data?.errors) {
            errors.value = Object.values(error.response.data.errors).flat()
        } else if (error.response?.data?.message) {
            errors.value = [error.response.data.message]
        } else {
            errors.value = ['Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.']
        }
    } finally {
        submitting.value = false
        initializingSession.value = false
    }
}

const validateForm = () => {
    errors.value = []
    
    if (!form.value.name?.trim()) {
        errors.value.push('Name ist erforderlich')
    }
    
    if (form.value.closing_time && form.value.opening_time && form.value.closing_time <= form.value.opening_time) {
        errors.value.push('Schließzeit muss nach der Öffnungszeit liegen')
    }
    
    if (form.value.contact_email && !isValidEmail(form.value.contact_email)) {
        errors.value.push('Ungültige E-Mail-Adresse')
    }
    
    if (form.value.court_count < 1 || form.value.court_count > 10) {
        errors.value.push('Anzahl Plätze muss zwischen 1 und 10 liegen')
    }
    
    if (form.value.min_booking_duration_minutes < 15 || form.value.min_booking_duration_minutes > 480) {
        errors.value.push('Mindestbuchungsdauer muss zwischen 15 und 480 Minuten liegen')
    }
    
    if (![15, 30, 60].includes(form.value.booking_increment_minutes)) {
        errors.value.push('Buchungsintervall muss 15, 30 oder 60 Minuten sein')
    }
    
    return errors.value.length === 0
}

const isValidEmail = (email) => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    return emailRegex.test(email)
}

const onTimeSlotsUpdated = () => {
    showSuccess('Öffnungszeiten erfolgreich gespeichert!')
}

const onTimeSlotsError = (error) => {
    console.error('Time slots error:', error)
}

const finishCreation = () => {
    router.visit(route('gym.halls'))
}

const showSuccess = (message) => {
    // You could implement a toast notification here
    console.log(message)
}

const handleSessionTimeout = () => {
    // Clear any stored session data
    errors.value = ['Ihre Sitzung ist abgelaufen. Die Seite wird neu geladen...']
    
    // Reload page after short delay to re-establish session
    setTimeout(() => {
        window.location.reload()
    }, 2000)
}
</script>