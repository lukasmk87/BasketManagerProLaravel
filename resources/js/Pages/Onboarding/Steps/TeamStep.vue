<script setup>
import { useForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    ageGroups: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['next', 'back']);

const form = useForm({
    name: '',
    age_group: '',
    gender: 'mixed',
});

const submit = () => {
    form.post(route('onboarding.team.store'), {
        preserveScroll: true,
        onSuccess: () => {
            emit('next');
        },
    });
};

const genderOptions = [
    { value: 'mixed', label: 'Gemischt' },
    { value: 'male', label: 'Männlich' },
    { value: 'female', label: 'Weiblich' },
];
</script>

<template>
    <div class="max-w-lg mx-auto">
        <div class="text-center mb-8">
            <div class="mx-auto w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-900">Erstelle dein erstes Team</h2>
            <p class="mt-2 text-gray-600">
                Leg dein erstes Basketball-Team an. Du kannst später weitere Teams hinzufügen.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Team Name -->
            <div>
                <InputLabel for="name" value="Team-Name *" />
                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="mt-1 block w-full"
                    placeholder="z.B. U16 Jungs"
                    required
                    autofocus
                />
                <InputError :message="form.errors.name" class="mt-2" />
            </div>

            <!-- Age Group -->
            <div>
                <InputLabel for="age_group" value="Altersgruppe *" />
                <select
                    id="age_group"
                    v-model="form.age_group"
                    class="mt-1 block w-full border-gray-300 focus:border-orange-500 focus:ring-orange-500 rounded-md shadow-sm"
                    required
                >
                    <option value="" disabled>Wähle eine Altersgruppe</option>
                    <option v-for="(label, value) in ageGroups" :key="value" :value="value">
                        {{ label }}
                    </option>
                </select>
                <InputError :message="form.errors.age_group" class="mt-2" />
            </div>

            <!-- Gender -->
            <div>
                <InputLabel value="Geschlecht" />
                <div class="mt-2 flex space-x-4">
                    <label
                        v-for="option in genderOptions"
                        :key="option.value"
                        class="flex items-center"
                    >
                        <input
                            type="radio"
                            v-model="form.gender"
                            :value="option.value"
                            class="h-4 w-4 text-orange-600 focus:ring-orange-500 border-gray-300"
                        />
                        <span class="ml-2 text-sm text-gray-700">{{ option.label }}</span>
                    </label>
                </div>
                <InputError :message="form.errors.gender" class="mt-2" />
            </div>

            <!-- Buttons -->
            <div class="pt-4 flex space-x-4">
                <SecondaryButton
                    type="button"
                    class="flex-1 justify-center py-3"
                    @click="emit('back')"
                >
                    Zurück
                </SecondaryButton>
                <PrimaryButton
                    type="submit"
                    class="flex-1 justify-center py-3"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing">Wird erstellt...</span>
                    <span v-else>Los geht's</span>
                </PrimaryButton>
            </div>
        </form>
    </div>
</template>
