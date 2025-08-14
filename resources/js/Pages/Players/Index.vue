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
                        :href="route('players.create')"
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
                        <div class="mb-6">
                            <div class="flex justify-between items-center">
                                <div class="w-1/3">
                                    <input
                                        type="text"
                                        placeholder="Spieler suchen..."
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    />
                                </div>
                                <div class="text-sm text-gray-600">
                                    {{ players.total }} Spieler gefunden
                                </div>
                            </div>
                        </div>

                        <!-- Players Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div
                                v-for="player in players.data"
                                :key="player.id"
                                class="bg-white border rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200"
                            >
                                <div class="p-6">
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
                                        <div>
                                            <span class="font-medium">Team:</span> {{ player.team?.name }}
                                        </div>
                                        <div>
                                            <span class="font-medium">Club:</span> {{ player.team?.club?.name }}
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
                                                :href="route('players.show', player.id)"
                                                class="text-indigo-600 hover:text-indigo-500 font-medium text-sm"
                                            >
                                                Details anzeigen
                                            </Link>
                                            <div class="flex space-x-2">
                                                <Link
                                                    v-if="player.can?.update"
                                                    :href="route('players.edit', player.id)"
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
                                :href="route('players.create')"
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
import AppLayout from '@/Layouts/AppLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import { Link } from '@inertiajs/vue3'

defineProps({
    players: Object,
    can: Object,
})

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
</script>