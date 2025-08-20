<template>
    <AppLayout title="Spiel-Statistiken">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Spiel-Statistiken
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

                <!-- Games Table -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            Spiel-Übersicht (Letzte 50 Spiele)
                        </h3>
                        
                        <div v-if="games.length === 0" class="text-center py-8 text-gray-500">
                            Keine abgeschlossenen Spiele gefunden.
                        </div>
                        
                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Datum
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Begegnung
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Ergebnis
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Ort
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Statistiken
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="game in games" :key="game.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                {{ formatDate(game.scheduled_at) }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ game.home_team }} vs {{ game.away_team }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 font-mono">
                                                <span :class="getScoreClass(game.home_score, game.away_score)">
                                                    {{ game.home_score }}
                                                </span>
                                                :
                                                <span :class="getScoreClass(game.away_score, game.home_score)">
                                                    {{ game.away_score }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ game.location || 'N/A' }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div v-if="game.statistics">
                                                <div class="grid grid-cols-2 gap-2 text-xs">
                                                    <span v-if="game.statistics.total_actions">
                                                        Aktionen: {{ game.statistics.total_actions }}
                                                    </span>
                                                    <span v-if="game.statistics.duration">
                                                        Dauer: {{ game.statistics.duration }}min
                                                    </span>
                                                    <span v-if="game.statistics.home_team_actions">
                                                        Heim: {{ game.statistics.home_team_actions }}
                                                    </span>
                                                    <span v-if="game.statistics.away_team_actions">
                                                        Gast: {{ game.statistics.away_team_actions }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div v-else class="text-gray-400">
                                                Keine Daten
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
                                            <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Spiele gesamt
                                        </dt>
                                        <dd class="text-lg font-medium text-gray-900">
                                            {{ games.length }}
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
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            ⌀ Punkte/Spiel
                                        </dt>
                                        <dd class="text-lg font-medium text-gray-900">
                                            {{ calculateAverageScore() }}
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
                                            <path fill-rule="evenodd" d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z" clip-rule="evenodd"/>
                                            <path fill-rule="evenodd" d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Höchstes Ergebnis
                                        </dt>
                                        <dd class="text-lg font-medium text-gray-900">
                                            {{ getHighestScore() }}
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
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            ⌀ Spieldauer
                                        </dt>
                                        <dd class="text-lg font-medium text-gray-900">
                                            {{ calculateAverageDuration() }}min
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
    games: Array,
})

const formatDate = (dateString) => {
    const date = new Date(dateString)
    return date.toLocaleDateString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getScoreClass = (score, opponentScore) => {
    if (score > opponentScore) {
        return 'font-bold text-green-600'
    } else if (score < opponentScore) {
        return 'text-red-600'
    }
    return 'text-gray-600'
}

const calculateAverageScore = () => {
    if (props.games.length === 0) return '0'
    
    const totalScore = props.games.reduce((sum, game) => {
        return sum + (game.home_score || 0) + (game.away_score || 0)
    }, 0)
    
    return Math.round(totalScore / (props.games.length * 2))
}

const getHighestScore = () => {
    if (props.games.length === 0) return '0'
    
    let highest = 0
    props.games.forEach(game => {
        highest = Math.max(highest, game.home_score || 0, game.away_score || 0)
    })
    
    return highest
}

const calculateAverageDuration = () => {
    const gamesWithDuration = props.games.filter(g => g.statistics && g.statistics.duration)
    if (gamesWithDuration.length === 0) return '40'
    
    const total = gamesWithDuration.reduce((sum, game) => sum + game.statistics.duration, 0)
    return Math.round(total / gamesWithDuration.length)
}
</script>