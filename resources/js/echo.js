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

export default echo;