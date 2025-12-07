<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConnectStatusCard from '@/Components/StripeConnect/ConnectStatusCard.vue';
import OnboardingButton from '@/Components/StripeConnect/OnboardingButton.vue';

const props = defineProps({
    tenant: Object,
    applicationFeePercent: Number,
    balance: Object,
    recentPayouts: Array,
});

const isLoading = ref(false);
const showDisconnectModal = ref(false);

const isConnected = computed(() => props.tenant.stripe_connect_status === 'active');
const isPending = computed(() => props.tenant.stripe_connect_status === 'pending');
const isNotConnected = computed(() => props.tenant.stripe_connect_status === 'not_connected');

const formatAmount = (cents, currency = 'eur') => {
    return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: currency.toUpperCase(),
    }).format(cents / 100);
};

const startOnboarding = async () => {
    isLoading.value = true;
    try {
        const response = await fetch(route('stripe-connect.onboard'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });
        const data = await response.json();
        if (data.url) {
            window.location.href = data.url;
        }
    } catch (error) {
        console.error('Onboarding failed:', error);
    } finally {
        isLoading.value = false;
    }
};

const openDashboard = () => {
    window.open(route('stripe-connect.dashboard'), '_blank');
};

const disconnect = async () => {
    isLoading.value = true;
    try {
        await router.post(route('stripe-connect.disconnect'));
        showDisconnectModal.value = false;
    } catch (error) {
        console.error('Disconnect failed:', error);
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <AppLayout title="Stripe Connect">
        <Head title="Stripe Connect" />

        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Stripe Connect
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- Not Connected State -->
                <div v-if="isNotConnected" class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-8">
                    <div class="text-center">
                        <div class="mx-auto h-16 w-16 flex items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900">
                            <svg class="h-8 w-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </div>
                        <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">
                            Stripe Connect einrichten
                        </h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                            Verbinden Sie Ihren Stripe Account, um Zahlungen von Ihren Clubs direkt zu empfangen.
                            Die Platform erhebt eine Gebühr von {{ applicationFeePercent }}% pro Transaktion.
                        </p>

                        <div class="mt-6 bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Vorteile:</h4>
                            <ul class="text-sm text-gray-600 dark:text-gray-300 space-y-2 text-left max-w-sm mx-auto">
                                <li class="flex items-center">
                                    <svg class="h-4 w-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    Direkte Zahlungen von Clubs
                                </li>
                                <li class="flex items-center">
                                    <svg class="h-4 w-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    Automatische Auszahlungen
                                </li>
                                <li class="flex items-center">
                                    <svg class="h-4 w-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    Eigenes Stripe Dashboard
                                </li>
                                <li class="flex items-center">
                                    <svg class="h-4 w-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    Deutsche Zahlungsmethoden (SEPA, Giropay)
                                </li>
                            </ul>
                        </div>

                        <div class="mt-8">
                            <OnboardingButton @click="startOnboarding" :loading="isLoading" />
                        </div>
                    </div>
                </div>

                <!-- Pending State -->
                <div v-else-if="isPending" class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-8">
                    <div class="text-center">
                        <div class="mx-auto h-16 w-16 flex items-center justify-center rounded-full bg-yellow-100 dark:bg-yellow-900">
                            <svg class="h-8 w-8 text-yellow-600 dark:text-yellow-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">
                            Einrichtung wird fortgesetzt
                        </h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Ihre Stripe Connect Einrichtung ist noch nicht abgeschlossen.
                            Bitte vervollständigen Sie die Verifizierung.
                        </p>
                        <div class="mt-6">
                            <button
                                @click="startOnboarding"
                                :disabled="isLoading"
                                class="inline-flex items-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white font-semibold rounded-lg transition"
                            >
                                <span v-if="isLoading">Laden...</span>
                                <span v-else>Einrichtung fortsetzen</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Connected State -->
                <div v-else class="space-y-6">
                    <!-- Status Card -->
                    <ConnectStatusCard
                        :status="tenant.stripe_connect_status"
                        :charges-enabled="tenant.stripe_connect_charges_enabled"
                        :payouts-enabled="tenant.stripe_connect_payouts_enabled"
                        :connected-at="tenant.stripe_connect_connected_at"
                        :account-id="tenant.stripe_connect_account_id"
                    />

                    <!-- Balance Card -->
                    <div v-if="balance" class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Kontostand</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Verfügbar</p>
                                <p class="text-2xl font-bold text-green-600 dark:text-green-400">
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

                    <!-- Recent Payouts -->
                    <div v-if="recentPayouts?.length" class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Letzte Auszahlungen</h3>
                        <div class="space-y-3">
                            <div
                                v-for="payout in recentPayouts"
                                :key="payout.id"
                                class="flex justify-between items-center py-2 border-b dark:border-gray-700 last:border-0"
                            >
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ formatAmount(payout.amount, payout.currency) }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ payout.arrival_date }}
                                    </p>
                                </div>
                                <span
                                    :class="{
                                        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': payout.status === 'paid',
                                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': payout.status === 'pending',
                                        'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': payout.status === 'failed',
                                    }"
                                    class="px-2 py-1 text-xs font-medium rounded-full"
                                >
                                    {{ payout.status }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Aktionen</h3>
                        <div class="flex flex-wrap gap-4">
                            <button
                                @click="openDashboard"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                                Stripe Dashboard öffnen
                            </button>
                            <button
                                @click="showDisconnectModal = true"
                                class="inline-flex items-center px-4 py-2 bg-red-100 hover:bg-red-200 text-red-700 font-semibold rounded-lg transition dark:bg-red-900 dark:text-red-200 dark:hover:bg-red-800"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Verbindung trennen
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Disconnect Modal -->
                <div v-if="showDisconnectModal" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex items-center justify-center min-h-screen px-4">
                        <div class="fixed inset-0 bg-black opacity-50" @click="showDisconnectModal = false"></div>
                        <div class="relative bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                Verbindung wirklich trennen?
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                                Wenn Sie die Verbindung trennen, können Clubs keine Zahlungen mehr an Sie leisten.
                                Sie können die Verbindung später wieder herstellen.
                            </p>
                            <div class="flex justify-end gap-3">
                                <button
                                    @click="showDisconnectModal = false"
                                    class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition"
                                >
                                    Abbrechen
                                </button>
                                <button
                                    @click="disconnect"
                                    :disabled="isLoading"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition"
                                >
                                    {{ isLoading ? 'Wird getrennt...' : 'Trennen' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
