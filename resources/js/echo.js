// PERF-005: Lazy load Echo and Pusher only when needed
// This saves ~20 KB gzipped for pages that don't use real-time features

// Determine the broadcasting driver from environment
const broadcastDriver = import.meta.env.VITE_BROADCAST_DRIVER || 'log';

// Echo instance - will be initialized lazily
let echo = null;
let echoInitPromise = null;

/**
 * Initialize Echo with the appropriate broadcaster
 * Uses dynamic imports to avoid loading Pusher on pages that don't need it
 */
async function initializeEcho() {
    if (echo) return echo;
    if (echoInitPromise) return echoInitPromise;

    echoInitPromise = (async () => {
        const { default: Echo } = await import('laravel-echo');
        let echoConfig = {};

        if (broadcastDriver === 'pusher') {
            // Only load Pusher when actually needed
            const { default: Pusher } = await import('pusher-js');
            window.Pusher = Pusher;

            // Production configuration for Pusher
            echoConfig = {
                broadcaster: 'pusher',
                key: import.meta.env.VITE_PUSHER_APP_KEY,
                cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1',
                wsHost: window.location.hostname,
                wsPort: 6001,
                wssPort: 6001,
                forceTLS: import.meta.env.VITE_PUSHER_SCHEME === 'https',
                enabledTransports: ['ws', 'wss'],
            };
        } else if (broadcastDriver === 'reverb') {
            // Laravel Reverb configuration (doesn't need Pusher)
            echoConfig = {
                broadcaster: 'reverb',
                key: import.meta.env.VITE_REVERB_APP_KEY,
                wsHost: import.meta.env.VITE_REVERB_HOST,
                wsPort: import.meta.env.VITE_REVERB_PORT || 80,
                wssPort: import.meta.env.VITE_REVERB_PORT || 443,
                forceTLS: (import.meta.env.VITE_REVERB_SCHEME || 'https') === 'https',
                enabledTransports: ['ws', 'wss'],
            };
        } else {
            // Development configuration - null broadcaster for testing
            echoConfig = {
                broadcaster: 'null',
            };
        }

        // Create Echo instance
        echo = new Echo(echoConfig);

        // Export for global use
        window.Echo = echo;

        return echo;
    })();

    return echoInitPromise;
}

// Auto-initialize if broadcasting is enabled (not 'log' or 'null')
if (broadcastDriver !== 'log') {
    initializeEcho();
}

// Basketball-specific channel helpers
// All helpers are now async to ensure Echo is initialized
export const subscribeToGame = async (gameId, callbacks = {}) => {
    const echoInstance = await initializeEcho();
    const channel = echoInstance.channel(`game.${gameId}`)
        .listen('GameStarted', callbacks.onGameStarted || (() => {}))
        .listen('GameFinished', callbacks.onGameFinished || (() => {}))
        .listen('GameActionAdded', callbacks.onActionAdded || (() => {}))
        .listen('GameScoreUpdated', callbacks.onScoreUpdated || (() => {}))
        .listen('GameClockUpdated', callbacks.onClockUpdated || (() => {}))
        .listen('GameTimeoutStarted', callbacks.onTimeoutStarted || (() => {}))
        .listen('GameTimeoutEnded', callbacks.onTimeoutEnded || (() => {}))
        .listen('GameActionCorrected', callbacks.onActionCorrected || (() => {}))
        .listen('GameActionDeleted', callbacks.onActionDeleted || (() => {}));

    return channel;
};

export const subscribeToLiveGames = async (callbacks = {}) => {
    const echoInstance = await initializeEcho();
    return echoInstance.channel('live-games')
        .listen('GameStarted', callbacks.onGameStarted || (() => {}))
        .listen('GameFinished', callbacks.onGameFinished || (() => {}))
        .listen('GameScoreUpdated', callbacks.onScoreUpdated || (() => {}));
};

export const subscribeToTeam = async (teamId, callbacks = {}) => {
    const echoInstance = await initializeEcho();
    return echoInstance.channel(`team.${teamId}`)
        .listen('GameStarted', callbacks.onGameStarted || (() => {}))
        .listen('GameFinished', callbacks.onGameFinished || (() => {}))
        .listen('GameScoreUpdated', callbacks.onScoreUpdated || (() => {}));
};

export const subscribeToClub = async (clubId, callbacks = {}) => {
    const echoInstance = await initializeEcho();
    return echoInstance.channel(`club.${clubId}`)
        .listen('GameStarted', callbacks.onGameStarted || (() => {}))
        .listen('GameFinished', callbacks.onGameFinished || (() => {}));
};

