<template>
    <AppLayout title="Spieler">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Spieler
                </h2>
                <div>
                    <PrimaryButton 
                        v-if="can.create"
                        :href="route('web.players.create')"
                        as="Link"
                    >
                        Spieler hinzufügen
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <!-- Search and Filter -->
                        <div class="mb-6 space-y-4">
                            <!-- Main Search Bar -->
                            <div class="flex justify-between items-center">
                                <div class="flex-1 max-w-md">
                                    <div class="relative">
                                        <input
                                            v-model="search"
                                            @input="performSearch"
                                            type="text"
                                            placeholder="Spieler suchen (Name, Rückennummer, Position)..."
                                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 pl-10"
                                        />
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-4">
                                    <button
                                        @click="showAdvancedFilters = !showAdvancedFilters"
                                        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                    >
                                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z" />
                                        </svg>
                                        Filter
                                        <span v-if="activeFiltersCount > 0" class="ml-2 bg-indigo-100 text-indigo-800 text-xs px-2 py-1 rounded-full">
                                            {{ activeFiltersCount }}
                                        </span>
                                    </button>
                                    
                                    <div class="text-sm text-gray-600">
                                        {{ players.total }} Spieler gefunden
                                    </div>
                                </div>
                            </div>

                            <!-- Advanced Filters -->
                            <div v-show="showAdvancedFilters" class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                    <!-- Team Filter -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Team</label>
                                        <select
                                            v-model="filters.team_id"
                                            @change="applyFilters"
                                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        >
                                            <option value="">Alle Teams</option>
                                            <option v-for="team in availableTeams" :key="team.id" :value="team.id">
                                                {{ team.name }}
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Position Filter -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Position</label>
                                        <select
                                            v-model="filters.position"
                                            @change="applyFilters"
                                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        >
                                            <option value="">Alle Positionen</option>
                                            <option value="PG">Point Guard (PG)</option>
                                            <option value="SG">Shooting Guard (SG)</option>
                                            <option value="SF">Small Forward (SF)</option>
                                            <option value="PF">Power Forward (PF)</option>
                                            <option value="C">Center (C)</option>
                                        </select>
                                    </div>

                                    <!-- Status Filter -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                        <select
                                            v-model="filters.status"
                                            @change="applyFilters"
                                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        >
                                            <option value="">Alle Status</option>
                                            <option value="active">Aktiv</option>
                                            <option value="inactive">Inaktiv</option>
                                            <option value="injured">Verletzt</option>
                                            <option value="suspended">Gesperrt</option>
                                        </select>
                                    </div>

                                    <!-- Medical Clearance Filter -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Medical Status</label>
                                        <select
                                            v-model="filters.medical_status"
                                            @change="applyFilters"
                                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        >
                                            <option value="">Alle</option>
                                            <option value="cleared">Freigegeben</option>
                                            <option value="expired">Abgelaufen</option>
                                            <option value="pending">Ausstehend</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Additional Filter Options -->
                                <div class="mt-4 flex flex-wrap gap-4">
                                    <label class="flex items-center">
                                        <input
                                            type="checkbox"
                                            v-model="filters.is_captain"
                                            @change="applyFilters"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                        <span class="ml-2 text-sm text-gray-700">Nur Kapitäne</span>
                                    </label>
                                    
                                    <label class="flex items-center">
                                        <input
                                            type="checkbox"
                                            v-model="filters.is_starter"
                                            @change="applyFilters"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                        <span class="ml-2 text-sm text-gray-700">Nur Stammspieler</span>
                                    </label>
                                    
                                    <label class="flex items-center">
                                        <input
                                            type="checkbox"
                                            v-model="filters.is_rookie"
                                            @change="applyFilters"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                        <span class="ml-2 text-sm text-gray-700">Nur Rookies</span>
                                    </label>
                                </div>

                                <!-- Clear Filters -->
                                <div class="mt-4 flex justify-end">
                                    <button
                                        @click="clearFilters"
                                        class="text-sm text-indigo-600 hover:text-indigo-500"
                                    >
                                        Alle Filter zurücksetzen
                                    </button>
                                </div>
                            </div>

                            <!-- Active Filters Display -->
                            <div v-if="activeFiltersCount > 0" class="flex flex-wrap gap-2">
                                <span v-if="filters.team_id" class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800">
                                    Team: {{ getTeamName(filters.team_id) }}
                                    <button @click="filters.team_id = ''; applyFilters()" class="ml-2 text-blue-600 hover:text-blue-800">×</button>
                                </span>
                                <span v-if="filters.position" class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-green-100 text-green-800">
                                    Position: {{ filters.position }}
                                    <button @click="filters.position = ''; applyFilters()" class="ml-2 text-green-600 hover:text-green-800">×</button>
                                </span>
                                <span v-if="filters.status" class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-yellow-100 text-yellow-800">
                                    Status: {{ getStatusText(filters.status) }}
                                    <button @click="filters.status = ''; applyFilters()" class="ml-2 text-yellow-600 hover:text-yellow-800">×</button>
                                </span>
                                <span v-if="filters.medical_status" class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-red-100 text-red-800">
                                    Medical: {{ getMedicalStatusText(filters.medical_status) }}
                                    <button @click="filters.medical_status = ''; applyFilters()" class="ml-2 text-red-600 hover:text-red-800">×</button>
                                </span>
                            </div>

                            <!-- Bulk Actions -->
                            <div v-if="selectedPlayers.length > 0" class="bg-indigo-50 p-4 rounded-lg border border-indigo-200">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <span class="text-sm font-medium text-indigo-900">
                                            {{ selectedPlayers.length }} Spieler ausgewählt
                                        </span>
                                        <button
                                            @click="clearSelection"
                                            class="text-sm text-indigo-700 hover:text-indigo-500"
                                        >
                                            Auswahl aufheben
                                        </button>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <select
                                            v-model="bulkAction"
                                            class="border-indigo-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm"
                                        >
                                            <option value="">Aktion wählen</option>
                                            <option value="activate">Aktivieren</option>
                                            <option value="deactivate">Deaktivieren</option>
                                            <option value="export">Exportieren</option>
                                        </select>
                                        <button
                                            @click="executeBulkAction"
                                            :disabled="!bulkAction"
                                            class="bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 text-white px-4 py-2 rounded-md text-sm font-medium"
                                        >
                                            Ausführen
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Players Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div
                                v-for="player in players.data"
                                :key="player.id"
                                class="bg-white border rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200 relative"
                            >
                                <!-- Selection Checkbox -->
                                <div class="absolute top-4 left-4 z-10">
                                    <input
                                        type="checkbox"
                                        :value="player.id"
                                        v-model="selectedPlayers"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    />
                                </div>
                                
                                <div class="p-6 pt-12">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center space-x-3">
                                            <div class="flex-shrink-0">
                                                <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center">
                                                    <span class="text-indigo-800 font-bold text-lg">
                                                        {{ player.jersey_number }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div>
                                                <h3 class="text-lg font-semibold text-gray-900">
                                                    {{ player.user?.name || `${player.first_name} ${player.last_name}` }}
                                                </h3>
                                                <p class="text-sm text-gray-600">{{ player.primary_position }}</p>
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-center space-x-2">
                                            <span
                                                v-if="player.is_captain"
                                                class="inline-flex items-center px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full"
                                            >
                                                Kapitän
                                            </span>
                                            <span
                                                v-if="player.is_starter"
                                                class="inline-flex items-center px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full"
                                            >
                                                Starter
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="text-sm text-gray-600 space-y-2">
                                        <div v-if="player.teams && player.teams.length > 0">
                                            <div v-for="team in player.teams" :key="team.id" class="mb-1">
                                                <span class="font-medium">Team:</span> {{ team.name }}
                                                <span v-if="team.pivot?.jersey_number" class="ml-1">#{{ team.pivot.jersey_number }}</span>
                                                <div v-if="team.club" class="text-gray-500 text-xs">
                                                    <span class="font-medium">Club:</span> {{ team.club.name }}
                                                </div>
                                            </div>
                                        </div>
                                        <div v-else class="text-gray-400 italic">
                                            Kein Team zugeordnet
                                        </div>
                                        <div v-if="player.height">
                                            <span class="font-medium">Größe:</span> {{ player.height }} cm
                                        </div>
                                        <div v-if="player.birth_date">
                                            <span class="font-medium">Alter:</span> {{ calculateAge(player.birth_date) }} Jahre
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <span
                                            :class="{
                                                'bg-green-100 text-green-800': player.status === 'active',
                                                'bg-red-100 text-red-800': player.status === 'injured',
                                                'bg-gray-100 text-gray-800': player.status === 'inactive',
                                                'bg-yellow-100 text-yellow-800': player.status === 'suspended'
                                            }"
                                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                        >
                                            {{ getStatusText(player.status) }}
                                        </span>
                                    </div>

                                    <div class="mt-4 pt-4 border-t border-gray-200">
                                        <div class="flex justify-between items-center">
                                            <Link
                                                :href="route('web.players.show', player.id)"
                                                class="text-indigo-600 hover:text-indigo-500 font-medium text-sm"
                                            >
                                                Details anzeigen
                                            </Link>
                                            <div class="flex space-x-2">
                                                <Link
                                                    v-if="player.can?.update"
                                                    :href="route('web.players.edit', player.id)"
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
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-if="players.data.length === 0" class="text-center py-12">
                            <div class="text-gray-500 text-lg mb-4">
                                Keine Spieler gefunden
                            </div>
                            <PrimaryButton 
                                v-if="can.create"
                                :href="route('web.players.create')"
                                as="Link"
                            >
                                Ersten Spieler hinzufügen
                            </PrimaryButton>
                        </div>

                        <!-- Pagination -->
                        <div v-if="players.data.length > 0" class="mt-8">
                            <div class="flex justify-center">
                                <div class="text-sm text-gray-600">
                                    Seite {{ players.current_page }} von {{ players.last_page }}
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
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    players: Object,
    can: Object,
    teams: {
        type: Array,
        default: () => []
    }
})

