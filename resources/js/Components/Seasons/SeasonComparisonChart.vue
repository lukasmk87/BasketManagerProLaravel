<script setup>
import { ref, computed } from 'vue';
import StatsChart from '@/Components/Basketball/StatsChart.vue';
import { ArrowDownTrayIcon } from '@heroicons/vue/24/outline';

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

const chartType = ref('bar');
const selectedMetric = ref('teams_count');

const availableMetrics = [
    { key: 'teams_count', label: 'Teams', format: 'number' },
    { key: 'games_count', label: 'Spiele', format: 'number' },
    { key: 'players_count', label: 'Spieler', format: 'number' },
    { key: 'avg_score', label: 'Ø Punkte', format: 'decimal' },
    { key: 'avg_assists', label: 'Ø Assists', format: 'decimal' },
    { key: 'avg_rebounds', label: 'Ø Rebounds', format: 'decimal' },
    { key: 'field_goal_percentage', label: 'FG%', format: 'percentage' },
    { key: 'three_point_percentage', label: '3P%', format: 'percentage' },
    { key: 'free_throw_percentage', label: 'FT%', format: 'percentage' },
    { key: 'win_percentage', label: 'Siegquote', format: 'percentage' }
];

const currentMetric = computed(() => {
    return availableMetrics.find(m => m.key === selectedMetric.value) || availableMetrics[0];
});

const getMetricValue = (season, key) => {
    return season[key] || 0;
};

const formatValue = (value, format) => {
    if (value === null || value === undefined) return '0';

    switch (format) {
        case 'decimal':
            return value.toFixed(1);
        case 'percentage':
            return value.toFixed(1);
        default:
            return value;
    }
};

const getSeasonColor = (index) => {
    return props.seasonColors[index] || props.seasonColors[0];
};

const chartData = computed(() => {
    if (chartType.value === 'radar') {
        // Radar: Multiple metrics for each season
        const radarMetrics = ['teams_count', 'games_count', 'players_count', 'avg_score',
                             'field_goal_percentage', 'three_point_percentage', 'free_throw_percentage', 'avg_assists'];

        return {
            labels: radarMetrics.map(key => {
                const metric = availableMetrics.find(m => m.key === key);
                return metric ? metric.label : key;
            }),
            datasets: props.seasons.map((season, index) => ({
                label: season.name,
                data: radarMetrics.map(key => getMetricValue(season, key)),
                backgroundColor: `${getSeasonColor(index).hex}33`,
                borderColor: getSeasonColor(index).hex,
                borderWidth: 2,
                pointBackgroundColor: getSeasonColor(index).hex,
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: getSeasonColor(index).hex
            }))
        };
    } else if (chartType.value === 'doughnut') {
        // Doughnut: One metric, distributed across seasons
        return {
            labels: props.seasons.map(s => s.name),
            datasets: [{
                label: currentMetric.value.label,
                data: props.seasons.map(s => getMetricValue(s, selectedMetric.value)),
                backgroundColor: props.seasons.map((_, index) => getSeasonColor(index).hex),
                borderColor: props.seasons.map((_, index) => '#fff'),
                borderWidth: 2
            }]
        };
    } else {
        // Bar/Line: One metric across seasons
        return {
            labels: props.seasons.map(s => s.name),
            datasets: [{
                label: currentMetric.value.label,
                data: props.seasons.map(s => getMetricValue(s, selectedMetric.value)),
                backgroundColor: props.seasons.map((_, index) => `${getSeasonColor(index).hex}33`),
                borderColor: props.seasons.map((_, index) => getSeasonColor(index).hex),
                borderWidth: 2,
                fill: chartType.value === 'line' ? false : true
            }]
        };
    }
});

