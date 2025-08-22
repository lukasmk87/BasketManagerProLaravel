<template>
    <div class="gym-court-grid-view">
        <!-- Header with Controls -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-medium text-gray-900">{{ gymHall.name }} - Court Grid</h3>
                <p class="mt-1 text-sm text-gray-500">
                    {{ formatDate(selectedDate) }} • {{ courts.length }} Courts • {{ timeSlots.length }} Zeitslots
                </p>
            </div>
            <div class="mt-4 sm:mt-0 flex items-center space-x-4">
                <!-- Time Increment Selector -->
                <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-700">Raster:</label>
                    <select 
                        v-model="selectedIncrement" 
                        class="rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                        @change="updateTimeGrid"
                    >
                        <option :value="15">15 Min</option>
                        <option :value="30">30 Min</option>
                        <option :value="60">60 Min</option>
                    </select>
                </div>
                
                <!-- View Controls -->
                <div class="flex rounded-md shadow-sm">
                    <button
                        @click="$emit('view-changed', 'grid')"
                        :class="[
                            'px-3 py-2 text-sm font-medium rounded-l-md border',
                            viewMode === 'grid' 
                                ? 'bg-blue-600 text-white border-blue-600' 
                                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                        ]"
                    >
                        <TableCellsIcon class="h-4 w-4" />
                    </button>
                    <button
                        @click="$emit('view-changed', 'calendar')"
                        :class="[
                            'px-3 py-2 text-sm font-medium rounded-r-md border-t border-r border-b',
                            viewMode === 'calendar' 
                                ? 'bg-blue-600 text-white border-blue-600' 
                                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                        ]"
                    >
                        <CalendarDaysIcon class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center h-64">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-md p-4">
            <div class="flex">
                <ExclamationTriangleIcon class="h-5 w-5 text-red-400" />
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Fehler beim Laden</h3>
                    <p class="text-sm text-red-700 mt-1">{{ error }}</p>
                </div>
            </div>
        </div>

        <!-- Court Grid -->
        <div v-else class="court-grid-container overflow-auto border border-gray-200 rounded-lg">
            <div class="inline-block min-w-full">
                <!-- Grid Header -->
                <div class="grid grid-cols-[80px_repeat(var(--court-count),_minmax(140px,_1fr))] bg-gray-50 border-b">
                    <!-- Time Column Header -->
                    <div class="p-3 text-xs font-medium text-gray-500 uppercase tracking-wide text-center border-r">
                        Zeit
                    </div>
                    
                    <!-- Court Headers -->
                    <div
                        v-for="court in courts"
                        :key="court.id"
                        class="p-3 text-center border-r border-gray-200 min-w-[140px]"
                    >
                        <div class="flex items-center justify-center space-x-2">
                            <div 
                                class="w-3 h-3 rounded-full"
                                :style="{ backgroundColor: court.color_code }"
                            ></div>
                            <div>
                                <div class="text-xs font-medium text-gray-700">{{ court.court_identifier }}</div>
                                <div class="text-xs text-gray-500 truncate">{{ court.court_name }}</div>
                            </div>
                        </div>
                        <div v-if="court.max_capacity" class="text-xs text-gray-400 mt-1">
                            Max. {{ court.max_capacity }}
                        </div>
                    </div>
                </div>

                <!-- Grid Body -->
                <div class="grid-body">
                    <div
                        v-for="timeSlot in timeSlots"
                        :key="timeSlot.time_key"
                        class="grid grid-cols-[80px_repeat(var(--court-count),_minmax(140px,_1fr))] border-b border-gray-100 hover:bg-gray-25"
                        :class="{ 
                            'bg-blue-25': timeSlot.is_current_hour,
                            'bg-gray-50': timeSlot.is_past 
                        }"
                    >
                        <!-- Time Column -->
                        <div class="p-2 text-xs text-gray-600 text-center border-r border-gray-200 font-mono">
                            <div class="font-medium">{{ timeSlot.start_time }}</div>
                            <div class="text-gray-400">{{ timeSlot.end_time }}</div>
                        </div>

                        <!-- Court Cells -->
                        <div
                            v-for="court in courts"
                            :key="`${timeSlot.time_key}-${court.id}`"
                            class="relative p-1 border-r border-gray-200 min-h-[60px] court-cell"
                            :class="getCourtCellClasses(timeSlot, court)"
                            @click="handleCellClick(timeSlot, court)"
                            @mouseenter="handleCellHover(timeSlot, court, true)"
                            @mouseleave="handleCellHover(timeSlot, court, false)"
                        >
                            <!-- Booking Display -->
                            <div 
                                v-if="getCellBooking(timeSlot, court)"
                                class="booking-cell h-full w-full rounded px-2 py-1 text-xs cursor-pointer"
                                :class="getBookingClasses(getCellBooking(timeSlot, court))"
                                :style="{ backgroundColor: court.color_code + '20', borderColor: court.color_code }"
                                @click.stop="$emit('booking-clicked', getCellBooking(timeSlot, court))"
                            >
                                <div class="font-medium truncate">
                                    {{ getCellBooking(timeSlot, court).team?.short_name || getCellBooking(timeSlot, court).team?.name }}
                                </div>
                                <div class="text-gray-600 truncate">
                                    {{ getCellBooking(timeSlot, court).start_time }} - {{ getCellBooking(timeSlot, court).end_time }}
                                </div>
                                <div v-if="getCellBooking(timeSlot, court).status === 'released'" class="flex items-center mt-1">
                                    <ExclamationTriangleIcon class="h-3 w-3 mr-1 text-yellow-600" />
                                    <span class="text-yellow-700">Freigegeben</span>
                                </div>
                            </div>

                            <!-- Empty Cell (Available for booking) -->
                            <div 
                                v-else-if="isCellAvailable(timeSlot, court) && canCreateBookings && !timeSlot.is_past"
                                class="empty-cell h-full w-full rounded border-2 border-dashed border-gray-300 flex items-center justify-center cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-colors"
                                :class="{ 'border-blue-400 bg-blue-50': isHovered(timeSlot, court) }"
                            >
                                <PlusIcon class="h-4 w-4 text-gray-400" />
                            </div>

                            <!-- Unavailable Cell -->
                            <div 
                                v-else-if="!isCellAvailable(timeSlot, court)"
                                class="unavailable-cell h-full w-full rounded bg-gray-100 flex items-center justify-center"
                            >
                                <XMarkIcon class="h-3 w-3 text-gray-400" />
                            </div>

                            <!-- Hover Tooltip -->
                            <div 
                                v-if="hoveredCell?.timeSlot?.time_key === timeSlot.time_key && hoveredCell?.court?.id === court.id"
                                class="absolute z-10 bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 text-xs text-white bg-gray-900 rounded shadow-lg whitespace-nowrap"
                            >
                                {{ court.court_name }} • {{ timeSlot.start_time }}-{{ timeSlot.end_time }}
                                <div v-if="getCellBooking(timeSlot, court)" class="text-gray-300">
                                    {{ getCellBooking(timeSlot, court).team?.name }}
                                </div>
                                <div v-else-if="isCellAvailable(timeSlot, court)" class="text-green-300">
                                    Verfügbar
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Legend -->
        <div class="mt-4 flex flex-wrap items-center justify-between">
            <div class="flex flex-wrap items-center space-x-6 text-xs">
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 rounded bg-green-200 border border-green-400"></div>
                    <span class="text-gray-600">Verfügbar</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 rounded bg-blue-200 border border-blue-400"></div>
                    <span class="text-gray-600">Gebucht</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 rounded bg-yellow-200 border border-yellow-400"></div>
                    <span class="text-gray-600">Freigegeben</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 rounded bg-gray-200 border border-gray-400"></div>
                    <span class="text-gray-600">Nicht verfügbar</span>
                </div>
            </div>
            
            <div v-if="courts.length > 0" class="text-xs text-gray-500 mt-2 sm:mt-0">
                Auslastung: {{ getUtilizationRate() }}%
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { 
    CalendarDaysIcon, 
    TableCellsIcon, 
    PlusIcon, 
    XMarkIcon, 
    ExclamationTriangleIcon 
} from '@heroicons/vue/24/outline'
import { format, parseISO, isToday, isBefore, startOfDay } from 'date-fns'
import { de } from 'date-fns/locale'

