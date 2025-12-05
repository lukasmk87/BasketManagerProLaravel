<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    invoice: Object,
    formatted: Object,
    canEdit: Boolean,
    canSend: Boolean,
    canMarkPaid: Boolean,
    canSendReminder: Boolean,
    canCancel: Boolean,
});

const showMarkPaidModal = ref(false);
const showCancelModal = ref(false);

const markPaidForm = useForm({
    paid_at: new Date().toISOString().split('T')[0],
    payment_reference: '',
    payment_notes: '',
});

const cancelForm = useForm({
    reason: '',
});

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
    return method === 'stripe' ? 'Stripe' : 'Banküberweisung';
};

const sendInvoice = () => {
    if (confirm('Rechnung jetzt an den Empfänger versenden?')) {
        router.post(route('admin.invoices.send', props.invoice.id));
    }
};

const sendReminder = () => {
    if (confirm('Zahlungserinnerung an den Empfänger senden?')) {
        router.post(route('admin.invoices.reminder', props.invoice.id));
    }
};

const markAsPaid = () => {
    markPaidForm.post(route('admin.invoices.mark-paid', props.invoice.id), {
        onSuccess: () => {
            showMarkPaidModal.value = false;
            markPaidForm.reset();
        },
    });
};

const cancelInvoice = () => {
    cancelForm.post(route('admin.invoices.cancel', props.invoice.id), {
        onSuccess: () => {
            showCancelModal.value = false;
            cancelForm.reset();
        },
    });
};
</script>

