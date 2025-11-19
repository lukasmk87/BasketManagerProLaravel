<script setup>
import { ref, computed } from 'vue';
import { ChevronDownIcon, ChevronRightIcon, ArrowDownTrayIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    seasons: {
        type: Array,
        required: true
    },
    seasonColors: {
        type: Array,
        required: true
    }
});

const emit = defineEmits(['export']);

const expandedCategories = ref(new Set(['basis']));

const metrics = [
    {
        category: 'basis',
        label: 'Basis-Metriken',
        items: [
            { key: 'teams_count', label: 'Teams', format: 'number', higherIsBetter: true },
            { key: 'games_count', label: 'Spiele', format: 'number', higherIsBetter: true },
            { key: 'players_count', label: 'Spieler', format: 'number', higherIsBetter: true },
            { key: 'duration_weeks', label: 'Dauer (Wochen)', format: 'number', higherIsBetter: null }
        ]
    },
    {
        category: 'statistics',
        label: 'Statistiken',
        items: [
            { key: 'avg_score', label: 'Ø Punkte', format: 'decimal', higherIsBetter: true },
            { key: 'avg_assists', label: 'Ø Assists', format: 'decimal', higherIsBetter: true },
            { key: 'avg_rebounds', label: 'Ø Rebounds', format: 'decimal', higherIsBetter: true },
            { key: 'avg_fouls', label: 'Ø Fouls', format: 'decimal', higherIsBetter: false }
        ]
    },
    {
        category: 'performance',
        label: 'Performance',
        items: [
            { key: 'field_goal_percentage', label: 'FG%', format: 'percentage', higherIsBetter: true },
            { key: 'three_point_percentage', label: '3P%', format: 'percentage', higherIsBetter: true },
            { key: 'free_throw_percentage', label: 'FT%', format: 'percentage', higherIsBetter: true },
            { key: 'win_percentage', label: 'Siegquote', format: 'percentage', higherIsBetter: true }
        ]
    }
];

const toggleCategory = (category) => {
    if (expandedCategories.value.has(category)) {
        expandedCategories.value.delete(category);
    } else {
        expandedCategories.value.add(category);
    }
};

const isCategoryExpanded = (category) => {
    return expandedCategories.value.has(category);
};

const formatValue = (value, format) => {
    if (value === null || value === undefined) return '-';

    switch (format) {
        case 'number':
            return value.toLocaleString('de-DE');
        case 'decimal':
            return value.toFixed(1);
        case 'percentage':
            return `${value.toFixed(1)}%`;
        default:
            return value;
    }
};

const getMetricValue = (season, key) => {
    if (key === 'duration_weeks') {
        const start = new Date(season.start_date);
        const end = new Date(season.end_date);
        const weeks = Math.floor((end - start) / (1000 * 60 * 60 * 24 * 7));
        return weeks;
    }

    return season[key] ?? 0;
};

const getCellColor = (season, metric) => {
    if (metric.higherIsBetter === null) return '';

    const values = props.seasons.map(s => getMetricValue(s, metric.key));
    const value = getMetricValue(season, metric.key);

    if (values.every(v => v === value)) return ''; // All equal

    const max = Math.max(...values);
    const min = Math.min(...values);

    if (metric.higherIsBetter) {
        if (value === max) return 'bg-green-50 text-green-900 font-medium';
        if (value === min) return 'bg-red-50 text-red-900';
    } else {
        if (value === min) return 'bg-green-50 text-green-900 font-medium';
        if (value === max) return 'bg-red-50 text-red-900';
    }

    return 'bg-yellow-50 text-yellow-900';
};

const calculateDelta = (metric) => {
    const values = props.seasons.map(s => getMetricValue(s, metric.key));
    const max = Math.max(...values);
    const min = Math.min(...values);

    if (max === min) return { value: 0, label: '±0', positive: null };

    const delta = max - min;
    const percentage = ((delta / min) * 100).toFixed(1);

    return {
        value: delta,
        label: `${delta.toFixed(1)} (${percentage}%)`,
        positive: metric.higherIsBetter ? (max > min) : (min < max)
    };
};

const getSeasonColor = (seasonId) => {
    const index = props.seasons.findIndex(s => s.id === seasonId);
    return props.seasonColors[index] || props.seasonColors[0];
};

const handleExport = () => {
    emit('export', 'csv');
};
</script>

<template>
    <div class="bg-white rounded-lg shadow">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900">Detaillierter Vergleich</h3>
            <button
                type="button"
                @click="handleExport"
                class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                <ArrowDownTrayIcon class="h-3.5 w-3.5 mr-1.5" />
                CSV Export
            </button>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 bg-gray-50 z-10">
                            Metrik
                        </th>
                        <th
                            v-for="season in seasons"
                            :key="season.id"
                            scope="col"
                            class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider"
                        >
                            <div
                                :class="[
                                    'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium',
                                    getSeasonColor(season.id).bg,
                                    getSeasonColor(season.id).text
                                ]"
                            >
                                {{ season.name }}
                            </div>
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Δ Best/Worst
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template v-for="category in metrics" :key="category.category">
                        <!-- Category Header -->
                        <tr class="bg-gray-100 hover:bg-gray-200 cursor-pointer" @click="toggleCategory(category.category)">
                            <td colspan="100" class="px-6 py-3">
                                <div class="flex items-center text-sm font-semibold text-gray-700">
                                    <ChevronDownIcon
                                        v-if="isCategoryExpanded(category.category)"
                                        class="h-4 w-4 mr-2 text-gray-500"
                                    />
                                    <ChevronRightIcon
                                        v-else
                                        class="h-4 w-4 mr-2 text-gray-500"
                                    />
                                    {{ category.label }}
                                </div>
                            </td>
                        </tr>

                        <!-- Category Items -->
                        <template v-if="isCategoryExpanded(category.category)">
                            <tr
                                v-for="metric in category.items"
                                :key="metric.key"
                                class="hover:bg-gray-50"
                            >
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 sticky left-0 bg-white">
                                    {{ metric.label }}
                                </td>
                                <td
                                    v-for="season in seasons"
                                    :key="season.id"
                                    :class="[
                                        'px-6 py-4 whitespace-nowrap text-sm text-center',
                                        getCellColor(season, metric)
                                    ]"
                                >
                                    {{ formatValue(getMetricValue(season, metric.key), metric.format) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                    <span
                                        :class="[
                                            'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium',
                                            calculateDelta(metric).positive === true ? 'bg-green-100 text-green-800' :
                                            calculateDelta(metric).positive === false ? 'bg-red-100 text-red-800' :
                                            'bg-gray-100 text-gray-800'
                                        ]"
                                    >
                                        {{ calculateDelta(metric).label }}
                                    </span>
                                </td>
                            </tr>
                        </template>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Footer Legend -->
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            <div class="flex items-center space-x-6 text-xs text-gray-600">
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-green-100 border border-green-500 rounded mr-1.5"></div>
                    <span>Beste Wert</span>
                </div>
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-red-100 border border-red-500 rounded mr-1.5"></div>
                    <span>Schlechteste Wert</span>
                </div>
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-yellow-100 border border-yellow-500 rounded mr-1.5"></div>
                    <span>Durchschnitt</span>
                </div>
            </div>
        </div>
    </div>
</template>
