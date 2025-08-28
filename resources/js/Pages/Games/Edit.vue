<template>
    <AppLayout title="Spiel bearbeiten">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Spiel bearbeiten: {{ game.home_team_display_name }} vs {{ game.away_team_display_name }}
                </h2>
                <SecondaryButton 
                    :href="route('web.games.show', game.id)"
                    as="Link"
                >
                    Zurück
                </SecondaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6" novalidate>
                        <!-- Game Status Warning -->
                        <div v-if="game.status !== 'scheduled'" class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
                            <p class="text-sm text-yellow-800">
                                <strong>Hinweis:</strong> Dieses Spiel hat den Status "{{ getStatusLabel(game.status) }}". 
                                Änderungen sind nur eingeschränkt möglich.
                            </p>
                        </div>

                        <!-- Form Navigation -->
                        <div class="mb-8 border-b border-gray-200">
                            <nav class="-mb-px flex space-x-8">
                                <button
                                    v-for="section in formSections"
                                    :key="section.id"
                                    @click.prevent="activeSection = section.id"
                                    :class="[
                                        'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm',
                                        activeSection === section.id
                                            ? 'border-indigo-500 text-indigo-600'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    ]"
                                    type="button"
                                >
                                    {{ section.name }}
                                    <span v-if="hasErrors(section.fields)" class="ml-2 text-red-500">•</span>
                                </button>
                            </nav>
                        </div>

                        <!-- Basis Informationen -->
                        <div v-show="activeSection === 'basic'" class="space-y-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Basis Informationen</h3>
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

                                <!-- Away Team Type Selection -->
                                <div>
                                    <InputLabel value="Gegnerisches Team*" />
                                    <div class="mt-2 space-y-4">
                                        <div class="flex items-center">
                                            <input
                                                id="opponent_type_internal"
                                                v-model="opponentType"
                                                name="opponent_type"
                                                type="radio"
                                                value="internal"
                                                class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300"
                                                :disabled="game.status !== 'scheduled'"
                                            />
                                            <label for="opponent_type_internal" class="ml-3 block text-sm font-medium text-gray-700">
                                                Internes Team (aus unserem System)
                                            </label>
                                        </div>
                                        <div class="flex items-center">
                                            <input
                                                id="opponent_type_external"
                                                v-model="opponentType"
                                                name="opponent_type"
                                                type="radio"
                                                value="external"
                                                class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300"
                                                :disabled="game.status !== 'scheduled'"
                                            />
                                            <label for="opponent_type_external" class="ml-3 block text-sm font-medium text-gray-700">
                                                Externes Team (Freitext)
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Internal Away Team -->
                                <div v-if="opponentType === 'internal'">
                                    <InputLabel for="away_team_id" value="Auswärtsteam*" />
                                    <select
                                        id="away_team_id"
                                        v-model="form.away_team_id"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        :required="opponentType === 'internal'"
                                        :disabled="!form.home_team_id || game.status !== 'scheduled'"
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

                                <!-- External Away Team -->
                                <div v-if="opponentType === 'external'">
                                    <InputLabel for="away_team_name" value="Auswärtsteam (Name)*" />
                                    <TextInput
                                        id="away_team_name"
                                        v-model="form.away_team_name"
                                        type="text"
                                        class="mt-1 block w-full"
                                        placeholder="z.B. SV Brackwede 2"
                                        :required="opponentType === 'external'"
                                        :disabled="game.status !== 'scheduled'"
                                    />
                                    <InputError :message="form.errors.away_team_name" class="mt-2" />
                                    <p class="mt-1 text-sm text-gray-500">
                                        Geben Sie den Namen des gegnerischen Teams ein, wie er angezeigt werden soll.
                                    </p>
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

                                <!-- Venue -->
                                <div>
                                    <InputLabel for="venue" value="Spielort" />
                                    <TextInput
                                        id="venue"
                                        v-model="form.venue"
                                        type="text"
                                        class="mt-1 block w-full"
                                        placeholder="z.B. Sporthalle Am Stadtpark"
                                    />
                                    <InputError :message="form.errors.venue" class="mt-2" />
                                </div>

                                <!-- Venue Code (Hallennummer) -->
                                <div>
                                    <InputLabel for="venue_code" value="Hallennummer" />
                                    <TextInput
                                        id="venue_code"
                                        v-model="form.venue_code"
                                        type="text"
                                        class="mt-1 block w-full"
                                        placeholder="z.B. 502A160"
                                    />
                                    <InputError :message="form.errors.venue_code" class="mt-2" />
                                    <p class="mt-1 text-sm text-gray-500">
                                        Hallennummer oder -kennzeichnung (z.B. aus Spielplänen)
                                    </p>
                                </div>

                                <!-- Venue Address -->
                                <div>
                                    <InputLabel for="venue_address" value="Adresse des Spielorts" />
                                    <TextInput
                                        id="venue_address"
                                        v-model="form.venue_address"
                                        type="text"
                                        class="mt-1 block w-full"
                                        placeholder="Straße, PLZ Ort"
                                    />
                                    <InputError :message="form.errors.venue_address" class="mt-2" />
                                </div>

                                <!-- Game Type -->
                                <div>
                                    <InputLabel for="type" value="Spieltyp*" />
                                    <select
                                        id="type"
                                        v-model="form.type"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        required
                                    >
                                        <option value="regular_season">Reguläre Saison</option>
                                        <option value="playoff">Playoff</option>
                                        <option value="championship">Meisterschaft</option>
                                        <option value="friendly">Freundschaftsspiel</option>
                                        <option value="tournament">Turnier</option>
                                        <option value="scrimmage">Trainingsspiel</option>
                                    </select>
                                    <InputError :message="form.errors.type" class="mt-2" />
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
                                    <InputLabel for="division" value="Division/Gruppe" />
                                    <TextInput
                                        id="division"
                                        v-model="form.division"
                                        type="text"
                                        class="mt-1 block w-full"
                                        placeholder="z.B. Gruppe A"
                                    />
                                    <InputError :message="form.errors.division" class="mt-2" />
                                </div>

                                <!-- Status -->
                                <div v-if="can?.update">
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

                                <!-- Pre-game Notes -->
                                <div class="md:col-span-2">
                                    <InputLabel for="pre_game_notes" value="Notizen vor dem Spiel" />
                                    <textarea
                                        id="pre_game_notes"
                                        v-model="form.pre_game_notes"
                                        rows="4"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        maxlength="1000"
                                    ></textarea>
                                    <InputError :message="form.errors.pre_game_notes" class="mt-2" />
                                    <div class="text-xs text-gray-500 mt-1">Max. 1000 Zeichen</div>
                                </div>
                            </div>
                        </div>

                        <!-- Turnier Informationen -->
                        <div v-show="activeSection === 'tournament'" class="space-y-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Turnier Informationen</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="tournament_id" value="Turnier ID" />
                                    <TextInput
                                        id="tournament_id"
                                        v-model="form.tournament_id"
                                        type="text"
                                        class="mt-1 block w-full"
                                        placeholder="Eindeutige Turnier-Kennung"
                                    />
                                    <InputError :message="form.errors.tournament_id" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="tournament_round" value="Turnierrunde" />
                                    <TextInput
                                        id="tournament_round"
                                        v-model="form.tournament_round"
                                        type="text"
                                        class="mt-1 block w-full"
                                        placeholder="z.B. Viertelfinale, Halbfinale"
                                    />
                                    <InputError :message="form.errors.tournament_round" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="tournament_game_number" value="Spiel Nummer im Turnier" />
                                    <TextInput
                                        id="tournament_game_number"
                                        v-model="form.tournament_game_number"
                                        type="number"
                                        class="mt-1 block w-full"
                                        min="1"
                                    />
                                    <InputError :message="form.errors.tournament_game_number" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <!-- Spielregeln -->
                        <div v-show="activeSection === 'rules'" class="space-y-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Spielregeln & Timing</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="total_periods" value="Anzahl Spielabschnitte" />
                                    <TextInput
                                        id="total_periods"
                                        v-model="form.total_periods"
                                        type="number"
                                        class="mt-1 block w-full"
                                        min="1"
                                        max="8"
                                    />
                                    <InputError :message="form.errors.total_periods" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="period_length_minutes" value="Länge pro Spielabschnitt (Min.)" />
                                    <TextInput
                                        id="period_length_minutes"
                                        v-model="form.period_length_minutes"
                                        type="number"
                                        class="mt-1 block w-full"
                                        min="1"
                                        max="30"
                                    />
                                    <InputError :message="form.errors.period_length_minutes" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="overtime_length_minutes" value="Verlängerung (Min.)" />
                                    <TextInput
                                        id="overtime_length_minutes"
                                        v-model="form.overtime_length_minutes"
                                        type="number"
                                        class="mt-1 block w-full"
                                        min="1"
                                        max="15"
                                    />
                                    <InputError :message="form.errors.overtime_length_minutes" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <!-- Veranstaltung -->
                        <div v-show="activeSection === 'event'" class="space-y-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Veranstaltungsdetails</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="capacity" value="Kapazität des Spielorts" />
                                    <TextInput
                                        id="capacity"
                                        v-model="form.capacity"
                                        type="number"
                                        class="mt-1 block w-full"
                                        min="0"
                                    />
                                    <InputError :message="form.errors.capacity" class="mt-2" />
                                </div>

                                <div class="space-y-3">
                                    <label class="flex items-center">
                                        <input
                                            type="checkbox"
                                            v-model="form.allow_spectators"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                        <span class="ml-2">Zuschauer erlaubt</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input
                                            type="checkbox"
                                            v-model="form.allow_media"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                        <span class="ml-2">Medien erlaubt</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Medien & Streaming -->
                        <div v-show="activeSection === 'media'" class="space-y-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Medien & Streaming</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-3">
                                    <label class="flex items-center">
                                        <input
                                            type="checkbox"
                                            v-model="form.is_streamed"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                        <span class="ml-2">Spiel wird gestreamt</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input
                                            type="checkbox"
                                            v-model="form.allow_recording"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                        <span class="ml-2">Aufnahmen erlauben</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input
                                            type="checkbox"
                                            v-model="form.allow_photos"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                        <span class="ml-2">Fotos erlauben</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input
                                            type="checkbox"
                                            v-model="form.allow_streaming"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                        <span class="ml-2">Live-Streaming erlauben</span>
                                    </label>
                                </div>

                                <div v-if="form.is_streamed">
                                    <InputLabel for="stream_url" value="Stream URL" />
                                    <TextInput
                                        id="stream_url"
                                        v-model="form.stream_url"
                                        type="url"
                                        class="mt-1 block w-full"
                                        placeholder="https://stream.example.com/live123"
                                    />
                                    <InputError :message="form.errors.stream_url" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <!-- Sicherheit & Medizin -->
                        <div v-show="activeSection === 'safety'" class="space-y-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Sicherheit & Medizin</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="medical_staff_present" value="Anwesende medizinische Betreuung" />
                                    <TextInput
                                        id="medical_staff_present"
                                        v-model="form.medical_staff_present"
                                        type="text"
                                        class="mt-1 block w-full"
                                        placeholder="Name des Arztes/Sanitäters"
                                    />
                                    <InputError :message="form.errors.medical_staff_present" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <!-- Wetterbedingungen -->
                        <div v-show="activeSection === 'weather'" class="space-y-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Wetterbedingungen</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <InputLabel for="weather_conditions" value="Wetterverhältnisse" />
                                    <select
                                        id="weather_conditions"
                                        v-model="form.weather_conditions"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    >
                                        <option value="">Auswählen</option>
                                        <option value="sunny">Sonnig</option>
                                        <option value="cloudy">Bewölkt</option>
                                        <option value="rainy">Regnerisch</option>
                                        <option value="snowy">Schnee</option>
                                        <option value="indoor">Indoor/Halle</option>
                                    </select>
                                    <InputError :message="form.errors.weather_conditions" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="temperature" value="Temperatur (°C)" />
                                    <TextInput
                                        id="temperature"
                                        v-model="form.temperature"
                                        type="number"
                                        class="mt-1 block w-full"
                                        min="-20"
                                        max="50"
                                    />
                                    <InputError :message="form.errors.temperature" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="court_conditions" value="Platzbedingungen" />
                                    <select
                                        id="court_conditions"
                                        v-model="form.court_conditions"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    >
                                        <option value="">Auswählen</option>
                                        <option value="excellent">Ausgezeichnet</option>
                                        <option value="good">Gut</option>
                                        <option value="fair">Befriedigend</option>
                                        <option value="poor">Schlecht</option>
                                        <option value="wet">Nass</option>
                                        <option value="slippery">Rutschig</option>
                                    </select>
                                    <InputError :message="form.errors.court_conditions" class="mt-2" />
                                </div>
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

