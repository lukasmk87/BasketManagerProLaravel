<script setup>
import { ref, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    roles: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');

watch(search, (newSearch) => {
    router.get(route('admin.roles.index'), {
        search: newSearch || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, { debounce: 300 });

const deleteRole = (role) => {
    if (confirm('Möchten Sie die Rolle "' + role.name + '" wirklich löschen?')) {
        router.delete(route('admin.roles.destroy', role.id));
    }
};
</script>

<template>
    <AppLayout title="Rollen-Verwaltung">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Rollen-Verwaltung
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Rollen und Berechtigungen verwalten
                    </p>
                </div>

                <div class="flex items-center space-x-3">
                    <SecondaryButton :href="route('admin.settings')" as="Link">
                        Zurück zum Admin Panel
                    </SecondaryButton>

                    <PrimaryButton :href="route('admin.roles.create')" as="Link">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Neue Rolle
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Search -->
                <div class="bg-white shadow rounded-lg mb-8">
                    <div class="p-6">
                        <div class="max-w-md">
                            <label for="search" class="block text-sm font-medium text-gray-700">Suchen</label>
                            <TextInput
                                id="search"
                                v-model="search"
                                type="text"
                                placeholder="Rollenname..."
                                class="mt-1 block w-full" />
                        </div>
                    </div>
                </div>

                <!-- Roles Table -->
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Rolle
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Berechtigungen
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Benutzer
                                    </th>
                                    <th scope="col" class="relative px-6 py-3">
                                        <span class="sr-only">Aktionen</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="role in roles" :key="role.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 capitalize">
                                            {{ role.name.replace('_', ' ') }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ role.permissions_count }} Berechtigungen
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            {{ role.users_count }} Benutzer
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end space-x-3">
                                            <Link
                                                :href="route('admin.roles.edit', role.id)"
                                                class="text-indigo-600 hover:text-indigo-900"
                                            >
                                                Bearbeiten
                                            </Link>
                                            <button
                                                v-if="!['super_admin', 'admin', 'club_admin'].includes(role.name)"
                                                @click="deleteRole(role)"
                                                class="text-red-600 hover:text-red-900"
                                            >
                                                Löschen
                                            </button>
                                            <span
                                                v-else
                                                class="text-gray-400 cursor-not-allowed"
                                                title="System-Rolle kann nicht gelöscht werden"
                                            >
                                                Löschen
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty State -->
                    <div v-if="roles.length === 0" class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Keine Rollen gefunden</h3>
                        <p class="mt-1 text-sm text-gray-500">Beginnen Sie mit der Erstellung einer neuen Rolle.</p>
                        <div class="mt-6">
                            <PrimaryButton :href="route('admin.roles.create')" as="Link">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                Neue Rolle
                            </PrimaryButton>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
