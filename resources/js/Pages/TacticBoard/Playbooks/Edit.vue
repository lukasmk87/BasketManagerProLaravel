<template>
    <AppLayout :title="`Bearbeiten: ${playbook.name}`">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('tactic-board.playbooks.show', playbook.id)"
                        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        <ArrowLeftIcon class="h-5 w-5" />
                    </Link>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        Bearbeiten: {{ playbook.name }}
                    </h2>
                </div>
                <button
                    @click="save"
                    :disabled="isSaving"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 transition disabled:opacity-50"
                >
                    {{ isSaving ? 'Speichern...' : 'Speichern' }}
                </button>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                    <div class="p-6 space-y-6">
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

                        <!-- Team -->
                        <div v-if="teams && teams.length > 0">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Team (optional)
                            </label>
                            <select
                                v-model="form.team_id"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option :value="null">Kein Team</option>
                                <option v-for="team in teams" :key="team.id" :value="team.id">
                                    {{ team.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Current Plays in Playbook -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Spielzüge im Playbook
                            </label>

                            <div v-if="currentPlays.length > 0" class="mb-4">
                                <draggable
                                    v-model="currentPlays"
                                    item-key="id"
                                    handle=".drag-handle"
                                    class="space-y-2"
                                    @end="onDragEnd"
                                >
                                    <template #item="{ element, index }">
                                        <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-md">
                                            <span class="drag-handle cursor-move text-gray-400 hover:text-gray-600">
                                                <Bars3Icon class="h-5 w-5" />
                                            </span>
                                            <span class="w-6 h-6 flex items-center justify-center bg-blue-600 text-white text-xs font-bold rounded-full">
                                                {{ index + 1 }}
                                            </span>
                                            <div class="flex-1">
                                                <span class="font-medium text-gray-900 dark:text-gray-100">
                                                    {{ element.name }}
                                                </span>
                                                <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">
                                                    {{ getCategoryLabel(element.category) }}
                                                </span>
                                            </div>
                                            <button
                                                @click="removePlay(element.id)"
                                                class="text-red-500 hover:text-red-700"
                                            >
                                                <XMarkIcon class="h-5 w-5" />
                                            </button>
                                        </div>
                                    </template>
                                </draggable>
                            </div>
                            <p v-else class="text-gray-500 dark:text-gray-400 mb-4">
                                Keine Spielzüge im Playbook
                            </p>
                        </div>

                        <!-- Available Plays to Add -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Spielzüge hinzufügen
                            </label>

                            <div class="max-h-64 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-md">
                                <div v-if="availableToAdd.length > 0" class="divide-y divide-gray-200 dark:divide-gray-600">
                                    <div
                                        v-for="play in availableToAdd"
                                        :key="play.id"
                                        class="flex items-center justify-between p-3 hover:bg-gray-50 dark:hover:bg-gray-700"
                                    >
                                        <div>
                                            <span class="font-medium text-gray-900 dark:text-gray-100">
                                                {{ play.name }}
                                            </span>
                                            <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">
                                                {{ getCategoryLabel(play.category) }}
                                            </span>
                                        </div>
                                        <button
                                            @click="addPlay(play)"
                                            class="text-blue-600 hover:text-blue-800 dark:text-blue-400"
                                        >
                                            <PlusIcon class="h-5 w-5" />
                                        </button>
                                    </div>
                                </div>
                                <div v-else class="p-4 text-center text-gray-500 dark:text-gray-400">
                                    Alle verfügbaren Spielzüge wurden hinzugefügt
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import draggable from 'vuedraggable';
import {
    ArrowLeftIcon,
    PlusIcon,
    XMarkIcon,
    Bars3Icon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    playbook: Object,
    categories: Array,
    availablePlays: Object,
    teams: Array,
});

// Saving state
const isSaving = ref(false);

// Current plays in playbook (for ordering)
const currentPlays = ref([...(props.playbook.plays || [])]);

// Form
const form = useForm({
    name: props.playbook.name,
    description: props.playbook.description || '',
    category: props.playbook.category,
    team_id: props.playbook.team_id || null,
    play_ids: props.playbook.plays?.map(p => p.id) || [],
});

// Available plays not in playbook
const availableToAdd = computed(() => {
    const currentIds = currentPlays.value.map(p => p.id);
    return (props.availablePlays?.data || []).filter(p => !currentIds.includes(p.id));
});

// Categories labels for plays
const playCategoryLabels = {
    offense: 'Offense',
    defense: 'Defense',
    inbound: 'Einwurf',
    fast_break: 'Fast Break',
    press_break: 'Press Break',
    zone_offense: 'Zonenoffense',
    zone_defense: 'Zonendefense',
    out_of_bounds: 'Out of Bounds',
    special: 'Speziell',
};

const getCategoryLabel = (category) => {
    return playCategoryLabels[category] || category;
};

// Add play to playbook
const addPlay = (play) => {
    currentPlays.value.push(play);
    form.play_ids = currentPlays.value.map(p => p.id);
};

// Remove play from playbook
const removePlay = (playId) => {
    currentPlays.value = currentPlays.value.filter(p => p.id !== playId);
    form.play_ids = currentPlays.value.map(p => p.id);
};

// Handle drag end - update order
const onDragEnd = () => {
    form.play_ids = currentPlays.value.map(p => p.id);
};

// Save
const save = () => {
    if (!form.name.trim()) {
        alert('Bitte geben Sie einen Namen ein.');
        return;
    }

    isSaving.value = true;
    form.play_ids = currentPlays.value.map(p => p.id);

    form.put(`/api/playbooks/${props.playbook.id}`, {
        onSuccess: () => {
            router.visit(route('tactic-board.playbooks.show', props.playbook.id));
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
