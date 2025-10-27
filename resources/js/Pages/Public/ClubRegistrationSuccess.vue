<script setup>
defineProps({
    clubName: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-green-50 to-blue-100 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full">
            <!-- Success Card -->
            <div class="bg-white shadow-xl rounded-lg overflow-hidden">
                <!-- Success Icon -->
                <div class="pt-10 pb-6 px-6 text-center">
                    <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-green-100">
                        <svg class="h-12 w-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>

                    <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                        Erfolgreich registriert!
                    </h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Willkommen bei {{ clubName }}
                    </p>
                </div>

                <!-- Content -->
                <div class="px-6 pb-8">
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-green-800">
                                    Was passiert jetzt?
                                </h3>
                                <div class="mt-2 text-sm text-green-700">
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>Ihr Account wurde erfolgreich erstellt</li>
                                        <li>Sie können sich jetzt anmelden</li>
                                        <li>Überprüfen Sie Ihre E-Mails für weitere Informationen</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Next Steps -->
                    <div class="mt-6 space-y-4">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-blue-800">
                                        Nächste Schritte
                                    </h3>
                                    <div class="mt-2 text-sm text-blue-700">
                                        <ol class="list-decimal list-inside space-y-1">
                                            <li>Melden Sie sich mit Ihren Zugangsdaten an</li>
                                            <li>Vervollständigen Sie Ihr Profil</li>
                                            <li>Erkunden Sie die Club-Features</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-8 space-y-3">
                        <a
                            :href="route('login')"
                            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out"
                        >
                            Zur Anmeldung
                        </a>
                        <a
                            href="/"
                            class="w-full flex justify-center py-3 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out"
                        >
                            Zur Startseite
                        </a>
                    </div>
                </div>
            </div>

            <!-- Contact Info -->
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Haben Sie Fragen? Kontaktieren Sie den Club-Administrator.
                </p>
            </div>

            <!-- Footer -->
            <div class="mt-8 text-center text-xs text-gray-500">
                <p>© {{ new Date().getFullYear() }} {{ clubName }}. Alle Rechte vorbehalten.</p>
            </div>
        </div>
    </div>
</template>