// Filter and Search State
const search = ref('')
const showAdvancedFilters = ref(false)
const selectedPlayers = ref([])
const bulkAction = ref('')

const filters = ref({
    team_id: '',
    position: '',
    status: '',
    medical_status: '',
    is_captain: false,
    is_starter: false,
    is_rookie: false
})

// Available Teams (extracted from players or passed as prop)
const availableTeams = computed(() => {
    if (props.teams && props.teams.length > 0) {
        return props.teams
    }

    // Extract unique teams from players
    const teams = new Map()
    props.players.data.forEach(player => {
        if (player.teams && player.teams.length > 0) {
            player.teams.forEach(team => {
                teams.set(team.id, team)
            })
        }
    })
    return Array.from(teams.values())
})

// Active Filters Count
const activeFiltersCount = computed(() => {
    let count = 0
    if (filters.value.team_id) count++
    if (filters.value.position) count++
    if (filters.value.status) count++
    if (filters.value.medical_status) count++
    if (filters.value.is_captain) count++
    if (filters.value.is_starter) count++
    if (filters.value.is_rookie) count++
    return count
})

// Helper Functions
const calculateAge = (birthDate) => {
    if (!birthDate) return null
    const today = new Date()
    const birth = new Date(birthDate)
    let age = today.getFullYear() - birth.getFullYear()
    const monthDiff = today.getMonth() - birth.getMonth()
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
        age--
    }
    return age
}

