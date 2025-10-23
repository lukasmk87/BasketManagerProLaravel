<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ClubAdminLayout from '@/Layouts/ClubAdminLayout.vue';

const props = defineProps({
    club: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    name: props.club.name,
    short_name: props.club.short_name || '',
    description: props.club.description || '',
    website: props.club.website || '',
    email: props.club.email || '',
    phone: props.club.phone || '',
    address: props.club.address || '',
    city: props.club.city || '',
    postal_code: props.club.postal_code || '',
    country: props.club.country || '',
    facebook_url: props.club.facebook_url || '',
    twitter_url: props.club.twitter_url || '',
    instagram_url: props.club.instagram_url || '',
});

const submit = () => {
    form.put(route('club-admin.settings.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <ClubAdminLayout title="Club Einstellungen">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Club Einstellungen
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Basic Information -->
                    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Basisinformationen</h3>
                            <p class="text-sm text-gray-500 mt-1">Grundlegende Informationen über Ihren Club</p>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700">Club Name *</label>
                                    <input
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        required
                                    >
                                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                                </div>

                                <div>
                                    <label for="short_name" class="block text-sm font-medium text-gray-700">Kurzname</label>
                                    <input
                                        id="short_name"
                                        v-model="form.short_name"
                                        type="text"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    >
                                    <p v-if="form.errors.short_name" class="mt-1 text-sm text-red-600">{{ form.errors.short_name }}</p>
                                </div>
                            </div>

                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700">Beschreibung</label>
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
                            </div>

                            <!-- Logo Display -->
                            <div v-if="club.logo_url" class="flex items-center space-x-4">
                                <img :src="club.logo_url" :alt="club.name" class="h-16 w-16 rounded-lg object-cover shadow-md">
                                <div>
                                    <p class="text-sm font-medium text-gray-700">Aktuelles Logo</p>
                                    <p class="text-xs text-gray-500">Logo kann über die Club-Bearbeitungsseite geändert werden</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Kontaktinformationen</h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700">E-Mail</label>
                                    <input
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    >
                                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                                </div>

                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700">Telefon</label>
                                    <input
                                        id="phone"
                                        v-model="form.phone"
                                        type="text"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    >
                                    <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</p>
                                </div>
                            </div>

                            <div>
                                <label for="website" class="block text-sm font-medium text-gray-700">Website</label>
                                <input
                                    id="website"
                                    v-model="form.website"
                                    type="url"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="https://"
                                >
                                <p v-if="form.errors.website" class="mt-1 text-sm text-red-600">{{ form.errors.website }}</p>
                            </div>

                            <div>
                                <label for="address" class="block text-sm font-medium text-gray-700">Adresse</label>
                                <input
                                    id="address"
                                    v-model="form.address"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                <p v-if="form.errors.address" class="mt-1 text-sm text-red-600">{{ form.errors.address }}</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label for="city" class="block text-sm font-medium text-gray-700">Stadt</label>
                                    <input
                                        id="city"
                                        v-model="form.city"
                                        type="text"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    >
                                    <p v-if="form.errors.city" class="mt-1 text-sm text-red-600">{{ form.errors.city }}</p>
                                </div>

                                <div>
                                    <label for="postal_code" class="block text-sm font-medium text-gray-700">PLZ</label>
                                    <input
                                        id="postal_code"
                                        v-model="form.postal_code"
                                        type="text"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    >
                                    <p v-if="form.errors.postal_code" class="mt-1 text-sm text-red-600">{{ form.errors.postal_code }}</p>
                                </div>

                                <div>
                                    <label for="country" class="block text-sm font-medium text-gray-700">Land</label>
                                    <input
                                        id="country"
                                        v-model="form.country"
                                        type="text"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    >
                                    <p v-if="form.errors.country" class="mt-1 text-sm text-red-600">{{ form.errors.country }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Social Media -->
                    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Social Media</h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div>
                                <label for="facebook_url" class="block text-sm font-medium text-gray-700">Facebook</label>
                                <input
                                    id="facebook_url"
                                    v-model="form.facebook_url"
                                    type="url"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="https://facebook.com/yourclub"
                                >
                                <p v-if="form.errors.facebook_url" class="mt-1 text-sm text-red-600">{{ form.errors.facebook_url }}</p>
                            </div>

                            <div>
                                <label for="twitter_url" class="block text-sm font-medium text-gray-700">Twitter / X</label>
                                <input
                                    id="twitter_url"
                                    v-model="form.twitter_url"
                                    type="url"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="https://twitter.com/yourclub"
                                >
                                <p v-if="form.errors.twitter_url" class="mt-1 text-sm text-red-600">{{ form.errors.twitter_url }}</p>
                            </div>

                            <div>
                                <label for="instagram_url" class="block text-sm font-medium text-gray-700">Instagram</label>
                                <input
                                    id="instagram_url"
                                    v-model="form.instagram_url"
                                    type="url"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="https://instagram.com/yourclub"
                                >
                                <p v-if="form.errors.instagram_url" class="mt-1 text-sm text-red-600">{{ form.errors.instagram_url }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end space-x-4">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                        >
                            <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                            </svg>
                            Änderungen speichern
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </ClubAdminLayout>
</template>
