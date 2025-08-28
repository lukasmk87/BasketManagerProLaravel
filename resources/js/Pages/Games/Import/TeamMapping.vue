<template>
    <AppLayout title="Team-Zuordnung">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Team-Zuordnung für Import
                </h2>
                <div class="flex space-x-3">
                    <SecondaryButton 
                        :href="route('games.import.index')"
                        as="Link"
                    >
                        ← Zurück zum Import
                    </SecondaryButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Info Card -->
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                <strong>Team-Zuordnung erforderlich</strong><br>
                                Die iCAL-Datei "{{ fileName }}" enthält {{ totalGames }} Spiele mit {{ icalTeams.length }} verschiedenen Teams. 
                                Ordnen Sie die Teams aus der iCAL-Datei den entsprechenden Teams in Ihrem System zu.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Selected Team Info -->
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <h3 class="text-lg font-medium text-green-800 mb-2">
                        Ausgewähltes Team für Import
                    </h3>
                    <div class="text-green-700">
                        <strong>{{ selectedTeam.name }}</strong> ({{ selectedTeam.club.name }})
                    </div>
                    <p class="text-sm text-green-600 mt-1">
                        Nur Spiele, die dieses Team betreffen, werden importiert.
                    </p>
                </div>

                <!-- Team Mapping Form -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            Team-Zuordnung
                        </h3>

                        <form @submit.prevent="submitMapping" class="space-y-6">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Team aus iCAL-Datei
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Zuordnung zu System-Team
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Status
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="icalTeam in icalTeams" :key="icalTeam" class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ icalTeam }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <select
                                                    v-model="form.team_mapping[icalTeam]"
                                                    class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                                    @change="updateMappingStatus(icalTeam)"
                                                >
                                                    <option value="">-- Nicht zuordnen --</option>
                                                    <option value="selectedTeam">{{ selectedTeam.name }} (Importiertes Team)</option>
                                                    <optgroup label="Andere Teams">
                                                        <option 
                                                            v-for="team in availableTeams.filter(t => t.id !== selectedTeam.id)" 
                                                            :key="team.id" 
                                                            :value="team.id"
                                                        >
                                                            {{ team.name }} ({{ team.club.name }})
                                                        </option>
                                                    </optgroup>
                                                </select>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span 
                                                    v-if="form.team_mapping[icalTeam] === 'selectedTeam'"
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                                                >
                                                    Import-Team
                                                </span>
                                                <span 
                                                    v-else-if="form.team_mapping[icalTeam] && form.team_mapping[icalTeam] !== ''"
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                                                >
                                                    Zugeordnet
                                                </span>
                                                <span 
                                                    v-else
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800"
                                                >
                                                    Nicht zugeordnet
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Summary -->
                            <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                                <h4 class="text-sm font-medium text-gray-900 mb-2">Zuordnungs-Übersicht:</h4>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-600">Gesamt:</span>
                                        <span class="font-medium ml-2">{{ icalTeams.length }} Teams</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-600">Zugeordnet:</span>
                                        <span class="font-medium ml-2">{{ mappedTeamsCount }} Teams</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-600">Import-Team:</span>
                                        <span class="font-medium ml-2">{{ selectedTeamMappingsCount }} Teams</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Error Display -->
                            <div v-if="form.errors && Object.keys(form.errors).length > 0" class="mt-4">
                                <div class="bg-red-50 border border-red-200 rounded-md p-4">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <h3 class="text-sm font-medium text-red-800">Fehler bei der Zuordnung:</h3>
                                            <div class="mt-2 text-sm text-red-700">
                                                <ul class="list-disc pl-5 space-y-1">
                                                    <li v-for="(error, field) in form.errors" :key="field">
                                                        {{ Array.isArray(error) ? error[0] : error }}
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex items-center justify-between space-x-4 pt-4">
                                <SecondaryButton
                                    type="button"
                                    @click="resetMapping"
                                    :disabled="form.processing"
                                >
                                    Zurücksetzen
                                </SecondaryButton>

                                <div class="flex space-x-3">
                                    <SecondaryButton
                                        :href="route('games.import.index')"
                                        as="Link"
                                        :disabled="form.processing"
                                    >
                                        Abbrechen
                                    </SecondaryButton>

                                    <PrimaryButton
                                        type="submit"
                                        :disabled="form.processing || selectedTeamMappingsCount === 0"
                                    >
                                        <span v-if="form.processing">Verarbeiten...</span>
                                        <span v-else>Vorschau anzeigen</span>
                                    </PrimaryButton>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Help Text -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-yellow-800 mb-2">💡 Hinweise zur Team-Zuordnung:</h4>
                    <ul class="text-sm text-yellow-700 space-y-1">
                        <li>• <strong>Import-Team:</strong> Wählen Sie "{{ selectedTeam.name }} (Importiertes Team)" für das Team, das Sie importieren möchten.</li>
                        <li>• <strong>Gegnerteams:</strong> Ordnen Sie bekannte Gegnerteams zu, um Doppelungen zu vermeiden.</li>
                        <li>• <strong>Nicht zuordnen:</strong> Teams, die nicht zugeordnet werden, werden in Spielen als externe Teams gespeichert.</li>
                        <li>• <strong>Mindestens ein Team</strong> muss als Import-Team markiert werden, damit Spiele importiert werden können.</li>
                    </ul>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

// Props
const props = defineProps({
    selectedTeam: {
        type: Object,
        required: true
    },
    icalTeams: {
        type: Array,
        required: true
    },
    availableTeams: {
        type: Array,
        required: true
    },
    fileName: {
        type: String,
        required: true
    },
    totalGames: {
        type: Number,
        required: true
    }
});

// Form setup
const form = useForm({
    team_id: props.selectedTeam.id,
    team_mapping: {}
});

// Initialize team mapping
props.icalTeams.forEach(team => {
    form.team_mapping[team] = '';
});

// Computed properties
const mappedTeamsCount = computed(() => {
    return Object.values(form.team_mapping).filter(value => value && value !== '').length;
});

const selectedTeamMappingsCount = computed(() => {
    return Object.values(form.team_mapping).filter(value => value === 'selectedTeam').length;
});

// Methods
const updateMappingStatus = (icalTeam) => {
    // Convert 'selectedTeam' to actual team ID for submission
    if (form.team_mapping[icalTeam] === 'selectedTeam') {
        form.team_mapping[icalTeam] = props.selectedTeam.id;
    }
};

const submitMapping = () => {
    // Prepare the mapping data for submission
    const mappingData = {};
    Object.keys(form.team_mapping).forEach(icalTeam => {
        const value = form.team_mapping[icalTeam];
        if (value && value !== '' && value !== 'selectedTeam') {
            mappingData[icalTeam] = parseInt(value);
        } else if (value === 'selectedTeam') {
            mappingData[icalTeam] = props.selectedTeam.id;
        }
    });

    form.transform((data) => ({
        ...data,
        team_mapping: mappingData
    })).post(route('games.import.map-teams'));
};

const resetMapping = () => {
    props.icalTeams.forEach(team => {
        form.team_mapping[team] = '';
    });
    form.clearErrors();
};
</script>