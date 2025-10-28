<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InvoiceCard from '@/Components/Club/Billing/InvoiceCard.vue';
import UpcomingInvoicePreview from '@/Components/Club/Billing/UpcomingInvoicePreview.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useTranslations } from '@/composables/useTranslations';

const { trans } = useTranslations();

const props = defineProps({
    club: {
        type: Object,
        required: true,
    },
});

// State
const invoices = ref([]);
const upcomingInvoice = ref(null);
const loading = ref(false);
const loadingUpcoming = ref(false);
const error = ref(null);
const statusFilter = ref('all');
const hasMore = ref(false);
const startingAfter = ref(null);

// Computed
const filteredInvoices = computed(() => {
    if (statusFilter.value === 'all') return invoices.value;
    return invoices.value.filter(invoice => invoice.status === statusFilter.value);
});

const statusOptions = computed(() => [
    { value: 'all', label: trans('billing.status.all') },
    { value: 'paid', label: trans('billing.status.paid') },
    { value: 'open', label: trans('billing.status.open') },
    { value: 'draft', label: trans('billing.status.draft') },
    { value: 'uncollectible', label: trans('billing.status.uncollectible') },
    { value: 'void', label: trans('billing.status.void') },
]);

// Methods
const fetchInvoices = async (append = false) => {
    if (loading.value) return;

    loading.value = true;
    error.value = null;

    try {
        const params = {
            limit: 10,
        };

        if (startingAfter.value && append) {
            params.starting_after = startingAfter.value;
        }

        if (statusFilter.value !== 'all') {
            params.status = statusFilter.value;
        }

        const response = await axios.get(
            route('club.billing.invoices.index', { club: props.club.id }),
            { params }
        );

        if (append) {
            invoices.value = [...invoices.value, ...response.data.invoices.data];
        } else {
            invoices.value = response.data.invoices.data;
        }

        hasMore.value = response.data.invoices.has_more;
        if (response.data.invoices.data.length > 0) {
            startingAfter.value = response.data.invoices.data[response.data.invoices.data.length - 1].id;
        }
    } catch (err) {
        console.error('Failed to fetch invoices:', err);
        error.value = err.response?.data?.error || trans('billing.messages.invoices_error');
    } finally {
        loading.value = false;
    }
};

const fetchUpcomingInvoice = async () => {
    if (loadingUpcoming.value) return;

    loadingUpcoming.value = true;

    try {
        const response = await axios.get(
            route('club.billing.invoices.upcoming', { club: props.club.id })
        );

        if (response.data.invoice) {
            upcomingInvoice.value = response.data.invoice;
        }
    } catch (err) {
        console.error('Failed to fetch upcoming invoice:', err);
        // Don't show error if no upcoming invoice (expected for some scenarios)
        if (err.response?.status !== 404) {
            console.error('Unexpected error:', err);
        }
    } finally {
        loadingUpcoming.value = false;
    }
};

const downloadPdf = async (invoice) => {
    try {
        const url = route('club.billing.invoices.pdf', {
            club: props.club.id,
            invoice: invoice.id,
        });

        // Open in new tab (Stripe will redirect to PDF)
        window.open(url, '_blank');
    } catch (err) {
        console.error('Failed to download PDF:', err);
        alert('Fehler beim Herunterladen der PDF');
    }
};

const viewDetails = (invoice) => {
    // For now, just download PDF
    // In the future, could open a modal with more details
    downloadPdf(invoice);
};

const loadMore = () => {
    fetchInvoices(true);
};

const onFilterChange = () => {
    startingAfter.value = null;
    fetchInvoices(false);
};

// Lifecycle
onMounted(() => {
    fetchInvoices();
    fetchUpcomingInvoice();
});
</script>

<template>
    <AppLayout :title="trans('billing.invoices.title')">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ trans('billing.invoices.title') }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                <!-- Upcoming Invoice -->
                <UpcomingInvoicePreview
                    v-if="upcomingInvoice"
                    :invoice="upcomingInvoice"
                    :loading="loadingUpcoming"
                />

                <!-- Invoices List -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <!-- Header -->
                    <div class="px-6 py-5 border-b border-gray-200">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">
                                    {{ trans('billing.invoices.history') }}
                                </h3>
                                <p class="text-sm text-gray-600 mt-1">
                                    {{ trans('billing.invoices.all_for_club', { club: club.name }) }}
                                </p>
                            </div>

                            <!-- Filter -->
                            <div class="flex items-center space-x-2">
                                <label for="status-filter" class="text-sm text-gray-700 font-medium">
                                    {{ trans('billing.labels.status') }}:
                                </label>
                                <select
                                    id="status-filter"
                                    v-model="statusFilter"
                                    @change="onFilterChange"
                                    class="rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option
                                        v-for="option in statusOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Invoices Content -->
                    <div class="p-6">
                        <!-- Loading State -->
                        <div v-if="loading && invoices.length === 0" class="flex items-center justify-center py-12">
                            <svg class="animate-spin h-10 w-10 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>

                        <!-- Error State -->
                        <div v-else-if="error" class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-red-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                {{ trans('billing.messages.load_error') }}
                            </h3>
                            <p class="text-gray-600 mb-4">
                                {{ error }}
                            </p>
                            <SecondaryButton @click="fetchInvoices(false)">
                                {{ trans('billing.actions.retry') }}
                            </SecondaryButton>
                        </div>

                        <!-- Empty State -->
                        <div v-else-if="filteredInvoices.length === 0" class="text-center py-12">
                            <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                {{ trans('billing.invoices.no_invoices') }}
                            </h3>
                            <p class="text-gray-600">
                                {{ statusFilter === 'all'
                                    ? trans('billing.invoices.no_invoices_created')
                                    : trans('billing.invoices.no_invoices_status', { status: statusOptions.value.find(o => o.value === statusFilter)?.label })
                                }}
                            </p>
                        </div>

                        <!-- Invoices Grid -->
                        <div v-else class="space-y-4">
                            <InvoiceCard
                                v-for="invoice in filteredInvoices"
                                :key="invoice.id"
                                :invoice="invoice"
                                @view-details="viewDetails"
                                @download-pdf="downloadPdf"
                            />

                            <!-- Load More Button -->
                            <div v-if="hasMore" class="flex justify-center pt-4">
                                <SecondaryButton
                                    @click="loadMore"
                                    :disabled="loading"
                                    class="flex items-center"
                                >
                                    <svg
                                        v-if="loading"
                                        class="animate-spin -ml-1 mr-3 h-5 w-5 text-gray-700"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                    >
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ trans('billing.actions.load_more') }}
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info Box -->
                <div class="rounded-lg border-2 border-blue-200 bg-blue-50 p-4">
                    <div class="flex items-start">
                        <svg class="h-6 w-6 text-blue-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        <div class="ml-3">
                            <h4 class="text-sm font-semibold text-blue-900">
                                {{ trans('billing.info.important') }}
                            </h4>
                            <ul class="mt-2 text-sm text-blue-800 space-y-1 list-disc list-inside">
                                <li>{{ trans('billing.info.auto_email') }}</li>
                                <li>{{ trans('billing.info.pdf_download') }}</li>
                                <li>{{ trans('billing.info.support') }}</li>
                                <li>{{ trans('billing.info.auto_charge') }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
