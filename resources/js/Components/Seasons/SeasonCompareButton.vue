<script setup>
import { router } from '@inertiajs/vue3';
import { ArrowsRightLeftIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    season: {
        type: Object,
        required: true
    },
    club: {
        type: Object,
        required: true
    },
    canCompare: {
        type: Boolean,
        default: false
    }
});

const handleCompare = () => {
    if (!props.canCompare) return;

    router.visit(route('club.seasons.compare', {
        club: props.club.id
    }), {
        data: {
            seasons: [props.season.id]
        }
    });
};
</script>

<template>
    <button
        type="button"
        @click="handleCompare"
        :disabled="!canCompare"
        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
    >
        <ArrowsRightLeftIcon class="h-4 w-4 mr-2" />
        Vergleichen
    </button>
</template>
