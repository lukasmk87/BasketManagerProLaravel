import './bootstrap';
import './echo';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import { router } from '@inertiajs/vue3';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Configure router to use axios with CSRF handling and add request interceptor
router.on('before', (event) => {
    // Ensure the CSRF token is fresh before each navigation
    const token = document.head.querySelector('meta[name="csrf-token"]');
    if (token && window.axios.defaults.headers.common['X-CSRF-TOKEN'] !== token.content) {
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
        console.log('Updated CSRF token for navigation');
    }
});

// Refresh CSRF token when the page becomes visible (user returns to tab)
document.addEventListener('visibilitychange', () => {
    if (!document.hidden && window.refreshCsrfTokenAndMeta) {
        console.log('Page became visible, refreshing CSRF token...');
        window.refreshCsrfTokenAndMeta().catch(error => {
            console.warn('Failed to refresh CSRF token on visibility change:', error);
        });
    }
});

// Refresh CSRF token periodically (every 30 minutes)
setInterval(() => {
    if (window.refreshCsrfTokenAndMeta) {
        console.log('Periodic CSRF token refresh...');
        window.refreshCsrfTokenAndMeta().catch(error => {
            console.warn('Failed to refresh CSRF token periodically:', error);
        });
    }
}, 30 * 60 * 1000); // 30 minutes

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
