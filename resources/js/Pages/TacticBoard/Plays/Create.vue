<template>
    <AppLayout title="Neuer Spielzug">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('tactic-board.plays.index')"
                        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        <ArrowLeftIcon class="h-5 w-5" />
                    </Link>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        {{ showTemplatePicker ? 'Neuen Spielzug erstellen' : 'Spielzug bearbeiten' }}
                    </h2>
                </div>
                <div v-if="!showTemplatePicker" class="flex items-center gap-3">
                    <button
                        @click="saveAsDraft"
                        :disabled="isSaving"
                        class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition disabled:opacity-50"
                    >
                        Als Entwurf speichern
                    </button>
                    <button
                        @click="saveAndPublish"
                        :disabled="isSaving"
                        class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition disabled:opacity-50"
                    >
                        Speichern & Veröffentlichen
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Template Picker Step -->
                <div v-if="showTemplatePicker" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <TemplatePicker
                        :featuredTemplates="featuredTemplates"
                        @select="handleTemplateSelect"
                        @startBlank="handleStartBlank"
                        @browseAll="handleBrowseAll"
                    />
                </div>

                <!-- Editor Step -->
                <div v-else class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <!-- Sidebar - Play Info -->
                    <div class="lg:col-span-1">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-6">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    Spielzug Details
                                </h3>
                                <button
                                    @click="goBackToTemplatePicker"
                                    class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                                >
                                    Template wechseln
                                </button>
                            </div>

                            <!-- Selected Template Info -->
                            <div v-if="selectedTemplate" class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                <p class="text-sm text-blue-700 dark:text-blue-300">
                                    Basiert auf: <strong>{{ selectedTemplate.name }}</strong>
                                </p>
                            </div>

                            <!-- Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Name *
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="z.B. Pick & Roll Links"
                                />
                                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <!-- Description -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Beschreibung
                                </label>
                                <textarea
                                    v-model="form.description"
                                    rows="3"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Beschreibung des Spielzugs..."
                                ></textarea>
                            </div>

                            <!-- Category -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Kategorie
                                </label>
                                <select
                                    v-model="form.category"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option v-for="cat in categories" :key="cat.value" :value="cat.value">
                                        {{ cat.label }}
                                    </option>
                                </select>
                            </div>

                            <!-- Court Type -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Spielfeld
                                </label>
                                <select
                                    v-model="form.court_type"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option v-for="ct in courtTypes" :key="ct.value" :value="ct.value">
                                        {{ ct.label }}
                                    </option>
                                </select>
                            </div>

                            <!-- Tags -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Tags
                                </label>
                                <input
                                    v-model="tagsInput"
                                    type="text"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Kommagetrennt, z.B. offense, pick-and-roll"
                                />
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Tags mit Komma trennen
                                </p>
                            </div>

                            <!-- Is Public -->
                            <div class="flex items-center">
                                <input
                                    v-model="form.is_public"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                    Öffentlich sichtbar
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content - Editor -->
                    <div class="lg:col-span-3">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                            <TacticBoardEditor
                                ref="editorRef"
                                :initialData="editorInitialData"
                                @change="handleEditorChange"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TacticBoardEditor from '@/Components/TacticBoard/TacticBoardEditor.vue';
import TemplatePicker from '@/Components/TacticBoard/TemplatePicker.vue';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    categories: Array,
    courtTypes: Array,
    defaultPlayData: Object,
    featuredTemplates: {
        type: Array,
        default: () => [],
    },
});

// Template picker state
const showTemplatePicker = ref(true);
const selectedTemplate = ref(null);

// Editor ref
const editorRef = ref(null);

// Saving state
const isSaving = ref(false);

// Tags input
const tagsInput = ref('');

// Form
const form = useForm({
    name: '',
    description: '',
    category: 'offense',
    court_type: 'half_horizontal',
    tags: [],
    is_public: false,
    play_data: props.defaultPlayData || {},
    status: 'draft',
});

// Computed initial data for editor
const editorInitialData = computed(() => {
    if (selectedTemplate.value?.play_data) {
        return selectedTemplate.value.play_data;
    }
    return props.defaultPlayData || {};
});

// Watch tags input and parse to array
watch(tagsInput, (value) => {
    form.tags = value.split(',').map(t => t.trim()).filter(t => t.length > 0);
});

// Handle template selection
const handleTemplateSelect = (data) => {
    selectedTemplate.value = data.template;
    form.name = data.name || data.template.name;
    form.description = data.template.description || '';
    form.category = data.template.category || 'offense';
    form.court_type = data.template.court_type || 'half_horizontal';
    form.play_data = data.template.play_data || {};

    if (data.template.tags?.length) {
        tagsInput.value = data.template.tags.join(', ');
    }

    showTemplatePicker.value = false;
};

// Handle start blank
const handleStartBlank = () => {
    selectedTemplate.value = null;
    form.name = '';
    form.description = '';
    form.category = 'offense';
    form.court_type = 'half_horizontal';
    form.play_data = props.defaultPlayData || {};
    tagsInput.value = '';
    showTemplatePicker.value = false;
};

// Handle browse all templates
const handleBrowseAll = () => {
    router.visit(route('tactic-board.templates'));
};

// Go back to template picker
const goBackToTemplatePicker = () => {
    showTemplatePicker.value = true;
};

// Handle editor changes
const handleEditorChange = (data) => {
    form.play_data = data;
};

// Save as draft
const saveAsDraft = async () => {
    if (!form.name.trim()) {
        alert('Bitte geben Sie einen Namen ein.');
        return;
    }

    isSaving.value = true;
    form.status = 'draft';

    // Get latest data from editor
    if (editorRef.value) {
        form.play_data = editorRef.value.exportData();
    }

    form.post('/api/plays', {
        onSuccess: (page) => {
            const playId = page.props.play?.id;
            if (playId) {
                router.visit(route('tactic-board.plays.show', playId));
            } else {
                router.visit(route('tactic-board.plays.index'));
            }
        },
        onError: (errors) => {
            console.error('Save error:', errors);
        },
        onFinish: () => {
            isSaving.value = false;
        },
    });
};

// Save and publish
const saveAndPublish = async () => {
    if (!form.name.trim()) {
        alert('Bitte geben Sie einen Namen ein.');
        return;
    }

    isSaving.value = true;
    form.status = 'published';

    // Get latest data from editor
    if (editorRef.value) {
        form.play_data = editorRef.value.exportData();
    }

    form.post('/api/plays', {
        onSuccess: (page) => {
            const playId = page.props.play?.id;
            if (playId) {
                router.visit(route('tactic-board.plays.show', playId));
            } else {
                router.visit(route('tactic-board.plays.index'));
            }
        },
        onError: (errors) => {
            console.error('Save error:', errors);
        },
        onFinish: () => {
            isSaving.value = false;
        },
    });
};
</script>
