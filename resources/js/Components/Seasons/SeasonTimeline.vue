<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { MinusIcon, PlusIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    seasons: {
        type: Array,
        required: true
    },
    seasonColors: {
        type: Array,
        required: true
    },
    club: {
        type: Object,
        required: true
    }
});

const timelineCanvas = ref(null);
const canvasContext = ref(null);
const hoveredSeason = ref(null);
const tooltipStyle = ref({});
const zoomLevel = ref(1);

const BAR_HEIGHT = 40;
const BAR_MARGIN = 10;
const PADDING_TOP = 40;
const PADDING_BOTTOM = 60;
const PADDING_HORIZONTAL = 20;

const timeRange = computed(() => {
    if (props.seasons.length === 0) return { min: new Date(), max: new Date() };

    const dates = props.seasons.flatMap(s => [new Date(s.start_date), new Date(s.end_date)]);
    const minDate = new Date(Math.min(...dates));
    const maxDate = new Date(Math.max(...dates));

    // Add some padding (10% on each side)
    const range = maxDate - minDate;
    const padding = range * 0.1;

    return {
        min: new Date(minDate.getTime() - padding),
        max: new Date(maxDate.getTime() + padding)
    };
});

const getTimelinePosition = (date) => {
    const canvas = timelineCanvas.value;
    if (!canvas) return 0;

    const totalDuration = timeRange.value.max - timeRange.value.min;
    const elapsed = new Date(date) - timeRange.value.min;
    const percentage = elapsed / totalDuration;

    const usableWidth = (canvas.width - 2 * PADDING_HORIZONTAL) * zoomLevel.value;
    return PADDING_HORIZONTAL + (percentage * usableWidth);
};

