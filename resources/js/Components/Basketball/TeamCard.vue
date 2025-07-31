<script setup>
import { computed } from 'vue';

const props = defineProps({
    team: {
        type: Object,
        required: true
    },
    showStats: {
        type: Boolean,
        default: true
    },
    showActions: {
        type: Boolean,
        default: false
    },
    compact: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['click', 'edit', 'view-details', 'manage-roster']);

const teamStatus = computed(() => {
    if (props.team.status === 'active') return { text: 'Aktiv', color: 'bg-green-100 text-green-800' };
    if (props.team.status === 'inactive') return { text: 'Inaktiv', color: 'bg-gray-100 text-gray-800' };
    if (props.team.status === 'archived') return { text: 'Archiviert', color: 'bg-yellow-100 text-yellow-800' };
    return { text: 'Unbekannt', color: 'bg-gray-100 text-gray-800' };
});

const teamRecord = computed(() => {
    if (!props.team.stats) return null;
    
    const wins = props.team.stats.wins || 0;
    const losses = props.team.stats.losses || 0;
    const total = wins + losses;
    
    if (total === 0) return { record: '0-0', percentage: 0 };
    
    const percentage = Math.round((wins / total) * 100);
    return { record: `${wins}-${losses}`, percentage };
});

const nextGame = computed(() => {
    return props.team.next_game || null;
});

const rosterInfo = computed(() => {
    const current = props.team.roster_size || 0;
    const max = props.team.max_roster_size || 15;
    
    return {
        current,
        max,
        available: max - current,
        percentage: Math.round((current / max) * 100)
    };
});

const leaguePosition = computed(() => {
    if (!props.team.league_position) return null;
    
    const position = props.team.league_position;
    let suffix = 'ter';
    
    if (position === 1) suffix = 'ster';
    else if (position === 3) suffix = 'ter';
    
    return `${position}.${suffix} Platz`;
});

const handleClick = () => {
    emit('click', props.team);
};

const handleEdit = (event) => {
    event.stopPropagation();
    emit('edit', props.team);
};

const handleViewDetails = (event) => {
    event.stopPropagation();
    emit('view-details', props.team);
};

const handleManageRoster = (event) => {
    event.stopPropagation();
    emit('manage-roster', props.team);
};
</script>

<template>
    <div class="bg-white overflow-hidden shadow rounded-lg hover:shadow-md transition-shadow duration-200 cursor-pointer"
         :class="{ 'hover:scale-105 transform transition-transform duration-200': !compact }"
         @click="handleClick">
        
        <!-- Compact Version -->
        <div v-if="compact" class="p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <!-- Team Logo/Icon -->
                    <div class="flex-shrink-0">
                        <div v-if="team.logo_url" class="h-10 w-10 rounded-lg overflow-hidden">
                            <img :src="team.logo_url" :alt="team.name" class="h-10 w-10 object-cover">
                        </div>
                        <div v-else class="h-10 w-10 rounded-lg bg-orange-300 flex items-center justify-center">
                            <svg class="w-5 h-5 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    </div>
                    
                    <!-- Team Info -->
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm font-medium text-gray-900 truncate">{{ team.name }}</h4>
                        <div class="flex items-center space-x-2 text-xs text-gray-500">
                            <span v-if="team.league">{{ team.league }}</span>
                            <span v-if="team.season">• {{ team.season }}</span>
                        </div>
                    </div>
                </div>
                
                <!-- Status Badge -->
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                      :class="teamStatus.color">
                    {{ teamStatus.text }}
                </span>
            </div>
        </div>
        
        <!-- Full Card Version -->
        <div v-else>
            <!-- Card Header -->
            <div class="p-6 pb-4">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-4">
                        <!-- Team Logo -->
                        <div class="flex-shrink-0">
                            <div v-if="team.logo_url" class="h-16 w-16 rounded-lg overflow-hidden">
                                <img :src="team.logo_url" :alt="team.name" class="h-16 w-16 object-cover">
                            </div>
                            <div v-else class="h-16 w-16 rounded-lg bg-orange-300 flex items-center justify-center">
                                <svg class="w-8 h-8 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                        
                        <!-- Team Info -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">{{ team.name }}</h3>
                            <div class="flex items-center space-x-2 text-sm text-gray-600">
                                <span v-if="team.league">{{ team.league }}</span>
                                <span v-if="team.season && team.league">•</span>
                                <span v-if="team.season">{{ team.season }}</span>
                                <span v-if="leaguePosition && (team.league || team.season)">•</span>
                                <span v-if="leaguePosition" class="font-medium">{{ leaguePosition }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Status Badge -->
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                          :class="teamStatus.color">
                        {{ teamStatus.text }}
                    </span>
                </div>
            </div>
            
            <!-- Stats Grid -->
            <div v-if="showStats" class="px-6 pb-4">
                <div class="grid grid-cols-3 gap-4">
                    <!-- Win-Loss Record -->
                    <div class="text-center">
                        <div class="text-lg font-semibold text-gray-900">
                            {{ teamRecord ? teamRecord.record : '0-0' }}
                        </div>
                        <div class="text-xs text-gray-500">Bilanz</div>
                        <div v-if="teamRecord && teamRecord.percentage > 0" class="text-xs text-gray-500">
                            {{ teamRecord.percentage }}%
                        </div>
                    </div>
                    
                    <!-- Roster Size -->
                    <div class="text-center">
                        <div class="text-lg font-semibold text-gray-900">
                            {{ rosterInfo.current }}/{{ rosterInfo.max }}
                        </div>
                        <div class="text-xs text-gray-500">Spieler</div>
                        <div v-if="rosterInfo.available > 0" class="text-xs text-green-600">
                            {{ rosterInfo.available }} frei
                        </div>
                        <div v-else class="text-xs text-red-600">
                            Kader voll
                        </div>
                    </div>
                    
                    <!-- Average Points -->
                    <div class="text-center">
                        <div class="text-lg font-semibold text-gray-900">
                            {{ team.stats?.avg_points || '0.0' }}
                        </div>
                        <div class="text-xs text-gray-500">Ø Punkte</div>
                    </div>
                </div>
            </div>
            
            <!-- Next Game Info -->
            <div v-if="nextGame" class="px-6 pb-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-blue-900">Nächstes Spiel</div>
                            <div class="text-xs text-blue-700">
                                vs {{ nextGame.opponent }} • {{ new Date(nextGame.date).toLocaleDateString('de-DE') }}
                            </div>
                        </div>
                        <div class="text-xs text-blue-600">
                            {{ nextGame.home ? 'Heim' : 'Auswärts' }}
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div v-if="showActions" class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                <div class="flex space-x-3">
                    <button @click="handleViewDetails" 
                            class="flex-1 bg-blue-600 text-white text-sm px-3 py-2 rounded-md hover:bg-blue-700 transition-colors">
                        Details
                    </button>
                    <button @click="handleManageRoster" 
                            class="flex-1 bg-gray-600 text-white text-sm px-3 py-2 rounded-md hover:bg-gray-700 transition-colors">
                        Kader
                    </button>
                    <button @click="handleEdit" 
                            class="p-2 border border-gray-300 rounded-md hover:bg-gray-100 transition-colors">
                        <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>