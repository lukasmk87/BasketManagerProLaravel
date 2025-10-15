<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    plan: {
        type: Object,
        required: true,
    },
    showActions: {
        type: Boolean,
        default: true,
    },
});

const billingPeriodLabel = computed(() => {
    const labels = {
        'monthly': 'Monatlich',
        'yearly': 'Jährlich',
        'quarterly': 'Quartalsweise'
    };
    return labels[props.plan.billing_period] || props.plan.billing_period;
});

const formatPrice = computed(() => {
    // Ensure price is a valid number, default to 0 if null/undefined
    const validPrice = typeof props.plan.price === 'number' && !isNaN(props.plan.price) ? props.plan.price : 0;
    return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: props.plan.currency || 'EUR',
    }).format(validPrice / 100);
});

const isUnlimited = (value) => {
    return value === -1 || value === null;
};

const formatLimit = (value, unit = '') => {
    if (isUnlimited(value)) {
        return 'Unbegrenzt';
    }
    return `${value.toLocaleString('de-DE')}${unit ? ' ' + unit : ''}`;
};
</script>

<template>
    <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200 overflow-hidden border border-gray-200"
         :class="{ 'ring-2 ring-indigo-500': plan.is_featured }">
        <!-- Header -->
        <div class="px-6 py-5 border-b border-gray-200"
             :class="plan.is_featured ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white' : 'bg-gray-50'">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="text-xl font-bold" :class="plan.is_featured ? 'text-white' : 'text-gray-900'">
                        {{ plan.name }}
                    </h3>
                    <p class="mt-1 text-sm" :class="plan.is_featured ? 'text-indigo-100' : 'text-gray-500'">
                        {{ plan.slug }}
                    </p>
                </div>
                <div class="flex flex-col items-end space-y-1">
                    <span v-if="plan.is_featured" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                        ⭐ Featured
                    </span>
                    <span v-if="!plan.is_active" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                        Inaktiv
                    </span>
                    <span v-if="plan.is_custom" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                        Custom
                    </span>
                </div>
            </div>
        </div>

        <!-- Price -->
        <div class="px-6 py-4 bg-white">
            <div class="flex items-baseline">
                <span class="text-4xl font-extrabold text-gray-900">{{ formatPrice }}</span>
                <span class="ml-2 text-sm text-gray-500">/ {{ billingPeriodLabel }}</span>
            </div>
            <p v-if="plan.trial_days > 0" class="mt-1 text-sm text-indigo-600">
                {{ plan.trial_days }} Tage kostenloser Test
            </p>
        </div>

        <!-- Description -->
        <div v-if="plan.description" class="px-6 py-3 border-t border-gray-100">
            <p class="text-sm text-gray-600">{{ plan.description }}</p>
        </div>

        <!-- Limits -->
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Limits</h4>
            <div class="space-y-2">
                <div v-if="plan.limits" class="grid grid-cols-2 gap-2 text-sm">
                    <div v-if="plan.limits.users !== undefined" class="flex items-center">
                        <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        <span class="text-gray-600">Users:</span>
                        <span class="ml-auto font-medium" :class="isUnlimited(plan.limits.users) ? 'text-green-600' : 'text-gray-900'">
                            {{ formatLimit(plan.limits.users) }}
                        </span>
                    </div>
                    <div v-if="plan.limits.teams !== undefined" class="flex items-center">
                        <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <span class="text-gray-600">Teams:</span>
                        <span class="ml-auto font-medium" :class="isUnlimited(plan.limits.teams) ? 'text-green-600' : 'text-gray-900'">
                            {{ formatLimit(plan.limits.teams) }}
                        </span>
                    </div>
                    <div v-if="plan.limits.players !== undefined" class="flex items-center">
                        <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <span class="text-gray-600">Spieler:</span>
                        <span class="ml-auto font-medium" :class="isUnlimited(plan.limits.players) ? 'text-green-600' : 'text-gray-900'">
                            {{ formatLimit(plan.limits.players) }}
                        </span>
                    </div>
                    <div v-if="plan.limits.storage_gb !== undefined" class="flex items-center">
                        <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                        </svg>
                        <span class="text-gray-600">Storage:</span>
                        <span class="ml-auto font-medium" :class="isUnlimited(plan.limits.storage_gb) ? 'text-green-600' : 'text-gray-900'">
                            {{ formatLimit(plan.limits.storage_gb, 'GB') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features -->
        <div v-if="plan.features && plan.features.length > 0" class="px-6 py-4 border-t border-gray-200">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Features</h4>
            <ul class="space-y-2">
                <li v-for="(feature, index) in plan.features" :key="index" class="flex items-start text-sm">
                    <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span class="text-gray-700">{{ typeof feature === 'object' ? (feature.name || feature.slug) : feature }}</span>
                </li>
            </ul>
        </div>

        <!-- Statistics -->
        <div v-if="plan.tenants_count !== undefined || plan.active_tenants_count !== undefined" class="px-6 py-3 bg-gray-50 border-t border-gray-200">
            <div class="flex justify-between text-sm">
                <div v-if="plan.tenants_count !== undefined">
                    <span class="text-gray-500">Tenants:</span>
                    <span class="ml-2 font-medium text-gray-900">{{ plan.tenants_count }}</span>
                </div>
                <div v-if="plan.active_tenants_count !== undefined">
                    <span class="text-gray-500">Aktiv:</span>
                    <span class="ml-2 font-medium text-green-600">{{ plan.active_tenants_count }}</span>
                </div>
                <div v-if="plan.monthly_revenue !== undefined">
                    <span class="text-gray-500">MRR:</span>
                    <span class="ml-2 font-medium text-indigo-600">
                        {{ new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' }).format((plan.monthly_revenue || 0) / 100) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div v-if="showActions" class="px-6 py-4 bg-white border-t border-gray-200 flex justify-between items-center">
            <Link
                :href="route('admin.plans.show', { plan: plan.slug })"
                class="text-sm font-medium text-indigo-600 hover:text-indigo-900"
            >
                Details anzeigen →
            </Link>
            <div class="flex space-x-2">
                <Link
                    :href="route('admin.plans.edit', { plan: plan.slug })"
                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    Bearbeiten
                </Link>
                <form
                    @submit.prevent="$inertia.post(route('admin.plans.clone', { plan: plan.slug }))"
                    class="inline"
                >
                    <button
                        type="submit"
                        class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        Klonen
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>
