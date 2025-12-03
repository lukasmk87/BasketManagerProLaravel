<script setup>
import { ref, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TenantCard from '@/Components/Admin/TenantCard.vue';
import Pagination from '@/Components/Pagination.vue';
import TextInput from '@/Components/TextInput.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DeleteTenantModal from './Partials/DeleteTenantModal.vue';

const props = defineProps({
    tenants: Object,
    plans: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

// Filter state
const search = ref(props.filters?.search || '');
const selectedPlan = ref(props.filters?.plan || '');
const selectedStatus = ref(props.filters?.status || '');

// Delete modal state
const showDeleteModal = ref(false);
const tenantToDelete = ref(null);

// Watch for changes and update URL
watch([search, selectedPlan, selectedStatus], ([newSearch, newPlan, newStatus]) => {
    router.get(route('admin.tenants.index'), {
        search: newSearch || undefined,
        plan: newPlan || undefined,
        status: newStatus || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, { debounce: 300 });

const clearFilters = () => {
    search.value = '';
    selectedPlan.value = '';
    selectedStatus.value = '';
};

const openDeleteModal = (tenant) => {
    tenantToDelete.value = tenant;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    tenantToDelete.value = null;
};

const handleTenantDeleted = () => {
    closeDeleteModal();
    // Page will be refreshed by Inertia redirect
};
</script>

<template>
    <AdminLayout title="Tenant Management">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Tenant Management
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Verwalte alle Tenants und deren Subscriptions
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <Link
                        :href="route('admin.dashboard')"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50"
                    >
                        ← Dashboard
                    </Link>
                    <Link
                        :href="route('admin.usage.stats')"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50"
                    >
                        📊 Usage Stats
                    </Link>
                    <Link
                        :href="route('admin.tenants.create')"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Neuen Tenant erstellen
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                <!-- Filters -->
                <div class="bg-white shadow rounded-lg p-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Search -->
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Suchen</label>
                            <TextInput
                                id="search"
                                v-model="search"
                                type="text"
                                placeholder="Name oder Domain..."
                                class="block w-full"
                            />
                        </div>

                        <!-- Plan Filter -->
                        <div>
                            <label for="plan" class="block text-sm font-medium text-gray-700 mb-1">Subscription Plan</label>
                            <select
                                id="plan"
                                v-model="selectedPlan"
                                class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            >
                                <option value="">Alle Plans</option>
                                <option v-for="plan in plans" :key="plan.id" :value="plan.id">
                                    {{ plan.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select
                                id="status"
                                v-model="selectedStatus"
                                class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            >
                                <option value="">Alle Status</option>
                                <option value="active">Aktiv</option>
                                <option value="inactive">Inaktiv</option>
                                <option value="suspended">Gesperrt</option>
                                <option value="trial">Im Trial</option>
                            </select>
                        </div>

                        <!-- Clear Filters -->
                        <div class="flex items-end">
                            <SecondaryButton @click="clearFilters" class="w-full">
                                Filter zurücksetzen
                            </SecondaryButton>
                        </div>
                    </div>
                </div>

                <!-- Summary Stats -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-4">
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-indigo-100 rounded-md p-3">
                                    <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Gesamt
                                        </dt>
                                        <dd class="text-2xl font-semibold text-gray-900">
                                            {{ tenants.total || 0 }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
                                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Aktiv
                                        </dt>
                                        <dd class="text-2xl font-semibold text-gray-900">
                                            {{ tenants.data?.filter(t => t.is_active).length || 0 }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-red-100 rounded-md p-3">
                                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Gesperrt
                                        </dt>
                                        <dd class="text-2xl font-semibold text-gray-900">
                                            {{ tenants.data?.filter(t => t.is_suspended).length || 0 }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-blue-100 rounded-md p-3">
                                    <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Im Trial
                                        </dt>
                                        <dd class="text-2xl font-semibold text-gray-900">
                                            {{ tenants.data?.filter(t => t.trial_ends_at && new Date(t.trial_ends_at) > new Date()).length || 0 }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tenants Grid -->
                <div>
                    <div v-if="tenants.data && tenants.data.length > 0" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <TenantCard
                                v-for="tenant in tenants.data"
                                :key="tenant.id"
                                :tenant="tenant"
                                :show-actions="true"
                                @delete="openDeleteModal"
                            />
                        </div>

                        <!-- Pagination -->
                        <div class="bg-white rounded-lg shadow p-4">
                            <Pagination :links="tenants.links" />
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="bg-white shadow rounded-lg">
                        <div class="text-center py-12 px-6">
                            <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            <h3 class="mt-4 text-lg font-medium text-gray-900">Keine Tenants gefunden</h3>
                            <p class="mt-2 text-sm text-gray-500">
                                Versuchen Sie, Ihre Filter anzupassen oder andere Suchbegriffe zu verwenden.
                            </p>
                            <div class="mt-6">
                                <SecondaryButton @click="clearFilters">
                                    Filter zurücksetzen
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Tenant Modal -->
        <DeleteTenantModal
            :show="showDeleteModal"
            :tenant="tenantToDelete"
            @close="closeDeleteModal"
            @deleted="handleTenantDeleted"
        />
    </AdminLayout>
</template>
