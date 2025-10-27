/**
 * Stripe Error Code to German Error Message Mapping
 *
 * Based on: https://stripe.com/docs/error-codes
 * Updated: 2025-10-27
 */

// Card Errors
const CARD_ERRORS = {
    // Declines
    'card_declined': 'Ihre Karte wurde abgelehnt. Bitte verwenden Sie eine andere Zahlungsmethode.',
    'generic_decline': 'Die Zahlung wurde abgelehnt. Bitte kontaktieren Sie Ihre Bank für weitere Informationen.',
    'insufficient_funds': 'Die Karte hat nicht genügend Guthaben. Bitte verwenden Sie eine andere Karte.',
    'lost_card': 'Die Zahlung wurde abgelehnt, da die Karte als verloren gemeldet wurde.',
    'stolen_card': 'Die Zahlung wurde abgelehnt, da die Karte als gestohlen gemeldet wurde.',
    'do_not_honor': 'Die Karte wurde abgelehnt. Bitte kontaktieren Sie Ihre Bank.',
    'do_not_try_again': 'Die Karte wurde abgelehnt. Bitte kontaktieren Sie Ihre Bank.',
    'fraudulent': 'Die Zahlung wurde als betrügerisch eingestuft und abgelehnt.',
    'invalid_account': 'Die Karte oder das Konto existiert nicht. Bitte prüfen Sie Ihre Kartendaten.',
    'merchant_blacklist': 'Die Zahlung wurde abgelehnt. Bitte verwenden Sie eine andere Zahlungsmethode.',
    'pickup_card': 'Die Karte kann nicht verwendet werden. Bitte kontaktieren Sie Ihre Bank.',
    'restricted_card': 'Die Karte kann nicht für diese Art von Zahlung verwendet werden.',
    'revocation_of_all_authorizations': 'Die Karte wurde abgelehnt. Bitte kontaktieren Sie Ihre Bank.',
    'revocation_of_authorization': 'Die Karte wurde abgelehnt. Bitte kontaktieren Sie Ihre Bank.',
    'security_violation': 'Die Zahlung wurde aus Sicherheitsgründen abgelehnt.',
    'service_not_allowed': 'Die Karte unterstützt diese Art von Zahlung nicht.',
    'transaction_not_allowed': 'Die Karte unterstützt diese Art von Zahlung nicht.',
    'try_again_later': 'Die Zahlung konnte nicht verarbeitet werden. Bitte versuchen Sie es später erneut.',
    'withdrawal_count_limit_exceeded': 'Das Limit für Transaktionen wurde überschritten. Bitte versuchen Sie es später erneut.',

    // Card Validation Errors
    'invalid_number': 'Die Kartennummer ist ungültig. Bitte überprüfen Sie Ihre Eingabe.',
    'invalid_expiry_month': 'Der Ablaufmonat ist ungültig.',
    'invalid_expiry_year': 'Das Ablaujahr ist ungültig.',
    'invalid_cvc': 'Die Kartenprüfnummer (CVC) ist ungültig.',
    'incorrect_number': 'Die Kartennummer ist falsch. Bitte überprüfen Sie Ihre Eingabe.',
    'incomplete_number': 'Die Kartennummer ist unvollständig.',
    'incomplete_cvc': 'Die Kartenprüfnummer (CVC) ist unvollständig.',
    'incomplete_expiry': 'Das Ablaufdatum ist unvollständig.',
    'expired_card': 'Die Karte ist abgelaufen.',
    'incorrect_cvc': 'Die Kartenprüfnummer (CVC) ist falsch.',
    'incorrect_zip': 'Die Postleitzahl stimmt nicht mit der Karte überein.',
    'invalid_expiry_year_past': 'Das Ablaufdatum liegt in der Vergangenheit.',

    // Processing Errors
    'processing_error': 'Bei der Verarbeitung der Zahlung ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut.',
    'card_velocity_exceeded': 'Sie haben zu viele Zahlungen in kurzer Zeit versucht. Bitte warten Sie einen Moment.',
    'live_mode_test_card': 'Sie haben eine Test-Kartennummer verwendet. Bitte verwenden Sie eine echte Karte.',
    'testmode_charges_only': 'Diese Karte kann nur im Testmodus verwendet werden.',
};

