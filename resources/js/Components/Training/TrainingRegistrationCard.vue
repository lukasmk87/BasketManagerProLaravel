<template>
  <div class="bg-white rounded-lg shadow-md p-6 mb-4">
    <!-- Training Session Header -->
    <div class="flex justify-between items-start mb-4">
      <div>
        <h3 class="text-lg font-semibold text-gray-900">{{ session.title }}</h3>
        <p class="text-sm text-gray-600">{{ formatDateTime(session.scheduled_at) }}</p>
        <div class="flex items-center mt-1">
          <MapPinIcon class="h-4 w-4 text-gray-400 mr-1" />
          <span class="text-sm text-gray-500">{{ session.venue || 'Venue TBD' }}</span>
        </div>
      </div>
      <div class="text-right">
        <div class="flex items-center mb-1">
          <ClockIcon class="h-4 w-4 text-gray-400 mr-1" />
          <span class="text-sm text-gray-500">{{ session.planned_duration }}min</span>
        </div>
        <StatusBadge :status="session.status" />
      </div>
    </div>

    <!-- Registration Deadline Info -->
    <div class="bg-blue-50 rounded-lg p-4 mb-4">
      <div class="flex items-center justify-between">
        <div>
          <h4 class="font-medium text-blue-900">Anmeldefrist</h4>
          <p class="text-sm text-blue-700">{{ formatDateTime(registrationSummary.registration_deadline) }}</p>
        </div>
        <div class="text-right">
          <span class="text-sm font-medium" :class="deadlineStatusClass">
            {{ deadlineText }}
          </span>
          <p class="text-xs text-blue-600 mt-1">
            {{ registrationSummary.available_spots }} verfügbare Plätze
          </p>
        </div>
      </div>
    </div>

    <!-- Registration Status -->
    <div v-if="playerRegistration" class="mb-4">
      <div class="flex items-center justify-between p-3 rounded-lg" :class="registrationStatusClass">
        <div class="flex items-center">
          <CheckCircleIcon v-if="playerRegistration.status === 'confirmed'" class="h-5 w-5 text-green-600 mr-2" />
          <ClockIcon v-else-if="playerRegistration.status === 'registered'" class="h-5 w-5 text-yellow-600 mr-2" />
          <XCircleIcon v-else-if="playerRegistration.status === 'cancelled'" class="h-5 w-5 text-red-600 mr-2" />
          <QueueListIcon v-else-if="playerRegistration.status === 'waitlist'" class="h-5 w-5 text-blue-600 mr-2" />
          <span class="font-medium">{{ registrationStatusText }}</span>
        </div>
        <div v-if="playerRegistration.is_late" class="flex items-center text-orange-600">
          <ExclamationTriangleIcon class="h-4 w-4 mr-1" />
          <span class="text-sm">Verspätete Anmeldung</span>
        </div>
      </div>
      
      <!-- Registration Notes -->
      <div v-if="playerRegistration.registration_notes" class="mt-2 p-2 bg-gray-50 rounded text-sm text-gray-700">
        <strong>Notizen:</strong> {{ playerRegistration.registration_notes }}
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex justify-between items-center">
      <div class="text-sm text-gray-500">
        <span>{{ registrationSummary.total_registrations }} angemeldet</span>
        <span v-if="registrationSummary.waitlist_count > 0" class="ml-2">
          ({{ registrationSummary.waitlist_count }} auf Warteliste)
        </span>
      </div>
      
      <div class="flex space-x-2">
        <!-- Register Button -->
        <button
          v-if="canRegister"
          @click="handleRegister"
          :disabled="isLoading"
          class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span v-if="isLoading">Anmelden...</span>
          <span v-else-if="!registrationSummary.has_capacity && registrationSummary.enable_waitlist">
            Auf Warteliste setzen
          </span>
          <span v-else>Anmelden</span>
        </button>

        <!-- Cancel Registration Button -->
        <button
          v-if="canCancelRegistration"
          @click="handleCancelRegistration"
          :disabled="isLoading"
          class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span v-if="isLoading">Abmelden...</span>
          <span v-else>Abmelden</span>
        </button>

        <!-- View Details Button -->
        <button
          @click="$emit('view-details', session.id)"
          class="px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
        >
          Details
        </button>
      </div>
    </div>

    <!-- Registration Modal -->
    <RegistrationModal
      v-if="showRegistrationModal"
      :session="session"
      :player-id="playerId"
      @close="showRegistrationModal = false"
      @registered="handleRegistrationSuccess"
    />

    <!-- Cancel Registration Modal -->
    <CancelRegistrationModal
      v-if="showCancelModal"
      :session="session"
      :registration="playerRegistration"
      :player-id="playerId"
      @close="showCancelModal = false"
      @cancelled="handleCancellationSuccess"
    />
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { 
  ClockIcon, 
  MapPinIcon, 
  CheckCircleIcon, 
  XCircleIcon, 
  QueueListIcon,
  ExclamationTriangleIcon 
} from '@heroicons/vue/24/outline'
import StatusBadge from '@/Components/Common/StatusBadge.vue'
import RegistrationModal from './RegistrationModal.vue'
import CancelRegistrationModal from './CancelRegistrationModal.vue'

