<template>
    <AppLayout title="Games Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Games Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Live Games Section -->
                <div v-if="liveGames.length > 0" class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6 lg:p-8">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                                🔴 Live Spiele ({{ liveGames.length }})
                            </h3>
                            <div class="flex items-center text-red-600 dark:text-red-400">
                                <div class="w-2 h-2 bg-red-600 rounded-full animate-pulse mr-2"></div>
                                <span class="text-sm font-medium">Live</span>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
                            <GameCard
                                v-for="game in liveGames"
                                :key="`live-${game.id}`"
                                :game="game"
                                :is-live="true"
                                @view-live-scoring="goToLiveScoring"
                                @view-details="goToGameDetails"
                                @view-shot-chart="showShotChart"
                            />
                        </div>
                    </div>
                </div>

                <!-- Upcoming Games Section -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6 lg:p-8">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                                📅 Heute & Bald
                            </h3>
                            <button
                                @click="refreshGames"
                                :disabled="loading"
                                class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white px-4 py-2 rounded-md text-sm font-medium flex items-center"
                            >
                                <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ loading ? 'Aktualisiere...' : '🔄 Aktualisieren' }}
                            </button>
                        </div>
                        
                        <div v-if="upcomingGames.length > 0" class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
                            <GameCard
                                v-for="game in upcomingGames"
                                :key="`upcoming-${game.id}`"
                                :game="game"
                                :is-live="false"
                                @view-live-scoring="goToLiveScoring"
                                @view-details="goToGameDetails"
                                @view-shot-chart="showShotChart"
                            />
                        </div>
                        
                        <div v-else class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a1 1 0 011-1h6a1 1 0 011 1v4m-9 4h10m-5-4v8m0 0l-3-3m3 3l3-3"/>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Keine Spiele heute</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Es sind keine Spiele für heute geplant.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Shot Charts Section -->
                <div v-if="selectedGameForChart" class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6 lg:p-8">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                                🎯 Shot Chart - {{ selectedGameForChart.home_team?.name }} vs {{ selectedGameForChart.away_team?.name }}
                            </h3>
                            <button
                                @click="closeShotChart"
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                            >
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        
                        <ShotChart
                            :game-actions="shotChartData"
                            :player-id="selectedPlayerForChart"
                            :team-id="selectedTeamForChart"
                            :realtime="selectedGameForChart.status === 'live'"
                            @shot-selected="onShotSelected"
                        />
                    </div>
                </div>

                <!-- Recent Games Section -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6 lg:p-8">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">
                            📈 Kürzlich beendet
                        </h3>
                        
                        <div v-if="recentGames.length > 0" class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
                            <GameCard
                                v-for="game in recentGames"
                                :key="`recent-${game.id}`"
                                :game="game"
                                :is-finished="true"
                                @view-details="goToGameDetails"
                                @view-shot-chart="showShotChart"
                            />
                        </div>
                        
                        <div v-else class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Keine kürzlichen Spiele</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Es gibt keine kürzlich beendeten Spiele.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Statistics Overview -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6 lg:p-8">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">
                            📊 Heute im Überblick
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                    {{ statistics.totalGamesToday }}
                                </div>
                                <div class="text-sm text-blue-800 dark:text-blue-300">
                                    Spiele heute
                                </div>
                            </div>
                            
                            <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                                <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                    {{ statistics.liveGames }}
                                </div>
                                <div class="text-sm text-green-800 dark:text-green-300">
                                    Live Spiele
                                </div>
                            </div>
                            
                            <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
                                <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                    {{ statistics.finishedGames }}
                                </div>
                                <div class="text-sm text-purple-800 dark:text-purple-300">
                                    Beendet
                                </div>
                            </div>
                            
                            <div class="bg-orange-50 dark:bg-orange-900/20 p-4 rounded-lg">
                                <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                                    {{ statistics.averageScore }}
                                </div>
                                <div class="text-sm text-orange-800 dark:text-orange-300">
                                    ⌀ Punkte/Spiel
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
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import GameCard from './Partials/GameCard.vue'
import ShotChart from '@/Components/Basketball/ShotChart.vue'
import { subscribeToLiveGames } from '@/echo'
import axios from 'axios'

// Props
const props = defineProps({
    games: Array,
})

// Reactive data
const loading = ref(false)
const localGames = ref(props.games || [])

