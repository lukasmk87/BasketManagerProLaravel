<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    club: {
        type: Object,
        required: true,
    },
    message: {
        type: String,
        default: 'Der Checkout wurde abgebrochen. Sie können es jederzeit erneut versuchen.',
    },
});
</script>

<template>
    <AppLayout title="Checkout Abgebrochen">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Checkout Abgebrochen
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <!-- Cancel Header -->
                    <div class="bg-gradient-to-r from-gray-500 to-gray-600 px-6 py-12 text-center text-white">
                        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-white bg-opacity-20 mb-4">
                            <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <h1 class="text-3xl font-bold mb-2">
                            Checkout Abgebrochen
                        </h1>
                        <p class="text-xl text-gray-100">
                            {{ message }}
                        </p>
                    </div>

                    <!-- Cancel Content -->
                    <div class="p-8">
                        <div class="space-y-6">
                            <!-- Club Info -->
                            <div class="rounded-lg bg-gray-50 p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-3">
                                    Was ist passiert?
                                </h3>
                                <p class="text-gray-600">
                                    Der Checkout-Vorgang für <strong>{{ club.name }}</strong> wurde abgebrochen.
                                    Es wurden keine Änderungen an Ihrem Abonnement vorgenommen und es wurde keine Zahlung durchgeführt.
                                </p>
                            </div>

                            <!-- Why This Might Have Happened -->
                            <div class="rounded-lg border-2 border-yellow-200 bg-yellow-50 p-6">
                                <h3 class="text-lg font-semibold text-yellow-900 mb-3">
                                    💡 Mögliche Gründe
                                </h3>
                                <ul class="space-y-2 text-sm text-yellow-800">
                                    <li class="flex items-start">
                                        <svg class="w-5 h-5 text-yellow-600 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                        </svg>
                                        <span>Sie haben den Browser geschlossen oder zurück navigiert</span>
                                    </li>
                                    <li class="flex items-start">
                                        <svg class="w-5 h-5 text-yellow-600 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                        </svg>
                                        <span>Sie haben auf "Abbrechen" geklickt</span>
                                    </li>
                                    <li class="flex items-start">
                                        <svg class="w-5 h-5 text-yellow-600 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                        </svg>
                                        <span>Der Checkout-Vorgang hat zu lange gedauert</span>
                                    </li>
                                    <li class="flex items-start">
                                        <svg class="w-5 h-5 text-yellow-600 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                        </svg>
                                        <span>Sie möchten einen anderen Plan wählen</span>
                                    </li>
                                </ul>
                            </div>

                            <!-- What You Can Do -->
                            <div class="rounded-lg border-2 border-blue-200 bg-blue-50 p-6">
                                <h3 class="text-lg font-semibold text-blue-900 mb-3">
                                    🚀 Was können Sie tun?
                                </h3>
                                <ul class="space-y-2 text-sm text-blue-800">
                                    <li class="flex items-start">
                                        <svg class="w-5 h-5 text-blue-600 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                        <span>Kehren Sie zur Abonnement-Seite zurück und wählen Sie einen Plan</span>
                                    </li>
                                    <li class="flex items-start">
                                        <svg class="w-5 h-5 text-blue-600 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                        <span>Überprüfen Sie die Plan-Details bevor Sie abonnieren</span>
                                    </li>
                                    <li class="flex items-start">
                                        <svg class="w-5 h-5 text-blue-600 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                        <span>Bei Fragen kontaktieren Sie unseren Support</span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Actions -->
                            <div class="flex flex-col sm:flex-row gap-3 pt-4">
                                <Link
                                    :href="route('club.subscription.index', { club: club.id })"
                                    class="flex-1"
                                >
                                    <PrimaryButton type="button" class="w-full justify-center">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                        Erneut versuchen
                                    </PrimaryButton>
                                </Link>

                                <Link
                                    :href="route('dashboard')"
                                    class="flex-1"
                                >
                                    <SecondaryButton type="button" class="w-full justify-center">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                        </svg>
                                        Zum Dashboard
                                    </SecondaryButton>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Support Info -->
                <div class="mt-6 text-center text-sm text-gray-600">
                    <p>
                        Benötigen Sie Hilfe bei der Auswahl des richtigen Plans?
                        <a href="mailto:support@basketmanager-pro.de" class="text-blue-600 hover:text-blue-800 font-medium">
                            Kontaktieren Sie unseren Support
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
