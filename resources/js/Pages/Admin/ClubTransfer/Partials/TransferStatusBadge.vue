<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: {
        type: String,
        required: true,
    },
});

const statusConfig = computed(() => {
    const configs = {
        pending: {
            class: 'bg-yellow-100 text-yellow-800',
            text: 'Ausstehend',
            icon: 'clock',
        },
        processing: {
            class: 'bg-blue-100 text-blue-800',
            text: 'In Bearbeitung',
            icon: 'spinner',
        },
        completed: {
            class: 'bg-green-100 text-green-800',
            text: 'Abgeschlossen',
            icon: 'check',
        },
        failed: {
            class: 'bg-red-100 text-red-800',
            text: 'Fehlgeschlagen',
            icon: 'x',
        },
        rolled_back: {
            class: 'bg-gray-100 text-gray-800',
            text: 'Zurückgesetzt',
            icon: 'undo',
        },
    };

    return configs[props.status] || {
        class: 'bg-gray-100 text-gray-800',
        text: props.status,
        icon: 'question',
    };
});
</script>

<template>
    <span
        :class="[
            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
            statusConfig.class
        ]"
    >
        <!-- Icon -->
        <svg
            v-if="statusConfig.icon === 'clock'"
            class="w-3.5 h-3.5 mr-1"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
            />
        </svg>

        <svg
            v-else-if="statusConfig.icon === 'spinner'"
            class="w-3.5 h-3.5 mr-1 animate-spin"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
            />
        </svg>

        <svg
            v-else-if="statusConfig.icon === 'check'"
            class="w-3.5 h-3.5 mr-1"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M5 13l4 4L19 7"
            />
        </svg>

        <svg
            v-else-if="statusConfig.icon === 'x'"
            class="w-3.5 h-3.5 mr-1"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
            />
        </svg>

        <svg
            v-else-if="statusConfig.icon === 'undo'"
            class="w-3.5 h-3.5 mr-1"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"
            />
        </svg>

        {{ statusConfig.text }}
    </span>
</template>
