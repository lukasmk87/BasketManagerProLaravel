<template>
    <div class="gym-calendar overflow-hidden">
        <!-- View Mode Controls -->
        <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between bg-white rounded-lg border p-4">
            <div>
                <h3 class="text-lg font-medium text-gray-900">Hallenverwaltung</h3>
                <p class="mt-1 text-sm text-gray-500">
                    {{ viewMode === 'week' ? 'Wochenansicht' : 'Court Grid-Ansicht' }} • 
                    {{ gymHalls.length }} {{ gymHalls.length === 1 ? 'Halle' : 'Hallen' }}
                </p>
            </div>
            <div class="mt-4 sm:mt-0 flex items-center space-x-3">
                <!-- Hall Selector for Grid View -->
                <div v-if="viewMode === 'grid' && gymHalls.length > 1" class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-700">Halle:</label>
                    <select 
                        v-model="selectedHallId" 
                        class="rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                        @change="$emit('hall-changed', selectedHallId)"
                    >
                        <option v-for="hall in gymHalls" :key="hall.id" :value="hall.id">
                            {{ hall.name }}
                        </option>
                    </select>
                </div>

                <!-- Date Selector for Grid View -->
                <div v-if="viewMode === 'grid'" class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-700">Datum:</label>
                    <input 
                        type="date" 
                        v-model="selectedDate"
                        class="rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                        @change="$emit('date-changed', selectedDate)"
                    />
                </div>

                <!-- View Mode Toggle -->
                <div class="flex rounded-md shadow-sm">
                    <button
                        @click="setViewMode('week')"
                        :class="[
                            'px-4 py-2 text-sm font-medium rounded-l-md border',
                            viewMode === 'week' 
                                ? 'bg-blue-600 text-white border-blue-600' 
                                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                        ]"
                    >
                        <CalendarIcon class="h-4 w-4 mr-2 inline" />
                        Woche
                    </button>
                    <button
                        @click="setViewMode('grid')"
                        :class="[
                            'px-4 py-2 text-sm font-medium rounded-r-md border-t border-r border-b',
                            viewMode === 'grid' 
                                ? 'bg-blue-600 text-white border-blue-600' 
                                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                        ]"
                    >
                        <TableCellsIcon class="h-4 w-4 mr-2 inline" />
                        Court Grid
                    </button>
                </div>
            </div>
        </div>

        <!-- Week View -->
        <div v-if="viewMode === 'week'" class="week-view">
            <!-- Calendar Header -->
            <div class="grid grid-cols-8 bg-gray-50 border-b">
                <div class="p-3 text-xs font-medium text-gray-500 uppercase tracking-wide">
                    Halle
                </div>
                <div
                    v-for="day in weekDays"
                    :key="day.key"
                    class="p-3 text-xs font-medium text-gray-500 uppercase tracking-wide text-center"
                    :class="{ 'bg-blue-50': day.isToday }"
                >
                    <div>{{ day.name }}</div>
                    <div class="mt-1 text-sm font-bold text-gray-900">
                        {{ day.date }}
                    </div>
                </div>
            </div>

            <!-- Calendar Body -->
            <div class="divide-y divide-gray-200">
                <div
                    v-for="gymHall in gymHalls"
                    :key="gymHall.id"
                    class="grid grid-cols-8 min-h-32"
                >
                    <!-- Gym Hall Info -->
                    <div class="p-4 bg-gray-50 border-r flex flex-col justify-center">
                        <h4 class="font-medium text-gray-900 text-sm truncate">
                            {{ gymHall.name }}
                        </h4>
                        <p class="text-xs text-gray-500 mt-1">
                            Kapazität: {{ gymHall.capacity || 'N/A' }}
                        </p>
                        <div class="mt-2 flex flex-wrap gap-1">
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                :class="gymHall.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                            >
                                {{ gymHall.is_active ? 'Aktiv' : 'Inaktiv' }}
                            </span>
                            <span 
                                v-if="gymHall.hall_type && gymHall.hall_type !== 'single'"
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                            >
                                {{ getHallTypeLabel(gymHall.hall_type) }}
                            </span>
                            <span 
                                v-if="gymHall.court_count > 1"
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800"
                            >
                                {{ gymHall.court_count }} Courts
                            </span>
                        </div>
                    </div>

                <!-- Daily Time Slots -->
                <div
                    v-for="day in weekDays"
                    :key="`${gymHall.id}-${day.key}`"
                    class="p-2 border-r relative min-h-32"
                    :class="{
                        'bg-blue-50': day.isToday,
                        'bg-gray-100': day.isPast
                    }"
                >
                    <div class="space-y-1">
                        <div
                            v-for="booking in getDayBookings(gymHall.id, day.date)"
                            :key="booking.id"
                            class="booking-slot cursor-pointer rounded p-2 text-xs transition-all duration-200 hover:shadow-md"
                            :class="getBookingClasses(booking)"
                            @click="$emit('booking-clicked', booking)"
                        >
                            <div class="font-medium truncate">
                                {{ booking.team?.short_name || booking.team?.name || 'Team' }}
                            </div>
                            <div class="text-xs opacity-75">
                                {{ formatTime(booking.start_time) }} - {{ formatTime(booking.end_time) }}
                            </div>
                            <div v-if="booking.status === 'released'" class="flex items-center mt-1">
                                <ExclamationTriangleIcon class="h-3 w-3 mr-1" />
                                <span class="text-xs">Freigegeben</span>
                            </div>
                            <div v-if="booking.requests_count > 0" class="flex items-center mt-1">
                                <UserGroupIcon class="h-3 w-3 mr-1" />
                                <span class="text-xs">{{ booking.requests_count }} Anfragen</span>
                            </div>
                        </div>

                        <!-- Empty time slots (clickable for admins) -->
                        <div
                            v-if="canManageTimeSlots && !day.isPast"
                            class="empty-slot border-2 border-dashed border-gray-300 rounded p-2 text-center cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-colors duration-200"
                            @click="$emit('time-slot-clicked', null, gymHall)"
                        >
                            <PlusIcon class="h-4 w-4 mx-auto text-gray-400" />
                            <span class="text-xs text-gray-500 mt-1 block">Zeit hinzufügen</span>
                        </div>
                    </div>

                    <!-- Available Time Indicator -->
                    <div
                        v-if="hasAvailableSlots(gymHall.id, day.date)"
                        class="absolute top-1 right-1 w-2 h-2 bg-green-400 rounded-full"
                        title="Verfügbare Zeiten"
                    ></div>
                </div>
                </div>
            </div>
        </div>

        <!-- Grid View -->
        <div v-else-if="viewMode === 'grid'" class="grid-view">
            <GymCourtGridView
                v-if="selectedGymHall"
                :gym-hall="selectedGymHall"
                :selected-date="selectedDate"
                :courts="selectedGymHallCourts"
                :bookings="selectedGymHallBookings"
                :can-create-bookings="canManageTimeSlots"
                :time-increment="30"
                view-mode="grid"
                @booking-clicked="$emit('booking-clicked', $event)"
                @cell-clicked="handleGridCellClick"
                @view-changed="setViewMode"
                @court-selected="$emit('court-selected', $event)"
            />
            
            <!-- No Hall Selected -->
            <div v-else class="text-center py-12">
                <TableCellsIcon class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-medium text-gray-900">Keine Halle ausgewählt</h3>
                <p class="mt-1 text-sm text-gray-500">Wählen Sie eine Halle aus, um die Court-Ansicht zu sehen.</p>
            </div>
        </div>

        <!-- Legend -->
        <div class="bg-gray-50 px-4 py-3 border-t">
            <div class="flex flex-wrap gap-4 text-xs">
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-blue-500 rounded mr-2"></div>
                    <span>Reserviert</span>
                </div>
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-yellow-500 rounded mr-2"></div>
                    <span>Freigegeben</span>
                </div>
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-green-500 rounded mr-2"></div>
                    <span>Bestätigt</span>
                </div>
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-purple-500 rounded mr-2"></div>
                    <span>Angefragt</span>
                </div>
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-red-500 rounded mr-2"></div>
                    <span>Storniert</span>
                </div>
                <div class="flex items-center">
                    <div class="w-2 h-2 bg-green-400 rounded-full mr-2"></div>
                    <span>Verfügbare Zeit</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { 
    PlusIcon, 
    ExclamationTriangleIcon,
    UserGroupIcon,
    CalendarIcon,
    TableCellsIcon
} from '@heroicons/vue/24/outline'
import GymCourtGridView from './GymCourtGridView.vue'
import { useGymCourts } from '@/Composables/useGymCourts'
import { format } from 'date-fns'

