<script setup>
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    invitation: {
        type: Object,
        required: true,
    },
    size: {
        type: String,
        default: 'medium', // 'small', 'medium', 'large'
    },
    showUrl: {
        type: Boolean,
        default: true,
    },
    showDownloads: {
        type: Boolean,
        default: true,
    },
    showStats: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();
const copied = ref(false);

// Compute registration URL
const registrationUrl = computed(() => {
    const baseUrl = page.props.appUrl || window.location.origin;
    return `${baseUrl}/register/player/${props.invitation.invitation_token}`;
});

// Compute QR code image URL
const qrCodeUrl = computed(() => {
    if (props.invitation.qr_code_path) {
        return `/storage/${props.invitation.qr_code_path}`;
    }
    return null;
});

// Size classes for the QR code container
const sizeClasses = computed(() => {
    const sizes = {
        small: 'w-32 h-32',
        medium: 'w-48 h-48',
        large: 'w-64 h-64',
    };
    return sizes[props.size] || sizes.medium;
});

// Copy URL to clipboard
const copyUrl = async () => {
    try {
        await navigator.clipboard.writeText(registrationUrl.value);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch (error) {
        console.error('Failed to copy URL:', error);
        alert('Fehler beim Kopieren der URL');
    }
};

// Download QR code in specified format
const downloadQR = (format = 'png') => {
    const downloadUrl = route('trainer.invitations.downloadQR', {
        invitation: props.invitation.id,
        format: format,
    });
    window.location.href = downloadUrl;
};
</script>

<template>
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <!-- QR Code Image -->
        <div class="flex justify-center mb-4">
            <div
                :class="sizeClasses"
                class="bg-white border-2 border-gray-300 rounded-lg p-2 flex items-center justify-center"
            >
                <img
                    v-if="qrCodeUrl"
                    :src="qrCodeUrl"
                    :alt="`QR Code für Einladung ${invitation.invitation_token}`"
                    class="w-full h-full object-contain"
                />
                <div v-else class="text-gray-400 text-center text-sm">
                    <svg
                        class="w-12 h-12 mx-auto mb-2"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"
                        />
                    </svg>
                    QR Code nicht verfügbar
                </div>
            </div>
        </div>

        <!-- Registration URL -->
        <div v-if="showUrl" class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Registrierungs-URL
            </label>
            <div class="flex gap-2">
                <input
                    type="text"
                    :value="registrationUrl"
                    readonly
                    class="flex-1 px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-sm font-mono text-gray-600 cursor-text"
                    @click="($event.target).select()"
                />
                <button
                    @click="copyUrl"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
                    :class="{ 'bg-green-600 hover:bg-green-700': copied }"
                >
                    <svg
                        v-if="!copied"
                        class="w-4 h-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
                        />
                    </svg>
                    <svg
                        v-else
                        class="w-4 h-4"
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
                    <span class="hidden sm:inline">
                        {{ copied ? 'Kopiert!' : 'Kopieren' }}
                    </span>
                </button>
            </div>
        </div>

        <!-- Download Buttons -->
        <div v-if="showDownloads" class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                QR-Code herunterladen
            </label>
            <div class="flex flex-wrap gap-2">
                <button
                    @click="downloadQR('png')"
                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors text-sm"
                >
                    PNG
                </button>
                <button
                    @click="downloadQR('svg')"
                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors text-sm"
                >
                    SVG
                </button>
                <button
                    @click="downloadQR('pdf')"
                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors text-sm"
                >
                    PDF
                </button>
            </div>
        </div>

        <!-- Optional Stats -->
        <div
            v-if="showStats && invitation.statistics"
            class="border-t border-gray-200 pt-4 mt-4"
        >
            <div class="grid grid-cols-2 gap-4 text-center">
                <div>
                    <div class="text-2xl font-bold text-blue-600">
                        {{ invitation.statistics.registered_count }}
                    </div>
                    <div class="text-xs text-gray-600">Registrierungen</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-green-600">
                        {{ invitation.statistics.remaining_slots }}
                    </div>
                    <div class="text-xs text-gray-600">Verfügbar</div>
                </div>
            </div>
        </div>
    </div>
</template>
