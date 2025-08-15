<template>
    <AppLayout title="Neues Spiel">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Neues Spiel erstellen
                </h2>
                <SecondaryButton 
                    :href="route('games.index')"
                    as="Link"
                >
                    Abbrechen
                </SecondaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Home Team -->
                            <div>
                                <InputLabel for="home_team_id" value="Heimteam*" />
                                <select
                                    id="home_team_id"
                                    v-model="form.home_team_id"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    required
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
                                    :disabled="!form.home_team_id"
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
                                    :min="minDateTime"
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
                        <div class="flex items-center justify-end mt-6">
                            <PrimaryButton
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                            >
                                Spiel erstellen
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import InputLabel from '@/Components/InputLabel.vue'
import InputError from '@/Components/InputError.vue'

const props = defineProps({
    teams: Array,
})

const form = useForm({
    home_team_id: '',
    away_team_id: '',
    scheduled_at: '',
    location: '',
    game_type: 'league',
    season: getCurrentSeason(),
    league: '',
    round: '',
    notes: '',
})

// Calculate current season (e.g., "2023/2024")
function getCurrentSeason() {
    const now = new Date()
    const year = now.getFullYear()
    const month = now.getMonth()
    // Season starts in August (month 7)
    if (month >= 7) {
        return `${year}/${year + 1}`
    } else {
        return `${year - 1}/${year}`
    }
}

const minDateTime = computed(() => {
    const now = new Date()
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset())
    return now.toISOString().slice(0, 16)
})

const availableAwayTeams = computed(() => {
    if (!form.home_team_id) return props.teams
    return props.teams.filter(team => team.id !== form.home_team_id)
})

const submit = () => {
    form.post(route('games.store'))
}
</script>