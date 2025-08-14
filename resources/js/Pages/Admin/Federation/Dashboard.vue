<template>
  <AppLayout title="Federation Management">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Federation Management
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Federation Status Overview -->
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mb-6">
          <div class="p-6">
            <div class="flex items-center justify-between mb-6">
              <h3 class="text-lg font-medium text-gray-900">Federation Status Overview</h3>
              <button @click="refreshStatus" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Aktualisieren
              </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- DBB Status Card -->
              <div class="bg-gray-50 rounded-lg p-4">
                <div class="flex items-center justify-between">
                  <div>
                    <h4 class="text-sm font-medium text-gray-500">DBB API Status</h4>
                    <div class="mt-2 flex items-center">
                      <div class="flex-shrink-0">
                        <div :class="[
                          'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                          dbbStatus.status === 'online' ? 'bg-green-100 text-green-800' : 
                          dbbStatus.status === 'degraded' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'
                        ]">
                          {{ dbbStatus.status }}
                        </div>
                      </div>
                      <div class="ml-3">
                        <p class="text-sm text-gray-700">{{ dbbStatus.responseTime }}ms</p>
                      </div>
                    </div>
                  </div>
                  <div class="text-right">
                    <p class="text-sm text-gray-500">Letzte Prüfung</p>
                    <p class="text-xs text-gray-400">{{ formatDate(dbbStatus.lastChecked) }}</p>
                  </div>
                </div>
                
                <div class="mt-4">
                  <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Verfügbare Endpunkte</span>
                    <span class="font-medium">{{ dbbStatus.availableEndpoints }}/{{ dbbStatus.totalEndpoints }}</span>
                  </div>
                  <div class="mt-2">
                    <div class="bg-gray-200 rounded-full h-2">
                      <div 
                        class="bg-green-500 h-2 rounded-full transition-all duration-300" 
                        :style="{ width: (dbbStatus.availableEndpoints / dbbStatus.totalEndpoints) * 100 + '%' }"
                      ></div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- FIBA Status Card -->
              <div class="bg-gray-50 rounded-lg p-4">
                <div class="flex items-center justify-between">
                  <div>
                    <h4 class="text-sm font-medium text-gray-500">FIBA API Status</h4>
                    <div class="mt-2 flex items-center">
                      <div class="flex-shrink-0">
                        <div :class="[
                          'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                          fibaStatus.status === 'online' ? 'bg-green-100 text-green-800' : 
                          fibaStatus.status === 'degraded' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'
                        ]">
                          {{ fibaStatus.status }}
                        </div>
                      </div>
                      <div class="ml-3">
                        <p class="text-sm text-gray-700">{{ fibaStatus.responseTime }}ms</p>
                      </div>
                    </div>
                  </div>
                  <div class="text-right">
                    <p class="text-sm text-gray-500">Letzte Prüfung</p>
                    <p class="text-xs text-gray-400">{{ formatDate(fibaStatus.lastChecked) }}</p>
                  </div>
                </div>
                
                <div class="mt-4">
                  <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Verfügbare Endpunkte</span>
                    <span class="font-medium">{{ fibaStatus.availableEndpoints }}/{{ fibaStatus.totalEndpoints }}</span>
                  </div>
                  <div class="mt-2">
                    <div class="bg-gray-200 rounded-full h-2">
                      <div 
                        class="bg-green-500 h-2 rounded-full transition-all duration-300" 
                        :style="{ width: (fibaStatus.availableEndpoints / fibaStatus.totalEndpoints) * 100 + '%' }"
                      ></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mb-6">
          <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Schnellzugriff</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              <button 
                @click="openPlayerSearchModal"
                class="flex flex-col items-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-gray-400 hover:bg-gray-50 transition-colors"
              >
                <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <span class="text-sm font-medium text-gray-900">Spielersuche</span>
                <span class="text-xs text-gray-500 mt-1">DBB/FIBA</span>
              </button>

              <button 
                @click="openLeagueManagementModal"
                class="flex flex-col items-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-gray-400 hover:bg-gray-50 transition-colors"
              >
                <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                <span class="text-sm font-medium text-gray-900">Liga-Verwaltung</span>
                <span class="text-xs text-gray-500 mt-1">Registrierung</span>
              </button>

              <button 
                @click="openTeamRegistrationModal"
                class="flex flex-col items-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-gray-400 hover:bg-gray-50 transition-colors"
              >
                <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <span class="text-sm font-medium text-gray-900">Team-Registrierung</span>
                <span class="text-xs text-gray-500 mt-1">Wettkämpfe</span>
              </button>

              <button 
                @click="openSyncManagerModal"
                class="flex flex-col items-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-gray-400 hover:bg-gray-50 transition-colors"
              >
                <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <span class="text-sm font-medium text-gray-900">Synchronisation</span>
                <span class="text-xs text-gray-500 mt-1">Daten-Sync</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Recent Federation Activities -->
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
          <div class="p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-medium text-gray-900">Letzte Federation-Aktivitäten</h3>
              <button 
                @click="openFederationConfigModal"
                class="text-sm text-blue-600 hover:text-blue-800"
              >
                Konfiguration
              </button>
            </div>

            <div class="flow-root">
              <ul role="list" class="-mb-8">
                <li v-for="(activity, index) in recentActivities" :key="activity.id">
                  <div class="relative pb-8" v-if="index !== recentActivities.length - 1">
                    <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                    <div class="relative flex space-x-3">
                      <div>
                        <span :class="[
                          activity.type === 'player_lookup' ? 'bg-green-500' :
                          activity.type === 'team_registration' ? 'bg-blue-500' :
                          activity.type === 'data_sync' ? 'bg-yellow-500' : 'bg-gray-500',
                          'h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white'
                        ]">
                          <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path v-if="activity.type === 'player_lookup'" fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                            <path v-else-if="activity.type === 'team_registration'" fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zM8 6a2 2 0 114 0v1H8V6z" clip-rule="evenodd"></path>
                            <path v-else fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"></path>
                          </svg>
                        </span>
                      </div>
                      <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                        <div>
                          <p class="text-sm text-gray-500">
                            {{ activity.description }}
                            <a :href="activity.targetUrl" class="font-medium text-gray-900" v-if="activity.targetUrl">{{ activity.target }}</a>
                          </p>
                        </div>
                        <div class="text-right text-sm whitespace-nowrap text-gray-500">
                          <time :datetime="activity.datetime">{{ formatRelativeTime(activity.datetime) }}</time>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="relative flex space-x-3" v-else>
                    <div>
                      <span :class="[
                        activity.type === 'player_lookup' ? 'bg-green-500' :
                        activity.type === 'team_registration' ? 'bg-blue-500' :
                        activity.type === 'data_sync' ? 'bg-yellow-500' : 'bg-gray-500',
                        'h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white'
                      ]">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                          <path v-if="activity.type === 'player_lookup'" fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                          <path v-else-if="activity.type === 'team_registration'" fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zM8 6a2 2 0 114 0v1H8V6z" clip-rule="evenodd"></path>
                          <path v-else fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"></path>
                        </svg>
                      </span>
                    </div>
                    <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                      <div>
                        <p class="text-sm text-gray-500">
                          {{ activity.description }}
                          <a :href="activity.targetUrl" class="font-medium text-gray-900" v-if="activity.targetUrl">{{ activity.target }}</a>
                        </p>
                      </div>
                      <div class="text-right text-sm whitespace-nowrap text-gray-500">
                        <time :datetime="activity.datetime">{{ formatRelativeTime(activity.datetime) }}</time>
                      </div>
                    </div>
                  </div>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <PlayerSearchModal 
      :show="showPlayerSearchModal" 
      @close="showPlayerSearchModal = false"
      @playerFound="handlePlayerFound"
    />

    <LeagueManagementModal 
      :show="showLeagueManagementModal" 
      @close="showLeagueManagementModal = false"
      @leagueRegistered="handleLeagueRegistered"
    />

    <TeamRegistrationModal 
      :show="showTeamRegistrationModal" 
      @close="showTeamRegistrationModal = false"
      @teamRegistered="handleTeamRegistered"
    />

    <SyncManagerModal 
      :show="showSyncManagerModal" 
      @close="showSyncManagerModal = false"
      @syncCompleted="handleSyncCompleted"
    />

    <FederationConfigModal 
      :show="showFederationConfigModal" 
      @close="showFederationConfigModal = false"
      @configUpdated="handleConfigUpdated"
    />
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PlayerSearchModal from './Modals/PlayerSearchModal.vue'
import LeagueManagementModal from './Modals/LeagueManagementModal.vue'
import TeamRegistrationModal from './Modals/TeamRegistrationModal.vue'
import SyncManagerModal from './Modals/SyncManagerModal.vue'
import FederationConfigModal from './Modals/FederationConfigModal.vue'

