<template>
    <AppLayout :title="`${club.name} bearbeiten`">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ club.name }} bearbeiten
                </h2>
                <SecondaryButton 
                    :href="route('web.clubs.show', club.id)"
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
                                <!-- Logo Upload Section -->
                                <div class="md:col-span-2">
                                    <InputLabel for="logo" value="Vereinslogo" />
                                    <div class="mt-2 flex items-center space-x-6">
                                        <!-- Current Logo Preview -->
                                        <div class="shrink-0">
                                            <img
                                                v-if="logoPreview || club.logo_url"
                                                :src="logoPreview || club.logo_url"
                                                alt="Club Logo"
                                                class="h-24 w-24 object-cover rounded-lg border-2 border-gray-300"
                                            />
                                            <div
                                                v-else
                                                class="h-24 w-24 rounded-lg border-2 border-dashed border-gray-300 flex items-center justify-center bg-gray-50"
                                            >
                                                <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                        </div>

                                        <!-- Upload Controls -->
                                        <div class="flex-1">
                                            <input
                                                type="file"
                                                ref="logoInput"
                                                @change="handleLogoChange"
                                                accept="image/jpeg,image/png,image/jpg,image/svg+xml"
                                                class="hidden"
                                            />
                                            <div class="flex space-x-3">
                                                <SecondaryButton
                                                    type="button"
                                                    @click="$refs.logoInput.click()"
                                                >
                                                    Neues Logo hochladen
                                                </SecondaryButton>
                                                <DangerButton
                                                    v-if="club.logo_url || logoPreview"
                                                    type="button"
                                                    @click="removeLogo"
                                                >
                                                    Logo entfernen
                                                </DangerButton>
                                            </div>
                                            <p class="mt-2 text-xs text-gray-500">
                                                JPEG, PNG, JPG oder SVG. Maximal 2MB.
                                            </p>
                                            <InputError :message="form.errors.logo" class="mt-2" />
                                        </div>
                                    </div>
                                </div>

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

                        <!-- Subscription Plan Section -->
                        <div class="mb-8" v-if="availablePlans.length > 0">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Subscription Plan</h3>
                            <div class="space-y-4">
                                <div>
                                    <InputLabel for="club_subscription_plan_id" value="Club-Plan auswählen" />
                                    <select
                                        id="club_subscription_plan_id"
                                        v-model="form.club_subscription_plan_id"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    >
                                        <option value="">Kein Plan (Tenant-Features werden vererbt)</option>
                                        <option
                                            v-for="plan in availablePlans"
                                            :key="plan.id"
                                            :value="plan.id"
                                        >
                                            {{ plan.name }} - {{ plan.price }} {{ plan.currency }}/{{ plan.billing_interval === 'monthly' ? 'Monat' : 'Jahr' }}
                                        </option>
                                    </select>
                                    <InputError :message="form.errors.club_subscription_plan_id" class="mt-2" />
                                </div>

                                <!-- Plan Details (if plan is selected) -->
                                <div
                                    v-if="form.club_subscription_plan_id"
                                    class="mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200"
                                >
                                    <template v-for="plan in availablePlans" :key="plan.id">
                                        <div v-if="plan.id === form.club_subscription_plan_id">
                                            <h4 class="font-semibold text-gray-900 mb-2">{{ plan.name }}</h4>
                                            <p v-if="plan.description" class="text-sm text-gray-600 mb-3">{{ plan.description }}</p>

                                            <!-- Features -->
                                            <div v-if="plan.features && plan.features.length > 0" class="mb-3">
                                                <div class="text-xs font-medium text-gray-700 mb-2">Features:</div>
                                                <div class="flex flex-wrap gap-2">
                                                    <span
                                                        v-for="feature in plan.features"
                                                        :key="feature"
                                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                                                    >
                                                        {{ feature }}
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Limits -->
                                            <div v-if="plan.limits && Object.keys(plan.limits).length > 0">
                                                <div class="text-xs font-medium text-gray-700 mb-2">Limits:</div>
                                                <div class="grid grid-cols-2 gap-2 text-sm">
                                                    <div v-for="(value, key) in plan.limits" :key="key" class="text-gray-600">
                                                        <span class="font-medium">{{ key }}:</span>
                                                        <span class="ml-1">{{ value === -1 ? 'Unlimited' : value }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <!-- Info Text -->
                                <div class="flex items-start space-x-2 text-sm text-gray-500">
                                    <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <p>
                                        Wenn kein Plan ausgewählt ist, erbt der Club automatisch alle Features des Tenants.
                                        Mit einem Club-Plan können Sie die verfügbaren Features und Limits individuell einschränken.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between mt-8 pt-6 border-t">
                            <DangerButton 
                                type="button"
                                @click="deleteClub"
                                v-if="can?.delete"
                            >
                                Club löschen
                            </DangerButton>

                            <div class="flex items-center space-x-3">
                                <SecondaryButton 
                                    :href="route('web.clubs.show', club.id)"
                                    as="Link"
                                >
                                    Abbrechen
                                </SecondaryButton>

                                <PrimaryButton 
                                    type="submit"
                                    :class="{ 'opacity-25': form.processing }" 
                                    :disabled="form.processing"
                                >
                                    Änderungen speichern
                                </PrimaryButton>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal :show="confirmingClubDeletion" @close="confirmingClubDeletion = false">
            <template #title>
                Club löschen
            </template>

            <template #content>
                Sind Sie sicher, dass Sie diesen Club löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.
                Alle zugehörigen Teams und Spieler werden ebenfalls gelöscht.
            </template>

            <template #footer>
                <SecondaryButton @click="confirmingClubDeletion = false">
                    Abbrechen
                </SecondaryButton>

                <DangerButton
                    class="ml-3"
                    :class="{ 'opacity-25': deleteForm.processing }"
                    :disabled="deleteForm.processing"
                    @click="deleteClubConfirmed"
                >
                    Club löschen
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
    club: Object,
    can: Object,
    availablePlans: {
        type: Array,
        default: () => [],
    },
})

