<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ClubAdminLayout from '@/Layouts/ClubAdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { usePricing } from '@/Composables/usePricing';

const { formatPrice: formatPriceWithSettings, getPriceLabel, getSmallBusinessNotice, pricingSettings } = usePricing();

const props = defineProps({
    club: Object,
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
    can_change_plan: {
        type: Boolean,
        default: false,
    },
});

const form = useForm({
    club_subscription_plan_id: props.club.club_subscription_plan_id || '',
});

const submit = () => {
    form.put(route('club-admin.subscriptions.update'), {
        preserveScroll: true,
    });
};

const getPlanColor = (color) => {
    const colors = {
        'blue': 'bg-blue-100 text-blue-800 border-blue-500',
        'green': 'bg-green-100 text-green-800 border-green-500',
        'purple': 'bg-purple-100 text-purple-800 border-purple-500',
        'gray': 'bg-gray-100 text-gray-800 border-gray-500',
    };
    return colors[color] || colors.gray;
};

const formatPrice = (price, currency = 'EUR', interval = 'monthly') => {
    if (price == 0) return 'Kostenlos';
    const formattedPrice = formatPriceWithSettings(price, currency);
    return `${formattedPrice}/${interval === 'monthly' ? 'Monat' : 'Jahr'}`;
};

