<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    plans: {
        type: Array,
        default: () => [],
    },
    plansByTenant: {
        type: Object,
        default: () => ({}),
    },
    tenants: {
        type: Array,
        default: () => [],
    },
});

const selectedTenantId = ref('all');

const filteredPlans = computed(() => {
    if (selectedTenantId.value === 'all') {
        return props.plans;
    }
    return props.plans.filter(plan => plan.tenant_id === selectedTenantId.value);
});

const getTenantName = (tenantId) => {
    const tenant = props.tenants.find(t => t.id === tenantId);
    return tenant?.name || 'Unbekannt';
};

const formatPrice = (price) => {
    const priceValue = typeof price === 'number' ? price : parseFloat(price) || 0;
    return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: 'EUR',
    }).format(priceValue);
};

const formatLimit = (value) => {
    if (value === -1 || value === null) {
        return 'Unbegrenzt';
    }
    return new Intl.NumberFormat('de-DE').format(value);
};

// Stats
const stats = computed(() => ({
    total: props.plans.length,
    active: props.plans.filter(p => p.is_active).length,
    tenants: Object.keys(props.plansByTenant).length,
}));
</script>

<template>
    <AdminLayout title="Club Subscription Plans">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Club Subscription Plans
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Verwalte Club-spezifische Subscription Plans pro Tenant
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <Link
                        :href="route('admin.dashboard')"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50"
                    >
                        Dashboard
                    </Link>
                    <Link
                        :href="route('admin.club-plans.create')"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700"
                    >
                        + Neuer Plan
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-3 mb-8">
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-indigo-100 rounded-md p-3">
                                    <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Gesamt Plans
                                        </dt>
                                        <dd class="text-2xl font-semibold text-gray-900">
                                            {{ stats.total }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
                                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Aktive Plans
                                        </dt>
                                        <dd class="text-2xl font-semibold text-gray-900">
                                            {{ stats.active }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-purple-100 rounded-md p-3">
                                    <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Tenants mit Plans
                                        </dt>
                                        <dd class="text-2xl font-semibold text-gray-900">
                                            {{ stats.tenants }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tenant Filter -->
                <div class="mb-6">
                    <label for="tenant-filter" class="block text-sm font-medium text-gray-700 mb-2">
                        Nach Tenant filtern
                    </label>
                    <select
                        id="tenant-filter"
                        v-model="selectedTenantId"
                        class="block w-full max-w-xs pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                    >
                        <option value="all">Alle Tenants</option>
                        <option v-for="tenant in tenants" :key="tenant.id" :value="tenant.id">
                            {{ tenant.name }}
                        </option>
                    </select>
                </div>

                <!-- Plans Table -->
                <div v-if="filteredPlans.length > 0" class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Plan
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Tenant
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Preis
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Limits
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Clubs
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th scope="col" class="relative px-6 py-3">
                                    <span class="sr-only">Aktionen</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="plan in filteredPlans" :key="plan.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div
                                            v-if="plan.color"
                                            class="w-3 h-3 rounded-full mr-3"
                                            :style="{ backgroundColor: plan.color }"
                                        ></div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ plan.name }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ plan.slug }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ plan.tenant?.name || getTenantName(plan.tenant_id) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ formatPrice(plan.price) }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ plan.billing_interval_label }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div v-if="plan.limits">
                                        <span class="block">Teams: {{ formatLimit(plan.limits.max_teams) }}</span>
                                        <span class="block">Spieler: {{ formatLimit(plan.limits.max_players) }}</span>
                                    </div>
                                    <span v-else class="text-gray-400">-</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ plan.clubs_count || 0 }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        v-if="plan.is_active"
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800"
                                    >
                                        Aktiv
                                    </span>
                                    <span
                                        v-else
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800"
                                    >
                                        Inaktiv
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <Link
                                        :href="route('admin.club-plans.show', plan.id)"
                                        class="text-indigo-600 hover:text-indigo-900 mr-4"
                                    >
                                        Details
                                    </Link>
                                    <Link
                                        :href="route('admin.club-plans.edit', plan.id)"
                                        class="text-gray-600 hover:text-gray-900"
                                    >
                                        Bearbeiten
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-else class="bg-white shadow rounded-lg">
                    <div class="text-center py-12 px-6">
                        <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">Keine Club Subscription Plans</h3>
                        <p class="mt-2 text-sm text-gray-500">
                            Erstelle deinen ersten Club Subscription Plan oder führe den Seeder aus.
                        </p>
                        <div class="mt-6">
                            <Link
                                :href="route('admin.club-plans.create')"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700"
                            >
                                + Ersten Plan erstellen
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
