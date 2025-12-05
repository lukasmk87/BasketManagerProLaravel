<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { PlusIcon, PencilIcon, TrashIcon, TagIcon } from '@heroicons/vue/24/outline';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';

const props = defineProps({
    categories: Array,
    stats: Object,
});

// Modal state
const showModal = ref(false);
const editingCategory = ref(null);
const isSubmitting = ref(false);
const errors = ref({});

// Form data
const form = ref({
    name: '',
    type: 'both',
    description: '',
    color: '#3B82F6',
});

const modalTitle = computed(() => editingCategory.value ? 'Kategorie bearbeiten' : 'Neue Kategorie');

const typeOptions = [
    { value: 'play', label: 'Spielzüge' },
    { value: 'drill', label: 'Übungen' },
    { value: 'both', label: 'Spielzüge & Übungen' },
];

function openCreateModal() {
    editingCategory.value = null;
    form.value = {
        name: '',
        type: 'both',
        description: '',
        color: '#3B82F6',
    };
    errors.value = {};
    showModal.value = true;
}

function openEditModal(category) {
    editingCategory.value = category;
    form.value = {
        name: category.name,
        type: category.type,
        description: category.description || '',
        color: category.color || '#3B82F6',
    };
    errors.value = {};
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    editingCategory.value = null;
    errors.value = {};
}

async function submitForm() {
    isSubmitting.value = true;
    errors.value = {};

    try {
        if (editingCategory.value) {
            await axios.put(`/api/tactic-categories/${editingCategory.value.id}`, form.value);
        } else {
            await axios.post('/api/tactic-categories', form.value);
        }
        closeModal();
        router.reload({ only: ['categories', 'stats'] });
    } catch (error) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        } else {
            errors.value = { general: [error.response?.data?.message || 'Ein Fehler ist aufgetreten.'] };
        }
    } finally {
        isSubmitting.value = false;
    }
}

async function deleteCategory(category) {
    if (!confirm(`Möchten Sie die Kategorie "${category.name}" wirklich löschen?`)) {
        return;
    }

    try {
        await axios.delete(`/api/tactic-categories/${category.id}`);
        router.reload({ only: ['categories', 'stats'] });
    } catch (error) {
        alert(error.response?.data?.message || 'Die Kategorie konnte nicht gelöscht werden.');
    }
}

function getTypeLabel(type) {
    return typeOptions.find(t => t.value === type)?.label || type;
}
</script>

