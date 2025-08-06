<template>
    <div class="min-h-screen bg-gray-900 text-white">
        <!-- Top Status Bar -->
        <div class="sticky top-0 z-50 bg-gray-800 border-b border-gray-700">
            <div class="px-4 py-3">
                <div class="flex items-center justify-between">
                    <!-- Teams & Score -->
                    <div class="flex items-center space-x-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-400">
                                {{ liveGame?.current_score_home || 0 }}
                            </div>
                            <div class="text-xs text-gray-400 truncate max-w-16">
                                {{ game.home_team?.short_name || 'HOME' }}
                            </div>
                        </div>
                        
                        <div class="text-xs text-gray-500">VS</div>
                        
                        <div class="text-center">
                            <div class="text-2xl font-bold text-red-400">
                                {{ liveGame?.current_score_away || 0 }}
                            </div>
                            <div class="text-xs text-gray-400 truncate max-w-16">
                                {{ game.away_team?.short_name || 'AWAY' }}
                            </div>
                        </div>
                    </div>

                    <!-- Game Clock -->
                    <div class="text-center">
                        <div class="text-xl font-mono font-bold">
                            {{ formatTime(liveGame?.period_time_remaining) }}
                        </div>
                        <div class="text-xs text-gray-400">
                            {{ currentPeriodDisplay }}
                        </div>
                        <div class="text-xs text-yellow-400">
                            Shot: {{ liveGame?.shot_clock_remaining || 24 }}s
                        </div>
                    </div>

                    <!-- Controls -->
                    <div class="flex space-x-1">
                        <button
                            v-if="!liveGame?.period_is_running"
                            @click="controlClock('start')"
                            class="bg-green-600 hover:bg-green-700 p-2 rounded"
                            :disabled="processing"
                        >
                            ▶️
                        </button>
                        <button
                            v-else
                            @click="controlClock('pause')"
                            class="bg-yellow-600 hover:bg-yellow-700 p-2 rounded"
                            :disabled="processing"
                        >
                            ⏸️
                        </button>
                        
                        <button
                            @click="showMenu = !showMenu"
                            class="bg-gray-600 hover:bg-gray-700 p-2 rounded"
                        >
                            ⚙️
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Menu Overlay -->
        <div v-if="showMenu" class="fixed inset-0 z-40 bg-black bg-opacity-50" @click="showMenu = false">
            <div class="fixed top-16 right-4 bg-gray-800 rounded-lg p-4 shadow-xl" @click.stop>
                <div class="space-y-2 text-sm">
                    <button @click="controlClock('end_period')" class="w-full text-left p-2 hover:bg-gray-700 rounded">
                        ⏹️ Viertel beenden
                    </button>
                    <button @click="openTimeoutMenu" class="w-full text-left p-2 hover:bg-gray-700 rounded">
                        ⏰ Timeout
                    </button>
                    <button @click="finishGame" class="w-full text-left p-2 hover:bg-gray-700 rounded text-red-400">
                        🏁 Spiel beenden
                    </button>
                </div>
            </div>
        </div>

        <!-- Team Selection Tabs -->
        <div class="bg-gray-800 border-b border-gray-700">
            <div class="flex">
                <button
                    @click="activeTeam = 'home'"
                    :class="activeTeam === 'home' ? 'bg-blue-600 text-white' : 'bg-gray-700 text-gray-300'"
                    class="flex-1 py-3 px-4 font-medium text-center"
                >
                    {{ game.home_team?.name || 'Home Team' }}
                </button>
                <button
                    @click="activeTeam = 'away'"
                    :class="activeTeam === 'away' ? 'bg-red-600 text-white' : 'bg-gray-700 text-gray-300'"
                    class="flex-1 py-3 px-4 font-medium text-center"
                >
                    {{ game.away_team?.name || 'Away Team' }}
                </button>
            </div>
        </div>

        <!-- Main Scoring Interface -->
        <div class="flex-1 p-4">
            <!-- Player Selection -->
            <div class="mb-4">
                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-2">
                    <button
                        v-for="player in currentRoster"
                        :key="player.id"
                        @click="selectedPlayerId = player.id"
                        :class="selectedPlayerId === player.id ? 
                            (activeTeam === 'home' ? 'bg-blue-600' : 'bg-red-600') : 
                            'bg-gray-700 hover:bg-gray-600'"
                        class="aspect-square rounded-lg flex flex-col items-center justify-center p-2"
                    >
                        <div class="text-lg font-bold">
                            {{ player.jersey_number || '?' }}
                        </div>
                        <div class="text-xs truncate w-full text-center">
                            {{ player.name?.split(' ').pop() || 'Player' }}
                        </div>
                    </button>
                </div>
                
                <div v-if="selectedPlayer" class="mt-2 text-center">
                    <span class="text-lg font-medium">
                        #{{ selectedPlayer.jersey_number }} {{ selectedPlayer.name }}
                    </span>
                </div>
            </div>

            <!-- Quick Score Buttons -->
            <div class="mb-6">
                <div class="grid grid-cols-3 gap-3">
                    <button
                        @click="quickScore('free_throw_made', 1)"
                        :disabled="!selectedPlayerId || processing"
                        class="aspect-square bg-green-600 hover:bg-green-700 disabled:bg-gray-600 rounded-xl flex flex-col items-center justify-center p-4"
                    >
                        <div class="text-3xl font-bold">+1</div>
                        <div class="text-xs">Freiwurf</div>
                    </button>
                    
                    <button
                        @click="quickScore('field_goal_made', 2)"
                        :disabled="!selectedPlayerId || processing"
                        class="aspect-square bg-blue-600 hover:bg-blue-700 disabled:bg-gray-600 rounded-xl flex flex-col items-center justify-center p-4"
                    >
                        <div class="text-3xl font-bold">+2</div>
                        <div class="text-xs">2-Punkte</div>
                    </button>
                    
                    <button
                        @click="quickScore('three_point_made', 3)"
                        :disabled="!selectedPlayerId || processing"
                        class="aspect-square bg-purple-600 hover:bg-purple-700 disabled:bg-gray-600 rounded-xl flex flex-col items-center justify-center p-4"
                    >
                        <div class="text-3xl font-bold">+3</div>
                        <div class="text-xs">3-Punkte</div>
                    </button>
                </div>
            </div>

            <!-- Action Categories -->
            <div class="space-y-4">
                <!-- Missed Shots -->
                <div>
                    <h3 class="text-sm font-medium text-gray-300 mb-2">Fehlwürfe</h3>
                    <div class="grid grid-cols-3 gap-2">
                        <button
                            @click="addAction('field_goal_missed', { is_successful: false })"
                            :disabled="!selectedPlayerId || processing"
                            class="bg-red-700 hover:bg-red-600 disabled:bg-gray-600 py-3 rounded-lg text-sm"
                        >
                            2P verfehlt
                        </button>
                        <button
                            @click="addAction('three_point_missed', { is_successful: false })"
                            :disabled="!selectedPlayerId || processing"
                            class="bg-red-700 hover:bg-red-600 disabled:bg-gray-600 py-3 rounded-lg text-sm"
                        >
                            3P verfehlt
                        </button>
                        <button
                            @click="addAction('free_throw_missed', { is_successful: false })"
                            :disabled="!selectedPlayerId || processing"
                            class="bg-red-700 hover:bg-red-600 disabled:bg-gray-600 py-3 rounded-lg text-sm"
                        >
                            FW verfehlt
                        </button>
                    </div>
                </div>

                <!-- Rebounds & Defense -->
                <div>
                    <h3 class="text-sm font-medium text-gray-300 mb-2">Rebounds & Defense</h3>
                    <div class="grid grid-cols-4 gap-2">
                        <button
                            @click="addAction('rebound_offensive')"
                            :disabled="!selectedPlayerId || processing"
                            class="bg-orange-700 hover:bg-orange-600 disabled:bg-gray-600 py-3 rounded-lg text-sm"
                        >
                            Off. Reb.
                        </button>
                        <button
                            @click="addAction('rebound_defensive')"
                            :disabled="!selectedPlayerId || processing"
                            class="bg-blue-700 hover:bg-blue-600 disabled:bg-gray-600 py-3 rounded-lg text-sm"
                        >
                            Def. Reb.
                        </button>
                        <button
                            @click="addAction('steal')"
                            :disabled="!selectedPlayerId || processing"
                            class="bg-teal-700 hover:bg-teal-600 disabled:bg-gray-600 py-3 rounded-lg text-sm"
                        >
                            Steal
                        </button>
                        <button
                            @click="addAction('block')"
                            :disabled="!selectedPlayerId || processing"
                            class="bg-cyan-700 hover:bg-cyan-600 disabled:bg-gray-600 py-3 rounded-lg text-sm"
                        >
                            Block
                        </button>
                    </div>
                </div>

                <!-- Assists & Other -->
                <div>
                    <h3 class="text-sm font-medium text-gray-300 mb-2">Sonstiges</h3>
                    <div class="grid grid-cols-3 gap-2">
                        <button
                            @click="addAction('assist')"
                            :disabled="!selectedPlayerId || processing"
                            class="bg-indigo-700 hover:bg-indigo-600 disabled:bg-gray-600 py-3 rounded-lg text-sm"
                        >
                            Assist
                        </button>
                        <button
                            @click="addAction('turnover')"
                            :disabled="!selectedPlayerId || processing"
                            class="bg-yellow-700 hover:bg-yellow-600 disabled:bg-gray-600 py-3 rounded-lg text-sm"
                        >
                            Ballverlust
                        </button>
                        <button
                            @click="openFoulDialog"
                            :disabled="!selectedPlayerId || processing"
                            class="bg-red-700 hover:bg-red-600 disabled:bg-gray-600 py-3 rounded-lg text-sm"
                        >
                            Foul
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Actions (Bottom) -->
        <div class="bg-gray-800 border-t border-gray-700 max-h-32 overflow-y-auto">
            <div class="p-2">
                <div class="text-xs text-gray-400 mb-1">Letzte Aktionen:</div>
                <div class="space-y-1">
                    <div
                        v-for="action in recentActions.slice(0, 3)"
                        :key="action.id"
                        class="flex justify-between items-center text-xs"
                    >
                        <span>
                            {{ formatActionTime(action) }} • {{ action.player?.name }} • {{ formatActionType(action.action_type) }}
                        </span>
                        <span v-if="action.points > 0" class="text-green-400 font-bold">
                            +{{ action.points }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Foul Dialog -->
        <div v-if="showFoulDialog" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-75 p-4">
            <div class="bg-gray-800 rounded-lg p-6 w-full max-w-sm">
                <h3 class="text-lg font-bold mb-4">Foul-Art auswählen</h3>
                <div class="space-y-2">
                    <button
                        @click="addFoul('foul_personal')"
                        class="w-full text-left bg-gray-700 hover:bg-gray-600 p-3 rounded"
                    >
                        Persönliches Foul
                    </button>
                    <button
                        @click="addFoul('foul_technical')"
                        class="w-full text-left bg-gray-700 hover:bg-gray-600 p-3 rounded"
                    >
                        Technisches Foul
                    </button>
                    <button
                        @click="addFoul('foul_offensive')"
                        class="w-full text-left bg-gray-700 hover:bg-gray-600 p-3 rounded"
                    >
                        Offensiv Foul
                    </button>
                </div>
                <button
                    @click="showFoulDialog = false"
                    class="w-full mt-4 bg-gray-600 hover:bg-gray-500 text-white py-2 rounded"
                >
                    Abbrechen
                </button>
            </div>
        </div>

        <!-- Timeout Dialog -->
        <div v-if="showTimeoutDialog" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-75 p-4">
            <div class="bg-gray-800 rounded-lg p-6 w-full max-w-sm">
                <h3 class="text-lg font-bold mb-4">Timeout</h3>
                <div class="space-y-2">
                    <button
                        @click="startTimeout('home')"
                        class="w-full text-left bg-blue-700 hover:bg-blue-600 p-3 rounded"
                    >
                        {{ game.home_team?.name }} ({{ liveGame?.timeouts_home_remaining || 5 }})
                    </button>
                    <button
                        @click="startTimeout('away')"
                        class="w-full text-left bg-red-700 hover:bg-red-600 p-3 rounded"
                    >
                        {{ game.away_team?.name }} ({{ liveGame?.timeouts_away_remaining || 5 }})
                    </button>
                    <button
                        @click="startTimeout('official')"
                        class="w-full text-left bg-gray-700 hover:bg-gray-600 p-3 rounded"
                    >
                        Offizielles Timeout
                    </button>
                </div>
                <button
                    @click="showTimeoutDialog = false"
                    class="w-full mt-4 bg-gray-600 hover:bg-gray-500 text-white py-2 rounded"
                >
                    Abbrechen
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { subscribeToGame, leaveGameChannels } from '@/echo'

// Props
const props = defineProps({
    game: Object,
    liveGame: Object,
    recentActions: Array,
    homeRoster: Array,
    awayRoster: Array,
})

// Reactive data
const processing = ref(false)
const activeTeam = ref('home')
const selectedPlayerId = ref('')
const showMenu = ref(false)
const showFoulDialog = ref(false)
const showTimeoutDialog = ref(false)
const localLiveGame = ref(props.liveGame)
const localRecentActions = ref(props.recentActions || [])

// Computed properties
const currentRoster = computed(() => {
    return activeTeam.value === 'home' ? props.homeRoster : props.awayRoster
})

const selectedPlayer = computed(() => {
    return currentRoster.value.find(p => p.id === selectedPlayerId.value)
})

const currentPeriodDisplay = computed(() => {
    if (!localLiveGame.value) return 'Nicht gestartet'
    
    const period = localLiveGame.value.current_period
    if (period <= (props.game.total_periods || 4)) {
        return `${period}. Viertel`
    } else {
        return `Verl. ${period - (props.game.total_periods || 4)}`
    }
})

// Methods (same as regular LiveScoring component but simplified)
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
        'field_goal_made': '2P',
        'three_point_made': '3P',
        'free_throw_made': 'FW',
        'rebound_offensive': 'Off.Reb',
        'rebound_defensive': 'Def.Reb',
        'assist': 'Assist',
        'steal': 'Steal',
        'block': 'Block',
        'turnover': 'TO',
        'foul_personal': 'Foul'
    }
    return translations[actionType] || actionType
}

