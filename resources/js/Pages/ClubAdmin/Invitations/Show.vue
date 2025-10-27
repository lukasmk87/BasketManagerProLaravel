<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import ClubAdminLayout from '@/Layouts/ClubAdminLayout.vue';

const props = defineProps({
    invitation: {
        type: Object,
        required: true,
    },
    statistics: {
        type: Object,
        required: true,
    },
});

const copied = ref(false);

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('de-DE', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const copyToClipboard = async (text) => {
    try {
        await navigator.clipboard.writeText(text);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch (err) {
        alert('Fehler beim Kopieren: ' + err);
    }
};

const downloadQR = (format) => {
    window.location.href = route('club-admin.invitations.download-qr', {
        invitation: props.invitation.id,
        format: format,
    });
};

const getRoleName = (role) => {
    const roles = {
        'member': 'Mitglied',
        'player': 'Spieler',
        'parent': 'Elternteil',
        'volunteer': 'Freiwilliger',
        'sponsor': 'Sponsor',
    };
    return roles[role] || role;
};
</script>

<template>
    <ClubAdminLayout title="Einladungsdetails">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Einladungsdetails
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        {{ invitation.club.name }}
                    </p>
                </div>
                <Link
                    :href="route('club-admin.invitations.index')"
                    class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 transition"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Zurück
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Total Uses -->
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-blue-100 rounded-md p-3">
                                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Registrierungen</p>
                                <p class="text-2xl font-semibold text-gray-900">
                                    {{ statistics.total_uses }} / {{ invitation.max_uses }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Remaining -->
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
                                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Verbleibend</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ statistics.remaining }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Days Remaining -->
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-yellow-100 rounded-md p-3">
                                <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Tage bis Ablauf</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ statistics.days_until_expiry }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Progress -->
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-purple-100 rounded-md p-3">
                                <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Fortschritt</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ statistics.progress_percentage }}%</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- QR Code Card -->
                    <div class="bg-white shadow-lg rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">QR-Code</h3>

                        <div v-if="invitation.qr_code_url" class="space-y-4">
                            <!-- QR Code Display -->
                            <div class="flex justify-center bg-gray-50 p-8 rounded-lg">
                                <img
                                    :src="invitation.qr_code_url"
                                    alt="QR Code"
                                    class="max-w-xs w-full h-auto"
                                />
                            </div>

                            <!-- Download Buttons -->
                            <div class="flex flex-col sm:flex-row gap-2">
                                <button
                                    @click="downloadQR('png')"
                                    class="flex-1 inline-flex justify-center items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 transition"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    PNG Download
                                </button>
                                <button
                                    @click="downloadQR('svg')"
                                    class="flex-1 inline-flex justify-center items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    SVG Download
                                </button>
                            </div>

                            <p class="text-xs text-gray-500 text-center">
                                QR-Code scannen, um zur Registrierungsseite zu gelangen
                            </p>
                        </div>

                        <div v-else class="text-center py-8 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="mt-2">QR-Code nicht verfügbar</p>
                        </div>
                    </div>

                    <!-- Invitation Details Card -->
                    <div class="bg-white shadow-lg rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Einladungsdetails</h3>

                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Token</dt>
                                <dd class="mt-1 text-sm text-gray-900 font-mono bg-gray-50 p-2 rounded break-all">
                                    {{ invitation.invitation_token }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500">Registrierungslink</dt>
                                <dd class="mt-1">
                                    <div class="flex items-center space-x-2">
                                        <input
                                            type="text"
                                            readonly
                                            :value="invitation.registration_url"
                                            class="flex-1 text-sm text-gray-900 bg-gray-50 border-gray-300 rounded px-3 py-2"
                                        />
                                        <button
                                            @click="copyToClipboard(invitation.registration_url)"
                                            class="inline-flex items-center px-3 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 transition"
                                        >
                                            <svg v-if="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>
                                    </div>
                                    <p v-if="copied" class="mt-1 text-xs text-green-600">Link kopiert!</p>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500">Standardrolle</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ getRoleName(invitation.default_role) }}
                                    </span>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500">Erstellt von</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ invitation.creator.name }}</dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500">Erstellt am</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ formatDate(invitation.created_at) }}</dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500">Gültig bis</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ formatDate(invitation.expires_at) }}</dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500">Status</dt>
                                <dd class="mt-1">
                                    <span
                                        v-if="statistics.is_active && !statistics.is_expired"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                                    >
                                        Aktiv
                                    </span>
                                    <span
                                        v-else-if="statistics.is_expired"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"
                                    >
                                        Abgelaufen
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800"
                                    >
                                        Inaktiv
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Usage Instructions -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3 flex-1">
                            <h3 class="text-sm font-medium text-blue-800">
                                Verwendung der Einladung
                            </h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <ul class="list-disc list-inside space-y-1">
                                    <li>Teilen Sie den Registrierungslink per E-Mail oder in sozialen Medien</li>
                                    <li>Drucken Sie den QR-Code aus und hängen Sie ihn im Clubhaus auf</li>
                                    <li>Neue Mitglieder können sich selbstständig registrieren</li>
                                    <li>Jede Registrierung zählt als eine Nutzung</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ClubAdminLayout>
</template>
