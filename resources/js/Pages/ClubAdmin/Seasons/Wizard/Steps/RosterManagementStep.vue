<script setup>
import { ref, computed } from 'vue';
import PlayerSelectionList from '@/Components/Wizard/PlayerSelectionList.vue';
import { ChevronDownIcon, ChevronRightIcon, UserGroupIcon, CheckCircleIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    selectedTeams: {
        type: Array,
        default: () => []
    },
    availablePlayers: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits(['update:form', 'jersey-conflict']);

const localForm = computed({
    get: () => props.form,
    set: (value) => emit('update:form', value)
});

const expandedTeams = ref(new Set());

const toggleTeam = (teamId) => {
    if (expandedTeams.value.has(teamId)) {
        expandedTeams.value.delete(teamId);
    } else {
        expandedTeams.value.add(teamId);
    }
};

const isTeamExpanded = (teamId) => {
    return expandedTeams.value.has(teamId);
};

const getTeamRoster = (teamId) => {
    return localForm.value.team_rosters?.[teamId] || [];
};

const updateTeamRoster = (teamId, roster) => {
    if (!localForm.value.team_rosters) {
        localForm.value.team_rosters = {};
    }

    localForm.value.team_rosters = {
        ...localForm.value.team_rosters,
        [teamId]: roster
    };
};

const getTeamPlayers = (teamId) => {
    return props.availablePlayers[teamId] || [];
};

const getTeamPlayerCount = (teamId) => {
    const roster = getTeamRoster(teamId);
    return roster.length;
};

const isTeamComplete = (teamId) => {
    const count = getTeamPlayerCount(teamId);
    return count >= 5; // Minimum 5 players
};

const hasTeamWarning = (teamId) => {
    const count = getTeamPlayerCount(teamId);
    return count > 0 && count < 5; // Less than minimum but some selected
};

const totalPlayers = computed(() => {
    return Object.values(localForm.value.team_rosters || {})
        .reduce((sum, roster) => sum + roster.length, 0);
});

const teamsWithPlayers = computed(() => {
    return props.selectedTeams.filter(team =>
        getTeamPlayerCount(team.id) > 0
    ).length;
});

const copyRosterFromPreviousSeason = async (teamId) => {
    // This would need backend support to fetch previous season rosters
    // For now, we'll just show a placeholder
    alert('Diese Funktion wird implementiert, sobald die Backend-Integration abgeschlossen ist.');
};

const clearTeamRoster = (teamId) => {
    if (confirm('Möchten Sie wirklich alle Spieler dieses Teams entfernen?')) {
        updateTeamRoster(teamId, []);
    }
};

const handleJerseyConflict = (conflict) => {
    emit('jersey-conflict', conflict);
};

const expandAll = () => {
    props.selectedTeams.forEach(team => {
        expandedTeams.value.add(team.id);
    });
};

const collapseAll = () => {
    expandedTeams.value.clear();
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h2 class="text-xl font-semibold text-gray-900">Kader verwalten</h2>
            <p class="mt-1 text-sm text-gray-500">
                Weisen Sie jedem Team Spieler zu und konfigurieren Sie deren Rollen.
            </p>
        </div>

        <!-- Summary Stats -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <div class="text-2xl font-bold text-blue-900">{{ selectedTeams.length }}</div>
                    <div class="text-sm text-blue-700">Teams</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-blue-900">{{ teamsWithPlayers }}</div>
                    <div class="text-sm text-blue-700">Teams mit Kaderm</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-blue-900">{{ totalPlayers }}</div>
                    <div class="text-sm text-blue-700">Spieler gesamt</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="flex justify-end space-x-2">
            <button
                type="button"
                @click="expandAll"
                class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                Alle ausklappen
            </button>
            <button
                type="button"
                @click="collapseAll"
                class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                Alle einklappen
            </button>
        </div>

        <!-- Teams Accordion -->
        <div class="space-y-3">
            <div
                v-for="team in selectedTeams"
                :key="team.id"
                class="bg-white border border-gray-200 rounded-lg overflow-hidden"
            >
                <!-- Team Header -->
                <button
                    type="button"
                    @click="toggleTeam(team.id)"
                    class="w-full flex items-center justify-between p-4 hover:bg-gray-50 transition-colors"
                >
                    <div class="flex items-center space-x-3 flex-1">
                        <!-- Expand Icon -->
                        <ChevronDownIcon
                            v-if="isTeamExpanded(team.id)"
                            class="h-5 w-5 text-gray-500"
                        />
                        <ChevronRightIcon
                            v-else
                            class="h-5 w-5 text-gray-500"
                        />

                        <!-- Team Logo -->
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
                        <div class="flex-1 text-left">
                            <div class="flex items-center space-x-2">
                                <h4 class="text-sm font-medium text-gray-900">
                                    {{ team.name }}
                                </h4>

                                <!-- Status Badge -->
                                <CheckCircleIcon
                                    v-if="isTeamComplete(team.id)"
                                    class="h-5 w-5 text-green-500"
                                    title="Vollständiger Kader"
                                />
                                <ExclamationTriangleIcon
                                    v-else-if="hasTeamWarning(team.id)"
                                    class="h-5 w-5 text-yellow-500"
                                    title="Weniger als 5 Spieler"
                                />
                            </div>
                            <p v-if="team.age_group" class="text-xs text-gray-500">
                                {{ team.age_group }}
                            </p>
                        </div>

                        <!-- Player Count -->
                        <div class="text-right">
                            <div class="text-sm font-medium text-gray-900">
                                {{ getTeamPlayerCount(team.id) }} Spieler
                            </div>
                            <div v-if="getTeamPlayerCount(team.id) < 5" class="text-xs text-yellow-600">
                                Min. 5 empfohlen
                            </div>
                        </div>
                    </div>
                </button>

                <!-- Team Roster Content -->
                <div
                    v-show="isTeamExpanded(team.id)"
                    class="border-t border-gray-200 p-4 bg-gray-50"
                >
                    <!-- Roster Actions -->
                    <div class="flex justify-between items-center mb-4">
                        <h5 class="text-sm font-medium text-gray-700">
                            Spieler-Auswahl
                        </h5>
                        <div class="flex space-x-2">
                            <button
                                type="button"
                                @click="copyRosterFromPreviousSeason(team.id)"
                                class="px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-100 rounded-md hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                Von vorheriger Saison kopieren
                            </button>
                            <button
                                v-if="getTeamPlayerCount(team.id) > 0"
                                type="button"
                                @click="clearTeamRoster(team.id)"
                                class="px-3 py-1.5 text-xs font-medium text-red-700 bg-red-100 rounded-md hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                            >
                                Alle entfernen
                            </button>
                        </div>
                    </div>

                    <!-- Player Selection List -->
                    <PlayerSelectionList
                        :available-players="getTeamPlayers(team.id)"
                        :selected-players="getTeamRoster(team.id)"
                        :team-id="team.id"
                        @update:selected-players="updateTeamRoster(team.id, $event)"
                        @jersey-conflict="handleJerseyConflict"
                    />

                    <!-- Minimum Warning -->
                    <div v-if="hasTeamWarning(team.id)" class="mt-4 bg-yellow-50 border-l-4 border-yellow-400 p-3">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <ExclamationTriangleIcon class="h-5 w-5 text-yellow-400" />
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    <strong>Empfehlung:</strong> Ein Team sollte mindestens 5 Spieler haben.
                                    Aktuell: {{ getTeamPlayerCount(team.id) }} Spieler.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- No Teams Selected -->
        <div v-if="selectedTeams.length === 0" class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
            <UserGroupIcon class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-2 text-sm font-medium text-gray-900">Keine Teams ausgewählt</h3>
            <p class="mt-1 text-sm text-gray-500">
                Gehen Sie zurück zu Schritt 2 und wählen Sie Teams aus.
            </p>
        </div>

        <!-- Info Box -->
        <div class="bg-gray-50 border border-gray-200 rounded-md p-4">
            <h4 class="text-sm font-medium text-gray-900 mb-2">Hinweise</h4>
            <ul class="text-xs text-gray-600 space-y-1 list-disc list-inside">
                <li>Mindestens 5 Spieler pro Team werden empfohlen</li>
                <li>Rückennummern müssen innerhalb eines Teams eindeutig sein</li>
                <li>Spieler-Status: Aktiv (spielberechtigt), Verletzt, Inaktiv</li>
                <li>Kader können später noch angepasst werden</li>
                <li>Teams ohne Spieler werden trotzdem erstellt</li>
            </ul>
        </div>
    </div>
</template>
