<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';

const props = defineProps({
    role: Object,
    permissions: Object,
});

const form = useForm({
    name: props.role.name,
    permissions: props.role.permissions.map(p => p.name),
});

const submit = () => {
    form.put(route('admin.roles.update', props.role.id));
};

const togglePermission = (permissionName) => {
    const index = form.permissions.indexOf(permissionName);
    if (index > -1) {
        form.permissions.splice(index, 1);
    } else {
        form.permissions.push(permissionName);
    }
};

const hasPermission = (permissionName) => {
    return form.permissions.includes(permissionName);
};

const selectAllInCategory = (category) => {
    const categoryPerms = props.permissions[category].map(p => p.name);
    const allSelected = categoryPerms.every(p => form.permissions.includes(p));

    if (allSelected) {
        form.permissions = form.permissions.filter(p => !categoryPerms.includes(p));
    } else {
        categoryPerms.forEach(p => {
            if (!form.permissions.includes(p)) {
                form.permissions.push(p);
            }
        });
    }
};
</script>

<template>
    <AppLayout :title="'Rolle bearbeiten: ' + role.name">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Rolle bearbeiten: <span class="capitalize">{{ role.name.replace('_', ' ') }}</span>
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6">
                        <!-- Role Name -->
                        <div class="mb-8">
                            <InputLabel for="name" value="Rollenname *" />
                            <TextInput
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <!-- Permissions -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                Berechtigungen ({{ form.permissions.length }} ausgewählt)
                            </h3>

                            <div v-for="(perms, category) in permissions" :key="category" class="mb-6">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="text-md font-medium text-gray-700 capitalize">
                                        {{ category }}
                                    </h4>
                                    <button
                                        type="button"
                                        @click="selectAllInCategory(category)"
                                        class="text-sm text-indigo-600 hover:text-indigo-900"
                                    >
                                        Alle auswählen/abwählen
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 pl-4">
                                    <div v-for="permission in perms" :key="permission.id">
                                        <label class="flex items-center cursor-pointer">
                                            <Checkbox
                                                :checked="hasPermission(permission.name)"
                                                @change="togglePermission(permission.name)"
                                            />
                                            <span class="ml-2 text-sm text-gray-600">
                                                {{ permission.name }}
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <InputError class="mt-2" :message="form.errors.permissions" />
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end space-x-4">
                            <SecondaryButton :href="route('admin.roles.index')" as="Link">
                                Abbrechen
                            </SecondaryButton>
                            <PrimaryButton
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                            >
                                Änderungen speichern
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