const props = defineProps({
    gymHall: {
        type: Object,
        required: true
    },
    selectedDate: {
        type: [String, Date],
        required: true
    },
    courts: {
        type: Array,
        default: () => []
    },
    bookings: {
        type: Array,
        default: () => []
    },
    viewMode: {
        type: String,
        default: 'grid'
    },
    canCreateBookings: {
        type: Boolean,
        default: false
    },
    timeIncrement: {
        type: Number,
        default: 30
    }
})

const emit = defineEmits([
    'booking-clicked',
    'cell-clicked', 
    'view-changed',
    'time-grid-updated',
    'court-selected'
])

// Reactive data
const loading = ref(false)
const error = ref(null)
const selectedIncrement = ref(props.timeIncrement)
const hoveredCell = ref(null)
const timeSlots = ref([])

// CSS Custom Property for dynamic grid columns
const courtGridStyle = computed(() => ({
    '--court-count': props.courts.length
}))

// Computed properties
const selectedDateObj = computed(() => {
    return typeof props.selectedDate === 'string' ? parseISO(props.selectedDate) : props.selectedDate
})

// Methods
const formatDate = (date) => {
    const dateObj = typeof date === 'string' ? parseISO(date) : date
    return format(dateObj, 'EEEE, d. MMMM yyyy', { locale: de })
}

