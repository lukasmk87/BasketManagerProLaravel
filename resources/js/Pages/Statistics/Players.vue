<template>
    <AppLayout title="Spieler-Statistiken">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Spieler-Statistiken
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Back Link -->
                <div class="mb-6">
                    <Link :href="route('statistics.index')" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Zurück zu Statistiken
                    </Link>
                </div>

                <!-- Players Table -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            Spieler-Übersicht
                        </h3>
                        
                        <div v-if="players.length === 0" class="text-center py-8 text-gray-500">
                            Keine Spieler gefunden.
                        </div>
                        
                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Spieler
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Nummer
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Position
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Team
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Verein
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Statistiken
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="player in players" :key="player.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ player.name }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">#{{ player.jersey_number }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ player.position || 'N/A' }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ player.team_name }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ player.club_name }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div v-if="player.statistics && player.statistics.games_played > 0">
                                                <div class="grid grid-cols-2 gap-2 text-xs">
                                                    <span v-if="player.statistics.avg_points">
                                                        ⌀ Punkte: {{ player.statistics.avg_points }}
                                                    </span>
                                                    <span v-if="player.statistics.avg_rebounds">
                                                        ⌀ Rebounds: {{ player.statistics.avg_rebounds }}
                                                    </span>
                                                    <span v-if="player.statistics.avg_assists">
                                                        ⌀ Assists: {{ player.statistics.avg_assists }}
                                                    </span>
                                                    <span v-if="player.statistics.games_played">
                                                        Spiele: {{ player.statistics.games_played }}
                                                    </span>
                                                    <span v-if="player.statistics.field_goal_percentage">
                                                        FG%: {{ player.statistics.field_goal_percentage }}%
                                                    </span>
                                                    <span v-if="player.statistics.three_point_percentage">
                                                        3P%: {{ player.statistics.three_point_percentage }}%
                                                    </span>
                                                </div>
                                            </div>
                                            <div v-else class="text-gray-400">
                                                Keine Spieldaten
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="mt-8 grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Spieler gesamt
                                        </dt>
                                        <dd class="text-lg font-medium text-gray-900">
                                            {{ players.length }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M3 6a3 3 0 013-3h10a1 1 0 01.8 1.6L14.25 8l2.55 3.4A1 1 0 0116 13H6a1 1 0 00-1 1v3a1 1 0 11-2 0V6z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            ⌀ Punkte/Spiel
                                        </dt>
                                        <dd class="text-lg font-medium text-gray-900">
                                            {{ calculateAveragePoints() }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            ⌀ Rebounds/Spiel
                                        </dt>
                                        <dd class="text-lg font-medium text-gray-900">
                                            {{ calculateAverageRebounds() }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M6.672 1.911a1 1 0 10-1.932.518l.259.966a1 1 0 001.932-.518l-.26-.966zM2.429 4.74a1 1 0 10-.517 1.932l.966.259a1 1 0 00.517-1.932l-.966-.26zm8.814-.569a1 1 0 00-1.415-1.414l-.707.707a1 1 0 101.415 1.414l.707-.707zm-7.071 7.072l.707-.707A1 1 0 003.465 9.12l-.708.707a1 1 0 001.415 1.415zm3.2-5.171a1 1 0 00-1.3 1.3l4 10a1 1 0 001.823.075l1.38-2.759 3.018 3.02a1 1 0 001.414-1.415l-3.019-3.02 2.76-1.379a1 1 0 00-.076-1.822l-10-4z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            ⌀ Assists/Spiel
                                        </dt>
                                        <dd class="text-lg font-medium text-gray-900">
                                            {{ calculateAverageAssists() }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    players: Array,
})

const calculateAveragePoints = () => {
    const playersWithStats = props.players.filter(p => p.statistics && p.statistics.avg_points)
    if (playersWithStats.length === 0) return '0.0'
    
    const total = playersWithStats.reduce((sum, player) => sum + parseFloat(player.statistics.avg_points), 0)
    return (total / playersWithStats.length).toFixed(1)
}

const calculateAverageRebounds = () => {
    const playersWithStats = props.players.filter(p => p.statistics && p.statistics.avg_rebounds)
    if (playersWithStats.length === 0) return '0.0'
    
    const total = playersWithStats.reduce((sum, player) => sum + parseFloat(player.statistics.avg_rebounds), 0)
    return (total / playersWithStats.length).toFixed(1)
}

const calculateAverageAssists = () => {
    const playersWithStats = props.players.filter(p => p.statistics && p.statistics.avg_assists)
    if (playersWithStats.length === 0) return '0.0'
    
    const total = playersWithStats.reduce((sum, player) => sum + parseFloat(player.statistics.avg_assists), 0)
    return (total / playersWithStats.length).toFixed(1)
}
</script>