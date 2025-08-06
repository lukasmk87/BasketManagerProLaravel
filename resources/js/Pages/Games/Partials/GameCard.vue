<template>
    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 border hover:shadow-md transition-shadow">
        <!-- Game Status Header -->
        <div class="flex justify-between items-center mb-3">
            <div class="flex items-center">
                <span 
                    :class="statusClasses"
                    class="px-2 py-1 text-xs font-medium rounded-full"
                >
                    {{ statusText }}
                </span>
                <span v-if="isLive" class="ml-2 flex items-center text-red-500">
                    <div class="w-2 h-2 bg-red-500 rounded-full animate-pulse mr-1"></div>
                    <span class="text-xs font-medium">LIVE</span>
                </span>
            </div>
            
            <div class="text-xs text-gray-500 dark:text-gray-400">
                {{ gameType }}
            </div>
        </div>

        <!-- Teams and Score -->
        <div class="space-y-3 mb-4">
            <!-- Home Team -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                        <span class="text-xs font-bold text-blue-600 dark:text-blue-400">
                            {{ game.home_team?.short_name?.slice(0, 2) || 'H' }}
                        </span>
                    </div>
                    <div>
                        <div class="font-medium text-gray-900 dark:text-gray-100 text-sm">
                            {{ game.home_team?.name || 'Home Team' }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            Heim
                        </div>
                    </div>
                </div>
                
                <div class="text-right">
                    <div class="text-xl font-bold text-gray-900 dark:text-gray-100">
                        {{ homeScore }}
                    </div>
                </div>
            </div>

            <!-- VS Divider -->
            <div class="flex items-center justify-center">
                <div class="text-xs text-gray-400 dark:text-gray-500 bg-gray-200 dark:bg-gray-600 px-2 py-1 rounded">
                    VS
                </div>
            </div>

            <!-- Away Team -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-red-100 dark:bg-red-900 rounded-full flex items-center justify-center">
                        <span class="text-xs font-bold text-red-600 dark:text-red-400">
                            {{ game.away_team?.short_name?.slice(0, 2) || 'A' }}
                        </span>
                    </div>
                    <div>
                        <div class="font-medium text-gray-900 dark:text-gray-100 text-sm">
                            {{ game.away_team?.name || 'Away Team' }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            Gast
                        </div>
                    </div>
                </div>
                
                <div class="text-right">
                    <div class="text-xl font-bold text-gray-900 dark:text-gray-100">
                        {{ awayScore }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Game Time Info -->
        <div class="flex justify-between items-center text-xs text-gray-500 dark:text-gray-400 mb-3">
            <div>
                <span v-if="isLive && game.live_game">
                    {{ currentPeriodDisplay }} • {{ formatTime(game.live_game.period_time_remaining) }}
                </span>
                <span v-else>
                    {{ formatGameTime }}
                </span>
            </div>
            <div>
                {{ game.venue }}
            </div>
        </div>

        <!-- Winner Indicator -->
        <div v-if="isFinished && winner" class="text-center mb-3">
            <div class="text-xs text-green-600 dark:text-green-400 font-medium">
                🏆 {{ winner.name }} gewinnt
                <span v-if="marginOfVictory > 0" class="ml-1">
                    (+{{ marginOfVictory }})
                </span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex space-x-2">
            <button
                v-if="isLive || game.status === 'scheduled'"
                @click="$emit('view-live-scoring', game)"
                :class="isLive ? 'bg-red-600 hover:bg-red-700' : 'bg-blue-600 hover:bg-blue-700'"
                class="flex-1 text-white px-3 py-2 rounded-md text-xs font-medium flex items-center justify-center"
            >
                <span v-if="isLive">📺 Live Scoring</span>
                <span v-else>▶️ Starten</span>
            </button>
            
            <button
                @click="$emit('view-details', game)"
                class="flex-1 bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-800 dark:text-gray-200 px-3 py-2 rounded-md text-xs font-medium"
            >
                📊 Details
            </button>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

// Props
const props = defineProps({
    game: Object,
    isLive: {
        type: Boolean,
        default: false
    },
    isFinished: {
        type: Boolean,
        default: false
    }
})

// Emits
defineEmits(['view-live-scoring', 'view-details'])

// Computed properties
const statusText = computed(() => {
    const statusMap = {
        'scheduled': 'Geplant',
        'live': 'Live',
        'halftime': 'Halbzeit',
        'overtime': 'Verlängerung',
        'finished': 'Beendet',
        'cancelled': 'Abgesagt',
        'postponed': 'Verschoben'
    }
    return statusMap[props.game.status] || props.game.status
})

const statusClasses = computed(() => {
    const baseClasses = 'px-2 py-1 text-xs font-medium rounded-full'
    const statusColors = {
        'scheduled': 'bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-200',
        'live': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        'halftime': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        'overtime': 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
        'finished': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        'cancelled': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        'postponed': 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200'
    }
    return statusColors[props.game.status] || statusColors['scheduled']
})

const gameType = computed(() => {
    const typeMap = {
        'regular_season': 'Liga',
        'playoff': 'Playoff',
        'championship': 'Finale',
        'friendly': 'Freundschaftsspiel',
        'tournament': 'Turnier',
        'scrimmage': 'Training'
    }
    return typeMap[props.game.type] || props.game.type
})

const homeScore = computed(() => {
    if (props.isLive && props.game.live_game) {
        return props.game.live_game.current_score_home || 0
    }
    return props.game.home_team_score || 0
})

const awayScore = computed(() => {
    if (props.isLive && props.game.live_game) {
        return props.game.live_game.current_score_away || 0
    }
    return props.game.away_team_score || 0
})

const currentPeriodDisplay = computed(() => {
    if (!props.game.live_game) return ''
    
    const period = props.game.live_game.current_period
    const totalPeriods = props.game.total_periods || 4
    
    if (period <= totalPeriods) {
        return `${period}. Viertel`
    } else {
        return `Verlängerung ${period - totalPeriods}`
    }
})

const formatGameTime = computed(() => {
    if (props.isFinished) {
        return `Beendet • ${formatDateTime(props.game.actual_end_time)}`
    }
    
    if (props.game.status === 'scheduled') {
        return formatDateTime(props.game.scheduled_at)
    }
    
    return formatDateTime(props.game.actual_start_time || props.game.scheduled_at)
})

const winner = computed(() => {
    if (!props.isFinished) return null
    
    if (homeScore.value > awayScore.value) {
        return props.game.home_team
    } else if (awayScore.value > homeScore.value) {
        return props.game.away_team
    }
    
    return null // Tie
})

const marginOfVictory = computed(() => {
    if (!props.isFinished) return 0
    return Math.abs(homeScore.value - awayScore.value)
})

// Methods
const formatTime = (timeString) => {
    if (!timeString) return '00:00'
    const parts = timeString.split(':')
    return `${parts[1]}:${parts[2]}`
}

const formatDateTime = (dateTimeString) => {
    if (!dateTimeString) return ''
    
    const date = new Date(dateTimeString)
    const today = new Date()
    const yesterday = new Date(today)
    yesterday.setDate(yesterday.getDate() - 1)
    
    const timeOptions = { 
        hour: '2-digit', 
        minute: '2-digit',
        timeZone: 'Europe/Berlin'
    }
    
    const dateOptions = { 
        day: '2-digit', 
        month: '2-digit',
        timeZone: 'Europe/Berlin'
    }
    
    if (date.toDateString() === today.toDateString()) {
        return `Heute • ${date.toLocaleTimeString('de-DE', timeOptions)}`
    } else if (date.toDateString() === yesterday.toDateString()) {
        return `Gestern • ${date.toLocaleTimeString('de-DE', timeOptions)}`
    } else {
        return `${date.toLocaleDateString('de-DE', dateOptions)} • ${date.toLocaleTimeString('de-DE', timeOptions)}`
    }
}
</script>