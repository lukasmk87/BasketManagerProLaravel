<template>
    <teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div
                    class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                    @click="handleClose"
                ></div>

                <!-- Modal panel -->
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <svg class="w-6 h-6 text-white mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                                <h3 class="text-lg font-semibold text-white" id="modal-title">
                                    Zahlungsmethode hinzufügen
                                </h3>
                            </div>
                            <button
                                @click="handleClose"
                                class="text-white hover:text-gray-200 transition-colors"
                            >
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="bg-white px-6 py-5">
                        <!-- Payment Method Type Selection -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-3">
                                Zahlungsmethode wählen
                            </label>
                            <div class="grid grid-cols-2 gap-3">
                                <button
                                    v-for="type in paymentMethodTypes"
                                    :key="type.value"
                                    @click="selectedType = type.value"
                                    class="flex items-center justify-center px-4 py-3 border-2 rounded-lg transition-all"
                                    :class="selectedType === type.value
                                        ? 'border-blue-500 bg-blue-50 text-blue-700'
                                        : 'border-gray-300 hover:border-gray-400 text-gray-700'"
                                >
                                    <span class="text-2xl mr-3">{{ type.icon }}</span>
                                    <span class="font-medium">{{ type.label }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Billing Details Form -->
                        <div class="mb-6 space-y-4">
                            <h4 class="text-sm font-medium text-gray-900">Rechnungsinformationen</h4>

                            <!-- Name -->
                            <div>
                                <label for="billing-name" class="block text-sm font-medium text-gray-700 mb-1">
                                    Name <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="billing-name"
                                    v-model="billingDetails.name"
                                    type="text"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                    :class="{ 'border-red-300': errors.name }"
                                    placeholder="Max Mustermann"
                                />
                                <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</p>
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="billing-email" class="block text-sm font-medium text-gray-700 mb-1">
                                    E-Mail <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="billing-email"
                                    v-model="billingDetails.email"
                                    type="email"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                    :class="{ 'border-red-300': errors.email }"
                                    placeholder="max@beispiel.de"
                                />
                                <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email }}</p>
                            </div>

                            <!-- Address -->
                            <div class="grid grid-cols-2 gap-3">
                                <div class="col-span-2">
                                    <label for="billing-street" class="block text-sm font-medium text-gray-700 mb-1">
                                        Straße & Hausnummer
                                    </label>
                                    <input
                                        id="billing-street"
                                        v-model="billingDetails.address.line1"
                                        type="text"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Musterstraße 123"
                                    />
                                </div>
                                <div>
                                    <label for="billing-postal" class="block text-sm font-medium text-gray-700 mb-1">
                                        PLZ
                                    </label>
                                    <input
                                        id="billing-postal"
                                        v-model="billingDetails.address.postal_code"
                                        type="text"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="12345"
                                    />
                                </div>
                                <div>
                                    <label for="billing-city" class="block text-sm font-medium text-gray-700 mb-1">
                                        Stadt
                                    </label>
                                    <input
                                        id="billing-city"
                                        v-model="billingDetails.address.city"
                                        type="text"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Berlin"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Stripe Element Container -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                {{ selectedType === 'card' ? 'Kartendaten' : 'IBAN' }} <span class="text-red-500">*</span>
                            </label>

                            <!-- Card Element -->
                            <div
                                v-if="selectedType === 'card'"
                                id="card-element"
                                class="px-3 py-3 border border-gray-300 rounded-md bg-white"
                                :class="{ 'border-red-300': errors.card }"
                            ></div>

                            <!-- SEPA Element -->
                            <div
                                v-if="selectedType === 'sepa_debit'"
                                id="sepa-element"
                                class="px-3 py-3 border border-gray-300 rounded-md bg-white"
                                :class="{ 'border-red-300': errors.sepa }"
                            ></div>

                            <p v-if="errors.card || errors.sepa" class="mt-1 text-sm text-red-600">
                                {{ errors.card || errors.sepa }}
                            </p>

                            <!-- Info for SEPA -->
                            <p v-if="selectedType === 'sepa_debit'" class="mt-2 text-xs text-gray-600">
                                Durch Angabe Ihrer IBAN und Bestätigung dieser Zahlung ermächtigen Sie {{ clubName }} und Stripe, unserem Zahlungsdienstleister,
                                eine Anweisung an Ihre Bank zu senden, Ihr Konto zu belasten, sowie Ihre Bank, Ihr Konto entsprechend dieser Anweisung zu belasten.
                                Sie haben Anspruch auf Erstattung von Ihrer Bank gemäß den Bedingungen Ihres Vertrages mit Ihrer Bank.
                                Eine Erstattung muss innerhalb von 8 Wochen ab dem Datum der Belastung Ihres Kontos beantragt werden.
                            </p>
                        </div>

                        <!-- General Error -->
                        <div v-if="errors.general" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-md">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-red-600 mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                <p class="text-sm text-red-800">{{ errors.general }}</p>
                            </div>
                        </div>

                        <!-- Set as Default Checkbox -->
                        <div class="mb-6">
                            <label class="flex items-center cursor-pointer">
                                <input
                                    v-model="setAsDefault"
                                    type="checkbox"
                                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                />
                                <span class="ml-2 text-sm text-gray-700">
                                    Als Standard-Zahlungsmethode festlegen
                                </span>
                            </label>
                        </div>

                        <!-- Security Info -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                            <div class="flex">
                                <svg class="w-5 h-5 text-blue-400 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                </svg>
                                <div class="flex-1">
                                    <h5 class="text-sm font-medium text-blue-800 mb-1">Sichere Verschlüsselung</h5>
                                    <p class="text-xs text-blue-700">
                                        Ihre Zahlungsdaten werden sicher über Stripe verschlüsselt und verarbeitet.
                                        Wir speichern keine Kartendaten auf unseren Servern.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="bg-gray-50 px-6 py-4 flex items-center justify-end space-x-3">
                        <button
                            type="button"
                            @click="handleClose"
                            class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            :disabled="processing"
                        >
                            Abbrechen
                        </button>
                        <button
                            type="button"
                            @click="handleSubmit"
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="processing || !isFormValid"
                        >
                            <svg v-if="processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                            </svg>
                            {{ processing ? 'Wird hinzugefügt...' : 'Zahlungsmethode hinzufügen' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </teleport>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useStripe } from '@/composables/useStripe';

const page = usePage();

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    clubId: {
        type: [Number, String],
        required: true,
    },
    clubName: {
        type: String,
        default: undefined,
    },
});