const props = defineProps({
    gymHalls: {
        type: Array,
        default: () => []
    },
    currentWeek: {
        type: String,
        required: true
    },
    bookings: {
        type: Object,
        default: () => ({})
    },
    canManageTimeSlots: {
        type: Boolean,
        default: false
    },
    initialViewMode: {
        type: String,
        default: 'week',
        validator: (value) => ['week', 'grid'].includes(value)
    },
    courts: {
        type: Object,
        default: () => ({})
    }
})

const emit = defineEmits([
    'booking-clicked', 
    'time-slot-clicked', 
    'view-mode-changed',
    'hall-changed',
    'date-changed',
    'court-selected',
    'grid-cell-clicked'
])

// Composables
const { fetchCourts } = useGymCourts()

// Reactive state
const viewMode = ref(props.initialViewMode)
const selectedHallId = ref(props.gymHalls.length > 0 ? props.gymHalls[0].id : null)
const selectedDate = ref(format(new Date(), 'yyyy-MM-dd'))
const hallCourts = ref({}) // Store courts per hall

const weekDays = computed(() => {
    const start = new Date(props.currentWeek)
    const days = []
    const today = new Date()
    today.setHours(0, 0, 0, 0)
    
    for (let i = 0; i < 7; i++) {
        const date = new Date(start)
        date.setDate(start.getDate() + i)
        
        days.push({
            key: `day-${i}`,
            name: date.toLocaleDateString('de-DE', { weekday: 'short' }),
            date: date.getDate(),
            fullDate: date.toISOString().split('T')[0],
            isToday: date.getTime() === today.getTime(),
            isPast: date < today
        })
    }
    
    return days
})

