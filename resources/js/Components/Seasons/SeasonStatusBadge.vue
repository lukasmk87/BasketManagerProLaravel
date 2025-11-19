<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: {
        type: String,
        required: true,
        validator: (value) => ['draft', 'active', 'completed'].includes(value)
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value)
    }
});

const statusConfig = computed(() => {
    const configs = {
        draft: {
            text: 'Entwurf',
            color: 'bg-gray-100 text-gray-800',
            dotColor: 'bg-gray-400'
        },
        active: {
            text: 'Aktiv',
            color: 'bg-green-100 text-green-800',
            dotColor: 'bg-green-400'
        },
        completed: {
            text: 'Abgeschlossen',
            color: 'bg-blue-100 text-blue-800',
            dotColor: 'bg-blue-400'
        }
    };

    return configs[props.status] || configs.draft;
});

const sizeClasses = computed(() => {
    const sizes = {
        sm: 'px-2 py-0.5 text-xs',
        md: 'px-2.5 py-0.5 text-xs',
        lg: 'px-3 py-1 text-sm'
    };

    return sizes[props.size];
});

const dotSizeClasses = computed(() => {
    const sizes = {
        sm: 'h-1.5 w-1.5',
        md: 'h-2 w-2',
        lg: 'h-2.5 w-2.5'
    };

    return sizes[props.size];
});
</script>

<template>
    <span
        class="inline-flex items-center rounded-full font-medium"
        :class="[statusConfig.color, sizeClasses]"
    >
        <svg
            class="-ml-0.5 mr-1.5 rounded-full"
            :class="[statusConfig.dotColor, dotSizeClasses]"
            fill="currentColor"
            viewBox="0 0 8 8"
        >
            <circle cx="4" cy="4" r="3" />
        </svg>
        {{ statusConfig.text }}
    </span>
</template>
