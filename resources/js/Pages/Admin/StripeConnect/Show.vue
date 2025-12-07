<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    tenant: Object,
    balance: Object,
    recentPayouts: Array,
    recentTransfers: Array,
    monthlyStats: Object,
});

const formatAmount = (cents, currency = 'eur') => {
    return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: currency.toUpperCase(),
    }).format(cents / 100);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('de-DE', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getStatusBadgeClass = (status) => {
    const classes = {
        succeeded: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        failed: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        refunded: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
        paid: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    };
    return classes[status] || classes.pending;
};

const refreshStatus = () => {
    router.post(route('admin.stripe-connect.refresh-tenant', props.tenant.id), {}, {
        preserveScroll: true,
    });
};
</script>

<template>
    <AdminLayout :title="`Connect: ${tenant.name}`">
        <Head :title="`Stripe Connect - ${tenant.name}`" />

        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <Link
                        :href="route('admin.stripe-connect.index')"
                        class="mr-4 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        {{ tenant.name }} - Stripe Connect
                    </h2>
                </div>
                <button
                    @click="refreshStatus"
                    class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-medium rounded-lg transition"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Status aktualisieren
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Tenant Info -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Account ID</h3>
                            <p class="mt-1 text-sm text-gray-900 dark:text-white font-mono">
                                {{ tenant.stripe_connect_account_id || '-' }}
                            </p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</h3>
                            <p class="mt-1">
                                <span
                                    :class="{
                                        'bg-green-100 text-green-800': tenant.stripe_connect_status === 'active',
                                        'bg-yellow-100 text-yellow-800': tenant.stripe_connect_status === 'pending',
                                        'bg-red-100 text-red-800': tenant.stripe_connect_status === 'restricted',
                                    }"
                                    class="px-2 py-1 text-sm font-medium rounded-full"
                                >
                                    {{ tenant.stripe_connect_status }}
                                </span>
                            </p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Application Fee</h3>
                            <p class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ tenant.application_fee_percent }}%
                            </p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Zahlungen</h3>
                            <p class="mt-1">
                                <span v-if="tenant.stripe_connect_charges_enabled" class="text-green-600">Aktiviert</span>
                                <span v-else class="text-red-600">Deaktiviert</span>
                            </p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Auszahlungen</h3>
                            <p class="mt-1">
                                <span v-if="tenant.stripe_connect_payouts_enabled" class="text-green-600">Aktiviert</span>
                                <span v-else class="text-red-600">Deaktiviert</span>
                            </p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Verbunden seit</h3>
                            <p class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ formatDate(tenant.stripe_connect_connected_at) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Balance & Monthly Stats -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Balance -->
                    <div v-if="balance" class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Kontostand</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Verfügbar</p>
                                <p class="text-2xl font-bold text-green-600">
                                    {{ formatAmount(balance.available, balance.currency) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Ausstehend</p>
                                <p class="text-2xl font-bold text-gray-600 dark:text-gray-300">
                                    {{ formatAmount(balance.pending, balance.currency) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Monthly Stats -->
                    <div v-if="monthlyStats" class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Diesen Monat</h3>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Volumen</p>
                                <p class="text-xl font-bold text-gray-900 dark:text-white">
                                    {{ formatAmount(monthlyStats.total_volume) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Gebühren</p>
                                <p class="text-xl font-bold text-indigo-600">
                                    {{ formatAmount(monthlyStats.total_fees) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Transfers</p>
                                <p class="text-xl font-bold text-gray-900 dark:text-white">
                                    {{ monthlyStats.transfer_count }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Payouts -->
                <div v-if="recentPayouts?.length" class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Letzte Auszahlungen</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Betrag</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ankunft</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="payout in recentPayouts" :key="payout.id">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
                                        {{ formatAmount(payout.amount, payout.currency) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span :class="getStatusBadgeClass(payout.status)" class="px-2 py-1 text-xs font-medium rounded-full">
                                            {{ payout.status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                        {{ payout.arrival_date }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Transfers -->
                <div v-if="recentTransfers?.length" class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Letzte Transfers</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Club</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Brutto</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Gebühr</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Netto</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Datum</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="transfer in recentTransfers" :key="transfer.id">
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                        {{ transfer.club_name || '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                        {{ formatAmount(transfer.gross_amount, transfer.currency) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-indigo-600">
                                        {{ formatAmount(transfer.application_fee_amount, transfer.currency) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm font-medium text-green-600">
                                        {{ formatAmount(transfer.net_amount, transfer.currency) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span :class="getStatusBadgeClass(transfer.status)" class="px-2 py-1 text-xs font-medium rounded-full">
                                            {{ transfer.status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                        {{ formatDate(transfer.created_at) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
