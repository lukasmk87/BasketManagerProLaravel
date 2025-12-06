<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import LandingPageLocaleSwitcher from '@/Components/Landing/LandingPageLocaleSwitcher.vue';

const props = defineProps({
    sections: Array,
    tenant_id: String,
    current_locale: {
        type: String,
        default: 'de'
    },
    available_locales: {
        type: Array,
        default: () => ['de', 'en']
    },
    is_super_admin: Boolean,
});

const formatDate = (dateString) => {
    if (!dateString) return 'Noch nicht aktualisiert';
    return new Date(dateString).toLocaleString('de-DE');
};

const currentLocale = ref(props.current_locale);

const switchLocale = (locale) => {
    router.get(route('admin.enterprise-page.index', { locale }), {}, {
        preserveState: true,
        preserveScroll: true,
    });
};

const publishSection = (section) => {
    if (confirm(`Möchten Sie die Änderungen für "${section.label}" wirklich veröffentlichen?`)) {
        router.post(route('admin.enterprise-page.publish', section.section), {
            locale: currentLocale.value
        });
    }
};

const unpublishSection = (section) => {
    if (confirm(`Möchten Sie die Veröffentlichung für "${section.label}" wirklich rückgängig machen?`)) {
        router.post(route('admin.enterprise-page.unpublish', section.section), {
            locale: currentLocale.value
        });
    }
};

const getLocaleStatusClass = (status) => {
    switch (status) {
        case 'published':
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
        case 'draft':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400';
        default:
            return 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400';
    }
};

const getLocaleStatusText = (status) => {
    switch (status) {
        case 'published':
            return 'Veröffentlicht';
        case 'draft':
            return 'Entwurf';
        default:
            return 'Nicht konfiguriert';
    }
};
</script>

<template>
    <AdminLayout title="Enterprise Seite verwalten">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        Enterprise Seite verwalten
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Bearbeiten Sie die Inhalte Ihrer Enterprise Marketing-Seite
                        <span v-if="is_super_admin" class="text-blue-600 dark:text-blue-400 font-medium">(Globale Einstellungen)</span>
                    </p>
                </div>

                <div class="flex items-center space-x-4">
                    <LandingPageLocaleSwitcher
                        v-model="currentLocale"
                        @change="switchLocale"
                        :show-label="true"
                    />

                    <SecondaryButton :href="route('admin.dashboard')" as="Link">
                        Zurück zum Dashboard
                    </SecondaryButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Enterprise Page Sections -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 mb-6">
                            Enterprise Seite Bereiche
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div
                                v-for="section in sections"
                                :key="section.section"
                                class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors border-2"
                                :class="section.is_published ? 'border-green-300 dark:border-green-600' : 'border-gray-200 dark:border-gray-600'"
                            >
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex items-center space-x-3">
                                        <!-- Icon -->
                                        <div class="flex-shrink-0">
                                            <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="section.icon"></path>
                                            </svg>
                                        </div>

                                        <!-- Title -->
                                        <div class="flex-1">
                                            <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                                {{ section.label }}
                                            </h4>
                                        </div>
                                    </div>
                                </div>

                                <!-- Description -->
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                    {{ section.description }}
                                </p>

                                <!-- Status Badges (Multi-Locale) -->
                                <div class="mb-4 space-y-2">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 w-8">DE:</span>
                                        <span
                                            :class="[
                                                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                                getLocaleStatusClass(section.locale_status?.de)
                                            ]"
                                        >
                                            {{ getLocaleStatusText(section.locale_status?.de) }}
                                        </span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 w-8">EN:</span>
                                        <span
                                            :class="[
                                                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                                getLocaleStatusClass(section.locale_status?.en)
                                            ]"
                                        >
                                            {{ getLocaleStatusText(section.locale_status?.en) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Meta Info -->
                                <div class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                                    <p v-if="section.published_at">Veröffentlicht: {{ formatDate(section.published_at) }}</p>
                                    <p>Aktualisiert: {{ formatDate(section.updated_at) }}</p>
                                </div>

                                <!-- Actions -->
                                <div class="flex flex-col space-y-2">
                                    <PrimaryButton
                                        :href="route('admin.enterprise-page.edit', { section: section.section, locale: currentLocale })"
                                        as="Link"
                                        class="text-sm w-full justify-center"
                                    >
                                        Bearbeiten ({{ currentLocale.toUpperCase() }})
                                    </PrimaryButton>

                                    <div class="flex space-x-2">
                                        <button
                                            v-if="section.has_content && !section.is_published"
                                            @click="publishSection(section)"
                                            class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                        >
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            </svg>
                                            Veröffentlichen
                                        </button>

                                        <button
                                            v-if="section.is_published"
                                            @click="unpublishSection(section)"
                                            class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                        >
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            Zurückziehen
                                        </button>

                                        <a
                                            :href="route('admin.enterprise-page.preview', { section: section.section, locale: currentLocale })"
                                            target="_blank"
                                            class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                        >
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                            Vorschau
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Help Section -->
                <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800 dark:text-blue-300">
                                Hinweis zur Enterprise-Seite
                            </h3>
                            <div class="mt-2 text-sm text-blue-700 dark:text-blue-400 space-y-2">
                                <p>
                                    <strong>Draft → Vorschau → Veröffentlichen:</strong> Bearbeiten Sie Inhalte, prüfen Sie die Vorschau und veröffentlichen Sie erst dann, wenn alles korrekt ist.
                                </p>
                                <p>
                                    <strong>10 Bereiche:</strong> SEO, Hero, Zielgruppen, White-Label, Multi-Club, Verbandsintegration, Erfolgsgeschichten, Preise, FAQ, Kontakt
                                </p>
                                <p>
                                    <strong>Mehrsprachigkeit:</strong> Alle Inhalte können in Deutsch und Englisch gepflegt werden.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
