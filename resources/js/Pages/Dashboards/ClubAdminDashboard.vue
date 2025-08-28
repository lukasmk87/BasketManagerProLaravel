<script setup>
import { computed } from 'vue';
import StatisticsWidget from '@/Components/Basketball/StatisticsWidget.vue';
import TeamCard from '@/Components/Basketball/TeamCard.vue';
import RecentActivity from '@/Components/Basketball/RecentActivity.vue';

const props = defineProps({
    dashboardData: Object,
    user: Object,
    recentActivities: Array,
});

const primaryClub = computed(() => props.dashboardData.primary_club || {});
const clubStatistics = computed(() => props.dashboardData.club_statistics || {});
const teamsOverview = computed(() => props.dashboardData.teams_overview || []);
const recentMemberActivity = computed(() => props.dashboardData.recent_member_activity || []);
const upcomingGames = computed(() => props.dashboardData.upcoming_games || []);
const allClubs = computed(() => props.dashboardData.all_clubs || []);

const basicStats = computed(() => clubStatistics.value.basic_stats || {});
const gameStats = computed(() => clubStatistics.value.game_stats || {});
const financialStats = computed(() => clubStatistics.value.financial_stats || {});
const recentActivity = computed(() => clubStatistics.value.recent_activity || {});

const hasMultipleClubs = computed(() => allClubs.value.length > 1);

const clubVerificationStatus = computed(() => {
    return primaryClub.value.is_verified ? 'Verifiziert' : 'Nicht verifiziert';
});
</script>

