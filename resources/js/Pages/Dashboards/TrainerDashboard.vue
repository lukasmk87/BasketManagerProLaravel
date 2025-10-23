<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import StatisticsWidget from '@/Components/Basketball/StatisticsWidget.vue';
import PlayerCard from '@/Components/Basketball/PlayerCard.vue';
import GameSchedule from '@/Components/Basketball/GameSchedule.vue';

const props = defineProps({
    dashboardData: Object,
    user: Object,
    recentActivities: Array,
});

const primaryTeam = computed(() => props.dashboardData.primary_team || {});
const teamStatistics = computed(() => props.dashboardData.team_statistics || {});
const rosterOverview = computed(() => props.dashboardData.roster_overview || []);
const upcomingGames = computed(() => props.dashboardData.upcoming_games || []);
const recentGames = computed(() => props.dashboardData.recent_games || []);
const trainingSchedule = computed(() => props.dashboardData.training_schedule || []);
const allTeams = computed(() => props.dashboardData.all_teams || []);

const basicStats = computed(() => teamStatistics.value.basic_stats || {});
const rosterStats = computed(() => teamStatistics.value.roster_stats || {});
const seasonStats = computed(() => teamStatistics.value.season_stats || {});

const hasMultipleTeams = computed(() => allTeams.value.length > 1);

const nextGame = computed(() => upcomingGames.value[0] || null);
const lastGame = computed(() => recentGames.value[0] || null);

const todaysTraining = computed(() => {
    if (!trainingSchedule.value || !Array.isArray(trainingSchedule.value)) return null;
    
    const today = new Date().toLocaleDateString('en', { weekday: 'long' }).toLowerCase();
    return trainingSchedule.value.find(session => session.day === today);
});

const teamRoleTranslation = computed(() => {
    return primaryTeam.value.role === 'head_coach' ? 'Cheftrainer' : 'Co-Trainer';
});
</script>

