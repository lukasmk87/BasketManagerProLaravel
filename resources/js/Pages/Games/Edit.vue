<template>
    <AppLayout title="Spiel bearbeiten">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Spiel bearbeiten: {{ game.homeTeam?.name || 'Team auswählen' }} vs {{ game.awayTeam?.name || 'Team auswählen' }}
                </h2>
                <SecondaryButton 
                    :href="route('games.show', game.id)"
                    as="Link"
                >
                    Zurück
                </SecondaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6">
                        <!-- Game Status Warning -->
                        <div v-if="game.status !== 'scheduled'" class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
                            <p class="text-sm text-yellow-800">
                                <strong>Hinweis:</strong> Dieses Spiel hat den Status "{{ getStatusLabel(game.status) }}". 
                                Änderungen sind nur eingeschränkt möglich.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Home Team -->
                            <div>
                                <InputLabel for="home_team_id" value="Heimteam*" />
                                <select
                                    id="home_team_id"
                                    v-model="form.home_team_id"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    required
                                    :disabled="game.status !== 'scheduled'"
                                >
                                    <option value="">Team auswählen</option>
                                    <option v-for="team in teams" :key="team.id" :value="team.id">
                                        {{ team.name }} ({{ team.club.name }})
                                    </option>
                                </select>
                                <InputError :message="form.errors.home_team_id" class="mt-2" />
                            </div>

                            <!-- Away Team -->
                            <div>
                                <InputLabel for="away_team_id" value="Auswärtsteam*" />
                                <select
                                    id="away_team_id"
                                    v-model="form.away_team_id"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    required
                                    :disabled="game.status !== 'scheduled'"
                                >
                                    <option value="">Team auswählen</option>
                                    <option 
                                        v-for="team in availableAwayTeams" 
                                        :key="team.id" 
                                        :value="team.id"
                                    >
                                        {{ team.name }} ({{ team.club.name }})
                                    </option>
                                </select>
                                <InputError :message="form.errors.away_team_id" class="mt-2" />
                            </div>

                            <!-- Scheduled Date/Time -->
                            <div>
                                <InputLabel for="scheduled_at" value="Datum und Uhrzeit*" />
                                <TextInput
                                    id="scheduled_at"
                                    v-model="form.scheduled_at"
                                    type="datetime-local"
                                    class="mt-1 block w-full"
                                    required
                                />
                                <InputError :message="form.errors.scheduled_at" class="mt-2" />
                            </div>

                            <!-- Location -->
                            <div>
                                <InputLabel for="location" value="Spielort" />
                                <TextInput
                                    id="location"
                                    v-model="form.location"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="z.B. Sporthalle Am Stadtpark"
                                />
                                <InputError :message="form.errors.location" class="mt-2" />
                            </div>

                            <!-- Game Type -->
                            <div>
                                <InputLabel for="game_type" value="Spieltyp*" />
                                <select
                                    id="game_type"
                                    v-model="form.game_type"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    required
                                >
                                    <option value="league">Liga</option>
                                    <option value="friendly">Freundschaftsspiel</option>
                                    <option value="playoff">Playoff</option>
                                    <option value="tournament">Turnier</option>
                                </select>
                                <InputError :message="form.errors.game_type" class="mt-2" />
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

                            <!-- Round -->
                            <div>
                                <InputLabel for="round" value="Spieltag/Runde" />
                                <TextInput
                                    id="round"
                                    v-model="form.round"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="z.B. 5. Spieltag"
                                />
                                <InputError :message="form.errors.round" class="mt-2" />
                            </div>

                            <!-- Status -->
                            <div>
                                <InputLabel for="status" value="Status" />
                                <select
                                    id="status"
                                    v-model="form.status"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                >
                                    <option value="scheduled">Geplant</option>
                                    <option value="live">Live</option>
                                    <option value="finished">Beendet</option>
                                    <option value="cancelled">Abgesagt</option>
                                    <option value="postponed">Verschoben</option>
                                </select>
                                <InputError :message="form.errors.status" class="mt-2" />
                            </div>

                            <!-- Score (only if finished) -->
                            <div v-if="game.status === 'finished'">
                                <InputLabel value="Endstand" />
                                <div class="flex items-center gap-2 mt-1">
                                    <TextInput
                                        v-model="form.home_score"
                                        type="number"
                                        class="w-20"
                                        min="0"
                                        placeholder="Heim"
                                    />
                                    <span class="text-gray-500">:</span>
                                    <TextInput
                                        v-model="form.away_score"
                                        type="number"
                                        class="w-20"
                                        min="0"
                                        placeholder="Gast"
                                    />
                                </div>
                            </div>

                            <!-- Notes -->
                            <div class="md:col-span-2">
                                <InputLabel for="notes" value="Notizen" />
                                <textarea
                                    id="notes"
                                    v-model="form.notes"
                                    rows="4"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    maxlength="1000"
                                ></textarea>
                                <InputError :message="form.errors.notes" class="mt-2" />
                                <div class="text-xs text-gray-500 mt-1">Max. 1000 Zeichen</div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex items-center justify-between mt-6">
                            <DangerButton
                                v-if="can?.delete && game.status === 'scheduled'"
                                type="button"
                                @click="confirmingGameDeletion = true"
                            >
                                Spiel löschen
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
            </div>
        </div>

        <!-- Delete Game Confirmation Modal -->
        <ConfirmationModal :show="confirmingGameDeletion" @close="confirmingGameDeletion = false">
            <template #title>
                Spiel löschen
            </template>

            <template #content>
                Sind Sie sicher, dass Sie dieses Spiel löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.
            </template>

            <template #footer>
                <SecondaryButton @click="confirmingGameDeletion = false">
                    Abbrechen
                </SecondaryButton>

                <DangerButton
                    class="ml-3"
                    :class="{ 'opacity-25': deleteForm.processing }"
                    :disabled="deleteForm.processing"
                    @click="deleteGameConfirmed"
                >
                    Spiel löschen
                </DangerButton>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'
