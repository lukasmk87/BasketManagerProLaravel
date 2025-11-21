<script setup>
import { ref } from 'vue';
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

// Parse JSON fields with fallback
const parseJsonField = (field, defaultValue = []) => {
    try {
        return field ? JSON.parse(field) : defaultValue;
    } catch (e) {
        return defaultValue;
    }
};

const medicalConditions = ref(parseJsonField(props.user?.medical_conditions, []));
const allergies = ref(parseJsonField(props.user?.allergies, []));
const medications = ref(parseJsonField(props.user?.medications, []));

const form = useForm({
    emergency_contact_name: props.user?.emergency_contact_name ?? '',
    emergency_contact_phone: props.user?.emergency_contact_phone ?? '',
    emergency_contact_relationship: props.user?.emergency_contact_relationship ?? '',
    blood_type: props.user?.blood_type ?? '',
    medical_conditions: medicalConditions.value,
    allergies: allergies.value,
    medications: medications.value,
    medical_consent: props.user?.medical_consent ?? false,
});

const addMedicalCondition = () => {
    form.medical_conditions.push({ name: '', notes: '' });
};

const removeMedicalCondition = (index) => {
    form.medical_conditions.splice(index, 1);
};

const addAllergy = () => {
    form.allergies.push({ name: '', severity: '', notes: '' });
};

const removeAllergy = (index) => {
    form.allergies.splice(index, 1);
};

const addMedication = () => {
    form.medications.push({ name: '', dosage: '', frequency: '' });
};

const removeMedication = (index) => {
    form.medications.splice(index, 1);
};

const updateEmergencyMedical = () => {
    form.post(route('user.emergency-medical.update'), {
        errorBag: 'updateEmergencyMedical',
        preserveScroll: true,
    });
};
</script>

