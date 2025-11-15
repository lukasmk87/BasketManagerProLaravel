<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import DialogModal from '@/Components/DialogModal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputLabel from '@/Components/InputLabel.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    club: {
        type: Object,
        required: true,
    },
    tenants: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits(['close', 'transferred']);

const currentStep = ref(1);
const previewData = ref(null);
const selectedTenantId = ref('');

const form = useForm({
    target_tenant_id: '',
    confirmed: false,
});

const selectedTenant = computed(() => {
    return props.tenants.find(t => t.id === selectedTenantId.value);
});

const loadPreview = async () => {
    if (!selectedTenantId.value) return;

    try {
        const response = await axios.post(route('admin.clubs.transfer.preview', props.club.id), {
            target_tenant_id: selectedTenantId.value,
        });

        previewData.value = response.data.data;
        currentStep.value = 2;
    } catch (error) {
        console.error('Failed to load preview:', error);
        alert('Fehler beim Laden der Preview: ' + (error.response?.data?.message || error.message));
    }
};

const submitTransfer = () => {
    form.target_tenant_id = selectedTenantId.value;

    form.post(route('admin.clubs.transfer', props.club.id), {
        onSuccess: () => {
            emit('transferred');
            closeModal();
        },
    });
};

const closeModal = () => {
    currentStep.value = 1;
    previewData.value = null;
    selectedTenantId.value = '';
    form.reset();
    emit('close');
};

const goBack = () => {
    if (currentStep.value === 2) {
        currentStep.value = 1;
        previewData.value = null;
    }
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
    <DialogModal :show="show" @close="closeModal" max-width="4xl">
        <template #title>
            Club-Transfer: {{ club.name }}
        </template>

        <template #content>
            <!-- Step 1: Tenant Selection -->
            <div v-if="currentStep === 1" class="space-y-6">
                <p class="text-sm text-gray-600">
                    Wählen Sie den Ziel-Tenant für den Transfer von <strong>{{ club.name }}</strong>
                </p>

                <div>
                    <InputLabel for="target-tenant" value="Ziel-Tenant" />
                    <select
                        id="target-tenant"
                        v-model="selectedTenantId"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                    >
                        <option value="">-- Tenant auswählen --</option>
                        <option v-for="tenant in tenants" :key="tenant.id" :value="tenant.id">
                            {{ tenant.name }}
                        </option>
                    </select>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                    <h4 class="text-sm font-medium text-blue-900 mb-2">Was passiert beim Transfer?</h4>
                    <ul class="text-sm text-blue-700 space-y-1 list-disc list-inside">
                        <li>Der Club wird zum ausgewählten Tenant transferiert</li>
                        <li>Alle Teams und Gym-Halls werden mit übertragen</li>
                        <li>Media-Dateien werden migriert</li>
                        <li>Stripe-Subscription wird gekündigt</li>
                        <li>User-Memberships werden entfernt</li>
                        <li>Ein 24-Stunden-Rollback-Fenster steht zur Verfügung</li>
                    </ul>
                </div>
            </div>

            <!-- Step 2: Impact Analysis -->
            <div v-if="currentStep === 2 && previewData" class="space-y-6">
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Data to Transfer -->
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-green-900 mb-3">
                            🟢 Wird übertragen
                        </h4>
                        <dl class="space-y-1 text-sm">
                            <div v-for="(value, key) in previewData.data_to_transfer" :key="key" class="flex justify-between">
                                <dt class="text-green-700">{{ key }}:</dt>
                                <dd class="font-medium text-green-900">{{ value }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Data to Remove -->
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-red-900 mb-3">
                            🔴 Wird entfernt
                        </h4>
                        <dl class="space-y-1 text-sm">
                            <div v-for="(value, key) in previewData.data_to_remove" :key="key" class="flex justify-between">
                                <dt class="text-red-700">{{ key }}:</dt>
                                <dd class="font-medium text-red-900">{{ value }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Target Capacity -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-blue-900 mb-3">
                            📊 Ziel-Tenant-Kapazität
                        </h4>
                        <dl class="space-y-1 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-blue-700">Aktuelle Clubs:</dt>
                                <dd class="font-medium text-blue-900">{{ previewData.target_tenant_capacity?.current_clubs || 0 }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-blue-700">Max. Clubs:</dt>
                                <dd class="font-medium text-blue-900">
                                    {{ previewData.target_tenant_capacity?.max_clubs || 'Unbegrenzt' }}
                                </dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-blue-700">Kapazität:</dt>
                                <dd class="font-medium" :class="previewData.target_tenant_capacity?.has_capacity ? 'text-green-600' : 'text-red-600'">
                                    {{ previewData.target_tenant_capacity?.has_capacity ? 'Verfügbar' : 'Voll' }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Warnings -->
                <div v-if="previewData.warnings && previewData.warnings.length > 0" class="space-y-3">
                    <h4 class="text-sm font-medium text-gray-900">⚠️ Warnungen</h4>
                    <div
                        v-for="(warning, index) in previewData.warnings"
                        :key="index"
                        :class="['border rounded-md p-3', getWarningClass(warning.severity)]"
                    >
                        <p class="font-medium text-sm">{{ warning.message }}</p>
                        <div v-if="warning.details" class="mt-2 text-xs opacity-90">
                            <pre class="whitespace-pre-wrap">{{ JSON.stringify(warning.details, null, 2) }}</pre>
                        </div>
                    </div>
                </div>

                <!-- Rollback Info -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                    <h4 class="text-sm font-medium text-yellow-900 mb-2">
                        🔄 Rollback-Information
                    </h4>
                    <dl class="text-sm text-yellow-800 space-y-1">
                        <div class="flex justify-between">
                            <dt>Verfügbarkeit:</dt>
                            <dd class="font-medium">{{ previewData.rollback_info?.available ? 'Ja' : 'Nein' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt>Fenster:</dt>
                            <dd class="font-medium">{{ previewData.rollback_info?.window || '-' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt>Läuft ab:</dt>
                            <dd class="font-medium">{{ previewData.rollback_info?.expires_at || '-' }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Confirmation Checkbox -->
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <Checkbox id="confirm-transfer" v-model:checked="form.confirmed" />
                    </div>
                    <div class="ml-3">
                        <label for="confirm-transfer" class="text-sm font-medium text-gray-700">
                            Ich bestätige, dass ich die obigen Informationen gelesen habe und den Transfer durchführen möchte.
                        </label>
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <SecondaryButton @click="goBack" v-if="currentStep === 2">
                Zurück
            </SecondaryButton>

            <SecondaryButton @click="closeModal">
                Abbrechen
            </SecondaryButton>

            <PrimaryButton
                v-if="currentStep === 1"
                @click="loadPreview"
                :disabled="!selectedTenantId"
                class="ml-3"
            >
                Preview anzeigen
            </PrimaryButton>

            <PrimaryButton
                v-if="currentStep === 2"
                @click="submitTransfer"
                :disabled="!form.confirmed || form.processing"
                class="ml-3"
            >
                <span v-if="form.processing">Transfer läuft...</span>
                <span v-else>Transfer starten</span>
            </PrimaryButton>
        </template>
    </DialogModal>
</template>
