<script setup>
import { computed } from 'vue';

const props = defineProps({
    event: {
        type: Object,
        required: true,
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['respond']);

const eventTypeLabel = computed(() => {
    return props.event.type === 'game' ? 'Spiel' : 'Training';
});

const eventTypeClass = computed(() => {
    return props.event.type === 'game'
        ? 'bg-blue-100 text-blue-800'
        : 'bg-purple-100 text-purple-800';
});

const availabilityStatus = computed(() => props.event.availability?.status || 'pending');

const statusBgClass = computed(() => {
    const classes = {
        'available': 'bg-green-50 border-green-200',
        'unavailable': 'bg-red-50 border-red-200',
        'maybe': 'bg-yellow-50 border-yellow-200',
        'pending': 'bg-gray-50 border-gray-200',
    };
    return classes[availabilityStatus.value] || classes.pending;
});

const statusLabel = computed(() => {
    const labels = {
        'available': 'Zugesagt',
        'unavailable': 'Abgesagt',
        'maybe': 'Unsicher',
        'pending': 'Ausstehend',
    };
    return labels[availabilityStatus.value] || 'Ausstehend';
});

const statusIcon = computed(() => {
    return {
        'available': 'check-circle',
        'unavailable': 'x-circle',
        'maybe': 'question-mark-circle',
        'pending': 'clock',
    }[availabilityStatus.value] || 'clock';
});

const isBlockedByAbsence = computed(() => props.event.availability?.source === 'absence');
const canRespond = computed(() => props.event.can_respond && !isBlockedByAbsence.value);

const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('de-DE', {
        weekday: 'short',
        day: '2-digit',
        month: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const handleRespond = (response) => {
    emit('respond', props.event.type, props.event.id, response);
};
</script>

<template>
    <div class="rounded-lg border p-4 transition-all" :class="statusBgClass">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <!-- Event Info -->
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" :class="eventTypeClass">
                        {{ eventTypeLabel }}
                    </span>
                    <span v-if="event.is_mandatory" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                        Pflicht
                    </span>
                </div>

                <h4 class="font-semibold text-gray-900">{{ event.title }}</h4>

                <div class="mt-1 text-sm text-gray-600 space-y-1">
                    <div class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span>{{ event.scheduled_at_formatted }}</span>
                    </div>
                    <div v-if="event.venue" class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span>{{ event.venue }}</span>
                    </div>
                </div>

                <!-- Current Status -->
                <div class="mt-3 flex items-center gap-2">
                    <span class="text-sm font-medium">Status:</span>
                    <span class="inline-flex items-center gap-1 text-sm">
                        <svg v-if="statusIcon === 'check-circle'" class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <svg v-else-if="statusIcon === 'x-circle'" class="w-4 h-4 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        <svg v-else-if="statusIcon === 'question-mark-circle'" class="w-4 h-4 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path>
                        </svg>
                        <svg v-else class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                        </svg>
                        <span :class="{
                            'text-green-700': availabilityStatus === 'available',
                            'text-red-700': availabilityStatus === 'unavailable',
                            'text-yellow-700': availabilityStatus === 'maybe',
                            'text-gray-600': availabilityStatus === 'pending',
                        }">{{ statusLabel }}</span>
                    </span>
                </div>

                <!-- Absence Warning -->
                <div v-if="isBlockedByAbsence" class="mt-2 p-2 bg-red-100 border border-red-200 rounded text-sm text-red-700">
                    <span class="font-medium">Durch Abwesenheit blockiert:</span>
                    {{ event.availability.reason }}
                </div>
            </div>

            <!-- Response Buttons -->
            <div v-if="canRespond" class="flex flex-col sm:flex-row gap-2">
                <button
                    @click="handleRespond('available')"
                    :disabled="loading"
                    class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium rounded-md transition-colors"
                    :class="availabilityStatus === 'available'
                        ? 'bg-green-600 text-white'
                        : 'bg-white text-green-700 border border-green-300 hover:bg-green-50'"
                >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Zusagen
                </button>

                <button
                    @click="handleRespond('maybe')"
                    :disabled="loading"
                    class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium rounded-md transition-colors"
                    :class="availabilityStatus === 'maybe'
                        ? 'bg-yellow-500 text-white'
                        : 'bg-white text-yellow-700 border border-yellow-300 hover:bg-yellow-50'"
                >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Unsicher
                </button>

                <button
                    @click="handleRespond('unavailable')"
                    :disabled="loading"
                    class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium rounded-md transition-colors"
                    :class="availabilityStatus === 'unavailable'
                        ? 'bg-red-600 text-white'
                        : 'bg-white text-red-700 border border-red-300 hover:bg-red-50'"
                >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Absagen
                </button>
            </div>

            <!-- Registration Closed Info -->
            <div v-else-if="!isBlockedByAbsence" class="text-sm text-gray-500 italic">
                Anmeldefrist abgelaufen
            </div>
        </div>
    </div>
</template>
