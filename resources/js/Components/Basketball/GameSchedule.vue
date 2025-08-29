<script setup>
import { computed } from 'vue';

const props = defineProps({
    upcomingGames: {
        type: Array,
        default: () => []
    },
    recentGames: {
        type: Array,
        default: () => []
    },
    title: {
        type: String,
        default: 'Spielplan'
    },
    showRecent: {
        type: Boolean,
        default: true
    },
    maxUpcoming: {
        type: Number,
        default: 5
    },
    maxRecent: {
        type: Number,
        default: 3
    },
    compact: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['game-click', 'view-all-upcoming', 'view-all-recent']);

const displayUpcomingGames = computed(() => {
    return props.upcomingGames.slice(0, props.maxUpcoming);
});

const displayRecentGames = computed(() => {
    return props.recentGames.slice(0, props.maxRecent);
});

const getGameStatus = (game) => {
    const statusMap = {
        'scheduled': { text: 'Geplant', color: 'bg-blue-100 text-blue-800' },
        'live': { text: 'Live', color: 'bg-red-100 text-red-800 animate-pulse' },
        'completed': { text: 'Beendet', color: 'bg-green-100 text-green-800' },
        'postponed': { text: 'Verschoben', color: 'bg-yellow-100 text-yellow-800' },
        'cancelled': { text: 'Abgesagt', color: 'bg-gray-100 text-gray-800' }
    };
    
    return statusMap[game.status] || statusMap.scheduled;
};

const getGameResult = (game, teamName) => {
    if (game.status !== 'completed') return null;
    
    const isHome = (game.home_team?.name || game.home_team_name) === teamName;
    const teamScore = isHome ? game.home_team_score : game.away_team_score;
    const opponentScore = isHome ? game.away_team_score : game.home_team_score;
    
    if (teamScore > opponentScore) return 'win';
    if (teamScore < opponentScore) return 'loss';
    return 'tie';
};

const formatGameTime = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleTimeString('de-DE', { 
        hour: '2-digit', 
        minute: '2-digit' 
    });
};

const formatGameDate = (dateString) => {
    const date = new Date(dateString);
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    
    if (date.toDateString() === today.toDateString()) {
        return 'Heute';
    } else if (date.toDateString() === tomorrow.toDateString()) {
        return 'Morgen';
    } else {
        return date.toLocaleDateString('de-DE', { 
            weekday: 'short', 
            day: '2-digit', 
            month: '2-digit' 
        });
    }
};

const handleGameClick = (game) => {
    emit('game-click', game);
};

const handleViewAllUpcoming = () => {
    emit('view-all-upcoming');
};

const handleViewAllRecent = () => {
    emit('view-all-recent');
};
</script>

