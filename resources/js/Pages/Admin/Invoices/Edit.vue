<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePricing } from '@/Composables/usePricing';

const { defaultTaxRate, getSmallBusinessNotice } = usePricing();

const props = defineProps({
    invoice: Object,
    paymentMethods: Object,
    defaultTaxRate: {
        type: Number,
        default: 19,
    },
});

const form = useForm({
    net_amount: props.invoice.net_amount,
    tax_rate: props.invoice.tax_rate,
    is_small_business: props.invoice.is_small_business || false,
    payment_method: props.invoice.payment_method,
    billing_period: props.invoice.billing_period || '',
    description: props.invoice.description || '',
    line_items: props.invoice.line_items || [],
    billing_name: props.invoice.billing_name,
    billing_email: props.invoice.billing_email,
    billing_address: props.invoice.billing_address || {
        street: '',
        zip: '',
        city: '',
        country: 'Deutschland',
    },
    vat_number: props.invoice.vat_number || '',
    issue_date: props.invoice.issue_date?.split('T')[0] || '',
    due_date: props.invoice.due_date?.split('T')[0] || '',
});

const getTypeLabel = (type) => {
    return type?.includes('Club') ? 'Club' : 'Tenant';
};

const getTypeBadgeClass = (type) => {
    return type?.includes('Club')
        ? 'bg-blue-100 text-blue-800'
        : 'bg-purple-100 text-purple-800';
};

const taxAmount = computed(() => {
    if (form.is_small_business) return 0;
    const net = parseFloat(form.net_amount) || 0;
    const rate = parseFloat(form.tax_rate) || 0;
    return (net * rate / 100).toFixed(2);
});

const grossAmount = computed(() => {
    const net = parseFloat(form.net_amount) || 0;
    const tax = parseFloat(taxAmount.value) || 0;
    return (net + tax).toFixed(2);
});

const addLineItem = () => {
    form.line_items.push({
        description: '',
        quantity: 1,
        unit_price: 0,
        total: 0,
    });
};

const removeLineItem = (index) => {
    form.line_items.splice(index, 1);
    recalculateNetAmount();
};

const updateLineItemTotal = (index) => {
    const item = form.line_items[index];
    item.total = (item.quantity * item.unit_price).toFixed(2);
    recalculateNetAmount();
};

const recalculateNetAmount = () => {
    if (form.line_items.length > 0) {
        const total = form.line_items.reduce((sum, item) => sum + parseFloat(item.total || 0), 0);
        form.net_amount = total.toFixed(2);
    }
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount);
};

const submit = () => {
    form.put(route('admin.invoices.update', props.invoice.id));
};
</script>

