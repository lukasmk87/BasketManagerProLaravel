<script setup>
import { ref, computed } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    lead: Object,
    statuses: Object,
    organizationTypes: Object,
    clubCountOptions: Object,
    teamCountOptions: Object,
    assignableUsers: Array,
});

const form = useForm({
    status: props.lead.status,
    assigned_to: props.lead.assigned_to,
    notes: props.lead.notes || '',
});

const showDeleteModal = ref(false);

const updateLead = () => {
    form.put(route('admin.enterprise-leads.update', props.lead.id), {
        preserveScroll: true,
    });
};

const deleteLead = () => {
    router.delete(route('admin.enterprise-leads.destroy', props.lead.id), {
        onSuccess: () => {
            showDeleteModal.value = false;
        },
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
    <AdminLayout title="Enterprise Lead Details">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link
                        :href="route('admin.enterprise-leads.index')"
                        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        {{ lead.organization_name }}
                    </h2>
                    <span :class="['px-2 py-1 text-xs font-semibold rounded-full', getTypeBadgeClass(lead.organization_type)]">
                        {{ organizationTypes[lead.organization_type] }}
                    </span>
                </div>
                <button
                    @click="showDeleteModal = true"
                    class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                >
                    Lead löschen
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Lead Info -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Contact Information -->
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Kontaktdaten</h3>
                            </div>
                            <div class="p-6">
                                <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Organisation</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ lead.organization_name }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Organisationstyp</dt>
                                        <dd class="mt-1">
                                            <span :class="['px-2 py-1 text-xs font-semibold rounded-full', getTypeBadgeClass(lead.organization_type)]">
                                                {{ organizationTypes[lead.organization_type] }}
                                            </span>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Ansprechpartner</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ lead.contact_name }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">E-Mail</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                            <a :href="'mailto:' + lead.email" class="text-orange-600 hover:text-orange-700">
                                                {{ lead.email }}
                                            </a>
                                        </dd>
                                    </div>
                                    <div v-if="lead.phone">
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Telefon</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                            <a :href="'tel:' + lead.phone" class="text-orange-600 hover:text-orange-700">
                                                {{ lead.phone }}
                                            </a>
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Organization Details -->
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Organisationsdetails</h3>
                            </div>
                            <div class="p-6">
                                <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Anzahl Vereine</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                            {{ clubCountOptions[lead.club_count] || lead.club_count || '-' }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Anzahl Teams</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                            {{ teamCountOptions[lead.team_count] || lead.team_count || '-' }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Newsletter</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                            {{ lead.newsletter_optin ? 'Ja' : 'Nein' }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">DSGVO akzeptiert</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                            {{ lead.gdpr_accepted ? 'Ja' : 'Nein' }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Message -->
                        <div v-if="lead.message" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Nachricht</h3>
                            </div>
                            <div class="p-6">
                                <p class="text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap">{{ lead.message }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar - Status & Management -->
                    <div class="space-y-6">
                        <!-- Status Card -->
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Status</h3>
                            </div>
                            <div class="p-6 space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">
                                        Aktueller Status
                                    </label>
                                    <select
                                        v-model="form.status"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                    >
                                        <option v-for="(label, value) in statuses" :key="value" :value="value">
                                            {{ label }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">
                                        Zugewiesen an
                                    </label>
                                    <select
                                        v-model="form.assigned_to"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                    >
                                        <option :value="null">Nicht zugewiesen</option>
                                        <option v-for="user in assignableUsers" :key="user.id" :value="user.id">
                                            {{ user.name }}
                                        </option>
                                    </select>
                                </div>

                                <button
                                    @click="updateLead"
                                    :disabled="form.processing"
                                    class="w-full px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 disabled:opacity-50"
                                >
                                    {{ form.processing ? 'Speichert...' : 'Status speichern' }}
                                </button>
                            </div>
                        </div>

                        <!-- Timeline -->
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Zeitverlauf</h3>
                            </div>
                            <div class="p-6 space-y-4">
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0 w-2 h-2 mt-2 bg-blue-500 rounded-full"></div>
                                    <div>
                                        <p class="text-sm text-gray-900 dark:text-gray-100">Eingegangen</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(lead.created_at) }}</p>
                                    </div>
                                </div>
                                <div v-if="lead.contacted_at" class="flex items-start space-x-3">
                                    <div class="flex-shrink-0 w-2 h-2 mt-2 bg-yellow-500 rounded-full"></div>
                                    <div>
                                        <p class="text-sm text-gray-900 dark:text-gray-100">Kontaktiert</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(lead.contacted_at) }}</p>
                                    </div>
                                </div>
                                <div v-if="lead.updated_at !== lead.created_at" class="flex items-start space-x-3">
                                    <div class="flex-shrink-0 w-2 h-2 mt-2 bg-gray-400 rounded-full"></div>
                                    <div>
                                        <p class="text-sm text-gray-900 dark:text-gray-100">Zuletzt aktualisiert</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(lead.updated_at) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Interne Notizen</h3>
                            </div>
                            <div class="p-6">
                                <textarea
                                    v-model="form.notes"
                                    rows="5"
                                    placeholder="Notizen zum Lead..."
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                ></textarea>
                                <button
                                    @click="updateLead"
                                    :disabled="form.processing"
                                    class="mt-3 w-full px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500 disabled:opacity-50"
                                >
                                    {{ form.processing ? 'Speichert...' : 'Notizen speichern' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" @click="showDeleteModal = false">
                    <div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75"></div>
                </div>

                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/30 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">
                                    Lead löschen
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Sind Sie sicher, dass Sie diesen Lead löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            @click="deleteLead"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Löschen
                        </button>
                        <button
                            @click="showDeleteModal = false"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Abbrechen
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
