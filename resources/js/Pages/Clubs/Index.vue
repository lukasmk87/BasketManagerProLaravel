<template>
    <AppLayout title="Clubs">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Clubs
                </h2>
                <div>
                    <PrimaryButton 
                        v-if="can.create"
                        :href="route('clubs.create')"
                        as="Link"
                    >
                        Club erstellen
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
                                        placeholder="Clubs suchen..."
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    />
                                </div>
                                <div class="text-sm text-gray-600">
                                    {{ clubs.total }} Clubs gefunden
                                </div>
                            </div>
                        </div>

                        <!-- Clubs Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div
                                v-for="club in clubs.data"
                                :key="club.id"
                                class="bg-white border rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200"
                            >
                                <div class="p-6">
                                    <div class="flex items-center justify-between mb-4">
                                        <h3 class="text-lg font-semibold text-gray-900">
                                            {{ club.name }}
                                        </h3>
                                        <span
                                            v-if="club.is_verified"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                                        >
                                            Verifiziert
                                        </span>
                                    </div>
                                    
                                    <div class="text-sm text-gray-600 space-y-2">
                                        <div v-if="club.address_city">
                                            <span class="font-medium">Stadt:</span> {{ club.address_city }}
                                        </div>
                                        <div>
                                            <span class="font-medium">Teams:</span> {{ club.teams_count }}
                                        </div>
                                        <div>
                                            <span class="font-medium">Mitglieder:</span> {{ club.users_count }}
                                        </div>
                                        <div v-if="club.founded_year">
                                            <span class="font-medium">Gegründet:</span> {{ club.founded_year }}
                                        </div>
                                    </div>

                                    <div class="mt-4 pt-4 border-t border-gray-200">
                                        <div class="flex justify-between items-center">
                                            <Link
                                                :href="route('clubs.show', club.id)"
                                                class="text-indigo-600 hover:text-indigo-500 font-medium text-sm"
                                            >
                                                Details anzeigen
                                            </Link>
                                            <div class="flex space-x-2">
                                                <Link
                                                    v-if="club.can?.update"
                                                    :href="route('clubs.edit', club.id)"
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
                        <div v-if="clubs.data.length === 0" class="text-center py-12">
                            <div class="text-gray-500 text-lg mb-4">
                                Keine Clubs gefunden
                            </div>
                            <PrimaryButton 
                                v-if="can.create"
                                :href="route('clubs.create')"
                                as="Link"
                            >
                                Ersten Club erstellen
                            </PrimaryButton>
                        </div>

                        <!-- Pagination -->
                        <div v-if="clubs.data.length > 0" class="mt-8">
                            <div class="flex justify-center">
                                <!-- Pagination component would go here -->
                                <div class="text-sm text-gray-600">
                                    Seite {{ clubs.current_page }} von {{ clubs.last_page }}
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
    clubs: Object,
    can: Object,
})
</script>