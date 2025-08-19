<template>
    <AppLayout title="Spiele">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Spiele
                </h2>
                <div>
                    <PrimaryButton 
                        v-if="can.create"
                        :href="route('web.games.create')"
                        as="Link"
                    >
                        Spiel planen
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Filter Tabs -->
                <div class="mb-6">
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8">
                            <button class="border-indigo-500 text-indigo-600 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                                Alle
                            </button>
                            <button class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                                Geplant
                            </button>
                            <button class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                                Live
                            </button>
                            <button class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                                Beendet
                            </button>
                        </nav>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <!-- Search and Filter -->
                        <div class="mb-6">
                            <div class="flex justify-between items-center">
                                <div class="w-1/3">
                                    <input
                                        type="text"
                                        placeholder="Spiele suchen..."
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    />
                                </div>
                                <div class="text-sm text-gray-600">
                                    {{ games.total }} Spiele gefunden
                                </div>
                            </div>
                        </div>

                        <!-- Games List -->
                        <div class="space-y-4">
                            <div
                                v-for="game in games.data"
                                :key="game.id"
                                class="bg-white border rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200 p-6"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-6">
                                        <!-- Game Status -->
                                        <div class="flex-shrink-0">
                                            <span
                                                :class="{
                                                    'bg-blue-100 text-blue-800': game.status === 'scheduled',
                                                    'bg-green-100 text-green-800': game.status === 'in_progress',
                                                    'bg-gray-100 text-gray-800': game.status === 'finished',
                                                    'bg-red-100 text-red-800': game.status === 'cancelled'
                                                }"
                                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                            >
                                                {{ getStatusText(game.status) }}
                                            </span>
                                        </div>

                                        <!-- Teams -->
                                        <div class="flex items-center space-x-4">
                                            <div class="text-right">
                                                <div class="text-lg font-semibold text-gray-900">
                                                    {{ game.home_team?.name }}
                                                </div>
                                                <div class="text-sm text-gray-600">
                                                    {{ game.home_team?.club?.name }}
                                                </div>
                                            </div>

                                            <div class="flex items-center space-x-2">
                                                <div v-if="game.status === 'finished'" class="text-center">
                                                    <div class="text-2xl font-bold text-gray-900">
                                                        {{ game.home_score }} : {{ game.away_score }}
                                                    </div>
                                                </div>
                                                <div v-else class="text-center">
                                                    <div class="text-lg font-medium text-gray-600">vs</div>
                                                </div>
                                            </div>

                                            <div class="text-left">
                                                <div class="text-lg font-semibold text-gray-900">
                                                    {{ game.away_team?.name }}
                                                </div>
                                                <div class="text-sm text-gray-600">
                                                    {{ game.away_team?.club?.name }}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Game Info -->
                                        <div class="text-sm text-gray-600">
                                            <div>{{ formatDate(game.scheduled_at) }}</div>
                                            <div v-if="game.location">{{ game.location }}</div>
                                            <div>{{ game.game_type }} • {{ game.season }}</div>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex items-center space-x-3">
                                        <Link
                                            :href="route('web.games.show', game.id)"
                                            class="text-indigo-600 hover:text-indigo-500 font-medium text-sm"
                                        >
                                            Details
                                        </Link>
                                        
                                        <Link
                                            v-if="game.status === 'scheduled' && game.can?.startGame"
                                            :href="route('games.live-scoring', game.id)"
                                            class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm font-medium"
                                        >
                                            Spiel starten
                                        </Link>
                                        
                                        <Link
                                            v-if="game.status === 'in_progress'"
                                            :href="route('games.live-scoring', game.id)"
                                            class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm font-medium"
                                        >
                                            Live Scoring
                                        </Link>
                                        
                                        <Link
                                            v-if="game.can?.update && game.status === 'scheduled'"
                                            :href="route('web.games.edit', game.id)"
                                            class="text-gray-400 hover:text-gray-500"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-if="games.data.length === 0" class="text-center py-12">
                            <div class="text-gray-500 text-lg mb-4">
                                Keine Spiele gefunden
                            </div>
                            <PrimaryButton 
                                v-if="can.create"
                                :href="route('web.games.create')"
                                as="Link"
                            >
                                Erstes Spiel planen
                            </PrimaryButton>
                        </div>

                        <!-- Pagination -->
                        <div v-if="games.data.length > 0" class="mt-8">
                            <div class="flex justify-center">
                                <div class="text-sm text-gray-600">
                                    Seite {{ games.current_page }} von {{ games.last_page }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import { Link } from '@inertiajs/vue3'

defineProps({
    games: Object,
    can: Object,
})

const getStatusText = (status) => {
    const statusTexts = {
        'scheduled': 'Geplant',
        'in_progress': 'Live',
        'finished': 'Beendet',
        'cancelled': 'Abgesagt'
    }
    return statusTexts[status] || status
}

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