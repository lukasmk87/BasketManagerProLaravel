<script setup>
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import AdminDashboard from '@/Pages/Dashboards/AdminDashboard.vue';
import ClubAdminDashboard from '@/Pages/Dashboards/ClubAdminDashboard.vue';
import TrainerDashboard from '@/Pages/Dashboards/TrainerDashboard.vue';
import PlayerDashboard from '@/Pages/Dashboards/PlayerDashboard.vue';
import BasicDashboard from '@/Pages/Dashboards/BasicDashboard.vue';

const props = defineProps({
    user: Object,
    dashboard_type: String,
    dashboard_data: Object,
    quick_actions: Array,
    recent_activities: Array,
});

const dashboardComponent = computed(() => {
    switch (props.dashboard_type) {
        case 'admin':
        case 'super_admin':
            return AdminDashboard;
        case 'club_admin':
            return ClubAdminDashboard;
        case 'trainer':
        case 'assistant_coach':
            return TrainerDashboard;
        case 'player':
            return PlayerDashboard;
        default:
            return BasicDashboard;
    }
});

const dashboardTitle = computed(() => {
    switch (props.dashboard_type) {
        case 'admin':
        case 'super_admin':
            return 'System-Administration';
        case 'club_admin':
            return 'Club-Verwaltung';
        case 'trainer':
        case 'assistant_coach':
            return 'Trainer-Dashboard';
        case 'player':
            return 'Spieler-Dashboard';
        default:
            return 'Dashboard';
    }
});

const userRoleBadge = computed(() => {
    const roleTranslations = {
        'super_admin': 'Super Administrator',
        'admin': 'Administrator',
        'club_admin': 'Club Administrator',
        'trainer': 'Trainer',
        'assistant_coach': 'Co-Trainer',
        'player': 'Spieler',
        'member': 'Mitglied',
    };
    
    return roleTranslations[props.dashboard_type] || 'Benutzer';
});

const welcomeMessage = computed(() => {
    const timeOfDay = new Date().getHours();
    let greeting;
    
    if (timeOfDay < 12) {
        greeting = 'Guten Morgen';
    } else if (timeOfDay < 18) {
        greeting = 'Guten Tag';
    } else {
        greeting = 'Guten Abend';
    }
    
    return `${greeting}, ${props.user.name}!`;
});
</script>

<template>
    <AppLayout :title="dashboardTitle">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        {{ dashboardTitle }}
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        {{ welcomeMessage }}
                    </p>
                </div>
                
                <div class="flex items-center space-x-3">
                    <!-- User Role Badge -->
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium"
                          :class="{
                              'bg-red-100 text-red-800': dashboard_type === 'admin' || dashboard_type === 'super_admin',
                              'bg-blue-100 text-blue-800': dashboard_type === 'club_admin',
                              'bg-green-100 text-green-800': dashboard_type === 'trainer' || dashboard_type === 'assistant_coach',
                              'bg-orange-100 text-orange-800': dashboard_type === 'player',
                              'bg-gray-100 text-gray-800': !['admin', 'super_admin', 'club_admin', 'trainer', 'assistant_coach', 'player'].includes(dashboard_type)
                          }">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd"></path>
                        </svg>
                        {{ userRoleBadge }}
                    </span>
                    
                    <!-- User Avatar -->
                    <div class="flex-shrink-0">
                        <img v-if="user.avatar_url" 
                             class="h-8 w-8 rounded-full object-cover" 
                             :src="user.avatar_url" 
                             :alt="user.name">
                        <div v-else class="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center">
                            <svg class="w-5 h-5 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Dynamic Dashboard Content -->
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Error State -->
                <div v-if="dashboard_data.error" class="mb-6">
                    <div class="bg-red-50 border border-red-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">
                                    Fehler beim Laden der Dashboard-Daten
                                </h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <p>{{ dashboard_data.error }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions Bar (only if not error state) -->
                <div v-if="!dashboard_data.error && quick_actions && quick_actions.length > 0" class="mb-6">
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-3 border-b border-gray-200">
                            <h3 class="text-sm font-medium text-gray-900">Schnellzugriff</h3>
                        </div>
                        <div class="px-4 py-4">
                            <div class="flex flex-wrap gap-2">
                                <a v-for="action in quick_actions" 
                                   :key="action.route" 
                                   :href="route(action.route)"
                                   class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition-colors duration-200">
                                    <svg v-if="action.icon" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <!-- Icon paths would be defined based on action.icon -->
                                        <path v-if="action.icon === 'users'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                        <path v-else-if="action.icon === 'building'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        <path v-else-if="action.icon === 'user-group'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        <path v-else-if="action.icon === 'calendar'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        <path v-else-if="action.icon === 'chart-bar'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                    {{ action.label }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Role-specific Dashboard Component (nur wenn kein Fehler) -->
                <component v-if="!dashboard_data.error"
                          :is="dashboardComponent"
                          :dashboard-data="dashboard_data"
                          :user="user"
                          :recent-activities="recent_activities" />
            </div>
        </div>
    </AppLayout>
</template>