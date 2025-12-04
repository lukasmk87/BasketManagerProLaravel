import { ref, computed } from 'vue';
import { loadStripe } from '@stripe/stripe-js';

/**
 * Composable for Stripe integration.
 *
 * Provides easy access to Stripe instance and helper methods for:
 * - Loading Stripe with publishable key
 * - Creating Stripe Elements
 * - Handling payment methods
 * - Managing checkout sessions
 *
 * @returns {Object} Stripe instance and helper methods
 */
export function useStripe() {
    const stripe = ref(null);
    const isLoading = ref(false);
    const error = ref(null);

    const stripeKey = import.meta.env.VITE_STRIPE_KEY;

    /**
     * Initialize Stripe instance.
     *
     * @returns {Promise<Object>} Stripe instance
     */
    const initStripe = async () => {
        if (stripe.value) {
            return stripe.value;
        }

        if (!stripeKey) {
            error.value = 'Stripe publishable key is not configured';
            console.error('VITE_STRIPE_KEY is not set in environment variables');
            return null;
        }

        try {
            isLoading.value = true;
            error.value = null;

            stripe.value = await loadStripe(stripeKey);

            if (!stripe.value) {
                throw new Error('Failed to load Stripe');
            }

            return stripe.value;
        } catch (e) {
            error.value = e.message;
            console.error('Failed to initialize Stripe:', e);
            return null;
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * Create Stripe Elements instance.
     *
     * @param {Object} options - Elements options (locale, fonts, etc.)
     * @returns {Promise<Object>} Stripe Elements instance
     */
    const createElements = async (options = {}) => {
        const stripeInstance = await initStripe();
        if (!stripeInstance) return null;

        const defaultOptions = {
            locale: 'de',
            ...options,
        };

        return stripeInstance.elements(defaultOptions);
    };

    /**
     * Redirect to Stripe Checkout.
     *
     * @param {String} checkoutUrl - Stripe Checkout URL
     */
    const redirectToCheckout = (checkoutUrl) => {
        if (!checkoutUrl) {
            error.value = 'No checkout URL provided';
            return;
        }

        window.location.href = checkoutUrl;
    };

    /**
     * Confirm card payment (for 3D Secure).
     *
     * @param {String} clientSecret - Payment Intent client secret
     * @param {Object} paymentMethod - Payment method details
     * @returns {Promise<Object>} Payment result
     */
    const confirmCardPayment = async (clientSecret, paymentMethod = {}) => {
        const stripeInstance = await initStripe();
        if (!stripeInstance) return { error: { message: 'Stripe not initialized' } };

        try {
            isLoading.value = true;
            error.value = null;

            const result = await stripeInstance.confirmCardPayment(clientSecret, paymentMethod);

            if (result.error) {
                error.value = result.error.message;
            }

            return result;
        } catch (e) {
            error.value = e.message;
            return { error: { message: e.message } };
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * Confirm Setup Intent (for adding payment methods).
     *
     * @param {String} clientSecret - Setup Intent client secret
     * @param {Object} paymentMethod - Payment method details
     * @returns {Promise<Object>} Setup result
     */
    const confirmCardSetup = async (clientSecret, paymentMethod = {}) => {
        const stripeInstance = await initStripe();
        if (!stripeInstance) return { error: { message: 'Stripe not initialized' } };

        try {
            isLoading.value = true;
            error.value = null;

            const result = await stripeInstance.confirmCardSetup(clientSecret, paymentMethod);

            if (result.error) {
                error.value = result.error.message;
            }

            return result;
        } catch (e) {
            error.value = e.message;
            return { error: { message: e.message } };
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * Confirm SEPA Debit Setup (for SEPA payment methods).
     *
     * @param {String} clientSecret - Setup Intent client secret
     * @param {Object} paymentMethod - Payment method details
     * @returns {Promise<Object>} Setup result
     */
    const confirmSepaDebitSetup = async (clientSecret, paymentMethod = {}) => {
        const stripeInstance = await initStripe();
        if (!stripeInstance) return { error: { message: 'Stripe not initialized' } };

        try {
            isLoading.value = true;
            error.value = null;

            const result = await stripeInstance.confirmSepaDebitSetup(clientSecret, paymentMethod);

            if (result.error) {
                error.value = result.error.message;
            }

            return result;
        } catch (e) {
            error.value = e.message;
            return { error: { message: e.message } };
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * Format amount in cents to currency display.
     *
     * @param {Number} amountInCents - Amount in cents
     * @param {String} currency - Currency code (EUR, USD, etc.)
     * @returns {String} Formatted amount
     */
    const formatAmount = (amountInCents, currency = 'EUR') => {
        const amount = amountInCents / 100;
        return new Intl.NumberFormat('de-DE', {
            style: 'currency',
            currency: currency.toUpperCase(),
        }).format(amount);
    };

    /**
     * Get payment method icon based on type.
     *
     * @param {String} type - Payment method type
     * @returns {String} Icon name or emoji
     */
    const getPaymentMethodIcon = (type) => {
        const icons = {
            card: '💳',
            sepa_debit: '🏦',
            sofort: '💶',
            giropay: '🇩🇪',
            eps: '🇦🇹',
            bancontact: '🇧🇪',
            ideal: '🇳🇱',
        };
        return icons[type] || '💳';
    };

    /**
     * Get localized payment method name.
     *
     * @param {String} type - Payment method type
     * @returns {String} Localized name
     */
    const getPaymentMethodName = (type) => {
        const names = {
            card: 'Kreditkarte / EC-Karte',
            sepa_debit: 'SEPA Lastschrift',
            sofort: 'SOFORT Überweisung',
            giropay: 'Giropay',
            eps: 'EPS',
            bancontact: 'Bancontact',
            ideal: 'iDEAL',
        };
        return names[type] || type;
    };

    // Computed properties
    const isReady = computed(() => stripe.value !== null && !isLoading.value);
    const hasError = computed(() => error.value !== null);

    return {
        // State
        stripe,
        isLoading,
        isReady,
        error,
        hasError,

        // Methods
        initStripe,
        createElements,
        redirectToCheckout,
        confirmCardPayment,
        confirmCardSetup,
        confirmSepaDebitSetup,

        // Helpers
        formatAmount,
        getPaymentMethodIcon,
        getPaymentMethodName,
    };
}