const emit = defineEmits(['close', 'added']);

const { stripe: stripeInstance } = useStripe();

// Computed value for app name with proper fallback chain
const displayName = computed(() => props.clubName || page.props.appName || 'BasketManager Pro');

const selectedType = ref('card');
const billingDetails = ref({
    name: '',
    email: '',
    address: {
        line1: '',
        city: '',
        postal_code: '',
        country: 'DE',
    },
});
const setAsDefault = ref(false);
const processing = ref(false);
const errors = ref({});

let stripe = null;
let elements = null;
let cardElement = null;
let sepaElement = null;

const paymentMethodTypes = [
    { value: 'card', label: 'Kreditkarte', icon: '💳' },
    { value: 'sepa_debit', label: 'SEPA Lastschrift', icon: '🏦' },
];

const isFormValid = computed(() => {
    return billingDetails.value.name && billingDetails.value.email;
});

const initializeStripe = async () => {
    try {
        stripe = await stripeInstance.value;
        if (!stripe) {
            errors.value.general = 'Stripe konnte nicht initialisiert werden.';
            return;
        }

        elements = stripe.elements({
            locale: 'de',
        });

        await nextTick();
        mountElements();
    } catch (error) {
        console.error('Stripe initialization error:', error);
        errors.value.general = 'Fehler beim Laden der Zahlungsformulare.';
    }
};

