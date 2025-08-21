import { ref, computed } from 'vue'
import axios from 'axios'

export function useGymCourts() {
    const courts = ref([])
    const loading = ref(false)
    const error = ref(null)
    const selectedCourts = ref([])

    // Computed properties
    const activeCourts = computed(() => {
        return courts.value.filter(court => court.is_active)
    })

    const courtsByType = computed(() => {
        return courts.value.reduce((acc, court) => {
            const type = court.court_type || 'full'
            if (!acc[type]) acc[type] = []
            acc[type].push(court)
            return acc
        }, {})
    })

    const availableCourts = computed(() => {
        return courts.value.filter(court => 
            court.is_active && !selectedCourts.value.includes(court.id)
        )
    })

    const totalCapacity = computed(() => {
        return activeCourts.value.reduce((total, court) => {
            return total + (court.max_capacity || 0)
        }, 0)
    })

    // Methods
    const fetchCourts = async (gymHallId) => {
        loading.value = true
        error.value = null

        try {
            const response = await axios.get(`/api/gym-halls/${gymHallId}/courts`)
            if (response.data.success) {
                courts.value = response.data.data.courts || []
                return courts.value
            } else {
                throw new Error(response.data.message || 'Fehler beim Laden der Courts')
            }
        } catch (err) {
            error.value = err.response?.data?.message || err.message
            console.error('Error fetching courts:', err)
            throw err
        } finally {
            loading.value = false
        }
    }

    const createCourt = async (gymHallId, courtData) => {
        loading.value = true
        error.value = null

        try {
            const response = await axios.post(`/api/gym-halls/${gymHallId}/courts`, courtData)
            if (response.data.success) {
                const newCourt = response.data.data
                courts.value.push(newCourt)
                return newCourt
            } else {
                throw new Error(response.data.message || 'Fehler beim Erstellen des Courts')
            }
        } catch (err) {
            error.value = err.response?.data?.message || err.message
            console.error('Error creating court:', err)
            throw err
        } finally {
            loading.value = false
        }
    }

    const updateCourt = async (gymHallId, courtId, updates) => {
        loading.value = true
        error.value = null

        try {
            const response = await axios.put(`/api/gym-halls/${gymHallId}/courts/${courtId}`, updates)
            if (response.data.success) {
                const updatedCourt = response.data.data
                const index = courts.value.findIndex(court => court.id === courtId)
                if (index !== -1) {
                    courts.value[index] = { ...courts.value[index], ...updatedCourt }
                }
                return updatedCourt
            } else {
                throw new Error(response.data.message || 'Fehler beim Aktualisieren des Courts')
            }
        } catch (err) {
            error.value = err.response?.data?.message || err.message
            console.error('Error updating court:', err)
            throw err
        } finally {
            loading.value = false
        }
    }

    const deleteCourt = async (gymHallId, courtId) => {
        loading.value = true
        error.value = null

        try {
            const response = await axios.delete(`/api/gym-halls/${gymHallId}/courts/${courtId}`)
            if (response.data.success) {
                courts.value = courts.value.filter(court => court.id !== courtId)
                return true
            } else {
                throw new Error(response.data.message || 'Fehler beim Löschen des Courts')
            }
        } catch (err) {
            error.value = err.response?.data?.message || err.message
            console.error('Error deleting court:', err)
            throw err
        } finally {
            loading.value = false
        }
    }

    const getCourtAvailability = async (gymHallId, courtId, date, duration = 30) => {
        try {
            const response = await axios.get(`/api/gym-halls/${gymHallId}/courts/${courtId}/availability`, {
                params: { date, duration }
            })
            
            if (response.data.success) {
                return response.data.data
            } else {
                throw new Error(response.data.message || 'Fehler beim Laden der Verfügbarkeit')
            }
        } catch (err) {
            console.error('Error fetching court availability:', err)
            throw err
        }
    }

    const getCourtSchedule = async (gymHallId, startDate, endDate) => {
        try {
            const response = await axios.get(`/api/gym-halls/${gymHallId}/court-schedule`, {
                params: { 
                    start_date: startDate, 
                    end_date: endDate,
                    include_courts: true 
                }
            })
            
            if (response.data.success) {
                return response.data.data
            } else {
                throw new Error(response.data.message || 'Fehler beim Laden des Court-Terminplans')
            }
        } catch (err) {
            console.error('Error fetching court schedule:', err)
            throw err
        }
    }

    const findAvailableSlots = async (gymHallId, date, duration = 30, teamCount = 1) => {
        try {
            const response = await axios.get(`/api/gym-halls/${gymHallId}/find-slots`, {
                params: { date, duration, team_count: teamCount }
            })
            
            if (response.data.success) {
                return response.data.data
            } else {
                throw new Error(response.data.message || 'Fehler beim Suchen verfügbarer Slots')
            }
        } catch (err) {
            console.error('Error finding available slots:', err)
            throw err
        }
    }

    const getTimeGrid = async (gymHallId, date, slotDuration = 30) => {
        try {
            const response = await axios.get(`/api/gym-halls/${gymHallId}/time-grid`, {
                params: { date, slot_duration: slotDuration }
            })
            
            if (response.data.success) {
                return response.data.data
            } else {
                throw new Error(response.data.message || 'Fehler beim Laden des Zeitrasters')
            }
        } catch (err) {
            console.error('Error fetching time grid:', err)
            throw err
        }
    }

    const updateCourtOrder = async (gymHallId, courtOrders) => {
        loading.value = true
        error.value = null

        try {
            const updates = courtOrders.map(async ({ courtId, sortOrder }) => {
                return updateCourt(gymHallId, courtId, { sort_order: sortOrder })
            })
            
            await Promise.all(updates)
            
            // Re-sort courts array
            courts.value.sort((a, b) => a.sort_order - b.sort_order)
            
            return true
        } catch (err) {
            error.value = err.message
            console.error('Error updating court order:', err)
            throw err
        } finally {
            loading.value = false
        }
    }

    const toggleCourtStatus = async (gymHallId, courtId, isActive) => {
        return updateCourt(gymHallId, courtId, { is_active: isActive })
    }

    const initializeDefaultCourts = async (gymHallId) => {
        loading.value = true
        error.value = null

        try {
            const response = await axios.post(`/api/gym-halls/${gymHallId}/initialize-courts`)
            if (response.data.success) {
                courts.value = response.data.data.courts || []
                return courts.value
            } else {
                throw new Error(response.data.message || 'Fehler beim Initialisieren der Courts')
            }
        } catch (err) {
            error.value = err.response?.data?.message || err.message
            console.error('Error initializing courts:', err)
            throw err
        } finally {
            loading.value = false
        }
    }

    // Court selection helpers
    const selectCourt = (courtId) => {
        if (!selectedCourts.value.includes(courtId)) {
            selectedCourts.value.push(courtId)
        }
    }

    const deselectCourt = (courtId) => {
        selectedCourts.value = selectedCourts.value.filter(id => id !== courtId)
    }

    const toggleCourtSelection = (courtId) => {
        if (selectedCourts.value.includes(courtId)) {
            deselectCourt(courtId)
        } else {
            selectCourt(courtId)
        }
    }

    const clearSelection = () => {
        selectedCourts.value = []
    }

    const selectAllCourts = () => {
        selectedCourts.value = activeCourts.value.map(court => court.id)
    }

    const isCourtSelected = (courtId) => {
        return selectedCourts.value.includes(courtId)
    }

    // Validation helpers
    const validateCourtData = (courtData) => {
        const errors = []

        if (!courtData.court_identifier?.trim()) {
            errors.push('Court-Identifikator ist erforderlich')
        }

        if (!courtData.court_name?.trim()) {
            errors.push('Court-Name ist erforderlich')
        }

        if (!['full', 'half', 'third'].includes(courtData.court_type)) {
            errors.push('Ungültiger Court-Typ')
        }

        if (courtData.max_capacity && (courtData.max_capacity < 1 || courtData.max_capacity > 200)) {
            errors.push('Kapazität muss zwischen 1 und 200 liegen')
        }

        if (courtData.color_code && !/^#[0-9A-Fa-f]{6}$/.test(courtData.color_code)) {
            errors.push('Ungültiger Farbcode (Format: #RRGGBB)')
        }

        return errors
    }

    const checkCourtConflicts = (courtIds, date, startTime, endTime, excludeBookingId = null) => {
        // This would check for booking conflicts across selected courts
        // Implementation depends on how booking data is structured
        return []
    }

    // Utility functions
    const formatCourtName = (court) => {
        return `${court.court_identifier} - ${court.court_name}`
    }

    const getCourtColor = (court) => {
        return court.color_code || '#3B82F6'
    }

    const getCourtTypeLabel = (courtType) => {
        const labels = {
            'full': 'Vollfeld',
            'half': 'Halbfeld', 
            'third': 'Drittelfeld'
        }
        return labels[courtType] || courtType
    }

    const generateCourtIdentifier = (index, hallType) => {
        if (hallType === 'single') return '1'
        if (hallType === 'double') return index === 0 ? 'A' : 'B'
        if (hallType === 'triple') return ['A', 'B', 'C'][index] || (index + 1).toString()
        return (index + 1).toString()
    }

    // Reset function
    const reset = () => {
        courts.value = []
        selectedCourts.value = []
        loading.value = false
        error.value = null
    }

    return {
        // State
        courts,
        loading,
        error,
        selectedCourts,

        // Computed
        activeCourts,
        courtsByType,
        availableCourts,
        totalCapacity,

        // Methods
        fetchCourts,
        createCourt,
        updateCourt,
        deleteCourt,
        getCourtAvailability,
        getCourtSchedule,
        findAvailableSlots,
        getTimeGrid,
        updateCourtOrder,
        toggleCourtStatus,
        initializeDefaultCourts,

        // Selection
        selectCourt,
        deselectCourt,
        toggleCourtSelection,
        clearSelection,
        selectAllCourts,
        isCourtSelected,

        // Validation
        validateCourtData,
        checkCourtConflicts,

        // Utilities
        formatCourtName,
        getCourtColor,
        getCourtTypeLabel,
        generateCourtIdentifier,

        // Reset
        reset
    }
}