<template>
    <FormSection @submitted="updateEmergencyMedical">
        <template #title>
            Notfall & Medizinische Daten
        </template>

        <template #description>
            <div class="space-y-2">
                <p>Diese Informationen werden nur im Notfall verwendet.</p>
                <div class="flex items-center text-sm text-amber-600">
                    <svg class="size-5 me-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    Diese Daten sind über das QR-Code Emergency-System zugänglich.
                </div>
            </div>
        </template>

        <template #form>
            <!-- Emergency Contact Name -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="emergency_contact_name" value="Notfallkontakt Name" />
                <TextInput
                    id="emergency_contact_name"
                    v-model="form.emergency_contact_name"
                    type="text"
                    class="mt-1 block w-full"
                />
                <InputError :message="form.errors.emergency_contact_name" class="mt-2" />
            </div>

            <!-- Emergency Contact Phone -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="emergency_contact_phone" value="Notfallkontakt Telefon" />
                <TextInput
                    id="emergency_contact_phone"
                    v-model="form.emergency_contact_phone"
                    type="tel"
                    class="mt-1 block w-full"
                />
                <InputError :message="form.errors.emergency_contact_phone" class="mt-2" />
            </div>

            <!-- Emergency Contact Relationship -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="emergency_contact_relationship" value="Beziehung" />
                <TextInput
                    id="emergency_contact_relationship"
                    v-model="form.emergency_contact_relationship"
                    type="text"
                    class="mt-1 block w-full"
                    placeholder="z.B. Mutter, Vater, Ehepartner..."
                />
                <InputError :message="form.errors.emergency_contact_relationship" class="mt-2" />
            </div>

            <!-- Blood Type -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="blood_type" value="Blutgruppe" />
                <select
                    id="blood_type"
                    v-model="form.blood_type"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                >
                    <option value="">Bitte auswählen</option>
                    <option value="A+">A+</option>
                    <option value="A-">A-</option>
                    <option value="B+">B+</option>
                    <option value="B-">B-</option>
                    <option value="AB+">AB+</option>
                    <option value="AB-">AB-</option>
                    <option value="0+">0+</option>
                    <option value="0-">0-</option>
                </select>
                <InputError :message="form.errors.blood_type" class="mt-2" />
            </div>

            <!-- Medical Conditions -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel value="Medizinische Bedingungen" />
                <p class="text-xs text-gray-500 mb-2">Chronische Erkrankungen, Vorerkrankungen, etc.</p>

                <div v-if="form.medical_conditions.length === 0" class="text-sm text-gray-500 italic">
                    Keine Bedingungen hinzugefügt
                </div>

                <div
                    v-for="(condition, index) in form.medical_conditions"
                    :key="index"
                    class="mt-3 p-3 border border-gray-200 rounded-md bg-gray-50"
                >
                    <div class="space-y-2">
                        <TextInput
                            v-model="condition.name"
                            type="text"
                            class="block w-full"
                            placeholder="Name der Erkrankung"
                        />
                        <textarea
                            v-model="condition.notes"
                            class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            rows="2"
                            placeholder="Zusätzliche Informationen..."
                        />
                    </div>
                    <button
                        type="button"
                        class="mt-2 text-sm text-red-600 hover:text-red-800"
                        @click="removeMedicalCondition(index)"
                    >
                        Entfernen
                    </button>
                </div>

                <button
                    type="button"
                    class="mt-3 text-sm text-indigo-600 hover:text-indigo-800"
                    @click="addMedicalCondition"
                >
                    + Bedingung hinzufügen
                </button>
                <InputError :message="form.errors.medical_conditions" class="mt-2" />
            </div>

            <!-- Allergies -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel value="Allergien" />
                <p class="text-xs text-gray-500 mb-2">Lebensmittel-, Medikamenten- oder andere Allergien</p>

                <div v-if="form.allergies.length === 0" class="text-sm text-gray-500 italic">
                    Keine Allergien hinzugefügt
                </div>

                <div
                    v-for="(allergy, index) in form.allergies"
                    :key="index"
                    class="mt-3 p-3 border border-red-100 rounded-md bg-red-50"
                >
                    <div class="space-y-2">
                        <TextInput
                            v-model="allergy.name"
                            type="text"
                            class="block w-full"
                            placeholder="Name der Allergie"
                        />
                        <select
                            v-model="allergy.severity"
                            class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        >
                            <option value="">Schweregrad</option>
                            <option value="mild">Leicht</option>
                            <option value="moderate">Mittel</option>
                            <option value="severe">Schwer</option>
                            <option value="life_threatening">Lebensbedrohlich</option>
                        </select>
                        <textarea
                            v-model="allergy.notes"
                            class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            rows="2"
                            placeholder="Reaktionen, Notfallmaßnahmen..."
                        />
                    </div>
                    <button
                        type="button"
                        class="mt-2 text-sm text-red-600 hover:text-red-800"
                        @click="removeAllergy(index)"
                    >
                        Entfernen
                    </button>
                </div>

                <button
                    type="button"
                    class="mt-3 text-sm text-red-600 hover:text-red-800"
                    @click="addAllergy"
                >
                    + Allergie hinzufügen
                </button>
                <InputError :message="form.errors.allergies" class="mt-2" />
            </div>

            <!-- Medications -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel value="Medikamente" />
                <p class="text-xs text-gray-500 mb-2">Regelmäßig eingenommene Medikamente</p>

                <div v-if="form.medications.length === 0" class="text-sm text-gray-500 italic">
                    Keine Medikamente hinzugefügt
                </div>

                <div
                    v-for="(medication, index) in form.medications"
                    :key="index"
                    class="mt-3 p-3 border border-blue-100 rounded-md bg-blue-50"
                >
                    <div class="space-y-2">
                        <TextInput
                            v-model="medication.name"
                            type="text"
                            class="block w-full"
                            placeholder="Medikamentenname"
                        />
                        <div class="grid grid-cols-2 gap-2">
                            <TextInput
                                v-model="medication.dosage"
                                type="text"
                                class="block w-full"
                                placeholder="Dosierung (z.B. 500mg)"
                            />
                            <TextInput
                                v-model="medication.frequency"
                                type="text"
                                class="block w-full"
                                placeholder="Häufigkeit (z.B. 2x täglich)"
                            />
                        </div>
                    </div>
                    <button
                        type="button"
                        class="mt-2 text-sm text-red-600 hover:text-red-800"
                        @click="removeMedication(index)"
                    >
                        Entfernen
                    </button>
                </div>

                <button
                    type="button"
                    class="mt-3 text-sm text-indigo-600 hover:text-indigo-800"
                    @click="addMedication"
                >
                    + Medikament hinzufügen
                </button>
                <InputError :message="form.errors.medications" class="mt-2" />
            </div>

            <!-- Medical Consent -->
            <div class="col-span-6 sm:col-span-4">
                <label class="flex items-start">
                    <input
                        v-model="form.medical_consent"
                        type="checkbox"
                        class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                    >
                    <span class="ms-2 text-sm text-gray-600">
                        Ich erlaube die Verwendung dieser medizinischen Informationen im Notfall durch berechtigtes Personal (Trainer, medizinisches Personal, Rettungsdienste).
                    </span>
                </label>
                <InputError :message="form.errors.medical_consent" class="mt-2" />
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
