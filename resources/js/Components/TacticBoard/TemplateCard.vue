<script setup>
import { computed } from 'vue';
import { EyeIcon, DocumentDuplicateIcon, StarIcon } from '@heroicons/vue/24/outline';
import { StarIcon as StarSolidIcon } from '@heroicons/vue/24/solid';
import TacticBoardViewer from './TacticBoardViewer.vue';
import FavoriteButton from './FavoriteButton.vue';

const props = defineProps({
    template: {
        type: Object,
        required: true,
    },
    showUseButton: {
        type: Boolean,
        default: true,
    },
    showFavoriteButton: {
        type: Boolean,
        default: true,
    },
    initialFavorited: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['use', 'preview', 'favorited']);

const categoryColors = {
    offense: 'bg-blue-100 text-blue-800',
    defense: 'bg-red-100 text-red-800',
    press_break: 'bg-purple-100 text-purple-800',
    inbound: 'bg-green-100 text-green-800',
    fast_break: 'bg-orange-100 text-orange-800',
    zone: 'bg-yellow-100 text-yellow-800',
    man_to_man: 'bg-pink-100 text-pink-800',
    transition: 'bg-teal-100 text-teal-800',
    special: 'bg-indigo-100 text-indigo-800',
};

const categoryColor = computed(() => {
    return categoryColors[props.template.category] || 'bg-gray-100 text-gray-800';
});

function handleUse() {
    emit('use', props.template);
}

function handlePreview() {
    emit('preview', props.template);
}

function handleFavorited(data) {
    emit('favorited', data);
}
</script>

<template>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow duration-200">
        <!-- Thumbnail -->
        <div class="relative aspect-[4/3] bg-gray-100 cursor-pointer" @click="handlePreview">
            <TacticBoardViewer
                v-if="template.play_data"
                :playData="template.play_data"
                :readonly="true"
                :showControls="false"
                class="w-full h-full"
            />
            <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                <span>Keine Vorschau</span>
            </div>

            <!-- Featured Badge -->
            <div v-if="template.is_featured" class="absolute top-2 left-2">
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                    <StarSolidIcon class="w-3 h-3 mr-1" />
                    Empfohlen
                </span>
            </div>

            <!-- Overlay on hover -->
            <div class="absolute inset-0 bg-black bg-opacity-0 hover:bg-opacity-30 transition-opacity duration-200 flex items-center justify-center opacity-0 hover:opacity-100">
                <EyeIcon class="w-8 h-8 text-white" />
            </div>
        </div>

        <!-- Content -->
        <div class="p-4">
            <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                    <h3 class="text-sm font-medium text-gray-900 truncate">
                        {{ template.name }}
                    </h3>
                    <span :class="['inline-block mt-1 px-2 py-0.5 rounded text-xs font-medium', categoryColor]">
                        {{ template.category_display }}
                    </span>
                </div>
                <FavoriteButton
                    v-if="showFavoriteButton"
                    :playId="template.id"
                    :initialFavorited="initialFavorited"
                    size="sm"
                    @toggled="handleFavorited"
                />
            </div>

            <p v-if="template.description" class="mt-2 text-xs text-gray-500 line-clamp-2">
                {{ template.description }}
            </p>

            <!-- Tags -->
            <div v-if="template.tags && template.tags.length" class="mt-2 flex flex-wrap gap-1">
                <span
                    v-for="tag in template.tags.slice(0, 3)"
                    :key="tag"
                    class="inline-block px-1.5 py-0.5 bg-gray-100 text-gray-600 rounded text-xs"
                >
                    {{ tag }}
                </span>
                <span v-if="template.tags.length > 3" class="text-xs text-gray-400">
                    +{{ template.tags.length - 3 }}
                </span>
            </div>

            <!-- Actions -->
            <div v-if="showUseButton" class="mt-3 flex gap-2">
                <button
                    @click="handleUse"
                    class="flex-1 inline-flex items-center justify-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    <DocumentDuplicateIcon class="w-4 h-4 mr-1" />
                    Verwenden
                </button>
                <button
                    @click="handlePreview"
                    class="inline-flex items-center justify-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    <EyeIcon class="w-4 h-4" />
                </button>
            </div>
        </div>
    </div>
</template>
