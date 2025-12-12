<template>
    <AppLayout :title="`Spiel: ${homeTeamName} vs ${awayTeamName}`">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Spieldetails
                </h2>
                <div class="flex gap-2">
                    <SecondaryButton 
                        v-if="can?.update"
                        :href="route('web.games.edit', game.id)"
                        as="Link"
                    >
                        Bearbeiten
                    </SecondaryButton>
                    <DangerButton
                        v-if="can?.delete && game.status === 'scheduled'"
                        type="button"
                        @click="confirmingGameDeletion = true"
                    >
                        Löschen
                    </DangerButton>
                    <PrimaryButton 
                        v-if="game.status === 'scheduled' && can?.score"
                        :href="route('games.live-scoring', game.id)"
                        as="Link"
                    >
                        Live-Scoring starten
                    </PrimaryButton>
                    <SecondaryButton 
                        :href="route('web.games.index')"
                        as="Link"
                    >
                        Zurück zur Übersicht
                    </SecondaryButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Game Header -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mb-6">
                    <div class="p-6">
                        <!-- Status Badge -->
                        <div class="flex justify-center mb-4">
                            <span 
                                :class="getStatusClasses(game.status)"
                                class="px-3 py-1 rounded-full text-sm font-medium"
                            >
                                {{ getStatusLabel(game.status) }}
                            </span>
                        </div>

                        <!-- Teams and Score -->
                        <div class="grid grid-cols-3 gap-4 items-center mb-6">
                            <!-- Home Team -->
                            <div class="text-center">
                                <h3 class="text-2xl font-bold text-gray-900">
                                    {{ homeTeamName }}
                                </h3>
                                <p v-if="homeClubName" class="text-gray-600">{{ homeClubName }}</p>
                                <p v-if="game.status === 'finished'" class="text-5xl font-bold mt-4">
                                    {{ game.home_score }}
                                </p>
                            </div>

                            <!-- VS -->
                            <div class="text-center">
                                <span class="text-3xl font-bold text-gray-400">VS</span>
                            </div>

                            <!-- Away Team -->
                            <div class="text-center">
                                <h3 class="text-2xl font-bold text-gray-900">
                                    {{ awayTeamName }}
                                </h3>
                                <p v-if="awayClubName" class="text-gray-600">{{ awayClubName }}</p>
                                <p v-if="game.status === 'finished'" class="text-5xl font-bold mt-4">
                                    {{ game.away_score }}
                                </p>
                            </div>
                        </div>

                        <!-- Game Info -->
                        <div class="border-t pt-4">
                            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Datum & Uhrzeit</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        {{ formatDateTime(game.scheduled_at) }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Spieltyp</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ getGameTypeLabel(game.type) }}</dd>
                                </div>
                                <div v-if="game.venue">
                                    <dt class="text-sm font-medium text-gray-500">Spielort</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ game.venue }}</dd>
                                </div>
                                <div v-if="game.venue_address">
                                    <dt class="text-sm font-medium text-gray-500">Adresse</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ game.venue_address }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Saison</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ game.season }}</dd>
                                </div>
                                <div v-if="game.league">
                                    <dt class="text-sm font-medium text-gray-500">Liga</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ game.league }}</dd>
                                </div>
                                <div v-if="game.division">
                                    <dt class="text-sm font-medium text-gray-500">Division</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ game.division }}</dd>
                                </div>
                                <div v-if="game.round">
                                    <dt class="text-sm font-medium text-gray-500">Spieltag/Runde</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ game.round }}</dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Player Availability / RSVP -->
                        <div v-if="playerAvailability" class="mt-4 border-t pt-4">
                            <h4 class="text-sm font-medium text-gray-500 mb-3">Deine Verfügbarkeit</h4>
                            <RsvpButtons
                                event-type="game"
                                :event-id="game.id"
                                :current-status="playerAvailability.status"
                                :can-respond="playerAvailability.can_respond"
                            />
                        </div>

                        <!-- Notes -->
                        <div v-if="game.notes" class="mt-4 border-t pt-4">
                            <h4 class="text-sm font-medium text-gray-500 mb-2">Notizen</h4>
                            <p class="text-sm text-gray-900">{{ game.notes }}</p>
                        </div>
                    </div>
                </div>

                <!-- Quarter Scores (if finished) -->
                <div v-if="game.status === 'finished' && game.quarter_scores" class="bg-white overflow-hidden shadow-xl sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Viertelergebnisse</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Team
                                        </th>
                                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Q1
                                        </th>
                                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Q2
                                        </th>
                                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Q3
                                        </th>
                                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Q4
                                        </th>
                                        <th v-if="game.quarter_scores.overtime" class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            OT
                                        </th>
                                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider font-bold">
                                            Gesamt
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr>
                                        <td class="px-4 py-2 text-sm font-medium text-gray-900">
                                            {{ homeTeamName }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-center">{{ game.quarter_scores.home.q1 || 0 }}</td>
                                        <td class="px-4 py-2 text-sm text-center">{{ game.quarter_scores.home.q2 || 0 }}</td>
                                        <td class="px-4 py-2 text-sm text-center">{{ game.quarter_scores.home.q3 || 0 }}</td>
                                        <td class="px-4 py-2 text-sm text-center">{{ game.quarter_scores.home.q4 || 0 }}</td>
                                        <td v-if="game.quarter_scores.overtime" class="px-4 py-2 text-sm text-center">
                                            {{ game.quarter_scores.home.ot || 0 }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-center font-bold">{{ game.home_score }}</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2 text-sm font-medium text-gray-900">
                                            {{ awayTeamName }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-center">{{ game.quarter_scores.away.q1 || 0 }}</td>
                                        <td class="px-4 py-2 text-sm text-center">{{ game.quarter_scores.away.q2 || 0 }}</td>
                                        <td class="px-4 py-2 text-sm text-center">{{ game.quarter_scores.away.q3 || 0 }}</td>
                                        <td class="px-4 py-2 text-sm text-center">{{ game.quarter_scores.away.q4 || 0 }}</td>
                                        <td v-if="game.quarter_scores.overtime" class="px-4 py-2 text-sm text-center">
                                            {{ game.quarter_scores.away.ot || 0 }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-center font-bold">{{ game.away_score }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Player Registration (for players) -->
                <PlayerRegistration
                    v-if="$page.props.auth.user?.player"
                    type="game"
                    :entity-id="game.id"
                    :current-registration="currentPlayerRegistration"
                    :deadline="game.booking_deadline"
                    @registration-updated="refreshRegistrationData"
                    class="mb-6"
                />

                <!-- Spielvorbereitung (Playbooks) -->
                <div v-if="can?.update || gamePlaybooks.length > 0" class="bg-white overflow-hidden shadow-xl sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold">Spielvorbereitung</h3>
                            <button
                                v-if="can?.update"
                                @click="showPlaybookSection = !showPlaybookSection"
                                type="button"
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-orange-700 bg-orange-100 hover:bg-orange-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
                            >
                                {{ showPlaybookSection ? 'Schließen' : 'Playbooks verwalten' }}
                            </button>
                        </div>

                        <!-- Loading State -->
                        <div v-if="playbooksLoading" class="text-center py-4">
                            <svg class="animate-spin h-6 w-6 mx-auto text-orange-500" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">Playbooks werden geladen...</p>
                        </div>

                        <!-- Playbook Selector (Edit Mode) -->
                        <PlaybookSelector
                            v-if="showPlaybookSection && can?.update"
                            :playbooks="availablePlaybooks"
                            :selected-playbooks="gamePlaybooks"
                            @attach="attachPlaybook"
                            @detach="detachPlaybook"
                            class="mb-6"
                        />

                        <!-- Verknüpfte Playbooks anzeigen -->
                        <div v-if="!playbooksLoading && gamePlaybooks.length > 0" class="space-y-4">
                            <div
                                v-for="playbook in gamePlaybooks"
                                :key="playbook.id"
                                class="border border-gray-200 rounded-lg overflow-hidden"
                            >
                                <!-- Playbook Header -->
                                <div
                                    class="flex items-center justify-between p-4 bg-gray-50 cursor-pointer"
                                    @click="togglePlaybookExpand(playbook.id)"
                                >
                                    <div class="flex items-center">
                                        <svg
                                            class="h-5 w-5 text-gray-400 mr-3 transition-transform duration-200"
                                            :class="{ 'rotate-90': expandedPlaybooks.includes(playbook.id) }"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                        <div>
                                            <h4 class="font-medium text-gray-900">{{ playbook.name }}</h4>
                                            <p v-if="playbook.description" class="text-sm text-gray-500">{{ playbook.description }}</p>
                                        </div>
                                    </div>
                                    <span class="text-sm text-gray-500">
                                        {{ playbook.plays?.length || 0 }} Spielzüge
                                    </span>
                                </div>

                                <!-- Playbook Plays (Expandable) -->
                                <div v-if="expandedPlaybooks.includes(playbook.id)" class="p-4 border-t bg-white">
                                    <div v-if="playbook.plays && playbook.plays.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                        <div
                                            v-for="play in playbook.plays"
                                            :key="play.id"
                                            class="bg-gray-50 rounded-lg overflow-hidden"
                                        >
                                            <!-- TacticBoardViewer or Thumbnail -->
                                            <div class="aspect-video bg-gray-800">
                                                <TacticBoardViewer
                                                    v-if="play.play_data"
                                                    :play-data="play.play_data"
                                                    :court-type="play.court_type || 'half_horizontal'"
                                                    class="w-full h-full"
                                                />
                                                <div v-else class="w-full h-full flex items-center justify-center">
                                                    <svg class="h-8 w-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="p-2">
                                                <h5 class="text-sm font-medium text-gray-900 truncate">{{ play.name }}</h5>
                                                <p v-if="play.category" class="text-xs text-gray-500">{{ play.category }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <p v-else class="text-sm text-gray-500 text-center py-4">
                                        Keine Spielzüge in diesem Playbook
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else-if="!playbooksLoading && gamePlaybooks.length === 0 && !showPlaybookSection" class="text-center py-6 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Keine Playbooks verknüpft</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Verknüpfen Sie Playbooks für die Spielvorbereitung.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Game Statistics (if available) -->
                <div v-if="gameStats" class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Spielstatistiken</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Home Team Stats -->
                            <div>
                                <h4 class="font-medium text-gray-900 mb-2">{{ homeTeamName }}</h4>
                                <dl class="space-y-2">
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Feldwurfquote</dt>
                                        <dd class="text-sm font-medium">{{ gameStats.home.field_goal_percentage }}%</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">3-Punkte-Quote</dt>
                                        <dd class="text-sm font-medium">{{ gameStats.home.three_point_percentage }}%</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Freiwurfquote</dt>
                                        <dd class="text-sm font-medium">{{ gameStats.home.free_throw_percentage }}%</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Rebounds</dt>
                                        <dd class="text-sm font-medium">{{ gameStats.home.rebounds }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Assists</dt>
                                        <dd class="text-sm font-medium">{{ gameStats.home.assists }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <!-- Away Team Stats -->
                            <div>
                                <h4 class="font-medium text-gray-900 mb-2">{{ awayTeamName }}</h4>
                                <dl class="space-y-2">
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Feldwurfquote</dt>
                                        <dd class="text-sm font-medium">{{ gameStats.away.field_goal_percentage }}%</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">3-Punkte-Quote</dt>
                                        <dd class="text-sm font-medium">{{ gameStats.away.three_point_percentage }}%</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Freiwurfquote</dt>
                                        <dd class="text-sm font-medium">{{ gameStats.away.free_throw_percentage }}%</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Rebounds</dt>
                                        <dd class="text-sm font-medium">{{ gameStats.away.rebounds }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Assists</dt>
                                        <dd class="text-sm font-medium">{{ gameStats.away.assists }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>
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
import { computed, ref, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import axios from 'axios'
import AppLayout from '@/Layouts/AppLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'
import PlayerRegistration from '@/Components/PlayerRegistration.vue'
import PlaybookSelector from '@/Components/TacticBoard/PlaybookSelector.vue'
import TacticBoardViewer from '@/Components/TacticBoard/TacticBoardViewer.vue'
import RsvpButtons from '@/Components/Availability/RsvpButtons.vue'

const props = defineProps({
    game: Object,
    gameStats: Object,
    can: Object,
    currentPlayerRegistration: Object,
    playerAvailability: Object,
    availablePlaybooks: {
        type: Array,
        default: () => []
    }
})

// Delete functionality
const confirmingGameDeletion = ref(false)
const deleteForm = useForm({})

// Playbook functionality
const gamePlaybooks = ref([])
const showPlaybookSection = ref(false)
const playbooksLoading = ref(false)
const expandedPlaybooks = ref([])

// Load playbooks on mount
onMounted(async () => {
    await loadGamePlaybooks()
})

async function loadGamePlaybooks() {
    playbooksLoading.value = true
    try {
        const response = await axios.get(`/api/games/${props.game.id}/playbooks`)
        gamePlaybooks.value = response.data.data || []
    } catch (error) {
        console.error('Fehler beim Laden der Playbooks:', error)
    } finally {
        playbooksLoading.value = false
    }
}

async function attachPlaybook(playbookId) {
    try {
        await axios.post(`/api/games/${props.game.id}/playbooks`, {
            playbook_id: playbookId
        })
        await loadGamePlaybooks()
    } catch (error) {
        console.error('Fehler beim Hinzufügen des Playbooks:', error)
    }
}

async function detachPlaybook(playbookId) {
    try {
        await axios.delete(`/api/games/${props.game.id}/playbooks/${playbookId}`)
        await loadGamePlaybooks()
    } catch (error) {
        console.error('Fehler beim Entfernen des Playbooks:', error)
    }
}

function togglePlaybookExpand(playbookId) {
    const index = expandedPlaybooks.value.indexOf(playbookId)
    if (index === -1) {
        expandedPlaybooks.value.push(playbookId)
    } else {
        expandedPlaybooks.value.splice(index, 1)
    }
}

const deleteGameConfirmed = () => {
    deleteForm.delete(route('web.games.destroy', props.game.id), {
        onSuccess: () => {
            // Will redirect to games index
        }
    })
}

const refreshRegistrationData = () => {
    // Refresh the page to get updated registration data
    window.location.reload()
}

// Computed properties for safe team name access
const homeTeamName = computed(() => {
    if (props.game.home_team_name) {
        return props.game.home_team_name
    }
    return props.game.home_team?.name || 'Unbekanntes Team'
})

const awayTeamName = computed(() => {
    if (props.game.away_team_name) {
        return props.game.away_team_name
    }
    return props.game.away_team?.name || 'Unbekanntes Team'
})

const homeClubName = computed(() => {
    if (props.game.home_team?.club?.name) {
        return props.game.home_team.club.name
    }
    return ''
})

const awayClubName = computed(() => {
    if (props.game.away_team?.club?.name) {
        return props.game.away_team.club.name
    }
    return ''
})

function formatDateTime(dateString) {
    const date = new Date(dateString)
    return date.toLocaleString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

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

function getStatusClasses(status) {
    const classes = {
        'scheduled': 'bg-blue-100 text-blue-800',
        'live': 'bg-red-100 text-red-800',
        'finished': 'bg-green-100 text-green-800',
        'cancelled': 'bg-gray-100 text-gray-800',
        'postponed': 'bg-yellow-100 text-yellow-800'
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

function getGameTypeLabel(type) {
    const labels = {
        'regular_season': 'Liga',
        'playoff': 'Playoff',
        'championship': 'Meisterschaft',
        'friendly': 'Freundschaftsspiel',
        'tournament': 'Turnier',
        'scrimmage': 'Testspielserie'
    }
    return labels[type] || type
}
</script>