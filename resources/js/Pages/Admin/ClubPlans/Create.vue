<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import FeatureSelector from '@/Components/Admin/FeatureSelector.vue';
import { usePricing } from '@/Composables/usePricing';

const { formatPrice, getPriceLabel, getSmallBusinessNotice, getAdminPriceLabel, inputPriceToNet, adminInputIsGross } = usePricing();

const props = defineProps({
    tenants: {
        type: Array,
        default: () => [],
    },
    featuresList: {
        type: Object,
        default: () => ({}),
    },
    defaultLimits: {
        type: Object,
        default: () => ({}),
    },
});

const form = useForm({
    tenant_id: '',
    name: '',
    slug: '',
    description: '',
    price: 0,
    currency: 'EUR',
    billing_interval: 'monthly',
    trial_period_days: 14,
    is_active: true,
    is_default: false,
    is_featured: true,
    sort_order: 0,
    color: '#6366F1',
    icon: '',
    features: [],
    limits: {
        max_teams: 10,
        max_players: 150,
        max_storage_gb: 25,
        max_games_per_month: 100,
        max_training_sessions_per_month: 200,
        max_api_calls_per_hour: 1000,
    },
});

const generateSlug = () => {
    if (form.name) {
        form.slug = form.name
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }
};

const savePlan = () => {
    const formData = form.transform((data) => ({
        ...data,
        price: inputPriceToNet(data.price),
    }));
    formData.post(route('admin.club-plans.store'));
};
</script>

