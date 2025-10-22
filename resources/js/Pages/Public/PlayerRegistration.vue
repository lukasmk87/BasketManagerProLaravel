<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    invitation: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    date_of_birth: '',
    preferred_position: '',
    height_cm: '',
    weight_kg: '',
    gdpr_consent: false,
});

const positions = [
    { value: 'PG', label: 'Point Guard (PG)' },
    { value: 'SG', label: 'Shooting Guard (SG)' },
    { value: 'SF', label: 'Small Forward (SF)' },
    { value: 'PF', label: 'Power Forward (PF)' },
    { value: 'C', label: 'Center (C)' },
];

// Calculate days until expiration
const daysUntilExpiration = computed(() => {
    if (!props.invitation.expires_at) return null;
    const now = new Date();
    const expiresAt = new Date(props.invitation.expires_at);
    const diff = expiresAt - now;
    const days = Math.ceil(diff / (1000 * 60 * 60 * 24));
    return days;
});

// Format expiration date
const formattedExpiresAt = computed(() => {
    if (!props.invitation.expires_at) return '';
    const date = new Date(props.invitation.expires_at);
    return date.toLocaleDateString('de-DE', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
});

// Max birth date (5 years ago)
const maxBirthDate = computed(() => {
    const date = new Date();
    date.setFullYear(date.getFullYear() - 5);
    return date.toISOString().split('T')[0];
});

// Min birth date (100 years ago)
const minBirthDate = computed(() => {
    const date = new Date();
    date.setFullYear(date.getFullYear() - 100);
    return date.toISOString().split('T')[0];
});

const submit = () => {
    form.post(route('public.player.register.submit', props.invitation.token), {
        onSuccess: () => {
            // Redirect is handled by controller
        },
    });
};
</script>

<template>
    <PublicLayout title="Spieler-Registrierung">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Club Header -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex items-center space-x-4">
                    <!-- Club Logo -->
                    <div v-if="invitation.club.logo_url" class="flex-shrink-0">
                        <img
                            :src="invitation.club.logo_url"
                            :alt="invitation.club.name"
                            class="h-16 w-16 rounded-full object-cover"
                        />
                    </div>
                    <div v-else class="flex-shrink-0">
                        <div class="h-16 w-16 rounded-full bg-indigo-100 flex items-center justify-center">
                            <svg class="h-8 w-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Club Info -->
                    <div class="flex-1">
                        <h1 class="text-2xl font-bold text-gray-900">
                            {{ invitation.club.name }}
                        </h1>
                        <p v-if="invitation.target_team" class="text-sm text-gray-600 mt-1">
                            Registrierung für: <span class="font-medium">{{ invitation.target_team.name }}</span>
                        </p>
                        <p v-else class="text-sm text-gray-600 mt-1">
                            Allgemeine Spieler-Registrierung
                        </p>
                    </div>
                </div>

                <!-- Invitation Info -->
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600">Verfügbare Plätze:</span>
                            <span class="ml-2 font-semibold" :class="invitation.remaining_spots <= 5 ? 'text-red-600' : 'text-green-600'">
                                {{ invitation.remaining_spots }}
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-600">Gültig bis:</span>
                            <span class="ml-2 font-semibold" :class="daysUntilExpiration <= 3 ? 'text-red-600' : 'text-gray-900'">
                                {{ formattedExpiresAt }}
                            </span>
                        </div>
                    </div>
                    <div v-if="daysUntilExpiration <= 7" class="mt-3 flex items-center text-sm text-amber-700 bg-amber-50 rounded-md p-3">
                        <svg class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        Diese Einladung läuft in {{ daysUntilExpiration }} {{ daysUntilExpiration === 1 ? 'Tag' : 'Tagen' }} ab!
                    </div>
                </div>
            </div>

            <!-- Registration Form -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">
                    Registrierungsformular
                </h2>

                <form @submit.prevent="submit" novalidate>
                    <div class="space-y-6">
                        <!-- Personal Information -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- First Name -->
                            <div>
                                <InputLabel for="first_name" value="Vorname *" />
                                <TextInput
                                    id="first_name"
                                    v-model="form.first_name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="Max"
                                    required
                                    autofocus
                                />
                                <InputError :message="form.errors.first_name" class="mt-2" />
                            </div>

                            <!-- Last Name -->
                            <div>
                                <InputLabel for="last_name" value="Nachname *" />
                                <TextInput
                                    id="last_name"
                                    v-model="form.last_name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="Mustermann"
                                    required
                                />
                                <InputError :message="form.errors.last_name" class="mt-2" />
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Email -->
                            <div>
                                <InputLabel for="email" value="E-Mail-Adresse *" />
                                <TextInput
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    class="mt-1 block w-full"
                                    placeholder="max@example.com"
                                    required
                                />
                                <InputError :message="form.errors.email" class="mt-2" />
                            </div>

                            <!-- Phone -->
                            <div>
                                <InputLabel for="phone" value="Telefonnummer" />
                                <TextInput
                                    id="phone"
                                    v-model="form.phone"
                                    type="tel"
                                    class="mt-1 block w-full"
                                    placeholder="+49 123 456789"
                                />
                                <InputError :message="form.errors.phone" class="mt-2" />
                            </div>
                        </div>

                        <!-- Date of Birth -->
                        <div>
                            <InputLabel for="date_of_birth" value="Geburtsdatum *" />
                            <TextInput
                                id="date_of_birth"
                                v-model="form.date_of_birth"
                                type="date"
                                class="mt-1 block w-full md:w-1/2"
                                :min="minBirthDate"
                                :max="maxBirthDate"
                                required
                            />
                            <InputError :message="form.errors.date_of_birth" class="mt-2" />
                        </div>

                        <!-- Basketball Information -->
                        <div class="pt-4 border-t border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                Basketball-Informationen
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Position -->
                                <div class="md:col-span-3">
                                    <InputLabel for="preferred_position" value="Bevorzugte Position *" />
                                    <select
                                        id="preferred_position"
                                        v-model="form.preferred_position"
                                        class="mt-1 block w-full md:w-1/2 border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        required
                                    >
                                        <option value="">Position auswählen</option>
                                        <option v-for="pos in positions" :key="pos.value" :value="pos.value">
                                            {{ pos.label }}
                                        </option>
                                    </select>
                                    <InputError :message="form.errors.preferred_position" class="mt-2" />
                                </div>

                                <!-- Height -->
                                <div>
                                    <InputLabel for="height_cm" value="Größe (cm)" />
                                    <TextInput
                                        id="height_cm"
                                        v-model="form.height_cm"
                                        type="number"
                                        class="mt-1 block w-full"
                                        placeholder="180"
                                        min="100"
                                        max="250"
                                    />
                                    <InputError :message="form.errors.height_cm" class="mt-2" />
                                </div>

                                <!-- Weight -->
                                <div>
                                    <InputLabel for="weight_kg" value="Gewicht (kg)" />
                                    <TextInput
                                        id="weight_kg"
                                        v-model="form.weight_kg"
                                        type="number"
                                        class="mt-1 block w-full"
                                        placeholder="75"
                                        min="30"
                                        max="200"
                                    />
                                    <InputError :message="form.errors.weight_kg" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <!-- GDPR Consent -->
                        <div class="pt-4 border-t border-gray-200">
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input
                                        id="gdpr_consent"
                                        v-model="form.gdpr_consent"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        required
                                    />
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="gdpr_consent" class="font-medium text-gray-700">
                                        Datenschutzerklärung *
                                    </label>
                                    <p class="text-gray-600 mt-1">
                                        Ich habe die <a href="/datenschutz" target="_blank" class="text-indigo-600 hover:text-indigo-500 underline">Datenschutzerklärung</a>
                                        gelesen und stimme der Verarbeitung meiner personenbezogenen Daten gemäß DSGVO zu.
                                        Mir ist bewusst, dass ich diese Einwilligung jederzeit widerrufen kann.
                                    </p>
                                    <InputError :message="form.errors.gdpr_consent" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <!-- Rate Limit Info -->
                        <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3 flex-1">
                                    <p class="text-sm text-blue-700">
                                        Aus Sicherheitsgründen sind maximal 5 Registrierungsversuche pro Minute erlaubt.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex items-center justify-end pt-6">
                            <PrimaryButton
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                                class="w-full md:w-auto"
                            >
                                <span v-if="form.processing">Wird gesendet...</span>
                                <span v-else>Jetzt registrieren</span>
                            </PrimaryButton>
                        </div>

                        <p class="text-xs text-gray-500 text-center">
                            * Pflichtfelder
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </PublicLayout>
</template>
