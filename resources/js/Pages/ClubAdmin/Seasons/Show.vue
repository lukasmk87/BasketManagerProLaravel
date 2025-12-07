<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SeasonStatusBadge from '@/Components/Seasons/SeasonStatusBadge.vue';
import SeasonStatisticsOverview from '@/Components/Seasons/SeasonStatisticsOverview.vue';
import SeasonActionsDropdown from '@/Components/Seasons/SeasonActionsDropdown.vue';
import SeasonTeamsOverview from '@/Components/Seasons/SeasonTeamsOverview.vue';
import SeasonGamesOverview from '@/Components/Seasons/SeasonGamesOverview.vue';
import {
    CalendarIcon,
    ChartBarIcon,
    UserGroupIcon,
    TrophyIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    club: {
        type: Object,
        required: true
    },
    season: {
        type: Object,
        required: true
    },
    teams: {
        type: Array,
        default: () => []
    },
    games: {
        type: Array,
        default: () => []
    },
    previousSeason: {
        type: Object,
        default: null
    },
    permissions: {
        type: Object,
        default: () => ({})
    }
});

const activeTab = ref('overview');

const tabs = [
    { id: 'overview', name: 'Übersicht', icon: ChartBarIcon },
    { id: 'teams', name: 'Teams', icon: UserGroupIcon, count: props.season.teams_count || 0 },
    { id: 'games', name: 'Spiele', icon: TrophyIcon, count: props.season.games_count || 0 },
    { id: 'statistics', name: 'Statistiken', icon: ChartBarIcon }
];

const canEdit = computed(() => {
    return props.permissions?.canEdit || false;
});

const seasonProgress = computed(() => {
    if (props.season.status !== 'active') return null;

    const start = new Date(props.season.start_date);
    const end = new Date(props.season.end_date);
    const today = new Date();

    const total = end - start;
    const elapsed = today - start;
    const progress = Math.min(Math.max(Math.round((elapsed / total) * 100), 0), 100);

    const remaining = Math.ceil((end - today) / (1000 * 60 * 60 * 24));

    return {
        percentage: progress,
        daysRemaining: remaining > 0 ? remaining : 0
    };
});

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('de-DE', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    });
};

const handleBack = () => {
    router.visit(route('club-admin.seasons.index'));
};

const handleEdit = () => {
    router.visit(route('club-admin.seasons.edit', props.season.id));
};

const handleActivate = () => {
    if (confirm(`Möchten Sie die Saison "${props.season.name}" wirklich aktivieren?`)) {
        router.post(route('club-admin.seasons.activate', props.season.id), {}, {
            preserveScroll: true
        });
    }
};

const handleComplete = () => {
    if (confirm(`Möchten Sie die Saison "${props.season.name}" wirklich abschließen?`)) {
        router.post(route('club-admin.seasons.complete', props.season.id), {
            create_snapshots: true
        }, {
            preserveScroll: true
        });
    }
};

const handleDelete = () => {
    if (confirm(`Möchten Sie die Saison "${props.season.name}" wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden!`)) {
        router.delete(route('club-admin.seasons.destroy', props.season.id));
    }
};
</script>