const priceLabel = computed(() => getPriceLabel());
const smallBusinessNotice = computed(() => getSmallBusinessNotice());

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
    <ClubAdminLayout title="Abo-Verwaltung">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Abo-Verwaltung
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Current Subscription -->
                <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Aktueller Subscription Plan</h3>
                                <p class="text-sm text-gray-500 mt-1">{{ club.name }}</p>
                            </div>
                            <span
                                v-if="current_plan"
                                :class="getPlanColor(current_plan.color)"
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                            >
                                {{ current_plan.name }}
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800"
                            >
                                Kein Plan (Tenant-Features)
                            </span>
                        </div>
                    </div>
                    <div class="p-6">
                        <div v-if="current_plan">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h4 class="text-2xl font-bold text-gray-900">{{ current_plan.name }}</h4>
                                    <p v-if="current_plan.description" class="text-sm text-gray-600 mt-2">{{ current_plan.description }}</p>
                                    <div class="mt-4">
                                        <span class="text-3xl font-bold text-gray-900">
                                            {{ formatPrice(current_plan.price, current_plan.currency, current_plan.billing_interval) }}
                                        </span>
                                        <span v-if="priceLabel" class="block text-sm text-gray-500 mt-1">{{ priceLabel }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Small Business Notice -->
                            <div v-if="smallBusinessNotice" class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                                <p class="text-sm text-amber-800">{{ smallBusinessNotice }}</p>
                            </div>

                            <!-- Features -->
                            <div v-if="current_plan.features && current_plan.features.length > 0" class="mt-6">
                                <div class="text-sm font-medium text-gray-700 mb-3">Features:</div>
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="feature in current_plan.features"
                                        :key="feature"
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                                    >
                                        {{ feature }}
                                    </span>
                                </div>
                            </div>

                            <!-- Limits -->
                            <div v-if="current_plan.limits && Object.keys(current_plan.limits).length > 0" class="mt-6">
                                <div class="text-sm font-medium text-gray-700 mb-3">Limits:</div>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <div v-for="(value, key) in current_plan.limits" :key="key" class="text-sm">
                                        <span class="font-medium text-gray-900">{{ key }}:</span>
                                        <span class="ml-1 text-gray-600">{{ value === -1 ? 'Unlimited' : value }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-600">
                                Kein Club-Plan zugeordnet. Der Club erbt alle Features vom Tenant.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Usage Statistics -->
                <div v-if="subscription_limits && Object.keys(subscription_limits).length > 0" class="bg-white shadow-lg rounded-lg overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Nutzungsstatistik</h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div v-for="(limit, key) in subscription_limits" :key="key" class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="font-medium text-gray-900">{{ key }}</span>
                                    <span class="text-gray-600">
                                        {{ limit.current }} / {{ limit.unlimited ? 'Unlimited' : limit.limit }}
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div
                                        :class="getLimitColor(getLimitPercentage(limit))"
                                        :style="{ width: getLimitPercentage(limit) + '%' }"
                                        class="h-2 rounded-full transition-all"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Change Plan (only for super_admin/admin) -->
                <div v-if="can_change_plan && available_plans.length > 0" class="bg-white shadow-lg rounded-lg overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Plan ändern</h3>
                        <p class="text-sm text-gray-500 mt-1">Wählen Sie einen anderen Subscription Plan für Ihren Club</p>
                    </div>
                    <div class="p-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <InputLabel for="club_subscription_plan_id" value="Neuen Plan auswählen" />
                                <select
                                    id="club_subscription_plan_id"
                                    v-model="form.club_subscription_plan_id"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                >
                                    <option value="">Kein Plan (Tenant-Features werden vererbt)</option>
                                    <option
                                        v-for="plan in available_plans"
                                        :key="plan.id"
                                        :value="plan.id"
                                    >
                                        {{ plan.name }} - {{ formatPrice(plan.price, plan.currency, plan.billing_interval) }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.club_subscription_plan_id" class="mt-2" />
                            </div>

                            <!-- Selected Plan Details -->
                            <div
                                v-if="form.club_subscription_plan_id"
                                class="mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200"
                            >
                                <template v-for="plan in available_plans" :key="plan.id">
                                    <div v-if="plan.id === form.club_subscription_plan_id">
                                        <h4 class="font-semibold text-gray-900 mb-2">{{ plan.name }}</h4>
                                        <p v-if="plan.description" class="text-sm text-gray-600 mb-3">{{ plan.description }}</p>

                                        <!-- Features -->
                                        <div v-if="plan.features && plan.features.length > 0" class="mb-3">
                                            <div class="text-xs font-medium text-gray-700 mb-2">Features:</div>
                                            <div class="flex flex-wrap gap-2">
                                                <span
                                                    v-for="feature in plan.features"
                                                    :key="feature"
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                                                >
                                                    {{ feature }}
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Limits -->
                                        <div v-if="plan.limits && Object.keys(plan.limits).length > 0">
                                            <div class="text-xs font-medium text-gray-700 mb-2">Limits:</div>
                                            <div class="grid grid-cols-2 gap-2 text-sm">
                                                <div v-for="(value, key) in plan.limits" :key="key" class="text-gray-600">
                                                    <span class="font-medium">{{ key }}:</span>
                                                    <span class="ml-1">{{ value === -1 ? 'Unlimited' : value }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- Info Text -->
                            <div class="flex items-start space-x-2 text-sm text-gray-500">
                                <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p>
                                    Wenn kein Plan ausgewählt ist, erbt der Club automatisch alle Features des Tenants.
                                    Mit einem Club-Plan können Sie die verfügbaren Features und Limits individuell einschränken.
                                </p>
                            </div>

                            <div class="flex items-center justify-end space-x-3">
                                <SecondaryButton
                                    type="button"
                                    @click="form.club_subscription_plan_id = club.club_subscription_plan_id || ''"
                                    :disabled="form.processing"
                                >
                                    Zurücksetzen
                                </SecondaryButton>

                                <PrimaryButton
                                    type="submit"
                                    :class="{ 'opacity-25': form.processing }"
                                    :disabled="form.processing"
                                >
                                    Plan ändern
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Available Plans Overview -->
                <div v-if="available_plans.length > 0" class="bg-white shadow-lg rounded-lg overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Verfügbare Pläne</h3>
                        <p class="text-sm text-gray-500 mt-1">Übersicht aller verfügbaren Subscription Pläne</p>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div
                                v-for="plan in available_plans"
                                :key="plan.id"
                                :class="[
                                    'border-2 rounded-lg p-6 transition-all',
                                    current_plan && current_plan.id === plan.id
                                        ? 'border-blue-500 shadow-md'
                                        : 'border-gray-200 hover:border-gray-300'
                                ]"
                            >
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="text-xl font-bold text-gray-900">{{ plan.name }}</h4>
                                    <span
                                        v-if="current_plan && current_plan.id === plan.id"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                                    >
                                        Aktuell
                                    </span>
                                </div>
                                <p v-if="plan.description" class="text-sm text-gray-500 mb-4">{{ plan.description }}</p>
                                <div class="mb-4">
                                    <span class="text-3xl font-bold text-gray-900">
                                        {{ formatPrice(plan.price, plan.currency, plan.billing_interval) }}
                                    </span>
                                    <span v-if="priceLabel" class="block text-sm text-gray-500 mt-1">{{ priceLabel }}</span>
                                </div>
                                <ul v-if="plan.features && plan.features.length > 0" class="space-y-2">
                                    <li
                                        v-for="feature in plan.features"
                                        :key="feature"
                                        class="flex items-start"
                                    >
                                        <svg class="h-5 w-5 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span class="ml-3 text-sm text-gray-700">{{ feature }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ClubAdminLayout>
</template>