// Grid View computed properties
const selectedGymHall = computed(() => {
    return props.gymHalls.find(hall => hall.id === selectedHallId.value)
})

const selectedGymHallCourts = computed(() => {
    return hallCourts.value[selectedHallId.value] || props.courts[selectedHallId.value] || []
})

const selectedGymHallBookings = computed(() => {
    if (!selectedGymHall.value || !props.bookings[selectedHallId.value]) {
        return []
    }
    return props.bookings[selectedHallId.value][selectedDate.value] || []
})

// Methods
const setViewMode = (mode) => {
    viewMode.value = mode
    emit('view-mode-changed', mode)
}

const getHallTypeLabel = (hallType) => {
    const labels = {
        'single': 'Einfach',
        'double': 'Doppel',
        'triple': 'Dreifach',
        'multi': 'Multi'
    }
    return labels[hallType] || hallType
}

const handleGridCellClick = (cellData) => {
    emit('grid-cell-clicked', {
        ...cellData,
        gymHall: selectedGymHall.value
    })
}

const loadCourtsForHall = async (hallId) => {
    if (!hallId || hallCourts.value[hallId]) {
        return // Already loaded or no hall
    }

    try {
        const courts = await fetchCourts(hallId)
        hallCourts.value[hallId] = courts
    } catch (error) {
        console.error('Error loading courts for hall:', error)
        hallCourts.value[hallId] = []
    }
}