<template>
    <AdminLayout title="Rechnung bearbeiten">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Rechnung bearbeiten
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ invoice.invoice_number }}
                    </p>
                </div>
                <Link
                    :href="route('admin.invoices.show', invoice.id)"
                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50"
                >
                    Abbrechen
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Invoiceable Info (Readonly) -->
                    <div class="bg-white shadow sm:rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Rechnungsempfänger</h3>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="flex items-center">
                                    <span :class="[getTypeBadgeClass(invoice.invoiceable_type), 'px-2 py-1 text-xs font-semibold rounded-full mr-3']">
                                        {{ getTypeLabel(invoice.invoiceable_type) }}
                                    </span>
                                    <div>
                                        <div class="font-medium text-gray-900">
                                            {{ invoice.invoiceable?.name || invoice.billing_name }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ invoice.billing_email }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-2 text-xs text-gray-500">
                                Der Empfänger kann nicht geändert werden.
                            </p>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="bg-white shadow sm:rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Zahlungsmethode</h3>
                            <div class="max-w-xs">
                                <select
                                    v-model="form.payment_method"
                                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                >
                                    <option v-for="(label, value) in paymentMethods" :key="value" :value="value">
                                        {{ label }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Invoice Details -->
                    <div class="bg-white shadow sm:rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Rechnungsdetails</h3>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Rechnungsdatum *</label>
                                    <input
                                        v-model="form.issue_date"
                                        type="date"
                                        required
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Fälligkeitsdatum *</label>
                                    <input
                                        v-model="form.due_date"
                                        type="date"
                                        required
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Abrechnungszeitraum</label>
                                    <input
                                        v-model="form.billing_period"
                                        type="text"
                                        placeholder="z.B. 01.01.2025 - 31.01.2025"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Beschreibung</label>
                                    <input
                                        v-model="form.description"
                                        type="text"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Line Items -->
                    <div class="bg-white shadow sm:rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Rechnungsposten</h3>
                                <button
                                    type="button"
                                    @click="addLineItem"
                                    class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200"
                                >
                                    + Posten hinzufügen
                                </button>
                            </div>

                            <div v-if="form.line_items.length > 0" class="space-y-4">
                                <div v-for="(item, index) in form.line_items" :key="index" class="grid grid-cols-12 gap-4 items-end">
                                    <div class="col-span-5">
                                        <label v-if="index === 0" class="block text-sm font-medium text-gray-700">Beschreibung</label>
                                        <input
                                            v-model="item.description"
                                            type="text"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        />
                                    </div>
                                    <div class="col-span-2">
                                        <label v-if="index === 0" class="block text-sm font-medium text-gray-700">Menge</label>
                                        <input
                                            v-model.number="item.quantity"
                                            type="number"
                                            min="1"
                                            @change="updateLineItemTotal(index)"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        />
                                    </div>
                                    <div class="col-span-2">
                                        <label v-if="index === 0" class="block text-sm font-medium text-gray-700">Einzelpreis</label>
                                        <input
                                            v-model.number="item.unit_price"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            @change="updateLineItemTotal(index)"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        />
                                    </div>
                                    <div class="col-span-2">
                                        <label v-if="index === 0" class="block text-sm font-medium text-gray-700">Gesamt</label>
                                        <input
                                            :value="item.total"
                                            type="text"
                                            disabled
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                        />
                                    </div>
                                    <div class="col-span-1">
                                        <button
                                            type="button"
                                            @click="removeLineItem(index)"
                                            class="text-red-600 hover:text-red-900"
                                        >
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-sm text-gray-500">Keine Posten hinzugefügt.</p>
                        </div>
                    </div>

                    <!-- Amounts -->
                    <div class="bg-white shadow sm:rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Beträge</h3>

                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Nettobetrag (EUR) *</label>
                                    <input
                                        v-model="form.net_amount"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        required
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    />
                                    <p v-if="form.errors.net_amount" class="mt-2 text-sm text-red-600">{{ form.errors.net_amount }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Steuersatz (%)</label>
                                    <input
                                        v-model="form.tax_rate"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        :disabled="form.is_small_business"
                                        :class="[
                                            'mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm',
                                            form.is_small_business ? 'bg-gray-100 cursor-not-allowed' : 'focus:ring-indigo-500 focus:border-indigo-500'
                                        ]"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Bruttobetrag</label>
                                    <div class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-900 sm:text-sm">
                                        {{ formatCurrency(grossAmount) }}
                                    </div>
                                </div>
                                <div class="flex items-end">
                                    <label class="flex items-center">
                                        <input
                                            v-model="form.is_small_business"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                            @change="form.tax_rate = form.is_small_business ? 0 : defaultTaxRate"
                                        />
                                        <span class="ml-2 text-sm text-gray-600">Kleinunternehmer</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Billing Details -->
                    <div class="bg-white shadow sm:rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Rechnungsadresse</h3>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Name *</label>
                                    <input
                                        v-model="form.billing_name"
                                        type="text"
                                        required
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">E-Mail *</label>
                                    <input
                                        v-model="form.billing_email"
                                        type="email"
                                        required
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    />
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Strasse</label>
                                    <input
                                        v-model="form.billing_address.street"
                                        type="text"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">PLZ</label>
                                    <input
                                        v-model="form.billing_address.zip"
                                        type="text"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Stadt</label>
                                    <input
                                        v-model="form.billing_address.city"
                                        type="text"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">USt-IdNr.</label>
                                    <input
                                        v-model="form.vat_number"
                                        type="text"
                                        placeholder="DE123456789"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="flex justify-end space-x-3">
                        <Link
                            :href="route('admin.invoices.show', invoice.id)"
                            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50"
                        >
                            Abbrechen
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 disabled:opacity-50"
                        >
                            Änderungen speichern
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
