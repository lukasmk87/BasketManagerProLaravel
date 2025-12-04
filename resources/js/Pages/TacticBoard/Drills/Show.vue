<template>
    <AppLayout :title="drill.name">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('tactic-board.drills.index')"
                        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        <ArrowLeftIcon class="h-5 w-5" />
                    </Link>
                    <div>
                        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                            {{ drill.name }}
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ drill.tactic_category?.name || getCategoryLabel(drill.category) }}
                        </p>
                    </div>
                </div>
                <div v-if="canEdit" class="flex items-center gap-3">
                    <Link
                        :href="route('tactic-board.drills.edit', drill.id)"
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
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Main Content - Viewer -->
                    <div class="lg:col-span-2">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                            <div v-if="drill.drill_data" class="aspect-video">
                                <TacticBoardViewer
                                    :data="drill.drill_data"
                                    :animation-data="drill.animation_data"
                                    :court-type="drill.court_type || 'half_horizontal'"
                                />
                            </div>
                            <div v-else class="aspect-video bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                <div class="text-center">
                                    <AcademicCapIcon class="mx-auto h-16 w-16 text-gray-400" />
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                        Keine visuelle Darstellung
                                    </p>
                                    <Link
                                        v-if="canEdit"
                                        :href="route('tactic-board.drills.edit', drill.id)"
                                        class="mt-4 inline-flex items-center px-3 py-1.5 text-sm text-blue-600 hover:text-blue-700"
                                    >
                                        Visualisierung hinzufügen
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Instructions Section -->
                        <div v-if="drill.instructions" class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                Anleitung
                            </h3>
                            <div class="prose dark:prose-invert max-w-none text-gray-700 dark:text-gray-300">
                                {{ drill.instructions }}
                            </div>
                        </div>

                        <!-- Objectives Section -->
                        <div v-if="drill.objectives" class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                Ziele
                            </h3>
                            <div class="prose dark:prose-invert max-w-none text-gray-700 dark:text-gray-300">
                                {{ drill.objectives }}
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar - Details -->
                    <div class="lg:col-span-1">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                Details
                            </h3>

                            <dl class="space-y-4">
                                <!-- Status -->
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                                    <dd class="mt-1">
                                        <span :class="getStatusClass(drill.status)" class="inline-flex px-2 py-1 text-xs font-semibold rounded">
                                            {{ getStatusLabel(drill.status) }}
                                        </span>
                                    </dd>
                                </div>

                                <!-- Category -->
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Kategorie</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        {{ drill.tactic_category?.name || getCategoryLabel(drill.category) }}
                                    </dd>
                                </div>

                                <!-- Difficulty -->
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Schwierigkeit</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        {{ getDifficultyLabel(drill.difficulty_level) }}
                                    </dd>
                                </div>

                                <!-- Age Group -->
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Altersgruppe</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        {{ getAgeGroupLabel(drill.age_group) }}
                                    </dd>
                                </div>

                                <!-- Duration -->
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Dauer</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        {{ drill.estimated_duration }} Minuten
                                    </dd>
                                </div>

                                <!-- Players -->
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Spieler</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        {{ drill.min_players }}{{ drill.max_players ? `-${drill.max_players}` : '+' }} Spieler
                                    </dd>
                                </div>

                                <!-- Court Type -->
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Feldtyp</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        {{ getCourtTypeLabel(drill.court_type) }}
                                    </dd>
                                </div>

                                <!-- Created By -->
                                <div v-if="drill.created_by">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Erstellt von</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        {{ drill.created_by.name }}
                                    </dd>
                                </div>

                                <!-- Created At -->
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Erstellt am</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        {{ formatDate(drill.created_at) }}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Description -->
                        <div v-if="drill.description" class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                Beschreibung
                            </h3>
                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                {{ drill.description }}
                            </p>
                        </div>

                        <!-- Related Plays -->
                        <div v-if="drill.plays && drill.plays.length > 0" class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                Verknüpfte Spielzüge
                            </h3>
                            <ul class="space-y-2">
                                <li v-for="play in drill.plays" :key="play.id">
                                    <Link
                                        :href="route('tactic-board.plays.show', play.id)"
                                        class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400"
                                    >
                                        {{ play.name }}
                                    </Link>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TacticBoardViewer from '@/Components/TacticBoard/TacticBoardViewer.vue';
import {
    ArrowLeftIcon,
    PencilIcon,
    AcademicCapIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    drill: Object,
    canEdit: Boolean,
});

const getCategoryLabel = (category) => {
    const labels = {
        ball_handling: 'Ballhandling',
        shooting: 'Wurf',
        passing: 'Passen',
        defense: 'Verteidigung',
        rebounding: 'Rebound',
        conditioning: 'Kondition',
        agility: 'Beweglichkeit',
        footwork: 'Beinarbeit',
        team_offense: 'Team-Offense',
        team_defense: 'Team-Defense',
        transition: 'Transition',
        set_plays: 'Spielzüge',
        scrimmage: 'Scrimmage',
        warm_up: 'Aufwärmen',
        cool_down: 'Abwärmen',
    };
    return labels[category] || category;
};

const getDifficultyLabel = (difficulty) => {
    const labels = {
        beginner: 'Anfänger',
        intermediate: 'Fortgeschritten',
        advanced: 'Fortgeschritten+',
        expert: 'Experte',
    };
    return labels[difficulty] || difficulty;
};

const getAgeGroupLabel = (ageGroup) => {
    const labels = {
        all: 'Alle Altersgruppen',
        U8: 'U8',
        U10: 'U10',
        U12: 'U12',
        U14: 'U14',
        U16: 'U16',
        U18: 'U18',
        adult: 'Erwachsene',
    };
    return labels[ageGroup] || ageGroup;
};

const getCourtTypeLabel = (courtType) => {
    const labels = {
        half_horizontal: 'Halbes Feld (horizontal)',
        full: 'Ganzes Feld',
        half_vertical: 'Halbes Feld (vertikal)',
    };
    return labels[courtType] || courtType;
};

const getStatusClass = (status) => {
    const classes = {
        draft: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        pending_review: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        approved: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        rejected: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        archived: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
    };
    return classes[status] || classes.draft;
};

const getStatusLabel = (status) => {
    const labels = {
        draft: 'Entwurf',
        pending_review: 'In Prüfung',
        approved: 'Freigegeben',
        rejected: 'Abgelehnt',
        archived: 'Archiviert',
    };
    return labels[status] || status;
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};
</script>
