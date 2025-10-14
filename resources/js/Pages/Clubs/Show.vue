<template>
    <AppLayout :title="club.name">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        {{ club.name }}
                        <span
                            v-if="club.is_verified"
                            class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                        >
                            Verifiziert
                        </span>
                    </h2>
                    <p v-if="club.short_name" class="text-gray-600">{{ club.short_name }}</p>
                </div>
                <div class="flex space-x-3">
                    <SecondaryButton 
                        :href="route('web.clubs.index')"
                        as="Link"
                    >
                        Zurück
                    </SecondaryButton>
                    <PrimaryButton 
                        v-if="can.update"
                        :href="route('web.clubs.edit', club.id)"
                        as="Link"
                    >
                        Bearbeiten
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Club Information -->
                    <div class="lg:col-span-2">
                        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Club-Informationen</h3>
                                
                                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div v-if="club.address_city">
                                        <dt class="text-sm font-medium text-gray-500">Stadt</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ club.address_city }}</dd>
                                    </div>
                                    
                                    <div v-if="club.founded_year">
                                        <dt class="text-sm font-medium text-gray-500">Gegründet</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ club.founded_year }}</dd>
                                    </div>
                                    
                                    <div v-if="club.country">
                                        <dt class="text-sm font-medium text-gray-500">Land</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ club.country }}</dd>
                                    </div>
                                    
                                    <div v-if="club.website">
                                        <dt class="text-sm font-medium text-gray-500">Website</dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            <a :href="club.website" target="_blank" class="text-indigo-600 hover:text-indigo-500">
                                                {{ club.website }}
                                            </a>
                                        </dd>
                                    </div>
                                    
                                    <div v-if="club.email">
                                        <dt class="text-sm font-medium text-gray-500">E-Mail</dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            <a :href="`mailto:${club.email}`" class="text-indigo-600 hover:text-indigo-500">
                                                {{ club.email }}
                                            </a>
                                        </dd>
                                    </div>
                                    
                                    <div v-if="club.phone">
                                        <dt class="text-sm font-medium text-gray-500">Telefon</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ club.phone }}</dd>
                                    </div>
                                </dl>
                                
                                <div v-if="club.address" class="mt-6">
                                    <dt class="text-sm font-medium text-gray-500">Adresse</dt>
                                    <dd class="mt-1 text-sm text-gray-900 whitespace-pre-line">{{ club.address }}</dd>
                                </div>
                                
                                <div v-if="club.description" class="mt-6">
                                    <dt class="text-sm font-medium text-gray-500">Beschreibung</dt>
                                    <dd class="mt-1 text-sm text-gray-900 whitespace-pre-line">{{ club.description }}</dd>
                                </div>
                            </div>
                        </div>

                        <!-- Teams -->
                        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mt-6">
                            <div class="p-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-medium text-gray-900">Teams</h3>
                                    <span class="text-sm text-gray-600">{{ club.teams.length }} Teams</span>
                                </div>
                                
                                <div v-if="club.teams.length > 0" class="space-y-4">
                                    <div
                                        v-for="team in club.teams"
                                        :key="team.id"
                                        class="border rounded-lg p-4 hover:bg-gray-50 transition-colors duration-200"
                                    >
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <h4 class="font-medium text-gray-900">{{ team.name }}</h4>
                                                <p class="text-sm text-gray-600 mt-1">
                                                    {{ team.season }} • {{ team.league || 'Keine Liga' }}
                                                </p>
                                                <p class="text-sm text-gray-500 mt-1">
                                                    {{ team.players.length }} Spieler
                                                </p>
                                            </div>
                                            <Link
                                                :href="route('web.teams.show', team.slug)"
                                                class="text-indigo-600 hover:text-indigo-500 text-sm font-medium"
                                            >
                                                Details
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                                
                                <div v-else class="text-center text-gray-500 py-6">
                                    Noch keine Teams erstellt
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Sidebar -->
                    <div>
                        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Statistiken</h3>
                                
                                <div class="space-y-4">
                                    <div class="text-center border rounded-lg p-4">
                                        <div class="text-2xl font-bold text-indigo-600">{{ statistics?.total_teams || 0 }}</div>
                                        <div class="text-sm text-gray-600">Teams</div>
                                    </div>
                                    
                                    <div class="text-center border rounded-lg p-4">
                                        <div class="text-2xl font-bold text-green-600">{{ statistics?.total_players || 0 }}</div>
                                        <div class="text-sm text-gray-600">Spieler</div>
                                    </div>
                                    
                                    <div class="text-center border rounded-lg p-4">
                                        <div class="text-2xl font-bold text-blue-600">{{ statistics?.total_games || 0 }}</div>
                                        <div class="text-sm text-gray-600">Spiele</div>
                                    </div>
                                    
                                    <div class="text-center border rounded-lg p-4">
                                        <div class="text-2xl font-bold text-purple-600">{{ club.users?.length || 0 }}</div>
                                        <div class="text-sm text-gray-600">Mitglieder</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Subscription Plan -->
                        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mt-6">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Subscription Plan</h3>

                                <div v-if="club.subscription_plan">
                                    <!-- Plan Badge -->
                                    <div class="mb-3">
                                        <span
                                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                                            :style="{
                                                backgroundColor: club.subscription_plan.color ? club.subscription_plan.color + '20' : '#EBF5FF',
                                                color: club.subscription_plan.color || '#1E40AF'
                                            }"
                                        >
                                            <svg v-if="club.subscription_plan.icon" class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            {{ club.subscription_plan.name }}
                                        </span>
                                    </div>

                                    <!-- Price -->
                                    <div class="mb-4 text-center border-b pb-3">
                                        <div class="text-2xl font-bold text-gray-900">
                                            {{ club.subscription_plan.price }} {{ club.subscription_plan.currency }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            / {{ club.subscription_plan.billing_interval === 'monthly' ? 'Monat' : 'Jahr' }}
                                        </div>
                                    </div>

                                    <!-- Description -->
                                    <p v-if="club.subscription_plan.description" class="text-sm text-gray-600 mb-4">
                                        {{ club.subscription_plan.description }}
                                    </p>

                                    <!-- Features -->
                                    <div v-if="club.subscription_plan.features && club.subscription_plan.features.length > 0" class="mb-4">
                                        <div class="text-xs font-semibold text-gray-700 mb-2 uppercase tracking-wide">Features</div>
                                        <div class="space-y-2">
                                            <div
                                                v-for="feature in club.subscription_plan.features"
                                                :key="feature"
                                                class="flex items-center text-sm text-gray-700"
                                            >
                                                <svg class="w-4 h-4 mr-2 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                <span>{{ feature }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Limits -->
                                    <div v-if="club.subscription_plan.limits && Object.keys(club.subscription_plan.limits).length > 0">
                                        <div class="text-xs font-semibold text-gray-700 mb-2 uppercase tracking-wide">Limits</div>
                                        <div class="space-y-2">
                                            <div
                                                v-for="(value, key) in club.subscription_plan.limits"
                                                :key="key"
                                                class="flex justify-between text-sm"
                                            >
                                                <span class="text-gray-600">{{ key }}</span>
                                                <span class="font-medium text-gray-900">{{ value === -1 ? '∞' : value }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-else class="text-center py-6">
                                    <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <p class="text-sm text-gray-500">Kein Plan zugeordnet</p>
                                    <p class="text-xs text-gray-400 mt-1">Nutzt Tenant-Features</p>
                                </div>
                            </div>
                        </div>

                        <!-- Club Members -->
                        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mt-6">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Mitglieder</h3>

                                <div v-if="club.users && club.users.length > 0" class="space-y-3">
                                    <div
                                        v-for="user in club.users.slice(0, 5)"
                                        :key="user.id"
                                        class="flex items-center justify-between"
                                    >
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                                            <div class="text-xs text-gray-500">{{ user.pivot?.role || 'Mitglied' }}</div>
                                        </div>
                                    </div>

                                    <div v-if="club.users.length > 5" class="text-center text-sm text-gray-500 pt-2 border-t">
                                        und {{ club.users.length - 5 }} weitere...
                                    </div>
                                </div>

                                <div v-else class="text-center text-gray-500 py-4">
                                    Keine Mitglieder
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
import SecondaryButton from '@/Components/SecondaryButton.vue'
import { Link } from '@inertiajs/vue3'

defineProps({
    club: Object,
    statistics: Object,
    can: Object,
})
</script>