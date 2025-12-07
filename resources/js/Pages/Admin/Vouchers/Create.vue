<script setup>
import { ref, watch, computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    tenants: { type: Array, default: () => [] },
    plans: { type: Array, default: () => [] },
    voucherTypes: { type: Array, default: () => [] },
});

const form = useForm({
    tenant_id: null,
    code: '',
    name: '',
    description: '',
    type: 'percent',
    discount_percent: null,
    discount_amount: null,
    trial_extension_days: null,
    duration_months: 1,
    max_redemptions: null,
    valid_from: '',
    valid_until: '',
    applicable_plan_ids: [],
    is_active: true,
});

const isSystemWide = computed(() => form.tenant_id === null || form.tenant_id === '');

const showPercentField = computed(() => form.type === 'percent');
const showAmountField = computed(() => form.type === 'fixed_amount');
const showTrialField = computed(() => form.type === 'trial_extension');
const showDurationField = computed(() => form.type !== 'trial_extension');

watch(() => form.type, (newType) => {
    // Reset type-specific fields
    if (newType === 'percent') {
        form.discount_amount = null;
        form.trial_extension_days = null;
    } else if (newType === 'fixed_amount') {
        form.discount_percent = null;
        form.trial_extension_days = null;
    } else if (newType === 'trial_extension') {
        form.discount_percent = null;
        form.discount_amount = null;
        form.duration_months = 1;
    }
});

const submit = () => {
    form.post(route('admin.vouchers.store'));
};