const updateTimeGrid = async () => {
    loading.value = true
    error.value = null
    
    try {
        const response = await fetch(`/api/v2/gym-halls/${props.gymHall.id}/time-grid?date=${format(selectedDateObj.value, 'yyyy-MM-dd')}&slot_duration=${selectedIncrement.value}`)
        
        // Check HTTP status first
        if (!response.ok) {
            if (response.status === 401) {
                throw new Error('Sitzung abgelaufen. Bitte melden Sie sich erneut an.')
            } else if (response.status === 403) {
                throw new Error('Keine Berechtigung für diese Aktion.')
            } else if (response.status === 404) {
                throw new Error('Endpunkt nicht gefunden. Bitte kontaktieren Sie den Support.')
            } else if (response.status >= 500) {
                throw new Error('Serverfehler. Bitte versuchen Sie es später erneut.')
            } else {
                throw new Error(`HTTP Fehler ${response.status}`)
            }
        }
        
        const data = await response.json()
        
        if (data.success) {
            timeSlots.value = data.data.time_slots.map(slot => ({
                ...slot,
                time_key: slot.start_time.replace(':', ''),
                is_current_hour: isCurrentHour(slot.start_time),
                is_past: isPastTime(slot.start_time)
            }))
            emit('time-grid-updated', timeSlots.value)
        } else {
            throw new Error(data.message || 'Fehler beim Laden des Zeitrasters')
        }
    } catch (err) {
        error.value = err.message
        console.error('Error loading time grid:', err)
    } finally {
        loading.value = false
    }
}

const isCurrentHour = (timeString) => {
    if (!isToday(selectedDateObj.value)) return false
    
    const now = new Date()
    const [hours] = timeString.split(':').map(Number)
    return now.getHours() === hours
}

const isPastTime = (timeString) => {
    if (!isToday(selectedDateObj.value)) {
        return isBefore(selectedDateObj.value, startOfDay(new Date()))
    }
    
    const now = new Date()
    const [hours, minutes] = timeString.split(':').map(Number)
    const slotTime = new Date()
    slotTime.setHours(hours, minutes, 0, 0)
    
    return slotTime < now
}

const getCellBooking = (timeSlot, court) => {
    return props.bookings.find(booking => {
        // Check if booking overlaps with this time slot and uses this court
        const bookingStart = booking.start_time
        const bookingEnd = booking.end_time
        const slotStart = timeSlot.start_time
        const slotEnd = timeSlot.end_time
        
        const timeOverlap = bookingStart < slotEnd && bookingEnd > slotStart
        const courtMatch = !booking.court_ids || booking.court_ids.length === 0 || booking.court_ids.includes(court.id)
        
        return timeOverlap && courtMatch
    })
}

