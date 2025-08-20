<template>
    <AppLayout title="Sporthallen">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Sporthallen verwalten
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium text-gray-900">
                                Alle Sporthallen
                            </h3>
                            <Link 
                                :href="route('gym.create-hall')" 
                                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md"
                            >
                                <PlusIcon class="h-5 w-5 mr-2" />
                                Neue Sporthalle
                            </Link>
                        </div>

                        <div v-if="gymHalls.length === 0" class="text-center py-12">
                            <BuildingStorefrontIcon class="mx-auto h-12 w-12 text-gray-400" />
                            <h3 class="mt-2 text-sm font-medium text-gray-900">
                                Keine Sporthallen vorhanden
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Erstellen Sie Ihre erste Sporthalle.
                            </p>
                            <div class="mt-6">
                                <Link 
                                    :href="route('gym.create-hall')" 
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md"
                                >
                                    <PlusIcon class="h-5 w-5 mr-2" />
                                    Neue Sporthalle
                                </Link>
                            </div>
                        </div>

                        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div 
                                v-for="hall in gymHalls" 
                                :key="hall.id"
                                class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-lg transition-shadow"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h4 class="text-lg font-semibold text-gray-900">
                                            {{ hall.name }}
                                        </h4>
                                        <p v-if="hall.description" class="text-sm text-gray-600 mt-1">
                                            {{ hall.description }}
                                        </p>
                                    </div>
                                    <div class="ml-2">
                                        <span 
                                            :class="[
                                                'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                                                hall.is_active 
                                                    ? 'bg-green-100 text-green-800' 
                                                    : 'bg-red-100 text-red-800'
                                            ]"
                                        >
                                            {{ hall.is_active ? 'Aktiv' : 'Inaktiv' }}
                                        </span>
                                    </div>
                                </div>

                                <div class="mt-4 space-y-2">
                                    <div class="flex items-center text-sm text-gray-600">
                                        <MapPinIcon class="h-4 w-4 mr-2" />
                                        <span>{{ hall.address || 'Keine Adresse angegeben' }}</span>
                                    </div>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <ClockIcon class="h-4 w-4 mr-2" />
                                        <span>{{ hall.time_slots_count || 0 }} Zeitslots</span>
                                    </div>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <CalendarIcon class="h-4 w-4 mr-2" />
                                        <span>{{ hall.bookings_count || 0 }} Buchungen</span>
                                    </div>
                                </div>

                                <div class="mt-6 flex space-x-2">
                                    <button
                                        @click="editHall(hall)"
                                        class="flex-1 px-3 py-2 text-sm font-medium text-blue-600 hover:text-blue-800"
                                    >
                                        Bearbeiten
                                    </button>
                                    <button
                                        @click="viewSchedule(hall)"
                                        class="flex-1 px-3 py-2 text-sm font-medium text-green-600 hover:text-green-800"
                                    >
                                        Terminplan
                                    </button>
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
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    BuildingStorefrontIcon,
    PlusIcon,
    MapPinIcon,
    ClockIcon,
    CalendarIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    gymHalls: Array,
})

const editHall = (hall) => {
    // Navigate to edit hall page or open modal
    console.log('Edit hall:', hall)
}

const viewSchedule = (hall) => {
    // Navigate to schedule view for this hall
    console.log('View schedule for hall:', hall)
}
</script>