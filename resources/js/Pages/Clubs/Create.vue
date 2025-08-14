<template>
    <AppLayout title="Club erstellen">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Neuen Club erstellen
                </h2>
                <SecondaryButton 
                    :href="route('clubs.index')"
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
                            <!-- Name -->
                            <div class="md:col-span-2">
                                <InputLabel for="name" value="Club-Name*" />
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

                            <!-- Short Name -->
                            <div>
                                <InputLabel for="short_name" value="Kurz-Name" />
                                <TextInput
                                    id="short_name"
                                    v-model="form.short_name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    maxlength="10"
                                />
                                <InputError :message="form.errors.short_name" class="mt-2" />
                                <div class="text-xs text-gray-500 mt-1">Max. 10 Zeichen</div>
                            </div>

                            <!-- Founded Year -->
                            <div>
                                <InputLabel for="founded_year" value="Gründungsjahr" />
                                <TextInput
                                    id="founded_year"
                                    v-model="form.founded_year"
                                    type="number"
                                    class="mt-1 block w-full"
                                    :min="1850"
                                    :max="currentYear"
                                />
                                <InputError :message="form.errors.founded_year" class="mt-2" />
                            </div>

                            <!-- City -->
                            <div>
                                <InputLabel for="city" value="Stadt" />
                                <TextInput
                                    id="city"
                                    v-model="form.city"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                                <InputError :message="form.errors.city" class="mt-2" />
                            </div>

                            <!-- Country -->
                            <div>
                                <InputLabel for="country" value="Land" />
                                <select
                                    id="country"
                                    v-model="form.country"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                >
                                    <option value="">Land auswählen</option>
                                    <option value="DE">Deutschland</option>
                                    <option value="AT">Österreich</option>
                                    <option value="CH">Schweiz</option>
                                    <option value="US">USA</option>
                                    <!-- Add more countries as needed -->
                                </select>
                                <InputError :message="form.errors.country" class="mt-2" />
                            </div>

                            <!-- Website -->
                            <div>
                                <InputLabel for="website" value="Website" />
                                <TextInput
                                    id="website"
                                    v-model="form.website"
                                    type="url"
                                    class="mt-1 block w-full"
                                    placeholder="https://..."
                                />
                                <InputError :message="form.errors.website" class="mt-2" />
                            </div>

                            <!-- Email -->
                            <div>
                                <InputLabel for="email" value="E-Mail" />
                                <TextInput
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    class="mt-1 block w-full"
                                />
                                <InputError :message="form.errors.email" class="mt-2" />
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

                            <!-- Address -->
                            <div class="md:col-span-2">
                                <InputLabel for="address" value="Adresse" />
                                <textarea
                                    id="address"
                                    v-model="form.address"
                                    rows="3"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                />
                                <InputError :message="form.errors.address" class="mt-2" />
                            </div>

                            <!-- Description -->
                            <div class="md:col-span-2">
                                <InputLabel for="description" value="Beschreibung" />
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="4"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    maxlength="1000"
                                />
                                <InputError :message="form.errors.description" class="mt-2" />
                                <div class="text-xs text-gray-500 mt-1">Max. 1000 Zeichen</div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-8 pt-6 border-t">
                            <SecondaryButton 
                                :href="route('clubs.index')"
                                as="Link"
                                class="mr-3"
                            >
                                Abbrechen
                            </SecondaryButton>

                            <PrimaryButton 
                                type="submit"
                                :class="{ 'opacity-25': form.processing }" 
                                :disabled="form.processing"
                            >
                                Club erstellen
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import InputLabel from '@/Components/InputLabel.vue'
import InputError from '@/Components/InputError.vue'

const form = useForm({
    name: '',
    short_name: '',
    founded_year: null,
    city: '',
    country: '',
    website: '',
    email: '',
    phone: '',
    address: '',
    description: '',
})

const currentYear = computed(() => new Date().getFullYear())

const submit = () => {
    form.post(route('clubs.store'))
}
</script>