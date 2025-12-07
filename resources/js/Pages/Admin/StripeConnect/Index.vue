<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    tenants: Array,
    stats: Object,
    platformSettings: Object,
    configurationRequired: {
        type: Boolean,
        default: false,
    },
});

const showSettingsModal = ref(false);
const settingsForm = ref({
    application_fee_percent: props.platformSettings?.application_fee_percent || 2.5,
    application_fee_fixed: props.platformSettings?.application_fee_fixed || 0,
    payout_schedule: props.platformSettings?.payout_schedule || 'daily',
    payout_delay_days: props.platformSettings?.payout_delay_days || 7,
});
const isSaving = ref(false);

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
    });
};

const getStatusBadgeClass = (status) => {
    const classes = {
        active: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        restricted: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        not_connected: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
    };
    return classes[status] || classes.not_connected;
};

const getStatusLabel = (status) => {
    const labels = {
        active: 'Aktiv',
        pending: 'Ausstehend',
        restricted: 'Eingeschränkt',
        not_connected: 'Nicht verbunden',
    };
    return labels[status] || status;
};

const saveSettings = async () => {
    isSaving.value = true;
    try {
        await router.put(route('admin.stripe-connect.platform-fee'), settingsForm.value, {
            preserveScroll: true,
            onSuccess: () => {
                showSettingsModal.value = false;
            },
        });
    } finally {
        isSaving.value = false;
    }
};

const refreshTenantStatus = async (tenantId) => {
    await router.post(route('admin.stripe-connect.refresh-tenant', tenantId), {}, {
        preserveScroll: true,
    });
};
</script>

