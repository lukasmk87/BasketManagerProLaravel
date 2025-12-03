<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import DialogModal from '@/Components/DialogModal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputLabel from '@/Components/InputLabel.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    tenant: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'deleted']);

const currentStep = ref(1);
const previewData = ref(null);
const loading = ref(false);
const selectedTenantId = ref('');
const confirmed = ref(false);

const form = useForm({
    target_tenant_id: '',
});

const selectedTargetTenant = computed(() => {
    if (!previewData.value?.available_target_tenants) return null;
    return previewData.value.available_target_tenants.find(t => t.id === selectedTenantId.value);
});

const requiresTargetTenant = computed(() => {
    return previewData.value?.requires_target_tenant ?? false;
});

const hasNoAvailableTargetTenants = computed(() => {
    if (!requiresTargetTenant.value) return false;
    return !previewData.value?.available_target_tenants?.length;
});

const canProceedToConfirmation = computed(() => {
    if (!requiresTargetTenant.value) return true;
    return !!selectedTenantId.value;
});

const canDelete = computed(() => {
    if (hasNoAvailableTargetTenants.value) return false;
    if (requiresTargetTenant.value && !selectedTenantId.value) return false;
    if (requiresTargetTenant.value && !confirmed.value) return false;
    return true;
});

// Load preview when modal opens
watch(() => props.show, async (isShow) => {
    if (isShow && props.tenant) {
        await loadPreview();
    }
});

const loadPreview = async () => {
    loading.value = true;
    try {
        const response = await axios.get(route('admin.tenants.delete-preview', props.tenant.id));
        previewData.value = response.data;
        currentStep.value = 1;
    } catch (error) {
        console.error('Failed to load deletion preview:', error);
        alert('Fehler beim Laden der Lösch-Vorschau: ' + (error.response?.data?.message || error.message));
    } finally {
        loading.value = false;
    }
};

const goToConfirmation = () => {
    currentStep.value = 2;
};

const goBack = () => {
    if (currentStep.value === 2) {
        currentStep.value = 1;
        confirmed.value = false;
    }
};

const deleteTenant = () => {
    form.target_tenant_id = selectedTenantId.value || null;

    router.delete(route('admin.tenants.destroy', props.tenant.id), {
        data: {
            target_tenant_id: selectedTenantId.value || null,
        },
        onSuccess: () => {
            emit('deleted');
            closeModal();
        },
        onError: (errors) => {
            console.error('Deletion failed:', errors);
        },
    });
};

const closeModal = () => {
    currentStep.value = 1;
    previewData.value = null;
    selectedTenantId.value = '';
    confirmed.value = false;
    form.reset();
    emit('close');
};

const getWarningClass = (severity) => {
    const classes = {
        critical: 'bg-red-100 border-red-300 text-red-800',
        high: 'bg-red-50 border-red-200 text-red-700',
        medium: 'bg-yellow-100 border-yellow-300 text-yellow-800',
        low: 'bg-blue-50 border-blue-200 text-blue-700',
    };
    return classes[severity] || classes.medium;
};
</script>

