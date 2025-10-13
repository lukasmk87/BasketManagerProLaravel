<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import LimitEditor from '@/Components/Admin/LimitEditor.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    tenant: {
        type: Object,
        required: true,
    },
    currentPlan: {
        type: Object,
        default: null,
    },
    existingCustomization: {
        type: Object,
        default: null,
    },
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'created']);

const form = useForm({
    custom_features: [],
    disabled_features: [],
    custom_limits: {
        users: props.currentPlan?.limits?.users || 100,
        teams: props.currentPlan?.limits?.teams || 10,
        players: props.currentPlan?.limits?.players || 200,
        storage_gb: props.currentPlan?.limits?.storage_gb || 50,
        api_calls_per_hour: props.currentPlan?.limits?.api_calls_per_hour || 1000,
    },
    notes: '',
    effective_from: new Date().toISOString().split('T')[0],
    effective_until: null,
});

// Available features that can be toggled
const availableFeatures = ref([
    { key: 'live_scoring', label: 'Live Scoring', description: 'Echtzeit-Spielverfolgung' },
    { key: 'video_analysis', label: 'Video Analysis', description: 'KI-gestützte Videoanalyse' },
    { key: 'advanced_statistics', label: 'Advanced Statistics', description: 'Erweiterte Statistiken' },
    { key: 'api_access', label: 'API Access', description: 'REST API Zugriff' },
    { key: 'custom_branding', label: 'Custom Branding', description: 'Eigenes Branding' },
    { key: 'priority_support', label: 'Priority Support', description: 'Priorisierter Support' },
    { key: 'advanced_reports', label: 'Advanced Reports', description: 'Erweiterte Reports' },
    { key: 'training_management', label: 'Training Management', description: 'Trainingsverwaltung' },
]);

const isFeatureEnabled = (featureKey) => {
    return form.custom_features.includes(featureKey);
};

const isFeatureDisabled = (featureKey) => {
    return form.disabled_features.includes(featureKey);
};

const toggleFeature = (featureKey, type = 'custom') => {
    if (type === 'custom') {
        const index = form.custom_features.indexOf(featureKey);
        if (index > -1) {
            form.custom_features.splice(index, 1);
        } else {
            form.custom_features.push(featureKey);
            // Remove from disabled if present
            const disabledIndex = form.disabled_features.indexOf(featureKey);
            if (disabledIndex > -1) {
                form.disabled_features.splice(disabledIndex, 1);
            }
        }
    } else {
        const index = form.disabled_features.indexOf(featureKey);
        if (index > -1) {
            form.disabled_features.splice(index, 1);
        } else {
            form.disabled_features.push(featureKey);
            // Remove from custom if present
            const customIndex = form.custom_features.indexOf(featureKey);
            if (customIndex > -1) {
                form.custom_features.splice(customIndex, 1);
            }
        }
    }
};

const hasChanges = computed(() => {
    return form.custom_features.length > 0 ||
           form.disabled_features.length > 0 ||
           form.notes.length > 0 ||
           JSON.stringify(form.custom_limits) !== JSON.stringify(props.currentPlan?.limits || {});
});

const closeModal = () => {
    form.reset();
    form.clearErrors();
    emit('close');
};

const submitCustomization = () => {
    form.post(route('admin.tenants.customization.create', props.tenant.id), {
        onSuccess: () => {
            emit('created');
            closeModal();
        },
    });
};
</script>

