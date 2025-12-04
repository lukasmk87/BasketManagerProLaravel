<template>
    <teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div
                    class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                    @click="handleClose"
                ></div>

                <!-- Modal panel -->
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-gray-700 to-gray-800 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <svg class="w-6 h-6 text-white mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                <h3 class="text-lg font-semibold text-white" id="modal-title">
                                    Rechnungsinformationen bearbeiten
                                </h3>
                            </div>
                            <button
                                @click="handleClose"
                                class="text-white hover:text-gray-200 transition-colors"
                            >
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Body -->
                    <form @submit.prevent="handleSubmit" class="bg-white px-6 py-5">
                        <!-- Payment Method Info -->
                        <div v-if="paymentMethod" class="mb-6 p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center">
                                <span class="text-2xl mr-3">{{ getPaymentMethodIcon() }}</span>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ getPaymentMethodName() }}</p>
                                    <p class="text-xs text-gray-600">{{ getPaymentMethodDetails() }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Billing Details Form -->
                        <div class="space-y-4">
                            <!-- Name -->
                            <div>
                                <label for="update-name" class="block text-sm font-medium text-gray-700 mb-1">
                                    Name <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="update-name"
                                    v-model="form.name"
                                    type="text"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                    :class="{ 'border-red-300': errors.name }"
                                    placeholder="Max Mustermann"
                                />
                                <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</p>
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="update-email" class="block text-sm font-medium text-gray-700 mb-1">
                                    E-Mail <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="update-email"
                                    v-model="form.email"
                                    type="email"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                    :class="{ 'border-red-300': errors.email }"
                                    placeholder="max@beispiel.de"
                                />
                                <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email }}</p>
                            </div>

                            <!-- Phone (optional) -->
                            <div>
                                <label for="update-phone" class="block text-sm font-medium text-gray-700 mb-1">
                                    Telefon <span class="text-gray-400 text-xs">(optional)</span>
                                </label>
                                <input
                                    id="update-phone"
                                    v-model="form.phone"
                                    type="tel"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="+49 123 456789"
                                />
                            </div>

                            <!-- Address Section -->
                            <div class="pt-4 border-t border-gray-200">
                                <h4 class="text-sm font-medium text-gray-900 mb-3">Adresse</h4>

                                <!-- Street -->
                                <div class="mb-3">
                                    <label for="update-street" class="block text-sm font-medium text-gray-700 mb-1">
                                        Straße & Hausnummer
                                    </label>
                                    <input
                                        id="update-street"
                                        v-model="form.address.line1"
                                        type="text"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Musterstraße 123"
                                    />
                                </div>

                                <!-- Line 2 (optional) -->
                                <div class="mb-3">
                                    <label for="update-line2" class="block text-sm font-medium text-gray-700 mb-1">
                                        Adresszusatz <span class="text-gray-400 text-xs">(optional)</span>
                                    </label>
                                    <input
                                        id="update-line2"
                                        v-model="form.address.line2"
                                        type="text"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Apartment, Suite, etc."
                                    />
                                </div>

                                <!-- City & Postal Code -->
                                <div class="grid grid-cols-2 gap-3 mb-3">
                                    <div>
                                        <label for="update-postal" class="block text-sm font-medium text-gray-700 mb-1">
                                            PLZ
                                        </label>
                                        <input
                                            id="update-postal"
                                            v-model="form.address.postal_code"
                                            type="text"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="12345"
                                        />
                                    </div>
                                    <div>
                                        <label for="update-city" class="block text-sm font-medium text-gray-700 mb-1">
                                            Stadt
                                        </label>
                                        <input
                                            id="update-city"
                                            v-model="form.address.city"
                                            type="text"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="Berlin"
                                        />
                                    </div>
                                </div>

                                <!-- State & Country -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label for="update-state" class="block text-sm font-medium text-gray-700 mb-1">
                                            Bundesland <span class="text-gray-400 text-xs">(optional)</span>
                                        </label>
                                        <input
                                            id="update-state"
                                            v-model="form.address.state"
                                            type="text"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="Bayern"
                                        />
                                    </div>
                                    <div>
                                        <label for="update-country" class="block text-sm font-medium text-gray-700 mb-1">
                                            Land
                                        </label>
                                        <select
                                            id="update-country"
                                            v-model="form.address.country"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                        >
                                            <option value="DE">Deutschland</option>
                                            <option value="AT">Österreich</option>
                                            <option value="CH">Schweiz</option>
                                            <option value="FR">Frankreich</option>
                                            <option value="IT">Italien</option>
                                            <option value="NL">Niederlande</option>
                                            <option value="BE">Belgien</option>
                                            <option value="PL">Polen</option>
                                            <option value="CZ">Tschechien</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- General Error -->
                        <div v-if="errors.general" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-md">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-red-600 mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                <p class="text-sm text-red-800">{{ errors.general }}</p>
                            </div>
                        </div>

                        <!-- Success Message -->
                        <div v-if="showSuccess" class="mt-4 p-3 bg-green-50 border border-green-200 rounded-md">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-green-600 mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <p class="text-sm text-green-800">Rechnungsinformationen erfolgreich aktualisiert!</p>
                            </div>
                        </div>
                    </form>

                    <!-- Footer -->
                    <div class="bg-gray-50 px-6 py-4 flex items-center justify-end space-x-3">
                        <button
                            type="button"
                            @click="handleClose"
                            class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            :disabled="processing"
                        >
                            Abbrechen
                        </button>
                        <button
                            type="button"
                            @click="handleSubmit"
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="processing || !isFormValid"
                        >
                            <svg v-if="processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                            </svg>
                            {{ processing ? 'Wird gespeichert...' : 'Änderungen speichern' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useStripe } from '@/Composables/core/useStripe';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    clubId: {
        type: [Number, String],
        required: true,
    },
    paymentMethod: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'updated']);

