<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InvitationQRCode from '@/Components/PlayerRegistration/InvitationQRCode.vue';

const props = defineProps({
    invitation: {
        type: Object,
        required: true,
    },
    statistics: {
        type: Object,
        required: true,
    },
    registeredPlayers: {
        type: Array,
        default: () => [],
    },
});

// Compute status
const invitationStatus = computed(() => {
    const now = new Date();
    const expiresAt = new Date(props.invitation.expires_at);

    if (expiresAt < now) {
        return {
            text: 'Abgelaufen',
            class: 'bg-red-100 text-red-800',
            icon: 'M6 18L18 6M6 6l12 12',
        };
    }

    if (props.invitation.max_registrations && props.statistics.registered_count >= props.invitation.max_registrations) {
        return {
            text: 'Voll',
            class: 'bg-yellow-100 text-yellow-800',
            icon: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        };
    }

    return {
        text: 'Aktiv',
        class: 'bg-green-100 text-green-800',
        icon: 'M5 13l4 4L19 7',
    };
});

// Format date
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('de-DE', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Format player age
const calculateAge = (birthDate) => {
    if (!birthDate) return null;
    const birth = new Date(birthDate);
    const today = new Date();
    let age = today.getFullYear() - birth.getFullYear();
    const monthDiff = today.getMonth() - birth.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
        age--;
    }
    return age;
};
</script>

<template>
    <AppLayout :title="`Einladung: ${invitation.invitation_token}`">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('trainer.invitations.index')"
                        class="text-gray-500 hover:text-gray-700"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                            {{ invitation.target_team?.name || invitation.club?.name || 'Einladung' }}
                        </h2>
                        <p class="text-sm text-gray-500">
                            Token: {{ invitation.invitation_token }}
                        </p>
                    </div>
                </div>
                <span
                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                    :class="invitationStatus.class"
                >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="invitationStatus.icon" />
                    </svg>
                    {{ invitationStatus.text }}
                </span>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- QR Code Section -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- QR Code -->
                    <div class="lg:col-span-1">
                        <InvitationQRCode
                            :invitation="invitation"
                            size="large"
                            :show-url="true"
                            :show-downloads="true"
                            :show-stats="true"
                        />
                    </div>

                    <!-- Info & Statistics -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Invitation Details -->
                        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Details</h3>
                            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Club</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ invitation.club?.name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Ziel-Team</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        {{ invitation.target_team?.name || 'Allgemein (kein spezifisches Team)' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Erstellt am</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ formatDate(invitation.created_at) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Läuft ab am</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ formatDate(invitation.expires_at) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Erstellt von</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ invitation.creator?.name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Maximale Registrierungen</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        {{ invitation.max_registrations || 'Unbegrenzt' }}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Statistics Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <!-- Total Registrations -->
                            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-500">Registrierungen</p>
                                        <p class="text-2xl font-semibold text-gray-900">{{ statistics.registered_count }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Pending Assignment -->
                            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-500">Ausstehend</p>
                                        <p class="text-2xl font-semibold text-gray-900">{{ statistics.pending_assignment }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Assigned -->
                            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-500">Zugewiesen</p>
                                        <p class="text-2xl font-semibold text-gray-900">{{ statistics.assigned_count }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Registered Players List -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">
                            Registrierte Spieler ({{ registeredPlayers.length }})
                        </h3>
                    </div>

                    <div v-if="registeredPlayers.length > 0" class="divide-y divide-gray-200">
                        <div
                            v-for="player in registeredPlayers"
                            :key="player.id"
                            class="p-6 hover:bg-gray-50 transition-colors"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex items-start gap-4 flex-1">
                                    <!-- Avatar -->
                                    <div
                                        class="flex-shrink-0 w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-bold text-lg"
                                    >
                                        {{ player.user?.name?.charAt(0) || '?' }}
                                    </div>

                                    <!-- Player Info -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1">
                                            <h4 class="text-base font-semibold text-gray-900">
                                                {{ player.user?.name }}
                                            </h4>
                                            <span
                                                v-if="player.pending_team_assignment"
                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"
                                            >
                                                Ausstehend
                                            </span>
                                            <span
                                                v-else
                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                                            >
                                                Zugewiesen
                                            </span>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 text-sm text-gray-600">
                                            <div v-if="player.user?.email" class="flex items-center gap-1">
                                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                </svg>
                                                <span class="truncate">{{ player.user.email }}</span>
                                            </div>
                                            <div v-if="player.birth_date" class="flex items-center gap-1">
                                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                {{ calculateAge(player.birth_date) }} Jahre
                                            </div>
                                            <div v-if="player.position" class="flex items-center gap-1">
                                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                                {{ player.position }}
                                            </div>
                                            <div v-if="player.height" class="flex items-center gap-1">
                                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                                </svg>
                                                {{ player.height }} cm
                                            </div>
                                            <div v-if="player.registration_completed_at" class="flex items-center gap-1">
                                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                {{ new Date(player.registration_completed_at).toLocaleDateString('de-DE') }}
                                            </div>
                                            <div v-if="!player.pending_team_assignment && player.team" class="flex items-center gap-1">
                                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
                                                <span class="font-medium text-blue-600">{{ player.team.name }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="p-12 text-center text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <p class="mt-2 text-sm">Noch keine Registrierungen</p>
                        <p class="mt-1 text-xs">Teilen Sie den QR-Code, um Spieler einzuladen.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
