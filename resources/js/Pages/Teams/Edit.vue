<template>
    <AppLayout :title="`${team.name} bearbeiten`">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ team.name }} bearbeiten
                </h2>
                <SecondaryButton 
                    :href="route('web.teams.show', team.slug)"
                    as="Link"
                >
                    Zurück
                </SecondaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <!-- Tab Navigation -->
                <div class="bg-white rounded-t-lg border-b border-gray-200">
                    <nav class="flex space-x-8 px-6 pt-6">
                        <button
                            @click="activeTab = 'details'"
                            :class="[
                                'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm',
                                activeTab === 'details'
                                    ? 'border-blue-500 text-blue-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            ]"
                        >
                            Team-Details
                        </button>
                        <button
                            @click="activeTab = 'players'"
                            :class="[
                                'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm',
                                activeTab === 'players'
                                    ? 'border-blue-500 text-blue-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            ]"
                        >
                            Spieler
                        </button>
                        <button
                            @click="activeTab = 'coaches'"
                            :class="[
                                'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm',
                                activeTab === 'coaches'
                                    ? 'border-blue-500 text-blue-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            ]"
                        >
                            Trainer
                        </button>
                        <button
                            @click="activeTab = 'schedule'"
                            :class="[
                                'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm',
                                activeTab === 'schedule'
                                    ? 'border-blue-500 text-blue-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            ]"
                        >
                            Hallenzeiten
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div class="bg-white shadow-xl rounded-b-lg">
                    <!-- Team Details Tab -->
                    <div v-show="activeTab === 'details'" class="p-6">
                        <form @submit.prevent="submit">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Team Name -->
                            <div class="md:col-span-2">
                                <InputLabel for="name" value="Team-Name*" />
                                <TextInput
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                    autofocus
                                />
                                <InputError :message="form.errors.name" class="mt-2" />
                            </div>

                            <!-- Club -->
                            <div>
                                <InputLabel for="club_id" value="Verein*" />
                                <select
                                    id="club_id"
                                    v-model="form.club_id"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    required
                                >
                                    <option value="">Verein auswählen</option>
                                    <option v-for="club in clubs" :key="club.id" :value="club.id">
                                        {{ club.name }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.club_id" class="mt-2" />
                            </div>

                            <!-- Season -->
                            <div>
                                <InputLabel for="season" value="Saison*" />
                                <TextInput
                                    id="season"
                                    v-model="form.season"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="2023/2024"
                                    maxlength="9"
                                    required
                                />
                                <InputError :message="form.errors.season" class="mt-2" />
                            </div>

                            <!-- League -->
                            <div>
                                <InputLabel for="league" value="Liga" />
                                <TextInput
                                    id="league"
                                    v-model="form.league"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="z.B. Bezirksliga"
                                />
                                <InputError :message="form.errors.league" class="mt-2" />
                            </div>

                            <!-- Division -->
                            <div>
                                <InputLabel for="division" value="Staffel" />
                                <TextInput
                                    id="division"
                                    v-model="form.division"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="z.B. Staffel A"
                                />
                                <InputError :message="form.errors.division" class="mt-2" />
                            </div>

                            <!-- Age Group -->
                            <div>
                                <InputLabel for="age_group" value="Altersklasse" />
                                <select
                                    id="age_group"
                                    v-model="form.age_group"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                >
                                    <option value="">Altersklasse auswählen</option>
                                    <option value="u8">U8</option>
                                    <option value="u10">U10</option>
                                    <option value="u12">U12</option>
                                    <option value="u14">U14</option>
                                    <option value="u16">U16</option>
                                    <option value="u18">U18</option>
                                    <option value="u20">U20</option>
                                    <option value="senior">Senioren</option>
                                    <option value="masters">Masters</option>
                                    <option value="veterans">Veterans</option>
                                </select>
                                <InputError :message="form.errors.age_group" class="mt-2" />
                            </div>

                            <!-- Gender -->
                            <div>
                                <InputLabel for="gender" value="Geschlecht*" />
                                <select
                                    id="gender"
                                    v-model="form.gender"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    required
                                >
                                    <option value="male">Männlich</option>
                                    <option value="female">Weiblich</option>
                                    <option value="mixed">Mixed</option>
                                </select>
                                <InputError :message="form.errors.gender" class="mt-2" />
                            </div>

                            <!-- Active Status -->
                            <div class="md:col-span-2">
                                <label class="flex items-center">
                                    <input
                                        type="checkbox"
                                        v-model="form.is_active"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    />
                                    <span class="ml-2">Team ist aktiv</span>
                                </label>
                            </div>

                            <!-- Recruiting Status -->
                            <div class="md:col-span-2">
                                <label class="flex items-center">
                                    <input
                                        type="checkbox"
                                        v-model="form.is_recruiting"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    />
                                    <span class="ml-2">Team nimmt neue Spieler auf</span>
                                </label>
                            </div>

                            <!-- Max Players -->
                            <div>
                                <InputLabel for="max_players" value="Max. Spieler" />
                                <TextInput
                                    id="max_players"
                                    v-model="form.max_players"
                                    type="number"
                                    class="mt-1 block w-full"
                                    min="5"
                                    max="20"
                                />
                                <InputError :message="form.errors.max_players" class="mt-2" />
                            </div>

                            <!-- Min Players -->
                            <div>
                                <InputLabel for="min_players" value="Min. Spieler" />
                                <TextInput
                                    id="min_players"
                                    v-model="form.min_players"
                                    type="number"
                                    class="mt-1 block w-full"
                                    min="3"
                                    max="15"
                                />
                                <InputError :message="form.errors.min_players" class="mt-2" />
                            </div>


                            <!-- Description -->
                            <div class="md:col-span-2">
                                <InputLabel for="description" value="Beschreibung" />
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="4"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    maxlength="1000"
                                ></textarea>
                                <InputError :message="form.errors.description" class="mt-2" />
                                <div class="text-xs text-gray-500 mt-1">Max. 1000 Zeichen</div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex items-center justify-between mt-6">
                            <DangerButton
                                v-if="can?.delete"
                                type="button"
                                @click="confirmingTeamDeletion = true"
                            >
                                Team löschen
                            </DangerButton>

                            <PrimaryButton
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                            >
                                Änderungen speichern
                            </PrimaryButton>
                        </div>
                        </form>
                    </div>

                    <!-- Players Tab -->
                    <div v-show="activeTab === 'players'" class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium text-gray-900">
                                Spielerverwaltung
                            </h3>
                            <PrimaryButton @click="showPlayerModal = true">
                                Spieler hinzufügen
                            </PrimaryButton>
                        </div>

                        <!-- Current Players Table -->
                        <div v-if="teamPlayers.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            #
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Spieler
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Position
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Rollen
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Aktionen
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="player in teamPlayers" :key="player.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <TextInput
                                                    v-model.number="player.pivot.jersey_number"
                                                    type="number"
                                                    min="0"
                                                    max="99"
                                                    class="w-16 text-center"
                                                    placeholder="#"
                                                    @blur="updatePlayer(player)"
                                                />
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {{ player.user?.name }}
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        {{ player.user?.email }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <select
                                                v-model="player.pivot.primary_position"
                                                class="text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                                @change="updatePlayer(player)"
                                            >
                                                <option value="">-</option>
                                                <option value="PG">PG</option>
                                                <option value="SG">SG</option>
                                                <option value="SF">SF</option>
                                                <option value="PF">PF</option>
                                                <option value="C">C</option>
                                            </select>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <select
                                                v-model="player.pivot.status"
                                                :class="{
                                                    'bg-green-50 text-green-800 border-green-300': player.pivot.status === 'active',
                                                    'bg-red-50 text-red-800 border-red-300': player.pivot.status === 'injured',
                                                    'bg-yellow-50 text-yellow-800 border-yellow-300': player.pivot.status === 'suspended',
                                                    'bg-gray-50 text-gray-800 border-gray-300': ['inactive', 'on_loan'].includes(player.pivot.status)
                                                }"
                                                class="text-sm rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                                @change="updatePlayer(player)"
                                            >
                                                <option value="active">Aktiv</option>
                                                <option value="inactive">Inaktiv</option>
                                                <option value="injured">Verletzt</option>
                                                <option value="suspended">Gesperrt</option>
                                                <option value="on_loan">Leihgabe</option>
                                            </select>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex space-x-2">
                                                <label class="flex items-center text-sm">
                                                    <input
                                                        type="checkbox"
                                                        v-model="player.pivot.is_starter"
                                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 mr-1"
                                                        @change="updatePlayer(player)"
                                                    />
                                                    <span class="text-xs">Starter</span>
                                                </label>
                                                <label class="flex items-center text-sm">
                                                    <input
                                                        type="checkbox"
                                                        v-model="player.pivot.is_captain"
                                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 mr-1"
                                                        @change="updatePlayer(player)"
                                                    />
                                                    <span class="text-xs">Kapitän</span>
                                                </label>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <SecondaryButton
                                                @click="removePlayer(player)"
                                                class="text-red-600 hover:text-red-900"
                                            >
                                                Entfernen
                                            </SecondaryButton>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-12">
                            <div class="text-gray-500 text-lg mb-4">
                                Keine Spieler im Team
                            </div>
                            <PrimaryButton @click="showPlayerModal = true">
                                Ersten Spieler hinzufügen
                            </PrimaryButton>
                        </div>
                    </div>

                    <!-- Coaches Tab -->
                    <div v-show="activeTab === 'coaches'" class="p-6">
                        <CoachesTab :team="team" :coaches="team.coaches || []" />
                    </div>

                    <!-- Hall Schedule Tab -->
                    <div v-show="activeTab === 'schedule'" class="p-6">
                        <TeamHallSchedule :team-id="team.id" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Team Confirmation Modal -->
        <ConfirmationModal :show="confirmingTeamDeletion" @close="confirmingTeamDeletion = false">
            <template #title>
                Team löschen
            </template>

            <template #content>
                Sind Sie sicher, dass Sie dieses Team löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.
                <div v-if="team.players_count > 0" class="mt-2 text-red-600">
                    Achtung: Dieses Team hat {{ team.players_count }} Spieler zugeordnet!
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="confirmingTeamDeletion = false">
                    Abbrechen
                </SecondaryButton>

                <DangerButton
                    class="ml-3"
                    :class="{ 'opacity-25': deleteForm.processing }"
                    :disabled="deleteForm.processing"
                    @click="deleteTeamConfirmed"
                >
                    Team löschen
                </DangerButton>
            </template>
        </ConfirmationModal>

        <!-- Player Management Modal -->
        <PlayerManagementModal
            :show="showPlayerModal"
            :team="team"
            @close="showPlayerModal = false"
            @playersAdded="loadTeamPlayers"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'
import TextInput from '@/Components/TextInput.vue'
import InputLabel from '@/Components/InputLabel.vue'
import InputError from '@/Components/InputError.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'
import PlayerManagementModal from '@/Components/PlayerManagementModal.vue'
import TeamHallSchedule from '@/Components/Teams/TeamHallSchedule.vue'
import CoachesTab from '@/Components/Teams/CoachesTab.vue'

const props = defineProps({
    team: Object,
    clubs: Array,
    can: Object,
})

const form = useForm({
    name: props.team.name || '',
    club_id: props.team.club_id || '',
    season: props.team.season || '',
    league: props.team.league || '',
    division: props.team.division || '',
    age_group: props.team.age_group || '',
    gender: props.team.gender || 'male',
    is_active: props.team.is_active || false,
    is_recruiting: props.team.is_recruiting || false,
    max_players: props.team.max_players || 15,
    min_players: props.team.min_players || 8,
    description: props.team.description || '',
})

const deleteForm = useForm({})

const confirmingTeamDeletion = ref(false)
const showPlayerModal = ref(false)
const teamPlayers = ref(props.team.players || [])
const activeTab = ref('details')


const submit = () => {
    form.put(route('web.teams.update', props.team.slug))
}

const deleteTeamConfirmed = () => {
    deleteForm.delete(route('web.teams.destroy', props.team.slug))
}

// Player management functions
const loadTeamPlayers = async () => {
    try {
        const response = await fetch(route('web.teams.players.index', props.team.slug))
        const data = await response.json()
        teamPlayers.value = data.players || []
    } catch (error) {
        console.error('Fehler beim Laden der Spieler:', error)
    }
}

const updatePlayer = async (player) => {
    try {
        const response = await fetch(route('web.teams.players.update', [props.team.slug, player.id]), {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            },
            body: JSON.stringify({
                jersey_number: player.pivot.jersey_number,
                primary_position: player.pivot.primary_position,
                is_active: player.pivot.is_active,
                is_starter: player.pivot.is_starter,
                is_captain: player.pivot.is_captain,
                status: player.pivot.status,
                notes: player.pivot.notes,
            })
        })

        const data = await response.json()
        
        if (!response.ok) {
            alert(data.error || 'Fehler beim Aktualisieren des Spielers')
            // Reload to reset any invalid changes
            await loadTeamPlayers()
        }
    } catch (error) {
        console.error('Fehler beim Aktualisieren des Spielers:', error)
        alert('Fehler beim Aktualisieren des Spielers')
    }
}

const removePlayer = async (player) => {
    if (!confirm(`Möchten Sie ${player.user?.name} wirklich aus dem Team entfernen?`)) {
        return
    }

    try {
        const response = await fetch(route('web.teams.players.detach', [props.team.slug, player.id]), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            }
        })

        if (response.ok) {
            await loadTeamPlayers()
        } else {
            let errorMessage = 'Fehler beim Entfernen des Spielers'
            try {
                const data = await response.json()
                errorMessage = data.error || data.message || errorMessage
            } catch (e) {
                // Response wasn't JSON, use default error message
            }
            alert(errorMessage)
        }
    } catch (error) {
        console.error('Fehler beim Entfernen des Spielers:', error)
        alert('Fehler beim Entfernen des Spielers')
    }
}

// Load team players when component mounts (only if not already provided)
onMounted(() => {
    if (!props.team.players || props.team.players.length === 0) {
        loadTeamPlayers()
    }
})
</script>