import TextInput from '@/Components/TextInput.vue'
import InputLabel from '@/Components/InputLabel.vue'
import InputError from '@/Components/InputError.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'

const props = defineProps({
    game: Object,
    teams: Array,
    can: Object,
})

// Format datetime for input field
function formatDateTimeForInput(dateString) {
    if (!dateString) return ''
    const date = new Date(dateString)
    date.setMinutes(date.getMinutes() - date.getTimezoneOffset())
    return date.toISOString().slice(0, 16)
}

const form = useForm({
    home_team_id: props.game.home_team_id || '',
    away_team_id: props.game.away_team_id || '',
    scheduled_at: formatDateTimeForInput(props.game.scheduled_at),
    location: props.game.location || '',
    game_type: props.game.game_type || 'league',
    season: props.game.season || '',
    league: props.game.league || '',
    round: props.game.round || '',
    status: props.game.status || 'scheduled',
    home_score: props.game.home_score || 0,
    away_score: props.game.away_score || 0,
    notes: props.game.notes || '',
})

const deleteForm = useForm({})

const confirmingGameDeletion = ref(false)

const availableAwayTeams = computed(() => {
    if (!form.home_team_id) return props.teams
    return props.teams.filter(team => team.id !== form.home_team_id)
})

function getStatusLabel(status) {
    const labels = {
        'scheduled': 'Geplant',
        'live': 'Live',
        'finished': 'Beendet',
        'cancelled': 'Abgesagt',
        'postponed': 'Verschoben'
    }
    return labels[status] || status
}

const submit = () => {
    form.put(route('games.update', props.game.id))
}

const deleteGameConfirmed = () => {
    deleteForm.delete(route('games.destroy', props.game.id))
}
</script>