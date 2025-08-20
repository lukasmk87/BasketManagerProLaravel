import { ref, computed } from 'vue'
import axios from 'axios'

export function useGymBookings() {
    const bookings = ref([])
    const loading = ref(false)
    const error = ref(null)

    // Computed properties
    const upcomingBookings = computed(() => {
        const now = new Date()
        return bookings.value.filter(booking => 
            new Date(booking.booking_date) >= now
        )
    })

    const pastBookings = computed(() => {
        const now = new Date()
        return bookings.value.filter(booking => 
            new Date(booking.booking_date) < now
        )
    })

    const myBookings = computed(() => {
        // This would be filtered by current user's teams
        return bookings.value
    })

    // Methods
    const fetchBookings = async (params = {}) => {
        loading.value = true
        error.value = null

        try {
            const response = await axios.get('/api/v2/gym-bookings', { params })
            bookings.value = response.data.data
            return response.data.data
        } catch (err) {
            error.value = err.message
            console.error('Error fetching bookings:', err)
            throw err
        } finally {
            loading.value = false
        }
    }

    const createBooking = async (bookingData) => {
        try {
            const response = await axios.post('/api/v2/gym-bookings', bookingData)
            
            // Add to local state
            bookings.value.push(response.data.data)
            
            return response.data.data
        } catch (err) {
            error.value = err.message
            console.error('Error creating booking:', err)
            throw err
        }
    }

    const updateBooking = async (bookingId, bookingData) => {
        try {
            const response = await axios.put(`/api/v2/gym-bookings/${bookingId}`, bookingData)
            
            // Update local state
            const index = bookings.value.findIndex(b => b.id === bookingId)
            if (index !== -1) {
                bookings.value[index] = response.data.data
            }
            
            return response.data.data
        } catch (err) {
            error.value = err.message
            console.error('Error updating booking:', err)
            throw err
        }
    }

    const releaseBooking = async (bookingId, reason = '') => {
        try {
            const response = await axios.post(`/api/v2/gym-bookings/${bookingId}/release`, {
                reason: reason
            })
            
            // Update local state
            const index = bookings.value.findIndex(b => b.id === bookingId)
            if (index !== -1) {
                bookings.value[index] = response.data.data
            }
            
            return response.data.data
        } catch (err) {
            error.value = err.message
            console.error('Error releasing booking:', err)
            throw err
        }
    }

    const cancelBooking = async (bookingId, reason = '') => {
        try {
            const response = await axios.post(`/api/v2/gym-bookings/${bookingId}/cancel`, {
                reason: reason
            })
            
            // Update local state
            const index = bookings.value.findIndex(b => b.id === bookingId)
            if (index !== -1) {
                bookings.value[index] = response.data.data
            }
            
            return response.data.data
        } catch (err) {
            error.value = err.message
            console.error('Error canceling booking:', err)
            throw err
        }
    }

    const requestBooking = async (bookingId, requestData) => {
        try {
            const response = await axios.post(`/api/v2/gym-bookings/${bookingId}/request`, requestData)
            return response.data.data
        } catch (err) {
            error.value = err.message
            console.error('Error requesting booking:', err)
            throw err
        }
    }

    const getAvailableBookingsForTeam = async (teamId, startDate, endDate) => {
        try {
            const response = await axios.get(`/api/v2/gym-bookings/available-for-team/${teamId}`, {
                params: {
                    start_date: startDate,
                    end_date: endDate
                }
            })
            return response.data.data
        } catch (err) {
            error.value = err.message
            console.error('Error fetching available bookings:', err)
            throw err
        }
    }

    const approveBookingRequest = async (requestId, reviewNotes = '') => {
        try {
            const response = await axios.post(`/api/v2/gym-booking-requests/${requestId}/approve`, {
                review_notes: reviewNotes
            })
            return response.data.data
        } catch (err) {
            error.value = err.message
            console.error('Error approving booking request:', err)
            throw err
        }
    }

    const rejectBookingRequest = async (requestId, rejectionReason, reviewNotes = '') => {
        try {
            const response = await axios.post(`/api/v2/gym-booking-requests/${requestId}/reject`, {
                rejection_reason: rejectionReason,
                review_notes: reviewNotes
            })
            return response.data.data
        } catch (err) {
            error.value = err.message
            console.error('Error rejecting booking request:', err)
            throw err
        }
    }

    const getBookingRequests = async (params = {}) => {
        try {
            const response = await axios.get('/api/v2/gym-booking-requests', { params })
            return response.data.data
        } catch (err) {
            error.value = err.message
            console.error('Error fetching booking requests:', err)
            throw err
        }
    }

    const getTeamBookings = async (teamId, params = {}) => {
        try {
            const response = await axios.get(`/api/v2/teams/${teamId}/gym-management/bookings`, { params })
            return response.data.data
        } catch (err) {
            error.value = err.message
            console.error('Error fetching team bookings:', err)
            throw err
        }
    }

    const getBookingById = async (bookingId) => {
        try {
            const response = await axios.get(`/api/v2/gym-bookings/${bookingId}`)
            return response.data.data
        } catch (err) {
            error.value = err.message
            console.error('Error fetching booking:', err)
            throw err
        }
    }

    const searchAvailableSlots = async (criteria) => {
        try {
            const response = await axios.post('/api/v2/gym-management/search-available', criteria)
            return response.data.data
        } catch (err) {
            error.value = err.message
            console.error('Error searching available slots:', err)
            throw err
        }
    }

    // Utility functions
    const isBookingEditable = (booking) => {
        if (!booking) return false
        
        const bookingDate = new Date(booking.booking_date)
        const now = new Date()
        
        // Can't edit past bookings
        if (bookingDate < now) return false
        
        // Can edit if status allows it
        return ['reserved', 'requested'].includes(booking.status)
    }

    const isBookingCancellable = (booking) => {
        if (!booking) return false
        
        const bookingDate = new Date(booking.booking_date)
        const now = new Date()
        
        // Can't cancel past bookings
        if (bookingDate < now) return false
        
        // Can cancel if status allows it
        return ['reserved', 'confirmed', 'requested'].includes(booking.status)
    }

    const isBookingReleasable = (booking) => {
        if (!booking) return false
        
        const bookingDate = new Date(booking.booking_date)
        const now = new Date()
        
        // Can't release past bookings
        if (bookingDate < now) return false
        
        // Can only release reserved bookings that allow substitution
        return booking.status === 'reserved' && 
               booking.gym_time_slot?.allows_substitution === true
    }

    const getBookingStatusColor = (status) => {
        const colorMap = {
            'reserved': 'blue',
            'released': 'yellow',
            'requested': 'purple',
            'confirmed': 'green',
            'cancelled': 'red',
            'completed': 'gray',
            'no_show': 'red'
        }
        return colorMap[status] || 'gray'
    }

    const getBookingStatusText = (status) => {
        const textMap = {
            'reserved': 'Reserviert',
            'released': 'Freigegeben',
            'requested': 'Angefragt',
            'confirmed': 'Bestätigt',
            'cancelled': 'Storniert',
            'completed': 'Abgeschlossen',
            'no_show': 'Nicht erschienen'
        }
        return textMap[status] || status
    }

    const calculateBookingDuration = (booking) => {
        if (!booking || !booking.start_time || !booking.end_time) return 0
        
        const start = new Date(`2000-01-01T${booking.start_time}`)
        const end = new Date(`2000-01-01T${booking.end_time}`)
        
        return Math.max(0, (end - start) / 60000) // minutes
    }

    const formatBookingTime = (booking) => {
        if (!booking || !booking.start_time || !booking.end_time) return 'N/A'
        
        try {
            const start = new Date(`2000-01-01T${booking.start_time}`)
            const end = new Date(`2000-01-01T${booking.end_time}`)
            
            const formatTime = (time) => time.toLocaleTimeString('de-DE', { 
                hour: '2-digit', 
                minute: '2-digit',
                hour12: false
            })
            
            return `${formatTime(start)} - ${formatTime(end)}`
        } catch (error) {
            return 'N/A'
        }
    }

    const formatBookingDate = (booking) => {
        if (!booking || !booking.booking_date) return 'N/A'
        
        try {
            const date = new Date(booking.booking_date)
            return date.toLocaleDateString('de-DE', {
                weekday: 'short',
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            })
        } catch (error) {
            return 'N/A'
        }
    }

    return {
        // State
        bookings,
        loading,
        error,

        // Computed
        upcomingBookings,
        pastBookings,
        myBookings,

        // Methods
        fetchBookings,
        createBooking,
        updateBooking,
        releaseBooking,
        cancelBooking,
        requestBooking,
        getAvailableBookingsForTeam,
        approveBookingRequest,
        rejectBookingRequest,
        getBookingRequests,
        getTeamBookings,
        getBookingById,
        searchAvailableSlots,

        // Utilities
        isBookingEditable,
        isBookingCancellable,
        isBookingReleasable,
        getBookingStatusColor,
        getBookingStatusText,
        calculateBookingDuration,
        formatBookingTime,
        formatBookingDate
    }
}