<script setup>
import { computed } from 'vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { useTranslations } from '@/Composables/core/useTranslations';

const { trans } = useTranslations();

const props = defineProps({
    club: {
        type: Object,
        required: true,
    },
    currentPlan: {
        type: Object,
        default: null,
    },
    hasActiveSubscription: {
        type: Boolean,
        default: false,
    },
    isOnTrial: {
        type: Boolean,
        default: false,
    },
    trialDaysRemaining: {
        type: Number,
        default: 0,
    },
    billingDaysRemaining: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits(['manage-billing', 'cancel-subscription']);

const subscriptionStatus = computed(() => {
    if (!props.hasActiveSubscription) return null;

    const status = props.club.subscription_status;
    const statusConfig = {
        'active': {
            label: trans('subscription.status.active'),
            color: 'bg-green-100 text-green-800 border-green-500',
            icon: '✓',
        },
        'trial': {
            label: trans('subscription.status.trial'),
            color: 'bg-blue-100 text-blue-800 border-blue-500',
            icon: '⏱️',
        },
        'past_due': {
            label: trans('subscription.status.past_due'),
            color: 'bg-red-100 text-red-800 border-red-500',
            icon: '⚠️',
        },
        'canceled': {
            label: trans('subscription.status.canceled'),
            color: 'bg-gray-100 text-gray-800 border-gray-500',
            icon: '✕',
        },
        'incomplete': {
            label: trans('subscription.status.incomplete'),
            color: 'bg-yellow-100 text-yellow-800 border-yellow-500',
            icon: '⌛',
        },
    };

    return statusConfig[status] || null;
});

const nextBillingDate = computed(() => {
    if (!props.club.subscription_current_period_end) return null;

    return new Date(props.club.subscription_current_period_end).toLocaleDateString('de-DE', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
});

const subscriptionStartDate = computed(() => {
    if (!props.club.subscription_started_at) return null;

    return new Date(props.club.subscription_started_at).toLocaleDateString('de-DE', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
});

const trialEndDate = computed(() => {
    if (!props.club.subscription_trial_ends_at) return null;

    return new Date(props.club.subscription_trial_ends_at).toLocaleDateString('de-DE', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
});

const formatPrice = computed(() => {
    if (!props.currentPlan || props.currentPlan.price === 0) return trans('subscription.plans.free');

    return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: props.currentPlan.currency || 'EUR',
    }).format(props.currentPlan.price);
});
</script>

<template>
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-8 text-white">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <h2 class="text-2xl font-bold mb-2">
                        {{ club.name }}
                    </h2>
                    <p v-if="currentPlan" class="text-blue-100">
                        {{ currentPlan.name }} Plan
                    </p>
                    <p v-else class="text-blue-100">
                        {{ trans('subscription.status.no_subscription') }}
                    </p>
                </div>

                <!-- Status Badge -->
                <div v-if="subscriptionStatus" class="flex-shrink-0">
                    <span
                        class="inline-flex items-center rounded-full px-4 py-2 text-sm font-semibold border-2"
                        :class="subscriptionStatus.color"
                    >
                        <span class="mr-2">{{ subscriptionStatus.icon }}</span>
                        {{ subscriptionStatus.label }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Subscription Details -->
        <div class="p-6">
            <div v-if="hasActiveSubscription && currentPlan" class="space-y-6">
                <!-- Trial Period Warning -->
                <div
                    v-if="isOnTrial"
                    class="rounded-lg border-2 border-blue-500 bg-blue-50 p-4"
                >
                    <div class="flex items-start">
                        <svg class="h-6 w-6 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="ml-3">
                            <h3 class="text-sm font-semibold text-blue-800">{{ trans('subscription.trial.running') }}</h3>
                            <p class="mt-1 text-sm text-blue-700">
                                {{ trans('subscription.trial.ends', { date: trialEndDate }) }}
                                <span v-if="trialDaysRemaining > 0" class="font-medium">
                                    ({{ trans('subscription.trial.days_remaining', { days: trialDaysRemaining, unit: trialDaysRemaining === 1 ? trans('subscription.common.day') : trans('subscription.common.days') }) }})
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Current Plan Info -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Price -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="text-sm text-gray-600 mb-1">{{ trans('subscription.billing.price') }}</div>
                        <div class="text-2xl font-bold text-gray-900">
                            {{ formatPrice }}
                            <span v-if="currentPlan.price > 0" class="text-sm font-normal text-gray-600">
                                {{ trans('subscription.billing.per_month') }}
                            </span>
                        </div>
                    </div>

                    <!-- Start Date -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="text-sm text-gray-600 mb-1">{{ trans('subscription.billing.start_date') }}</div>
                        <div class="text-lg font-semibold text-gray-900">
                            {{ subscriptionStartDate || trans('subscription.common.not_available') }}
                        </div>
                    </div>

                    <!-- Next Billing -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="text-sm text-gray-600 mb-1">
                            {{ club.subscription_ends_at ? trans('subscription.billing.ends_at') : trans('subscription.billing.next_billing') }}
                        </div>
                        <div class="text-lg font-semibold text-gray-900">
                            {{ nextBillingDate || trans('subscription.common.not_available') }}
                        </div>
                        <div v-if="billingDaysRemaining > 0 && !club.subscription_ends_at" class="text-xs text-gray-600 mt-1">
                            {{ trans('subscription.trial.days_remaining', { days: billingDaysRemaining, unit: billingDaysRemaining === 1 ? trans('subscription.common.day') : trans('subscription.common.days_dative') }) }}
                        </div>
                    </div>
                </div>

                <!-- Stripe Customer ID (for debugging) -->
                <div v-if="club.stripe_customer_id" class="text-xs text-gray-500">
                    Stripe Customer ID: {{ club.stripe_customer_id }}
                </div>

                <!-- Actions -->
                <div class="flex flex-wrap gap-3 pt-4 border-t border-gray-200">
                    <SecondaryButton
                        type="button"
                        @click="emit('manage-billing')"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        {{ trans('subscription.portal.open') }}
                    </SecondaryButton>

                    <DangerButton
                        v-if="club.subscription_status !== 'canceled'"
                        type="button"
                        @click="emit('cancel-subscription')"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        {{ trans('subscription.cancel') }}
                    </DangerButton>
                </div>
            </div>

            <!-- No Subscription -->
            <div v-else class="text-center py-12">
                <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">
                    {{ trans('subscription.status.no_subscription') }}
                </h3>
                <p class="text-gray-600 max-w-md mx-auto">
                    {{ trans('subscription.messages.no_plans') }}
                </p>
            </div>
        </div>
    </div>
</template>
