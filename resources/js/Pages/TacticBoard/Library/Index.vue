<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { MagnifyingGlassIcon, FunnelIcon, XMarkIcon, PlusIcon, StarIcon, HeartIcon, FolderIcon } from '@heroicons/vue/24/outline';
import { HeartIcon as HeartSolidIcon, StarIcon as StarSolidIcon } from '@heroicons/vue/24/solid';
import AppLayout from '@/Layouts/AppLayout.vue';
import TemplateCard from '@/Components/TacticBoard/TemplateCard.vue';
import TemplatePreviewModal from '@/Components/TacticBoard/TemplatePreviewModal.vue';
import Pagination from '@/Components/Pagination.vue';
import axios from 'axios';

const props = defineProps({
    plays: Object,
    quickAccessFavorites: Array,
    filters: Object,
    categories: Object,
    courtTypes: Object,
    availableTags: Array,
    stats: Object,
});

const activeTab = ref(props.filters?.tab || 'all');
const search = ref(props.filters?.search || '');
const selectedCategory = ref(props.filters?.category || '');
const selectedCourtType = ref(props.filters?.court_type || '');
const selectedTags = ref(props.filters?.tags || []);
const showFilters = ref(false);
const previewPlay = ref(null);
const showPreview = ref(false);
const isLoading = ref(false);

const tabs = [
    { id: 'all', label: 'Alle', icon: FolderIcon },
    { id: 'my_plays', label: 'Meine Spielzüge', icon: StarIcon },
    { id: 'favorites', label: 'Favoriten', icon: HeartIcon },
];

const hasActiveFilters = computed(() => {
    return selectedCategory.value || selectedCourtType.value || selectedTags.value.length > 0;
});

