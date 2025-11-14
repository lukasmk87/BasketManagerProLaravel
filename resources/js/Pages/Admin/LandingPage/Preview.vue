<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    section: String,
    label: String,
    content: Object,
    current_locale: {
        type: String,
        default: 'de'
    },
    is_preview: {
        type: Boolean,
        default: true
    },
});

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

const hasContent = computed(() => {
    return props.content && Object.keys(props.content).length > 0;
});

const renderContent = computed(() => {
    if (!hasContent.value) {
        return null;
    }
    return props.content;
});
</script>

<template>
    <AdminLayout title="Landing Page Vorschau">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getSectionIcon(section)" />
                    </svg>
                    <div>
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                            Vorschau: {{ label }}
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">
                            Preview-Modus - Schreibgeschützte Ansicht
                        </p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <span
                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold"
                        :class="current_locale === 'de' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'"
                    >
                        {{ current_locale.toUpperCase() }}
                    </span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                        </svg>
                        Vorschau
                    </span>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Action Bar -->
                <div class="mb-6 flex justify-between items-center">
                    <div class="flex space-x-3">
                        <SecondaryButton
                            :href="route('admin.landing-page.edit', { section: section, locale: current_locale })"
                            as="Link"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Zurück zur Bearbeitung
                        </SecondaryButton>
                        <SecondaryButton
                            :href="route('admin.landing-page.index', { locale: current_locale })"
                            as="Link"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            Zurück zur Übersicht
                        </SecondaryButton>
                    </div>
                </div>

                <!-- Preview Content -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <!-- Preview Header -->
                    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-white">
                                Preview: {{ label }}
                            </h3>
                            <span class="text-sm text-indigo-100">
                                Locale: {{ current_locale.toUpperCase() }}
                            </span>
                        </div>
                    </div>

                    <!-- Content Display -->
                    <div class="p-6">
                        <div v-if="!hasContent" class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Keine Inhalte vorhanden</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Für diese Sektion wurden noch keine Inhalte erstellt.
                            </p>
                            <div class="mt-6">
                                <PrimaryButton
                                    :href="route('admin.landing-page.edit', { section: section, locale: current_locale })"
                                    as="Link"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Inhalte erstellen
                                </PrimaryButton>
                            </div>
                        </div>

                        <!-- Hero Section Preview -->
                        <div v-else-if="section === 'hero'" class="space-y-6">
                            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-8 rounded-lg">
                                <h1 class="text-4xl font-bold text-gray-900 mb-4">
                                    {{ renderContent.headline || 'Headline fehlt' }}
                                </h1>
                                <p class="text-xl text-gray-700 mb-6">
                                    {{ renderContent.subheadline || 'Subheadline fehlt' }}
                                </p>
                                <div class="flex space-x-4">
                                    <div v-if="renderContent.cta_primary_text" class="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg shadow-md">
                                        {{ renderContent.cta_primary_text }}
                                    </div>
                                    <div v-if="renderContent.cta_secondary_text" class="px-6 py-3 bg-white text-indigo-600 font-semibold rounded-lg shadow-md border-2 border-indigo-600">
                                        {{ renderContent.cta_secondary_text }}
                                    </div>
                                </div>
                                <div v-if="renderContent.stats" class="mt-8 grid grid-cols-3 gap-4">
                                    <div v-for="(stat, key) in renderContent.stats" :key="key" class="text-center">
                                        <div class="text-2xl font-bold text-indigo-600">{{ stat.value }}</div>
                                        <div class="text-sm text-gray-600">{{ stat.label }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Features Section Preview -->
                        <div v-else-if="section === 'features'" class="space-y-6">
                            <div class="text-center mb-8">
                                <h2 class="text-3xl font-bold text-gray-900 mb-3">
                                    {{ renderContent.headline || 'Headline fehlt' }}
                                </h2>
                                <p v-if="renderContent.subheadline" class="text-lg text-gray-600">
                                    {{ renderContent.subheadline }}
                                </p>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div v-for="(item, index) in renderContent.items" :key="index" class="p-6 bg-gray-50 rounded-lg">
                                    <div class="text-indigo-600 text-3xl mb-4">
                                        {{ item.icon || '📌' }}
                                    </div>
                                    <h3 class="text-xl font-semibold text-gray-900 mb-2">
                                        {{ item.title }}
                                    </h3>
                                    <p class="text-gray-600">
                                        {{ item.description }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Pricing Section Preview -->
                        <div v-else-if="section === 'pricing'" class="space-y-6">
                            <div class="text-center mb-8">
                                <h2 class="text-3xl font-bold text-gray-900 mb-3">
                                    {{ renderContent.headline || 'Headline fehlt' }}
                                </h2>
                                <p v-if="renderContent.subheadline" class="text-lg text-gray-600">
                                    {{ renderContent.subheadline }}
                                </p>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div
                                    v-for="(plan, index) in renderContent.items"
                                    :key="index"
                                    class="relative p-6 bg-white border rounded-lg shadow-lg"
                                    :class="{ 'ring-2 ring-indigo-500': plan.popular }"
                                >
                                    <div v-if="plan.popular" class="absolute top-0 right-0 bg-indigo-500 text-white text-xs font-bold px-3 py-1 rounded-bl-lg rounded-tr-lg">
                                        BELIEBT
                                    </div>
                                    <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ plan.name }}</h3>
                                    <div class="mb-4">
                                        <span class="text-4xl font-bold text-gray-900">{{ plan.price }}</span>
                                        <span v-if="plan.period" class="text-gray-600">/ {{ plan.period }}</span>
                                    </div>
                                    <p v-if="plan.description" class="text-gray-600 mb-4">{{ plan.description }}</p>
                                    <ul class="space-y-2 mb-6">
                                        <li v-for="(feature, fIndex) in plan.features" :key="fIndex" class="flex items-start">
                                            <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                            <span class="text-gray-700">{{ feature }}</span>
                                        </li>
                                    </ul>
                                    <button class="w-full px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700">
                                        {{ plan.cta_text }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- FAQ Section Preview -->
                        <div v-else-if="section === 'faq'" class="space-y-6">
                            <div class="text-center mb-8">
                                <h2 class="text-3xl font-bold text-gray-900">
                                    {{ renderContent.headline || 'FAQ' }}
                                </h2>
                            </div>
                            <div class="space-y-4 max-w-3xl mx-auto">
                                <div v-for="(item, index) in renderContent.items" :key="index" class="bg-gray-50 rounded-lg p-6">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                        {{ item.question }}
                                    </h3>
                                    <p class="text-gray-700">
                                        {{ item.answer }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Testimonials Section Preview -->
                        <div v-else-if="section === 'testimonials'" class="space-y-6">
                            <div class="text-center mb-8">
                                <h2 class="text-3xl font-bold text-gray-900">
                                    {{ renderContent.headline || 'Testimonials' }}
                                </h2>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div v-for="(testimonial, index) in renderContent.items" :key="index" class="bg-white p-6 rounded-lg shadow-md">
                                    <div class="flex items-center mb-4">
                                        <div v-if="testimonial.image" class="w-12 h-12 rounded-full bg-gray-300 mr-4"></div>
                                        <div>
                                            <h4 class="font-semibold text-gray-900">{{ testimonial.name }}</h4>
                                            <p class="text-sm text-gray-600">{{ testimonial.role }}</p>
                                            <p class="text-sm text-gray-500">{{ testimonial.club }}</p>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <span v-for="i in testimonial.rating" :key="i" class="text-yellow-400">★</span>
                                    </div>
                                    <p class="text-gray-700 italic">"{{ testimonial.quote }}"</p>
                                </div>
                            </div>
                        </div>

                        <!-- CTA Section Preview -->
                        <div v-else-if="section === 'cta'" class="space-y-6">
                            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 p-12 rounded-lg text-center text-white">
                                <h2 class="text-4xl font-bold mb-4">
                                    {{ renderContent.headline || 'CTA Headline' }}
                                </h2>
                                <p v-if="renderContent.subheadline" class="text-xl mb-8 text-indigo-100">
                                    {{ renderContent.subheadline }}
                                </p>
                                <div class="flex justify-center space-x-4">
                                    <button v-if="renderContent.cta_primary_text" class="px-8 py-4 bg-white text-indigo-600 font-bold rounded-lg shadow-lg hover:bg-gray-100">
                                        {{ renderContent.cta_primary_text }}
                                    </button>
                                    <button v-if="renderContent.cta_secondary_text" class="px-8 py-4 bg-transparent border-2 border-white text-white font-bold rounded-lg hover:bg-white hover:text-indigo-600">
                                        {{ renderContent.cta_secondary_text }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- SEO Section Preview -->
                        <div v-else-if="section === 'seo'" class="space-y-6">
                            <div class="bg-blue-50 p-6 rounded-lg">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">SEO Metadaten</h3>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Meta Title</label>
                                        <div class="p-3 bg-white rounded border border-gray-300">
                                            {{ renderContent.title || 'Kein Title definiert' }}
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">Länge: {{ (renderContent.title || '').length }}/60 Zeichen</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Meta Description</label>
                                        <div class="p-3 bg-white rounded border border-gray-300">
                                            {{ renderContent.description || 'Keine Description definiert' }}
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">Länge: {{ (renderContent.description || '').length }}/160 Zeichen</p>
                                    </div>
                                    <div v-if="renderContent.keywords">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Keywords</label>
                                        <div class="p-3 bg-white rounded border border-gray-300">
                                            {{ renderContent.keywords }}
                                        </div>
                                    </div>
                                    <div v-if="renderContent.og_image">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Open Graph Image</label>
                                        <div class="p-3 bg-white rounded border border-gray-300">
                                            <img :src="renderContent.og_image" alt="OG Image" class="max-w-xs rounded shadow">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Section Preview -->
                        <div v-else-if="section === 'footer'" class="space-y-6">
                            <div class="bg-gray-900 text-white p-8 rounded-lg">
                                <h3 class="text-xl font-bold mb-6">Footer Inhalte</h3>
                                <pre class="bg-gray-800 p-4 rounded text-sm overflow-auto">{{ JSON.stringify(renderContent, null, 2) }}</pre>
                            </div>
                        </div>

                        <!-- Generic Section Preview (Fallback) -->
                        <div v-else class="space-y-4">
                            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-yellow-700">
                                            Für diese Sektion ist noch keine spezielle Preview verfügbar. Hier sehen Sie die Rohdaten:
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 p-6 rounded-lg">
                                <pre class="text-sm overflow-auto">{{ JSON.stringify(renderContent, null, 2) }}</pre>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Bar Bottom -->
                <div class="mt-6 flex justify-between items-center">
                    <SecondaryButton
                        :href="route('admin.landing-page.index', { locale: current_locale })"
                        as="Link"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Zurück zur Übersicht
                    </SecondaryButton>
                    <PrimaryButton
                        :href="route('admin.landing-page.edit', { section: section, locale: current_locale })"
                        as="Link"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Bearbeiten
                    </PrimaryButton>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
