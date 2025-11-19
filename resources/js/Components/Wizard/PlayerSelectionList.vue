<script setup>
import { ref, computed } from 'vue';
import { MagnifyingGlassIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline';
import { VueDraggable } from 'vue-draggable-plus';

const props = defineProps({
    availablePlayers: {
        type: Array,
        default: () => []
    },
    selectedPlayers: {
        type: Array,
        default: () => []
    },
    teamId: {
        type: Number,
        required: true
    },
    readonly: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:selectedPlayers', 'jersey-conflict']);

const searchQuery = ref('');
const sortBy = ref('name'); // 'name', 'jersey', 'position'

const selectedPlayerIds = computed(() => {
    return new Set(props.selectedPlayers.map(p => p.player_id || p.id));
});

const filteredPlayers = computed(() => {
    let players = props.availablePlayers;

    // Search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        players = players.filter(player =>
            player.name?.toLowerCase().includes(query) ||
            player.jersey_number?.toString().includes(query) ||
            player.position?.toLowerCase().includes(query)
        );
    }

    // Sort
    if (sortBy.value === 'name') {
        players = [...players].sort((a, b) => (a.name || '').localeCompare(b.name || '', 'de'));
    } else if (sortBy.value === 'jersey') {
        players = [...players].sort((a, b) => (a.jersey_number || 999) - (b.jersey_number || 999));
    } else if (sortBy.value === 'position') {
        players = [...players].sort((a, b) => (a.position || '').localeCompare(b.position || ''));
    }

    return players;
});

const jerseyConflicts = computed(() => {
    const conflicts = new Map();
    const jerseyNumbers = {};

    props.selectedPlayers.forEach(player => {
        const jersey = player.jersey_number;
        if (jersey) {
            if (jerseyNumbers[jersey]) {
                if (!conflicts.has(jersey)) {
                    conflicts.set(jersey, [jerseyNumbers[jersey]]);
                }
                conflicts.get(jersey).push(player);
            } else {
                jerseyNumbers[jersey] = player;
            }
        }
    });

    return conflicts;
});

const hasConflicts = computed(() => jerseyConflicts.value.size > 0);

const isPlayerSelected = (player) => {
    return selectedPlayerIds.value.has(player.player_id || player.id);
};

const togglePlayer = (player) => {
    if (props.readonly) return;

    const playerId = player.player_id || player.id;
    let newSelected;

    if (isPlayerSelected(player)) {
        // Remove player
        newSelected = props.selectedPlayers.filter(p =>
            (p.player_id || p.id) !== playerId
        );
    } else {
        // Add player
        newSelected = [
            ...props.selectedPlayers,
            {
                player_id: playerId,
                name: player.name,
                jersey_number: player.jersey_number || null,
                position: player.position || 'PG',
                status: 'active'
            }
        ];
    }

    emit('update:selectedPlayers', newSelected);
};

const toggleAll = () => {
    if (props.readonly) return;

    if (selectedPlayerIds.value.size === props.availablePlayers.length) {
        // Deselect all
        emit('update:selectedPlayers', []);
    } else {
        // Select all
        const allSelected = props.availablePlayers.map(player => ({
            player_id: player.player_id || player.id,
            name: player.name,
            jersey_number: player.jersey_number || null,
            position: player.position || 'PG',
            status: 'active'
        }));
        emit('update:selectedPlayers', allSelected);
    }
};

const updatePlayerField = (player, field, value) => {
    if (props.readonly) return;

    const newSelected = props.selectedPlayers.map(p => {
        if ((p.player_id || p.id) === (player.player_id || player.id)) {
            const updated = { ...p, [field]: value };

            // Check for jersey conflicts
            if (field === 'jersey_number' && value) {
                const conflicts = props.selectedPlayers.filter(sp =>
                    sp.jersey_number === value &&
                    (sp.player_id || sp.id) !== (player.player_id || player.id)
                );

                if (conflicts.length > 0) {
                    emit('jersey-conflict', { player: updated, conflictsWith: conflicts });
                }
            }

            return updated;
        }
        return p;
    });

    emit('update:selectedPlayers', newSelected);
};

const getPlayerData = (player) => {
    const playerId = player.player_id || player.id;
    return props.selectedPlayers.find(p => (p.player_id || p.id) === playerId) || player;
};

const positionName = (position) => {
    const positions = {
        'PG': 'Point Guard',
        'SG': 'Shooting Guard',
        'SF': 'Small Forward',
        'PF': 'Power Forward',
        'C': 'Center'
    };
    return positions[position] || position;
};
</script>

<template>
    <div class="space-y-4">
        <!-- Header Controls -->
        <div class="flex items-center justify-between gap-4">
            <!-- Search -->
            <div class="relative flex-1 max-w-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
                </div>
                <input
                    v-model="searchQuery"
                    type="text"
                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    placeholder="Spieler suchen..."
                />
            </div>

            <!-- Sort -->
            <div class="flex items-center space-x-2">
                <label class="text-sm text-gray-700">Sortieren:</label>
                <select
                    v-model="sortBy"
                    class="block w-auto border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                >
                    <option value="name">Name</option>
                    <option value="jersey">Nummer</option>
                    <option value="position">Position</option>
                </select>
            </div>

            <!-- Bulk Actions -->
            <button
                v-if="!readonly"
                type="button"
                @click="toggleAll"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                {{ selectedPlayerIds.size === availablePlayers.length ? 'Alle abwählen' : 'Alle auswählen' }}
            </button>
        </div>

        <!-- Jersey Conflicts Warning -->
        <div v-if="hasConflicts" class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <ExclamationTriangleIcon class="h-5 w-5 text-yellow-400" />
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        <strong>Achtung:</strong> Es gibt Konflikte bei Rückennummern.
                        Mehrere Spieler haben dieselbe Nummer zugewiesen.
                    </p>
                </div>
            </div>
        </div>

        <!-- Selected Count -->
        <div class="flex items-center justify-between text-sm text-gray-600">
            <span>{{ selectedPlayerIds.size }} von {{ availablePlayers.length }} Spieler ausgewählt</span>
        </div>

        <!-- Player List -->
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="w-12 px-6 py-3">
                                <span class="sr-only">Auswahl</span>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Spieler
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nummer
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Position
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr
                            v-for="player in filteredPlayers"
                            :key="player.player_id || player.id"
                            :class="[
                                isPlayerSelected(player) ? 'bg-blue-50' : 'hover:bg-gray-50',
                                readonly ? '' : 'cursor-pointer'
                            ]"
                            @click="togglePlayer(player)"
                        >
                            <!-- Checkbox -->
                            <td class="px-6 py-4 whitespace-nowrap" @click.stop>
                                <input
                                    type="checkbox"
                                    :checked="isPlayerSelected(player)"
                                    @change="togglePlayer(player)"
                                    :disabled="readonly"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                />
                            </td>

                            <!-- Player Name -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ player.name }}
                                </div>
                            </td>

                            <!-- Jersey Number -->
                            <td class="px-6 py-4 whitespace-nowrap" @click.stop>
                                <input
                                    v-if="isPlayerSelected(player)"
                                    type="number"
                                    :value="getPlayerData(player).jersey_number"
                                    @input="updatePlayerField(getPlayerData(player), 'jersey_number', $event.target.value ? parseInt($event.target.value) : null)"
                                    min="0"
                                    max="99"
                                    :readonly="readonly"
                                    placeholder="#"
                                    :class="[
                                        'w-16 text-center text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500',
                                        jerseyConflicts.has(getPlayerData(player).jersey_number) ? 'border-red-300 bg-red-50' : ''
                                    ]"
                                />
                                <span v-else class="text-sm text-gray-500">-</span>
                            </td>

                            <!-- Position -->
                            <td class="px-6 py-4 whitespace-nowrap" @click.stop>
                                <select
                                    v-if="isPlayerSelected(player)"
                                    :value="getPlayerData(player).position"
                                    @change="updatePlayerField(getPlayerData(player), 'position', $event.target.value)"
                                    :disabled="readonly"
                                    class="text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option value="PG">PG</option>
                                    <option value="SG">SG</option>
                                    <option value="SF">SF</option>
                                    <option value="PF">PF</option>
                                    <option value="C">C</option>
                                </select>
                                <span v-else class="text-sm text-gray-500">-</span>
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4 whitespace-nowrap" @click.stop>
                                <select
                                    v-if="isPlayerSelected(player)"
                                    :value="getPlayerData(player).status"
                                    @change="updatePlayerField(getPlayerData(player), 'status', $event.target.value)"
                                    :disabled="readonly"
                                    :class="[
                                        'text-sm rounded-md focus:ring-blue-500 focus:border-blue-500',
                                        getPlayerData(player).status === 'active' ? 'bg-green-50 text-green-800 border-green-300' :
                                        getPlayerData(player).status === 'injured' ? 'bg-red-50 text-red-800 border-red-300' :
                                        'bg-gray-50 text-gray-800 border-gray-300'
                                    ]"
                                >
                                    <option value="active">Aktiv</option>
                                    <option value="injured">Verletzt</option>
                                    <option value="inactive">Inaktiv</option>
                                </select>
                                <span v-else class="text-sm text-gray-500">-</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Empty State -->
            <div v-if="filteredPlayers.length === 0" class="text-center py-12">
                <p class="text-sm text-gray-500">
                    {{ searchQuery ? 'Keine Spieler gefunden' : 'Keine Spieler verfügbar' }}
                </p>
            </div>
        </div>
    </div>
</template>