const chartOptions = computed(() => {
    const baseOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 20,
                    font: { size: 12 },
                    usePointStyle: true
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                borderColor: 'rgba(255, 255, 255, 0.2)',
                borderWidth: 1,
                titleColor: '#fff',
                bodyColor: '#fff',
                callbacks: {
                    label: (context) => {
                        const label = context.dataset.label || '';
                        const value = context.parsed.y !== undefined ? context.parsed.y : context.parsed;
                        const formatted = formatValue(value, currentMetric.value.format);

                        if (currentMetric.value.format === 'percentage') {
                            return `${label}: ${formatted}%`;
                        }
                        return `${label}: ${formatted}`;
                    }
                }
            }
        }
    };

    if (chartType.value === 'radar') {
        return {
            ...baseOptions,
            scales: {
                r: {
                    beginAtZero: true,
                    ticks: {
                        backdropColor: 'transparent'
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                }
            }
        };
    } else if (chartType.value === 'doughnut') {
        return baseOptions;
    } else {
        return {
            ...baseOptions,
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 11 } }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0, 0, 0, 0.05)' },
                    ticks: {
                        callback: (value) => {
                            if (currentMetric.value.format === 'percentage') {
                                return `${value}%`;
                            }
                            return value;
                        }
                    }
                }
            }
        };
    }
});

const downloadChart = () => {
    const canvas = document.querySelector('canvas');
    if (!canvas) return;

    const url = canvas.toDataURL('image/png');
    const link = document.createElement('a');
    link.download = `saison-vergleich-${selectedMetric.value}-${chartType.value}.png`;
    link.href = url;
    link.click();
};
</script>

<template>
    <div class="bg-white rounded-lg shadow">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0">
                <!-- Chart Type Selector -->
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-700 font-medium">Chart-Typ:</span>
                    <div class="flex items-center bg-gray-100 rounded-md p-1">
                        <button
                            @click="chartType = 'bar'"
                            :class="[
                                'px-3 py-1.5 text-xs font-medium rounded transition-colors',
                                chartType === 'bar'
                                    ? 'bg-white text-gray-900 shadow-sm'
                                    : 'text-gray-600 hover:text-gray-900'
                            ]"
                        >
                            Balken
                        </button>
                        <button
                            @click="chartType = 'line'"
                            :class="[
                                'px-3 py-1.5 text-xs font-medium rounded transition-colors',
                                chartType === 'line'
                                    ? 'bg-white text-gray-900 shadow-sm'
                                    : 'text-gray-600 hover:text-gray-900'
                            ]"
                        >
                            Linie
                        </button>
                        <button
                            @click="chartType = 'radar'"
                            :class="[
                                'px-3 py-1.5 text-xs font-medium rounded transition-colors',
                                chartType === 'radar'
                                    ? 'bg-white text-gray-900 shadow-sm'
                                    : 'text-gray-600 hover:text-gray-900'
                            ]"
                        >
                            Radar
                        </button>
                        <button
                            @click="chartType = 'doughnut'"
                            :class="[
                                'px-3 py-1.5 text-xs font-medium rounded transition-colors',
                                chartType === 'doughnut'
                                    ? 'bg-white text-gray-900 shadow-sm'
                                    : 'text-gray-600 hover:text-gray-900'
                            ]"
                        >
                            Donut
                        </button>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <!-- Metric Selector (not for radar) -->
                    <div v-if="chartType !== 'radar'" class="flex items-center space-x-2">
                        <label for="metric-select" class="text-sm text-gray-700 font-medium">Metrik:</label>
                        <select
                            id="metric-select"
                            v-model="selectedMetric"
                            class="block w-auto border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                        >
                            <option v-for="metric in availableMetrics" :key="metric.key" :value="metric.key">
                                {{ metric.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Download Button -->
                    <button
                        type="button"
                        @click="downloadChart"
                        class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <ArrowDownTrayIcon class="h-3.5 w-3.5 mr-1.5" />
                        PNG
                    </button>
                </div>
            </div>
        </div>

        <!-- Chart -->
        <div class="p-6">
            <div class="h-96">
                <StatsChart
                    :type="chartType"
                    :data="chartData"
                    :options="chartOptions"
                />
            </div>
        </div>

        <!-- Info -->
        <div v-if="chartType === 'radar'" class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            <p class="text-xs text-gray-600">
                <strong>Radar-Chart:</strong> Zeigt mehrere Metriken gleichzeitig für jede Saison.
                Je größer die Fläche, desto besser die Gesamt-Performance.
            </p>
        </div>
    </div>
</template>
