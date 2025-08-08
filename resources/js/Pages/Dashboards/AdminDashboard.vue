<script setup>
import { computed } from 'vue';
import StatisticsWidget from '@/Components/Basketball/StatisticsWidget.vue';
import StatsChart from '@/Components/Basketball/StatsChart.vue';
import RecentActivity from '@/Components/Basketball/RecentActivity.vue';

const props = defineProps({
    dashboardData: Object,
    user: Object,
    recentActivities: Array,
});

const systemOverview = computed(() => props.dashboardData.system_overview || {});
const roleDistribution = computed(() => props.dashboardData.role_distribution || {});
const recentRegistrations = computed(() => props.dashboardData.recent_registrations || []);
const systemHealth = computed(() => props.dashboardData.system_health || {});
const upcomingGames = computed(() => props.dashboardData.upcoming_games_today || []);
const activeLiveGames = computed(() => props.dashboardData.active_live_games || []);

const healthStatus = computed(() => {
    const storage = systemHealth.value.storage_usage;
    if (!storage) return 'unknown';
    
    const storagePercentage = storage.percentage || 0;
    if (storagePercentage > 90) return 'critical';
    if (storagePercentage > 75) return 'warning';
    return 'healthy';
});

const chartData = computed(() => {
    const distribution = roleDistribution.value || {};
    const keys = Object.keys(distribution);
    const values = Object.values(distribution);
    
    // Fallback wenn keine Daten verfügbar sind
    if (keys.length === 0 || values.every(v => !v || v === 0)) {
        return {
            labels: ['Keine Daten'],
            datasets: [{
                label: 'Benutzer',
                data: [1],
                backgroundColor: ['#E5E7EB'],
            }]
        };
    }
    
    return {
        labels: keys,
        datasets: [{
            label: 'Benutzer',
            data: values,
            backgroundColor: [
                '#EF4444', '#3B82F6', '#10B981', '#F59E0B', 
                '#8B5CF6', '#EC4899', '#6B7280', '#14B8A6'
            ],
        }]
    };
});
</script>

