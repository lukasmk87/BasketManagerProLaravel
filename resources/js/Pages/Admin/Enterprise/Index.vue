<script setup>
import { ref, computed } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    leads: Object,
    stats: Object,
    statuses: Object,
    organizationTypes: Object,
    assignableUsers: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');

const applyFilters = () => {
    router.get(route('admin.enterprise-leads.index'), {
        status: props.filters?.status,
        type: props.filters?.type,
        search: search.value,
    }, {
        preserveState: true,
    });
};

const changeStatus = (status) => {
    router.get(route('admin.enterprise-leads.index'), {
        status: status || '',
        type: props.filters?.type,
        search: search.value,
    }, {
        preserveState: true,
    });
};

const changeType = (type) => {
    router.get(route('admin.enterprise-leads.index'), {
        status: props.filters?.status,
        type: type || '',
        search: search.value,
    }, {
        preserveState: true,
    });
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getStatusBadgeClass = (status) => {
    const classes = {
        new: 'bg-blue-100 text-blue-800',
        contacted: 'bg-yellow-100 text-yellow-800',
        qualified: 'bg-purple-100 text-purple-800',
        proposal: 'bg-orange-100 text-orange-800',
        won: 'bg-green-100 text-green-800',
        lost: 'bg-red-100 text-red-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const getTypeBadgeClass = (type) => {
    const classes = {
        verband: 'bg-indigo-100 text-indigo-800',
        grossverein: 'bg-teal-100 text-teal-800',
        akademie: 'bg-cyan-100 text-cyan-800',
        sonstige: 'bg-gray-100 text-gray-800',
    };
    return classes[type] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <AdminLayout title="Enterprise Leads">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Enterprise Leads
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Stats Cards -->
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4 mb-6">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg p-4">
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.total }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Gesamt</div>
                    </div>
                    <div class="bg-blue-50 dark:bg-blue-900/30 overflow-hidden shadow-sm rounded-lg p-4 cursor-pointer hover:bg-blue-100" @click="changeStatus('new')">
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ stats.new }}</div>
                        <div class="text-sm text-blue-500 dark:text-blue-400">Neu</div>
                    </div>
                    <div class="bg-yellow-50 dark:bg-yellow-900/30 overflow-hidden shadow-sm rounded-lg p-4 cursor-pointer hover:bg-yellow-100" @click="changeStatus('contacted')">
                        <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ stats.contacted }}</div>
                        <div class="text-sm text-yellow-500 dark:text-yellow-400">Kontaktiert</div>
                    </div>
                    <div class="bg-purple-50 dark:bg-purple-900/30 overflow-hidden shadow-sm rounded-lg p-4 cursor-pointer hover:bg-purple-100" @click="changeStatus('qualified')">
                        <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ stats.qualified }}</div>
                        <div class="text-sm text-purple-500 dark:text-purple-400">Qualifiziert</div>
                    </div>
                    <div class="bg-orange-50 dark:bg-orange-900/30 overflow-hidden shadow-sm rounded-lg p-4 cursor-pointer hover:bg-orange-100" @click="changeStatus('proposal')">
                        <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ stats.proposal }}</div>
                        <div class="text-sm text-orange-500 dark:text-orange-400">Angebot</div>
                    </div>
                    <div class="bg-green-50 dark:bg-green-900/30 overflow-hidden shadow-sm rounded-lg p-4 cursor-pointer hover:bg-green-100" @click="changeStatus('won')">
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ stats.won }}</div>
                        <div class="text-sm text-green-500 dark:text-green-400">Gewonnen</div>
                    </div>
                    <div class="bg-red-50 dark:bg-red-900/30 overflow-hidden shadow-sm rounded-lg p-4 cursor-pointer hover:bg-red-100" @click="changeStatus('lost')">
                        <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ stats.lost }}</div>
                        <div class="text-sm text-red-500 dark:text-red-400">Verloren</div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg mb-6">
                    <div class="p-4 flex flex-wrap gap-4 items-center">
                        <!-- Search -->
                        <div class="flex-1 min-w-[200px]">
                            <input
                                type="text"
                                v-model="search"
                                @keyup.enter="applyFilters"
                                placeholder="Suche nach Organisation, Name, E-Mail..."
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-orange-500 focus:ring-orange-500"
                            />
                        </div>

                        <!-- Status Filter -->
                        <select
                            :value="filters?.status || ''"
                            @change="changeStatus($event.target.value)"
                            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-orange-500 focus:ring-orange-500"
                        >
                            <option value="">Alle Status</option>
                            <option v-for="(label, value) in statuses" :key="value" :value="value">
                                {{ label }}
                            </option>
                        </select>

                        <!-- Type Filter -->
                        <select
                            :value="filters?.type || ''"
                            @change="changeType($event.target.value)"
                            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-orange-500 focus:ring-orange-500"
                        >
                            <option value="">Alle Typen</option>
                            <option v-for="(label, value) in organizationTypes" :key="value" :value="value">
                                {{ label }}
                            </option>
                        </select>

                        <button
                            @click="applyFilters"
                            class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2"
                        >
                            Suchen
                        </button>

                        <button
                            @click="() => { search = ''; changeStatus(''); changeType(''); }"
                            class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500"
                        >
                            Zurücksetzen
                        </button>
                    </div>
                </div>

                <!-- Leads Table -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Organisation
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Typ
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Kontakt
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Zugewiesen
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Eingegangen
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Aktionen
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="lead in leads.data" :key="lead.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ lead.organization_name }}
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ lead.email }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="['px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full', getTypeBadgeClass(lead.organization_type)]">
                                            {{ organizationTypes[lead.organization_type] || lead.organization_type }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-gray-100">
                                            {{ lead.contact_name }}
                                        </div>
                                        <div v-if="lead.phone" class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ lead.phone }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="['px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full', getStatusBadgeClass(lead.status)]">
                                            {{ statuses[lead.status] || lead.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ lead.assigned_user?.name || '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ formatDate(lead.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <Link
                                            :href="route('admin.enterprise-leads.show', lead.id)"
                                            class="text-orange-600 hover:text-orange-900 dark:text-orange-400 dark:hover:text-orange-300"
                                        >
                                            Details
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="leads.data.length === 0">
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                        Keine Leads gefunden.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="leads.links && leads.links.length > 3" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                Zeige {{ leads.from }} bis {{ leads.to }} von {{ leads.total }} Einträgen
                            </div>
                            <div class="flex space-x-2">
                                <template v-for="link in leads.links" :key="link.label">
                                    <Link
                                        v-if="link.url"
                                        :href="link.url"
                                        :class="[
                                            'px-3 py-1 rounded-md text-sm',
                                            link.active
                                                ? 'bg-orange-600 text-white'
                                                : 'bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-500'
                                        ]"
                                        v-html="link.label"
                                    />
                                    <span
                                        v-else
                                        class="px-3 py-1 text-sm text-gray-400 dark:text-gray-500"
                                        v-html="link.label"
                                    />
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