const getTodayPosition = () => {
    return getTimelinePosition(new Date());
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('de-DE', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

const formatDateShort = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('de-DE', {
        month: 'short',
        year: 'numeric'
    });
};

const getSeasonColor = (seasonId) => {
    const index = props.seasons.findIndex(s => s.id === seasonId);
    return props.seasonColors[index] || props.seasonColors[0];
};

const drawTimeline = () => {
    const canvas = timelineCanvas.value;
    const ctx = canvasContext.value;
    if (!canvas || !ctx) return;

    // Clear canvas
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // Draw grid lines (months)
    drawTimeGrid(ctx, canvas);

    // Draw season bars
    props.seasons.forEach((season, index) => {
        drawSeasonBar(ctx, season, index);
    });

    // Draw today marker
    drawTodayMarker(ctx, canvas);
};

const drawTimeGrid = (ctx, canvas) => {
    ctx.strokeStyle = 'rgba(0, 0, 0, 0.1)';
    ctx.lineWidth = 1;
    ctx.font = '10px sans-serif';
    ctx.fillStyle = '#666';
    ctx.textAlign = 'center';

    // Draw time markers (every 3 months or based on range)
    const totalMonths = (timeRange.value.max.getFullYear() - timeRange.value.min.getFullYear()) * 12 +
                        (timeRange.value.max.getMonth() - timeRange.value.min.getMonth());
    const interval = totalMonths > 24 ? 6 : 3; // 6 months if > 2 years, else 3 months

    let currentDate = new Date(timeRange.value.min);
    currentDate.setDate(1); // First of month

    while (currentDate <= timeRange.value.max) {
        const x = getTimelinePosition(currentDate);

        if (x >= PADDING_HORIZONTAL && x <= canvas.width - PADDING_HORIZONTAL) {
            // Draw vertical line
            ctx.beginPath();
            ctx.moveTo(x, PADDING_TOP);
            ctx.lineTo(x, canvas.height - PADDING_BOTTOM);
            ctx.stroke();

            // Draw label
            ctx.fillText(
                formatDateShort(currentDate),
                x,
                canvas.height - PADDING_BOTTOM + 20
            );
        }

        currentDate.setMonth(currentDate.getMonth() + interval);
    }
};

const drawSeasonBar = (ctx, season, index) => {
    const x1 = getTimelinePosition(season.start_date);
    const x2 = getTimelinePosition(season.end_date);
    const width = x2 - x1;
    const y = PADDING_TOP + index * (BAR_HEIGHT + BAR_MARGIN);

    const color = getSeasonColor(season.id);

    // Draw bar background
    ctx.fillStyle = color.hex;
    ctx.fillRect(x1, y, width, BAR_HEIGHT);

    // Draw bar border
    ctx.strokeStyle = color.hex;
    ctx.lineWidth = 2;
    ctx.strokeRect(x1, y, width, BAR_HEIGHT);

    // Draw season name
    ctx.fillStyle = '#fff';
    ctx.font = 'bold 12px sans-serif';
    ctx.textAlign = 'left';
    ctx.fillText(season.name, x1 + 10, y + BAR_HEIGHT / 2 + 4);

    // Draw status indicator
    if (season.status === 'active') {
        ctx.fillStyle = '#10B981';
        ctx.beginPath();
        ctx.arc(x1 + width - 15, y + 15, 6, 0, 2 * Math.PI);
        ctx.fill();
    }
};

const drawTodayMarker = (ctx, canvas) => {
    const x = getTodayPosition();

    if (x < PADDING_HORIZONTAL || x > canvas.width - PADDING_HORIZONTAL) return;

    // Draw vertical line
    ctx.strokeStyle = '#EF4444';
    ctx.lineWidth = 2;
    ctx.setLineDash([5, 5]);
    ctx.beginPath();
    ctx.moveTo(x, PADDING_TOP);
    ctx.lineTo(x, canvas.height - PADDING_BOTTOM);
    ctx.stroke();
    ctx.setLineDash([]);

    // Draw label
    ctx.fillStyle = '#EF4444';
    ctx.font = 'bold 11px sans-serif';
    ctx.textAlign = 'center';
    ctx.fillText('Heute', x, PADDING_TOP - 10);
};

const handleMouseMove = (event) => {
    const canvas = timelineCanvas.value;
    if (!canvas) return;

    const rect = canvas.getBoundingClientRect();
    const x = event.clientX - rect.left;
    const y = event.clientY - rect.top;

    let foundSeason = null;

    props.seasons.forEach((season, index) => {
        const x1 = getTimelinePosition(season.start_date);
        const x2 = getTimelinePosition(season.end_date);
        const barY = PADDING_TOP + index * (BAR_HEIGHT + BAR_MARGIN);

        if (x >= x1 && x <= x2 && y >= barY && y <= barY + BAR_HEIGHT) {
            foundSeason = season;
        }
    });

    if (foundSeason) {
        hoveredSeason.value = foundSeason;
        tooltipStyle.value = {
            left: `${event.clientX + 10}px`,
            top: `${event.clientY - 60}px`
        };
        canvas.style.cursor = 'pointer';
    } else {
        hoveredSeason.value = null;
        canvas.style.cursor = 'default';
    }
};

const handleClick = (event) => {
    if (hoveredSeason.value) {
        router.visit(route('club.seasons.show', {
            club: props.club.id,
            season: hoveredSeason.value.id
        }));
    }
};

const zoomIn = () => {
    if (zoomLevel.value < 5) {
        zoomLevel.value += 0.5;
    }
};

const zoomOut = () => {
    if (zoomLevel.value > 1) {
        zoomLevel.value -= 0.5;
    }
};

const resizeCanvas = () => {
    const canvas = timelineCanvas.value;
    if (!canvas) return;

    const container = canvas.parentElement;
    canvas.width = container.clientWidth;
    canvas.height = PADDING_TOP + (props.seasons.length * (BAR_HEIGHT + BAR_MARGIN)) + PADDING_BOTTOM;

    drawTimeline();
};

watch(() => props.seasons, () => {
    resizeCanvas();
}, { deep: true });

watch(zoomLevel, () => {
    drawTimeline();
});

onMounted(() => {
    const canvas = timelineCanvas.value;
    if (canvas) {
        canvasContext.value = canvas.getContext('2d');
        resizeCanvas();
    }

    window.addEventListener('resize', resizeCanvas);
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', resizeCanvas);
});
</script>

<template>
    <div class="bg-white rounded-lg shadow">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900">Saison-Zeitstrahl</h3>

            <!-- Zoom Controls -->
            <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-600">Zoom:</span>
                <button
                    type="button"
                    @click="zoomOut"
                    :disabled="zoomLevel <= 1"
                    class="inline-flex items-center p-1.5 border border-gray-300 rounded text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <MinusIcon class="h-4 w-4" />
                </button>
                <span class="text-sm font-medium text-gray-900 w-10 text-center">{{ zoomLevel }}x</span>
                <button
                    type="button"
                    @click="zoomIn"
                    :disabled="zoomLevel >= 5"
                    class="inline-flex items-center p-1.5 border border-gray-300 rounded text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <PlusIcon class="h-4 w-4" />
                </button>
            </div>
        </div>

        <!-- Canvas Timeline -->
        <div class="p-6">
            <div class="relative overflow-x-auto">
                <canvas
                    ref="timelineCanvas"
                    @mousemove="handleMouseMove"
                    @click="handleClick"
                    class="w-full border border-gray-200 rounded"
                ></canvas>

                <!-- Tooltip -->
                <div
                    v-if="hoveredSeason"
                    :style="tooltipStyle"
                    class="fixed z-50 bg-gray-900 text-white text-xs rounded-lg px-3 py-2 pointer-events-none shadow-lg"
                >
                    <div class="font-semibold mb-1">{{ hoveredSeason.name }}</div>
                    <div class="text-gray-300">
                        {{ formatDate(hoveredSeason.start_date) }} - {{ formatDate(hoveredSeason.end_date) }}
                    </div>
                    <div class="mt-1 flex items-center space-x-3 text-gray-400">
                        <span>{{ hoveredSeason.teams_count || 0 }} Teams</span>
                        <span>•</span>
                        <span>{{ hoveredSeason.games_count || 0 }} Spiele</span>
                    </div>
                    <div class="mt-1 text-gray-400">
                        Status: <span class="font-medium text-white">
                            {{ hoveredSeason.status === 'draft' ? 'Entwurf' :
                               hoveredSeason.status === 'active' ? 'Aktiv' : 'Abgeschlossen' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Legend -->
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            <div class="flex flex-wrap gap-4">
                <div v-for="season in seasons" :key="season.id" class="flex items-center">
                    <div
                        :style="{ backgroundColor: getSeasonColor(season.id).hex }"
                        class="w-4 h-4 rounded mr-2"
                    ></div>
                    <span class="text-sm text-gray-700">{{ season.name }}</span>
                </div>
                <div class="flex items-center ml-auto">
                    <div class="w-1 h-4 bg-red-500 mr-2"></div>
                    <span class="text-sm text-gray-700">Heute</span>
                </div>
            </div>
        </div>
    </div>
</template>