<template>
    <AppLayout :title="`${season.name} - Saison Details`">
        <Head :title="`${season.name} - ${club.name}`" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Back Button -->
                <div class="mb-4">
                    <button
                        @click="handleBack"
                        class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 transition"
                    >
                        <ArrowLeftIcon class="h-4 w-4 mr-1" />
                        Zurück zur Übersicht
                    </button>
                </div>

                <!-- Page Header -->
                <div class="bg-white shadow rounded-lg mb-6">
                    <div class="px-6 py-5">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-2">
                                    <h1 class="text-2xl font-bold text-gray-900">
                                        {{ season.name }}
                                    </h1>
                                    <SeasonStatusBadge :status="season.status" size="lg" />
                                    <span
                                        v-if="season.is_current"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                                    >
                                        Aktuelle Saison
                                    </span>
                                </div>

                                <div class="flex items-center space-x-4 text-sm text-gray-500">
                                    <div class="flex items-center">
                                        <CalendarIcon class="h-4 w-4 mr-1.5" />
                                        {{ formatDate(season.start_date) }} - {{ formatDate(season.end_date) }}
                                    </div>
                                    <div v-if="season.teams_count" class="flex items-center">
                                        <UserGroupIcon class="h-4 w-4 mr-1.5" />
                                        {{ season.teams_count }} Teams
                                    </div>
                                    <div v-if="season.games_count" class="flex items-center">
                                        <TrophyIcon class="h-4 w-4 mr-1.5" />
                                        {{ season.games_count }} Spiele
                                    </div>
                                </div>

                                <p v-if="season.description" class="mt-3 text-sm text-gray-600">
                                    {{ season.description }}
                                </p>

                                <!-- Progress Bar (for active seasons) -->
                                <div v-if="seasonProgress" class="mt-4">
                                    <div class="flex items-center justify-between text-xs text-gray-600 mb-1">
                                        <span>Saison-Fortschritt</span>
                                        <span>{{ seasonProgress.daysRemaining }} Tage verbleibend</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div
                                            class="bg-blue-600 h-2 rounded-full transition-all duration-500"
                                            :style="{ width: `${seasonProgress.percentage}%` }"
                                        ></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions Dropdown -->
                            <div class="ml-4">
                                <SeasonActionsDropdown
                                    :season="season"
                                    :club="club"
                                    :permissions="permissions"
                                    @edit="handleEdit"
                                    @activate="handleActivate"
                                    @complete="handleComplete"
                                    @delete="handleDelete"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Tabs Navigation -->
                    <div class="border-t border-gray-200">
                        <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                            <button
                                v-for="tab in tabs"
                                :key="tab.id"
                                @click="activeTab = tab.id"
                                :class="[
                                    activeTab === tab.id
                                        ? 'border-blue-500 text-blue-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    'group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm transition'
                                ]"
                            >
                                <component
                                    :is="tab.icon"
                                    :class="[
                                        activeTab === tab.id ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500',
                                        '-ml-0.5 mr-2 h-5 w-5'
                                    ]"
                                />
                                <span>{{ tab.name }}</span>
                                <span
                                    v-if="tab.count !== undefined"
                                    :class="[
                                        activeTab === tab.id
                                            ? 'bg-blue-100 text-blue-600'
                                            : 'bg-gray-100 text-gray-900',
                                        'ml-2 py-0.5 px-2.5 rounded-full text-xs font-medium'
                                    ]"
                                >
                                    {{ tab.count }}
                                </span>
                            </button>
                        </nav>
                    </div>
                </div>

                <!-- Tab Content -->
                <div class="bg-white shadow rounded-lg">
                    <!-- Overview Tab -->
                    <div v-show="activeTab === 'overview'" class="p-6">
                        <div class="space-y-8">
                            <!-- Season Statistics -->
                            <SeasonStatisticsOverview
                                :season="season"
                                :previous-season="previousSeason"
                            />

                            <!-- Season Details Grid -->
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Saison-Details</h3>
                                <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2 lg:grid-cols-3">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Startdatum</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ formatDate(season.start_date) }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Enddatum</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ formatDate(season.end_date) }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                                        <dd class="mt-1">
                                            <SeasonStatusBadge :status="season.status" />
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Erstellt am</dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ formatDate(season.created_at) }}
                                        </dd>
                                    </div>
                                    <div v-if="season.completed_at">
                                        <dt class="text-sm font-medium text-gray-500">Abgeschlossen am</dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ formatDate(season.completed_at) }}
                                        </dd>
                                    </div>
                                    <div v-if="season.activated_at">
                                        <dt class="text-sm font-medium text-gray-500">Aktiviert am</dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ formatDate(season.activated_at) }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                            <!-- Quick Actions -->
                            <div v-if="canEdit && season.status !== 'completed'">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Schnellaktionen</h3>
                                <div class="flex flex-wrap gap-3">
                                    <button
                                        v-if="season.status === 'draft'"
                                        @click="handleActivate"
                                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                    >
                                        Saison aktivieren
                                    </button>
                                    <button
                                        v-if="season.status === 'active'"
                                        @click="handleComplete"
                                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                    >
                                        Saison abschließen
                                    </button>
                                    <button
                                        @click="handleEdit"
                                        class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                    >
                                        Saison bearbeiten
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Teams Tab -->
                    <div v-show="activeTab === 'teams'" class="p-6">
                        <SeasonTeamsOverview
                            :season="season"
                            :club="club"
                            :teams="teams"
                            :permissions="permissions"
                        />
                    </div>

                    <!-- Games Tab -->
                    <div v-show="activeTab === 'games'" class="p-6">
                        <SeasonGamesOverview
                            :season="season"
                            :club="club"
                            :games="games"
                            :permissions="permissions"
                        />
                    </div>

                    <!-- Statistics Tab -->
                    <div v-show="activeTab === 'statistics'" class="p-6">
                        <div class="text-center py-12">
                            <ChartBarIcon class="mx-auto h-12 w-12 text-gray-400" />
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Erweiterte Statistiken</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Detaillierte Statistik-Ansicht wird in Phase 2.4 implementiert.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
