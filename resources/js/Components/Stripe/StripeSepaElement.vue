<template>
    <div class="stripe-sepa-element-wrapper">
        <!-- Element Container -->
        <div
            :id="elementId"
            class="stripe-sepa-element"
            :class="[
                elementClasses,
                {
                    'has-error': hasError,
                    'is-complete': isComplete,
                    'is-focused': isFocused,
                    'is-disabled': disabled,
                }
            ]"
        ></div>

        <!-- Error Message -->
        <p v-if="hasError && showErrorMessage" class="stripe-error-message">
            {{ errorMessage }}
        </p>

        <!-- Helper Text -->
        <p v-if="helperText && !hasError" class="stripe-helper-text">
            {{ helperText }}
        </p>

        <!-- SEPA Mandate Text -->
        <div v-if="showMandate && isComplete" class="sepa-mandate">
            <div class="mandate-header">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h4 class="mandate-title">SEPA-Lastschriftmandat</h4>
            </div>
            <p class="mandate-text">
                {{ mandateText || getDefaultMandateText() }}
            </p>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useStripe } from '@/composables/useStripe';
import { formatStripeError } from '@/utils/stripeErrors';

const page = usePage();

const props = defineProps({
    // Styling
    elementClasses: {
        type: String,
        default: '',
    },

    // Behavior
    disabled: {
        type: Boolean,
        default: false,
    },
    autofocus: {
        type: Boolean,
        default: false,
    },
    supportedCountries: {
        type: Array,
        default: () => ['SEPA'], // SEPA countries
    },
    placeholderCountry: {
        type: String,
        default: 'DE',
    },

    // Messages
    showErrorMessage: {
        type: Boolean,
        default: true,
    },
    helperText: {
        type: String,
        default: '',
    },

    // SEPA Mandate
    showMandate: {
        type: Boolean,
        default: true,
    },
    mandateText: {
        type: String,
        default: '',
    },
    merchantName: {
        type: String,
        default: undefined,
    },

    // Custom Styling
    customStyle: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits([
    'ready',
    'change',
    'focus',
    'blur',
    'escape',
    'error',
    'complete',
]);

const { stripe: stripeInstance } = useStripe();

// Computed value for merchant name with proper fallback chain
const displayMerchantName = computed(() => props.merchantName || page.props.appName || 'BasketManager Pro');

const elementId = `stripe-sepa-${Math.random().toString(36).substr(2, 9)}`;
let stripe = null;
let elements = null;
let ibanElement = null;

const isReady = ref(false);
const isFocused = ref(false);
const isComplete = ref(false);
const hasError = ref(false);
const errorMessage = ref('');
const isEmpty = ref(true);
const bankName = ref('');
const country = ref('');

// Element Configuration
const elementOptions = computed(() => {
    const baseStyle = {
        base: {
            fontSize: '16px',
            color: '#1f2937',
            fontFamily: 'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif',
            fontSmoothing: 'antialiased',
            '::placeholder': {
                color: '#9ca3af',
            },
            ':-webkit-autofill': {
                color: '#1f2937',
            },
        },
        invalid: {
            color: '#dc2626',
            iconColor: '#dc2626',
            '::placeholder': {
                color: '#ef4444',
            },
        },
        complete: {
            color: '#059669',
            iconColor: '#059669',
        },
    };

    // Merge custom styles
    const style = Object.keys(props.customStyle).length > 0
        ? {
            base: { ...baseStyle.base, ...props.customStyle.base },
            invalid: { ...baseStyle.invalid, ...props.customStyle.invalid },
            complete: { ...baseStyle.complete, ...props.customStyle.complete },
        }
        : baseStyle;

    return {
        style,
        supportedCountries: props.supportedCountries,
        placeholderCountry: props.placeholderCountry,
        disabled: props.disabled,
    };
});

const getDefaultMandateText = () => {
    return `Durch Angabe Ihrer IBAN und Bestätigung dieser Zahlung ermächtigen Sie ${displayMerchantName.value} und Stripe, unserem Zahlungsdienstleister, eine Anweisung an Ihre Bank zu senden, Ihr Konto zu belasten, sowie Ihre Bank, Ihr Konto entsprechend dieser Anweisung zu belasten. Sie haben Anspruch auf Erstattung von Ihrer Bank gemäß den Bedingungen Ihres Vertrages mit Ihrer Bank. Eine Erstattung muss innerhalb von 8 Wochen ab dem Datum der Belastung Ihres Kontos beantragt werden.`;
};

const initializeElement = async () => {
    try {
        stripe = await stripeInstance.value;
        if (!stripe) {
            console.error('Stripe.js could not be loaded');
            return;
        }

        elements = stripe.elements({
            locale: 'de',
        });

        await nextTick();

        const container = document.getElementById(elementId);
        if (!container) {
            console.error('SEPA element container not found');
            return;
        }

        ibanElement = elements.create('iban', elementOptions.value);
        ibanElement.mount(`#${elementId}`);

        // Event Listeners
        ibanElement.on('ready', handleReady);
        ibanElement.on('change', handleChange);
        ibanElement.on('focus', handleFocus);
        ibanElement.on('blur', handleBlur);
        ibanElement.on('escape', handleEscape);

        if (props.autofocus) {
            ibanElement.focus();
        }

        isReady.value = true;
    } catch (error) {
        console.error('Error initializing Stripe SEPA Element:', error);
        hasError.value = true;
        errorMessage.value = 'Element konnte nicht geladen werden';
    }
};

const handleReady = () => {
    emit('ready', ibanElement);
};

const handleChange = (event) => {
    isEmpty.value = event.empty;
    isComplete.value = event.complete;
    bankName.value = event.bankName || '';
    country.value = event.country || '';

    if (event.error) {
        hasError.value = true;
        errorMessage.value = formatStripeError(event.error);
        emit('error', event.error);
    } else {
        hasError.value = false;
        errorMessage.value = '';
    }

    if (event.complete) {
        emit('complete', event);
    }

    emit('change', event);
};

const handleFocus = () => {
    isFocused.value = true;
    emit('focus');
};

const handleBlur = () => {
    isFocused.value = false;
    emit('blur');
};

const handleEscape = () => {
    emit('escape');
};

// Public Methods (exposed via defineExpose)
const focus = () => {
    if (ibanElement) {
        ibanElement.focus();
    }
};

const blur = () => {
    if (ibanElement) {
        ibanElement.blur();
    }
};

const clear = () => {
    if (ibanElement) {
        ibanElement.clear();
        isComplete.value = false;
        hasError.value = false;
        errorMessage.value = '';
        isEmpty.value = true;
        bankName.value = '';
        country.value = '';
    }
};

const update = (options) => {
    if (ibanElement) {
        ibanElement.update(options);
    }
};

const destroy = () => {
    if (ibanElement) {
        ibanElement.off('ready');
        ibanElement.off('change');
        ibanElement.off('focus');
        ibanElement.off('blur');
        ibanElement.off('escape');
        ibanElement.unmount();
        ibanElement.destroy();
        ibanElement = null;
    }
};

const getElement = () => {
    return ibanElement;
};

// Watch for disabled prop changes
watch(() => props.disabled, (newValue) => {
    if (ibanElement) {
        ibanElement.update({ disabled: newValue });
    }
});

onMounted(() => {
    initializeElement();
});

onBeforeUnmount(() => {
    destroy();
});

defineExpose({
    focus,
    blur,
    clear,
    update,
    destroy,
    getElement,
    isReady,
    isComplete,
    hasError,
    bankName,
    country,
});
</script>

<style scoped>
.stripe-sepa-element-wrapper {
    width: 100%;
}

.stripe-sepa-element {
    padding: 0.75rem 1rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    background-color: #ffffff;
    transition: all 0.2s ease;
}

.stripe-sepa-element.is-focused {
    outline: none;
    border-color: #3b82f6;
    ring: 2px;
    ring-color: rgba(59, 130, 246, 0.5);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.stripe-sepa-element.has-error {
    border-color: #dc2626;
    background-color: #fef2f2;
}

.stripe-sepa-element.is-complete {
    border-color: #059669;
}

.stripe-sepa-element.is-disabled {
    background-color: #f3f4f6;
    cursor: not-allowed;
    opacity: 0.6;
}

.stripe-error-message {
    margin-top: 0.5rem;
    font-size: 0.875rem;
    color: #dc2626;
}

.stripe-helper-text {
    margin-top: 0.5rem;
    font-size: 0.875rem;
    color: #6b7280;
}

.sepa-mandate {
    margin-top: 1rem;
    padding: 1rem;
    background-color: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 0.5rem;
}

.mandate-header {
    display: flex;
    align-items: center;
    margin-bottom: 0.5rem;
}

.mandate-title {
    margin-left: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #1e40af;
}

.mandate-text {
    font-size: 0.75rem;
    line-height: 1.5;
    color: #1e3a8a;
}
</style>
