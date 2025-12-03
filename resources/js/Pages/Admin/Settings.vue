<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';

const props = defineProps({
    system_stats: Object,
    recent_activities: Array,
    settings: Object,
    pricing_settings: Object,
    roles: Array,
    permissions: Object,
});

const activeTab = ref('overview');

const form = useForm({
    app_name: props.settings.app_name,
    app_timezone: props.settings.app_timezone,
    app_locale: props.settings.app_locale,
    registration_enabled: props.settings.registration_enabled,
    email_verification_required: props.settings.email_verification_required,
});

const updateSettings = () => {
    form.put(route('admin.settings.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Success handled by redirect
        },
    });
};

const pricingForm = useForm({
    display_mode: props.pricing_settings?.display_mode ?? 'gross',
    is_small_business: props.pricing_settings?.is_small_business ?? false,
    default_tax_rate: props.pricing_settings?.default_tax_rate ?? 19.00,
});

const updatePricingSettings = () => {
    pricingForm.put(route('admin.settings.pricing.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Success handled by redirect
        },
    });
};

// Calculate preview prices based on current settings
const previewNetPrice = 49.00;
const previewGrossPrice = computed(() => {
    if (pricingForm.is_small_business) {
        return previewNetPrice;
    }
    return (previewNetPrice * (1 + pricingForm.default_tax_rate / 100)).toFixed(2);
});
const previewTaxAmount = computed(() => {
    if (pricingForm.is_small_business) {
        return 0;
    }
    return (previewNetPrice * pricingForm.default_tax_rate / 100).toFixed(2);
});

const tabs = [
    { id: 'overview', name: 'Übersicht', icon: 'chart-bar' },
    { id: 'settings', name: 'Einstellungen', icon: 'cog' },
    { id: 'pricing', name: 'Preise & Steuern', icon: 'currency-euro' },
    { id: 'roles', name: 'Rollen & Berechtigungen', icon: 'shield-check' },
    { id: 'activities', name: 'Aktivitäten', icon: 'clock' },
];

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString('de-DE');
};

const getIconColor = (tab) => {
    return activeTab.value === tab ? 'text-blue-600' : 'text-gray-400';
};
</script>

