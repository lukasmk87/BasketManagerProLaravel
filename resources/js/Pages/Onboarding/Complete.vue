<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    club: {
        type: Object,
        default: null,
    },
    plan: {
        type: Object,
        default: null,
    },
});
</script>

<template>
    <Head title="Willkommen!" />

    <div class="min-h-screen bg-gradient-to-b from-green-50 to-white flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full text-center">
            <!-- Success Icon -->
            <div class="mx-auto w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mb-6 animate-bounce-slow">
                <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            <!-- Title -->
            <h1 class="text-3xl font-bold text-gray-900 mb-4">
                Alles bereit!
            </h1>

            <!-- Subtitle -->
            <p class="text-lg text-gray-600 mb-8">
                Dein Club <span class="font-semibold text-gray-900">{{ club?.name || 'wurde erstellt' }}</span>
                ist jetzt eingerichtet und du kannst loslegen.
            </p>

            <!-- Club Info Card -->
            <div v-if="club" class="bg-white rounded-xl shadow-lg p-6 mb-8 text-left">
                <div class="flex items-center space-x-4 mb-4">
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                        <img
                            v-if="club.logo_path"
                            :src="`/storage/${club.logo_path}`"
                            :alt="club.name"
                            class="w-10 h-10 object-cover rounded"
                        />
                        <svg v-else class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">{{ club.name }}</h3>
                        <p class="text-sm text-gray-500">{{ club.address_city }}</p>
                    </div>
                </div>

                <div v-if="plan" class="pt-4 border-t border-gray-100">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">Aktueller Plan:</span>
                        <span class="font-medium text-orange-600">{{ plan.name }}</span>
                    </div>
                </div>
            </div>

            <!-- Next Steps -->
            <div class="bg-gray-50 rounded-lg p-4 mb-8 text-left">
                <h4 class="font-medium text-gray-900 mb-3">Nächste Schritte:</h4>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Füge Spieler zu deinem Team hinzu</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Plane dein erstes Spiel oder Training</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Lade weitere Trainer oder Eltern ein</span>
                    </li>
                </ul>
            </div>

            <!-- CTA Button -->
            <Link :href="route('dashboard')">
                <PrimaryButton class="w-full justify-center py-3 text-lg">
                    Zum Dashboard
                </PrimaryButton>
            </Link>

            <!-- Support Link -->
            <p class="mt-6 text-sm text-gray-500">
                Fragen?
                <a href="mailto:support@basketmanager.pro" class="text-orange-600 hover:text-orange-700">
                    Kontaktiere uns
                </a>
            </p>
        </div>
    </div>
</template>

<style scoped>
@keyframes bounce-slow {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-5px);
    }
}
.animate-bounce-slow {
    animation: bounce-slow 2s ease-in-out infinite;
}
</style>
