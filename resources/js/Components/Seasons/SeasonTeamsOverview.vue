<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import TeamRosterPreview from './TeamRosterPreview.vue';
import { PlusIcon, UserGroupIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    season: {
        type: Object,
        required: true
    },
    club: {
        type: Object,
        required: true
    },
    teams: {
        type: Array,
        default: () => []
    },
    permissions: {
        type: Object,
        default: () => ({})
    },
    loading: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['add-team', 'manage-roster', 'view-team']);

const viewMode = ref('list'); // 'list' or 'grid'

const canManageTeams = computed(() => {
    return props.permissions?.canEdit ||
           props.permissions?.canManage ||
           false;
});

const sortedTeams = computed(() => {
    if (!props.teams || !Array.isArray(props.teams)) {
        return [];
    }

    // Sort by team name
    return [...props.teams].sort((a, b) => {
        return a.name.localeCompare(b.name, 'de');
    });
});

const teamCount = computed(() => {
    return props.season.teams_count || sortedTeams.value.length || 0;
});

const handleAddTeam = () => {
    emit('add-team');
    router.visit(route('club.teams.create', {
        club: props.club.id,
        season: props.season.id
    }));
};

const handleManageRoster = (team) => {
    emit('manage-roster', team);
    router.visit(route('club.teams.roster', {
        club: props.club.id,
        team: team.id
    }));
};

const handleViewTeam = (team) => {
    emit('view-team', team);
    router.visit(route('club.teams.show', {
        club: props.club.id,
        team: team.id
    }));
};
</script>

<template>
    <div>
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-medium text-gray-900">Teams</h3>
                <p class="mt-1 text-sm text-gray-500">
                    {{ teamCount }} {{ teamCount === 1 ? 'Team' : 'Teams' }} in dieser Saison
                </p>
            </div>

            <div class="flex items-center space-x-3">
                <!-- View Mode Toggle -->
                <div class="flex items-center bg-gray-100 rounded-md p-1">
                    <button
                        @click="viewMode = 'list'"
                        :class="[
                            'px-3 py-1.5 text-xs font-medium rounded transition-colors',
                            viewMode === 'list'
                                ? 'bg-white text-gray-900 shadow-sm'
                                : 'text-gray-600 hover:text-gray-900'
                        ]"
                    >
                        Liste
                    </button>
                    <button
                        @click="viewMode = 'grid'"
                        :class="[
                            'px-3 py-1.5 text-xs font-medium rounded transition-colors',
                            viewMode === 'grid'
                                ? 'bg-white text-gray-900 shadow-sm'
                                : 'text-gray-600 hover:text-gray-900'
                        ]"
                    >
                        Grid
                    </button>
                </div>

                <!-- Add Team Button -->
                <button
                    v-if="canManageTeams && season.status !== 'completed'"
                    type="button"
                    @click="handleAddTeam"
                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition"
                >
                    <PlusIcon class="h-4 w-4 mr-1.5" />
                    Team hinzufügen
                </button>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-12">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        </div>

        <!-- Teams List View -->
        <div v-else-if="viewMode === 'list' && sortedTeams.length > 0" class="space-y-3">
            <TeamRosterPreview
                v-for="team in sortedTeams"
                :key="team.id"
                :team="team"
                :season="season"
                :club="club"
                :default-expanded="false"
                :show-actions="canManageTeams"
                :show-stats="true"
                @manage-roster="handleManageRoster"
            />
        </div>

        <!-- Teams Grid View -->
        <div
            v-else-if="viewMode === 'grid' && sortedTeams.length > 0"
            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
        >
            <div
                v-for="team in sortedTeams"
                :key="team.id"
                class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-md transition-shadow cursor-pointer"
                @click="handleViewTeam(team)"
            >
                <!-- Team Card -->
                <div class="p-4">
                    <!-- Team Logo & Name -->
                    <div class="flex items-center space-x-3 mb-4">
                        <div v-if="team.logo_url" class="flex-shrink-0">
                            <img
                                :src="team.logo_url"
                                :alt="team.name"
                                class="h-12 w-12 rounded-full object-cover"
                            />
                        </div>
                        <div v-else class="flex-shrink-0">
                            <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
                                <UserGroupIcon class="h-7 w-7 text-blue-600" />
                            </div>
                        </div>

                        <div class="flex-1 min-w-0">
                            <h4 class="text-sm font-medium text-gray-900 truncate">
                                {{ team.name }}
                            </h4>
                            <p v-if="team.age_group" class="text-xs text-gray-500">
                                {{ team.age_group }}
                            </p>
                        </div>
                    </div>

                    <!-- Team Stats -->
                    <div class="grid grid-cols-3 gap-3 mb-4">
                        <div class="text-center">
                            <div class="text-lg font-semibold text-gray-900">
                                {{ team.players_count || 0 }}
                            </div>
                            <div class="text-xs text-gray-500">Spieler</div>
                        </div>
                        <div class="text-center">
                            <div class="text-lg font-semibold text-gray-900">
                                {{ team.games_count || 0 }}
                            </div>
                            <div class="text-xs text-gray-500">Spiele</div>
                        </div>
                        <div class="text-center">
                            <div class="text-lg font-semibold text-gray-900">
                                {{ team.wins_count || 0 }}
                            </div>
                            <div class="text-xs text-gray-500">Siege</div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div v-if="canManageTeams" class="flex space-x-2 pt-3 border-t border-gray-200">
                        <button
                            @click.stop="handleViewTeam(team)"
                            class="flex-1 px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition"
                        >
                            Details
                        </button>
                        <button
                            @click.stop="handleManageRoster(team)"
                            class="flex-1 px-3 py-1.5 text-xs font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition"
                        >
                            Kader
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
            <UserGroupIcon class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-2 text-sm font-medium text-gray-900">Keine Teams</h3>
            <p class="mt-1 text-sm text-gray-500">
                Diese Saison hat noch keine zugewiesenen Teams.
            </p>
            <div v-if="canManageTeams && season.status !== 'completed'" class="mt-6">
                <button
                    type="button"
                    @click="handleAddTeam"
                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    <PlusIcon class="h-4 w-4 mr-2" />
                    Erstes Team hinzufügen
                </button>
            </div>
        </div>
    </div>
</template>
