<template>
    <AppLayout :title="`Bearbeiten: ${play.name}`">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('tactic-board.plays.show', play.id)"
                        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        <ArrowLeftIcon class="h-5 w-5" />
                    </Link>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        Bearbeiten: {{ play.name }}
                    </h2>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        @click="save"
                        :disabled="isSaving"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 transition disabled:opacity-50"
                    >
                        {{ isSaving ? 'Speichern...' : 'Speichern' }}
                    </button>
                    <button
                        v-if="play.status === 'draft'"
                        @click="publish"
                        :disabled="isSaving"
                        class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition disabled:opacity-50"
                    >
                        Veröffentlichen
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <!-- Sidebar - Play Info -->
                    <div class="lg:col-span-1">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                Spielzug Details
                            </h3>

                            <!-- Status Badge -->
                            <div class="flex items-center gap-2">
                                <span
                                    :class="[
                                        'px-2 py-1 text-xs font-semibold rounded',
                                        getStatusClass(play.status)
                                    ]"
                                >
                                    {{ getStatusLabel(play.status) }}
                                </span>
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

                            <!-- Meta Info -->
                            <div class="pt-4 border-t border-gray-200 dark:border-gray-700 space-y-2">
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Erstellt von: {{ play.created_by?.name || 'Unbekannt' }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Erstellt am: {{ formatDate(play.created_at) }}
                                </p>
                                <p v-if="play.updated_at" class="text-xs text-gray-500 dark:text-gray-400">
                                    Aktualisiert: {{ formatDate(play.updated_at) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content - Editor -->
                    <div class="lg:col-span-3">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                            <TacticBoardEditor
                                ref="editorRef"
                                :initialData="{ play_data: play.play_data }"
                                :playId="play.id"
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
import { ref, computed, watch, onMounted } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TacticBoardEditor from '@/Components/TacticBoard/TacticBoardEditor.vue';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    play: Object,
    categories: Array,
    courtTypes: Array,
});

// Editor ref
const editorRef = ref(null);

// Saving state
const isSaving = ref(false);

// Tags input
const tagsInput = ref((props.play.tags || []).join(', '));

// Form
const form = useForm({
    name: props.play.name,
    description: props.play.description || '',
    category: props.play.category,
    court_type: props.play.court_type,
    tags: props.play.tags || [],
    is_public: props.play.is_public || false,
    play_data: props.play.play_data || {},
});

// Watch tags input and parse to array
watch(tagsInput, (value) => {
    form.tags = value.split(',').map(t => t.trim()).filter(t => t.length > 0);
});

// Handle editor changes
const handleEditorChange = (data) => {
    form.play_data = data;
};

// Save
const save = async () => {
    if (!form.name.trim()) {
        alert('Bitte geben Sie einen Namen ein.');
        return;
    }

    isSaving.value = true;

    // Get latest data from editor
    if (editorRef.value) {
        form.play_data = editorRef.value.exportData();
    }

    form.put(`/api/plays/${props.play.id}`, {
        onSuccess: () => {
            router.visit(route('tactic-board.plays.show', props.play.id));
        },
        onError: (errors) => {
            console.error('Save error:', errors);
        },
        onFinish: () => {
            isSaving.value = false;
        },
    });
};

// Publish
const publish = async () => {
    isSaving.value = true;

    // Get latest data from editor
    if (editorRef.value) {
        form.play_data = editorRef.value.exportData();
    }

    // First save, then publish
    form.put(`/api/plays/${props.play.id}`, {
        onSuccess: () => {
            // Now publish
            router.post(`/api/plays/${props.play.id}/publish`, {}, {
                onSuccess: () => {
                    router.visit(route('tactic-board.plays.show', props.play.id));
                },
                onFinish: () => {
                    isSaving.value = false;
                },
            });
        },
        onError: (errors) => {
            console.error('Save error:', errors);
            isSaving.value = false;
        },
    });
};

// Helper functions
const getStatusClass = (status) => {
    switch (status) {
        case 'published':
            return 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100';
        case 'draft':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100';
        case 'archived':
            return 'bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-100';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const getStatusLabel = (status) => {
    switch (status) {
        case 'published': return 'Veröffentlicht';
        case 'draft': return 'Entwurf';
        case 'archived': return 'Archiviert';
        default: return status;
    }
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>
