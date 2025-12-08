<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import EventCard from './EventCard.vue';
import AbsenceCard from './AbsenceCard.vue';
import CreateAbsenceModal from './CreateAbsenceModal.vue';

const props = defineProps({
    playerId: {
        type: [Number, String],
        default: null,
    },
});

const upcomingEvents = ref([]);
const absences = ref([]);
const loading = ref(true);
const respondLoading = ref(false);
const absenceLoading = ref(false);
const showAbsenceModal = ref(false);
const editingAbsence = ref(null);
const error = ref(null);

const fetchData = async () => {
    loading.value = true;
    error.value = null;

    try {
        const [eventsResponse, absencesResponse] = await Promise.all([
            axios.get('/api/v2/availability/my-events'),
            axios.get('/api/v2/absences/my'),
        ]);

        upcomingEvents.value = eventsResponse.data.data || [];
        absences.value = absencesResponse.data.data || [];
    } catch (e) {
        console.error('Failed to load availability data:', e);
        error.value = 'Fehler beim Laden der Daten.';
    } finally {
        loading.value = false;
    }
};

const handleRespond = async (eventType, eventId, response) => {
    respondLoading.value = true;

    try {
        await axios.post('/api/v2/availability/respond', {
            event_type: eventType,
            event_id: eventId,
            response: response,
        });

        // Refresh events
        const eventsResponse = await axios.get('/api/v2/availability/my-events');
        upcomingEvents.value = eventsResponse.data.data || [];
    } catch (e) {
        console.error('Failed to respond:', e);
        alert(e.response?.data?.message || 'Fehler beim Speichern der Antwort.');
    } finally {
        respondLoading.value = false;
    }
};

const openCreateAbsenceModal = () => {
    editingAbsence.value = null;
    showAbsenceModal.value = true;
};

const openEditAbsenceModal = (absence) => {
    editingAbsence.value = absence;
    showAbsenceModal.value = true;
};

const handleAbsenceSubmit = async (formData) => {
    absenceLoading.value = true;

    try {
        if (formData.id) {
            // Update
            await axios.put(`/api/v2/absences/${formData.id}`, formData);
        } else {
            // Create
            await axios.post('/api/v2/absences', formData);
        }

        showAbsenceModal.value = false;
        editingAbsence.value = null;

        // Refresh data
        await fetchData();
    } catch (e) {
        console.error('Failed to save absence:', e);
        alert(e.response?.data?.message || 'Fehler beim Speichern der Abwesenheit.');
    } finally {
        absenceLoading.value = false;
    }
};

const handleDeleteAbsence = async (absence) => {
    if (!confirm('Möchtest du diese Abwesenheit wirklich löschen?')) {
        return;
    }

    try {
        await axios.delete(`/api/v2/absences/${absence.id}`);
        await fetchData();
    } catch (e) {
        console.error('Failed to delete absence:', e);
        alert(e.response?.data?.message || 'Fehler beim Löschen der Abwesenheit.');
    }
};

onMounted(fetchData);
</script>

<template>
    <div class="space-y-6">
        <!-- Loading State -->
        <div v-if="loading" class="text-center py-8">
            <svg class="animate-spin h-8 w-8 text-orange-600 mx-auto" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="mt-2 text-gray-500">Lade Verfügbarkeiten...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
            <p class="text-red-700">{{ error }}</p>
            <button @click="fetchData" class="mt-2 text-sm text-red-600 hover:text-red-800 underline">
                Erneut versuchen
            </button>
        </div>

        <template v-else>
            <!-- Upcoming Events Section -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">
                            Anstehende Termine
                        </h3>
                        <span class="text-sm text-gray-500">
                            {{ upcomingEvents.length }} Termine
                        </span>
                    </div>

                    <!-- Empty State -->
                    <div v-if="upcomingEvents.length === 0" class="text-center py-8 text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <p class="mt-2">Keine anstehenden Termine</p>
                    </div>

                    <!-- Events List -->
                    <div v-else class="space-y-3">
                        <EventCard
                            v-for="event in upcomingEvents"
                            :key="`${event.type}-${event.id}`"
                            :event="event"
                            :loading="respondLoading"
                            @respond="handleRespond"
                        />
                    </div>
                </div>
            </div>

            <!-- Absences Section -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">
                            Meine Abwesenheiten
                        </h3>
                        <button
                            @click="openCreateAbsenceModal"
                            class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-orange-600 rounded-md hover:bg-orange-700 transition-colors"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Abwesenheit eintragen
                        </button>
                    </div>

                    <!-- Empty State -->
                    <div v-if="absences.length === 0" class="text-center py-8 text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <p class="mt-2">Keine Abwesenheiten eingetragen</p>
                        <p class="text-sm">Trage Urlaub, Krankheit oder Verletzungen ein, damit dein Trainer Bescheid weiß.</p>
                    </div>

                    <!-- Absences List -->
                    <div v-else class="space-y-3">
                        <AbsenceCard
                            v-for="absence in absences"
                            :key="absence.id"
                            :absence="absence"
                            @edit="openEditAbsenceModal"
                            @delete="handleDeleteAbsence"
                        />
                    </div>
                </div>
            </div>
        </template>

        <!-- Create/Edit Absence Modal -->
        <CreateAbsenceModal
            :show="showAbsenceModal"
            :absence="editingAbsence"
            :loading="absenceLoading"
            @close="showAbsenceModal = false"
            @submit="handleAbsenceSubmit"
        />
    </div>
</template>
