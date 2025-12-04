<script setup>
import { ref, onMounted } from 'vue';
import { ChevronLeftIcon, ChevronRightIcon, PlusIcon, Squares2X2Icon } from '@heroicons/vue/24/outline';
import TemplateCard from './TemplateCard.vue';
import TemplatePreviewModal from './TemplatePreviewModal.vue';
import axios from 'axios';

const props = defineProps({
    featuredTemplates: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['select', 'startBlank', 'browseAll']);

const templates = ref(props.featuredTemplates);
const isLoading = ref(false);
const previewTemplate = ref(null);
const showPreview = ref(false);
const scrollContainer = ref(null);

onMounted(async () => {
    if (templates.value.length === 0) {
        await loadFeaturedTemplates();
    }
});

async function loadFeaturedTemplates() {
    isLoading.value = true;
    try {
        const response = await axios.get('/api/templates/featured');
        templates.value = response.data.data;
    } catch (error) {
        console.error('Failed to load templates:', error);
    } finally {
        isLoading.value = false;
    }
}

function handlePreview(template) {
    previewTemplate.value = template;
    showPreview.value = true;
}

function handleUse(data) {
    showPreview.value = false;
    emit('select', data);
}

function handleStartBlank() {
    emit('startBlank');
}

function handleBrowseAll() {
    emit('browseAll');
}

function scrollLeft() {
    if (scrollContainer.value) {
        scrollContainer.value.scrollBy({ left: -300, behavior: 'smooth' });
    }
}

function scrollRight() {
    if (scrollContainer.value) {
        scrollContainer.value.scrollBy({ left: 300, behavior: 'smooth' });
    }
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="text-center">
            <h2 class="text-2xl font-bold text-gray-900">Neuen Spielzug erstellen</h2>
            <p class="mt-2 text-gray-600">
                Wähle ein Template oder starte mit einem leeren Spielfeld
            </p>
        </div>

        <!-- Start Blank Option -->
        <div class="flex justify-center">
            <button
                @click="handleStartBlank"
                class="inline-flex items-center px-6 py-3 border-2 border-dashed border-gray-300 rounded-lg text-gray-600 hover:border-blue-500 hover:text-blue-600 transition-colors duration-200"
            >
                <PlusIcon class="w-6 h-6 mr-2" />
                <span class="font-medium">Leeren Spielzug starten</span>
            </button>
        </div>

        <!-- Divider -->
        <div class="relative">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-200"></div>
            </div>
            <div class="relative flex justify-center">
                <span class="px-4 bg-white text-sm text-gray-500">oder wähle ein Template</span>
            </div>
        </div>

        <!-- Featured Templates Carousel -->
        <div class="relative">
            <!-- Navigation Buttons -->
            <button
                @click="scrollLeft"
                class="absolute left-0 top-1/2 -translate-y-1/2 z-10 p-2 bg-white rounded-full shadow-lg border border-gray-200 hover:bg-gray-50"
            >
                <ChevronLeftIcon class="w-5 h-5 text-gray-600" />
            </button>
            <button
                @click="scrollRight"
                class="absolute right-0 top-1/2 -translate-y-1/2 z-10 p-2 bg-white rounded-full shadow-lg border border-gray-200 hover:bg-gray-50"
            >
                <ChevronRightIcon class="w-5 h-5 text-gray-600" />
            </button>

            <!-- Templates Container -->
            <div
                ref="scrollContainer"
                class="flex gap-4 overflow-x-auto scrollbar-hide px-8 py-2 -mx-2"
                style="scroll-snap-type: x mandatory;"
            >
                <div
                    v-if="isLoading"
                    v-for="i in 3"
                    :key="i"
                    class="flex-shrink-0 w-64 h-72 bg-gray-100 rounded-lg animate-pulse"
                ></div>
                <div
                    v-else
                    v-for="template in templates"
                    :key="template.id"
                    class="flex-shrink-0 w-64"
                    style="scroll-snap-align: start;"
                >
                    <TemplateCard
                        :template="template"
                        :showFavoriteButton="false"
                        @preview="handlePreview"
                        @use="(t) => handleUse({ template: t, name: t.name })"
                    />
                </div>
            </div>
        </div>

        <!-- Browse All Link -->
        <div class="text-center">
            <button
                @click="handleBrowseAll"
                class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium"
            >
                <Squares2X2Icon class="w-5 h-5 mr-2" />
                Alle Templates durchsuchen
            </button>
        </div>

        <!-- Preview Modal -->
        <TemplatePreviewModal
            :template="previewTemplate"
            :show="showPreview"
            @close="showPreview = false"
            @use="handleUse"
        />
    </div>
</template>

<style scoped>
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
</style>
