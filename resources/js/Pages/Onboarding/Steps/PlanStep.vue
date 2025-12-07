<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import VoucherInput from '@/Components/Club/VoucherInput.vue';
import { usePricing } from '@/Composables/usePricing';

const { formatPrice: formatPriceWithSettings, getPriceLabel, getSmallBusinessNotice, pricingSettings } = usePricing();

const props = defineProps({
    availablePlans: {
        type: Array,
        required: true,
    },
    freePlanId: {
        type: String,
        default: null,
    },
    club: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['complete', 'back']);

const selectedPlanId = ref(props.freePlanId);
const billingInterval = ref('monthly');
const voucherCode = ref('');
const validatedVoucher = ref(null);

const form = useForm({
    plan_id: props.freePlanId,
    billing_interval: 'monthly',
    voucher_code: '',
});

const handleVoucherValidated = (voucher) => {
    validatedVoucher.value = voucher;
    form.voucher_code = voucher.code;
};

const handleVoucherCleared = () => {
    validatedVoucher.value = null;
    form.voucher_code = '';
};

const getDiscountedPrice = (originalPrice) => {
    if (!validatedVoucher.value || parseFloat(originalPrice) === 0) {
        return null;
    }

    if (validatedVoucher.value.type === 'percent') {
        const discount = parseFloat(originalPrice) * (validatedVoucher.value.discount_percent / 100);
        return parseFloat(originalPrice) - discount;
    } else if (validatedVoucher.value.type === 'fixed_amount') {
        const discounted = parseFloat(originalPrice) - validatedVoucher.value.discount_amount;
        return Math.max(0, discounted);
    }

    return null;
};

const sortedPlans = computed(() => {
    return [...props.availablePlans].sort((a, b) => a.price - b.price);
});

const selectedPlan = computed(() => {
    return props.availablePlans.find(p => p.id === selectedPlanId.value);
});

const isFreePlan = computed(() => {
    return selectedPlan.value && parseFloat(selectedPlan.value.price) === 0;
});

const formatPrice = (price, currency = 'EUR') => {
    if (parseFloat(price) === 0) return 'Kostenlos';

    // Calculate base price with yearly discount if applicable
    const netPrice = billingInterval.value === 'yearly'
        ? parseFloat(price) * 12 * 0.9  // 10% discount for yearly
        : parseFloat(price);

    // Use pricing settings for display
    return formatPriceWithSettings(netPrice, currency);
};

const priceLabel = computed(() => getPriceLabel());
const smallBusinessNotice = computed(() => getSmallBusinessNotice());

const submit = () => {
    form.plan_id = selectedPlanId.value;
    form.billing_interval = billingInterval.value;

    form.post(route('onboarding.plan.store'), {
        preserveScroll: true,
        onSuccess: () => {
            emit('complete');
        },
    });
};

const selectPlan = (planId) => {
    selectedPlanId.value = planId;
};

const getPlanColor = (plan) => {
    const colors = {
        'blue': 'border-blue-500 bg-blue-50',
        'green': 'border-green-500 bg-green-50',
        'purple': 'border-purple-500 bg-purple-50',
        'yellow': 'border-yellow-500 bg-yellow-50',
        'orange': 'border-orange-500 bg-orange-50',
        'gray': 'border-gray-500 bg-gray-50',
    };
    return colors[plan.color] || colors.gray;
};

const getFeatureList = (plan) => {
    if (!plan.features) return [];
    if (Array.isArray(plan.features)) return plan.features;
    return Object.values(plan.features);
};
</script>

<template>
    <div class="max-w-4xl mx-auto">
        <div class="text-center mb-8">
            <div class="mx-auto w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-900">Wähle deinen Plan</h2>
            <p class="mt-2 text-gray-600">
                Starte kostenlos und upgrade jederzeit, wenn du mehr brauchst.
            </p>
        </div>

        <!-- Billing Interval Toggle -->
        <div class="flex justify-center mb-6" v-if="sortedPlans.some(p => parseFloat(p.price) > 0)">
            <div class="bg-gray-100 p-1 rounded-lg inline-flex">
                <button
                    type="button"
                    @click="billingInterval = 'monthly'"
                    :class="[
                        'px-4 py-2 text-sm font-medium rounded-md transition-colors',
                        billingInterval === 'monthly'
                            ? 'bg-white text-gray-900 shadow'
                            : 'text-gray-500 hover:text-gray-700'
                    ]"
                >
                    Monatlich
                </button>
                <button
                    type="button"
                    @click="billingInterval = 'yearly'"
                    :class="[
                        'px-4 py-2 text-sm font-medium rounded-md transition-colors',
                        billingInterval === 'yearly'
                            ? 'bg-white text-gray-900 shadow'
                            : 'text-gray-500 hover:text-gray-700'
                    ]"
                >
                    Jährlich
                    <span class="ml-1 text-xs text-green-600 font-bold">-10%</span>
                </button>
            </div>
        </div>

        <!-- Voucher Input -->
        <div v-if="club" class="max-w-md mx-auto mb-8">
            <div class="bg-gray-50 rounded-lg p-4">
                <VoucherInput
                    :club-id="club.id"
                    :plan-id="selectedPlanId"
                    v-model="voucherCode"
                    label="Haben Sie einen Voucher-Code?"
                    placeholder="VOUCHER123..."
                    @voucher-validated="handleVoucherValidated"
                    @voucher-cleared="handleVoucherCleared"
                />
            </div>
        </div>

        <!-- Plan Cards -->
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4 mb-8">
            <div
                v-for="plan in sortedPlans"
                :key="plan.id"
                @click="selectPlan(plan.id)"
                :class="[
                    'relative rounded-lg border-2 p-4 cursor-pointer transition-all',
                    selectedPlanId === plan.id
                        ? 'border-orange-500 ring-2 ring-orange-500 ring-opacity-50'
                        : 'border-gray-200 hover:border-gray-300'
                ]"
            >
                <!-- Selected Badge -->
                <div
                    v-if="selectedPlanId === plan.id"
                    class="absolute -top-3 left-1/2 transform -translate-x-1/2"
                >
                    <span class="bg-orange-500 text-white text-xs font-medium px-3 py-1 rounded-full">
                        Ausgewählt
                    </span>
                </div>

                <!-- Free Badge for Free Plan -->
                <div
                    v-if="parseFloat(plan.price) === 0 && plan.id === freePlanId"
                    class="absolute -top-3 right-4"
                >
                    <span class="bg-green-500 text-white text-xs font-medium px-2 py-1 rounded-full">
                        Empfohlen
                    </span>
                </div>

                <!-- Plan Name -->
                <h3 class="text-lg font-semibold text-gray-900 mb-2">
                    {{ plan.name }}
                </h3>

                <!-- Price -->
                <div class="mb-4">
                    <template v-if="getDiscountedPrice(plan.price) !== null">
                        <span class="text-lg text-gray-400 line-through mr-2">
                            {{ formatPrice(plan.price, plan.currency) }}
                        </span>
                        <span class="text-2xl font-bold text-green-600">
                            {{ formatPrice(getDiscountedPrice(plan.price), plan.currency) }}
                        </span>
                    </template>
                    <span v-else class="text-2xl font-bold text-gray-900">
                        {{ formatPrice(plan.price, plan.currency) }}
                    </span>
                    <span v-if="parseFloat(plan.price) > 0" class="text-gray-500 text-sm">
                        / {{ billingInterval === 'yearly' ? 'Jahr' : 'Monat' }}
                        <span v-if="priceLabel" class="block text-xs">{{ priceLabel }}</span>
                    </span>
                </div>

                <!-- Features -->
                <ul class="space-y-2 text-sm">
                    <li v-for="(feature, index) in getFeatureList(plan).slice(0, 4)" :key="index" class="flex items-start">
                        <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span class="text-gray-600">{{ feature }}</span>
                    </li>
                </ul>

                <!-- Limits -->
                <div v-if="plan.limits" class="mt-4 pt-4 border-t border-gray-100">
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div v-if="plan.limits.max_teams">
                            <span class="text-gray-500">Teams:</span>
                            <span class="font-medium ml-1">{{ plan.limits.max_teams === -1 ? 'Unbegrenzt' : plan.limits.max_teams }}</span>
                        </div>
                        <div v-if="plan.limits.max_players">
                            <span class="text-gray-500">Spieler:</span>
                            <span class="font-medium ml-1">{{ plan.limits.max_players === -1 ? 'Unbegrenzt' : plan.limits.max_players }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Small Business Notice -->
        <div v-if="smallBusinessNotice" class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-lg text-center">
            <p class="text-sm text-amber-800">{{ smallBusinessNotice }}</p>
        </div>

        <!-- Buttons -->
        <div class="flex justify-center space-x-4">
            <SecondaryButton
                type="button"
                class="px-6 py-3"
                @click="emit('back')"
            >
                Zurück
            </SecondaryButton>
            <PrimaryButton
                type="button"
                class="px-8 py-3"
                :disabled="!selectedPlanId || form.processing"
                @click="submit"
            >
                <span v-if="form.processing">Wird verarbeitet...</span>
                <span v-else-if="isFreePlan">Mit Free Plan starten</span>
                <span v-else>Plan auswählen & bezahlen</span>
            </PrimaryButton>
        </div>
    </div>
</template>
