<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SeasonStatusBadge from '@/Components/Seasons/SeasonStatusBadge.vue';
import Pagination from '@/Components/Pagination.vue';
import { PencilIcon, TrashIcon, CheckCircleIcon, PlayIcon, EyeIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    club: {
        type: Object,
        required: true
    },
    seasons: {
        type: Object, // Paginated data
        required: true
    }
});

const canManageSeasons = computed(() => {
    return props.$page?.props?.auth?.permissions?.includes('manage seasons') || false;
});

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('de-DE', { year: 'numeric', month: 'short', day: 'numeric' });
};

const handleView = (season) => {
    router.visit(route('club-admin.seasons.show', season.id));
};

const handleEdit = (season) => {
    router.visit(route('club-admin.seasons.edit', season.id));
};

const handleActivate = (season) => {
    if (confirm(`Möchten Sie die Saison "${season.name}" wirklich aktivieren?`)) {
        router.post(route('club-admin.seasons.activate', season.id));
    }
};

const handleComplete = (season) => {
    if (confirm(`Möchten Sie die Saison "${season.name}" wirklich abschließen?`)) {
        router.post(route('club-admin.seasons.complete', season.id), {
            create_snapshots: true
        });
    }
};

const handleDelete = (season) => {
    if (confirm(`Möchten Sie die Saison "${season.name}" wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden!`)) {
        router.delete(route('club-admin.seasons.destroy', season.id));
    }
};
</script>

<template>
    <AppLayout title="Saisons">
        <Head :title="`Saisons - ${club.name}`" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Page Header -->
                <div class="md:flex md:items-center md:justify-between mb-8">
                    <div class="flex-1 min-w-0">
                        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                            Saisons
                        </h2>
                        <p class="mt-1 text-sm text-gray-500">
                            Alle Saisons für {{ club.name }}
                        </p>
                    </div>
                    <div class="mt-4 flex md:mt-0 md:ml-4">
                        <Link
                            :href="route('club-admin.seasons.create')"
                            class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            Neue Saison
                        </Link>
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white shadow overflow-hidden sm:rounded-md">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Saison
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Zeitraum
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Teams
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Spiele
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Spieler
                                    </th>
                                    <th scope="col" class="relative px-6 py-3">
                                        <span class="sr-only">Aktionen</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr
                                    v-for="season in seasons.data"
                                    :key="season.id"
                                    class="hover:bg-gray-50 transition-colors duration-150"
                                    :class="{ 'bg-blue-50': season.is_current }"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div>
                                                <div class="flex items-center space-x-2">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {{ season.name }}
                                                    </div>
                                                    <span v-if="season.is_current" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                        Aktiv
                                                    </span>
                                                </div>
                                                <div v-if="season.description" class="text-sm text-gray-500 mt-1">
                                                    {{ season.description }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            {{ formatDate(season.start_date) }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            bis {{ formatDate(season.end_date) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <SeasonStatusBadge :status="season.status" />
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ season.teams_count || 0 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ season.games_count || 0 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ season.players_count || 0 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end space-x-2">
                                            <!-- View -->
                                            <button
                                                @click="handleView(season)"
                                                class="text-blue-600 hover:text-blue-900"
                                                title="Anzeigen"
                                            >
                                                <EyeIcon class="h-5 w-5" />
                                            </button>

                                            <!-- Edit -->
                                            <button
                                                v-if="canManageSeasons"
                                                @click="handleEdit(season)"
                                                class="text-indigo-600 hover:text-indigo-900"
                                                title="Bearbeiten"
                                            >
                                                <PencilIcon class="h-5 w-5" />
                                            </button>

                                            <!-- Activate -->
                                            <button
                                                v-if="canManageSeasons && season.status === 'draft'"
                                                @click="handleActivate(season)"
                                                class="text-green-600 hover:text-green-900"
                                                title="Aktivieren"
                                            >
                                                <PlayIcon class="h-5 w-5" />
                                            </button>

                                            <!-- Complete -->
                                            <button
                                                v-if="canManageSeasons && season.status === 'active'"
                                                @click="handleComplete(season)"
                                                class="text-blue-600 hover:text-blue-900"
                                                title="Abschließen"
                                            >
                                                <CheckCircleIcon class="h-5 w-5" />
                                            </button>

                                            <!-- Delete -->
                                            <button
                                                v-if="canManageSeasons && season.status !== 'active'"
                                                @click="handleDelete(season)"
                                                class="text-red-600 hover:text-red-900"
                                                title="Löschen"
                                            >
                                                <TrashIcon class="h-5 w-5" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="seasons.data.length > 0" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                        <Pagination :links="seasons.links" />
                    </div>

                    <!-- Empty State -->
                    <div v-if="seasons.data.length === 0" class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Keine Saisons</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Es wurden noch keine Saisons für diesen Club erstellt.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
