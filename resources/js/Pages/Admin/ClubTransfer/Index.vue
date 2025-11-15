<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import TextInput from '@/Components/TextInput.vue';
import TransferStatusBadge from './Partials/TransferStatusBadge.vue';

const props = defineProps({
    transfers: {
        type: Object,
        required: true,
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

// Filter state
const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || 'all');
const sourceTenantId = ref(props.filters?.source_tenant_id || '');
const targetTenantId = ref(props.filters?.target_tenant_id || '');

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
const stats = {
    total: props.transfers.total || 0,
    active: props.transfers.data.filter(t => ['pending', 'processing'].includes(t.status)).length,
    completed: props.transfers.data.filter(t => t.status === 'completed').length,
    failed: props.transfers.data.filter(t => t.status === 'failed').length,
};

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
                                            {{ transfer.club.name }}
                                        </div>
                                    </td>

                                    <!-- Transfer Path -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center text-sm text-gray-500">
                                            <span class="truncate max-w-[120px]">{{ transfer.source_tenant.name }}</span>
                                            <svg class="mx-2 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                            </svg>
                                            <span class="truncate max-w-[120px]">{{ transfer.target_tenant.name }}</span>
                                        </div>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <TransferStatusBadge :status="transfer.status" />
                                    </td>

                                    <!-- Initiated By -->
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ transfer.initiated_by.name }}
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
    </AdminLayout>
</template>
