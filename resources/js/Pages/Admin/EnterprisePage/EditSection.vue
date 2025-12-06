<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import LandingPageLocaleSwitcher from '@/Components/Landing/LandingPageLocaleSwitcher.vue';

const props = defineProps({
    section: String,
    label: String,
    description: String,
    icon: String,
    content: Object,
    is_published: Boolean,
    published_at: String,
    tenant_id: String,
    current_locale: {
        type: String,
        default: 'de'
    },
    available_locales: {
        type: Array,
        default: () => ['de', 'en']
    },
    other_locale_has_content: {
        type: Boolean,
        default: false
    },
    schema: Object,
});

const form = useForm({
    content: props.content || {},
    locale: props.current_locale,
});

const currentLocale = ref(props.current_locale);
const hasUnsavedChanges = ref(false);

watch(
    () => form.data(),
    () => {
        hasUnsavedChanges.value = form.isDirty;
    },
    { deep: true }
);

const saveContent = () => {
    form.put(route('admin.enterprise-page.update', props.section), {
        preserveScroll: true,
        onSuccess: () => {
            hasUnsavedChanges.value = false;
        },
    });
};

const switchLocale = (locale) => {
    if (hasUnsavedChanges.value) {
        if (!confirm('Sie haben ungespeicherte Änderungen. Möchten Sie trotzdem die Sprache wechseln?')) {
            currentLocale.value = props.current_locale;
            return;
        }
    }

    router.get(route('admin.enterprise-page.edit', { section: props.section, locale }), {}, {
        preserveState: false,
    });
};

const copyFromLocale = () => {
    const fromLocale = currentLocale.value === 'de' ? 'en' : 'de';
    const toLocale = currentLocale.value;

    if (confirm(`Möchten Sie die Inhalte von ${fromLocale.toUpperCase()} nach ${toLocale.toUpperCase()} kopieren? Bestehende Inhalte werden überschrieben.`)) {
        router.post(route('admin.enterprise-page.copy-locale', props.section), {
            from_locale: fromLocale,
            to_locale: toLocale,
            overwrite: true,
        });
    }
};

const publishContent = () => {
    if (confirm(`Möchten Sie die Änderungen wirklich veröffentlichen?`)) {
        form.transform(() => ({
            ...form.data(),
            _method: 'POST',
        })).post(route('admin.enterprise-page.publish', props.section));
    }
};

const characterCount = (field, maxLength) => {
    const value = form.content[field] || '';
    const count = typeof value === 'string' ? value.length : 0;
    return `${count} / ${maxLength} Zeichen`;
};

const isOverLimit = (field, maxLength) => {
    const value = form.content[field] || '';
    return typeof value === 'string' && value.length > maxLength;
};

// Section types
const isSeoSection = computed(() => props.section === 'seo');
const isHeroSection = computed(() => props.section === 'hero');
const isAudienceSection = computed(() => props.section === 'audience');
const isWhitelabelSection = computed(() => props.section === 'whitelabel');
const isMulticlubSection = computed(() => props.section === 'multiclub');
const isFederationSection = computed(() => props.section === 'federation');
const isUsecasesSection = computed(() => props.section === 'usecases');
const isPricingSection = computed(() => props.section === 'pricing');
const isFaqSection = computed(() => props.section === 'faq');
const isContactSection = computed(() => props.section === 'contact');

// JSON Editor for complex sections
const showJsonEditor = computed(() => {
    return ['audience', 'whitelabel', 'multiclub', 'federation', 'usecases', 'pricing'].includes(props.section);
});

const jsonContent = computed({
    get: () => JSON.stringify(form.content, null, 2),
    set: (value) => {
        try {
            form.content = JSON.parse(value);
        } catch (e) {
            // Invalid JSON, don't update
        }
    }
});

const jsonError = ref(null);

const validateJson = () => {
    try {
        JSON.parse(jsonContent.value);
        jsonError.value = null;
        return true;
    } catch (e) {
        jsonError.value = e.message;
        return false;
    }
};

// FAQ Section Helpers
const addFaqItem = () => {
    if (!form.content.items) {
        form.content.items = [];
    }
    form.content.items.push({ question: '', answer: '' });
};

const removeFaqItem = (index) => {
    form.content.items.splice(index, 1);
};

// Prevent accidental navigation
const beforeUnloadHandler = (e) => {
    if (hasUnsavedChanges.value) {
        e.preventDefault();
        e.returnValue = '';
    }
};

if (typeof window !== 'undefined') {
    window.addEventListener('beforeunload', beforeUnloadHandler);
}
</script>

