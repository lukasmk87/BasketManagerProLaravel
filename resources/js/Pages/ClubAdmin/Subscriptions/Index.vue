<script setup>
import ClubAdminLayout from '@/Layouts/ClubAdminLayout.vue';

const props = defineProps({
    club: Object,
    subscription: Object,
    message: String,
});
</script>

<template>
    <ClubAdminLayout title="Abo-Verwaltung">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Abo-Verwaltung
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Info Message -->
                <div v-if="message" class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">{{ message }}</p>
                        </div>
                    </div>
                </div>

                <!-- Current Subscription -->
                <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Aktuelles Abonnement</h3>
                                <p class="text-sm text-gray-500 mt-1">{{ club.name }}</p>
                            </div>
                            <span
                                :class="subscription.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                            >
                                {{ subscription.status }}
                            </span>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-2xl font-bold text-gray-900">{{ subscription.plan }} Plan</h4>
                                <p class="text-sm text-gray-500 mt-1">Ihr aktueller Tarif</p>
                            </div>
                            <button
                                type="button"
                                disabled
                                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest opacity-50 cursor-not-allowed"
                            >
                                Upgrade (Bald verfügbar)
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Features -->
                <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Inkludierte Features</h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div
                                v-for="(value, feature) in subscription.features"
                                :key="feature"
                                class="flex items-center p-4 bg-gray-50 rounded-lg"
                            >
                                <div class="flex-shrink-0">
                                    <svg
                                        v-if="value === true || typeof value === 'number'"
                                        class="h-6 w-6 text-green-500"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <svg
                                        v-else
                                        class="h-6 w-6 text-red-500"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-900">{{ feature }}</p>
                                    <p class="text-sm text-gray-500">
                                        <template v-if="typeof value === 'number'">
                                            Bis zu {{ value }}
                                        </template>
                                        <template v-else-if="value === true">
                                            Aktiviert
                                        </template>
                                        <template v-else>
                                            Nicht verfügbar
                                        </template>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Available Plans (Preview) -->
                <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Verfügbare Tarife</h3>
                        <p class="text-sm text-gray-500 mt-1">Upgrade auf einen höheren Plan für mehr Features</p>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Basic Plan -->
                            <div class="border-2 border-gray-200 rounded-lg p-6">
                                <h4 class="text-xl font-bold text-gray-900">Basic</h4>
                                <p class="text-sm text-gray-500 mt-1">Für kleine Clubs</p>
                                <div class="mt-4">
                                    <span class="text-3xl font-bold text-gray-900">€9.99</span>
                                    <span class="text-gray-500">/Monat</span>
                                </div>
                                <ul class="mt-6 space-y-3">
                                    <li class="flex items-start">
                                        <svg class="h-5 w-5 text-green-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span class="ml-3 text-sm text-gray-700">Bis zu 5 Teams</span>
                                    </li>
                                    <li class="flex items-start">
                                        <svg class="h-5 w-5 text-green-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span class="ml-3 text-sm text-gray-700">Bis zu 100 Spieler</span>
                                    </li>
                                    <li class="flex items-start">
                                        <svg class="h-5 w-5 text-green-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span class="ml-3 text-sm text-gray-700">Statistiken</span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Professional Plan -->
                            <div class="border-2 border-blue-500 rounded-lg p-6 relative">
                                <div class="absolute top-0 right-0 bg-blue-500 text-white px-3 py-1 text-xs font-semibold rounded-bl-lg">
                                    Empfohlen
                                </div>
                                <h4 class="text-xl font-bold text-gray-900">Professional</h4>
                                <p class="text-sm text-gray-500 mt-1">Für größere Clubs</p>
                                <div class="mt-4">
                                    <span class="text-3xl font-bold text-gray-900">€29.99</span>
                                    <span class="text-gray-500">/Monat</span>
                                </div>
                                <ul class="mt-6 space-y-3">
                                    <li class="flex items-start">
                                        <svg class="h-5 w-5 text-green-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span class="ml-3 text-sm text-gray-700">Unbegrenzte Teams</span>
                                    </li>
                                    <li class="flex items-start">
                                        <svg class="h-5 w-5 text-green-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span class="ml-3 text-sm text-gray-700">Unbegrenzte Spieler</span>
                                    </li>
                                    <li class="flex items-start">
                                        <svg class="h-5 w-5 text-green-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span class="ml-3 text-sm text-gray-700">Live Scoring</span>
                                    </li>
                                    <li class="flex items-start">
                                        <svg class="h-5 w-5 text-green-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span class="ml-3 text-sm text-gray-700">Erweiterte Statistiken</span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Enterprise Plan -->
                            <div class="border-2 border-gray-200 rounded-lg p-6">
                                <h4 class="text-xl font-bold text-gray-900">Enterprise</h4>
                                <p class="text-sm text-gray-500 mt-1">Für große Organisationen</p>
                                <div class="mt-4">
                                    <span class="text-3xl font-bold text-gray-900">Individuell</span>
                                </div>
                                <ul class="mt-6 space-y-3">
                                    <li class="flex items-start">
                                        <svg class="h-5 w-5 text-green-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span class="ml-3 text-sm text-gray-700">Alles aus Professional</span>
                                    </li>
                                    <li class="flex items-start">
                                        <svg class="h-5 w-5 text-green-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span class="ml-3 text-sm text-gray-700">Priority Support</span>
                                    </li>
                                    <li class="flex items-start">
                                        <svg class="h-5 w-5 text-green-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span class="ml-3 text-sm text-gray-700">Custom Features</span>
                                    </li>
                                    <li class="flex items-start">
                                        <svg class="h-5 w-5 text-green-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span class="ml-3 text-sm text-gray-700">SLA</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ClubAdminLayout>
</template>
