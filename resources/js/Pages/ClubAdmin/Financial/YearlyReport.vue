<script setup>
import { Link, router } from '@inertiajs/vue3';
import ClubAdminLayout from '@/Layouts/ClubAdminLayout.vue';

const props = defineProps({
    club: Object,
    year: Number,
    yearly_summary: Object,
    monthly_report: Array,
    available_years: Array,
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount || 0);
};

const monthNames = [
    'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
    'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'
];

const changeYear = (newYear) => {
    router.get(route('club-admin.financial.yearly-report'), { year: newYear });
};
</script>

<template>
    <ClubAdminLayout title="Jahresbericht">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Jahresbericht {{ year }}
                </h2>
                <div class="flex items-center gap-4">
                    <select
                        :value="year"
                        @change="changeYear($event.target.value)"
                        class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option v-for="y in available_years" :key="y" :value="y">{{ y }}</option>
                    </select>
                    <Link
                        :href="route('club-admin.financial.index')"
                        class="text-sm text-blue-600 hover:text-blue-800"
                    >
                        ← Zurück
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Yearly Summary -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                    <div class="bg-white overflow-hidden shadow-lg rounded-lg">
                        <div class="p-5">
                            <dt class="text-sm font-medium text-gray-500">Gesamteinnahmen {{ year }}</dt>
                            <dd class="mt-1 text-2xl font-semibold text-green-600">
                                {{ formatCurrency(yearly_summary?.total_income) }}
                            </dd>
                        </div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-lg rounded-lg">
                        <div class="p-5">
                            <dt class="text-sm font-medium text-gray-500">Gesamtausgaben {{ year }}</dt>
                            <dd class="mt-1 text-2xl font-semibold text-red-600">
                                {{ formatCurrency(yearly_summary?.total_expenses) }}
                            </dd>
                        </div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-lg rounded-lg">
                        <div class="p-5">
                            <dt class="text-sm font-medium text-gray-500">Jahresbilanz {{ year }}</dt>
                            <dd
                                class="mt-1 text-2xl font-semibold"
                                :class="yearly_summary?.balance >= 0 ? 'text-green-600' : 'text-red-600'"
                            >
                                {{ formatCurrency(yearly_summary?.balance) }}
                            </dd>
                        </div>
                    </div>
                </div>

                <!-- Monthly Breakdown -->
                <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Monatliche Übersicht</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Monat
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Einnahmen
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Ausgaben
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Bilanz
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="month in monthly_report" :key="month.month">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ monthNames[month.month - 1] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600">
                                        {{ formatCurrency(month.income) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600">
                                        {{ formatCurrency(month.expenses) }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium"
                                        :class="month.balance >= 0 ? 'text-green-600' : 'text-red-600'"
                                    >
                                        {{ formatCurrency(month.balance) }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                        Gesamt
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-green-600">
                                        {{ formatCurrency(yearly_summary?.total_income) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-red-600">
                                        {{ formatCurrency(yearly_summary?.total_expenses) }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold"
                                        :class="yearly_summary?.balance >= 0 ? 'text-green-600' : 'text-red-600'"
                                    >
                                        {{ formatCurrency(yearly_summary?.balance) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </ClubAdminLayout>
</template>
