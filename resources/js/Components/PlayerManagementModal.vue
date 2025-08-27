<template>
    <DialogModal :show="show" @close="closeModal">
        <template #title>
            Spieler zu Team hinzufügen
        </template>

        <template #content>
            <div class="space-y-6">
                <!-- Search Players -->
                <div>
                    <InputLabel for="search" value="Spieler suchen" />
                    <TextInput
                        id="search"
                        v-model="searchQuery"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="Name oder E-Mail eingeben..."
                        @input="searchPlayers"
                    />
                </div>

                <!-- Available Players List -->
                <div v-if="availablePlayers.length > 0" class="max-h-64 overflow-y-auto border rounded-lg">
                    <div class="p-4">
                        <h4 class="font-medium text-gray-900 mb-3">Verfügbare Spieler</h4>
                        <div class="space-y-2">
                            <label 
                                v-for="player in availablePlayers" 
                                :key="player.id"
                                class="flex items-center p-2 hover:bg-gray-50 rounded cursor-pointer"
                            >
                                <input
                                    type="checkbox"
                                    :value="player.id"
                                    v-model="selectedPlayerIds"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                />
                                <div class="ml-3 flex-1">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ player.user?.name }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ player.user?.email }}
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div v-else-if="searchQuery && !loading" class="text-center py-8 text-gray-500">
                    Keine verfügbaren Spieler gefunden
                </div>

                <!-- Player Details for Multiple Selection -->
                <div v-if="selectedPlayerIds.length > 0" class="space-y-4 border-t pt-4">
                    <h4 class="font-medium text-gray-900">Spielerdetails</h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Jersey Number -->
                        <div>
                            <InputLabel for="jersey_number" value="Trikotnummer" />
                            <TextInput
                                id="jersey_number"
                                v-model.number="form.jersey_number"
                                type="number"
                                min="0"
                                max="99"
                                class="mt-1 block w-full"
                                placeholder="Optional"
                            />
                            <InputError :message="form.errors.jersey_number" class="mt-2" />
                        </div>

                        <!-- Primary Position -->
                        <div>
                            <InputLabel for="primary_position" value="Hauptposition" />
                            <select
                                id="primary_position"
                                v-model="form.primary_position"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            >
                                <option value="">Position auswählen</option>
                                <option value="PG">Point Guard (PG)</option>
                                <option value="SG">Shooting Guard (SG)</option>
                                <option value="SF">Small Forward (SF)</option>
                                <option value="PF">Power Forward (PF)</option>
                                <option value="C">Center (C)</option>
                            </select>
                        </div>

                        <!-- Status -->
                        <div>
                            <InputLabel for="status" value="Status" />
                            <select
                                id="status"
                                v-model="form.status"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            >
                                <option value="active">Aktiv</option>
                                <option value="inactive">Inaktiv</option>
                                <option value="injured">Verletzt</option>
                                <option value="suspended">Gesperrt</option>
                                <option value="on_loan">Leihgabe</option>
                            </select>
                        </div>
                    </div>

                    <!-- Checkboxes -->
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input
                                type="checkbox"
                                v-model="form.is_starter"
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            />
                            <span class="ml-2">Stammspieler</span>
                        </label>
                        
                        <label class="flex items-center">
                            <input
                                type="checkbox"
                                v-model="form.is_captain"
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            />
                            <span class="ml-2">Kapitän</span>
                        </label>
                    </div>

                    <!-- Notes -->
                    <div>
                        <InputLabel for="notes" value="Notizen" />
                        <textarea
                            id="notes"
                            v-model="form.notes"
                            rows="3"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            placeholder="Notizen zum Spieler in diesem Team..."
                            maxlength="1000"
                        ></textarea>
                        <div class="text-xs text-gray-500 mt-1">Max. 1000 Zeichen</div>
                    </div>
                </div>

                <div v-if="loading" class="text-center py-4">
                    <div class="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-indigo-600"></div>
                    <span class="ml-2 text-sm text-gray-600">Lade Spieler...</span>
                </div>
            </div>
        </template>

        <template #footer>
            <SecondaryButton @click="closeModal">
                Abbrechen
            </SecondaryButton>

            <PrimaryButton
                class="ml-3"
                :class="{ 'opacity-25': form.processing || selectedPlayerIds.length === 0 }"
                :disabled="form.processing || selectedPlayerIds.length === 0"
                @click="addPlayers"
            >
                {{ selectedPlayerIds.length }} Spieler hinzufügen
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import DialogModal from '@/Components/DialogModal.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import InputLabel from '@/Components/InputLabel.vue'
import InputError from '@/Components/InputError.vue'

const props = defineProps({
    show: Boolean,
    team: Object,
})

const emit = defineEmits(['close', 'playersAdded'])

const searchQuery = ref('')
const availablePlayers = ref([])
const selectedPlayerIds = ref([])
const loading = ref(false)

const form = useForm({
    player_ids: [],
    jersey_number: null,
    primary_position: '',
    status: 'active',
    is_starter: false,
    is_captain: false,
    notes: '',
})

// Search players with debounce
let searchTimeout = null
const searchPlayers = () => {
    if (searchTimeout) {
        clearTimeout(searchTimeout)
    }
    
    searchTimeout = setTimeout(async () => {
        if (searchQuery.value.length < 2) {
            availablePlayers.value = []
            return
        }

        loading.value = true
        
        try {
            const response = await fetch(`/api/players/search?q=${encodeURIComponent(searchQuery.value)}&exclude_team=${props.team.id}`)
            const data = await response.json()
            availablePlayers.value = data.players || []
        } catch (error) {
            console.error('Fehler beim Suchen der Spieler:', error)
            availablePlayers.value = []
        } finally {
            loading.value = false
        }
    }, 300)
}

const addPlayers = () => {
    form.player_ids = selectedPlayerIds.value
    
    form.post(route('web.teams.players.attach', props.team.slug), {
        preserveScroll: true,
        onSuccess: (response) => {
            emit('playersAdded')
            closeModal()
        },
        onError: (errors) => {
            console.error('Fehler beim Hinzufügen der Spieler:', errors)
        }
    })
}

const closeModal = () => {
    emit('close')
    
    // Reset form and state
    form.reset()
    selectedPlayerIds.value = []
    searchQuery.value = ''
    availablePlayers.value = []
}

// Watch for show prop changes to reset state
watch(() => props.show, (newShow) => {
    if (!newShow) {
        closeModal()
    }
})
</script>