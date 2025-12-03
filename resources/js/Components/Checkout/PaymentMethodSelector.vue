<script setup>
import { ref, watch, onMounted } from 'vue';
import PaymentMethodIcon from '@/Components/Stripe/PaymentMethodIcon.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: 'card',
    },
    club: {
        type: Object,
        required: true,
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['update:modelValue', 'invoice-data-change']);

const selectedMethod = ref(props.modelValue);
const invoiceForm = ref({
    billing_name: '',
    billing_email: '',
    billing_address: {
        street: '',
        city: '',
        postal_code: '',
        country: 'DE',
    },
    vat_number: '',
});

// Pre-fill from club data
onMounted(() => {
    invoiceForm.value.billing_name = props.club?.name || '';
    invoiceForm.value.billing_email = props.club?.email || '';
});

watch(selectedMethod, (value) => {
    emit('update:modelValue', value);
});

watch(invoiceForm, (value) => {
    emit('invoice-data-change', value);
}, { deep: true });

const selectMethod = (method) => {
    selectedMethod.value = method;
};
</script>

<template>
    <div class="space-y-4">
        <h4 class="text-sm font-semibold text-gray-900">Zahlungsmethode wählen</h4>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Card/SEPA Option -->
            <button
                type="button"
                @click="selectMethod('card')"
                :class="[
                    'relative flex flex-col items-center p-4 rounded-lg border-2 transition-all duration-200 cursor-pointer',
                    selectedMethod === 'card'
                        ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-500 ring-offset-2'
                        : 'border-gray-200 hover:border-gray-300 bg-white'
                ]"
            >
                <div class="flex items-center gap-2 mb-2">
                    <PaymentMethodIcon type="visa" size="sm" />
                    <PaymentMethodIcon type="mastercard" size="sm" />
                    <PaymentMethodIcon type="sepa" size="sm" />
                </div>
                <span class="text-sm font-medium text-gray-900">Kreditkarte / SEPA</span>
                <span class="text-xs text-gray-500 mt-1">Sofortige Aktivierung</span>
                <div
                    v-if="selectedMethod === 'card'"
                    class="absolute top-2 right-2"
                >
                    <svg class="w-5 h-5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
            </button>

            <!-- Invoice Option -->
            <button
                type="button"
                @click="selectMethod('invoice')"
                :class="[
                    'relative flex flex-col items-center p-4 rounded-lg border-2 transition-all duration-200 cursor-pointer',
                    selectedMethod === 'invoice'
                        ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-500 ring-offset-2'
                        : 'border-gray-200 hover:border-gray-300 bg-white'
                ]"
            >
                <div class="flex items-center justify-center mb-2 w-[80px] h-[21px]">
                    <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <span class="text-sm font-medium text-gray-900">Auf Rechnung</span>
                <span class="text-xs text-gray-500 mt-1">Manuelle Prüfung erforderlich</span>
                <div
                    v-if="selectedMethod === 'invoice'"
                    class="absolute top-2 right-2"
                >
                    <svg class="w-5 h-5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
            </button>
        </div>

        <!-- Invoice Form (only visible when invoice is selected) -->
        <transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 max-h-0"
            enter-to-class="opacity-100 max-h-[500px]"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 max-h-[500px]"
            leave-to-class="opacity-0 max-h-0"
        >
            <div v-if="selectedMethod === 'invoice'" class="overflow-hidden">
                <div class="mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200 space-y-4">
                    <div class="flex items-start gap-2 text-sm text-amber-700 bg-amber-50 p-3 rounded-md">
                        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <span>Rechnungszahlung erfordert eine manuelle Prüfung. Ihr Abo wird nach Zahlungseingang aktiviert.</span>
                    </div>

                    <h5 class="text-sm font-semibold text-gray-900">Rechnungsadresse</h5>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Billing Name -->
                        <div>
                            <InputLabel for="billing_name" value="Rechnungsempfänger *" />
                            <TextInput
                                id="billing_name"
                                v-model="invoiceForm.billing_name"
                                type="text"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="errors.billing_name" class="mt-1" />
                        </div>

                        <!-- Billing Email -->
                        <div>
                            <InputLabel for="billing_email" value="Rechnungs-E-Mail *" />
                            <TextInput
                                id="billing_email"
                                v-model="invoiceForm.billing_email"
                                type="email"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="errors.billing_email" class="mt-1" />
                        </div>

                        <!-- Street -->
                        <div class="sm:col-span-2">
                            <InputLabel for="billing_street" value="Straße und Hausnummer" />
                            <TextInput
                                id="billing_street"
                                v-model="invoiceForm.billing_address.street"
                                type="text"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="errors['billing_address.street']" class="mt-1" />
                        </div>

                        <!-- Postal Code -->
                        <div>
                            <InputLabel for="billing_postal_code" value="PLZ" />
                            <TextInput
                                id="billing_postal_code"
                                v-model="invoiceForm.billing_address.postal_code"
                                type="text"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="errors['billing_address.postal_code']" class="mt-1" />
                        </div>

                        <!-- City -->
                        <div>
                            <InputLabel for="billing_city" value="Stadt" />
                            <TextInput
                                id="billing_city"
                                v-model="invoiceForm.billing_address.city"
                                type="text"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="errors['billing_address.city']" class="mt-1" />
                        </div>

                        <!-- Country -->
                        <div>
                            <InputLabel for="billing_country" value="Land" />
                            <select
                                id="billing_country"
                                v-model="invoiceForm.billing_address.country"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            >
                                <option value="DE">Deutschland</option>
                                <option value="AT">Österreich</option>
                                <option value="CH">Schweiz</option>
                            </select>
                            <InputError :message="errors['billing_address.country']" class="mt-1" />
                        </div>

                        <!-- VAT Number -->
                        <div>
                            <InputLabel for="vat_number" value="USt-IdNr. (optional)" />
                            <TextInput
                                id="vat_number"
                                v-model="invoiceForm.vat_number"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="DE123456789"
                            />
                            <InputError :message="errors.vat_number" class="mt-1" />
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>
