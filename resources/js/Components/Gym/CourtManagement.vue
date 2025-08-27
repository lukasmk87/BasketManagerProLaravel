<template>
    <div class="bg-white rounded-lg border border-gray-200">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Felder verwalten</h3>
                    <p class="text-sm text-gray-600 mt-1">
                        Benennen und verwalten Sie die Felder dieser Halle
                    </p>
                </div>
                <button
                    @click="refreshCourts"
                    :disabled="loading"
                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-md disabled:opacity-50 flex items-center"
                >
                    <ArrowPathIcon class="w-4 h-4 mr-2" />
                    {{ loading ? 'Laden...' : 'Aktualisieren' }}
                </button>
            </div>

            <div v-if="loading && !courts.length" class="flex items-center justify-center py-12">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <span class="ml-3 text-gray-600">Felder werden geladen...</span>
            </div>

            <div v-else-if="courts.length === 0" class="text-center py-12">
                <BuildingStorefrontIcon class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-medium text-gray-900">
                    Keine Felder vorhanden
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    Diese Halle hat noch keine definierten Felder.
                </p>
                <div class="mt-6">
                    <button
                        @click="openCreateCourtModal"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md"
                    >
                        <PlusIcon class="h-5 w-5 mr-2" />
                        Erstes Feld erstellen
                    </button>
                </div>
            </div>

            <div v-else class="space-y-4">
                <!-- Add Court Button -->
                <div class="flex justify-end">
                    <button
                        @click="openCreateCourtModal"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md"
                    >
                        <PlusIcon class="h-5 w-5 mr-2" />
                        Neues Feld hinzufügen
                    </button>
                </div>

                <!-- Courts List -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div 
                        v-for="court in courts" 
                        :key="court.id"
                        class="flex items-center justify-between p-4 bg-gray-50 border border-gray-200 rounded-lg hover:border-gray-300 transition-colors"
                    >
                        <div class="flex items-center space-x-3">
                            <div 
                                class="w-4 h-4 rounded-full"
                                :style="{ backgroundColor: court.color_code }"
                            ></div>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <p class="text-sm font-medium text-gray-900">{{ court.name }}</p>
                                    <!-- Main Court Icon -->
                                    <svg v-if="court.is_main_court" class="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <p class="text-xs text-gray-500">Feld Nr. {{ court.court_number }}</p>
                                    <span v-if="court.is_main_court" class="text-xs text-yellow-600 font-medium">Hauptplatz</span>
                                </div>
                            </div>
                            <span :class="[
                                'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                                court.is_active 
                                    ? 'bg-green-100 text-green-800' 
                                    : 'bg-red-100 text-red-800'
                            ]">
                                {{ court.is_active ? 'Aktiv' : 'Inaktiv' }}
                            </span>
                        </div>
                        
                        <div class="flex items-center space-x-2">
                            <!-- Main Court Toggle -->
                            <button
                                @click="toggleMainCourt(court)"
                                :class="[
                                    'p-2 rounded-md transition-colors',
                                    court.is_main_court
                                        ? 'text-yellow-600 hover:text-yellow-800 hover:bg-yellow-50'
                                        : 'text-gray-400 hover:text-yellow-600 hover:bg-yellow-50'
                                ]"
                                :title="court.is_main_court ? 'Als Hauptplatz entfernen' : 'Als Hauptplatz setzen'"
                            >
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            </button>
                            
                            <button
                                @click="editCourt(court)"
                                class="p-2 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-md transition-colors"
                                title="Feld bearbeiten"
                            >
                                <PencilIcon class="w-4 h-4" />
                            </button>
                            <button
                                @click="toggleCourtStatus(court)"
                                :class="[
                                    'p-2 rounded-md transition-colors',
                                    court.is_active 
                                        ? 'text-red-600 hover:text-red-800 hover:bg-red-50' 
                                        : 'text-green-600 hover:text-green-800 hover:bg-green-50'
                                ]"
                                :title="court.is_active ? 'Feld deaktivieren' : 'Feld aktivieren'"
                            >
                                <component :is="court.is_active ? EyeSlashIcon : EyeIcon" class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Messages -->
        <div v-if="statusMessage" class="mx-6 mb-6">
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

    <!-- Court Edit/Create Modal -->
    <div v-if="showCourtModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg max-w-md w-full mx-4">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">
                    {{ editingCourt ? 'Feld bearbeiten' : 'Neues Feld erstellen' }}
                </h3>
            </div>
            <form @submit.prevent="saveCourt" class="p-6">
                <div class="space-y-4">
                    <div>
                        <label for="court-name" class="block text-sm font-medium text-gray-700 mb-1">
                            Feldname
                        </label>
                        <input
                            id="court-name"
                            v-model="courtForm.name"
                            type="text"
                            required
                            class="w-full rounded border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="z.B. Hauptfeld, Feld A, ..."
                        />
                    </div>
                    
                    <div v-if="!editingCourt">
                        <label for="court-number" class="block text-sm font-medium text-gray-700 mb-1">
                            Feldnummer
                        </label>
                        <input
                            id="court-number"
                            v-model.number="courtForm.court_number"
                            type="number"
                            min="1"
                            required
                            class="w-full rounded border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="1"
                        />
                        <p class="text-xs text-gray-500 mt-1">Eindeutige Nummer für dieses Feld</p>
                    </div>
                    
                    <div v-if="editingCourt" class="space-y-3">
                        <div class="flex items-center">
                            <input
                                id="court-active"
                                v-model="courtForm.is_active"
                                type="checkbox"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            />
                            <label for="court-active" class="ml-2 text-sm text-gray-900">
                                Feld ist aktiv
                            </label>
                        </div>
                        
                        <div class="flex items-center">
                            <input
                                id="court-main"
                                v-model="courtForm.is_main_court"
                                type="checkbox"
                                class="rounded border-gray-300 text-yellow-600 focus:ring-yellow-500"
                            />
                            <label for="court-main" class="ml-2 text-sm text-gray-900">
                                Als Hauptplatz markieren
                            </label>
                        </div>
                        <p class="text-xs text-gray-500 mt-1 ml-6">
                            Wenn der Hauptplatz belegt ist, sind keine Parallel-Buchungen möglich
                        </p>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button
                        type="button"
                        @click="closeCourtModal"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md"
                    >
                        Abbrechen
                    </button>
                    <button
                        type="submit"
                        :disabled="!courtForm.name || saving"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md disabled:opacity-50"
                    >
                        {{ saving ? 'Speichere...' : (editingCourt ? 'Aktualisieren' : 'Erstellen') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import {
    ArrowPathIcon,
    BuildingStorefrontIcon,
    PlusIcon,
    PencilIcon,
    EyeIcon,
    EyeSlashIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    gymHallId: {
        type: Number,
        required: true
    },
    gymHall: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['updated'])

// State
const courts = ref([])
const loading = ref(false)
const saving = ref(false)
const statusMessage = ref(null)
const showCourtModal = ref(false)
const editingCourt = ref(null)

const courtForm = ref({
    name: '',
    court_number: null,
    is_active: true,
    is_main_court: false
})

// Methods
const refreshCourts = async () => {
    try {
        loading.value = true
        
        const response = await axios.get(`/gym-management/halls/${props.gymHallId}/courts`)
        
        if (response.data.success) {
            courts.value = response.data.data
        }
    } catch (error) {
        console.error('Fehler beim Laden der Felder:', error)
        showError('Fehler beim Laden der Felder')
    } finally {
        loading.value = false
    }
}

const openCreateCourtModal = () => {
    editingCourt.value = null
    courtForm.value = {
        name: '',
        court_number: courts.value.length + 1,
        is_active: true,
        is_main_court: false
    }
    showCourtModal.value = true
}

const editCourt = (court) => {
    editingCourt.value = court
    courtForm.value = {
        name: court.name,
        court_number: court.court_number,
        is_active: court.is_active,
        is_main_court: court.is_main_court
    }
    showCourtModal.value = true
}

const closeCourtModal = () => {
    showCourtModal.value = false
    editingCourt.value = null
    courtForm.value = {
        name: '',
        court_number: null,
        is_active: true,
        is_main_court: false
    }
}

const saveCourt = async () => {
    try {
        saving.value = true
        
        let response
        
        if (editingCourt.value) {
            // Update existing court
            response = await axios.put(`/gym-management/courts/${editingCourt.value.id}`, {
                name: courtForm.value.name,
                is_active: courtForm.value.is_active,
                is_main_court: courtForm.value.is_main_court
            })
        } else {
            // Create new court
            response = await axios.post(`/gym-management/halls/${props.gymHallId}/courts`, {
                name: courtForm.value.name,
                court_number: courtForm.value.court_number
            })
        }
        
        if (response.data.success) {
            showSuccess(response.data.message)
            closeCourtModal()
            await refreshCourts()
            emit('updated')
        }
    } catch (error) {
        console.error('Fehler beim Speichern:', error)
        
        let errorMessage = 'Fehler beim Speichern des Felds'
        if (error.response?.data?.message) {
            errorMessage = error.response.data.message
        } else if (error.response?.data?.errors) {
            const errors = error.response.data.errors
            errorMessage = Object.values(errors).flat().join(', ')
        }
        
        showError(errorMessage)
    } finally {
        saving.value = false
    }
}

const toggleCourtStatus = async (court) => {
    try {
        const response = await axios.put(`/gym-management/courts/${court.id}`, {
            name: court.name,
            is_active: !court.is_active
        })
        
        if (response.data.success) {
            showSuccess(`Feld ${court.is_active ? 'deaktiviert' : 'aktiviert'}`)
            await refreshCourts()
            emit('updated')
        }
    } catch (error) {
        console.error('Fehler beim Ändern des Status:', error)
        showError('Fehler beim Ändern des Feld-Status')
    }
}

const toggleMainCourt = async (court) => {
    try {
        const response = await axios.put(`/gym-management/courts/${court.id}`, {
            name: court.name,
            is_active: court.is_active,
            is_main_court: !court.is_main_court
        })
        
        if (response.data.success) {
            const action = court.is_main_court ? 'entfernt' : 'gesetzt'
            showSuccess(`Hauptplatz-Status ${action}`)
            await refreshCourts()
            emit('updated')
        }
    } catch (error) {
        console.error('Fehler beim Ändern des Hauptplatz-Status:', error)
        showError('Fehler beim Ändern des Hauptplatz-Status')
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

// Lifecycle
onMounted(() => {
    refreshCourts()
})
</script>