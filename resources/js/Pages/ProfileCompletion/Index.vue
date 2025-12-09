<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    requiredFields: {
        type: Array,
        required: true,
    },
    club: {
        type: Object,
        default: null,
    },
    clubRole: {
        type: String,
        default: null,
    },
});

const form = useForm({
    phone: props.user?.phone ?? '',
    date_of_birth: props.user?.date_of_birth ?? '',
    gender: props.user?.gender ?? '',
    emergency_contact_name: props.user?.emergency_contact_name ?? '',
    emergency_contact_phone: props.user?.emergency_contact_phone ?? '',
});

const isRequired = (field) => props.requiredFields.includes(field);

const needsEmergencyContact = computed(() => {
    return isRequired('emergency_contact_name') || isRequired('emergency_contact_phone');
});

const roleLabel = computed(() => {
    const roles = {
        'player': 'Spieler',
        'trainer': 'Trainer',
        'member': 'Mitglied',
        'parent': 'Elternteil',
        'volunteer': 'Freiwilliger',
        'sponsor': 'Sponsor',
    };
    return roles[props.clubRole] || 'Mitglied';
});

const submit = () => {
    form.post(route('profile-completion.store'));
};
</script>

<template>
    <Head title="Profil vervollständigen" />

    <div class="min-h-screen bg-gradient-to-b from-orange-50 to-white">
        <!-- Header -->
        <header class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-center">
                    <a href="/" class="flex items-center space-x-2">
                        <img
                            src="/images/logo.svg"
                            alt="BasketManager Pro"
                            class="h-10 w-auto"
                            onerror="this.style.display='none'"
                        />
                        <span class="text-xl font-bold text-gray-900">BasketManager Pro</span>
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="py-8">
            <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Club Info -->
                <div v-if="club" class="text-center mb-8">
                    <img
                        v-if="club.logo_url"
                        :src="club.logo_url"
                        :alt="club.name"
                        class="h-16 w-16 mx-auto mb-4 rounded-full object-cover"
                    />
                    <h1 class="text-2xl font-bold text-gray-900">
                        Willkommen bei {{ club.name }}!
                    </h1>
                    <p class="text-gray-600 mt-2">
                        Sie wurden als <strong>{{ roleLabel }}</strong> eingeladen.
                        Bitte vervollständigen Sie Ihr Profil um fortzufahren.
                    </p>
                </div>

                <div v-else class="text-center mb-8">
                    <h1 class="text-2xl font-bold text-gray-900">
                        Profil vervollständigen
                    </h1>
                    <p class="text-gray-600 mt-2">
                        Bitte vervollständigen Sie Ihr Profil um fortzufahren.
                    </p>
                </div>

                <!-- Flash Messages -->
                <div
                    v-if="$page.props.flash?.success"
                    class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700"
                >
                    {{ $page.props.flash.success }}
                </div>

                <div
                    v-if="$page.props.flash?.error || form.errors.incomplete"
                    class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700"
                >
                    {{ $page.props.flash?.error || form.errors.incomplete }}
                </div>

                <!-- Form -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Required Fields Info -->
                        <p class="text-sm text-gray-500">
                            Felder mit <span class="text-red-500">*</span> sind Pflichtfelder.
                        </p>

                        <!-- Phone -->
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700">
                                Telefon <span v-if="isRequired('phone')" class="text-red-500">*</span>
                            </label>
                            <input
                                id="phone"
                                v-model="form.phone"
                                type="tel"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500"
                                :class="{ 'border-red-500': form.errors.phone }"
                                placeholder="+49 123 456789"
                                :required="isRequired('phone')"
                            />
                            <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">
                                {{ form.errors.phone }}
                            </p>
                        </div>

                        <!-- Date of Birth -->
                        <div>
                            <label for="date_of_birth" class="block text-sm font-medium text-gray-700">
                                Geburtsdatum <span v-if="isRequired('date_of_birth')" class="text-red-500">*</span>
                            </label>
                            <input
                                id="date_of_birth"
                                v-model="form.date_of_birth"
                                type="date"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500"
                                :class="{ 'border-red-500': form.errors.date_of_birth }"
                                :required="isRequired('date_of_birth')"
                            />
                            <p v-if="form.errors.date_of_birth" class="mt-1 text-sm text-red-600">
                                {{ form.errors.date_of_birth }}
                            </p>
                        </div>

                        <!-- Gender (Optional) -->
                        <div>
                            <label for="gender" class="block text-sm font-medium text-gray-700">
                                Geschlecht
                            </label>
                            <select
                                id="gender"
                                v-model="form.gender"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500"
                            >
                                <option value="">Bitte wählen...</option>
                                <option value="male">Männlich</option>
                                <option value="female">Weiblich</option>
                                <option value="other">Divers</option>
                                <option value="prefer_not_to_say">Keine Angabe</option>
                            </select>
                        </div>

                        <!-- Emergency Contact Section (for players) -->
                        <div v-if="needsEmergencyContact" class="border-t pt-6 mt-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                Notfallkontakt
                            </h3>
                            <p class="text-sm text-gray-500 mb-4">
                                Für Ihre Sicherheit beim Training und Spielen benötigen wir einen Notfallkontakt.
                            </p>

                            <div class="space-y-4">
                                <!-- Emergency Contact Name -->
                                <div>
                                    <label for="emergency_contact_name" class="block text-sm font-medium text-gray-700">
                                        Name des Notfallkontakts
                                        <span v-if="isRequired('emergency_contact_name')" class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="emergency_contact_name"
                                        v-model="form.emergency_contact_name"
                                        type="text"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500"
                                        :class="{ 'border-red-500': form.errors.emergency_contact_name }"
                                        placeholder="z.B. Maria Müller"
                                        :required="isRequired('emergency_contact_name')"
                                    />
                                    <p v-if="form.errors.emergency_contact_name" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.emergency_contact_name }}
                                    </p>
                                </div>

                                <!-- Emergency Contact Phone -->
                                <div>
                                    <label for="emergency_contact_phone" class="block text-sm font-medium text-gray-700">
                                        Telefon des Notfallkontakts
                                        <span v-if="isRequired('emergency_contact_phone')" class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="emergency_contact_phone"
                                        v-model="form.emergency_contact_phone"
                                        type="tel"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500"
                                        :class="{ 'border-red-500': form.errors.emergency_contact_phone }"
                                        placeholder="+49 123 456789"
                                        :required="isRequired('emergency_contact_phone')"
                                    />
                                    <p v-if="form.errors.emergency_contact_phone" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.emergency_contact_phone }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-6">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <svg
                                    v-if="form.processing"
                                    class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ form.processing ? 'Wird gespeichert...' : 'Profil vervollständigen' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Help Text -->
                <p class="text-center text-sm text-gray-500 mt-6">
                    Diese Daten können Sie später in Ihrem Profil jederzeit ändern.
                </p>
            </div>
        </main>
    </div>
</template>
