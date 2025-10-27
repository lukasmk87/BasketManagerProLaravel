<script setup>
import { computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    plan: {
        type: Object,
        required: true,
    },
    billingInterval: {
        type: String,
        default: 'monthly',
        validator: (value) => ['monthly', 'yearly'].includes(value),
    },
    isCurrentPlan: {
        type: Boolean,
        default: false,
    },
    isRecommended: {
        type: Boolean,
        default: false,
    },
    loading: {
        type: Boolean,
        default: false,
    },
    currentPlan: {
        type: Object,
        default: null,
    },
    hasActiveSubscription: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['subscribe', 'manage']);

const price = computed(() => {
    if (props.plan.price === 0) return 'Kostenlos';

    const amount = props.billingInterval === 'yearly'
        ? props.plan.price * 12 * 0.9  // 10% discount for yearly
        : props.plan.price;

    return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: props.plan.currency || 'EUR',
    }).format(amount);
});

const billingPeriod = computed(() => {
    return props.billingInterval === 'yearly' ? 'Jahr' : 'Monat';
});

const planColor = computed(() => {
    const colors = {
        'blue': 'border-blue-500 bg-blue-50',
        'green': 'border-green-500 bg-green-50',
        'purple': 'border-purple-500 bg-purple-50',
        'yellow': 'border-yellow-500 bg-yellow-50',
        'red': 'border-red-500 bg-red-50',
        'gray': 'border-gray-500 bg-gray-50',
    };
    return colors[props.plan.color] || colors.gray;
});

const badgeColor = computed(() => {
    const colors = {
        'blue': 'bg-blue-100 text-blue-800',
        'green': 'bg-green-100 text-green-800',
        'purple': 'bg-purple-100 text-purple-800',
        'yellow': 'bg-yellow-100 text-yellow-800',
        'red': 'bg-red-100 text-red-800',
        'gray': 'bg-gray-100 text-gray-800',
    };
    return colors[props.plan.color] || colors.gray;
});

const isUpgrade = computed(() => {
    if (!props.currentPlan || props.isCurrentPlan || !props.hasActiveSubscription) return false;
    return props.plan.price > props.currentPlan.price;
});

const isDowngrade = computed(() => {
    if (!props.currentPlan || props.isCurrentPlan || !props.hasActiveSubscription) return false;
    return props.plan.price < props.currentPlan.price && props.plan.price > 0;
});

const isSwitchToFree = computed(() => {
    if (!props.currentPlan || props.isCurrentPlan || !props.hasActiveSubscription) return false;
    return props.plan.price === 0;
});

const buttonText = computed(() => {
    if (props.isCurrentPlan) {
        return 'Aktueller Plan';
    }

    if (!props.hasActiveSubscription) {
        return props.plan.price === 0 ? 'Plan auswählen' : 'Jetzt abonnieren';
    }

    // User has active subscription and wants to change
    if (isUpgrade.value) {
        return `↑ Auf ${props.plan.name} upgraden`;
    }

    if (isDowngrade.value) {
        return `↓ Zu ${props.plan.name} wechseln`;
    }

    if (isSwitchToFree.value) {
        return 'Zu kostenlosem Plan wechseln';
    }

    return 'Plan wechseln';
});

const handleAction = () => {
    if (props.isCurrentPlan) {
        emit('manage');
    } else {
        emit('subscribe', props.plan);
    }
};
</script>

<template>
    <div
        class="relative flex flex-col rounded-lg border-2 shadow-sm transition-all hover:shadow-lg"
        :class="[
            isCurrentPlan
                ? 'border-blue-600 ring-2 ring-blue-600 ring-offset-2'
                : 'border-gray-200 hover:border-gray-300',
            isRecommended && !isCurrentPlan && 'ring-2 ring-yellow-500 ring-offset-2'
        ]"
    >
        <!-- Recommended Badge -->
        <div
            v-if="isRecommended && !isCurrentPlan"
            class="absolute -top-4 left-1/2 -translate-x-1/2"
        >
            <span class="inline-flex items-center rounded-full bg-yellow-100 px-4 py-1 text-xs font-semibold text-yellow-800 shadow">
                ⭐ Empfohlen
            </span>
        </div>

        <!-- Current Plan Badge -->
        <div
            v-if="isCurrentPlan"
            class="absolute -top-4 left-1/2 -translate-x-1/2"
        >
            <span class="inline-flex items-center rounded-full bg-blue-100 px-4 py-1 text-xs font-semibold text-blue-800 shadow">
                ✓ Aktueller Plan
            </span>
        </div>

        <div class="flex flex-1 flex-col p-6">
            <!-- Plan Header -->
            <div class="mb-4">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-xl font-bold text-gray-900">
                        {{ plan.name }}
                    </h3>
                    <span
                        v-if="plan.icon"
                        class="text-2xl"
                        v-html="plan.icon"
                    />
                </div>
                <p v-if="plan.description" class="text-sm text-gray-600">
                    {{ plan.description }}
                </p>
            </div>

            <!-- Pricing -->
            <div class="mb-6">
                <div class="flex items-baseline">
                    <span class="text-4xl font-bold text-gray-900">
                        {{ price }}
                    </span>
                    <span v-if="plan.price > 0" class="ml-2 text-sm text-gray-600">
                        / {{ billingPeriod }}
                    </span>
                </div>
                <p v-if="plan.trial_period_days > 0" class="mt-2 text-sm text-green-600 font-medium">
                    {{ plan.trial_period_days }} Tage kostenlos testen
                </p>
            </div>

            <!-- Features -->
            <div v-if="plan.features && plan.features.length > 0" class="mb-6 flex-1">
                <h4 class="mb-3 text-sm font-semibold text-gray-700">
                    Features:
                </h4>
                <ul class="space-y-2">
                    <li
                        v-for="feature in plan.features"
                        :key="feature"
                        class="flex items-start text-sm"
                    >
                        <svg class="mr-3 h-5 w-5 flex-shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span class="text-gray-700">{{ feature }}</span>
                    </li>
                </ul>
            </div>

            <!-- Limits -->
            <div v-if="plan.limits && Object.keys(plan.limits).length > 0" class="mb-6">
                <h4 class="mb-3 text-sm font-semibold text-gray-700">
                    Limits:
                </h4>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div
                        v-for="(value, key) in plan.limits"
                        :key="key"
                        class="flex flex-col rounded bg-gray-50 p-2"
                    >
                        <span class="text-xs text-gray-600">{{ key }}</span>
                        <span class="font-semibold text-gray-900">
                            {{ value === -1 ? 'Unlimited' : value }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Action Button -->
            <div class="mt-auto">
                <PrimaryButton
                    v-if="!isCurrentPlan"
                    type="button"
                    @click="handleAction"
                    :disabled="loading"
                    class="w-full justify-center"
                    :class="[
                        { 'opacity-50 cursor-not-allowed': loading },
                        isUpgrade ? 'bg-green-600 hover:bg-green-700' : '',
                        isDowngrade ? 'bg-blue-600 hover:bg-blue-700' : ''
                    ]"
                >
                    <span v-if="loading">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Wird geladen...
                    </span>
                    <span v-else>
                        {{ buttonText }}
                    </span>
                </PrimaryButton>
                <SecondaryButton
                    v-else
                    type="button"
                    @click="handleAction"
                    :disabled="loading"
                    class="w-full justify-center"
                >
                    Abonnement verwalten
                </SecondaryButton>
            </div>
        </div>
    </div>
</template>
