<script setup>
import { computed } from 'vue';
import { useStripe } from '@/Composables/core/useStripe.js';
import { useTranslations } from '@/Composables/core/useTranslations';

const props = defineProps({
    invoice: {
        type: Object,
        required: true,
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const { formatAmount } = useStripe();
const { trans } = useTranslations();

const nextBillingDate = computed(() => {
    if (!props.invoice.period_end) return null;
    return new Date(props.invoice.period_end * 1000).toLocaleDateString('de-DE', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
});

const daysUntilBilling = computed(() => {
    if (!props.invoice.period_end) return 0;
    const diff = props.invoice.period_end * 1000 - Date.now();
    return Math.ceil(diff / (1000 * 60 * 60 * 24));
});

const formattedTotal = computed(() => {
    return formatAmount(props.invoice.total || 0, props.invoice.currency || 'EUR');
});

const formattedSubtotal = computed(() => {
    return formatAmount(props.invoice.subtotal || 0, props.invoice.currency || 'EUR');
});
</script>

<template>
    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg border-2 border-blue-300 shadow-sm p-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center">
                <div class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-blue-500 mr-3">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">
                        {{ trans('billing.invoices.upcoming') }}
                    </h3>
                    <p class="text-sm text-gray-600">
                        {{ trans('billing.invoices.upcoming_preview') }}
                    </p>
                </div>
            </div>
            <span
                v-if="!loading"
                class="inline-flex items-center rounded-full bg-blue-500 px-3 py-1 text-sm font-semibold text-white"
            >
                {{ nextBillingDate }}
            </span>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-8">
            <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        <!-- Content -->
        <div v-else class="space-y-4">
            <!-- Days Until Billing -->
            <div class="flex items-center justify-between py-2 border-b border-blue-200">
                <span class="text-sm text-gray-700">{{ trans('billing.invoices.days_until') }}:</span>
                <span class="text-lg font-bold text-blue-600">
                    {{ daysUntilBilling }} {{ daysUntilBilling === 1 ? trans('subscription.common.day') : trans('subscription.common.days') }}
                </span>
            </div>

            <!-- Line Items -->
            <div v-if="invoice.lines && invoice.lines.length > 0" class="space-y-2">
                <div class="text-sm font-medium text-gray-700 mb-2">{{ trans('billing.invoices.positions') }}:</div>
                <div
                    v-for="(line, index) in invoice.lines"
                    :key="index"
                    class="flex justify-between items-start py-2 border-b border-blue-100"
                >
                    <div class="flex-1">
                        <div class="text-sm font-medium text-gray-900">{{ line.description }}</div>
                        <div v-if="line.period" class="text-xs text-gray-600 mt-1">
                            {{ new Date(line.period.start * 1000).toLocaleDateString('de-DE') }} -
                            {{ new Date(line.period.end * 1000).toLocaleDateString('de-DE') }}
                        </div>
                    </div>
                    <div class="text-sm font-semibold text-gray-900 ml-4">
                        {{ formatAmount(line.amount, invoice.currency) }}
                    </div>
                </div>
            </div>

            <!-- Totals -->
            <div class="pt-4 border-t-2 border-blue-300 space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-700">{{ trans('billing.labels.subtotal') }}:</span>
                    <span class="font-medium text-gray-900">{{ formattedSubtotal }}</span>
                </div>

                <div v-if="invoice.tax" class="flex justify-between text-sm">
                    <span class="text-gray-700">{{ trans('billing.labels.tax', { percent: invoice.tax_percent }) }}:</span>
                    <span class="font-medium text-gray-900">{{ formatAmount(invoice.tax, invoice.currency) }}</span>
                </div>

                <div v-if="invoice.discount" class="flex justify-between text-sm text-green-600">
                    <span>{{ trans('billing.labels.discount') }}:</span>
                    <span class="font-medium">-{{ formatAmount(invoice.discount, invoice.currency) }}</span>
                </div>

                <div class="flex justify-between pt-2 border-t border-blue-200">
                    <span class="text-lg font-semibold text-gray-900">{{ trans('billing.labels.total') }}:</span>
                    <span class="text-2xl font-bold text-blue-600">{{ formattedTotal }}</span>
                </div>
            </div>

            <!-- Info Note -->
            <div class="mt-4 flex items-start space-x-2 rounded-lg bg-white bg-opacity-60 p-3">
                <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
                <p class="text-xs text-gray-700">
                    {{ trans('billing.info.auto_charge') }}
                </p>
            </div>
        </div>
    </div>
</template>
