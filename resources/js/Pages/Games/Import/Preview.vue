<template>
    <AppLayout title="Import Vorschau">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Import Vorschau: {{ team.name }}
                </h2>
                <SecondaryButton 
                    :href="route('games.import.index')"
                    as="Link"
                >
                    ← Zurück
                </SecondaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Import Summary -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">
                                Import Zusammenfassung
                            </h3>
                            <div class="text-sm text-gray-500">
                                Datei: {{ fileName }}
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="bg-blue-50 rounded-lg p-4 text-center">
                                <div class="text-2xl font-bold text-blue-600">{{ totalGames }}</div>
                                <div class="text-sm text-blue-800">Spiele in Datei</div>
                            </div>
                            <div class="bg-green-50 rounded-lg p-4 text-center">
                                <div class="text-2xl font-bold text-green-600">{{ matchingGames }}</div>
                                <div class="text-sm text-green-800">Passende Spiele</div>
                            </div>
                            <div class="bg-yellow-50 rounded-lg p-4 text-center">
                                <div class="text-2xl font-bold text-yellow-600">{{ newGames }}</div>
                                <div class="text-sm text-yellow-800">Neue Spiele</div>
                            </div>
                            <div class="bg-red-50 rounded-lg p-4 text-center">
                                <div class="text-2xl font-bold text-red-600">{{ existingGames }}</div>
                                <div class="text-sm text-red-800">Bereits vorhanden</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Games Preview -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="px-6 py-4 bg-gray-50 border-b">
                        <h3 class="text-lg font-medium text-gray-900">
                            Zu importierende Spiele
                        </h3>
                        <p class="text-sm text-gray-600 mt-1">
                            Überprüfen Sie die gefundenen Spiele vor dem Import
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Datum/Zeit
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Begegnung
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Spielort
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Saison
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Spieltyp
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr 
                                    v-for="game in preview" 
                                    :key="game.external_game_id"
                                    :class="game.can_import ? 'hover:bg-green-50' : 'bg-gray-50'"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span v-if="game.can_import" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            ✓ Neu
                                        </span>
                                        <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            ⚠ Vorhanden
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ formatDateTime(game.scheduled_at) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <div class="flex items-center space-x-2">
                                            <span :class="game.is_home_game ? 'font-semibold' : ''">
                                                {{ game.home_team_display }}
                                            </span>
                                            <span class="text-gray-400">-</span>
                                            <span :class="!game.is_home_game ? 'font-semibold' : ''">
                                                {{ game.away_team_display }}
                                            </span>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            <span v-if="game.is_home_game" class="bg-blue-100 text-blue-800 px-1 py-0.5 rounded text-xs">
                                                Heimspiel
                                            </span>
                                            <span v-else class="bg-orange-100 text-orange-800 px-1 py-0.5 rounded text-xs">
                                                Auswärtsspiel
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <div>{{ game.venue || 'Nicht angegeben' }}</div>
                                        <div v-if="game.venue_code" class="text-xs text-gray-500">
                                            Halle: {{ game.venue_code }}
                                        </div>
                                        <div v-if="game.venue_address" class="text-xs text-gray-500 mt-1">
                                            {{ game.venue_address }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ game.season }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <span :class="getGameTypeClasses(game.game_type)" class="px-2 py-1 text-xs font-medium rounded-full">
                                            {{ getGameTypeLabel(game.game_type) }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-600">
                                <span v-if="newGames > 0">
                                    {{ newGames }} neue Spiele werden importiert.
                                </span>
                                <span v-else class="text-orange-600">
                                    Keine neuen Spiele zum Importieren gefunden.
                                </span>
                            </div>
                            
                            <div class="flex space-x-4">
                                <SecondaryButton
                                    @click="cancelImport"
                                    :disabled="form.processing"
                                >
                                    Abbrechen
                                </SecondaryButton>
                                
                                <PrimaryButton
                                    @click="confirmImport"
                                    :class="{ 'opacity-25': form.processing }"
                                    :disabled="form.processing || newGames === 0"
                                >
                                    <span v-if="form.processing" class="flex items-center">
                                        <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Importiere...
                                    </span>
                                    <span v-else>
                                        ✅ Import bestätigen ({{ newGames }} Spiele)
                                    </span>
                                </PrimaryButton>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'

const props = defineProps({
    team: Object,
    preview: Array,
    fileName: String,
    totalGames: Number,
    matchingGames: Number,
    newGames: Number,
    existingGames: Number,
    gameType: String,
})

const form = useForm({
    team_id: props.team.id,
    confirmed: true,
})

const confirmImport = () => {
    form.post(route('games.import.import'), {
        onSuccess: () => {
            // Will redirect to index with success message
        },
        onError: (errors) => {
            console.log('Import errors:', errors)
        }
    })
}

const cancelImport = () => {
    form.post(route('games.import.cancel', { team_id: props.team.id }), {
        onSuccess: () => {
            // Will redirect to index
        }
    })
}

const formatDateTime = (dateTimeString) => {
    if (!dateTimeString) return ''
    
    const date = new Date(dateTimeString)
    const today = new Date()
    
    const dateOptions = { 
        weekday: 'short',
        day: '2-digit', 
        month: '2-digit',
        year: '2-digit'
    }
    
    const timeOptions = { 
        hour: '2-digit', 
        minute: '2-digit'
    }
    
    return `${date.toLocaleDateString('de-DE', dateOptions)} • ${date.toLocaleTimeString('de-DE', timeOptions)}`
}

const getGameTypeLabel = (gameType) => {
    const labels = {
        'regular_season': 'Liga',
        'playoff': 'Playoff',
        'championship': 'Meisterschaft',
        'friendly': 'Freundschaftsspiel',
        'tournament': 'Turnier',
        'scrimmage': 'Trainingsspiel'
    }
    return labels[gameType] || gameType
}

const getGameTypeClasses = (gameType) => {
    const classes = {
        'regular_season': 'bg-blue-100 text-blue-800',
        'playoff': 'bg-purple-100 text-purple-800',
        'championship': 'bg-yellow-100 text-yellow-800',
        'friendly': 'bg-green-100 text-green-800',
        'tournament': 'bg-orange-100 text-orange-800',
        'scrimmage': 'bg-gray-100 text-gray-800'
    }
    return classes[gameType] || 'bg-gray-100 text-gray-800'
}
</script>