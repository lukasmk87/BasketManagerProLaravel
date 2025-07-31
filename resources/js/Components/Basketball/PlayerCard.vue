<script setup>
import { computed } from 'vue';

const props = defineProps({
    player: {
        type: Object,
        required: true
    },
    compact: {
        type: Boolean,
        default: false
    },
    showActions: {
        type: Boolean,
        default: false
    },
    showStats: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['click', 'edit', 'remove', 'view-stats']);

const positionName = computed(() => {
    const positions = {
        'PG': 'Point Guard',
        'SG': 'Shooting Guard', 
        'SF': 'Small Forward',
        'PF': 'Power Forward',
        'C': 'Center'
    };
    return positions[props.player.position] || props.player.position;
});

const statusBadges = computed(() => {
    const badges = [];
    
    if (props.player.is_captain) {
        badges.push({
            text: 'Kapitän',
            color: 'bg-yellow-100 text-yellow-800',
            icon: 'M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z'
        });
    }
    
    if (props.player.is_starter) {
        badges.push({
            text: 'Starter',
            color: 'bg-green-100 text-green-800',
            icon: 'M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z'
        });
    }
    
    if (props.player.status === 'injured') {
        badges.push({
            text: 'Verletzt',
            color: 'bg-red-100 text-red-800',
            icon: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L12.732 4.5c-.77-.833-2.694-.833-3.464 0L1.352 16.5C.582 18.333 1.544 20 3.084 20z'
        });
    }
    
    return badges;
});

const playerAge = computed(() => {
    if (!props.player.birth_date) return null;
    
    const birthDate = new Date(props.player.birth_date);
    const today = new Date();
    const age = today.getFullYear() - birthDate.getFullYear();
    
    return age;
});

const handleClick = () => {
    emit('click', props.player);
};

const handleEdit = (event) => {
    event.stopPropagation();
    emit('edit', props.player);
};

const handleRemove = (event) => {
    event.stopPropagation();
    emit('remove', props.player);
};

const handleViewStats = (event) => {
    event.stopPropagation();
    emit('view-stats', props.player);
};
</script>

<template>
    <div class="bg-white overflow-hidden shadow rounded-lg hover:shadow-md transition-shadow duration-200 cursor-pointer"
         :class="{ 'hover:scale-105 transform transition-transform duration-200': !compact }"
         @click="handleClick">
        
        <!-- Compact Version (für Listen) -->
        <div v-if="compact" class="p-4">
            <div class="flex items-center space-x-3">
                <!-- Jersey Number -->
                <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-full bg-orange-300 flex items-center justify-center">
                        <span class="text-sm font-bold text-orange-600">
                            {{ player.jersey_number || '?' }}
                        </span>
                    </div>
                </div>
                
                <!-- Player Info -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center space-x-2">
                        <p class="text-sm font-medium text-gray-900 truncate">
                            {{ player.name }}
                        </p>
                        
                        <!-- Status Badges (compact) -->
                        <div class="flex space-x-1">
                            <span v-for="badge in statusBadges.slice(0, 1)" :key="badge.text"
                                  class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium"
                                  :class="badge.color">
                                <svg class="w-2 h-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path :d="badge.icon"></path>
                                </svg>
                            </span>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-2 text-xs text-gray-500">
                        <span>{{ positionName }}</span>
                        <span v-if="playerAge">• {{ playerAge }} Jahre</span>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div v-if="showActions" class="flex space-x-1">
                    <button @click="handleViewStats" 
                            class="p-1 rounded-full hover:bg-gray-100 transition-colors">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Full Card Version -->
        <div v-else class="p-6">
            <!-- Header with Jersey Number and Name -->
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0">
                        <div class="h-16 w-16 rounded-full bg-orange-300 flex items-center justify-center">
                            <span class="text-xl font-bold text-orange-600">
                                {{ player.jersey_number || '?' }}
                            </span>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">{{ player.name }}</h3>
                        <div class="flex items-center space-x-2 text-sm text-gray-600">
                            <span>{{ positionName }}</span>
                            <span v-if="playerAge">• {{ playerAge }} Jahre</span>
                            <span v-if="player.height">• {{ player.height }}cm</span>
                        </div>
                    </div>
                </div>
                
                <!-- Actions Dropdown -->
                <div v-if="showActions" class="relative">
                    <button class="p-2 rounded-full hover:bg-gray-100 transition-colors">
                        <svg class="w-4 h-4 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Status Badges -->
            <div v-if="statusBadges.length > 0" class="flex flex-wrap gap-2 mb-4">
                <span v-for="badge in statusBadges" :key="badge.text"
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      :class="badge.color">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path :d="badge.icon"></path>
                    </svg>
                    {{ badge.text }}
                </span>
            </div>
            
            <!-- Player Stats (if enabled) -->
            <div v-if="showStats && player.stats" class="grid grid-cols-3 gap-4 mb-4">
                <div class="text-center">
                    <div class="text-lg font-semibold text-gray-900">{{ player.stats.points || '0' }}</div>
                    <div class="text-xs text-gray-500">Punkte</div>
                </div>
                <div class="text-center">
                    <div class="text-lg font-semibold text-gray-900">{{ player.stats.rebounds || '0' }}</div>
                    <div class="text-xs text-gray-500">Rebounds</div>
                </div>
                <div class="text-center">
                    <div class="text-lg font-semibold text-gray-900">{{ player.stats.assists || '0' }}</div>
                    <div class="text-xs text-gray-500">Assists</div>
                </div>
            </div>
            
            <!-- Contact Info -->
            <div v-if="player.email || player.phone" class="border-t border-gray-200 pt-4">
                <div class="text-xs text-gray-500 space-y-1">
                    <div v-if="player.email" class="flex items-center space-x-2">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <span class="truncate">{{ player.email }}</span>
                    </div>
                    <div v-if="player.phone" class="flex items-center space-x-2">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                        <span>{{ player.phone }}</span>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div v-if="showActions" class="flex space-x-2 mt-4 pt-4 border-t border-gray-200">
                <button @click="handleViewStats" 
                        class="flex-1 bg-blue-600 text-white text-sm px-3 py-2 rounded-md hover:bg-blue-700 transition-colors">
                    Statistiken
                </button>
                <button @click="handleEdit" 
                        class="flex-1 bg-gray-600 text-white text-sm px-3 py-2 rounded-md hover:bg-gray-700 transition-colors">
                    Bearbeiten
                </button>
            </div>
        </div>
    </div>
</template>