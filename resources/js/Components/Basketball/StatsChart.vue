<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue';

const props = defineProps({
    type: {
        type: String,
        default: 'bar',
        validator: (value) => ['bar', 'line', 'doughnut', 'pie', 'radar', 'polarArea'].includes(value)
    },
    data: {
        type: Object,
        required: true
    },
    options: {
        type: Object,
        default: () => ({})
    },
    height: {
        type: Number,
        default: 300
    },
    width: {
        type: Number,
        default: null
    },
    responsive: {
        type: Boolean,
        default: true
    },
    maintainAspectRatio: {
        type: Boolean,
        default: false
    }
});

const chartRef = ref(null);
const chartInstance = ref(null);
const canvasRef = ref(null);
const loading = ref(true);
const error = ref(null);

// Default chart options für verschiedene Chart-Typen
const defaultOptions = computed(() => {
    const baseOptions = {
        responsive: props.responsive,
        maintainAspectRatio: props.maintainAspectRatio,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 20,
                    font: {
                        size: 12
                    }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleColor: 'white',
                bodyColor: 'white',
                borderColor: 'rgba(0, 0, 0, 0.1)',
                borderWidth: 1,
                cornerRadius: 6,
                displayColors: true
            }
        }
    };

    // Spezifische Optionen für verschiedene Chart-Typen
    switch (props.type) {
        case 'bar':
        case 'line':
            return {
                ...baseOptions,
                scales: {
                    x: {
                        grid: {
                            display: true,
                            drawBorder: false,
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            font: {
                                size: 11
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: true,
                            drawBorder: false,
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            font: {
                                size: 11
                            }
                        }
                    }
                }
            };
        
        case 'radar':
            return {
                ...baseOptions,
                scales: {
                    r: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        angleLines: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        pointLabels: {
                            font: {
                                size: 11
                            }
                        }
                    }
                }
            };
        
        case 'doughnut':
        case 'pie':
            return {
                ...baseOptions,
                cutout: props.type === 'doughnut' ? '50%' : '0%',
                plugins: {
                    ...baseOptions.plugins,
                    legend: {
                        ...baseOptions.plugins.legend,
                        position: 'right'
                    }
                }
            };
        
        default:
            return baseOptions;
    }
});

// Merged options
const mergedOptions = computed(() => {
    return {
        ...defaultOptions.value,
        ...props.options
    };
});

// Chart Konfiguration
const chartConfig = computed(() => ({
    type: props.type,
    data: props.data,
    options: mergedOptions.value
}));

// Chart.js dynamisch laden und Chart erstellen
const initChart = async () => {
    try {
        loading.value = true;
        error.value = null;

        // Chart.js dynamisch importieren
        const { Chart, registerables } = await import('chart.js');
        Chart.register(...registerables);

        // Vorherige Chart-Instanz zerstören
        if (chartInstance.value) {
            chartInstance.value.destroy();
        }

        await nextTick();

        if (!canvasRef.value) {
            throw new Error('Canvas-Element nicht verfügbar');
        }

        // Neue Chart-Instanz erstellen
        chartInstance.value = new Chart(canvasRef.value, chartConfig.value);
        
        loading.value = false;
    } catch (err) {
        console.error('Fehler beim Initialisieren des Charts:', err);
        error.value = 'Chart konnte nicht geladen werden';
        loading.value = false;
    }
};

// Chart aktualisieren
const updateChart = () => {
    if (chartInstance.value && props.data) {
        chartInstance.value.data = props.data;
        chartInstance.value.options = mergedOptions.value;
        chartInstance.value.update('active');
    }
};

// Chart neu laden
const reloadChart = async () => {
    await initChart();
};

// Watchers
watch(() => props.data, () => {
    if (chartInstance.value) {
        updateChart();
    }
}, { deep: true });

watch(() => props.options, () => {
    if (chartInstance.value) {
        updateChart();
    }
}, { deep: true });

watch(() => props.type, async () => {
    await initChart();
});

// Lifecycle
onMounted(async () => {
    await initChart();
});

// Component unmount cleanup
const destroyChart = () => {
    if (chartInstance.value) {
        chartInstance.value.destroy();
        chartInstance.value = null;
    }
};

// Export für Parent-Komponenten
defineExpose({
    reloadChart,
    updateChart,
    chartInstance: chartInstance
});
</script>

<template>
    <div ref="chartRef" class="relative">
        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center" :style="{ height: height + 'px' }">
            <div class="flex flex-col items-center space-y-3">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-orange-600"></div>
                <span class="text-sm text-gray-600">Chart wird geladen...</span>
            </div>
        </div>
        
        <!-- Error State -->
        <div v-else-if="error" class="flex items-center justify-center bg-red-50 rounded-lg" :style="{ height: height + 'px' }">
            <div class="text-center">
                <svg class="mx-auto h-12 w-12 text-red-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L12.732 4.5c-.77-.833-2.694-.833-3.464 0L1.352 16.5C.582 18.333 1.544 20 3.084 20z"></path>
                </svg>
                <p class="text-sm text-red-700 mb-2">{{ error }}</p>
                <button @click="reloadChart" class="text-sm text-red-600 hover:text-red-800 underline">
                    Erneut versuchen
                </button>
            </div>
        </div>
        
        <!-- Chart Canvas -->
        <div v-else class="relative" :style="{ height: height + 'px', width: width ? width + 'px' : '100%' }">
            <canvas 
                ref="canvasRef"
                :height="height"
                :width="width"
                class="max-w-full">
            </canvas>
        </div>
        
        <!-- No Data State -->
        <div v-if="!loading && !error && (!data || !data.datasets || data.datasets.length === 0)" 
             class="absolute inset-0 flex items-center justify-center bg-gray-50 rounded-lg">
            <div class="text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <p class="text-sm text-gray-600">Keine Daten verfügbar</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
canvas {
    display: block;
    box-sizing: border-box;
}
</style>