// Shot Chart data
const selectedGameForChart = ref(null)
const selectedPlayerForChart = ref(null)
const selectedTeamForChart = ref(null)
const shotChartData = ref([])

// Computed properties
const liveGames = computed(() => {
    return localGames.value.filter(game => 
        ['live', 'halftime', 'overtime'].includes(game.status)
    )
})

const upcomingGames = computed(() => {
    const today = new Date().toISOString().split('T')[0]
    return localGames.value.filter(game => {
        const gameDate = new Date(game.scheduled_at).toISOString().split('T')[0]
        return game.status === 'scheduled' && gameDate >= today
    }).slice(0, 12) // Limit to 12 upcoming games
})

const recentGames = computed(() => {
    return localGames.value.filter(game => game.status === 'finished')
        .sort((a, b) => new Date(b.actual_end_time) - new Date(a.actual_end_time))
        .slice(0, 9) // Last 9 finished games
})

const statistics = computed(() => {
    const today = new Date().toISOString().split('T')[0]
    const todayGames = localGames.value.filter(game => {
        const gameDate = new Date(game.scheduled_at).toISOString().split('T')[0]
        return gameDate === today
    })
    
    const finishedTodayGames = todayGames.filter(game => game.status === 'finished')
    const totalScore = finishedTodayGames.reduce((sum, game) => 
        sum + (game.home_team_score || 0) + (game.away_team_score || 0), 0
    )
    
    return {
        totalGamesToday: todayGames.length,
        liveGames: liveGames.value.length,
        finishedGames: finishedTodayGames.length,
        averageScore: finishedTodayGames.length > 0 ? 
            Math.round(totalScore / finishedTodayGames.length) : 0
    }
})

// WebSocket channel
let liveGamesChannel = null

// Methods
const refreshGames = async () => {
    loading.value = true
    try {
        // Reload the page data
        router.reload({ only: ['games'] })
    } catch (error) {
        console.error('Error refreshing games:', error)
    }
    loading.value = false
}

const goToLiveScoring = (game) => {
    router.get(route('games.live-scoring', game.id))
}

const goToGameDetails = (game) => {
    // TODO: Implement game details page
    console.log('View game details:', game)
}

// Shot Chart Methods
const showShotChart = async (game, playerId = null, teamId = null) => {
    selectedGameForChart.value = game
    selectedPlayerForChart.value = playerId
    selectedTeamForChart.value = teamId
    
    await loadShotChartData(game.id, playerId, teamId)
}

const closeShotChart = () => {
    selectedGameForChart.value = null
    selectedPlayerForChart.value = null
    selectedTeamForChart.value = null
    shotChartData.value = []
}

const loadShotChartData = async (gameId, playerId = null, teamId = null) => {
    try {
        const params = {}
        if (playerId) params.player_id = playerId
        if (teamId) params.team_id = teamId
        
        const response = await axios.get(`/api/v2/games/${gameId}/shot-chart`, {
            params
        })
        
        shotChartData.value = response.data.shots || []
    } catch (error) {
        console.error('Error loading shot chart data:', error)
        shotChartData.value = []
    }
}

const onShotSelected = (shot) => {
    console.log('Shot selected:', shot)
    // Could open a modal with shot details, player info, etc.
}

// Lifecycle
onMounted(() => {
    // Subscribe to live games updates
    liveGamesChannel = subscribeToLiveGames({
        onGameStarted: (event) => {
            console.log('Game started in dashboard:', event)
            refreshGames()
        },
        
        onGameFinished: (event) => {
            console.log('Game finished in dashboard:', event)
            refreshGames()
        },
        
        onScoreUpdated: (event) => {
            console.log('Score updated in dashboard:', event)
            // Update the specific game in local data
            const gameIndex = localGames.value.findIndex(g => g.id === event.game_id)
            if (gameIndex !== -1) {
                // Update scores from live game data
                if (event.liveGame) {
                    localGames.value[gameIndex].home_team_score = event.liveGame.current_score_home
                    localGames.value[gameIndex].away_team_score = event.liveGame.current_score_away
                }
            }
        }
    })
})

onUnmounted(() => {
    // Cleanup WebSocket connection
    if (liveGamesChannel) {
        window.Echo.leaveChannel('live-games')
    }
})
</script>