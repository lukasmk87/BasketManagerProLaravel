<template>
    <AppLayout title="Neues Playbook">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('tactic-board.playbooks.index')"
                        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        <ArrowLeftIcon class="h-5 w-5" />
                    </Link>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        Neues Playbook
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
                                placeholder="z.B. Offense Playbook 2024"
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
                                placeholder="Beschreibung des Playbooks..."
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

                        <!-- Available Plays -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Spielzüge hinzufügen
                            </label>

                            <div class="max-h-96 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-md">
                                <div v-if="availablePlays.data && availablePlays.data.length > 0" class="divide-y divide-gray-200 dark:divide-gray-600">
                                    <label
                                        v-for="play in availablePlays.data"
                                        :key="play.id"
                                        class="flex items-center p-4 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
                                    >
                                        <input
                                            type="checkbox"
                                            :value="play.id"
                                            v-model="selectedPlayIds"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        />
                                        <div class="ml-3 flex-1">
                                            <span class="font-medium text-gray-900 dark:text-gray-100">
                                                {{ play.name }}
                                            </span>
                                            <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">
                                                {{ getCategoryLabel(play.category) }}
                                            </span>
                                        </div>
                                    </label>
                                </div>
                                <div v-else class="p-8 text-center text-gray-500 dark:text-gray-400">
                                    Keine Spielzüge verfügbar
                                </div>
                            </div>

                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                {{ selectedPlayIds.length }} Spielzüge ausgewählt
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    categories: Array,
    availablePlays: Object,
    teams: Array,
});

// Saving state
const isSaving = ref(false);

// Selected plays
const selectedPlayIds = ref([]);

// Form
const form = useForm({
    name: '',
    description: '',
    category: 'game',
    team_id: null,
    play_ids: [],
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

// Save
const save = () => {
    if (!form.name.trim()) {
        alert('Bitte geben Sie einen Namen ein.');
        return;
    }

    isSaving.value = true;
    form.play_ids = selectedPlayIds.value;

    form.post('/api/playbooks', {
        onSuccess: (page) => {
            const playbookId = page.props.playbook?.id;
            if (playbookId) {
                router.visit(route('tactic-board.playbooks.show', playbookId));
            } else {
                router.visit(route('tactic-board.playbooks.index'));
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
