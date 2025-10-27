<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    invitation: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    phone: '',
    date_of_birth: '',
    gender: '',
    gdpr_consent: false,
    terms_consent: false,
});

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const getRoleName = (role) => {
    const roles = {
        'member': 'Mitglied',
        'player': 'Spieler',
        'parent': 'Elternteil',
        'volunteer': 'Freiwilliger',
        'sponsor': 'Sponsor',
    };
    return roles[role] || role;
};

const submit = () => {
    form.post(route('public.club.submit', { token: props.invitation.token }), {
        onSuccess: () => {
            // Redirect handled by controller
        },
    });
};
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <div v-if="invitation.club.logo_url" class="flex justify-center mb-4">
                    <img
                        :src="invitation.club.logo_url"
                        :alt="invitation.club.name"
                        class="h-20 w-20 object-contain"
                    />
                </div>
                <h2 class="text-3xl font-extrabold text-gray-900">
                    Willkommen bei {{ invitation.club.name }}
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    Registrieren Sie sich als {{ getRoleName(invitation.default_role) }}
                </p>
            </div>

            <!-- Club Info -->
            <div v-if="invitation.club.description" class="bg-white rounded-lg shadow-sm p-4 mb-6">
                <p class="text-sm text-gray-700">{{ invitation.club.description }}</p>
            </div>

            <!-- Registration Form -->
            <div class="bg-white shadow-xl rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">
                                Vollständiger Name <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                id="name"
                                required
                                autocomplete="name"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                :class="{ 'border-red-500': form.errors.name }"
                                placeholder="Max Mustermann"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">
                                E-Mail-Adresse <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.email"
                                type="email"
                                id="email"
                                required
                                autocomplete="email"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                :class="{ 'border-red-500': form.errors.email }"
                                placeholder="max@beispiel.de"
                            />
                            <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <!-- Password -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">
                                Passwort <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-1 relative">
                                <input
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    id="password"
                                    required
                                    autocomplete="new-password"
                                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm pr-10"
                                    :class="{ 'border-red-500': form.errors.password }"
                                    placeholder="Mindestens 8 Zeichen"
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center"
                                >
                                    <svg v-if="!showPassword" class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg v-else class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                    </svg>
                                </button>
                            </div>
                            <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">
                                {{ form.errors.password }}
                            </p>
                        </div>

                        <!-- Password Confirmation -->
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                                Passwort bestätigen <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-1 relative">
                                <input
                                    v-model="form.password_confirmation"
                                    :type="showPasswordConfirmation ? 'text' : 'password'"
                                    id="password_confirmation"
                                    required
                                    autocomplete="new-password"
                                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm pr-10"
                                    placeholder="Passwort wiederholen"
                                />
                                <button
                                    type="button"
                                    @click="showPasswordConfirmation = !showPasswordConfirmation"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center"
                                >
                                    <svg v-if="!showPasswordConfirmation" class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg v-else class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Phone (Optional) -->
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700">
                                Telefonnummer (optional)
                            </label>
                            <input
                                v-model="form.phone"
                                type="tel"
                                id="phone"
                                autocomplete="tel"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                placeholder="+49 123 456789"
                            />
                        </div>

                        <!-- Date of Birth (Optional) -->
                        <div>
                            <label for="date_of_birth" class="block text-sm font-medium text-gray-700">
                                Geburtsdatum (optional)
                            </label>
                            <input
                                v-model="form.date_of_birth"
                                type="date"
                                id="date_of_birth"
                                :max="new Date().toISOString().split('T')[0]"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            />
                        </div>

                        <!-- Gender (Optional) -->
                        <div>
                            <label for="gender" class="block text-sm font-medium text-gray-700">
                                Geschlecht (optional)
                            </label>
                            <select
                                v-model="form.gender"
                                id="gender"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            >
                                <option value="">Bitte wählen...</option>
                                <option value="male">Männlich</option>
                                <option value="female">Weiblich</option>
                                <option value="other">Divers</option>
                                <option value="prefer_not_to_say">Keine Angabe</option>
                            </select>
                        </div>

                        <!-- Consents -->
                        <div class="space-y-4 border-t pt-6">
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input
                                        v-model="form.gdpr_consent"
                                        id="gdpr_consent"
                                        type="checkbox"
                                        required
                                        class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded"
                                        :class="{ 'border-red-500': form.errors.gdpr_consent }"
                                    />
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="gdpr_consent" class="font-medium text-gray-700">
                                        Datenschutzerklärung <span class="text-red-500">*</span>
                                    </label>
                                    <p class="text-gray-500">
                                        Ich stimme der Verarbeitung meiner Daten gemäß der
                                        <a href="#" class="text-blue-600 hover:text-blue-500">Datenschutzerklärung</a> zu.
                                    </p>
                                    <p v-if="form.errors.gdpr_consent" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.gdpr_consent }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input
                                        v-model="form.terms_consent"
                                        id="terms_consent"
                                        type="checkbox"
                                        required
                                        class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded"
                                        :class="{ 'border-red-500': form.errors.terms_consent }"
                                    />
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="terms_consent" class="font-medium text-gray-700">
                                        Nutzungsbedingungen <span class="text-red-500">*</span>
                                    </label>
                                    <p class="text-gray-500">
                                        Ich akzeptiere die
                                        <a href="#" class="text-blue-600 hover:text-blue-500">Nutzungsbedingungen</a>.
                                    </p>
                                    <p v-if="form.errors.terms_consent" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.terms_consent }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-4">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out"
                                :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                            >
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ form.processing ? 'Registriere...' : 'Jetzt registrieren' }}
                            </button>
                        </div>

                        <!-- Invitation Info -->
                        <div class="text-center text-xs text-gray-500 pt-4">
                            <p>
                                Diese Einladung ist gültig bis {{ new Date(invitation.expires_at).toLocaleDateString('de-DE') }}
                            </p>
                            <p class="mt-1">
                                Noch {{ invitation.remaining_uses }} von {{ invitation.remaining_uses + form.current_uses }} Plätzen verfügbar
                            </p>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-8 text-center text-sm text-gray-600">
                <p>© {{ new Date().getFullYear() }} {{ invitation.club.name }}. Alle Rechte vorbehalten.</p>
            </div>
        </div>
    </div>
</template>
