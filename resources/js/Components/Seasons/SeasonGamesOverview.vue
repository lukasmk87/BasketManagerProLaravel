<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { PlusIcon, CalendarIcon, TrophyIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    season: {
        type: Object,
        required: true
    },
    club: {
        type: Object,
        required: true
    },
    games: {
        type: Array,
        default: () => []
    },
    permissions: {
        type: Object,
        default: () => ({})
    },
    loading: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['schedule-game', 'view-game', 'view-live-scoring']);

const activeFilter = ref('all'); // 'all', 'scheduled', 'live', 'finished'

const canManageGames = computed(() => {
    return props.permissions?.canEdit ||
           props.permissions?.canManage ||
           false;
});

const filteredGames = computed(() => {
    if (!props.games || !Array.isArray(props.games)) {
        return [];
    }

    if (activeFilter.value === 'all') {
        return props.games;
    }

    return props.games.filter(game => {
        if (activeFilter.value === 'live') {
            return game.status === 'live' || game.status === 'halftime' || game.status === 'overtime';
        }
        if (activeFilter.value === 'scheduled') {
            return game.status === 'scheduled';
        }
        if (activeFilter.value === 'finished') {
            return game.status === 'finished';
        }
        return true;
    });
});

const upcomingGames = computed(() => {
    return filteredGames.value
        .filter(game => game.status === 'scheduled')
        .sort((a, b) => new Date(a.scheduled_at) - new Date(b.scheduled_at))
        .slice(0, 5);
});

const recentGames = computed(() => {
    return filteredGames.value
        .filter(game => game.status === 'finished')
        .sort((a, b) => new Date(b.scheduled_at) - new Date(a.scheduled_at))
        .slice(0, 5);
});

const liveGames = computed(() => {
    return filteredGames.value
        .filter(game => game.status === 'live' || game.status === 'halftime' || game.status === 'overtime');
});

const gameCount = computed(() => {
    return props.season.games_count || filteredGames.value.length || 0;
});

