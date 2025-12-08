<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    team: {
        type: Object,
        required: true,
    },
    upcomingEvents: {
        type: Array,
        default: () => [],
    },
});

const loading = ref(false);
const selectedEvent = ref(null);
const eventAvailability = ref(null);
const teamAbsences = ref([]);

// Date range for overview
const startDate = ref(new Date().toISOString().split('T')[0]);
const endDate = ref(new Date(Date.now() + 14 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]);

const availablePlayers = computed(() => {
    if (!eventAvailability.value) return [];
    return eventAvailability.value.players.filter(p => p.availability.status === 'available');
});

const unavailablePlayers = computed(() => {
    if (!eventAvailability.value) return [];
    return eventAvailability.value.players.filter(p => p.availability.status === 'unavailable');
});

const maybePlayers = computed(() => {
    if (!eventAvailability.value) return [];
    return eventAvailability.value.players.filter(p => p.availability.status === 'maybe');
});

const pendingPlayers = computed(() => {
    if (!eventAvailability.value) return [];
    return eventAvailability.value.players.filter(p =>
        !['available', 'unavailable', 'maybe'].includes(p.availability.status)
    );
});

const fetchEventAvailability = async (event) => {
    loading.value = true;
    try {
        const response = await axios.get('/api/v2/availability/event', {
            params: {
                event_type: event.type,
                event_id: event.id,
            },
        });
        eventAvailability.value = response.data.data;
    } catch (e) {
        console.error('Failed to fetch event availability:', e);
    } finally {
        loading.value = false;
    }
};

const fetchTeamAbsences = async () => {
    try {
        const response = await axios.get(`/api/v2/absences/team/${props.team.id}`, {
            params: {
                start_date: startDate.value,
                end_date: endDate.value,
            },
        });
        teamAbsences.value = response.data.data || [];
    } catch (e) {
        console.error('Failed to fetch team absences:', e);
    }
};

const selectEvent = (event) => {
    selectedEvent.value = event;
    fetchEventAvailability(event);
};

const getStatusColor = (status) => {
    const colors = {
        available: 'bg-green-100 text-green-800',
        unavailable: 'bg-red-100 text-red-800',
        maybe: 'bg-yellow-100 text-yellow-800',
        pending: 'bg-gray-100 text-gray-600',
    };
    return colors[status] || colors.pending;
};

const getStatusLabel = (status) => {
    const labels = {
        available: 'Zugesagt',
        unavailable: 'Abgesagt',
        maybe: 'Unsicher',
        pending: 'Ausstehend',
    };
    return labels[status] || 'Ausstehend';
};

