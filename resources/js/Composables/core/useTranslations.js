import { usePage } from '@inertiajs/vue3';

/**
 * Composable für den Zugriff auf Übersetzungen aus Laravel Backend
 *
 * Übersetzungen werden über HandleInertiaRequests Middleware als verschachtelte
 * Objekte bereitgestellt (z.B. { billing: { title: 'Abrechnung', ... } })
 *
 * @example
 * const { trans } = useTranslations();
 * const title = trans('billing.title'); // 'Abrechnung'
 * const missing = trans('missing.key'); // '' (leerer String als Fallback)
 */
export function useTranslations() {
    const page = usePage();

    /**
     * Übersetzt einen verschachtelten Schlüssel (z.B. 'billing.title')
     *
     * @param {string} key - Der Übersetzungsschlüssel (mit Punktnotation für verschachtelte Werte)
     * @returns {string} Die Übersetzung oder leerer String bei fehlenden Werten
     */
    const trans = (key) => {
        if (!key || typeof key !== 'string') {
            if (import.meta.env.DEV) {
                console.warn('[useTranslations] Invalid translation key:', key);
            }
            return '';
        }

        // Hole die Übersetzungen aus den Inertia Page Props
        const translations = page.props.translations;

        if (!translations) {
            if (import.meta.env.DEV) {
                console.warn('[useTranslations] No translations available in page props');
            }
            return '';
        }

        // Zerlege den Key in Teile (z.B. 'billing.invoices.title' -> ['billing', 'invoices', 'title'])
        const keys = key.split('.');

        // Navigiere durch das verschachtelte Objekt
        let result = translations;
        for (const k of keys) {
            if (result && typeof result === 'object' && k in result) {
                result = result[k];
            } else {
                // Übersetzung nicht gefunden
                if (import.meta.env.DEV) {
                    console.warn(`[useTranslations] Translation not found for key: ${key}`);
                }
                return '';
            }
        }

        // Stelle sicher, dass das Ergebnis ein String ist
        if (typeof result === 'string') {
            return result;
        }

        // Falls das Ergebnis ein Objekt ist (z.B. trans('billing') gibt das ganze Objekt zurück)
        if (typeof result === 'object') {
            if (import.meta.env.DEV) {
                console.warn(`[useTranslations] Translation key "${key}" returned an object instead of a string. Did you mean to access a nested property?`);
            }
            return '';
        }

        return String(result);
    };

    /**
     * Prüft, ob eine Übersetzung existiert
     *
     * @param {string} key - Der Übersetzungsschlüssel
     * @returns {boolean} true wenn die Übersetzung existiert
     */
    const hasTranslation = (key) => {
        if (!key || typeof key !== 'string') return false;

        const translations = page.props.translations;
        if (!translations) return false;

        const keys = key.split('.');
        let result = translations;

        for (const k of keys) {
            if (result && typeof result === 'object' && k in result) {
                result = result[k];
            } else {
                return false;
            }
        }

        return typeof result === 'string';
    };

    return {
        trans,
        hasTranslation,
    };
}
