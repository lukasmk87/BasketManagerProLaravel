<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SeasonComparisonSelector from '@/Components/Seasons/SeasonComparisonSelector.vue';
import SeasonComparisonChart from '@/Components/Seasons/SeasonComparisonChart.vue';
import SeasonComparisonTable from '@/Components/Seasons/SeasonComparisonTable.vue';
import SeasonTimeline from '@/Components/Seasons/SeasonTimeline.vue';
import {
    ChartBarIcon,
    TableCellsIcon,
    CalendarIcon,
    DocumentChartBarIcon,
    ArrowDownTrayIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    club: {
        type: Object,
        required: true
    },
    availableSeasons: {
        type: Array,
        default: () => []
    },
    initialSelectedSeasons: {
        type: Array,
        default: () => []
    },
    permissions: {
        type: Object,
        default: () => ({})
    }
});

const selectedSeasonIds = ref(props.initialSelectedSeasons || []);
const activeTab = ref('overview');

const seasonColors = [
    { bg: 'bg-blue-100', text: 'text-blue-800', border: 'border-blue-500', hex: '#3B82F6' },
    { bg: 'bg-green-100', text: 'text-green-800', border: 'border-green-500', hex: '#10B981' },
    { bg: 'bg-orange-100', text: 'text-orange-800', border: 'border-orange-500', hex: '#F59E0B' },
    { bg: 'bg-purple-100', text: 'text-purple-800', border: 'border-purple-500', hex: '#8B5CF6' }
];

const tabs = [
    { key: 'overview', label: 'Übersicht', icon: DocumentChartBarIcon },
    { key: 'charts', label: 'Charts', icon: ChartBarIcon },
    { key: 'table', label: 'Tabelle', icon: TableCellsIcon },
    { key: 'timeline', label: 'Timeline', icon: CalendarIcon }
];

const selectedSeasons = computed(() => {
    return selectedSeasonIds.value
        .map(id => props.availableSeasons.find(s => s.id === id))
        .filter(Boolean)
        .sort((a, b) => new Date(b.start_date) - new Date(a.start_date));
});

const hasEnoughSeasons = computed(() => {
    return selectedSeasons.value.length >= 2;
});

const canExport = computed(() => {
    return props.permissions?.exportSeasons || props.permissions?.manageSeasons || false;
});

const handleSeasonSelectionChange = (newSelection) => {
    selectedSeasonIds.value = newSelection;

    // Update URL query params
    router.get(
        route('club.seasons.compare', { club: props.club.id }),
        { seasons: newSelection },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true
        }
    );
};

