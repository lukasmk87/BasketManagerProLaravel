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
    coaches: {
        type: Array,
        default: () => [],
    },
    age_groups: {
        type: Array,
        default: () => [],
    },
    genders: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    name: '',
    season: new Date().getFullYear() + '/' + (new Date().getFullYear() + 1),
    league: '',
    age_group: '',
    gender: 'mixed',
    head_coach_id: null,
    is_active: true,
});

const submit = () => {
    form.post(route('club-admin.teams.store'), {
        onSuccess: () => {
            // Redirect will be handled by backend
        },
    });
};
</script>

<template>
    <ClubAdminLayout title="Team erstellen">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Neues Team erstellen
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        {{ club.name }}
                    </p>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                    <form @submit.prevent="submit" class="p-6 space-y-6">
                        <!-- Team Name -->
                        <div>
                            <InputLabel for="name" value="Team-Name *" />
                            <TextInput
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full"
                                required
                                autofocus
                                placeholder="z.B. U16 männlich"
                            />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>

                        <!-- Season -->
                        <div>
                            <InputLabel for="season" value="Saison *" />
                            <TextInput
                                id="season"
                                v-model="form.season"
                                type="text"
                                class="mt-1 block w-full"
                                required
                                placeholder="2024/2025"
                            />
                            <InputError :message="form.errors.season" class="mt-2" />
                        </div>

                        <!-- League -->
                        <div>
                            <InputLabel for="league" value="Liga" />
                            <TextInput
                                id="league"
                                v-model="form.league"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="z.B. Regionalliga, Kreisliga, etc."
                            />
                            <InputError :message="form.errors.league" class="mt-2" />
                        </div>

                        <!-- Age Group -->
                        <div>
                            <InputLabel for="age_group" value="Altersklasse" />
                            <select
                                id="age_group"
                                v-model="form.age_group"
                                class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                            >
                                <option value="">Bitte wählen</option>
                                <option v-for="group in age_groups" :key="group" :value="group">
                                    {{ group }}
                                </option>
                            </select>
                            <InputError :message="form.errors.age_group" class="mt-2" />
                        </div>

                        <!-- Gender -->
                        <div>
                            <InputLabel for="gender" value="Geschlecht *" />
                            <select
                                id="gender"
                                v-model="form.gender"
                                class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                required
                            >
                                <option v-for="gender in genders" :key="gender.value" :value="gender.value">
                                    {{ gender.label }}
                                </option>
                            </select>
                            <InputError :message="form.errors.gender" class="mt-2" />
                        </div>

                        <!-- Head Coach -->
                        <div>
                            <InputLabel for="head_coach_id" value="Cheftrainer" />
                            <select
                                id="head_coach_id"
                                v-model="form.head_coach_id"
                                class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                            >
                                <option :value="null">Kein Trainer zugewiesen</option>
                                <option v-for="coach in coaches" :key="coach.id" :value="coach.id">
                                    {{ coach.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.head_coach_id" class="mt-2" />
                        </div>

                        <!-- Active Status -->
                        <div class="flex items-center">
                            <input
                                id="is_active"
                                v-model="form.is_active"
                                type="checkbox"
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                            />
                            <label for="is_active" class="ml-2 block text-sm text-gray-900">
                                Team ist aktiv
                            </label>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-end gap-4 pt-4 border-t">
                            <a
                                :href="route('club-admin.teams')"
                                class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                Abbrechen
                            </a>

                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Team erstellen
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </ClubAdminLayout>
</template>
