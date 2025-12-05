<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    tenant: {
        type: Object,
        required: true,
    },
    availablePlans: {
        type: Array,
        required: true,
    },
    currentPlan: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['updated', 'cancelled']);

const isEditing = ref(false);

const form = useForm({
    subscription_plan_id: props.currentPlan?.id || null,
});

const selectedPlan = computed(() => {
    return props.availablePlans.find(plan => plan.id === form.subscription_plan_id);
});

const hasChanges = computed(() => {
    return form.subscription_plan_id !== props.currentPlan?.id;
});

const planComparison = computed(() => {
    if (!selectedPlan.value || !props.currentPlan) return null;

    return {
        priceChange: selectedPlan.value.price - props.currentPlan.price,
        limitsChange: {
            users: (selectedPlan.value.limits?.users || 0) - (props.currentPlan.limits?.users || 0),
            teams: (selectedPlan.value.limits?.teams || 0) - (props.currentPlan.limits?.teams || 0),
            players: (selectedPlan.value.limits?.players || 0) - (props.currentPlan.limits?.players || 0),
        },
    };
});

const startEditing = () => {
    isEditing.value = true;
    form.subscription_plan_id = props.currentPlan?.id || null;
};

const cancelEditing = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    emit('cancelled');
};

const submitUpdate = () => {
    form.put(route('admin.tenants.subscription.update', { tenant: props.tenant.id }), {
        onSuccess: () => {
            isEditing.value = false;
            emit('updated');
        },
    });
};

const formatPrice = (cents) => {
    return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: 'EUR',
    }).format(cents / 100);
};

const getPlanBadgeClass = (plan) => {
    if (plan.is_featured) return 'bg-yellow-100 text-yellow-800';
    if (plan.is_custom) return 'bg-purple-100 text-purple-800';
    return 'bg-blue-100 text-blue-800';
};
</script>

<template>
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Subscription Plan</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Aktueller Plan für {{ tenant.name }}
                    </p>
                </div>
                <PrimaryButton v-if="!isEditing" @click="startEditing" type="button">
                    Plan ändern
                </PrimaryButton>
            </div>
        </div>

        <!-- Current Plan Display (Not Editing) -->
        <div v-if="!isEditing" class="px-6 py-5">
            <div v-if="currentPlan" class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h4 class="text-lg font-semibold text-gray-900">{{ currentPlan.name }}</h4>
                            <span
                                v-if="currentPlan.is_featured || currentPlan.is_custom"
                                :class="[getPlanBadgeClass(currentPlan), 'px-2 py-0.5 rounded-full text-xs font-medium']"
                            >
                                {{ currentPlan.is_featured ? 'Featured' : 'Custom' }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-500">{{ currentPlan.slug }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-2xl font-bold text-gray-900">{{ formatPrice(currentPlan.price) }}</p>
                    <p class="text-sm text-gray-500">pro Monat</p>
                </div>
            </div>
            <div v-else class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <p class="mt-2 text-sm text-gray-500">Kein Plan zugewiesen</p>
            </div>
        </div>

        <!-- Plan Selection (Editing Mode) -->
        <form v-else @submit.prevent="submitUpdate" class="px-6 py-5 space-y-5">
            <!-- Plan Selector -->
            <div>
                <InputLabel value="Neuen Plan auswählen" for="plan_selector" />
                <select
                    id="plan_selector"
                    v-model="form.subscription_plan_id"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                >
                    <option :value="null">-- Plan auswählen --</option>
                    <option
                        v-for="plan in availablePlans"
                        :key="plan.id"
                        :value="plan.id"
                    >
                        {{ plan.name }} - {{ formatPrice(plan.price) }}/Monat
                        {{ plan.is_featured ? '⭐' : '' }}
                        {{ plan.is_custom ? '🎨' : '' }}
                    </option>
                </select>
                <InputError :message="form.errors.subscription_plan_id" class="mt-2" />
            </div>

            <!-- Plan Comparison -->
            <div v-if="selectedPlan && currentPlan && hasChanges" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-blue-900 mb-3">📊 Änderungen im Überblick</h4>

                <!-- Price Change -->
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-700">Preis:</span>
                        <div class="flex items-center space-x-2">
                            <span class="line-through text-gray-400">{{ formatPrice(currentPlan.price) }}</span>
                            <span class="text-gray-400">→</span>
                            <span class="font-semibold" :class="planComparison.priceChange > 0 ? 'text-red-600' : 'text-green-600'">
                                {{ formatPrice(selectedPlan.price) }}
                            </span>
                            <span
                                class="text-xs font-medium"
                                :class="planComparison.priceChange > 0 ? 'text-red-600' : 'text-green-600'"
                            >
                                {{ planComparison.priceChange > 0 ? '+' : '' }}{{ formatPrice(Math.abs(planComparison.priceChange)) }}
                            </span>
                        </div>
                    </div>

                    <!-- Limits Changes -->
                    <div class="pt-2 border-t border-blue-200">
                        <p class="text-xs font-medium text-gray-500 uppercase mb-2">Limit-Änderungen:</p>
                        <div class="space-y-1">
                            <div v-for="(change, key) in planComparison.limitsChange" :key="key" class="flex justify-between text-xs">
                                <span class="text-gray-600 capitalize">{{ key }}:</span>
                                <span
                                    class="font-medium"
                                    :class="change > 0 ? 'text-green-600' : change < 0 ? 'text-red-600' : 'text-gray-500'"
                                >
                                    {{ change > 0 ? '+' : '' }}{{ change }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Selected Plan Details -->
            <div v-if="selectedPlan" class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <h4 class="text-sm font-semibold text-gray-900 mb-2">{{ selectedPlan.name }}</h4>
                <p v-if="selectedPlan.description" class="text-sm text-gray-600 mb-3">
                    {{ selectedPlan.description }}
                </p>
                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div v-if="selectedPlan.limits">
                        <span class="text-gray-500">Users:</span>
                        <span class="ml-1 font-medium">{{ selectedPlan.limits.users === -1 ? 'Unbegrenzt' : selectedPlan.limits.users }}</span>
                    </div>
                    <div v-if="selectedPlan.limits">
                        <span class="text-gray-500">Teams:</span>
                        <span class="ml-1 font-medium">{{ selectedPlan.limits.teams === -1 ? 'Unbegrenzt' : selectedPlan.limits.teams }}</span>
                    </div>
                </div>
            </div>

            <!-- Warning for Downgrade -->
            <div v-if="currentPlan && selectedPlan && planComparison && planComparison.priceChange < 0" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-yellow-600 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <div>
                        <h5 class="text-sm font-medium text-yellow-900">Downgrade-Warnung</h5>
                        <p class="mt-1 text-xs text-yellow-700">
                            Der Tenant wechselt zu einem günstigeren Plan. Bitte stellen Sie sicher, dass alle Features kompatibel sind.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                <SecondaryButton @click="cancelEditing" type="button" :disabled="form.processing">
                    Abbrechen
                </SecondaryButton>
                <PrimaryButton type="submit" :disabled="!hasChanges || form.processing">
                    <span v-if="form.processing">Wird gespeichert...</span>
                    <span v-else>Plan ändern</span>
                </PrimaryButton>
            </div>
        </form>
    </div>
</template>