const { getPaymentMethodIcon: getIcon, getPaymentMethodName: getName } = useStripe();

const form = ref({
    name: '',
    email: '',
    phone: '',
    address: {
        line1: '',
        line2: '',
        city: '',
        postal_code: '',
        state: '',
        country: 'DE',
    },
});

const processing = ref(false);
const errors = ref({});
const showSuccess = ref(false);

const isFormValid = computed(() => {
    return form.value.name && form.value.email;
});

const getPaymentMethodIcon = () => {
    if (!props.paymentMethod) return '💳';
    return getIcon(props.paymentMethod.type);
};

const getPaymentMethodName = () => {
    if (!props.paymentMethod) return '';
    return getName(props.paymentMethod.type);
};

const getPaymentMethodDetails = () => {
    if (!props.paymentMethod) return '';

    const pm = props.paymentMethod;
    if (pm.card) {
        return `${pm.card.brand?.toUpperCase() || 'Karte'} •••• ${pm.card.last4}`;
    }
    if (pm.sepa_debit) {
        return `SEPA •••• ${pm.sepa_debit.last4}`;
    }
    return pm.type || 'Zahlungsmethode';
};

const loadPaymentMethod = () => {
    if (!props.paymentMethod) return;

    const bd = props.paymentMethod.billing_details || {};
    const addr = bd.address || {};

    form.value = {
        name: bd.name || '',
        email: bd.email || '',
        phone: bd.phone || '',
        address: {
            line1: addr.line1 || '',
            line2: addr.line2 || '',
            city: addr.city || '',
            postal_code: addr.postal_code || '',
            state: addr.state || '',
            country: addr.country || 'DE',
        },
    };
};

const validateForm = () => {
    errors.value = {};

    if (!form.value.name) {
        errors.value.name = 'Name ist erforderlich';
    }

    if (!form.value.email) {
        errors.value.email = 'E-Mail ist erforderlich';
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)) {
        errors.value.email = 'Bitte geben Sie eine gültige E-Mail-Adresse ein';
    }

    return Object.keys(errors.value).length === 0;
};

const handleSubmit = async () => {
    if (!validateForm() || !props.paymentMethod) {
        return;
    }

    processing.value = true;
    errors.value = {};
    showSuccess.value = false;

    try {
        const billingDetails = {
            name: form.value.name,
            email: form.value.email,
            phone: form.value.phone || undefined,
            address: {
                line1: form.value.address.line1 || undefined,
                line2: form.value.address.line2 || undefined,
                city: form.value.address.city || undefined,
                postal_code: form.value.address.postal_code || undefined,
                state: form.value.address.state || undefined,
                country: form.value.address.country,
            },
        };

        await axios.put(
            route('club.billing.payment-methods.update', {
                club: props.clubId,
                paymentMethod: props.paymentMethod.id,
            }),
            { billing_details: billingDetails }
        );

        showSuccess.value = true;

        // Emit update event
        emit('updated');

        // Auto-close after 1.5 seconds
        setTimeout(() => {
            if (showSuccess.value) {
                handleClose();
            }
        }, 1500);
    } catch (error) {
        console.error('Update billing details error:', error);
        errors.value.general = error.response?.data?.message || 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.';
    } finally {
        processing.value = false;
    }
};

const handleClose = () => {
    if (!processing.value) {
        showSuccess.value = false;
        errors.value = {};
        emit('close');
    }
};

// Watch for payment method changes
watch(() => props.paymentMethod, (newValue) => {
    if (newValue) {
        loadPaymentMethod();
    }
}, { immediate: true });

// Watch for modal show
watch(() => props.show, (newValue) => {
    if (newValue) {
        loadPaymentMethod();
        showSuccess.value = false;
        errors.value = {};
    }
});
</script>
