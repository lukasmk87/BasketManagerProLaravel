<template>
    <div class="min-h-screen bg-gradient-to-br from-orange-50 via-white to-blue-50 flex items-center justify-center px-4">
        <div class="max-w-md w-full">
            <div class="bg-white rounded-lg shadow-lg p-8">
                <!-- Logo/Icon -->
                <div class="text-center mb-8">
                    <div class="text-6xl mb-4">🏀</div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        BasketManager Pro
                    </h1>
                    <p class="text-gray-600">Installation Wizard / Installations-Assistent</p>
                </div>

                <!-- Language Selection -->
                <div class="space-y-4">
                    <h2 class="text-xl font-semibold text-center text-gray-800 mb-4">
                        Select Language / Sprache wählen
                    </h2>

                    <form @submit.prevent="selectLanguage">
                        <div class="space-y-3">
                            <button
                                v-for="(lang, code) in languages"
                                :key="code"
                                type="button"
                                @click="selectedLanguage = code"
                                class="w-full flex items-center justify-between p-4 rounded-lg border-2 transition-all hover:shadow-md"
                                :class="{
                                    'border-orange-500 bg-orange-50': selectedLanguage === code,
                                    'border-gray-200 hover:border-gray-300': selectedLanguage !== code
                                }"
                            >
                                <div class="flex items-center space-x-3">
                                    <span class="text-3xl">{{ lang.flag }}</span>
                                    <span class="text-lg font-medium text-gray-900">{{ lang.name }}</span>
                                </div>
                                <div v-if="selectedLanguage === code" class="text-orange-500">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </div>

                        <button
                            type="submit"
                            :disabled="!selectedLanguage || form.processing"
                            class="w-full mt-6 px-6 py-3 bg-orange-600 text-white font-semibold rounded-lg hover:bg-orange-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors"
                        >
                            <span v-if="!form.processing">Continue / Weiter →</span>
                            <span v-else>Loading...</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-6 text-sm text-gray-500">
                <p>© {{ currentYear }} BasketManager Pro</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    languages: {
        type: Object,
        required: true
    }
});

const selectedLanguage = ref('de'); // Default to German
const currentYear = new Date().getFullYear();

const form = useForm({
    language: 'de'
});

const selectLanguage = () => {
    form.language = selectedLanguage.value;
    form.post(route('install.language'));
};
</script>
