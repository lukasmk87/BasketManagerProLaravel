<script setup>
import { computed } from 'vue';
import StatisticsWidget from '../Basketball/StatisticsWidget.vue';

const props = defineProps({
    season: {
        type: Object,
        required: true
    },
    previousSeason: {
        type: Object,
        default: null
    },
    loading: {
        type: Boolean,
        default: false
    }
});

const teamsStats = computed(() => {
    const current = props.season.teams_count || 0;
    const previous = props.previousSeason?.teams_count || 0;

    let trend = null;
    if (previous > 0) {
        const change = current - previous;
        const changePercent = Math.round((change / previous) * 100);

        trend = {
            value: change,
            label: change >= 0 ? `+${changePercent}%` : `${changePercent}%`,
            direction: change >= 0 ? 'up' : 'down',
            positive: change >= 0
        };
    }

    return {
        value: current,
        trend
    };
});

const gamesStats = computed(() => {
    const current = props.season.games_count || 0;
    const previous = props.previousSeason?.games_count || 0;

    let trend = null;
    if (previous > 0) {
        const change = current - previous;
        const changePercent = Math.round((change / previous) * 100);

        trend = {
            value: change,
            label: change >= 0 ? `+${changePercent}%` : `${changePercent}%`,
            direction: change >= 0 ? 'up' : 'down',
            positive: change >= 0
        };
    }

    return {
        value: current,
        trend
    };
});

const playersStats = computed(() => {
    const current = props.season.players_count || 0;
    const previous = props.previousSeason?.players_count || 0;

    let trend = null;
    if (previous > 0) {
        const change = current - previous;
        const changePercent = Math.round((change / previous) * 100);

        trend = {
            value: change,
            label: change >= 0 ? `+${changePercent}%` : `${changePercent}%`,
            direction: change >= 0 ? 'up' : 'down',
            positive: change >= 0
        };
    }

    return {
        value: current,
        trend
    };
});

const averageScoreStats = computed(() => {
    const current = props.season.average_score || 0;
    const previous = props.previousSeason?.average_score || 0;

    let trend = null;
    if (previous > 0) {
        const change = current - previous;
        const changePercent = Math.round((change / previous) * 100);

        trend = {
            value: change.toFixed(1),
            label: change >= 0 ? `+${changePercent}%` : `${changePercent}%`,
            direction: change >= 0 ? 'up' : 'down',
            positive: change >= 0
        };
    }

    return {
        value: current.toFixed(1),
        trend
    };
});
</script>

<template>
    <div>
        <h3 class="text-lg font-medium text-gray-900 mb-4">Saison-Statistiken</h3>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Teams -->
            <StatisticsWidget
                title="Teams"
                :value="teamsStats.value"
                subtitle="Anzahl Teams in Saison"
                icon="users"
                color="blue"
                :trend="teamsStats.trend"
                :loading="loading"
            />

            <!-- Spiele -->
            <StatisticsWidget
                title="Spiele"
                :value="gamesStats.value"
                subtitle="Gespielte & geplante Spiele"
                icon="trophy"
                color="green"
                :trend="gamesStats.trend"
                :loading="loading"
            />

            <!-- Spieler -->
            <StatisticsWidget
                title="Spieler"
                :value="playersStats.value"
                subtitle="Aktive Spieler"
                icon="user-group"
                color="indigo"
                :trend="playersStats.trend"
                :loading="loading"
            />

            <!-- Durchschnittliche Punkte -->
            <StatisticsWidget
                title="Ø Punkte"
                :value="averageScoreStats.value"
                subtitle="Pro Spiel"
                icon="chart-bar"
                color="orange"
                :trend="averageScoreStats.trend"
                :loading="loading"
            />
        </div>

        <!-- Zusätzliche Infos -->
        <div v-if="season.status === 'completed'" class="mt-4 p-4 bg-blue-50 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm text-blue-700">
                        Diese Saison wurde am {{ new Date(season.updated_at).toLocaleDateString('de-DE') }} abgeschlossen.
                        Alle Statistiken wurden archiviert und können jederzeit eingesehen werden.
                    </p>
                </div>
            </div>
        </div>

        <div v-else-if="season.status === 'draft'" class="mt-4 p-4 bg-gray-50 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm text-gray-700">
                        Diese Saison ist noch im Entwurfsmodus. Aktiviere die Saison, um mit dem Spielbetrieb zu beginnen.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
