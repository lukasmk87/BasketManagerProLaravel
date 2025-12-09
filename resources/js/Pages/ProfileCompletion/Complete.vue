<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    club: {
        type: Object,
        default: null,
    },
});
</script>

<template>
    <Head title="Profil vervollständigt" />

    <div class="min-h-screen bg-gradient-to-b from-green-50 to-white flex items-center justify-center py-12">
        <div class="max-w-md mx-auto text-center px-4">
            <div class="bg-white rounded-xl shadow-lg p-8">
                <!-- Success Icon -->
                <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>

                <!-- Club Logo -->
                <img
                    v-if="club?.logo_url"
                    :src="club.logo_url"
                    :alt="club.name"
                    class="h-16 w-16 mx-auto mb-4 rounded-full object-cover"
                />

                <h1 class="text-2xl font-bold text-gray-900 mb-4">
                    Profil vervollständigt!
                </h1>

                <p class="text-gray-600 mb-6">
                    Ihr Profil wurde erfolgreich erstellt. Sie können jetzt
                    <span v-if="club" class="font-medium">{{ club.name }}</span>
                    <span v-else>die App</span>
                    vollständig nutzen.
                </p>

                <!-- Next Steps -->
                <div class="bg-gray-50 rounded-lg p-4 mb-6 text-left">
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Nächste Schritte:</h3>
                    <ul class="text-sm text-gray-600 space-y-2">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-orange-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                            Schauen Sie sich Ihr Team im Dashboard an
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-orange-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                            Sehen Sie kommende Trainings und Spiele
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-orange-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                            Vervollständigen Sie Ihr Profil mit weiteren Details
                        </li>
                    </ul>
                </div>

                <Link
                    :href="route('dashboard')"
                    class="inline-block w-full py-3 px-4 bg-orange-600 text-white rounded-md hover:bg-orange-700 transition-colors font-medium"
                >
                    Zum Dashboard
                </Link>
            </div>
        </div>
    </div>
</template>