const props = defineProps({
  session: {
    type: Object,
    required: true
  },
  playerRegistration: {
    type: Object,
    default: null
  },
  registrationSummary: {
    type: Object,
    required: true
  },
  playerId: {
    type: Number,
    required: true
  }
})

const emit = defineEmits(['view-details', 'registration-updated'])

const isLoading = ref(false)
const showRegistrationModal = ref(false)
const showCancelModal = ref(false)

// Computed properties
const canRegister = computed(() => {
  return !props.playerRegistration &&
         props.registrationSummary.registration_open &&
         (props.registrationSummary.has_capacity || props.registrationSummary.enable_waitlist)
})

const canCancelRegistration = computed(() => {
  return props.playerRegistration &&
         ['registered', 'confirmed', 'waitlist'].includes(props.playerRegistration.status) &&
         props.playerRegistration.can_cancel
})

const deadlineStatusClass = computed(() => {
  const hoursUntilDeadline = props.registrationSummary.hours_until_deadline
  if (hoursUntilDeadline < 0) return 'text-red-600'
  if (hoursUntilDeadline < 24) return 'text-orange-600'
  return 'text-green-600'
})

const deadlineText = computed(() => {
  const hoursUntilDeadline = props.registrationSummary.hours_until_deadline
  if (hoursUntilDeadline < 0) {
    return 'Anmeldefrist abgelaufen'
  }
  if (hoursUntilDeadline < 1) {
    const minutes = Math.round(hoursUntilDeadline * 60)
    return `${minutes} Min. verbleibend`
  }
  if (hoursUntilDeadline < 24) {
    return `${Math.round(hoursUntilDeadline)} Std. verbleibend`
  }
  const days = Math.round(hoursUntilDeadline / 24)
  return `${days} Tag${days > 1 ? 'e' : ''} verbleibend`
})

const registrationStatusText = computed(() => {
  if (!props.playerRegistration) return ''
  
  const statusTexts = {
    registered: 'Angemeldet (wartend auf Bestätigung)',
    confirmed: 'Bestätigt',
    cancelled: 'Abgemeldet',
    waitlist: 'Auf Warteliste',
    declined: 'Abgelehnt'
  }
  
  return statusTexts[props.playerRegistration.status] || props.playerRegistration.status
})

const registrationStatusClass = computed(() => {
  if (!props.playerRegistration) return ''
  
  const statusClasses = {
    registered: 'bg-yellow-50 border border-yellow-200',
    confirmed: 'bg-green-50 border border-green-200',
    cancelled: 'bg-red-50 border border-red-200',
    waitlist: 'bg-blue-50 border border-blue-200',
    declined: 'bg-gray-50 border border-gray-200'
  }
  
  return statusClasses[props.playerRegistration.status] || 'bg-gray-50 border border-gray-200'
})

// Methods
const handleRegister = () => {
  showRegistrationModal.value = true
}

const handleCancelRegistration = () => {
  showCancelModal.value = true
}

const handleRegistrationSuccess = (newRegistration) => {
  emit('registration-updated', {
    sessionId: props.session.id,
    registration: newRegistration
  })
}

const handleCancellationSuccess = () => {
  emit('registration-updated', {
    sessionId: props.session.id,
    registration: null
  })
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