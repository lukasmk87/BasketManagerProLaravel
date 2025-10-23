<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';

const props = defineProps({
    roles: Array,
    clubs: Array,
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    phone: '',
    date_of_birth: '',
    gender: '',
    is_active: true,
    roles: [],
    clubs: [],
    send_credentials_email: true,
});

const submit = () => {
    form.post(route('admin.users.store'), {
        onSuccess: () => {
            form.reset();
        },
    });
};

const toggleRole = (roleName) => {
    const index = form.roles.indexOf(roleName);
    if (index > -1) {
        form.roles.splice(index, 1);
    } else {
        form.roles.push(roleName);
    }
};

const hasRole = (roleName) => {
    return form.roles.includes(roleName);
};

const toggleClub = (clubId) => {
    const index = form.clubs.indexOf(clubId);
    if (index > -1) {
        form.clubs.splice(index, 1);
    } else {
        form.clubs.push(clubId);
    }
};

const hasClub = (clubId) => {
    return form.clubs.includes(clubId);
};

const generateRandomPassword = () => {
    const length = 12;
    const charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*';
    let password = '';
    for (let i = 0; i < length; i++) {
        password += charset.charAt(Math.floor(Math.random() * charset.length));
    }
    form.password = password;
};
</script>

<template>
    <AppLayout title="Neuer Benutzer">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Neuer Benutzer
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Neuen Benutzer anlegen und Rollen zuweisen
                    </p>
                </div>

                <div class="flex items-center space-x-3">
                    <SecondaryButton :href="route('admin.users')" as="Link">
                        Zurück zur Benutzer-Liste
                    </SecondaryButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6">
                        <!-- Basic Information -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                Grundinformationen
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Name -->
                                <div>
                                    <InputLabel for="name" value="Name *" />
                                    <TextInput
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        class="mt-1 block w-full"
                                        required
                                        autofocus
                                        placeholder="Max Mustermann"
                                    />
                                    <InputError class="mt-2" :message="form.errors.name" />
                                </div>

                                <!-- Email -->
                                <div>
                                    <InputLabel for="email" value="E-Mail *" />
                                    <TextInput
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        class="mt-1 block w-full"
                                        required
                                        placeholder="max@example.com"
                                    />
                                    <InputError class="mt-2" :message="form.errors.email" />
                                </div>

                                <!-- Password -->
                                <div>
                                    <InputLabel for="password" value="Passwort *" />
                                    <div class="flex gap-2">
                                        <TextInput
                                            id="password"
                                            v-model="form.password"
                                            type="text"
                                            class="mt-1 block w-full"
                                            required
                                            placeholder="Mindestens 8 Zeichen"
                                        />
                                        <button
                                            type="button"
                                            @click="generateRandomPassword"
                                            class="mt-1 px-3 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm rounded-md"
                                            title="Zufälliges Passwort generieren"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <InputError class="mt-2" :message="form.errors.password" />
                                    <p class="mt-1 text-xs text-gray-500">
                                        Der Benutzer sollte das Passwort nach dem ersten Login ändern.
                                    </p>
                                </div>

                                <!-- Phone -->
                                <div>
                                    <InputLabel for="phone" value="Telefon" />
                                    <TextInput
                                        id="phone"
                                        v-model="form.phone"
                                        type="tel"
                                        class="mt-1 block w-full"
                                        placeholder="+49 123 456789"
                                    />
                                    <InputError class="mt-2" :message="form.errors.phone" />
                                </div>

                                <!-- Date of Birth -->
                                <div>
                                    <InputLabel for="date_of_birth" value="Geburtsdatum" />
                                    <TextInput
                                        id="date_of_birth"
                                        v-model="form.date_of_birth"
                                        type="date"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError class="mt-2" :message="form.errors.date_of_birth" />
                                </div>

                                <!-- Gender -->
                                <div>
                                    <InputLabel for="gender" value="Geschlecht" />
                                    <select
                                        id="gender"
                                        v-model="form.gender"
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    >
                                        <option value="">Bitte wählen</option>
                                        <option value="male">Männlich</option>
                                        <option value="female">Weiblich</option>
                                        <option value="other">Divers</option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.gender" />
                                </div>
                            </div>

                            <!-- Active Status -->
                            <div class="mt-6">
                                <label class="flex items-center">
                                    <Checkbox
                                        v-model:checked="form.is_active"
                                        name="is_active"
                                    />
                                    <span class="ml-2 text-sm text-gray-600">Benutzer ist aktiv</span>
                                </label>
                                <InputError class="mt-2" :message="form.errors.is_active" />
                            </div>

                            <!-- Send Credentials Email -->
                            <div class="mt-4">
                                <label class="flex items-center">
                                    <Checkbox
                                        v-model:checked="form.send_credentials_email"
                                        name="send_credentials_email"
                                    />
                                    <span class="ml-2 text-sm text-gray-600">
                                        E-Mail mit Zugangsdaten an Benutzer senden
                                    </span>
                                </label>
                                <p class="ml-6 mt-1 text-xs text-gray-500">
                                    Der Benutzer erhält eine E-Mail mit Benutzername und Passwort.
                                </p>
                                <InputError class="mt-2" :message="form.errors.send_credentials_email" />
                            </div>
                        </div>

                        <!-- Roles -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                Rollen *
                            </h3>
                            <p class="text-sm text-gray-600 mb-4">
                                Wählen Sie mindestens eine Rolle für den Benutzer.
                            </p>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div v-for="role in roles" :key="role.id" class="flex items-center">
                                    <label class="flex items-center cursor-pointer">
                                        <Checkbox
                                            :checked="hasRole(role.name)"
                                            @change="toggleRole(role.name)"
                                        />
                                        <span class="ml-2 text-sm text-gray-600 capitalize">
                                            {{ role.name.replace('_', ' ') }}
                                        </span>
                                    </label>
                                </div>
                            </div>
                            <InputError class="mt-2" :message="form.errors.roles" />
                        </div>

                        <!-- Club Assignment -->
                        <div class="mb-8" v-if="clubs.length > 0">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                Club-Zuordnung
                            </h3>
                            <p class="text-sm text-gray-600 mb-4">
                                Optional: Ordnen Sie den Benutzer einem oder mehreren Clubs zu.
                            </p>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div v-for="club in clubs" :key="club.id" class="flex items-center">
                                    <label class="flex items-center cursor-pointer">
                                        <Checkbox
                                            :checked="hasClub(club.id)"
                                            @change="toggleClub(club.id)"
                                        />
                                        <span class="ml-2 text-sm text-gray-600">
                                            {{ club.name }}
                                        </span>
                                    </label>
                                </div>
                            </div>
                            <InputError class="mt-2" :message="form.errors.clubs" />
                        </div>

                        <!-- Info Box -->
                        <div class="mb-8 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div class="ml-3 flex-1">
                                    <h3 class="text-sm font-medium text-blue-800">
                                        Wichtige Hinweise
                                    </h3>
                                    <div class="mt-2 text-sm text-blue-700">
                                        <ul class="list-disc list-inside space-y-1">
                                            <li>Der Benutzer erhält eine E-Mail mit seinen Zugangsdaten (falls konfiguriert)</li>
                                            <li>Das generierte Passwort sollte beim ersten Login geändert werden</li>
                                            <li>Mindestens eine Rolle muss ausgewählt werden</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-end space-x-4">
                            <SecondaryButton :href="route('admin.users')" as="Link">
                                Abbrechen
                            </SecondaryButton>

                            <PrimaryButton
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                            >
                                Benutzer anlegen
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
