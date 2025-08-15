<template>
    <AppLayout :title="`${player.user?.name || player.first_name + ' ' + player.last_name} bearbeiten`">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ player.user?.name || player.first_name + ' ' + player.last_name }} bearbeiten
                </h2>
                <SecondaryButton 
                    :href="route('players.show', player.id)"
                    as="Link"
                >
                    Zurück
                </SecondaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- First Name -->
                            <div>
                                <InputLabel for="first_name" value="Vorname*" />
                                <TextInput
                                    id="first_name"
                                    v-model="form.first_name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                    autofocus
                                />
                                <InputError :message="form.errors.first_name" class="mt-2" />
                            </div>

                            <!-- Last Name -->
                            <div>
                                <InputLabel for="last_name" value="Nachname*" />
                                <TextInput
                                    id="last_name"
                                    v-model="form.last_name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                />
                                <InputError :message="form.errors.last_name" class="mt-2" />
                            </div>

                            <!-- Team -->
                            <div>
                                <InputLabel for="team_id" value="Team*" />
                                <select
                                    id="team_id"
                                    v-model="form.team_id"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    required
                                >
                                    <option value="">Team auswählen</option>
                                    <option v-for="team in teams" :key="team.id" :value="team.id">
                                        {{ team.name }} ({{ team.club.name }})
                                    </option>
                                </select>
                                <InputError :message="form.errors.team_id" class="mt-2" />
                            </div>

                            <!-- Jersey Number -->
                            <div>
                                <InputLabel for="jersey_number" value="Rückennummer*" />
                                <TextInput
                                    id="jersey_number"
                                    v-model="form.jersey_number"
                                    type="number"
                                    class="mt-1 block w-full"
                                    min="0"
                                    max="99"
                                    required
                                />
                                <InputError :message="form.errors.jersey_number" class="mt-2" />
                            </div>

                            <!-- Primary Position -->
                            <div>
                                <InputLabel for="primary_position" value="Hauptposition*" />
                                <select
                                    id="primary_position"
                                    v-model="form.primary_position"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    required
                                >
                                    <option value="">Position wählen</option>
                                    <option value="PG">Point Guard (PG)</option>
                                    <option value="SG">Shooting Guard (SG)</option>
                                    <option value="SF">Small Forward (SF)</option>
                                    <option value="PF">Power Forward (PF)</option>
                                    <option value="C">Center (C)</option>
                                </select>
                                <InputError :message="form.errors.primary_position" class="mt-2" />
                            </div>

                            <!-- Secondary Position -->
                            <div>
                                <InputLabel for="secondary_position" value="Zweitposition" />
                                <select
                                    id="secondary_position"
                                    v-model="form.secondary_position"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                >
                                    <option value="">Keine</option>
                                    <option value="PG">Point Guard (PG)</option>
                                    <option value="SG">Shooting Guard (SG)</option>
                                    <option value="SF">Small Forward (SF)</option>
                                    <option value="PF">Power Forward (PF)</option>
                                    <option value="C">Center (C)</option>
                                </select>
                                <InputError :message="form.errors.secondary_position" class="mt-2" />
                            </div>

                            <!-- Height -->
                            <div>
                                <InputLabel for="height" value="Größe (cm)" />
                                <TextInput
                                    id="height"
                                    v-model="form.height"
                                    type="number"
                                    class="mt-1 block w-full"
                                    min="100"
                                    max="250"
                                />
                                <InputError :message="form.errors.height" class="mt-2" />
                            </div>

                            <!-- Weight -->
                            <div>
                                <InputLabel for="weight" value="Gewicht (kg)" />
                                <TextInput
                                    id="weight"
                                    v-model="form.weight"
                                    type="number"
                                    class="mt-1 block w-full"
                                    min="30"
                                    max="200"
                                />
                                <InputError :message="form.errors.weight" class="mt-2" />
                            </div>

                            <!-- Birth Date -->
                            <div>
                                <InputLabel for="birth_date" value="Geburtsdatum" />
                                <TextInput
                                    id="birth_date"
                                    v-model="form.birth_date"
                                    type="date"
                                    class="mt-1 block w-full"
                                    :max="maxBirthDate"
                                />
                                <InputError :message="form.errors.birth_date" class="mt-2" />
                            </div>

                            <!-- Nationality -->
                            <div>
                                <InputLabel for="nationality" value="Nationalität" />
                                <TextInput
                                    id="nationality"
                                    v-model="form.nationality"
                                    type="text"
                                    class="mt-1 block w-full"
                                    maxlength="2"
                                    placeholder="DE"
                                />
                                <InputError :message="form.errors.nationality" class="mt-2" />
                                <div class="text-xs text-gray-500 mt-1">2-Buchstaben Ländercode (z.B. DE, US)</div>
                            </div>

                            <!-- Status -->
                            <div>
                                <InputLabel for="status" value="Status*" />
                                <select
                                    id="status"
                                    v-model="form.status"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    required
                                >
                                    <option value="active">Aktiv</option>
                                    <option value="inactive">Inaktiv</option>
                                    <option value="injured">Verletzt</option>
                                    <option value="suspended">Gesperrt</option>
                                </select>
                                <InputError :message="form.errors.status" class="mt-2" />
                            </div>

                            <!-- Captain -->
                            <div>
                                <label class="flex items-center">
                                    <input
                                        type="checkbox"
                                        v-model="form.is_captain"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    />
                                    <span class="ml-2">Kapitän</span>
                                </label>
                            </div>

                            <!-- Starter -->
                            <div>
                                <label class="flex items-center">
                                    <input
                                        type="checkbox"
                                        v-model="form.is_starter"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    />
                                    <span class="ml-2">Stammspieler</span>
                                </label>
                            </div>

                            <!-- Notes -->
                            <div class="md:col-span-2">
                                <InputLabel for="notes" value="Notizen" />
                                <textarea
                                    id="notes"
                                    v-model="form.notes"
                                    rows="4"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    maxlength="1000"
                                ></textarea>
                                <InputError :message="form.errors.notes" class="mt-2" />
                                <div class="text-xs text-gray-500 mt-1">Max. 1000 Zeichen</div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex items-center justify-between mt-6">
                            <DangerButton
                                v-if="can?.delete"
                                type="button"
                                @click="confirmingPlayerDeletion = true"
                            >
                                Spieler löschen
                            </DangerButton>

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

        <!-- Delete Player Confirmation Modal -->
        <ConfirmationModal :show="confirmingPlayerDeletion" @close="confirmingPlayerDeletion = false">
            <template #title>
                Spieler löschen
            </template>

            <template #content>
                Sind Sie sicher, dass Sie diesen Spieler löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.
            </template>

            <template #footer>
                <SecondaryButton @click="confirmingPlayerDeletion = false">
                    Abbrechen
                </SecondaryButton>

                <DangerButton
                    class="ml-3"
                    :class="{ 'opacity-25': deleteForm.processing }"
                    :disabled="deleteForm.processing"
                    @click="deletePlayerConfirmed"
                >
                    Spieler löschen
                </DangerButton>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'
import TextInput from '@/Components/TextInput.vue'
import InputLabel from '@/Components/InputLabel.vue'
import InputError from '@/Components/InputError.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'

const props = defineProps({
    player: Object,
    teams: Array,
    can: Object,
})

const form = useForm({
    first_name: props.player.first_name || '',
    last_name: props.player.last_name || '',
    team_id: props.player.team_id || '',
    jersey_number: props.player.jersey_number || '',
    primary_position: props.player.primary_position || '',
    secondary_position: props.player.secondary_position || '',
    height: props.player.height || null,
    weight: props.player.weight || null,
    birth_date: props.player.birth_date || '',
    nationality: props.player.nationality || '',
    is_captain: props.player.is_captain || false,
    is_starter: props.player.is_starter || false,
    status: props.player.status || 'active',
    notes: props.player.notes || '',
})

const deleteForm = useForm({})

const confirmingPlayerDeletion = ref(false)

const maxBirthDate = computed(() => {
    const today = new Date()
    return today.toISOString().split('T')[0]
})

const submit = () => {
    form.put(route('players.update', props.player.id))
}

const deletePlayerConfirmed = () => {
    deleteForm.delete(route('players.destroy', props.player.id))
}
</script>