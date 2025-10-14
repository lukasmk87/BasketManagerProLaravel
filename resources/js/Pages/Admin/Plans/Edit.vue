<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import LimitEditor from '@/Components/Admin/LimitEditor.vue';
import FeatureSelector from '@/Components/Admin/FeatureSelector.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import { ref } from 'vue';

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

const showDeleteModal = ref(false);

const form = useForm({
    name: props.plan.name,
    slug: props.plan.slug,
    description: props.plan.description,
    price: props.plan.price / 100, // Convert cents to euros
    currency: props.plan.currency,
    billing_period: props.plan.billing_period,
    trial_days: props.plan.trial_days,
    is_active: props.plan.is_active,
    is_featured: props.plan.is_featured,
    sort_order: props.plan.sort_order,
    features: props.plan.raw_features || [],
    limits: props.plan.limits || {
        users: 100,
        teams: 10,
        players: 200,
        storage_gb: 50,
        api_calls_per_hour: 1000,
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
    form.transform((data) => ({
        ...data,
        price: Math.round(data.price * 100), // Convert euros to cents
    })).put(route('admin.plans.update', props.plan.id));
};

const deletePlan = () => {
    form.delete(route('admin.plans.destroy', props.plan.id), {
        onSuccess: () => {
            showDeleteModal.value = false;
        },
    });
};
</script>

<template>
    <AppLayout :title="`${plan.name} bearbeiten`">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        {{ plan.name }} bearbeiten
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        {{ plan.slug }}
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <Link
                        :href="route('admin.plans.show', plan.id)"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50"
                    >
                        ← Zurück
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="savePlan" class="space-y-6">
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
                                ></textarea>
                                <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
                            </div>

                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">
                                        Preis (€) *
                                    </label>
                                    <input
                                        v-model.number="form.price"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        required
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    />
                                    <p v-if="form.errors.price" class="mt-1 text-sm text-red-600">{{ form.errors.price }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">
                                        Billing Period *
                                    </label>
                                    <select
                                        v-model="form.billing_period"
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    >
                                        <option value="monthly">Monatlich</option>
                                        <option value="yearly">Jährlich</option>
                                        <option value="quarterly">Quartalsweise</option>
                                    </select>
                                    <p v-if="form.errors.billing_period" class="mt-1 text-sm text-red-600">{{ form.errors.billing_period }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">
                                        Trial Days
                                    </label>
                                    <input
                                        v-model.number="form.trial_days"
                                        type="number"
                                        min="0"
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    />
                                    <p v-if="form.errors.trial_days" class="mt-1 text-sm text-red-600">{{ form.errors.trial_days }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">
                                        Sort Order
                                    </label>
                                    <input
                                        v-model.number="form.sort_order"
                                        type="number"
                                        min="0"
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    />
                                    <p class="mt-1 text-xs text-gray-500">Niedrigere Zahlen erscheinen zuerst</p>
                                </div>
                                <div class="flex items-center pt-6">
                                    <label class="flex items-center">
                                        <input
                                            v-model="form.is_active"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <span class="ml-2 text-sm text-gray-700">Aktiv</span>
                                    </label>
                                </div>
                                <div class="flex items-center pt-6">
                                    <label class="flex items-center">
                                        <input
                                            v-model="form.is_featured"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
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
                            <p class="mt-1 text-sm text-gray-500">Definiere die maximalen Ressourcen für diesen Plan. -1 = unbegrenzt</p>
                        </div>
                        <div class="px-6 py-5 space-y-4">
                            <LimitEditor
                                v-model="form.limits.users"
                                label="Max Users"
                                metric="users"
                                :max="10000"
                            />
                            <LimitEditor
                                v-model="form.limits.teams"
                                label="Max Teams"
                                metric="teams"
                                :max="1000"
                            />
                            <LimitEditor
                                v-model="form.limits.players"
                                label="Max Players"
                                metric="players"
                                :max="10000"
                            />
                            <LimitEditor
                                v-model="form.limits.storage_gb"
                                label="Storage"
                                metric="storage_gb"
                                unit="GB"
                                :max="5000"
                            />
                            <LimitEditor
                                v-model="form.limits.api_calls_per_hour"
                                label="API Calls per Hour"
                                metric="api_calls_per_hour"
                                :max="100000"
                            />
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
                    <div class="flex items-center justify-between px-6 py-4 bg-gray-50 rounded-lg">
                        <DangerButton
                            type="button"
                            @click="showDeleteModal = true"
                            :disabled="(plan.tenants_count || 0) > 0"
                        >
                            Plan löschen
                        </DangerButton>
                        <div class="flex space-x-3">
                            <SecondaryButton
                                type="button"
                                @click="$inertia.visit(route('admin.plans.show', plan.id))"
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
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal :show="showDeleteModal" @close="showDeleteModal = false">
            <template #title>
                Plan löschen
            </template>

            <template #content>
                Sind Sie sicher, dass Sie den Plan "{{ plan.name }}" löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.
            </template>

            <template #footer>
                <SecondaryButton @click="showDeleteModal = false">
                    Abbrechen
                </SecondaryButton>

                <DangerButton
                    class="ml-3"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                    @click="deletePlan"
                >
                    Plan löschen
                </DangerButton>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>
