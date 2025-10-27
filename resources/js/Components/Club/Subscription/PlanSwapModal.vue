<template>
    <Modal :show="show" max-width="3xl" :closeable="!swapping" @close="handleClose">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-white">
                        Plan-Wechsel
                    </h3>
                    <p class="mt-1 text-sm text-blue-100">
                        Vorschau der Änderungen und Kosten
                    </p>
                </div>
            </div>
        </div>

        <!-- Body -->
        <div class="p-6">
            <!-- Loading State -->
            <div v-if="loading" class="space-y-6">
                <div class="flex justify-center items-center py-12">
                    <div class="text-center">
                        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
                        <p class="text-sm text-gray-600">Proration wird berechnet...</p>
                    </div>
                </div>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="rounded-lg bg-red-50 border border-red-200 p-4">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="ml-3">
                        <h4 class="text-sm font-semibold text-red-900">Fehler beim Laden der Vorschau</h4>
                        <p class="mt-1 text-sm text-red-800">{{ error }}</p>
                        <button
                            @click="fetchPreview"
                            class="mt-3 text-sm font-medium text-red-900 hover:text-red-700 underline"
                        >
                            Erneut versuchen
                        </button>
                    </div>
                </div>
            </div>

            <!-- Preview Content -->
            <div v-else-if="previewData" class="space-y-6">
                <!-- Plan Comparison -->
                <div>
                    <h4 class="text-sm font-semibold text-gray-900 mb-4">Plan-Vergleich</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Current Plan -->
                        <div class="rounded-lg border-2 border-gray-300 bg-gray-50 p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-medium text-gray-600 uppercase">Aktuell</span>
                                <span class="text-xs text-gray-500">{{ currentBillingInterval === 'yearly' ? 'Jährlich' : 'Monatlich' }}</span>
                            </div>
                            <h5 class="text-lg font-bold text-gray-900 mb-1">{{ previewData.current_plan.name }}</h5>
                            <p class="text-2xl font-bold text-gray-700">
                                {{ formatAmount(previewData.current_plan.price, previewData.current_plan.currency) }}
                                <span class="text-sm font-normal text-gray-600">/ {{ currentBillingInterval === 'yearly' ? 'Jahr' : 'Monat' }}</span>
                            </p>
                        </div>

                        <!-- New Plan -->
                        <div class="rounded-lg border-2 p-4" :class="upgradeBorderClass">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-medium uppercase" :class="upgradeBadgeClass">
                                    {{ isUpgrade ? '↑ Upgrade' : isDowngrade ? '↓ Downgrade' : 'Wechsel' }}
                                </span>
                                <span class="text-xs text-gray-500">{{ billingInterval === 'yearly' ? 'Jährlich' : 'Monatlich' }}</span>
                            </div>
                            <h5 class="text-lg font-bold text-gray-900 mb-1">{{ previewData.new_plan.name }}</h5>
                            <p class="text-2xl font-bold" :class="upgradeTextClass">
                                {{ formatAmount(previewData.new_plan.price, previewData.new_plan.currency) }}
                                <span class="text-sm font-normal text-gray-600">/ {{ billingInterval === 'yearly' ? 'Jahr' : 'Monat' }}</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Proration Summary -->
                <div class="rounded-lg bg-blue-50 border-2 border-blue-200 p-4">
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        <div class="ml-3 flex-1">
                            <h4 class="text-sm font-semibold text-blue-900 mb-3">Kostenübersicht</h4>

                            <div class="space-y-2 text-sm">
                                <!-- Credit (Guthaben) -->
                                <div v-if="previewData.proration.credit > 0" class="flex justify-between items-center">
                                    <span class="text-gray-700">
                                        <span class="inline-flex items-center">
                                            <svg class="w-4 h-4 text-green-600 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            Guthaben (ungenutzter Zeitraum)
                                        </span>
                                    </span>
                                    <span class="font-semibold text-green-600">
                                        - {{ formatAmount(previewData.proration.credit, previewData.proration.currency) }}
                                    </span>
                                </div>

                                <!-- Debit (Neue Gebühr) -->
                                <div v-if="previewData.proration.debit > 0" class="flex justify-between items-center">
                                    <span class="text-gray-700">
                                        <span class="inline-flex items-center">
                                            <svg class="w-4 h-4 text-blue-600 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                                            </svg>
                                            Neue Plan-Gebühr (anteilig)
                                        </span>
                                    </span>
                                    <span class="font-semibold text-blue-600">
                                        + {{ formatAmount(previewData.proration.debit, previewData.proration.currency) }}
                                    </span>
                                </div>

                                <!-- Divider -->
                                <div class="border-t border-blue-300 my-2"></div>

                                <!-- Net Proration Amount -->
                                <div class="flex justify-between items-center">
                                    <span class="font-semibold text-gray-900">Heute fällig:</span>
                                    <span class="text-lg font-bold text-blue-900">
                                        {{ formatAmount(previewData.upcoming_invoice.amount_due, previewData.upcoming_invoice.currency) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Explanation -->
                            <div class="mt-3 pt-3 border-t border-blue-300">
                                <p class="text-xs text-blue-800">
                                    <strong>Was passiert?</strong>
                                    <span v-if="isUpgrade">
                                        Sie upgraden zu einem höherwertigen Plan. Sie erhalten eine anteilige Rückerstattung für die verbleibende Zeit Ihres aktuellen Plans und zahlen den anteiligen Betrag für den neuen Plan bis zum nächsten Abrechnungsdatum.
                                    </span>
                                    <span v-else-if="isDowngrade">
                                        Sie wechseln zu einem günstigeren Plan. Sie erhalten ein Guthaben für die verbleibende Zeit Ihres aktuellen Plans, das mit der ersten Zahlung des neuen Plans verrechnet wird.
                                    </span>
                                    <span v-else>
                                        Sie wechseln zu einem anderen Plan. Die Differenz wird automatisch berechnet und verrechnet.
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Line Items (Collapsible) -->
                <details class="group">
                    <summary class="cursor-pointer rounded-lg border border-gray-300 bg-gray-50 px-4 py-3 hover:bg-gray-100 transition-colors">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-900">Details anzeigen ({{ previewData.line_items.length }} Positionen)</span>
                            <svg class="w-5 h-5 text-gray-600 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </summary>

                    <div class="mt-2 rounded-lg border border-gray-200 overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Beschreibung</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Zeitraum</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-700 uppercase">Betrag</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="(item, index) in previewData.line_items" :key="index" :class="item.proration ? 'bg-blue-50' : ''">
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        {{ item.description }}
                                        <span v-if="item.proration" class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                            Proration
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ formatDate(item.period.start) }} - {{ formatDate(item.period.end) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right" :class="item.amount < 0 ? 'text-green-600 font-semibold' : 'text-gray-900'">
                                        {{ item.amount < 0 ? '-' : '' }}{{ formatAmount(Math.abs(item.amount), item.currency) }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="2" class="px-4 py-3 text-sm font-semibold text-gray-900 text-right">Gesamt:</td>
                                    <td class="px-4 py-3 text-sm font-bold text-gray-900 text-right">
                                        {{ formatAmount(previewData.upcoming_invoice.total, previewData.upcoming_invoice.currency) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </details>

                <!-- Next Billing Info -->
                <div class="rounded-lg bg-gray-100 border border-gray-300 p-4">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-gray-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <div class="ml-3">
                            <h5 class="text-sm font-semibold text-gray-900 mb-1">Nächste Abrechnung</h5>
                            <p class="text-sm text-gray-700">
                                {{ formatDate(previewData.next_billing_date) }} -
                                <span class="font-semibold">{{ formatAmount(previewData.new_plan.price, previewData.new_plan.currency) }}</span>
                                {{ billingInterval === 'yearly' ? 'jährlich' : 'monatlich' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Important Info -->
                <div class="rounded-lg bg-yellow-50 border-2 border-yellow-200 p-4">
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-yellow-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <div class="ml-3">
                            <h5 class="text-sm font-semibold text-yellow-900 mb-2">Wichtige Hinweise</h5>
                            <ul class="text-sm text-yellow-800 space-y-1 list-disc list-inside">
                                <li>Der Plan-Wechsel wird <strong>sofort wirksam</strong></li>
                                <li>Sie erhalten eine anteilige Rückerstattung für den ungenutzten Zeitraum</li>
                                <li>Ihre Standard-Zahlungsmethode wird für den fälligen Betrag belastet</li>
                                <li>Nächste reguläre Abrechnung: {{ formatDate(previewData.next_billing_date) }}</li>
                                <li>Sie können jederzeit wieder wechseln oder kündigen</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="bg-gray-50 px-6 py-4 flex items-center justify-end gap-3">
            <SecondaryButton @click="handleClose" :disabled="swapping">
                Abbrechen
            </SecondaryButton>

            <PrimaryButton
                @click="handleSwap"
                :disabled="loading || swapping || error"
                :class="{ 'opacity-50 cursor-not-allowed': loading || swapping || error }"
            >
                <span v-if="swapping" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Plan wird gewechselt...
                </span>
                <span v-else>
                    Plan jetzt wechseln
                </span>
            </PrimaryButton>
        </div>
    </Modal>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { formatStripeError } from '@/utils/stripeErrors';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    clubId: {
        type: Number,
        required: true,
    },
    currentPlan: {
        type: Object,
        required: true,
    },
    newPlan: {
        type: Object,
        required: true,
    },
    billingInterval: {
        type: String,
        default: 'monthly',
        validator: (value) => ['monthly', 'yearly'].includes(value),
    },
    currentBillingInterval: {
        type: String,
        default: 'monthly',
        validator: (value) => ['monthly', 'yearly'].includes(value),
    },
});

const emit = defineEmits(['close', 'confirmed']);

// State
const loading = ref(false);
const swapping = ref(false);
const error = ref(null);
const previewData = ref(null);

// Computed
const isUpgrade = computed(() => {
    return previewData.value?.is_upgrade || false;
});

const isDowngrade = computed(() => {
    return previewData.value?.is_downgrade || false;
});

const upgradeBorderClass = computed(() => {
    if (isUpgrade.value) return 'border-green-500 bg-green-50';
    if (isDowngrade.value) return 'border-blue-500 bg-blue-50';
    return 'border-gray-300 bg-gray-50';
});

const upgradeBadgeClass = computed(() => {
    if (isUpgrade.value) return 'text-green-700';
    if (isDowngrade.value) return 'text-blue-700';
    return 'text-gray-700';
});

const upgradeTextClass = computed(() => {
    if (isUpgrade.value) return 'text-green-700';
    if (isDowngrade.value) return 'text-blue-700';
    return 'text-gray-700';
});

// Methods
const fetchPreview = async () => {
    loading.value = true;
    error.value = null;
    previewData.value = null;

    try {
        const response = await axios.post(
            route('club.billing.preview-plan-swap', { club: props.clubId }),
            {
                new_plan_id: props.newPlan.id,
                billing_interval: props.billingInterval,
                proration_behavior: 'create_prorations',
            }
        );

        previewData.value = response.data.preview;
    } catch (err) {
        console.error('Preview plan swap error:', err);
        error.value = formatStripeError(err.response?.data?.error || err);
    } finally {
        loading.value = false;
    }
};

const handleSwap = async () => {
    if (swapping.value || !previewData.value) return;

    swapping.value = true;
    error.value = null;

    try {
        await axios.post(
            route('club.subscription.swap', { club: props.clubId }),
            {
                new_plan_id: props.newPlan.id,
                billing_interval: props.billingInterval,
                proration_behavior: 'create_prorations',
            }
        );

        emit('confirmed', {
            plan: props.newPlan,
            billingInterval: props.billingInterval,
        });
    } catch (err) {
        console.error('Plan swap error:', err);
        error.value = formatStripeError(err.response?.data?.error || err);
    } finally {
        swapping.value = false;
    }
};

const handleClose = () => {
    if (!swapping.value) {
        emit('close');
    }
};

const formatAmount = (amount, currency = 'EUR') => {
    return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: currency.toUpperCase(),
    }).format(amount);
};

const formatDate = (timestamp) => {
    return new Intl.DateTimeFormat('de-DE', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    }).format(new Date(timestamp * 1000));
};

// Watch for show prop changes to fetch preview
watch(() => props.show, (newValue) => {
    if (newValue) {
        fetchPreview();
    } else {
        // Reset state when closing
        setTimeout(() => {
            previewData.value = null;
            error.value = null;
        }, 300);
    }
});
</script>
