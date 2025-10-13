<script setup>
import { computed } from 'vue';

const props = defineProps({
    metric: {
        type: String,
        required: true,
    },
    current: {
        type: Number,
        required: true,
    },
    limit: {
        type: Number,
        required: true, // -1 for unlimited
    },
    label: {
        type: String,
        default: null,
    },
    unit: {
        type: String,
        default: '',
    },
    showPercentage: {
        type: Boolean,
        default: true,
    },
    size: {
        type: String,
        default: 'md', // 'sm', 'md', 'lg'
        validator: (value) => ['sm', 'md', 'lg'].includes(value),
    },
});

const isUnlimited = computed(() => props.limit === -1);

const percentage = computed(() => {
    if (isUnlimited.value) return 0;
    if (props.limit === 0) return 100;
    return Math.min(Math.round((props.current / props.limit) * 100), 100);
});

const progressColor = computed(() => {
    if (isUnlimited.value) return 'bg-green-500';

    const p = percentage.value;
    if (p >= 100) return 'bg-red-500';
    if (p >= 90) return 'bg-red-400';
    if (p >= 80) return 'bg-orange-500';
    if (p >= 70) return 'bg-yellow-500';
    return 'bg-green-500';
});

const progressBgColor = computed(() => {
    if (isUnlimited.value) return 'bg-green-100';

    const p = percentage.value;
    if (p >= 100) return 'bg-red-100';
    if (p >= 90) return 'bg-red-50';
    if (p >= 80) return 'bg-orange-100';
    if (p >= 70) return 'bg-yellow-100';
    return 'bg-green-100';
});

const statusIcon = computed(() => {
    if (isUnlimited.value) return '∞';

    const p = percentage.value;
    if (p >= 100) return '🚨';
    if (p >= 90) return '⚠️';
    if (p >= 80) return '⚡';
    if (p >= 70) return '📊';
    return '✅';
});

const statusText = computed(() => {
    if (isUnlimited.value) return 'Unbegrenzt';

    const p = percentage.value;
    if (p >= 100) return 'Limit erreicht';
    if (p >= 90) return 'Kritisch';
    if (p >= 80) return 'Warnung';
    if (p >= 70) return 'Erhöht';
    return 'Normal';
});

const displayLabel = computed(() => {
    return props.label || props.metric.charAt(0).toUpperCase() + props.metric.slice(1);
});

const formatNumber = (value) => {
    return value.toLocaleString('de-DE');
};

const heightClass = computed(() => {
    const heights = {
        sm: 'h-1.5',
        md: 'h-2.5',
        lg: 'h-4',
    };
    return heights[props.size];
});

const textSizeClass = computed(() => {
    const sizes = {
        sm: 'text-xs',
        md: 'text-sm',
        lg: 'text-base',
    };
    return sizes[props.size];
});
</script>

<template>
    <div class="space-y-2">
        <!-- Header -->
        <div class="flex items-center justify-between" :class="textSizeClass">
            <div class="flex items-center space-x-2">
                <span class="font-medium text-gray-700">{{ displayLabel }}</span>
                <span class="text-xs" :title="statusText">{{ statusIcon }}</span>
            </div>
            <div class="flex items-center space-x-2">
                <span class="font-semibold text-gray-900">
                    {{ formatNumber(current) }}{{ unit }}
                </span>
                <span class="text-gray-400">/</span>
                <span class="font-medium" :class="isUnlimited ? 'text-green-600' : 'text-gray-600'">
                    {{ isUnlimited ? 'Unbegrenzt' : formatNumber(limit) + unit }}
                </span>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="relative">
            <div
                class="w-full rounded-full overflow-hidden"
                :class="[heightClass, progressBgColor]"
            >
                <div
                    v-if="!isUnlimited"
                    class="h-full rounded-full transition-all duration-500 ease-in-out"
                    :class="progressColor"
                    :style="{ width: `${percentage}%` }"
                ></div>
                <div
                    v-else
                    class="h-full rounded-full bg-gradient-to-r from-green-400 via-green-500 to-green-600 animate-pulse"
                    style="width: 100%"
                ></div>
            </div>
        </div>

        <!-- Footer Info -->
        <div v-if="showPercentage" class="flex items-center justify-between text-xs">
            <span
                v-if="!isUnlimited"
                :class="{
                    'text-red-600 font-semibold': percentage >= 100,
                    'text-orange-600 font-medium': percentage >= 80 && percentage < 100,
                    'text-yellow-600': percentage >= 70 && percentage < 80,
                    'text-gray-500': percentage < 70,
                }"
            >
                {{ percentage }}% genutzt
            </span>
            <span v-else class="text-green-600 font-medium">
                ∞ Unbegrenztes Limit
            </span>

            <span
                v-if="!isUnlimited && percentage >= 80"
                class="px-2 py-0.5 rounded-full text-xs font-medium"
                :class="{
                    'bg-red-100 text-red-800': percentage >= 100,
                    'bg-red-50 text-red-700': percentage >= 90 && percentage < 100,
                    'bg-orange-100 text-orange-800': percentage >= 80 && percentage < 90,
                }"
            >
                {{ statusText }}
            </span>
        </div>
    </div>
</template>
