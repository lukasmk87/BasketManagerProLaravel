<script setup>
import { useForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const emit = defineEmits(['next']);

const form = useForm({
    name: '',
    city: '',
    description: '',
    logo: null,
});

const submit = () => {
    form.post(route('onboarding.club.store'), {
        preserveScroll: true,
        onSuccess: () => {
            emit('next');
        },
    });
};

const handleFileChange = (event) => {
    form.logo = event.target.files[0];
};
</script>

<template>
    <div class="max-w-lg mx-auto">
        <div class="text-center mb-8">
            <div class="mx-auto w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-900">Erstelle deinen Club</h2>
            <p class="mt-2 text-gray-600">
                Gib deinem Basketball-Club einen Namen und Standort.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Club Name -->
            <div>
                <InputLabel for="name" value="Club-Name *" />
                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="mt-1 block w-full"
                    placeholder="z.B. BC Musterstadt"
                    required
                    autofocus
                />
                <InputError :message="form.errors.name" class="mt-2" />
            </div>

            <!-- City -->
            <div>
                <InputLabel for="city" value="Stadt *" />
                <TextInput
                    id="city"
                    v-model="form.city"
                    type="text"
                    class="mt-1 block w-full"
                    placeholder="z.B. München"
                    required
                />
                <InputError :message="form.errors.city" class="mt-2" />
            </div>

            <!-- Description -->
            <div>
                <InputLabel for="description" value="Beschreibung (optional)" />
                <textarea
                    id="description"
                    v-model="form.description"
                    class="mt-1 block w-full border-gray-300 focus:border-orange-500 focus:ring-orange-500 rounded-md shadow-sm"
                    rows="3"
                    placeholder="Erzähle etwas über deinen Club..."
                ></textarea>
                <InputError :message="form.errors.description" class="mt-2" />
            </div>

            <!-- Logo Upload -->
            <div>
                <InputLabel for="logo" value="Logo (optional)" />
                <div class="mt-1 flex items-center space-x-4">
                    <div class="flex-shrink-0">
                        <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden">
                            <img
                                v-if="form.logo"
                                :src="URL.createObjectURL(form.logo)"
                                class="w-full h-full object-cover"
                            />
                            <svg v-else class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                    <label class="cursor-pointer">
                        <span class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Logo hochladen
                        </span>
                        <input
                            type="file"
                            class="hidden"
                            accept="image/jpeg,image/png,image/jpg,image/svg+xml"
                            @change="handleFileChange"
                        />
                    </label>
                </div>
                <InputError :message="form.errors.logo" class="mt-2" />
            </div>

            <!-- Submit Button -->
            <div class="pt-4">
                <PrimaryButton
                    type="submit"
                    class="w-full justify-center py-3"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing">Wird erstellt...</span>
                    <span v-else>Weiter zum Team</span>
                </PrimaryButton>
            </div>
        </form>
    </div>
</template>
