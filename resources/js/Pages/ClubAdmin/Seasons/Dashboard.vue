<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SeasonCard from '@/Components/Seasons/SeasonCard.vue';
import SeasonStatisticsOverview from '@/Components/Seasons/SeasonStatisticsOverview.vue';
import { PlusIcon, ChartBarIcon, CalendarIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    club: {
        type: Object,
        required: true
    },
    seasons: {
        type: Array,
        default: () => []
    },
    currentSeason: {
        type: Object,
        default: null
    },
    statistics: {
        type: Object,
        default: () => ({})
    }
});

const viewMode = ref('grid'); // 'grid' or 'list'

const activeSeason = computed(() => {
    return props.seasons.find(s => s.status === 'active') || props.currentSeason;
});

const draftSeasons = computed(() => {
    return props.seasons.filter(s => s.status === 'draft');
});

const completedSeasons = computed(() => {
    return props.seasons.filter(s => s.status === 'completed');
});

const previousSeason = computed(() => {
    const completed = completedSeasons.value;
    if (completed.length === 0) return null;

    // Sort by end_date descending and get the first one
    return completed.sort((a, b) => new Date(b.end_date) - new Date(a.end_date))[0];
});

const canManageSeasons = computed(() => {
    return props.$page?.props?.auth?.permissions?.includes('manage seasons') || false;
});

const canStartNewSeason = computed(() => {
    return props.$page?.props?.auth?.permissions?.includes('start new season') || false;
});

const handleSeasonClick = (season) => {
    router.visit(route('club.seasons.show', { club: props.club.id, season: season.id }));
};

const handleEditSeason = (season) => {
    router.visit(route('club.seasons.edit', { club: props.club.id, season: season.id }));
};

const handleActivateSeason = (season) => {
    if (confirm(`Möchten Sie die Saison "${season.name}" wirklich aktivieren? Dies deaktiviert alle anderen Saisons.`)) {
        router.post(route('club.seasons.activate', { club: props.club.id, season: season.id }), {}, {
            preserveScroll: true,
            onSuccess: () => {
                // Success message wird von Laravel Flash Message gehandelt
            }
        });
    }
};

const handleCompleteSeason = (season) => {
    if (confirm(`Möchten Sie die Saison "${season.name}" wirklich abschließen? Dies erstellt einen Statistik-Snapshot und beendet die Saison.`)) {
        router.post(route('club.seasons.complete', { club: props.club.id, season: season.id }), {
            create_snapshots: true
        }, {
            preserveScroll: true,
            onSuccess: () => {
                // Success message
            }
        });
    }
};

const handleDeleteSeason = (season) => {
    if (confirm(`Möchten Sie die Saison "${season.name}" wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden!`)) {
        router.delete(route('club.seasons.destroy', { club: props.club.id, season: season.id }), {
            preserveScroll: true,
            onSuccess: () => {
                // Success message
            }
        });
    }
};

const startNewSeason = () => {
    router.visit(route('club.seasons.wizard', { club: props.club.id }));
};

const compareSeasons = () => {
    router.visit(route('club.seasons.compare', { club: props.club.id }));
};
</script>

<template>
    <AppLayout title="Saison-Management">
        <Head :title="`Saison-Management - ${club.name}`" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Page Header -->
                <div class="md:flex md:items-center md:justify-between mb-8">
                    <div class="flex-1 min-w-0">
                        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                            Saison-Management
                        </h2>
                        <p class="mt-1 text-sm text-gray-500">
                            Verwalten Sie alle Saisons für {{ club.name }}
                        </p>
                    </div>
                    <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
                        <button
                            v-if="seasons.length >= 2"
                            type="button"
                            @click="compareSeasons"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            <ChartBarIcon class="-ml-1 mr-2 h-5 w-5 text-gray-500" />
                            Vergleichen
                        </button>
                        <button
                            v-if="canStartNewSeason"
                            type="button"
                            @click="startNewSeason"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            <PlusIcon class="-ml-1 mr-2 h-5 w-5" />
                            Neue Saison
                        </button>
                    </div>
                </div>

                <!-- Aktuelle Saison - Highlighted -->
                <div v-if="activeSeason" class="mb-8">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Aktuelle Saison</h3>
                        <Link
                            :href="route('club.seasons.show', { club: club.id, season: activeSeason.id })"
                            class="text-sm font-medium text-blue-600 hover:text-blue-500"
                        >
                            Details anzeigen →
                        </Link>
                    </div>

                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-6 rounded-lg shadow-md border border-blue-200">
                        <SeasonCard
                            :season="activeSeason"
                            :show-stats="true"
                            :show-actions="canManageSeasons"
                            @click="handleSeasonClick"
                            @edit="handleEditSeason"
                            @complete="handleCompleteSeason"
                        />
                    </div>

                    <!-- Statistiken für aktuelle Saison -->
                    <div class="mt-6">
                        <SeasonStatisticsOverview
                            :season="activeSeason"
                            :previous-season="previousSeason"
                        />
                    </div>
                </div>

                <!-- Entwurfs-Saisons -->
                <div v-if="draftSeasons.length > 0" class="mb-8">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Entwürfe</h3>
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        <SeasonCard
                            v-for="season in draftSeasons"
                            :key="season.id"
                            :season="season"
                            :show-stats="false"
                            :show-actions="canManageSeasons"
                            @click="handleSeasonClick"
                            @edit="handleEditSeason"
                            @activate="handleActivateSeason"
                            @delete="handleDeleteSeason"
                        />
                    </div>
                </div>

                <!-- Abgeschlossene Saisons -->
                <div v-if="completedSeasons.length > 0" class="mb-8">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Abgeschlossene Saisons</h3>
                        <div class="flex space-x-2">
                            <button
                                @click="viewMode = 'grid'"
                                :class="viewMode === 'grid' ? 'bg-gray-200' : 'bg-white'"
                                class="p-2 border border-gray-300 rounded-md hover:bg-gray-50"
                            >
                                <svg class="h-5 w-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                            </button>
                            <button
                                @click="viewMode = 'list'"
                                :class="viewMode === 'list' ? 'bg-gray-200' : 'bg-white'"
                                class="p-2 border border-gray-300 rounded-md hover:bg-gray-50"
                            >
                                <svg class="h-5 w-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div v-if="viewMode === 'grid'" class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        <SeasonCard
                            v-for="season in completedSeasons"
                            :key="season.id"
                            :season="season"
                            :show-stats="true"
                            :show-actions="canManageSeasons"
                            @click="handleSeasonClick"
                            @edit="handleEditSeason"
                            @delete="handleDeleteSeason"
                        />
                    </div>

                    <div v-else class="space-y-3">
                        <SeasonCard
                            v-for="season in completedSeasons"
                            :key="season.id"
                            :season="season"
                            :show-stats="true"
                            :show-actions="canManageSeasons"
                            :compact="true"
                            @click="handleSeasonClick"
                            @edit="handleEditSeason"
                            @delete="handleDeleteSeason"
                        />
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="seasons.length === 0" class="text-center py-12">
                    <CalendarIcon class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Keine Saisons</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Erstellen Sie Ihre erste Saison, um mit dem Management zu beginnen.
                    </p>
                    <div class="mt-6">
                        <button
                            v-if="canStartNewSeason"
                            type="button"
                            @click="startNewSeason"
                            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            <PlusIcon class="-ml-1 mr-2 h-5 w-5" />
                            Neue Saison erstellen
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
