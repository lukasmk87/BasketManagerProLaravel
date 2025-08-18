<template>
    <AppLayout title="Team Details">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        {{ team.name }}
                    </h2>
                    <p class="text-sm text-gray-600">
                        {{ team.club?.name }} • {{ team.season }}
                    </p>
                </div>
                <div class="flex space-x-3">
                    <Link
                        v-if="can.update"
                        :href="route('teams.edit', team.id)"
                        class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 transition ease-in-out duration-150"
                    >
                        Bearbeiten
                    </Link>
                    <Link
                        :href="route('teams.index')"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:bg-gray-50 active:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 transition ease-in-out duration-150"
                    >
                        Zurück
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Team Info Card -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Team Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Name</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ team.name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Club</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ team.club?.name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Saison</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ team.season }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Liga</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ team.league || 'Nicht zugewiesen' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Division</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ team.division || 'Nicht zugewiesen' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Altersgruppe</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ team.age_group || 'Nicht angegeben' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Geschlecht</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    <span v-if="team.gender === 'male'">Männlich</span>
                                    <span v-else-if="team.gender === 'female'">Weiblich</span>
                                    <span v-else>Gemischt</span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Status</dt>
                                <dd class="mt-1">
                                    <span
                                        :class="{
                                            'bg-green-100 text-green-800': team.is_active,
                                            'bg-red-100 text-red-800': !team.is_active
                                        }"
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                    >
                                        {{ team.is_active ? 'Aktiv' : 'Inaktiv' }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Cheftrainer</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ team.head_coach?.name || 'Nicht zugewiesen' }}</dd>
                            </div>
                        </div>
                        
                        <div v-if="team.description" class="mt-6">
                            <dt class="text-sm font-medium text-gray-500">Beschreibung</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ team.description }}</dd>
                        </div>
                    </div>
                </div>

                <!-- Statistics Card -->
                <div v-if="statistics" class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Statistiken</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div class="text-center">
                                <dt class="text-sm font-medium text-gray-500">Spieler</dt>
                                <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ team.players?.length || 0 }}</dd>
                            </div>
                            <div class="text-center">
                                <dt class="text-sm font-medium text-gray-500">Spiele gesamt</dt>
                                <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ (team.home_games?.length || 0) + (team.away_games?.length || 0) }}</dd>
                            </div>
                            <div class="text-center">
                                <dt class="text-sm font-medium text-gray-500">Heimspiele</dt>
                                <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ team.home_games?.length || 0 }}</dd>
                            </div>
                            <div class="text-center">
                                <dt class="text-sm font-medium text-gray-500">Auswärtsspiele</dt>
                                <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ team.away_games?.length || 0 }}</dd>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Players Card -->
                <div v-if="team.players && team.players.length > 0" class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Spieler ({{ team.players.length }})</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Nummer
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Name
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Position
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="player in team.players" :key="player.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <div class="flex items-center">
                                                #{{ player.pivot?.jersey_number || '-' }}
                                                <span v-if="player.pivot?.is_captain" class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    Kapitän
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">{{ player.user?.name }}</div>
                                                    <div class="text-sm text-gray-500">{{ player.user?.email }}</div>
                                                </div>
                                                <div v-if="player.pivot?.is_starter" class="ml-3">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                        Starter
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ player.pivot?.primary_position || 'Nicht angegeben' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                :class="{
                                                    'bg-green-100 text-green-800': player.pivot?.status === 'active',
                                                    'bg-red-100 text-red-800': player.pivot?.status === 'injured',
                                                    'bg-yellow-100 text-yellow-800': player.pivot?.status === 'suspended',
                                                    'bg-gray-100 text-gray-800': ['inactive', 'on_loan'].includes(player.pivot?.status)
                                                }"
                                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                            >
                                                <span v-if="player.pivot?.status === 'active'">Aktiv</span>
                                                <span v-else-if="player.pivot?.status === 'injured'">Verletzt</span>
                                                <span v-else-if="player.pivot?.status === 'suspended'">Gesperrt</span>
                                                <span v-else-if="player.pivot?.status === 'on_loan'">Leihgabe</span>
                                                <span v-else-if="player.pivot?.status === 'inactive'">Inaktiv</span>
                                                <span v-else>Unbekannt</span>
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Recent Games -->
                <div v-if="(team.home_games && team.home_games.length > 0) || (team.away_games && team.away_games.length > 0)" class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Letzte Spiele</h3>
                        <div class="space-y-4">
                            <!-- Home Games -->
                            <div v-for="game in team.home_games?.slice(0, 5)" :key="'home-' + game.id" class="flex items-center justify-between p-4 border rounded-lg">
                                <div class="flex-1">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ team.name }} vs {{ game.away_team?.name }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ new Date(game.scheduled_at).toLocaleDateString('de-DE') }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div v-if="game.status === 'finished'" class="text-sm font-medium">
                                        {{ game.home_team_score }}:{{ game.away_team_score }}
                                    </div>
                                    <div class="text-xs text-gray-500">{{ game.status === 'finished' ? 'Beendet' : 'Geplant' }}</div>
                                </div>
                            </div>
                            
                            <!-- Away Games -->
                            <div v-for="game in team.away_games?.slice(0, 5)" :key="'away-' + game.id" class="flex items-center justify-between p-4 border rounded-lg">
                                <div class="flex-1">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ game.home_team?.name }} vs {{ team.name }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ new Date(game.scheduled_at).toLocaleDateString('de-DE') }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div v-if="game.status === 'finished'" class="text-sm font-medium">
                                        {{ game.home_team_score }}:{{ game.away_team_score }}
                                    </div>
                                    <div class="text-xs text-gray-500">{{ game.status === 'finished' ? 'Beendet' : 'Geplant' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="!team.players || team.players.length === 0" class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6 text-center">
                        <div class="text-gray-500 text-lg mb-4">
                            Keine Spieler in diesem Team
                        </div>
                        <p class="text-gray-400 text-sm">
                            Spieler können über die Spielerverwaltung zu diesem Team hinzugefügt werden.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'

defineProps({
    team: Object,
    statistics: Object,
    can: Object,
})
</script>