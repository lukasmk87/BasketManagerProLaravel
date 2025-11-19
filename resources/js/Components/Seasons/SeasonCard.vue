<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import SeasonStatusBadge from './SeasonStatusBadge.vue';
import { CalendarIcon, UserGroupIcon, TrophyIcon, ChartBarIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    season: {
        type: Object,
        required: true
    },
    showStats: {
        type: Boolean,
        default: true
    },
    showActions: {
        type: Boolean,
        default: false
    },
    compact: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['click', 'edit', 'activate', 'complete', 'delete']);

const seasonStats = computed(() => {
    return {
        teams: props.season.teams_count || 0,
        games: props.season.games_count || 0,
        players: props.season.players_count || 0,
        averageScore: props.season.average_score || 0
    };
});

const dateRange = computed(() => {
    const start = new Date(props.season.start_date);
    const end = new Date(props.season.end_date);

    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    const startFormatted = start.toLocaleDateString('de-DE', options);
    const endFormatted = end.toLocaleDateString('de-DE', options);

    return `${startFormatted} - ${endFormatted}`;
});

const daysRemaining = computed(() => {
    if (props.season.status !== 'active') return null;

    const end = new Date(props.season.end_date);
    const today = new Date();
    const diff = Math.ceil((end - today) / (1000 * 60 * 60 * 24));

    if (diff < 0) return null;
    return diff;
});

const progress = computed(() => {
    const start = new Date(props.season.start_date);
    const end = new Date(props.season.end_date);
    const today = new Date();

    const total = end - start;
    const elapsed = today - start;

    const percentage = Math.min(Math.max(Math.round((elapsed / total) * 100), 0), 100);
    return percentage;
});

const handleClick = () => {
    emit('click', props.season);
};

const handleEdit = (event) => {
    event.stopPropagation();
    emit('edit', props.season);
};

const handleActivate = (event) => {
    event.stopPropagation();
    emit('activate', props.season);
};

const handleComplete = (event) => {
    event.stopPropagation();
    emit('complete', props.season);
};

const handleDelete = (event) => {
    event.stopPropagation();
    emit('delete', props.season);
};
</script>

<template>
    <div class="bg-white overflow-hidden shadow rounded-lg hover:shadow-md transition-shadow duration-200 cursor-pointer"
         :class="{ 'hover:scale-105 transform transition-transform duration-200': !compact, 'border-l-4 border-blue-500': season.is_current }"
         @click="handleClick">

        <!-- Compact Version -->
        <div v-if="compact" class="p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <CalendarIcon class="h-8 w-8 text-blue-500" />
                    <div>
                        <h3 class="text-sm font-medium text-gray-900">{{ season.name }}</h3>
                        <p class="text-xs text-gray-500">{{ dateRange }}</p>
                    </div>
                </div>
                <SeasonStatusBadge :status="season.status" />
            </div>
        </div>

        <!-- Full Version -->
        <div v-else>
            <!-- Header -->
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-2">
                            <h3 class="text-lg font-medium text-gray-900">{{ season.name }}</h3>
                            <span v-if="season.is_current" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Aktuelle Saison
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-gray-500 flex items-center">
                            <CalendarIcon class="h-4 w-4 mr-1" />
                            {{ dateRange }}
                        </p>
                        <p v-if="season.description" class="mt-2 text-sm text-gray-600">
                            {{ season.description }}
                        </p>
                    </div>
                    <SeasonStatusBadge :status="season.status" />
                </div>

                <!-- Progress Bar (nur für aktive Saisons) -->
                <div v-if="season.status === 'active'" class="mt-4">
                    <div class="flex items-center justify-between text-xs text-gray-600 mb-1">
                        <span>Fortschritt</span>
                        <span>{{ progress }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full transition-all duration-500" :style="{ width: progress + '%' }"></div>
                    </div>
                    <p v-if="daysRemaining !== null" class="mt-1 text-xs text-gray-500">
                        {{ daysRemaining }} Tage verbleibend
                    </p>
                </div>
            </div>

            <!-- Stats -->
            <div v-if="showStats" class="border-t border-gray-200 px-4 py-5 sm:p-6">
                <dl class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="flex flex-col">
                        <dt class="text-xs font-medium text-gray-500 flex items-center">
                            <UserGroupIcon class="h-4 w-4 mr-1" />
                            Teams
                        </dt>
                        <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ seasonStats.teams }}</dd>
                    </div>
                    <div class="flex flex-col">
                        <dt class="text-xs font-medium text-gray-500 flex items-center">
                            <TrophyIcon class="h-4 w-4 mr-1" />
                            Spiele
                        </dt>
                        <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ seasonStats.games }}</dd>
                    </div>
                    <div class="flex flex-col">
                        <dt class="text-xs font-medium text-gray-500 flex items-center">
                            <UserGroupIcon class="h-4 w-4 mr-1" />
                            Spieler
                        </dt>
                        <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ seasonStats.players }}</dd>
                    </div>
                    <div class="flex flex-col">
                        <dt class="text-xs font-medium text-gray-500 flex items-center">
                            <ChartBarIcon class="h-4 w-4 mr-1" />
                            Ø Punkte
                        </dt>
                        <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ seasonStats.averageScore.toFixed(1) }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Actions -->
            <div v-if="showActions" class="border-t border-gray-200 px-4 py-4 sm:px-6">
                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        @click="handleEdit"
                        class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        Bearbeiten
                    </button>

                    <button
                        v-if="season.status === 'draft'"
                        type="button"
                        @click="handleActivate"
                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                    >
                        Aktivieren
                    </button>

                    <button
                        v-if="season.status === 'active'"
                        type="button"
                        @click="handleComplete"
                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        Abschließen
                    </button>

                    <button
                        v-if="season.status !== 'active'"
                        type="button"
                        @click="handleDelete"
                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                    >
                        Löschen
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
