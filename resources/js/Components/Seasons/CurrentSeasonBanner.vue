<script setup>
import { Link } from '@inertiajs/vue3';
import { CalendarIcon, ChevronRightIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    club: {
        type: Object,
        required: true
    },
    season: {
        type: Object,
        required: true
    }
});

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('de-DE', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};
</script>

<template>
    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 shadow-md">
        <div class="max-w-7xl mx-auto">
            <div class="px-4 py-3 sm:px-6 lg:px-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <!-- Left: Current Season Info -->
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-10 w-10 rounded-full bg-blue-800 bg-opacity-50">
                                <CalendarIcon class="h-6 w-6 text-blue-100" />
                            </div>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-medium text-blue-200 uppercase tracking-wide">
                                Aktive Saison
                            </p>
                            <div class="flex flex-col sm:flex-row sm:items-baseline sm:space-x-2">
                                <h3 class="text-lg font-bold text-white">
                                    {{ season.name }}
                                </h3>
                                <span class="text-sm text-blue-200">
                                    {{ formatDate(season.start_date) }} - {{ formatDate(season.end_date) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Quick Stats & Action -->
                    <div class="flex items-center space-x-4 sm:space-x-6">
                        <!-- Quick Stats -->
                        <div class="flex items-center space-x-4 sm:space-x-6">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-white">
                                    {{ season.teams_count || 0 }}
                                </div>
                                <div class="text-xs text-blue-200">
                                    Teams
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-white">
                                    {{ season.games_count || 0 }}
                                </div>
                                <div class="text-xs text-blue-200">
                                    Spiele
                                </div>
                            </div>
                            <div class="hidden sm:block text-center">
                                <div class="text-2xl font-bold text-white">
                                    {{ season.players_count || 0 }}
                                </div>
                                <div class="text-xs text-blue-200">
                                    Spieler
                                </div>
                            </div>
                        </div>

                        <!-- View Details Link -->
                        <Link
                            :href="route('club.seasons.show', { club: club.id, season: season.id })"
                            class="inline-flex items-center px-4 py-2 bg-white bg-opacity-20 hover:bg-opacity-30 text-white text-sm font-medium rounded-md transition-all duration-150 ease-in-out backdrop-blur-sm"
                        >
                            Details
                            <ChevronRightIcon class="ml-1 h-4 w-4" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
