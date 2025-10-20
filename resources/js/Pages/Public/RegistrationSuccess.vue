<script setup>
defineProps({
    clubName: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <PublicLayout :title="`Registrierung erfolgreich - ${clubName}`">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Success Icon -->
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-green-100 mb-6">
                    <svg class="h-16 w-16 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>

                <h1 class="text-3xl font-bold text-gray-900 mb-4">
                    Registrierung erfolgreich!
                </h1>

                <p class="text-lg text-gray-600 mb-8">
                    Vielen Dank für Ihre Registrierung bei <span class="font-semibold">{{ clubName }}</span>.
                </p>
            </div>

            <!-- Information Card -->
            <div class="bg-white shadow-xl rounded-lg overflow-hidden mb-6">
                <div class="p-6 sm:p-8">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">
                        <svg class="inline w-6 h-6 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Wie geht es weiter?
                    </h2>

                    <ol class="space-y-4">
                        <li class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center h-8 w-8 rounded-full bg-blue-100 text-blue-600 font-bold">
                                    1
                                </div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-base font-medium text-gray-900">Prüfung Ihrer Daten</h3>
                                <p class="mt-1 text-sm text-gray-600">
                                    Ein Club-Administrator wird Ihre Registrierung prüfen und Sie einem passenden Team zuweisen.
                                </p>
                            </div>
                        </li>

                        <li class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center h-8 w-8 rounded-full bg-blue-100 text-blue-600 font-bold">
                                    2
                                </div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-base font-medium text-gray-900">E-Mail-Benachrichtigung</h3>
                                <p class="mt-1 text-sm text-gray-600">
                                    Sie erhalten eine E-Mail mit Ihren Zugangsdaten und weiteren Informationen.
                                </p>
                            </div>
                        </li>

                        <li class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center h-8 w-8 rounded-full bg-blue-100 text-blue-600 font-bold">
                                    3
                                </div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-base font-medium text-gray-900">Los geht's!</h3>
                                <p class="mt-1 text-sm text-gray-600">
                                    Nach der Freischaltung können Sie sich einloggen und alle Funktionen nutzen.
                                </p>
                            </div>
                        </li>
                    </ol>
                </div>
            </div>

            <!-- Additional Information -->
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-r-lg mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Wichtiger Hinweis</h3>
                        <div class="mt-2 text-sm text-blue-700 space-y-2">
                            <p>
                                Die Bearbeitung Ihrer Registrierung kann einige Tage dauern. Bitte haben Sie etwas Geduld.
                            </p>
                            <p>
                                Sie können dieses Fenster nun schließen.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="bg-white shadow-xl rounded-lg overflow-hidden">
                <div class="p-6 sm:p-8 text-center">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Fragen?</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Bei Fragen zur Registrierung können Sie sich direkt an den Club wenden.
                    </p>
                    <div class="flex justify-center gap-4">
                        <a
                            href="/impressum"
                            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            Impressum
                        </a>
                        <a
                            href="/datenschutz"
                            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            Datenschutz
                        </a>
                    </div>
                </div>
            </div>

            <!-- Decorative Elements -->
            <div class="mt-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                </svg>
                <p class="mt-4 text-sm text-gray-500">
                    Wir freuen uns darauf, Sie bald im Team begrüßen zu dürfen!
                </p>
            </div>
        </div>
    </PublicLayout>
</template>

<script>
import PublicLayout from '@/Layouts/PublicLayout.vue';

export default {
    components: {
        PublicLayout,
    },
};
</script>
