<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    tenant: {
        type: Object,
        required: true,
    },
    showActions: {
        type: Boolean,
        default: true,
    },
});

const statusBadge = computed(() => {
    if (props.tenant.is_suspended) {
        return { class: 'bg-red-100 text-red-800', text: 'Gesperrt' };
    }
    if (props.tenant.is_active) {
        return { class: 'bg-green-100 text-green-800', text: 'Aktiv' };
    }
    return { class: 'bg-gray-100 text-gray-800', text: 'Inaktiv' };
});

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Intl.DateTimeFormat('de-DE', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    }).format(new Date(dateString));
};

const formatRevenue = (amount) => {
    if (!amount) return '-';
    return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount / 100);
};

const isTrialActive = computed(() => {
    if (!props.tenant.trial_ends_at) return false;
    return new Date(props.tenant.trial_ends_at) > new Date();
});

const trialDaysLeft = computed(() => {
    if (!isTrialActive.value) return 0;
    const now = new Date();
    const trialEnd = new Date(props.tenant.trial_ends_at);
    const diffTime = Math.abs(trialEnd - now);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays;
});
</script>

<template>
    <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200 overflow-hidden border border-gray-200">
        <!-- Header -->
        <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200">
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <div class="flex items-center space-x-3">
                        <h3 class="text-lg font-bold text-gray-900">
                            {{ tenant.name }}
                        </h3>
                        <span
                            :class="[
                                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                statusBadge.class
                            ]"
                        >
                            {{ statusBadge.text }}
                        </span>
                    </div>
                    <div class="mt-1 flex items-center space-x-4 text-sm text-gray-500">
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                            </svg>
                            {{ tenant.domain || tenant.subdomain }}
                        </span>
                        <span v-if="tenant.slug">{{ tenant.slug }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subscription Info -->
        <div class="px-6 py-4">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <span class="text-xs font-medium text-gray-500 uppercase">Subscription Plan</span>
                    <p class="mt-1 text-sm font-semibold text-gray-900">
                        {{ tenant.subscription_plan?.name || tenant.subscription_tier || 'Kein Plan' }}
                    </p>
                </div>
                <div v-if="isTrialActive" class="text-right">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        🎯 Trial: {{ trialDaysLeft }} Tage verbleibend
                    </span>
                </div>
            </div>

            <!-- Trial Info -->
            <div v-if="tenant.trial_ends_at" class="text-xs text-gray-500">
                Trial endet am: {{ formatDate(tenant.trial_ends_at) }}
            </div>
        </div>

        <!-- Usage Statistics -->
        <div v-if="tenant.current_counts" class="px-6 py-4 bg-gray-50 border-t border-gray-200">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Aktuelle Nutzung</h4>
            <div class="grid grid-cols-2 gap-4">
                <div v-if="tenant.current_counts.users !== undefined">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                            <span class="text-sm text-gray-600">Users</span>
                        </div>
                        <span class="text-sm font-medium text-gray-900">
                            {{ tenant.current_counts.users }}
                            <span v-if="tenant.max_limits?.users" class="text-gray-400">
                                / {{ tenant.max_limits.users === -1 ? '∞' : tenant.max_limits.users }}
                            </span>
                        </span>
                    </div>
                </div>

                <div v-if="tenant.current_counts.teams !== undefined">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <span class="text-sm text-gray-600">Teams</span>
                        </div>
                        <span class="text-sm font-medium text-gray-900">
                            {{ tenant.current_counts.teams }}
                            <span v-if="tenant.max_limits?.teams" class="text-gray-400">
                                / {{ tenant.max_limits.teams === -1 ? '∞' : tenant.max_limits.teams }}
                            </span>
                        </span>
                    </div>
                </div>

                <div v-if="tenant.current_counts.players !== undefined">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <span class="text-sm text-gray-600">Spieler</span>
                        </div>
                        <span class="text-sm font-medium text-gray-900">
                            {{ tenant.current_counts.players }}
                        </span>
                    </div>
                </div>

                <div v-if="tenant.current_counts.games !== undefined">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            <span class="text-sm text-gray-600">Spiele</span>
                        </div>
                        <span class="text-sm font-medium text-gray-900">
                            {{ tenant.current_counts.games }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenue -->
        <div v-if="tenant.revenue" class="px-6 py-3 bg-white border-t border-gray-100">
            <div class="flex justify-between items-center text-sm">
                <div>
                    <span class="text-gray-500">Total Revenue:</span>
                    <span class="ml-2 font-medium text-gray-900">{{ formatRevenue(tenant.revenue.total) }}</span>
                </div>
                <div>
                    <span class="text-gray-500">MRR:</span>
                    <span class="ml-2 font-medium text-indigo-600">{{ formatRevenue(tenant.revenue.mrr) }}</span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-between items-center text-xs text-gray-500">
            <div>
                <span>Erstellt: {{ formatDate(tenant.created_at) }}</span>
            </div>
            <div v-if="tenant.last_activity_at">
                <span>Letzte Aktivität: {{ formatDate(tenant.last_activity_at) }}</span>
            </div>
        </div>

        <!-- Actions -->
        <div v-if="showActions" class="px-6 py-4 bg-white border-t border-gray-200 flex justify-end space-x-2">
            <Link
                :href="route('admin.tenants.show', tenant.id)"
                class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
                Details anzeigen →
            </Link>
        </div>
    </div>
</template>
