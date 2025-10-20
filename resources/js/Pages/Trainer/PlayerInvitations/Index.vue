<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    invitations: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const search = ref(props.filters.search || '');
const filter = ref(props.filters.filter || 'all');

// Compute status badge classes
const getStatusBadge = (invitation) => {
    const now = new Date();
    const expiresAt = new Date(invitation.expires_at);

    if (expiresAt < now) {
        return {
            text: 'Abgelaufen',
            classes: 'bg-red-100 text-red-800',
        };
    }

    if (invitation.max_registrations && invitation.registered_players_count >= invitation.max_registrations) {
        return {
            text: 'Voll',
            classes: 'bg-yellow-100 text-yellow-800',
        };
    }

    return {
        text: 'Aktiv',
        classes: 'bg-green-100 text-green-800',
    };
};

// Handle search
const handleSearch = () => {
    router.get(route('trainer.invitations.index'), {
        search: search.value || undefined,
        filter: filter.value !== 'all' ? filter.value : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Handle filter change
const handleFilterChange = () => {
    router.get(route('trainer.invitations.index'), {
        search: search.value || undefined,
        filter: filter.value !== 'all' ? filter.value : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Handle deactivate
const handleDeactivate = (invitation) => {
    if (!confirm(`Möchten Sie die Einladung "${invitation.invitation_token}" wirklich deaktivieren?`)) {
        return;
    }

    router.delete(route('trainer.invitations.destroy', invitation.id), {
        preserveScroll: true,
    });
};

// Format date
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('de-DE', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

// Compute remaining time for active invitations
const getRemainingTime = (expiresAt) => {
    const now = new Date();
    const expires = new Date(expiresAt);
    const diffMs = expires - now;

    if (diffMs <= 0) return 'Abgelaufen';

    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
    const diffHours = Math.floor((diffMs % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));

    if (diffDays > 0) {
        return `${diffDays} Tag${diffDays > 1 ? 'e' : ''}`;
    }

    if (diffHours > 0) {
        return `${diffHours} Stunde${diffHours > 1 ? 'n' : ''}`;
    }

    return 'Bald abgelaufen';
};
</script>

<template>
    <AppLayout title="Spieler-Einladungen">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Spieler-Einladungen
                </h2>
                <Link
                    :href="route('trainer.invitations.create')"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Neue Einladung
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Filters and Search -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mb-6 p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Search -->
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">
                                Suchen
                            </label>
                            <div class="flex gap-2">
                                <input
                                    id="search"
                                    v-model="search"
                                    type="text"
                                    placeholder="Token oder Team suchen..."
                                    class="flex-1 px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                    @keyup.enter="handleSearch"
                                />
                                <button
                                    @click="handleSearch"
                                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Filter -->
                        <div>
                            <label for="filter" class="block text-sm font-medium text-gray-700 mb-1">
                                Status
                            </label>
                            <select
                                id="filter"
                                v-model="filter"
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                @change="handleFilterChange"
                            >
                                <option value="all">Alle</option>
                                <option value="active">Nur Aktive</option>
                                <option value="expired">Nur Abgelaufene</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Invitations List -->
                <div v-if="invitations.data.length > 0" class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="divide-y divide-gray-200">
                        <div
                            v-for="invitation in invitations.data"
                            :key="invitation.id"
                            class="p-6 hover:bg-gray-50 transition-colors"
                        >
                            <div class="flex items-start justify-between">
                                <!-- Left Section: Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-3 mb-2">
                                        <h3 class="text-lg font-semibold text-gray-900">
                                            {{ invitation.target_team?.name || invitation.club?.name || 'Allgemein' }}
                                        </h3>
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                            :class="getStatusBadge(invitation).classes"
                                        >
                                            {{ getStatusBadge(invitation).text }}
                                        </span>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-3">
                                        <!-- Token -->
                                        <div class="flex items-center gap-2 text-sm text-gray-600">
                                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                            </svg>
                                            <span class="font-mono">{{ invitation.invitation_token.substring(0, 12) }}...</span>
                                        </div>

                                        <!-- Registrations -->
                                        <div class="flex items-center gap-2 text-sm text-gray-600">
                                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                            <span>
                                                {{ invitation.registered_players_count || 0 }}
                                                <template v-if="invitation.max_registrations">
                                                    / {{ invitation.max_registrations }}
                                                </template>
                                                Registrierungen
                                            </span>
                                        </div>

                                        <!-- Expiry -->
                                        <div class="flex items-center gap-2 text-sm text-gray-600">
                                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span>{{ getRemainingTime(invitation.expires_at) }}</span>
                                        </div>

                                        <!-- Created -->
                                        <div class="flex items-center gap-2 text-sm text-gray-600">
                                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <span>{{ formatDate(invitation.created_at) }}</span>
                                        </div>
                                    </div>

                                    <!-- Creator Info -->
                                    <div v-if="invitation.creator" class="text-xs text-gray-500">
                                        Erstellt von: {{ invitation.creator.name }}
                                    </div>
                                </div>

                                <!-- Right Section: Actions -->
                                <div class="flex flex-col gap-2 ml-4">
                                    <Link
                                        :href="route('trainer.invitations.show', invitation.id)"
                                        class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Anzeigen
                                    </Link>
                                    <button
                                        v-if="getStatusBadge(invitation).text === 'Aktiv'"
                                        @click="handleDeactivate(invitation)"
                                        class="inline-flex items-center justify-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        Deaktivieren
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="invitations.links.length > 3" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <Link
                                    v-if="invitations.prev_page_url"
                                    :href="invitations.prev_page_url"
                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    Zurück
                                </Link>
                                <Link
                                    v-if="invitations.next_page_url"
                                    :href="invitations.next_page_url"
                                    class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    Weiter
                                </Link>
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Zeige
                                        <span class="font-medium">{{ invitations.from }}</span>
                                        bis
                                        <span class="font-medium">{{ invitations.to }}</span>
                                        von
                                        <span class="font-medium">{{ invitations.total }}</span>
                                        Einladungen
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                        <Link
                                            v-for="(link, index) in invitations.links"
                                            :key="index"
                                            :href="link.url"
                                            v-html="link.label"
                                            :class="[
                                                'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                                                link.active
                                                    ? 'z-10 bg-blue-50 border-blue-500 text-blue-600'
                                                    : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                                index === 0 ? 'rounded-l-md' : '',
                                                index === invitations.links.length - 1 ? 'rounded-r-md' : '',
                                            ]"
                                            :preserve-scroll="true"
                                        />
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Keine Einladungen</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ filters.search || filters.filter ? 'Keine Einladungen gefunden.' : 'Erstellen Sie Ihre erste Spieler-Einladung.' }}
                    </p>
                    <div class="mt-6">
                        <Link
                            :href="route('trainer.invitations.create')"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Neue Einladung erstellen
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
