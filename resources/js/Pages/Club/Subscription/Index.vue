<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SubscriptionOverview from '@/Components/Club/Subscription/SubscriptionOverview.vue';
import PlanCard from '@/Components/Club/Subscription/PlanCard.vue';
import BillingIntervalToggle from '@/Components/Club/Subscription/BillingIntervalToggle.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import PlanSwapModal from '@/Components/Club/Subscription/PlanSwapModal.vue';
import PaymentMethodSelector from '@/Components/Checkout/PaymentMethodSelector.vue';
import VoucherInput from '@/Components/Club/VoucherInput.vue';
import { useStripe } from '@/Composables/core/useStripe.js';
import { useTranslations } from '@/Composables/core/useTranslations';

const page = usePage();

const { trans } = useTranslations();

const props = defineProps({
    club: {
        type: Object,
        required: true,
    },
    current_plan: {
        type: Object,
        default: null,
    },
    available_plans: {
        type: Array,
        default: () => [],
    },
    subscription_limits: {
        type: Object,
        default: () => ({}),
    },
    has_active_subscription: {
        type: Boolean,
        default: false,
    },
    is_on_trial: {
        type: Boolean,
        default: false,
    },
    trial_days_remaining: {
        type: Number,
        default: 0,
    },
    billing_days_remaining: {
        type: Number,
        default: 0,
    },
    active_voucher: {
        type: Object,
        default: null,
    },
});

const { redirectToCheckout, formatAmount } = useStripe();

// State
const billingInterval = ref('monthly');
const loadingCheckout = ref(false);
const selectedPlanId = ref(null);
const showCancelModal = ref(false);
const cancelImmediately = ref(false);
const showSwapModal = ref(false);
const selectedNewPlan = ref(null);
const currentBillingInterval = ref('monthly'); // Track current subscription's billing interval
const paymentMethod = ref('card');
const invoiceData = ref({});
const loadingInvoiceRequest = ref(false);
const voucherCode = ref('');
const validatedVoucher = ref(null);
const redeemingVoucher = ref(false);

const handleVoucherValidated = (voucher) => {
    validatedVoucher.value = voucher;
};

const handleVoucherCleared = () => {
    validatedVoucher.value = null;
    voucherCode.value = '';
};

const redeemVoucher = async () => {
    if (!validatedVoucher.value || redeemingVoucher.value) return;

    redeemingVoucher.value = true;

    try {
        await axios.post(route('club.vouchers.redeem', props.club.id), {
            code: validatedVoucher.value.code,
        });

        // Reload page to show updated voucher status
        router.reload({
            onFinish: () => {
                redeemingVoucher.value = false;
                voucherCode.value = '';
                validatedVoucher.value = null;
            },
        });
    } catch (error) {
        console.error('Voucher redemption failed:', error);
        alert(error.response?.data?.message || 'Fehler beim Einlösen des Vouchers');
        redeemingVoucher.value = false;
    }
};

// Computed
const sortedPlans = computed(() => {
    return [...props.available_plans].sort((a, b) => {
        // Sort by price (free plans first, then by price ascending)
        if (a.price === 0 && b.price > 0) return -1;
        if (a.price > 0 && b.price === 0) return 1;
        return a.price - b.price;
    });
});

// Methods
const handlePlanSelection = (plan) => {
    // Check if user has active subscription
    if (props.has_active_subscription && props.current_plan) {
        // Open swap modal for proration preview
        selectedNewPlan.value = plan;
        showSwapModal.value = true;
    } else if (paymentMethod.value === 'invoice') {
        // Invoice payment flow
        initiateInvoiceRequest(plan);
    } else {
        // Normal checkout flow for new subscriptions
        initiateCheckout(plan);
    }
};

const initiateCheckout = async (plan) => {
    if (loadingCheckout.value) return;

    selectedPlanId.value = plan.id;
    loadingCheckout.value = true;

    try {
        const response = await axios.post(route('club.checkout', { club: props.club.id }), {
            plan_id: plan.id,
            billing_interval: billingInterval.value,
            success_url: route('club.checkout.success', { club: props.club.id }),
            cancel_url: route('club.checkout.cancel', { club: props.club.id }),
        });

        if (response.data.checkout_url) {
            // Redirect to Stripe Checkout
            redirectToCheckout(response.data.checkout_url);
        } else {
            alert('Fehler: Keine Checkout-URL erhalten');
            loadingCheckout.value = false;
            selectedPlanId.value = null;
        }
    } catch (error) {
        console.error('Checkout initiation failed:', error);
        alert(`Fehler beim Starten des Checkouts: ${error.response?.data?.error || error.message}`);
        loadingCheckout.value = false;
        selectedPlanId.value = null;
    }
};

const initiateInvoiceRequest = async (plan) => {
    if (loadingInvoiceRequest.value) return;

    selectedPlanId.value = plan.id;
    loadingInvoiceRequest.value = true;

    router.post(route('club.checkout.request-invoice', { club: props.club.id }), {
        plan_id: plan.id,
        billing_interval: billingInterval.value,
        ...invoiceData.value,
    }, {
        preserveScroll: true,
        onFinish: () => {
            loadingInvoiceRequest.value = false;
            selectedPlanId.value = null;
        },
    });
};

