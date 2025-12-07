<script setup>
defineProps({
    loading: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['click']);
</script>

<template>
    <button
        @click="$emit('click')"
        :disabled="loading"
        class="inline-flex items-center px-6 py-3 bg-[#635BFF] hover:bg-[#5851ea] text-white font-semibold rounded-lg transition shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed"
    >
        <!-- Stripe Logo -->
        <svg
            v-if="!loading"
            class="w-5 h-5 mr-2"
            viewBox="0 0 60 25"
            fill="currentColor"
        >
            <path d="M59.64 14.28h-8.06c.19 1.93 1.6 2.55 3.2 2.55 1.64 0 2.96-.37 4.05-.95v3.32a8.33 8.33 0 0 1-4.56 1.1c-4.01 0-6.83-2.5-6.83-7.48 0-4.19 2.39-7.52 6.3-7.52 3.92 0 5.96 3.28 5.96 7.5 0 .4-.02 1.04-.06 1.48zm-6.3-5.63c-1.03 0-1.87.73-2.03 2.2h4.13c-.06-1.39-.81-2.2-2.1-2.2zM41.02 20.3V8.84h4.14l.23 1.48c.89-1.11 2.15-1.79 3.79-1.79l-.06 3.97c-1.39-.15-2.56.25-3.42.87v6.93h-4.68zM33.45 5.3h4.68V20.3h-4.68zM27.34 5.3V1.91l4.68-.98V5.3h3.37v3.55h-3.37v5.08c0 1.28.55 1.67 1.42 1.67.58 0 1.22-.12 1.95-.4v3.33c-.87.38-1.88.77-3.35.77-2.77 0-4.7-1.2-4.7-4.88V8.84h-2.43V5.3h2.43zM17.76 8.84c1.93-2.31 3.79-3.54 6.56-3.54v4.2c-2.19-.17-4.36.85-5.42 2.32v8.48h-4.68V5.3h3.37l.17 3.54zM4.68 15.75c.66.51 2.02 1.2 3.76 1.2 1.08 0 1.63-.38 1.63-.94 0-.66-.75-.89-2.11-1.26-2.57-.68-4.91-1.68-4.91-4.58 0-2.9 2.47-4.87 5.78-4.87 2.06 0 4.14.65 5.55 1.78l-1.68 2.97c-.81-.56-1.99-1.04-3.4-1.04-.94 0-1.5.32-1.5.81 0 .6.79.85 2.11 1.2 2.68.75 4.91 1.68 4.91 4.65 0 2.97-2.32 5.03-6.04 5.03-2.36 0-4.65-.75-6.28-2.1l2.18-2.85zM0 5.3h4.68v15h-4.68z"/>
        </svg>

        <!-- Loading Spinner -->
        <svg
            v-if="loading"
            class="animate-spin w-5 h-5 mr-2"
            fill="none"
            viewBox="0 0 24 24"
        >
            <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
            />
            <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            />
        </svg>

        <span v-if="loading">Wird vorbereitet...</span>
        <span v-else>Mit Stripe verbinden</span>
    </button>
</template>
