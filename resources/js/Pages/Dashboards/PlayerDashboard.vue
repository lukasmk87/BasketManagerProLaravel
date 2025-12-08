<script setup>
import { computed } from 'vue';
import StatisticsWidget from '@/Components/Basketball/StatisticsWidget.vue';
import PlayerCard from '@/Components/Basketball/PlayerCard.vue';
import StatsChart from '@/Components/Basketball/StatsChart.vue';
import AvailabilitySection from '@/Components/Availability/AvailabilitySection.vue';

const props = defineProps({
    dashboardData: Object,
    user: Object,
    recentActivities: Array,
});

const playerInfo = computed(() => props.dashboardData.player_info || {});
const teamInfo = computed(() => props.dashboardData.team_info || {});
const personalStatistics = computed(() => props.dashboardData.personal_statistics || {});
const teamRoster = computed(() => props.dashboardData.team_roster || []);
const upcomingGames = computed(() => props.dashboardData.upcoming_games || []);
const recentGames = computed(() => props.dashboardData.recent_games || []);
const trainingSchedule = computed(() => props.dashboardData.training_schedule || []);
const developmentGoals = computed(() => props.dashboardData.development_goals || []);
const trainingFocusAreas = computed(() => props.dashboardData.training_focus_areas || []);

const nextGame = computed(() => upcomingGames.value[0] || null);
const lastGame = computed(() => recentGames.value[0] || null);

const todaysTraining = computed(() => {
    if (!trainingSchedule.value || !Array.isArray(trainingSchedule.value)) return null;
    
    const today = new Date().toLocaleDateString('en', { weekday: 'long' }).toLowerCase();
    return trainingSchedule.value.find(session => session.day === today);
});

const positionName = computed(() => {
    const positions = {
        'PG': 'Point Guard',
        'SG': 'Shooting Guard', 
        'SF': 'Small Forward',
        'PF': 'Power Forward',
        'C': 'Center'
    };
    return positions[playerInfo.value.position] || playerInfo.value.position;
});

const statsChartData = computed(() => {
    const stats = personalStatistics.value;
    return {
        labels: ['Punkte', 'Rebounds', 'Assists', 'Steals', 'Blocks'],
        datasets: [{
            label: 'Durchschnitt pro Spiel',
            data: [
                stats.avg_points || 0,
                stats.avg_rebounds || 0,  
                stats.avg_assists || 0,
                stats.avg_steals || 0,
                stats.avg_blocks || 0
            ],
            backgroundColor: 'rgba(249, 115, 22, 0.2)',
            borderColor: 'rgba(249, 115, 22, 1)',
            borderWidth: 2,
            fill: true
        }]
    };
});

const teammates = computed(() => {
    return teamRoster.value.filter(player => player.id !== playerInfo.value.id);
});

const teamCaptains = computed(() => {
    return teamRoster.value.filter(player => player.is_captain);
});

const teamStarters = computed(() => {
    return teamRoster.value.filter(player => player.is_starter);
});
</script>

