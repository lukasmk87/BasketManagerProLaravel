import { ref, reactive, computed } from 'vue'
import axios from 'axios'

export function useGymManagement(initialGymHalls = [], initialStats = {}) {
    // Utility functions (defined first to avoid hoisting issues)
    const getISODateString = (date) => {
        return date.toISOString().split('T')[0]
    }

    const getCurrentWeekStart = () => {
        const now = new Date()
        const dayOfWeek = now.getDay()
        const diff = now.getDate() - dayOfWeek + (dayOfWeek === 0 ? -6 : 1) // Adjust for Monday start
        const monday = new Date(now.setDate(diff))
        return getISODateString(monday)
    }

    const isCurrentWeek = () => {
        return currentWeek.value === getCurrentWeekStart()
    }

    // Reactive state
    const gymHalls = ref(initialGymHalls)
    const stats = reactive({
        total_halls: 0,
        active_bookings: 0,
        pending_requests: 0,
        utilization_rate: 0,
        ...initialStats
    })
    
    const weeklyBookings = ref({})
    const pendingRequests = ref([])
    const recentActivities = ref([])
    const currentWeek = ref(getCurrentWeekStart())
    const loading = ref(false)
    const error = ref(null)

    // Computed properties
    const activeGymHalls = computed(() => {
        return gymHalls.value.filter(hall => hall.is_active)
    })

    // Methods
    const refreshData = async () => {
        loading.value = true
        error.value = null
        
        try {
            await Promise.all([
                refreshStats(),
                refreshWeeklyBookings(),
                refreshPendingRequests(),
                refreshRecentActivities()
            ])
        } catch (err) {
            error.value = err.message
            console.error('Error refreshing gym data:', err)
        } finally {
            loading.value = false
        }
    }

    const refreshStats = async () => {
        try {
            const response = await axios.get('/api/v2/gym-management/stats')
            Object.assign(stats, response.data.data)
        } catch (err) {
            console.error('Error refreshing stats:', err)
        }
    }

    const refreshWeeklyBookings = async () => {
        try {
            const response = await axios.get('/api/v2/gym-management/weekly-bookings', {
                params: { week_start: currentWeek.value }
            })
            weeklyBookings.value = response.data.data
        } catch (err) {
            console.error('Error refreshing weekly bookings:', err)
        }
    }

    const refreshPendingRequests = async () => {
        try {
            const response = await axios.get('/api/v2/gym-management/pending-requests')
            pendingRequests.value = response.data.data
        } catch (err) {
            console.error('Error refreshing pending requests:', err)
        }
    }

    const refreshRecentActivities = async () => {
        try {
            const response = await axios.get('/api/v2/gym-management/recent-activities')
            recentActivities.value = response.data.data
        } catch (err) {
            console.error('Error refreshing recent activities:', err)
        }
    }

    const previousWeek = () => {
        const current = new Date(currentWeek.value)
        current.setDate(current.getDate() - 7)
        currentWeek.value = getISODateString(current)
        refreshWeeklyBookings()
    }

    const nextWeek = () => {
        const current = new Date(currentWeek.value)
        current.setDate(current.getDate() + 7)
        currentWeek.value = getISODateString(current)
        refreshWeeklyBookings()
    }

    const goToToday = () => {
        currentWeek.value = getCurrentWeekStart()
        refreshWeeklyBookings()
    }

    const approveRequest = async (requestId, reviewNotes = '') => {
        try {
            await axios.post(`/api/v2/gym-booking-requests/${requestId}/approve`, {
                review_notes: reviewNotes
            })
            
            // Remove from pending requests
            pendingRequests.value = pendingRequests.value.filter(req => req.id !== requestId)
            
            // Refresh data
            await refreshData()
            
            return true
        } catch (err) {
            console.error('Error approving request:', err)
            throw err
        }
    }

    const rejectRequest = async (requestId, rejectionReason, reviewNotes = '') => {
        try {
            await axios.post(`/api/v2/gym-booking-requests/${requestId}/reject`, {
                rejection_reason: rejectionReason,
                review_notes: reviewNotes
            })
            
            // Remove from pending requests
            pendingRequests.value = pendingRequests.value.filter(req => req.id !== requestId)
            
            // Refresh data
            await refreshData()
            
            return true
        } catch (err) {
            console.error('Error rejecting request:', err)
            throw err
        }
    }

    const createGymHall = async (hallData) => {
        try {
            const response = await axios.post('/api/v2/gym-halls', hallData)
            gymHalls.value.push(response.data.data)
            await refreshStats()
            return response.data.data
        } catch (err) {
            console.error('Error creating gym hall:', err)
            throw err
        }
    }

    const updateGymHall = async (hallId, hallData) => {
        try {
            const response = await axios.put(`/api/v2/gym-halls/${hallId}`, hallData)
            
            const index = gymHalls.value.findIndex(hall => hall.id === hallId)
            if (index !== -1) {
                gymHalls.value[index] = response.data.data
            }
            
            return response.data.data
        } catch (err) {
            console.error('Error updating gym hall:', err)
            throw err
        }
    }

    const deleteGymHall = async (hallId) => {
        try {
            await axios.delete(`/api/v2/gym-halls/${hallId}`)
            gymHalls.value = gymHalls.value.filter(hall => hall.id !== hallId)
            await refreshStats()
            return true
        } catch (err) {
            console.error('Error deleting gym hall:', err)
            throw err
        }
    }

    const createTimeSlot = async (timeSlotData) => {
        try {
            const response = await axios.post('/api/v2/gym-time-slots', timeSlotData)
            await refreshData()
            return response.data.data
        } catch (err) {
            console.error('Error creating time slot:', err)
            throw err
        }
    }

    const updateTimeSlot = async (timeSlotId, timeSlotData) => {
        try {
            const response = await axios.put(`/api/v2/gym-time-slots/${timeSlotId}`, timeSlotData)
            await refreshData()
            return response.data.data
        } catch (err) {
            console.error('Error updating time slot:', err)
            throw err
        }
    }

    const assignTimeSlotToTeam = async (timeSlotId, teamId, reason = '') => {
        try {
            await axios.post(`/api/v2/gym-time-slots/${timeSlotId}/assign`, {
                team_id: teamId,
                reason: reason
            })
            await refreshData()
            return true
        } catch (err) {
            console.error('Error assigning time slot:', err)
            throw err
        }
    }

    const unassignTimeSlot = async (timeSlotId, reason = '') => {
        try {
            await axios.delete(`/api/v2/gym-time-slots/${timeSlotId}/unassign`, {
                data: { reason: reason }
            })
            await refreshData()
            return true
        } catch (err) {
            console.error('Error unassigning time slot:', err)
            throw err
        }
    }

    const generateBookings = async (timeSlotId, startDate, endDate) => {
        try {
            const response = await axios.post(`/api/v2/gym-time-slots/${timeSlotId}/generate-bookings`, {
                start_date: startDate,
                end_date: endDate
            })
            await refreshData()
            return response.data.bookings_created
        } catch (err) {
            console.error('Error generating bookings:', err)
            throw err
        }
    }

    const getHallStatistics = async (hallId, period = 'month') => {
        try {
            const response = await axios.get(`/api/v2/gym-halls/${hallId}/statistics`, {
                params: { period }
            })
            return response.data.data.statistics
        } catch (err) {
            console.error('Error getting hall statistics:', err)
            throw err
        }
    }

    const getAvailableTimesForTeam = async (teamId, startDate, endDate) => {
        try {
            const response = await axios.get(`/api/v2/gym-bookings/available-for-team/${teamId}`, {
                params: { start_date: startDate, end_date: endDate }
            })
            return response.data.data
        } catch (err) {
            console.error('Error getting available times:', err)
            throw err
        }
    }

    const searchAvailableSlots = async (criteria) => {
        try {
            const response = await axios.post('/api/v2/gym-management/search-available', criteria)
            return response.data.data
        } catch (err) {
            console.error('Error searching available slots:', err)
            throw err
        }
    }

    // Export everything
    return {
        // State
        gymHalls,
        stats,
        weeklyBookings,
        pendingRequests,
        recentActivities,
        currentWeek,
        loading,
        error,

        // Computed
        activeGymHalls,

        // Methods
        refreshData,
        refreshStats,
        refreshWeeklyBookings,
        refreshPendingRequests,
        refreshRecentActivities,
        previousWeek,
        nextWeek,
        goToToday,
        approveRequest,
        rejectRequest,
        createGymHall,
        updateGymHall,
        deleteGymHall,
        createTimeSlot,
        updateTimeSlot,
        assignTimeSlotToTeam,
        unassignTimeSlot,
        generateBookings,
        getHallStatistics,
        getAvailableTimesForTeam,
        searchAvailableSlots,

        // Utilities
        isCurrentWeek
    }
}