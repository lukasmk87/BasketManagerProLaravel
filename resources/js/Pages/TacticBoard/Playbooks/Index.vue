<template>
    <AppLayout title="Playbooks">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('tactic-board.index')"
                        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        <ArrowLeftIcon class="h-5 w-5" />
                    </Link>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        Playbooks
                    </h2>
                </div>
                <Link
                    :href="route('tactic-board.playbooks.create')"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 transition"
                >
                    <PlusIcon class="h-4 w-4 mr-2" />
                    Neues Playbook
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Filters -->
                <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="flex flex-wrap gap-4">
                        <!-- Search -->
                        <div class="flex-1 min-w-[200px]">
                            <input
                                v-model="filterForm.search"
                                type="text"
                                placeholder="Playbook suchen..."
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

                        <!-- Apply Filters -->
                        <button
                            @click="applyFilters"
                            class="px-4 py-2 bg-gray-200 dark:bg-gray-600 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500 transition"
                        >
                            Filter anwenden
                        </button>
                    </div>
                </div>

                <!-- Playbooks Grid -->
                <div v-if="playbooks.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="playbook in playbooks.data"
                        :key="playbook.id"
                        class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden hover:shadow-lg transition"
                    >
                        <Link :href="route('tactic-board.playbooks.show', playbook.id)">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                        {{ playbook.name }}
                                    </h3>
                                    <span v-if="playbook.is_default" class="px-2 py-1 text-xs font-semibold rounded bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">
                                        Standard
                                    </span>
                                </div>

                                <p v-if="playbook.description" class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                                    {{ playbook.description }}
                                </p>

                                <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700">
                                        {{ getCategoryLabel(playbook.category) }}
                                    </span>
                                    <span v-if="playbook.team" class="inline-flex items-center px-2 py-0.5 rounded bg-blue-100 dark:bg-blue-800 text-blue-800 dark:text-blue-100">
                                        {{ playbook.team.name }}
                                    </span>
                                </div>

                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500 dark:text-gray-400">
                                        {{ playbook.plays_count || 0 }} Spielzüge
                                    </span>
                                    <span class="text-gray-500 dark:text-gray-400">
                                        {{ formatDate(playbook.created_at) }}
                                    </span>
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>

                <!-- Empty State -->
                <div
                    v-else
                    class="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center"
                >
                    <BookOpenIcon class="mx-auto h-16 w-16 text-gray-400" />
                    <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Keine Playbooks gefunden
                    </h3>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Erstellen Sie Ihr erstes Playbook, um Spielzüge zu organisieren.
                    </p>
                    <Link
                        :href="route('tactic-board.playbooks.create')"
                        class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 rounded-md text-white hover:bg-blue-700 transition"
                    >
                        <PlusIcon class="h-4 w-4 mr-2" />
                        Playbook erstellen
                    </Link>
                </div>

                <!-- Pagination -->
                <div v-if="playbooks.data.length > 0" class="mt-6">
                    <Pagination :links="playbooks.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { PlusIcon, ArrowLeftIcon, BookOpenIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    playbooks: Object,
    filters: Object,
    categories: Array,
});

// Filter form
const filterForm = reactive({
    search: props.filters?.search || '',
    category: props.filters?.category || '',
});

// Apply filters
const applyFilters = () => {
    router.get(route('tactic-board.playbooks.index'), filterForm, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Helper functions
const getCategoryLabel = (category) => {
    const cat = props.categories?.find(c => c.value === category);
    return cat?.label || category;
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
