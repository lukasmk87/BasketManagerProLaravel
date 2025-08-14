<template>
  <DialogModal :show="show" @close="close" max-width="4xl">
    <template #title>
      Spielersuche in Verbands-Datenbanken
    </template>

    <template #content>
      <div class="space-y-6">
        <!-- Search Form -->
        <div class="bg-gray-50 p-4 rounded-lg">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
              <InputLabel for="license_number" value="Lizenznummer" />
              <TextInput
                id="license_number"
                ref="licenseNumberInput"
                v-model="form.licenseNumber"
                type="text"
                class="mt-1 block w-full"
                placeholder="z.B. 12345678"
                @keyup.enter="searchPlayer"
                maxlength="12"
              />
              <InputError :message="form.errors.licenseNumber" class="mt-2" />
            </div>

            <div>
              <InputLabel for="search_database" value="Datenbank" />
              <select 
                id="search_database" 
                v-model="form.database" 
                class="mt-1 block w-full border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm"
              >
                <option value="both">Beide (DBB & FIBA)</option>
                <option value="dbb">DBB (Deutscher Basketball Bund)</option>
                <option value="fiba">FIBA Europe</option>
              </select>
            </div>
          </div>

          <div class="flex items-center space-x-4">
            <PrimaryButton @click="searchPlayer" :disabled="form.processing || !form.licenseNumber">
              <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
              Spieler suchen
            </PrimaryButton>

            <SecondaryButton @click="clearSearch">
              Zurücksetzen
            </SecondaryButton>
          </div>
        </div>

        <!-- Search Results -->
        <div v-if="searchResults.length > 0" class="space-y-4">
          <h4 class="text-lg font-medium text-gray-900">Suchergebnisse</h4>
          
          <div class="grid gap-4">
            <div 
              v-for="player in searchResults" 
              :key="player.id"
              class="border rounded-lg p-4 hover:bg-gray-50"
            >
              <div class="flex items-center justify-between">
                <div class="flex-1">
                  <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0">
                      <img class="h-12 w-12 rounded-full" :src="player.avatar || '/default-avatar.png'" :alt="player.fullName">
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center space-x-2">
                        <h3 class="text-lg font-medium text-gray-900">
                          {{ player.firstName }} {{ player.lastName }}
                        </h3>
                        <span :class="[
                          'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                          player.source === 'dbb' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'
                        ]">
                          {{ player.source.toUpperCase() }}
                        </span>
                      </div>
                      <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mt-2 text-sm text-gray-500">
                        <div>
                          <span class="font-medium">Lizenznr:</span> {{ player.licenseNumber }}
                        </div>
                        <div>
                          <span class="font-medium">Geboren:</span> {{ formatDate(player.birthDate) }}
                        </div>
                        <div>
                          <span class="font-medium">Position:</span> {{ player.position || 'N/A' }}
                        </div>
                        <div>
                          <span class="font-medium">Status:</span> 
                          <span :class="[
                            player.status === 'active' ? 'text-green-600' : 
                            player.status === 'suspended' ? 'text-red-600' : 'text-yellow-600'
                          ]">
                            {{ translateStatus(player.status) }}
                          </span>
                        </div>
                      </div>
                      <div class="mt-2 text-sm text-gray-600" v-if="player.currentTeam">
                        <span class="font-medium">Aktuelles Team:</span> {{ player.currentTeam }}
                      </div>
                    </div>
                  </div>
                </div>
                <div class="flex flex-col space-y-2">
                  <PrimaryButton 
                    @click="selectPlayer(player)" 
                    size="sm"
                  >
                    Auswählen
                  </PrimaryButton>
                  <SecondaryButton 
                    @click="viewPlayerDetails(player)" 
                    size="sm"
                  >
                    Details
                  </SecondaryButton>
                </div>
              </div>

              <!-- Player Details (Expandable) -->
              <div v-if="selectedPlayerId === player.id" class="mt-4 pt-4 border-t border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="space-y-2">
                    <h5 class="font-medium text-gray-900">Allgemeine Informationen</h5>
                    <div class="text-sm space-y-1">
                      <p><span class="font-medium">Nationalität:</span> {{ player.nationality || 'N/A' }}</p>
                      <p><span class="font-medium">Größe:</span> {{ player.height || 'N/A' }} cm</p>
                      <p><span class="font-medium">Gewicht:</span> {{ player.weight || 'N/A' }} kg</p>
                      <p><span class="font-medium">Registriert seit:</span> {{ formatDate(player.registeredSince) }}</p>
                    </div>
                  </div>
                  
                  <div class="space-y-2">
                    <h5 class="font-medium text-gray-900">Berechtigung & Status</h5>
                    <div class="text-sm space-y-1">
                      <p><span class="font-medium">Spielberechtigt:</span> 
                        <span :class="player.eligible ? 'text-green-600' : 'text-red-600'">
                          {{ player.eligible ? 'Ja' : 'Nein' }}
                        </span>
                      </p>
                      <p v-if="player.transferWindow"><span class="font-medium">Transfer-Fenster:</span> {{ player.transferWindow }}</p>
                      <p v-if="player.suspensionReason"><span class="font-medium">Sperrgrund:</span> {{ player.suspensionReason }}</p>
                    </div>
                  </div>
                </div>

                <div class="mt-4" v-if="player.recentTeams && player.recentTeams.length > 0">
                  <h5 class="font-medium text-gray-900 mb-2">Vereinshistorie</h5>
                  <div class="space-y-1">
                    <div 
                      v-for="team in player.recentTeams" 
                      :key="team.id"
                      class="text-sm text-gray-600 flex justify-between"
                    >
                      <span>{{ team.name }}</span>
                      <span>{{ formatDate(team.from) }} - {{ team.to ? formatDate(team.to) : 'heute' }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- No Results -->
        <div v-if="searchPerformed && searchResults.length === 0 && !form.processing" class="text-center py-8">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">Keine Spieler gefunden</h3>
          <p class="mt-1 text-sm text-gray-500">Kein Spieler mit dieser Lizenznummer in der ausgewählten Datenbank gefunden.</p>
        </div>

        <!-- Error Message -->
        <div v-if="errorMessage" class="bg-red-50 border border-red-200 rounded-md p-4">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-red-800">Fehler bei der Spielersuche</h3>
              <div class="mt-2 text-sm text-red-700">
                {{ errorMessage }}
              </div>
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
import { ref, nextTick, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import DialogModal from '@/Components/DialogModal.vue'
import InputLabel from '@/Components/InputLabel.vue'
import InputError from '@/Components/InputError.vue'
import TextInput from '@/Components/TextInput.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'

// Props
const props = defineProps({
  show: Boolean
})

// Emits
const emit = defineEmits(['close', 'playerFound'])

// Form
const form = useForm({
  licenseNumber: '',
  database: 'both'
})

// Reactive data
const licenseNumberInput = ref(null)
const searchResults = ref([])
const searchPerformed = ref(false)
const selectedPlayerId = ref(null)
const errorMessage = ref('')

// Watch for modal opening
watch(() => props.show, (show) => {
  if (show) {
    nextTick(() => licenseNumberInput.value?.focus())
  }
})

// Methods
const searchPlayer = async () => {
  if (!form.licenseNumber) return

  form.processing = true
  errorMessage.value = ''
  searchPerformed.value = false

  try {
    let endpoints = []
    
    if (form.database === 'both' || form.database === 'dbb') {
      endpoints.push(axios.get(`/federation/dbb/player?license_number=${form.licenseNumber}`))
    }
    
    if (form.database === 'both' || form.database === 'fiba') {
      endpoints.push(axios.get(`/federation/fiba/player/search?license_number=${form.licenseNumber}`))
    }

    const responses = await Promise.allSettled(endpoints)
    const results = []

    responses.forEach((response, index) => {
      if (response.status === 'fulfilled' && response.value.data.success) {
        const player = response.value.data.player
        player.source = form.database === 'both' ? (index === 0 ? 'dbb' : 'fiba') : form.database
        results.push(player)
      }
    })

    searchResults.value = results
    searchPerformed.value = true

    if (results.length === 0) {
      errorMessage.value = 'Spieler mit dieser Lizenznummer nicht gefunden.'
    }

  } catch (error) {
    console.error('Player search failed:', error)
    errorMessage.value = 'Fehler bei der Verbindung zu den Verbandsdatenbanken. Bitte versuchen Sie es später erneut.'
    searchResults.value = []
    searchPerformed.value = true
  } finally {
    form.processing = false
  }
}

const clearSearch = () => {
  form.reset()
  searchResults.value = []
  searchPerformed.value = false
  selectedPlayerId.value = null
  errorMessage.value = ''
  nextTick(() => licenseNumberInput.value?.focus())
}

const selectPlayer = (player) => {
  emit('playerFound', player)
  close()
}

const viewPlayerDetails = (player) => {
  selectedPlayerId.value = selectedPlayerId.value === player.id ? null : player.id
}

const close = () => {
  form.reset()
  form.clearErrors()
  searchResults.value = []
  searchPerformed.value = false
  selectedPlayerId.value = null
  errorMessage.value = ''
  emit('close')
}

// Utility functions
const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('de-DE')
}

const translateStatus = (status) => {
  const translations = {
    active: 'Aktiv',
    suspended: 'Gesperrt',
    inactive: 'Inaktiv',
    transfer: 'Wechsel'
  }
  return translations[status] || status
}
</script>