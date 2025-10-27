<template>
    <div v-if="isDevelopment" class="test-card-selector">
        <div class="selector-header">
            <svg class="w-5 h-5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <span class="selector-title">Test-Modus</span>
        </div>

        <select
            v-model="selectedCard"
            @change="fillTestCard"
            class="selector-dropdown"
        >
            <option value="">Test-Karte wählen...</option>

            <optgroup label="✅ Erfolgreiche Zahlungen">
                <option value="success_visa">Visa - Erfolgreich</option>
                <option value="success_visa_debit">Visa (Debit) - Erfolgreich</option>
                <option value="success_mastercard">Mastercard - Erfolgreich</option>
                <option value="success_mastercard_2series">Mastercard (2-Series) - Erfolgreich</option>
                <option value="success_mastercard_debit">Mastercard (Debit) - Erfolgreich</option>
                <option value="success_amex">American Express - Erfolgreich</option>
                <option value="success_discover">Discover - Erfolgreich</option>
                <option value="success_diners">Diners Club - Erfolgreich</option>
                <option value="success_jcb">JCB - Erfolgreich</option>
                <option value="success_unionpay">UnionPay - Erfolgreich</option>
            </optgroup>

            <optgroup label="🔐 3D Secure / SCA">
                <option value="3ds_required">3D Secure - Erforderlich (erfolgreich)</option>
                <option value="3ds_required_fail">3D Secure - Fehlgeschlagen</option>
                <option value="3ds_optional">3D Secure - Optional</option>
            </optgroup>

            <optgroup label="❌ Fehlgeschlagene Zahlungen">
                <option value="declined_generic">Allgemeine Ablehnung</option>
                <option value="declined_insufficient_funds">Unzureichende Mittel</option>
                <option value="declined_lost_card">Verlorene Karte</option>
                <option value="declined_stolen_card">Gestohlene Karte</option>
                <option value="declined_expired_card">Abgelaufene Karte</option>
                <option value="declined_incorrect_cvc">Falsche CVC</option>
                <option value="declined_processing_error">Verarbeitungsfehler</option>
                <option value="declined_incorrect_number">Falsche Kartennummer</option>
            </optgroup>

            <optgroup label="⚠️ Spezielle Szenarien">
                <option value="dispute_fraudulent">Streitfall - Betrügerisch</option>
                <option value="dispute_product_not_received">Streitfall - Produkt nicht erhalten</option>
                <option value="live_mode_test">Live-Modus Test (fehlgeschlagen)</option>
            </optgroup>
        </select>

        <div v-if="selectedCardData" class="card-info">
            <div class="info-item">
                <span class="info-label">Nummer:</span>
                <code class="info-value">{{ selectedCardData.number }}</code>
                <button @click="copyToClipboard(selectedCardData.number)" class="copy-btn" title="Kopieren">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </button>
            </div>
            <div class="info-row">
                <div class="info-item">
                    <span class="info-label">Ablauf:</span>
                    <code class="info-value">{{ selectedCardData.expiry }}</code>
                </div>
                <div class="info-item">
                    <span class="info-label">CVC:</span>
                    <code class="info-value">{{ selectedCardData.cvc }}</code>
                </div>
            </div>
            <div class="info-item">
                <span class="info-label">PLZ:</span>
                <code class="info-value">{{ selectedCardData.zip }}</code>
            </div>
            <p class="scenario-description">{{ selectedCardData.description }}</p>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const isDevelopment = computed(() => {
    return import.meta.env.DEV || import.meta.env.MODE === 'development';
});

const selectedCard = ref('');

