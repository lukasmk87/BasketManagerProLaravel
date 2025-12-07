<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    vouchers: { type: Array, default: () => [] },
    tenants: { type: Array, default: () => [] },
    statistics: { type: Object, default: () => ({}) },
});

const selectedTenantId = ref('all');
const searchQuery = ref('');

const filteredVouchers = computed(() => {
    let result = props.vouchers;

    // Filter by tenant
    if (selectedTenantId.value === 'system') {
        result = result.filter(v => v.is_system_wide);
    } else if (selectedTenantId.value !== 'all') {
        result = result.filter(v => v.tenant_id === selectedTenantId.value);
    }

    // Filter by search
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(v =>
            v.code.toLowerCase().includes(query) ||
            v.name.toLowerCase().includes(query)
        );
    }

    return result;
});

const getTypeBadgeClass = (type) => {
    const classes = {
        percent: 'bg-blue-100 text-blue-800',
        fixed_amount: 'bg-green-100 text-green-800',
        trial_extension: 'bg-purple-100 text-purple-800',
    };
    return classes[type] || 'bg-gray-100 text-gray-800';
};

const getStatusBadgeClass = (color) => {
    const classes = {
        green: 'bg-green-100 text-green-800',
        red: 'bg-red-100 text-red-800',
        yellow: 'bg-yellow-100 text-yellow-800',
        orange: 'bg-orange-100 text-orange-800',
    };
    return classes[color] || 'bg-gray-100 text-gray-800';
};

const toggleActive = (voucher) => {
    router.post(route('admin.vouchers.toggle-active', voucher.id), {}, {
        preserveScroll: true,
    });
};
</script>

<template>
    <AdminLayout title="Voucher-Verwaltung">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Voucher-Verwaltung
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        System-weite und Tenant-spezifische Vouchers verwalten
                    </p>
                </div>
                <Link
                    :href="route('admin.vouchers.create')"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    + Neuer Voucher
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Statistics -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-5 mb-8">
                    <div class="bg-white overflow-hidden shadow rounded-lg p-5">
                        <div class="text-sm font-medium text-gray-500">Gesamt</div>
                        <div class="text-2xl font-semibold text-gray-900">{{ statistics.total_vouchers }}</div>
                    </div>
                    <div class="bg-white overflow-hidden shadow rounded-lg p-5">
                        <div class="text-sm font-medium text-gray-500">Aktiv</div>
                        <div class="text-2xl font-semibold text-green-600">{{ statistics.active_vouchers }}</div>
                    </div>
                    <div class="bg-white overflow-hidden shadow rounded-lg p-5">
                        <div class="text-sm font-medium text-gray-500">System-weit</div>
                        <div class="text-2xl font-semibold text-indigo-600">{{ statistics.system_wide_vouchers }}</div>
                    </div>
                    <div class="bg-white overflow-hidden shadow rounded-lg p-5">
                        <div class="text-sm font-medium text-gray-500">Einlösungen</div>
                        <div class="text-2xl font-semibold text-gray-900">{{ statistics.total_redemptions }}</div>
                    </div>
                    <div class="bg-white overflow-hidden shadow rounded-lg p-5">
                        <div class="text-sm font-medium text-gray-500">Rabatt gegeben</div>
                        <div class="text-2xl font-semibold text-gray-900">{{ statistics.total_discount_given }} EUR</div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="mb-6 flex flex-wrap gap-4">
                    <div>
                        <select
                            v-model="selectedTenantId"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                            <option value="all">Alle Vouchers</option>
                            <option value="system">System-weit (Super Admin)</option>
                            <option v-for="tenant in tenants" :key="tenant.id" :value="tenant.id">
                                {{ tenant.name }}
                            </option>
                        </select>
                    </div>
                    <div class="flex-1 max-w-xs">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Suche nach Code oder Name..."
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        />
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Typ</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rabatt</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tenant</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Einlösungen</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="voucher in filteredVouchers" :key="voucher.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-mono text-sm font-medium text-gray-900">{{ voucher.code }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ voucher.name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="[getTypeBadgeClass(voucher.type), 'px-2 py-1 text-xs font-semibold rounded-full']">
                                        {{ voucher.type_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ voucher.discount_label }}
                                    <span class="text-gray-500 text-xs block">{{ voucher.duration_label }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span v-if="voucher.is_system_wide" class="text-indigo-600 font-medium">System-weit</span>
                                    <span v-else class="text-gray-900">{{ voucher.tenant_name }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ voucher.current_redemptions }}
                                    <span v-if="voucher.max_redemptions">/ {{ voucher.max_redemptions }}</span>
                                    <span v-else class="text-gray-400">/ unbegrenzt</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button
                                        @click="toggleActive(voucher)"
                                        :class="[getStatusBadgeClass(voucher.status_color), 'px-2 py-1 text-xs font-semibold rounded-full cursor-pointer hover:opacity-80']"
                                    >
                                        {{ voucher.status_label }}
                                    </button>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                    <Link
                                        :href="route('admin.vouchers.show', voucher.id)"
                                        class="text-indigo-600 hover:text-indigo-900"
                                    >
                                        Details
                                    </Link>
                                    <Link
                                        :href="route('admin.vouchers.edit', voucher.id)"
                                        class="text-gray-600 hover:text-gray-900"
                                    >
                                        Bearbeiten
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="filteredVouchers.length === 0">
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    Keine Vouchers gefunden.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
