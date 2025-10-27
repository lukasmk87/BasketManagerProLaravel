<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    pages: Array,
});

const getSlugDisplayName = (slug) => {
    const slugMap = {
        'privacy': 'Datenschutz',
        'terms': 'AGB',
        'imprint': 'Impressum',
        'gdpr': 'GDPR',
    };
    return slugMap[slug] || slug;
};

const getSlugUrl = (slug) => {
    const urlMap = {
        'privacy': '/datenschutz',
        'terms': '/agb',
        'imprint': '/impressum',
        'gdpr': '/gdpr',
    };
    return urlMap[slug] || `/${slug}`;
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString('de-DE');
};
</script>

<template>
    <AdminLayout title="Rechtliche Seiten verwalten">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Rechtliche Seiten verwalten
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Bearbeiten Sie Datenschutzerklärung, AGB, Impressum und GDPR-Informationen
                    </p>
                </div>

                <div class="flex items-center space-x-3">
                    <SecondaryButton :href="route('admin.settings')" as="Link">
                        Zurück zum Admin Panel
                    </SecondaryButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Legal Pages List -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-6">
                            Verfügbare Seiten
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div
                                v-for="page in pages"
                                :key="page.id"
                                class="bg-gray-50 rounded-lg p-6 hover:bg-gray-100 transition-colors"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h4 class="text-lg font-semibold text-gray-900 mb-2">
                                            {{ getSlugDisplayName(page.slug) }}
                                        </h4>
                                        <p class="text-sm text-gray-600 mb-3">
                                            {{ page.title }}
                                        </p>

                                        <!-- Status Badge -->
                                        <div class="mb-3">
                                            <span v-if="page.is_published" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                </svg>
                                                Veröffentlicht
                                            </span>
                                            <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                </svg>
                                                Entwurf
                                            </span>
                                        </div>

                                        <!-- Meta Info -->
                                        <div class="text-xs text-gray-500 mb-4">
                                            <p>Zuletzt aktualisiert: {{ formatDate(page.updated_at) }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center space-x-3 mt-4">
                                    <PrimaryButton
                                        :href="route('admin.legal-pages.edit', page.slug)"
                                        as="Link"
                                        class="text-sm"
                                    >
                                        Bearbeiten
                                    </PrimaryButton>

                                    <a
                                        :href="getSlugUrl(page.slug)"
                                        target="_blank"
                                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150"
                                    >
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                        </svg>
                                        Ansehen
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Help Section -->
                <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">
                                Hinweis
                            </h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <p>
                                    Bitte stellen Sie sicher, dass alle rechtlichen Seiten aktuell und rechtlich korrekt sind.
                                    Bei Änderungen an der Datenschutzerklärung oder den AGB sollten Nutzer entsprechend informiert werden.
                                </p>
                                <p class="mt-2">
                                    Die Platzhalter in den Texten (z.B. "[Ihre Adresse]") sollten durch die tatsächlichen Unternehmensdaten ersetzt werden.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