const form = useForm({
    name: props.club.name || '',
    short_name: props.club.short_name || '',
    founded_year: props.club.founded_year || null,
    description: props.club.description || '',
    website: props.club.website || '',
    email: props.club.email || '',
    phone: props.club.phone || '',

    // Detailed address fields
    address_street: props.club.address_street || '',
    address_city: props.club.address_city || '',
    address_state: props.club.address_state || '',
    address_zip: props.club.address_zip || '',
    address_country: props.club.address_country || '',

    // Basketball-specific fields
    facilities: props.club.facilities || null,

    // Club colors
    primary_color: props.club.primary_color || '#000000',
    secondary_color: props.club.secondary_color || '#FFFFFF',
    accent_color: props.club.accent_color || '#FFD700',

    // Subscription Plan
    club_subscription_plan_id: props.club.club_subscription_plan_id || '',

    // Status fields
    is_active: props.club.is_active ?? true,
    is_verified: props.club.is_verified ?? false,

    // Emergency contacts
    emergency_contact_name: props.club.emergency_contact_name || '',
    emergency_contact_phone: props.club.emergency_contact_phone || '',
    emergency_contact_email: props.club.emergency_contact_email || '',

    // Financial information
    membership_fee: props.club.membership_fee || null,
    currency: props.club.currency || 'EUR',

    // Social media and other fields
    social_links: props.club.social_links || null,
    default_language: props.club.default_language || 'de',
    supported_languages: props.club.supported_languages || null,
    settings: props.club.settings || null,
    preferences: props.club.preferences || null,
})

const deleteForm = useForm({})
const confirmingClubDeletion = ref(false)
const logoPreview = ref(null)
const logoInput = ref(null)

const currentYear = computed(() => new Date().getFullYear())

const handleLogoChange = (event) => {
    const file = event.target.files[0]
    if (file) {
        // Create preview
        const reader = new FileReader()
        reader.onload = (e) => {
            logoPreview.value = e.target.result
        }
        reader.readAsDataURL(file)

        // Upload logo immediately via separate endpoint
        const logoForm = useForm({
            logo: file
        })

        logoForm.post(route('web.clubs.logo.upload', props.club.id), {
            preserveScroll: true,
            onSuccess: () => {
                // Logo uploaded successfully
                logoForm.reset()
            },
            onError: () => {
                // Reset preview on error
                logoPreview.value = null
                if (logoInput.value) {
                    logoInput.value.value = ''
                }
            }
        })
    }
}

const removeLogo = () => {
    if (confirm('Möchten Sie das Logo wirklich entfernen?')) {
        const deleteForm = useForm({})
        deleteForm.delete(route('web.clubs.logo.delete', props.club.id), {
            preserveScroll: true,
            onSuccess: () => {
                logoPreview.value = null
                if (logoInput.value) {
                    logoInput.value.value = ''
                }
            }
        })
    }
}

const submit = () => {
    // Only submit club data, logo is handled separately
    form.put(route('web.clubs.update', props.club.id))
}

const deleteClub = () => {
    confirmingClubDeletion.value = true
}

const deleteClubConfirmed = () => {
    deleteForm.delete(route('web.clubs.destroy', props.club.id), {
        onSuccess: () => confirmingClubDeletion.value = false
    })
}
</script>