const generateCode = () => {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let code = '';
    for (let i = 0; i < 8; i++) {
        code += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    form.code = code;
};
</script>

<template>
    <AdminLayout title="Neuer Voucher">
        <template #header>
            <div class="flex items-center">
                <Link :href="route('admin.vouchers.index')" class="text-gray-500 hover:text-gray-700 mr-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Neuen Voucher erstellen
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6 space-y-6">
                        <!-- Tenant Selection -->
                        <div>
                            <InputLabel for="tenant_id" value="Geltungsbereich" />
                            <select
                                id="tenant_id"
                                v-model="form.tenant_id"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            >
                                <option :value="null">System-weit (alle Tenants)</option>
                                <option v-for="tenant in tenants" :key="tenant.id" :value="tenant.id">
                                    {{ tenant.name }}
                                </option>
                            </select>
                            <p class="mt-1 text-sm text-gray-500">
                                {{ isSystemWide ? 'Dieser Voucher gilt für alle Tenants.' : 'Nur für den gewählten Tenant.' }}
                            </p>
                            <InputError class="mt-2" :message="form.errors.tenant_id" />
                        </div>

                        <!-- Code -->
                        <div>
                            <InputLabel for="code" value="Voucher-Code (optional)" />
                            <div class="mt-1 flex rounded-md shadow-sm">
                                <TextInput
                                    id="code"
                                    v-model="form.code"
                                    type="text"
                                    class="flex-1 rounded-none rounded-l-md uppercase"
                                    placeholder="Automatisch generiert wenn leer"
                                />
                                <button
                                    type="button"
                                    @click="generateCode"
                                    class="inline-flex items-center px-4 py-2 border border-l-0 border-gray-300 rounded-r-md bg-gray-50 text-gray-700 text-sm font-medium hover:bg-gray-100"
                                >
                                    Generieren
                                </button>
                            </div>
                            <InputError class="mt-2" :message="form.errors.code" />
                        </div>

                        <!-- Name -->
                        <div>
                            <InputLabel for="name" value="Name" />
                            <TextInput
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full"
                                required
                                placeholder="z.B. Frühbucher-Rabatt 2025"
                            />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <!-- Description -->
                        <div>
                            <InputLabel for="description" value="Beschreibung (optional)" />
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="2"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                placeholder="Interne Notizen zum Voucher..."
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.description" />
                        </div>

                        <!-- Voucher Type -->
                        <div>
                            <InputLabel for="type" value="Voucher-Typ" />
                            <select
                                id="type"
                                v-model="form.type"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            >
                                <option v-for="type in voucherTypes" :key="type.value" :value="type.value">
                                    {{ type.label }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.type" />
                        </div>

                        <!-- Discount Percent -->
                        <div v-if="showPercentField">
                            <InputLabel for="discount_percent" value="Rabatt in Prozent" />
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <TextInput
                                    id="discount_percent"
                                    v-model="form.discount_percent"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    max="100"
                                    class="block w-full pr-8"
                                    placeholder="z.B. 20"
                                />
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">%</span>
                                </div>
                            </div>
                            <InputError class="mt-2" :message="form.errors.discount_percent" />
                        </div>

                        <!-- Discount Amount -->
                        <div v-if="showAmountField">
                            <InputLabel for="discount_amount" value="Rabattbetrag pro Monat" />
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <TextInput
                                    id="discount_amount"
                                    v-model="form.discount_amount"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    class="block w-full pr-12"
                                    placeholder="z.B. 50"
                                />
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">EUR</span>
                                </div>
                            </div>
                            <InputError class="mt-2" :message="form.errors.discount_amount" />
                        </div>

                        <!-- Trial Extension Days -->
                        <div v-if="showTrialField">
                            <InputLabel for="trial_extension_days" value="Trial-Verlängerung in Tagen" />
                            <TextInput
                                id="trial_extension_days"
                                v-model="form.trial_extension_days"
                                type="number"
                                min="1"
                                max="365"
                                class="mt-1 block w-full"
                                placeholder="z.B. 30"
                            />
                            <InputError class="mt-2" :message="form.errors.trial_extension_days" />
                        </div>

                        <!-- Duration Months -->
                        <div v-if="showDurationField">
                            <InputLabel for="duration_months" value="Dauer (Monate)" />
                            <TextInput
                                id="duration_months"
                                v-model="form.duration_months"
                                type="number"
                                min="1"
                                max="36"
                                class="mt-1 block w-full"
                            />
                            <p class="mt-1 text-sm text-gray-500">
                                Wie viele Abrechnungszyklen der Rabatt gilt.
                            </p>
                            <InputError class="mt-2" :message="form.errors.duration_months" />
                        </div>

                        <!-- Max Redemptions -->
                        <div>
                            <InputLabel for="max_redemptions" value="Max. Einlösungen (optional)" />
                            <TextInput
                                id="max_redemptions"
                                v-model="form.max_redemptions"
                                type="number"
                                min="1"
                                class="mt-1 block w-full"
                                placeholder="Leer = unbegrenzt"
                            />
                            <InputError class="mt-2" :message="form.errors.max_redemptions" />
                        </div>

                        <!-- Validity Period -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="valid_from" value="Gültig ab (optional)" />
                                <TextInput
                                    id="valid_from"
                                    v-model="form.valid_from"
                                    type="date"
                                    class="mt-1 block w-full"
                                />
                                <InputError class="mt-2" :message="form.errors.valid_from" />
                            </div>
                            <div>
                                <InputLabel for="valid_until" value="Gültig bis (optional)" />
                                <TextInput
                                    id="valid_until"
                                    v-model="form.valid_until"
                                    type="date"
                                    class="mt-1 block w-full"
                                />
                                <InputError class="mt-2" :message="form.errors.valid_until" />
                            </div>
                        </div>

                        <!-- Plan Restrictions -->
                        <div>
                            <InputLabel value="Anwendbar auf Pläne (optional)" />
                            <div class="mt-2 space-y-2 max-h-48 overflow-y-auto border rounded-md p-3">
                                <label
                                    v-for="plan in plans"
                                    :key="plan.id"
                                    class="flex items-center"
                                >
                                    <input
                                        type="checkbox"
                                        :value="plan.id"
                                        v-model="form.applicable_plan_ids"
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                    <span class="ml-2 text-sm text-gray-700">
                                        {{ plan.name }} ({{ plan.price }} EUR)
                                    </span>
                                </label>
                            </div>
                            <p class="mt-1 text-sm text-gray-500">
                                Leer = gilt für alle Pläne
                            </p>
                            <InputError class="mt-2" :message="form.errors.applicable_plan_ids" />
                        </div>

                        <!-- Active -->
                        <div class="flex items-center">
                            <input
                                id="is_active"
                                v-model="form.is_active"
                                type="checkbox"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <label for="is_active" class="ml-2 text-sm text-gray-700">
                                Voucher ist aktiv
                            </label>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end space-x-4 pt-4 border-t">
                            <Link :href="route('admin.vouchers.index')">
                                <SecondaryButton type="button">Abbrechen</SecondaryButton>
                            </Link>
                            <PrimaryButton :disabled="form.processing">
                                Voucher erstellen
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
