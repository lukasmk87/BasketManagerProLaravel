<template>
    <div class="stripe-card-element-wrapper">
        <!-- Element Container -->
        <div
            :id="elementId"
            class="stripe-card-element"
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
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import { useStripe } from '@/Composables/core/useStripe';
import { formatStripeError } from '@/utils/stripeErrors';

const props = defineProps({
    // Styling
    elementClasses: {
        type: String,
        default: '',
    },
    hidePostalCode: {
        type: Boolean,
        default: false,
    },
    hideIcon: {
        type: Boolean,
        default: false,
    },
    iconStyle: {
        type: String,
        default: 'default', // 'default' | 'solid'
        validator: (value) => ['default', 'solid'].includes(value),
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

    // Custom Placeholder
    placeholder: {
        type: Object,
        default: () => ({}),
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

const elementId = `stripe-card-${Math.random().toString(36).substr(2, 9)}`;
let stripe = null;
let elements = null;
let cardElement = null;

const isReady = ref(false);
const isFocused = ref(false);
const isComplete = ref(false);
const hasError = ref(false);
const errorMessage = ref('');
const brand = ref('');
const isEmpty = ref(true);

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
        hidePostalCode: props.hidePostalCode,
        hideIcon: props.hideIcon,
        iconStyle: props.iconStyle,
        disabled: props.disabled,
        ...props.placeholder,
    };
});

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
            console.error('Card element container not found');
            return;
        }

        cardElement = elements.create('card', elementOptions.value);
        cardElement.mount(`#${elementId}`);

        // Event Listeners
        cardElement.on('ready', handleReady);
        cardElement.on('change', handleChange);
        cardElement.on('focus', handleFocus);
        cardElement.on('blur', handleBlur);
        cardElement.on('escape', handleEscape);

        if (props.autofocus) {
            cardElement.focus();
        }

        isReady.value = true;
    } catch (error) {
        console.error('Error initializing Stripe Card Element:', error);
        hasError.value = true;
        errorMessage.value = 'Element konnte nicht geladen werden';
    }
};

const handleReady = () => {
    emit('ready', cardElement);
};

const handleChange = (event) => {
    isEmpty.value = event.empty;
    isComplete.value = event.complete;
    brand.value = event.brand || '';

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
    if (cardElement) {
        cardElement.focus();
    }
};

const blur = () => {
    if (cardElement) {
        cardElement.blur();
    }
};

const clear = () => {
    if (cardElement) {
        cardElement.clear();
        isComplete.value = false;
        hasError.value = false;
        errorMessage.value = '';
        isEmpty.value = true;
    }
};

const update = (options) => {
    if (cardElement) {
        cardElement.update(options);
    }
};

const destroy = () => {
    if (cardElement) {
        cardElement.off('ready');
        cardElement.off('change');
        cardElement.off('focus');
        cardElement.off('blur');
        cardElement.off('escape');
        cardElement.unmount();
        cardElement.destroy();
        cardElement = null;
    }
};

const getElement = () => {
    return cardElement;
};

// Watch for disabled prop changes
watch(() => props.disabled, (newValue) => {
    if (cardElement) {
        cardElement.update({ disabled: newValue });
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
    brand,
});
</script>

<style scoped>
.stripe-card-element-wrapper {
    width: 100%;
}

.stripe-card-element {
    padding: 0.75rem 1rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    background-color: #ffffff;
    transition: all 0.2s ease;
}

.stripe-card-element.is-focused {
    outline: none;
    border-color: #3b82f6;
    ring: 2px;
    ring-color: rgba(59, 130, 246, 0.5);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.stripe-card-element.has-error {
    border-color: #dc2626;
    background-color: #fef2f2;
}

.stripe-card-element.is-complete {
    border-color: #059669;
}

.stripe-card-element.is-disabled {
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
</style>
