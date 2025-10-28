import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Vue composable for accessing Laravel translations in Vue components.
 *
 * Usage:
 * import { useTranslations } from '@/composables/useTranslations';
 * const { trans, transChoice, locale } = useTranslations();
 *
 * trans('subscription.title')
 * trans('subscription.messages.plan_swapped', { plan: 'Premium' })
 */
export function useTranslations() {
    const page = usePage();

    // Get translations from page props (shared via HandleInertiaRequests)
    const translations = computed(() => page.props.translations || {});
    const locale = computed(() => page.props.locale || 'de');

    /**
     * Translate a key with optional parameter replacements.
     *
     * @param {string} key - Translation key using dot notation (e.g., 'subscription.title')
     * @param {object} replacements - Key-value pairs for placeholder replacement (e.g., { name: 'John' })
     * @returns {string} Translated string with placeholders replaced
     *
     * @example
     * trans('subscription.title') // => 'Club Abonnement'
     * trans('subscription.messages.plan_swapped', { plan: 'Premium' }) // => 'Plan erfolgreich gewechselt zu Premium!'
     */
    const trans = (key, replacements = {}) => {
        // Get translation using dot notation
        let translation = getNestedProperty(translations.value, key);

        // Fallback to key if translation not found
        if (!translation) {
            if (import.meta.env.DEV) {
                console.warn(`[useTranslations] Translation not found for key: ${key}`);
            }
            return key;
        }

        // Replace placeholders (e.g., :name, :count, :plan)
        if (typeof translation === 'string') {
            Object.keys(replacements).forEach(placeholder => {
                const regex = new RegExp(`:${placeholder}`, 'g');
                translation = translation.replace(regex, replacements[placeholder]);
            });
        }

        return translation;
    };

    /**
     * Get nested property from object using dot notation.
     *
     * @param {object} obj - The object to traverse
     * @param {string} path - Dot-notated path (e.g., 'subscription.plans.title')
     * @returns {any} The value at the path, or undefined if not found
     *
     * @private
     */
    const getNestedProperty = (obj, path) => {
        if (!obj || typeof obj !== 'object') {
            return undefined;
        }

        return path.split('.').reduce((acc, part) => {
            return acc && typeof acc === 'object' ? acc[part] : undefined;
        }, obj);
    };

    /**
     * Pluralize translation based on count.
     * Simple implementation - can be enhanced for complex pluralization rules.
     *
     * @param {string} key - Translation key
     * @param {number} count - Count for pluralization
     * @param {object} replacements - Additional replacements
     * @returns {string} Translated string
     *
     * @example
     * transChoice('subscription.trial.days_remaining', 5, { days: 5, unit: 'Tage' })
     */
    const transChoice = (key, count, replacements = {}) => {
        const translation = trans(key, { count, ...replacements });
        return translation;
    };

    /**
     * Check if a translation key exists.
     *
     * @param {string} key - Translation key to check
     * @returns {boolean} True if translation exists
     */
    const has = (key) => {
        const translation = getNestedProperty(translations.value, key);
        return translation !== undefined;
    };

    return {
        trans,
        transChoice,
        has,
        locale,
        translations,
    };
}