<template>
    <div class="space-y-6">
        <!-- Message State (wenn kein Trainer) -->
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
            <!-- Team Header -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="flex items-center space-x-3">
                                <h2 class="text-2xl font-bold text-gray-900">{{ primaryTeam.name }}</h2>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    {{ teamRoleTranslation }}
                                </span>
                            </div>
                            <div class="mt-2 text-sm text-gray-600 space-x-4">
                                <span>{{ primaryTeam.club_name }}</span>
                                <span>•</span>
                                <span>{{ primaryTeam.season }}</span>
                                <span v-if="primaryTeam.league">•</span>
                                <span v-if="primaryTeam.league">{{ primaryTeam.league }}</span>
                                <span v-if="hasMultipleTeams" class="font-medium">
                                    • Trainiert {{ allTeams.length }} Teams
                                </span>
                            </div>
                        </div>
                        
                        <!-- Today's Training Info -->
                        <div v-if="todaysTraining" class="bg-orange-50 border border-orange-200 rounded-lg p-3">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
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

            <!-- Team Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <StatisticsWidget
                    title="Spiele gespielt"
                    :value="basicStats.games_played"
                    :subtitle="`${basicStats.games_won}W - ${basicStats.games_lost}L`"
                    icon="chart-bar"
                    color="blue" />
                
                <StatisticsWidget
                    title="Siegquote"
                    :value="`${basicStats.win_percentage}%`"
                    :subtitle="`${basicStats.games_won} Siege`"
                    icon="trending-up"
                    color="green" />
                
                <StatisticsWidget
                    title="Kader-Größe"
                    :value="rosterStats.current_roster_size"
                    :subtitle="`${rosterStats.available_spots} Plätze frei`"
                    icon="users"
                    color="indigo" />
                
                <StatisticsWidget
                    title="Ø Alter"
                    :value="`${rosterStats.average_player_age}`"
                    :subtitle="`${rosterStats.captains_count} Kapitäne`"
                    icon="user-group"
                    color="orange" />
            </div>

            <!-- Next Game and Recent Game -->
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
                            
                            <div class="flex space-x-2">
                                <Link :href="route('games.live-scoring', nextGame.id)" class="flex-1 bg-blue-600 text-white text-sm px-3 py-2 rounded-md hover:bg-blue-700 transition-colors text-center">
                                    Aufstellung planen
                                </Link>
                                <Link :href="route('web.games.show', nextGame.id)" class="flex-1 bg-gray-600 text-white text-sm px-3 py-2 rounded-md hover:bg-gray-700 transition-colors text-center">
                                    Spieldetails
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Game -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            Letztes Spiel
                        </h3>
                        
                        <div v-if="!lastGame" class="text-center py-8 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            <p class="mt-2">Noch kein Spiel gespielt</p>
                        </div>
                        
                        <div v-else class="space-y-4">
                            <div class="text-center p-4 rounded-lg"
                                 :class="{
                                     'bg-green-50': lastGame.home_team_score > lastGame.away_team_score && (lastGame.home_team?.name || lastGame.home_team_name) === primaryTeam.name,
                                     'bg-green-50': lastGame.away_team_score > lastGame.home_team_score && (lastGame.away_team?.name || lastGame.away_team_name) === primaryTeam.name,
                                     'bg-red-50': lastGame.home_team_score < lastGame.away_team_score && (lastGame.home_team?.name || lastGame.home_team_name) === primaryTeam.name,
                                     'bg-red-50': lastGame.away_team_score < lastGame.home_team_score && (lastGame.away_team?.name || lastGame.away_team_name) === primaryTeam.name,
                                     'bg-gray-50': lastGame.home_team_score === lastGame.away_team_score
                                 }">
                                <div class="text-lg font-semibold mb-2"
                                     :class="{
                                         'text-green-900': (lastGame.home_team_score > lastGame.away_team_score && (lastGame.home_team?.name || lastGame.home_team_name) === primaryTeam.name) || (lastGame.away_team_score > lastGame.home_team_score && (lastGame.away_team?.name || lastGame.away_team_name) === primaryTeam.name),
                                         'text-red-900': (lastGame.home_team_score < lastGame.away_team_score && (lastGame.home_team?.name || lastGame.home_team_name) === primaryTeam.name) || (lastGame.away_team_score < lastGame.home_team_score && (lastGame.away_team?.name || lastGame.away_team_name) === primaryTeam.name),
                                         'text-gray-900': lastGame.home_team_score === lastGame.away_team_score
                                     }">
                                    {{ lastGame.home_team?.name || lastGame.home_team_name || 'TBD' }} {{ lastGame.home_team_score }} : {{ lastGame.away_team_score }} {{ lastGame.away_team?.name || lastGame.away_team_name || 'TBD' }}
                                </div>
                                <div class="text-sm text-gray-600">
                                    {{ new Date(lastGame.scheduled_at).toLocaleDateString('de-DE', { 
                                        day: '2-digit', 
                                        month: '2-digit' 
                                    }) }}
                                </div>
                            </div>
                            
                            <div class="flex space-x-2">
                                <Link :href="route('games.statistics', lastGame.id)" class="flex-1 bg-blue-600 text-white text-sm px-3 py-2 rounded-md hover:bg-blue-700 transition-colors text-center">
                                    Statistiken
                                </Link>
                                <Link :href="route('web.games.show', lastGame.id)" class="flex-1 bg-gray-600 text-white text-sm px-3 py-2 rounded-md hover:bg-gray-700 transition-colors text-center">
                                    Analyse
                                </Link>
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
                            Kader ({{ rosterOverview.length }} Spieler)
                        </h3>
                        <div class="flex space-x-2">
                            <Link :href="route('trainer.invitations.create')" class="text-sm font-medium text-orange-600 hover:text-orange-500">
                                Spieler hinzufügen
                            </Link>
                            <span class="text-gray-300">•</span>
                            <Link :href="route('web.teams.players.index', primaryTeam.id)" class="text-sm font-medium text-orange-600 hover:text-orange-500">
                                Aufstellung verwalten
                            </Link>
                        </div>
                    </div>
                    
                    <div v-if="rosterOverview.length === 0" class="text-center py-8 text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        <p class="mt-2">Noch keine Spieler im Kader</p>
                    </div>
                    
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        <PlayerCard v-for="player in rosterOverview" :key="player.id" :player="player" />
                    </div>
                </div>
            </div>

            <!-- Training Schedule and Upcoming Games -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Training Schedule -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                Trainingsplan
                            </h3>
                            <Link :href="route('training.sessions')" class="text-sm font-medium text-orange-600 hover:text-orange-500">
                                Bearbeiten
                            </Link>
                        </div>
                        
                        <div v-if="!trainingSchedule || trainingSchedule.length === 0" class="text-center py-8 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="mt-2">Kein Trainingsplan definiert</p>
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

                <!-- Game Schedule -->
                <GameSchedule :upcoming-games="upcomingGames" :recent-games="recentGames" />
            </div>
        </template>
    </div>
</template>