// Props
const props = defineProps({
  dbbStatus: {
    type: Object,
    default: () => ({
      status: 'offline',
      responseTime: 0,
      lastChecked: new Date().toISOString(),
      availableEndpoints: 0,
      totalEndpoints: 8
    })
  },
  fibaStatus: {
    type: Object,
    default: () => ({
      status: 'offline',
      responseTime: 0,
      lastChecked: new Date().toISOString(),
      availableEndpoints: 0,
      totalEndpoints: 6
    })
  },
  recentActivities: {
    type: Array,
    default: () => []
  }
})

// Modal states
const showPlayerSearchModal = ref(false)
const showLeagueManagementModal = ref(false)
const showTeamRegistrationModal = ref(false)
const showSyncManagerModal = ref(false)
const showFederationConfigModal = ref(false)

// Mock data for development - wird durch echte API-Aufrufe ersetzt
const dbbStatus = ref({
  status: 'online',
  responseTime: 245,
  lastChecked: new Date().toISOString(),
  availableEndpoints: 7,
  totalEndpoints: 8
})

const fibaStatus = ref({
  status: 'degraded',
  responseTime: 890,
  lastChecked: new Date().toISOString(),
  availableEndpoints: 4,
  totalEndpoints: 6
})

const recentActivities = ref([
  {
    id: 1,
    type: 'player_lookup',
    description: 'Spielersuche durchgeführt für Lizenz',
    target: '12345678',
    targetUrl: '#',
    datetime: new Date(Date.now() - 2 * 60 * 1000).toISOString() // 2 Minuten ago
  },
  {
    id: 2,
    type: 'team_registration',
    description: 'Team registriert für Liga',
    target: 'Regionalliga Nord',
    targetUrl: '#',
    datetime: new Date(Date.now() - 15 * 60 * 1000).toISOString() // 15 Minuten ago
  },
  {
    id: 3,
    type: 'data_sync',
    description: 'Automatische Daten-Synchronisation abgeschlossen',
    target: 'DBB API',
    targetUrl: null,
    datetime: new Date(Date.now() - 45 * 60 * 1000).toISOString() // 45 Minuten ago
  },
  {
    id: 4,
    type: 'player_lookup',
    description: 'Spielervalidierung fehlgeschlagen für Lizenz',
    target: '87654321',
    targetUrl: '#',
    datetime: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString() // 2 Stunden ago
  }
])

