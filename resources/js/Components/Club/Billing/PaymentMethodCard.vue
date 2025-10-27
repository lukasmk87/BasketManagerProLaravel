<template>
    <div
        class="bg-white rounded-lg shadow-sm border hover:shadow-md transition-shadow duration-200"
        :class="{ 'border-blue-300 ring-2 ring-blue-100': paymentMethod.is_default }"
    >
        <div class="p-6">
            <!-- Header: Icon + Type + Default Badge -->
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center space-x-3">
                    <!-- Payment Method Icon -->
                    <div class="flex-shrink-0">
                        <span class="text-3xl">{{ getPaymentMethodIcon(paymentMethod.type) }}</span>
                    </div>

                    <!-- Payment Method Details -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">
                            {{ getPaymentMethodName(paymentMethod.type) }}
                        </h3>
                        <p class="text-sm text-gray-600 mt-1">
                            {{ getPaymentMethodDetails(paymentMethod) }}
                        </p>
                    </div>
                </div>

                <!-- Default Badge -->
                <span
                    v-if="paymentMethod.is_default"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                >
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Standard
                </span>
            </div>

            <!-- Billing Details (if available) -->
            <div v-if="paymentMethod.billing_details?.name" class="mb-4 pt-4 border-t border-gray-100">
                <div class="grid grid-cols-1 gap-2 text-sm">
                    <div v-if="paymentMethod.billing_details.name" class="flex items-center text-gray-600">
                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        {{ paymentMethod.billing_details.name }}
                    </div>
                    <div v-if="paymentMethod.billing_details.email" class="flex items-center text-gray-600">
                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        {{ paymentMethod.billing_details.email }}
                    </div>
                </div>
            </div>

            <!-- Expiration Warning (for cards) -->
            <div
                v-if="isExpiringCard"
                class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg"
            >
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-yellow-600 mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-sm text-yellow-800">
                        Diese Karte läuft bald ab ({{ getCardExpiration(paymentMethod) }})
                    </p>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end space-x-2 pt-4 border-t border-gray-100">
                <!-- Set as Default -->
                <button
                    v-if="!paymentMethod.is_default && !loading"
                    @click="$emit('set-default', paymentMethod.id)"
                    class="px-3 py-1.5 text-sm font-medium text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-md transition-colors"
                    :disabled="loading"
                >
                    Als Standard festlegen
                </button>

                <!-- Update Billing Details -->
                <button
                    @click="$emit('update', paymentMethod.id)"
                    class="px-3 py-1.5 text-sm font-medium text-gray-600 hover:text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
                    :disabled="loading"
                >
                    Bearbeiten
                </button>

                <!-- Delete -->
                <button
                    v-if="!paymentMethod.is_default"
                    @click="confirmDelete"
                    class="px-3 py-1.5 text-sm font-medium text-red-600 hover:text-red-700 hover:bg-red-50 rounded-md transition-colors"
                    :disabled="loading"
                >
                    <svg v-if="loading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                    </svg>
                    <span v-else>Entfernen</span>
                </button>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <teleport to="body">
            <div
                v-if="showDeleteConfirm"
                class="fixed inset-0 z-50 overflow-y-auto"
                aria-labelledby="modal-title"
                role="dialog"
                aria-modal="true"
            >
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <!-- Background overlay -->
                    <div
                        class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                        @click="showDeleteConfirm = false"
                    ></div>

                    <!-- Modal panel -->
                    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                        Zahlungsmethode entfernen
                                    </h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500">
                                            Möchten Sie diese Zahlungsmethode wirklich entfernen? Diese Aktion kann nicht rückgängig gemacht werden.
                                        </p>
                                        <p class="text-sm text-gray-700 font-medium mt-2">
                                            {{ getPaymentMethodName(paymentMethod.type) }} •••• {{ getPaymentMethodLast4(paymentMethod) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button
                                type="button"
                                @click="handleDelete"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                Entfernen
                            </button>
                            <button
                                type="button"
                                @click="showDeleteConfirm = false"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                Abbrechen
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </teleport>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useStripe } from '@/composables/useStripe';

const props = defineProps({
    paymentMethod: {
        type: Object,
        required: true,
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['set-default', 'update', 'delete']);

const { getPaymentMethodIcon, getPaymentMethodName } = useStripe();
const showDeleteConfirm = ref(false);

const getPaymentMethodDetails = (pm) => {
    if (pm.card) {
        return `${pm.card.brand?.toUpperCase() || 'Karte'} •••• ${pm.card.last4}`;
    }
    if (pm.sepa_debit) {
        return `SEPA •••• ${pm.sepa_debit.last4}`;
    }
    if (pm.type === 'sofort') {
        return 'SOFORT Überweisung';
    }
    if (pm.type === 'giropay') {
        return 'Giropay';
    }
    if (pm.type === 'ideal') {
        return 'iDEAL';
    }
    return pm.type || 'Zahlungsmethode';
};

const getPaymentMethodLast4 = (pm) => {
    return pm.card?.last4 || pm.sepa_debit?.last4 || '****';
};

const getCardExpiration = (pm) => {
    if (!pm.card) return '';
    return `${pm.card.exp_month.toString().padStart(2, '0')}/${pm.card.exp_year}`;
};

const isExpiringCard = computed(() => {
    if (!props.paymentMethod.card) return false;

    const now = new Date();
    const currentYear = now.getFullYear();
    const currentMonth = now.getMonth() + 1;

    const expYear = props.paymentMethod.card.exp_year;
    const expMonth = props.paymentMethod.card.exp_month;

    // Warn if expiring within 2 months
    if (expYear === currentYear) {
        return (expMonth - currentMonth) <= 2 && (expMonth - currentMonth) >= 0;
    }
    if (expYear === currentYear + 1 && currentMonth >= 11) {
        return (expMonth + 12 - currentMonth) <= 2;
    }

    return false;
});

const confirmDelete = () => {
    showDeleteConfirm.value = true;
};

const handleDelete = () => {
    showDeleteConfirm.value = false;
    emit('delete', props.paymentMethod.id);
};
</script>
