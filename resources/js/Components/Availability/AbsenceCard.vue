<script setup>
import { computed } from 'vue';

const props = defineProps({
    absence: {
        type: Object,
        required: true,
    },
    canEdit: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['edit', 'delete']);

const typeConfig = computed(() => {
    const config = {
        vacation: { label: 'Urlaub', icon: 'sun', color: 'blue' },
        illness: { label: 'Krankheit', icon: 'thermometer', color: 'yellow' },
        injury: { label: 'Verletzung', icon: 'bandage', color: 'red' },
        personal: { label: 'Persönlich', icon: 'user', color: 'purple' },
        other: { label: 'Sonstiges', icon: 'info', color: 'gray' },
    };
    return config[props.absence.type] || config.other;
});

const typeClasses = computed(() => {
    const colors = {
        blue: 'bg-blue-100 text-blue-800 border-blue-200',
        yellow: 'bg-yellow-100 text-yellow-800 border-yellow-200',
        red: 'bg-red-100 text-red-800 border-red-200',
        purple: 'bg-purple-100 text-purple-800 border-purple-200',
        gray: 'bg-gray-100 text-gray-800 border-gray-200',
    };
    return colors[typeConfig.value.color] || colors.gray;
});

const statusBadge = computed(() => {
    if (props.absence.is_current) {
        return { label: 'Aktiv', class: 'bg-green-100 text-green-800' };
    } else if (props.absence.is_upcoming) {
        return { label: 'Geplant', class: 'bg-blue-100 text-blue-800' };
    } else {
        return { label: 'Vergangen', class: 'bg-gray-100 text-gray-600' };
    }
});

const canModify = computed(() => {
    return props.canEdit && (props.absence.is_current || props.absence.is_upcoming);
});
</script>

<template>
    <div class="bg-white border rounded-lg p-4 shadow-sm">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <!-- Header with Type and Status -->
                <div class="flex items-center gap-2 mb-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border" :class="typeClasses">
                        <!-- Type Icon -->
                        <svg v-if="typeConfig.icon === 'sun'" class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"></path>
                        </svg>
                        <svg v-else-if="typeConfig.icon === 'thermometer'" class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        <svg v-else-if="typeConfig.icon === 'bandage'" class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                        <svg v-else-if="typeConfig.icon === 'user'" class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                        </svg>
                        <svg v-else class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                        {{ absence.type_display || typeConfig.label }}
                    </span>

                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" :class="statusBadge.class">
                        {{ statusBadge.label }}
                    </span>
                </div>

                <!-- Date Range -->
                <div class="flex items-center text-sm text-gray-700 mb-1">
                    <svg class="w-4 h-4 mr-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span class="font-medium">{{ absence.period_display }}</span>
                    <span class="ml-2 text-gray-500">({{ absence.duration_days }} {{ absence.duration_days === 1 ? 'Tag' : 'Tage' }})</span>
                </div>

                <!-- Reason -->
                <div v-if="absence.reason" class="text-sm text-gray-600 mt-2">
                    <span class="font-medium">Grund:</span> {{ absence.reason }}
                </div>
            </div>

            <!-- Actions -->
            <div v-if="canModify" class="flex items-center gap-1 ml-4">
                <button
                    @click="emit('edit', absence)"
                    class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded transition-colors"
                    title="Bearbeiten"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                </button>
                <button
                    @click="emit('delete', absence)"
                    class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors"
                    title="Löschen"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>
