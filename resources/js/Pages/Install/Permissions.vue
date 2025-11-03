<template>
    <Layout :app-name="'BasketManager Pro'" :subtitle="$t('permissions_title')" :current-step="3" :language="language">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">
                {{ $t('permissions_title') }}
            </h2>
            <p class="text-gray-600 mb-8">
                {{ $t('permissions_description') }}
            </p>

            <!-- Permissions List -->
            <div class="bg-gray-50 rounded-lg p-4 space-y-3 mb-6">
                <div
                    v-for="(perm, folder) in permissions.permissions"
                    :key="folder"
                    class="flex items-center justify-between py-3 border-b border-gray-200 last:border-0"
                >
                    <div class="flex-1">
                        <div class="font-medium text-gray-900">{{ perm.name }}</div>
                        <div class="text-sm text-gray-600 mt-1">
                            {{ perm.path }}
                        </div>
                        <div v-if="perm.permission" class="text-xs text-gray-500 mt-1">
                            Permission: {{ perm.permission }}
                        </div>
                    </div>
                    <div class="flex-shrink-0 ml-4">
                        <span
                            v-if="perm.status === 'success'"
                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800"
                        >
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            {{ $t('writable') }}
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800"
                        >
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            {{ $t('not_writable') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Status Message -->
            <div v-if="canProceed" class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-green-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span class="text-green-800 font-semibold">{{ $t('all_permissions_ok') }}</span>
                </div>
            </div>

            <div v-else class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <div class="mb-3">
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-red-600 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                        <div>
                            <p class="text-red-800 font-semibold">{{ $t('permissions_issues') }}</p>
                            <div class="mt-3 bg-gray-900 text-gray-100 p-3 rounded font-mono text-sm overflow-x-auto">
                                <div v-for="(perm, folder) in permissions.permissions" :key="folder">
                                    <div v-if="perm.status !== 'success'">
                                        chmod -R 755 {{ perm.path }}<br>
                                        chown -R www-data:www-data {{ perm.path }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="flex justify-between mt-8">
                <Link
                    :href="route('install.requirements')"
                    class="px-6 py-3 border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition-colors"
                >
                    ← {{ $t('back') }}
                </Link>

                <Link
                    v-if="canProceed"
                    :href="route('install.environment')"
                    class="px-6 py-3 bg-orange-600 text-white font-semibold rounded-lg hover:bg-orange-700 transition-colors"
                >
                    {{ $t('continue') }} →
                </Link>

                <button
                    v-else
                    @click="$inertia.reload()"
                    class="px-6 py-3 bg-orange-600 text-white font-semibold rounded-lg hover:bg-orange-700 transition-colors"
                >
                    🔄 {{ $t('retry') }}
                </button>
            </div>
        </div>
    </Layout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import Layout from './Layout.vue';

const props = defineProps({
    permissions: {
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
            permissions_title: 'Ordner-Berechtigungen prüfen',
            permissions_description: 'Die folgenden Verzeichnisse müssen beschreibbar sein.',
            writable: 'Beschreibbar',
            not_writable: 'Nicht beschreibbar',
            all_permissions_ok: 'Alle Berechtigungen korrekt!',
            permissions_issues: 'Einige Ordner sind nicht beschreibbar. Führen Sie die folgenden Befehle aus:',
            back: 'Zurück',
            continue: 'Weiter',
            retry: 'Erneut prüfen'
        },
        en: {
            permissions_title: 'Check Folder Permissions',
            permissions_description: 'The following directories must be writable.',
            writable: 'Writable',
            not_writable: 'Not Writable',
            all_permissions_ok: 'All permissions correct!',
            permissions_issues: 'Some folders are not writable. Run the following commands:',
            back: 'Back',
            continue: 'Continue',
            retry: 'Check Again'
        }
    };
    return translations[props.language]?.[key] || key;
};
</script>
