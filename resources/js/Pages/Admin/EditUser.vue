<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';

const props = defineProps({
    user: Object,
    roles: Array,
});

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    is_active: props.user.is_active,
    roles: props.user.roles.map(role => role.name),
});

const submit = () => {
    form.put(route('admin.users.update', props.user.id), {
        onSuccess: () => {
            // Redirect is handled by the controller
        },
    });
};

const deleteUser = () => {
    if (confirm('Sind Sie sicher, dass Sie diesen Benutzer löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.')) {
        router.delete(route('admin.users.destroy', props.user.id));
    }
};

const sendPasswordReset = () => {
    if (confirm('Möchten Sie einen Passwort-Reset-Link an ' + props.user.email + ' senden?')) {
        router.post(route('admin.users.send-password-reset', props.user.id), {}, {
            preserveState: true,
            preserveScroll: true,
        });
    }
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
</script>

<template>
    <AppLayout title="Benutzer bearbeiten">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Benutzer bearbeiten
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Benutzer-Informationen und Rollen verwalten
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
                                    <InputLabel for="name" value="Name" />
                                    <TextInput
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        class="mt-1 block w-full"
                                        required
                                        autofocus
                                    />
                                    <InputError class="mt-2" :message="form.errors.name" />
                                </div>

                                <!-- Email -->
                                <div>
                                    <InputLabel for="email" value="E-Mail" />
                                    <TextInput
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        class="mt-1 block w-full"
                                        required
                                    />
                                    <InputError class="mt-2" :message="form.errors.email" />
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
                        </div>

                        <!-- Roles -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                Rollen
                            </h3>
                            
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

                        <!-- User Information -->
                        <div class="mb-8 p-4 bg-gray-50 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">
                                Benutzer-Informationen
                            </h4>
                            <div class="text-sm text-gray-600 space-y-1">
                                <p><strong>ID:</strong> {{ user.id }}</p>
                                <p><strong>Erstellt:</strong> {{ new Date(user.created_at).toLocaleDateString('de-DE') }}</p>
                                <p><strong>Aktualisiert:</strong> {{ new Date(user.updated_at).toLocaleDateString('de-DE') }}</p>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <button
                                    type="button"
                                    @click="deleteUser"
                                    class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 active:bg-red-900 transition ease-in-out duration-150"
                                >
                                    Benutzer löschen
                                </button>

                                <button
                                    type="button"
                                    @click="sendPasswordReset"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 active:bg-blue-900 transition ease-in-out duration-150"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                    </svg>
                                    Passwort-Reset senden
                                </button>
                            </div>

                            <div class="flex items-center space-x-4">
                                <SecondaryButton :href="route('admin.users')" as="Link">
                                    Abbrechen
                                </SecondaryButton>

                                <PrimaryButton
                                    :class="{ 'opacity-25': form.processing }"
                                    :disabled="form.processing"
                                >
                                    Änderungen speichern
                                </PrimaryButton>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>