<template>
    <AdminLayout title="Rechnung Details">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Rechnung {{ invoice.invoice_number }}
                    </h2>
                    <p class="text-sm text-gray-600 mt-1 flex items-center space-x-2">
                        <span :class="[getTypeBadgeClass(invoice.invoiceable_type), 'px-2 py-0.5 text-xs font-medium rounded-full']">
                            {{ getTypeLabel(invoice.invoiceable_type) }}
                        </span>
                        <span>{{ invoice.invoiceable?.name || invoice.billing_name }}</span>
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <Link
                        :href="route('admin.invoices.index')"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50"
                    >
                        Zurück
                    </Link>
                    <a
                        :href="route('admin.invoices.pdf', invoice.id)"
                        target="_blank"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50"
                    >
                        PDF Download
                    </a>
                    <Link
                        v-if="canEdit"
                        :href="route('admin.invoices.edit', invoice.id)"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700"
                    >
                        Bearbeiten
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Invoice Details -->
                        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                            <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">
                                    Rechnungsdetails
                                </h3>
                                <span :class="[getStatusBadgeClass(invoice.status), 'px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full']">
                                    {{ getStatusLabel(invoice.status) }}
                                </span>
                            </div>
                            <div class="border-t border-gray-200">
                                <dl>
                                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                        <dt class="text-sm font-medium text-gray-500">Rechnungsnummer</dt>
                                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ invoice.invoice_number }}</dd>
                                    </div>
                                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                        <dt class="text-sm font-medium text-gray-500">Rechnungsdatum</dt>
                                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ formatDate(invoice.issue_date) }}</dd>
                                    </div>
                                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                        <dt class="text-sm font-medium text-gray-500">Fälligkeitsdatum</dt>
                                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ formatDate(invoice.due_date) }}</dd>
                                    </div>
                                    <div v-if="invoice.billing_period" class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                        <dt class="text-sm font-medium text-gray-500">Abrechnungszeitraum</dt>
                                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ invoice.billing_period }}</dd>
                                    </div>
                                    <div v-if="invoice.description" class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                        <dt class="text-sm font-medium text-gray-500">Beschreibung</dt>
                                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ invoice.description }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Billing Details -->
                        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                            <div class="px-4 py-5 sm:px-6">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Rechnungsempfänger</h3>
                            </div>
                            <div class="border-t border-gray-200">
                                <dl>
                                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                        <dt class="text-sm font-medium text-gray-500">Name</dt>
                                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ invoice.billing_name }}</dd>
                                    </div>
                                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                        <dt class="text-sm font-medium text-gray-500">E-Mail</dt>
                                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ invoice.billing_email }}</dd>
                                    </div>
                                    <div v-if="invoice.billing_address" class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                        <dt class="text-sm font-medium text-gray-500">Adresse</dt>
                                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                            <div v-if="invoice.billing_address.street">{{ invoice.billing_address.street }}</div>
                                            <div v-if="invoice.billing_address.zip || invoice.billing_address.city">
                                                {{ invoice.billing_address.zip }} {{ invoice.billing_address.city }}
                                            </div>
                                            <div v-if="invoice.billing_address.country">{{ invoice.billing_address.country }}</div>
                                        </dd>
                                    </div>
                                    <div v-if="invoice.vat_number" class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                        <dt class="text-sm font-medium text-gray-500">USt-IdNr.</dt>
                                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ invoice.vat_number }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Line Items -->
                        <div v-if="invoice.line_items && invoice.line_items.length > 0" class="bg-white shadow overflow-hidden sm:rounded-lg">
                            <div class="px-4 py-5 sm:px-6">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Rechnungsposten</h3>
                            </div>
                            <div class="border-t border-gray-200">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Beschreibung</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Menge</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Einzelpreis</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Gesamt</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="(item, index) in invoice.line_items" :key="index">
                                            <td class="px-6 py-4 text-sm text-gray-900">{{ item.description }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-900 text-right">{{ item.quantity }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-900 text-right">{{ formatCurrency(item.unit_price) }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-900 text-right">{{ formatCurrency(item.total) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Amount Summary -->
                        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                            <div class="px-4 py-5 sm:px-6">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Beträge</h3>
                            </div>
                            <div class="border-t border-gray-200 px-4 py-5">
                                <dl class="space-y-3">
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Nettobetrag</dt>
                                        <dd class="text-sm text-gray-900">{{ formatCurrency(invoice.net_amount) }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">MwSt. ({{ invoice.tax_rate }}%)</dt>
                                        <dd class="text-sm text-gray-900">{{ formatCurrency(invoice.tax_amount) }}</dd>
                                    </div>
                                    <div class="border-t pt-3 flex justify-between">
                                        <dt class="text-base font-medium text-gray-900">Bruttobetrag</dt>
                                        <dd class="text-base font-medium text-gray-900">{{ formatCurrency(invoice.gross_amount) }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Payment Method Info -->
                        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                            <div class="px-4 py-5 sm:px-6">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Zahlungsmethode</h3>
                            </div>
                            <div class="border-t border-gray-200 px-4 py-5">
                                <div class="flex items-center space-x-2 mb-3">
                                    <span :class="[getPaymentMethodBadgeClass(invoice.payment_method), 'px-2.5 py-1 text-sm font-medium rounded-full']">
                                        {{ getPaymentMethodLabel(invoice.payment_method) }}
                                    </span>
                                </div>
                                <dl class="space-y-2">
                                    <template v-if="invoice.payment_method === 'stripe'">
                                        <div v-if="invoice.stripe_invoice_id" class="flex justify-between">
                                            <dt class="text-sm text-gray-500">Stripe Invoice</dt>
                                            <dd class="text-sm text-gray-900 font-mono text-xs">{{ invoice.stripe_invoice_id }}</dd>
                                        </div>
                                        <div v-if="invoice.stripe_hosted_invoice_url" class="mt-3">
                                            <a
                                                :href="invoice.stripe_hosted_invoice_url"
                                                target="_blank"
                                                class="inline-flex items-center px-3 py-2 border border-indigo-300 rounded-md text-sm font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100"
                                            >
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                </svg>
                                                In Stripe öffnen
                                            </a>
                                        </div>
                                    </template>
                                    <template v-else>
                                        <p class="text-sm text-gray-600">
                                            Zahlung per Banküberweisung erwartet.
                                        </p>
                                    </template>
                                </dl>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                            <div class="px-4 py-5 sm:px-6">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Aktionen</h3>
                            </div>
                            <div class="border-t border-gray-200 px-4 py-5 space-y-3">
                                <button
                                    v-if="canSend"
                                    @click="sendInvoice"
                                    class="w-full inline-flex justify-center items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase hover:bg-blue-700"
                                >
                                    Rechnung versenden
                                </button>
                                <button
                                    v-if="canMarkPaid"
                                    @click="showMarkPaidModal = true"
                                    class="w-full inline-flex justify-center items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase hover:bg-green-700"
                                >
                                    Als bezahlt markieren
                                </button>
                                <button
                                    v-if="canSendReminder"
                                    @click="sendReminder"
                                    class="w-full inline-flex justify-center items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase hover:bg-yellow-700"
                                >
                                    Mahnung senden ({{ invoice.reminder_count + 1 }}. Mahnung)
                                </button>
                                <button
                                    v-if="canCancel"
                                    @click="showCancelModal = true"
                                    class="w-full inline-flex justify-center items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase hover:bg-red-700"
                                >
                                    Stornieren
                                </button>
                            </div>
                        </div>

                        <!-- Payment Info (if paid) -->
                        <div v-if="invoice.status === 'paid'" class="bg-green-50 shadow overflow-hidden sm:rounded-lg">
                            <div class="px-4 py-5 sm:px-6">
                                <h3 class="text-lg leading-6 font-medium text-green-800">Zahlung erhalten</h3>
                            </div>
                            <div class="border-t border-green-200 px-4 py-5">
                                <dl class="space-y-2">
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-green-600">Bezahlt am</dt>
                                        <dd class="text-sm text-green-800">{{ formatDate(invoice.paid_at) }}</dd>
                                    </div>
                                    <div v-if="invoice.payment_reference" class="flex justify-between">
                                        <dt class="text-sm text-green-600">Referenz</dt>
                                        <dd class="text-sm text-green-800">{{ invoice.payment_reference }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mark as Paid Modal -->
        <div v-if="showMarkPaidModal" class="fixed z-10 inset-0 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showMarkPaidModal = false"></div>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form @submit.prevent="markAsPaid">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Als bezahlt markieren</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Zahlungsdatum</label>
                                    <input
                                        v-model="markPaidForm.paid_at"
                                        type="date"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Zahlungsreferenz (optional)</label>
                                    <input
                                        v-model="markPaidForm.payment_reference"
                                        type="text"
                                        placeholder="z.B. Transaktions-ID"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Notizen (optional)</label>
                                    <textarea
                                        v-model="markPaidForm.payment_notes"
                                        rows="3"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    ></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button
                                type="submit"
                                :disabled="markPaidForm.processing"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                Als bezahlt markieren
                            </button>
                            <button
                                type="button"
                                @click="showMarkPaidModal = false"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm"
                            >
                                Abbrechen
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Cancel Modal -->
        <div v-if="showCancelModal" class="fixed z-10 inset-0 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showCancelModal = false"></div>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form @submit.prevent="cancelInvoice">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Rechnung stornieren</h3>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Stornierungsgrund</label>
                                <textarea
                                    v-model="cancelForm.reason"
                                    rows="3"
                                    required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    placeholder="Bitte geben Sie den Grund für die Stornierung an..."
                                ></textarea>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button
                                type="submit"
                                :disabled="cancelForm.processing || !cancelForm.reason"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                Stornieren
                            </button>
                            <button
                                type="button"
                                @click="showCancelModal = false"
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