<template>
    <div class="space-y-6">
        <!-- Message State (wenn kein Spieler) -->
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
            <!-- Player Profile Header -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="flex-shrink-0">
                                <div class="h-20 w-20 rounded-full bg-orange-300 flex items-center justify-center">
                                    <span class="text-2xl font-bold text-orange-600">
                                        {{ playerInfo.jersey_number || '?' }}
                                    </span>
                                </div>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900">{{ playerInfo.name }}</h2>
                                <div class="mt-1 flex items-center space-x-3">
                                    <span class="text-sm text-gray-600">{{ positionName }}</span>
                                    <span v-if="playerInfo.is_captain" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                        </svg>
                                        Kapitän
                                    </span>
                                    <span v-if="playerInfo.is_starter" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        Stammspieler
                                    </span>
                                </div>
                                <div class="mt-2 text-sm text-gray-600">
                                    <span>{{ teamInfo.name }}</span>
                                    <span class="mx-1">•</span>
                                    <span>{{ teamInfo.club_name }}</span>
                                    <span v-if="teamInfo.league" class="mx-1">•</span>
                                    <span v-if="teamInfo.league">{{ teamInfo.league }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Today's Training Info -->
                        <div v-if="todaysTraining" class="bg-orange-50 border border-orange-200 rounded-lg p-3">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                <div class="text-sm">
                                    <div class="font-medium text-orange-900">Training heute</div>
                                    <div class="text-orange-700">{{ todaysTraining.start_time }} - {{ todaysTraining.end_time }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Personal Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <StatisticsWidget
                    title="Spiele"
                    :value="personalStatistics.games_played"
                    :subtitle="`${personalStatistics.games_started || 0} als Starter`"
                    icon="calendar"
                    color="blue" />

                <StatisticsWidget
                    title="Ø Punkte"
                    :value="personalStatistics.avg_points || '0.0'"
                    :subtitle="`${personalStatistics.total_points || 0} gesamt`"
                    icon="chart-bar"
                    color="green" />

                <StatisticsWidget
                    title="Ø Rebounds"
                    :value="personalStatistics.avg_rebounds || '0.0'"
                    :subtitle="`${personalStatistics.total_rebounds || 0} gesamt`"
                    icon="trending-up"
                    color="indigo" />

                <StatisticsWidget
                    title="Ø Assists"
                    :value="personalStatistics.avg_assists || '0.0'"
                    :subtitle="`${personalStatistics.assists || 0} gesamt`"
                    icon="users"
                    color="orange" />
            </div>

            <!-- Availability & Absences Section -->
            <AvailabilitySection :player-id="playerInfo.id" />

            <!-- Performance Chart and Development -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Performance Radar Chart -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            Performance-Profil
                        </h3>
                        <StatsChart 
                            type="radar" 
                            :data="statsChartData"
                            :height="300" />
                    </div>
                </div>

                <!-- Development Goals -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            Entwicklungsziele
                        </h3>
                        
                        <div v-if="developmentGoals.length === 0 && trainingFocusAreas.length === 0" class="text-center py-8 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="mt-2">Keine Entwicklungsziele definiert</p>
                        </div>
                        
                        <div v-else class="space-y-4">
                            <div v-if="developmentGoals.length > 0">
                                <h4 class="text-sm font-medium text-gray-900 mb-2">Ziele</h4>
                                <div class="space-y-2">
                                    <div v-for="goal in developmentGoals" :key="goal" 
                                         class="flex items-center space-x-2 text-sm">
                                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <span class="text-gray-700">{{ goal }}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div v-if="trainingFocusAreas.length > 0">
                                <h4 class="text-sm font-medium text-gray-900 mb-2">Trainingsfokus</h4>
                                <div class="flex flex-wrap gap-2">
                                    <span v-for="area in trainingFocusAreas" :key="area" 
                                          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                        {{ area }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Games and Schedule -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Next Game -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            Nächstes Spiel
                        </h3>
                        
                        <div v-if="!nextGame" class="text-center py-8 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <p class="mt-2">Kein bevorstehendes Spiel</p>
                        </div>
                        
                        <div v-else class="space-y-4">
                            <div class="text-center p-4 bg-blue-50 rounded-lg">
                                <div class="text-lg font-semibold text-blue-900 mb-2">
                                    {{ nextGame.home_team?.name || nextGame.home_team_name || 'TBD' }} vs {{ nextGame.away_team?.name || nextGame.away_team_name || 'TBD' }}
                                </div>
                                <div class="text-sm text-blue-700">
                                    {{ new Date(nextGame.scheduled_at).toLocaleDateString('de-DE', { 
                                        weekday: 'long', 
                                        day: '2-digit', 
                                        month: '2-digit',
                                        year: 'numeric',
                                        hour: '2-digit', 
                                        minute: '2-digit' 
                                    }) }}
                                </div>
                            </div>
                            
                            <div class="text-center">
                                <button class="bg-orange-600 text-white text-sm px-4 py-2 rounded-md hover:bg-orange-700 transition-colors">
                                    Spieldetails anzeigen
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Training Schedule -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            Trainingsplan
                        </h3>
                        
                        <div v-if="!trainingSchedule || trainingSchedule.length === 0" class="text-center py-8 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="mt-2">Kein Trainingsplan verfügbar</p>
                        </div>
                        
                        <div v-else class="space-y-3">
                            <div v-for="session in trainingSchedule" :key="session.day" 
                                 class="flex items-center justify-between p-3 rounded-lg"
                                 :class="{
                                     'bg-orange-50 border border-orange-200': session.day === new Date().toLocaleDateString('en', { weekday: 'long' }).toLowerCase(),
                                     'bg-gray-50': session.day !== new Date().toLocaleDateString('en', { weekday: 'long' }).toLowerCase()
                                 }">
                                <div class="flex items-center space-x-3">
                                    <div class="font-medium text-sm capitalize"
                                         :class="{
                                             'text-orange-900': session.day === new Date().toLocaleDateString('en', { weekday: 'long' }).toLowerCase(),
                                             'text-gray-900': session.day !== new Date().toLocaleDateString('en', { weekday: 'long' }).toLowerCase()
                                         }">
                                        {{ {
                                            'monday': 'Montag',
                                            'tuesday': 'Dienstag', 
                                            'wednesday': 'Mittwoch',
                                            'thursday': 'Donnerstag',
                                            'friday': 'Freitag',
                                            'saturday': 'Samstag',
                                            'sunday': 'Sonntag'
                                        }[session.day] }}
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        {{ session.start_time }} - {{ session.end_time }}
                                    </div>
                                </div>
                                <div v-if="session.venue" class="text-xs text-gray-500">
                                    {{ session.venue }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Team Roster -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Meine Teamkollegen
                        </h3>
                        <div class="text-sm text-gray-500">
                            {{ teamRoster.length }} Spieler im Kader
                        </div>
                    </div>
                    
                    <!-- Team Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="text-center p-3 bg-yellow-50 rounded-lg">
                            <div class="text-lg font-semibold text-yellow-800">{{ teamCaptains.length }}</div>
                            <div class="text-sm text-yellow-700">Kapitäne</div>
                        </div>
                        <div class="text-center p-3 bg-green-50 rounded-lg">
                            <div class="text-lg font-semibold text-green-800">{{ teamStarters.length }}</div>
                            <div class="text-sm text-green-700">Stammspieler</div>
                        </div>
                        <div class="text-center p-3 bg-blue-50 rounded-lg">
                            <div class="text-lg font-semibold text-blue-800">{{ teammates.length }}</div>
                            <div class="text-sm text-blue-700">Teamkollegen</div>
                        </div>
                    </div>
                    
                    <div v-if="teammates.length === 0" class="text-center py-8 text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        <p class="mt-2">Noch keine Teamkollegen</p>
                    </div>
                    
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        <PlayerCard v-for="teammate in teammates" :key="teammate.id" :player="teammate" :compact="true" />
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>