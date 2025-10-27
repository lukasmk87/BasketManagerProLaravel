<template>
    <teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-[60] overflow-y-auto"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div
                    class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm"
                ></div>

                <!-- Modal panel -->
                <div class="inline-block align-middle bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full">
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-white" id="modal-title">
                                    {{ title }}
                                </h3>
                                <p class="mt-1 text-sm text-blue-100">
                                    {{ subtitle }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="bg-white px-6 py-6">
                        <!-- Loading State -->
                        <div v-if="status === 'processing'" class="text-center">
                            <!-- Animated Spinner -->
                            <div class="flex justify-center mb-4">
                                <div class="relative">
                                    <div class="w-16 h-16 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
                                    <svg class="absolute inset-0 m-auto w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                </div>
                            </div>

                            <h4 class="text-lg font-semibold text-gray-900 mb-2">
                                {{ processingMessage }}
                            </h4>

                            <p class="text-sm text-gray-600 mb-4">
                                {{ processingDescription }}
                            </p>

                            <!-- Progress Bar -->
                            <div v-if="showProgressBar" class="w-full bg-gray-200 rounded-full h-2 mb-4">
                                <div
                                    class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                    :style="{ width: `${progress}%` }"
                                ></div>
                            </div>

                            <!-- Timeout Warning -->
                            <div v-if="showTimeoutWarning" class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-yellow-600 mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    <p class="text-sm text-yellow-800">
                                        Die Authentifizierung dauert länger als erwartet. Bitte haben Sie noch einen Moment Geduld.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Success State -->
                        <div v-if="status === 'success'" class="text-center">
                            <div class="flex justify-center mb-4">
                                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                                    <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                            </div>

                            <h4 class="text-lg font-semibold text-gray-900 mb-2">
                                Authentifizierung erfolgreich!
                            </h4>

                            <p class="text-sm text-gray-600">
                                Ihre Zahlung wird jetzt verarbeitet...
                            </p>
                        </div>

                        <!-- Error State -->
                        <div v-if="status === 'error'" class="text-center">
                            <div class="flex justify-center mb-4">
                                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center">
                                    <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </div>
                            </div>

                            <h4 class="text-lg font-semibold text-gray-900 mb-2">
                                Authentifizierung fehlgeschlagen
                            </h4>

                            <p class="text-sm text-gray-600 mb-4">
                                {{ errorMessage || 'Die Authentifizierung konnte nicht abgeschlossen werden.' }}
                            </p>

                            <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-left">
                                <h5 class="text-sm font-medium text-red-900 mb-2">Was können Sie tun?</h5>
                                <ul class="text-sm text-red-800 space-y-1 list-disc list-inside">
                                    <li>Versuchen Sie es erneut</li>
                                    <li>Verwenden Sie eine andere Zahlungsmethode</li>
                                    <li>Kontaktieren Sie Ihre Bank für weitere Informationen</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Instructions -->
                        <div v-if="showInstructions" class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                                <div class="flex-1">
                                    <h5 class="text-sm font-medium text-blue-900 mb-1">Wichtiger Hinweis</h5>
                                    <p class="text-xs text-blue-800 leading-relaxed">
                                        {{ instructions }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div v-if="showCancelButton" class="bg-gray-50 px-6 py-4 flex items-center justify-end">
                        <button
                            type="button"
                            @click="handleCancel"
                            class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            :disabled="status === 'success'"
                        >
                            {{ cancelButtonText }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </teleport>
</template>

<script setup>
import { ref, computed, watch, onBeforeUnmount } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    status: {
        type: String,
        default: 'processing', // 'processing' | 'success' | 'error'
        validator: (value) => ['processing', 'success', 'error'].includes(value),
    },
    title: {
        type: String,
        default: 'Sichere Authentifizierung',
    },
    subtitle: {
        type: String,
        default: '3D Secure Verifizierung',
    },
    processingMessage: {
        type: String,
        default: 'Authentifizierung läuft...',
    },
    processingDescription: {
        type: String,
        default: 'Bitte authentifizieren Sie sich in dem Popup-Fenster Ihrer Bank. Dies kann einen Moment dauern.',
    },
    errorMessage: {
        type: String,
        default: '',
    },
    instructions: {
        type: String,
        default: 'Ein neues Fenster sollte sich öffnen, in dem Sie sich bei Ihrer Bank authentifizieren können. Falls kein Fenster erscheint, erlauben Sie bitte Pop-ups für diese Seite.',
    },
    showInstructions: {
        type: Boolean,
        default: true,
    },
    showCancelButton: {
        type: Boolean,
        default: true,
    },
    cancelButtonText: {
        type: String,
        default: 'Abbrechen',
    },
    showProgressBar: {
        type: Boolean,
        default: false,
    },
    timeout: {
        type: Number,
        default: 60000, // 60 seconds
    },
});

const emit = defineEmits(['cancel', 'timeout']);

const progress = ref(0);
const showTimeoutWarning = ref(false);
const elapsedTime = ref(0);

let progressInterval = null;
let timeoutTimer = null;

const startProgressBar = () => {
    if (!props.showProgressBar) return;

    progress.value = 0;
    elapsedTime.value = 0;

    progressInterval = setInterval(() => {
        elapsedTime.value += 100;
        progress.value = Math.min((elapsedTime.value / props.timeout) * 100, 100);

        // Show warning at 75% of timeout
        if (progress.value >= 75 && !showTimeoutWarning.value) {
            showTimeoutWarning.value = true;
        }
    }, 100);
};

const startTimeoutTimer = () => {
    if (props.timeout <= 0) return;

    timeoutTimer = setTimeout(() => {
        emit('timeout');
        showTimeoutWarning.value = false;
    }, props.timeout);
};

const clearTimers = () => {
    if (progressInterval) {
        clearInterval(progressInterval);
        progressInterval = null;
    }

    if (timeoutTimer) {
        clearTimeout(timeoutTimer);
        timeoutTimer = null;
    }

    showTimeoutWarning.value = false;
    progress.value = 0;
    elapsedTime.value = 0;
};

const handleCancel = () => {
    clearTimers();
    emit('cancel');
};

// Watch for show prop changes
watch(() => props.show, (newValue) => {
    if (newValue && props.status === 'processing') {
        startProgressBar();
        startTimeoutTimer();
    } else {
        clearTimers();
    }
});

// Watch for status changes
watch(() => props.status, (newValue) => {
    if (newValue === 'success' || newValue === 'error') {
        clearTimers();
    } else if (newValue === 'processing' && props.show) {
        startProgressBar();
        startTimeoutTimer();
    }
});

onBeforeUnmount(() => {
    clearTimers();
});
</script>

<style scoped>
@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}
</style>
