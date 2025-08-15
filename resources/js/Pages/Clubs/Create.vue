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
                        <!-- Basic Information Section -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Grundinformationen</h3>
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
                        </div>

                        <!-- Contact Information Section -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Kontaktinformationen</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                                <div class="md:col-span-2">
                                    <InputLabel for="phone" value="Telefon" />
                                    <TextInput
                                        id="phone"
                                        v-model="form.phone"
                                        type="tel"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError :message="form.errors.phone" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <!-- Address Section -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Adresse</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Street -->
                                <div class="md:col-span-2">
                                    <InputLabel for="address_street" value="Straße und Hausnummer" />
                                    <TextInput
                                        id="address_street"
                                        v-model="form.address_street"
                                        type="text"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError :message="form.errors.address_street" class="mt-2" />
                                </div>

                                <!-- ZIP -->
                                <div>
                                    <InputLabel for="address_zip" value="PLZ" />
                                    <TextInput
                                        id="address_zip"
                                        v-model="form.address_zip"
                                        type="text"
                                        class="mt-1 block w-full"
                                        maxlength="20"
                                    />
                                    <InputError :message="form.errors.address_zip" class="mt-2" />
                                </div>

                                <!-- City -->
                                <div>
                                    <InputLabel for="address_city" value="Stadt" />
                                    <TextInput
                                        id="address_city"
                                        v-model="form.address_city"
                                        type="text"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError :message="form.errors.address_city" class="mt-2" />
                                </div>

                                <!-- State -->
                                <div>
                                    <InputLabel for="address_state" value="Bundesland/Staat" />
                                    <TextInput
                                        id="address_state"
                                        v-model="form.address_state"
                                        type="text"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError :message="form.errors.address_state" class="mt-2" />
                                </div>

                                <!-- Country -->
                                <div>
                                    <InputLabel for="address_country" value="Land" />
                                    <select
                                        id="address_country"
                                        v-model="form.address_country"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    >
                                        <option value="">Land auswählen</option>
                                        <option value="DE">Deutschland</option>
                                        <option value="AT">Österreich</option>
                                        <option value="CH">Schweiz</option>
                                        <option value="US">USA</option>
                                    </select>
                                    <InputError :message="form.errors.address_country" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <!-- Basketball Information Section -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Basketball-Informationen</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- League -->
                                <div>
                                    <InputLabel for="league" value="Liga" />
                                    <TextInput
                                        id="league"
                                        v-model="form.league"
                                        type="text"
                                        class="mt-1 block w-full"
                                        placeholder="z.B. Regionalliga"
                                    />
                                    <InputError :message="form.errors.league" class="mt-2" />
                                </div>

                                <!-- Division -->
                                <div>
                                    <InputLabel for="division" value="Staffel" />
                                    <TextInput
                                        id="division"
                                        v-model="form.division"
                                        type="text"
                                        class="mt-1 block w-full"
                                        placeholder="z.B. Staffel A"
                                    />
                                    <InputError :message="form.errors.division" class="mt-2" />
                                </div>

                                <!-- Season -->
                                <div>
                                    <InputLabel for="season" value="Aktuelle Saison" />
                                    <TextInput
                                        id="season"
                                        v-model="form.season"
                                        type="text"
                                        class="mt-1 block w-full"
                                        placeholder="2023/2024"
                                        maxlength="9"
                                    />
                                    <InputError :message="form.errors.season" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <!-- Club Colors Section -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Vereinsfarben</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Primary Color -->
                                <div>
                                    <InputLabel for="primary_color" value="Hauptfarbe" />
                                    <div class="mt-1 flex items-center space-x-3">
                                        <input
                                            id="primary_color"
                                            v-model="form.primary_color"
                                            type="color"
                                            class="h-10 w-20 border border-gray-300 rounded cursor-pointer"
                                        />
                                        <TextInput
                                            v-model="form.primary_color"
                                            type="text"
                                            class="flex-1"
                                            placeholder="#FF0000"
                                            pattern="^#[A-Fa-f0-9]{6}$"
                                        />
                                    </div>
                                    <InputError :message="form.errors.primary_color" class="mt-2" />
                                </div>

                                <!-- Secondary Color -->
                                <div>
                                    <InputLabel for="secondary_color" value="Sekundärfarbe" />
                                    <div class="mt-1 flex items-center space-x-3">
                                        <input
                                            id="secondary_color"
                                            v-model="form.secondary_color"
                                            type="color"
                                            class="h-10 w-20 border border-gray-300 rounded cursor-pointer"
                                        />
                                        <TextInput
                                            v-model="form.secondary_color"
                                            type="text"
                                            class="flex-1"
                                            placeholder="#0000FF"
                                            pattern="^#[A-Fa-f0-9]{6}$"
                                        />
                                    </div>
                                    <InputError :message="form.errors.secondary_color" class="mt-2" />
                                </div>

                                <!-- Accent Color -->
                                <div>
                                    <InputLabel for="accent_color" value="Akzentfarbe" />
                                    <div class="mt-1 flex items-center space-x-3">
                                        <input
                                            id="accent_color"
                                            v-model="form.accent_color"
                                            type="color"
                                            class="h-10 w-20 border border-gray-300 rounded cursor-pointer"
                                        />
                                        <TextInput
                                            v-model="form.accent_color"
                                            type="text"
                                            class="flex-1"
                                            placeholder="#FFD700"
                                            pattern="^#[A-Fa-f0-9]{6}$"
                                        />
                                    </div>
                                    <InputError :message="form.errors.accent_color" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <!-- Emergency Contacts Section -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Notfallkontakte</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Emergency Contact Name -->
                                <div>
                                    <InputLabel for="emergency_contact_name" value="Name" />
                                    <TextInput
                                        id="emergency_contact_name"
                                        v-model="form.emergency_contact_name"
                                        type="text"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError :message="form.errors.emergency_contact_name" class="mt-2" />
                                </div>

                                <!-- Emergency Contact Phone -->
                                <div>
                                    <InputLabel for="emergency_contact_phone" value="Telefon" />
                                    <TextInput
                                        id="emergency_contact_phone"
                                        v-model="form.emergency_contact_phone"
                                        type="tel"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError :message="form.errors.emergency_contact_phone" class="mt-2" />
                                </div>

                                <!-- Emergency Contact Email -->
                                <div>
                                    <InputLabel for="emergency_contact_email" value="E-Mail" />
                                    <TextInput
                                        id="emergency_contact_email"
                                        v-model="form.emergency_contact_email"
                                        type="email"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError :message="form.errors.emergency_contact_email" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <!-- Financial Information Section -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Finanzinformationen</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Membership Fee -->
                                <div>
                                    <InputLabel for="membership_fee" value="Mitgliedsbeitrag" />
                                    <div class="mt-1 flex items-center space-x-3">
                                        <TextInput
                                            id="membership_fee"
                                            v-model="form.membership_fee"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max="9999.99"
                                            class="flex-1"
                                            placeholder="0.00"
                                        />
                                        <select
                                            v-model="form.currency"
                                            class="border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        >
                                            <option value="EUR">EUR</option>
                                            <option value="USD">USD</option>
                                            <option value="CHF">CHF</option>
                                        </select>
                                    </div>
                                    <InputError :message="form.errors.membership_fee" class="mt-2" />
                                    <InputError :message="form.errors.currency" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <!-- Status Section -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Status</h3>
                            <div class="space-y-3">
                                <label class="flex items-center">
                                    <input
                                        type="checkbox"
                                        v-model="form.is_active"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    />
                                    <span class="ml-2">Club ist aktiv</span>
                                </label>
                                <label class="flex items-center">
                                    <input
                                        type="checkbox"
                                        v-model="form.is_verified"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    />
                                    <span class="ml-2">Club ist verifiziert</span>
                                </label>
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
    description: '',
    website: '',
    email: '',
    phone: '',
    
    // Detailed address fields
    address_street: '',
    address_city: '',
    address_state: '',
    address_zip: '',
    address_country: '',
    
    // Basketball-specific fields
    league: '',
    division: '',
    season: '',
    facilities: null,
    
    // Club colors
    primary_color: '',
    secondary_color: '',
    accent_color: '',
    
    // Status fields
    is_active: true,
    is_verified: false,
    
    // Emergency contacts
    emergency_contact_name: '',
    emergency_contact_phone: '',
    emergency_contact_email: '',
    
    // Financial information
    membership_fee: null,
    currency: 'EUR',
    
    // Social media and other fields
    social_links: null,
    default_language: 'de',
    supported_languages: null,
    settings: null,
    preferences: null,
})

const currentYear = computed(() => new Date().getFullYear())

const submit = () => {
    form.post(route('clubs.store'))
}
</script>