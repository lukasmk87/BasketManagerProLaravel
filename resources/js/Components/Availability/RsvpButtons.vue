<script setup>
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    eventType: {
        type: String,
        required: true,
        validator: (value) => ['game', 'training'].includes(value),
    },
    eventId: {
        type: [Number, String],
        required: true,
    },
    currentStatus: {
        type: String,
        default: 'pending',
    },
    canRespond: {
        type: Boolean,
        default: true,
    },
    compact: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['updated']);

const loading = ref(false);
const localStatus = ref(props.currentStatus);

const handleRespond = async (response) => {
    if (loading.value || !props.canRespond) return;

    // Validierung
    if (!props.eventId) {
        console.error('RsvpButtons: No event_id provided');
        return;
    }

    loading.value = true;
    try {
        await axios.post('/api/v2/availability/respond', {
            event_type: props.eventType,
            event_id: props.eventId,
            response: response,
        });
        localStatus.value = response;
        emit('updated', response);
    } catch (error) {
        console.error('Error updating availability:', error);
        // Zeige Server-Fehlermeldung an
        const message = error.response?.data?.message || 'Fehler beim Speichern der Verfügbarkeit.';
        alert(message);
    } finally {
        loading.value = false;
    }
};

const statusLabel = {
    'available': 'Zugesagt',
    'unavailable': 'Abgesagt',
    'maybe': 'Unsicher',
    'pending': 'Ausstehend',
};
</script>

<template>
    <div v-if="canRespond" :class="compact ? 'flex gap-1' : 'flex flex-col sm:flex-row gap-2'">
        <!-- Zusagen Button -->
        <button
            @click="handleRespond('available')"
            :disabled="loading"
            :class="[
                compact ? 'p-1.5' : 'px-3 py-2',
                'inline-flex items-center justify-center text-sm font-medium rounded-md transition-colors',
                localStatus === 'available'
                    ? 'bg-green-600 text-white'
                    : 'bg-white text-green-700 border border-green-300 hover:bg-green-50'
            ]"
            :title="compact ? 'Zusagen' : ''"
        >
            <svg class="w-4 h-4" :class="{ 'mr-1': !compact }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span v-if="!compact">Zusagen</span>
        </button>

        <!-- Unsicher Button -->
        <button
            @click="handleRespond('maybe')"
            :disabled="loading"
            :class="[
                compact ? 'p-1.5' : 'px-3 py-2',
                'inline-flex items-center justify-center text-sm font-medium rounded-md transition-colors',
                localStatus === 'maybe'
                    ? 'bg-yellow-500 text-white'
                    : 'bg-white text-yellow-700 border border-yellow-300 hover:bg-yellow-50'
            ]"
            :title="compact ? 'Unsicher' : ''"
        >
            <svg class="w-4 h-4" :class="{ 'mr-1': !compact }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span v-if="!compact">Unsicher</span>
        </button>

        <!-- Absagen Button -->
        <button
            @click="handleRespond('unavailable')"
            :disabled="loading"
            :class="[
                compact ? 'p-1.5' : 'px-3 py-2',
                'inline-flex items-center justify-center text-sm font-medium rounded-md transition-colors',
                localStatus === 'unavailable'
                    ? 'bg-red-600 text-white'
                    : 'bg-white text-red-700 border border-red-300 hover:bg-red-50'
            ]"
            :title="compact ? 'Absagen' : ''"
        >
            <svg class="w-4 h-4" :class="{ 'mr-1': !compact }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            <span v-if="!compact">Absagen</span>
        </button>
    </div>

    <!-- Status Badge wenn canRespond false -->
    <div v-else class="inline-flex items-center gap-1 text-sm">
        <span :class="{
            'text-green-700': localStatus === 'available',
            'text-red-700': localStatus === 'unavailable',
            'text-yellow-700': localStatus === 'maybe',
            'text-gray-500': localStatus === 'pending',
        }">
            {{ statusLabel[localStatus] || 'Ausstehend' }}
        </span>
    </div>
</template>
