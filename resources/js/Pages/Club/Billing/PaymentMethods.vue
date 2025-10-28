<template>
    <Head :title="trans('billing.payment_methods.title')" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        {{ trans('billing.payment_methods.title') }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        {{ trans('billing.payment_methods.manage', { club: club.name }) }}
                    </p>
                </div>

                <!-- Breadcrumb -->
                <nav class="hidden md:flex" aria-label="Breadcrumb">
                    <ol class="flex items-center space-x-2 text-sm">
                        <li>
                            <Link :href="route('dashboard')" class="text-gray-500 hover:text-gray-700">
                                {{ trans('billing.breadcrumbs.dashboard') }}
                            </Link>
                        </li>
                        <li class="flex items-center">
                            <svg class="flex-shrink-0 h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <Link :href="route('club.show', club.id)" class="ml-2 text-gray-500 hover:text-gray-700">
                                {{ club.name }}
                            </Link>
                        </li>
                        <li class="flex items-center">
                            <svg class="flex-shrink-0 h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span class="ml-2 text-gray-700 font-medium">{{ trans('billing.payment_methods.title') }}</span>
                        </li>
                    </ol>
                </nav>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Current Subscription Info (if exists) -->
                <div
                    v-if="club.subscription_status === 'active' && club.subscription_plan"
                    class="mb-6 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-6"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900">{{ trans('billing.payment_methods.active_subscription') }}</h3>
                                <p class="mt-1 text-sm text-gray-700">
                                    {{ club.subscription_plan.name }} •
                                    {{ formatCurrency(club.subscription_plan.price) }}{{ trans('subscription.billing.per_month') }}
                                </p>
                                <p v-if="club.subscription_current_period_end" class="mt-1 text-xs text-gray-600">
                                    {{ trans('billing.payment_methods.next_payment', { date: formatDate(club.subscription_current_period_end) }) }}
                                </p>
                            </div>
                        </div>
                        <Link
                            :href="route('club.subscription.index', club.id)"
                            class="text-sm font-medium text-blue-600 hover:text-blue-700"
                        >
                            {{ trans('billing.navigation.manage_subscription') }}
                        </Link>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                    <div class="p-6">
                        <PaymentMethodList
                            :payment-methods="paymentMethods"
                            :loading="loading"
                            :show-type-filter="true"
                            @add="openAddModal"
                            @set-default="handleSetDefault"
                            @update="openUpdateModal"
                            @delete="handleDelete"
                        />
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Link to Invoices -->
                    <Link
                        :href="route('club.billing.invoices.index', club.id)"
                        class="block p-6 bg-white border border-gray-200 rounded-lg hover:shadow-md transition-shadow"
                    >
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900">{{ trans('billing.cards.invoices_title') }}</h3>
                                <p class="mt-1 text-sm text-gray-600">
                                    {{ trans('billing.cards.invoices_desc') }}
                                </p>
                            </div>
                        </div>
                    </Link>

                    <!-- Link to Subscription -->
                    <Link
                        :href="route('club.subscription.index', club.id)"
                        class="block p-6 bg-white border border-gray-200 rounded-lg hover:shadow-md transition-shadow"
                    >
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900">{{ trans('billing.cards.subscription_title') }}</h3>
                                <p class="mt-1 text-sm text-gray-600">
                                    {{ trans('billing.cards.subscription_desc_alt') }}
                                </p>
                            </div>
                        </div>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Add Payment Method Modal -->
        <AddPaymentMethodModal
            :show="showAddModal"
            :club-id="club.id"
            :club-name="club.name"
            @close="closeAddModal"
            @added="handlePaymentMethodAdded"
        />

        <!-- Update Billing Details Modal -->
        <UpdateBillingDetailsModal
            :show="showUpdateModal"
            :club-id="club.id"
            :payment-method="selectedPaymentMethod"
            @close="closeUpdateModal"
            @updated="handleBillingDetailsUpdated"
        />

        <!-- Toast Notifications -->
        <teleport to="body">
            <div
                v-if="toast.show"
                class="fixed bottom-4 right-4 z-50 max-w-sm"
            >
                <div
                    class="rounded-lg shadow-lg overflow-hidden"
                    :class="toast.type === 'success' ? 'bg-green-500' : 'bg-red-500'"
                >
                    <div class="p-4 flex items-start">
                        <svg
                            v-if="toast.type === 'success'"
                            class="w-6 h-6 text-white flex-shrink-0"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <svg
                            v-else
                            class="w-6 h-6 text-white flex-shrink-0"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-white">{{ toast.message }}</p>
                        </div>
                        <button
                            @click="toast.show = false"
                            class="ml-auto flex-shrink-0 text-white hover:text-gray-200"
                        >
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </teleport>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PaymentMethodList from '@/Components/Club/Billing/PaymentMethodList.vue';
import AddPaymentMethodModal from '@/Components/Club/Billing/AddPaymentMethodModal.vue';
import UpdateBillingDetailsModal from '@/Components/Club/Billing/UpdateBillingDetailsModal.vue';
import { useTranslations } from '@/composables/useTranslations';

const { trans } = useTranslations();

const props = defineProps({
    club: {
        type: Object,
        required: true,
    },
});

const paymentMethods = ref([]);
const loading = ref(true);
const showAddModal = ref(false);
const showUpdateModal = ref(false);
const selectedPaymentMethod = ref(null);
const toast = ref({
    show: false,
    type: 'success', // 'success' | 'error'
    message: '',
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount);
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Intl.DateTimeFormat('de-DE', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    }).format(new Date(dateString));
};

