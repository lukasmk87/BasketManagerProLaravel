<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import ClubAdminLayout from '@/Layouts/ClubAdminLayout.vue';

const props = defineProps({
    club: {
        type: Object,
        default: null,
    },
    statistics: {
        type: Object,
        default: () => ({}),
    },
    teams: {
        type: Array,
        default: () => [],
    },
    upcoming_games: {
        type: Array,
        default: () => [],
    },
    recent_members: {
        type: Array,
        default: () => [],
    },
    all_clubs: {
        type: Array,
        default: () => [],
    },
    // SEC-008: Storage usage data
    storage_usage: {
        type: Object,
        default: () => ({
            used: 0,
            limit: 5,
            percentage: 0,
            formatted_used: '0 GB',
            formatted_limit: '5 GB',
            is_near_limit: false,
            is_over_limit: false,
        }),
    },
    // Current season
    current_season: {
        type: Object,
        default: null,
    },
    error: {
        type: String,
        default: null,
    },
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatJoinDate = (date) => {
    return new Date(date).toLocaleDateString('de-DE', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

// SEC-008: Computed properties for storage display
const storageProgressColor = computed(() => {
    if (props.storage_usage.percentage >= 90) return 'bg-red-500';
    if (props.storage_usage.percentage >= 75) return 'bg-yellow-500';
    return 'bg-green-500';
});

const storageIconColor = computed(() => {
    if (props.storage_usage.percentage >= 90) return 'bg-red-500';
    if (props.storage_usage.percentage >= 75) return 'bg-yellow-500';
    return 'bg-cyan-500';
});

const storageLinkColor = computed(() => {
    if (props.storage_usage.percentage >= 90) return 'text-red-600 hover:text-red-800';
    if (props.storage_usage.percentage >= 75) return 'text-yellow-600 hover:text-yellow-800';
    return 'text-cyan-600 hover:text-cyan-800';
});
</script>

<template>
    <ClubAdminLayout title="Club Admin Dashboard">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Club Admin Dashboard
                    </h2>
                    <p v-if="club" class="text-sm text-gray-600 mt-1">
                        Verwaltung für {{ club.name }}
                    </p>
                </div>
                <div v-if="club?.logo_url" class="flex items-center">
                    <img :src="club.logo_url" :alt="club.name" class="h-12 w-12 rounded-lg object-cover shadow-md">
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                <!-- Error Message -->
                <div v-if="error" class="bg-red-50 border-l-4 border-red-400 p-4 rounded-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">{{ error }}</p>
                        </div>
                    </div>
                </div>

                <!-- No Club Warning -->
                <div v-if="!club && !error" class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">Kein Club verfügbar. Bitte kontaktieren Sie einen Administrator.</p>
                        </div>
                    </div>
                </div>

                <!-- Dashboard Content (only shown when club is available) -->
                <template v-if="club">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                    <!-- Total Teams -->
                    <div class="bg-white overflow-hidden shadow-lg rounded-lg hover:shadow-xl transition-shadow">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Teams
                                        </dt>
                                        <dd class="flex items-baseline">
                                            <div class="text-2xl font-semibold text-gray-900">
                                                {{ statistics.total_teams || 0 }}
                                            </div>
                                            <div v-if="statistics.active_teams" class="ml-2 text-sm text-green-600">
                                                ({{ statistics.active_teams }} aktiv)
                                            </div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-5 py-3">
                            <Link :href="route('club-admin.teams')" class="text-sm font-medium text-blue-600 hover:text-blue-800">
                                Alle Teams anzeigen →
                            </Link>
                        </div>
                    </div>

                    <!-- Total Players -->
                    <div class="bg-white overflow-hidden shadow-lg rounded-lg hover:shadow-xl transition-shadow">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Spieler
                                        </dt>
                                        <dd class="flex items-baseline">
                                            <div class="text-2xl font-semibold text-gray-900">
                                                {{ statistics.total_players || 0 }}
                                            </div>
                                            <div v-if="statistics.active_players" class="ml-2 text-sm text-green-600">
                                                ({{ statistics.active_players }} aktiv)
                                            </div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-5 py-3">
                            <Link :href="route('club-admin.players')" class="text-sm font-medium text-green-600 hover:text-green-800">
                                Alle Spieler anzeigen →
                            </Link>
                        </div>
                    </div>

                    <!-- Pending Players -->
                    <div class="bg-white overflow-hidden shadow-lg rounded-lg hover:shadow-xl transition-shadow">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Ausstehend
                                        </dt>
                                        <dd class="flex items-baseline">
                                            <div class="text-2xl font-semibold text-gray-900">
                                                {{ statistics.pending_players || 0 }}
                                            </div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-5 py-3">
                            <Link :href="route('club-admin.pending-players.index')" class="text-sm font-medium text-yellow-600 hover:text-yellow-800">
                                Spieler zuweisen →
                            </Link>
                        </div>
                    </div>

                    <!-- Members -->
                    <div class="bg-white overflow-hidden shadow-lg rounded-lg hover:shadow-xl transition-shadow">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Mitglieder
                                        </dt>
                                        <dd class="flex items-baseline">
                                            <div class="text-2xl font-semibold text-gray-900">
                                                {{ statistics.total_members || 0 }}
                                            </div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-5 py-3">
                            <Link :href="route('club-admin.members')" class="text-sm font-medium text-purple-600 hover:text-purple-800">
                                Mitglieder verwalten →
                            </Link>
                        </div>
                    </div>

                    <!-- SEC-008: Storage Usage -->
                    <div class="bg-white overflow-hidden shadow-lg rounded-lg hover:shadow-xl transition-shadow">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div :class="[storageIconColor, 'flex-shrink-0 rounded-md p-3']">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Speicher
                                        </dt>
                                        <dd>
                                            <div class="text-lg font-semibold text-gray-900">
                                                {{ storage_usage.formatted_used }} / {{ storage_usage.formatted_limit }}
                                            </div>
                                            <!-- Progress Bar -->
                                            <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                                                <div
                                                    :class="[storageProgressColor, 'h-2 rounded-full transition-all duration-300']"
                                                    :style="{ width: Math.min(storage_usage.percentage, 100) + '%' }"
                                                ></div>
                                            </div>
                                            <div class="flex items-center mt-1">
                                                <span class="text-xs text-gray-500">{{ storage_usage.percentage }}% belegt</span>
                                                <span v-if="storage_usage.is_over_limit" class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                                    Limit erreicht!
                                                </span>
                                                <span v-else-if="storage_usage.is_near_limit" class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    Fast voll
                                                </span>
                                            </div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-5 py-3">
                            <Link :href="route('club-admin.settings')" :class="['text-sm font-medium', storageLinkColor]">
                                Speicher verwalten →
                            </Link>
                        </div>
                    </div>

                    <!-- Current Season -->
                    <div class="bg-white overflow-hidden shadow-lg rounded-lg hover:shadow-xl transition-shadow">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-orange-500 rounded-md p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Aktuelle Saison
                                        </dt>
                                        <dd class="flex items-baseline">
                                            <div class="text-2xl font-semibold text-gray-900">
                                                {{ current_season?.name || 'Keine' }}
                                            </div>
                                            <div v-if="current_season?.status" class="ml-2">
                                                <span v-if="current_season.status === 'active'" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                    Aktiv
                                                </span>
                                                <span v-else-if="current_season.status === 'draft'" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    Entwurf
                                                </span>
                                            </div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-5 py-3">
                            <Link v-if="current_season" :href="route('club.seasons.index', { club: club.id })" class="text-sm font-medium text-orange-600 hover:text-orange-800">
                                Saisons verwalten →
                            </Link>
                            <Link v-else :href="route('club.seasons.index', { club: club.id })" class="text-sm font-medium text-orange-600 hover:text-orange-800">
                                Saison erstellen →
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Schnellzugriff</h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
                            <Link :href="route('web.teams.create')" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                <svg class="w-8 h-8 text-blue-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span class="text-sm font-medium text-gray-900">Neues Team</span>
                            </Link>

                            <Link :href="route('web.players.create')" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                <svg class="w-8 h-8 text-green-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                </svg>
                                <span class="text-sm font-medium text-gray-900">Neuer Spieler</span>
                            </Link>

                            <Link :href="route('club-admin.invitations.create')" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                <svg class="w-8 h-8 text-indigo-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                                </svg>
                                <span class="text-sm font-medium text-gray-900">Einladung</span>
                            </Link>

                            <Link :href="route('web.games.create')" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                <svg class="w-8 h-8 text-orange-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-sm font-medium text-gray-900">Neues Spiel</span>
                            </Link>

                            <Link :href="route('club.seasons.index', { club: club.id })" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                <svg class="w-8 h-8 text-amber-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span class="text-sm font-medium text-gray-900">Saisons</span>
                            </Link>

                            <Link :href="route('club-admin.reports')" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                <svg class="w-8 h-8 text-purple-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span class="text-sm font-medium text-gray-900">Berichte</span>
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Teams Overview -->
                    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-900">Teams</h3>
                                <Link :href="route('club-admin.teams')" class="text-sm font-medium text-blue-600 hover:text-blue-800">
                                    Alle anzeigen →
                                </Link>
                            </div>
                        </div>
                        <div class="p-6">
                            <div v-if="teams.length > 0" class="space-y-3">
                                <div
                                    v-for="team in teams.slice(0, 5)"
                                    :key="team.id"
                                    class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
                                >
                                    <div class="flex-1">
                                        <Link :href="route('web.teams.show', team.id)" class="font-medium text-gray-900 hover:text-blue-600">
                                            {{ team.name }}
                                        </Link>
                                        <p class="text-sm text-gray-500">
                                            {{ team.age_group }} • {{ team.gender }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium text-gray-900">{{ team.player_count }} Spieler</p>
                                        <p class="text-xs text-gray-500">{{ team.games_count }} Spiele</p>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-8 text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <p class="mt-2 text-sm">Keine Teams vorhanden</p>
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming Games -->
                    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-900">Kommende Spiele</h3>
                                <Link :href="route('web.games.index')" class="text-sm font-medium text-blue-600 hover:text-blue-800">
                                    Alle anzeigen →
                                </Link>
                            </div>
                        </div>
                        <div class="p-6">
                            <div v-if="upcoming_games.length > 0" class="space-y-3">
                                <div
                                    v-for="game in upcoming_games.slice(0, 5)"
                                    :key="game.id"
                                    class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
                                >
                                    <div class="flex-1">
                                        <Link :href="route('web.games.show', game.id)" class="text-sm font-medium text-gray-900 hover:text-blue-600">
                                            {{ game.home_team?.name || game.home_team_name }} vs {{ game.away_team?.name || game.away_team_name }}
                                        </Link>
                                        <p class="text-xs text-gray-500 mt-1">
                                            {{ formatDate(game.scheduled_at) }}
                                        </p>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ game.status }}
                                    </span>
                                </div>
                            </div>
                            <div v-else class="text-center py-8 text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <p class="mt-2 text-sm">Keine anstehenden Spiele</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Members -->
                <div v-if="recent_members.length > 0" class="bg-white shadow-lg rounded-lg overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900">Neueste Mitglieder</h3>
                            <Link :href="route('club-admin.members')" class="text-sm font-medium text-blue-600 hover:text-blue-800">
                                Alle anzeigen →
                            </Link>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            <div
                                v-for="member in recent_members"
                                :key="member.id"
                                class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                            >
                                <div>
                                    <p class="font-medium text-gray-900">{{ member.name }}</p>
                                    <p class="text-sm text-gray-500">{{ member.email }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ member.role }}
                                    </span>
                                    <p class="text-xs text-gray-500 mt-1">{{ formatJoinDate(member.joined_at) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                </template>
                <!-- End of Dashboard Content -->
            </div>
        </div>
    </ClubAdminLayout>
</template>