// Form Section Management
const activeSection = ref('basic')

const formSections = ref([
    { id: 'basic', name: 'Basis', fields: ['home_team_id', 'away_team_id', 'away_team_name', 'scheduled_at', 'venue', 'type', 'season'] },
    { id: 'tournament', name: 'Turnier', fields: ['tournament_id', 'tournament_round', 'tournament_game_number'] },
    { id: 'rules', name: 'Regeln', fields: ['total_periods', 'period_length_minutes', 'overtime_length_minutes'] },
    { id: 'event', name: 'Veranstaltung', fields: ['capacity', 'allow_spectators', 'allow_media'] },
    { id: 'media', name: 'Medien', fields: ['is_streamed', 'stream_url', 'allow_recording', 'allow_photos', 'allow_streaming'] },
    { id: 'safety', name: 'Sicherheit', fields: ['medical_staff_present'] },
    { id: 'weather', name: 'Wetter', fields: ['weather_conditions', 'temperature', 'court_conditions'] }
])

// Opponent Type Management - determine from existing game data
const opponentType = ref(props.game.away_team_id ? 'internal' : 'external')

// Format datetime for input field
function formatDateTimeForInput(dateString) {
    if (!dateString) return ''
    const date = new Date(dateString)
    date.setMinutes(date.getMinutes() - date.getTimezoneOffset())
    return date.toISOString().slice(0, 16)
}

