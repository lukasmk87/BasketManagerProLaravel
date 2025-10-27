<script setup>
import { ref, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    users: Object,
    roles: Array,
    role_stats: Object,
    filters: Object,
});

// Search and filter state
const search = ref(props.filters.search || '');
const selectedRole = ref(props.filters.role || '');
const selectedStatus = ref(props.filters.status !== null ? props.filters.status : '');

// Watch for changes and update URL
watch([search, selectedRole, selectedStatus], ([newSearch, newRole, newStatus]) => {
    router.get(route('admin.users'), {
        search: newSearch || undefined,
        role: newRole || undefined,
        status: newStatus !== '' ? newStatus : undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, { debounce: 300 });

const clearFilters = () => {
    search.value = '';
    selectedRole.value = '';
    selectedStatus.value = '';
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('de-DE');
};

const getStatusBadge = (isActive) => {
    return isActive
        ? 'bg-green-100 text-green-800'
        : 'bg-red-100 text-red-800';
};

const getStatusText = (isActive) => {
    return isActive ? 'Aktiv' : 'Inaktiv';
};

const sendPasswordReset = (user) => {
    if (confirm('Möchten Sie einen Passwort-Reset-Link an ' + user.email + ' senden?')) {
        router.post(route('admin.users.send-password-reset', user.id), {}, {
            preserveState: true,
            preserveScroll: true,
        });
    }
};

const deleteUser = (user) => {
    if (confirm(`Möchten Sie den Benutzer "${user.name}" wirklich löschen?\n\nDiese Aktion kann nicht rückgängig gemacht werden.`)) {
        router.delete(route('admin.users.destroy', user.id), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                // Success message wird von Backend über Session Flash gesendet
            },
            onError: (errors) => {
                alert('Fehler beim Löschen des Benutzers: ' + (errors.message || 'Unbekannter Fehler'));
            },
        });
    }
};
</script>

<template>
    <AppLayout title="Benutzer-Verwaltung">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Benutzer-Verwaltung
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Benutzer und Rollen verwalten
                    </p>
                </div>
                
                <div class="flex items-center space-x-3">
                    <SecondaryButton :href="route('admin.settings')" as="Link">
                        Zurück zum Admin Panel
                    </SecondaryButton>

                    <SecondaryButton :href="route('admin.users.import.index')" as="Link">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        Bulk-Import
                    </SecondaryButton>

                    <PrimaryButton :href="route('admin.users.create')" as="Link">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Neuer Benutzer
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Gesamt Benutzer</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ users.total }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-for="(count, roleName) in role_stats" :key="roleName" class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate capitalize">{{ roleName }}</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ count }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white shadow rounded-lg mb-8">
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <!-- Search -->
                            <div>
                                <label for="search" class="block text-sm font-medium text-gray-700">Suchen</label>
                                <TextInput
                                    id="search"
                                    v-model="search"
                                    type="text"
                                    placeholder="Name oder E-Mail..."
                                    class="mt-1 block w-full" />
                            </div>

                            <!-- Role Filter -->
                            <div>
                                <label for="role" class="block text-sm font-medium text-gray-700">Rolle</label>
                                <select
                                    id="role"
                                    v-model="selectedRole"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">Alle Rollen</option>
                                    <option v-for="role in roles" :key="role.id" :value="role.name">
                                        {{ role.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Status Filter -->
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                                <select
                                    id="status"
                                    v-model="selectedStatus"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">Alle Status</option>
                                    <option value="1">Aktiv</option>
                                    <option value="0">Inaktiv</option>
                                </select>
                            </div>

                            <!-- Clear Filters -->
                            <div class="flex items-end">
                                <SecondaryButton @click="clearFilters" class="w-full">
                                    Filter zurücksetzen
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Benutzer
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Rollen
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Clubs
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Registriert
                                    </th>
                                    <th scope="col" class="relative px-6 py-3">
                                        <span class="sr-only">Aktionen</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                                                <div class="text-sm text-gray-500">{{ user.email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-wrap gap-1">
                                            <span v-for="role in user.roles" :key="role.id"
                                                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ role.name }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ user.clubs_count }} Club(s)
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="[
                                            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                            getStatusBadge(user.is_active)
                                        ]">
                                            {{ getStatusText(user.is_active) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatDate(user.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end space-x-3">
                                            <Link
                                                :href="route('admin.users.edit', user.id)"
                                                class="text-indigo-600 hover:text-indigo-900"
                                            >
                                                Bearbeiten
                                            </Link>
                                            <button
                                                @click="sendPasswordReset(user)"
                                                class="text-blue-600 hover:text-blue-900"
                                                title="Passwort-Reset senden"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                                </svg>
                                            </button>
                                            <button
                                                v-if="user.id !== $page.props.auth.user.id"
                                                @click="deleteUser(user)"
                                                class="text-red-600 hover:text-red-900"
                                                title="Benutzer löschen"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty State -->
                    <div v-if="users.data.length === 0" class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Keine Benutzer gefunden</h3>
                        <p class="mt-1 text-sm text-gray-500">Versuchen Sie, Ihre Filter anzupassen.</p>
                    </div>

                    <!-- Pagination -->
                    <div v-if="users.data.length > 0" class="px-6 py-4 border-t border-gray-200">
                        <Pagination :links="users.links" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>