<template>
    <div class="space-y-6">
        <!-- Message State (wenn kein Club Admin) -->
        <div v-if="dashboardData.message" class="bg-blue-50 border border-blue-200 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-700">{{ dashboardData.message }}</p>
                </div>
            </div>
        </div>

        <template v-else>
            <!-- Club Header -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="flex-shrink-0">
                                <img v-if="primaryClub.logo_url" 
                                     class="h-16 w-16 rounded-lg object-cover" 
                                     :src="primaryClub.logo_url" 
                                     :alt="primaryClub.name">
                                <div v-else class="h-16 w-16 rounded-lg bg-gray-300 flex items-center justify-center">
                                    <svg class="w-8 h-8 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900">{{ primaryClub.name }}</h2>
                                <div class="flex items-center space-x-3 mt-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                          :class="{
                                              'bg-green-100 text-green-800': primaryClub.is_verified,
                                              'bg-yellow-100 text-yellow-800': !primaryClub.is_verified
                                          }">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path v-if="primaryClub.is_verified" fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            <path v-else fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                        {{ clubVerificationStatus }}
                                    </span>
                                    <span v-if="hasMultipleClubs" class="text-xs text-gray-500">
                                        Verwaltet {{ allClubs.length }} Clubs
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Club Statistics Overview -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <StatisticsWidget
                    title="Teams"
                    :value="basicStats.total_teams"
                    :subtitle="`${basicStats.active_teams} aktiv`"
                    icon="user-group"
                    color="blue" />
                
                <StatisticsWidget
                    title="Spieler"
                    :value="basicStats.total_players"
                    :subtitle="`${basicStats.active_players} aktiv`"
                    icon="users"
                    color="green" />
                
                <StatisticsWidget
                    title="Mitglieder"
                    :value="basicStats.total_members"
                    :subtitle="`${basicStats.active_members} aktiv`"
                    icon="user-group"
                    color="indigo" />
                
                <StatisticsWidget
                    title="Siegquote"
                    :value="`${gameStats.win_percentage}%`"
                    :subtitle="`${gameStats.total_wins}/${gameStats.total_games} Spiele`"
                    icon="chart-bar"
                    color="orange" />
            </div>

            <!-- Quick Actions -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Schnellaktionen
                        </h3>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Spielplan Import -->
                        <a :href="route('games.import.index')" 
                           class="flex items-center justify-center p-4 bg-blue-50 border border-blue-200 hover:bg-blue-100 hover:border-blue-300 rounded-lg transition-colors group">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    <svg class="w-6 h-6 text-blue-600 group-hover:text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-blue-900 group-hover:text-blue-800">
                                        Spielplan importieren
                                    </div>
                                    <div class="text-xs text-blue-600">
                                        iCAL-Dateien hochladen
                                    </div>
                                </div>
                            </div>
                        </a>

                        <!-- Neues Spiel -->
                        <a :href="route('web.games.create')" 
                           class="flex items-center justify-center p-4 bg-green-50 border border-green-200 hover:bg-green-100 hover:border-green-300 rounded-lg transition-colors group">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    <svg class="w-6 h-6 text-green-600 group-hover:text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-green-900 group-hover:text-green-800">
                                        Neues Spiel
                                    </div>
                                    <div class="text-xs text-green-600">
                                        Manuell erstellen
                                    </div>
                                </div>
                            </div>
                        </a>

                        <!-- Import-Verlauf -->
                        <a :href="route('games.import.history')" 
                           class="flex items-center justify-center p-4 bg-purple-50 border border-purple-200 hover:bg-purple-100 hover:border-purple-300 rounded-lg transition-colors group">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    <svg class="w-6 h-6 text-purple-600 group-hover:text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-purple-900 group-hover:text-purple-800">
                                        Import-Verlauf
                                    </div>
                                    <div class="text-xs text-purple-600">
                                        Statistiken anzeigen
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Teams Overview -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Teams-Übersicht
                        </h3>
                        <a href="#" class="text-sm font-medium text-orange-600 hover:text-orange-500">
                            Alle Teams anzeigen →
                        </a>
                    </div>
                    
                    <div v-if="teamsOverview.length === 0" class="text-center py-8 text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <p class="mt-2">Noch keine Teams vorhanden</p>
                        <a href="#" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-orange-600 hover:bg-orange-700">
                            Erstes Team erstellen
                        </a>
                    </div>
                    
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <TeamCard v-for="team in teamsOverview" :key="team.id" :team="team" />
                    </div>
                </div>
            </div>

            <!-- Recent Activity and Upcoming Games -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Upcoming Games -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                Kommende Spiele
                            </h3>
                            <span class="text-sm text-gray-500">
                                {{ upcomingGames.length }} geplant
                            </span>
                        </div>
                        
                        <div v-if="upcomingGames.length === 0" class="text-center py-8 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <p class="mt-2">Keine kommenden Spiele</p>
                        </div>
                        
                        <div v-else class="space-y-3">
                            <div v-for="game in upcomingGames.slice(0, 6)" :key="game.id" 
                                 class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2 text-sm font-medium text-gray-900">
                                        <span>{{ game.home_team.name }}</span>
                                        <span class="text-gray-500">vs</span>
                                        <span>{{ game.away_team.name }}</span>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        {{ new Date(game.scheduled_at).toLocaleDateString('de-DE', { 
                                            weekday: 'short', 
                                            day: '2-digit', 
                                            month: '2-digit',
                                            hour: '2-digit', 
                                            minute: '2-digit' 
                                        }) }}
                                    </div>
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ game.status === 'scheduled' ? 'Geplant' : game.status }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Member Activity -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            Neue Mitglieder
                        </h3>
                        
                        <div v-if="recentMemberActivity.length === 0" class="text-center py-8 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                            <p class="mt-2">Keine neuen Mitglieder</p>
                        </div>
                        
                        <div v-else class="space-y-3">
                            <div v-for="member in recentMemberActivity.slice(0, 8)" :key="member.id" 
                                 class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg transition-colors">
                                <div class="flex items-center space-x-3">
                                    <div class="flex-shrink-0">
                                        <div class="h-8 w-8 rounded-full bg-orange-300 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            {{ member.name }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ member.role === 'admin' ? 'Administrator' : 
                                               member.role === 'manager' ? 'Manager' : 'Mitglied' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ new Date(member.joined_at).toLocaleDateString('de-DE') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Financial Overview (if available) -->
            <div v-if="financialStats && financialStats.total_annual_revenue" class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                        Financial-Übersicht
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="text-center p-4 bg-green-50 rounded-lg">
                            <div class="text-2xl font-bold text-green-600">
                                {{ new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' }).format(financialStats.total_annual_revenue || 0) }}
                            </div>
                            <div class="text-sm text-green-700">Geschätzter Jahresumsatz</div>
                        </div>
                        <div class="text-center p-4 bg-blue-50 rounded-lg">
                            <div class="text-2xl font-bold text-blue-600">
                                {{ new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' }).format(financialStats.membership_fee_annual || 0) }}
                            </div>
                            <div class="text-sm text-blue-700">Jahresbeitrag</div>
                        </div>
                        <div class="text-center p-4 bg-purple-50 rounded-lg">
                            <div class="text-2xl font-bold text-purple-600">
                                {{ new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' }).format(financialStats.membership_fee_monthly || 0) }}
                            </div>
                            <div class="text-sm text-purple-700">Monatsbeitrag</div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>