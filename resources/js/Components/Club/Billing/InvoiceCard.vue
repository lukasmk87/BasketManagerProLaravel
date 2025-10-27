<script setup>
import { computed } from 'vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useStripe } from '@/composables/useStripe.js';

const props = defineProps({
    invoice: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['view-details', 'download-pdf']);

const { formatAmount } = useStripe();

const statusConfig = computed(() => {
    const statuses = {
        'draft': {
            label: 'Entwurf',
            color: 'bg-gray-100 text-gray-800 border-gray-300',
            icon: '📝',
        },
        'open': {
            label: 'Offen',
            color: 'bg-yellow-100 text-yellow-800 border-yellow-300',
            icon: '⏳',
        },
        'paid': {
            label: 'Bezahlt',
            color: 'bg-green-100 text-green-800 border-green-300',
            icon: '✓',
        },
        'uncollectible': {
            label: 'Uneinbringlich',
            color: 'bg-red-100 text-red-800 border-red-300',
            icon: '✕',
        },
        'void': {
            label: 'Storniert',
            color: 'bg-gray-100 text-gray-800 border-gray-300',
            icon: '∅',
        },
    };
    return statuses[props.invoice.status] || statuses.draft;
});

const invoiceDate = computed(() => {
    if (!props.invoice.created) return 'N/A';
    return new Date(props.invoice.created * 1000).toLocaleDateString('de-DE', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
});

const dueDate = computed(() => {
    if (!props.invoice.due_date) return null;
    return new Date(props.invoice.due_date * 1000).toLocaleDateString('de-DE', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
});

const formattedAmount = computed(() => {
    return formatAmount(props.invoice.amount_due || 0, props.invoice.currency || 'EUR');
});

const isPastDue = computed(() => {
    if (props.invoice.status !== 'open') return false;
    if (!props.invoice.due_date) return false;
    return Date.now() > props.invoice.due_date * 1000;
});
</script>

<template>
    <div
        class="bg-white rounded-lg border-2 shadow-sm transition-all hover:shadow-md"
        :class="[
            isPastDue ? 'border-red-300' : 'border-gray-200',
            'hover:border-gray-300'
        ]"
    >
        <div class="p-6">
            <!-- Header -->
            <div class="flex items-start justify-between mb-4">
                <div class="flex-1">
                    <div class="flex items-center space-x-2">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Rechnung #{{ invoice.number || invoice.id.slice(-8) }}
                        </h3>
                        <span
                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold border"
                            :class="statusConfig.color"
                        >
                            {{ statusConfig.icon }} {{ statusConfig.label }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 mt-1">
                        {{ invoiceDate }}
                    </p>
                </div>

                <!-- Amount -->
                <div class="text-right">
                    <div class="text-2xl font-bold text-gray-900">
                        {{ formattedAmount }}
                    </div>
                    <div v-if="invoice.amount_paid && invoice.amount_paid !== invoice.amount_due" class="text-sm text-gray-600 mt-1">
                        {{ formatAmount(invoice.amount_paid, invoice.currency) }} bezahlt
                    </div>
                </div>
            </div>

            <!-- Due Date -->
            <div v-if="dueDate" class="mb-4">
                <div class="flex items-center text-sm">
                    <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span class="text-gray-600">Fällig am:</span>
                    <span
                        class="ml-2 font-medium"
                        :class="isPastDue ? 'text-red-600' : 'text-gray-900'"
                    >
                        {{ dueDate }}
                        <span v-if="isPastDue" class="ml-1 text-red-600 font-semibold">
                            (überfällig)
                        </span>
                    </span>
                </div>
            </div>

            <!-- Line Items Preview -->
            <div v-if="invoice.lines && invoice.lines.length > 0" class="mb-4">
                <div class="text-sm text-gray-600 mb-2">Positionen:</div>
                <ul class="space-y-1">
                    <li
                        v-for="(line, index) in invoice.lines.slice(0, 3)"
                        :key="index"
                        class="text-sm text-gray-700 flex justify-between"
                    >
                        <span>{{ line.description }}</span>
                        <span class="font-medium">{{ formatAmount(line.amount, invoice.currency) }}</span>
                    </li>
                    <li v-if="invoice.lines.length > 3" class="text-sm text-gray-500 italic">
                        + {{ invoice.lines.length - 3 }} weitere Position(en)
                    </li>
                </ul>
            </div>

            <!-- Actions -->
            <div class="flex gap-2 pt-4 border-t border-gray-200">
                <SecondaryButton
                    type="button"
                    @click="emit('view-details', invoice)"
                    class="flex-1 justify-center text-sm"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    Details
                </SecondaryButton>

                <SecondaryButton
                    v-if="invoice.invoice_pdf"
                    type="button"
                    @click="emit('download-pdf', invoice)"
                    class="flex-1 justify-center text-sm"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    PDF
                </SecondaryButton>
            </div>
        </div>
    </div>
</template>
