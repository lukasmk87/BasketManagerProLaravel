import { ref, onMounted, onUnmounted } from 'vue'
import { getEcho } from '@/echo'

export function useGymNotifications() {
    // Echo instance - loaded lazily
    let echoInstance = null
    const notifications = ref([])
    const channels = ref(new Map())
    const subscriptions = ref(new Set())

    // Notification types for gym management
    const NOTIFICATION_TYPES = {
        GYM_TIME_RELEASED: 'gym_time_released',
        GYM_BOOKING_REQUESTED: 'gym_booking_requested',
        GYM_BOOKING_APPROVED: 'gym_booking_approved',
        GYM_BOOKING_REJECTED: 'gym_booking_rejected',
        GYM_BOOKING_CANCELLED: 'gym_booking_cancelled',
        GYM_SCHEDULE_UPDATED: 'gym_schedule_updated',
        GYM_BOOKING_REMINDER: 'gym_booking_reminder'
    }

    // Subscribe to gym updates for a specific club
    const subscribeToGymUpdates = async (clubId, callbacks = {}) => {
        const channelName = `club.${clubId}.gym`

        if (channels.value.has(channelName)) {
            return channels.value.get(channelName)
        }

        echoInstance = await getEcho()
        const channel = echoInstance.channel(channelName)
            .listen('GymTimeReleased', (e) => {
                handleNotification(NOTIFICATION_TYPES.GYM_TIME_RELEASED, e.data, callbacks.onTimeReleased)
            })
            .listen('GymBookingRequested', (e) => {
                handleNotification(NOTIFICATION_TYPES.GYM_BOOKING_REQUESTED, e.data, callbacks.onBookingRequested)
            })
            .listen('GymBookingApproved', (e) => {
                handleNotification(NOTIFICATION_TYPES.GYM_BOOKING_APPROVED, e.data, callbacks.onBookingApproved)
            })
            .listen('GymBookingRejected', (e) => {
                handleNotification(NOTIFICATION_TYPES.GYM_BOOKING_REJECTED, e.data, callbacks.onBookingRejected)
            })
            .listen('GymScheduleUpdated', (e) => {
                handleNotification(NOTIFICATION_TYPES.GYM_SCHEDULE_UPDATED, e.data, callbacks.onScheduleUpdated)
            })

        channels.value.set(channelName, channel)
        subscriptions.value.add(channelName)

        return channel
    }

    // Subscribe to gym updates for a specific hall
    const subscribeToGymHallUpdates = async (hallId, callbacks = {}) => {
        const channelName = `gym-hall.${hallId}`

        if (channels.value.has(channelName)) {
            return channels.value.get(channelName)
        }

        echoInstance = await getEcho()
        const channel = echoInstance.channel(channelName)
            .listen('TimeSlotReleased', (e) => {
                handleNotification(NOTIFICATION_TYPES.GYM_TIME_RELEASED, e.data, callbacks.onTimeSlotReleased)
            })
            .listen('BookingRequested', (e) => {
                handleNotification(NOTIFICATION_TYPES.GYM_BOOKING_REQUESTED, e.data, callbacks.onBookingRequested)
            })
            .listen('BookingConfirmed', (e) => {
                handleNotification(NOTIFICATION_TYPES.GYM_BOOKING_APPROVED, e.data, callbacks.onBookingConfirmed)
            })

        channels.value.set(channelName, channel)
        subscriptions.value.add(channelName)

        return channel
    }

    // Subscribe to team-specific gym notifications
    const subscribeToTeamGymUpdates = async (teamId, callbacks = {}) => {
        const channelName = `team.${teamId}.gym`

        if (channels.value.has(channelName)) {
            return channels.value.get(channelName)
        }

        echoInstance = await getEcho()
        const channel = echoInstance.channel(channelName)
            .listen('BookingReleased', (e) => {
                handleNotification(NOTIFICATION_TYPES.GYM_TIME_RELEASED, e.data, callbacks.onBookingReleased)
            })
            .listen('RequestApproved', (e) => {
                handleNotification(NOTIFICATION_TYPES.GYM_BOOKING_APPROVED, e.data, callbacks.onRequestApproved)
            })
            .listen('RequestRejected', (e) => {
                handleNotification(NOTIFICATION_TYPES.GYM_BOOKING_REJECTED, e.data, callbacks.onRequestRejected)
            })
            .listen('BookingReminder', (e) => {
                handleNotification(NOTIFICATION_TYPES.GYM_BOOKING_REMINDER, e.data, callbacks.onBookingReminder)
            })

        channels.value.set(channelName, channel)
        subscriptions.value.add(channelName)

        return channel
    }

    // Handle incoming notifications
    const handleNotification = (type, data, callback) => {
        const notification = {
            id: Date.now() + Math.random(),
            type,
            data,
            timestamp: new Date(),
            read: false
        }

        // Add to notifications list
        notifications.value.unshift(notification)

        // Keep only last 50 notifications
        if (notifications.value.length > 50) {
            notifications.value = notifications.value.slice(0, 50)
        }

        // Show browser notification if permission granted
        showBrowserNotification(notification)

        // Execute callback if provided
        if (callback && typeof callback === 'function') {
            callback(data)
        }

        // Store in localStorage for persistence
        storeNotification(notification)
    }

    // Show browser push notification
    const showBrowserNotification = (notification) => {
        if (!('Notification' in window) || Notification.permission !== 'granted') {
            return
        }

        const { title, body, icon } = formatNotificationContent(notification)

        const browserNotification = new Notification(title, {
            body,
            icon: icon || '/images/logo-192.png',
            badge: '/images/badge-gym.png',
            tag: `gym-${notification.type}-${notification.data.id}`,
            data: notification.data,
            requireInteraction: ['GYM_BOOKING_REQUESTED', 'GYM_TIME_RELEASED'].includes(notification.type)
        })

        browserNotification.onclick = () => {
            // Handle notification click - navigate to relevant page
            handleNotificationClick(notification)
            browserNotification.close()
        }

        // Auto-close after 5 seconds for non-interactive notifications
        if (!browserNotification.requireInteraction) {
            setTimeout(() => {
                browserNotification.close()
            }, 5000)
        }
    }

    // Format notification content for display
    const formatNotificationContent = (notification) => {
        const { type, data } = notification

        switch (type) {
            case NOTIFICATION_TYPES.GYM_TIME_RELEASED:
                return {
                    title: '🏀 Hallenzeit freigegeben',
                    body: `${data.gym_hall_name} am ${formatDate(data.date)} um ${formatTime(data.start_time)} ist jetzt verfügbar`,
                    icon: '/images/notifications/gym-released.png'
                }

            case NOTIFICATION_TYPES.GYM_BOOKING_REQUESTED:
                return {
                    title: '📋 Neue Buchungsanfrage',
                    body: `${data.requesting_team_name} möchte ${data.gym_hall_name} am ${formatDate(data.date)} buchen`,
                    icon: '/images/notifications/booking-request.png'
                }

            case NOTIFICATION_TYPES.GYM_BOOKING_APPROVED:
                return {
                    title: '✅ Buchung genehmigt',
                    body: `Ihre Anfrage für ${data.gym_hall_name} am ${formatDate(data.date)} wurde genehmigt`,
                    icon: '/images/notifications/booking-approved.png'
                }

            case NOTIFICATION_TYPES.GYM_BOOKING_REJECTED:
                return {
                    title: '❌ Buchung abgelehnt',
                    body: `Ihre Anfrage für ${data.gym_hall_name} am ${formatDate(data.date)} wurde abgelehnt`,
                    icon: '/images/notifications/booking-rejected.png'
                }

            case NOTIFICATION_TYPES.GYM_BOOKING_REMINDER:
                return {
                    title: '⏰ Hallenzeit-Erinnerung',
                    body: `Ihr Training in ${data.gym_hall_name} beginnt in ${data.minutes_until} Minuten`,
                    icon: '/images/notifications/reminder.png'
                }

            case NOTIFICATION_TYPES.GYM_SCHEDULE_UPDATED:
                return {
                    title: '📅 Hallenplan aktualisiert',
                    body: `Der Hallenplan wurde aktualisiert. ${data.changes_count} Änderungen vorgenommen`,
                    icon: '/images/notifications/schedule-update.png'
                }

            default:
                return {
                    title: '🏀 Gym-Management Update',
                    body: 'Eine neue Aktualisierung ist verfügbar',
                    icon: '/images/notifications/default.png'
                }
        }
    }

    // Handle notification click actions
    const handleNotificationClick = (notification) => {
        const { type, data } = notification

        // Mark as read
        markAsRead(notification.id)

        // Navigate based on notification type
        switch (type) {
            case NOTIFICATION_TYPES.GYM_TIME_RELEASED:
                window.location.href = `/gym/bookings/available`
                break

            case NOTIFICATION_TYPES.GYM_BOOKING_REQUESTED:
                window.location.href = `/gym/requests`
                break

            case NOTIFICATION_TYPES.GYM_BOOKING_APPROVED:
            case NOTIFICATION_TYPES.GYM_BOOKING_REJECTED:
                window.location.href = `/gym/my-bookings`
                break

            case NOTIFICATION_TYPES.GYM_BOOKING_REMINDER:
                window.location.href = `/gym/halls/${data.gym_hall_id}`
                break

            case NOTIFICATION_TYPES.GYM_SCHEDULE_UPDATED:
                window.location.href = `/gym/dashboard`
                break

            default:
                window.location.href = `/gym/dashboard`
        }
    }

    // Request notification permission
    const requestNotificationPermission = async () => {
        if (!('Notification' in window)) {
            console.warn('Browser does not support notifications')
            return false
        }

        if (Notification.permission === 'granted') {
            return true
        }

        if (Notification.permission === 'denied') {
            return false
        }

        const permission = await Notification.requestPermission()
        return permission === 'granted'
    }

    // Mark notification as read
    const markAsRead = (notificationId) => {
        const notification = notifications.value.find(n => n.id === notificationId)
        if (notification) {
            notification.read = true
            updateStoredNotifications()
        }
    }

    // Mark all notifications as read
    const markAllAsRead = () => {
        notifications.value.forEach(notification => {
            notification.read = true
        })
        updateStoredNotifications()
    }

    // Clear all notifications
    const clearAll = () => {
        notifications.value = []
        localStorage.removeItem('gym-notifications')
    }

    // Get unread count
    const getUnreadCount = () => {
        return notifications.value.filter(n => !n.read).length
    }

    // Store notification in localStorage
    const storeNotification = (notification) => {
        const stored = getStoredNotifications()
        stored.unshift(notification)
        
        // Keep only last 50
        const trimmed = stored.slice(0, 50)
        localStorage.setItem('gym-notifications', JSON.stringify(trimmed))
    }

    // Update stored notifications
    const updateStoredNotifications = () => {
        localStorage.setItem('gym-notifications', JSON.stringify(notifications.value))
    }

    // Get stored notifications from localStorage
    const getStoredNotifications = () => {
        try {
            const stored = localStorage.getItem('gym-notifications')
            return stored ? JSON.parse(stored) : []
        } catch (error) {
            console.error('Error parsing stored notifications:', error)
            return []
        }
    }

    // Load stored notifications
    const loadStoredNotifications = () => {
        const stored = getStoredNotifications()
        notifications.value = stored.map(n => ({
            ...n,
            timestamp: new Date(n.timestamp)
        }))
    }

    // Cleanup subscriptions
    const cleanup = async () => {
        if (!echoInstance) {
            echoInstance = await getEcho()
        }
        subscriptions.value.forEach(channelName => {
            if (channels.value.has(channelName)) {
                echoInstance.leaveChannel(channelName)
                channels.value.delete(channelName)
            }
        })
        subscriptions.value.clear()
    }

    // Utility functions
    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('de-DE')
    }

    const formatTime = (timeString) => {
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

    // Initialize
    onMounted(() => {
        loadStoredNotifications()
        requestNotificationPermission()
    })

    onUnmounted(() => {
        cleanup()
    })

    return {
        // State
        notifications,
        NOTIFICATION_TYPES,

        // Methods
        subscribeToGymUpdates,
        subscribeToGymHallUpdates,
        subscribeToTeamGymUpdates,
        requestNotificationPermission,
        markAsRead,
        markAllAsRead,
        clearAll,
        getUnreadCount,
        cleanup,

        // Utilities
        formatNotificationContent,
        handleNotificationClick
    }
}