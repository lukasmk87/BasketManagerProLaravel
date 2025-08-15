<template>
    <AppLayout title="Trainingseinheiten">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Trainingseinheiten
                </h2>
                <div class="flex gap-2">
                    <SecondaryButton 
                        :href="route('training.drills')"
                        as="Link"
                    >
                        Übungen verwalten
                    </SecondaryButton>
                    <PrimaryButton 
                        v-if="can?.create"
                        @click="createSession"
                    >
                        Neue Trainingseinheit
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Filter Section -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Team</label>
                                <select
                                    v-model="filters.team"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                >
                                    <option value="">Alle Teams</option>
                                    <option v-for="team in teams" :key="team.id" :value="team.id">
                                        {{ team.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Zeitraum</label>
                                <select
                                    v-model="filters.period"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                >
                                    <option value="all">Alle</option>
                                    <option value="upcoming">Kommende</option>
                                    <option value="past">Vergangene</option>
                                    <option value="today">Heute</option>
                                    <option value="week">Diese Woche</option>
                                    <option value="month">Dieser Monat</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Trainer</label>
                                <select
                                    v-model="filters.coach"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                >
                                    <option value="">Alle Trainer</option>
                                    <option v-for="coach in coaches" :key="coach.id" :value="coach.id">
                                        {{ coach.name }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sessions List -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <div v-if="sessions.data.length === 0" class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Keine Trainingseinheiten</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Es wurden keine Trainingseinheiten gefunden.
                            </p>
                        </div>

                        <div v-else class="space-y-4">
                            <div 
                                v-for="session in sessions.data" 
                                :key="session.id"
                                class="border rounded-lg p-4 hover:bg-gray-50 transition-colors"
                            >
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-2">
                                            <h3 class="text-lg font-semibold text-gray-900">
                                                {{ session.team.name }}
                                            </h3>
                                            <span 
                                                :class="getSessionStatusClasses(session)"
                                                class="px-2 py-1 text-xs rounded-full font-medium"
                                            >
                                                {{ getSessionStatus(session) }}
                                            </span>
                                        </div>
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2 text-sm text-gray-600">
                                            <div class="flex items-center gap-1">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                {{ formatDate(session.scheduled_at) }}
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                {{ formatTime(session.scheduled_at) }} - {{ formatTime(session.end_time) }}
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                {{ session.location || 'Sporthalle' }}
                                            </div>
                                        </div>

                                        <div v-if="session.coach" class="mt-2 text-sm text-gray-600">
                                            <span class="font-medium">Trainer:</span> {{ session.coach.name }}
                                        </div>

                                        <div v-if="session.focus_areas && session.focus_areas.length" class="mt-2 flex flex-wrap gap-1">
                                            <span 
                                                v-for="area in session.focus_areas" 
                                                :key="area"
                                                class="px-2 py-1 bg-indigo-100 text-indigo-700 text-xs rounded-full"
                                            >
                                                {{ area }}
                                            </span>
                                        </div>

                                        <div v-if="session.notes" class="mt-2 text-sm text-gray-500">
                                            {{ session.notes }}
                                        </div>
                                    </div>

                                    <div class="flex gap-2 ml-4">
                                        <SecondaryButton
                                            :href="route('training.sessions.show', session.id)"
                                            as="Link"
                                            size="sm"
                                        >
                                            Details
                                        </SecondaryButton>
                                        <SecondaryButton
                                            v-if="can?.update"
                                            @click="editSession(session)"
                                            size="sm"
                                        >
                                            Bearbeiten
                                        </SecondaryButton>
                                    </div>
                                </div>

                                <!-- Attendance Summary -->
                                <div v-if="session.attendance_count !== undefined" class="mt-3 pt-3 border-t">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600">
                                            Anwesenheit: {{ session.attendance_count }} / {{ session.expected_players }} Spieler
                                        </span>
                                        <div class="w-32 bg-gray-200 rounded-full h-2">
                                            <div 
                                                :style="`width: ${(session.attendance_count / session.expected_players) * 100}%`"
                                                class="bg-green-500 h-2 rounded-full"
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div v-if="sessions.last_page > 1" class="mt-6 flex justify-center">
                            <nav class="flex gap-1">
                                <button
                                    v-for="page in sessions.last_page"
                                    :key="page"
                                    @click="changePage(page)"
                                    :class="[
                                        'px-3 py-1 rounded',
                                        page === sessions.current_page 
                                            ? 'bg-indigo-600 text-white' 
                                            : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                                    ]"
                                >
                                    {{ page }}
                                </button>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'

const props = defineProps({
    sessions: Object,
    teams: Array,
    coaches: Array,
    can: Object,
})

const filters = ref({
    team: '',
    period: 'all',
    coach: ''
})

function formatDate(dateString) {
    const date = new Date(dateString)
    return date.toLocaleDateString('de-DE', {
        weekday: 'long',
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    })
}

function formatTime(dateString) {
    const date = new Date(dateString)
    return date.toLocaleTimeString('de-DE', {
        hour: '2-digit',
        minute: '2-digit'
    })
}

function getSessionStatus(session) {
    const now = new Date()
    const sessionDate = new Date(session.scheduled_at)
    const endTime = new Date(session.end_time)
    
    if (session.cancelled) return 'Abgesagt'
    if (now < sessionDate) return 'Geplant'
    if (now >= sessionDate && now <= endTime) return 'Läuft'
    return 'Beendet'
}

function getSessionStatusClasses(session) {
    const status = getSessionStatus(session)
    const classes = {
        'Abgesagt': 'bg-red-100 text-red-800',
        'Geplant': 'bg-blue-100 text-blue-800',
        'Läuft': 'bg-green-100 text-green-800',
        'Beendet': 'bg-gray-100 text-gray-800'
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

function createSession() {
    // Implementation for creating a new session
    router.get('/training/sessions/create')
}

function editSession(session) {
    // Implementation for editing a session
    router.get(`/training/sessions/${session.id}/edit`)
}

function changePage(page) {
    router.get(route('training.sessions'), {
        ...filters.value,
        page: page
    }, {
        preserveState: true,
        preserveScroll: true
    })
}
</script>