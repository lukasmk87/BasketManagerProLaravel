<template>
    <AppLayout title="Training">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Training
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Navigation Tabs -->
                <div class="mb-8">
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8">
                            <Link 
                                :href="route('training.index')" 
                                class="border-indigo-500 text-indigo-600 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                            >
                                Übersicht
                            </Link>
                            <Link 
                                :href="route('training.sessions')" 
                                class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                            >
                                Trainingseinheiten
                            </Link>
                            <Link 
                                :href="route('training.drills')" 
                                class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                            >
                                Übungen
                            </Link>
                        </nav>
                    </div>
                </div>

                <!-- Upcoming Sessions -->
                <div class="mb-8">
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">
                                Kommende Trainingseinheiten
                            </h3>
                        </div>
                        <div class="p-6">
                            <div v-if="upcomingSessions.length > 0" class="space-y-4">
                                <div
                                    v-for="session in upcomingSessions"
                                    :key="session.id"
                                    class="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50"
                                >
                                    <div class="flex items-center space-x-4">
                                        <div class="flex-shrink-0">
                                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H3m2 0h4M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 8v-1m4 1v-1"/>
                                                </svg>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ session.team?.name }}
                                            </div>
                                            <div class="text-sm text-gray-600">
                                                {{ formatDate(session.scheduled_at) }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ session.location || 'Standort nicht angegeben' }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-3">
                                        <span class="text-sm text-gray-500">
                                            {{ session.duration || 90 }} Min.
                                        </span>
                                        <Link
                                            :href="route('training.sessions.show', session.id)"
                                            class="text-indigo-600 hover:text-indigo-500 text-sm font-medium"
                                        >
                                            Details
                                        </Link>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center text-gray-500 py-8">
                                Keine kommenden Trainingseinheiten geplant
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Sessions -->
                <div class="mb-8">
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">
                                Vergangene Trainingseinheiten
                            </h3>
                        </div>
                        <div class="p-6">
                            <div v-if="recentSessions.length > 0" class="space-y-4">
                                <div
                                    v-for="session in recentSessions"
                                    :key="session.id"
                                    class="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50"
                                >
                                    <div class="flex items-center space-x-4">
                                        <div class="flex-shrink-0">
                                            <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                </svg>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ session.team?.name }}
                                            </div>
                                            <div class="text-sm text-gray-600">
                                                {{ formatDate(session.scheduled_at) }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ session.location || 'Standort nicht angegeben' }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-3">
                                        <span
                                            :class="{
                                                'bg-green-100 text-green-800': session.status === 'completed',
                                                'bg-red-100 text-red-800': session.status === 'cancelled'
                                            }"
                                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                        >
                                            {{ session.status === 'completed' ? 'Abgeschlossen' : 'Abgesagt' }}
                                        </span>
                                        <Link
                                            :href="route('training.sessions.show', session.id)"
                                            class="text-indigo-600 hover:text-indigo-500 text-sm font-medium"
                                        >
                                            Details
                                        </Link>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center text-gray-500 py-8">
                                Keine vergangenen Trainingseinheiten
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <Link :href="route('training.sessions.create')" class="group">
                        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg hover:shadow-2xl transition-shadow duration-300">
                            <div class="p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-5">
                                        <h3 class="text-lg font-medium text-gray-900 group-hover:text-blue-600">
                                            Training planen
                                        </h3>
                                        <p class="text-sm text-gray-600">
                                            Neue Trainingseinheit erstellen
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Link>

                    <Link :href="route('training.drills')" class="group">
                        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg hover:shadow-2xl transition-shadow duration-300">
                            <div class="p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-5">
                                        <h3 class="text-lg font-medium text-gray-900 group-hover:text-green-600">
                                            Übungen verwalten
                                        </h3>
                                        <p class="text-sm text-gray-600">
                                            Übungsbibliothek durchsuchen
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Link>

                    <Link :href="route('statistics.index')" class="group">
                        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg hover:shadow-2xl transition-shadow duration-300">
                            <div class="p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-5">
                                        <h3 class="text-lg font-medium text-gray-900 group-hover:text-purple-600">
                                            Fortschritt verfolgen
                                        </h3>
                                        <p class="text-sm text-gray-600">
                                            Trainingsberichte anzeigen
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'

defineProps({
    upcomingSessions: Array,
    recentSessions: Array,
})

const formatDate = (dateString) => {
    if (!dateString) return ''
    const date = new Date(dateString)
    return date.toLocaleDateString('de-DE', {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}
</script>