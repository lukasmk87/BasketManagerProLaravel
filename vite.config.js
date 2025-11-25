import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

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
    ],
    // PERF-005: Code-Splitting for smaller initial bundle
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    // Charts (only on Stats pages)
                    'chart': ['chart.js'],

                    // Rich Text Editor (only on Editor pages)
                    'editor': [
                        '@tiptap/starter-kit',
                        '@tiptap/vue-3',
                        '@tiptap/extension-link',
                        '@tiptap/extension-image',
                        '@tiptap/extension-placeholder',
                        '@tiptap/extension-text-align',
                        '@tiptap/extension-underline',
                        '@tiptap/extension-character-count',
                    ],

                    // Stripe (only on Checkout pages)
                    'stripe': ['@stripe/stripe-js'],

                    // Real-time (only for Live features)
                    'realtime': ['laravel-echo', 'pusher-js'],

                    // Drag & Drop (only Team Management)
                    'dragdrop': ['sortablejs', 'vue-draggable-plus'],

                    // Vendor Core
                    'vendor': ['vue', '@inertiajs/vue3', 'axios'],
                },
            },
        },
    },
});