const mountElements = () => {
    const elementStyle = {
        base: {
            fontSize: '16px',
            color: '#1f2937',
            fontFamily: 'system-ui, -apple-system, sans-serif',
            '::placeholder': {
                color: '#9ca3af',
            },
        },
        invalid: {
            color: '#dc2626',
            iconColor: '#dc2626',
        },
    };

    // Card Element
    if (!cardElement && document.getElementById('card-element')) {
        cardElement = elements.create('card', {
            style: elementStyle,
            hidePostalCode: false,
        });
        cardElement.mount('#card-element');
        cardElement.on('change', (event) => {
            if (event.error) {
                errors.value.card = event.error.message;
            } else {
                delete errors.value.card;
            }
        });
    }

    // SEPA Element
    if (!sepaElement && document.getElementById('sepa-element')) {
        sepaElement = elements.create('iban', {
            style: elementStyle,
            supportedCountries: ['SEPA'],
            placeholderCountry: 'DE',
        });
        sepaElement.mount('#sepa-element');
        sepaElement.on('change', (event) => {
            if (event.error) {
                errors.value.sepa = event.error.message;
            } else {
                delete errors.value.sepa;
            }
        });
    }
};

const validateForm = () => {
    errors.value = {};

    if (!billingDetails.value.name) {
        errors.value.name = 'Name ist erforderlich';
    }

    if (!billingDetails.value.email) {
        errors.value.email = 'E-Mail ist erforderlich';
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(billingDetails.value.email)) {
        errors.value.email = 'Bitte geben Sie eine gültige E-Mail-Adresse ein';
    }

    return Object.keys(errors.value).length === 0;
};

const handleSubmit = async () => {
    if (!validateForm()) {
        return;
    }

    processing.value = true;
    errors.value = {};

    try {
        // Step 1: Create Setup Intent
        const setupResponse = await axios.post(
            route('club.billing.payment-methods.setup', { club: props.clubId }),
            {
                payment_method_types: [selectedType.value],
            }
        );

        const clientSecret = setupResponse.data.client_secret;

        // Step 2: Confirm Setup with Stripe
        let result;
        const paymentMethodData = {
            billing_details: {
                name: billingDetails.value.name,
                email: billingDetails.value.email,
                address: {
                    line1: billingDetails.value.address.line1 || undefined,
                    city: billingDetails.value.address.city || undefined,
                    postal_code: billingDetails.value.address.postal_code || undefined,
                    country: billingDetails.value.address.country,
                },
            },
        };

        if (selectedType.value === 'card') {
            result = await stripe.confirmCardSetup(clientSecret, {
                payment_method: {
                    card: cardElement,
                    ...paymentMethodData,
                },
            });
        } else if (selectedType.value === 'sepa_debit') {
            result = await stripe.confirmSepaDebitSetup(clientSecret, {
                payment_method: {
                    sepa_debit: sepaElement,
                    ...paymentMethodData,
                },
            });
        }

        if (result.error) {
            errors.value.general = result.error.message;
            processing.value = false;
            return;
        }

        // Step 3: Attach Payment Method to Customer
        await axios.post(
            route('club.billing.payment-methods.attach', { club: props.clubId }),
            {
                payment_method_id: result.setupIntent.payment_method,
                set_as_default: setAsDefault.value,
            }
        );

        // Success!
        emit('added');
        resetForm();
        emit('close');
    } catch (error) {
        console.error('Payment method error:', error);
        errors.value.general = error.response?.data?.message || 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.';
    } finally {
        processing.value = false;
    }
};

const handleClose = () => {
    if (!processing.value) {
        resetForm();
        emit('close');
    }
};

const resetForm = () => {
    selectedType.value = 'card';
    billingDetails.value = {
        name: '',
        email: '',
        address: {
            line1: '',
            city: '',
            postal_code: '',
            country: 'DE',
        },
    };
    setAsDefault.value = false;
    errors.value = {};

    // Clear Stripe elements
    if (cardElement) {
        cardElement.clear();
    }
    if (sepaElement) {
        sepaElement.clear();
    }
};

// Watch for type changes to remount elements
watch(selectedType, async (newType) => {
    await nextTick();
    if (newType === 'card' && !cardElement) {
        mountElements();
    } else if (newType === 'sepa_debit' && !sepaElement) {
        mountElements();
    }
});

// Watch for modal show to initialize Stripe
watch(() => props.show, async (newValue) => {
    if (newValue) {
        await nextTick();
        if (!stripe) {
            await initializeStripe();
        } else {
            mountElements();
        }
    }
});

onMounted(() => {
    if (props.show) {
        initializeStripe();
    }
});
</script>