// Legacy alias with typo for backwards compatibility
export const subscribeToCLub = subscribeToClub;

// Private channel for authenticated users
export const subscribeToUser = async (userId, callbacks = {}) => {
    const echoInstance = await initializeEcho();
    return echoInstance.private(`App.Models.User.${userId}`)
        .notification(callbacks.onNotification || (() => {}));
};

// Helper function to leave all game-related channels
export const leaveGameChannels = async (gameId) => {
    const echoInstance = await initializeEcho();
    echoInstance.leaveChannel(`game.${gameId}`);
};

// Gym Management channel helpers
export const subscribeToGymHall = async (gymHallId, callbacks = {}) => {
    const echoInstance = await initializeEcho();
    const channel = echoInstance.channel(`gym-hall.${gymHallId}`)
        .listen('TimeSlotReleased', callbacks.onTimeSlotReleased || (() => {}))
        .listen('BookingRequested', callbacks.onBookingRequested || (() => {}))
        .listen('BookingConfirmed', callbacks.onBookingConfirmed || (() => {}))
        .listen('BookingCancelled', callbacks.onBookingCancelled || (() => {}))
        .listen('ScheduleUpdated', callbacks.onScheduleUpdated || (() => {}));

    return channel;
};

export const subscribeToClubGym = async (clubId, callbacks = {}) => {
    const echoInstance = await initializeEcho();
    return echoInstance.channel(`club.${clubId}.gym`)
        .listen('GymTimeReleased', callbacks.onTimeReleased || (() => {}))
        .listen('GymBookingRequested', callbacks.onBookingRequested || (() => {}))
        .listen('GymBookingApproved', callbacks.onBookingApproved || (() => {}))
        .listen('GymBookingRejected', callbacks.onBookingRejected || (() => {}))
        .listen('GymScheduleUpdated', callbacks.onScheduleUpdated || (() => {}));
};

export const subscribeToTeamGym = async (teamId, callbacks = {}) => {
    const echoInstance = await initializeEcho();
    return echoInstance.channel(`team.${teamId}.gym`)
        .listen('BookingReleased', callbacks.onBookingReleased || (() => {}))
        .listen('RequestApproved', callbacks.onRequestApproved || (() => {}))
        .listen('RequestRejected', callbacks.onRequestRejected || (() => {}))
        .listen('BookingReminder', callbacks.onBookingReminder || (() => {}))
        .listen('ConflictDetected', callbacks.onConflictDetected || (() => {}));
};

// Private user notifications for gym events
export const subscribeToUserGymNotifications = async (userId, callbacks = {}) => {
    const echoInstance = await initializeEcho();
    return echoInstance.private(`App.Models.User.${userId}`)
        .notification((notification) => {
            if (notification.type && notification.type.includes('Gym')) {
                (callbacks.onGymNotification || (() => {}))(notification);
            }
            (callbacks.onNotification || (() => {}))(notification);
        });
};

// Helper function to leave gym-related channels
export const leaveGymChannels = async (hallId, clubId = null, teamId = null) => {
    const echoInstance = await initializeEcho();
    echoInstance.leaveChannel(`gym-hall.${hallId}`);

    if (clubId) {
        echoInstance.leaveChannel(`club.${clubId}.gym`);
    }

    if (teamId) {
        echoInstance.leaveChannel(`team.${teamId}.gym`);
    }
};

// Batch subscribe to all gym channels for a user
export const subscribeToAllGymChannels = async (userContext, callbacks = {}) => {
    const subscriptions = [];

    // Subscribe to user's club gym updates
    if (userContext.clubId) {
        subscriptions.push(await subscribeToClubGym(userContext.clubId, callbacks.club));
    }

    // Subscribe to user's team gym updates
    if (userContext.teamIds && userContext.teamIds.length > 0) {
        for (const teamId of userContext.teamIds) {
            subscriptions.push(await subscribeToTeamGym(teamId, callbacks.team));
        }
    }

    // Subscribe to specific gym halls user has access to
    if (userContext.gymHallIds && userContext.gymHallIds.length > 0) {
        for (const hallId of userContext.gymHallIds) {
            subscriptions.push(await subscribeToGymHall(hallId, callbacks.hall));
        }
    }

    // Subscribe to private user notifications
    if (userContext.userId) {
        subscriptions.push(await subscribeToUserGymNotifications(userContext.userId, callbacks.user));
    }

    return subscriptions;
};

// Export initializeEcho for manual initialization if needed
export { initializeEcho };

// Export a getter for the echo instance (for advanced use cases)
export const getEcho = () => initializeEcho();