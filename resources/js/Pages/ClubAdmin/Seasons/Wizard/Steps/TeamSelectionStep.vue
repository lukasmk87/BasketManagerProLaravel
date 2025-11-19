<script setup>
import { ref, computed } from 'vue';
import TeamSelectionCard from '@/Components/Wizard/TeamSelectionCard.vue';
import { MagnifyingGlassIcon, ArrowPathIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    clubTeams: {
        type: Array,
        default: () => []
    },
    previousSeasons: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['update:form']);

const localForm = computed({
    get: () => props.form,
    set: (value) => emit('update:form', value)
});

const selectionMode = ref('manual'); // 'manual' or 'copy'
const searchQuery = ref('');
const filterAgeGroup = ref('');
const filterLeague = ref('');

const selectedTeamIds = computed(() => {
    return new Set(localForm.value.selected_teams || []);
});

const selectedSeason = computed(() => {
    if (!localForm.value.copy_from_season) return null;
    return props.previousSeasons.find(s => s.id === localForm.value.copy_from_season);
});

const filteredTeams = computed(() => {
    let teams = props.clubTeams;

    // Search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        teams = teams.filter(team =>
            team.name?.toLowerCase().includes(query) ||
            team.age_group?.toLowerCase().includes(query) ||
            team.league?.toLowerCase().includes(query)
        );
    }

    // Age group filter
    if (filterAgeGroup.value) {
        teams = teams.filter(team => team.age_group === filterAgeGroup.value);
    }

    // League filter
    if (filterLeague.value) {
        teams = teams.filter(team => team.league === filterLeague.value);
    }

    return teams;
});

const availableAgeGroups = computed(() => {
    const groups = new Set(props.clubTeams.map(t => t.age_group).filter(Boolean));
    return Array.from(groups).sort();
});

const availableLeagues = computed(() => {
    const leagues = new Set(props.clubTeams.map(t => t.league).filter(Boolean));
    return Array.from(leagues).sort();
});

const toggleTeam = (team) => {
    const teamId = team.id;
    let newSelected = [...(localForm.value.selected_teams || [])];

    if (selectedTeamIds.value.has(teamId)) {
        newSelected = newSelected.filter(id => id !== teamId);
    } else {
        newSelected.push(teamId);
    }

    localForm.value.selected_teams = newSelected;
};

const selectAllFiltered = () => {
    const allFilteredIds = filteredTeams.value.map(t => t.id);
    localForm.value.selected_teams = [...new Set([
        ...(localForm.value.selected_teams || []),
        ...allFilteredIds
    ])];
};

const deselectAll = () => {
    localForm.value.selected_teams = [];
};

const copyTeamsFromSeason = () => {
    if (!selectedSeason.value) return;

    // Copy team IDs from selected season
    const teamIds = selectedSeason.value.teams?.map(t => t.id) || [];
    localForm.value.selected_teams = teamIds;

    // Switch to manual mode to show selection
    selectionMode.value = 'manual';
};

