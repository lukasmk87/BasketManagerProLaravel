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

// Enhanced CSRF token handling with Inertia integration
router.on('before', (event) => {
    // Ensure CSRF token is available and up to date
    ensureTokenForAxios();
});

// Handle 419 errors globally for Inertia requests
router.on('error', async (errors) => {
    // Check if any errors are 419 (CSRF token mismatch)
    const has419Error = Object.values(errors.response?.data?.errors || {}).some(error => 
        error.some(msg => msg.includes('CSRF') || msg.includes('419'))
    ) || errors.response?.status === 419;
    
    if (has419Error) {
        console.warn('CSRF token error detected, attempting to refresh...');
        try {
            const newToken = await handle419Error();
            if (newToken) {
                console.log('CSRF token refreshed, retrying request...');
                // The token has been updated, Inertia will automatically retry
                return;
            }
        } catch (error) {
            console.error('Failed to refresh CSRF token:', error);
        }
    }
});

// Update tokens when page props change
router.on('success', (page) => {
    if (page.props?.csrf_token) {
        updateToken(page.props.csrf_token);
    }
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
