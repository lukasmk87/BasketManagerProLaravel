<script setup>
import { ref, computed } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';

const props = defineProps({
    modelValue: String,
    label: {
        type: String,
        default: 'Icon auswählen'
    },
});

const emit = defineEmits(['update:modelValue']);

// Popular Heroicons for Basketball/Sports context
const availableIcons = [
    { name: 'chart-bar', label: 'Statistiken' },
    { name: 'users', label: 'Team' },
    { name: 'clipboard-list', label: 'Training' },
    { name: 'trophy', label: 'Turnier' },
    { name: 'shield-check', label: 'Sicherheit' },
    { name: 'mobile', label: 'Mobile' },
    { name: 'calendar', label: 'Kalender' },
    { name: 'clock', label: 'Zeit' },
    { name: 'map-pin', label: 'Ort' },
    { name: 'star', label: 'Stern' },
    { name: 'heart', label: 'Herz' },
    { name: 'fire', label: 'Feuer' },
    { name: 'lightning-bolt', label: 'Blitz' },
    { name: 'check-circle', label: 'Bestätigt' },
    { name: 'x-circle', label: 'Abgelehnt' },
    { name: 'information-circle', label: 'Info' },
    { name: 'exclamation', label: 'Warnung' },
    { name: 'bell', label: 'Benachrichtigung' },
    { name: 'chat', label: 'Chat' },
    { name: 'mail', label: 'Email' },
    { name: 'phone', label: 'Telefon' },
    { name: 'camera', label: 'Kamera' },
    { name: 'video-camera', label: 'Video' },
    { name: 'photo', label: 'Foto' },
    { name: 'document', label: 'Dokument' },
    { name: 'folder', label: 'Ordner' },
    { name: 'download', label: 'Download' },
    { name: 'upload', label: 'Upload' },
    { name: 'cog', label: 'Einstellungen' },
    { name: 'adjustments', label: 'Anpassungen' },
];

const searchQuery = ref('');
const showPicker = ref(false);

const filteredIcons = computed(() => {
    if (!searchQuery.value) return availableIcons;

    const query = searchQuery.value.toLowerCase();
    return availableIcons.filter(icon =>
        icon.name.toLowerCase().includes(query) ||
        icon.label.toLowerCase().includes(query)
    );
});

const selectIcon = (iconName) => {
    emit('update:modelValue', iconName);
    showPicker.value = false;
};

const getIconPath = (iconName) => {
    // Heroicon SVG paths (simplified, common ones)
    const paths = {
        'chart-bar': 'M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 6a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2zm1 5a1 1 0 00-1 1v2a1 1 0 001 1h16a1 1 0 001-1v-2a1 1 0 00-1-1H4z',
        'users': 'M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z',
        'clipboard-list': 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
        'trophy': 'M4 4a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm12 4v4a4 4 0 01-4 4 4 4 0 01-4-4V8m0 12h8',
        'shield-check': 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
        'mobile': 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z',
        'calendar': 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        'clock': 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        'star': 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
        'check-circle': 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    };

    return paths[iconName] || paths['star']; // Fallback to star
};
</script>

<template>
    <div class="space-y-2">
        <InputLabel :value="label" />

        <!-- Selected Icon Display -->
        <div class="flex items-center space-x-3">
            <button
                type="button"
                @click="showPicker = !showPicker"
                class="flex items-center space-x-3 px-4 py-3 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition"
            >
                <svg v-if="modelValue" class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getIconPath(modelValue)"></path>
                </svg>
                <div v-else class="w-8 h-8 bg-gray-200 rounded flex items-center justify-center text-gray-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <span class="text-sm text-gray-700">
                    {{ modelValue ? availableIcons.find(i => i.name === modelValue)?.label || modelValue : 'Icon auswählen' }}
                </span>
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <button
                v-if="modelValue"
                type="button"
                @click="emit('update:modelValue', null)"
                class="p-2 text-red-600 hover:bg-red-50 rounded-md transition"
                title="Icon entfernen"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Icon Picker Modal -->
        <div v-if="showPicker" class="mt-3 p-4 bg-gray-50 border border-gray-300 rounded-lg">
            <!-- Search -->
            <div class="mb-4">
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Icon suchen..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                />
            </div>

            <!-- Icon Grid -->
            <div class="grid grid-cols-5 gap-3 max-h-64 overflow-y-auto">
                <button
                    v-for="icon in filteredIcons"
                    :key="icon.name"
                    type="button"
                    @click="selectIcon(icon.name)"
                    class="flex flex-col items-center justify-center p-3 bg-white border border-gray-200 rounded-md hover:border-indigo-500 hover:bg-indigo-50 transition group"
                    :class="{ 'border-indigo-600 bg-indigo-50': modelValue === icon.name }"
                    :title="icon.label"
                >
                    <svg class="w-8 h-8 text-gray-600 group-hover:text-indigo-600 transition" :class="{ 'text-indigo-600': modelValue === icon.name }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getIconPath(icon.name)"></path>
                    </svg>
                    <span class="mt-1 text-xs text-gray-500 text-center">{{ icon.label }}</span>
                </button>
            </div>

            <!-- No Results -->
            <div v-if="filteredIcons.length === 0" class="text-center py-8 text-gray-500">
                <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p>Keine Icons gefunden</p>
            </div>

            <!-- Close Button -->
            <div class="mt-4 flex justify-end">
                <button
                    type="button"
                    @click="showPicker = false"
                    class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                    Schließen
                </button>
            </div>
        </div>
    </div>
</template>