<template>
    <AdminLayout :title="`${label} bearbeiten`">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <div class="flex items-center space-x-3">
                        <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="icon"></path>
                        </svg>
                        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                            {{ label }} bearbeiten
                        </h2>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 ml-11">
                        {{ description }}
                    </p>
                    <div v-if="is_published" class="mt-2 ml-11">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                            Veröffentlicht am {{ new Date(published_at).toLocaleString('de-DE') }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <LandingPageLocaleSwitcher
                        v-model="currentLocale"
                        @change="switchLocale"
                        :show-label="true"
                    />

                    <button
                        v-if="other_locale_has_content"
                        @click="copyFromLocale"
                        type="button"
                        class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                        Von {{ currentLocale === 'de' ? 'EN' : 'DE' }} kopieren
                    </button>

                    <a
                        :href="route('admin.enterprise-page.preview', { section, locale: currentLocale })"
                        target="_blank"
                        class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        Vorschau
                    </a>

                    <SecondaryButton :href="route('admin.enterprise-page.index', { locale: currentLocale })" as="Link">
                        Zurück zur Übersicht
                    </SecondaryButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="saveContent">
                    <!-- Unsaved Changes Warning -->
                    <div v-if="hasUnsavedChanges" class="mb-6 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                        <div class="flex">
                            <svg class="h-5 w-5 text-yellow-600 dark:text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-800 dark:text-yellow-300 font-medium">
                                    Sie haben ungespeicherte Änderungen
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content Card -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg mb-6">
                        <div class="p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 mb-6">
                                Inhalte bearbeiten
                            </h3>

                            <!-- SEO Section Fields -->
                            <div v-if="isSeoSection" class="space-y-6">
                                <div>
                                    <InputLabel for="title" value="Meta Title *" />
                                    <TextInput
                                        id="title"
                                        v-model="form.content.title"
                                        type="text"
                                        class="mt-1 block w-full"
                                        :class="{ 'border-red-500': isOverLimit('title', 60) }"
                                        required
                                        maxlength="60"
                                    />
                                    <InputError class="mt-2" :message="form.errors['content.title']" />
                                    <p class="mt-1 text-xs" :class="isOverLimit('title', 60) ? 'text-red-600' : 'text-gray-500 dark:text-gray-400'">
                                        {{ characterCount('title', 60) }} (Empfohlen: 50-60 Zeichen)
                                    </p>
                                </div>

                                <div>
                                    <InputLabel for="description" value="Meta Description *" />
                                    <textarea
                                        id="description"
                                        v-model="form.content.description"
                                        rows="3"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                        :class="{ 'border-red-500': isOverLimit('description', 300) }"
                                        required
                                        maxlength="300"
                                    ></textarea>
                                    <InputError class="mt-2" :message="form.errors['content.description']" />
                                    <p class="mt-1 text-xs" :class="isOverLimit('description', 300) ? 'text-red-600' : 'text-gray-500 dark:text-gray-400'">
                                        {{ characterCount('description', 300) }}
                                    </p>
                                </div>

                                <div>
                                    <InputLabel for="keywords" value="Keywords (kommagetrennt)" />
                                    <TextInput
                                        id="keywords"
                                        v-model="form.content.keywords"
                                        type="text"
                                        class="mt-1 block w-full"
                                        placeholder="Basketball Enterprise, White Label, Verband Software"
                                    />
                                    <InputError class="mt-2" :message="form.errors['content.keywords']" />
                                </div>
                            </div>

                            <!-- Hero Section Fields -->
                            <div v-if="isHeroSection" class="space-y-6">
                                <div>
                                    <InputLabel for="headline" value="Hauptüberschrift *" />
                                    <TextInput
                                        id="headline"
                                        v-model="form.content.headline"
                                        type="text"
                                        class="mt-1 block w-full"
                                        required
                                        maxlength="255"
                                    />
                                    <InputError class="mt-2" :message="form.errors['content.headline']" />
                                </div>

                                <div>
                                    <InputLabel for="subheadline" value="Unterüberschrift *" />
                                    <textarea
                                        id="subheadline"
                                        v-model="form.content.subheadline"
                                        rows="3"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                        required
                                        maxlength="500"
                                    ></textarea>
                                    <InputError class="mt-2" :message="form.errors['content.subheadline']" />
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <InputLabel for="cta_primary_text" value="Primärer Button Text *" />
                                        <TextInput
                                            id="cta_primary_text"
                                            v-model="form.content.cta_primary_text"
                                            type="text"
                                            class="mt-1 block w-full"
                                            required
                                            maxlength="50"
                                        />
                                    </div>
                                    <div>
                                        <InputLabel for="cta_primary_link" value="Primärer Button Link *" />
                                        <TextInput
                                            id="cta_primary_link"
                                            v-model="form.content.cta_primary_link"
                                            type="text"
                                            class="mt-1 block w-full"
                                            required
                                            placeholder="#contact"
                                        />
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <InputLabel for="cta_secondary_text" value="Sekundärer Button Text" />
                                        <TextInput
                                            id="cta_secondary_text"
                                            v-model="form.content.cta_secondary_text"
                                            type="text"
                                            class="mt-1 block w-full"
                                            maxlength="50"
                                        />
                                    </div>
                                    <div>
                                        <InputLabel for="cta_secondary_link" value="Sekundärer Button Link" />
                                        <TextInput
                                            id="cta_secondary_link"
                                            v-model="form.content.cta_secondary_link"
                                            type="text"
                                            class="mt-1 block w-full"
                                            placeholder="#pricing"
                                        />
                                    </div>
                                </div>

                                <!-- Stats as JSON -->
                                <div>
                                    <InputLabel value="Stats (als JSON)" />
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Format: [{"value": "99,9%", "label": "SLA Garantie"}, ...]</p>
                                    <textarea
                                        v-model="form.content.stats"
                                        rows="6"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm font-mono text-sm"
                                        :value="JSON.stringify(form.content.stats || [], null, 2)"
                                        @input="(e) => { try { form.content.stats = JSON.parse(e.target.value); } catch(err) {} }"
                                    ></textarea>
                                </div>
                            </div>

                            <!-- FAQ Section -->
                            <div v-if="isFaqSection" class="space-y-6">
                                <div>
                                    <InputLabel for="headline" value="Überschrift *" />
                                    <TextInput
                                        id="headline"
                                        v-model="form.content.headline"
                                        type="text"
                                        class="mt-1 block w-full"
                                        required
                                        maxlength="255"
                                    />
                                    <InputError class="mt-2" :message="form.errors['content.headline']" />
                                </div>

                                <div class="space-y-4">
                                    <div class="flex justify-between items-center">
                                        <InputLabel value="FAQ Items" />
                                        <button
                                            type="button"
                                            @click="addFaqItem"
                                            class="inline-flex items-center px-3 py-1 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition"
                                        >
                                            + Frage hinzufügen
                                        </button>
                                    </div>

                                    <div v-for="(item, index) in (form.content.items || [])" :key="index" class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 space-y-3">
                                        <div class="flex justify-between items-start">
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Frage {{ index + 1 }}</span>
                                            <button
                                                type="button"
                                                @click="removeFaqItem(index)"
                                                class="text-red-600 hover:text-red-800 text-sm"
                                            >
                                                Entfernen
                                            </button>
                                        </div>
                                        <div>
                                            <InputLabel :for="`question-${index}`" value="Frage *" />
                                            <TextInput
                                                :id="`question-${index}`"
                                                v-model="item.question"
                                                type="text"
                                                class="mt-1 block w-full"
                                                required
                                            />
                                        </div>
                                        <div>
                                            <InputLabel :for="`answer-${index}`" value="Antwort *" />
                                            <textarea
                                                :id="`answer-${index}`"
                                                v-model="item.answer"
                                                rows="3"
                                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-gray-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                                required
                                            ></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Contact Section -->
                            <div v-if="isContactSection" class="space-y-6">
                                <div>
                                    <InputLabel for="headline" value="Überschrift *" />
                                    <TextInput
                                        id="headline"
                                        v-model="form.content.headline"
                                        type="text"
                                        class="mt-1 block w-full"
                                        required
                                        maxlength="255"
                                    />
                                    <InputError class="mt-2" :message="form.errors['content.headline']" />
                                </div>

                                <div>
                                    <InputLabel for="subheadline" value="Unterüberschrift" />
                                    <textarea
                                        id="subheadline"
                                        v-model="form.content.subheadline"
                                        rows="2"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                        maxlength="500"
                                    ></textarea>
                                </div>

                                <div>
                                    <InputLabel for="success_message" value="Erfolgsmeldung nach Absenden *" />
                                    <textarea
                                        id="success_message"
                                        v-model="form.content.success_message"
                                        rows="2"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                        required
                                        maxlength="500"
                                    ></textarea>
                                    <InputError class="mt-2" :message="form.errors['content.success_message']" />
                                </div>
                            </div>

                            <!-- JSON Editor for complex sections -->
                            <div v-if="showJsonEditor" class="space-y-6">
                                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-4">
                                    <div class="flex">
                                        <svg class="h-5 w-5 text-yellow-600 dark:text-yellow-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                        </svg>
                                        <div class="text-sm text-yellow-800 dark:text-yellow-300">
                                            <p><strong>{{ label }} JSON Editor:</strong> Für diese komplexe Section können Sie die Inhalte als JSON bearbeiten.</p>
                                            <p class="mt-1">Achten Sie darauf, dass das JSON gültig ist, bevor Sie speichern.</p>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <InputLabel for="json_content" value="Inhalt (JSON)" />
                                    <textarea
                                        id="json_content"
                                        v-model="jsonContent"
                                        @blur="validateJson"
                                        rows="25"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm font-mono text-sm"
                                        :class="{ 'border-red-500': jsonError }"
                                    ></textarea>
                                    <p v-if="jsonError" class="mt-2 text-sm text-red-600">
                                        JSON Fehler: {{ jsonError }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-between bg-white dark:bg-gray-800 shadow rounded-lg p-4">
                        <div class="flex items-center space-x-4">
                            <PrimaryButton type="submit" :disabled="form.processing">
                                Als Entwurf speichern
                            </PrimaryButton>

                            <button
                                type="button"
                                @click="publishContent"
                                :disabled="form.processing"
                                class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:opacity-50 transition ease-in-out duration-150"
                            >
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                Speichern & Veröffentlichen
                            </button>
                        </div>

                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            <span v-if="form.processing">Speichert...</span>
                            <span v-else-if="form.recentlySuccessful">Gespeichert</span>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
