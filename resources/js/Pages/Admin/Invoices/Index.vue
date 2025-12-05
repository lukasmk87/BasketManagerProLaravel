<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    invoices: Object,
    statistics: Object,
    filters: Object,
    statuses: Object,
    paymentMethods: Object,
    invoiceableTypes: Object,
});

const localFilters = ref({
    status: props.filters?.status || '',
    type: props.filters?.type || '',
    payment_method: props.filters?.payment_method || '',
    search: props.filters?.search || '',
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
});

const applyFilters = () => {
    router.get(route('admin.invoices.index'), {
        status: localFilters.value.status || undefined,
        type: localFilters.value.type || undefined,
        payment_method: localFilters.value.payment_method || undefined,
        search: localFilters.value.search || undefined,
        start_date: localFilters.value.start_date || undefined,
        end_date: localFilters.value.end_date || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetFilters = () => {
    localFilters.value = {
        status: '',
        type: '',
        payment_method: '',
        search: '',
        start_date: '',
        end_date: '',
    };
    router.get(route('admin.invoices.index'));
};

watch(() => localFilters.value.status, applyFilters);
watch(() => localFilters.value.type, applyFilters);
watch(() => localFilters.value.payment_method, applyFilters);

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount);
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('de-DE');
};

const getStatusBadgeClass = (status) => {
    const classes = {
        draft: 'bg-gray-100 text-gray-800',
        sent: 'bg-blue-100 text-blue-800',
        paid: 'bg-green-100 text-green-800',
        overdue: 'bg-red-100 text-red-800',
        cancelled: 'bg-gray-100 text-gray-600',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const getStatusLabel = (status) => {
    const labels = {
        draft: 'Entwurf',
        sent: 'Versendet',
        paid: 'Bezahlt',
        overdue: 'Überfällig',
        cancelled: 'Storniert',
    };
    return labels[status] || status;
};

const getTypeBadgeClass = (type) => {
    return type?.includes('Club')
        ? 'bg-blue-100 text-blue-800'
        : 'bg-purple-100 text-purple-800';
};

const getTypeLabel = (type) => {
    return type?.includes('Club') ? 'Club' : 'Tenant';
};

const getPaymentMethodBadgeClass = (method) => {
    return method === 'stripe'
        ? 'bg-indigo-100 text-indigo-800'
        : 'bg-gray-100 text-gray-800';
};

const getPaymentMethodLabel = (method) => {
    return method === 'stripe' ? 'Stripe' : 'Bank';
};
</script>

<template>
    <AdminLayout title="Rechnungsverwaltung">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Rechnungsverwaltung
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Verwalte Rechnungen für Clubs und Tenants
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <Link
                        :href="route('admin.invoice-requests.index')"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50"
                    >
                        Anfragen
                    </Link>
                    <Link
                        :href="route('admin.invoices.create')"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700"
                    >
                        + Neue Rechnung
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-4 mb-8">
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-indigo-100 rounded-md p-3">
                                    <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Gesamt</dt>
                                        <dd class="text-2xl font-semibold text-gray-900">{{ statistics.total }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-yellow-100 rounded-md p-3">
                                    <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Offen</dt>
                                        <dd class="text-2xl font-semibold text-gray-900">{{ statistics.sent }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-red-100 rounded-md p-3">
                                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Überfällig</dt>
                                        <dd class="text-2xl font-semibold text-gray-900">{{ statistics.overdue }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
                                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Diesen Monat</dt>
                                        <dd class="text-2xl font-semibold text-gray-900">{{ formatCurrency(statistics.paid_this_month) }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white shadow rounded-lg mb-6 p-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Suche</label>
                            <input
                                v-model="localFilters.search"
                                type="text"
                                placeholder="Rechnungsnr., Name..."
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                @keyup.enter="applyFilters"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Typ</label>
                            <select
                                v-model="localFilters.type"
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            >
                                <option value="">Alle</option>
                                <option value="club">Clubs</option>
                                <option value="tenant">Tenants</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select
                                v-model="localFilters.status"
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            >
                                <option value="">Alle</option>
                                <option v-for="(label, value) in statuses" :key="value" :value="value">
                                    {{ label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Zahlung</label>
                            <select
                                v-model="localFilters.payment_method"
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            >
                                <option value="">Alle</option>
                                <option v-for="(label, value) in paymentMethods" :key="value" :value="value">
                                    {{ label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Von</label>
                            <input
                                v-model="localFilters.start_date"
                                type="date"
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Bis</label>
                            <input
                                v-model="localFilters.end_date"
                                type="date"
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            />
                        </div>
                        <div class="flex items-end space-x-2">
                            <button
                                @click="applyFilters"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase hover:bg-indigo-700"
                            >
                                Suchen
                            </button>
                            <button
                                @click="resetFilters"
                                class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase hover:bg-gray-50"
                            >
                                Reset
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Invoices Table -->
                <div v-if="invoices.data.length > 0" class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Rechnungsnr.
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Empfänger
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Betrag
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Zahlung
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Fällig
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="relative px-6 py-3">
                                    <span class="sr-only">Aktionen</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="invoice in invoices.data" :key="invoice.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ invoice.invoice_number }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ formatDate(invoice.issue_date) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <span :class="[getTypeBadgeClass(invoice.invoiceable_type), 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full mr-2']">
                                            {{ getTypeLabel(invoice.invoiceable_type) }}
                                        </span>
                                    </div>
                                    <div class="text-sm text-gray-900 mt-1">{{ invoice.invoiceable?.name || invoice.billing_name }}</div>
                                    <div class="text-xs text-gray-500">{{ invoice.billing_email }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ formatCurrency(invoice.gross_amount) }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        ({{ formatCurrency(invoice.net_amount) }} netto)
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="[getPaymentMethodBadgeClass(invoice.payment_method), 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full']">
                                        {{ getPaymentMethodLabel(invoice.payment_method) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ formatDate(invoice.due_date) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="[getStatusBadgeClass(invoice.status), 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full']">
                                        {{ getStatusLabel(invoice.status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <Link
                                        :href="route('admin.invoices.show', invoice.id)"
                                        class="text-indigo-600 hover:text-indigo-900 mr-3"
                                    >
                                        Details
                                    </Link>
                                    <a
                                        :href="route('admin.invoices.pdf', invoice.id)"
                                        target="_blank"
                                        class="text-gray-600 hover:text-gray-900"
                                    >
                                        PDF
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div v-if="invoices.links && invoices.links.length > 3" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                        <nav class="flex items-center justify-between">
                            <div class="hidden sm:block">
                                <p class="text-sm text-gray-700">
                                    Zeige <span class="font-medium">{{ invoices.from }}</span> bis
                                    <span class="font-medium">{{ invoices.to }}</span> von
                                    <span class="font-medium">{{ invoices.total }}</span> Ergebnissen
                                </p>
                            </div>
                            <div class="flex-1 flex justify-between sm:justify-end space-x-2">
                                <Link
                                    v-for="link in invoices.links"
                                    :key="link.label"
                                    :href="link.url || '#'"
                                    :class="[
                                        'relative inline-flex items-center px-4 py-2 border text-sm font-medium rounded-md',
                                        link.active
                                            ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                                            : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                        !link.url ? 'opacity-50 cursor-not-allowed' : '',
                                    ]"
                                    v-html="link.label"
                                />
                            </div>
                        </nav>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="bg-white shadow rounded-lg">
                    <div class="text-center py-12 px-6">
                        <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">Keine Rechnungen vorhanden</h3>
                        <p class="mt-2 text-sm text-gray-500">
                            Erstellen Sie Ihre erste Rechnung für einen Club.
                        </p>
                        <div class="mt-6">
                            <Link
                                :href="route('admin.invoices.create')"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700"
                            >
                                + Erste Rechnung erstellen
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
