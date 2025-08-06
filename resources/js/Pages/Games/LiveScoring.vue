<template>
    <AppLayout title="Live Scoring">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Live Scoring: {{ game.home_team.name }} vs {{ game.away_team.name }}
            </h2>
        </template>

        <div class="py-6">
            <!-- Game Status Bar -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6 lg:p-8">
                        <div class="flex justify-between items-center">
                            <div class="flex space-x-6">
                                <div class="text-center">
                                    <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                        {{ liveGame?.current_score_home || game.home_team_score || 0 }}
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ game.home_team.name }}
                                    </div>
                                </div>
                                
                                <div class="text-center px-4">
                                    <div class="text-lg font-semibold text-gray-500 dark:text-gray-400">VS</div>
                                </div>
                                
                                <div class="text-center">
                                    <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                        {{ liveGame?.current_score_away || game.away_team_score || 0 }}
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ game.away_team.name }}
                                    </div>
                                </div>
                            </div>

                            <!-- Game Clock -->
                            <div class="text-center">
                                <div class="text-2xl font-mono font-bold text-gray-900 dark:text-gray-100">
                                    {{ formatTime(liveGame?.period_time_remaining) }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ currentPeriodDisplay }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-500">
                                    Shot Clock: {{ liveGame?.shot_clock_remaining || 24 }}s
                                </div>
                            </div>

                            <!-- Game Controls -->
                            <div class="flex space-x-2">
                                <button
                                    v-if="!isGameStarted"
                                    @click="startGame"
                                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium"
                                    :disabled="processing"
                                >
                                    Spiel starten
                                </button>
                                
                                <template v-if="isGameStarted && canControl">
                                    <button
                                        v-if="!liveGame?.period_is_running"
                                        @click="controlClock('start')"
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-md text-sm font-medium"
                                        :disabled="processing"
                                    >
                                        ▶️ Start
                                    </button>
                                    <button
                                        v-else
                                        @click="controlClock('pause')"
                                        class="bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-2 rounded-md text-sm font-medium"
                                        :disabled="processing"
                                    >
                                        ⏸️ Pause
                                    </button>
                                    
                                    <button
                                        @click="controlClock('end_period')"
                                        class="bg-orange-600 hover:bg-orange-700 text-white px-3 py-2 rounded-md text-sm font-medium"
                                        :disabled="processing"
                                    >
                                        ⏹️ Ende
                                    </button>
                                    
                                    <button
                                        v-if="game.status !== 'finished'"
                                        @click="finishGame"
                                        class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-md text-sm font-medium"
                                        :disabled="processing"
                                    >
                                        🏁 Beenden
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Timeout Controls -->
                        <div v-if="isGameStarted && canControl" class="mt-4 flex justify-center space-x-4">
                            <button
                                @click="startTimeout('home')"
                                class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm"
                                :disabled="processing || liveGame?.is_in_timeout"
                            >
                                Timeout {{ game.home_team.name }} ({{ liveGame?.timeouts_home_remaining || 5 }})
                            </button>
                            <button
                                @click="startTimeout('away')"
                                class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm"
                                :disabled="processing || liveGame?.is_in_timeout"
                            >
                                Timeout {{ game.away_team.name }} ({{ liveGame?.timeouts_away_remaining || 5 }})
                            </button>
                            <button
                                @click="startTimeout('official')"
                                class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded text-sm"
                                :disabled="processing || liveGame?.is_in_timeout"
                            >
                                Offizielles Timeout
                            </button>
                            <button
                                v-if="liveGame?.is_in_timeout"
                                @click="endTimeout"
                                class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm"
                                :disabled="processing"
                            >
                                Timeout beenden
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scoring Interface -->
            <div v-if="isGameStarted" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Home Team Scoring -->
                    <ScoringPanel
                        :team="game.home_team"
                        :players="homeRoster"
                        :side="'home'"
                        @add-action="addAction"
                        :disabled="processing || !liveGame?.period_is_running"
                    />

                    <!-- Away Team Scoring -->
                    <ScoringPanel
                        :team="game.away_team"
                        :players="awayRoster"
                        :side="'away'"
                        @add-action="addAction"
                        :disabled="processing || !liveGame?.period_is_running"
                    />
                </div>

                <!-- Recent Actions -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6 lg:p-8">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                            Letzte Aktionen
                        </h3>
                        
                        <div class="space-y-2 max-h-96 overflow-y-auto">
                            <div
                                v-for="action in recentActions"
                                :key="action.id"
                                class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
                            >
                                <div class="flex items-center space-x-3">
                                    <span class="text-sm font-mono text-gray-500 dark:text-gray-400">
                                        {{ formatActionTime(action) }}
                                    </span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">
                                        {{ action.player?.name || 'Unbekannt' }}
                                    </span>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ formatActionType(action.action_type) }}
                                    </span>
                                    <span v-if="action.points > 0" class="text-sm font-bold text-green-600">
                                        +{{ action.points }}
                                    </span>
                                </div>
                                
                                <div v-if="canControl" class="flex space-x-2">
                                    <button
                                        @click="correctAction(action)"
                                        class="text-yellow-600 hover:text-yellow-800 text-sm"
                                    >
                                        ✏️ Korrigieren
                                    </button>
                                    <button
                                        @click="deleteAction(action)"
                                        class="text-red-600 hover:text-red-800 text-sm"
                                    >
                                        🗑️ Löschen
                                    </button>
                                </div>
                            </div>
                            
                            <div v-if="!recentActions?.length" class="text-center text-gray-500 dark:text-gray-400 py-8">
                                Noch keine Aktionen aufgezeichnet
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
import ScoringPanel from './Partials/ScoringPanel.vue'
import { subscribeToGame, leaveGameChannels } from '@/echo'

