<template>
    <div>
        <!-- Header mit Add Button -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Zahlungsmethoden</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Verwalten Sie Ihre Kreditkarten, Bankkonto und andere Zahlungsmethoden
                </p>
            </div>
            <button
                v-if="!loading && paymentMethods.length > 0"
                @click="$emit('add')"
                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Zahlungsmethode hinzufügen
            </button>
        </div>

        <!-- Type Filter (optional) -->
        <div v-if="showTypeFilter && !loading && paymentMethods.length > 0" class="mb-6">
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="type in availableTypes"
                    :key="type.value"
                    @click="selectedType = type.value"
                    class="px-4 py-2 text-sm font-medium rounded-lg transition-colors"
                    :class="selectedType === type.value
                        ? 'bg-blue-100 text-blue-700 border-2 border-blue-300'
                        : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'"
                >
                    <span class="mr-2">{{ type.icon }}</span>
                    {{ type.label }}
                    <span v-if="getTypeCount(type.value) > 0" class="ml-2 text-xs opacity-75">
                        ({{ getTypeCount(type.value) }})
                    </span>
                </button>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="space-y-4">
            <div v-for="i in 3" :key="i" class="bg-white rounded-lg shadow-sm border p-6 animate-pulse">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-gray-200 rounded"></div>
                    <div class="flex-1 space-y-2">
                        <div class="h-4 bg-gray-200 rounded w-1/4"></div>
                        <div class="h-3 bg-gray-200 rounded w-1/3"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div
            v-else-if="filteredPaymentMethods.length === 0 && !loading"
            class="text-center py-12 bg-white rounded-lg shadow-sm border"
        >
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900">
                {{ selectedType === 'all' ? 'Keine Zahlungsmethoden' : 'Keine Zahlungsmethoden dieses Typs' }}
            </h3>
            <p class="mt-2 text-sm text-gray-600">
                {{ selectedType === 'all'
                    ? 'Fügen Sie eine Zahlungsmethode hinzu, um Abonnements zu bezahlen.'
                    : 'Es wurden keine Zahlungsmethoden dieses Typs gefunden.' }}
            </p>
            <div class="mt-6">
                <button
                    v-if="selectedType === 'all'"
                    @click="$emit('add')"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Erste Zahlungsmethode hinzufügen
                </button>
                <button
                    v-else
                    @click="selectedType = 'all'"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                >
                    Alle Zahlungsmethoden anzeigen
                </button>
            </div>
        </div>

        <!-- Payment Method Cards -->
        <div v-else class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <PaymentMethodCard
                v-for="paymentMethod in filteredPaymentMethods"
                :key="paymentMethod.id"
                :payment-method="paymentMethod"
                :loading="actionLoading === paymentMethod.id"
                @set-default="handleSetDefault"
                @update="handleUpdate"
                @delete="handleDelete"
            />
        </div>

        <!-- Info Box -->
        <div v-if="!loading && paymentMethods.length > 0" class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <h3 class="text-sm font-medium text-blue-800">Wichtige Informationen</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <ul class="list-disc list-inside space-y-1">
                            <li>Die Standard-Zahlungsmethode wird für zukünftige Abonnements verwendet</li>
                            <li>Sie können jederzeit neue Zahlungsmethoden hinzufügen oder entfernen</li>
                            <li>Alle Zahlungsdaten werden sicher über Stripe verschlüsselt</li>
                            <li>Sie können die Standard-Zahlungsmethode nicht löschen</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import PaymentMethodCard from './PaymentMethodCard.vue';

const props = defineProps({
    paymentMethods: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
    showTypeFilter: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['add', 'set-default', 'update', 'delete']);

const selectedType = ref('all');
const actionLoading = ref(null);

const availableTypes = computed(() => {
    const types = [
        { value: 'all', label: 'Alle', icon: '📋' },
        { value: 'card', label: 'Kreditkarte', icon: '💳' },
        { value: 'sepa_debit', label: 'SEPA Lastschrift', icon: '🏦' },
        { value: 'sofort', label: 'SOFORT', icon: '⚡' },
        { value: 'giropay', label: 'Giropay', icon: '🇩🇪' },
    ];

    // Only show types that have payment methods
    return types.filter(type => {
        if (type.value === 'all') return true;
        return props.paymentMethods.some(pm => pm.type === type.value);
    });
});

const filteredPaymentMethods = computed(() => {
    if (selectedType.value === 'all') {
        return props.paymentMethods;
    }
    return props.paymentMethods.filter(pm => pm.type === selectedType.value);
});

const getTypeCount = (type) => {
    if (type === 'all') return props.paymentMethods.length;
    return props.paymentMethods.filter(pm => pm.type === type).length;
};

const handleSetDefault = async (paymentMethodId) => {
    actionLoading.value = paymentMethodId;
    try {
        await emit('set-default', paymentMethodId);
    } finally {
        actionLoading.value = null;
    }
};

const handleUpdate = (paymentMethodId) => {
    emit('update', paymentMethodId);
};

const handleDelete = async (paymentMethodId) => {
    actionLoading.value = paymentMethodId;
    try {
        await emit('delete', paymentMethodId);
    } finally {
        actionLoading.value = null;
    }
};
</script>
