<script setup>
import { Head } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';

defineProps({
    title: String,
});
</script>

<template>
    <div>
        <Head :title="title" />

        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            <!-- Simple Header -->
            <nav class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex items-center">
                            <ApplicationMark class="block h-9 w-auto" />
                        </div>
                        <div class="flex items-center">
                            <ThemeToggle />
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Content -->
            <main class="py-12">
                <slot />
            </main>

            <!-- Simple Footer -->
            <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-auto">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <div class="flex justify-center items-center text-sm text-gray-500 dark:text-gray-400 space-x-6">
                        <span>© {{ new Date().getFullYear() }} BasketManager Pro</span>
                        <span>•</span>
                        <a href="/impressum" class="hover:text-gray-700 dark:hover:text-gray-300">Impressum</a>
                        <span>•</span>
                        <a href="/datenschutz" class="hover:text-gray-700 dark:hover:text-gray-300">Datenschutz</a>
                    </div>
                </div>
            </footer>
        </div>
    </div>
</template>
