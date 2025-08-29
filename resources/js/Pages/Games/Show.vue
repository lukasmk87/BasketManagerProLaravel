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
                                <div v-if="game.location">
                                    <dt class="text-sm font-medium text-gray-500">Spielort</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ game.location }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Spieltyp</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ getGameTypeLabel(game.game_type) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Saison</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ game.season }}</dd>
                                </div>
                                <div v-if="game.league">
                                    <dt class="text-sm font-medium text-gray-500">Liga</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ game.league }}</dd>
                                </div>
                                <div v-if="game.round">
                                    <dt class="text-sm font-medium text-gray-500">Spieltag/Runde</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ game.round }}</dd>
                                </div>
                            </dl>
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
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'

const props = defineProps({
    game: Object,
    gameStats: Object,
    can: Object,
})

// Delete functionality
const confirmingGameDeletion = ref(false)
const deleteForm = useForm({})

const deleteGameConfirmed = () => {
    deleteForm.delete(route('web.games.destroy', props.game.id), {
        onSuccess: () => {
            // Will redirect to games index
        }
    })
}

// Computed properties for safe team name access
const homeTeamName = computed(() => {
    if (props.game.home_team_name) {
        return props.game.home_team_name
    }
    return props.game.homeTeam?.name || 'Unbekanntes Team'
})

const awayTeamName = computed(() => {
    if (props.game.away_team_name) {
        return props.game.away_team_name
    }
    return props.game.awayTeam?.name || 'Unbekanntes Team'
})

const homeClubName = computed(() => {
    if (props.game.homeTeam?.club?.name) {
        return props.game.homeTeam.club.name
    }
    return ''
})

const awayClubName = computed(() => {
    if (props.game.awayTeam?.club?.name) {
        return props.game.awayTeam.club.name
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
        'league': 'Liga',
        'friendly': 'Freundschaftsspiel',
        'playoff': 'Playoff',
        'tournament': 'Turnier'
    }
    return labels[type] || type
}
</script>