<template>
    <Modal :show="show" @close="closeModal" max-width="4xl">
        <div class="p-6">
            <!-- Header -->
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Plan Customization</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Erstelle eine Custom-Konfiguration für {{ tenant.name }}
                </p>
            </div>

            <form @submit.prevent="submitCustomization" class="space-y-6">
                <!-- Custom Features Section -->
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Custom Features</h3>
                            <p class="text-sm text-gray-500">Zusätzliche Features aktivieren</p>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            {{ form.custom_features.length }} ausgewählt
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <button
                            v-for="feature in availableFeatures"
                            :key="'custom-' + feature.key"
                            type="button"
                            @click="toggleFeature(feature.key, 'custom')"
                            class="text-left p-3 rounded-lg border-2 transition-all duration-150"
                            :class="isFeatureEnabled(feature.key)
                                ? 'border-green-500 bg-green-50'
                                : 'border-gray-200 bg-white hover:border-gray-300'"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900">{{ feature.label }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ feature.description }}</p>
                                </div>
                                <svg
                                    v-if="isFeatureEnabled(feature.key)"
                                    class="w-5 h-5 text-green-600 flex-shrink-0 ml-2"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Disabled Features Section -->
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Disabled Features</h3>
                            <p class="text-sm text-gray-500">Features aus dem Plan deaktivieren</p>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            {{ form.disabled_features.length }} deaktiviert
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <button
                            v-for="feature in availableFeatures"
                            :key="'disabled-' + feature.key"
                            type="button"
                            @click="toggleFeature(feature.key, 'disabled')"
                            class="text-left p-3 rounded-lg border-2 transition-all duration-150"
                            :class="isFeatureDisabled(feature.key)
                                ? 'border-red-500 bg-red-50'
                                : 'border-gray-200 bg-white hover:border-gray-300'"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900">{{ feature.label }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ feature.description }}</p>
                                </div>
                                <svg
                                    v-if="isFeatureDisabled(feature.key)"
                                    class="w-5 h-5 text-red-600 flex-shrink-0 ml-2"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Custom Limits Section -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Custom Limits</h3>
                    <div class="space-y-4 bg-gray-50 p-4 rounded-lg">
                        <LimitEditor
                            v-model="form.custom_limits.users"
                            label="Max Users"
                            metric="users"
                            :max="10000"
                            description="Maximale Anzahl an Benutzern"
                            :error="form.errors['custom_limits.users']"
                        />

                        <LimitEditor
                            v-model="form.custom_limits.teams"
                            label="Max Teams"
                            metric="teams"
                            :max="1000"
                            description="Maximale Anzahl an Teams"
                            :error="form.errors['custom_limits.teams']"
                        />

                        <LimitEditor
                            v-model="form.custom_limits.players"
                            label="Max Players"
                            metric="players"
                            :max="10000"
                            description="Maximale Anzahl an Spielern"
                            :error="form.errors['custom_limits.players']"
                        />

                        <LimitEditor
                            v-model="form.custom_limits.storage_gb"
                            label="Storage"
                            metric="storage_gb"
                            unit="GB"
                            :max="5000"
                            description="Maximaler Speicherplatz in GB"
                            :error="form.errors['custom_limits.storage_gb']"
                        />

                        <LimitEditor
                            v-model="form.custom_limits.api_calls_per_hour"
                            label="API Calls"
                            metric="api_calls_per_hour"
                            unit="/h"
                            :max="100000"
                            :step="100"
                            description="API Calls pro Stunde"
                            :error="form.errors['custom_limits.api_calls_per_hour']"
                        />
                    </div>
                </div>

                <!-- Dates & Notes -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Gültig ab" for="effective_from" />
                        <TextInput
                            id="effective_from"
                            v-model="form.effective_from"
                            type="date"
                            class="mt-1 block w-full"
                        />
                        <InputError :message="form.errors.effective_from" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel value="Gültig bis (optional)" for="effective_until" />
                        <TextInput
                            id="effective_until"
                            v-model="form.effective_until"
                            type="date"
                            class="mt-1 block w-full"
                        />
                        <InputError :message="form.errors.effective_until" class="mt-2" />
                    </div>
                </div>

                <div>
                    <InputLabel value="Notizen (optional)" for="notes" />
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        rows="3"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        placeholder="Interne Notizen zur Customization..."
                    ></textarea>
                    <InputError :message="form.errors.notes" class="mt-2" />
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                    <SecondaryButton @click="closeModal" type="button" :disabled="form.processing">
                        Abbrechen
                    </SecondaryButton>
                    <PrimaryButton type="submit" :disabled="!hasChanges || form.processing">
                        <span v-if="form.processing">Wird erstellt...</span>
                        <span v-else>Customization erstellen</span>
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
