<script setup>
import { computed } from 'vue';
import {
    CalendarIcon,
    UserGroupIcon,
    TrophyIcon,
    CogIcon,
    PencilIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    selectedTeams: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['edit-step']);

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('de-DE', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    });
};

const seasonDuration = computed(() => {
    if (!props.form.start_date || !props.form.end_date) return null;

    const start = new Date(props.form.start_date);
    const end = new Date(props.form.end_date);
    const diffTime = Math.abs(end - start);
    const diffWeeks = Math.floor(diffTime / (1000 * 60 * 60 * 24 * 7));

    return `${diffWeeks} Wochen`;
});

const totalPlayers = computed(() => {
    if (!props.form.team_rosters) return 0;
    return Object.values(props.form.team_rosters)
        .reduce((sum, roster) => sum + roster.length, 0);
});

const teamsWithFullRoster = computed(() => {
    if (!props.form.team_rosters) return 0;
    return Object.entries(props.form.team_rosters)
        .filter(([_, roster]) => roster.length >= 5).length;
});

const teamsWithWarnings = computed(() => {
    if (!props.form.team_rosters) return [];
    return props.selectedTeams.filter(team => {
        const roster = props.form.team_rosters[team.id] || [];
        return roster.length > 0 && roster.length < 5;
    });
});

const criticalWarnings = computed(() => {
    const warnings = [];

    if (props.selectedTeams.length === 0) {
        warnings.push('Keine Teams ausgewählt');
    }

    if (totalPlayers.value === 0) {
        warnings.push('Keine Spieler zugewiesen');
    }

    if (teamsWithWarnings.value.length > 0) {
        warnings.push(`${teamsWithWarnings.value.length} Team(s) mit weniger als 5 Spielern`);
    }

    if (!props.form.name) {
        warnings.push('Kein Saison-Name angegeben');
    }

    if (!props.form.start_date || !props.form.end_date) {
        warnings.push('Saison-Zeitraum unvollständig');
    }

    return warnings;
});

