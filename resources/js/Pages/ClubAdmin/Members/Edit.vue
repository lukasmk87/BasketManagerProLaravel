<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ClubAdminLayout from '@/Layouts/ClubAdminLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    club: {
        type: Object,
        required: true,
    },
    member: {
        type: Object,
        required: true,
    },
    available_roles: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    name: props.member.name,
    email: props.member.email,
    phone: props.member.phone || '',
    club_role: props.member.club_role,
    is_active: props.member.is_active,
    membership_is_active: props.member.membership_is_active,
});

const submit = () => {
    form.put(route('club-admin.members.update', props.member.id), {
        onSuccess: () => {
            // Redirect will be handled by backend
        },
    });
};
</script>

<template>
    <ClubAdminLayout title="Mitglied bearbeiten">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Mitglied bearbeiten
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        {{ club.name }} &middot; {{ member.name }}
                    </p>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                    <form @submit.prevent="submit" class="p-6 space-y-6">
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
                                autocomplete="name"
                            />
                            <InputError :message="form.errors.name" class="mt-2" />
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
                                autocomplete="email"
                            />
                            <InputError :message="form.errors.email" class="mt-2" />
                        </div>

                        <!-- Phone -->
                        <div>
                            <InputLabel for="phone" value="Telefon" />
                            <TextInput
                                id="phone"
                                v-model="form.phone"
                                type="tel"
                                class="mt-1 block w-full"
                                autocomplete="tel"
                            />
                            <InputError :message="form.errors.phone" class="mt-2" />
                        </div>

                        <!-- Club Role -->
                        <div>
                            <InputLabel for="club_role" value="Club-Rolle *" />
                            <select
                                id="club_role"
                                v-model="form.club_role"
                                class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                required
                            >
                                <option v-for="role in available_roles" :key="role.value" :value="role.value">
                                    {{ role.label }}
                                </option>
                            </select>
                            <InputError :message="form.errors.club_role" class="mt-2" />
                            <p class="mt-1 text-sm text-gray-500">
                                Die Rolle bestimmt die Berechtigungen des Mitglieds innerhalb Ihres Clubs.
                            </p>
                        </div>

                        <!-- User Active Status -->
                        <div class="flex items-center">
                            <input
                                id="is_active"
                                v-model="form.is_active"
                                type="checkbox"
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                            />
                            <label for="is_active" class="ml-2 block text-sm text-gray-900">
                                Benutzer ist global aktiv
                            </label>
                        </div>

                        <!-- Membership Active Status -->
                        <div class="flex items-center">
                            <input
                                id="membership_is_active"
                                v-model="form.membership_is_active"
                                type="checkbox"
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                            />
                            <label for="membership_is_active" class="ml-2 block text-sm text-gray-900">
                                Mitgliedschaft in diesem Club ist aktiv
                            </label>
                        </div>

                        <!-- Member Info -->
                        <div class="border-t pt-4">
                            <p class="text-sm text-gray-600">
                                <strong>Beigetreten:</strong> {{ new Date(member.joined_at).toLocaleDateString('de-DE') }}
                            </p>
                            <p class="text-sm text-gray-600 mt-1">
                                <strong>System-Rollen:</strong> {{ member.roles.join(', ') || 'Keine' }}
                            </p>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-end gap-4 pt-4 border-t">
                            <a
                                :href="route('club-admin.members')"
                                class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                Abbrechen
                            </a>

                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Aktualisieren
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </ClubAdminLayout>
</template>
