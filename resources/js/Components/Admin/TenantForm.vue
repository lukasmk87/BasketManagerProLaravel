<script setup>
import { computed, watch } from 'vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    availablePlans: {
        type: Array,
        default: () => [],
    },
    timezones: {
        type: Object,
        default: () => ({}),
    },
    countries: {
        type: Object,
        default: () => ({}),
    },
    mode: {
        type: String,
        default: 'create', // 'create' or 'edit'
    },
});

const subscriptionTiers = [
    { value: 'free', label: 'Free' },
    { value: 'basic', label: 'Basic' },
    { value: 'professional', label: 'Professional' },
    { value: 'enterprise', label: 'Enterprise' },
];

// Watcher: Synchronisiere subscription_tier automatisch wenn ein Plan gewählt wird
watch(() => props.form.subscription_plan_id, (newPlanId) => {
    if (newPlanId) {
        const selectedPlan = props.availablePlans.find(p => p.id === newPlanId);
        if (selectedPlan && selectedPlan.slug) {
            props.form.subscription_tier = selectedPlan.slug;
        }
    }
});

// Computed: Prüfe ob ein Plan gewählt ist (um Tier-Dropdown zu deaktivieren)
const hasPlanSelected = computed(() => {
    return !!props.form.subscription_plan_id;
});

const currencies = [
    { value: 'EUR', label: 'EUR (€)' },
    { value: 'USD', label: 'USD ($)' },
    { value: 'GBP', label: 'GBP (£)' },
    { value: 'CHF', label: 'CHF' },
];

const locales = [
    { value: 'de', label: 'Deutsch' },
    { value: 'en', label: 'English' },
    { value: 'fr', label: 'Français' },
    { value: 'es', label: 'Español' },
];
</script>