const handleEditStep = (step) => {
    emit('edit-step', step);
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h2 class="text-xl font-semibold text-gray-900">Zusammenfassung</h2>
            <p class="mt-1 text-sm text-gray-500">
                Überprüfen Sie alle Angaben, bevor Sie die Saison erstellen.
            </p>
        </div>

        <!-- Critical Warnings -->
        <div v-if="criticalWarnings.length > 0" class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <ExclamationTriangleIcon class="h-5 w-5 text-yellow-400" />
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">
                        Bitte überprüfen Sie folgende Punkte:
                    </h3>
                    <ul class="mt-2 text-sm text-yellow-700 list-disc list-inside space-y-1">
                        <li v-for="warning in criticalWarnings" :key="warning">
                            {{ warning }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Section 1: Basis-Informationen -->
        <div class="bg-white border border-gray-200 rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <div class="flex items-center">
                    <CalendarIcon class="h-5 w-5 text-gray-400 mr-3" />
                    <h3 class="text-lg font-medium text-gray-900">Basis-Informationen</h3>
                </div>
                <button
                    type="button"
                    @click="handleEditStep(1)"
                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    <PencilIcon class="h-3.5 w-3.5 mr-1" />
                    Bearbeiten
                </button>
            </div>
            <div class="px-6 py-4">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Saison-Name</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-medium">{{ form.name || '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Dauer</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ seasonDuration || '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Startdatum</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ formatDate(form.start_date) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Enddatum</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ formatDate(form.end_date) }}</dd>
                    </div>
                    <div v-if="form.description" class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Beschreibung</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ form.description }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Section 2: Teams & Spieler -->
        <div class="bg-white border border-gray-200 rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <div class="flex items-center">
                    <UserGroupIcon class="h-5 w-5 text-gray-400 mr-3" />
                    <h3 class="text-lg font-medium text-gray-900">Teams & Spieler</h3>
                </div>
                <div class="flex space-x-2">
                    <button
                        type="button"
                        @click="handleEditStep(2)"
                        class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <PencilIcon class="h-3.5 w-3.5 mr-1" />
                        Teams
                    </button>
                    <button
                        type="button"
                        @click="handleEditStep(3)"
                        class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <PencilIcon class="h-3.5 w-3.5 mr-1" />
                        Kader
                    </button>
                </div>
            </div>
            <div class="px-6 py-4">
                <!-- Summary Stats -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                    <div class="bg-blue-50 rounded-lg p-4">
                        <div class="text-2xl font-bold text-blue-900">{{ selectedTeams.length }}</div>
                        <div class="text-sm text-blue-700">Teams</div>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4">
                        <div class="text-2xl font-bold text-green-900">{{ teamsWithFullRoster }}</div>
                        <div class="text-sm text-green-700">Vollständige Kader</div>
                    </div>
                    <div class="bg-purple-50 rounded-lg p-4">
                        <div class="text-2xl font-bold text-purple-900">{{ totalPlayers }}</div>
                        <div class="text-sm text-purple-700">Spieler gesamt</div>
                    </div>
                </div>

                <!-- Team List -->
                <div v-if="selectedTeams.length > 0" class="space-y-2">
                    <h4 class="text-sm font-medium text-gray-700 mb-3">Ausgewählte Teams</h4>
                    <div
                        v-for="team in selectedTeams"
                        :key="team.id"
                        class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                    >
                        <div class="flex items-center space-x-3">
                            <UserGroupIcon class="h-5 w-5 text-gray-400" />
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ team.name }}</div>
                                <div class="text-xs text-gray-500">
                                    {{ form.team_rosters?.[team.id]?.length || 0 }} Spieler
                                </div>
                            </div>
                        </div>
                        <div>
                            <CheckCircleIcon
                                v-if="(form.team_rosters?.[team.id]?.length || 0) >= 5"
                                class="h-5 w-5 text-green-500"
                                title="Vollständiger Kader"
                            />
                            <ExclamationTriangleIcon
                                v-else-if="(form.team_rosters?.[team.id]?.length || 0) > 0"
                                class="h-5 w-5 text-yellow-500"
                                title="Weniger als 5 Spieler"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 3: Einstellungen -->
        <div class="bg-white border border-gray-200 rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <div class="flex items-center">
                    <CogIcon class="h-5 w-5 text-gray-400 mr-3" />
                    <h3 class="text-lg font-medium text-gray-900">Einstellungen</h3>
                </div>
                <button
                    type="button"
                    @click="handleEditStep(4)"
                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    <PencilIcon class="h-3.5 w-3.5 mr-1" />
                    Bearbeiten
                </button>
            </div>
            <div class="px-6 py-4">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status nach Erstellung</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ form.auto_activate ? 'Sofort aktivieren' : 'Als Entwurf speichern' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Spielplan generieren</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ form.create_schedule
                                ? `Ja (${form.schedule_type === 'double' ? 'Doppelrunde' : 'Einfache Runde'})`
                                : 'Nein'
                            }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Team-Benachrichtigungen</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ form.notify_teams ? 'Ja' : 'Nein' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Spieler-Benachrichtigungen</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ form.notify_players ? 'Ja' : 'Nein' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Next Steps Info -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h4 class="text-sm font-medium text-blue-900 mb-2">Nach dem Erstellen</h4>
            <ul class="text-sm text-blue-700 space-y-1">
                <li v-if="form.auto_activate">✓ Die Saison wird sofort aktiviert</li>
                <li v-else>✓ Die Saison wird als Entwurf gespeichert</li>
                <li v-if="form.create_schedule">✓ Ein Spielplan wird automatisch generiert</li>
                <li v-if="form.notify_teams">✓ Trainer werden per E-Mail benachrichtigt</li>
                <li>✓ Sie können Kader und Einstellungen jederzeit anpassen</li>
                <li>✓ Teams und Spiele können hinzugefügt werden</li>
            </ul>
        </div>

        <!-- Action Info -->
        <div class="bg-gray-50 border border-gray-200 rounded-md p-4">
            <h4 class="text-sm font-medium text-gray-900 mb-2">Bereit zum Erstellen?</h4>
            <p class="text-xs text-gray-600">
                Klicken Sie auf "Saison erstellen" um die neue Saison mit allen konfigurierten
                Einstellungen anzulegen. Alle Angaben können später noch angepasst werden.
            </p>
        </div>
    </div>
</template>