const formatDateTime = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('de-DE', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getStatusBadge = (status) => {
    const statusMap = {
        scheduled: { text: 'Geplant', color: 'bg-gray-100 text-gray-800' },
        live: { text: 'Live', color: 'bg-red-100 text-red-800' },
        halftime: { text: 'Halbzeit', color: 'bg-yellow-100 text-yellow-800' },
        overtime: { text: 'Verlängerung', color: 'bg-orange-100 text-orange-800' },
        finished: { text: 'Beendet', color: 'bg-green-100 text-green-800' },
        cancelled: { text: 'Abgesagt', color: 'bg-red-100 text-red-800' },
        postponed: { text: 'Verschoben', color: 'bg-purple-100 text-purple-800' }
    };
    return statusMap[status] || statusMap.scheduled;
};

const getGameType = (type) => {
    const typeMap = {
        regular_season: 'Liga',
        playoff: 'Playoff',
        championship: 'Finale',
        friendly: 'Freundschaftsspiel',
        tournament: 'Turnier',
        scrimmage: 'Training'
    };
    return typeMap[type] || type;
};

const handleScheduleGame = () => {
    emit('schedule-game');
    router.visit(route('club.games.create', {
        club: props.club.id,
        season: props.season.id
    }));
};

const handleViewGame = (game) => {
    emit('view-game', game);
    router.visit(route('club.games.show', {
        club: props.club.id,
        game: game.id
    }));
};

const handleViewLiveScoring = (game) => {
    emit('view-live-scoring', game);
    router.visit(route('club.games.live', {
        club: props.club.id,
        game: game.id
    }));
};
</script>

<template>
    <div>
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-medium text-gray-900">Spiele</h3>
                <p class="mt-1 text-sm text-gray-500">
                    {{ gameCount }} {{ gameCount === 1 ? 'Spiel' : 'Spiele' }} in dieser Saison
                </p>
            </div>

            <div class="flex items-center space-x-3">
                <!-- Filter Buttons -->
                <div class="flex items-center bg-gray-100 rounded-md p-1">
                    <button
                        @click="activeFilter = 'all'"
                        :class="[
                            'px-3 py-1.5 text-xs font-medium rounded transition-colors',
                            activeFilter === 'all'
                                ? 'bg-white text-gray-900 shadow-sm'
                                : 'text-gray-600 hover:text-gray-900'
                        ]"
                    >
                        Alle
                    </button>
                    <button
                        @click="activeFilter = 'scheduled'"
                        :class="[
                            'px-3 py-1.5 text-xs font-medium rounded transition-colors',
                            activeFilter === 'scheduled'
                                ? 'bg-white text-gray-900 shadow-sm'
                                : 'text-gray-600 hover:text-gray-900'
                        ]"
                    >
                        Geplant
                    </button>
                    <button
                        @click="activeFilter = 'live'"
                        :class="[
                            'px-3 py-1.5 text-xs font-medium rounded transition-colors',
                            activeFilter === 'live'
                                ? 'bg-white text-gray-900 shadow-sm'
                                : 'text-gray-600 hover:text-gray-900'
                        ]"
                    >
                        Live
                    </button>
                    <button
                        @click="activeFilter = 'finished'"
                        :class="[
                            'px-3 py-1.5 text-xs font-medium rounded transition-colors',
                            activeFilter === 'finished'
                                ? 'bg-white text-gray-900 shadow-sm'
                                : 'text-gray-600 hover:text-gray-900'
                        ]"
                    >
                        Beendet
                    </button>
                </div>

                <!-- Schedule Game Button -->
                <button
                    v-if="canManageGames && season.status !== 'completed'"
                    type="button"
                    @click="handleScheduleGame"
                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition"
                >
                    <PlusIcon class="h-4 w-4 mr-1.5" />
                    Spiel planen
                </button>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-12">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        </div>

        <div v-else class="space-y-6">
            <!-- Live Games (if any) -->
            <div v-if="liveGames.length > 0">
                <h4 class="text-sm font-medium text-gray-900 mb-3 flex items-center">
                    <span class="inline-flex items-center">
                        <span class="flex h-2 w-2 relative mr-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                        </span>
                        Live Spiele
                    </span>
                </h4>
                <div class="space-y-3">
                    <div
                        v-for="game in liveGames"
                        :key="game.id"
                        class="bg-white border-2 border-red-200 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer"
                        @click="handleViewLiveScoring(game)"
                    >
                        <!-- Game card content (same as below) -->
                        <div class="flex items-center justify-between mb-3">
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                :class="getStatusBadge(game.status).color"
                            >
                                {{ getStatusBadge(game.status).text }}
                            </span>
                            <span class="text-xs text-gray-500">{{ getGameType(game.type) }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <!-- Home Team -->
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ game.home_team?.name || 'TBD' }}
                                </div>
                                <div class="text-xs text-gray-500">Heim</div>
                            </div>

                            <!-- Score -->
                            <div class="px-4">
                                <div class="text-2xl font-bold text-gray-900">
                                    {{ game.home_team_score || 0 }} : {{ game.away_team_score || 0 }}
                                </div>
                            </div>

                            <!-- Away Team -->
                            <div class="flex-1 text-right">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ game.away_team?.name || game.external_away_team || 'TBD' }}
                                </div>
                                <div class="text-xs text-gray-500">Gast</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Games -->
            <div v-if="upcomingGames.length > 0">
                <h4 class="text-sm font-medium text-gray-900 mb-3">Kommende Spiele</h4>
                <div class="space-y-3">
                    <div
                        v-for="game in upcomingGames"
                        :key="game.id"
                        class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer"
                        @click="handleViewGame(game)"
                    >
                        <div class="flex items-center justify-between mb-3">
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                :class="getStatusBadge(game.status).color"
                            >
                                {{ getStatusBadge(game.status).text }}
                            </span>
                            <span class="text-xs text-gray-500">{{ getGameType(game.type) }}</span>
                        </div>

                        <div class="flex items-center justify-between mb-2">
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ game.home_team?.name || 'TBD' }}
                                </div>
                                <div class="text-xs text-gray-500">Heim</div>
                            </div>

                            <div class="px-4 text-gray-400 font-medium">vs</div>

                            <div class="flex-1 text-right">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ game.away_team?.name || game.external_away_team || 'TBD' }}
                                </div>
                                <div class="text-xs text-gray-500">Gast</div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-xs text-gray-500">
                            <div class="flex items-center">
                                <CalendarIcon class="h-3.5 w-3.5 mr-1" />
                                {{ formatDateTime(game.scheduled_at) }}
                            </div>
                            <div v-if="game.venue">{{ game.venue }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Games -->
            <div v-if="recentGames.length > 0">
                <h4 class="text-sm font-medium text-gray-900 mb-3">Letzte Spiele</h4>
                <div class="space-y-3">
                    <div
                        v-for="game in recentGames"
                        :key="game.id"
                        class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer"
                        @click="handleViewGame(game)"
                    >
                        <div class="flex items-center justify-between mb-3">
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                :class="getStatusBadge(game.status).color"
                            >
                                {{ getStatusBadge(game.status).text }}
                            </span>
                            <span class="text-xs text-gray-500">{{ getGameType(game.type) }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ game.home_team?.name || 'TBD' }}
                                </div>
                                <div class="text-xs text-gray-500">Heim</div>
                            </div>

                            <div class="px-4">
                                <div class="text-2xl font-bold text-gray-900">
                                    {{ game.home_team_score || 0 }} : {{ game.away_team_score || 0 }}
                                </div>
                            </div>

                            <div class="flex-1 text-right">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ game.away_team?.name || game.external_away_team || 'TBD' }}
                                </div>
                                <div class="text-xs text-gray-500">Gast</div>
                            </div>
                        </div>

                        <div class="text-xs text-gray-500 text-center mt-2">
                            {{ formatDateTime(game.scheduled_at) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="filteredGames.length === 0" class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                <TrophyIcon class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-medium text-gray-900">Keine Spiele</h3>
                <p class="mt-1 text-sm text-gray-500">
                    <span v-if="activeFilter !== 'all'">
                        Keine Spiele mit Status "{{ getStatusBadge(activeFilter).text }}" gefunden.
                    </span>
                    <span v-else>
                        Diese Saison hat noch keine geplanten Spiele.
                    </span>
                </p>
                <div v-if="canManageGames && season.status !== 'completed'" class="mt-6">
                    <button
                        type="button"
                        @click="handleScheduleGame"
                        class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <PlusIcon class="h-4 w-4 mr-2" />
                        Erstes Spiel planen
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
