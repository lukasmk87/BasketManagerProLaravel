import './bootstrap';
import './echo';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import { router } from '@inertiajs/vue3';
import { getCurrentToken, updateToken, handle419Error, ensureTokenForAxios } from './utils/csrf';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Track 419 retry attempts to prevent infinite loops
let csrf419RetryCount = 0;
const MAX_CSRF_RETRIES = 1;

// Enhanced CSRF token handling with Inertia integration
router.on('before', (event) => {
    // Ensure CSRF token is available and up to date
    const token = getCurrentToken();
    if (token) {
        // Explicitly set headers for Inertia requests
        event.detail.visit.headers = {
            ...event.detail.visit.headers,
            'X-CSRF-TOKEN': token,
        };
    }
    ensureTokenForAxios();
});

// Handle 419 errors globally for Inertia requests with retry limit
router.on('error', async (event) => {
    const response = event.detail?.response;

    // Check if error is 419 (CSRF token mismatch)
    if (response?.status === 419) {
        console.warn(`CSRF token error detected (attempt ${csrf419RetryCount + 1}/${MAX_CSRF_RETRIES})`);

        // Check if we've exceeded max retries
        if (csrf419RetryCount >= MAX_CSRF_RETRIES) {
            console.error('Max CSRF retry attempts reached. Please refresh the page.');
            csrf419RetryCount = 0; // Reset for next time

            // Show user-friendly error message
            alert('Ihre Sitzung ist abgelaufen. Die Seite wird neu geladen.');
            window.location.reload();
            return;
        }

        csrf419RetryCount++;

        try {
            const newToken = await handle419Error();
            if (newToken) {
                console.log('CSRF token refreshed successfully, please retry your action.');
                // Reset retry count on success
                csrf419RetryCount = 0;

                // Don't auto-retry, let the user manually retry
                // This prevents accidental duplicate submissions
                return;
            }
        } catch (error) {
            console.error('Failed to refresh CSRF token:', error);
            csrf419RetryCount = 0;

            // Fallback to page reload
            if (confirm('Fehler beim Aktualisieren des Sicherheitstokens. Seite neu laden?')) {
                window.location.reload();
            }
        }
    } else {
        // Reset retry count for non-419 errors
        csrf419RetryCount = 0;
    }
});

// Update tokens when page props change and reset retry counter
router.on('success', (event) => {
    const page = event.detail?.page;
    if (page?.props?.csrf_token) {
        updateToken(page.props.csrf_token);
    }
    // Reset retry count on successful request
    csrf419RetryCount = 0;
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
