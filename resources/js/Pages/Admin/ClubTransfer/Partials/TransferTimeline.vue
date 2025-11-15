<script setup>
import { ref } from 'vue';

const props = defineProps({
    logs: {
        type: Array,
        required: true,
    },
});

const expandedLog = ref(null);

const toggleExpand = (logId) => {
    expandedLog.value = expandedLog.value === logId ? null : logId;
};

const stepNames = {
    validation: 'Validierung',
    rollback_snapshot: 'Snapshot erstellen',
    stripe_cancellation: 'Stripe-Kündigung',
    membership_removal: 'Mitgliedschaften entfernen',
    media_migration: 'Medien migrieren',
    club_update: 'Club aktualisieren',
    related_records_update: 'Verknüpfte Daten aktualisieren',
    cache_clear: 'Cache leeren',
    completion: 'Abschluss',
    rollback: 'Rollback',
};

const getStepName = (step) => {
    return stepNames[step] || step;
};

const getStatusConfig = (status) => {
    const configs = {
        started: {
            class: 'bg-blue-100 text-blue-800',
            iconClass: 'text-blue-600',
            icon: 'play',
        },
        in_progress: {
            class: 'bg-blue-100 text-blue-800',
            iconClass: 'text-blue-600 animate-pulse',
            icon: 'spinner',
        },
        completed: {
            class: 'bg-green-100 text-green-800',
            iconClass: 'text-green-600',
            icon: 'check',
        },
        failed: {
            class: 'bg-red-100 text-red-800',
            iconClass: 'text-red-600',
            icon: 'x',
        },
        skipped: {
            class: 'bg-gray-100 text-gray-800',
            iconClass: 'text-gray-600',
            icon: 'forward',
        },
    };

    return configs[status] || configs.started;
};

const formatTime = (timestamp) => {
    return new Intl.DateTimeFormat('de-DE', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    }).format(new Date(timestamp));
};
</script>

<template>
    <div class="flow-root">
        <ul role="list" class="-mb-8">
            <li v-for="(log, index) in logs" :key="log.id">
                <div class="relative pb-8">
                    <!-- Connector line -->
                    <span
                        v-if="index !== logs.length - 1"
                        class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"
                        aria-hidden="true"
                    ></span>

                    <div class="relative flex space-x-3">
                        <!-- Icon -->
                        <div>
                            <span
                                :class="[
                                    'h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white',
                                    getStatusConfig(log.status).class
                                ]"
                            >
                                <!-- Check Icon -->
                                <svg
                                    v-if="getStatusConfig(log.status).icon === 'check'"
                                    :class="['h-5 w-5', getStatusConfig(log.status).iconClass]"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>

                                <!-- X Icon -->
                                <svg
                                    v-else-if="getStatusConfig(log.status).icon === 'x'"
                                    :class="['h-5 w-5', getStatusConfig(log.status).iconClass]"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>

                                <!-- Spinner Icon -->
                                <svg
                                    v-else-if="getStatusConfig(log.status).icon === 'spinner'"
                                    :class="['h-5 w-5', getStatusConfig(log.status).iconClass]"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                    />
                                </svg>

                                <!-- Play Icon -->
                                <svg
                                    v-else-if="getStatusConfig(log.status).icon === 'play'"
                                    :class="['h-5 w-5', getStatusConfig(log.status).iconClass]"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>

                                <!-- Forward Icon -->
                                <svg
                                    v-else
                                    :class="['h-5 w-5', getStatusConfig(log.status).iconClass]"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M13 5l7 7-7 7M5 5l7 7-7 7"
                                    />
                                </svg>
                            </span>
                        </div>

                        <!-- Content -->
                        <div class="flex min-w-0 flex-1 justify-between space-x-4">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">
                                    {{ getStepName(log.step) }}
                                </p>
                                <p class="text-sm text-gray-500 mt-0.5">
                                    {{ log.message }}
                                </p>

                                <!-- Expandable Details -->
                                <button
                                    v-if="log.data"
                                    @click="toggleExpand(log.id)"
                                    class="mt-2 text-xs text-indigo-600 hover:text-indigo-900 focus:outline-none"
                                >
                                    <span v-if="expandedLog === log.id">▼ Details ausblenden</span>
                                    <span v-else>▶ Details anzeigen</span>
                                </button>

                                <div
                                    v-if="expandedLog === log.id && log.data"
                                    class="mt-2 bg-gray-50 rounded-md p-3"
                                >
                                    <pre class="text-xs text-gray-700 whitespace-pre-wrap">{{ JSON.stringify(log.data, null, 2) }}</pre>
                                </div>
                            </div>

                            <div class="whitespace-nowrap text-right text-sm text-gray-500">
                                <div>{{ formatTime(log.created_at) }}</div>
                                <div v-if="log.duration" class="text-xs text-gray-400 mt-0.5">
                                    {{ log.duration }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</template>
