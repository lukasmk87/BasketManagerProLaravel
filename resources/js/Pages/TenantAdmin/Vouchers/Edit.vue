<script setup>
import { ref, watch, computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    voucher: { type: Object, required: true },
    plans: { type: Array, default: () => [] },
    voucherTypes: { type: Array, default: () => [] },
    canChangeCode: { type: Boolean, default: false },
});

const form = useForm({
    code: props.voucher.code,
    name: props.voucher.name,
    description: props.voucher.description || '',
    type: props.voucher.type,
    discount_percent: props.voucher.discount_percent,
    discount_amount: props.voucher.discount_amount,
    trial_extension_days: props.voucher.trial_extension_days,
    duration_months: props.voucher.duration_months,
    max_redemptions: props.voucher.max_redemptions,
    valid_from: props.voucher.valid_from || '',
    valid_until: props.voucher.valid_until || '',
    applicable_plan_ids: props.voucher.applicable_plan_ids || [],
    is_active: props.voucher.is_active,
});

const showPercentField = computed(() => form.type === 'percent');
const showAmountField = computed(() => form.type === 'fixed_amount');
const showTrialField = computed(() => form.type === 'trial_extension');
const showDurationField = computed(() => form.type !== 'trial_extension');

watch(() => form.type, (newType, oldType) => {
    if (newType === oldType) return;

    if (newType === 'percent') {
        form.discount_amount = null;
        form.trial_extension_days = null;
    } else if (newType === 'fixed_amount') {
        form.discount_percent = null;
        form.trial_extension_days = null;
    } else if (newType === 'trial_extension') {
        form.discount_percent = null;
        form.discount_amount = null;
    }
});

const submit = () => {
    form.put(route('tenant-admin.vouchers.update', props.voucher.id));
};
</script>

<template>
    <AppLayout title="Voucher bearbeiten">
        <template #header>
            <div class="flex items-center">
                <Link :href="route('tenant-admin.vouchers.show', voucher.id)" class="text-gray-500 hover:text-gray-700 mr-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Voucher bearbeiten: {{ voucher.code }}
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6 space-y-6">
                        <!-- Info about redemptions -->
                        <div v-if="voucher.current_redemptions > 0" class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                            <div class="flex">
                                <svg class="h-5 w-5 text-yellow-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <div class="text-sm text-yellow-700">
                                    Dieser Voucher wurde bereits {{ voucher.current_redemptions }}x eingelöst.
                                    Der Code kann nicht mehr geändert werden.
                                </div>
                            </div>
                        </div>

                        <!-- Code -->
                        <div>
                            <InputLabel for="code" value="Voucher-Code" />
                            <TextInput
                                id="code"
                                v-model="form.code"
                                type="text"
                                class="mt-1 block w-full uppercase"
                                :disabled="!canChangeCode"
                                :class="{ 'bg-gray-100': !canChangeCode }"
                            />
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
                                />
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">%</span>
                                </div>
                            </div>
                            <InputError class="mt-2" :message="form.errors.discount_percent" />
                        </div>

                        <!-- Discount Amount -->
                        <div v-if="showAmountField">
                            <InputLabel for="discount_amount" value="Rabattbetrag pro Monat (max. 1000 EUR)" />
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <TextInput
                                    id="discount_amount"
                                    v-model="form.discount_amount"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    max="1000"
                                    class="block w-full pr-12"
                                />
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">EUR</span>
                                </div>
                            </div>
                            <InputError class="mt-2" :message="form.errors.discount_amount" />
                        </div>

                        <!-- Trial Extension Days -->
                        <div v-if="showTrialField">
                            <InputLabel for="trial_extension_days" value="Trial-Verlängerung in Tagen (max. 90)" />
                            <TextInput
                                id="trial_extension_days"
                                v-model="form.trial_extension_days"
                                type="number"
                                min="1"
                                max="90"
                                class="mt-1 block w-full"
                            />
                            <InputError class="mt-2" :message="form.errors.trial_extension_days" />
                        </div>

                        <!-- Duration Months -->
                        <div v-if="showDurationField">
                            <InputLabel for="duration_months" value="Dauer (max. 12 Monate)" />
                            <TextInput
                                id="duration_months"
                                v-model="form.duration_months"
                                type="number"
                                min="1"
                                max="12"
                                class="mt-1 block w-full"
                            />
                            <p class="mt-1 text-sm text-gray-500">
                                Wie viele Abrechnungszyklen der Rabatt gilt.
                            </p>
                            <InputError class="mt-2" :message="form.errors.duration_months" />
                        </div>

                        <!-- Max Redemptions -->
                        <div>
                            <InputLabel for="max_redemptions" value="Max. Einlösungen (optional, max. 1000)" />
                            <TextInput
                                id="max_redemptions"
                                v-model="form.max_redemptions"
                                type="number"
                                min="1"
                                max="1000"
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
                        <div v-if="plans.length > 0">
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
                            <Link :href="route('tenant-admin.vouchers.show', voucher.id)">
                                <SecondaryButton type="button">Abbrechen</SecondaryButton>
                            </Link>
                            <PrimaryButton :disabled="form.processing">
                                Änderungen speichern
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