const handleExportCSV = () => {
    if (!canExport.value) return;

    // Build CSV data
    const metrics = [
        { key: 'name', label: 'Saison' },
        { key: 'start_date', label: 'Start' },
        { key: 'end_date', label: 'Ende' },
        { key: 'teams_count', label: 'Teams' },
        { key: 'games_count', label: 'Spiele' },
        { key: 'players_count', label: 'Spieler' },
        { key: 'avg_score', label: 'Ø Punkte' },
        { key: 'avg_assists', label: 'Ø Assists' },
        { key: 'avg_rebounds', label: 'Ø Rebounds' },
        { key: 'field_goal_percentage', label: 'FG%' },
        { key: 'three_point_percentage', label: '3P%' },
        { key: 'free_throw_percentage', label: 'FT%' },
        { key: 'win_percentage', label: 'Siegquote' }
    ];

    // Headers
    let csvContent = metrics.map(m => m.label).join(',') + '\n';

    // Data rows
    selectedSeasons.value.forEach(season => {
        const row = metrics.map(metric => {
            let value = season[metric.key] || '';

            if (metric.key === 'start_date' || metric.key === 'end_date') {
                value = new Date(value).toLocaleDateString('de-DE');
            }

            // Escape commas in values
            if (typeof value === 'string' && value.includes(',')) {
                value = `"${value}"`;
            }

            return value;
        });
        csvContent += row.join(',') + '\n';
    });

    // Download
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `saison-vergleich-${Date.now()}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

const handleExportPDF = () => {
    if (!canExport.value) return;

    router.post(
        route('club.seasons.export', { club: props.club.id }),
        {
            seasons: selectedSeasonIds.value,
            format: 'pdf'
        },
        {
            onSuccess: () => {
                // PDF download will be triggered by the backend
            }
        }
    );
};
</script>

<template>
    <AppLayout :title="`Saison-Vergleich - ${club.name}`">
        <Head :title="`Saison-Vergleich - ${club.name}`" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">
                                Saison-Vergleich
                            </h1>
                            <p class="mt-1 text-sm text-gray-500">
                                Vergleichen Sie bis zu 4 Saisons gleichzeitig
                            </p>
                        </div>

                        <!-- Export Buttons -->
                        <div v-if="hasEnoughSeasons && canExport" class="flex items-center space-x-2">
                            <button
                                type="button"
                                @click="handleExportCSV"
                                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                <ArrowDownTrayIcon class="h-4 w-4 mr-2" />
                                CSV Export
                            </button>
                            <button
                                type="button"
                                @click="handleExportPDF"
                                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                <ArrowDownTrayIcon class="h-4 w-4 mr-2" />
                                PDF Export
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Season Selector -->
                <div class="mb-6">
                    <SeasonComparisonSelector
                        :available-seasons="availableSeasons"
                        :selected-seasons="selectedSeasonIds"
                        :max-selections="4"
                        @update:selected-seasons="handleSeasonSelectionChange"
                    />
                </div>

                <!-- Content -->
                <div v-if="hasEnoughSeasons">
                    <!-- Tab Navigation -->
                    <div class="mb-6">
                        <div class="border-b border-gray-200">
                            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                                <button
                                    v-for="tab in tabs"
                                    :key="tab.key"
                                    type="button"
                                    @click="activeTab = tab.key"
                                    :class="[
                                        activeTab === tab.key
                                            ? 'border-blue-500 text-blue-600'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                        'group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm transition-colors'
                                    ]"
                                >
                                    <component
                                        :is="tab.icon"
                                        :class="[
                                            activeTab === tab.key ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500',
                                            '-ml-0.5 mr-2 h-5 w-5'
                                        ]"
                                    />
                                    {{ tab.label }}
                                </button>
                            </nav>
                        </div>
                    </div>

                    <!-- Tab Content -->
                    <div class="space-y-6">
                        <!-- Overview Tab -->
                        <div v-show="activeTab === 'overview'" class="space-y-6">
                            <!-- Quick Stats Grid -->
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                                <div
                                    v-for="(season, index) in selectedSeasons"
                                    :key="season.id"
                                    class="bg-white overflow-hidden shadow rounded-lg"
                                >
                                    <div class="p-5">
                                        <div class="flex items-center justify-between mb-3">
                                            <h3 class="text-sm font-medium text-gray-900 truncate">
                                                {{ season.name }}
                                            </h3>
                                            <div
                                                :class="[
                                                    'w-3 h-3 rounded-full',
                                                    seasonColors[index % seasonColors.length].border
                                                ]"
                                                :style="{ backgroundColor: seasonColors[index % seasonColors.length].hex }"
                                            ></div>
                                        </div>
                                        <dl class="space-y-2 text-xs">
                                            <div class="flex justify-between">
                                                <dt class="text-gray-500">Teams:</dt>
                                                <dd class="text-gray-900 font-medium">{{ season.teams_count || 0 }}</dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-gray-500">Spiele:</dt>
                                                <dd class="text-gray-900 font-medium">{{ season.games_count || 0 }}</dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-gray-500">Spieler:</dt>
                                                <dd class="text-gray-900 font-medium">{{ season.players_count || 0 }}</dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-gray-500">Ø Punkte:</dt>
                                                <dd class="text-gray-900 font-medium">
                                                    {{ season.avg_score ? season.avg_score.toFixed(1) : '0.0' }}
                                                </dd>
                                            </div>
                                        </dl>
                                    </div>
                                </div>
                            </div>

                            <!-- Timeline -->
                            <SeasonTimeline
                                :seasons="selectedSeasons"
                                :season-colors="seasonColors"
                                :club="club"
                            />

                            <!-- Quick Chart Preview -->
                            <SeasonComparisonChart
                                :seasons="selectedSeasons"
                                :season-colors="seasonColors"
                            />
                        </div>

                        <!-- Charts Tab -->
                        <div v-show="activeTab === 'charts'">
                            <SeasonComparisonChart
                                :seasons="selectedSeasons"
                                :season-colors="seasonColors"
                            />
                        </div>

                        <!-- Table Tab -->
                        <div v-show="activeTab === 'table'">
                            <SeasonComparisonTable
                                :seasons="selectedSeasons"
                                :season-colors="seasonColors"
                                @export="handleExportCSV"
                            />
                        </div>

                        <!-- Timeline Tab -->
                        <div v-show="activeTab === 'timeline'">
                            <SeasonTimeline
                                :seasons="selectedSeasons"
                                :season-colors="seasonColors"
                                :club="club"
                            />
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="bg-white shadow rounded-lg">
                    <div class="px-6 py-12 text-center">
                        <ChartBarIcon class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900">
                            Keine Saisons ausgewählt
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Wählen Sie mindestens 2 Saisons aus, um einen Vergleich zu starten.
                        </p>
                        <div class="mt-6">
                            <p class="text-xs text-gray-500">
                                <strong>Tipp:</strong> Nutzen Sie die Schnellauswahl oben, um die letzten 2 oder 3 Saisons zu vergleichen.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Help Text -->
                <div v-if="availableSeasons.length < 2" class="mt-6">
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    Sie benötigen mindestens 2 Saisons, um die Vergleichsfunktion zu nutzen.
                                    <a
                                        :href="route('club.seasons.wizard.index', { club: club.id })"
                                        class="font-medium underline hover:text-yellow-600"
                                    >
                                        Erstellen Sie jetzt eine neue Saison.
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