const handlePlanSwapConfirmed = (data) => {
    showSwapModal.value = false;
    selectedNewPlan.value = null;

    // Reload the page to show updated subscription
    router.reload({
        onSuccess: () => {
            // Show success notification
            alert(`Plan erfolgreich gewechselt zu ${data.plan.name}!`);
        },
    });
};

const openBillingPortal = async () => {
    try {
        const response = await axios.post(route('club.billing-portal', { club: props.club.id }), {
            return_url: route('club.subscription.index', { club: props.club.id }),
        });

        if (response.data.portal_url) {
            window.location.href = response.data.portal_url;
        } else {
            alert('Fehler: Keine Portal-URL erhalten');
        }
    } catch (error) {
        console.error('Billing portal error:', error);
        alert(trans('billing.messages.portal_error', { error: error.response?.data?.error || error.message }));
    }
};

const confirmCancelSubscription = () => {
    showCancelModal.value = true;
};

const cancelSubscription = async () => {
    try {
        // This would be implemented via the billing portal for now
        // In the future, we can add a cancel endpoint
        alert(trans('subscription.messages.use_portal_to_cancel'));
        showCancelModal.value = false;
        await openBillingPortal();
    } catch (error) {
        console.error('Cancel subscription error:', error);
        alert(trans('subscription.messages.cancel_error', { error: error.message }));
    }
};

const isCurrentPlan = (plan) => {
    return props.current_plan && props.current_plan.id === plan.id;
};

const isRecommendedPlan = (plan) => {
    // Mark the middle-tier plan as recommended (or use a plan property)
    const middleIndex = Math.floor(sortedPlans.value.length / 2);
    return sortedPlans.value.indexOf(plan) === middleIndex;
};

const getLimitPercentage = (limit) => {
    if (!limit || limit.unlimited) return 0;
    return Math.min(100, limit.percentage || 0);
};

const getLimitColor = (percentage) => {
    if (percentage < 70) return 'bg-green-500';
    if (percentage < 90) return 'bg-yellow-500';
    return 'bg-red-500';
};
</script>

