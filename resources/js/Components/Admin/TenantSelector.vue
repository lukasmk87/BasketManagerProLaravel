<template>
    <div v-if="$page.props.superAdmin?.isSuperAdmin" class="relative">
        <!-- Tenant Selector Dropdown Button -->
        <button
            @click="open = !open"
            type="button"
            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700 active:bg-gray-50 dark:active:bg-gray-700 transition ease-in-out duration-150"
        >
            <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>

            <span v-if="selectedTenant" class="font-semibold text-indigo-600">
                {{ selectedTenant.name }}
            </span>
            <span v-else class="text-gray-600 dark:text-gray-400">
                Alle Tenants
            </span>

            <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
            </svg>
        </button>

        <!-- Dropdown Menu -->
        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div
                v-show="open"
                class="absolute right-0 z-50 mt-2 w-64 rounded-md shadow-lg origin-top-right"
                @click.away="open = false"
            >
                <div class="rounded-md ring-1 ring-black ring-opacity-5 bg-white dark:bg-gray-800">
                    <!-- Search Input -->
                    <div class="p-2 border-b border-gray-200 dark:border-gray-700">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Tenant suchen..."
                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-300"
                        />
                    </div>

                    <!-- Tenant List -->
                    <div class="py-1 max-h-60 overflow-y-auto">
                        <!-- "Alle Tenants" Option -->
                        <button
                            @click="clearSelection"
                            class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-150"
                            :class="{
                                'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-semibold': !selectedTenantId,
                                'text-gray-700 dark:text-gray-300': selectedTenantId
                            }"
                        >
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                                <span>Alle Tenants anzeigen</span>
                            </div>
                        </button>

                        <!-- Filtered Tenant Items -->
                        <button
                            v-for="tenant in filteredTenants"
                            :key="tenant.id"
                            @click="selectTenant(tenant)"
                            class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-150"
                            :class="{
                                'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-semibold': selectedTenantId === tenant.id,
                                'text-gray-700 dark:text-gray-300': selectedTenantId !== tenant.id
                            }"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium truncate">
                                        {{ tenant.name }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                        {{ tenant.domain || tenant.subdomain }}
                                    </div>
                                </div>
                                <svg
                                    v-if="selectedTenantId === tenant.id"
                                    class="w-5 h-5 ml-2 text-indigo-600 dark:text-indigo-400 flex-shrink-0"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>

                        <!-- No Results Message -->
                        <div
                            v-if="filteredTenants.length === 0 && search"
                            class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 text-center"
                        >
                            Keine Tenants gefunden
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    selectedTenantId: {
        type: String,
        default: null,
    },
    selectedTenant: {
        type: Object,
        default: null,
    },
    availableTenants: {
        type: Array,
        default: () => [],
    },
});

const open = ref(false);
const search = ref('');

// Filtered tenants based on search
const filteredTenants = computed(() => {
    if (!search.value) {
        return props.availableTenants;
    }

    const searchLower = search.value.toLowerCase();
    return props.availableTenants.filter(tenant =>
        tenant.name.toLowerCase().includes(searchLower) ||
        (tenant.domain && tenant.domain.toLowerCase().includes(searchLower)) ||
        (tenant.subdomain && tenant.subdomain.toLowerCase().includes(searchLower))
    );
});

// Select a tenant
const selectTenant = (tenant) => {
    router.post(route('admin.select-tenant', { tenant: tenant.id }), {}, {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            search.value = '';
        },
    });
};

// Clear tenant selection (show all tenants)
const clearSelection = () => {
    router.delete(route('admin.clear-tenant'), {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            search.value = '';
        },
    });
};
</script>
