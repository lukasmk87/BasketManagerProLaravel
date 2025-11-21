<script setup>
import { ref, computed } from 'vue';
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
const parseJsonField = (field, defaultValue = null) => {
    try {
        return field ? JSON.parse(field) : defaultValue;
    } catch (e) {
        return defaultValue;
    }
};

const basketballExperience = ref(parseJsonField(props.user?.basketball_experience, { years: '', level_description: '' }));
const preferredPositions = ref(parseJsonField(props.user?.preferred_positions, []));
const coachingCertifications = ref(parseJsonField(props.user?.coaching_certifications, []));
const refereeCertifications = ref(parseJsonField(props.user?.referee_certifications, []));

const form = useForm({
    basketball_experience: basketballExperience.value,
    preferred_positions: preferredPositions.value,
    skill_level: props.user?.skill_level ?? '',
    player_profile_active: props.user?.player_profile_active ?? false,
    coaching_certifications: coachingCertifications.value,
    referee_certifications: refereeCertifications.value,
});

const availablePositions = [
    { value: 'PG', label: 'Point Guard (PG)' },
    { value: 'SG', label: 'Shooting Guard (SG)' },
    { value: 'SF', label: 'Small Forward (SF)' },
    { value: 'PF', label: 'Power Forward (PF)' },
    { value: 'C', label: 'Center (C)' },
];

const togglePosition = (position) => {
    const index = form.preferred_positions.indexOf(position);
    if (index > -1) {
        form.preferred_positions.splice(index, 1);
    } else {
        form.preferred_positions.push(position);
    }
};

const isPositionSelected = (position) => {
    return form.preferred_positions.includes(position);
};

const addCoachingCertification = () => {
    form.coaching_certifications.push({ name: '', year: '', issuer: '' });
};

const removeCoachingCertification = (index) => {
    form.coaching_certifications.splice(index, 1);
};

const addRefereeCertification = () => {
    form.referee_certifications.push({ name: '', year: '', issuer: '' });
};

const removeRefereeCertification = (index) => {
    form.referee_certifications.splice(index, 1);
};

const updateBasketballData = () => {
    form.post(route('user.basketball-data.update'), {
        errorBag: 'updateBasketballData',
        preserveScroll: true,
    });
};
</script>

