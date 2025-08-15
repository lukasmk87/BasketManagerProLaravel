<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    system_info: Object,
    database_info: Object,
    cache_info: Object,
    queue_info: Object,
    storage_info: Object,
});

const getEnvironmentBadge = (env) => {
    switch (env) {
        case 'production':
            return 'bg-green-100 text-green-800';
        case 'staging':
            return 'bg-yellow-100 text-yellow-800';
        case 'local':
        case 'development':
            return 'bg-blue-100 text-blue-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const getDebugBadge = (debug) => {
    return debug 
        ? 'bg-red-100 text-red-800' 
        : 'bg-green-100 text-green-800';
};

const getStorageUsageColor = (percentage) => {
    if (percentage >= 90) return 'bg-red-500';
    if (percentage >= 75) return 'bg-yellow-500';
    return 'bg-green-500';
};
</script>

<template>
    <AppLayout title="System-Information">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        System-Information
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Detaillierte System- und Server-Informationen
                    </p>
                </div>
                
                <div class="flex items-center space-x-3">
                    <SecondaryButton :href="route('admin.settings')" as="Link">
                        Zurück zum Admin Panel
                    </SecondaryButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                <!-- System Information -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            System-Information
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-600">PHP Version</span>
                                    <span class="text-sm text-gray-900">{{ system_info.php_version }}</span>
                                </div>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-600">Laravel Version</span>
                                    <span class="text-sm text-gray-900">{{ system_info.laravel_version }}</span>
                                </div>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-600">Umgebung</span>
                                    <span :class="[
                                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                        getEnvironmentBadge(system_info.environment)
                                    ]">
                                        {{ system_info.environment }}
                                    </span>
                                </div>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-600">Debug Modus</span>
                                    <span :class="[
                                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                        getDebugBadge(system_info.debug_mode)
                                    ]">
                                        {{ system_info.debug_mode ? 'An' : 'Aus' }}
                                    </span>
                                </div>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-600">Zeitzone</span>
                                    <span class="text-sm text-gray-900">{{ system_info.timezone }}</span>
                                </div>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-600">Server-Zeit</span>
                                    <span class="text-sm text-gray-900">{{ system_info.server_time }}</span>
                                </div>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-600">Memory Limit</span>
                                    <span class="text-sm text-gray-900">{{ system_info.memory_limit }}</span>
                                </div>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-600">Max Execution Time</span>
                                    <span class="text-sm text-gray-900">{{ system_info.max_execution_time }}s</span>
                                </div>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-600">Max Upload Size</span>
                                    <span class="text-sm text-gray-900">{{ system_info.upload_max_filesize }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Storage Information -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            Speicher-Information
                        </h3>
                        <div class="space-y-4">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-600">Speicher-Verbrauch</span>
                                    <span class="text-sm text-gray-900">
                                        {{ storage_info.used_space }} / {{ storage_info.total_space }}
                                        ({{ storage_info.usage_percentage }}%)
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div 
                                        :class="[
                                            'h-2 rounded-full transition-all duration-300',
                                            getStorageUsageColor(storage_info.usage_percentage)
                                        ]"
                                        :style="`width: ${storage_info.usage_percentage}%`">
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="bg-blue-50 p-4 rounded-lg">
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-blue-600">{{ storage_info.total_space }}</div>
                                        <div class="text-sm text-blue-600">Gesamt</div>
                                    </div>
                                </div>

                                <div class="bg-red-50 p-4 rounded-lg">
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-red-600">{{ storage_info.used_space }}</div>
                                        <div class="text-sm text-red-600">Verwendet</div>
                                    </div>
                                </div>

                                <div class="bg-green-50 p-4 rounded-lg">
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-green-600">{{ storage_info.free_space }}</div>
                                        <div class="text-sm text-green-600">Verfügbar</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Database Information -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                Datenbank
                            </h3>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-gray-600">Verbindung</span>
                                    <span class="text-sm text-gray-900">{{ database_info.connection }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-gray-600">Datenbank</span>
                                    <span class="text-sm text-gray-900">{{ database_info.database_name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-gray-600">Tabellen</span>
                                    <span class="text-sm text-gray-900">{{ database_info.tables_count }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                Cache & Queue
                            </h3>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-gray-600">Cache Driver</span>
                                    <span class="text-sm text-gray-900">{{ cache_info.driver }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-gray-600">Cache Prefix</span>
                                    <span class="text-sm text-gray-900">{{ cache_info.prefix || 'Kein Prefix' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-gray-600">Queue Driver</span>
                                    <span class="text-sm text-gray-900">{{ queue_info.driver }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-gray-600">Queue Connection</span>
                                    <span class="text-sm text-gray-900">{{ queue_info.connection }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Storage Path Information -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            Pfad-Information
                        </h3>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-600">Storage-Pfad</span>
                                <span class="text-sm text-gray-900 font-mono">{{ storage_info.path }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>