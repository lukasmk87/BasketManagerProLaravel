<template>
    <AppLayout title="Taktik-Board">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Taktik-Board
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Tab Navigation -->
                <div class="mb-8">
                    <div class="border-b border-gray-200 dark:border-gray-700">
                        <nav class="-mb-px flex space-x-8">
                            <Link
                                :href="route('tactic-board.index')"
                                class="border-indigo-500 dark:border-indigo-400 text-indigo-600 dark:text-indigo-400 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
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
                        </nav>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <Link :href="route('tactic-board.plays.create')" class="group">
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg hover:shadow-2xl transition-shadow">
                            <div class="p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 bg-blue-500 rounded-md flex items-center justify-center">
                                            <PlusIcon class="w-6 h-6 text-white" />
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 group-hover:text-blue-600 dark:group-hover:text-blue-400">
                                            Neuer Spielzug
                                        </h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            Spielzug erstellen
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Link>

                    <Link :href="route('tactic-board.playbooks.create')" class="group">
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg hover:shadow-2xl transition-shadow">
                            <div class="p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 bg-green-500 rounded-md flex items-center justify-center">
                                            <BookOpenIcon class="w-6 h-6 text-white" />
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 group-hover:text-green-600 dark:group-hover:text-green-400">
                                            Neues Playbook
                                        </h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            Playbook anlegen
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Link>

                    <Link :href="route('tactic-board.templates')" class="group">
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg hover:shadow-2xl transition-shadow">
                            <div class="p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 bg-purple-500 rounded-md flex items-center justify-center">
                                            <RectangleStackIcon class="w-6 h-6 text-white" />
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 group-hover:text-purple-600 dark:group-hover:text-purple-400">
                                            Templates
                                        </h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            Vorlagen durchsuchen
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Link>

                    <Link :href="route('tactic-board.library')" class="group">
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg hover:shadow-2xl transition-shadow">
                            <div class="p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 bg-orange-500 rounded-md flex items-center justify-center">
                                            <StarIcon class="w-6 h-6 text-white" />
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 group-hover:text-orange-600 dark:group-hover:text-orange-400">
                                            Bibliothek
                                        </h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            Meine Sammlung
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow sm:rounded-lg">
                        <div class="p-6">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                Meine Spielzüge
                            </dt>
                            <dd class="mt-1 text-3xl font-semibold text-gray-900 dark:text-gray-100">
                                {{ stats.total_plays }}
                            </dd>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow sm:rounded-lg">
                        <div class="p-6">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                Meine Playbooks
                            </dt>
                            <dd class="mt-1 text-3xl font-semibold text-gray-900 dark:text-gray-100">
                                {{ stats.total_playbooks }}
                            </dd>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow sm:rounded-lg">
                        <div class="p-6">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                Favoriten
                            </dt>
                            <dd class="mt-1 text-3xl font-semibold text-gray-900 dark:text-gray-100">
                                {{ stats.favorites }}
                            </dd>
                        </div>
                    </div>
                </div>

                <!-- Recent Plays -->
                <div class="mb-8">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Aktuelle Spielzüge
                            </h3>
                            <Link
                                :href="route('tactic-board.plays.index')"
                                class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-500"
                            >
                                Alle anzeigen
                            </Link>
                        </div>
                        <div class="p-6">
                            <div v-if="recentPlays.data && recentPlays.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div
                                    v-for="play in recentPlays.data"
                                    :key="play.id"
                                    class="flex items-center p-4 border dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700"
                                >
                                    <div class="flex-shrink-0">
                                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                                            <DocumentIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                                        </div>
                                    </div>
                                    <div class="ml-4 flex-1 min-w-0">
                                        <Link
                                            :href="route('tactic-board.plays.show', play.id)"
                                            class="text-sm font-medium text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400 truncate block"
                                        >
                                            {{ play.name }}
                                        </Link>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ getCategoryLabel(play.category) }}
                                        </p>
                                    </div>
                                    <Link
                                        :href="route('tactic-board.plays.edit', play.id)"
                                        class="text-gray-400 hover:text-blue-600 dark:hover:text-blue-400"
                                    >
                                        <PencilIcon class="w-5 h-5" />
                                    </Link>
                                </div>
                            </div>
                            <div v-else class="text-center text-gray-500 dark:text-gray-400 py-8">
                                Noch keine Spielzüge erstellt
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Playbooks -->
                <div>
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Aktuelle Playbooks
                            </h3>
                            <Link
                                :href="route('tactic-board.playbooks.index')"
                                class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-500"
                            >
                                Alle anzeigen
                            </Link>
                        </div>
                        <div class="p-6">
                            <div v-if="recentPlaybooks.data && recentPlaybooks.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div
                                    v-for="playbook in recentPlaybooks.data"
                                    :key="playbook.id"
                                    class="flex items-center p-4 border dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700"
                                >
                                    <div class="flex-shrink-0">
                                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                                            <BookOpenIcon class="w-6 h-6 text-green-600 dark:text-green-400" />
                                        </div>
                                    </div>
                                    <div class="ml-4 flex-1 min-w-0">
                                        <Link
                                            :href="route('tactic-board.playbooks.show', playbook.id)"
                                            class="text-sm font-medium text-gray-900 dark:text-gray-100 hover:text-green-600 dark:hover:text-green-400 truncate block"
                                        >
                                            {{ playbook.name }}
                                        </Link>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ playbook.plays_count || 0 }} Spielzüge
                                        </p>
                                    </div>
                                    <Link
                                        :href="route('tactic-board.playbooks.edit', playbook.id)"
                                        class="text-gray-400 hover:text-green-600 dark:hover:text-green-400"
                                    >
                                        <PencilIcon class="w-5 h-5" />
                                    </Link>
                                </div>
                            </div>
                            <div v-else class="text-center text-gray-500 dark:text-gray-400 py-8">
                                Noch keine Playbooks erstellt
                            </div>
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
import {
    PlusIcon,
    BookOpenIcon,
    RectangleStackIcon,
    StarIcon,
    DocumentIcon,
    PencilIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    recentPlays: Object,
    recentPlaybooks: Object,
    stats: Object,
});

const categoryLabels = {
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
    return categoryLabels[category] || category;
};
</script>