<template>
    <AdminLayout title="Admin Panel">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Admin Panel
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        System-Verwaltung und Konfiguration
                    </p>
                </div>
                
                <div class="flex items-center space-x-3">
                    <SecondaryButton :href="route('dashboard')" as="Link">
                        Zurück zum Dashboard
                    </SecondaryButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Tab Navigation -->
                <div class="mb-8">
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8">
                            <button
                                v-for="tab in tabs"
                                :key="tab.id"
                                @click="activeTab = tab.id"
                                :class="[
                                    'flex items-center py-2 px-1 border-b-2 font-medium text-sm transition-colors',
                                    activeTab === tab.id
                                        ? 'border-blue-500 text-blue-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                ]"
                            >
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <!-- Chart Bar Icon -->
                                    <path v-if="tab.icon === 'chart-bar'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    <!-- Cog Icon -->
                                    <path v-else-if="tab.icon === 'cog'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    <!-- Shield Check Icon -->
                                    <path v-else-if="tab.icon === 'shield-check'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                    <!-- Clock Icon -->
                                    <path v-else-if="tab.icon === 'clock'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    <!-- Currency Euro Icon -->
                                    <path v-else-if="tab.icon === 'currency-euro'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 15.536c-1.171 1.952-3.07 1.952-4.242 0-1.172-1.953-1.172-5.119 0-7.072 1.171-1.952 3.07-1.952 4.242 0M8 10.5h4m-4 3h4m9-1.5a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{ tab.name }}
                            </button>
                        </nav>
                    </div>
                </div>

                <!-- Tab Content -->
                <div class="space-y-6">
                    <!-- Overview Tab -->
                    <div v-if="activeTab === 'overview'" class="space-y-6">
                        <!-- System Statistics -->
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    System-Statistiken
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                    <div class="bg-blue-50 p-4 rounded-lg">
                                        <div class="flex items-center">
                                            <svg class="w-8 h-8 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path>
                                            </svg>
                                            <div class="ml-3">
                                                <p class="text-sm font-medium text-gray-500">Benutzer</p>
                                                <p class="text-lg font-semibold text-gray-900">{{ system_stats.total_users }}</p>
                                                <p class="text-xs text-gray-600">{{ system_stats.active_users }} aktiv</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="bg-green-50 p-4 rounded-lg">
                                        <div class="flex items-center">
                                            <svg class="w-8 h-8 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"></path>
                                            </svg>
                                            <div class="ml-3">
                                                <p class="text-sm font-medium text-gray-500">Clubs</p>
                                                <p class="text-lg font-semibold text-gray-900">{{ system_stats.total_clubs }}</p>
                                                <p class="text-xs text-gray-600">{{ system_stats.active_clubs }} aktiv</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="bg-orange-50 p-4 rounded-lg">
                                        <div class="flex items-center">
                                            <svg class="w-8 h-8 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"></path>
                                            </svg>
                                            <div class="ml-3">
                                                <p class="text-sm font-medium text-gray-500">Teams</p>
                                                <p class="text-lg font-semibold text-gray-900">{{ system_stats.total_teams }}</p>
                                                <p class="text-xs text-gray-600">{{ system_stats.active_teams }} aktiv</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="bg-purple-50 p-4 rounded-lg">
                                        <div class="flex items-center">
                                            <svg class="w-8 h-8 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                            </svg>
                                            <div class="ml-3">
                                                <p class="text-sm font-medium text-gray-500">Spieler</p>
                                                <p class="text-lg font-semibold text-gray-900">{{ system_stats.total_players }}</p>
                                                <p class="text-xs text-gray-600">{{ system_stats.active_players }} aktiv</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    Verwaltung
                                </h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                    <SecondaryButton :href="route('admin.users')" as="Link" class="justify-center">
                                        Benutzer verwalten
                                    </SecondaryButton>
                                    <SecondaryButton :href="route('web.clubs.index')" as="Link" class="justify-center">
                                        Clubs verwalten
                                    </SecondaryButton>
                                    <SecondaryButton :href="route('admin.legal-pages.index')" as="Link" class="justify-center">
                                        Legal Pages verwalten
                                    </SecondaryButton>
                                    <SecondaryButton :href="route('admin.landing-page.index')" as="Link" class="justify-center">
                                        Landing Page verwalten
                                    </SecondaryButton>
                                    <SecondaryButton :href="route('admin.system')" as="Link" class="justify-center">
                                        System-Info
                                    </SecondaryButton>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Settings Tab -->
                    <div v-if="activeTab === 'settings'" class="space-y-6">
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    System-Einstellungen
                                </h3>
                                <form @submit.prevent="updateSettings" class="space-y-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <InputLabel for="app_name" value="Anwendungsname" />
                                            <TextInput
                                                id="app_name"
                                                v-model="form.app_name"
                                                type="text"
                                                class="mt-1 block w-full"
                                                required />
                                            <InputError :message="form.errors.app_name" class="mt-2" />
                                        </div>

                                        <div>
                                            <InputLabel for="app_timezone" value="Zeitzone" />
                                            <TextInput
                                                id="app_timezone"
                                                v-model="form.app_timezone"
                                                type="text"
                                                class="mt-1 block w-full"
                                                required />
                                            <InputError :message="form.errors.app_timezone" class="mt-2" />
                                        </div>

                                        <div>
                                            <InputLabel for="app_locale" value="Sprache" />
                                            <select
                                                id="app_locale"
                                                v-model="form.app_locale"
                                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                                <option value="de">Deutsch</option>
                                                <option value="en">English</option>
                                            </select>
                                            <InputError :message="form.errors.app_locale" class="mt-2" />
                                        </div>
                                    </div>

                                    <div class="space-y-4">
                                        <div class="flex items-center">
                                            <Checkbox
                                                id="registration_enabled"
                                                v-model:checked="form.registration_enabled" />
                                            <InputLabel for="registration_enabled" value="Registrierung aktiviert" class="ml-2" />
                                        </div>

                                        <div class="flex items-center">
                                            <Checkbox
                                                id="email_verification_required"
                                                v-model:checked="form.email_verification_required" />
                                            <InputLabel for="email_verification_required" value="E-Mail-Verifizierung erforderlich" class="ml-2" />
                                        </div>
                                    </div>

                                    <div class="flex justify-end">
                                        <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                            Einstellungen speichern
                                        </PrimaryButton>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing Tab -->
                    <div v-if="activeTab === 'pricing'" class="space-y-6">
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    Preise & Mehrwertsteuer
                                </h3>
                                <form @submit.prevent="updatePricingSettings" class="space-y-6">
                                    <!-- Display Mode -->
                                    <div>
                                        <InputLabel value="Preisanzeige-Modus" class="mb-3" />
                                        <div class="space-y-3">
                                            <label class="flex items-start cursor-pointer">
                                                <input
                                                    type="radio"
                                                    v-model="pricingForm.display_mode"
                                                    value="gross"
                                                    class="mt-1 h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500" />
                                                <span class="ml-3">
                                                    <span class="block text-sm font-medium text-gray-900">Bruttopreise (inkl. MwSt.)</span>
                                                    <span class="block text-sm text-gray-500">Empfohlen für Endkunden (B2C). Der angezeigte Preis enthält bereits die Mehrwertsteuer.</span>
                                                </span>
                                            </label>
                                            <label class="flex items-start cursor-pointer">
                                                <input
                                                    type="radio"
                                                    v-model="pricingForm.display_mode"
                                                    value="net"
                                                    class="mt-1 h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500" />
                                                <span class="ml-3">
                                                    <span class="block text-sm font-medium text-gray-900">Nettopreise (zzgl. MwSt.)</span>
                                                    <span class="block text-sm text-gray-500">Empfohlen für Geschäftskunden (B2B). Die Mehrwertsteuer wird separat ausgewiesen.</span>
                                                </span>
                                            </label>
                                        </div>
                                        <InputError :message="pricingForm.errors.display_mode" class="mt-2" />
                                    </div>

                                    <!-- Small Business (Kleinunternehmer) -->
                                    <div class="border-t border-gray-200 pt-6">
                                        <div class="flex items-start">
                                            <Checkbox
                                                id="is_small_business"
                                                v-model:checked="pricingForm.is_small_business" />
                                            <div class="ml-3">
                                                <InputLabel for="is_small_business" value="Kleinunternehmer-Regelung (§19 UStG)" class="font-medium" />
                                                <p class="text-sm text-gray-500 mt-1">
                                                    Als Kleinunternehmer dürfen Sie keine Mehrwertsteuer ausweisen.
                                                    Auf allen Rechnungen erscheint der Hinweis:
                                                    <span class="block mt-1 italic text-amber-700">"Gemäß §19 UStG wird keine Umsatzsteuer berechnet."</span>
                                                </p>
                                            </div>
                                        </div>
                                        <InputError :message="pricingForm.errors.is_small_business" class="mt-2" />
                                    </div>

                                    <!-- Default Tax Rate -->
                                    <div v-if="!pricingForm.is_small_business" class="border-t border-gray-200 pt-6">
                                        <InputLabel for="default_tax_rate" value="Standard-MwSt.-Satz (%)" />
                                        <div class="mt-1 flex items-center">
                                            <input
                                                type="number"
                                                id="default_tax_rate"
                                                v-model.number="pricingForm.default_tax_rate"
                                                step="0.01"
                                                min="0"
                                                max="100"
                                                class="block w-24 border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm" />
                                            <span class="ml-2 text-gray-500">%</span>
                                        </div>
                                        <p class="text-sm text-gray-500 mt-1">
                                            Der deutsche Standard-MwSt.-Satz beträgt 19%. Der ermäßigte Satz liegt bei 7%.
                                        </p>
                                        <InputError :message="pricingForm.errors.default_tax_rate" class="mt-2" />
                                    </div>

                                    <!-- Preview -->
                                    <div class="border-t border-gray-200 pt-6">
                                        <h4 class="text-sm font-medium text-gray-900 mb-3">Vorschau</h4>
                                        <div class="bg-gray-50 rounded-lg p-4">
                                            <p class="text-sm text-gray-600 mb-3">Bei einem Netto-Preis von <strong>49,00 EUR</strong>:</p>
                                            <div v-if="pricingForm.is_small_business" class="space-y-2">
                                                <p class="text-sm">
                                                    <span class="text-gray-500">Anzeige:</span>
                                                    <span class="ml-2 font-semibold text-gray-900">49,00 EUR</span>
                                                </p>
                                                <p class="text-sm">
                                                    <span class="text-gray-500">Rechnung:</span>
                                                    <span class="ml-2">49,00 EUR (keine MwSt.)</span>
                                                </p>
                                                <p class="text-sm text-amber-700 italic mt-2">
                                                    "Gemäß §19 UStG wird keine Umsatzsteuer berechnet."
                                                </p>
                                            </div>
                                            <div v-else class="space-y-2">
                                                <p class="text-sm">
                                                    <span class="text-gray-500">Anzeige:</span>
                                                    <span class="ml-2 font-semibold text-gray-900">
                                                        {{ pricingForm.display_mode === 'gross' ? previewGrossPrice : '49,00' }} EUR
                                                        {{ pricingForm.display_mode === 'gross' ? 'inkl. MwSt.' : 'zzgl. MwSt.' }}
                                                    </span>
                                                </p>
                                                <p class="text-sm">
                                                    <span class="text-gray-500">MwSt.-Betrag:</span>
                                                    <span class="ml-2">{{ previewTaxAmount }} EUR</span>
                                                </p>
                                                <p class="text-sm">
                                                    <span class="text-gray-500">Rechnungsbetrag (brutto):</span>
                                                    <span class="ml-2 font-semibold">{{ previewGrossPrice }} EUR</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex justify-end pt-4">
                                        <PrimaryButton :class="{ 'opacity-25': pricingForm.processing }" :disabled="pricingForm.processing">
                                            Preiseinstellungen speichern
                                        </PrimaryButton>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Roles Tab -->
                    <div v-if="activeTab === 'roles'" class="space-y-6">
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    Rollen & Berechtigungen
                                </h3>
                                <div class="space-y-4">
                                    <div v-for="role in roles" :key="role.id" class="border border-gray-200 rounded-lg p-4">
                                        <div class="flex items-center justify-between mb-2">
                                            <h4 class="text-md font-semibold text-gray-900">{{ role.name }}</h4>
                                            <span class="text-sm text-gray-500">{{ role.permissions.length }} Berechtigungen</span>
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            <span v-for="permission in role.permissions" :key="permission.id"
                                                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ permission.name }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Activities Tab -->
                    <div v-if="activeTab === 'activities'" class="space-y-6">
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    Letzte Aktivitäten
                                </h3>
                                <div class="space-y-3">
                                    <div v-for="activity in recent_activities" :key="activity.id"
                                         class="flex items-start space-x-3 p-3 hover:bg-gray-50 rounded-lg">
                                        <div class="flex-shrink-0">
                                            <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                                                <svg class="w-4 h-4 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm text-gray-900">
                                                <span class="font-medium">{{ activity.user_name }}</span>
                                                {{ activity.description }}
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                {{ formatDate(activity.created_at) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>