const showToast = (message, type = 'success') => {
    toast.value = {
        show: true,
        type,
        message,
    };

    // Auto-hide after 5 seconds
    setTimeout(() => {
        toast.value.show = false;
    }, 5000);
};

const fetchPaymentMethods = async () => {
    loading.value = true;

    try {
        const response = await axios.get(
            route('club.billing.payment-methods.index', { club: props.club.id })
        );

        paymentMethods.value = response.data.payment_methods || [];
    } catch (error) {
        console.error('Error fetching payment methods:', error);
        showToast(trans('billing.messages.payment_methods_error'), 'error');
    } finally {
        loading.value = false;
    }
};

const openAddModal = () => {
    showAddModal.value = true;
};

const closeAddModal = () => {
    showAddModal.value = false;
};

const handlePaymentMethodAdded = async () => {
    showToast(trans('billing.messages.added'));
    await fetchPaymentMethods();
};

const openUpdateModal = (paymentMethodId) => {
    const pm = paymentMethods.value.find(pm => pm.id === paymentMethodId);
    if (pm) {
        selectedPaymentMethod.value = pm;
        showUpdateModal.value = true;
    }
};

const closeUpdateModal = () => {
    showUpdateModal.value = false;
    selectedPaymentMethod.value = null;
};

const handleBillingDetailsUpdated = async () => {
    showToast(trans('billing.messages.updated'));
    await fetchPaymentMethods();
};

const handleSetDefault = async (paymentMethodId) => {
    try {
        await axios.post(
            route('club.billing.payment-methods.default', {
                club: props.club.id,
                paymentMethod: paymentMethodId,
            })
        );

        showToast(trans('billing.messages.default_set'));
        await fetchPaymentMethods();
    } catch (error) {
        console.error('Error setting default payment method:', error);
        showToast(trans('billing.messages.default_error'), 'error');
    }
};

const handleDelete = async (paymentMethodId) => {
    try {
        await axios.delete(
            route('club.billing.payment-methods.detach', {
                club: props.club.id,
                paymentMethod: paymentMethodId,
            })
        );

        showToast(trans('billing.messages.removed'));
        await fetchPaymentMethods();
    } catch (error) {
        console.error('Error deleting payment method:', error);
        showToast(
            error.response?.data?.message || trans('billing.messages.remove_error'),
            'error'
        );
    }
};

onMounted(() => {
    fetchPaymentMethods();
});
</script>
