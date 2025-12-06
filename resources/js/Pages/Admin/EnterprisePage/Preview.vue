<script setup>
import { ref } from 'vue';

const props = defineProps({
    section: String,
    label: String,
    content: Object,
    current_locale: {
        type: String,
        default: 'de'
    },
    is_preview: Boolean,
});

const formatJson = (obj) => JSON.stringify(obj, null, 2);
</script>

<template>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        <!-- Preview Banner -->
        <div class="bg-yellow-500 text-white text-center py-2 text-sm font-medium">
            Vorschau: {{ label }} ({{ current_locale.toUpperCase() }}) - Diese Inhalte sind noch nicht veröffentlicht
        </div>

        <div class="max-w-4xl mx-auto py-8 px-4">
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        {{ label }}
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Section: {{ section }} | Locale: {{ current_locale }}
                    </p>
                </div>

                <div class="p-6">
                    <!-- Rendered Content Preview based on section type -->

                    <!-- SEO Section -->
                    <div v-if="section === 'seo'" class="space-y-4">
                        <div>
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Title</span>
                            <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ content.title }}</p>
                        </div>
                        <div>
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Description</span>
                            <p class="text-gray-700 dark:text-gray-300">{{ content.description }}</p>
                        </div>
                        <div v-if="content.keywords">
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Keywords</span>
                            <p class="text-gray-700 dark:text-gray-300">{{ content.keywords }}</p>
                        </div>
                    </div>

                    <!-- Hero Section -->
                    <div v-else-if="section === 'hero'" class="space-y-6">
                        <div class="text-center">
                            <h1 class="text-4xl font-extrabold text-gray-900 dark:text-gray-100">{{ content.headline }}</h1>
                            <p class="mt-4 text-xl text-gray-600 dark:text-gray-400">{{ content.subheadline }}</p>
                        </div>

                        <div v-if="content.stats && content.stats.length" class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8">
                            <div v-for="stat in content.stats" :key="stat.label" class="text-center p-4 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg">
                                <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ stat.value }}</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ stat.label }}</div>
                            </div>
                        </div>

                        <div class="flex justify-center space-x-4 mt-6">
                            <a :href="content.cta_primary_link" class="px-6 py-3 bg-indigo-600 text-white rounded-lg font-medium">
                                {{ content.cta_primary_text }}
                            </a>
                            <a v-if="content.cta_secondary_text" :href="content.cta_secondary_link" class="px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium">
                                {{ content.cta_secondary_text }}
                            </a>
                        </div>
                    </div>

                    <!-- FAQ Section -->
                    <div v-else-if="section === 'faq'" class="space-y-6">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ content.headline }}</h2>
                        <div class="space-y-4">
                            <div v-for="(item, index) in content.items" :key="index" class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ item.question }}</h3>
                                <p class="mt-2 text-gray-600 dark:text-gray-400">{{ item.answer }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Section -->
                    <div v-else-if="section === 'contact'" class="space-y-4 text-center">
                        <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ content.headline }}</h2>
                        <p class="text-lg text-gray-600 dark:text-gray-400">{{ content.subheadline }}</p>
                        <div class="mt-4 p-4 bg-green-50 dark:bg-green-900/30 rounded-lg">
                            <p class="text-green-700 dark:text-green-400">Erfolgsmeldung: {{ content.success_message }}</p>
                        </div>
                    </div>

                    <!-- Generic JSON Preview for other sections -->
                    <div v-else>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Raw Content</h2>
                        <pre class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg overflow-auto text-sm text-gray-800 dark:text-gray-200 font-mono">{{ formatJson(content) }}</pre>
                    </div>
                </div>
            </div>

            <!-- Back Link -->
            <div class="mt-6 text-center">
                <button onclick="window.close()" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                    Fenster schließen
                </button>
            </div>
        </div>
    </div>
</template>
