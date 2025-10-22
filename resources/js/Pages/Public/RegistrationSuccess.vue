<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineProps({
    clubName: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <PublicLayout title="Registrierung erfolgreich">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
                <!-- Success Icon -->
                <div class="flex justify-center mb-6">
                    <div class="h-16 w-16 rounded-full bg-green-100 flex items-center justify-center">
                        <svg class="h-10 w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </div>

                <!-- Success Title -->
                <h1 class="text-3xl font-bold text-gray-900 text-center mb-4">
                    Vielen Dank für Ihre Registrierung!
                </h1>

                <!-- Success Message -->
                <p class="text-center text-lg text-gray-600 mb-8">
                    Ihre Anmeldung bei <span class="font-semibold">{{ clubName }}</span> wurde erfolgreich eingereicht.
                </p>

                <!-- Next Steps -->
                <div class="bg-blue-50 border border-blue-200 rounded-md p-6 mb-6">
                    <h2 class="text-lg font-semibold text-blue-900 mb-4 flex items-center">
                        <svg class="h-6 w-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                        Was passiert als Nächstes?
                    </h2>

                    <div class="space-y-4">
                        <!-- Step 1 -->
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="h-8 w-8 rounded-full bg-blue-600 flex items-center justify-center text-white font-semibold text-sm">
                                    1
                                </div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-blue-900">
                                    Prüfung durch den Club-Administrator
                                </h3>
                                <p class="text-sm text-blue-700 mt-1">
                                    Ihr Profil wird vom Club-Administrator geprüft und einem Team zugewiesen.
                                </p>
                            </div>
                        </div>

                        <!-- Step 2 -->
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="h-8 w-8 rounded-full bg-blue-600 flex items-center justify-center text-white font-semibold text-sm">
                                    2
                                </div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-blue-900">
                                    E-Mail mit Zugangsdaten
                                </h3>
                                <p class="text-sm text-blue-700 mt-1">
                                    Sie erhalten eine E-Mail mit Ihren Zugangsdaten und weiteren Informationen.
                                </p>
                            </div>
                        </div>

                        <!-- Step 3 -->
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="h-8 w-8 rounded-full bg-blue-600 flex items-center justify-center text-white font-semibold text-sm">
                                    3
                                </div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-blue-900">
                                    Los geht's!
                                </h3>
                                <p class="text-sm text-blue-700 mt-1">
                                    Nach der Freischaltung können Sie sich einloggen und alle Funktionen nutzen.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Important Info -->
                <div class="bg-amber-50 border border-amber-200 rounded-md p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-amber-800 mb-1">
                                Wichtige Hinweise:
                            </h3>
                            <ul class="text-sm text-amber-700 list-disc list-inside space-y-1">
                                <li>
                                    Überprüfen Sie auch Ihren Spam-Ordner auf die Bestätigungs-E-Mail
                                </li>
                                <li>
                                    Die Bearbeitung kann je nach Verein 1-3 Werktage dauern
                                </li>
                                <li>
                                    Sie können diese Seite jetzt schließen
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Contact Info -->
                <div class="border-t border-gray-200 pt-6 text-center">
                    <p class="text-sm text-gray-600 mb-4">
                        Haben Sie Fragen oder Probleme? Kontaktieren Sie den Verein direkt.
                    </p>
                    <a
                        href="/"
                        class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        Zur Startseite
                    </a>
                </div>
            </div>

            <!-- Additional Info Card -->
            <div class="mt-6 bg-gradient-to-r from-indigo-50 to-blue-50 rounded-lg border border-indigo-200 p-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-indigo-900 mb-2">
                            Tipp: Bereiten Sie sich vor!
                        </h3>
                        <p class="text-sm text-indigo-700">
                            Während Sie auf die Freischaltung warten, können Sie bereits Ihre Basketball-Ausrüstung checken
                            und sich auf das erste Training freuen. Willkommen im Team!
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
