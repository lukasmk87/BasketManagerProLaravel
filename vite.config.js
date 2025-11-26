import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { visualizer } from 'rollup-plugin-visualizer';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        // PERF-005: Bundle-Visualizer für Analyse
        visualizer({
            filename: 'bundle-stats.html',
            open: false,
            gzipSize: true,
            brotliSize: true,
        }),
    ],
    // PERF-005: Code-Splitting for smaller initial bundle
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    // Video/ML Components - Only loaded on analysis pages (~60 KB savings)
                    if (id.includes('/components/ml/') || id.includes('/components/video/')) {
                        return 'video-ml';
                    }

                    // Charts (only on Stats pages)
                    if (id.includes('node_modules/chart.js')) {
                        return 'chart';
                    }

                    // Rich Text Editor (only on Editor pages)
                    if (id.includes('node_modules/@tiptap/')) {
                        return 'editor';
                    }

                    // Stripe (only on Checkout pages)
                    if (id.includes('node_modules/@stripe/')) {
                        return 'stripe';
                    }

                    // Real-time (only for Live features) - now lazy loaded in echo.js
                    if (id.includes('node_modules/laravel-echo') || id.includes('node_modules/pusher-js')) {
                        return 'realtime';
                    }

                    // Drag & Drop (only Team Management)
                    if (id.includes('node_modules/sortablejs') || id.includes('node_modules/vue-draggable-plus')) {
                        return 'dragdrop';
                    }

                    // Vendor Core
                    if (id.includes('node_modules/vue') ||
                        id.includes('node_modules/@inertiajs') ||
                        id.includes('node_modules/axios')) {
                        return 'vendor';
                    }
                },
            },
        },
        // Increase chunk size warning for better awareness
        chunkSizeWarningLimit: 500,
    },
});
