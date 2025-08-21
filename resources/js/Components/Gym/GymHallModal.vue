<template>
    <DialogModal :show="show" @close="$emit('close')" max-width="xl">
        <template #title>
            <div class="flex items-center">
                <svg class="h-6 w-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H3m2 0h4M9 7h6m-6 4h6m-6 4h6" />
                </svg>
                {{ gymHall ? 'Sporthalle bearbeiten' : 'Neue Sporthalle' }}
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

                    <!-- Opening Hours -->
                    <div>
                        <h4 class="font-medium text-gray-900 mb-4">Öffnungszeiten</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Öffnung</label>
                                <input
                                    v-model="form.opening_time"
                                    type="time"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Schließung</label>
                                <input
                                    v-model="form.closing_time"
                                    type="time"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                />
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

                    <!-- Additional Settings -->
                    <div>
                        <h4 class="font-medium text-gray-900 mb-4">Weitere Einstellungen</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                            <div class="flex items-center space-x-4 pt-6">
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
                        v-if="gymHall"
                        @click="deleteGymHall"
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
                        {{ submitting ? 'Speichere...' : (gymHall ? 'Aktualisieren' : 'Erstellen') }}
                    </button>
                </div>
            </div>
        </template>
    </DialogModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import DialogModal from '@/Components/DialogModal.vue'

const props = defineProps({
    show: Boolean,
    gymHall: Object
})

const emit = defineEmits(['close', 'updated'])

const page = usePage()

// Form data
const form = ref({
    club_id: null,
    name: '',
    description: '',
    address_street: '',
    address_city: '',
    address_zip: '',
    address_country: 'Deutschland',
    capacity: null,
    opening_time: '',
    closing_time: '',
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

const submitting = ref(false)
const errors = ref([])

// Computed
const facilitiesArray = computed(() => {
    return Object.keys(facilitiesCheckboxes.value).filter(key => facilitiesCheckboxes.value[key])
})

const equipmentArray = computed(() => {
    return Object.keys(equipmentCheckboxes.value).filter(key => equipmentCheckboxes.value[key])
})

// Methods
const submitForm = async () => {
    if (!validateForm()) return
    
    submitting.value = true
    errors.value = []
    
    try {
        // Validate gym hall ID for updates
        if (props.gymHall && (!props.gymHall.id || props.gymHall.id === undefined)) {
            errors.value = ['Sporthalle ID fehlt oder ist ungültig. Bitte versuchen Sie es erneut.']
            return
        }
        
        const url = props.gymHall 
            ? `/api/v2/gym-halls/${props.gymHall.id}`
            : '/api/v2/gym-halls'
        
        const method = props.gymHall ? 'PUT' : 'POST'
        
        const data = {
            ...form.value,
            club_id: page.props.user?.current_team?.club_id,
            facilities: facilitiesArray.value,
            equipment: equipmentArray.value
        }
        
        // Use axios instead of fetch for better CSRF token handling
        const response = await window.axios({
            method: method,
            url: url,
            data: data,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        
        if (response.status >= 200 && response.status < 300) {
            emit('updated')
            emit('close')
            resetForm()
        } else {
            errors.value = Object.values(response.data.errors || {}).flat()
        }
    } catch (error) {
        console.error('Error saving gym hall:', error)
        if (error.response?.data?.errors) {
            errors.value = Object.values(error.response.data.errors).flat()
        } else if (error.response?.data?.message) {
            errors.value = [error.response.data.message]
        } else {
            errors.value = ['Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.']
        }
    } finally {
        submitting.value = false
    }
}

const deleteGymHall = async () => {
    if (!props.gymHall || !props.gymHall.id) return
    
    const confirmed = confirm('Möchten Sie diese Sporthalle wirklich löschen? Alle zugehörigen Zeitfenster und Buchungen werden ebenfalls gelöscht.')
    if (!confirmed) return
    
    try {
        const response = await window.axios.delete(`/api/v2/gym-halls/${props.gymHall.id}`, {
            headers: {
                'Accept': 'application/json'
            }
        })
        
        if (response.status >= 200 && response.status < 300) {
            emit('updated')
            emit('close')
        } else {
            errors.value = [response.data.message || 'Fehler beim Löschen']
        }
    } catch (error) {
        console.error('Error deleting gym hall:', error)
        if (error.response?.data?.message) {
            errors.value = [error.response.data.message]
        } else {
            errors.value = ['Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.']
        }
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
    
    return errors.value.length === 0
}

const isValidEmail = (email) => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    return emailRegex.test(email)
}

const resetForm = () => {
    form.value = {
        club_id: null,
        name: '',
        description: '',
        address_street: '',
        address_city: '',
        address_zip: '',
        address_country: 'Deutschland',
        capacity: null,
        opening_time: '',
        closing_time: '',
        hourly_rate: null,
        contact_name: '',
        contact_phone: '',
        contact_email: '',
        is_active: true,
        requires_key: false,
        special_rules: ''
    }
    
    facilitiesCheckboxes.value = {
        changing_rooms: false,
        showers: false,
        parking: false,
        wheelchair_accessible: false
    }
    
    equipmentCheckboxes.value = {
        basketballs: false,
        scoreboard: false,
        shot_clock: false,
        sound_system: false
    }
    
    errors.value = []
}

// Watch for gymHall changes to populate form
watch(() => props.gymHall, (newGymHall) => {
    if (newGymHall) {
        form.value = {
            club_id: newGymHall.club_id,
            name: newGymHall.name || '',
            description: newGymHall.description || '',
            address_street: newGymHall.address_street || '',
            address_city: newGymHall.address_city || '',
            address_zip: newGymHall.address_zip || '',
            address_country: newGymHall.address_country || 'Deutschland',
            capacity: newGymHall.capacity,
            opening_time: newGymHall.opening_time || '',
            closing_time: newGymHall.closing_time || '',
            hourly_rate: newGymHall.hourly_rate,
            contact_name: newGymHall.contact_name || '',
            contact_phone: newGymHall.contact_phone || '',
            contact_email: newGymHall.contact_email || '',
            is_active: newGymHall.is_active ?? true,
            requires_key: newGymHall.requires_key ?? false,
            special_rules: newGymHall.special_rules || ''
        }
        
        // Set facilities checkboxes
        const facilities = newGymHall.facilities || []
        facilitiesCheckboxes.value = {
            changing_rooms: facilities.includes('changing_rooms'),
            showers: facilities.includes('showers'),
            parking: facilities.includes('parking'),
            wheelchair_accessible: facilities.includes('wheelchair_accessible')
        }
        
        // Set equipment checkboxes
        const equipment = newGymHall.equipment || []
        equipmentCheckboxes.value = {
            basketballs: equipment.includes('basketballs'),
            scoreboard: equipment.includes('scoreboard'),
            shot_clock: equipment.includes('shot_clock'),
            sound_system: equipment.includes('sound_system')
        }
    } else {
        resetForm()
    }
}, { immediate: true })

// Watch for show prop to reset form when modal opens
watch(() => props.show, (show) => {
    if (show && !props.gymHall) {
        resetForm()
    }
})
</script>