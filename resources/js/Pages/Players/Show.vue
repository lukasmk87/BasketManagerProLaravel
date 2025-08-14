<template>
    <AppLayout :title="player.user?.name || `Spieler #${player.jersey_number}`">
        <template #header>
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <Link
                        :href="route('players.index')"
                        class="text-gray-400 hover:text-gray-600"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        {{ player.user?.name || `Spieler #${player.jersey_number}` }}
                    </h2>
                </div>
                <div class="flex space-x-2">
                    <Link
                        v-if="can.update"
                        :href="route('players.edit', player.id)"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium"
                    >
                        Bearbeiten
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Player Basic Info Card -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center space-x-6">
                            <!-- Jersey Number -->
                            <div class="flex-shrink-0">
                                <div class="w-24 h-24 bg-indigo-100 rounded-full flex items-center justify-center">
                                    <span class="text-indigo-800 font-bold text-3xl">
                                        {{ player.jersey_number || '?' }}
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Basic Info -->
                            <div class="flex-1">
                                <h3 class="text-2xl font-bold text-gray-900 mb-2">
                                    {{ player.user?.name || 'Unbekannter Spieler' }}
                                </h3>
                                <div class="grid grid-cols-2 gap-4 text-sm text-gray-600">
                                    <div>
                                        <span class="font-medium">Position:</span> 
                                        {{ player.primary_position || 'Nicht angegeben' }}
                                    </div>
                                    <div>
                                        <span class="font-medium">Team:</span> 
                                        {{ player.team?.name || 'Kein Team' }}
                                    </div>
                                    <div>
                                        <span class="font-medium">Verein:</span> 
                                        {{ player.team?.club?.name || 'Kein Verein' }}
                                    </div>
                                    <div>
                                        <span class="font-medium">Status:</span>
                                        <span
                                            :class="{
                                                'text-green-600': player.status === 'active',
                                                'text-red-600': player.status === 'injured',
                                                'text-gray-600': player.status === 'inactive',
                                                'text-yellow-600': player.status === 'suspended'
                                            }"
                                            class="font-medium"
                                        >
                                            {{ getStatusText(player.status) }}
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Badges -->
                                <div class="flex space-x-2 mt-4">
                                    <span
                                        v-if="player.is_captain"
                                        class="inline-flex items-center px-3 py-1 text-sm font-medium bg-yellow-100 text-yellow-800 rounded-full"
                                    >
                                        Kapitän
                                    </span>
                                    <span
                                        v-if="player.is_starter"
                                        class="inline-flex items-center px-3 py-1 text-sm font-medium bg-blue-100 text-blue-800 rounded-full"
                                    >
                                        Starter
                                    </span>
                                    <span
                                        v-if="player.is_rookie"
                                        class="inline-flex items-center px-3 py-1 text-sm font-medium bg-green-100 text-green-800 rounded-full"
                                    >
                                        Rookie
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Physical Info & Stats Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Physical Information -->
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                        <div class="p-6">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">
                                Physische Daten
                            </h4>
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Größe:</span>
                                    <span class="font-medium">
                                        {{ player.height_cm ? `${player.height_cm} cm` : 'Nicht angegeben' }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Gewicht:</span>
                                    <span class="font-medium">
                                        {{ player.weight_kg ? `${player.weight_kg} kg` : 'Nicht angegeben' }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Dominante Hand:</span>
                                    <span class="font-medium">
                                        {{ getDominantHandText(player.dominant_hand) }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Erfahrung:</span>
                                    <span class="font-medium">
                                        {{ player.years_experience ? `${player.years_experience} Jahre` : 'Nicht angegeben' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Season Statistics -->
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                        <div class="p-6">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">
                                Saison-Statistiken
                            </h4>
                            <div class="space-y-3 text-sm" v-if="statistics">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Spiele:</span>
                                    <span class="font-medium">{{ statistics.games_played || 0 }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Punkte:</span>
                                    <span class="font-medium">{{ statistics.points_scored || 0 }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Assists:</span>
                                    <span class="font-medium">{{ statistics.assists || 0 }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Rebounds:</span>
                                    <span class="font-medium">{{ statistics.rebounds_total || 0 }}</span>
                                </div>
                            </div>
                            <div v-else class="text-gray-500 text-sm">
                                Keine Statistiken verfügbar
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Player Ratings -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg" v-if="hasRatings">
                    <div class="p-6">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">
                            Spieler-Bewertungen
                        </h4>
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-indigo-600">
                                    {{ player.shooting_rating || '-' }}
                                </div>
                                <div class="text-sm text-gray-600">Wurf</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-indigo-600">
                                    {{ player.defense_rating || '-' }}
                                </div>
                                <div class="text-sm text-gray-600">Verteidigung</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-indigo-600">
                                    {{ player.passing_rating || '-' }}
                                </div>
                                <div class="text-sm text-gray-600">Pass</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-indigo-600">
                                    {{ player.rebounding_rating || '-' }}
                                </div>
                                <div class="text-sm text-gray-600">Rebound</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-indigo-600">
                                    {{ player.speed_rating || '-' }}
                                </div>
                                <div class="text-sm text-gray-600">Schnelligkeit</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Information -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg" v-if="player.coach_notes || player.medical_conditions">
                    <div class="p-6">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">
                            Zusätzliche Informationen
                        </h4>
                        <div class="space-y-4">
                            <div v-if="player.coach_notes">
                                <h5 class="font-medium text-gray-900">Trainer-Notizen:</h5>
                                <p class="text-sm text-gray-600 mt-1">{{ player.coach_notes }}</p>
                            </div>
                            <div v-if="player.medical_conditions && player.medical_conditions.length > 0">
                                <h5 class="font-medium text-gray-900">Medizinische Hinweise:</h5>
                                <ul class="text-sm text-gray-600 mt-1 list-disc list-inside">
                                    <li v-for="condition in player.medical_conditions" :key="condition">
                                        {{ condition }}
                                    </li>
                                </ul>
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
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
    player: Object,
    statistics: Object,
    can: Object,
})

const hasRatings = computed(() => {
    return props.player.shooting_rating || 
           props.player.defense_rating || 
           props.player.passing_rating || 
           props.player.rebounding_rating || 
           props.player.speed_rating
})

const getStatusText = (status) => {
    const statusTexts = {
        'active': 'Aktiv',
        'inactive': 'Inaktiv',
        'injured': 'Verletzt',
        'suspended': 'Gesperrt',
        'retired': 'Zurückgetreten'
    }
    return statusTexts[status] || status
}

const getDominantHandText = (hand) => {
    const handTexts = {
        'left': 'Links',
        'right': 'Rechts',
        'ambidextrous': 'Beidhändig'
    }
    return handTexts[hand] || 'Nicht angegeben'
}
</script>