<template>
    <div class="space-y-6">
        <!-- System Overview Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <StatisticsWidget
                title="Benutzer"
                :value="systemOverview.total_users"
                :subtitle="`${systemOverview.active_users} aktiv`"
                icon="users"
                color="blue" />
            
            <StatisticsWidget
                title="Clubs"
                :value="systemOverview.total_clubs"
                :subtitle="`${systemOverview.verified_clubs} verifiziert`"
                icon="building"
                color="green" />
            
            <StatisticsWidget
                title="Teams"
                :value="systemOverview.total_teams"
                :subtitle="`${systemOverview.active_teams} aktiv`"
                icon="user-group"
                color="indigo" />
            
            <StatisticsWidget
                title="Spieler"
                :value="systemOverview.total_players"
                :subtitle="`${systemOverview.active_players} aktiv`"
                icon="users"
                color="orange" />
        </div>

        <!-- Charts and Live Data Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Role Distribution Chart -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                        Rollen-Verteilung
                    </h3>
                    <StatsChart 
                        type="doughnut" 
                        :data="chartData"
                        :height="300" />
                </div>
            </div>

            <!-- System Health -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            System-Status
                        </h3>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                              :class="{
                                  'bg-green-100 text-green-800': healthStatus === 'healthy',
                                  'bg-yellow-100 text-yellow-800': healthStatus === 'warning',
                                  'bg-red-100 text-red-800': healthStatus === 'critical',
                                  'bg-gray-100 text-gray-800': healthStatus === 'unknown'
                              }">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            {{ healthStatus === 'healthy' ? 'Gesund' : 
                               healthStatus === 'warning' ? 'Warnung' : 
                               healthStatus === 'critical' ? 'Kritisch' : 'Unbekannt' }}
                        </span>
                    </div>
                    
                    <div class="space-y-4">
                        <!-- Storage Usage -->
                        <div v-if="systemHealth.storage_usage">
                            <div class="flex justify-between text-sm text-gray-600 mb-1">
                                <span>Speicher-Verbrauch</span>
                                <span>{{ systemHealth.storage_usage.used }} / {{ systemHealth.storage_usage.total }}</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="h-2 rounded-full"
                                     :class="{
                                         'bg-green-500': systemHealth.storage_usage.percentage < 75,
                                         'bg-yellow-500': systemHealth.storage_usage.percentage >= 75 && systemHealth.storage_usage.percentage < 90,
                                         'bg-red-500': systemHealth.storage_usage.percentage >= 90
                                     }"
                                     :style="`width: ${systemHealth.storage_usage.percentage}%`"></div>
                            </div>
                        </div>

                        <!-- Cache Status -->
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Cache</span>
                            <span class="text-sm font-medium"
                                  :class="{
                                      'text-green-600': systemHealth.cache_status?.status === 'operational',
                                      'text-red-600': systemHealth.cache_status?.status !== 'operational'
                                  }">
                                {{ systemHealth.cache_status?.status === 'operational' ? 'Betriebsbereit' : 'Fehler' }}
                                ({{ systemHealth.cache_status?.driver }})
                            </span>
                        </div>

                        <!-- Queue Status -->
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Warteschlange</span>
                            <span class="text-sm font-medium"
                                  :class="{
                                      'text-green-600': systemHealth.queue_status?.status === 'operational',
                                      'text-red-600': systemHealth.queue_status?.status !== 'operational'
                                  }">
                                {{ systemHealth.queue_status?.status === 'operational' ? 'Betriebsbereit' : 'Fehler' }}
                                ({{ systemHealth.queue_status?.driver }})
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Live Games and Upcoming Games -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Active Live Games -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Live-Spiele
                        </h3>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            <span class="w-1.5 h-1.5 mr-1.5 bg-red-400 rounded-full animate-pulse"></span>
                            {{ activeLiveGames.length }} aktiv
                        </span>
                    </div>
                    
                    <div v-if="activeLiveGames.length === 0" class="text-center py-8 text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="mt-2">Aktuell keine Live-Spiele</p>
                    </div>
                    
                    <div v-else class="space-y-3">
                        <div v-for="game in activeLiveGames" :key="game.id" 
                             class="flex items-center justify-between p-3 bg-red-50 rounded-lg border border-red-200">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 text-sm font-medium text-gray-900">
                                    <span>{{ game.home_team.name }}</span>
                                    <span class="text-gray-500">vs</span>
                                    <span>{{ game.away_team.name }}</span>
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    {{ new Date(game.scheduled_at).toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' }) }}
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                                <span class="text-xs font-medium text-red-700">LIVE</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Today's Upcoming Games -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Heutige Spiele
                        </h3>
                        <span class="text-sm text-gray-500">
                            {{ upcomingGames.length }} geplant
                        </span>
                    </div>
                    
                    <div v-if="upcomingGames.length === 0" class="text-center py-8 text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <p class="mt-2">Heute keine Spiele geplant</p>
                    </div>
                    
                    <div v-else class="space-y-3">
                        <div v-for="game in upcomingGames.slice(0, 5)" :key="game.id" 
                             class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 text-sm font-medium text-gray-900">
                                    <span>{{ game.home_team.name }}</span>
                                    <span class="text-gray-500">vs</span>
                                    <span>{{ game.away_team.name }}</span>
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    {{ new Date(game.scheduled_at).toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' }) }}
                                </div>
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ game.status === 'scheduled' ? 'Geplant' : game.status }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Registrations and Activity -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent User Registrations -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                        Neue Registrierungen
                    </h3>
                    
                    <div v-if="recentRegistrations.length === 0" class="text-center py-8 text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        <p class="mt-2">Keine neuen Registrierungen</p>
                    </div>
                    
                    <div v-else class="space-y-3">
                        <div v-for="user in recentRegistrations.slice(0, 8)" :key="user.id" 
                             class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg transition-colors">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    <div class="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        {{ user.name }}
                                    </p>
                                    <p class="text-xs text-gray-500 truncate">
                                        {{ user.email }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div class="flex space-x-1">
                                    <span v-for="role in user.roles" :key="role" 
                                          class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ role }}
                                    </span>
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ new Date(user.created_at).toLocaleDateString('de-DE') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <RecentActivity :activities="recentActivities" />
        </div>
    </div>
</template>