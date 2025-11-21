<script setup>
import { useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    user: Object,
});

const form = useForm({
    phone: props.user?.phone ?? '',
    date_of_birth: props.user?.date_of_birth ?? '',
    gender: props.user?.gender ?? '',
    address_street: props.user?.address_street ?? '',
    address_city: props.user?.address_city ?? '',
    address_state: props.user?.address_state ?? '',
    address_zip: props.user?.address_zip ?? '',
    address_country: props.user?.address_country ?? 'DE',
    nationality: props.user?.nationality ?? 'DE',
    bio: props.user?.bio ?? '',
    occupation: props.user?.occupation ?? '',
    employer: props.user?.employer ?? '',
});

const updatePersonalData = () => {
    form.post(route('user.personal-data.update'), {
        errorBag: 'updatePersonalData',
        preserveScroll: true,
    });
};
</script>

<template>
    <FormSection @submitted="updatePersonalData">
        <template #title>
            Persönliche Daten
        </template>

        <template #description>
            Aktualisieren Sie Ihre persönlichen Informationen und Kontaktdaten.
        </template>

        <template #form>
            <!-- Phone -->
            <div class="col-span-6 sm:col-span-4">
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

            <!-- Date of Birth -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="date_of_birth" value="Geburtsdatum" />
                <TextInput
                    id="date_of_birth"
                    v-model="form.date_of_birth"
                    type="date"
                    class="mt-1 block w-full"
                    autocomplete="bday"
                />
                <InputError :message="form.errors.date_of_birth" class="mt-2" />
            </div>

            <!-- Gender -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="gender" value="Geschlecht" />
                <select
                    id="gender"
                    v-model="form.gender"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                >
                    <option value="">Bitte auswählen</option>
                    <option value="male">Männlich</option>
                    <option value="female">Weiblich</option>
                    <option value="other">Divers</option>
                    <option value="prefer_not_to_say">Keine Angabe</option>
                </select>
                <InputError :message="form.errors.gender" class="mt-2" />
            </div>

            <!-- Address Street -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="address_street" value="Straße und Hausnummer" />
                <TextInput
                    id="address_street"
                    v-model="form.address_street"
                    type="text"
                    class="mt-1 block w-full"
                    autocomplete="street-address"
                />
                <InputError :message="form.errors.address_street" class="mt-2" />
            </div>

            <!-- Address City & Zip (Grid) -->
            <div class="col-span-6 sm:col-span-4 grid grid-cols-2 gap-4">
                <div>
                    <InputLabel for="address_zip" value="PLZ" />
                    <TextInput
                        id="address_zip"
                        v-model="form.address_zip"
                        type="text"
                        class="mt-1 block w-full"
                        autocomplete="postal-code"
                    />
                    <InputError :message="form.errors.address_zip" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="address_city" value="Stadt" />
                    <TextInput
                        id="address_city"
                        v-model="form.address_city"
                        type="text"
                        class="mt-1 block w-full"
                        autocomplete="address-level2"
                    />
                    <InputError :message="form.errors.address_city" class="mt-2" />
                </div>
            </div>

            <!-- Address State & Country (Grid) -->
            <div class="col-span-6 sm:col-span-4 grid grid-cols-2 gap-4">
                <div>
                    <InputLabel for="address_state" value="Bundesland" />
                    <TextInput
                        id="address_state"
                        v-model="form.address_state"
                        type="text"
                        class="mt-1 block w-full"
                        autocomplete="address-level1"
                    />
                    <InputError :message="form.errors.address_state" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="address_country" value="Land (ISO-Code)" />
                    <TextInput
                        id="address_country"
                        v-model="form.address_country"
                        type="text"
                        class="mt-1 block w-full"
                        maxlength="2"
                        placeholder="DE"
                        autocomplete="country"
                    />
                    <InputError :message="form.errors.address_country" class="mt-2" />
                </div>
            </div>

            <!-- Nationality -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="nationality" value="Nationalität (ISO-Code)" />
                <TextInput
                    id="nationality"
                    v-model="form.nationality"
                    type="text"
                    class="mt-1 block w-full"
                    maxlength="2"
                    placeholder="DE"
                />
                <InputError :message="form.errors.nationality" class="mt-2" />
            </div>

            <!-- Occupation -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="occupation" value="Beruf" />
                <TextInput
                    id="occupation"
                    v-model="form.occupation"
                    type="text"
                    class="mt-1 block w-full"
                />
                <InputError :message="form.errors.occupation" class="mt-2" />
            </div>

            <!-- Employer -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="employer" value="Arbeitgeber" />
                <TextInput
                    id="employer"
                    v-model="form.employer"
                    type="text"
                    class="mt-1 block w-full"
                />
                <InputError :message="form.errors.employer" class="mt-2" />
            </div>

            <!-- Bio -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="bio" value="Über mich" />
                <textarea
                    id="bio"
                    v-model="form.bio"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                    rows="4"
                />
                <InputError :message="form.errors.bio" class="mt-2" />
            </div>
        </template>

        <template #actions>
            <ActionMessage :on="form.recentlySuccessful" class="me-3">
                Gespeichert.
            </ActionMessage>

            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Speichern
            </PrimaryButton>
        </template>
    </FormSection>
</template>
