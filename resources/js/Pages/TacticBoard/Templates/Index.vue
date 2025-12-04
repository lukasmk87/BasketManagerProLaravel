<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { MagnifyingGlassIcon, FunnelIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import AppLayout from '@/Layouts/AppLayout.vue';
import TemplateCard from '@/Components/TacticBoard/TemplateCard.vue';
import TemplatePreviewModal from '@/Components/TacticBoard/TemplatePreviewModal.vue';
import Pagination from '@/Components/Pagination.vue';
import axios from 'axios';

const props = defineProps({
    templates: Object,
    filters: Object,
    categories: Object,
    courtTypes: Object,
    availableTags: Array,
});

const search = ref(props.filters?.search || '');
const selectedCategory = ref(props.filters?.category || '');
const selectedCourtType = ref(props.filters?.court_type || '');
const selectedTags = ref(props.filters?.tags || []);
const showFilters = ref(false);
const previewTemplate = ref(null);
const showPreview = ref(false);
const isLoading = ref(false);

const hasActiveFilters = computed(() => {
    return selectedCategory.value || selectedCourtType.value || selectedTags.value.length > 0;
});

function applyFilters() {
    isLoading.value = true;
    router.get(route('tactic-board.templates'), {
        search: search.value || undefined,
        category: selectedCategory.value || undefined,
        court_type: selectedCourtType.value || undefined,
        tags: selectedTags.value.length > 0 ? selectedTags.value : undefined,
    }, {
        preserveState: true,
        onFinish: () => {
            isLoading.value = false;
        },
    });
}

function clearFilters() {
    search.value = '';
    selectedCategory.value = '';
    selectedCourtType.value = '';
    selectedTags.value = [];
    applyFilters();
}

function toggleTag(tag) {
    const index = selectedTags.value.indexOf(tag);
    if (index === -1) {
        selectedTags.value.push(tag);
    } else {
        selectedTags.value.splice(index, 1);
    }
}

function handlePreview(template) {
    previewTemplate.value = template;
    showPreview.value = true;
}

async function handleUseTemplate(data) {
    try {
        const response = await axios.post(`/api/templates/${data.template.id}/use`, {
            name: data.name,
        });
        // Redirect to edit the new play
        router.visit(route('tactic-board.plays.edit', response.data.data.id));
    } catch (error) {
        console.error('Failed to use template:', error);
    }
}

// Debounced search
let searchTimeout;
watch(search, (newValue) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 300);
});
</script>

<template>
    <Head title="Template-Galerie" />

    <AppLayout title="Template-Galerie">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Template-Galerie
                </h2>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Search and Filters -->
                <div class="mb-6 space-y-4">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <!-- Search -->
                        <div class="flex-1 relative">
                            <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Templates durchsuchen..."
                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>

                        <!-- Filter Toggle -->
                        <button
                            @click="showFilters = !showFilters"
                            :class="[
                                'inline-flex items-center px-4 py-2 border rounded-lg',
                                hasActiveFilters
                                    ? 'border-blue-500 text-blue-600 bg-blue-50'
                                    : 'border-gray-300 text-gray-700 bg-white hover:bg-gray-50'
                            ]"
                        >
                            <FunnelIcon class="w-5 h-5 mr-2" />
                            Filter
                            <span v-if="hasActiveFilters" class="ml-2 px-2 py-0.5 bg-blue-500 text-white text-xs rounded-full">
                                {{ (selectedCategory ? 1 : 0) + (selectedCourtType ? 1 : 0) + selectedTags.length }}
                            </span>
                        </button>
                    </div>

                    <!-- Filter Panel -->
                    <Transition
                        enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 -translate-y-2"
                        enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 translate-y-0"
                        leave-to-class="opacity-0 -translate-y-2"
                    >
                        <div v-if="showFilters" class="bg-white rounded-lg border border-gray-200 p-4 space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                <!-- Category -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Kategorie</label>
                                    <select
                                        v-model="selectedCategory"
                                        @change="applyFilters"
                                        class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                    >
                                        <option value="">Alle Kategorien</option>
                                        <option v-for="(label, key) in categories" :key="key" :value="key">
                                            {{ label }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Court Type -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Feldtyp</label>
                                    <select
                                        v-model="selectedCourtType"
                                        @change="applyFilters"
                                        class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                    >
                                        <option value="">Alle Feldtypen</option>
                                        <option v-for="(label, key) in courtTypes" :key="key" :value="key">
                                            {{ label }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Tags -->
                            <div v-if="availableTags && availableTags.length">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tags</label>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="tag in availableTags"
                                        :key="tag"
                                        @click="toggleTag(tag); applyFilters();"
                                        :class="[
                                            'px-3 py-1 rounded-full text-sm transition-colors',
                                            selectedTags.includes(tag)
                                                ? 'bg-blue-500 text-white'
                                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                        ]"
                                    >
                                        {{ tag }}
                                    </button>
                                </div>
                            </div>

                            <!-- Clear Filters -->
                            <div v-if="hasActiveFilters" class="flex justify-end">
                                <button
                                    @click="clearFilters"
                                    class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700"
                                >
                                    <XMarkIcon class="w-4 h-4 mr-1" />
                                    Filter zurücksetzen
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>

                <!-- Loading -->
                <div v-if="isLoading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <div v-for="i in 8" :key="i" class="bg-gray-100 rounded-lg h-80 animate-pulse"></div>
                </div>

                <!-- Templates Grid -->
                <div v-else-if="templates.data && templates.data.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <TemplateCard
                        v-for="template in templates.data"
                        :key="template.id"
                        :template="template"
                        @preview="handlePreview"
                        @use="(t) => handleUseTemplate({ template: t, name: t.name })"
                    />
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-12">
                    <div class="text-gray-400 mb-4">
                        <svg class="mx-auto h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900">Keine Templates gefunden</h3>
                    <p class="mt-1 text-gray-500">
                        Versuche es mit anderen Suchbegriffen oder Filtern.
                    </p>
                    <button
                        v-if="hasActiveFilters"
                        @click="clearFilters"
                        class="mt-4 text-blue-600 hover:text-blue-800"
                    >
                        Filter zurücksetzen
                    </button>
                </div>

                <!-- Pagination -->
                <div v-if="templates.data && templates.data.length && templates.last_page > 1" class="mt-6">
                    <Pagination :links="templates.links" />
                </div>
            </div>
        </div>

        <!-- Preview Modal -->
        <TemplatePreviewModal
            :template="previewTemplate"
            :show="showPreview"
            @close="showPreview = false"
            @use="handleUseTemplate"
        />
    </AppLayout>
</template>
