<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    invitation: {
        type: Object,
        required: true,
    },
});

const currentStep = ref(1);
const totalSteps = 3;

const form = useForm({
    // Step 1: Personal Information
    first_name: '',
    last_name: '',
    birth_date: '',

    // Step 2: Contact Information
    email: '',
    phone: '',
    street: '',
    postal_code: '',
    city: '',
    country: 'Deutschland',

    // Step 3: Basketball Information
    position: null,
    height: null,
    experience: '',

    // GDPR
    gdpr_consent: false,
    newsletter_consent: false,
});

// Basketball positions
const positions = [
    { value: 'PG', label: 'Point Guard (PG) - Aufbauspieler' },
    { value: 'SG', label: 'Shooting Guard (SG) - Shooting Guard' },
    { value: 'SF', label: 'Small Forward (SF) - Kleiner Flügelspieler' },
    { value: 'PF', label: 'Power Forward (PF) - Kraftflügel' },
    { value: 'C', label: 'Center (C) - Center' },
];

// Check if current step is valid
const isStepValid = computed(() => {
    if (currentStep.value === 1) {
        return form.first_name && form.last_name && form.birth_date;
    }
    if (currentStep.value === 2) {
        return form.email && form.phone;
    }
    if (currentStep.value === 3) {
        return form.gdpr_consent;
    }
    return false;
});

// Navigate steps
const nextStep = () => {
    if (currentStep.value < totalSteps && isStepValid.value) {
        currentStep.value++;
    }
};

const previousStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

// Submit form
const submit = () => {
    if (!isStepValid.value) return;

    form.post(route('public.player.register.submit', props.invitation.token), {
        onSuccess: () => {
            // Will redirect to success page
        },
    });
};

// Format expiry date
const formatExpiryDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('de-DE', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};
</script>

