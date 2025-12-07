<script setup>
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';

const props = defineProps({
    clubId: { type: String, required: true },
    planId: { type: String, default: null },
    modelValue: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
    showLabel: { type: Boolean, default: true },
    label: { type: String, default: 'Voucher-Code' },
    placeholder: { type: String, default: 'Code eingeben...' },
});

const emit = defineEmits(['update:modelValue', 'voucher-validated', 'voucher-cleared', 'voucher-error']);

const code = ref(props.modelValue);
const isValidating = ref(false);
const validatedVoucher = ref(null);
const errorMessage = ref('');
const debounceTimer = ref(null);

const isValid = computed(() => validatedVoucher.value !== null);
const hasError = computed(() => errorMessage.value !== '');

watch(() => props.modelValue, (newVal) => {
    if (newVal !== code.value) {
        code.value = newVal;
    }
});

watch(code, (newCode) => {
    emit('update:modelValue', newCode);

    // Clear previous state
    if (debounceTimer.value) {
        clearTimeout(debounceTimer.value);
    }

    // Clear validation if code is empty
    if (!newCode || newCode.trim() === '') {
        validatedVoucher.value = null;
        errorMessage.value = '';
        emit('voucher-cleared');
        return;
    }

    // Debounce validation
    debounceTimer.value = setTimeout(() => {
        validateCode(newCode.trim().toUpperCase());
    }, 500);
});

const validateCode = async (voucherCode) => {
    if (!voucherCode) return;

    isValidating.value = true;
    errorMessage.value = '';
    validatedVoucher.value = null;

    try {
        const params = { code: voucherCode };
        if (props.planId) {
            params.plan_id = props.planId;
        }

        const response = await axios.post(route('club.vouchers.validate', props.clubId), params);

        if (response.data.valid) {
            validatedVoucher.value = response.data.voucher;
            emit('voucher-validated', response.data.voucher);
        } else {
            errorMessage.value = response.data.message || 'Ungültiger Voucher-Code';
            emit('voucher-error', errorMessage.value);
        }
    } catch (error) {
        if (error.response?.data?.message) {
            errorMessage.value = error.response.data.message;
        } else if (error.response?.status === 422) {
            const errors = error.response.data.errors;
            errorMessage.value = errors?.code?.[0] || 'Validierungsfehler';
        } else {
            errorMessage.value = 'Fehler bei der Validierung';
        }
        emit('voucher-error', errorMessage.value);
    } finally {
        isValidating.value = false;
    }
};

const clearVoucher = () => {
    code.value = '';
    validatedVoucher.value = null;
    errorMessage.value = '';
    emit('update:modelValue', '');
    emit('voucher-cleared');
};

const getDiscountLabel = (voucher) => {
    if (voucher.type === 'percent') {
        return `${voucher.discount_percent}% Rabatt`;
    } else if (voucher.type === 'fixed_amount') {
        return `${voucher.discount_amount} EUR Rabatt`;
    } else if (voucher.type === 'trial_extension') {
        return `${voucher.trial_extension_days} Tage extra Trial`;
    }
    return '';
};

const getDurationLabel = (voucher) => {
    if (voucher.type === 'trial_extension') {
        return 'Einmalig';
    }
    if (voucher.duration_months === 1) {
        return '1 Monat';
    }
    return `${voucher.duration_months} Monate`;
};
</script>

<template>
    <div class="voucher-input">
        <InputLabel v-if="showLabel" :for="'voucher-code-' + clubId" :value="label" />

        <div class="mt-1 relative">
            <div class="flex rounded-md shadow-sm">
                <TextInput
                    :id="'voucher-code-' + clubId"
                    v-model="code"
                    type="text"
                    :disabled="disabled || isValidating"
                    :placeholder="placeholder"
                    class="flex-1 rounded-r-none uppercase"
                    :class="{
                        'border-green-500 focus:border-green-500 focus:ring-green-500': isValid,
                        'border-red-500 focus:border-red-500 focus:ring-red-500': hasError,
                    }"
                    @keyup.enter.prevent
                />
                <button
                    v-if="code && !isValidating"
                    type="button"
                    @click="clearVoucher"
                    class="inline-flex items-center px-3 border border-l-0 border-gray-300 rounded-r-md bg-gray-50 text-gray-500 hover:text-gray-700 hover:bg-gray-100"
                    :class="{
                        'border-green-500': isValid,
                        'border-red-500': hasError,
                    }"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                <div
                    v-else-if="isValidating"
                    class="inline-flex items-center px-3 border border-l-0 border-gray-300 rounded-r-md bg-gray-50"
                >
                    <svg class="animate-spin h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <span
                    v-else
                    class="inline-flex items-center px-3 border border-l-0 border-gray-300 rounded-r-md bg-gray-50 text-gray-400"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                </span>
            </div>

            <!-- Success State -->
            <div v-if="isValid && validatedVoucher" class="mt-2 p-3 bg-green-50 border border-green-200 rounded-md">
                <div class="flex items-start">
                    <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-green-800">
                            {{ validatedVoucher.name }}
                        </p>
                        <div class="mt-1 flex flex-wrap gap-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                {{ getDiscountLabel(validatedVoucher) }}
                            </span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                {{ getDurationLabel(validatedVoucher) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Error State -->
            <div v-if="hasError" class="mt-2 p-3 bg-red-50 border border-red-200 rounded-md">
                <div class="flex items-center">
                    <svg class="h-5 w-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm text-red-700">{{ errorMessage }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
