<script setup>
import { ref, computed, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    club: Object,
    clubs: Array,
    plans: Array,
    defaultTaxRate: Number,
    paymentTermsDays: Number,
});

const form = useForm({
    club_id: props.club?.id || '',
    club_subscription_plan_id: '',
    net_amount: '',
    tax_rate: props.defaultTaxRate,
    billing_period: '',
    description: '',
    line_items: [],
    billing_name: props.club?.invoice_billing_name || props.club?.name || '',
    billing_email: props.club?.billing_email || props.club?.email || '',
    billing_address: props.club?.billing_address || {
        street: '',
        zip: '',
        city: '',
        country: 'Deutschland',
    },
    vat_number: props.club?.invoice_vat_number || '',
    issue_date: new Date().toISOString().split('T')[0],
    due_date: new Date(Date.now() + props.paymentTermsDays * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
});

const selectedClub = computed(() => {
    return props.clubs.find(c => c.id == form.club_id);
});

const selectedPlan = computed(() => {
    return props.plans.find(p => p.id === form.club_subscription_plan_id);
});

// Auto-fill billing details when club changes
watch(() => form.club_id, (newClubId) => {
    const club = props.clubs.find(c => c.id == newClubId);
    if (club) {
        form.billing_name = club.invoice_billing_name || club.name;
        form.billing_email = club.billing_email || club.email;
        form.billing_address = club.billing_address || {
            street: '',
            zip: '',
            city: '',
            country: 'Deutschland',
        };
        form.vat_number = club.invoice_vat_number || '';
    }
});

// Auto-fill net amount when plan changes
watch(() => form.club_subscription_plan_id, (newPlanId) => {
    const plan = props.plans.find(p => p.id === newPlanId);
    if (plan) {
        form.net_amount = plan.price;
        form.description = `Subscription: ${plan.name}`;
        form.line_items = [{
            description: `Subscription: ${plan.name} (monatlich)`,
            quantity: 1,
            unit_price: plan.price,
            total: plan.price,
        }];
    }
});

const taxAmount = computed(() => {
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
};

const updateLineItemTotal = (index) => {
    const item = form.line_items[index];
    item.total = (item.quantity * item.unit_price).toFixed(2);
    recalculateNetAmount();
};

const recalculateNetAmount = () => {
    const total = form.line_items.reduce((sum, item) => sum + parseFloat(item.total || 0), 0);
    form.net_amount = total.toFixed(2);
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount);
};

const submit = () => {
    form.post(route('admin.invoices.store'));
};
</script>

<template>
    <AdminLayout title="Neue Rechnung">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Neue Rechnung erstellen
                    </h2>
                </div>
                <Link
                    :href="route('admin.invoices.index')"
                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50"
                >
                    Abbrechen
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Club Selection -->
                    <div class="bg-white shadow sm:rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Verein auswählen</h3>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Verein *</label>
                                    <select
                                        v-model="form.club_id"
                                        required
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    >
                                        <option value="">Bitte wählen...</option>
                                        <option v-for="club in clubs" :key="club.id" :value="club.id">
                                            {{ club.name }}
                                        </option>
                                    </select>
                                    <p v-if="form.errors.club_id" class="mt-2 text-sm text-red-600">{{ form.errors.club_id }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Subscription-Plan (optional)</label>
                                    <select
                                        v-model="form.club_subscription_plan_id"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    >
                                        <option value="">Keinen Plan auswählen</option>
                                        <option v-for="plan in plans" :key="plan.id" :value="plan.id">
                                            {{ plan.name }} ({{ formatCurrency(plan.price) }}/Monat)
                                        </option>
                                    </select>
                                </div>
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
                            <p v-else class="text-sm text-gray-500">Keine Posten hinzugefügt. Klicken Sie auf "Posten hinzufügen" oder wählen Sie einen Plan.</p>
                        </div>
                    </div>

                    <!-- Amounts -->
                    <div class="bg-white shadow sm:rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Beträge</h3>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Nettobetrag (€) *</label>
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
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Bruttobetrag</label>
                                    <div class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-900 sm:text-sm">
                                        {{ formatCurrency(grossAmount) }}
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">inkl. {{ formatCurrency(taxAmount) }} MwSt.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Billing Details -->
                    <div class="bg-white shadow sm:rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Rechnungsempfänger</h3>
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
                                    <label class="block text-sm font-medium text-gray-700">Straße</label>
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
                            :href="route('admin.invoices.index')"
                            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50"
                        >
                            Abbrechen
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 disabled:opacity-50"
                        >
                            Rechnung erstellen
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
