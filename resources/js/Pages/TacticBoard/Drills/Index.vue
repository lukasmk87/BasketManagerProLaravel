<template>
    <AppLayout title="Übungen - Taktik-Board">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Taktik-Board
                </h2>
                <Link
                    :href="route('tactic-board.drills.create')"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 transition"
                >
                    <PlusIcon class="h-4 w-4 mr-2" />
                    Neue Übung
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
                                class="border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                            >
                                Spielzüge
                            </Link>
                            <Link
                                :href="route('tactic-board.drills.index')"
                                class="border-indigo-500 dark:border-indigo-400 text-indigo-600 dark:text-indigo-400 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
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
                            <Link
                                :href="route('tactic-board.categories')"
                                class="border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                            >
                                Kategorien
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
                                placeholder="Übung suchen..."
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>

                        <!-- Category Filter -->
                        <div>
                            <select
                                v-model="filterForm.category_id"
                                class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">Alle Kategorien</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                    {{ cat.name }}
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
                                <option v-for="(label, value) in courtTypes" :key="value" :value="value">
                                    {{ label }}
                                </option>
                            </select>
                        </div>

                        <!-- Difficulty Filter -->
                        <div>
                            <select
                                v-model="filterForm.difficulty_level"
                                class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">Alle Schwierigkeiten</option>
                                <option v-for="(label, value) in difficultyLevels" :key="value" :value="value">
                                    {{ label }}
                                </option>
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

                <!-- Drills Grid -->
                <div v-if="drills.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <div
                        v-for="drill in drills.data"
                        :key="drill.id"
                        class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden hover:shadow-lg transition"
                    >
                        <!-- Thumbnail -->
                        <Link :href="route('tactic-board.drills.show', drill.id)">
                            <div class="aspect-video bg-gray-200 dark:bg-gray-700 relative">
                                <img
                                    v-if="drill.thumbnail_path"
                                    :src="drill.thumbnail_path"
                                    :alt="drill.name"
                                    class="w-full h-full object-cover"
                                />
                                <div
                                    v-else
                                    class="w-full h-full flex items-center justify-center"
                                >
                                    <AcademicCapIcon class="h-16 w-16 text-gray-400" />
                                </div>

                                <!-- Visual Badge -->
                                <span
                                    v-if="drill.drill_data"
                                    class="absolute top-2 left-2 px-2 py-1 text-xs font-semibold rounded bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200"
                                >
                                    Visuell
                                </span>

                                <!-- Status Badge -->
                                <span
                                    :class="[
                                        'absolute top-2 right-2 px-2 py-1 text-xs font-semibold rounded',
                                        getStatusClass(drill.status)
                                    ]"
                                >
                                    {{ getStatusLabel(drill.status) }}
                                </span>
                            </div>
                        </Link>

                        <!-- Content -->
                        <div class="p-4">
                            <Link :href="route('tactic-board.drills.show', drill.id)">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 truncate hover:text-blue-600 dark:hover:text-blue-400">
                                    {{ drill.name }}
                                </h3>
                            </Link>
                            <p v-if="drill.description" class="mt-1 text-sm text-gray-500 dark:text-gray-400 line-clamp-2">
                                {{ drill.description }}
                            </p>
                            <div class="mt-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                <span class="inline-flex items-center" :style="{ color: drill.tactic_category?.color }">
                                    {{ drill.tactic_category?.name || getCategoryLabel(drill.category) }}
                                </span>
                                <span>{{ drill.estimated_duration }} Min</span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-3 bg-gray-50 dark:bg-gray-900 flex justify-end space-x-2">
                            <Link
                                :href="route('tactic-board.drills.edit', drill.id)"
                                class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400"
                            >
                                <PencilIcon class="h-5 w-5" />
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-12">
                    <AcademicCapIcon class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Keine Übungen</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Erstelle deine erste Übung mit dem visuellen Editor.
                    </p>
                    <div class="mt-6">
                        <Link
                            :href="route('tactic-board.drills.create')"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700"
                        >
                            <PlusIcon class="h-4 w-4 mr-2" />
                            Neue Übung erstellen
                        </Link>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="drills.data.length > 0 && drills.links" class="mt-6">
                    <nav class="flex items-center justify-between">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <Link
                                v-if="drills.prev_page_url"
                                :href="drills.prev_page_url"
                                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                            >
                                Zurück
                            </Link>
                            <Link
                                v-if="drills.next_page_url"
                                :href="drills.next_page_url"
                                class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                            >
                                Weiter
                            </Link>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                Zeige {{ drills.from }} bis {{ drills.to }} von {{ drills.total }} Einträgen
                            </p>
                        </div>
                    </nav>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    PlusIcon,
    AcademicCapIcon,
    PencilIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    drills: Object,
    filters: Object,
    categories: Array,
    courtTypes: Object,
    difficultyLevels: Object,
});

const filterForm = reactive({
    search: props.filters?.search || '',
    category_id: props.filters?.category_id || '',
    court_type: props.filters?.court_type || '',
    difficulty_level: props.filters?.difficulty_level || '',
});

const applyFilters = () => {
    const params = {};
    if (filterForm.search) params.search = filterForm.search;
    if (filterForm.category_id) params.category_id = filterForm.category_id;
    if (filterForm.court_type) params.court_type = filterForm.court_type;
    if (filterForm.difficulty_level) params.difficulty_level = filterForm.difficulty_level;

    router.get(route('tactic-board.drills.index'), params, {
        preserveState: true,
        preserveScroll: true,
    });
};

const getCategoryLabel = (category) => {
    const labels = {
        ball_handling: 'Ballhandling',
        shooting: 'Wurf',
        passing: 'Passen',
        defense: 'Verteidigung',
        rebounding: 'Rebound',
        conditioning: 'Kondition',
        agility: 'Beweglichkeit',
        footwork: 'Beinarbeit',
        team_offense: 'Team-Offense',
        team_defense: 'Team-Defense',
        transition: 'Transition',
        set_plays: 'Spielzüge',
        scrimmage: 'Scrimmage',
        warm_up: 'Aufwärmen',
        cool_down: 'Abwärmen',
    };
    return labels[category] || category;
};

const getStatusClass = (status) => {
    const classes = {
        draft: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        pending_review: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        approved: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        rejected: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        archived: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
    };
    return classes[status] || classes.draft;
};

const getStatusLabel = (status) => {
    const labels = {
        draft: 'Entwurf',
        pending_review: 'Prüfung',
        approved: 'Freigegeben',
        rejected: 'Abgelehnt',
        archived: 'Archiviert',
    };
    return labels[status] || status;
};
</script>
