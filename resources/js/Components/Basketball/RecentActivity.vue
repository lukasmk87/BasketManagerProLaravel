<script setup>
import { computed } from 'vue';

const props = defineProps({
    activities: {
        type: Array,
        default: () => []
    },
    title: {
        type: String,
        default: 'Letzte Aktivitäten'
    },
    showAvatar: {
        type: Boolean,
        default: true
    },
    maxItems: {
        type: Number,
        default: 10
    },
    groupByDate: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['activity-click', 'view-all']);

const displayActivities = computed(() => {
    return props.activities.slice(0, props.maxItems);
});

const groupedActivities = computed(() => {
    if (!props.groupByDate) return null;
    
    const groups = {};
    displayActivities.value.forEach(activity => {
        const date = new Date(activity.created_at).toLocaleDateString('de-DE');
        if (!groups[date]) {
            groups[date] = [];
        }
        groups[date].push(activity);
    });
    
    return groups;
});

const getActivityIcon = (activity) => {
    const iconMap = {
        'user_registered': 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
        'team_created': 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
        'player_added': 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z',
        'game_scheduled': 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        'game_completed': 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'training_scheduled': 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        'club_verified': 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
        'stats_updated': 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        'default': 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
    };
    
    return iconMap[activity.type] || iconMap.default;
};

const getActivityColor = (activity) => {
    const colorMap = {
        'user_registered': 'text-blue-600 bg-blue-100',
        'team_created': 'text-green-600 bg-green-100',
        'player_added': 'text-purple-600 bg-purple-100',
        'game_scheduled': 'text-orange-600 bg-orange-100',
        'game_completed': 'text-green-600 bg-green-100',
        'training_scheduled': 'text-indigo-600 bg-indigo-100',
        'club_verified': 'text-green-600 bg-green-100',
        'stats_updated': 'text-blue-600 bg-blue-100',
        'default': 'text-gray-600 bg-gray-100'
    };
    
    return colorMap[activity.type] || colorMap.default;
};

const formatTimeAgo = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    if (diffInSeconds < 60) return 'vor wenigen Sekunden';
    if (diffInSeconds < 3600) return `vor ${Math.floor(diffInSeconds / 60)} Minuten`;
    if (diffInSeconds < 86400) return `vor ${Math.floor(diffInSeconds / 3600)} Stunden`;
    if (diffInSeconds < 2592000) return `vor ${Math.floor(diffInSeconds / 86400)} Tagen`;
    
    return date.toLocaleDateString('de-DE');
};

const handleActivityClick = (activity) => {
    emit('activity-click', activity);
};

const handleViewAll = () => {
    emit('view-all');
};
</script>

<template>
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <!-- Header -->
        <div class="p-6 pb-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    {{ title }}
                </h3>
                <button v-if="activities.length > maxItems" 
                        @click="handleViewAll"
                        class="text-sm font-medium text-orange-600 hover:text-orange-500">
                    Alle anzeigen →
                </button>
            </div>
        </div>
        
        <!-- Activities List -->
        <div class="px-6 pb-6">
            <!-- Empty State -->
            <div v-if="activities.length === 0" class="text-center py-8 text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-sm">Keine Aktivitäten verfügbar</p>
            </div>
            
            <!-- Grouped Activities -->
            <div v-else-if="groupByDate && groupedActivities">
                <div v-for="(dateActivities, date) in groupedActivities" :key="date" class="mb-6 last:mb-0">
                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-3">
                        {{ date }}
                    </div>
                    
                    <div class="space-y-3">
                        <div v-for="activity in dateActivities" :key="activity.id"
                             class="flex items-start space-x-3 p-3 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer"
                             @click="handleActivityClick(activity)">
                            
                            <!-- Activity Icon -->
                            <div class="flex-shrink-0">
                                <div class="h-8 w-8 rounded-full flex items-center justify-center"
                                     :class="getActivityColor(activity)">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getActivityIcon(activity)"></path>
                                    </svg>
                                </div>
                            </div>
                            
                            <!-- Activity Content -->
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-900">
                                    {{ activity.description }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ formatTimeAgo(activity.created_at) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Regular Activities List -->
            <div v-else class="space-y-3">
                <div v-for="activity in displayActivities" :key="activity.id"
                     class="flex items-start space-x-3 p-3 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer"
                     @click="handleActivityClick(activity)">
                    
                    <!-- User Avatar (if enabled) -->
                    <div v-if="showAvatar && activity.user" class="flex-shrink-0">
                        <div class="h-8 w-8 rounded-full overflow-hidden">
                            <img v-if="activity.user.avatar_url" 
                                 :src="activity.user.avatar_url" 
                                 :alt="activity.user.name"
                                 class="h-8 w-8 object-cover">
                            <div v-else class="h-8 w-8 bg-gray-300 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Activity Icon (wenn kein Avatar) -->
                    <div v-else class="flex-shrink-0">
                        <div class="h-8 w-8 rounded-full flex items-center justify-center"
                             :class="getActivityColor(activity)">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getActivityIcon(activity)"></path>
                            </svg>
                        </div>
                    </div>
                    
                    <!-- Activity Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-gray-900">
                                {{ activity.description }}
                            </p>
                            <div class="flex items-center space-x-2">
                                <!-- Activity Type Badge -->
                                <span v-if="activity.type_label" 
                                      class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                    {{ activity.type_label }}
                                </span>
                            </div>
                        </div>
                        
                        <!-- User Name and Time -->
                        <div class="flex items-center space-x-2 text-xs text-gray-500 mt-1">
                            <span v-if="activity.user && showAvatar">{{ activity.user.name }}</span>
                            <span v-if="activity.user && showAvatar">•</span>
                            <span>{{ formatTimeAgo(activity.created_at) }}</span>
                        </div>
                        
                        <!-- Additional Info -->
                        <div v-if="activity.meta && Object.keys(activity.meta).length > 0" 
                             class="mt-2 text-xs text-gray-600">
                            <span v-for="(value, key) in activity.meta" :key="key" class="mr-3">
                                <span class="font-medium">{{ key }}:</span> {{ value }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>