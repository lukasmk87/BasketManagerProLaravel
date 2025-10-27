<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TenantForm from '@/Components/Admin/TenantForm.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { ref } from 'vue';

const props = defineProps({
    tenant: {
        type: Object,
        required: true,
    },
    availablePlans: {
        type: Array,
        default: () => [],
    },
    timezones: {
        type: Object,
        default: () => ({}),
    },
    countries: {
        type: Object,
        default: () => ({}),
    },
});

const form = useForm({
    // Basic Info
    name: props.tenant.name || '',
    slug: props.tenant.slug || '',
    domain: props.tenant.domain || '',
    subdomain: props.tenant.subdomain || '',

    // Billing Information
    billing_email: props.tenant.billing_email || '',
    billing_name: props.tenant.billing_name || '',
    billing_address: props.tenant.billing_address || '',
    vat_number: props.tenant.vat_number || '',
    country_code: props.tenant.country_code || 'DE',

    // Configuration
    timezone: props.tenant.timezone || 'Europe/Berlin',
    locale: props.tenant.locale || 'de',
    currency: props.tenant.currency || 'EUR',

    // Subscription
    subscription_tier: props.tenant.subscription_tier || 'free',
    subscription_plan_id: props.tenant.subscription_plan_id || '',
    trial_ends_at: props.tenant.trial_ends_at || '',

    // Status
    is_active: props.tenant.is_active ?? true,
    is_suspended: props.tenant.is_suspended ?? false,
    suspension_reason: props.tenant.suspension_reason || '',

    // Limits
    max_users: props.tenant.max_users || 10,
    max_teams: props.tenant.max_teams || 5,
    max_storage_gb: props.tenant.max_storage_gb || 5,
    max_api_calls_per_hour: props.tenant.max_api_calls_per_hour || 100,

    // Notes
    notes: props.tenant.notes || '',
});

const showDeleteConfirmation = ref(false);
const deleteForm = useForm({});

const submit = () => {
    form.put(route('admin.tenants.update', props.tenant.id), {
        onSuccess: () => {
            // Redirect will be handled by the controller
        },
    });
};

const deleteTenant = () => {
    if (confirm('Sind Sie sicher, dass Sie diesen Tenant löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.')) {
        deleteForm.delete(route('admin.tenants.destroy', props.tenant.id), {
            onSuccess: () => {
                // Redirect to index page
            },
        });
    }
};
</script>

<template>
    <AdminLayout :title="`Tenant bearbeiten: ${tenant.name}`">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Tenant bearbeiten: {{ tenant.name }}
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Bearbeite die Grunddaten des Tenants
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <Link
                        :href="route('admin.tenants.show', tenant.id)"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50"
                    >
                        ← Zurück
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit">
                    <!-- Tenant Form Component -->
                    <TenantForm
                        :form="form"
                        :available-plans="availablePlans"
                        :timezones="timezones"
                        :countries="countries"
                        mode="edit"
                    />

                    <!-- Submit Buttons -->
                    <div class="mt-6 bg-white shadow rounded-lg p-6">
                        <div class="flex items-center justify-between">
                            <!-- Delete Button (Left) -->
                            <DangerButton
                                type="button"
                                @click="deleteTenant"
                                :class="{ 'opacity-25': deleteForm.processing }"
                                :disabled="deleteForm.processing"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                <span v-if="deleteForm.processing">Lösche...</span>
                                <span v-else>Tenant löschen</span>
                            </DangerButton>

                            <!-- Save Buttons (Right) -->
                            <div class="flex items-center space-x-3">
                                <SecondaryButton
                                    type="button"
                                    @click="$inertia.visit(route('admin.tenants.show', tenant.id))"
                                >
                                    Abbrechen
                                </SecondaryButton>

                                <PrimaryButton
                                    :class="{ 'opacity-25': form.processing }"
                                    :disabled="form.processing"
                                >
                                    <span v-if="form.processing">Speichere...</span>
                                    <span v-else>Änderungen speichern</span>
                                </PrimaryButton>
                            </div>
                        </div>

                        <!-- Processing Indicator -->
                        <div v-if="form.processing || deleteForm.processing" class="mt-4 text-sm text-gray-500 text-right">
                            <svg class="animate-spin inline h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span v-if="form.processing">Änderungen werden gespeichert...</span>
                            <span v-if="deleteForm.processing">Tenant wird gelöscht...</span>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
