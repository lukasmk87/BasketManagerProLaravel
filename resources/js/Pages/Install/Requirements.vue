<template>
    <Layout :app-name="'BasketManager Pro'" :subtitle="$t('requirements_title')" :current-step="2" :language="language">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">
                {{ $t('requirements_title') }}
            </h2>
            <p class="text-gray-600 mb-8">
                {{ $t('requirements_description') }}
            </p>

            <!-- PHP Version -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">{{ $t('php_version') }}</h3>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center justify-between py-2">
                        <div class="flex-1">
                            <span class="font-medium text-gray-900">{{ requirements.php_version.name }}</span>
                            <div class="text-sm text-gray-600">
                                <span v-if="requirements.php_version.required">Required: {{ requirements.php_version.required }}</span>
                                <span v-if="requirements.php_version.current"> | Current: {{ requirements.php_version.current }}</span>
                            </div>
                        </div>
                        <div class="flex-shrink-0 ml-4">
                            <span
                                v-if="requirements.php_version.status === 'success'"
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800"
                            >
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                OK
                            </span>
                            <span
                                v-else-if="requirements.php_version.status === 'warning'"
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800"
                            >
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                Warning
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800"
                            >
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                                Error
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PHP Extensions -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">{{ $t('php_extensions') }}</h3>
                <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                    <div
                        v-for="(ext, name) in requirements.extensions"
                        :key="name"
                        class="flex items-center justify-between py-2"
                    >
                        <div class="flex-1">
                            <span class="font-medium text-gray-900">{{ ext.name }}</span>
                            <div class="text-sm text-gray-600" v-if="ext.message">
                                <span>{{ ext.message }}</span>
                            </div>
                        </div>
                        <div class="flex-shrink-0 ml-4">
                            <span
                                v-if="ext.status === 'success'"
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800"
                            >
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                OK
                            </span>
                            <span
                                v-else-if="ext.status === 'warning'"
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800"
                            >
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                Warning
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800"
                            >
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                                Error
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PHP Configuration -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">{{ $t('php_configuration') }}</h3>
                <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                    <!-- Memory Limit -->
                    <div class="flex items-center justify-between py-2">
                        <div class="flex-1">
                            <span class="font-medium text-gray-900">{{ requirements.memory_limit.name }}</span>
                            <div class="text-sm text-gray-600">
                                <span v-if="requirements.memory_limit.required">Required: {{ requirements.memory_limit.required }}</span>
                                <span v-if="requirements.memory_limit.current"> | Current: {{ requirements.memory_limit.current }}</span>
                            </div>
                        </div>
                        <div class="flex-shrink-0 ml-4">
                            <span
                                v-if="requirements.memory_limit.status === 'success'"
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800"
                            >
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                OK
                            </span>
                            <span
                                v-else-if="requirements.memory_limit.status === 'warning'"
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800"
                            >
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                Warning
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800"
                            >
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                                Error
                            </span>
                        </div>
                    </div>

                    <!-- Upload Max Filesize -->
                    <div class="flex items-center justify-between py-2">
                        <div class="flex-1">
                            <span class="font-medium text-gray-900">{{ requirements.upload_max_filesize.name }}</span>
                            <div class="text-sm text-gray-600">
                                <span v-if="requirements.upload_max_filesize.required">Required: {{ requirements.upload_max_filesize.required }}</span>
                                <span v-if="requirements.upload_max_filesize.current"> | Current: {{ requirements.upload_max_filesize.current }}</span>
                            </div>
                        </div>
                        <div class="flex-shrink-0 ml-4">
                            <span
                                v-if="requirements.upload_max_filesize.status === 'success'"
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800"
                            >
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                OK
                            </span>
                            <span
                                v-else-if="requirements.upload_max_filesize.status === 'warning'"
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800"
                            >
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                Warning
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800"
                            >
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                                Error
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Message -->
            <div v-if="canProceed" class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-green-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span class="text-green-800 font-semibold">{{ $t('all_requirements_met') }}</span>
                </div>
            </div>

            <div v-else class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-red-600 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                    <span class="text-red-800">{{ $t('requirements_not_met') }}</span>
                </div>
            </div>

            <!-- Navigation -->
            <div class="flex justify-between mt-8">
                <Link
                    :href="route('install.welcome')"
                    class="px-6 py-3 border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition-colors"
                >
                    ← {{ $t('back') }}
                </Link>

                <Link
                    v-if="canProceed"
                    :href="route('install.permissions')"
                    class="px-6 py-3 bg-orange-600 text-white font-semibold rounded-lg hover:bg-orange-700 transition-colors"
                >
                    {{ $t('continue') }} →
                </Link>
            </div>
        </div>
    </Layout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import Layout from './Layout.vue';

const props = defineProps({
    requirements: {
        type: Object,
        required: true
    },
    canProceed: {
        type: Boolean,
        required: true
    },
    language: {
        type: String,
        default: 'de'
    }
});

const $t = (key) => {
    const translations = {
        de: {
            requirements_title: 'Server-Anforderungen prüfen',
            requirements_description: 'Ihr Server muss die folgenden Anforderungen erfüllen, um BasketManager Pro auszuführen.',
            php_version: 'PHP Version',
            php_extensions: 'PHP Erweiterungen',
            php_configuration: 'PHP Konfiguration',
            all_requirements_met: 'Alle Anforderungen erfüllt!',
            requirements_not_met: 'Einige Anforderungen sind nicht erfüllt. Bitte beheben Sie diese Probleme, bevor Sie fortfahren.',
            back: 'Zurück',
            continue: 'Weiter'
        },
        en: {
            requirements_title: 'Check Server Requirements',
            requirements_description: 'Your server must meet the following requirements to run BasketManager Pro.',
            php_version: 'PHP Version',
            php_extensions: 'PHP Extensions',
            php_configuration: 'PHP Configuration',
            all_requirements_met: 'All requirements met!',
            requirements_not_met: 'Some requirements are not met. Please fix these issues before proceeding.',
            back: 'Back',
            continue: 'Continue'
        }
    };
    return translations[props.language]?.[key] || key;
};
</script>
