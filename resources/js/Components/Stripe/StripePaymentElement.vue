<template>
    <div class="stripe-payment-element-wrapper">
        <!-- Element Container -->
        <div
            :id="elementId"
            class="stripe-payment-element"
            :class="[
                elementClasses,
                {
                    'has-error': hasError,
                    'is-complete': isComplete,
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

        <!-- Wallets (Apple Pay / Google Pay) -->
        <div v-if="showWallets && walletsAvailable" class="wallet-divider">
            <span>oder zahlen Sie mit</span>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import { useStripe } from '@/composables/useStripe';
import { formatStripeError } from '@/utils/stripeErrors';

const props = defineProps({
    // Required: Client Secret from PaymentIntent or SetupIntent
    clientSecret: {
        type: String,
        required: true,
    },

    // Styling
    elementClasses: {
        type: String,
        default: '',
    },

    // Appearance (Theme)
    appearance: {
        type: Object,
        default: () => ({
            theme: 'stripe', // 'stripe' | 'night' | 'flat'
        }),
    },

    // Layout
    layout: {
        type: String,
        default: 'tabs', // 'tabs' | 'accordion' | 'auto'
        validator: (value) => ['tabs', 'accordion', 'auto'].includes(value),
    },

    // Payment Method Types
    paymentMethodTypes: {
        type: Array,
        default: () => ['card', 'sepa_debit', 'sofort', 'giropay', 'ideal'],
    },

    // Wallets
    wallets: {
        type: Object,
        default: () => ({
            applePay: 'auto',
            googlePay: 'auto',
        }),
    },
    showWallets: {
        type: Boolean,
        default: true,
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

    // Messages
    showErrorMessage: {
        type: Boolean,
        default: true,
    },
    helperText: {
        type: String,
        default: '',
    },

    // Terms
    terms: {
        type: Object,
        default: () => ({
            card: 'auto',
            sepaDebit: 'auto',
        }),
    },

    // Fields
    fields: {
        type: Object,
        default: () => ({
            billingDetails: 'auto',
        }),
    },

    // Business
    business: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits([
    'ready',
    'change',
    'focus',
    'blur',
    'loaderror',
    'escape',
    'complete',
]);

const { stripe: stripeInstance } = useStripe();

const elementId = `stripe-payment-${Math.random().toString(36).substr(2, 9)}`;
let stripe = null;
let elements = null;
let paymentElement = null;

const isReady = ref(false);
const isComplete = ref(false);
const hasError = ref(false);
const errorMessage = ref('');
const isEmpty = ref(true);
const walletsAvailable = ref(false);

// Element Configuration
const elementsOptions = computed(() => {
    const options = {
        clientSecret: props.clientSecret,
        appearance: {
            theme: props.appearance.theme || 'stripe',
            variables: {
                colorPrimary: '#3b82f6',
                colorBackground: '#ffffff',
                colorText: '#1f2937',
                colorDanger: '#dc2626',
                fontFamily: 'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif',
                spacingUnit: '4px',
                borderRadius: '6px',
                ...props.appearance.variables,
            },
            rules: {
                '.Input': {
                    border: '1px solid #d1d5db',
                    boxShadow: 'none',
                },
                '.Input:focus': {
                    border: '1px solid #3b82f6',
                    boxShadow: '0 0 0 3px rgba(59, 130, 246, 0.1)',
                },
                '.Input--invalid': {
                    border: '1px solid #dc2626',
                },
                '.Label': {
                    fontWeight: '500',
                    marginBottom: '4px',
                },
                ...props.appearance.rules,
            },
        },
        locale: 'de',
    };

    // Add business if provided
    if (props.business) {
        options.business = props.business;
    }

    return options;
});

const paymentElementOptions = computed(() => {
    const options = {
        layout: props.layout,
    };

    // Payment Method Types
    if (props.paymentMethodTypes && props.paymentMethodTypes.length > 0) {
        options.paymentMethodOrder = props.paymentMethodTypes;
    }

    // Wallets
    if (props.wallets) {
        options.wallets = props.wallets;
    }

    // Terms
    if (props.terms) {
        options.terms = props.terms;
    }

    // Fields
    if (props.fields) {
        options.fields = props.fields;
    }

    return options;
});

const initializeElement = async () => {
    try {
        stripe = await stripeInstance.value;
        if (!stripe) {
            console.error('Stripe.js could not be loaded');
            hasError.value = true;
            errorMessage.value = 'Zahlungsformular konnte nicht geladen werden';
            emit('loaderror', new Error('Stripe.js could not be loaded'));
            return;
        }

        if (!props.clientSecret) {
            console.error('Client secret is required');
            hasError.value = true;
            errorMessage.value = 'Zahlungsvorgang konnte nicht initialisiert werden';
            return;
        }

        elements = stripe.elements(elementsOptions.value);

        await nextTick();

        const container = document.getElementById(elementId);
        if (!container) {
            console.error('Payment element container not found');
            return;
        }

        paymentElement = elements.create('payment', paymentElementOptions.value);
        paymentElement.mount(`#${elementId}`);

        // Event Listeners
        paymentElement.on('ready', handleReady);
        paymentElement.on('change', handleChange);
        paymentElement.on('focus', handleFocus);
        paymentElement.on('blur', handleBlur);
        paymentElement.on('escape', handleEscape);
        paymentElement.on('loaderror', handleLoadError);

        if (props.autofocus) {
            paymentElement.focus();
        }

        isReady.value = true;
    } catch (error) {
        console.error('Error initializing Stripe Payment Element:', error);
        hasError.value = true;
        errorMessage.value = 'Element konnte nicht geladen werden';
        emit('loaderror', error);
    }
};

const handleReady = (event) => {
    // Check if wallets are available
    if (event && event.availablePaymentMethods) {
        const methods = event.availablePaymentMethods;
        walletsAvailable.value = methods.includes('apple_pay') || methods.includes('google_pay');
    }
    emit('ready', paymentElement);
};

const handleChange = (event) => {
    isEmpty.value = event.empty;
    isComplete.value = event.complete;

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
    emit('focus');
};

const handleBlur = () => {
    emit('blur');
};

const handleEscape = () => {
    emit('escape');
};

const handleLoadError = (event) => {
    hasError.value = true;
    errorMessage.value = 'Fehler beim Laden der Zahlungsmethoden';
    emit('loaderror', event);
};

// Public Methods (exposed via defineExpose)
const focus = () => {
    if (paymentElement) {
        paymentElement.focus();
    }
};

const blur = () => {
    if (paymentElement) {
        paymentElement.blur();
    }
};

const collapse = () => {
    if (paymentElement) {
        paymentElement.collapse();
    }
};

const update = (options) => {
    if (paymentElement) {
        paymentElement.update(options);
    }
};

const destroy = () => {
    if (paymentElement) {
        paymentElement.off('ready');
        paymentElement.off('change');
        paymentElement.off('focus');
        paymentElement.off('blur');
        paymentElement.off('escape');
        paymentElement.off('loaderror');
        paymentElement.unmount();
        paymentElement.destroy();
        paymentElement = null;
    }
};

const getElement = () => {
    return paymentElement;
};

const getElements = () => {
    return elements;
};

// Watch for clientSecret changes (to support updating the intent)
watch(() => props.clientSecret, async (newValue, oldValue) => {
    if (newValue && newValue !== oldValue && elements) {
        // Update elements with new clientSecret
        try {
            await elements.fetchUpdates();
        } catch (error) {
            console.error('Error updating elements:', error);
        }
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
    collapse,
    update,
    destroy,
    getElement,
    getElements,
    isReady,
    isComplete,
    hasError,
});
</script>

<style scoped>
.stripe-payment-element-wrapper {
    width: 100%;
}

.stripe-payment-element {
    /* Stripe Payment Element handles its own styling */
    min-height: 40px;
}

.stripe-payment-element.is-disabled {
    opacity: 0.6;
    pointer-events: none;
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

.wallet-divider {
    margin-top: 1.5rem;
    margin-bottom: 0.5rem;
    text-align: center;
    position: relative;
}

.wallet-divider::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background-color: #e5e7eb;
    z-index: 0;
}

.wallet-divider span {
    position: relative;
    z-index: 1;
    padding: 0 1rem;
    background-color: #ffffff;
    font-size: 0.875rem;
    color: #6b7280;
}
</style>
