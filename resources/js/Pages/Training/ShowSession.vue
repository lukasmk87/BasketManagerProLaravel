<template>
    <AppLayout title="Trainingseinheit Details">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Trainingseinheit: {{ session.team.name }}
                </h2>
                <div class="flex gap-2">
                    <SecondaryButton 
                        v-if="can?.update"
                        @click="editSession"
                    >
                        Bearbeiten
                    </SecondaryButton>
                    <SecondaryButton 
                        :href="route('training.sessions')"
                        as="Link"
                    >
                        Zurück zur Übersicht
                    </SecondaryButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Session Overview -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mb-6">
                    <div class="p-6">
                        <!-- Status Badge -->
                        <div class="flex justify-between items-start mb-4">
                            <span 
                                :class="getSessionStatusClasses()"
                                class="px-3 py-1 rounded-full text-sm font-medium"
                            >
                                {{ getSessionStatus() }}
                            </span>
                            <div v-if="session.cancelled" class="text-red-600 text-sm">
                                Abgesagt: {{ session.cancellation_reason }}
                            </div>
                        </div>

                        <!-- Session Info -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="text-lg font-semibold mb-3">Allgemeine Informationen</h3>
                                <dl class="space-y-2">
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Team:</dt>
                                        <dd class="text-sm font-medium">{{ session.team.name }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Datum:</dt>
                                        <dd class="text-sm font-medium">{{ formatDate(session.scheduled_at) }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Zeit:</dt>
                                        <dd class="text-sm font-medium">
                                            {{ formatTime(session.scheduled_at) }} - {{ formatTime(session.end_time) }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Ort:</dt>
                                        <dd class="text-sm font-medium">{{ session.location || 'Sporthalle' }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Trainer:</dt>
                                        <dd class="text-sm font-medium">{{ session.coach?.name || 'Nicht zugewiesen' }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <div>
                                <h3 class="text-lg font-semibold mb-3">Trainingsschwerpunkte</h3>
                                <div v-if="session.focus_areas && session.focus_areas.length" class="flex flex-wrap gap-2">
                                    <span 
                                        v-for="area in session.focus_areas" 
                                        :key="area"
                                        class="px-3 py-1 bg-indigo-100 text-indigo-700 text-sm rounded-full"
                                    >
                                        {{ area }}
                                    </span>
                                </div>
                                <p v-else class="text-sm text-gray-500">Keine Schwerpunkte definiert</p>

                                <div v-if="session.notes" class="mt-4">
                                    <h4 class="text-sm font-medium text-gray-700 mb-1">Notizen:</h4>
                                    <p class="text-sm text-gray-600">{{ session.notes }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Training Plan -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold">Trainingsplan</h3>
                            <button
                                v-if="can?.update"
                                @click="addDrill"
                                class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                            >
                                + Übung hinzufügen
                            </button>
                        </div>

                        <div v-if="session.drills && session.drills.length" class="space-y-3">
                            <div 
                                v-for="(drill, index) in session.drills" 
                                :key="drill.id"
                                class="border rounded-lg p-4 hover:bg-gray-50"
                            >
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <span class="text-2xl font-bold text-gray-300">{{ index + 1 }}</span>
                                            <div>
                                                <h4 class="font-medium text-gray-900">{{ drill.name }}</h4>
                                                <p class="text-sm text-gray-600">{{ drill.duration }} Min.</p>
                                            </div>
                                        </div>
                                        <p class="text-sm text-gray-600 ml-10">{{ drill.description }}</p>
                                        
                                        <div v-if="drill.equipment && drill.equipment.length" class="ml-10 mt-2">
                                            <span class="text-xs text-gray-500">Material: </span>
                                            <span class="text-xs text-gray-600">{{ drill.equipment.join(', ') }}</span>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center gap-2 ml-4">
                                        <span class="text-sm text-gray-500">{{ drill.category }}</span>
                                        <button
                                            v-if="can?.update"
                                            @click="removeDrill(drill)"
                                            class="text-red-600 hover:text-red-900"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8">
                            <p class="text-sm text-gray-500">Noch keine Übungen geplant</p>
                        </div>

                        <!-- Total Duration -->
                        <div v-if="session.drills && session.drills.length" class="mt-4 pt-4 border-t">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-700">Gesamtdauer:</span>
                                <span class="text-sm font-bold text-gray-900">{{ totalDuration }} Minuten</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attendance -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold">Anwesenheit</h3>
                            <button
                                v-if="can?.update && !session.cancelled"
                                @click="markAttendance"
                                class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                            >
                                Anwesenheit erfassen
                            </button>
                        </div>

                        <!-- Attendance Summary -->
                        <div class="mb-4 p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600">Anwesend</p>
                                    <p class="text-2xl font-bold text-green-600">{{ presentCount }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Abwesend</p>
                                    <p class="text-2xl font-bold text-red-600">{{ absentCount }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Entschuldigt</p>
                                    <p class="text-2xl font-bold text-yellow-600">{{ excusedCount }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Anwesenheitsquote</p>
                                    <p class="text-2xl font-bold text-indigo-600">{{ attendanceRate }}%</p>
                                </div>
                            </div>
                        </div>

                        <!-- Player List -->
                        <div v-if="session.attendance && session.attendance.length" class="space-y-2">
                            <div 
                                v-for="attendance in session.attendance" 
                                :key="attendance.player_id"
                                class="flex items-center justify-between p-3 border rounded-lg"
                            >
                                <div class="flex items-center gap-3">
                                    <span 
                                        :class="getAttendanceStatusClasses(attendance.status)"
                                        class="w-3 h-3 rounded-full"
                                    ></span>
                                    <div>
                                        <p class="font-medium text-sm">{{ attendance.player.name }}</p>
                                        <p class="text-xs text-gray-500">#{{ attendance.player.jersey_number }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-gray-600">{{ getAttendanceLabel(attendance.status) }}</span>
                                    <span v-if="attendance.note" class="text-xs text-gray-500">
                                        ({{ attendance.note }})
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8">
                            <p class="text-sm text-gray-500">Anwesenheit noch nicht erfasst</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'

const props = defineProps({
    session: Object,
    can: Object,
})

const totalDuration = computed(() => {
    if (!props.session.drills) return 0
    return props.session.drills.reduce((sum, drill) => sum + drill.duration, 0)
})

const presentCount = computed(() => {
    if (!props.session.attendance) return 0
    return props.session.attendance.filter(a => a.status === 'present').length
})

const absentCount = computed(() => {
    if (!props.session.attendance) return 0
    return props.session.attendance.filter(a => a.status === 'absent').length
})

const excusedCount = computed(() => {
    if (!props.session.attendance) return 0
    return props.session.attendance.filter(a => a.status === 'excused').length
})

const attendanceRate = computed(() => {
    if (!props.session.attendance || props.session.attendance.length === 0) return 0
    const total = props.session.attendance.length
    const present = presentCount.value
    return Math.round((present / total) * 100)
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

function getSessionStatus() {
    const now = new Date()
    const sessionDate = new Date(props.session.scheduled_at)
    const endTime = new Date(props.session.end_time)
    
    if (props.session.cancelled) return 'Abgesagt'
    if (now < sessionDate) return 'Geplant'
    if (now >= sessionDate && now <= endTime) return 'Läuft'
    return 'Beendet'
}

function getSessionStatusClasses() {
    const status = getSessionStatus()
    const classes = {
        'Abgesagt': 'bg-red-100 text-red-800',
        'Geplant': 'bg-blue-100 text-blue-800',
        'Läuft': 'bg-green-100 text-green-800',
        'Beendet': 'bg-gray-100 text-gray-800'
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

function getAttendanceLabel(status) {
    const labels = {
        'present': 'Anwesend',
        'absent': 'Abwesend',
        'excused': 'Entschuldigt',
        'late': 'Verspätet'
    }
    return labels[status] || status
}

function getAttendanceStatusClasses(status) {
    const classes = {
        'present': 'bg-green-500',
        'absent': 'bg-red-500',
        'excused': 'bg-yellow-500',
        'late': 'bg-orange-500'
    }
    return classes[status] || 'bg-gray-500'
}

function editSession() {
    router.get(`/training/sessions/${props.session.id}/edit`)
}

function addDrill() {
    // Implementation for adding a drill to the session
    router.get(`/training/sessions/${props.session.id}/drills/add`)
}

function removeDrill(drill) {
    if (confirm('Möchten Sie diese Übung wirklich entfernen?')) {
        router.delete(`/training/sessions/${props.session.id}/drills/${drill.id}`)
    }
}

function markAttendance() {
    router.get(`/training/sessions/${props.session.id}/attendance`)
}
</script>