// Payment Intent Errors
const PAYMENT_INTENT_ERRORS = {
    'payment_intent_authentication_failure': 'Die Authentifizierung ist fehlgeschlagen. Bitte versuchen Sie es erneut.',
    'payment_intent_incompatible_payment_method': 'Diese Zahlungsmethode ist mit der aktuellen Zahlung nicht kompatibel.',
    'payment_intent_invalid_parameter': 'Ein ungültiger Parameter wurde übermittelt.',
    'payment_intent_payment_attempt_failed': 'Der Zahlungsversuch ist fehlgeschlagen. Bitte versuchen Sie es erneut.',
    'payment_intent_unexpected_state': 'Die Zahlung befindet sich in einem unerwarteten Zustand.',
};

// Setup Intent Errors
const SETUP_INTENT_ERRORS = {
    'setup_intent_authentication_failure': 'Die Authentifizierung ist fehlgeschlagen. Bitte versuchen Sie es erneut.',
    'setup_intent_invalid_parameter': 'Ein ungültiger Parameter wurde übermittelt.',
    'setup_intent_setup_attempt_failed': 'Das Einrichten der Zahlungsmethode ist fehlgeschlagen.',
    'setup_intent_unexpected_state': 'Das Einrichten befindet sich in einem unerwarteten Zustand.',
};

// SEPA Errors
const SEPA_ERRORS = {
    'invalid_iban': 'Die IBAN ist ungültig. Bitte überprüfen Sie Ihre Eingabe.',
    'iban_invalid_country': 'Die IBAN ist für dieses Land nicht gültig.',
    'invalid_bank_account_iban': 'Die IBAN ist ungültig.',
    'bank_account_declined': 'Das Bankkonto wurde abgelehnt.',
    'bank_account_unusable': 'Das Bankkonto kann nicht verwendet werden.',
    'bank_account_unverified': 'Das Bankkonto wurde nicht verifiziert.',
    'bank_account_verification_failed': 'Die Verifizierung des Bankkontos ist fehlgeschlagen.',
    'debit_not_authorized': 'Die Lastschrift wurde nicht autorisiert.',
};

// Customer Errors
const CUSTOMER_ERRORS = {
    'customer_max_payment_methods': 'Sie haben die maximale Anzahl an Zahlungsmethoden erreicht.',
    'email_invalid': 'Die E-Mail-Adresse ist ungültig.',
};

// Rate Limit Errors
const RATE_LIMIT_ERRORS = {
    'rate_limit': 'Zu viele Anfragen. Bitte versuchen Sie es in einem Moment erneut.',
};

// Generic Errors
const GENERIC_ERRORS = {
    'api_error': 'Ein interner Fehler ist aufgetreten. Bitte versuchen Sie es erneut.',
    'invalid_request_error': 'Die Anfrage war ungültig.',
    'validation_error': 'Die Validierung ist fehlgeschlagen. Bitte überprüfen Sie Ihre Eingaben.',
};

// All Errors Combined
const ALL_ERRORS = {
    ...CARD_ERRORS,
    ...PAYMENT_INTENT_ERRORS,
    ...SETUP_INTENT_ERRORS,
    ...SEPA_ERRORS,
    ...CUSTOMER_ERRORS,
    ...RATE_LIMIT_ERRORS,
    ...GENERIC_ERRORS,
};

/**
 * Get German error message for Stripe error code
 * @param {string} code - Stripe error code
 * @param {string} defaultMessage - Default message if code not found
 * @returns {string} German error message
 */
