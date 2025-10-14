<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import LimitEditor from '@/Components/Admin/LimitEditor.vue';
import FeatureToggle from '@/Components/Admin/FeatureToggle.vue';
import TenantCard from '@/Components/Admin/TenantCard.vue';
import Pagination from '@/Components/Pagination.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';

const props = defineProps({
    plan: Object,
    tenants: Object,
});

const isEditing = ref(false);
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
    features: props.plan.features || [],
    limits: props.plan.limits || {
        users: 100,
        teams: 10,
        players: 200,
        storage_gb: 50,
        api_calls_per_hour: 1000,
    },
});

const startEditing = () => {
    isEditing.value = true;
};

const cancelEditing = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
};

const savePlan = () => {
    form.transform((data) => ({
        ...data,
        price: Math.round(data.price * 100), // Convert euros to cents
    })).put(route('admin.plans.update', props.plan.slug), {
        onSuccess: () => {
            isEditing.value = false;
        },
    });
};

const clonePlan = () => {
    form.post(route('admin.plans.clone', props.plan.slug));
};

const deletePlan = () => {
    form.delete(route('admin.plans.destroy', props.plan.slug), {
        onSuccess: () => {
            showDeleteModal.value = false;
        },
    });
};

const formatPrice = (cents) => {
    // Ensure cents is a valid number, default to 0 if null/undefined
    const validCents = typeof cents === 'number' && !isNaN(cents) ? cents : 0;
    return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: 'EUR',
    }).format(validCents / 100);
};

const formatNumber = (number) => {
    return new Intl.NumberFormat('de-DE').format(number);
};
</script>