<template>
    <div class="space-y-6">
        <!-- Basic Information -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Grundinformationen</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Name -->
                <div>
                    <InputLabel for="name" value="Name *" />
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

                <!-- Slug -->
                <div>
                    <InputLabel for="slug" value="Slug" />
                    <TextInput
                        id="slug"
                        v-model="form.slug"
                        type="text"
                        class="mt-1 block w-full"
                    />
                    <p class="mt-1 text-xs text-gray-500">Wird automatisch generiert, falls leer</p>
                    <InputError :message="form.errors.slug" class="mt-2" />
                </div>

                <!-- Domain -->
                <div>
                    <InputLabel for="domain" value="Custom Domain" />
                    <TextInput
                        id="domain"
                        v-model="form.domain"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="example.com"
                    />
                    <InputError :message="form.errors.domain" class="mt-2" />
                </div>

                <!-- Subdomain -->
                <div>
                    <InputLabel for="subdomain" value="Subdomain" />
                    <TextInput
                        id="subdomain"
                        v-model="form.subdomain"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="example"
                    />
                    <p class="mt-1 text-xs text-gray-500">Beispiel: example.basketmanager-pro.com</p>
                    <InputError :message="form.errors.subdomain" class="mt-2" />
                </div>
            </div>
        </div>

        <!-- Billing Information -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Rechnungsinformationen</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Billing Name -->
                <div>
                    <InputLabel for="billing_name" value="Billing Name" />
                    <TextInput
                        id="billing_name"
                        v-model="form.billing_name"
                        type="text"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="form.errors.billing_name" class="mt-2" />
                </div>

                <!-- Billing Email -->
                <div>
                    <InputLabel for="billing_email" value="Billing E-Mail" />
                    <TextInput
                        id="billing_email"
                        v-model="form.billing_email"
                        type="email"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="form.errors.billing_email" class="mt-2" />
                </div>

                <!-- Billing Address -->
                <div class="md:col-span-2">
                    <InputLabel for="billing_address" value="Billing Adresse" />
                    <textarea
                        id="billing_address"
                        v-model="form.billing_address"
                        rows="2"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                    ></textarea>
                    <InputError :message="form.errors.billing_address" class="mt-2" />
                </div>

                <!-- VAT Number -->
                <div>
                    <InputLabel for="vat_number" value="USt-IdNr." />
                    <TextInput
                        id="vat_number"
                        v-model="form.vat_number"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="DE123456789"
                    />
                    <InputError :message="form.errors.vat_number" class="mt-2" />
                </div>

                <!-- Country Code -->
                <div>
                    <InputLabel for="country_code" value="Land" />
                    <select
                        id="country_code"
                        v-model="form.country_code"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                    >
                        <option value="">Bitte wählen...</option>
                        <option v-for="(label, code) in countries" :key="code" :value="code">
                            {{ label }}
                        </option>
                    </select>
                    <InputError :message="form.errors.country_code" class="mt-2" />
                </div>
            </div>
        </div>

        <!-- Configuration -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Konfiguration</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Timezone -->
                <div>
                    <InputLabel for="timezone" value="Zeitzone" />
                    <select
                        id="timezone"
                        v-model="form.timezone"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                    >
                        <option value="">Bitte wählen...</option>
                        <option v-for="(label, tz) in timezones" :key="tz" :value="tz">
                            {{ label }}
                        </option>
                    </select>
                    <InputError :message="form.errors.timezone" class="mt-2" />
                </div>

                <!-- Locale -->
                <div>
                    <InputLabel for="locale" value="Sprache" />
                    <select
                        id="locale"
                        v-model="form.locale"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                    >
                        <option value="">Bitte wählen...</option>
                        <option v-for="loc in locales" :key="loc.value" :value="loc.value">
                            {{ loc.label }}
                        </option>
                    </select>
                    <InputError :message="form.errors.locale" class="mt-2" />
                </div>

                <!-- Currency -->
                <div>
                    <InputLabel for="currency" value="Währung" />
                    <select
                        id="currency"
                        v-model="form.currency"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                    >
                        <option value="">Bitte wählen...</option>
                        <option v-for="cur in currencies" :key="cur.value" :value="cur.value">
                            {{ cur.label }}
                        </option>
                    </select>
                    <InputError :message="form.errors.currency" class="mt-2" />
                </div>
            </div>
        </div>

        <!-- Subscription & Limits -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Subscription & Limits</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Subscription Plan -->
                <div>
                    <InputLabel for="subscription_plan_id" value="Subscription Plan" />
                    <select
                        id="subscription_plan_id"
                        v-model="form.subscription_plan_id"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                    >
                        <option value="">Kein Plan</option>
                        <option v-for="plan in availablePlans" :key="plan.id" :value="plan.id">
                            {{ plan.name }} ({{ plan.formatted_price }})
                        </option>
                    </select>
                    <InputError :message="form.errors.subscription_plan_id" class="mt-2" />
                </div>

                <!-- Subscription Tier -->
                <div>
                    <InputLabel for="subscription_tier" value="Subscription Tier" />
                    <select
                        id="subscription_tier"
                        v-model="form.subscription_tier"
                        :disabled="hasPlanSelected"
                        :class="[
                            'mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm',
                            hasPlanSelected ? 'bg-gray-100 cursor-not-allowed' : ''
                        ]"
                    >
                        <option value="">Bitte wählen...</option>
                        <option v-for="tier in subscriptionTiers" :key="tier.value" :value="tier.value">
                            {{ tier.label }}
                        </option>
                    </select>
                    <p v-if="hasPlanSelected" class="mt-1 text-xs text-gray-500">
                        Wird automatisch vom gewählten Plan übernommen
                    </p>
                    <InputError :message="form.errors.subscription_tier" class="mt-2" />
                </div>

                <!-- Max Users -->
                <div>
                    <InputLabel for="max_users" value="Max. Benutzer" />
                    <TextInput
                        id="max_users"
                        v-model.number="form.max_users"
                        type="number"
                        min="1"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="form.errors.max_users" class="mt-2" />
                </div>

                <!-- Max Teams -->
                <div>
                    <InputLabel for="max_teams" value="Max. Teams" />
                    <TextInput
                        id="max_teams"
                        v-model.number="form.max_teams"
                        type="number"
                        min="1"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="form.errors.max_teams" class="mt-2" />
                </div>

                <!-- Max Storage GB -->
                <div>
                    <InputLabel for="max_storage_gb" value="Max. Speicher (GB)" />
                    <TextInput
                        id="max_storage_gb"
                        v-model.number="form.max_storage_gb"
                        type="number"
                        min="0"
                        step="0.1"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="form.errors.max_storage_gb" class="mt-2" />
                </div>

                <!-- Max API Calls Per Hour -->
                <div>
                    <InputLabel for="max_api_calls_per_hour" value="Max. API-Calls pro Stunde" />
                    <TextInput
                        id="max_api_calls_per_hour"
                        v-model.number="form.max_api_calls_per_hour"
                        type="number"
                        min="0"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="form.errors.max_api_calls_per_hour" class="mt-2" />
                </div>

                <!-- Trial Ends At -->
                <div v-if="mode === 'create'" class="md:col-span-2">
                    <InputLabel for="trial_ends_at" value="Trial-Ende" />
                    <TextInput
                        id="trial_ends_at"
                        v-model="form.trial_ends_at"
                        type="datetime-local"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="form.errors.trial_ends_at" class="mt-2" />
                </div>
            </div>
        </div>

        <!-- Status -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Status</h3>

            <div class="space-y-4">
                <!-- Is Active -->
                <div class="flex items-center">
                    <Checkbox
                        id="is_active"
                        v-model:checked="form.is_active"
                    />
                    <InputLabel for="is_active" value="Aktiv" class="ml-2" />
                </div>

                <!-- Is Suspended -->
                <div class="flex items-center">
                    <Checkbox
                        id="is_suspended"
                        v-model:checked="form.is_suspended"
                    />
                    <InputLabel for="is_suspended" value="Gesperrt" class="ml-2" />
                </div>

                <!-- Suspension Reason -->
                <div v-if="form.is_suspended">
                    <InputLabel for="suspension_reason" value="Sperrgrund" />
                    <textarea
                        id="suspension_reason"
                        v-model="form.suspension_reason"
                        rows="3"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                    ></textarea>
                    <InputError :message="form.errors.suspension_reason" class="mt-2" />
                </div>
            </div>
        </div>

        <!-- Notes -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Notizen</h3>

            <div>
                <InputLabel for="notes" value="Interne Notizen" />
                <textarea
                    id="notes"
                    v-model="form.notes"
                    rows="4"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                    placeholder="Interne Notizen für Admins..."
                ></textarea>
                <InputError :message="form.errors.notes" class="mt-2" />
            </div>
        </div>
    </div>
</template>
