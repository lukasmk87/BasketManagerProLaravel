<script setup>
import { ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';

const props = defineProps({
    user: Object,
    roles: Array,
    clubs: Array,
});

const page = usePage();

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    is_active: props.user.is_active,
    roles: props.user.roles.map(role => role.name),
    clubs: props.user.clubs?.map(club => club.id) || [],
});

const submit = () => {
    form.put(route('admin.users.update', props.user.id), {
        onSuccess: () => {
            // Redirect is handled by the controller
        },
    });
};

const canDeleteUser = () => {
    // Get current authenticated user
    const currentUser = page.props.auth?.user;
    if (!currentUser) return false;

    // User can't delete themselves
    if (props.user.id === currentUser.id) {
        return false;
    }

    // Super admins can delete anyone (except themselves)
    // Note: currentUser.roles is a string array ['super_admin', 'admin']
    if (currentUser.roles?.includes('super_admin')) {
        return true;
    }

    // Regular admins can't delete super admins
    // Note: props.user.roles is an object array [{name: 'super_admin'}]
    if (currentUser.roles?.includes('admin')) {
        return !props.user.roles?.some(role => role.name === 'super_admin');
    }

    // Club admins can't delete admins or super admins
    if (currentUser.roles?.includes('club_admin')) {
        return !props.user.roles?.some(role => ['super_admin', 'admin', 'club_admin'].includes(role.name));
    }

    return false;
};

const getDeleteWarningMessage = () => {
    let message = `Sind Sie sicher, dass Sie den Benutzer "${props.user.name}" löschen möchten?\n\n`;

    // Add role information
    if (props.user.roles && props.user.roles.length > 0) {
        message += `Rollen: ${props.user.roles.map(r => r.name).join(', ')}\n`;
    }

    message += '\n';

    // Explain what happens
    message += 'WICHTIG:\n';
    message += '• Wenn der Benutzer aktive Zuordnungen hat (Teams, Clubs), wird er deaktiviert (Soft Delete)\n';
    message += '• Ansonsten wird der Benutzer permanent gelöscht (Hard Delete)\n';
    message += '• Alle zugehörigen Daten werden entsprechend behandelt\n';
    message += '\nDiese Aktion kann nicht rückgängig gemacht werden!\n\n';
    message += 'Möchten Sie fortfahren?';

    return message;
};

const deleteUser = () => {
    // Check permission first
    if (!canDeleteUser()) {
        alert('Sie haben keine Berechtigung, diesen Benutzer zu löschen.\n\n' +
              'Mögliche Gründe:\n' +
              '• Sie können sich nicht selbst löschen\n' +
              '• Sie können keine Super Admins löschen\n' +
              '• Als Club Admin können Sie keine Admins löschen\n\n' +
              'Bitte kontaktieren Sie einen Administrator für weitere Unterstützung.');
        return;
    }

    const message = getDeleteWarningMessage();

    if (confirm(message)) {
        console.log('🗑️ Deleting user:', props.user.id, 'Route:', route('admin.users.destroy', props.user.id));

        router.delete(route('admin.users.destroy', props.user.id), {
            preserveScroll: true,
            onSuccess: () => {
                console.log('✅ User deleted successfully');
                // Success message wird von Backend über Session Flash gesendet
                // User wird zur Users-Liste weitergeleitet
            },
            onError: (errors) => {
                console.error('❌ Delete failed:', errors);
                // Show detailed error message
                let errorMessage = 'Fehler beim Löschen des Benutzers:\n\n';

                if (errors.message) {
                    errorMessage += errors.message;
                } else if (typeof errors === 'string') {
                    errorMessage += errors;
                } else {
                    errorMessage += 'Ein unbekannter Fehler ist aufgetreten.\n\n';
                    errorMessage += 'Mögliche Ursachen:\n';
                    errorMessage += '• Fehlende Berechtigungen\n';
                    errorMessage += '• Der Benutzer existiert nicht mehr\n';
                    errorMessage += '• Datenbankfehler oder Abhängigkeiten\n\n';
                    errorMessage += 'Weitere Details finden Sie in den Server-Logs.';
                }

                alert(errorMessage);
            }
        });
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

const isSuperAdmin = () => {
    const currentUser = page.props.auth?.user;
    return currentUser?.roles?.includes('super_admin');
};
</script>

<template>
    <AdminLayout title="Benutzer bearbeiten">
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

                        <!-- Clubs (nur für Super Admins) -->
                        <div v-if="isSuperAdmin()" class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                Club-Zuweisungen
                            </h3>
                            <p class="text-sm text-gray-600 mb-4">
                                Wählen Sie die Clubs aus, denen dieser Benutzer zugewiesen werden soll.
                            </p>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
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

                            <div v-if="form.clubs.length > 0" class="mt-4 p-4 bg-blue-50 rounded-lg">
                                <p class="text-sm text-blue-800">
                                    <strong>Hinweis:</strong> Die Pivot-Rolle wird automatisch basierend auf den Spatie-Rollen gesetzt:
                                    <span v-if="hasRole('club_admin')" class="font-semibold">Admin</span>
                                    <span v-else class="font-semibold">Member</span>
                                </p>
                            </div>
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
                                    v-if="canDeleteUser()"
                                    type="button"
                                    @click="deleteUser"
                                    class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 active:bg-red-900 transition ease-in-out duration-150"
                                >
                                    Benutzer löschen
                                </button>

                                <button
                                    v-else
                                    type="button"
                                    disabled
                                    class="inline-flex items-center px-4 py-2 bg-gray-400 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest cursor-not-allowed opacity-50"
                                    title="Sie haben keine Berechtigung, diesen Benutzer zu löschen"
                                >
                                    Benutzer löschen (Nicht berechtigt)
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
    </AdminLayout>
</template>