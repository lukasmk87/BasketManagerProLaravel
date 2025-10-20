<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PendingPlayerCard from '@/Components/PlayerRegistration/PendingPlayerCard.vue';

const props = defineProps({
    pendingPlayers: {
        type: Object,
        required: true,
    },
    teams: {
        type: Array,
        default: () => [],
    },
    clubs: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const search = ref(props.filters.search || '');
const clubFilter = ref(props.filters.club_id || null);
const selectedPlayers = ref(new Set());
const bulkMode = ref(false);

// Handle search
const handleSearch = () => {
    router.get(route('club-admin.pending-players.index'), {
        search: search.value || undefined,
        club_id: clubFilter.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Handle club filter change
const handleClubFilterChange = () => {
    router.get(route('club-admin.pending-players.index'), {
        search: search.value || undefined,
        club_id: clubFilter.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Toggle player selection
const togglePlayerSelection = (playerId) => {
    if (selectedPlayers.value.has(playerId)) {
        selectedPlayers.value.delete(playerId);
    } else {
        selectedPlayers.value.add(playerId);
    }
};

// Select all players
const selectAll = () => {
    if (selectedPlayers.value.size === props.pendingPlayers.data.length) {
        selectedPlayers.value.clear();
    } else {
        props.pendingPlayers.data.forEach(player => {
            selectedPlayers.value.add(player.id);
        });
    }
};

// Computed: All selected?
const allSelected = computed(() => {
    return props.pendingPlayers.data.length > 0 &&
        selectedPlayers.value.size === props.pendingPlayers.data.length;
});

// Handle single player assignment
const handleAssign = (data) => {
    router.post(route('club-admin.pending-players.assign'), {
        player_id: data.playerId,
        team_id: data.teamId,
        team_data: data.teamData,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            // Success handled by backend flash message
        },
    });
};

// Handle single player rejection
const handleReject = (playerId) => {
    router.delete(route('club-admin.pending-players.reject', playerId), {
        preserveScroll: true,
        onSuccess: () => {
            selectedPlayers.value.delete(playerId);
        },
    });
};

// Handle bulk assignment (not implemented in this view, would need separate UI)
const toggleBulkMode = () => {
    bulkMode.value = !bulkMode.value;
    if (!bulkMode.value) {
        selectedPlayers.value.clear();
    }
};
</script>

<template>
    <AppLayout title="Ausstehende Spieler-Registrierungen">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Ausstehende Spieler-Registrierungen
                </h2>
                <div class="flex items-center gap-2">
                    <span
                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800"
                    >
                        {{ pendingPlayers.total }} ausstehend
                    </span>
                    <button
                        @click="toggleBulkMode"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs uppercase tracking-widest shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        :class="bulkMode ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-white text-gray-700 hover:bg-gray-50'"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        {{ bulkMode ? 'Beenden' : 'Mehrfachauswahl' }}
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Filters -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mb-6 p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Search -->
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">
                                Suchen
                            </label>
                            <div class="flex gap-2">
                                <input
                                    id="search"
                                    v-model="search"
                                    type="text"
                                    placeholder="Name oder E-Mail suchen..."
                                    class="flex-1 px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                    @keyup.enter="handleSearch"
                                />
                                <button
                                    @click="handleSearch"
                                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Club Filter -->
                        <div v-if="clubs.length > 1">
                            <label for="club_filter" class="block text-sm font-medium text-gray-700 mb-1">
                                Club filtern
                            </label>
                            <select
                                id="club_filter"
                                v-model="clubFilter"
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                @change="handleClubFilterChange"
                            >
                                <option :value="null">Alle Clubs</option>
                                <option v-for="club in clubs" :key="club.id" :value="club.id">
                                    {{ club.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Bulk Actions Bar -->
                <div
                    v-if="bulkMode && selectedPlayers.size > 0"
                    class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6 rounded-r-lg"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-blue-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                            <p class="text-sm font-medium text-blue-700">
                                {{ selectedPlayers.size }} Spieler ausgewählt
                            </p>
                        </div>
                        <button
                            @click="selectedPlayers.clear()"
                            class="text-sm text-blue-700 hover:text-blue-900 font-medium"
                        >
                            Auswahl aufheben
                        </button>
                    </div>
                </div>

                <!-- Select All (Bulk Mode) -->
                <div v-if="bulkMode && pendingPlayers.data.length > 0" class="mb-4 flex items-center">
                    <input
                        type="checkbox"
                        :checked="allSelected"
                        @change="selectAll"
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                    />
                    <label class="ml-2 text-sm font-medium text-gray-700">
                        Alle auswählen ({{ pendingPlayers.data.length }})
                    </label>
                </div>

                <!-- Pending Players List -->
                <div v-if="pendingPlayers.data.length > 0" class="space-y-4">
                    <PendingPlayerCard
                        v-for="player in pendingPlayers.data"
                        :key="player.id"
                        :player="player"
                        :teams="teams"
                        :bulk-mode="bulkMode"
                        :selected="selectedPlayers.has(player.id)"
                        @assign="handleAssign"
                        @reject="handleReject"
                        @toggle-select="togglePlayerSelection"
                    />

                    <!-- Pagination -->
                    <div v-if="pendingPlayers.links.length > 3" class="bg-white shadow-xl sm:rounded-lg px-4 py-3">
                        <div class="flex items-center justify-between">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <Link
                                    v-if="pendingPlayers.prev_page_url"
                                    :href="pendingPlayers.prev_page_url"
                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    Zurück
                                </Link>
                                <Link
                                    v-if="pendingPlayers.next_page_url"
                                    :href="pendingPlayers.next_page_url"
                                    class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    Weiter
                                </Link>
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Zeige
                                        <span class="font-medium">{{ pendingPlayers.from }}</span>
                                        bis
                                        <span class="font-medium">{{ pendingPlayers.to }}</span>
                                        von
                                        <span class="font-medium">{{ pendingPlayers.total }}</span>
                                        Spielern
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                        <Link
                                            v-for="(link, index) in pendingPlayers.links"
                                            :key="index"
                                            :href="link.url"
                                            v-html="link.label"
                                            :class="[
                                                'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                                                link.active
                                                    ? 'z-10 bg-blue-50 border-blue-500 text-blue-600'
                                                    : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                                index === 0 ? 'rounded-l-md' : '',
                                                index === pendingPlayers.links.length - 1 ? 'rounded-r-md' : '',
                                            ]"
                                            :preserve-scroll="true"
                                        />
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">
                        {{ filters.search || filters.club_id ? 'Keine ausstehenden Spieler gefunden' : 'Keine ausstehenden Registrierungen' }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ filters.search || filters.club_id ? 'Versuchen Sie, die Filter zu ändern.' : 'Alle Spieler wurden bereits einem Team zugewiesen.' }}
                    </p>
                </div>

                <!-- Help Information -->
                <div class="mt-6 bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-3">
                        <svg class="inline w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Spieler zuweisen
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600">
                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">Einzelzuweisung</h4>
                            <ol class="list-decimal list-inside space-y-1">
                                <li>Klicken Sie auf einen Spieler, um Details anzuzeigen</li>
                                <li>Wählen Sie ein Team aus der Dropdown-Liste</li>
                                <li>Optional: Trikotnummer und Position festlegen</li>
                                <li>Klicken Sie auf "Team zuweisen"</li>
                            </ol>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">Mehrfachauswahl</h4>
                            <ol class="list-decimal list-inside space-y-1">
                                <li>Aktivieren Sie "Mehrfachauswahl"</li>
                                <li>Wählen Sie mehrere Spieler aus</li>
                                <li>Nutzen Sie Bulk-Aktionen (in Entwicklung)</li>
                                <li>Oder weisen Sie einzeln zu</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