<template>
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <!-- Header -->
        <div class="p-6 pb-4">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                {{ title }}
            </h3>
        </div>
        
        <div class="px-6 pb-6 space-y-6">
            <!-- Upcoming Games Section -->
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-sm font-medium text-gray-900">
                        Kommende Spiele
                    </h4>
                    <button v-if="upcomingGames.length > maxUpcoming" 
                            @click="handleViewAllUpcoming"
                            class="text-sm font-medium text-orange-600 hover:text-orange-500">
                        Alle anzeigen →
                    </button>
                </div>
                
                <!-- No Upcoming Games -->
                <div v-if="displayUpcomingGames.length === 0" class="text-center py-6 text-gray-500">
                    <svg class="mx-auto h-10 w-10 text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <p class="text-sm">Keine kommenden Spiele</p>
                </div>
                
                <!-- Upcoming Games List -->
                <div v-else class="space-y-3">
                    <div v-for="game in displayUpcomingGames" :key="game.id"
                         class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer"
                         @click="handleGameClick(game)">
                        
                        <div class="flex-1">
                            <!-- Teams -->
                            <div class="flex items-center space-x-3 mb-2">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ game.home_team?.name || game.home_team_name || 'TBD' }}
                                </div>
                                <div class="text-sm text-gray-500">vs</div>
                                <div class="text-sm font-medium text-gray-900">
                                    {{ game.away_team?.name || game.away_team_name || 'TBD' }}
                                </div>
                            </div>
                            
                            <!-- Date & Time -->
                            <div class="flex items-center space-x-3 text-xs text-gray-500">
                                <span>{{ formatGameDate(game.scheduled_at) }}</span>
                                <span>•</span>
                                <span>{{ formatGameTime(game.scheduled_at) }}</span>
                                <span v-if="game.venue">•</span>
                                <span v-if="game.venue">{{ game.venue }}</span>
                            </div>
                        </div>
                        
                        <!-- Status -->
                        <div class="flex items-center space-x-3">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                  :class="getGameStatus(game).color">
                                {{ getGameStatus(game).text }}
                            </span>
                            
                            <!-- Home/Away Indicator -->
                            <div class="text-xs text-gray-500">
                                {{ game.is_home_game ? 'Heim' : 'Auswärts' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Games Section -->
            <div v-if="showRecent" class="border-t border-gray-200 pt-6">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-sm font-medium text-gray-900">
                        Letzte Spiele
                    </h4>
                    <button v-if="recentGames.length > maxRecent" 
                            @click="handleViewAllRecent"
                            class="text-sm font-medium text-orange-600 hover:text-orange-500">
                        Alle anzeigen →
                    </button>
                </div>
                
                <!-- No Recent Games -->
                <div v-if="displayRecentGames.length === 0" class="text-center py-6 text-gray-500">
                    <svg class="mx-auto h-10 w-10 text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <p class="text-sm">Noch keine Spiele gespielt</p>
                </div>
                
                <!-- Recent Games List -->
                <div v-else class="space-y-3">
                    <div v-for="game in displayRecentGames" :key="game.id"
                         class="flex items-center justify-between p-4 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer"
                         :class="{
                             'bg-green-50 border border-green-200': getGameResult(game, game.team_name) === 'win',
                             'bg-red-50 border border-red-200': getGameResult(game, game.team_name) === 'loss',
                             'bg-gray-50 border border-gray-200': getGameResult(game, game.team_name) === 'tie' || !getGameResult(game, game.team_name)
                         }"
                         @click="handleGameClick(game)">
                        
                        <div class="flex-1">
                            <!-- Teams and Score -->
                            <div class="flex items-center space-x-3 mb-2">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ game.home_team?.name || game.home_team_name || 'TBD' }}
                                </div>
                                <div class="text-lg font-bold"
                                     :class="{
                                         'text-green-700': getGameResult(game, game.team_name) === 'win',
                                         'text-red-700': getGameResult(game, game.team_name) === 'loss',
                                         'text-gray-700': getGameResult(game, game.team_name) === 'tie'
                                     }">
                                    {{ game.home_team_score }}
                                </div>
                                <div class="text-sm text-gray-500">:</div>
                                <div class="text-lg font-bold"
                                     :class="{
                                         'text-green-700': getGameResult(game, game.team_name) === 'win',
                                         'text-red-700': getGameResult(game, game.team_name) === 'loss',
                                         'text-gray-700': getGameResult(game, game.team_name) === 'tie'
                                     }">
                                    {{ game.away_team_score }}
                                </div>
                                <div class="text-sm font-medium text-gray-900">
                                    {{ game.away_team?.name || game.away_team_name || 'TBD' }}
                                </div>
                            </div>
                            
                            <!-- Date -->
                            <div class="text-xs text-gray-500">
                                {{ new Date(game.scheduled_at).toLocaleDateString('de-DE', { 
                                    weekday: 'short', 
                                    day: '2-digit', 
                                    month: '2-digit' 
                                }) }}
                            </div>
                        </div>
                        
                        <!-- Result Badge -->
                        <div class="flex items-center space-x-2">
                            <span v-if="getGameResult(game, game.team_name) === 'win'"
                                  class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                Sieg
                            </span>
                            <span v-else-if="getGameResult(game, game.team_name) === 'loss'"
                                  class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                                Niederlage
                            </span>
                            <span v-else-if="getGameResult(game, game.team_name) === 'tie'"
                                  class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                Unentschieden
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>