const form = useForm({
    // Basic Information
    home_team_id: props.game.home_team_id || '',
    away_team_id: props.game.away_team_id || '',
    away_team_name: props.game.away_team_name || '',
    home_team_name: props.game.home_team_name || '',
    scheduled_at: formatDateTimeForInput(props.game.scheduled_at),
    venue: props.game.venue || '',
    venue_address: props.game.venue_address || '',
    venue_code: props.game.venue_code || '',
    type: props.game.type || 'regular_season',
    season: props.game.season || '',
    league: props.game.league || '',
    division: props.game.division || '',
    pre_game_notes: props.game.pre_game_notes || '',
    status: props.game.status || 'scheduled',
    
    // Tournament Information
    tournament_id: props.game.tournament_id || '',
    tournament_round: props.game.tournament_round || '',
    tournament_game_number: props.game.tournament_game_number || null,
    
    // Game Rules
    total_periods: props.game.total_periods || 4,
    period_length_minutes: props.game.period_length_minutes || 10,
    overtime_length_minutes: props.game.overtime_length_minutes || 5,
    
    // Event Details
    capacity: props.game.capacity || null,
    allow_spectators: props.game.allow_spectators ?? true,
    allow_media: props.game.allow_media ?? true,
    
    // Media & Streaming
    is_streamed: props.game.is_streamed || false,
    stream_url: props.game.stream_url || '',
    allow_recording: props.game.allow_recording ?? true,
    allow_photos: props.game.allow_photos ?? true,
    allow_streaming: props.game.allow_streaming || false,
    
    // Safety
    medical_staff_present: props.game.medical_staff_present || '',
    
    // Weather
    weather_conditions: props.game.weather_conditions || '',
    temperature: props.game.temperature || null,
    court_conditions: props.game.court_conditions || '',
})

const deleteForm = useForm({})

const confirmingGameDeletion = ref(false)

const availableAwayTeams = computed(() => {
    if (!form.home_team_id) return props.teams
    return props.teams.filter(team => team.id !== form.home_team_id)
})

// Helper Functions
const hasErrors = computed(() => {
    return (fields) => fields.some(field => form.errors[field])
})

function getStatusLabel(status) {
    const labels = {
        'scheduled': 'Geplant',
        'live': 'Live',
        'halftime': 'Halbzeit',
        'overtime': 'Verlängerung',
        'finished': 'Beendet',
        'cancelled': 'Abgesagt',
        'postponed': 'Verschoben',
        'forfeited': 'Aufgegeben'
    }
    return labels[status] || status
}

const submit = () => {
    // Clear away team data based on opponent type
    if (opponentType.value === 'external') {
        form.away_team_id = null
    } else {
        form.away_team_name = ''
    }
    
    form.put(route('web.games.update', props.game.id))
}

const deleteGameConfirmed = () => {
    deleteForm.delete(route('web.games.destroy', props.game.id))
}
</script>