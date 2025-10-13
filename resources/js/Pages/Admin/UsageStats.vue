<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import UsageLimitProgress from '@/Components/Admin/UsageLimitProgress.vue';

const props = defineProps({
    approaching_limits: Array,
});

// Group tenants by severity
const criticalTenants = computed(() => {
    return props.approaching_limits.filter(item => item.percentage >= 100);
});

const warningTenants = computed(() => {
    return props.approaching_limits.filter(item => item.percentage >= 90 && item.percentage < 100);
});

const cautionTenants = computed(() => {
    return props.approaching_limits.filter(item => item.percentage >= 80 && item.percentage < 90);
});

const getSeverityBadge = (percentage) => {
    if (percentage >= 100) {
        return { class: 'bg-red-100 text-red-800', text: 'Kritisch', icon: '🚨' };
    }
    if (percentage >= 90) {
        return { class: 'bg-orange-100 text-orange-800', text: 'Warnung', icon: '⚠️' };
    }
    return { class: 'bg-yellow-100 text-yellow-800', text: 'Achtung', icon: '⚡' };
};

const getMetricLabel = (metric) => {
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

const getMetricUnit = (metric) => {
    const units = {
        'storage_gb': 'GB',
        'api_calls_per_hour': '/h',
    };
    return units[metric] || '';
};

const formatNumber = (number) => {
    return new Intl.NumberFormat('de-DE').format(number);
};

const exportData = () => {
    // This would trigger a download via backend
    window.location.href = route('admin.usage.export');
};
</script>

<template>
    <AppLayout title="Usage Statistics">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Usage Statistics
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Übersicht über Tenants die ihre Limits erreichen
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <Link
                        :href="route('admin.dashboard')"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50"
                    >
                        ← Dashboard
                    </Link>
                    <button
                        @click="exportData"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700"
                    >
                        📥 Export CSV
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                    <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-red-500">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <span class="text-3xl">🚨</span>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Kritisch (≥100%)
                                        </dt>
                                        <dd class="text-2xl font-semibold text-red-600">
                                            {{ criticalTenants.length }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-orange-500">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <span class="text-3xl">⚠️</span>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Warnung (90-99%)
                                        </dt>
                                        <dd class="text-2xl font-semibold text-orange-600">
                                            {{ warningTenants.length }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-yellow-500">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <span class="text-3xl">⚡</span>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Achtung (80-89%)
                                        </dt>
                                        <dd class="text-2xl font-semibold text-yellow-600">
                                            {{ cautionTenants.length }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Critical Tenants -->
                <div v-if="criticalTenants.length > 0" class="bg-white shadow rounded-lg overflow-hidden border-l-4 border-red-500">
                    <div class="px-6 py-5 border-b border-gray-200 bg-red-50">
                        <div class="flex items-center">
                            <span class="text-2xl mr-3">🚨</span>
                            <div>
                                <h3 class="text-lg font-semibold text-red-900">Kritische Limits erreicht</h3>
                                <p class="mt-1 text-sm text-red-700">{{ criticalTenants.length }} Tenant(s) haben ihre Limits erreicht oder überschritten</p>
                            </div>
                        </div>
                    </div>
                    <div class="divide-y divide-gray-200">
                        <div
                            v-for="item in criticalTenants"
                            :key="`${item.tenant_id}-${item.metric}`"
                            class="px-6 py-4 hover:bg-gray-50"
                        >
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex-1">
                                    <Link
                                        :href="route('admin.tenants.show', item.tenant_id)"
                                        class="text-base font-semibold text-gray-900 hover:text-indigo-600"
                                    >
                                        {{ item.tenant }}
                                    </Link>
                                    <div class="mt-1 flex items-center space-x-2">
                                        <span class="text-sm text-gray-500">{{ getMetricLabel(item.metric) }}</span>
                                        <span :class="[getSeverityBadge(item.percentage).class, 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium']">
                                            {{ getSeverityBadge(item.percentage).icon }} {{ getSeverityBadge(item.percentage).text }}
                                        </span>
                                    </div>
                                </div>
                                <div class="text-right ml-4">
                                    <div class="text-2xl font-bold text-red-600">{{ item.percentage }}%</div>
                                    <div class="text-xs text-gray-500">
                                        {{ formatNumber(item.current) }} / {{ formatNumber(item.limit) }} {{ getMetricUnit(item.metric) }}
                                    </div>
                                </div>
                            </div>
                            <UsageLimitProgress
                                :metric="item.metric"
                                :current="item.current"
                                :limit="item.limit"
                                :label="getMetricLabel(item.metric)"
                                :unit="getMetricUnit(item.metric)"
                                :show-percentage="false"
                                size="sm"
                            />
                        </div>
                    </div>
                </div>

                <!-- Warning Tenants -->
                <div v-if="warningTenants.length > 0" class="bg-white shadow rounded-lg overflow-hidden border-l-4 border-orange-500">
                    <div class="px-6 py-5 border-b border-gray-200 bg-orange-50">
                        <div class="flex items-center">
                            <span class="text-2xl mr-3">⚠️</span>
                            <div>
                                <h3 class="text-lg font-semibold text-orange-900">Warnung</h3>
                                <p class="mt-1 text-sm text-orange-700">{{ warningTenants.length }} Tenant(s) nähern sich ihren Limits (90-99%)</p>
                            </div>
                        </div>
                    </div>
                    <div class="divide-y divide-gray-200">
                        <div
                            v-for="item in warningTenants"
                            :key="`${item.tenant_id}-${item.metric}`"
                            class="px-6 py-4 hover:bg-gray-50"
                        >
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex-1">
                                    <Link
                                        :href="route('admin.tenants.show', item.tenant_id)"
                                        class="text-base font-semibold text-gray-900 hover:text-indigo-600"
                                    >
                                        {{ item.tenant }}
                                    </Link>
                                    <div class="mt-1 flex items-center space-x-2">
                                        <span class="text-sm text-gray-500">{{ getMetricLabel(item.metric) }}</span>
                                        <span :class="[getSeverityBadge(item.percentage).class, 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium']">
                                            {{ getSeverityBadge(item.percentage).icon }} {{ getSeverityBadge(item.percentage).text }}
                                        </span>
                                    </div>
                                </div>
                                <div class="text-right ml-4">
                                    <div class="text-2xl font-bold text-orange-600">{{ item.percentage }}%</div>
                                    <div class="text-xs text-gray-500">
                                        {{ formatNumber(item.current) }} / {{ formatNumber(item.limit) }} {{ getMetricUnit(item.metric) }}
                                    </div>
                                </div>
                            </div>
                            <UsageLimitProgress
                                :metric="item.metric"
                                :current="item.current"
                                :limit="item.limit"
                                :label="getMetricLabel(item.metric)"
                                :unit="getMetricUnit(item.metric)"
                                :show-percentage="false"
                                size="sm"
                            />
                        </div>
                    </div>
                </div>

                <!-- Caution Tenants -->
                <div v-if="cautionTenants.length > 0" class="bg-white shadow rounded-lg overflow-hidden border-l-4 border-yellow-500">
                    <div class="px-6 py-5 border-b border-gray-200 bg-yellow-50">
                        <div class="flex items-center">
                            <span class="text-2xl mr-3">⚡</span>
                            <div>
                                <h3 class="text-lg font-semibold text-yellow-900">Achtung</h3>
                                <p class="mt-1 text-sm text-yellow-700">{{ cautionTenants.length }} Tenant(s) erreichen bald ihre Limits (80-89%)</p>
                            </div>
                        </div>
                    </div>
                    <div class="divide-y divide-gray-200">
                        <div
                            v-for="item in cautionTenants"
                            :key="`${item.tenant_id}-${item.metric}`"
                            class="px-6 py-4 hover:bg-gray-50"
                        >
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex-1">
                                    <Link
                                        :href="route('admin.tenants.show', item.tenant_id)"
                                        class="text-base font-semibold text-gray-900 hover:text-indigo-600"
                                    >
                                        {{ item.tenant }}
                                    </Link>
                                    <div class="mt-1 flex items-center space-x-2">
                                        <span class="text-sm text-gray-500">{{ getMetricLabel(item.metric) }}</span>
                                        <span :class="[getSeverityBadge(item.percentage).class, 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium']">
                                            {{ getSeverityBadge(item.percentage).icon }} {{ getSeverityBadge(item.percentage).text }}
                                        </span>
                                    </div>
                                </div>
                                <div class="text-right ml-4">
                                    <div class="text-2xl font-bold text-yellow-600">{{ item.percentage }}%</div>
                                    <div class="text-xs text-gray-500">
                                        {{ formatNumber(item.current) }} / {{ formatNumber(item.limit) }} {{ getMetricUnit(item.metric) }}
                                    </div>
                                </div>
                            </div>
                            <UsageLimitProgress
                                :metric="item.metric"
                                :current="item.current"
                                :limit="item.limit"
                                :label="getMetricLabel(item.metric)"
                                :unit="getMetricUnit(item.metric)"
                                :show-percentage="false"
                                size="sm"
                            />
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="approaching_limits.length === 0" class="bg-white shadow rounded-lg">
                    <div class="text-center py-12 px-6">
                        <svg class="mx-auto h-16 w-16 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">Alles im grünen Bereich!</h3>
                        <p class="mt-2 text-sm text-gray-500">
                            Aktuell gibt es keine Tenants, die ihre Limits erreichen oder überschreiten.
                        </p>
                        <p class="mt-1 text-xs text-gray-400">
                            Tenants werden hier angezeigt, wenn sie 80% oder mehr ihrer Limits erreichen.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