<template>
    <AdminLayout title="Stripe Connect">
        <Head title="Stripe Connect Verwaltung" />

        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Stripe Connect Verwaltung
                </h2>
                <button
                    v-if="!configurationRequired"
                    @click="showSettingsModal = true"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Einstellungen
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Configuration Required Warning -->
                <div v-if="configurationRequired" class="mb-8 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6">
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div>
                            <h3 class="text-lg font-semibold text-yellow-800 dark:text-yellow-200">
                                Stripe-Konfiguration erforderlich
                            </h3>
                            <p class="mt-1 text-yellow-700 dark:text-yellow-300">
                                Stripe Connect ist nicht konfiguriert. Bitte setzen Sie die folgenden Umgebungsvariablen in der <code class="bg-yellow-100 dark:bg-yellow-800 px-1 rounded">.env</code> Datei:
                            </p>
                            <ul class="mt-3 list-disc list-inside text-sm text-yellow-700 dark:text-yellow-300 space-y-1">
                                <li><code class="bg-yellow-100 dark:bg-yellow-800 px-1 rounded">STRIPE_KEY</code> - Publishable Key (pk_...)</li>
                                <li><code class="bg-yellow-100 dark:bg-yellow-800 px-1 rounded">STRIPE_SECRET</code> - Secret Key (sk_...)</li>
                                <li><code class="bg-yellow-100 dark:bg-yellow-800 px-1 rounded">STRIPE_CONNECT_CLIENT_ID</code> - Connect Client ID (ca_...)</li>
                            </ul>
                            <p class="mt-3 text-sm text-yellow-600 dark:text-yellow-400">
                                Diese Werte finden Sie im <a href="https://dashboard.stripe.com/apikeys" target="_blank" class="underline hover:text-yellow-800 dark:hover:text-yellow-200">Stripe Dashboard</a> unter Developers → API keys.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div v-if="!configurationRequired" class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Verbundene Accounts</div>
                        <div class="mt-2 text-3xl font-bold text-green-600">{{ stats.total_connected }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Onboarding ausstehend</div>
                        <div class="mt-2 text-3xl font-bold text-yellow-600">{{ stats.pending_onboarding }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Eingeschränkt</div>
                        <div class="mt-2 text-3xl font-bold text-red-600">{{ stats.restricted }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Platform-Gebühren (Monat)</div>
                        <div class="mt-2 text-3xl font-bold text-indigo-600">{{ stats.monthly_fees_formatted }}</div>
                    </div>
                </div>

                <!-- Platform Settings Summary -->
                <div v-if="!configurationRequired && platformSettings" class="bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-4 mb-8">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-sm text-indigo-700 dark:text-indigo-300">
                                Aktuelle Platform-Gebühr: <strong>{{ platformSettings.application_fee_percent }}%</strong>
                                <span v-if="platformSettings.application_fee_fixed"> + {{ platformSettings.application_fee_fixed }} EUR</span>
                            </span>
                        </div>
                        <span class="text-sm text-indigo-600 dark:text-indigo-400">
                            Auszahlung: {{ platformSettings.payout_schedule }} ({{ platformSettings.payout_delay_days }} Tage Verzögerung)
                        </span>
                    </div>
                </div>

                <!-- Tenants Table -->
                <div v-if="!configurationRequired" class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                            Verbundene Tenants
                        </h3>

                        <div v-if="tenants.length === 0" class="text-center py-12 text-gray-500 dark:text-gray-400">
                            Noch keine Tenants mit Stripe Connect verbunden.
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Tenant
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Zahlungen
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Auszahlungen
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Clubs
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Verbunden am
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Aktionen
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="tenant in tenants" :key="tenant.id">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ tenant.name }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ tenant.stripe_connect_account_id }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                :class="getStatusBadgeClass(tenant.stripe_connect_status)"
                                                class="px-2 py-1 text-xs font-medium rounded-full"
                                            >
                                                {{ getStatusLabel(tenant.stripe_connect_status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span v-if="tenant.stripe_connect_charges_enabled" class="text-green-600 dark:text-green-400">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                            <span v-else class="text-gray-400">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span v-if="tenant.stripe_connect_payouts_enabled" class="text-green-600 dark:text-green-400">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                            <span v-else class="text-gray-400">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ tenant.clubs_count }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ formatDate(tenant.stripe_connect_connected_at) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                            <div class="flex justify-end gap-2">
                                                <button
                                                    @click="refreshTenantStatus(tenant.id)"
                                                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                                                    title="Status aktualisieren"
                                                >
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                    </svg>
                                                </button>
                                                <Link
                                                    :href="route('admin.stripe-connect.show', tenant.id)"
                                                    class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                                >
                                                    Details
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Settings Modal -->
        <div v-if="showSettingsModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black opacity-50" @click="showSettingsModal = false"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6">
                        Platform-Gebühren Einstellungen
                    </h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Gebühr in Prozent
                            </label>
                            <input
                                v-model.number="settingsForm.application_fee_percent"
                                type="number"
                                step="0.1"
                                min="0"
                                max="50"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Prozentsatz pro Transaktion (0-50%)
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Fixe Gebühr (EUR)
                            </label>
                            <input
                                v-model.number="settingsForm.application_fee_fixed"
                                type="number"
                                step="0.01"
                                min="0"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Zusätzliche fixe Gebühr pro Transaktion
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Auszahlungs-Intervall
                            </label>
                            <select
                                v-model="settingsForm.payout_schedule"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            >
                                <option value="daily">Täglich</option>
                                <option value="weekly">Wöchentlich</option>
                                <option value="monthly">Monatlich</option>
                                <option value="manual">Manuell</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Auszahlungs-Verzögerung (Tage)
                            </label>
                            <input
                                v-model.number="settingsForm.payout_delay_days"
                                type="number"
                                min="2"
                                max="14"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Verzögerung bis zur Auszahlung (2-14 Tage)
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button
                            @click="showSettingsModal = false"
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition"
                        >
                            Abbrechen
                        </button>
                        <button
                            @click="saveSettings"
                            :disabled="isSaving"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition disabled:opacity-50"
                        >
                            {{ isSaving ? 'Speichern...' : 'Speichern' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
