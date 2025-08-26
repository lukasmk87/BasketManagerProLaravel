<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="close"></div>

      <!-- Modal content -->
      <div class="inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
        <div>
          <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
            <CheckIcon class="h-6 w-6 text-green-600" />
          </div>
          
          <div class="mt-3 text-center sm:mt-5">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
              Training-Anmeldung
            </h3>
            <div class="mt-2">
              <p class="text-sm text-gray-500">
                {{ session.title }}
              </p>
              <p class="text-xs text-gray-400 mt-1">
                {{ formatDateTime(session.scheduled_at) }}
              </p>
            </div>
          </div>
        </div>

        <!-- Registration Form -->
        <form @submit.prevent="submitRegistration" class="mt-5">
          <div class="space-y-4">
            <!-- Notes field -->
            <div>
              <label for="notes" class="block text-sm font-medium text-gray-700">
                Notizen (optional)
              </label>
              <textarea
                id="notes"
                v-model="form.notes"
                rows="3"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                placeholder="Besondere Hinweise oder Anmerkungen..."
                maxlength="500"
              ></textarea>
              <p class="text-xs text-gray-500 mt-1">{{ form.notes?.length || 0 }}/500 Zeichen</p>
            </div>

            <!-- Deadline warning if close -->
            <div v-if="isCloseToDeadline" class="bg-orange-50 border border-orange-200 rounded-md p-3">
              <div class="flex">
                <ExclamationTriangleIcon class="h-5 w-5 text-orange-400 mr-2" />
                <div class="text-sm">
                  <p class="text-orange-800 font-medium">Anmeldefrist läuft bald ab!</p>
                  <p class="text-orange-700 mt-1">
                    Nur noch {{ timeUntilDeadline }} bis zur Anmeldefrist.
                  </p>
                </div>
              </div>
            </div>

            <!-- Waitlist warning if no capacity -->
            <div v-if="!hasCapacity && enableWaitlist" class="bg-blue-50 border border-blue-200 rounded-md p-3">
              <div class="flex">
                <InformationCircleIcon class="h-5 w-5 text-blue-400 mr-2" />
                <div class="text-sm">
                  <p class="text-blue-800 font-medium">Warteliste</p>
                  <p class="text-blue-700 mt-1">
                    Das Training ist bereits ausgebucht. Sie werden zur Warteliste hinzugefügt.
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Error message -->
          <div v-if="errorMessage" class="mt-4 bg-red-50 border border-red-200 rounded-md p-3">
            <div class="flex">
              <XCircleIcon class="h-5 w-5 text-red-400 mr-2" />
              <div class="text-sm">
                <p class="text-red-800">{{ errorMessage }}</p>
              </div>
            </div>
          </div>

          <!-- Action buttons -->
          <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
            <button
              type="submit"
              :disabled="isLoading || !canSubmit"
              class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:col-start-2 sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="isLoading">Anmelden...</span>
              <span v-else-if="!hasCapacity && enableWaitlist">Auf Warteliste setzen</span>
              <span v-else>Anmelden</span>
            </button>
            
            <button
              type="button"
              @click="close"
              :disabled="isLoading"
              class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:mt-0 sm:col-start-1 sm:text-sm disabled:opacity-50"
            >
              Abbrechen
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { 
  CheckIcon, 
  ExclamationTriangleIcon, 
  InformationCircleIcon,
  XCircleIcon 
} from '@heroicons/vue/24/outline'

const props = defineProps({
  session: {
    type: Object,
    required: true
  },
  playerId: {
    type: Number,
    required: true
  }
})

const emit = defineEmits(['close', 'registered'])

const isLoading = ref(false)
const errorMessage = ref('')

const form = useForm({
  player_id: props.playerId,
  notes: ''
})

// Computed properties
const hasCapacity = computed(() => {
  return props.session.registration_summary?.has_capacity ?? true
})

const enableWaitlist = computed(() => {
  return props.session.registration_summary?.enable_waitlist ?? true
})

const hoursUntilDeadline = computed(() => {
  return props.session.registration_summary?.hours_until_deadline ?? 24
})

const isCloseToDeadline = computed(() => {
  return hoursUntilDeadline.value < 24 && hoursUntilDeadline.value > 0
})

const timeUntilDeadline = computed(() => {
  const hours = hoursUntilDeadline.value
  if (hours < 1) {
    const minutes = Math.round(hours * 60)
    return `${minutes} Minuten`
  }
  if (hours < 24) {
    return `${Math.round(hours)} Stunden`
  }
  const days = Math.round(hours / 24)
  return `${days} Tag${days > 1 ? 'e' : ''}`
})

const canSubmit = computed(() => {
  return !isLoading.value && (hasCapacity.value || enableWaitlist.value)
})

// Methods
const submitRegistration = async () => {
  errorMessage.value = ''
  isLoading.value = true

  try {
    const response = await axios.post(`/api/training-sessions/${props.session.id}/register`, {
      player_id: form.player_id,
      notes: form.notes
    })

    emit('registered', response.data.data)
    close()
  } catch (error) {
    if (error.response?.data?.error) {
      errorMessage.value = error.response.data.error
    } else {
      errorMessage.value = 'Ein unerwarteter Fehler ist aufgetreten. Bitte versuchen Sie es erneut.'
    }
  } finally {
    isLoading.value = false
  }
}

const close = () => {
  emit('close')
}

const formatDateTime = (dateTimeString) => {
  const date = new Date(dateTimeString)
  return date.toLocaleString('de-DE', {
    weekday: 'short',
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>