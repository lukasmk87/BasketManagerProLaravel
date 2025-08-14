<template>
  <DialogModal :show="show" @close="close" max-width="4xl">
    <template #title>
      Team-Registrierung für Wettkämpfe
    </template>

    <template #content>
      <div class="space-y-6">
        <!-- Step Indicator -->
        <div class="flex items-center justify-center space-x-4 mb-6">
          <div 
            v-for="(step, index) in steps" 
            :key="index"
            class="flex items-center"
          >
            <div :class="[
              'flex items-center justify-center w-8 h-8 rounded-full border-2 text-sm font-medium',
              currentStep >= index + 1 
                ? 'bg-indigo-600 border-indigo-600 text-white' 
                : 'border-gray-300 text-gray-500'
            ]">
              {{ index + 1 }}
            </div>
            <div :class="[
              'ml-2 text-sm font-medium',
              currentStep >= index + 1 ? 'text-indigo-600' : 'text-gray-500'
            ]">
              {{ step.title }}
            </div>
            <div v-if="index < steps.length - 1" class="ml-4 w-8 border-t border-gray-300"></div>
          </div>
        </div>

        <!-- Step 1: Team Selection -->
        <div v-if="currentStep === 1" class="space-y-4">
          <h3 class="text-lg font-medium text-gray-900">Team auswählen</h3>
          
          <div class="bg-gray-50 p-4 rounded-lg">
            <div class="grid grid-cols-1 gap-4">
              <div>
                <InputLabel for="team_select" value="Team" />
                <select 
                  id="team_select" 
                  v-model="form.selectedTeam" 
                  class="mt-1 block w-full border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm"
                  @change="loadTeamDetails"
                >
                  <option value="">Team auswählen...</option>
                  <option v-for="team in availableTeams" :key="team.id" :value="team">
                    {{ team.name }}
                  </option>
                </select>
                <InputError :message="form.errors.selectedTeam" class="mt-2" />
              </div>
            </div>
          </div>

          <!-- Selected Team Details -->
          <div v-if="form.selectedTeam" class="border rounded-lg p-4 bg-white">
            <div class="flex items-start space-x-4">
              <div class="flex-shrink-0">
                <img class="h-16 w-16 rounded-lg object-cover" :src="form.selectedTeam.logo || '/default-team-logo.png'" :alt="form.selectedTeam.name">
              </div>
              <div class="flex-1">
                <h4 class="text-lg font-medium text-gray-900">{{ form.selectedTeam.name }}</h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mt-2 text-sm text-gray-500">
                  <div>
                    <span class="font-medium">Liga:</span> {{ form.selectedTeam.currentLeague || 'N/A' }}
                  </div>
                  <div>
                    <span class="font-medium">Spieler:</span> {{ form.selectedTeam.playerCount || 0 }}
                  </div>
                  <div>
                    <span class="font-medium">Trainer:</span> {{ form.selectedTeam.headCoach || 'N/A' }}
                  </div>
                  <div>
                    <span class="font-medium">Gegründet:</span> {{ form.selectedTeam.foundedYear || 'N/A' }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Step 2: Competition Selection -->
        <div v-if="currentStep === 2" class="space-y-4">
          <h3 class="text-lg font-medium text-gray-900">Wettkampf auswählen</h3>

          <!-- Competition Type Selection -->
          <div class="bg-gray-50 p-4 rounded-lg">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
              <div>
                <InputLabel for="competition_type" value="Wettkampf-Art" />
                <select 
                  id="competition_type" 
                  v-model="form.competitionType" 
                  class="mt-1 block w-full border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm"
                  @change="loadCompetitions"
                >
                  <option value="">Art auswählen...</option>
                  <option value="league">Liga</option>
                  <option value="tournament">Turnier</option>
                  <option value="cup">Pokal</option>
                </select>
              </div>

              <div>
                <InputLabel for="federation" value="Verband" />
                <select 
                  id="federation" 
                  v-model="form.federation" 
                  class="mt-1 block w-full border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm"
                  @change="loadCompetitions"
                >
                  <option value="">Verband auswählen...</option>
                  <option value="dbb">DBB (Deutscher Basketball Bund)</option>
                  <option value="fiba">FIBA Europe</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Available Competitions -->
          <div v-if="availableCompetitions.length > 0" class="space-y-3">
            <h4 class="font-medium text-gray-900">Verfügbare Wettkämpfe</h4>
            
            <div class="grid gap-3">
              <div 
                v-for="competition in availableCompetitions" 
                :key="competition.id"
                @click="selectCompetition(competition)"
                :class="[
                  'border rounded-lg p-4 cursor-pointer transition-colors',
                  form.selectedCompetition?.id === competition.id
                    ? 'border-indigo-500 bg-indigo-50'
                    : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'
                ]"
              >
                <div class="flex items-center justify-between">
                  <div class="flex-1">
                    <div class="flex items-center space-x-2">
                      <h5 class="font-medium text-gray-900">{{ competition.name }}</h5>
                      <span :class="[
                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                        competition.registrationOpen 
                          ? 'bg-green-100 text-green-800' 
                          : 'bg-red-100 text-red-800'
                      ]">
                        {{ competition.registrationOpen ? 'Offen' : 'Geschlossen' }}
                      </span>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mt-2 text-sm text-gray-500">
                      <div>
                        <span class="font-medium">Saison:</span> {{ competition.season }}
                      </div>
                      <div>
                        <span class="font-medium">Teilnehmer:</span> {{ competition.currentTeams }}/{{ competition.maxTeams }}
                      </div>
                      <div>
                        <span class="font-medium">Start:</span> {{ formatDate(competition.startDate) }}
                      </div>
                      <div>
                        <span class="font-medium">Anmeldeschluss:</span> {{ formatDate(competition.registrationDeadline) }}
                      </div>
                    </div>
                  </div>
                  <div class="ml-4">
                    <div :class="[
                      'w-4 h-4 rounded-full border-2',
                      form.selectedCompetition?.id === competition.id
                        ? 'bg-indigo-600 border-indigo-600'
                        : 'border-gray-300'
                    ]"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- No Competitions -->
          <div v-if="form.competitionType && form.federation && availableCompetitions.length === 0" class="text-center py-6">
            <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Keine Wettkämpfe verfügbar</h3>
            <p class="mt-1 text-sm text-gray-500">Für die gewählten Kriterien sind derzeit keine Wettkämpfe verfügbar.</p>
          </div>
        </div>

        <!-- Step 3: Registration Details -->
        <div v-if="currentStep === 3" class="space-y-4">
          <h3 class="text-lg font-medium text-gray-900">Registrierungsdetails</h3>

          <!-- Competition Summary -->
          <div v-if="form.selectedCompetition" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3 flex-1">
                <h4 class="text-sm font-medium text-blue-800">Registrierung für: {{ form.selectedCompetition.name }}</h4>
                <div class="mt-2 text-sm text-blue-700">
                  <p>Team: {{ form.selectedTeam.name }}</p>
                  <p>Saison: {{ form.selectedCompetition.season }}</p>
                  <p>Teilnahmegebühr: {{ form.selectedCompetition.registrationFee || 'Kostenlos' }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Registration Form -->
          <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <InputLabel for="contact_person" value="Ansprechpartner" />
                <TextInput
                  id="contact_person"
                  v-model="form.contactPerson"
                  type="text"
                  class="mt-1 block w-full"
                  required
                />
                <InputError :message="form.errors.contactPerson" class="mt-2" />
              </div>

              <div>
                <InputLabel for="contact_email" value="Kontakt E-Mail" />
                <TextInput
                  id="contact_email"
                  v-model="form.contactEmail"
                  type="email"
                  class="mt-1 block w-full"
                  required
                />
                <InputError :message="form.errors.contactEmail" class="mt-2" />
              </div>

              <div>
                <InputLabel for="contact_phone" value="Telefon" />
                <TextInput
                  id="contact_phone"
                  v-model="form.contactPhone"
                  type="tel"
                  class="mt-1 block w-full"
                />
              </div>

              <div>
                <InputLabel for="expected_players" value="Erwartete Spieleranzahl" />
                <TextInput
                  id="expected_players"
                  v-model="form.expectedPlayers"
                  type="number"
                  min="8"
                  max="20"
                  class="mt-1 block w-full"
                  required
                />
                <InputError :message="form.errors.expectedPlayers" class="mt-2" />
              </div>
            </div>

            <div>
              <InputLabel for="additional_notes" value="Zusätzliche Anmerkungen" />
              <textarea
                id="additional_notes"
                v-model="form.additionalNotes"
                rows="3"
                class="mt-1 block w-full border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm"
                placeholder="Besondere Anforderungen, Anmerkungen..."
              ></textarea>
            </div>

            <!-- Terms and Conditions -->
            <div class="flex items-start space-x-3">
              <input
                id="accept_terms"
                v-model="form.acceptTerms"
                type="checkbox"
                class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                required
              >
              <label for="accept_terms" class="text-sm text-gray-700">
                Ich akzeptiere die 
                <a href="#" class="text-indigo-600 hover:text-indigo-500">Teilnahmebedingungen</a>
                und 
                <a href="#" class="text-indigo-600 hover:text-indigo-500">Datenschutzbestimmungen</a>
                des Verbandes.
              </label>
            </div>
            <InputError :message="form.errors.acceptTerms" class="mt-2" />
          </div>
        </div>

        <!-- Step 4: Confirmation -->
        <div v-if="currentStep === 4" class="space-y-4">
          <h3 class="text-lg font-medium text-gray-900">Registrierung bestätigen</h3>

          <div class="bg-gray-50 rounded-lg p-4 space-y-4">
            <div>
              <h4 class="font-medium text-gray-900">Team</h4>
              <p class="text-sm text-gray-600">{{ form.selectedTeam?.name }}</p>
            </div>

            <div>
              <h4 class="font-medium text-gray-900">Wettkampf</h4>
              <p class="text-sm text-gray-600">{{ form.selectedCompetition?.name }}</p>
              <p class="text-sm text-gray-500">{{ form.selectedCompetition?.season }}</p>
            </div>

            <div>
              <h4 class="font-medium text-gray-900">Kontaktdaten</h4>
              <div class="text-sm text-gray-600">
                <p>{{ form.contactPerson }}</p>
                <p>{{ form.contactEmail }}</p>
                <p v-if="form.contactPhone">{{ form.contactPhone }}</p>
              </div>
            </div>

            <div>
              <h4 class="font-medium text-gray-900">Erwartete Spieleranzahl</h4>
              <p class="text-sm text-gray-600">{{ form.expectedPlayers }} Spieler</p>
            </div>

            <div v-if="form.additionalNotes">
              <h4 class="font-medium text-gray-900">Anmerkungen</h4>
              <p class="text-sm text-gray-600">{{ form.additionalNotes }}</p>
            </div>
          </div>

          <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <h4 class="text-sm font-medium text-yellow-800">Wichtiger Hinweis</h4>
                <p class="mt-1 text-sm text-yellow-700">
                  Nach der Registrierung erhalten Sie eine Bestätigungs-E-Mail. Die endgültige Teilnahmeberechtigung 
                  wird vom jeweiligen Verband geprüft und bestätigt.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <template #footer>
      <div class="flex justify-between">
        <SecondaryButton 
          v-if="currentStep > 1" 
          @click="previousStep"
        >
          Zurück
        </SecondaryButton>
        <div v-else></div>

        <div class="flex space-x-3">
          <SecondaryButton @click="close">
            Abbrechen
          </SecondaryButton>
          
          <PrimaryButton 
            v-if="currentStep < 4"
            @click="nextStep" 
            :disabled="!canProceed"
          >
            Weiter
          </PrimaryButton>
          
          <PrimaryButton 
            v-else
            @click="submitRegistration" 
            :disabled="form.processing"
            class="bg-green-600 hover:bg-green-700"
          >
            <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Registrierung abschließen
          </PrimaryButton>
        </div>
      </div>
    </template>
  </DialogModal>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
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
const emit = defineEmits(['close', 'teamRegistered'])

// Steps
const steps = ref([
  { title: 'Team auswählen' },
  { title: 'Wettkampf' },
  { title: 'Details' },
  { title: 'Bestätigung' }
])

const currentStep = ref(1)

// Form
const form = useForm({
  selectedTeam: null,
  competitionType: '',
  federation: '',
  selectedCompetition: null,
  contactPerson: '',
  contactEmail: '',
  contactPhone: '',
  expectedPlayers: 12,
  additionalNotes: '',
  acceptTerms: false
})

// Data
const availableTeams = ref([])
const availableCompetitions = ref([])

// Mock data
onMounted(() => {
  loadMockData()
})

const loadMockData = () => {
  availableTeams.value = [
    {
      id: 1,
      name: 'Mein Basketball Team',
      logo: null,
      currentLeague: 'Regionalliga Nord',
      playerCount: 14,
      headCoach: 'Max Mustermann',
      foundedYear: 2018
    },
    {
      id: 2,
      name: 'Zweites Team',
      logo: null,
      currentLeague: 'Oberliga',
      playerCount: 12,
      headCoach: 'Anna Schmidt',
      foundedYear: 2020
    }
  ]
}

// Computed
const canProceed = computed(() => {
  switch (currentStep.value) {
    case 1:
      return form.selectedTeam !== null
    case 2:
      return form.selectedCompetition !== null
    case 3:
      return form.contactPerson && form.contactEmail && form.expectedPlayers && form.acceptTerms
    default:
      return true
  }
})

// Methods
const loadTeamDetails = () => {
  // Additional team details would be loaded here
  console.log('Loading details for team:', form.selectedTeam)
}

const loadCompetitions = async () => {
  if (!form.competitionType || !form.federation) return

  try {
    // Mock data based on selections
    const mockCompetitions = [
      {
        id: 1,
        name: 'Deutsche Meisterschaft',
        season: '2024/25',
        currentTeams: 24,
        maxTeams: 32,
        registrationOpen: true,
        startDate: '2024-10-01',
        registrationDeadline: '2024-09-15',
        registrationFee: '€1000'
      },
      {
        id: 2,
        name: 'Europa Cup',
        season: '2024/25',
        currentTeams: 16,
        maxTeams: 16,
        registrationOpen: false,
        startDate: '2024-11-01',
        registrationDeadline: '2024-08-31',
        registrationFee: '€2500'
      }
    ]

    // Filter based on type and federation
    availableCompetitions.value = mockCompetitions.filter(comp => {
      if (form.competitionType === 'tournament') return comp.name.includes('Cup') || comp.name.includes('Meisterschaft')
      if (form.competitionType === 'league') return comp.name.includes('Liga')
      return true
    })

  } catch (error) {
    console.error('Failed to load competitions:', error)
  }
}

const selectCompetition = (competition) => {
  form.selectedCompetition = competition
}

const nextStep = () => {
  if (canProceed.value && currentStep.value < 4) {
    currentStep.value++
  }
}

const previousStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--
  }
}

const submitRegistration = async () => {
  form.processing = true

  try {
    // API call to submit registration
    // await axios.post('/federation/team/register-competition', {
    //   team_id: form.selectedTeam.id,
    //   competition_id: form.selectedCompetition.id,
    //   contact_person: form.contactPerson,
    //   contact_email: form.contactEmail,
    //   contact_phone: form.contactPhone,
    //   expected_players: form.expectedPlayers,
    //   additional_notes: form.additionalNotes
    // })

    // Mock successful registration
    await new Promise(resolve => setTimeout(resolve, 2000))

    emit('teamRegistered', {
      team: form.selectedTeam,
      competition: form.selectedCompetition,
      contactPerson: form.contactPerson
    })

    close()

  } catch (error) {
    console.error('Registration failed:', error)
    form.setError('general', 'Registrierung fehlgeschlagen. Bitte versuchen Sie es erneut.')
  } finally {
    form.processing = false
  }
}

const close = () => {
  currentStep.value = 1
  form.reset()
  form.clearErrors()
  availableCompetitions.value = []
  emit('close')
}

// Utility functions
const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('de-DE')
}
</script>