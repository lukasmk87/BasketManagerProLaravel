<template>
    <Layout :app-name="'BasketManager Pro'" :subtitle="$t('database_title')" :current-step="5" :language="language">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">
                {{ $t('database_title') }}
            </h2>
            <p class="text-gray-600 mb-8">
                {{ $t('database_description') }}
            </p>

            <!-- Database Status Banner -->
            <div v-if="!migrationStarted" class="mb-8">
                <div v-if="databaseStatus.connected" class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-green-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <div>
                            <p class="text-green-800 font-semibold">{{ $t('database_connected') }}</p>
                            <p class="text-green-700 text-sm">{{ $t('database_name') }}: <code class="bg-green-100 px-2 py-1 rounded">{{ databaseStatus.database_name }}</code></p>
                        </div>
                    </div>
                </div>

                <div v-else class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-yellow-600 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <div>
                            <p class="text-yellow-800 font-semibold">{{ $t('database_not_connected') }}</p>
                            <p class="text-yellow-700 text-sm mb-2">{{ databaseStatus.message }}</p>
                            <div class="bg-yellow-100 rounded p-3 text-sm text-yellow-800 mt-2">
                                <p class="font-semibold mb-1">{{ $t('database_creation_help') }}</p>
                                <code class="block bg-yellow-200 p-2 rounded mt-1 text-xs">
                                    CREATE DATABASE `{{ databaseStatus.database_name }}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
                                </code>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Migration Status -->
            <div v-if="!migrationStarted" class="text-center py-12">
                <div class="text-6xl mb-6">🗄️</div>
                <p class="text-gray-700 mb-8">
                    {{ $t('database_ready_message') }}
                </p>
                <button
                    @click="runMigrations"
                    :disabled="running || !databaseStatus.connected"
                    class="px-8 py-4 bg-orange-600 text-white text-lg font-semibold rounded-lg hover:bg-orange-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors shadow-lg"
                >
                    <span v-if="!running">🚀 {{ $t('run_migrations') }}</span>
                    <span v-else>
                        <svg class="animate-spin inline-block w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ $t('migrations_running') }}
                    </span>
                </button>
                <p v-if="!databaseStatus.connected" class="text-sm text-gray-500 mt-4">
                    {{ $t('fix_database_first') }}
                </p>
            </div>

            <!-- Migration Output -->
            <div v-else class="space-y-4">
                <!-- Progress Bar -->
                <div v-if="running" class="mb-6">
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-orange-600 h-2.5 rounded-full transition-all duration-300" :style="{ width: progress + '%' }"></div>
                    </div>
                    <p class="text-sm text-gray-600 mt-2 text-center">{{ progress }}%</p>
                </div>

                <!-- Output Console -->
                <div class="bg-gray-900 text-green-400 p-6 rounded-lg font-mono text-sm overflow-auto max-h-96">
                    <div v-for="(line, index) in outputLines" :key="index" class="mb-1">
                        <span class="text-gray-500">{{ index + 1 }}.</span> {{ line }}
                    </div>
                    <div v-if="running" class="mt-2">
                        <span class="animate-pulse">▋</span>
                    </div>
                </div>

                <!-- Success Message -->
                <div v-if="migrationSuccess" class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-green-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <div>
                            <p class="text-green-800 font-semibold">{{ $t('migrations_completed') }}</p>
                            <p class="text-green-700 text-sm">{{ $t('migrations_success_message') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Error Message -->
                <div v-if="migrationError" class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-red-600 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                        <div>
                            <p class="text-red-800 font-semibold">{{ $t('migration_failed') }}</p>
                            <p class="text-red-700 text-sm">{{ errorMessage }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="flex justify-between mt-8 pt-6 border-t border-gray-200">
                <Link
                    :href="route('install.environment')"
                    class="px-6 py-3 border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition-colors"
                >
                    ← {{ $t('back') }}
                </Link>

                <Link
                    v-if="migrationSuccess"
                    :href="route('install.admin')"
                    class="px-6 py-3 bg-orange-600 text-white font-semibold rounded-lg hover:bg-orange-700 transition-colors"
                >
                    {{ $t('continue') }} →
                </Link>

                <button
                    v-else-if="migrationError"
                    @click="runMigrations"
                    :disabled="running"
                    class="px-6 py-3 bg-orange-600 text-white font-semibold rounded-lg hover:bg-orange-700 disabled:bg-gray-400 transition-colors"
                >
                    🔄 {{ $t('retry') }}
                </button>
            </div>
        </div>
    </Layout>
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import Layout from './Layout.vue';
import axios from 'axios';

const props = defineProps({
    language: {
        type: String,
        default: 'de'
    },
    migrationStatus: {
        type: Boolean,
        default: false
    },
    databaseStatus: {
        type: Object,
        default: () => ({
            connected: false,
            message: null,
            database_name: null
        })
    }
});

const migrationStarted = ref(props.migrationStatus);
const running = ref(false);
const progress = ref(0);
const outputLines = ref([]);
const migrationSuccess = ref(props.migrationStatus);
const migrationError = ref(false);
const errorMessage = ref('');

const runMigrations = async () => {
    migrationStarted.value = true;
    running.value = true;
    progress.value = 0;
    outputLines.value = [];
    migrationSuccess.value = false;
    migrationError.value = false;
    errorMessage.value = '';

    // Simulate progress
    const progressInterval = setInterval(() => {
        if (progress.value < 90) {
            progress.value += Math.random() * 10;
        }
    }, 500);

    try {
        outputLines.value.push('Starting database migrations...');
        outputLines.value.push('');

        const response = await axios.post(route('install.database.migrate'));

        if (response.data.success) {
            // Add output lines
            if (response.data.output && Array.isArray(response.data.output)) {
                outputLines.value.push(...response.data.output);
            }

            progress.value = 100;
            outputLines.value.push('');
            outputLines.value.push('✅ All migrations completed successfully!');

            migrationSuccess.value = true;
        } else {
            throw new Error(response.data.message || 'Migration failed');
        }
    } catch (error) {
        progress.value = 100;
        migrationError.value = true;
        errorMessage.value = error.response?.data?.message || error.message || 'An error occurred during migration';

        outputLines.value.push('');
        outputLines.value.push('❌ Migration failed: ' + errorMessage.value);
    } finally {
        clearInterval(progressInterval);
        running.value = false;
    }
};

const $t = (key) => {
    const translations = {
        de: {
            database_title: 'Datenbank einrichten',
            database_description: 'Dieser Schritt erstellt alle notwendigen Datenbanktabellen und Grunddaten.',
            database_ready_message: 'Klicken Sie auf "Migrationen ausführen", um die Datenbank einzurichten.',
            database_connected: 'Datenbankverbindung erfolgreich',
            database_not_connected: 'Datenbankverbindung fehlgeschlagen',
            database_name: 'Datenbank',
            database_creation_help: 'So erstellen Sie die Datenbank:',
            fix_database_first: 'Bitte beheben Sie zuerst die Datenbankprobleme, bevor Sie fortfahren.',
            run_migrations: 'Migrationen ausführen',
            migrations_running: 'Migrationen werden ausgeführt...',
            migrations_completed: 'Migrationen erfolgreich abgeschlossen!',
            migrations_success_message: 'Alle Datenbanktabellen wurden erfolgreich erstellt.',
            migration_failed: 'Migration fehlgeschlagen',
            back: 'Zurück',
            continue: 'Weiter',
            retry: 'Erneut versuchen'
        },
        en: {
            database_title: 'Set Up Database',
            database_description: 'This step will create all necessary database tables and seed initial data.',
            database_ready_message: 'Click "Run Migrations" to set up the database.',
            database_connected: 'Database connection successful',
            database_not_connected: 'Database connection failed',
            database_name: 'Database',
            database_creation_help: 'To create the database:',
            fix_database_first: 'Please fix database issues first before proceeding.',
            run_migrations: 'Run Migrations',
            migrations_running: 'Running migrations...',
            migrations_completed: 'Migrations completed successfully!',
            migrations_success_message: 'All database tables have been created successfully.',
            migration_failed: 'Migration failed',
            back: 'Back',
            continue: 'Continue',
            retry: 'Retry'
        }
    };
    return translations[props.language]?.[key] || key;
};
</script>