// Methods
const refreshStatus = async () => {
  try {
    const response = await axios.get('/federation/status')
    dbbStatus.value = response.data.dbb
    fibaStatus.value = response.data.fiba
  } catch (error) {
    console.error('Failed to refresh federation status:', error)
  }
}

const openPlayerSearchModal = () => {
  showPlayerSearchModal.value = true
}

const openLeagueManagementModal = () => {
  showLeagueManagementModal.value = true
}

const openTeamRegistrationModal = () => {
  showTeamRegistrationModal.value = true
}

const openSyncManagerModal = () => {
  showSyncManagerModal.value = true
}

const openFederationConfigModal = () => {
  showFederationConfigModal.value = true
}

// Event handlers
const handlePlayerFound = (player) => {
  console.log('Player found:', player)
  // Add new activity
  recentActivities.value.unshift({
    id: Date.now(),
    type: 'player_lookup',
    description: 'Spieler gefunden:',
    target: `${player.firstName} ${player.lastName}`,
    targetUrl: `/players/${player.id}`,
    datetime: new Date().toISOString()
  })
}

const handleLeagueRegistered = (league) => {
  console.log('League registered:', league)
  recentActivities.value.unshift({
    id: Date.now(),
    type: 'team_registration',
    description: 'Liga-Registrierung abgeschlossen für',
    target: league.name,
    targetUrl: `/leagues/${league.id}`,
    datetime: new Date().toISOString()
  })
}

const handleTeamRegistered = (team) => {
  console.log('Team registered:', team)
  recentActivities.value.unshift({
    id: Date.now(),
    type: 'team_registration',
    description: 'Team registriert:',
    target: team.name,
    targetUrl: `/teams/${team.id}`,
    datetime: new Date().toISOString()
  })
}

const handleSyncCompleted = (syncResult) => {
  console.log('Sync completed:', syncResult)
  recentActivities.value.unshift({
    id: Date.now(),
    type: 'data_sync',
    description: 'Daten-Synchronisation abgeschlossen',
    target: `${syncResult.recordsSync} Datensätze`,
    targetUrl: null,
    datetime: new Date().toISOString()
  })
}

const handleConfigUpdated = (config) => {
  console.log('Config updated:', config)
  refreshStatus()
}

// Utility functions
const formatDate = (dateString) => {
  return new Date(dateString).toLocaleString('de-DE')
}

const formatRelativeTime = (dateString) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffInMinutes = Math.floor((now - date) / (1000 * 60))
  
  if (diffInMinutes < 1) return 'Gerade eben'
  if (diffInMinutes < 60) return `vor ${diffInMinutes} Min`
  
  const diffInHours = Math.floor(diffInMinutes / 60)
  if (diffInHours < 24) return `vor ${diffInHours}h`
  
  const diffInDays = Math.floor(diffInHours / 24)
  return `vor ${diffInDays}d`
}

// Auto-refresh status every 30 seconds
onMounted(() => {
  const interval = setInterval(refreshStatus, 30000)
  
  // Clean up interval on unmount
  return () => {
    clearInterval(interval)
  }
})
</script>