export function getGermanErrorMessage(code, defaultMessage = null) {
    if (!code) {
        return defaultMessage || 'Ein unbekannter Fehler ist aufgetreten.';
    }

    return ALL_ERRORS[code] || defaultMessage || 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.';
}

/**
 * Format Stripe error for display
 * @param {Object} error - Stripe error object
 * @returns {string} Formatted German error message
 */
export function formatStripeError(error) {
    if (!error) {
        return 'Ein unbekannter Fehler ist aufgetreten.';
    }

    // Error has code
    if (error.code) {
        return getGermanErrorMessage(error.code, error.message);
    }

    // Error has decline_code (card declines)
    if (error.decline_code) {
        return getGermanErrorMessage(error.decline_code, error.message);
    }

    // Error has type
    if (error.type) {
        switch (error.type) {
            case 'card_error':
                return 'Es gab ein Problem mit Ihrer Karte. Bitte überprüfen Sie Ihre Eingaben.';
            case 'validation_error':
                return 'Bitte überprüfen Sie Ihre Eingaben.';
            case 'api_error':
                return 'Ein interner Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.';
            case 'authentication_error':
                return 'Die Authentifizierung ist fehlgeschlagen.';
            case 'rate_limit_error':
                return 'Zu viele Anfragen. Bitte versuchen Sie es in einem Moment erneut.';
            case 'invalid_request_error':
                return 'Die Anfrage war ungültig.';
            default:
                return error.message || 'Ein Fehler ist aufgetreten.';
        }
    }

    // Fallback to message
    return error.message || 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.';
}

/**
 * Check if error is retriable
 * @param {Object} error - Stripe error object
 * @returns {boolean} True if user should retry
 */
export function isRetriableError(error) {
    if (!error) return false;

    const retriableCodes = [
        'processing_error',
        'try_again_later',
        'rate_limit',
        'api_error',
        'payment_intent_payment_attempt_failed',
        'setup_intent_setup_attempt_failed',
    ];

    return retriableCodes.includes(error.code) || retriableCodes.includes(error.decline_code);
}

/**
 * Get user-friendly action message based on error
 * @param {Object} error - Stripe error object
 * @returns {string|null} Action message or null
 */
export function getErrorAction(error) {
    if (!error) return null;

    const code = error.code || error.decline_code;

    const actions = {
        'card_declined': 'Bitte verwenden Sie eine andere Zahlungsmethode oder kontaktieren Sie Ihre Bank.',
        'insufficient_funds': 'Bitte laden Sie Ihr Konto auf oder verwenden Sie eine andere Karte.',
        'expired_card': 'Bitte aktualisieren Sie Ihre Kartendaten.',
        'incorrect_cvc': 'Bitte überprüfen Sie die Kartenprüfnummer (CVC) auf der Rückseite Ihrer Karte.',
        'incorrect_number': 'Bitte überprüfen Sie die Kartennummer.',
        'invalid_iban': 'Bitte überprüfen Sie die IBAN.',
        'processing_error': 'Bitte versuchen Sie es in einigen Minuten erneut.',
        'try_again_later': 'Bitte versuchen Sie es später erneut.',
        'rate_limit': 'Bitte warten Sie einen Moment und versuchen Sie es dann erneut.',
    };

    return actions[code] || null;
}

/**
 * Check if error requires contacting support
 * @param {Object} error - Stripe error object
 * @returns {boolean} True if user should contact support
 */
export function requiresSupportContact(error) {
    if (!error) return false;

    const supportCodes = [
        'api_error',
        'setup_intent_unexpected_state',
        'payment_intent_unexpected_state',
        'bank_account_verification_failed',
    ];

    return supportCodes.includes(error.code);
}

export default {
    getGermanErrorMessage,
    formatStripeError,
    isRetriableError,
    getErrorAction,
    requiresSupportContact,
    CARD_ERRORS,
    PAYMENT_INTENT_ERRORS,
    SETUP_INTENT_ERRORS,
    SEPA_ERRORS,
    CUSTOMER_ERRORS,
    ALL_ERRORS,
};