<template>
    <Head title="Kategorien - Taktik-Board" />

    <AppLayout title="Kategorien - Taktik-Board">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Taktik-Board
                </h2>
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 transition"
                >
                    <PlusIcon class="h-4 w-4 mr-2" />
                    Neue Kategorie
                </button>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Tab Navigation -->
                <div class="mb-8">
                    <div class="border-b border-gray-200 dark:border-gray-700">
                        <nav class="-mb-px flex space-x-8">
                            <Link
                                :href="route('tactic-board.index')"
                                class="border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                            >
                                Übersicht
                            </Link>
                            <Link
                                :href="route('tactic-board.plays.index')"
                                class="border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                            >
                                Spielzüge
                            </Link>
                            <Link
                                :href="route('tactic-board.drills.index')"
                                class="border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                            >
                                Übungen
                            </Link>
                            <Link
                                :href="route('tactic-board.playbooks.index')"
                                class="border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                            >
                                Playbooks
                            </Link>
                            <Link
                                :href="route('tactic-board.templates')"
                                class="border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                            >
                                Templates
                            </Link>
                            <Link
                                :href="route('tactic-board.library')"
                                class="border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                            >
                                Bibliothek
                            </Link>
                            <Link
                                :href="route('tactic-board.categories')"
                                class="border-indigo-500 dark:border-indigo-400 text-indigo-600 dark:text-indigo-400 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                            >
                                Kategorien
                            </Link>
                        </nav>
                    </div>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Gesamt</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ stats.total }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Für Spielzüge</p>
                        <p class="text-2xl font-semibold text-blue-600">{{ stats.play_categories }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Für Übungen</p>
                        <p class="text-2xl font-semibold text-green-600">{{ stats.drill_categories }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Eigene</p>
                        <p class="text-2xl font-semibold text-purple-600">{{ stats.custom_categories }}</p>
                    </div>
                </div>

                <!-- Categories Table -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Kategorie
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Typ
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Verwendung
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Aktionen
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="category in categories" :key="category.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div
                                            class="w-4 h-4 rounded-full mr-3 flex-shrink-0"
                                            :style="{ backgroundColor: category.color }"
                                        ></div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ category.name }}
                                                <span v-if="category.is_system" class="ml-2 px-2 py-0.5 text-xs bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300 rounded">
                                                    System
                                                </span>
                                            </div>
                                            <div v-if="category.description" class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ category.description }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full"
                                        :class="{
                                            'bg-blue-100 text-blue-800': category.type === 'play',
                                            'bg-green-100 text-green-800': category.type === 'drill',
                                            'bg-purple-100 text-purple-800': category.type === 'both',
                                        }"
                                    >
                                        {{ category.type_display }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <div class="flex items-center space-x-4">
                                        <span v-if="category.plays_count > 0" class="text-blue-600">
                                            {{ category.plays_count }} Spielzüge
                                        </span>
                                        <span v-if="category.drills_count > 0" class="text-green-600">
                                            {{ category.drills_count }} Übungen
                                        </span>
                                        <span v-if="category.total_usage === 0" class="text-gray-400">
                                            Nicht verwendet
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-2">
                                        <button
                                            @click="openEditModal(category)"
                                            class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                            title="Bearbeiten"
                                        >
                                            <PencilIcon class="h-5 w-5" />
                                        </button>
                                        <button
                                            v-if="!category.is_system && category.total_usage === 0"
                                            @click="deleteCategory(category)"
                                            class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                            title="Löschen"
                                        >
                                            <TrashIcon class="h-5 w-5" />
                                        </button>
                                        <span
                                            v-else-if="category.is_system"
                                            class="text-gray-300 dark:text-gray-600 cursor-not-allowed"
                                            title="System-Kategorien können nicht gelöscht werden"
                                        >
                                            <TrashIcon class="h-5 w-5" />
                                        </span>
                                        <span
                                            v-else
                                            class="text-gray-300 dark:text-gray-600 cursor-not-allowed"
                                            title="Kategorie wird noch verwendet"
                                        >
                                            <TrashIcon class="h-5 w-5" />
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 transition-opacity" @click="closeModal">
                    <div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75"></div>
                </div>

                <!-- Modal panel -->
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form @submit.prevent="submitForm">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                {{ modalTitle }}
                            </h3>
                        </div>

                        <div class="px-6 py-4 space-y-4">
                            <!-- Error message -->
                            <div v-if="errors.general" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded p-3">
                                <p class="text-sm text-red-600 dark:text-red-400">{{ errors.general[0] }}</p>
                            </div>

                            <!-- Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Name *
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    required
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="z.B. Press Break"
                                />
                                <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name[0] }}</p>
                            </div>

                            <!-- Type -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Typ *
                                </label>
                                <select
                                    v-model="form.type"
                                    required
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option v-for="option in typeOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                                <p v-if="errors.type" class="mt-1 text-sm text-red-600">{{ errors.type[0] }}</p>
                            </div>

                            <!-- Description -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Beschreibung
                                </label>
                                <textarea
                                    v-model="form.description"
                                    rows="2"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Optionale Beschreibung..."
                                ></textarea>
                            </div>

                            <!-- Color -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Farbe
                                </label>
                                <div class="flex items-center space-x-3">
                                    <input
                                        v-model="form.color"
                                        type="color"
                                        class="h-10 w-20 rounded border border-gray-300 dark:border-gray-600 cursor-pointer"
                                    />
                                    <input
                                        v-model="form.color"
                                        type="text"
                                        class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="#3B82F6"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 flex justify-end space-x-3">
                            <button
                                type="button"
                                @click="closeModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-md hover:bg-gray-50 dark:hover:bg-gray-500"
                            >
                                Abbrechen
                            </button>
                            <button
                                type="submit"
                                :disabled="isSubmitting"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 disabled:opacity-50"
                            >
                                {{ isSubmitting ? 'Speichern...' : 'Speichern' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
