<template>
    <AppLayout :title="playbook.name">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('tactic-board.playbooks.index')"
                        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        <ArrowLeftIcon class="h-5 w-5" />
                    </Link>
                    <div>
                        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                            {{ playbook.name }}
                        </h2>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                {{ getCategoryLabel(playbook.category) }}
                            </span>
                            <span v-if="playbook.is_default" class="px-2 py-0.5 text-xs font-semibold rounded bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">
                                Standard
                            </span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        @click="exportPdf"
                        class="inline-flex items-center px-3 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition"
                    >
                        <DocumentIcon class="h-4 w-4 mr-2" />
                        PDF Export
                    </button>
                    <Link
                        v-if="canEdit"
                        :href="route('tactic-board.playbooks.edit', playbook.id)"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 transition"
                    >
                        <PencilIcon class="h-4 w-4 mr-2" />
                        Bearbeiten
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <!-- Main Content - Plays List -->
                    <div class="lg:col-span-3">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    Spielzüge ({{ playbook.plays?.length || 0 }})
                                </h3>
                            </div>

                            <!-- Plays Grid -->
                            <div v-if="playbook.plays && playbook.plays.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4 p-6">
                                <div
                                    v-for="(play, index) in playbook.plays"
                                    :key="play.id"
                                    class="bg-gray-50 dark:bg-gray-700 rounded-lg overflow-hidden"
                                >
                                    <Link :href="route('tactic-board.plays.show', play.id)">
                                        <!-- Thumbnail -->
                                        <div class="aspect-video bg-gray-200 dark:bg-gray-600 relative">
                                            <img
                                                v-if="play.thumbnail_path"
                                                :src="play.thumbnail_path"
                                                :alt="play.name"
                                                class="w-full h-full object-cover"
                                            />
                                            <div
                                                v-else
                                                class="w-full h-full flex items-center justify-center"
                                            >
                                                <DocumentIcon class="h-12 w-12 text-gray-400" />
                                            </div>
                                            <!-- Order badge -->
                                            <span class="absolute top-2 left-2 w-6 h-6 flex items-center justify-center bg-blue-600 text-white text-xs font-bold rounded-full">
                                                {{ index + 1 }}
                                            </span>
                                        </div>
                                    </Link>

                                    <div class="p-4">
                                        <Link
                                            :href="route('tactic-board.plays.show', play.id)"
                                            class="text-base font-semibold text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400"
                                        >
                                            {{ play.name }}
                                        </Link>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            {{ getCategoryLabel(play.category) }}
                                        </p>
                                        <p v-if="play.pivot?.notes" class="mt-2 text-sm text-gray-600 dark:text-gray-400 italic">
                                            "{{ play.pivot.notes }}"
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Empty State -->
                            <div v-else class="p-12 text-center">
                                <DocumentIcon class="mx-auto h-12 w-12 text-gray-400" />
                                <p class="mt-4 text-gray-600 dark:text-gray-400">
                                    Dieses Playbook enthält noch keine Spielzüge.
                                </p>
                                <Link
                                    v-if="canEdit"
                                    :href="route('tactic-board.playbooks.edit', playbook.id)"
                                    class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 rounded-md text-white hover:bg-blue-700 transition"
                                >
                                    Spielzüge hinzufügen
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="lg:col-span-1 space-y-6">
                        <!-- Details -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                Details
                            </h3>

                            <div class="space-y-4">
                                <div v-if="playbook.team">
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">
                                        Team
                                    </label>
                                    <p class="text-gray-900 dark:text-gray-100">
                                        {{ playbook.team.name }}
                                    </p>
                                </div>

                                <div v-if="playbook.description">
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">
                                        Beschreibung
                                    </label>
                                    <p class="text-gray-900 dark:text-gray-100">
                                        {{ playbook.description }}
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">
                                        Erstellt von
                                    </label>
                                    <p class="text-gray-900 dark:text-gray-100">
                                        {{ playbook.created_by?.name || 'Unbekannt' }}
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">
                                        Erstellt am
                                    </label>
                                    <p class="text-gray-900 dark:text-gray-100">
                                        {{ formatDate(playbook.created_at) }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Statistics -->
                        <div v-if="statistics" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                Statistiken
                            </h3>

                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Spielzüge</span>
                                    <span class="font-semibold text-gray-900 dark:text-gray-100">{{ statistics.total_plays }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Offense</span>
                                    <span class="font-semibold text-gray-900 dark:text-gray-100">{{ statistics.offense_plays || 0 }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Defense</span>
                                    <span class="font-semibold text-gray-900 dark:text-gray-100">{{ statistics.defense_plays || 0 }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div v-if="canEdit" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                Aktionen
                            </h3>
                            <div class="space-y-2">
                                <button
                                    @click="duplicatePlaybook"
                                    class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-md text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition text-left"
                                >
                                    Duplizieren
                                </button>
                                <button
                                    v-if="!playbook.is_default"
                                    @click="setAsDefault"
                                    class="w-full px-4 py-2 bg-yellow-100 dark:bg-yellow-800 rounded-md text-sm text-yellow-700 dark:text-yellow-200 hover:bg-yellow-200 dark:hover:bg-yellow-700 transition text-left"
                                >
                                    Als Standard setzen
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ArrowLeftIcon,
    PencilIcon,
    DocumentIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    playbook: Object,
    statistics: Object,
    canEdit: Boolean,
});

// Categories labels
const categoryLabels = {
    game: 'Spiel',
    practice: 'Training',
    situational: 'Situativ',
};

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

// Export PDF
const exportPdf = () => {
    window.open(`/api/playbooks/${props.playbook.id}/export/pdf`, '_blank');
};

// Actions
const duplicatePlaybook = () => {
    if (confirm('Möchten Sie dieses Playbook duplizieren?')) {
        router.post(`/api/playbooks/${props.playbook.id}/duplicate`, {}, {
            onSuccess: (page) => {
                if (page.props.playbook?.id) {
                    router.visit(route('tactic-board.playbooks.edit', page.props.playbook.id));
                }
            },
        });
    }
};

const setAsDefault = () => {
    if (confirm('Möchten Sie dieses Playbook als Standard setzen?')) {
        router.post(`/api/playbooks/${props.playbook.id}/set-default`, {}, {
            onSuccess: () => {
                router.reload();
            },
        });
    }
};

// Helper functions
const getCategoryLabel = (category) => {
    return playCategoryLabels[category] || categoryLabels[category] || category;
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
