<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: {
        type: String,
        required: true,
    },
    chargesEnabled: {
        type: Boolean,
        default: false,
    },
    payoutsEnabled: {
        type: Boolean,
        default: false,
    },
    connectedAt: {
        type: String,
        default: null,
    },
    accountId: {
        type: String,
        default: null,
    },
});

const statusConfig = computed(() => {
    const configs = {
        active: {
            label: 'Aktiv',
            bgClass: 'bg-green-100 dark:bg-green-900',
            textClass: 'text-green-800 dark:text-green-200',
            icon: 'M5 13l4 4L19 7',
        },
        pending: {
            label: 'Ausstehend',
            bgClass: 'bg-yellow-100 dark:bg-yellow-900',
            textClass: 'text-yellow-800 dark:text-yellow-200',
            icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        },
        restricted: {
            label: 'Eingeschränkt',
            bgClass: 'bg-red-100 dark:bg-red-900',
            textClass: 'text-red-800 dark:text-red-200',
            icon: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        },
        not_connected: {
            label: 'Nicht verbunden',
            bgClass: 'bg-gray-100 dark:bg-gray-700',
            textClass: 'text-gray-800 dark:text-gray-200',
            icon: 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636',
        },
    };
    return configs[props.status] || configs.not_connected;
});

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('de-DE', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};
</script>

<template>
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
        <div class="flex items-start justify-between">
            <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    Stripe Connect Status
                </h3>
                <p v-if="accountId" class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Account: {{ accountId }}
                </p>
            </div>
            <span
                :class="[statusConfig.bgClass, statusConfig.textClass]"
                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
            >
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="statusConfig.icon" />
                </svg>
                {{ statusConfig.label }}
            </span>
        </div>

        <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
            <!-- Charges Enabled -->
            <div class="flex items-center">
                <div
                    :class="chargesEnabled ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-400'"
                    class="flex-shrink-0 h-8 w-8 rounded-full flex items-center justify-center"
                >
                    <svg v-if="chargesEnabled" class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    <svg v-else class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Zahlungen</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ chargesEnabled ? 'Aktiviert' : 'Deaktiviert' }}
                    </p>
                </div>
            </div>

            <!-- Payouts Enabled -->
            <div class="flex items-center">
                <div
                    :class="payoutsEnabled ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-400'"
                    class="flex-shrink-0 h-8 w-8 rounded-full flex items-center justify-center"
                >
                    <svg v-if="payoutsEnabled" class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    <svg v-else class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Auszahlungen</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ payoutsEnabled ? 'Aktiviert' : 'Deaktiviert' }}
                    </p>
                </div>
            </div>

            <!-- Connected Since -->
            <div class="flex items-center col-span-2">
                <div class="flex-shrink-0 h-8 w-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Verbunden seit</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ formatDate(connectedAt) }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
