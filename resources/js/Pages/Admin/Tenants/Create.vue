<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TenantForm from '@/Components/Admin/TenantForm.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
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
    name: '',
    slug: '',
    domain: '',
    subdomain: '',

    // Billing Information
    billing_email: '',
    billing_name: '',
    billing_address: '',
    vat_number: '',
    country_code: 'DE',

    // Configuration
    timezone: 'Europe/Berlin',
    locale: 'de',
    currency: 'EUR',

    // Subscription
    subscription_tier: 'free',
    subscription_plan_id: '',
    trial_ends_at: '',

    // Status
    is_active: true,
    is_suspended: false,
    suspension_reason: '',

    // Limits
    max_users: 10,
    max_teams: 5,
    max_storage_gb: 5,
    max_api_calls_per_hour: 100,

    // Notes
    notes: '',
});

const submit = () => {
    form.post(route('admin.tenants.store'), {
        onSuccess: () => {
            // Redirect will be handled by the controller
        },
    });
};
</script>

<template>
    <AppLayout title="Neuen Tenant erstellen">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Neuen Tenant erstellen
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Erstelle einen neuen Tenant für das Multi-Tenant-System
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <Link
                        :href="route('admin.tenants.index')"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50"
                    >
                        ← Zurück zur Liste
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
                        mode="create"
                    />

                    <!-- Submit Buttons -->
                    <div class="mt-6 bg-white shadow rounded-lg p-6">
                        <div class="flex items-center justify-end space-x-3">
                            <SecondaryButton
                                type="button"
                                @click="$inertia.visit(route('admin.tenants.index'))"
                            >
                                Abbrechen
                            </SecondaryButton>

                            <PrimaryButton
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                            >
                                <span v-if="form.processing">Erstelle...</span>
                                <span v-else>Tenant erstellen</span>
                            </PrimaryButton>
                        </div>

                        <!-- Processing Indicator -->
                        <div v-if="form.processing" class="mt-4 text-sm text-gray-500 text-right">
                            <svg class="animate-spin inline h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Tenant wird erstellt...
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