const getDayBookings = (gymHallId, dayNumber) => {
    const dateStr = weekDays.value.find(d => d.date === dayNumber)?.fullDate
    if (!dateStr || !props.bookings[gymHallId]) {
        return []
    }
    
    return props.bookings[gymHallId][dateStr] || []
}

const hasAvailableSlots = (gymHallId, dayNumber) => {
    const bookings = getDayBookings(gymHallId, dayNumber)
    const dayData = weekDays.value.find(d => d.date === dayNumber)
    
    if (!dayData || dayData.isPast) return false
    
    // Simple heuristic: if less than 8 bookings, there might be available slots
    return bookings.length < 8
}

const getBookingClasses = (booking) => {
    const baseClasses = 'border-l-4'
    
    switch (booking.status) {
        case 'reserved':
            return `${baseClasses} bg-blue-100 border-blue-500 text-blue-900 hover:bg-blue-200`
        case 'released':
            return `${baseClasses} bg-yellow-100 border-yellow-500 text-yellow-900 hover:bg-yellow-200`
        case 'confirmed':
            return `${baseClasses} bg-green-100 border-green-500 text-green-900 hover:bg-green-200`
        case 'requested':
            return `${baseClasses} bg-purple-100 border-purple-500 text-purple-900 hover:bg-purple-200`
        case 'cancelled':
            return `${baseClasses} bg-red-100 border-red-500 text-red-900 hover:bg-red-200 opacity-75`
        case 'completed':
            return `${baseClasses} bg-gray-100 border-gray-500 text-gray-900 hover:bg-gray-200 opacity-75`
        default:
            return `${baseClasses} bg-gray-100 border-gray-500 text-gray-900 hover:bg-gray-200`
    }
}

const formatTime = (timeString) => {
    if (!timeString) return ''
    
    try {
        const time = new Date(`2000-01-01T${timeString}`)
        return time.toLocaleTimeString('de-DE', { 
            hour: '2-digit', 
            minute: '2-digit',
            hour12: false
        })
    } catch (error) {
        return timeString
    }
}

// Watchers
watch(() => selectedHallId.value, (newHallId) => {
    if (newHallId && viewMode.value === 'grid') {
        loadCourtsForHall(newHallId)
    }
})

watch(() => viewMode.value, (newMode) => {
    if (newMode === 'grid' && selectedHallId.value) {
        loadCourtsForHall(selectedHallId.value)
    }
})

watch(() => props.gymHalls, (newHalls) => {
    if (newHalls.length > 0 && !selectedHallId.value) {
        selectedHallId.value = newHalls[0].id
    }
}, { immediate: true })

// Lifecycle
onMounted(() => {
    if (viewMode.value === 'grid' && selectedHallId.value) {
        loadCourtsForHall(selectedHallId.value)
    }
})
</script>

<style scoped>
.gym-calendar {
    @apply border border-gray-200 rounded-lg;
}

.booking-slot {
    min-height: 3rem;
}

.empty-slot {
    min-height: 3rem;
}

/* Mobile responsive adjustments */
@media (max-width: 768px) {
    .gym-calendar {
        /* Stack vertically on mobile */
        display: block;
    }
    
    .grid {
        display: block;
    }
    
    .grid > div {
        border-bottom: 1px solid #e5e7eb;
        padding: 0.75rem;
    }
}

/* Smooth animations */
.booking-slot, .empty-slot {
    transition: all 0.2s ease-in-out;
}

.booking-slot:hover {
    transform: translateY(-1px);
}

/* Print styles */
@media print {
    .empty-slot {
        display: none;
    }
    
    .gym-calendar {
        border: 1px solid #000;
    }
    
    .booking-slot {
        break-inside: avoid;
    }
}
</style>