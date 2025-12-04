<script setup>
import { ref, computed } from 'vue';
import { HeartIcon } from '@heroicons/vue/24/outline';
import { HeartIcon as HeartSolidIcon } from '@heroicons/vue/24/solid';
import axios from 'axios';

const props = defineProps({
    playId: {
        type: [Number, String],
        required: true,
    },
    initialFavorited: {
        type: Boolean,
        default: false,
    },
    size: {
        type: String,
        default: 'md', // sm, md, lg
    },
    showLabel: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['toggled']);

const isFavorited = ref(props.initialFavorited);
const isLoading = ref(false);

const sizeClasses = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'w-4 h-4';
        case 'lg':
            return 'w-7 h-7';
        default:
            return 'w-5 h-5';
    }
});

const buttonClasses = computed(() => {
    const base = 'inline-flex items-center justify-center transition-colors duration-200';
    if (isFavorited.value) {
        return `${base} text-red-500 hover:text-red-600`;
    }
    return `${base} text-gray-400 hover:text-red-500`;
});

async function toggleFavorite() {
    if (isLoading.value) return;

    isLoading.value = true;

    try {
        const response = await axios.post(`/api/favorites/plays/${props.playId}/toggle`);
        isFavorited.value = response.data.is_favorited;
        emit('toggled', {
            playId: props.playId,
            isFavorited: isFavorited.value,
            favorite: response.data.favorite,
        });
    } catch (error) {
        console.error('Failed to toggle favorite:', error);
    } finally {
        isLoading.value = false;
    }
}
</script>

<template>
    <button
        @click.stop="toggleFavorite"
        :class="buttonClasses"
        :disabled="isLoading"
        :title="isFavorited ? 'Aus Favoriten entfernen' : 'Zu Favoriten hinzufügen'"
    >
        <component
            :is="isFavorited ? HeartSolidIcon : HeartIcon"
            :class="[sizeClasses, { 'animate-pulse': isLoading }]"
        />
        <span v-if="showLabel" class="ml-1 text-sm">
            {{ isFavorited ? 'Favorit' : 'Favorisieren' }}
        </span>
    </button>
</template>
