import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Determine the broadcasting driver from environment
const broadcastDriver = import.meta.env.VITE_BROADCAST_DRIVER || 'log';

let echoConfig = {};

if (broadcastDriver === 'pusher') {
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
    // Laravel Reverb configuration
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
const echo = new Echo(echoConfig);

// Export for global use
window.Echo = echo;

// Basketball-specific channel helpers
export const subscribeToGame = (gameId, callbacks = {}) => {
    const channel = echo.channel(`game.${gameId}`)
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

export const subscribeToLiveGames = (callbacks = {}) => {
    return echo.channel('live-games')
        .listen('GameStarted', callbacks.onGameStarted || (() => {}))
        .listen('GameFinished', callbacks.onGameFinished || (() => {}))
        .listen('GameScoreUpdated', callbacks.onScoreUpdated || (() => {}));
};

export const subscribeToTeam = (teamId, callbacks = {}) => {
    return echo.channel(`team.${teamId}`)
        .listen('GameStarted', callbacks.onGameStarted || (() => {}))
        .listen('GameFinished', callbacks.onGameFinished || (() => {}))
        .listen('GameScoreUpdated', callbacks.onScoreUpdated || (() => {}));
};

export const subscribeToCLub = (clubId, callbacks = {}) => {
    return echo.channel(`club.${clubId}`)
        .listen('GameStarted', callbacks.onGameStarted || (() => {}))
        .listen('GameFinished', callbacks.onGameFinished || (() => {}));
};

// Private channel for authenticated users
export const subscribeToUser = (userId, callbacks = {}) => {
    return echo.private(`App.Models.User.${userId}`)
        .notification(callbacks.onNotification || (() => {}));
};

// Helper function to leave all game-related channels
export const leaveGameChannels = (gameId) => {
    echo.leaveChannel(`game.${gameId}`);
};

// Gym Management channel helpers
export const subscribeToGymHall = (gymHallId, callbacks = {}) => {
    const channel = echo.channel(`gym-hall.${gymHallId}`)
        .listen('TimeSlotReleased', callbacks.onTimeSlotReleased || (() => {}))
        .listen('BookingRequested', callbacks.onBookingRequested || (() => {}))
        .listen('BookingConfirmed', callbacks.onBookingConfirmed || (() => {}))
        .listen('BookingCancelled', callbacks.onBookingCancelled || (() => {}))
        .listen('ScheduleUpdated', callbacks.onScheduleUpdated || (() => {}));
    
    return channel;
};

export const subscribeToClubGym = (clubId, callbacks = {}) => {
    return echo.channel(`club.${clubId}.gym`)
        .listen('GymTimeReleased', callbacks.onTimeReleased || (() => {}))
        .listen('GymBookingRequested', callbacks.onBookingRequested || (() => {}))
        .listen('GymBookingApproved', callbacks.onBookingApproved || (() => {}))
        .listen('GymBookingRejected', callbacks.onBookingRejected || (() => {}))
        .listen('GymScheduleUpdated', callbacks.onScheduleUpdated || (() => {}));
};

export const subscribeToTeamGym = (teamId, callbacks = {}) => {
    return echo.channel(`team.${teamId}.gym`)
        .listen('BookingReleased', callbacks.onBookingReleased || (() => {}))
        .listen('RequestApproved', callbacks.onRequestApproved || (() => {}))
        .listen('RequestRejected', callbacks.onRequestRejected || (() => {}))
        .listen('BookingReminder', callbacks.onBookingReminder || (() => {}))
        .listen('ConflictDetected', callbacks.onConflictDetected || (() => {}));
};

// Private user notifications for gym events
export const subscribeToUserGymNotifications = (userId, callbacks = {}) => {
    return echo.private(`App.Models.User.${userId}`)
        .notification((notification) => {
            if (notification.type && notification.type.includes('Gym')) {
                (callbacks.onGymNotification || (() => {}))(notification);
            }
            (callbacks.onNotification || (() => {}))(notification);
        });
};

// Helper function to leave gym-related channels
export const leaveGymChannels = (hallId, clubId = null, teamId = null) => {
    echo.leaveChannel(`gym-hall.${hallId}`);
    
    if (clubId) {
        echo.leaveChannel(`club.${clubId}.gym`);
    }
    
    if (teamId) {
        echo.leaveChannel(`team.${teamId}.gym`);
    }
};

// Batch subscribe to all gym channels for a user
export const subscribeToAllGymChannels = (userContext, callbacks = {}) => {
    const subscriptions = [];
    
    // Subscribe to user's club gym updates
    if (userContext.clubId) {
        subscriptions.push(subscribeToClubGym(userContext.clubId, callbacks.club));
    }
    
    // Subscribe to user's team gym updates
    if (userContext.teamIds && userContext.teamIds.length > 0) {
        userContext.teamIds.forEach(teamId => {
            subscriptions.push(subscribeToTeamGym(teamId, callbacks.team));
        });
    }
    
    // Subscribe to specific gym halls user has access to
    if (userContext.gymHallIds && userContext.gymHallIds.length > 0) {
        userContext.gymHallIds.forEach(hallId => {
            subscriptions.push(subscribeToGymHall(hallId, callbacks.hall));
        });
    }
    
    // Subscribe to private user notifications
    if (userContext.userId) {
        subscriptions.push(subscribeToUserGymNotifications(userContext.userId, callbacks.user));
    }
    
    return subscriptions;
};

export default echo;