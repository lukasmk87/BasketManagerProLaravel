<template>
    <AppLayout title="Übung bearbeiten">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('tactic-board.drills.show', drill.id)"
                        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        <ArrowLeftIcon class="h-5 w-5" />
                    </Link>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        Übung bearbeiten
                    </h2>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        @click="saveChanges"
                        :disabled="isSaving"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 transition disabled:opacity-50"
                    >
                        Änderungen speichern
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <!-- Sidebar - Drill Info -->
                    <div class="lg:col-span-1">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                Übungs-Details
                            </h3>

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
                                    v-model="form.category_id"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option value="">Kategorie wählen...</option>
                                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                        {{ cat.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Court Type -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Feldtyp
                                </label>
                                <select
                                    v-model="form.court_type"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option v-for="(label, value) in courtTypes" :key="value" :value="value">
                                        {{ label }}
                                    </option>
                                </select>
                            </div>

                            <!-- Difficulty -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Schwierigkeit
                                </label>
                                <select
                                    v-model="form.difficulty_level"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option v-for="(label, value) in difficultyLevels" :key="value" :value="value">
                                        {{ label }}
                                    </option>
                                </select>
                            </div>

                            <!-- Age Group -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Altersgruppe
                                </label>
                                <select
                                    v-model="form.age_group"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option v-for="(label, value) in ageGroups" :key="value" :value="value">
                                        {{ label }}
                                    </option>
                                </select>
                            </div>

                            <!-- Duration -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Dauer (Minuten)
                                </label>
                                <input
                                    v-model.number="form.estimated_duration"
                                    type="number"
                                    min="1"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>

                            <!-- Players -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Min. Spieler
                                    </label>
                                    <input
                                        v-model.number="form.min_players"
                                        type="number"
                                        min="1"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Max. Spieler
                                    </label>
                                    <input
                                        v-model.number="form.max_players"
                                        type="number"
                                        min="1"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    />
                                </div>
                            </div>

                            <!-- Objectives -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Ziele
                                </label>
                                <textarea
                                    v-model="form.objectives"
                                    rows="2"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                ></textarea>
                            </div>

                            <!-- Instructions -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Anleitung
                                </label>
                                <textarea
                                    v-model="form.instructions"
                                    rows="4"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content - Editor -->
                    <div class="lg:col-span-3">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                            <TacticBoardEditor
                                ref="editorRef"
                                :initial-data="drill.drill_data || defaultDrillData"
                                :court-type="form.court_type"
                                :drill-id="drill.id"
                                entity-type="drill"
                                @update:data="handleDataUpdate"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TacticBoardEditor from '@/Components/TacticBoard/TacticBoardEditor.vue';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';
import axios from 'axios';

const props = defineProps({
    drill: Object,
    categories: Array,
    courtTypes: Object,
    difficultyLevels: Object,
    ageGroups: Object,
});

const defaultDrillData = {
    elements: {
        players: [],
        paths: [],
        shapes: [],
        annotations: [],
    },
    settings: {
        showGrid: true,
        snapToGrid: true,
        gridSize: 20,
    },
};

const editorRef = ref(null);
const isSaving = ref(false);

const form = reactive({
    name: props.drill.name || '',
    description: props.drill.description || '',
    objectives: props.drill.objectives || '',
    instructions: props.drill.instructions || '',
    category_id: props.drill.category_id || '',
    court_type: props.drill.court_type || 'half_horizontal',
    difficulty_level: props.drill.difficulty_level || 'beginner',
    age_group: props.drill.age_group || 'all',
    estimated_duration: props.drill.estimated_duration || 15,
    min_players: props.drill.min_players || 1,
    max_players: props.drill.max_players || null,
    drill_data: props.drill.drill_data || defaultDrillData,
    animation_data: props.drill.animation_data || null,
});

const handleDataUpdate = (data) => {
    form.drill_data = data;
};

const saveChanges = async () => {
    isSaving.value = true;

    try {
        await axios.put(`/api/drills/${props.drill.id}`, {
            name: form.name,
            description: form.description,
            objectives: form.objectives,
            instructions: form.instructions,
            category_id: form.category_id || null,
            court_type: form.court_type,
            difficulty_level: form.difficulty_level,
            age_group: form.age_group,
            estimated_duration: form.estimated_duration,
            min_players: form.min_players,
            max_players: form.max_players,
            drill_data: form.drill_data,
            animation_data: form.animation_data,
        });

        router.visit(route('tactic-board.drills.show', props.drill.id));
    } catch (error) {
        console.error('Failed to save drill:', error);
    } finally {
        isSaving.value = false;
    }
};
</script>
