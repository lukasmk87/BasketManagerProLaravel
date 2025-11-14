<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import LandingPageLocaleSwitcher from '@/Components/Landing/LandingPageLocaleSwitcher.vue';

const props = defineProps({
    sections: Array,
    tenant_id: Number,
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

const getSectionIcon = (section) => {
    const icons = {
        'hero': 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
        'features': 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        'pricing': 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'testimonials': 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
        'faq': 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'cta': 'M13 10V3L4 14h7v7l9-11h-7z',
        'footer': 'M19 9l-7 7-7-7',
        'seo': 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
    };
    return icons[section] || 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01';
};

const currentLocale = ref(props.current_locale);

const switchLocale = (locale) => {
    router.get(route('admin.landing-page.index', { locale }), {}, {
        preserveState: true,
        preserveScroll: true,
    });
};

const publishSection = (section) => {
    if (confirm(`Möchten Sie die Änderungen für "${section.label}" wirklich veröffentlichen?`)) {
        router.post(route('admin.landing-page.publish', section.section), {
            locale: currentLocale.value
        });
    }
};

const unpublishSection = (section) => {
    if (confirm(`Möchten Sie die Veröffentlichung für "${section.label}" wirklich rückgängig machen?`)) {
        router.post(route('admin.landing-page.unpublish', section.section), {
            locale: currentLocale.value
        });
    }
};

const getLocaleStatusClass = (status) => {
    switch (status) {
        case 'published':
            return 'bg-green-100 text-green-800';
        case 'draft':
            return 'bg-yellow-100 text-yellow-800';
        default:
            return 'bg-gray-100 text-gray-600';
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
    <AdminLayout title="Landing Page verwalten">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Landing Page verwalten
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Bearbeiten Sie die Inhalte Ihrer Landing Page
                        <span v-if="is_super_admin" class="text-blue-600 font-medium">(Globale Einstellungen)</span>
                        <span v-else class="text-green-600 font-medium">(Tenant-spezifisch)</span>
                    </p>
                </div>

                <div class="flex items-center space-x-4">
                    <LandingPageLocaleSwitcher
                        v-model="currentLocale"
                        @change="switchLocale"
                        :show-label="true"
                    />

                    <SecondaryButton :href="route('admin.settings')" as="Link">
                        Zurück zum Admin Panel
                    </SecondaryButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Landing Page Sections -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-6">
                            Landing Page Bereiche
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div
                                v-for="section in sections"
                                :key="section.section"
                                class="bg-gray-50 rounded-lg p-6 hover:bg-gray-100 transition-colors border-2"
                                :class="section.is_published ? 'border-green-300' : 'border-gray-200'"
                            >
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex items-center space-x-3">
                                        <!-- Icon -->
                                        <div class="flex-shrink-0">
                                            <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getSectionIcon(section.section)"></path>
                                            </svg>
                                        </div>

                                        <!-- Title -->
                                        <div class="flex-1">
                                            <h4 class="text-lg font-semibold text-gray-900">
                                                {{ section.label }}
                                            </h4>
                                        </div>
                                    </div>
                                </div>

                                <!-- Description -->
                                <p class="text-sm text-gray-600 mb-4">
                                    {{ section.description }}
                                </p>

                                <!-- Status Badges (Multi-Locale) -->
                                <div class="mb-4 space-y-2">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-xs font-medium text-gray-500 w-8">DE:</span>
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
                                        <span class="text-xs font-medium text-gray-500 w-8">EN:</span>
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
                                <div class="text-xs text-gray-500 mb-4">
                                    <p v-if="section.published_at">Veröffentlicht: {{ formatDate(section.published_at) }}</p>
                                    <p>Aktualisiert: {{ formatDate(section.updated_at) }}</p>
                                </div>

                                <!-- Actions -->
                                <div class="flex flex-col space-y-2">
                                    <PrimaryButton
                                        :href="route('admin.landing-page.edit', { section: section.section, locale: currentLocale })"
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
                                            :href="route('admin.landing-page.preview', section.section)"
                                            target="_blank"
                                            class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
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
                <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">
                                Hinweis zum Workflow
                            </h3>
                            <div class="mt-2 text-sm text-blue-700 space-y-2">
                                <p>
                                    <strong>Draft → Vorschau → Veröffentlichen:</strong> Bearbeiten Sie Inhalte, prüfen Sie die Vorschau und veröffentlichen Sie erst dann, wenn alles korrekt ist.
                                </p>
                                <p>
                                    <strong>Tenant-Scoped Content:</strong> {{ is_super_admin ? 'Als Super Admin bearbeiten Sie globale Standard-Inhalte, die verwendet werden, wenn kein Tenant-spezifischer Content existiert.' : 'Als Club Admin bearbeiten Sie nur die Inhalte für Ihren eigenen Verein. Änderungen beeinflussen nicht andere Tenants.' }}
                                </p>
                                <p>
                                    <strong>Fallback-Hierarchie:</strong> Tenant-Content → Global-Content → Hardcoded Defaults
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
