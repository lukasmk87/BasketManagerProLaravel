<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ChevronDownIcon, ChevronRightIcon, UserGroupIcon, PencilIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    team: {
        type: Object,
        required: true
    },
    season: {
        type: Object,
        required: true
    },
    club: {
        type: Object,
        required: true
    },
    defaultExpanded: {
        type: Boolean,
        default: false
    },
    showActions: {
        type: Boolean,
        default: true
    },
    showStats: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['manage-roster']);

const isExpanded = ref(props.defaultExpanded);

const toggleExpanded = () => {
    isExpanded.value = !isExpanded.value;
};

const sortedPlayers = computed(() => {
    if (!props.team.players || !Array.isArray(props.team.players)) {
        return [];
    }

    // Sort by jersey number
    return [...props.team.players].sort((a, b) => {
        const numA = parseInt(a.jersey_number) || 999;
        const numB = parseInt(b.jersey_number) || 999;
        return numA - numB;
    });
});

const playerCount = computed(() => {
    return props.team.players_count || sortedPlayers.value.length || 0;
});

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

const positionShort = (position) => {
    const positions = {
        'Point Guard': 'PG',
        'Shooting Guard': 'SG',
        'Small Forward': 'SF',
        'Power Forward': 'PF',
        'Center': 'C'
    };
    return positions[position] || position;
};

const getStatusBadge = (player) => {
    if (player.status === 'injured') {
        return { text: 'Verletzt', color: 'bg-red-100 text-red-800' };
    }
    if (player.status === 'inactive') {
        return { text: 'Inaktiv', color: 'bg-gray-100 text-gray-600' };
    }
    if (player.is_captain) {
        return { text: 'Kapitän', color: 'bg-yellow-100 text-yellow-800' };
    }
    if (player.is_starter) {
        return { text: 'Starter', color: 'bg-green-100 text-green-800' };
    }
    return null;
};

const teamStats = computed(() => {
    if (!props.showStats || !props.team.statistics) {
        return null;
    }

    return {
        avgPoints: props.team.statistics.avg_points || 0,
        avgAssists: props.team.statistics.avg_assists || 0,
        avgRebounds: props.team.statistics.avg_rebounds || 0
    };
});

const handleManageRoster = () => {
    emit('manage-roster', props.team);
};
</script>

<template>
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <!-- Team Header (Always Visible) -->
        <div
            class="flex items-center justify-between p-4 cursor-pointer hover:bg-gray-50 transition-colors"
            @click="toggleExpanded"
        >
            <div class="flex items-center space-x-3 flex-1">
                <!-- Expand/Collapse Icon -->
                <button
                    type="button"
                    class="flex-shrink-0 p-1 rounded hover:bg-gray-200 transition-colors"
                    @click.stop="toggleExpanded"
                >
                    <ChevronDownIcon
                        v-if="isExpanded"
                        class="h-5 w-5 text-gray-500 transition-transform"
                    />
                    <ChevronRightIcon
                        v-else
                        class="h-5 w-5 text-gray-500 transition-transform"
                    />
                </button>

                <!-- Team Logo (if available) -->
                <div v-if="team.logo_url" class="flex-shrink-0">
                    <img
                        :src="team.logo_url"
                        :alt="team.name"
                        class="h-10 w-10 rounded-full object-cover"
                    />
                </div>
                <div v-else class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                        <UserGroupIcon class="h-6 w-6 text-blue-600" />
                    </div>
                </div>

                <!-- Team Info -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center space-x-2">
                        <h4 class="text-sm font-medium text-gray-900 truncate">
                            {{ team.name }}
                        </h4>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            {{ playerCount }} Spieler
                        </span>
                    </div>
                    <p v-if="team.age_group" class="text-xs text-gray-500">
                        {{ team.age_group }}
                    </p>
                </div>
            </div>

            <!-- Action Button -->
            <div v-if="showActions" class="flex-shrink-0 ml-2">
                <button
                    type="button"
                    @click.stop="handleManageRoster"
                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition"
                    title="Kader verwalten"
                >
                    <PencilIcon class="h-3.5 w-3.5 mr-1" />
                    Verwalten
                </button>
            </div>
        </div>

        <!-- Roster List (Expandable) -->
        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 -translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-1"
        >
            <div v-show="isExpanded" class="border-t border-gray-200">
                <!-- Player List -->
                <div v-if="sortedPlayers.length > 0" class="divide-y divide-gray-100">
                    <div
                        v-for="player in sortedPlayers"
                        :key="player.id"
                        class="flex items-center px-4 py-3 hover:bg-gray-50 transition-colors"
                    >
                        <!-- Jersey Number -->
                        <div class="flex-shrink-0 mr-3">
                            <div class="h-8 w-8 rounded-full bg-orange-100 flex items-center justify-center">
                                <span class="text-xs font-bold text-orange-600">
                                    {{ player.jersey_number || '?' }}
                                </span>
                            </div>
                        </div>

                        <!-- Player Info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center space-x-2">
                                <p class="text-sm font-medium text-gray-900 truncate">
                                    {{ player.name }}
                                </p>

                                <!-- Status Badge -->
                                <span
                                    v-if="getStatusBadge(player)"
                                    class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium"
                                    :class="getStatusBadge(player).color"
                                >
                                    {{ getStatusBadge(player).text }}
                                </span>
                            </div>

                            <p class="text-xs text-gray-500">
                                {{ positionName(player.position) }}
                            </p>
                        </div>

                        <!-- Position Badge -->
                        <div class="flex-shrink-0 ml-2">
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-700">
                                {{ positionShort(player.position) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="px-4 py-8 text-center">
                    <UserGroupIcon class="mx-auto h-10 w-10 text-gray-400" />
                    <p class="mt-2 text-sm text-gray-500">
                        Keine Spieler im Kader
                    </p>
                    <button
                        v-if="showActions"
                        type="button"
                        @click="handleManageRoster"
                        class="mt-3 inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        Spieler hinzufügen
                    </button>
                </div>

                <!-- Team Statistics (Optional) -->
                <div v-if="showStats && teamStats" class="px-4 py-3 bg-gray-50 border-t border-gray-200">
                    <h5 class="text-xs font-medium text-gray-700 mb-2">Team-Durchschnitt</h5>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="text-center">
                            <div class="text-sm font-semibold text-gray-900">
                                {{ teamStats.avgPoints.toFixed(1) }}
                            </div>
                            <div class="text-xs text-gray-500">Punkte</div>
                        </div>
                        <div class="text-center">
                            <div class="text-sm font-semibold text-gray-900">
                                {{ teamStats.avgAssists.toFixed(1) }}
                            </div>
                            <div class="text-xs text-gray-500">Assists</div>
                        </div>
                        <div class="text-center">
                            <div class="text-sm font-semibold text-gray-900">
                                {{ teamStats.avgRebounds.toFixed(1) }}
                            </div>
                            <div class="text-xs text-gray-500">Rebounds</div>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>
