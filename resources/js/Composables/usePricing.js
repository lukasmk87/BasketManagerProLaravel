import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Composable for handling pricing display based on system settings.
 * Uses pricing settings from Inertia shared props.
 */
export function usePricing() {
    const page = usePage();

    /**
     * Get pricing settings from Inertia props with defaults.
     */
    const pricingSettings = computed(() => page.props.pricing || {
        display_gross: true,
        is_small_business: false,
        default_tax_rate: 19.00,
        small_business_notice: null,
    });

    /**
     * Check if prices should be displayed gross (including VAT).
     */
    const displayGross = computed(() => pricingSettings.value.display_gross);

    /**
     * Check if operator is a small business (Kleinunternehmer).
     */
    const isSmallBusiness = computed(() => pricingSettings.value.is_small_business);

    /**
     * Get the default tax rate.
     */
    const defaultTaxRate = computed(() => pricingSettings.value.default_tax_rate);

    /**
     * Format a price amount for display.
     * @param {number} amount - The amount to format
     * @param {string} currency - Currency code (default: EUR)
     * @returns {string} Formatted price string
     */
    const formatAmount = (amount, currency = 'EUR') => {
        return new Intl.NumberFormat('de-DE', {
            style: 'currency',
            currency: currency,
        }).format(amount);
    };

    /**
     * Calculate and format display price based on net price and settings.
     * @param {number} netPrice - Net price from database
     * @param {string} currency - Currency code (default: EUR)
     * @returns {string} Formatted display price
     */
    const formatPrice = (netPrice, currency = 'EUR') => {
        const settings = pricingSettings.value;

        if (settings.is_small_business) {
            return formatAmount(netPrice, currency);
        }

        const taxRate = settings.default_tax_rate / 100;
        const displayPrice = settings.display_gross
            ? netPrice * (1 + taxRate)
            : netPrice;

        return formatAmount(displayPrice, currency);
    };

    /**
     * Get the price label based on settings.
     * @returns {string} "inkl. MwSt.", "zzgl. MwSt.", or empty string
     */
    const getPriceLabel = () => {
        const settings = pricingSettings.value;

        if (settings.is_small_business) {
            return '';
        }

        return settings.display_gross ? 'inkl. MwSt.' : 'zzgl. MwSt.';
    };

    /**
     * Get the small business notice text if applicable.
     * @returns {string|null} Notice text or null
     */
    const getSmallBusinessNotice = () => {
        return pricingSettings.value.is_small_business
            ? 'Gemäß §19 UStG wird keine Umsatzsteuer berechnet.'
            : null;
    };

    /**
     * Get admin price input configuration.
     */
    const adminInputConfig = computed(() => page.props.pricing?.admin_input || {
        mode: 'net',
        label: 'Nettopreis (zzgl. MwSt.)',
        input_is_gross: false,
    });

    /**
     * Check if admin should input gross prices.
     */
    const adminInputIsGross = computed(() => adminInputConfig.value.input_is_gross);

    /**
     * Get the label for admin price input field.
     */
    const getAdminPriceLabel = () => {
        return adminInputConfig.value.label;
    };

    /**
     * Convert gross price to net price.
     * @param {number} grossPrice - Gross price including VAT
     * @returns {number} Net price
     */
    const grossToNet = (grossPrice) => {
        const settings = pricingSettings.value;

        if (settings.is_small_business) {
            return grossPrice;
        }

        const taxRate = settings.default_tax_rate / 100;
        return Math.round((grossPrice / (1 + taxRate)) * 100) / 100;
    };

    /**
     * Convert net price to gross price.
     * @param {number} netPrice - Net price excluding VAT
     * @returns {number} Gross price
     */
    const netToGross = (netPrice) => {
        const settings = pricingSettings.value;

        if (settings.is_small_business) {
            return netPrice;
        }

        const taxRate = settings.default_tax_rate / 100;
        return Math.round((netPrice * (1 + taxRate)) * 100) / 100;
    };

    /**
     * Convert input price to net price for storage.
     * Use this before sending to backend.
     * @param {number} inputPrice - Price as entered by admin
     * @returns {number} Net price for database storage
     */
    const inputPriceToNet = (inputPrice) => {
        if (adminInputConfig.value.input_is_gross) {
            return grossToNet(inputPrice);
        }
        return inputPrice;
    };

    /**
     * Convert net price from database to input price for display.
     * Use this when loading data from backend.
     * @param {number} netPrice - Net price from database
     * @returns {number} Price to display in input field
     */
    const netPriceToInput = (netPrice) => {
        if (adminInputConfig.value.input_is_gross) {
            return netToGross(netPrice);
        }
        return netPrice;
    };

    /**
     * Calculate full pricing breakdown for a net price.
     * @param {number} netPrice - Net price from database
     * @returns {object} Pricing breakdown
     */
    const calculatePricing = (netPrice) => {
        const settings = pricingSettings.value;

        if (settings.is_small_business) {
            return {
                netPrice: netPrice,
                grossPrice: netPrice,
                taxAmount: 0,
                taxRate: 0,
                displayPrice: netPrice,
                priceLabel: '',
                isSmallBusiness: true,
                smallBusinessNotice: getSmallBusinessNotice(),
            };
        }

        const taxRate = settings.default_tax_rate;
        const taxAmount = netPrice * (taxRate / 100);
        const grossPrice = netPrice + taxAmount;
        const displayPrice = settings.display_gross ? grossPrice : netPrice;

        return {
            netPrice: netPrice,
            grossPrice: grossPrice,
            taxAmount: taxAmount,
            taxRate: taxRate,
            displayPrice: displayPrice,
            priceLabel: getPriceLabel(),
            isSmallBusiness: false,
            smallBusinessNotice: null,
        };
    };

    /**
     * Get full display string with price and label.
     * @param {number} netPrice - Net price from database
     * @param {string} currency - Currency code (default: EUR)
     * @returns {string} Full price string with label
     */
    const getFullPriceString = (netPrice, currency = 'EUR') => {
        const formattedPrice = formatPrice(netPrice, currency);
        const label = getPriceLabel();

        if (label) {
            return `${formattedPrice} ${label}`;
        }

        return formattedPrice;
    };

    return {
        pricingSettings,
        displayGross,
        isSmallBusiness,
        defaultTaxRate,
        formatAmount,
        formatPrice,
        getPriceLabel,
        getSmallBusinessNotice,
        calculatePricing,
        getFullPriceString,
        // Admin price input
        adminInputConfig,
        adminInputIsGross,
        getAdminPriceLabel,
        grossToNet,
        netToGross,
        inputPriceToNet,
        netPriceToInput,
    };
}