const getStatusText = (status) => {
    const statusTexts = {
        'active': 'Aktiv',
        'inactive': 'Inaktiv',
        'injured': 'Verletzt',
        'suspended': 'Gesperrt'
    }
    return statusTexts[status] || status
}

const getMedicalStatusText = (status) => {
    const statusTexts = {
        'cleared': 'Freigegeben',
        'expired': 'Abgelaufen',
        'pending': 'Ausstehend'
    }
    return statusTexts[status] || status
}

const getTeamName = (teamId) => {
    const team = availableTeams.value.find(t => t.id == teamId)
    return team ? team.name : 'Unbekannt'
}

// Search and Filter Functions
let searchTimeout = null

const performSearch = () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        applyFilters()
    }, 300) // Debounce search
}

const applyFilters = () => {
    const params = new URLSearchParams()
    
    if (search.value) {
        params.append('search', search.value)
    }
    
    Object.entries(filters.value).forEach(([key, value]) => {
        if (value && value !== false) {
            params.append(key, value)
        }
    })
    
    const queryString = params.toString()
    const url = queryString ? `${route('web.players.index')}?${queryString}` : route('web.players.index')
    
    router.visit(url, {
        preserveState: true,
        preserveScroll: true,
        only: ['players']
    })
}

const clearFilters = () => {
    search.value = ''
    filters.value = {
        team_id: '',
        position: '',
        status: '',
        medical_status: '',
        is_captain: false,
        is_starter: false,
        is_rookie: false
    }
    applyFilters()
}

// Bulk Actions
const clearSelection = () => {
    selectedPlayers.value = []
    bulkAction.value = ''
}

const executeBulkAction = () => {
    if (!bulkAction.value || selectedPlayers.value.length === 0) return
    
    const data = {
        player_ids: selectedPlayers.value,
        action: bulkAction.value
    }
    
    switch (bulkAction.value) {
        case 'activate':
            router.post(route('web.players.bulk-status'), {
                ...data,
                status: 'active'
            })
            break
        case 'deactivate':
            router.post(route('web.players.bulk-status'), {
                ...data,
                status: 'inactive'
            })
            break
        case 'export':
            // Handle export functionality
            const url = route('web.players.export') + '?' + new URLSearchParams({
                player_ids: selectedPlayers.value.join(',')
            }).toString()
            window.open(url, '_blank')
            break
    }
    
    clearSelection()
}

// Initialize filters from URL parameters
const initializeFilters = () => {
    const urlParams = new URLSearchParams(window.location.search)
    
    search.value = urlParams.get('search') || ''
    filters.value.team_id = urlParams.get('team_id') || ''
    filters.value.position = urlParams.get('position') || ''
    filters.value.status = urlParams.get('status') || ''
    filters.value.medical_status = urlParams.get('medical_status') || ''
    filters.value.is_captain = urlParams.get('is_captain') === 'true'
    filters.value.is_starter = urlParams.get('is_starter') === 'true'
    filters.value.is_rookie = urlParams.get('is_rookie') === 'true'
}

// Initialize on mount
initializeFilters()
</script>