const formatDate = (dateStr) => {
    return new Date(dateStr).toLocaleDateString('de-DE', {
        weekday: 'short',
        day: '2-digit',
        month: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getAbsenceTypeLabel = (type) => {
    const labels = {
        vacation: 'Urlaub',
        illness: 'Krankheit',
        injury: 'Verletzung',
        personal: 'Persönlich',
        other: 'Sonstiges',
    };
    return labels[type] || type;
};

onMounted(() => {
    fetchTeamAbsences();
    if (props.upcomingEvents.length > 0) {
        selectEvent(props.upcomingEvents[0]);
    }
});

watch([startDate, endDate], () => {
    fetchTeamAbsences();
});
</script>

<template>
    <AppLayout :title="`Verfügbarkeit - ${team.name}`">
        <Head :title="`Verfügbarkeit - ${team.name}`" />

        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Verfügbarkeitsübersicht
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">{{ team.name }}</p>
                </div>
                <Link
                    :href="route('teams.show', team.id)"
                    class="text-sm text-gray-600 hover:text-gray-900"
                >
                    &larr; Zurück zum Team
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Event Selector -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Termine auswählen</h3>

                        <div v-if="upcomingEvents.length === 0" class="text-center py-4 text-gray-500">
                            Keine anstehenden Termine gefunden.
                        </div>

                        <div v-else class="flex flex-wrap gap-2">
                            <button
                                v-for="event in upcomingEvents"
                                :key="`${event.type}-${event.id}`"
                                @click="selectEvent(event)"
                                class="px-4 py-2 text-sm rounded-lg border transition-all"
                                :class="selectedEvent?.id === event.id && selectedEvent?.type === event.type
                                    ? 'bg-orange-600 text-white border-orange-600'
                                    : 'bg-white text-gray-700 border-gray-300 hover:border-orange-400'"
                            >
                                <span class="font-medium">{{ event.type === 'game' ? 'Spiel' : 'Training' }}</span>
                                <span class="ml-1 text-xs opacity-75">{{ formatDate(event.scheduled_at) }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Availability Overview for Selected Event -->
                <div v-if="selectedEvent && eventAvailability" class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">
                                    {{ eventAvailability.event.title }}
                                </h3>
                                <p class="text-sm text-gray-500">
                                    {{ eventAvailability.event.scheduled_at_formatted }}
                                </p>
                            </div>

                            <!-- Summary Stats -->
                            <div class="flex gap-4">
                                <div class="text-center px-3 py-1 bg-green-50 rounded">
                                    <div class="text-lg font-bold text-green-700">{{ eventAvailability.summary.available }}</div>
                                    <div class="text-xs text-green-600">Zugesagt</div>
                                </div>
                                <div class="text-center px-3 py-1 bg-yellow-50 rounded">
                                    <div class="text-lg font-bold text-yellow-700">{{ eventAvailability.summary.maybe }}</div>
                                    <div class="text-xs text-yellow-600">Unsicher</div>
                                </div>
                                <div class="text-center px-3 py-1 bg-gray-50 rounded">
                                    <div class="text-lg font-bold text-gray-700">{{ eventAvailability.summary.pending }}</div>
                                    <div class="text-xs text-gray-600">Ausstehend</div>
                                </div>
                                <div class="text-center px-3 py-1 bg-red-50 rounded">
                                    <div class="text-lg font-bold text-red-700">{{ eventAvailability.summary.unavailable }}</div>
                                    <div class="text-xs text-red-600">Abgesagt</div>
                                </div>
                            </div>
                        </div>

                        <!-- Loading State -->
                        <div v-if="loading" class="text-center py-8">
                            <svg class="animate-spin h-8 w-8 text-orange-600 mx-auto" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>

                        <!-- Player Lists -->
                        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <!-- Available -->
                            <div class="bg-green-50 rounded-lg p-4">
                                <h4 class="font-medium text-green-800 mb-3 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    Zugesagt ({{ availablePlayers.length }})
                                </h4>
                                <div v-if="availablePlayers.length === 0" class="text-sm text-green-600 italic">
                                    Keine Zusagen
                                </div>
                                <div v-else class="space-y-2">
                                    <div
                                        v-for="player in availablePlayers"
                                        :key="player.id"
                                        class="flex items-center text-sm"
                                    >
                                        <span class="w-6 text-center font-medium text-green-700">{{ player.jersey_number || '-' }}</span>
                                        <span class="ml-2 text-gray-900">{{ player.name }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Maybe -->
                            <div class="bg-yellow-50 rounded-lg p-4">
                                <h4 class="font-medium text-yellow-800 mb-3 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path>
                                    </svg>
                                    Unsicher ({{ maybePlayers.length }})
                                </h4>
                                <div v-if="maybePlayers.length === 0" class="text-sm text-yellow-600 italic">
                                    Keine unsicheren
                                </div>
                                <div v-else class="space-y-2">
                                    <div
                                        v-for="player in maybePlayers"
                                        :key="player.id"
                                        class="flex items-center text-sm"
                                    >
                                        <span class="w-6 text-center font-medium text-yellow-700">{{ player.jersey_number || '-' }}</span>
                                        <span class="ml-2 text-gray-900">{{ player.name }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Pending -->
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h4 class="font-medium text-gray-700 mb-3 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                    </svg>
                                    Ausstehend ({{ pendingPlayers.length }})
                                </h4>
                                <div v-if="pendingPlayers.length === 0" class="text-sm text-gray-500 italic">
                                    Alle haben geantwortet
                                </div>
                                <div v-else class="space-y-2">
                                    <div
                                        v-for="player in pendingPlayers"
                                        :key="player.id"
                                        class="flex items-center text-sm"
                                    >
                                        <span class="w-6 text-center font-medium text-gray-500">{{ player.jersey_number || '-' }}</span>
                                        <span class="ml-2 text-gray-700">{{ player.name }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Unavailable -->
                            <div class="bg-red-50 rounded-lg p-4">
                                <h4 class="font-medium text-red-800 mb-3 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                    </svg>
                                    Abgesagt ({{ unavailablePlayers.length }})
                                </h4>
                                <div v-if="unavailablePlayers.length === 0" class="text-sm text-red-600 italic">
                                    Keine Absagen
                                </div>
                                <div v-else class="space-y-2">
                                    <div
                                        v-for="player in unavailablePlayers"
                                        :key="player.id"
                                        class="flex flex-col text-sm"
                                    >
                                        <div class="flex items-center">
                                            <span class="w-6 text-center font-medium text-red-700">{{ player.jersey_number || '-' }}</span>
                                            <span class="ml-2 text-gray-900">{{ player.name }}</span>
                                        </div>
                                        <span v-if="player.availability.reason" class="ml-8 text-xs text-red-600">
                                            {{ player.availability.reason }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Team Absences -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Abwesenheiten im Team</h3>
                            <div class="flex items-center gap-2 text-sm">
                                <input
                                    type="date"
                                    v-model="startDate"
                                    class="px-2 py-1 border rounded text-sm"
                                />
                                <span>bis</span>
                                <input
                                    type="date"
                                    v-model="endDate"
                                    class="px-2 py-1 border rounded text-sm"
                                />
                            </div>
                        </div>

                        <div v-if="teamAbsences.length === 0" class="text-center py-8 text-gray-500">
                            Keine Abwesenheiten im ausgewählten Zeitraum.
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Spieler</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Art</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Zeitraum</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Grund</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="absence in teamAbsences" :key="absence.id">
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                            {{ absence.player_name }}
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            <span
                                                class="px-2 py-1 rounded text-xs font-medium"
                                                :class="{
                                                    'bg-blue-100 text-blue-800': absence.type === 'vacation',
                                                    'bg-yellow-100 text-yellow-800': absence.type === 'illness',
                                                    'bg-red-100 text-red-800': absence.type === 'injury',
                                                    'bg-purple-100 text-purple-800': absence.type === 'personal',
                                                    'bg-gray-100 text-gray-800': absence.type === 'other',
                                                }"
                                            >
                                                {{ absence.type_display || getAbsenceTypeLabel(absence.type) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            {{ absence.period_display }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            {{ absence.reason || '-' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