const testCards = {
    // Successful payments
    success_visa: {
        number: '4242 4242 4242 4242',
        expiry: '12/34',
        cvc: '123',
        zip: '12345',
        description: 'Standard Visa-Testkarte - Zahlung wird immer erfolgreich sein',
    },
    success_visa_debit: {
        number: '4000 0566 5566 5556',
        expiry: '12/34',
        cvc: '123',
        zip: '12345',
        description: 'Visa Debitkarte - Zahlung wird erfolgreich sein',
    },
    success_mastercard: {
        number: '5555 5555 5555 4444',
        expiry: '12/34',
        cvc: '123',
        zip: '12345',
        description: 'Standard Mastercard-Testkarte - Zahlung wird erfolgreich sein',
    },
    success_mastercard_2series: {
        number: '2223 0031 2200 3222',
        expiry: '12/34',
        cvc: '123',
        zip: '12345',
        description: 'Mastercard (2-Series) - Zahlung wird erfolgreich sein',
    },
    success_mastercard_debit: {
        number: '5200 8282 8282 8210',
        expiry: '12/34',
        cvc: '123',
        zip: '12345',
        description: 'Mastercard Debitkarte - Zahlung wird erfolgreich sein',
    },
    success_amex: {
        number: '3782 822463 10005',
        expiry: '12/34',
        cvc: '1234',
        zip: '12345',
        description: 'American Express - Zahlung wird erfolgreich sein',
    },
    success_discover: {
        number: '6011 1111 1111 1117',
        expiry: '12/34',
        cvc: '123',
        zip: '12345',
        description: 'Discover - Zahlung wird erfolgreich sein',
    },
    success_diners: {
        number: '3056 9309 0259 04',
        expiry: '12/34',
        cvc: '123',
        zip: '12345',
        description: 'Diners Club - Zahlung wird erfolgreich sein',
    },
    success_jcb: {
        number: '3566 0020 2036 0505',
        expiry: '12/34',
        cvc: '123',
        zip: '12345',
        description: 'JCB - Zahlung wird erfolgreich sein',
    },
    success_unionpay: {
        number: '6200 0000 0000 0005',
        expiry: '12/34',
        cvc: '123',
        zip: '12345',
        description: 'UnionPay - Zahlung wird erfolgreich sein',
    },

    // 3D Secure
    '3ds_required': {
        number: '4000 0027 6000 3184',
        expiry: '12/34',
        cvc: '123',
        zip: '12345',
        description: '3D Secure wird verlangt und erfolgreich sein (Authentifizierung erforderlich)',
    },
    '3ds_required_fail': {
        number: '4000 0000 0000 3055',
        expiry: '12/34',
        cvc: '123',
        zip: '12345',
        description: '3D Secure wird verlangt aber fehlschlagen',
    },
    '3ds_optional': {
        number: '4000 0025 0000 0003',
        expiry: '12/34',
        cvc: '123',
        zip: '12345',
        description: '3D Secure ist optional (wird nicht angefordert)',
    },

    // Declined payments
    declined_generic: {
        number: '4000 0000 0000 0002',
        expiry: '12/34',
        cvc: '123',
        zip: '12345',
        description: 'Karte wird mit generic_decline abgelehnt',
    },
    declined_insufficient_funds: {
        number: '4000 0000 0000 9995',
        expiry: '12/34',
        cvc: '123',
        zip: '12345',
        description: 'Karte wird mit insufficient_funds abgelehnt',
    },
    declined_lost_card: {
        number: '4000 0000 0000 9987',
        expiry: '12/34',
        cvc: '123',
        zip: '12345',
        description: 'Karte wird mit lost_card abgelehnt',
    },
    declined_stolen_card: {
        number: '4000 0000 0000 9979',
        expiry: '12/34',
        cvc: '123',
        zip: '12345',
        description: 'Karte wird mit stolen_card abgelehnt',
    },
    declined_expired_card: {
        number: '4000 0000 0000 0069',
        expiry: '12/34',
        cvc: '123',
        zip: '12345',
        description: 'Karte wird mit expired_card abgelehnt',
    },
    declined_incorrect_cvc: {
        number: '4000 0000 0000 0127',
        expiry: '12/34',
        cvc: '123',
        zip: '12345',
        description: 'Karte wird mit incorrect_cvc abgelehnt',
    },
    declined_processing_error: {
        number: '4000 0000 0000 0119',
        expiry: '12/34',
        cvc: '123',
        zip: '12345',
        description: 'Karte wird mit processing_error abgelehnt',
    },
    declined_incorrect_number: {
        number: '4242 4242 4242 4241',
        expiry: '12/34',
        cvc: '123',
        zip: '12345',
        description: 'Ungültige Kartennummer (Luhn-Check fehlgeschlagen)',
    },

    // Special scenarios
    dispute_fraudulent: {
        number: '4000 0000 0000 0259',
        expiry: '12/34',
        cvc: '123',
        zip: '12345',
        description: 'Zahlung erfolgreich, aber wird später als betrügerisch gemeldet',
    },
    dispute_product_not_received: {
        number: '4000 0000 0000 2685',
        expiry: '12/34',
        cvc: '123',
        zip: '12345',
        description: 'Zahlung erfolgreich, aber Kunde meldet "Produkt nicht erhalten"',
    },
    live_mode_test: {
        number: '4000 0000 0000 0101',
        expiry: '12/34',
        cvc: '123',
        zip: '12345',
        description: 'Test-Karte im Live-Modus (wird abgelehnt)',
    },
};

const selectedCardData = computed(() => {
    if (!selectedCard.value) return null;
    return testCards[selectedCard.value] || null;
});

const emit = defineEmits(['card-selected']);

const fillTestCard = () => {
    if (selectedCardData.value) {
        emit('card-selected', selectedCardData.value);
    }
};

const copyToClipboard = async (text) => {
    try {
        await navigator.clipboard.writeText(text.replace(/\s/g, ''));
        // Could show a toast notification here
    } catch (err) {
        console.error('Failed to copy:', err);
    }
};
</script>

<style scoped>
.test-card-selector {
    margin-bottom: 1rem;
    padding: 1rem;
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border: 2px solid #fbbf24;
    border-radius: 0.5rem;
}

.selector-header {
    display: flex;
    align-items: center;
    margin-bottom: 0.75rem;
}

.selector-title {
    margin-left: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #92400e;
}

.selector-dropdown {
    width: 100%;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    border: 1px solid #d97706;
    border-radius: 0.375rem;
    background-color: #ffffff;
    color: #1f2937;
    cursor: pointer;
}

.selector-dropdown:focus {
    outline: none;
    border-color: #b45309;
    ring: 2px;
    ring-color: rgba(217, 119, 6, 0.3);
}

.card-info {
    margin-top: 0.75rem;
    padding: 0.75rem;
    background-color: rgba(255, 255, 255, 0.8);
    border-radius: 0.375rem;
}

.info-item {
    display: flex;
    align-items: center;
    margin-bottom: 0.5rem;
}

.info-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
}

.info-label {
    font-size: 0.75rem;
    font-weight: 500;
    color: #78350f;
    margin-right: 0.5rem;
}

.info-value {
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    font-size: 0.875rem;
    padding: 0.25rem 0.5rem;
    background-color: #f3f4f6;
    border-radius: 0.25rem;
    color: #1f2937;
}

.copy-btn {
    margin-left: 0.5rem;
    padding: 0.25rem;
    background-color: transparent;
    border: none;
    color: #92400e;
    cursor: pointer;
    transition: color 0.2s;
}

.copy-btn:hover {
    color: #78350f;
}

.scenario-description {
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid #fbbf24;
    font-size: 0.75rem;
    color: #92400e;
    line-height: 1.4;
}
</style>
