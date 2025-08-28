<template>
    <AppLayout title="Import Verlauf">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Import Verlauf
                </h2>
                <div class="flex space-x-3">
                    <SecondaryButton 
                        :href="route('games.import.index')"
                        as="Link"
                    >
                        📂 Neuer Import
                    </SecondaryButton>
                    <SecondaryButton 
                        :href="route('web.games.index')"
                        as="Link"
                    >
                        🏀 Alle Spiele
                    </SecondaryButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Filters -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Filter</h3>
                        <form @submit.prevent="applyFilters" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <!-- Team Filter -->
                            <div>
                                <InputLabel for="team_filter" value="Team" />
                                <select
                                    id="team_filter"
                                    v-model="filterForm.team_id"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                >
                                    <option value="">Alle Teams</option>
                                    <option 
                                        v-for="team in teams" 
                                        :key="team.id" 
                                        :value="team.id"
                                    >
                                        {{ team.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Import Source Filter -->
                            <div>
                                <InputLabel for="source_filter" value="Import-Quelle" />
                                <select
                                    id="source_filter"
                                    v-model="filterForm.import_source"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                >
                                    <option value="">Alle Quellen</option>
                                    <option value="ical">iCAL Import</option>
                                    <option value="api">API Import</option>
                                </select>
                            </div>

                            <!-- Date From -->
                            <div>
                                <InputLabel for="date_from" value="Von Datum" />
                                <TextInput
                                    id="date_from"
                                    v-model="filterForm.date_from"
                                    type="date"
                                    class="mt-1 block w-full"
                                />
                            </div>

                            <!-- Date To -->
                            <div>
                                <InputLabel for="date_to" value="Bis Datum" />
                                <TextInput
                                    id="date_to"
                                    v-model="filterForm.date_to"
                                    type="date"
                                    class="mt-1 block w-full"
                                />
                            </div>
                        </form>

                        <div class="mt-4 flex justify-between items-center">
                            <div class="text-sm text-gray-600">
                                {{ importedGames.total }} importierte Spiele gefunden
                            </div>
                            <div class="flex space-x-2">
                                <SecondaryButton @click="clearFilters">
                                    Filter löschen
                                </SecondaryButton>
                                <PrimaryButton @click="applyFilters">
                                    Filter anwenden
                                </PrimaryButton>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Imported Games List -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="px-6 py-4 bg-gray-50 border-b">
                        <h3 class="text-lg font-medium text-gray-900">
                            Importierte Spiele
                        </h3>
                        <p class="text-sm text-gray-600 mt-1">
                            Alle Spiele die über Import-Funktionen erstellt wurden
                        </p>
                    </div>

                    <div v-if="importedGames.data.length === 0" class="p-6 text-center text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Keine importierten Spiele</h3>
                        <p class="mt-1 text-sm text-gray-500">Mit den aktuellen Filtern wurden keine Spiele gefunden.</p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Import
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
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Aktionen
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="game in importedGames.data" :key="game.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <span 
                                                :class="importSourceClasses(game.import_source)"
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                            >
                                                {{ importSourceName(game.import_source) }}
                                            </span>
                                        </div>
                                        <div v-if="game.external_game_id" class="text-xs text-gray-500 mt-1">
                                            ID: {{ game.external_game_id.substring(0, 8) }}...
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ formatDateTime(game.scheduled_at) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <div class="flex items-center space-x-2">
                                            <span :class="game.is_home_game ? 'font-semibold' : ''">
                                                {{ getHomeTeamName(game) }}
                                            </span>
                                            <span class="text-gray-400">-</span>
                                            <span :class="!game.is_home_game ? 'font-semibold' : ''">
                                                {{ getAwayTeamName(game) }}
                                            </span>
                                        </div>
                                        <div class="flex items-center space-x-2 mt-1">
                                            <span v-if="game.is_home_game" class="bg-blue-100 text-blue-800 px-1 py-0.5 rounded text-xs">
                                                Heimspiel
                                            </span>
                                            <span v-else class="bg-orange-100 text-orange-800 px-1 py-0.5 rounded text-xs">
                                                Auswärtsspiel
                                            </span>
                                            <span v-if="hasExternalTeam(game)" class="bg-gray-100 text-gray-800 px-1 py-0.5 rounded text-xs">
                                                Externes Team
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <div>{{ game.venue || 'Nicht angegeben' }}</div>
                                        <div v-if="game.venue_code" class="text-xs text-gray-500">
                                            Halle: {{ game.venue_code }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span 
                                            :class="statusClasses(game.status)"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        >
                                            {{ statusName(game.status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button
                                            @click="$inertia.visit(route('web.games.show', game.id))"
                                            class="text-indigo-600 hover:text-indigo-900 mr-4"
                                        >
                                            Anzeigen
                                        </button>
                                        <button
                                            v-if="game.status === 'scheduled'"
                                            @click="$inertia.visit(route('web.games.edit', game.id))"
                                            class="text-gray-600 hover:text-gray-900"
                                        >
                                            Bearbeiten
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="importedGames.links && importedGames.links.length > 3" class="px-6 py-4 border-t">
                        <nav class="flex items-center justify-between">
                            <div class="flex justify-between flex-1 sm:hidden">
                                <Link
                                    v-if="importedGames.prev_page_url"
                                    :href="importedGames.prev_page_url"
                                    class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:text-gray-500"
                                >
                                    Zurück
                                </Link>
                                <Link
                                    v-if="importedGames.next_page_url"
                                    :href="importedGames.next_page_url"
                                    class="relative ml-3 inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:text-gray-500"
                                >
                                    Weiter
                                </Link>
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Zeige
                                        <span class="font-medium">{{ importedGames.from || 0 }}</span>
                                        bis
                                        <span class="font-medium">{{ importedGames.to || 0 }}</span>
                                        von
                                        <span class="font-medium">{{ importedGames.total }}</span>
                                        Ergebnissen
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                        <template v-for="link in importedGames.links" :key="link.label">
                                            <Link
                                                v-if="link.url"
                                                :href="link.url"
                                                v-html="link.label"
                                                :class="[
                                                    'relative inline-flex items-center px-4 py-2 text-sm font-medium',
                                                    link.active 
                                                        ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                                                        : 'bg-white border-gray-300 text-gray-500 hover:text-gray-700 hover:bg-gray-50'
                                                ]"
                                            />
                                            <span
                                                v-else
                                                v-html="link.label"
                                                class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-300 bg-white border border-gray-300 cursor-default"
                                            />
                                        </template>
                                    </nav>
                                </div>
                            </div>
                        </nav>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import InputLabel from '@/Components/InputLabel.vue'

const props = defineProps({
    importedGames: Object,
    teams: Array,
    filters: Object,
})

// Filter form
const filterForm = reactive({
    team_id: props.filters?.team_id || '',
    import_source: props.filters?.import_source || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
})

const applyFilters = () => {
    const query = {}
    if (filterForm.team_id) query.team_id = filterForm.team_id
    if (filterForm.import_source) query.import_source = filterForm.import_source
    if (filterForm.date_from) query.date_from = filterForm.date_from
    if (filterForm.date_to) query.date_to = filterForm.date_to
    
    router.get(route('games.import.history'), query, {
        preserveState: true,
        preserveScroll: true,
    })
}

const clearFilters = () => {
    filterForm.team_id = ''
    filterForm.import_source = ''
    filterForm.date_from = ''
    filterForm.date_to = ''
    
    router.get(route('games.import.history'))
}

// Helper functions
const importSourceName = (source) => {
    const sourceMap = {
        'ical': 'iCAL',
        'api': 'API',
        'manual': 'Manuell'
    }
    return sourceMap[source] || source
}

const importSourceClasses = (source) => {
    const classMap = {
        'ical': 'bg-blue-100 text-blue-800',
        'api': 'bg-purple-100 text-purple-800',
        'manual': 'bg-gray-100 text-gray-800'
    }
    return classMap[source] || 'bg-gray-100 text-gray-800'
}

const statusName = (status) => {
    const statusMap = {
        'scheduled': 'Geplant',
        'live': 'Live',
        'halftime': 'Halbzeit',
        'overtime': 'Verlängerung',
        'finished': 'Beendet',
        'cancelled': 'Abgesagt',
        'postponed': 'Verschoben'
    }
    return statusMap[status] || status
}

const statusClasses = (status) => {
    const classMap = {
        'scheduled': 'bg-gray-100 text-gray-800',
        'live': 'bg-red-100 text-red-800',
        'halftime': 'bg-yellow-100 text-yellow-800',
        'overtime': 'bg-orange-100 text-orange-800',
        'finished': 'bg-green-100 text-green-800',
        'cancelled': 'bg-red-100 text-red-800',
        'postponed': 'bg-purple-100 text-purple-800'
    }
    return classMap[status] || 'bg-gray-100 text-gray-800'
}

const getHomeTeamName = (game) => {
    return game.home_team_name || game.home_team?.name || 'Unbekannt'
}

const getAwayTeamName = (game) => {
    return game.away_team_name || game.away_team?.name || 'Unbekannt'
}

const hasExternalTeam = (game) => {
    return (game.away_team_id === null && game.away_team_name) || 
           (game.home_team_id === null && game.home_team_name)
}

const formatDateTime = (dateTimeString) => {
    if (!dateTimeString) return ''
    
    const date = new Date(dateTimeString)
    const dateOptions = { 
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
</script>