const quickScore = (actionType, points) => {
    addAction(actionType, { points, is_successful: true })
}

const addAction = async (actionType, additionalData = {}) => {
    if (!selectedPlayerId.value) return
    
    processing.value = true
    try {
        const teamId = activeTeam.value === 'home' ? props.game.home_team_id : props.game.away_team_id
        await router.post(route('games.add-action', props.game.id), {
            player_id: selectedPlayerId.value,
            team_id: teamId,
            action_type: actionType,
            ...additionalData
        })
    } catch (error) {
        console.error('Error adding action:', error)
    }
    processing.value = false
}

const controlClock = async (action) => {
    processing.value = true
    try {
        await router.post(route('games.control-clock', props.game.id), { action })
    } catch (error) {
        console.error('Error controlling clock:', error)
    }
    processing.value = false
}

const startTimeout = async (team) => {
    processing.value = true
    showTimeoutDialog.value = false
    try {
        await router.post(route('games.timeout', props.game.id), { team })
    } catch (error) {
        console.error('Error starting timeout:', error)
    }
    processing.value = false
}

const finishGame = async () => {
    if (!confirm('Spiel wirklich beenden?')) return
    
    processing.value = true
    try {
        await router.post(route('games.finish', props.game.id))
    } catch (error) {
        console.error('Error finishing game:', error)
    }
    processing.value = false
}

const openTimeoutMenu = () => {
    showMenu.value = false
    showTimeoutDialog.value = true
}

const openFoulDialog = () => {
    showFoulDialog.value = true
}

const addFoul = (foulType) => {
    addAction(foulType, {
        foul_type: foulType.replace('foul_', ''),
        points: 0
    })
    showFoulDialog.value = false
}

// WebSocket setup (similar to regular LiveScoring)
let gameChannel = null

onMounted(() => {
    gameChannel = subscribeToGame(props.game.id, {
        onActionAdded: (event) => {
            localLiveGame.value = event.liveGame
            if (event.action) {
                localRecentActions.value.unshift(event.action)
            }
        },
        onScoreUpdated: (event) => {
            localLiveGame.value = event.liveGame
        },
        onClockUpdated: (event) => {
            localLiveGame.value = event.liveGame
        }
    })
})

onUnmounted(() => {
    if (gameChannel) {
        leaveGameChannels(props.game.id)
    }
})
</script>

<style scoped>
/* Prevent zoom on double tap for mobile */
button {
    touch-action: manipulation;
}

/* Hide scrollbar but keep functionality */
.overflow-y-auto::-webkit-scrollbar {
    display: none;
}
.overflow-y-auto {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>