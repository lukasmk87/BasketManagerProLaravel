<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    requests: Object,
    pendingCount: Number,
    currentStatus: String,
    currentType: { type: String, default: '' },
    statuses: Object,
    requestableTypes: Object,
});

const showApproveModal = ref(false);
const showRejectModal = ref(false);
const selectedRequest = ref(null);

const approveForm = useForm({
    admin_notes: '',
});

const rejectForm = useForm({
    rejection_reason: '',
});

const changeStatus = (status) => {
    router.get(route('admin.invoice-requests.index'), {
        status: status,
        type: props.currentType,
    }, {
        preserveState: true,
    });
};

const changeType = (type) => {
    router.get(route('admin.invoice-requests.index'), {
        status: props.currentStatus,
        type: type,
    }, {
        preserveState: true,
    });
};

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
        pending: 'bg-yellow-100 text-yellow-800',
        approved: 'bg-green-100 text-green-800',
        rejected: 'bg-red-100 text-red-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const getStatusLabel = (status) => {
    const labels = {
        pending: 'Ausstehend',
        approved: 'Genehmigt',
        rejected: 'Abgelehnt',
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

const openApproveModal = (request) => {
    selectedRequest.value = request;
    showApproveModal.value = true;
};

const openRejectModal = (request) => {
    selectedRequest.value = request;
    showRejectModal.value = true;
};

const approveRequest = () => {
    approveForm.post(route('admin.invoice-requests.approve', selectedRequest.value.id), {
        onSuccess: () => {
            showApproveModal.value = false;
            approveForm.reset();
            selectedRequest.value = null;
        },
    });
};

const rejectRequest = () => {
    rejectForm.post(route('admin.invoice-requests.reject', selectedRequest.value.id), {
        onSuccess: () => {
            showRejectModal.value = false;
            rejectForm.reset();
            selectedRequest.value = null;
        },
    });
};
</script>

<template>
    <AdminLayout title="Rechnungsanfragen">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Rechnungsanfragen
                        <span v-if="pendingCount > 0" class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            {{ pendingCount }} ausstehend
                        </span>
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Anfragen zur Zahlung per Rechnung
                    </p>
                </div>
                <Link
                    :href="route('admin.invoices.index')"
                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50"
                >
                    Zu Rechnungen
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Filters -->
                <div class="mb-6 space-y-4">
                    <!-- Type Filter -->
                    <div class="flex items-center space-x-4">
                        <span class="text-sm font-medium text-gray-700">Typ:</span>
                        <div class="flex space-x-2">
                            <button
                                @click="changeType('')"
                                :class="[
                                    'px-3 py-1.5 rounded-md text-sm font-medium',
                                    !currentType
                                        ? 'bg-indigo-600 text-white'
                                        : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300',
                                ]"
                            >
                                Alle
                            </button>
                            <button
                                v-for="(label, value) in requestableTypes"
                                :key="value"
                                @click="changeType(value)"
                                :class="[
                                    'px-3 py-1.5 rounded-md text-sm font-medium',
                                    currentType === value
                                        ? 'bg-indigo-600 text-white'
                                        : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300',
                                ]"
                            >
                                {{ label }}
                            </button>
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div class="flex items-center space-x-4">
                        <span class="text-sm font-medium text-gray-700">Status:</span>
                        <div class="flex space-x-2">
                            <button
                                v-for="(label, value) in { all: 'Alle', ...statuses }"
                                :key="value"
                                @click="changeStatus(value)"
                                :class="[
                                    'px-3 py-1.5 rounded-md text-sm font-medium',
                                    currentStatus === value
                                        ? 'bg-indigo-600 text-white'
                                        : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300',
                                ]"
                            >
                                {{ label }}
                                <span v-if="value === 'pending' && pendingCount > 0" class="ml-1">
                                    ({{ pendingCount }})
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Requests Table -->
                <div v-if="requests.data.length > 0" class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Antragsteller
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Plan
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Intervall
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Rechnungsempfänger
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Datum
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
                            <tr v-for="request in requests.data" :key="request.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <span :class="[getTypeBadgeClass(request.requestable_type), 'px-2 py-0.5 text-xs font-medium rounded-full mr-2']">
                                            {{ getTypeLabel(request.requestable_type) }}
                                        </span>
                                        <span class="text-sm font-medium text-gray-900">{{ request.requestable?.name || '-' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ request.subscription_plan?.name || '-' }}</div>
                                    <div class="text-sm text-gray-500">{{ formatCurrency(request.subscription_plan?.price || 0) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ request.billing_interval === 'yearly' ? 'Jährlich' : 'Monatlich' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ request.billing_name }}</div>
                                    <div class="text-sm text-gray-500">{{ request.billing_email }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ formatDate(request.created_at) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="[getStatusBadgeClass(request.status), 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full']">
                                        {{ getStatusLabel(request.status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <template v-if="request.status === 'pending'">
                                        <button
                                            @click="openApproveModal(request)"
                                            class="text-green-600 hover:text-green-900 mr-3"
                                        >
                                            Genehmigen
                                        </button>
                                        <button
                                            @click="openRejectModal(request)"
                                            class="text-red-600 hover:text-red-900"
                                        >
                                            Ablehnen
                                        </button>
                                    </template>
                                    <template v-else-if="request.status === 'approved' && request.invoice_id">
                                        <Link
                                            :href="route('admin.invoices.show', request.invoice_id)"
                                            class="text-indigo-600 hover:text-indigo-900"
                                        >
                                            Zur Rechnung
                                        </Link>
                                    </template>
                                    <span v-else class="text-gray-400">-</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div v-if="requests.links && requests.links.length > 3" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                        <nav class="flex items-center justify-between">
                            <div class="flex-1 flex justify-between sm:justify-end space-x-2">
                                <Link
                                    v-for="link in requests.links"
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
                        <h3 class="mt-4 text-lg font-medium text-gray-900">Keine Anfragen</h3>
                        <p class="mt-2 text-sm text-gray-500">
                            Es gibt derzeit keine Rechnungsanfragen mit diesem Status.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Approve Modal -->
        <div v-if="showApproveModal" class="fixed z-10 inset-0 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showApproveModal = false"></div>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form @submit.prevent="approveRequest">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Anfrage genehmigen</h3>
                            <p class="text-sm text-gray-500 mb-4">
                                Für
                                <span :class="[getTypeBadgeClass(selectedRequest?.requestable_type), 'px-2 py-0.5 text-xs font-medium rounded-full mx-1']">
                                    {{ getTypeLabel(selectedRequest?.requestable_type) }}
                                </span>
                                <strong>{{ selectedRequest?.requestable?.name }}</strong> wird eine Rechnung erstellt und die Zahlungsart auf Rechnung umgestellt.
                            </p>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Admin-Notizen (optional)</label>
                                <textarea
                                    v-model="approveForm.admin_notes"
                                    rows="3"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                ></textarea>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button
                                type="submit"
                                :disabled="approveForm.processing"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                Genehmigen & Rechnung erstellen
                            </button>
                            <button
                                type="button"
                                @click="showApproveModal = false"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm"
                            >
                                Abbrechen
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Reject Modal -->
        <div v-if="showRejectModal" class="fixed z-10 inset-0 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showRejectModal = false"></div>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form @submit.prevent="rejectRequest">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Anfrage ablehnen</h3>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Ablehnungsgrund *</label>
                                <textarea
                                    v-model="rejectForm.rejection_reason"
                                    rows="3"
                                    required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    placeholder="Bitte geben Sie den Grund für die Ablehnung an..."
                                ></textarea>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button
                                type="submit"
                                :disabled="rejectForm.processing || !rejectForm.rejection_reason"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                Ablehnen
                            </button>
                            <button
                                type="button"
                                @click="showRejectModal = false"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm"
                            >
                                Abbrechen
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
