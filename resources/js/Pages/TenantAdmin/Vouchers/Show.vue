<script setup>
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    voucher: { type: Object, required: true },
    statistics: { type: Object, required: true },
    redemptions: { type: Array, default: () => [] },
});

const toggleActive = () => {
    if (props.voucher.is_system_wide) return;
    router.post(route('tenant-admin.vouchers.toggle-active', props.voucher.id), {}, {
        preserveScroll: true,
    });
};

const getTypeBadgeClass = (type) => {
    const classes = {
        percent: 'bg-blue-100 text-blue-800',
        fixed_amount: 'bg-green-100 text-green-800',
        trial_extension: 'bg-purple-100 text-purple-800',
    };
    return classes[type] || 'bg-gray-100 text-gray-800';
};

const getStatusBadgeClass = (color) => {
    const classes = {
        green: 'bg-green-100 text-green-800',
        red: 'bg-red-100 text-red-800',
        yellow: 'bg-yellow-100 text-yellow-800',
        orange: 'bg-orange-100 text-orange-800',
    };
    return classes[color] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <AppLayout title="Voucher Details">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <Link :href="route('tenant-admin.vouchers.index')" class="text-gray-500 hover:text-gray-700 mr-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Voucher: {{ voucher.code }}
                    </h2>
                    <span v-if="voucher.is_system_wide" class="ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                        System-weit
                    </span>
                </div>
                <div v-if="voucher.can_edit" class="flex items-center space-x-3">
                    <button
                        @click="toggleActive"
                        :class="voucher.is_active ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700'"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        {{ voucher.is_active ? 'Deaktivieren' : 'Aktivieren' }}
                    </button>
                    <Link
                        :href="route('tenant-admin.vouchers.edit', voucher.id)"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        Bearbeiten
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                <!-- System-wide Notice -->
                <div v-if="voucher.is_system_wide" class="bg-indigo-50 border border-indigo-200 rounded-md p-4">
                    <div class="flex">
                        <svg class="h-5 w-5 text-indigo-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="text-sm text-indigo-700">
                            Dies ist ein system-weiter Voucher, der vom Administrator erstellt wurde.
                            Sie können diesen Voucher nicht bearbeiten.
                        </div>
                    </div>
                </div>

                <!-- Voucher Details -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">{{ voucher.name }}</h3>
                                <p v-if="voucher.description" class="mt-1 text-sm text-gray-500">{{ voucher.description }}</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span :class="[getTypeBadgeClass(voucher.type), 'px-3 py-1 text-sm font-semibold rounded-full']">
                                    {{ voucher.type_label }}
                                </span>
                                <span :class="[getStatusBadgeClass(voucher.status_color), 'px-3 py-1 text-sm font-semibold rounded-full']">
                                    {{ voucher.status_label }}
                                </span>
                            </div>
                        </div>

                        <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Code</dt>
                                <dd class="mt-1 text-lg font-mono font-semibold text-gray-900">{{ voucher.code }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Rabatt</dt>
                                <dd class="mt-1 text-lg font-semibold text-green-600">{{ voucher.discount_label }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Dauer</dt>
                                <dd class="mt-1 text-lg font-semibold text-gray-900">{{ voucher.duration_label }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Einlösungen</dt>
                                <dd class="mt-1 text-lg font-semibold text-gray-900">
                                    {{ voucher.current_redemptions }}
                                    <span v-if="voucher.max_redemptions">/ {{ voucher.max_redemptions }}</span>
                                    <span v-else class="text-gray-400">/ unbegrenzt</span>
                                </dd>
                            </div>
                        </div>

                        <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-3">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Gültigkeitszeitraum</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    <span v-if="voucher.valid_from || voucher.valid_until">
                                        {{ voucher.valid_from || 'Jetzt' }} - {{ voucher.valid_until || 'Unbegrenzt' }}
                                    </span>
                                    <span v-else class="text-gray-500">Unbegrenzt</span>
                                </dd>
                            </div>
                            <div v-if="voucher.applicable_plans && voucher.applicable_plans.length > 0">
                                <dt class="text-sm font-medium text-gray-500">Gilt für Pläne</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    <span v-for="(plan, index) in voucher.applicable_plans" :key="plan.id">
                                        {{ plan.name }}<span v-if="index < voucher.applicable_plans.length - 1">, </span>
                                    </span>
                                </dd>
                            </div>
                            <div v-else>
                                <dt class="text-sm font-medium text-gray-500">Gilt für Pläne</dt>
                                <dd class="mt-1 text-sm text-gray-500">Alle Pläne</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Erstellt</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    {{ voucher.created_at }}
                                    <span v-if="voucher.creator_name" class="text-gray-500">von {{ voucher.creator_name }}</span>
                                </dd>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Statistiken</h3>
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                            <div class="bg-gray-50 rounded-lg p-4">
                                <dt class="text-sm font-medium text-gray-500">Einlösungen gesamt</dt>
                                <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ statistics.total_redemptions }}</dd>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <dt class="text-sm font-medium text-gray-500">Aktive Rabatte</dt>
                                <dd class="mt-1 text-2xl font-semibold text-green-600">{{ statistics.active_redemptions }}</dd>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <dt class="text-sm font-medium text-gray-500">Abgeschlossen</dt>
                                <dd class="mt-1 text-2xl font-semibold text-gray-600">{{ statistics.completed_redemptions }}</dd>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <dt class="text-sm font-medium text-gray-500">Rabatt gegeben</dt>
                                <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ statistics.total_discount_given }} EUR</dd>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Redemptions Table -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Einlösungen</h3>

                        <div v-if="redemptions.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Club</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Eingelöst von</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Datum</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fortschritt</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ersparnis</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="redemption in redemptions" :key="redemption.id">
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ redemption.club_name }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                            {{ redemption.redeemed_by || '-' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                            {{ redemption.redeemed_at }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                            {{ redemption.months_applied }} / {{ redemption.duration_months }} Monate
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-green-600 font-medium">
                                            {{ redemption.total_discount }} EUR
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span
                                                :class="redemption.is_fully_applied ? 'bg-gray-100 text-gray-800' : 'bg-green-100 text-green-800'"
                                                class="px-2 py-1 text-xs font-semibold rounded-full"
                                            >
                                                {{ redemption.is_fully_applied ? 'Abgeschlossen' : 'Aktiv' }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="text-center py-8 text-gray-500">
                            Noch keine Einlösungen vorhanden.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
