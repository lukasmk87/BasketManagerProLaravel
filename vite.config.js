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
                    // Video/ML Components - Only loaded on analysis pages
                    if (id.includes('/Components/Features/ML/') || id.includes('/Components/Features/Video/')) {
                        return 'video-ml';
                    }

                    // Charts (only on Stats pages)
                    if (id.includes('node_modules/chart.js')) {
                        return 'chart';
                    }

                    // Rich Text Editor (only on Editor pages)
                    if (id.includes('node_modules/@tiptap/') || id.includes('node_modules/prosemirror')) {
                        return 'editor';
                    }

                    // Stripe (only on Checkout pages)
                    if (id.includes('node_modules/@stripe/')) {
                        return 'stripe';
                    }

                    // Real-time (only for Live features)
                    if (id.includes('node_modules/laravel-echo') || id.includes('node_modules/pusher-js')) {
                        return 'realtime';
                    }

                    // Konva/TacticBoard
                    if (id.includes('node_modules/konva') || id.includes('node_modules/vue-konva')) {
                        return 'konva';
                    }

                    // Icons
                    if (id.includes('node_modules/@heroicons/')) {
                        return 'icons';
                    }

                    // VueUse Utilities
                    if (id.includes('node_modules/@vueuse/')) {
                        return 'vueuse';
                    }

                    // Date utilities
                    if (id.includes('node_modules/date-fns')) {
                        return 'date-utils';
                    }

                    // GIF Export (TacticBoard)
                    if (id.includes('node_modules/gif.js')) {
                        return 'gif';
                    }

                    // Vendor Core - nur essenzielle Libraries
                    if (id.includes('node_modules/vue') ||
                        id.includes('node_modules/@inertiajs') ||
                        id.includes('node_modules/@vue') ||
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