function applyFilters() {
    isLoading.value = true;
    router.get(route('tactic-board.library'), {
        tab: activeTab.value,
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

function changeTab(tabId) {
    activeTab.value = tabId;
    applyFilters();
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

function handlePreview(play) {
    previewPlay.value = play;
    showPreview.value = true;
}

async function handleUsePlay(data) {
    try {
        // If it's a template or favorited play, create a copy
        if (data.template.is_system_template || data.template.is_favorited) {
            const response = await axios.post(`/api/templates/${data.template.id}/use`, {
                name: data.name,
            });
            router.visit(route('tactic-board.plays.edit', response.data.data.id));
        } else {
            // If it's own play, just edit it
            router.visit(route('tactic-board.plays.edit', data.template.id));
        }
    } catch (error) {
        console.error('Failed to use play:', error);
    }
}

async function handleToggleFavorite(playId) {
    try {
        await axios.post(`/api/favorites/plays/${playId}/toggle`);
        // Refresh the page to update the list
        router.reload({ only: ['plays', 'quickAccessFavorites', 'stats'] });
    } catch (error) {
        console.error('Failed to toggle favorite:', error);
    }
}

async function toggleQuickAccess(favoriteId) {
    try {
        await axios.put(`/api/favorites/${favoriteId}`, {
            is_quick_access: true,
        });
        router.reload({ only: ['quickAccessFavorites'] });
    } catch (error) {
        console.error('Failed to toggle quick access:', error);
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
    <Head title="Bibliothek - Taktik-Board" />

    <AppLayout title="Bibliothek - Taktik-Board">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Taktik-Board
                </h2>
                <Link
                    :href="route('tactic-board.plays.create')"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    <PlusIcon class="w-4 h-4 mr-2" />
                    Neuer Spielzug
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Tab Navigation -->
                <div class="mb-8">
                    <div class="border-b border-gray-200 dark:border-gray-700">
                        <nav class="-mb-px flex space-x-8">
                            <Link
                                :href="route('tactic-board.index')"
                                class="border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                            >
                                Übersicht
                            </Link>
                            <Link
                                :href="route('tactic-board.plays.index')"
                                class="border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                            >
                                Spielzüge
                            </Link>
                            <Link
                                :href="route('tactic-board.drills.index')"
                                class="border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                            >
                                Übungen
                            </Link>
                            <Link
                                :href="route('tactic-board.playbooks.index')"
                                class="border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                            >
                                Playbooks
                            </Link>
                            <Link
                                :href="route('tactic-board.templates')"
                                class="border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                            >
                                Templates
                            </Link>
                            <Link
                                :href="route('tactic-board.library')"
                                class="border-indigo-500 dark:border-indigo-400 text-indigo-600 dark:text-indigo-400 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                            >
                                Bibliothek
                            </Link>
                        </nav>
                    </div>
                </div>

                <div class="flex flex-col lg:flex-row gap-6">
                    <!-- Sidebar with Quick Access -->
                    <div class="lg:w-64 flex-shrink-0">
                        <!-- Stats -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-4">
                            <h3 class="text-sm font-medium text-gray-900 mb-3">Übersicht</h3>
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Eigene Spielzüge</span>
                                    <span class="font-medium text-gray-900">{{ stats?.my_plays || 0 }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Favoriten</span>
                                    <span class="font-medium text-gray-900">{{ stats?.favorites || 0 }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Gesamt</span>
                                    <span class="font-medium text-gray-900">{{ stats?.total || 0 }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Access -->
                        <div v-if="quickAccessFavorites && quickAccessFavorites.length" class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                            <h3 class="text-sm font-medium text-gray-900 mb-3 flex items-center">
                                <StarSolidIcon class="w-4 h-4 mr-2 text-yellow-500" />
                                Schnellzugriff
                            </h3>
                            <div class="space-y-2">
                                <button
                                    v-for="favorite in quickAccessFavorites"
                                    :key="favorite.id"
                                    @click="handlePreview(favorite.play)"
                                    class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
                                >
                                    <div class="font-medium truncate">{{ favorite.play?.name }}</div>
                                    <div class="text-xs text-gray-500">{{ favorite.play?.category_display }}</div>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="flex-1">
                        <!-- Tabs -->
                        <div class="mb-6">
                            <div class="border-b border-gray-200">
                                <nav class="-mb-px flex space-x-8">
                                    <button
                                        v-for="tab in tabs"
                                        :key="tab.id"
                                        @click="changeTab(tab.id)"
                                        :class="[
                                            'group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm',
                                            activeTab === tab.id
                                                ? 'border-blue-500 text-blue-600'
                                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                        ]"
                                    >
                                        <component
                                            :is="tab.icon"
                                            :class="[
                                                'mr-2 h-5 w-5',
                                                activeTab === tab.id ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500'
                                            ]"
                                        />
                                        {{ tab.label }}
                                    </button>
                                </nav>
                            </div>
                        </div>

                        <!-- Search and Filters -->
                        <div class="mb-6 space-y-4">
                            <div class="flex flex-col sm:flex-row gap-4">
                                <!-- Search -->
                                <div class="flex-1 relative">
                                    <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                                    <input
                                        v-model="search"
                                        type="text"
                                        placeholder="Spielzüge durchsuchen..."
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
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                        <div v-if="isLoading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div v-for="i in 6" :key="i" class="bg-gray-100 rounded-lg h-80 animate-pulse"></div>
                        </div>

                        <!-- Plays Grid -->
                        <div v-else-if="plays.data && plays.data.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            <TemplateCard
                                v-for="play in plays.data"
                                :key="play.id"
                                :template="play"
                                :showFavoriteButton="true"
                                :initialFavorited="play.is_favorited"
                                @preview="handlePreview"
                                @use="(p) => handleUsePlay({ template: p, name: p.name })"
                                @favorited="handleToggleFavorite(play.id)"
                            />
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-12">
                            <div class="text-gray-400 mb-4">
                                <component
                                    :is="activeTab === 'favorites' ? HeartIcon : FolderIcon"
                                    class="mx-auto h-12 w-12"
                                />
                            </div>
                            <h3 class="text-lg font-medium text-gray-900">
                                {{ activeTab === 'favorites' ? 'Keine Favoriten' : 'Keine Spielzüge gefunden' }}
                            </h3>
                            <p class="mt-1 text-gray-500">
                                <template v-if="activeTab === 'favorites'">
                                    Füge Spielzüge zu deinen Favoriten hinzu, um sie hier zu sehen.
                                </template>
                                <template v-else-if="activeTab === 'my_plays'">
                                    Erstelle deinen ersten eigenen Spielzug.
                                </template>
                                <template v-else>
                                    Versuche es mit anderen Suchbegriffen oder Filtern.
                                </template>
                            </p>
                            <div class="mt-4 space-x-4">
                                <Link
                                    v-if="activeTab !== 'favorites'"
                                    :href="route('tactic-board.plays.create')"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                                >
                                    <PlusIcon class="w-4 h-4 mr-2" />
                                    Spielzug erstellen
                                </Link>
                                <Link
                                    v-if="activeTab === 'favorites'"
                                    :href="route('tactic-board.templates')"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                                >
                                    Templates durchsuchen
                                </Link>
                                <button
                                    v-if="hasActiveFilters"
                                    @click="clearFilters"
                                    class="text-blue-600 hover:text-blue-800"
                                >
                                    Filter zurücksetzen
                                </button>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div v-if="plays.data && plays.data.length && plays.last_page > 1" class="mt-6">
                            <Pagination :links="plays.links" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview Modal -->
        <TemplatePreviewModal
            :template="previewPlay"
            :show="showPreview"
            @close="showPreview = false"
            @use="handleUsePlay"
        />
    </AppLayout>
</template>
