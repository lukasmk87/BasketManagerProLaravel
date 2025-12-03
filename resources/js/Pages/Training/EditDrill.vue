<template>
    <AppLayout :title="`Drill bearbeiten: ${drill.name}`">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Drill bearbeiten: {{ drill.name }}
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
                                        <label for="name" class="block text-sm font-medium text-gray-700">
                                            Name der Übung *
                                        </label>
                                        <input
                                            id="name"
                                            v-model="form.name"
                                            type="text"
                                            required
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                            :class="{ 'border-red-500': errors.name }"
                                        />
                                        <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</p>
                                    </div>

                                    <div>
                                        <label for="category" class="block text-sm font-medium text-gray-700">
                                            Kategorie *
                                        </label>
                                        <select
                                            id="category"
                                            v-model="form.category"
                                            required
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                            :class="{ 'border-red-500': errors.category }"
                                        >
                                            <option value="">Kategorie wählen</option>
                                            <option value="ball_handling">Ballhandling</option>
                                            <option value="shooting">Wurf</option>
                                            <option value="passing">Passen</option>
                                            <option value="defense">Verteidigung</option>
                                            <option value="rebounding">Rebound</option>
                                            <option value="conditioning">Kondition</option>
                                            <option value="agility">Beweglichkeit</option>
                                            <option value="footwork">Beinarbeit</option>
                                            <option value="team_offense">Team-Offense</option>
                                            <option value="team_defense">Team-Defense</option>
                                            <option value="transition">Transition</option>
                                            <option value="set_plays">Spielzüge</option>
                                            <option value="scrimmage">Scrimmage</option>
                                            <option value="warm_up">Aufwärmen</option>
                                            <option value="cool_down">Abwärmen</option>
                                        </select>
                                        <p v-if="errors.category" class="mt-1 text-sm text-red-600">{{ errors.category }}</p>
                                    </div>

                                    <div>
                                        <label for="difficulty_level" class="block text-sm font-medium text-gray-700">
                                            Schwierigkeitslevel *
                                        </label>
                                        <select
                                            id="difficulty_level"
                                            v-model="form.difficulty_level"
                                            required
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                            :class="{ 'border-red-500': errors.difficulty_level }"
                                        >
                                            <option value="">Schwierigkeit wählen</option>
                                            <option value="beginner">Anfänger</option>
                                            <option value="intermediate">Fortgeschritten</option>
                                            <option value="advanced">Fortgeschritten</option>
                                            <option value="expert">Experte</option>
                                        </select>
                                        <p v-if="errors.difficulty_level" class="mt-1 text-sm text-red-600">{{ errors.difficulty_level }}</p>
                                    </div>

                                    <div>
                                        <label for="age_group" class="block text-sm font-medium text-gray-700">
                                            Altersgruppe *
                                        </label>
                                        <select
                                            id="age_group"
                                            v-model="form.age_group"
                                            required
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                            :class="{ 'border-red-500': errors.age_group }"
                                        >
                                            <option value="">Altersgruppe wählen</option>
                                            <option value="U8">U8</option>
                                            <option value="U10">U10</option>
                                            <option value="U12">U12</option>
                                            <option value="U14">U14</option>
                                            <option value="U16">U16</option>
                                            <option value="U18">U18</option>
                                            <option value="adult">Erwachsene</option>
                                            <option value="all">Alle Altersgruppen</option>
                                        </select>
                                        <p v-if="errors.age_group" class="mt-1 text-sm text-red-600">{{ errors.age_group }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Description and Instructions -->
                            <div class="space-y-6">
                                <div>
                                    <label for="description" class="block text-sm font-medium text-gray-700">
                                        Beschreibung *
                                    </label>
                                    <textarea
                                        id="description"
                                        v-model="form.description"
                                        required
                                        rows="3"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        :class="{ 'border-red-500': errors.description }"
                                        placeholder="Kurze Beschreibung der Übung..."
                                    ></textarea>
                                    <p v-if="errors.description" class="mt-1 text-sm text-red-600">{{ errors.description }}</p>
                                </div>

                                <div>
                                    <label for="objectives" class="block text-sm font-medium text-gray-700">
                                        Ziele der Übung *
                                    </label>
                                    <textarea
                                        id="objectives"
                                        v-model="form.objectives"
                                        required
                                        rows="2"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        :class="{ 'border-red-500': errors.objectives }"
                                        placeholder="Was soll mit dieser Übung erreicht werden?"
                                    ></textarea>
                                    <p v-if="errors.objectives" class="mt-1 text-sm text-red-600">{{ errors.objectives }}</p>
                                </div>

                                <div>
                                    <label for="instructions" class="block text-sm font-medium text-gray-700">
                                        Anweisungen *
                                    </label>
                                    <textarea
                                        id="instructions"
                                        v-model="form.instructions"
                                        required
                                        rows="5"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        :class="{ 'border-red-500': errors.instructions }"
                                        placeholder="Schritt-für-Schritt Anweisungen zur Durchführung der Übung..."
                                    ></textarea>
                                    <p v-if="errors.instructions" class="mt-1 text-sm text-red-600">{{ errors.instructions }}</p>
                                </div>
                            </div>

                            <!-- Training Parameters -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Trainingsparameter</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <label for="min_players" class="block text-sm font-medium text-gray-700">
                                            Mindestanzahl Spieler *
                                        </label>
                                        <input
                                            id="min_players"
                                            v-model.number="form.min_players"
                                            type="number"
                                            min="1"
                                            max="15"
                                            required
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                            :class="{ 'border-red-500': errors.min_players }"
                                        />
                                        <p v-if="errors.min_players" class="mt-1 text-sm text-red-600">{{ errors.min_players }}</p>
                                    </div>

                                    <div>
                                        <label for="max_players" class="block text-sm font-medium text-gray-700">
                                            Maximalanzahl Spieler
                                        </label>
                                        <input
                                            id="max_players"
                                            v-model.number="form.max_players"
                                            type="number"
                                            min="1"
                                            max="30"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                            :class="{ 'border-red-500': errors.max_players }"
                                        />
                                        <p v-if="errors.max_players" class="mt-1 text-sm text-red-600">{{ errors.max_players }}</p>
                                    </div>

                                    <div>
                                        <label for="estimated_duration" class="block text-sm font-medium text-gray-700">
                                            Geschätzte Dauer (Min.) *
                                        </label>
                                        <input
                                            id="estimated_duration"
                                            v-model.number="form.estimated_duration"
                                            type="number"
                                            min="1"
                                            max="120"
                                            required
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                            :class="{ 'border-red-500': errors.estimated_duration }"
                                        />
                                        <p v-if="errors.estimated_duration" class="mt-1 text-sm text-red-600">{{ errors.estimated_duration }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Settings -->
                            <div class="space-y-4">
                                <h3 class="text-lg font-medium text-gray-900">Zusätzliche Einstellungen</h3>
                                
                                <!-- Status Selection -->
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                                        Status
                                    </label>
                                    <select
                                        id="status"
                                        v-model="form.status"
                                        class="w-full md:w-1/3 border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        :class="{ 'border-red-500': errors.status }"
                                    >
                                        <option value="draft">Entwurf</option>
                                        <option value="pending_review">Zur Prüfung</option>
                                        <option value="approved">Genehmigt</option>
                                        <option value="rejected">Abgelehnt</option>
                                        <option value="archived">Archiviert</option>
                                    </select>
                                    <p v-if="errors.status" class="mt-1 text-sm text-red-600">{{ errors.status }}</p>
                                    <div class="mt-2 text-xs text-gray-500">
                                        <div v-if="form.status === 'draft'" class="text-orange-600">
                                            <strong>Entwurf:</strong> Übung ist in Entwicklung und nur für Sie sichtbar
                                        </div>
                                        <div v-else-if="form.status === 'pending_review'" class="text-yellow-600">
                                            <strong>Zur Prüfung:</strong> Übung ist einsatzbereit und wartet auf offizielle Genehmigung
                                        </div>
                                        <div v-else-if="form.status === 'approved'" class="text-blue-600">
                                            <strong>Genehmigt:</strong> Übung ist offiziell freigegeben für alle Benutzer
                                        </div>
                                        <div v-else-if="form.status === 'rejected'" class="text-red-600">
                                            <strong>Abgelehnt:</strong> Übung benötigt Überarbeitung vor der Verwendung
                                        </div>
                                        <div v-else-if="form.status === 'archived'" class="text-gray-600">
                                            <strong>Archiviert:</strong> Übung wird nicht mehr verwendet
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-6">
                                    <label class="flex items-center">
                                        <input
                                            v-model="form.requires_full_court"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-offset-0 focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                        <span class="ml-2 text-sm text-gray-700">Benötigt ganzes Feld</span>
                                    </label>

                                    <label class="flex items-center">
                                        <input
                                            v-model="form.requires_half_court"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-offset-0 focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                        <span class="ml-2 text-sm text-gray-700">Benötigt halbes Feld</span>
                                    </label>

                                    <label class="flex items-center">
                                        <input
                                            v-model="form.is_competitive"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-offset-0 focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                        <span class="ml-2 text-sm text-gray-700">Wettkampfcharakter</span>
                                    </label>

                                    <label class="flex items-center">
                                        <input
                                            v-model="form.is_public"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-offset-0 focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                        <span class="ml-2 text-sm text-gray-700">Öffentlich verfügbar</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Verknüpfte Spielzüge -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div v-if="playsLoading" class="text-center py-4">
                                    <svg class="animate-spin h-6 w-6 mx-auto text-orange-500" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500">Spielzüge werden geladen...</p>
                                </div>
                                <PlaySelector
                                    v-else
                                    :plays="availablePlays"
                                    :initial-selected-plays="drillPlays"
                                    :show-preview="true"
                                    @update:selected-plays="handlePlaysUpdate"
                                />
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex justify-between pt-6 border-t border-gray-200">
                                <div class="flex gap-2">
                                    <SecondaryButton @click="goBack">
                                        Abbrechen
                                    </SecondaryButton>
                                    <SecondaryButton 
                                        @click="viewDrill"
                                        type="button"
                                    >
                                        Zur Ansicht
                                    </SecondaryButton>
                                </div>

                                <div class="flex gap-2">
                                    <DangerButton
                                        v-if="drill.status !== 'archived'"
                                        @click="deleteDrill"
                                        type="button"
                                    >
                                        Löschen
                                    </DangerButton>
                                    
                                    <PrimaryButton
                                        type="submit"
                                        :disabled="processing"
                                        :class="{ 'opacity-25': processing }"
                                    >
                                        <span v-if="processing">Wird gespeichert...</span>
                                        <span v-else>Änderungen speichern</span>
                                    </PrimaryButton>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import AppLayout from '@/Layouts/AppLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'
import PlaySelector from '@/Components/TacticBoard/PlaySelector.vue'

const props = defineProps({
    drill: Object,
    availablePlays: {
        type: Array,
        default: () => []
    }
})

const processing = ref(false)
const errors = ref({})
const drillPlays = ref([])
const playsLoading = ref(false)

// Load linked plays on mount
onMounted(async () => {
    await loadDrillPlays()
})

async function loadDrillPlays() {
    playsLoading.value = true
    try {
        const response = await axios.get(`/api/drills/${props.drill.id}/plays`)
        drillPlays.value = response.data.data || []
    } catch (error) {
        console.error('Fehler beim Laden der Spielzüge:', error)
    } finally {
        playsLoading.value = false
    }
}

async function handlePlaysUpdate(selectedPlays) {
    drillPlays.value = selectedPlays
}

async function syncPlays() {
    try {
        // Get current plays from API
        const currentResponse = await axios.get(`/api/drills/${props.drill.id}/plays`)
        const currentPlayIds = (currentResponse.data.data || []).map(p => p.id)
        const newPlayIds = drillPlays.value.map(p => p.id || p.play_id)

        // Detach removed plays
        for (const playId of currentPlayIds) {
            if (!newPlayIds.includes(playId)) {
                await axios.delete(`/api/drills/${props.drill.id}/plays/${playId}`)
            }
        }

        // Attach new plays
        for (const play of drillPlays.value) {
            const playId = play.id || play.play_id
            if (!currentPlayIds.includes(playId)) {
                await axios.post(`/api/drills/${props.drill.id}/plays`, {
                    play_id: playId,
                    order: play.order
                })
            }
        }

        // Reorder if needed
        if (newPlayIds.length > 0) {
            await axios.put(`/api/drills/${props.drill.id}/plays/reorder`, {
                play_ids: newPlayIds
            })
        }
    } catch (error) {
        console.error('Fehler beim Synchronisieren der Spielzüge:', error)
        throw error
    }
}

const form = reactive({
    name: props.drill.name,
    description: props.drill.description,
    objectives: props.drill.objectives,
    instructions: props.drill.instructions,
    category: props.drill.category,
    difficulty_level: props.drill.difficulty_level,
    age_group: props.drill.age_group,
    min_players: props.drill.min_players,
    max_players: props.drill.max_players,
    estimated_duration: props.drill.estimated_duration,
    requires_full_court: props.drill.requires_full_court,
    requires_half_court: props.drill.requires_half_court,
    is_competitive: props.drill.is_competitive,
    is_public: props.drill.is_public,
    status: props.drill.status,
})

async function submitForm() {
    processing.value = true
    errors.value = {}

    try {
        // First sync plays
        await syncPlays()

        // Then update drill
        router.put(`/training/drills/${props.drill.id}`, form, {
            onSuccess: () => {
                // Form submitted successfully, redirect will be handled by controller
            },
            onError: (errorResponse) => {
                errors.value = errorResponse
            },
            onFinish: () => {
                processing.value = false
            }
        })
    } catch (error) {
        console.error('Fehler beim Speichern:', error)
        processing.value = false
    }
}

function deleteDrill() {
    if (confirm('Sind Sie sicher, dass Sie diese Übung löschen möchten?')) {
        router.delete(`/training/drills/${props.drill.id}`)
    }
}

function viewDrill() {
    router.get(`/training/drills/${props.drill.id}`)
}

function goBack() {
    router.get('/training/drills')
}
</script>