const isCellAvailable = (timeSlot, court) => {
    // Check if court is active
    if (!court.is_active) return false
    
    // Check for operating hours
    const dayOfWeek = format(selectedDateObj.value, 'EEEE').toLowerCase()
    const operatingHours = props.gymHall.operating_hours?.[dayOfWeek]
    
    if (!operatingHours?.is_open) return false
    
    // Check if time slot is within operating hours
    const slotStart = timeSlot.start_time
    const slotEnd = timeSlot.end_time
    const openTime = operatingHours.open_time
    const closeTime = operatingHours.close_time
    
    if (slotStart < openTime || slotEnd > closeTime) return false
    
    // Check for existing booking
    return !getCellBooking(timeSlot, court)
}

const getCourtCellClasses = (timeSlot, court) => {
    const booking = getCellBooking(timeSlot, court)
    const available = isCellAvailable(timeSlot, court)
    
    return {
        'cursor-pointer': available && props.canCreateBookings && !timeSlot.is_past,
        'cursor-not-allowed': !available || timeSlot.is_past,
        'hover:bg-blue-50': available && props.canCreateBookings && !timeSlot.is_past,
    }
}

const getBookingClasses = (booking) => {
    return {
        'border border-l-4': true,
        'bg-blue-100 border-blue-500': booking.status === 'confirmed',
        'bg-green-100 border-green-500': booking.status === 'reserved',
        'bg-yellow-100 border-yellow-500': booking.status === 'released',
        'bg-red-100 border-red-500': booking.status === 'cancelled',
        'bg-gray-100 border-gray-500': booking.status === 'completed'
    }
}

const handleCellClick = (timeSlot, court) => {
    const booking = getCellBooking(timeSlot, court)
    
    if (booking) {
        emit('booking-clicked', booking)
    } else if (isCellAvailable(timeSlot, court) && props.canCreateBookings && !timeSlot.is_past) {
        emit('cell-clicked', {
            timeSlot,
            court,
            date: props.selectedDate,
            startTime: timeSlot.start_time,
            endTime: timeSlot.end_time,
            duration: selectedIncrement.value
        })
    }
}

const handleCellHover = (timeSlot, court, isEntering) => {
    if (isEntering) {
        hoveredCell.value = { timeSlot, court }
    } else {
        hoveredCell.value = null
    }
}

const isHovered = (timeSlot, court) => {
    return hoveredCell.value?.timeSlot?.time_key === timeSlot.time_key && 
           hoveredCell.value?.court?.id === court.id
}

const getUtilizationRate = () => {
    if (timeSlots.value.length === 0 || props.courts.length === 0) return 0
    
    const totalSlots = timeSlots.value.length * props.courts.length
    const bookedSlots = timeSlots.value.reduce((acc, timeSlot) => {
        return acc + props.courts.reduce((courtAcc, court) => {
            return courtAcc + (getCellBooking(timeSlot, court) ? 1 : 0)
        }, 0)
    }, 0)
    
    return Math.round((bookedSlots / totalSlots) * 100)
}

// Watchers
watch(() => props.selectedDate, updateTimeGrid)
watch(() => props.gymHall.id, updateTimeGrid)
watch(() => selectedIncrement.value, updateTimeGrid)

// Lifecycle
onMounted(() => {
    updateTimeGrid()
})
</script>

<style scoped>
.court-grid-container {
    max-height: 70vh;
}

.court-cell {
    transition: all 0.2s ease;
}

.booking-cell {
    border-width: 1px;
    border-style: solid;
}

.empty-cell {
    transition: all 0.2s ease;
}

.grid-body {
    max-height: calc(70vh - 60px);
    overflow-y: auto;
}

/* Custom scrollbar */
.court-grid-container::-webkit-scrollbar,
.grid-body::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

.court-grid-container::-webkit-scrollbar-track,
.grid-body::-webkit-scrollbar-track {
    background: #f1f5f9;
}

.court-grid-container::-webkit-scrollbar-thumb,
.grid-body::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.court-grid-container::-webkit-scrollbar-thumb:hover,
.grid-body::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Color utilities for highlighting */
.bg-gray-25 {
    background-color: #fafafa;
}

.bg-blue-25 {
    background-color: #eff6ff;
}
</style>