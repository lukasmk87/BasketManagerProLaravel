<template>
  <DialogModal :show="show" @close="close" max-width="5xl">
    <template #title>
      Liga-Verwaltung & Registrierung
    </template>

    <template #content>
      <div class="space-y-6">
        <!-- Tab Navigation -->
        <div class="border-b border-gray-200">
          <nav class="-mb-px flex space-x-8">
            <button
              @click="activeTab = 'browse'"
              :class="[
                activeTab === 'browse' 
                  ? 'border-indigo-500 text-indigo-600' 
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
              ]"
            >
              Verfügbare Ligen
            </button>
            <button
              @click="activeTab = 'registered'"
              :class="[
                activeTab === 'registered' 
                  ? 'border-indigo-500 text-indigo-600' 
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
              ]"
            >
              Registrierte Teams
            </button>
          </nav>
        </div>

        <!-- Browse Leagues Tab -->
        <div v-if="activeTab === 'browse'" class="space-y-4">
          <!-- Search and Filter -->
          <div class="bg-gray-50 p-4 rounded-lg">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
              <div>
                <InputLabel for="league_search" value="Liga-Name suchen" />
                <TextInput
                  id="league_search"
                  v-model="leagueSearch"
                  type="text"
                  class="mt-1 block w-full"
                  placeholder="z.B. Regionalliga Nord"
                />
              </div>

              <div>
                <InputLabel for="league_level" value="Liga-Ebene" />
                <select 
                  id="league_level" 
                  v-model="selectedLevel" 
                  class="mt-1 block w-full border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm"
                >
                  <option value="">Alle Ebenen</option>
                  <option value="1">1. Liga</option>
                  <option value="2">2. Liga</option>
                  <option value="regional">Regionalliga</option>
                  <option value="oberliga">Oberliga</option>
                  <option value="landesliga">Landesliga</option>
                  <option value="bezirksliga">Bezirksliga</option>
                </select>
              </div>

              <div>
                <InputLabel for="league_region" value="Region" />
                <select 
                  id="league_region" 
                  v-model="selectedRegion" 
                  class="mt-1 block w-full border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm"
                >
                  <option value="">Alle Regionen</option>
                  <option value="nord">Nord</option>
                  <option value="sued">Süd</option>
                  <option value="ost">Ost</option>
                  <option value="west">West</option>
                  <option value="nordost">Nordost</option>
                  <option value="suedwest">Südwest</option>
                </select>
              </div>
            </div>

            <div class="flex items-center space-x-4">
              <PrimaryButton @click="searchLeagues" :disabled="loadingLeagues">
                <svg v-if="loadingLeagues" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Ligen suchen
              </PrimaryButton>

              <SecondaryButton @click="clearLeagueSearch">
                Zurücksetzen
              </SecondaryButton>
            </div>
          </div>

          <!-- Available Leagues List -->
          <div v-if="availableLeagues.length > 0" class="space-y-3">
            <h4 class="text-lg font-medium text-gray-900">Verfügbare Ligen ({{ availableLeagues.length }})</h4>
            
            <div class="grid gap-4">
              <div 
                v-for="league in paginatedLeagues" 
                :key="league.id"
                class="border rounded-lg p-4 hover:bg-gray-50 transition-colors"
              >
                <div class="flex items-center justify-between">
                  <div class="flex-1">
                    <div class="flex items-center space-x-3">
                      <div class="flex-1 min-w-0">
                        <div class="flex items-center space-x-2">
                          <h3 class="text-lg font-medium text-gray-900">
                            {{ league.name }}
                          </h3>
                          <span :class="[
                            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                            league.level === '1' ? 'bg-gold-100 text-gold-800' :
                            league.level === '2' ? 'bg-silver-100 text-silver-800' :
                            'bg-blue-100 text-blue-800'
                          ]">
                            {{ formatLeagueLevel(league.level) }}
                          </span>
                          <span v-if="league.registrationOpen" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            Anmeldung offen
                          </span>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mt-2 text-sm text-gray-500">
                          <div>
                            <span class="font-medium">Saison:</span> {{ league.season }}
                          </div>
                          <div>
                            <span class="font-medium">Region:</span> {{ league.region }}
                          </div>
                          <div>
                            <span class="font-medium">Teams:</span> {{ league.currentTeams }}/{{ league.maxTeams }}
                          </div>
                          <div>
                            <span class="font-medium">Anmeldeschluss:</span> {{ formatDate(league.registrationDeadline) }}
                          </div>
                        </div>
                        <div class="mt-2 text-sm text-gray-600" v-if="league.description">
                          {{ league.description }}
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="flex flex-col space-y-2">
                    <PrimaryButton 
                      @click="registerForLeague(league)" 
                      :disabled="!league.registrationOpen || league.currentTeams >= league.maxTeams"
                      size="sm"
                    >
                      {{ league.registrationOpen ? 'Registrieren' : 'Geschlossen' }}
                    </PrimaryButton>
                    <SecondaryButton 
                      @click="viewLeagueDetails(league)" 
                      size="sm"
                    >
                      Details
                    </SecondaryButton>
                  </div>
                </div>

                <!-- League Details (Expandable) -->
                <div v-if="selectedLeagueId === league.id" class="mt-4 pt-4 border-t border-gray-200">
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-3">
                      <h5 class="font-medium text-gray-900">Liga-Informationen</h5>
                      <div class="text-sm space-y-1">
                        <p><span class="font-medium">Spielmodus:</span> {{ league.gameMode || 'Reguläre Saison + Playoffs' }}</p>
                        <p><span class="font-medium">Spieltage:</span> {{ league.gamedays || 'N/A' }}</p>
                        <p><span class="font-medium">Startdatum:</span> {{ formatDate(league.seasonStart) }}</p>
                        <p><span class="font-medium">Enddatum:</span> {{ formatDate(league.seasonEnd) }}</p>
                      </div>
                    </div>
                    
                    <div class="space-y-3">
                      <h5 class="font-medium text-gray-900">Anforderungen & Kosten</h5>
                      <div class="text-sm space-y-1">
                        <p><span class="font-medium">Teilnahmegebühr:</span> {{ league.registrationFee || 'Kostenlos' }}</p>
                        <p><span class="font-medium">Min. Spieleranzahl:</span> {{ league.minPlayers || '8' }}</p>
                        <p><span class="font-medium">Max. Ausländer:</span> {{ league.maxForeigners || 'Unbegrenzt' }}</p>
                        <p><span class="font-medium">Lizenzpflicht:</span> {{ league.licenseRequired ? 'Ja' : 'Nein' }}</p>
                      </div>
                    </div>
                  </div>

                  <div class="mt-4" v-if="league.currentTeamsList && league.currentTeamsList.length > 0">
                    <h5 class="font-medium text-gray-900 mb-2">Aktuell registrierte Teams</h5>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                      <div 
                        v-for="team in league.currentTeamsList" 
                        :key="team.id"
                        class="text-sm text-gray-600 bg-gray-100 rounded px-2 py-1"
                      >
                        {{ team.name }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Pagination -->
            <div v-if="availableLeagues.length > leaguesPerPage" class="flex justify-center space-x-2 mt-4">
              <button
                @click="currentPage > 1 && currentPage--"
                :disabled="currentPage === 1"
                class="px-3 py-1 text-sm border border-gray-300 rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
              >
                Zurück
              </button>
              
              <span class="px-3 py-1 text-sm text-gray-500">
                Seite {{ currentPage }} von {{ totalPages }}
              </span>
              
              <button
                @click="currentPage < totalPages && currentPage++"
                :disabled="currentPage === totalPages"
                class="px-3 py-1 text-sm border border-gray-300 rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
              >
                Weiter
              </button>
            </div>
          </div>

          <!-- No Leagues Found -->
          <div v-if="leaguesSearched && availableLeagues.length === 0" class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Keine Ligen gefunden</h3>
            <p class="mt-1 text-sm text-gray-500">Keine Ligen entsprechen den aktuellen Suchkriterien.</p>
          </div>
        </div>

        <!-- Registered Teams Tab -->
        <div v-if="activeTab === 'registered'" class="space-y-4">
          <div class="flex justify-between items-center">
            <h4 class="text-lg font-medium text-gray-900">Registrierte Teams</h4>
            <PrimaryButton @click="refreshRegistrations" :disabled="loadingRegistrations">
              Aktualisieren
            </PrimaryButton>
          </div>

          <div v-if="registeredTeams.length > 0" class="space-y-3">
            <div 
              v-for="registration in registeredTeams" 
              :key="registration.id"
              class="border rounded-lg p-4"
            >
              <div class="flex items-center justify-between">
                <div class="flex-1">
                  <div class="flex items-center space-x-3">
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center space-x-2">
                        <h3 class="text-lg font-medium text-gray-900">
                          {{ registration.teamName }}
                        </h3>
                        <span :class="[
                          'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                          registration.status === 'approved' ? 'bg-green-100 text-green-800' :
                          registration.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                          'bg-red-100 text-red-800'
                        ]">
                          {{ translateRegistrationStatus(registration.status) }}
                        </span>
                      </div>
                      <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mt-2 text-sm text-gray-500">
                        <div>
                          <span class="font-medium">Liga:</span> {{ registration.leagueName }}
                        </div>
                        <div>
                          <span class="font-medium">Registriert:</span> {{ formatDate(registration.registeredAt) }}
                        </div>
                        <div>
                          <span class="font-medium">Saison:</span> {{ registration.season }}
                        </div>
                        <div>
                          <span class="font-medium">Spieler:</span> {{ registration.playerCount }}/{{ registration.maxPlayers }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="flex space-x-2">
                  <SecondaryButton 
                    @click="viewRegistrationDetails(registration)" 
                    size="sm"
                  >
                    Details
                  </SecondaryButton>
                  <button
                    v-if="registration.status === 'pending'"
                    @click="cancelRegistration(registration)"
                    class="px-3 py-1 text-sm text-red-600 hover:text-red-800 border border-red-300 rounded-md hover:bg-red-50"
                  >
                    Stornieren
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- No Registrations -->
          <div v-else class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Keine Registrierungen</h3>
            <p class="mt-1 text-sm text-gray-500">Bisher wurden keine Teams für Ligen registriert.</p>
          </div>
        </div>

        <!-- Registration Confirmation Modal -->
        <div v-if="showRegistrationConfirm" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
          <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Liga-Registrierung bestätigen</h3>
            <p class="text-sm text-gray-500 mb-4">
              Möchten Sie Ihr Team "{{ selectedTeam?.name }}" für die Liga "{{ selectedLeague?.name }}" registrieren?
            </p>
            <div class="text-sm text-gray-700 space-y-1 mb-4">
              <p><span class="font-medium">Saison:</span> {{ selectedLeague?.season }}</p>
              <p><span class="font-medium">Teilnahmegebühr:</span> {{ selectedLeague?.registrationFee || 'Kostenlos' }}</p>
              <p><span class="font-medium">Anmeldeschluss:</span> {{ formatDate(selectedLeague?.registrationDeadline) }}</p>
            </div>
            <div class="flex justify-end space-x-3">
              <SecondaryButton @click="cancelRegistrationConfirm">
                Abbrechen
              </SecondaryButton>
              <PrimaryButton @click="confirmRegistration" :disabled="processingRegistration">
                Registrieren
              </PrimaryButton>
            </div>
          </div>
        </div>
      </div>
    </template>

    <template #footer>
      <SecondaryButton @click="close">
        Schließen
      </SecondaryButton>
    </template>
  </DialogModal>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import DialogModal from '@/Components/DialogModal.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'

// Props
const props = defineProps({
  show: Boolean
})

// Emits
const emit = defineEmits(['close', 'leagueRegistered'])

// Reactive data
const activeTab = ref('browse')
const leagueSearch = ref('')
const selectedLevel = ref('')
const selectedRegion = ref('')
const availableLeagues = ref([])
const registeredTeams = ref([])
const selectedLeagueId = ref(null)
const leaguesSearched = ref(false)
const loadingLeagues = ref(false)
const loadingRegistrations = ref(false)

// Registration confirmation
const showRegistrationConfirm = ref(false)
const selectedLeague = ref(null)
const selectedTeam = ref(null)
const processingRegistration = ref(false)

// Pagination
const currentPage = ref(1)
const leaguesPerPage = ref(5)

// Computed
const paginatedLeagues = computed(() => {
  const start = (currentPage.value - 1) * leaguesPerPage.value
  const end = start + leaguesPerPage.value
  return availableLeagues.value.slice(start, end)
})

const totalPages = computed(() => {
  return Math.ceil(availableLeagues.value.length / leaguesPerPage.value)
})

// Mock data for development
onMounted(() => {
  loadMockData()
})

const loadMockData = () => {
  availableLeagues.value = [
    {
      id: 1,
      name: 'Regionalliga Nord',
      level: 'regional',
      region: 'Nord',
      season: '2024/25',
      currentTeams: 12,
      maxTeams: 14,
      registrationOpen: true,
      registrationDeadline: '2024-09-15',
      seasonStart: '2024-10-01',
      seasonEnd: '2025-04-30',
      description: 'Die höchste Regionalliga im Norden Deutschlands',
      registrationFee: '€500',
      minPlayers: 10,
      maxForeigners: 2,
      licenseRequired: true
    },
    {
      id: 2,
      name: 'Oberliga West',
      level: 'oberliga',
      region: 'West',
      season: '2024/25',
      currentTeams: 16,
      maxTeams: 16,
      registrationOpen: false,
      registrationDeadline: '2024-08-31',
      seasonStart: '2024-09-15',
      seasonEnd: '2025-03-31',
      description: 'Oberliga für den westlichen Bereich',
      registrationFee: '€300',
      minPlayers: 8,
      maxForeigners: 1,
      licenseRequired: true
    }
  ]

  registeredTeams.value = [
    {
      id: 1,
      teamName: 'Mein Basketball Team',
      leagueName: 'Regionalliga Nord',
      season: '2024/25',
      status: 'pending',
      registeredAt: '2024-08-10',
      playerCount: 12,
      maxPlayers: 15
    }
  ]
}

// Methods
const searchLeagues = async () => {
  loadingLeagues.value = true
  leaguesSearched.value = false

  try {
    // API-Call to search leagues
    // const response = await axios.get('/federation/dbb/leagues', {
    //   params: {
    //     search: leagueSearch.value,
    //     level: selectedLevel.value,
    //     region: selectedRegion.value
    //   }
    // })
    // availableLeagues.value = response.data.leagues
    
    // For now, use mock data with filtering
    let filtered = availableLeagues.value
    
    if (leagueSearch.value) {
      filtered = filtered.filter(league => 
        league.name.toLowerCase().includes(leagueSearch.value.toLowerCase())
      )
    }
    
    if (selectedLevel.value) {
      filtered = filtered.filter(league => league.level === selectedLevel.value)
    }
    
    if (selectedRegion.value) {
      filtered = filtered.filter(league => 
        league.region.toLowerCase() === selectedRegion.value.toLowerCase()
      )
    }
    
    availableLeagues.value = filtered
    leaguesSearched.value = true
    currentPage.value = 1

  } catch (error) {
    console.error('League search failed:', error)
  } finally {
    loadingLeagues.value = false
  }
}

const clearLeagueSearch = () => {
  leagueSearch.value = ''
  selectedLevel.value = ''
  selectedRegion.value = ''
  loadMockData() // Reset to all leagues
  currentPage.value = 1
  leaguesSearched.value = false
}

const registerForLeague = (league) => {
  selectedLeague.value = league
  selectedTeam.value = { name: 'Mein Basketball Team' } // Would come from current tenant
  showRegistrationConfirm.value = true
}

const confirmRegistration = async () => {
  processingRegistration.value = true

  try {
    // API call to register team
    // await axios.post('/federation/dbb/team/register', {
    //   league_id: selectedLeague.value.id,
    //   team_id: selectedTeam.value.id
    // })

    // Add to registered teams (mock)
    registeredTeams.value.unshift({
      id: Date.now(),
      teamName: selectedTeam.value.name,
      leagueName: selectedLeague.value.name,
      season: selectedLeague.value.season,
      status: 'pending',
      registeredAt: new Date().toISOString(),
      playerCount: 12,
      maxPlayers: 15
    })

    emit('leagueRegistered', {
      league: selectedLeague.value,
      team: selectedTeam.value
    })

    showRegistrationConfirm.value = false
    activeTab.value = 'registered'

  } catch (error) {
    console.error('Registration failed:', error)
  } finally {
    processingRegistration.value = false
  }
}

const cancelRegistrationConfirm = () => {
  showRegistrationConfirm.value = false
  selectedLeague.value = null
  selectedTeam.value = null
}

const viewLeagueDetails = (league) => {
  selectedLeagueId.value = selectedLeagueId.value === league.id ? null : league.id
}

const viewRegistrationDetails = (registration) => {
  console.log('View registration details:', registration)
}

const cancelRegistration = async (registration) => {
  if (confirm('Möchten Sie diese Registrierung wirklich stornieren?')) {
    try {
      // API call to cancel registration
      // await axios.delete(`/federation/registrations/${registration.id}`)

      // Remove from list (mock)
      const index = registeredTeams.value.findIndex(r => r.id === registration.id)
      if (index > -1) {
        registeredTeams.value.splice(index, 1)
      }

    } catch (error) {
      console.error('Cancellation failed:', error)
    }
  }
}

const refreshRegistrations = async () => {
  loadingRegistrations.value = true

  try {
    // API call to refresh registrations
    // const response = await axios.get('/federation/registrations')
    // registeredTeams.value = response.data.registrations
    
  } catch (error) {
    console.error('Refresh failed:', error)
  } finally {
    loadingRegistrations.value = false
  }
}

const close = () => {
  activeTab.value = 'browse'
  selectedLeagueId.value = null
  emit('close')
}

// Utility functions
const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('de-DE')
}

const formatLeagueLevel = (level) => {
  const levels = {
    '1': '1. Liga',
    '2': '2. Liga',
    'regional': 'Regionalliga',
    'oberliga': 'Oberliga',
    'landesliga': 'Landesliga',
    'bezirksliga': 'Bezirksliga'
  }
  return levels[level] || level
}

const translateRegistrationStatus = (status) => {
  const translations = {
    pending: 'Ausstehend',
    approved: 'Genehmigt',
    rejected: 'Abgelehnt',
    cancelled: 'Storniert'
  }
  return translations[status] || status
}
</script>

<style scoped>
.bg-gold-100 {
  background-color: #fef3c7;
}
.text-gold-800 {
  color: #92400e;
}
.bg-silver-100 {
  background-color: #f1f5f9;
}
.text-silver-800 {
  color: #334155;
}
</style>