// Props
const props = defineProps({
    game: Object,
    liveGame: Object,
    recentActions: Array,
    homeRoster: Array,
    awayRoster: Array,
    canControl: Boolean,
})

// Reactive data
const processing = ref(false)
const localLiveGame = ref(props.liveGame)
const localRecentActions = ref(props.recentActions || [])

// Computed properties
const isGameStarted = computed(() => {
    return props.game.status !== 'scheduled'
})

const currentPeriodDisplay = computed(() => {
    if (!localLiveGame.value) return 'Nicht gestartet'
    
    const period = localLiveGame.value.current_period
    if (period <= props.game.total_periods) {
        return `${period}. Viertel`
    } else {
        return `Verlängerung ${period - props.game.total_periods}`
    }
})

// Methods
const formatTime = (timeString) => {
    if (!timeString) return '00:00'
    const parts = timeString.split(':')
    return `${parts[1]}:${parts[2]}`
}

const formatActionTime = (action) => {
    return `Q${action.period} ${formatTime(action.time_remaining)}`
}

const formatActionType = (actionType) => {
    const translations = {
        'field_goal_made': '2-Punkte-Wurf',
        'field_goal_missed': '2-Punkte-Wurf verfehlt',
        'three_point_made': '3-Punkte-Wurf',
        'three_point_missed': '3-Punkte-Wurf verfehlt',
        'free_throw_made': 'Freiwurf',
        'free_throw_missed': 'Freiwurf verfehlt',
        'rebound_offensive': 'Off. Rebound',
        'rebound_defensive': 'Def. Rebound',
        'assist': 'Assist',
        'steal': 'Steal',
        'block': 'Block',
        'turnover': 'Ballverlust',
        'foul_personal': 'Persönliches Foul',
        'foul_technical': 'Technisches Foul',
        'substitution_in': 'Einwechslung',
        'substitution_out': 'Auswechslung'
    }
    return translations[actionType] || actionType
}

const startGame = async () => {
    processing.value = true
    try {
        await router.post(route('games.start', props.game.id))
    } catch (error) {
        console.error('Error starting game:', error)
    }
    processing.value = false
}