<template>
    <AppLayout :title="plan.name">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <div class="flex items-center space-x-3">
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                            {{ plan.name }}
                        </h2>
                        <span v-if="plan.is_featured" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            ⭐ Featured
                        </span>
                        <span v-if="!plan.is_active" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            Inaktiv
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 mt-1">
                        {{ plan.slug }}
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <Link
                        :href="route('admin.plans.index')"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50"
                    >
                        ← Zurück
                    </Link>
                    <SecondaryButton v-if="!isEditing" @click="clonePlan">
                        Klonen
                    </SecondaryButton>
                    <PrimaryButton v-if="!isEditing" @click="startEditing">
                        Bearbeiten
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-4">
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <dt class="text-sm font-medium text-gray-500 truncate">Preis</dt>
                            <dd class="mt-1 text-2xl font-semibold text-gray-900">
                                {{ formatPrice(plan.price) }}
                            </dd>
                            <dd class="text-xs text-gray-500">pro {{ plan.billing_period_label || plan.billing_period }}</dd>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <dt class="text-sm font-medium text-gray-500 truncate">Tenants</dt>
                            <dd class="mt-1 text-2xl font-semibold text-gray-900">
                                {{ plan.tenants_count || 0 }}
                            </dd>
                            <dd class="text-xs text-gray-500">{{ plan.active_tenants_count || 0 }} aktiv</dd>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <dt class="text-sm font-medium text-gray-500 truncate">MRR</dt>
                            <dd class="mt-1 text-2xl font-semibold text-gray-900">
                                {{ formatPrice(plan.monthly_revenue || 0) }}
                            </dd>
                            <dd class="text-xs text-gray-500">Monatlich wiederkehrend</dd>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <dt class="text-sm font-medium text-gray-500 truncate">Trial</dt>
                            <dd class="mt-1 text-2xl font-semibold text-gray-900">
                                {{ plan.trial_days }}
                            </dd>
                            <dd class="text-xs text-gray-500">Tage kostenlos</dd>
                        </div>
                    </div>
                </div>

                <!-- View/Edit Mode -->
                <div v-if="!isEditing" class="space-y-8">
                    <!-- Plan Details -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-200 bg-gray-50">
                            <h3 class="text-lg font-semibold text-gray-900">Plan Details</h3>
                        </div>
                        <div class="px-6 py-5 space-y-4">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Beschreibung</p>
                                <p class="mt-1 text-base text-gray-900">{{ plan.description || '-' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Limits -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-200 bg-gray-50">
                            <h3 class="text-lg font-semibold text-gray-900">Limits</h3>
                        </div>
                        <div class="px-6 py-5">
                            <dl class="grid grid-cols-2 gap-6">
                                <div v-if="plan.limits && plan.limits.users !== undefined">
                                    <dt class="text-sm font-medium text-gray-500">Max Users</dt>
                                    <dd class="mt-1 text-2xl font-semibold text-gray-900">
                                        {{ plan.limits.users === -1 ? 'Unbegrenzt' : formatNumber(plan.limits.users) }}
                                    </dd>
                                </div>
                                <div v-if="plan.limits && plan.limits.teams !== undefined">
                                    <dt class="text-sm font-medium text-gray-500">Max Teams</dt>
                                    <dd class="mt-1 text-2xl font-semibold text-gray-900">
                                        {{ plan.limits.teams === -1 ? 'Unbegrenzt' : formatNumber(plan.limits.teams) }}
                                    </dd>
                                </div>
                                <div v-if="plan.limits && plan.limits.players !== undefined">
                                    <dt class="text-sm font-medium text-gray-500">Max Players</dt>
                                    <dd class="mt-1 text-2xl font-semibold text-gray-900">
                                        {{ plan.limits.players === -1 ? 'Unbegrenzt' : formatNumber(plan.limits.players) }}
                                    </dd>
                                </div>
                                <div v-if="plan.limits && plan.limits.storage_gb !== undefined">
                                    <dt class="text-sm font-medium text-gray-500">Storage</dt>
                                    <dd class="mt-1 text-2xl font-semibold text-gray-900">
                                        {{ plan.limits.storage_gb === -1 ? 'Unbegrenzt' : formatNumber(plan.limits.storage_gb) + ' GB' }}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Features -->
                    <div v-if="plan.features && plan.features.length > 0" class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-200 bg-gray-50">
                            <h3 class="text-lg font-semibold text-gray-900">Features</h3>
                        </div>
                        <div class="px-6 py-5">
                            <ul class="space-y-3">
                                <li v-for="(feature, index) in plan.features" :key="index" class="flex items-start">
                                    <svg class="w-6 h-6 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-base text-gray-900">{{ typeof feature === 'object' ? (feature.name || feature.slug) : feature }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Edit Form -->
                <form v-else @submit.prevent="savePlan" class="space-y-6">
                    <!-- Basic Info -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-200 bg-gray-50">
                            <h3 class="text-lg font-semibold text-gray-900">Grundlegende Informationen</h3>
                        </div>
                        <div class="px-6 py-5 space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Name</label>
                                    <input v-model="form.name" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Slug</label>
                                    <input v-model="form.slug" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" />
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Beschreibung</label>
                                <textarea v-model="form.description" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></textarea>
                            </div>

                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Preis (€)</label>
                                    <input v-model="form.price" type="number" step="0.01" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Billing Period</label>
                                    <select v-model="form.billing_period" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                        <option value="monthly">Monatlich</option>
                                        <option value="yearly">Jährlich</option>
                                        <option value="quarterly">Quartalsweise</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Trial Days</label>
                                    <input v-model="form.trial_days" type="number" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" />
                                </div>
                            </div>

                            <div class="flex items-center space-x-6">
                                <label class="flex items-center">
                                    <input v-model="form.is_active" type="checkbox" class="rounded border-gray-300" />
                                    <span class="ml-2 text-sm text-gray-700">Aktiv</span>
                                </label>
                                <label class="flex items-center">
                                    <input v-model="form.is_featured" type="checkbox" class="rounded border-gray-300" />
                                    <span class="ml-2 text-sm text-gray-700">Featured</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Limits -->
                    <div v-if="form.limits" class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-200 bg-gray-50">
                            <h3 class="text-lg font-semibold text-gray-900">Limits</h3>
                        </div>
                        <div class="px-6 py-5 space-y-4">
                            <LimitEditor v-model="form.limits.users" label="Max Users" metric="users" :max="10000" />
                            <LimitEditor v-model="form.limits.teams" label="Max Teams" metric="teams" :max="1000" />
                            <LimitEditor v-model="form.limits.players" label="Max Players" metric="players" :max="10000" />
                            <LimitEditor v-model="form.limits.storage_gb" label="Storage" metric="storage_gb" unit="GB" :max="5000" />
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-between px-6 py-4 bg-gray-50 border-t border-gray-200">
                        <DangerButton @click="showDeleteModal = true" type="button" :disabled="(plan.tenants_count || 0) > 0">
                            Plan löschen
                        </DangerButton>
                        <div class="flex space-x-3">
                            <SecondaryButton @click="cancelEditing" type="button">
                                Abbrechen
                            </SecondaryButton>
                            <PrimaryButton type="submit" :disabled="form.processing">
                                Speichern
                            </PrimaryButton>
                        </div>
                    </div>
                </form>

                <!-- Tenants using this plan -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-semibold text-gray-900">Tenants ({{ plan.tenants_count || 0 }})</h3>
                        <p class="mt-1 text-sm text-gray-500">Tenants die diesen Plan nutzen</p>
                    </div>
                    <div class="px-6 py-5">
                        <div v-if="tenants.data && tenants.data.length > 0" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <TenantCard
                                    v-for="tenant in tenants.data"
                                    :key="tenant.id"
                                    :tenant="tenant"
                                    :show-actions="true"
                                />
                            </div>
                            <Pagination :links="tenants.links" />
                        </div>
                        <div v-else class="text-center py-8">
                            <p class="text-sm text-gray-500">Keine Tenants nutzen diesen Plan.</p>
                        </div>
                    </div>
                </div>
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
