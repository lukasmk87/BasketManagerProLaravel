<template>
    <div class="inline-flex items-center space-x-2">
        <span v-if="showLabel" class="text-sm font-medium text-gray-700 dark:text-gray-300">
            Inhalts-Sprache:
        </span>
        <div class="inline-flex rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 p-1">
            <button
                v-for="locale in availableLocales"
                :key="locale.code"
                type="button"
                @click="switchLocale(locale.code)"
                :class="[
                    'px-3 py-1.5 text-sm font-medium rounded-md transition-all duration-200',
                    currentLocale === locale.code
                        ? 'bg-indigo-600 text-white shadow-sm'
                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                ]"
                :title="`Auf ${locale.name} wechseln`"
            >
                <div class="flex items-center space-x-1.5">
                    <span class="text-lg" v-if="locale.flag">{{ locale.flag }}</span>
                    <span>{{ locale.label }}</span>
                    <svg
                        v-if="currentLocale === locale.code"
                        class="w-4 h-4 ml-1"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </button>
        </div>

        <!-- Optional: Content Status Indicator -->
        <div v-if="showStatus && localeStatus" class="flex items-center space-x-2 ml-3">
            <div
                v-for="locale in availableLocales"
                :key="`status-${locale.code}`"
                class="flex items-center space-x-1"
            >
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ locale.label }}:</span>
                <span
                    :class="[
                        'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium',
                        getStatusClass(locale.code)
                    ]"
                >
                    {{ getStatusText(locale.code) }}
                </span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: 'de'
    },
    showLabel: {
        type: Boolean,
        default: true
    },
    showStatus: {
        type: Boolean,
        default: false
    },
    localeStatus: {
        type: Object,
        default: null,
        // Format: { de: 'published', en: 'draft' }
    }
});

const emit = defineEmits(['update:modelValue', 'change']);

const currentLocale = computed(() => props.modelValue);

const availableLocales = [
    {
        code: 'de',
        label: 'DE',
        name: 'Deutsch',
        flag: '🇩🇪'
    },
    {
        code: 'en',
        label: 'EN',
        name: 'English',
        flag: '🇬🇧'
    }
];

const switchLocale = (locale) => {
    if (locale !== currentLocale.value) {
        emit('update:modelValue', locale);
        emit('change', locale);
    }
};

const getStatusClass = (locale) => {
    if (!props.localeStatus || !props.localeStatus[locale]) {
        return 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300';
    }

    const status = props.localeStatus[locale];

    switch (status) {
        case 'published':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        case 'draft':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
        case 'not_configured':
            return 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400';
        default:
            return 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300';
    }
};

const getStatusText = (locale) => {
    if (!props.localeStatus || !props.localeStatus[locale]) {
        return 'Nicht konfiguriert';
    }

    const status = props.localeStatus[locale];

    switch (status) {
        case 'published':
            return 'Veröffentlicht';
        case 'draft':
            return 'Entwurf';
        case 'not_configured':
            return 'Nicht konfiguriert';
        default:
            return 'Unbekannt';
    }
};
</script>

<style scoped>
/* Smooth transitions */
button {
    transition: all 0.2s ease-in-out;
}

/* Focus styles */
button:focus {
    outline: none;
    ring: 2px;
    ring-offset: 2px;
    ring-color: rgb(99 102 241);
}

/* Active state animation */
button:active {
    transform: scale(0.95);
}
</style>