<template>
    <AdminLayout title="Neuer Club Subscription Plan">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Neuer Club Subscription Plan
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Erstelle einen neuen Club Subscription Plan für einen Tenant
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <Link
                        :href="route('admin.club-plans.index')"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50"
                    >
                        Zurück
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="savePlan" class="space-y-6">
                    <!-- Tenant Selection -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-200 bg-gray-50">
                            <h3 class="text-lg font-semibold text-gray-900">Tenant auswählen</h3>
                        </div>
                        <div class="px-6 py-5">
                            <label class="block text-sm font-medium text-gray-700">
                                Tenant *
                            </label>
                            <select
                                v-model="form.tenant_id"
                                required
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            >
                                <option value="">Bitte auswählen...</option>
                                <option v-for="tenant in tenants" :key="tenant.id" :value="tenant.id">
                                    {{ tenant.name }}
                                </option>
                            </select>
                            <p v-if="form.errors.tenant_id" class="mt-1 text-sm text-red-600">{{ form.errors.tenant_id }}</p>
                        </div>
                    </div>

                    <!-- Basic Info -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-200 bg-gray-50">
                            <h3 class="text-lg font-semibold text-gray-900">Grundlegende Informationen</h3>
                        </div>
                        <div class="px-6 py-5 space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">
                                        Name *
                                    </label>
                                    <input
                                        v-model="form.name"
                                        @blur="generateSlug"
                                        type="text"
                                        required
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                        placeholder="z.B. Standard Club"
                                    />
                                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">
                                        Slug *
                                    </label>
                                    <input
                                        v-model="form.slug"
                                        type="text"
                                        required
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                        placeholder="z.B. standard-club"
                                    />
                                    <p v-if="form.errors.slug" class="mt-1 text-sm text-red-600">{{ form.errors.slug }}</p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    Beschreibung
                                </label>
                                <textarea
                                    v-model="form.description"
                                    rows="3"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    placeholder="Beschreibe die Vorteile dieses Plans..."
                                ></textarea>
                            </div>

                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">
                                        Preis (EUR) * <span class="font-normal text-gray-500">{{ getAdminPriceLabel() }}</span>
                                    </label>
                                    <input
                                        v-model.number="form.price"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        required
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    />
                                    <p class="mt-1 text-xs text-gray-500">
                                        Vorschau für Kunden: {{ form.price > 0 ? formatPrice(adminInputIsGross ? inputPriceToNet(form.price) : form.price) : 'Kostenlos' }}
                                        <span v-if="form.price > 0 && getPriceLabel()">{{ getPriceLabel() }}</span>
                                    </p>
                                    <p v-if="getSmallBusinessNotice()" class="mt-1 text-xs text-amber-600">
                                        {{ getSmallBusinessNotice() }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">
                                        Abrechnungsintervall *
                                    </label>
                                    <select
                                        v-model="form.billing_interval"
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    >
                                        <option value="monthly">Monatlich</option>
                                        <option value="yearly">Jährlich</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">
                                        Trial (Tage)
                                    </label>
                                    <input
                                        v-model.number="form.trial_period_days"
                                        type="number"
                                        min="0"
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">
                                        Farbe
                                    </label>
                                    <div class="mt-1 flex items-center space-x-2">
                                        <input
                                            v-model="form.color"
                                            type="color"
                                            class="h-10 w-14 border-gray-300 rounded-md shadow-sm cursor-pointer"
                                        />
                                        <input
                                            v-model="form.color"
                                            type="text"
                                            class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                            placeholder="#6366F1"
                                        />
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">
                                        Sortierung
                                    </label>
                                    <input
                                        v-model.number="form.sort_order"
                                        type="number"
                                        min="0"
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    />
                                </div>
                                <div class="flex items-end space-x-4">
                                    <label class="flex items-center">
                                        <input
                                            v-model="form.is_active"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <span class="ml-2 text-sm text-gray-700">Aktiv</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input
                                            v-model="form.is_default"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <span class="ml-2 text-sm text-gray-700">Standard</span>
                                    </label>
                                    <label class="flex items-center" title="Featured Pläne werden auf der Landingpage angezeigt und sind bei der Registrierung auswählbar">
                                        <input
                                            v-model="form.is_featured"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-yellow-600 focus:ring-yellow-500"
                                        />
                                        <span class="ml-2 text-sm text-gray-700">Featured</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Limits -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-200 bg-gray-50">
                            <h3 class="text-lg font-semibold text-gray-900">Limits</h3>
                            <p class="mt-1 text-sm text-gray-500">-1 = unbegrenzt</p>
                        </div>
                        <div class="px-6 py-5 space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">
                                        Max Teams *
                                    </label>
                                    <input
                                        v-model.number="form.limits.max_teams"
                                        type="number"
                                        min="-1"
                                        required
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">
                                        Max Spieler *
                                    </label>
                                    <input
                                        v-model.number="form.limits.max_players"
                                        type="number"
                                        min="-1"
                                        required
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">
                                        Max Storage (GB)
                                    </label>
                                    <input
                                        v-model.number="form.limits.max_storage_gb"
                                        type="number"
                                        min="-1"
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">
                                        Max Spiele / Monat
                                    </label>
                                    <input
                                        v-model.number="form.limits.max_games_per_month"
                                        type="number"
                                        min="-1"
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">
                                        Max Trainings / Monat
                                    </label>
                                    <input
                                        v-model.number="form.limits.max_training_sessions_per_month"
                                        type="number"
                                        min="-1"
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">
                                        Max API Calls / Stunde
                                    </label>
                                    <input
                                        v-model.number="form.limits.max_api_calls_per_hour"
                                        type="number"
                                        min="-1"
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Features -->
                    <div v-if="featuresList && Object.keys(featuresList).length > 0" class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-200 bg-gray-50">
                            <h3 class="text-lg font-semibold text-gray-900">Features</h3>
                            <p class="mt-1 text-sm text-gray-500">Wähle die Features für diesen Plan</p>
                        </div>
                        <div class="px-6 py-5 space-y-3">
                            <FeatureSelector
                                v-for="(featureName, featureKey) in featuresList"
                                :key="featureKey"
                                v-model="form.features"
                                :feature-key="featureKey"
                                :feature-name="featureName"
                            />
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end px-6 py-4 bg-gray-50 rounded-lg space-x-3">
                        <SecondaryButton
                            type="button"
                            @click="$inertia.visit(route('admin.club-plans.index'))"
                        >
                            Abbrechen
                        </SecondaryButton>
                        <PrimaryButton
                            type="submit"
                            :disabled="form.processing"
                            :class="{ 'opacity-25': form.processing }"
                        >
                            Plan erstellen
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
