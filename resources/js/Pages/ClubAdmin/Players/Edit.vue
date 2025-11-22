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
    player: {
        type: Object,
        required: true,
    },
    teams: {
        type: Array,
        default: () => [],
    },
    positions: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    name: props.player.name,
    email: props.player.email || '',
    birth_date: props.player.birth_date || '',
    phone: props.player.phone || '',
    status: props.player.status,
    team_id: props.player.team_id || null,
    jersey_number: props.player.jersey_number || null,
    primary_position: props.player.primary_position || '',
});

const submit = () => {
    form.put(route('club-admin.players.update', props.player.id), {
        onSuccess: () => {
            // Redirect will be handled by backend
        },
    });
};
</script>

<template>
    <ClubAdminLayout title="Spieler bearbeiten">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Spieler bearbeiten
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        {{ club.name }} &middot; {{ player.name }}
                    </p>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                    <form @submit.prevent="submit" class="p-6 space-y-6">
                        <h3 class="text-lg font-medium text-gray-900">Persönliche Informationen</h3>

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
                            />
                            <InputError :message="form.errors.email" class="mt-2" />
                        </div>

                        <!-- Birth Date -->
                        <div>
                            <InputLabel for="birth_date" value="Geburtsdatum" />
                            <TextInput
                                id="birth_date"
                                v-model="form.birth_date"
                                type="date"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.birth_date" class="mt-2" />
                        </div>

                        <!-- Phone -->
                        <div>
                            <InputLabel for="phone" value="Telefon" />
                            <TextInput
                                id="phone"
                                v-model="form.phone"
                                type="tel"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.phone" class="mt-2" />
                        </div>

                        <!-- Status -->
                        <div>
                            <InputLabel for="status" value="Status *" />
                            <select
                                id="status"
                                v-model="form.status"
                                class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                required
                            >
                                <option value="active">Aktiv</option>
                                <option value="inactive">Inaktiv</option>
                                <option value="injured">Verletzt</option>
                                <option value="suspended">Gesperrt</option>
                            </select>
                            <InputError :message="form.errors.status" class="mt-2" />
                        </div>

                        <h3 class="text-lg font-medium text-gray-900 pt-4 border-t">Team-Zuweisung</h3>

                        <!-- Team -->
                        <div>
                            <InputLabel for="team_id" value="Team" />
                            <select
                                id="team_id"
                                v-model="form.team_id"
                                class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                            >
                                <option :value="null">Kein Team zugewiesen</option>
                                <option v-for="team in teams" :key="team.id" :value="team.id">
                                    {{ team.name }} ({{ team.season }})
                                </option>
                            </select>
                            <InputError :message="form.errors.team_id" class="mt-2" />
                        </div>

                        <!-- Jersey Number -->
                        <div>
                            <InputLabel for="jersey_number" value="Trikotnummer" />
                            <TextInput
                                id="jersey_number"
                                v-model="form.jersey_number"
                                type="number"
                                min="0"
                                max="99"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.jersey_number" class="mt-2" />
                        </div>

                        <!-- Primary Position -->
                        <div>
                            <InputLabel for="primary_position" value="Position" />
                            <select
                                id="primary_position"
                                v-model="form.primary_position"
                                class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                            >
                                <option value="">Keine Position</option>
                                <option v-for="position in positions" :key="position" :value="position">
                                    {{ position }}
                                </option>
                            </select>
                            <InputError :message="form.errors.primary_position" class="mt-2" />
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-end gap-4 pt-4 border-t">
                            <a
                                :href="route('club-admin.players')"
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
