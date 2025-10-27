<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import ClubAdminLayout from '@/Layouts/ClubAdminLayout.vue';

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

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('de-DE', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getDaysRemaining = (expiresAt) => {
    const now = new Date();
    const expiry = new Date(expiresAt);
    const diff = Math.ceil((expiry - now) / (1000 * 60 * 60 * 24));
    return diff;
};

const getStatusBadge = (invitation) => {
    const now = new Date();
    const expiresAt = new Date(invitation.expires_at);

    if (!invitation.is_active) {
        return { text: 'Deaktiviert', class: 'bg-gray-100 text-gray-800' };
    }

    if (expiresAt < now) {
        return { text: 'Abgelaufen', class: 'bg-red-100 text-red-800' };
    }

    if (invitation.current_uses >= invitation.max_uses) {
        return { text: 'Limit erreicht', class: 'bg-orange-100 text-orange-800' };
    }

    const daysRemaining = getDaysRemaining(expiresAt);
    if (daysRemaining <= 3) {
        return { text: 'Läuft bald ab', class: 'bg-yellow-100 text-yellow-800' };
    }

    return { text: 'Aktiv', class: 'bg-green-100 text-green-800' };
};

const getRoleBadgeColor = (role) => {
    const colors = {
        'member': 'bg-gray-100 text-gray-800',
        'player': 'bg-purple-100 text-purple-800',
        'parent': 'bg-blue-100 text-blue-800',
        'volunteer': 'bg-green-100 text-green-800',
        'sponsor': 'bg-yellow-100 text-yellow-800',
    };
    return colors[role] || 'bg-gray-100 text-gray-800';
};

const applyFilters = () => {
    router.get(route('club-admin.invitations.index'), {
        search: search.value,
        filter: filter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    search.value = '';
    filter.value = 'all';
    applyFilters();
};

const deleteInvitation = (invitationId) => {
    if (confirm('Möchten Sie diese Einladung wirklich deaktivieren?')) {
        router.delete(route('club-admin.invitations.destroy', invitationId), {
            preserveScroll: true,
        });
    }
};

const getProgressPercentage = (invitation) => {
    return Math.round((invitation.current_uses / invitation.max_uses) * 100);
};
</script>

<template>
    <ClubAdminLayout title="Einladungsverwaltung">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Club-Einladungen
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Verwalten Sie Einladungslinks für neue Mitglieder
                    </p>
                </div>
                <Link
                    :href="route('club-admin.invitations.create')"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
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
                <!-- Filters -->
                <div class="bg-white shadow-sm rounded-lg mb-6 p-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Search -->
                        <div class="md:col-span-2">
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">
                                Suche
                            </label>
                            <input
                                v-model="search"
                                type="text"
                                id="search"
                                placeholder="Nach Token oder Club suchen..."
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                @keyup.enter="applyFilters"
                            />
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label for="filter" class="block text-sm font-medium text-gray-700 mb-1">
                                Status
                            </label>
                            <select
                                v-model="filter"
                                id="filter"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                @change="applyFilters"
                            >
                                <option value="all">Alle</option>
                                <option value="active">Aktiv</option>
                                <option value="expired">Abgelaufen</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 flex gap-2">
                        <button
                            @click="applyFilters"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 transition"
                        >
                            Filter anwenden
                        </button>
                        <button
                            @click="resetFilters"
                            class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 transition"
                        >
                            Zurücksetzen
                        </button>
                    </div>
                </div>

                <!-- Invitations List -->
                <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Token / Club
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Rolle
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Nutzung
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Gültig bis
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Aktionen
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="invitation in invitations.data" :key="invitation.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 font-mono">
                                                {{ invitation.invitation_token.substring(0, 12) }}...
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ invitation.club.name }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="[getRoleBadgeColor(invitation.default_role), 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium']">
                                            {{ invitation.default_role }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">
                                            {{ invitation.current_uses }} / {{ invitation.max_uses }}
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                            <div
                                                class="bg-blue-600 h-2 rounded-full"
                                                :style="{ width: getProgressPercentage(invitation) + '%' }"
                                            ></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatDate(invitation.expires_at) }}
                                        <div v-if="getDaysRemaining(invitation.expires_at) > 0" class="text-xs text-gray-400">
                                            ({{ getDaysRemaining(invitation.expires_at) }} Tage)
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            :class="[getStatusBadge(invitation).class, 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium']"
                                        >
                                            {{ getStatusBadge(invitation).text }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                        <Link
                                            :href="route('club-admin.invitations.show', invitation.id)"
                                            class="text-blue-600 hover:text-blue-900"
                                        >
                                            Details
                                        </Link>
                                        <button
                                            v-if="invitation.is_active"
                                            @click="deleteInvitation(invitation.id)"
                                            class="text-red-600 hover:text-red-900"
                                        >
                                            Deaktivieren
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty State -->
                    <div v-if="invitations.data.length === 0" class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Keine Einladungen</h3>
                        <p class="mt-1 text-sm text-gray-500">Erstellen Sie eine neue Einladung, um Mitglieder einzuladen.</p>
                        <div class="mt-6">
                            <Link
                                :href="route('club-admin.invitations.create')"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 transition"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Erste Einladung erstellen
                            </Link>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="invitations.data.length > 0" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
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
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                        <Link
                                            v-if="invitations.prev_page_url"
                                            :href="invitations.prev_page_url"
                                            class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                                        >
                                            Zurück
                                        </Link>
                                        <Link
                                            v-if="invitations.next_page_url"
                                            :href="invitations.next_page_url"
                                            class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                                        >
                                            Weiter
                                        </Link>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ClubAdminLayout>
</template>
