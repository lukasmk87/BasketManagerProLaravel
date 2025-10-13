<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';

const props = defineProps({
    page: Object,
});

const form = useForm({
    title: props.page.title,
    content: props.page.content,
    meta_description: props.page.meta_description,
    is_published: props.page.is_published,
});

const updatePage = () => {
    form.put(route('admin.legal-pages.update', props.page.slug), {
        preserveScroll: true,
        onSuccess: () => {
            // Success handled by redirect
        },
    });
};

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
</script>

<template>
    <AppLayout :title="`${getSlugDisplayName(page.slug)} bearbeiten`">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        {{ getSlugDisplayName(page.slug) }} bearbeiten
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        {{ page.title }}
                    </p>
                </div>

                <div class="flex items-center space-x-3">
                    <a
                        :href="getSlugUrl(page.slug)"
                        target="_blank"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                        Vorschau
                    </a>

                    <SecondaryButton :href="route('admin.legal-pages.index')" as="Link">
                        Zurück zur Übersicht
                    </SecondaryButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="updatePage">
                    <!-- Main Content Card -->
                    <div class="bg-white overflow-hidden shadow rounded-lg mb-6">
                        <div class="p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-6">
                                Seiteninformationen
                            </h3>

                            <div class="space-y-6">
                                <!-- Title -->
                                <div>
                                    <InputLabel for="title" value="Seitentitel" />
                                    <TextInput
                                        id="title"
                                        v-model="form.title"
                                        type="text"
                                        class="mt-1 block w-full"
                                        required
                                    />
                                    <InputError :message="form.errors.title" class="mt-2" />
                                </div>

                                <!-- Meta Description -->
                                <div>
                                    <InputLabel for="meta_description" value="Meta-Beschreibung (SEO)" />
                                    <textarea
                                        id="meta_description"
                                        v-model="form.meta_description"
                                        rows="2"
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                        placeholder="Kurze Beschreibung für Suchmaschinen (max. 160 Zeichen)"
                                        maxlength="500"
                                    ></textarea>
                                    <InputError :message="form.errors.meta_description" class="mt-2" />
                                    <p class="mt-1 text-sm text-gray-500">
                                        {{ form.meta_description?.length || 0 }} / 500 Zeichen
                                    </p>
                                </div>

                                <!-- Content -->
                                <div>
                                    <InputLabel for="content" value="Seiteninhalt (HTML)" />
                                    <textarea
                                        id="content"
                                        v-model="form.content"
                                        rows="25"
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm font-mono text-sm"
                                        required
                                    ></textarea>
                                    <InputError :message="form.errors.content" class="mt-2" />
                                    <div class="mt-2 text-sm text-gray-500 space-y-1">
                                        <p><strong>Erlaubte HTML-Tags:</strong> h2, h3, h4, p, ul, ol, li, strong, a, br</p>
                                        <p><strong>Beispiel:</strong> <code class="bg-gray-100 px-1 py-0.5 rounded">&lt;h2&gt;Überschrift&lt;/h2&gt;&lt;p&gt;Text&lt;/p&gt;</code></p>
                                    </div>
                                </div>

                                <!-- Is Published -->
                                <div class="flex items-center">
                                    <Checkbox
                                        id="is_published"
                                        v-model:checked="form.is_published"
                                    />
                                    <InputLabel for="is_published" value="Seite veröffentlichen" class="ml-2" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-600">
                            <span v-if="form.isDirty" class="text-orange-600 font-medium">
                                ⚠️ Ungespeicherte Änderungen
                            </span>
                        </div>

                        <div class="flex items-center space-x-3">
                            <SecondaryButton
                                :href="route('admin.legal-pages.index')"
                                as="Link"
                                type="button"
                            >
                                Abbrechen
                            </SecondaryButton>

                            <PrimaryButton
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                            >
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Änderungen speichern
                            </PrimaryButton>
                        </div>
                    </div>
                </form>

                <!-- Info Box -->
                <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">
                                Wichtiger Hinweis
                            </h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <ul class="list-disc list-inside space-y-1">
                                    <li>Änderungen an rechtlichen Seiten sollten von einem Juristen geprüft werden</li>
                                    <li>Bei Änderungen an Datenschutz oder AGB sollten Nutzer informiert werden</li>
                                    <li>Stellen Sie sicher, dass alle Platzhalter durch echte Daten ersetzt sind</li>
                                    <li>Verwenden Sie die Vorschau-Funktion, um Änderungen zu überprüfen</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