const finishGame = async () => {
    if (!confirm('Sind Sie sicher, dass Sie das Spiel beenden möchten?')) {
        return
    }
    
    processing.value = true
    try {
        await router.post(route('games.finish', props.game.id))
    } catch (error) {
        console.error('Error finishing game:', error)
    }
    processing.value = false
}

const controlClock = async (action) => {
    processing.value = true
    try {
        await router.post(route('games.control-clock', props.game.id), {
            action: action
        })
    } catch (error) {
        console.error('Error controlling clock:', error)
    }
    processing.value = false
}

const startTimeout = async (team) => {
    processing.value = true
    try {
        await router.post(route('games.timeout', props.game.id), {
            team: team
        })
    } catch (error) {
        console.error('Error starting timeout:', error)
    }
    processing.value = false
}

const endTimeout = async () => {
    processing.value = true
    try {
        await router.delete(route('games.end-timeout', props.game.id))
    } catch (error) {
        console.error('Error ending timeout:', error)
    }
    processing.value = false
}

const addAction = async (actionData) => {
    processing.value = true
    try {
        await router.post(route('games.add-action', props.game.id), actionData)
    } catch (error) {
        console.error('Error adding action:', error)
    }
    processing.value = false
}

const correctAction = async (action) => {
    // TODO: Implement action correction modal
    console.log('Correct action:', action)
}

const deleteAction = async (action) => {
    if (!confirm('Sind Sie sicher, dass Sie diese Aktion löschen möchten?')) {
        return
    }
    
    processing.value = true
    try {
        await router.delete(route('games.delete-action', action.id))
    } catch (error) {
        console.error('Error deleting action:', error)
    }
    processing.value = false
}

// WebSocket channel
let gameChannel = null

// Lifecycle
onMounted(() => {
    // Setup WebSocket connection for real-time updates
    gameChannel = subscribeToGame(props.game.id, {
        onGameStarted: (event) => {
            console.log('Game started:', event)
            localLiveGame.value = event.live_game
            // Refresh page data
            router.reload({ only: ['game', 'liveGame'] })
        },
        
        onGameFinished: (event) => {
            console.log('Game finished:', event)
            // Refresh page data
            router.reload({ only: ['game', 'liveGame'] })
        },
        
        onActionAdded: (event) => {
            console.log('Action added:', event)
            localLiveGame.value = event.liveGame
            // Add new action to recent actions
            if (event.action) {
                localRecentActions.value.unshift(event.action)
                // Keep only last 20 actions
                if (localRecentActions.value.length > 20) {
                    localRecentActions.value = localRecentActions.value.slice(0, 20)
                }
            }
        },
        
        onScoreUpdated: (event) => {
            console.log('Score updated:', event)
            localLiveGame.value = event.liveGame
        },
        
        onClockUpdated: (event) => {
            console.log('Clock updated:', event)
            localLiveGame.value = event.liveGame
        },
        
        onTimeoutStarted: (event) => {
            console.log('Timeout started:', event)
            localLiveGame.value = event.liveGame
        },
        
        onTimeoutEnded: (event) => {
            console.log('Timeout ended:', event)
            localLiveGame.value = event.liveGame
        },
        
        onActionCorrected: (event) => {
            console.log('Action corrected:', event)
            // Find and update the corrected action
            const actionIndex = localRecentActions.value.findIndex(a => a.id === event.action.id)
            if (actionIndex !== -1) {
                localRecentActions.value[actionIndex] = event.action
            }
            localLiveGame.value = event.liveGame
        },
        
        onActionDeleted: (event) => {
            console.log('Action deleted:', event)
            // Remove the deleted action
            localRecentActions.value = localRecentActions.value.filter(a => a.id !== event.actionId)
            // Refresh game state
            router.reload({ only: ['liveGame'] })
        }
    })
})

onUnmounted(() => {
    // Cleanup WebSocket connection
    if (gameChannel) {
        leaveGameChannels(props.game.id)
    }
})
</script>