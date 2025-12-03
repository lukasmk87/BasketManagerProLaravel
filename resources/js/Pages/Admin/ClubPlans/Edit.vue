<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import FeatureSelector from '@/Components/Admin/FeatureSelector.vue';
import { usePricing } from '@/Composables/usePricing';

const { formatPrice, getPriceLabel, getSmallBusinessNotice } = usePricing();

const props = defineProps({
    plan: {
        type: Object,
        required: true,
    },
    featuresList: {
        type: Object,
        default: () => ({}),
    },
});

const form = useForm({
    name: props.plan.name || '',
    slug: props.plan.slug || '',
    description: props.plan.description || '',
    price: props.plan.price || 0,
    currency: props.plan.currency || 'EUR',
    billing_interval: props.plan.billing_interval || 'monthly',
    trial_period_days: props.plan.trial_period_days || 0,
    is_active: props.plan.is_active ?? true,
    is_default: props.plan.is_default ?? false,
    sort_order: props.plan.sort_order || 0,
    color: props.plan.color || '#6366F1',
    icon: props.plan.icon || '',
    features: props.plan.features || [],
    limits: {
        max_teams: props.plan.limits?.max_teams ?? 10,
        max_players: props.plan.limits?.max_players ?? 150,
        max_storage_gb: props.plan.limits?.max_storage_gb ?? 25,
        max_games_per_month: props.plan.limits?.max_games_per_month ?? 100,
        max_training_sessions_per_month: props.plan.limits?.max_training_sessions_per_month ?? 200,
        max_api_calls_per_hour: props.plan.limits?.max_api_calls_per_hour ?? 1000,
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

const updatePlan = () => {
    form.put(route('admin.club-plans.update', props.plan.id));
};
</script>

<template>
    <AdminLayout :title="`Plan bearbeiten: ${plan.name}`">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <div class="flex items-center space-x-3">
                        <div
                            v-if="plan.color"
                            class="w-4 h-4 rounded-full"
                            :style="{ backgroundColor: plan.color }"
                        ></div>
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                            Plan bearbeiten: {{ plan.name }}
                        </h2>
                    </div>
                    <p class="text-sm text-gray-600 mt-1">
                        {{ plan.slug }} | {{ plan.tenant?.name || 'Unbekannter Tenant' }}
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <Link
                        :href="route('admin.club-plans.show', plan.id)"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50"
                    >
                        Zurück
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="updatePlan" class="space-y-6">
                    <!-- Tenant Info (Read-only) -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-200 bg-gray-50">
                            <h3 class="text-lg font-semibold text-gray-900">Tenant</h3>
                        </div>
                        <div class="px-6 py-5">
                            <p class="text-sm text-gray-500">Der Tenant kann nicht geändert werden.</p>
                            <p class="mt-2 text-base font-medium text-gray-900">
                                {{ plan.tenant?.name || 'Unbekannt' }}
                            </p>
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
                                        Preis (EUR) * <span class="font-normal text-gray-500">– Netto-Preis eingeben</span>
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
                                        Vorschau für Kunden: {{ form.price > 0 ? formatPrice(form.price) : 'Kostenlos' }}
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

                    <!-- Stripe Info -->
                    <div v-if="plan.stripe_product_id || plan.stripe_price_id" class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-200 bg-gray-50">
                            <h3 class="text-lg font-semibold text-gray-900">Stripe Integration</h3>
                        </div>
                        <div class="px-6 py-5">
                            <div class="grid grid-cols-2 gap-4">
                                <div v-if="plan.stripe_product_id">
                                    <p class="text-sm font-medium text-gray-500">Stripe Product ID</p>
                                    <p class="mt-1 text-sm font-mono text-gray-900">{{ plan.stripe_product_id }}</p>
                                </div>
                                <div v-if="plan.stripe_price_id">
                                    <p class="text-sm font-medium text-gray-500">Stripe Price ID</p>
                                    <p class="mt-1 text-sm font-mono text-gray-900">{{ plan.stripe_price_id }}</p>
                                </div>
                            </div>
                            <p class="mt-4 text-sm text-amber-600">
                                Hinweis: Änderungen am Preis erfordern ggf. eine neue Stripe Price ID.
                            </p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end px-6 py-4 bg-gray-50 rounded-lg space-x-3">
                        <SecondaryButton
                            type="button"
                            @click="$inertia.visit(route('admin.club-plans.show', plan.id))"
                        >
                            Abbrechen
                        </SecondaryButton>
                        <PrimaryButton
                            type="submit"
                            :disabled="form.processing"
                            :class="{ 'opacity-25': form.processing }"
                        >
                            Änderungen speichern
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