<template>
    <PublicLayout :title="`Spieler-Registrierung - ${invitation.club.name}`">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Club Header -->
            <div class="text-center mb-8">
                <div v-if="invitation.club.logo_url" class="flex justify-center mb-4">
                    <img
                        :src="invitation.club.logo_url"
                        :alt="invitation.club.name"
                        class="h-24 w-auto"
                    />
                </div>
                <h1 class="text-3xl font-bold text-gray-900">
                    {{ invitation.club.name }}
                </h1>
                <p class="mt-2 text-lg text-gray-600">
                    Spieler-Registrierung
                </p>
                <p v-if="invitation.target_team" class="mt-1 text-sm text-gray-500">
                    Für Team: {{ invitation.target_team.name }}
                </p>
            </div>

            <!-- Progress Steps -->
            <div class="mb-8">
                <nav aria-label="Progress">
                    <ol class="flex items-center">
                        <li
                            v-for="step in totalSteps"
                            :key="step"
                            :class="[
                                'relative',
                                step < totalSteps ? 'pr-8 sm:pr-20' : '',
                                'flex-1',
                            ]"
                        >
                            <!-- Connector Line -->
                            <div
                                v-if="step < totalSteps"
                                class="absolute inset-0 flex items-center"
                                aria-hidden="true"
                            >
                                <div
                                    class="h-0.5 w-full"
                                    :class="step < currentStep ? 'bg-blue-600' : 'bg-gray-200'"
                                />
                            </div>

                            <!-- Step Indicator -->
                            <div class="relative flex items-center justify-center">
                                <div
                                    :class="[
                                        'h-8 w-8 rounded-full flex items-center justify-center border-2 transition-colors',
                                        step < currentStep
                                            ? 'bg-blue-600 border-blue-600'
                                            : step === currentStep
                                            ? 'bg-white border-blue-600'
                                            : 'bg-white border-gray-300',
                                    ]"
                                >
                                    <span
                                        v-if="step < currentStep"
                                        class="text-white font-medium"
                                    >
                                        ✓
                                    </span>
                                    <span
                                        v-else
                                        :class="[
                                            'font-medium',
                                            step === currentStep ? 'text-blue-600' : 'text-gray-500',
                                        ]"
                                    >
                                        {{ step }}
                                    </span>
                                </div>
                            </div>

                            <!-- Step Label -->
                            <div class="mt-2 text-center">
                                <span
                                    class="text-xs font-medium"
                                    :class="step <= currentStep ? 'text-blue-600' : 'text-gray-500'"
                                >
                                    {{
                                        step === 1
                                            ? 'Persönliches'
                                            : step === 2
                                            ? 'Kontakt'
                                            : 'Basketball'
                                    }}
                                </span>
                            </div>
                        </li>
                    </ol>
                </nav>
            </div>

            <!-- Form Container -->
            <div class="bg-white shadow-xl rounded-lg overflow-hidden">
                <form @submit.prevent="currentStep === totalSteps ? submit() : nextStep()">
                    <div class="p-6 sm:p-8">
                        <!-- Step 1: Personal Information -->
                        <div v-show="currentStep === 1" class="space-y-6">
                            <h2 class="text-2xl font-bold text-gray-900">Persönliche Daten</h2>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">
                                        Vorname *
                                    </label>
                                    <input
                                        id="first_name"
                                        v-model="form.first_name"
                                        type="text"
                                        required
                                        class="w-full px-4 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500"
                                        :class="{ 'border-red-500': form.errors.first_name }"
                                    />
                                    <p v-if="form.errors.first_name" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.first_name }}
                                    </p>
                                </div>

                                <div>
                                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">
                                        Nachname *
                                    </label>
                                    <input
                                        id="last_name"
                                        v-model="form.last_name"
                                        type="text"
                                        required
                                        class="w-full px-4 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500"
                                        :class="{ 'border-red-500': form.errors.last_name }"
                                    />
                                    <p v-if="form.errors.last_name" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.last_name }}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <label for="birth_date" class="block text-sm font-medium text-gray-700 mb-1">
                                    Geburtsdatum *
                                </label>
                                <input
                                    id="birth_date"
                                    v-model="form.birth_date"
                                    type="date"
                                    required
                                    class="w-full px-4 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500"
                                    :class="{ 'border-red-500': form.errors.birth_date }"
                                />
                                <p v-if="form.errors.birth_date" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.birth_date }}
                                </p>
                            </div>
                        </div>

                        <!-- Step 2: Contact Information -->
                        <div v-show="currentStep === 2" class="space-y-6">
                            <h2 class="text-2xl font-bold text-gray-900">Kontaktdaten</h2>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div class="sm:col-span-2">
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                                        E-Mail-Adresse *
                                    </label>
                                    <input
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        required
                                        class="w-full px-4 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500"
                                        :class="{ 'border-red-500': form.errors.email }"
                                    />
                                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.email }}
                                    </p>
                                </div>

                                <div class="sm:col-span-2">
                                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
                                        Telefonnummer *
                                    </label>
                                    <input
                                        id="phone"
                                        v-model="form.phone"
                                        type="tel"
                                        required
                                        placeholder="z.B. +49 123 456789"
                                        class="w-full px-4 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500"
                                        :class="{ 'border-red-500': form.errors.phone }"
                                    />
                                    <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.phone }}
                                    </p>
                                </div>
                            </div>

                            <div class="border-t border-gray-200 pt-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Adresse (Optional)</h3>

                                <div class="space-y-4">
                                    <div>
                                        <label for="street" class="block text-sm font-medium text-gray-700 mb-1">
                                            Straße und Hausnummer
                                        </label>
                                        <input
                                            id="street"
                                            v-model="form.street"
                                            type="text"
                                            class="w-full px-4 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500"
                                            :class="{ 'border-red-500': form.errors.street }"
                                        />
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                        <div>
                                            <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-1">
                                                PLZ
                                            </label>
                                            <input
                                                id="postal_code"
                                                v-model="form.postal_code"
                                                type="text"
                                                class="w-full px-4 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500"
                                            />
                                        </div>

                                        <div class="sm:col-span-2">
                                            <label for="city" class="block text-sm font-medium text-gray-700 mb-1">
                                                Ort
                                            </label>
                                            <input
                                                id="city"
                                                v-model="form.city"
                                                type="text"
                                                class="w-full px-4 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Basketball Information & GDPR -->
                        <div v-show="currentStep === 3" class="space-y-6">
                            <h2 class="text-2xl font-bold text-gray-900">Basketball-Informationen</h2>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <label for="position" class="block text-sm font-medium text-gray-700 mb-1">
                                        Bevorzugte Position (Optional)
                                    </label>
                                    <select
                                        id="position"
                                        v-model="form.position"
                                        class="w-full px-4 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500"
                                    >
                                        <option :value="null">-- Position auswählen --</option>
                                        <option v-for="pos in positions" :key="pos.value" :value="pos.value">
                                            {{ pos.label }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label for="height" class="block text-sm font-medium text-gray-700 mb-1">
                                        Körpergröße in cm (Optional)
                                    </label>
                                    <input
                                        id="height"
                                        v-model.number="form.height"
                                        type="number"
                                        min="100"
                                        max="250"
                                        placeholder="z.B. 180"
                                        class="w-full px-4 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500"
                                    />
                                </div>
                            </div>

                            <div>
                                <label for="experience" class="block text-sm font-medium text-gray-700 mb-1">
                                    Basketball-Erfahrung (Optional)
                                </label>
                                <textarea
                                    id="experience"
                                    v-model="form.experience"
                                    rows="3"
                                    placeholder="Bisherige Vereine, Erfahrung, besondere Fähigkeiten..."
                                    class="w-full px-4 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500"
                                />
                            </div>

                            <!-- GDPR Section -->
                            <div class="border-t border-gray-200 pt-6 space-y-4">
                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input
                                            id="gdpr_consent"
                                            v-model="form.gdpr_consent"
                                            type="checkbox"
                                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                            :class="{ 'border-red-500': form.errors.gdpr_consent }"
                                            required
                                        />
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="gdpr_consent" class="font-medium text-gray-700">
                                            Datenschutzerklärung *
                                        </label>
                                        <p class="text-gray-500">
                                            Ich habe die
                                            <a href="/datenschutz" target="_blank" class="text-blue-600 hover:text-blue-800 underline">
                                                Datenschutzerklärung
                                            </a>
                                            gelesen und akzeptiere sie.
                                        </p>
                                        <p v-if="form.errors.gdpr_consent" class="mt-1 text-red-600">
                                            {{ form.errors.gdpr_consent }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input
                                            id="newsletter_consent"
                                            v-model="form.newsletter_consent"
                                            type="checkbox"
                                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                        />
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="newsletter_consent" class="font-medium text-gray-700">
                                            Newsletter (Optional)
                                        </label>
                                        <p class="text-gray-500">
                                            Ich möchte per E-Mail über Neuigkeiten informiert werden.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="bg-gray-50 px-6 py-4 sm:px-8 flex items-center justify-between border-t border-gray-200">
                        <button
                            v-if="currentStep > 1"
                            type="button"
                            @click="previousStep"
                            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Zurück
                        </button>
                        <div v-else />

                        <button
                            type="submit"
                            :disabled="!isStepValid || form.processing"
                            class="inline-flex items-center px-6 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 transition ease-in-out duration-150"
                        >
                            <svg
                                v-if="form.processing"
                                class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                            </svg>
                            {{ currentStep === totalSteps ? 'Registrierung abschließen' : 'Weiter' }}
                            <svg
                                v-if="currentStep < totalSteps"
                                class="w-4 h-4 ml-2"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Info Box -->
            <div class="mt-6 bg-blue-50 border-l-4 border-blue-400 p-4 rounded-r-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            Nach der Registrierung wird Ihre Anmeldung von einem Club-Administrator geprüft und einem Team zugewiesen. Sie erhalten dann eine E-Mail mit Ihren Zugangsdaten.
                        </p>
                        <p v-if="invitation.remaining_spots > 0 && invitation.remaining_spots <= 10" class="mt-2 text-sm font-medium text-blue-800">
                            Noch {{ invitation.remaining_spots }} Plätze verfügbar!
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