<template>
    <FormSection @submitted="updateBasketballData">
        <template #title>
            Basketball-Daten
        </template>

        <template #description>
            Verwalten Sie Ihre Basketball-Erfahrung, bevorzugte Positionen und Zertifikate.
        </template>

        <template #form>
            <!-- Player Profile Active -->
            <div class="col-span-6 sm:col-span-4">
                <label class="flex items-center">
                    <input
                        v-model="form.player_profile_active"
                        type="checkbox"
                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                    >
                    <span class="ms-2 text-sm text-gray-600">Spielerprofil aktivieren</span>
                </label>
                <p class="text-xs text-gray-500 mt-1">
                    Aktivieren Sie diese Option, um als Spieler sichtbar zu sein.
                </p>
            </div>

            <!-- Skill Level -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="skill_level" value="Erfahrungslevel" />
                <select
                    id="skill_level"
                    v-model="form.skill_level"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                >
                    <option value="">Bitte auswählen</option>
                    <option value="beginner">Anfänger</option>
                    <option value="intermediate">Fortgeschritten</option>
                    <option value="advanced">Erfahren</option>
                    <option value="professional">Professionell</option>
                </select>
                <InputError :message="form.errors.skill_level" class="mt-2" />
            </div>

            <!-- Basketball Experience -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel value="Basketball-Erfahrung" />
                <div class="mt-2 space-y-3">
                    <div>
                        <InputLabel for="experience_years" value="Jahre Erfahrung" />
                        <TextInput
                            id="experience_years"
                            v-model="form.basketball_experience.years"
                            type="number"
                            class="mt-1 block w-full"
                            min="0"
                        />
                    </div>
                    <div>
                        <InputLabel for="experience_description" value="Beschreibung" />
                        <textarea
                            id="experience_description"
                            v-model="form.basketball_experience.level_description"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            rows="3"
                            placeholder="z.B. Spieler seit 2010, Erfahrung in Regionalliga..."
                        />
                    </div>
                </div>
                <InputError :message="form.errors.basketball_experience" class="mt-2" />
            </div>

            <!-- Preferred Positions -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel value="Bevorzugte Positionen" />
                <p class="text-xs text-gray-500 mb-2">Wählen Sie eine oder mehrere Positionen aus.</p>
                <div class="mt-2 space-y-2">
                    <label
                        v-for="position in availablePositions"
                        :key="position.value"
                        class="flex items-center"
                    >
                        <input
                            type="checkbox"
                            :checked="isPositionSelected(position.value)"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                            @change="togglePosition(position.value)"
                        >
                        <span class="ms-2 text-sm text-gray-600">{{ position.label }}</span>
                    </label>
                </div>
                <InputError :message="form.errors.preferred_positions" class="mt-2" />
            </div>

            <!-- Coaching Certifications -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel value="Trainer-Lizenzen" />
                <p class="text-xs text-gray-500 mb-2">Fügen Sie Ihre Trainer-Lizenzen hinzu.</p>

                <div v-if="form.coaching_certifications.length === 0" class="text-sm text-gray-500 italic">
                    Keine Lizenzen hinzugefügt
                </div>

                <div
                    v-for="(cert, index) in form.coaching_certifications"
                    :key="index"
                    class="mt-3 p-3 border border-gray-200 rounded-md"
                >
                    <div class="space-y-2">
                        <TextInput
                            v-model="cert.name"
                            type="text"
                            class="block w-full"
                            placeholder="Lizenz-Name (z.B. C-Lizenz)"
                        />
                        <div class="grid grid-cols-2 gap-2">
                            <TextInput
                                v-model="cert.year"
                                type="number"
                                class="block w-full"
                                placeholder="Jahr"
                            />
                            <TextInput
                                v-model="cert.issuer"
                                type="text"
                                class="block w-full"
                                placeholder="Aussteller (z.B. DBB)"
                            />
                        </div>
                    </div>
                    <button
                        type="button"
                        class="mt-2 text-sm text-red-600 hover:text-red-800"
                        @click="removeCoachingCertification(index)"
                    >
                        Entfernen
                    </button>
                </div>

                <button
                    type="button"
                    class="mt-3 text-sm text-indigo-600 hover:text-indigo-800"
                    @click="addCoachingCertification"
                >
                    + Lizenz hinzufügen
                </button>
                <InputError :message="form.errors.coaching_certifications" class="mt-2" />
            </div>

            <!-- Referee Certifications -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel value="Schiedsrichter-Lizenzen" />
                <p class="text-xs text-gray-500 mb-2">Fügen Sie Ihre Schiedsrichter-Lizenzen hinzu.</p>

                <div v-if="form.referee_certifications.length === 0" class="text-sm text-gray-500 italic">
                    Keine Lizenzen hinzugefügt
                </div>

                <div
                    v-for="(cert, index) in form.referee_certifications"
                    :key="index"
                    class="mt-3 p-3 border border-gray-200 rounded-md"
                >
                    <div class="space-y-2">
                        <TextInput
                            v-model="cert.name"
                            type="text"
                            class="block w-full"
                            placeholder="Lizenz-Name (z.B. SR-Lizenz Regionalliga)"
                        />
                        <div class="grid grid-cols-2 gap-2">
                            <TextInput
                                v-model="cert.year"
                                type="number"
                                class="block w-full"
                                placeholder="Jahr"
                            />
                            <TextInput
                                v-model="cert.issuer"
                                type="text"
                                class="block w-full"
                                placeholder="Aussteller (z.B. DBB)"
                            />
                        </div>
                    </div>
                    <button
                        type="button"
                        class="mt-2 text-sm text-red-600 hover:text-red-800"
                        @click="removeRefereeCertification(index)"
                    >
                        Entfernen
                    </button>
                </div>

                <button
                    type="button"
                    class="mt-3 text-sm text-indigo-600 hover:text-indigo-800"
                    @click="addRefereeCertification"
                >
                    + Lizenz hinzufügen
                </button>
                <InputError :message="form.errors.referee_certifications" class="mt-2" />
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
