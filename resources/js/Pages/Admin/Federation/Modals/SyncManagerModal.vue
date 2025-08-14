<template>
  <DialogModal :show="show" @close="close" max-width="4xl">
    <template #title>
      Federation Daten-Synchronisation
    </template>

    <template #content>
      <div class="space-y-6">
        <!-- Sync Overview -->
        <div class="bg-gray-50 p-4 rounded-lg">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">Synchronisations-Status</h3>
            <div class="text-sm text-gray-500">
              Letzte Sync: {{ lastSync ? formatDateTime(lastSync) : 'Noch nie' }}
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- DBB Sync Status -->
            <div class="border rounded-lg p-3">
              <div class="flex items-center justify-between mb-2">
                <h4 class="font-medium text-gray-900">DBB Synchronisation</h4>
                <span :class="[
                  'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                  dbbSyncStatus === 'success' ? 'bg-green-100 text-green-800' :
                  dbbSyncStatus === 'running' ? 'bg-blue-100 text-blue-800' :
                  dbbSyncStatus === 'error' ? 'bg-red-100 text-red-800' :
                  'bg-gray-100 text-gray-800'
                ]">
                  {{ translateSyncStatus(dbbSyncStatus) }}
                </span>
              </div>
              <div class="text-sm text-gray-600">
                <p>Spieler: {{ dbbStats.players || 0 }}</p>
                <p>Teams: {{ dbbStats.teams || 0 }}</p>
                <p>Ligen: {{ dbbStats.leagues || 0 }}</p>
              </div>
            </div>

            <!-- FIBA Sync Status -->
            <div class="border rounded-lg p-3">
              <div class="flex items-center justify-between mb-2">
                <h4 class="font-medium text-gray-900">FIBA Synchronisation</h4>
                <span :class="[
                  'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                  fibaSyncStatus === 'success' ? 'bg-green-100 text-green-800' :
                  fibaSyncStatus === 'running' ? 'bg-blue-100 text-blue-800' :
                  fibaSyncStatus === 'error' ? 'bg-red-100 text-red-800' :
                  'bg-gray-100 text-gray-800'
                ]">
                  {{ translateSyncStatus(fibaSyncStatus) }}
                </span>
              </div>
              <div class="text-sm text-gray-600">
                <p>Spieler: {{ fibaStats.players || 0 }}</p>
                <p>Wettkämpfe: {{ fibaStats.competitions || 0 }}</p>
                <p>Clubs: {{ fibaStats.clubs || 0 }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Sync Options -->
        <div class="space-y-4">
          <h3 class="text-lg font-medium text-gray-900">Synchronisations-Optionen</h3>

          <!-- Sync Type Selection -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <InputLabel for="sync_type" value="Synchronisations-Typ" />
              <select 
                id="sync_type" 
                v-model="syncOptions.type" 
                class="mt-1 block w-full border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm"
              >
                <option value="incremental">Inkrementell (nur Änderungen)</option>
                <option value="full">Vollständig (alle Daten)</option>
                <option value="selective">Selektiv (ausgewählte Bereiche)</option>
              </select>
            </div>

            <div>
              <InputLabel for="federation_select" value="Verband" />
              <select 
                id="federation_select" 
                v-model="syncOptions.federation" 
                class="mt-1 block w-full border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm"
              >
                <option value="both">Beide (DBB & FIBA)</option>
                <option value="dbb">Nur DBB</option>
                <option value="fiba">Nur FIBA</option>
              </select>
            </div>
          </div>

          <!-- Selective Sync Options -->
          <div v-if="syncOptions.type === 'selective'" class="bg-gray-50 p-4 rounded-lg">
            <h4 class="font-medium text-gray-900 mb-3">Zu synchronisierende Bereiche</h4>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
              <label class="flex items-center">
                <input 
                  type="checkbox" 
                  v-model="syncOptions.areas" 
                  value="players"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                >
                <span class="ml-2 text-sm text-gray-700">Spielerdaten</span>
              </label>
              <label class="flex items-center">
                <input 
                  type="checkbox" 
                  v-model="syncOptions.areas" 
                  value="teams"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                >
                <span class="ml-2 text-sm text-gray-700">Teams</span>
              </label>
              <label class="flex items-center">
                <input 
                  type="checkbox" 
                  v-model="syncOptions.areas" 
                  value="leagues"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                >
                <span class="ml-2 text-sm text-gray-700">Ligen</span>
              </label>
              <label class="flex items-center">
                <input 
                  type="checkbox" 
                  v-model="syncOptions.areas" 
                  value="competitions"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                >
                <span class="ml-2 text-sm text-gray-700">Wettkämpfe</span>
              </label>
              <label class="flex items-center">
                <input 
                  type="checkbox" 
                  v-model="syncOptions.areas" 
                  value="officials"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                >
                <span class="ml-2 text-sm text-gray-700">Schiedsrichter</span>
              </label>
              <label class="flex items-center">
                <input 
                  type="checkbox" 
                  v-model="syncOptions.areas" 
                  value="schedules"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                >
                <span class="ml-2 text-sm text-gray-700">Spielpläne</span>
              </label>
            </div>
          </div>

          <!-- Advanced Options -->
          <div class="bg-gray-50 p-4 rounded-lg">
            <h4 class="font-medium text-gray-900 mb-3">Erweiterte Optionen</h4>
            <div class="space-y-3">
              <label class="flex items-center">
                <input 
                  type="checkbox" 
                  v-model="syncOptions.validateData"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                >
                <span class="ml-2 text-sm text-gray-700">Daten vor Import validieren</span>
              </label>
              <label class="flex items-center">
                <input 
                  type="checkbox" 
                  v-model="syncOptions.backupBeforeSync"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                >
                <span class="ml-2 text-sm text-gray-700">Backup vor Synchronisation erstellen</span>
              </label>
              <label class="flex items-center">
                <input 
                  type="checkbox" 
                  v-model="syncOptions.sendNotification"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                >
                <span class="ml-2 text-sm text-gray-700">E-Mail-Benachrichtigung nach Abschluss</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Sync Progress -->
        <div v-if="isSyncing" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <div class="flex items-center justify-between mb-2">
            <h4 class="font-medium text-blue-900">Synchronisation läuft...</h4>
            <span class="text-sm text-blue-700">{{ Math.round(syncProgress) }}%</span>
          </div>
          <div class="w-full bg-blue-200 rounded-full h-2 mb-2">
            <div 
              class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
              :style="{ width: syncProgress + '%' }"
            ></div>
          </div>
          <div class="text-sm text-blue-700">
            {{ currentSyncStep }}
          </div>
        </div>

        <!-- Sync Results -->
        <div v-if="syncResult && !isSyncing" class="space-y-4">
          <h4 class="text-lg font-medium text-gray-900">Synchronisations-Ergebnis</h4>
          
          <div :class="[
            'border rounded-lg p-4',
            syncResult.success ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50'
          ]">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg v-if="syncResult.success" class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <svg v-else class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <h5 :class="[
                  'text-sm font-medium',
                  syncResult.success ? 'text-green-800' : 'text-red-800'
                ]">
                  {{ syncResult.success ? 'Synchronisation erfolgreich' : 'Synchronisation fehlgeschlagen' }}
                </h5>
                <div :class="[
                  'mt-2 text-sm',
                  syncResult.success ? 'text-green-700' : 'text-red-700'
                ]">
                  <p>Dauer: {{ formatDuration(syncResult.duration) }}</p>
                  <p>Verarbeitete Datensätze: {{ syncResult.recordsProcessed || 0 }}</p>
                  <p>Aktualisiert: {{ syncResult.recordsUpdated || 0 }}</p>
                  <p>Neu erstellt: {{ syncResult.recordsCreated || 0 }}</p>
                  <p v-if="syncResult.recordsSkipped">Übersprungen: {{ syncResult.recordsSkipped }}</p>
                  <p v-if="syncResult.errors && syncResult.errors.length > 0">
                    Fehler: {{ syncResult.errors.length }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Error Details -->
          <div v-if="syncResult.errors && syncResult.errors.length > 0" class="bg-red-50 border border-red-200 rounded-lg p-4">
            <h5 class="font-medium text-red-800 mb-2">Fehlerdetails</h5>
            <div class="space-y-1">
              <div 
                v-for="(error, index) in syncResult.errors.slice(0, 5)" 
                :key="index"
                class="text-sm text-red-700"
              >
                {{ error.message }} <span v-if="error.code" class="text-red-500">(Code: {{ error.code }})</span>
              </div>
              <div v-if="syncResult.errors.length > 5" class="text-sm text-red-600">
                ... und {{ syncResult.errors.length - 5 }} weitere Fehler
              </div>
            </div>
          </div>
        </div>

        <!-- Sync History -->
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <h4 class="text-lg font-medium text-gray-900">Letzte Synchronisationen</h4>
            <SecondaryButton @click="refreshHistory" size="sm">
              Aktualisieren
            </SecondaryButton>
          </div>

          <div class="space-y-2">
            <div 
              v-for="sync in syncHistory" 
              :key="sync.id"
              class="border rounded-lg p-3 hover:bg-gray-50"
            >
              <div class="flex items-center justify-between">
                <div class="flex-1">
                  <div class="flex items-center space-x-2">
                    <span :class="[
                      'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                      sync.status === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                    ]">
                      {{ sync.status === 'success' ? 'Erfolgreich' : 'Fehlgeschlagen' }}
                    </span>
                    <span class="text-sm font-medium text-gray-900">{{ sync.type }}</span>
                    <span class="text-sm text-gray-500">{{ sync.federation }}</span>
                  </div>
                  <div class="mt-1 text-sm text-gray-600">
                    {{ formatDateTime(sync.createdAt) }} - 
                    {{ sync.recordsProcessed || 0 }} Datensätze - 
                    {{ formatDuration(sync.duration) }}
                  </div>
                </div>
                <SecondaryButton @click="viewSyncDetails(sync)" size="sm">
                  Details
                </SecondaryButton>
              </div>
            </div>
          </div>

          <!-- No History -->
          <div v-if="syncHistory.length === 0" class="text-center py-6">
            <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Keine Synchronisations-Historie</h3>
            <p class="mt-1 text-sm text-gray-500">Bisher wurden noch keine Synchronisationen durchgeführt.</p>
          </div>
        </div>
      </div>
    </template>

    <template #footer>
      <div class="flex justify-between">
        <SecondaryButton @click="close">
          Schließen
        </SecondaryButton>
        
        <PrimaryButton 
          @click="startSync" 
          :disabled="isSyncing || (syncOptions.type === 'selective' && syncOptions.areas.length === 0)"
          class="bg-blue-600 hover:bg-blue-700"
        >
          <svg v-if="isSyncing" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
          </svg>
          {{ isSyncing ? 'Synchronisiert...' : 'Synchronisation starten' }}
        </PrimaryButton>
      </div>
    </template>
  </DialogModal>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import DialogModal from '@/Components/DialogModal.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'

// Props
const props = defineProps({
  show: Boolean
})

// Emits
const emit = defineEmits(['close', 'syncCompleted'])

// Reactive data
const lastSync = ref(null)
const dbbSyncStatus = ref('idle')
const fibaSyncStatus = ref('idle')
const dbbStats = ref({ players: 1248, teams: 89, leagues: 12 })
const fibaStats = ref({ players: 2156, competitions: 8, clubs: 145 })

const syncOptions = ref({
  type: 'incremental',
  federation: 'both',
  areas: ['players', 'teams'],
  validateData: true,
  backupBeforeSync: true,
  sendNotification: true
})

const isSyncing = ref(false)
const syncProgress = ref(0)
const currentSyncStep = ref('')
const syncResult = ref(null)
const syncHistory = ref([])

// Mock data
onMounted(() => {
  loadMockData()
})

const loadMockData = () => {
  lastSync.value = new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString() // 2 hours ago
  
  syncHistory.value = [
    {
      id: 1,
      type: 'Inkrementell',
      federation: 'DBB',
      status: 'success',
      recordsProcessed: 245,
      duration: 125000, // milliseconds
      createdAt: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString()
    },
    {
      id: 2,
      type: 'Vollständig',
      federation: 'FIBA',
      status: 'error',
      recordsProcessed: 1024,
      duration: 310000,
      createdAt: new Date(Date.now() - 24 * 60 * 60 * 1000).toISOString()
    },
    {
      id: 3,
      type: 'Selektiv',
      federation: 'Beide',
      status: 'success',
      recordsProcessed: 89,
      duration: 45000,
      createdAt: new Date(Date.now() - 3 * 24 * 60 * 60 * 1000).toISOString()
    }
  ]
}

// Methods
const startSync = async () => {
  isSyncing.value = true
  syncProgress.value = 0
  syncResult.value = null

  try {
    // Simulate sync process
    const steps = [
      'Verbindung zu APIs herstellen...',
      'Daten abrufen...',
      'Validierung läuft...',
      'Daten importieren...',
      'Cache aktualisieren...',
      'Synchronisation abschließen...'
    ]

    for (let i = 0; i < steps.length; i++) {
      currentSyncStep.value = steps[i]
      syncProgress.value = (i + 1) / steps.length * 100
      await new Promise(resolve => setTimeout(resolve, 1000))
    }

    // Mock result
    syncResult.value = {
      success: true,
      duration: 6000,
      recordsProcessed: 423,
      recordsUpdated: 156,
      recordsCreated: 67,
      recordsSkipped: 12,
      errors: []
    }

    // Update history
    syncHistory.value.unshift({
      id: Date.now(),
      type: syncOptions.value.type === 'incremental' ? 'Inkrementell' : 
            syncOptions.value.type === 'full' ? 'Vollständig' : 'Selektiv',
      federation: syncOptions.value.federation === 'both' ? 'Beide' : 
                  syncOptions.value.federation.toUpperCase(),
      status: 'success',
      recordsProcessed: syncResult.value.recordsProcessed,
      duration: syncResult.value.duration,
      createdAt: new Date().toISOString()
    })

    // Update last sync time
    lastSync.value = new Date().toISOString()

    emit('syncCompleted', syncResult.value)

  } catch (error) {
    console.error('Sync failed:', error)
    syncResult.value = {
      success: false,
      duration: 3000,
      recordsProcessed: 89,
      recordsUpdated: 0,
      recordsCreated: 0,
      errors: [
        { message: 'API-Verbindung zu DBB fehlgeschlagen', code: 'CONN_001' },
        { message: 'Ungültige Datenstruktur erkannt', code: 'DATA_002' }
      ]
    }
  } finally {
    isSyncing.value = false
    currentSyncStep.value = ''
    syncProgress.value = 0
  }
}

const refreshHistory = async () => {
  try {
    // API call to refresh history
    // const response = await axios.get('/federation/sync/history')
    // syncHistory.value = response.data.history
  } catch (error) {
    console.error('Failed to refresh history:', error)
  }
}

const viewSyncDetails = (sync) => {
  console.log('View sync details:', sync)
  // Open detailed sync report
}

const close = () => {
  if (!isSyncing.value) {
    syncResult.value = null
    emit('close')
  }
}

// Utility functions
const translateSyncStatus = (status) => {
  const translations = {
    idle: 'Bereit',
    running: 'Läuft',
    success: 'Erfolgreich',
    error: 'Fehler'
  }
  return translations[status] || status
}

const formatDateTime = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleString('de-DE')
}

const formatDuration = (milliseconds) => {
  if (!milliseconds) return '0s'
  
  const seconds = Math.floor(milliseconds / 1000)
  const minutes = Math.floor(seconds / 60)
  
  if (minutes > 0) {
    return `${minutes}m ${seconds % 60}s`
  }
  return `${seconds}s`
}
</script>