const handleSeasonChange = (event) => {
    const seasonId = event.target.value ? parseInt(event.target.value) : null;
    localForm.value.copy_from_season = seasonId;

    if (seasonId) {
        copyTeamsFromSeason();
    }
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h2 class="text-xl font-semibold text-gray-900">Teams auswählen</h2>
            <p class="mt-1 text-sm text-gray-500">
                Wählen Sie die Teams aus, die an dieser Saison teilnehmen sollen.
            </p>
        </div>

        <!-- Mode Selector -->
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <label class="text-sm font-medium text-gray-700 mb-3 block">
                Auswahlmodus
            </label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <button
                    type="button"
                    @click="selectionMode = 'manual'"
                    :class="[
                        'flex items-center justify-center px-4 py-3 border-2 rounded-lg text-sm font-medium transition-all',
                        selectionMode === 'manual'
                            ? 'border-blue-500 bg-blue-50 text-blue-700'
                            : 'border-gray-300 bg-white text-gray-700 hover:border-gray-400'
                    ]"
                >
                    Manuelle Auswahl
                </button>
                <button
                    type="button"
                    @click="selectionMode = 'copy'"
                    :class="[
                        'flex items-center justify-center px-4 py-3 border-2 rounded-lg text-sm font-medium transition-all',
                        selectionMode === 'copy'
                            ? 'border-blue-500 bg-blue-50 text-blue-700'
                            : 'border-gray-300 bg-white text-gray-700 hover:border-gray-400'
                    ]"
                >
                    <ArrowPathIcon class="h-4 w-4 mr-2" />
                    Von vorheriger Saison kopieren
                </button>
            </div>
        </div>

        <!-- Copy Mode -->
        <div v-show="selectionMode === 'copy'" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Saison auswählen
                </label>
                <select
                    :value="localForm.copy_from_season"
                    @change="handleSeasonChange"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                >
                    <option :value="null">Saison auswählen...</option>
                    <option
                        v-for="season in previousSeasons"
                        :key="season.id"
                        :value="season.id"
                    >
                        {{ season.name }} ({{ season.teams_count || 0 }} Teams)
                    </option>
                </select>
            </div>

            <div v-if="selectedSeason" class="bg-blue-50 border border-blue-200 rounded-md p-4">
                <h4 class="text-sm font-medium text-blue-900 mb-2">
                    Teams aus "{{ selectedSeason.name }}"
                </h4>
                <div v-if="selectedSeason.teams && selectedSeason.teams.length > 0" class="text-sm text-blue-700">
                    <ul class="list-disc list-inside space-y-1">
                        <li v-for="team in selectedSeason.teams.slice(0, 5)" :key="team.id">
                            {{ team.name }}
                            <span v-if="team.players_count" class="text-blue-600">
                                ({{ team.players_count }} Spieler)
                            </span>
                        </li>
                        <li v-if="selectedSeason.teams.length > 5" class="text-blue-600">
                            ... und {{ selectedSeason.teams.length - 5 }} weitere
                        </li>
                    </ul>
                    <button
                        type="button"
                        @click="copyTeamsFromSeason"
                        class="mt-3 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        Teams übernehmen
                    </button>
                </div>
                <p v-else class="text-sm text-blue-700">
                    Diese Saison hat keine Teams.
                </p>
            </div>
        </div>

        <!-- Manual Selection Mode -->
        <div v-show="selectionMode === 'manual'" class="space-y-4">
            <!-- Filters and Search -->
            <div class="flex flex-col sm:flex-row gap-3">
                <!-- Search -->
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
                    </div>
                    <input
                        v-model="searchQuery"
                        type="text"
                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        placeholder="Teams suchen..."
                    />
                </div>

                <!-- Age Group Filter -->
                <select
                    v-model="filterAgeGroup"
                    class="block w-full sm:w-40 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                >
                    <option value="">Alle Altersgruppen</option>
                    <option v-for="group in availableAgeGroups" :key="group" :value="group">
                        {{ group }}
                    </option>
                </select>

                <!-- League Filter -->
                <select
                    v-model="filterLeague"
                    class="block w-full sm:w-40 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                >
                    <option value="">Alle Ligen</option>
                    <option v-for="league in availableLeagues" :key="league" :value="league">
                        {{ league }}
                    </option>
                </select>
            </div>

            <!-- Bulk Actions -->
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-600">
                    <span class="font-medium">{{ selectedTeamIds.size }}</span> von {{ clubTeams.length }} Teams ausgewählt
                </div>
                <div class="flex space-x-2">
                    <button
                        type="button"
                        @click="selectAllFiltered"
                        class="px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-100 rounded-md hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        Alle auswählen
                    </button>
                    <button
                        type="button"
                        @click="deselectAll"
                        class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                    >
                        Auswahl aufheben
                    </button>
                </div>
            </div>

            <!-- Team Grid -->
            <div v-if="filteredTeams.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <TeamSelectionCard
                    v-for="team in filteredTeams"
                    :key="team.id"
                    :team="team"
                    :selected="selectedTeamIds.has(team.id)"
                    :disabled="team.disabled || false"
                    :show-stats="true"
                    @toggle="toggleTeam"
                />
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                <p class="text-sm text-gray-500">
                    {{ searchQuery || filterAgeGroup || filterLeague
                        ? 'Keine Teams gefunden. Versuchen Sie es mit anderen Filtern.'
                        : 'Keine Teams verfügbar.'
                    }}
                </p>
            </div>
        </div>

        <!-- Selection Summary -->
        <div v-if="selectedTeamIds.size === 0" class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        Sie haben noch keine Teams ausgewählt. Wählen Sie mindestens ein Team aus, um fortzufahren.
                    </p>
                </div>
            </div>
        </div>

        <!-- Info Box -->
        <div class="bg-gray-50 border border-gray-200 rounded-md p-4">
            <h4 class="text-sm font-medium text-gray-900 mb-2">Hinweise</h4>
            <ul class="text-xs text-gray-600 space-y-1 list-disc list-inside">
                <li>Teams können später noch hinzugefügt oder entfernt werden</li>
                <li>Beim Kopieren werden nur die Teams übernommen, nicht die Kader</li>
                <li>Inaktive Teams werden grau dargestellt</li>
            </ul>
        </div>
    </div>
</template>
