<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: {
        type: String,
        required: true
    },
    value: {
        type: [String, Number],
        required: true
    },
    subtitle: {
        type: String,
        default: ''
    },
    icon: {
        type: String,
        default: 'chart-bar'
    },
    color: {
        type: String,
        default: 'blue',
        validator: (value) => ['blue', 'green', 'indigo', 'orange', 'red', 'purple', 'yellow', 'gray'].includes(value)
    },
    trend: {
        type: Object,
        default: null
    },
    loading: {
        type: Boolean,
        default: false
    }
});

const colorClasses = computed(() => {
    const colors = {
        blue: {
            bg: 'bg-blue-50',
            iconBg: 'bg-blue-100',
            icon: 'text-blue-600',
            text: 'text-blue-900',
            subtitle: 'text-blue-700'
        },
        green: {
            bg: 'bg-green-50',
            iconBg: 'bg-green-100',
            icon: 'text-green-600',
            text: 'text-green-900',
            subtitle: 'text-green-700'
        },
        indigo: {
            bg: 'bg-indigo-50',
            iconBg: 'bg-indigo-100',
            icon: 'text-indigo-600',
            text: 'text-indigo-900',
            subtitle: 'text-indigo-700'
        },
        orange: {
            bg: 'bg-orange-50',
            iconBg: 'bg-orange-100',
            icon: 'text-orange-600',
            text: 'text-orange-900',
            subtitle: 'text-orange-700'
        },
        red: {
            bg: 'bg-red-50',
            iconBg: 'bg-red-100',
            icon: 'text-red-600',
            text: 'text-red-900',
            subtitle: 'text-red-700'
        },
        purple: {
            bg: 'bg-purple-50',
            iconBg: 'bg-purple-100',
            icon: 'text-purple-600',
            text: 'text-purple-900',
            subtitle: 'text-purple-700'
        },
        yellow: {
            bg: 'bg-yellow-50',
            iconBg: 'bg-yellow-100',
            icon: 'text-yellow-600',
            text: 'text-yellow-900',
            subtitle: 'text-yellow-700'
        },
        gray: {
            bg: 'bg-gray-50',
            iconBg: 'bg-gray-100',
            icon: 'text-gray-600',
            text: 'text-gray-900',
            subtitle: 'text-gray-700'
        }
    };
    
    return colors[props.color] || colors.blue;
});

const iconPaths = computed(() => {
    const icons = {
        'chart-bar': 'M3 3v2l5 5-3 3v2h2l3-3 5 5v-2l-5-5 3-3V3h-2l-3 3L3 3z',
        'users': 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'user-group': 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
        'trending-up': 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
        'calendar': 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        'building': 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
        'clock': 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'
    };
    
    return icons[props.icon] || icons['chart-bar'];
});

const trendIcon = computed(() => {
    if (!props.trend) return null;
    
    return props.trend.direction === 'up' 
        ? 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'  // trending-up
        : 'M13 17h8m0 0V9m0 8l-8-8-4 4-6-6';  // trending-down  
});

const trendColor = computed(() => {
    if (!props.trend) return '';
    
    return props.trend.direction === 'up' 
        ? 'text-green-600' 
        : 'text-red-600';
});
</script>

<template>
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div :class="[colorClasses.iconBg, 'rounded-md p-3']">
                        <div v-if="loading" class="animate-spin rounded-full h-6 w-6 border-b-2 border-gray-900"></div>
                        <svg v-else class="h-6 w-6" :class="colorClasses.icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="iconPaths"></path>
                        </svg>
                    </div>
                </div>
                
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">
                            {{ title }}
                        </dt>
                        <dd class="flex items-baseline">
                            <div class="text-2xl font-semibold text-gray-900" :class="{ 'animate-pulse bg-gray-200 rounded w-16 h-8': loading }">
                                <span v-if="!loading">{{ value }}</span>
                            </div>
                            
                            <!-- Trend Indicator -->
                            <div v-if="trend && !loading" class="ml-2 flex items-baseline text-sm font-semibold">
                                <svg class="self-center flex-shrink-0 h-4 w-4" :class="trendColor" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="trendIcon"></path>
                                </svg>
                                <span class="sr-only">{{ trend.direction === 'up' ? 'Erhöht' : 'Verringert' }} um</span>
                                <span :class="trendColor">{{ trend.value }}%</span>
                            </div>
                        </dd>
                        
                        <!-- Subtitle -->
                        <dd v-if="subtitle && !loading" class="mt-1 text-sm text-gray-600">
                            {{ subtitle }}
                        </dd>
                        <dd v-else-if="loading" class="mt-1 animate-pulse bg-gray-200 rounded w-24 h-4"></dd>
                    </dl>
                </div>
            </div>
        </div>
        
        <!-- Optional colored bottom border -->
        <div :class="colorClasses.bg" class="px-5 py-3" v-if="trend && !loading">
            <div class="text-sm">
                <span class="font-medium" :class="colorClasses.text">{{ trend.label || 'Veränderung' }}</span>
                <span :class="colorClasses.subtitle"> vs. letzter Zeitraum</span>
            </div>
        </div>
    </div>
</template>