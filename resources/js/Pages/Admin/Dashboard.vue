<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TenantCard from '@/Components/Admin/TenantCard.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    tenants: Object,
    stats: Object,
    planStats: Array,
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount / 100);
};

const formatNumber = (number) => {
    return new Intl.NumberFormat('de-DE').format(number);
};

// Chart data for plan distribution (simplified without external charting library)
const planDistribution = computed(() => {
    if (!props.planStats || props.planStats.length === 0) return [];

    const total = props.planStats.reduce((sum, plan) => sum + (plan.tenants_count || 0), 0);

    return props.planStats.map(plan => ({
        ...plan,
        percentage: total > 0 ? Math.round((plan.tenants_count / total) * 100) : 0,
    }));
});

const getPlanColor = (index) => {
    const colors = [
        'bg-blue-500',
        'bg-green-500',
        'bg-yellow-500',
        'bg-purple-500',
        'bg-pink-500',
        'bg-indigo-500',
        'bg-red-500',
    ];
    return colors[index % colors.length];
};
</script>

<template>
    <AppLayout title="Admin Dashboard">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Admin Dashboard
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Subscription & Tenant Verwaltung
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <Link
                        :href="route('admin.plans.index')"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        📋 Plans verwalten
                    </Link>
                    <Link
                        :href="route('admin.tenants.index')"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        🏢 Alle Tenants
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Total Tenants -->
                    <div class="bg-white overflow-hidden shadow-lg rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Gesamt Tenants
                                        </dt>
                                        <dd class="flex items-baseline">
                                            <div class="text-2xl font-semibold text-gray-900">
                                                {{ formatNumber(stats.total_tenants) }}
                                            </div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Active Tenants -->
                    <div class="bg-white overflow-hidden shadow-lg rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Aktive Tenants
                                        </dt>
                                        <dd class="flex items-baseline">
                                            <div class="text-2xl font-semibold text-gray-900">
                                                {{ formatNumber(stats.active_tenants) }}
                                            </div>
                                            <div class="ml-2 flex items-baseline text-sm font-semibold text-green-600">
                                                {{ Math.round((stats.active_tenants / stats.total_tenants) * 100) }}%
                                            </div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Revenue -->
                    <div class="bg-white overflow-hidden shadow-lg rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Total Revenue
                                        </dt>
                                        <dd class="flex items-baseline">
                                            <div class="text-2xl font-semibold text-gray-900">
                                                {{ formatCurrency(stats.total_revenue) }}
                                            </div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MRR -->
                    <div class="bg-white overflow-hidden shadow-lg rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            MRR
                                        </dt>
                                        <dd class="flex items-baseline">
                                            <div class="text-2xl font-semibold text-gray-900">
                                                {{ formatCurrency(stats.mrr) }}
                                            </div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Plan Distribution -->
                <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Plan-Verteilung</h3>
                        <p class="mt-1 text-sm text-gray-500">Übersicht über aktive Subscription Plans</p>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div v-for="(plan, index) in planDistribution" :key="plan.id" class="space-y-2">
                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center space-x-3">
                                        <div :class="[getPlanColor(index), 'w-3 h-3 rounded-full']"></div>
                                        <span class="font-medium text-gray-900">{{ plan.name }}</span>
                                    </div>
                                    <div class="flex items-center space-x-4">
                                        <span class="text-gray-500">{{ plan.tenants_count }} Tenants</span>
                                        <span class="font-semibold text-gray-900">{{ plan.percentage }}%</span>
                                    </div>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div
                                        :class="getPlanColor(index)"
                                        class="h-2.5 rounded-full transition-all duration-500"
                                        :style="{ width: `${plan.percentage}%` }"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <div v-if="planDistribution.length === 0" class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">Keine Plan-Daten verfügbar</p>
                        </div>
                    </div>
                </div>

                <!-- Recent Tenants -->
                <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Aktuelle Tenants</h3>
                                <p class="mt-1 text-sm text-gray-500">Übersicht der registrierten Tenants</p>
                            </div>
                            <Link
                                :href="route('admin.tenants.index')"
                                class="text-sm font-medium text-indigo-600 hover:text-indigo-900"
                            >
                                Alle anzeigen →
                            </Link>
                        </div>
                    </div>

                    <div class="p-6">
                        <div v-if="tenants.data && tenants.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <TenantCard
                                v-for="tenant in tenants.data"
                                :key="tenant.id"
                                :tenant="tenant"
                                :show-actions="true"
                            />
                        </div>

                        <div v-else class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Keine Tenants</h3>
                            <p class="mt-1 text-sm text-gray-500">Es sind noch keine Tenants registriert.</p>
                        </div>

                        <!-- Pagination -->
                        <div v-if="tenants.data && tenants.data.length > 0" class="mt-6">
                            <Pagination :links="tenants.links" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
