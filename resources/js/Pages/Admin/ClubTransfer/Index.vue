<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import TextInput from '@/Components/TextInput.vue';
import TransferStatusBadge from './Partials/TransferStatusBadge.vue';
import PreviewTransferModal from './Partials/PreviewTransferModal.vue';

const props = defineProps({
    transfers: {
        type: Object,
        required: true,
    },
    clubs: {
        type: Array,
        default: () => [],
    },
    clubPlans: {
        type: Object,
        default: () => ({}),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    tenants: {
        type: Array,
        default: () => [],
    },
});

// Tab state
const activeTab = ref('clubs');

// Transfer modal state
const showTransferModal = ref(false);
const selectedClub = ref(null);

// Filter state
const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || 'all');
const sourceTenantId = ref(props.filters?.source_tenant_id || '');
const targetTenantId = ref(props.filters?.target_tenant_id || '');

// Club search
const clubSearch = ref('');

// Filtered clubs
const filteredClubs = computed(() => {
    if (!clubSearch.value) return props.clubs;
    const searchLower = clubSearch.value.toLowerCase();
    return props.clubs.filter(club =>
        club.name.toLowerCase().includes(searchLower) ||
        club.tenant?.name?.toLowerCase().includes(searchLower)
    );
});

// Watch filters and update URL
watch([search, status, sourceTenantId, targetTenantId], ([newSearch, newStatus, newSource, newTarget]) => {
    router.get(route('admin.club-transfers.index'), {
        search: newSearch || undefined,
        status: newStatus !== 'all' ? newStatus : undefined,
        source_tenant_id: newSource || undefined,
        target_tenant_id: newTarget || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, { debounce: 300 });

const clearFilters = () => {
    search.value = '';
    status.value = 'all';
    sourceTenantId.value = '';
    targetTenantId.value = '';
};

// Statistics
const stats = computed(() => ({
    total: props.transfers.total || 0,
    active: props.transfers.data.filter(t => ['pending', 'processing'].includes(t.status)).length,
    completed: props.transfers.data.filter(t => t.status === 'completed').length,
    failed: props.transfers.data.filter(t => t.status === 'failed').length,
}));

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Intl.DateTimeFormat('de-DE', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(dateString));
};

const openTransferModal = (club) => {
    selectedClub.value = club;
    showTransferModal.value = true;
};

const closeTransferModal = () => {
    showTransferModal.value = false;
    selectedClub.value = null;
};

const onTransferred = () => {
    router.reload();
};

// Plan management
const updatingPlanForClub = ref(null);

const getPlansForTenant = (tenantId) => {
    return props.clubPlans[tenantId] || [];
};

const updateClubPlan = async (club, planId) => {
    updatingPlanForClub.value = club.id;
    try {
        await axios.put(route('admin.clubs.plan.update', club.id), {
            club_subscription_plan_id: planId || null,
        });
        router.reload({ only: ['clubs'] });
    } catch (error) {
        console.error('Failed to update plan:', error);
        alert('Fehler beim Aktualisieren des Plans: ' + (error.response?.data?.message || error.message));
    } finally {
        updatingPlanForClub.value = null;
    }
};
</script>

<template>
    <AdminLayout title="Club-Transfers">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Club-Transfers
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Verwaltung aller Club-Transfers zwischen Tenants
                    </p>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

                <!-- Tabs -->
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8">
                        <button
                            @click="activeTab = 'clubs'"
                            :class="[
                                'py-4 px-1 border-b-2 font-medium text-sm transition-colors',
                                activeTab === 'clubs'
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            ]"
                        >
                            <svg class="inline-block w-5 h-5 mr-2 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            Clubs ({{ clubs.length }})
                        </button>
                        <button
                            @click="activeTab = 'history'"
                            :class="[
                                'py-4 px-1 border-b-2 font-medium text-sm transition-colors',
                                activeTab === 'history'
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            ]"
                        >
                            <svg class="inline-block w-5 h-5 mr-2 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Transfer-Historie ({{ stats.total }})
                        </button>
                    </nav>
                </div>

                <!-- Clubs Tab -->
                <div v-if="activeTab === 'clubs'" class="space-y-6">
                    <!-- Club Search -->
                    <div class="bg-white shadow rounded-lg p-6">
                        <div class="max-w-md">
                            <label for="club-search" class="block text-sm font-medium text-gray-700 mb-1">
                                Club suchen
                            </label>
                            <TextInput
                                id="club-search"
                                v-model="clubSearch"
                                type="text"
                                class="w-full"
                                placeholder="Club-Name oder Tenant suchen..."
                            />
                        </div>
                    </div>

                    <!-- Clubs Table -->
                    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Club
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Aktueller Tenant
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Teams
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Benutzer
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Plan
                                        </th>
                                        <th scope="col" class="relative px-6 py-3">
                                            <span class="sr-only">Aktionen</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="club in filteredClubs" :key="club.id" class="hover:bg-gray-50">
                                        <!-- Club Name -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10 bg-indigo-100 rounded-full flex items-center justify-center">
                                                    <span class="text-indigo-600 font-semibold text-sm">
                                                        {{ club.name.charAt(0).toUpperCase() }}
                                                    </span>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {{ club.name }}
                                                    </div>
                                                    <div v-if="club.short_name" class="text-sm text-gray-500">
                                                        {{ club.short_name }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Current Tenant -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                {{ club.tenant?.name || 'Kein Tenant' }}
                                            </div>
                                        </td>

                                        <!-- Teams Count -->
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ club.teams_count || 0 }}
                                        </td>

                                        <!-- Users Count -->
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ club.users_count || 0 }}
                                        </td>

                                        <!-- Status -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                :class="[
                                                    'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                                                    club.is_active
                                                        ? 'bg-green-100 text-green-800'
                                                        : 'bg-gray-100 text-gray-800'
                                                ]"
                                            >
                                                {{ club.is_active ? 'Aktiv' : 'Inaktiv' }}
                                            </span>
                                        </td>

                                        <!-- Plan -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="relative">
                                                <select
                                                    :value="club.club_subscription_plan_id || ''"
                                                    @change="updateClubPlan(club, $event.target.value)"
                                                    :disabled="updatingPlanForClub === club.id || !club.tenant_id"
                                                    class="block w-full min-w-[150px] pl-3 pr-10 py-1.5 text-sm border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 rounded-md disabled:opacity-50 disabled:cursor-not-allowed"
                                                >
                                                    <option value="">Kein Plan</option>
                                                    <option
                                                        v-for="plan in getPlansForTenant(club.tenant_id)"
                                                        :key="plan.id"
                                                        :value="plan.id"
                                                    >
                                                        {{ plan.name }}
                                                    </option>
                                                </select>
                                                <!-- Loading indicator -->
                                                <div
                                                    v-if="updatingPlanForClub === club.id"
                                                    class="absolute inset-y-0 right-0 flex items-center pr-8 pointer-events-none"
                                                >
                                                    <svg class="animate-spin h-4 w-4 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            <!-- Current plan info -->
                                            <div v-if="club.subscription_plan" class="mt-1 text-xs text-gray-500">
                                                {{ club.subscription_plan.name }}
                                            </div>
                                        </td>

                                        <!-- Actions -->
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button
                                                @click="openTransferModal(club)"
                                                :disabled="!club.tenant"
                                                :class="[
                                                    'inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm',
                                                    club.tenant
                                                        ? 'text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500'
                                                        : 'text-gray-400 bg-gray-100 cursor-not-allowed'
                                                ]"
                                            >
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                                </svg>
                                                Transferieren
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Empty State -->
                        <div v-if="filteredClubs.length === 0" class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Keine Clubs gefunden</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                {{ clubSearch ? 'Keine Clubs entsprechen deiner Suche.' : 'Es wurden noch keine Clubs erstellt.' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- History Tab -->
                <div v-if="activeTab === 'history'" class="space-y-8">
                    <!-- Filters -->
                    <div class="bg-white shadow rounded-lg p-6">
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                            <!-- Search -->
                            <div>
                                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">
                                    Suche
                                </label>
                                <TextInput
                                    id="search"
                                    v-model="search"
                                    type="text"
                                    class="w-full"
                                    placeholder="Club-Name suchen..."
                                />
                            </div>

                            <!-- Status Filter -->
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                                    Status
                                </label>
                                <select
                                    id="status"
                                    v-model="status"
                                    class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                >
                                    <option value="all">Alle Status</option>
                                    <option value="pending">Ausstehend</option>
                                    <option value="processing">In Bearbeitung</option>
                                    <option value="completed">Abgeschlossen</option>
                                    <option value="failed">Fehlgeschlagen</option>
                                    <option value="rolled_back">Zurückgesetzt</option>
                                </select>
                            </div>

                            <!-- Source Tenant Filter -->
                            <div>
                                <label for="source-tenant" class="block text-sm font-medium text-gray-700 mb-1">
                                    Quell-Tenant
                                </label>
                                <select
                                    id="source-tenant"
                                    v-model="sourceTenantId"
                                    class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                >
                                    <option value="">Alle Tenants</option>
                                    <option v-for="tenant in tenants" :key="tenant.id" :value="tenant.id">
                                        {{ tenant.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Target Tenant Filter -->
                            <div>
                                <label for="target-tenant" class="block text-sm font-medium text-gray-700 mb-1">
                                    Ziel-Tenant
                                </label>
                                <select
                                    id="target-tenant"
                                    v-model="targetTenantId"
                                    class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                >
                                    <option value="">Alle Tenants</option>
                                    <option v-for="tenant in tenants" :key="tenant.id" :value="tenant.id">
                                        {{ tenant.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Clear Filters -->
                            <div class="flex items-end">
                                <button
                                    @click="clearFilters"
                                    type="button"
                                    class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                >
                                    Filter zurücksetzen
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-4">
                        <!-- Total Transfers -->
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Gesamt</dt>
                                            <dd class="text-lg font-semibold text-gray-900">{{ stats.total }}</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Active Transfers -->
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Aktiv</dt>
                                            <dd class="text-lg font-semibold text-blue-900">{{ stats.active }}</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Completed Transfers -->
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Erfolgreich</dt>
                                            <dd class="text-lg font-semibold text-green-900">{{ stats.completed }}</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Failed Transfers -->
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="h-6 w-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Fehlgeschlagen</dt>
                                            <dd class="text-lg font-semibold text-red-900">{{ stats.failed }}</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Club
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Transfer
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Initiiert von
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Datum
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Dauer
                                        </th>
                                        <th scope="col" class="relative px-6 py-3">
                                            <span class="sr-only">Aktionen</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="transfer in transfers.data" :key="transfer.id" class="hover:bg-gray-50">
                                        <!-- Club -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ transfer.club?.name || 'Unbekannt' }}
                                            </div>
                                        </td>

                                        <!-- Transfer Path -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center text-sm text-gray-500">
                                                <span class="truncate max-w-[120px]">{{ transfer.source_tenant?.name || 'Unbekannt' }}</span>
                                                <svg class="mx-2 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                                </svg>
                                                <span class="truncate max-w-[120px]">{{ transfer.target_tenant?.name || 'Unbekannt' }}</span>
                                            </div>
                                        </td>

                                        <!-- Status -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <TransferStatusBadge :status="transfer.status" />
                                        </td>

                                        <!-- Initiated By -->
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ transfer.initiated_by?.name || 'System' }}
                                        </td>

                                        <!-- Date -->
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ formatDate(transfer.started_at || transfer.created_at) }}
                                        </td>

                                        <!-- Duration -->
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ transfer.duration || '-' }}
                                        </td>

                                        <!-- Actions -->
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <Link
                                                :href="route('admin.club-transfers.show', transfer.id)"
                                                class="text-indigo-600 hover:text-indigo-900"
                                            >
                                                Details
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Empty State -->
                        <div v-if="transfers.data.length === 0" class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Keine Transfers gefunden</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Es wurden keine Club-Transfers gefunden, die deinen Filterkriterien entsprechen.
                            </p>
                        </div>

                        <!-- Pagination -->
                        <div v-if="transfers.data.length > 0" class="px-6 py-4 border-t border-gray-200">
                            <Pagination :links="transfers.links" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transfer Modal -->
        <PreviewTransferModal
            v-if="selectedClub"
            :show="showTransferModal"
            :club="selectedClub"
            :tenants="tenants.filter(t => t.id !== selectedClub?.tenant_id)"
            @close="closeTransferModal"
            @transferred="onTransferred"
        />
    </AdminLayout>
</template>
