<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineProps({
    error: {
        type: String,
        required: true,
    },
});

const errorDetails = {
    'invalid_token': {
        title: 'Ungültiger Registrierungslink',
        icon: 'exclamation',
        color: 'red',
        message: 'Der verwendete Registrierungslink ist ungültig. Bitte überprüfen Sie den Link oder kontaktieren Sie den Verein.',
    },
    'expired': {
        title: 'Registrierung abgelaufen',
        icon: 'clock',
        color: 'yellow',
        message: 'Diese Registrierungseinladung ist leider abgelaufen. Bitte kontaktieren Sie den Verein für eine neue Einladung.',
    },
    'limit_reached': {
        title: 'Maximale Anzahl erreicht',
        icon: 'users',
        color: 'orange',
        message: 'Für diese Einladung sind bereits alle verfügbaren Plätze belegt. Bitte kontaktieren Sie den Verein für weitere Informationen.',
    },
    'default': {
        title: 'Registrierung nicht möglich',
        icon: 'exclamation',
        color: 'red',
        message: 'Die Registrierung kann derzeit nicht durchgeführt werden. Bitte versuchen Sie es später erneut oder kontaktieren Sie den Verein.',
    },
};
</script>

<template>
    <PublicLayout title="Registrierung nicht möglich">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
                <!-- Error Icon -->
                <div class="flex justify-center mb-6">
                    <div
                        :class="[
                            'h-16 w-16 rounded-full flex items-center justify-center',
                            error === 'expired' ? 'bg-yellow-100' : error === 'limit_reached' ? 'bg-orange-100' : 'bg-red-100'
                        ]"
                    >
                        <!-- Exclamation Icon -->
                        <svg
                            v-if="errorDetails[error]?.icon === 'exclamation' || !errorDetails[error]"
                            class="h-10 w-10"
                            :class="error === 'expired' ? 'text-yellow-600' : error === 'limit_reached' ? 'text-orange-600' : 'text-red-600'"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                            />
                        </svg>

                        <!-- Clock Icon -->
                        <svg
                            v-else-if="errorDetails[error]?.icon === 'clock'"
                            class="h-10 w-10 text-yellow-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>

                        <!-- Users Icon -->
                        <svg
                            v-else-if="errorDetails[error]?.icon === 'users'"
                            class="h-10 w-10 text-orange-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                            />
                        </svg>
                    </div>
                </div>

                <!-- Error Title -->
                <h1 class="text-2xl font-bold text-gray-900 text-center mb-4">
                    {{ errorDetails[error]?.title || errorDetails.default.title }}
                </h1>

                <!-- Error Message -->
                <p class="text-center text-gray-600 mb-8">
                    {{ errorDetails[error]?.message || errorDetails.default.message }}
                </p>

                <!-- Help Box -->
                <div class="bg-blue-50 border border-blue-200 rounded-md p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800 mb-1">
                                Was Sie tun können:
                            </h3>
                            <ul class="text-sm text-blue-700 list-disc list-inside space-y-1">
                                <li v-if="error === 'invalid_token'">
                                    Überprüfen Sie, ob Sie den vollständigen Link verwendet haben
                                </li>
                                <li v-if="error === 'expired' || error === 'limit_reached'">
                                    Kontaktieren Sie den Verein für eine neue Einladung
                                </li>
                                <li>
                                    Wenden Sie sich an den Club-Administrator
                                </li>
                                <li>
                                    Überprüfen Sie Ihre E-Mails auf eine aktualisierte Einladung
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Back to Homepage -->
                <div class="text-center">
                    <a
                        href="/"
                        class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Zur Startseite
                    </a>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