<template>
    <AppLayout :title="trans('subscription.title')">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ trans('subscription.title') }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                <!-- Current Subscription Overview -->
                <SubscriptionOverview
                    :club="club"
                    :current-plan="current_plan"
                    :has-active-subscription="has_active_subscription"
                    :is-on-trial="is_on_trial"
                    :trial-days-remaining="trial_days_remaining"
                    :billing-days-remaining="billing_days_remaining"
                    @manage-billing="openBillingPortal"
                    @cancel-subscription="confirmCancelSubscription"
                />

                <!-- Active Voucher Display -->
                <div v-if="active_voucher" class="bg-white rounded-lg shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900">Aktiver Voucher</h3>
                                <p class="text-sm text-gray-600">{{ active_voucher.voucher_name }}</p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        {{ active_voucher.discount_label }}
                                    </span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ active_voucher.months_applied }} / {{ active_voucher.duration_months }} Monate
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-500">Bisherige Ersparnis</p>
                            <p class="text-xl font-bold text-green-600">{{ active_voucher.total_discount }} EUR</p>
                        </div>
                    </div>
                    <div v-if="active_voucher.months_remaining > 0" class="mt-4 pt-4 border-t">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">Verbleibende Rabattmonate</span>
                            <span class="font-medium text-gray-900">{{ active_voucher.months_remaining }}</span>
                        </div>
                        <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                            <div
                                class="bg-green-500 h-2 rounded-full"
                                :style="{ width: (active_voucher.months_applied / active_voucher.duration_months * 100) + '%' }"
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- Voucher Input (only if no active voucher) -->
                <div v-else class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Voucher einlösen</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Haben Sie einen Voucher-Code? Geben Sie ihn hier ein, um einen Rabatt auf Ihr Abonnement zu erhalten.
                    </p>
                    <div class="max-w-md">
                        <VoucherInput
                            :club-id="club.id"
                            :plan-id="current_plan?.id"
                            v-model="voucherCode"
                            label="Voucher-Code"
                            placeholder="CODE eingeben..."
                            @voucher-validated="handleVoucherValidated"
                            @voucher-cleared="handleVoucherCleared"
                        />
                        <button
                            v-if="validatedVoucher"
                            @click="redeemVoucher"
                            :disabled="redeemingVoucher"
                            class="mt-4 w-full inline-flex justify-center items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg v-if="redeemingVoucher" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ redeemingVoucher ? 'Wird eingelöst...' : 'Voucher einlösen' }}
                        </button>
                    </div>
                </div>

                <!-- Usage Statistics -->
                <div v-if="subscription_limits && Object.keys(subscription_limits).length > 0" class="bg-white rounded-lg shadow-lg p-6">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">
                            {{ trans('subscription.usage.title') }}
                        </h3>
                        <p class="text-sm text-gray-600">
                            {{ trans('subscription.usage.description') }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div v-for="(limit, key) in subscription_limits" :key="key" class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="font-medium text-gray-900">{{ key }}</span>
                                <span class="text-gray-600">
                                    {{ limit.current }} / {{ limit.unlimited ? '∞' : limit.limit }}
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div
                                    :class="getLimitColor(getLimitPercentage(limit))"
                                    :style="{ width: getLimitPercentage(limit) + '%' }"
                                    class="h-2.5 rounded-full transition-all duration-300"
                                />
                            </div>
                            <p v-if="getLimitPercentage(limit) >= 90" class="text-xs text-red-600">
                                {{ trans('subscription.usage.limit_warning') }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Available Plans -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="mb-8 text-center">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">
                            {{ trans('subscription.plans.available') }}
                        </h3>
                        <p class="text-gray-600 mb-6">
                            {{ trans('subscription.plans.choose') }}
                        </p>

                        <!-- Payment Method Selector (only for new subscriptions) -->
                        <div v-if="!has_active_subscription" class="max-w-xl mx-auto mb-8">
                            <PaymentMethodSelector
                                v-model="paymentMethod"
                                :club="club"
                                :errors="page.props.errors"
                                @invoice-data-change="invoiceData = $event"
                            />
                        </div>

                        <!-- Billing Interval Toggle -->
                        <div class="flex justify-center">
                            <BillingIntervalToggle
                                v-model="billingInterval"
                                :show-savings="true"
                                :savings-percentage="10"
                            />
                        </div>
                    </div>

                    <!-- Plans Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mt-8">
                        <PlanCard
                            v-for="plan in sortedPlans"
                            :key="plan.id"
                            :plan="plan"
                            :billing-interval="billingInterval"
                            :is-current-plan="isCurrentPlan(plan)"
                            :is-recommended="isRecommendedPlan(plan)"
                            :current-plan="current_plan"
                            :has-active-subscription="has_active_subscription"
                            :loading="(loadingCheckout || loadingInvoiceRequest) && selectedPlanId === plan.id"
                            @subscribe="handlePlanSelection"
                            @manage="openBillingPortal"
                        />
                    </div>

                    <!-- Info Box -->
                    <div class="mt-8 rounded-lg border-2 border-blue-200 bg-blue-50 p-4">
                        <div class="flex items-start">
                            <svg class="h-6 w-6 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div class="ml-3">
                                <h4 class="text-sm font-semibold text-blue-900">
                                    {{ trans('subscription.info.important_notes') }}
                                </h4>
                                <ul class="mt-2 text-sm text-blue-800 space-y-1 list-disc list-inside">
                                    <li>{{ trans('subscription.info.yearly_discount') }}</li>
                                    <li>{{ trans('subscription.info.prices_include_vat') }}</li>
                                    <li>{{ trans('subscription.info.can_change_anytime') }}</li>
                                    <li>{{ trans('subscription.info.prorated_refund') }}</li>
                                    <li>{{ trans('subscription.info.secure_payment') }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cancel Subscription Modal -->
        <ConfirmationModal :show="showCancelModal" @close="showCancelModal = false">
            <template #title>
                {{ trans('subscription.cancel_modal.title') }}
            </template>

            <template #content>
                <p class="text-sm text-gray-600 mb-4">
                    {{ trans('subscription.cancel_modal.confirm') }}
                </p>

                <div class="space-y-3">
                    <label class="flex items-start space-x-3 cursor-pointer">
                        <input
                            type="radio"
                            v-model="cancelImmediately"
                            :value="false"
                            class="mt-1"
                        />
                        <div>
                            <div class="font-medium text-gray-900">{{ trans('subscription.cancel_modal.at_period_end') }}</div>
                            <div class="text-sm text-gray-600">
                                {{ trans('subscription.cancel_modal.at_period_end_desc') }}
                            </div>
                        </div>
                    </label>

                    <label class="flex items-start space-x-3 cursor-pointer">
                        <input
                            type="radio"
                            v-model="cancelImmediately"
                            :value="true"
                            class="mt-1"
                        />
                        <div>
                            <div class="font-medium text-gray-900">{{ trans('subscription.cancel_modal.immediately') }}</div>
                            <div class="text-sm text-gray-600">
                                {{ trans('subscription.cancel_modal.immediately_desc') }}
                            </div>
                        </div>
                    </label>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="showCancelModal = false">
                    {{ trans('subscription.common.cancel') }}
                </SecondaryButton>

                <DangerButton
                    class="ml-3"
                    @click="cancelSubscription"
                >
                    {{ trans('subscription.cancel') }}
                </DangerButton>
            </template>
        </ConfirmationModal>

        <!-- Plan Swap Modal -->
        <PlanSwapModal
            v-if="selectedNewPlan"
            :show="showSwapModal"
            :club-id="club.id"
            :current-plan="current_plan"
            :new-plan="selectedNewPlan"
            :billing-interval="billingInterval"
            :current-billing-interval="currentBillingInterval"
            @close="showSwapModal = false"
            @confirmed="handlePlanSwapConfirmed"
        />
    </AppLayout>
</template>