<template>
    <DialogModal :show="show" @close="closeModal" max-width="3xl">
        <template #title>
            Tenant löschen: {{ tenant?.name }}
        </template>

        <template #content>
            <!-- Loading State -->
            <div v-if="loading" class="flex items-center justify-center py-8">
                <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="ml-3 text-gray-600">Lade Informationen...</span>
            </div>

            <!-- Step 1: Overview & Target Selection -->
            <div v-else-if="previewData && currentStep === 1" class="space-y-6">
                <!-- No Clubs - Simple Deletion -->
                <div v-if="!requiresTargetTenant">
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                        <p class="text-green-800">
                            Dieser Tenant hat keine Clubs und kann direkt gelöscht werden.
                        </p>
                    </div>
                </div>

                <!-- Has Clubs - Need Target Tenant -->
                <div v-else>
                    <!-- Warning about clubs -->
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-yellow-800">
                                    Dieser Tenant enthält {{ previewData.statistics.clubs_count }} Club(s)
                                </p>
                                <p class="mt-1 text-sm text-yellow-700">
                                    Diese müssen vor dem Löschen zu einem anderen Tenant transferiert werden.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- No target tenants available -->
                    <div v-if="hasNoAvailableTargetTenants" class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <p class="text-red-800 font-medium">
                            Es gibt keine aktiven Ziel-Tenants für den Transfer.
                        </p>
                        <p class="text-sm text-red-700 mt-1">
                            Bitte erstellen Sie zuerst einen neuen Tenant oder aktivieren Sie einen bestehenden.
                        </p>
                    </div>

                    <!-- Target tenant selection -->
                    <div v-else>
                        <InputLabel for="target-tenant" value="Ziel-Tenant für Club-Transfer *" />
                        <select
                            id="target-tenant"
                            v-model="selectedTenantId"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        >
                            <option value="">-- Ziel-Tenant auswählen --</option>
                            <option v-for="t in previewData.available_target_tenants" :key="t.id" :value="t.id">
                                {{ t.name }} ({{ t.clubs_count }} Clubs)
                            </option>
                        </select>
                    </div>

                    <!-- Club list -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Zu transferierende Clubs:</h4>
                        <ul class="space-y-2">
                            <li v-for="club in previewData.clubs" :key="club.id" class="flex items-center justify-between text-sm">
                                <span class="text-gray-700">{{ club.name }}</span>
                                <span class="text-gray-500">
                                    {{ club.users_count }} User, {{ club.teams_count }} Teams
                                    <span v-if="club.has_stripe_subscription" class="ml-2 text-yellow-600" title="Hat Stripe-Subscription">
                                        (Stripe)
                                    </span>
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Tenant Statistics -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-900 mb-3">Tenant-Statistiken:</h4>
                    <dl class="grid grid-cols-2 md:grid-cols-5 gap-4 text-sm">
                        <div>
                            <dt class="text-gray-500">Clubs</dt>
                            <dd class="font-medium text-gray-900">{{ previewData.statistics.clubs_count }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Users</dt>
                            <dd class="font-medium text-gray-900">{{ previewData.statistics.users_count }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Teams</dt>
                            <dd class="font-medium text-gray-900">{{ previewData.statistics.teams_count }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Spieler</dt>
                            <dd class="font-medium text-gray-900">{{ previewData.statistics.players_count }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Spiele</dt>
                            <dd class="font-medium text-gray-900">{{ previewData.statistics.games_count }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Warnings -->
                <div v-if="previewData.warnings && previewData.warnings.length > 0" class="space-y-3">
                    <h4 class="text-sm font-medium text-gray-900">Warnungen:</h4>
                    <div
                        v-for="(warning, index) in previewData.warnings"
                        :key="index"
                        :class="['border rounded-md p-3', getWarningClass(warning.severity)]"
                    >
                        <p class="font-medium text-sm">{{ warning.message }}</p>
                    </div>
                </div>
            </div>

            <!-- Step 2: Confirmation -->
            <div v-else-if="previewData && currentStep === 2" class="space-y-6">
                <!-- Summary -->
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <h4 class="font-medium text-red-900 mb-3">Zusammenfassung der Löschung:</h4>
                    <ul class="text-sm text-red-700 space-y-2">
                        <li class="flex items-start">
                            <svg class="h-5 w-5 text-red-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            <span>
                                Tenant <strong>"{{ tenant?.name }}"</strong> wird gelöscht
                            </span>
                        </li>
                        <li v-if="requiresTargetTenant && selectedTargetTenant" class="flex items-start">
                            <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span>
                                {{ previewData.statistics.clubs_count }} Club(s) werden zu <strong>"{{ selectedTargetTenant.name }}"</strong> transferiert
                            </span>
                        </li>
                        <li v-if="previewData.club_users_to_transfer > 0" class="flex items-start">
                            <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span>
                                {{ previewData.club_users_to_transfer }} User werden mit-transferiert
                            </span>
                        </li>
                    </ul>
                </div>

                <!-- Final confirmation checkbox -->
                <div v-if="requiresTargetTenant" class="flex items-start">
                    <div class="flex items-center h-5">
                        <Checkbox id="confirm-deletion" v-model:checked="confirmed" />
                    </div>
                    <div class="ml-3">
                        <label for="confirm-deletion" class="text-sm font-medium text-gray-700">
                            Ich verstehe, dass diese Aktion nicht rückgängig gemacht werden kann und bestätige die Löschung.
                        </label>
                    </div>
                </div>

                <!-- No clubs - simple confirmation text -->
                <div v-else class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                    <p class="text-sm text-yellow-800">
                        <strong>Achtung:</strong> Diese Aktion kann nicht rückgängig gemacht werden.
                    </p>
                </div>
            </div>
        </template>

        <template #footer>
            <!-- Step 1 buttons -->
            <template v-if="currentStep === 1">
                <SecondaryButton @click="closeModal">
                    Abbrechen
                </SecondaryButton>

                <DangerButton
                    v-if="!requiresTargetTenant"
                    @click="goToConfirmation"
                    class="ml-3"
                >
                    Weiter zur Bestätigung
                </DangerButton>

                <PrimaryButton
                    v-else
                    @click="goToConfirmation"
                    :disabled="!canProceedToConfirmation || hasNoAvailableTargetTenants"
                    class="ml-3"
                >
                    Weiter
                </PrimaryButton>
            </template>

            <!-- Step 2 buttons -->
            <template v-if="currentStep === 2">
                <SecondaryButton @click="goBack">
                    Zurück
                </SecondaryButton>

                <SecondaryButton @click="closeModal" class="ml-3">
                    Abbrechen
                </SecondaryButton>

                <DangerButton
                    @click="deleteTenant"
                    :disabled="!canDelete || form.processing"
                    class="ml-3"
                >
                    <span v-if="form.processing">Wird gelöscht...</span>
                    <span v-else>Tenant löschen</span>
                </DangerButton>
            </template>
        </template>
    </DialogModal>
</template>
