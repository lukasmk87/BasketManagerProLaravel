<template>
    <AppLayout title="Spielzüge - Taktik-Board">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Taktik-Board
                </h2>
                <Link
                    :href="route('tactic-board.plays.create')"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 transition"
                >
                    <PlusIcon class="h-4 w-4 mr-2" />
                    Neuer Spielzug
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
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
                                class="border-indigo-500 dark:border-indigo-400 text-indigo-600 dark:text-indigo-400 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
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
                                class="border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                            >
                                Bibliothek
                            </Link>
                        </nav>
                    </div>
                </div>

                <!-- Filters -->
                <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="flex flex-wrap gap-4">
                        <!-- Search -->
                        <div class="flex-1 min-w-[200px]">
                            <input
                                v-model="filterForm.search"
                                type="text"
                                placeholder="Spielzug suchen..."
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>

                        <!-- Category Filter -->
                        <div>
                            <select
                                v-model="filterForm.category"
                                class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">Alle Kategorien</option>
                                <option v-for="cat in categories" :key="cat.value" :value="cat.value">
                                    {{ cat.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Court Type Filter -->
                        <div>
                            <select
                                v-model="filterForm.court_type"
                                class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">Alle Feldtypen</option>
                                <option v-for="ct in courtTypes" :key="ct.value" :value="ct.value">
                                    {{ ct.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <select
                                v-model="filterForm.status"
                                class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">Alle Status</option>
                                <option value="draft">Entwurf</option>
                                <option value="published">Veröffentlicht</option>
                                <option value="archived">Archiviert</option>
                            </select>
                        </div>

                        <!-- Apply Filters -->
                        <button
                            @click="applyFilters"
                            class="px-4 py-2 bg-gray-200 dark:bg-gray-600 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500 transition"
                        >
                            Filter anwenden
                        </button>
                    </div>
                </div>

                <!-- Plays Grid -->
                <div v-if="plays.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <div
                        v-for="play in plays.data"
                        :key="play.id"
                        class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden hover:shadow-lg transition"
                    >
                        <!-- Thumbnail -->
                        <Link :href="route('tactic-board.plays.show', play.id)">
                            <div class="aspect-video bg-gray-200 dark:bg-gray-700 relative">
                                <img
                                    v-if="play.thumbnail_path"
                                    :src="play.thumbnail_path"
                                    :alt="play.name"
                                    class="w-full h-full object-cover"
                                />
                                <div
                                    v-else
                                    class="w-full h-full flex items-center justify-center"
                                >
                                    <DocumentIcon class="h-16 w-16 text-gray-400" />
                                </div>

                                <!-- Status Badge -->
                                <span
                                    :class="[
                                        'absolute top-2 right-2 px-2 py-1 text-xs font-semibold rounded',
                                        getStatusClass(play.status)
                                    ]"
                                >
                                    {{ getStatusLabel(play.status) }}
                                </span>
                            </div>
                        </Link>

                        <!-- Info -->
                        <div class="p-4">
                            <Link
                                :href="route('tactic-board.plays.show', play.id)"
                                class="text-lg font-semibold text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400"
                            >
                                {{ play.name }}
                            </Link>

                            <div class="mt-2 flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700">
                                    {{ getCategoryLabel(play.category) }}
                                </span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700">
                                    {{ getCourtTypeLabel(play.court_type) }}
                                </span>
                            </div>

                            <p v-if="play.description" class="mt-2 text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                {{ play.description }}
                            </p>

                            <div class="mt-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                <span>{{ play.created_by?.name || 'Unbekannt' }}</span>
                                <span>{{ formatDate(play.created_at) }}</span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 flex justify-end gap-2">
                            <Link
                                :href="route('tactic-board.plays.edit', play.id)"
                                class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                            >
                                <PencilIcon class="h-5 w-5" />
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div
                    v-else
                    class="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center"
                >
                    <DocumentIcon class="mx-auto h-16 w-16 text-gray-400" />
                    <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Keine Spielzüge gefunden
                    </h3>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Erstellen Sie Ihren ersten Spielzug, um loszulegen.
                    </p>
                    <Link
                        :href="route('tactic-board.plays.create')"
                        class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 rounded-md text-white hover:bg-blue-700 transition"
                    >
                        <PlusIcon class="h-4 w-4 mr-2" />
                        Spielzug erstellen
                    </Link>
                </div>

                <!-- Pagination -->
                <div v-if="plays.data.length > 0" class="mt-6">
                    <Pagination :links="plays.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { PlusIcon, PencilIcon, DocumentIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    plays: Object,
    filters: Object,
    categories: Array,
    courtTypes: Array,
});

// Filter form
const filterForm = reactive({
    search: props.filters?.search || '',
    category: props.filters?.category || '',
    court_type: props.filters?.court_type || '',
    status: props.filters?.status || '',
});

// Apply filters
const applyFilters = () => {
    router.get(route('tactic-board.plays.index'), filterForm, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Helper functions
const getStatusClass = (status) => {
    switch (status) {
        case 'published':
            return 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100';
        case 'draft':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100';
        case 'archived':
            return 'bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-100';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const getStatusLabel = (status) => {
    switch (status) {
        case 'published': return 'Veröffentlicht';
        case 'draft': return 'Entwurf';
        case 'archived': return 'Archiviert';
        default: return status;
    }
};

const getCategoryLabel = (category) => {
    const cat = props.categories?.find(c => c.value === category);
    return cat?.label || category;
};

const getCourtTypeLabel = (courtType) => {
    const ct = props.courtTypes?.find(c => c.value === courtType);
    return ct?.label || courtType;
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
