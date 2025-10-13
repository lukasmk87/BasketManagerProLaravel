<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import UsageLimitProgress from '@/Components/Admin/UsageLimitProgress.vue';
import SubscriptionEditor from '@/Components/Admin/SubscriptionEditor.vue';
import CustomizationForm from '@/Components/Admin/CustomizationForm.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    tenant: Object,
    limits: Object,
    availablePlans: Array,
});

const showCustomizationModal = ref(false);

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Intl.DateTimeFormat('de-DE', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(dateString));
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount / 100);
};

const getLimitMetricLabel = (metric) => {
    const labels = {
        'users': 'Users',
        'teams': 'Teams',
        'players': 'Spieler',
        'games': 'Spiele',
        'storage_gb': 'Storage',
        'api_calls_per_hour': 'API Calls',
    };
    return labels[metric] || metric;
};

const getLimitUnit = (metric) => {
    const units = {
        'storage_gb': 'GB',
        'api_calls_per_hour': '/h',
    };
    return units[metric] || '';
};

const onSubscriptionUpdated = () => {
    window.location.reload();
};

const onCustomizationCreated = () => {
    window.location.reload();
};
</script>

<template>
    <AppLayout :title="tenant.name">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <div class="flex items-center space-x-3">
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                            {{ tenant.name }}
                        </h2>
                        <span
                            v-if="tenant.is_active"
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                        >
                            Aktiv
                        </span>
                        <span
                            v-else-if="tenant.is_suspended"
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"
                        >
                            Gesperrt
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800"
                        >
                            Inaktiv
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 mt-1">
                        {{ tenant.domain || tenant.subdomain }}
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <Link
                        :href="route('admin.tenants.index')"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50"
                    >
                        ← Zurück
                    </Link>
                    <Link
                        v-if="tenant?.id"
                        :href="route('admin.tenants.edit', tenant.id)"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Bearbeiten
                    </Link>
                    <PrimaryButton v-if="tenant?.id" @click="showCustomizationModal = true">
                        Customization erstellen
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                <!-- Tenant Info Cards -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-4">
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <dt class="text-sm font-medium text-gray-500 truncate">Erstellt am</dt>
                            <dd class="mt-1 text-base font-semibold text-gray-900">
                                {{ formatDate(tenant.created_at) }}
                            </dd>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <dt class="text-sm font-medium text-gray-500 truncate">Letzte Aktivität</dt>
                            <dd class="mt-1 text-base font-semibold text-gray-900">
                                {{ formatDate(tenant.last_activity_at) }}
                            </dd>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Revenue</dt>
                            <dd class="mt-1 text-base font-semibold text-gray-900">
                                {{ formatCurrency(tenant.revenue?.total || 0) }}
                            </dd>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <dt class="text-sm font-medium text-gray-500 truncate">MRR</dt>
                            <dd class="mt-1 text-base font-semibold text-indigo-600">
                                {{ formatCurrency(tenant.revenue?.mrr || 0) }}
                            </dd>
                        </div>
                    </div>
                </div>

                <!-- Subscription Editor -->
                <SubscriptionEditor
                    :tenant="tenant"
                    :available-plans="availablePlans"
                    :current-plan="tenant.subscription_plan"
                    @updated="onSubscriptionUpdated"
                />

                <!-- Usage Limits -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-semibold text-gray-900">Usage & Limits</h3>
                        <p class="mt-1 text-sm text-gray-500">Aktuelle Nutzung vs. Limits</p>
                    </div>

                    <div class="px-6 py-5 space-y-6">
                        <div v-for="(limitData, metric) in limits" :key="metric">
                            <UsageLimitProgress
                                :metric="metric"
                                :current="limitData.current"
                                :limit="limitData.limit"
                                :label="getLimitMetricLabel(metric)"
                                :unit="getLimitUnit(metric)"
                                :show-percentage="true"
                                size="lg"
                            />
                        </div>

                        <div v-if="Object.keys(limits).length === 0" class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">Keine Limit-Daten verfügbar</p>
                        </div>
                    </div>
                </div>

                <!-- Customizations -->
                <div v-if="tenant.customization" class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-semibold text-gray-900">Aktive Customization</h3>
                        <p class="mt-1 text-sm text-gray-500">Spezielle Konfiguration für diesen Tenant</p>
                    </div>

                    <div class="px-6 py-5 space-y-4">
                        <!-- Custom Features -->
                        <div v-if="tenant.customization.custom_features && tenant.customization.custom_features.length > 0">
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">Custom Features</h4>
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-for="feature in tenant.customization.custom_features"
                                    :key="feature"
                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800"
                                >
                                    ✓ {{ feature }}
                                </span>
                            </div>
                        </div>

                        <!-- Disabled Features -->
                        <div v-if="tenant.customization.disabled_features && tenant.customization.disabled_features.length > 0">
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">Disabled Features</h4>
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-for="feature in tenant.customization.disabled_features"
                                    :key="feature"
                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800"
                                >
                                    ✗ {{ feature }}
                                </span>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div v-if="tenant.customization.notes">
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">Notizen</h4>
                            <p class="text-sm text-gray-600">{{ tenant.customization.notes }}</p>
                        </div>

                        <!-- Validity Period -->
                        <div class="text-xs text-gray-500 border-t pt-3">
                            <p>
                                Gültig von: {{ formatDate(tenant.customization.effective_from) }}
                                <span v-if="tenant.customization.effective_until">
                                    bis {{ formatDate(tenant.customization.effective_until) }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Additional Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Current Counts -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-200 bg-gray-50">
                            <h3 class="text-lg font-semibold text-gray-900">Aktuelle Counts</h3>
                        </div>
                        <div class="px-6 py-5">
                            <dl class="space-y-3">
                                <div v-if="tenant.current_counts?.users !== undefined" class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Users:</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ tenant.current_counts.users }}</dd>
                                </div>
                                <div v-if="tenant.current_counts?.teams !== undefined" class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Teams:</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ tenant.current_counts.teams }}</dd>
                                </div>
                                <div v-if="tenant.current_counts?.players !== undefined" class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Spieler:</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ tenant.current_counts.players }}</dd>
                                </div>
                                <div v-if="tenant.current_counts?.games !== undefined" class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Spiele:</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ tenant.current_counts.games }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Tenant Settings -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-200 bg-gray-50">
                            <h3 class="text-lg font-semibold text-gray-900">Tenant Settings</h3>
                        </div>
                        <div class="px-6 py-5">
                            <dl class="space-y-3">
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Slug:</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ tenant.slug }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Subscription Tier:</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ tenant.subscription_tier || '-' }}</dd>
                                </div>
                                <div v-if="tenant.trial_ends_at" class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Trial endet:</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ formatDate(tenant.trial_ends_at) }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customization Modal -->
        <CustomizationForm
            :show="showCustomizationModal"
            :tenant="tenant"
            :current-plan="tenant.subscription_plan"
            :existing-customization="tenant.customization"
            @close="showCustomizationModal = false"
            @created="onCustomizationCreated"
        />
    </AppLayout>
</template>