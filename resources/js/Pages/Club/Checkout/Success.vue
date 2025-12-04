<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useTranslations } from '@/Composables/core/useTranslations';

const { trans } = useTranslations();

const props = defineProps({
    club: {
        type: Object,
        required: true,
    },
    session_id: {
        type: String,
        default: null,
    },
    message: {
        type: String,
        default: null,
    },
});
</script>

<template>
    <AppLayout :title="trans('checkout.success.title')">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ trans('checkout.success.title') }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <!-- Success Header -->
                    <div class="bg-gradient-to-r from-green-500 to-green-600 px-6 py-12 text-center text-white">
                        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-white bg-opacity-20 mb-4">
                            <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <h1 class="text-3xl font-bold mb-2">
                            {{ trans('checkout.success.congratulations') }}
                        </h1>
                        <p class="text-xl text-green-100">
                            {{ message || trans('checkout.success.default_message') }}
                        </p>
                    </div>

                    <!-- Success Content -->
                    <div class="p-8">
                        <div class="space-y-6">
                            <!-- Club Info -->
                            <div class="rounded-lg bg-gray-50 p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                    {{ trans('checkout.success.subscription_details') }}
                                </h3>
                                <div class="space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">{{ trans('checkout.success.club') }}:</span>
                                        <span class="font-medium text-gray-900">{{ club.name }}</span>
                                    </div>
                                    <div v-if="club.subscriptionPlan" class="flex justify-between">
                                        <span class="text-gray-600">{{ trans('checkout.success.plan') }}:</span>
                                        <span class="font-medium text-gray-900">{{ club.subscriptionPlan.name }}</span>
                                    </div>
                                    <div v-if="club.subscription_status" class="flex justify-between">
                                        <span class="text-gray-600">{{ trans('checkout.success.status') }}:</span>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            {{ club.subscription_status === 'active' ? trans('subscription.status.active') : club.subscription_status }}
                                        </span>
                                    </div>
                                    <div v-if="session_id" class="flex justify-between text-sm">
                                        <span class="text-gray-500">{{ trans('checkout.success.session_id') }}:</span>
                                        <span class="font-mono text-gray-700">{{ session_id }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Next Steps -->
                            <div class="rounded-lg border-2 border-blue-200 bg-blue-50 p-6">
                                <h3 class="text-lg font-semibold text-blue-900 mb-3">
                                    {{ trans('checkout.success.next_steps') }}
                                </h3>
                                <ul class="space-y-2 text-sm text-blue-800">
                                    <li class="flex items-start">
                                        <svg class="w-5 h-5 text-blue-600 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                        <span>{{ trans('checkout.success.step_email') }}</span>
                                    </li>
                                    <li class="flex items-start">
                                        <svg class="w-5 h-5 text-blue-600 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                        <span>{{ trans('checkout.success.step_features') }}</span>
                                    </li>
                                    <li class="flex items-start">
                                        <svg class="w-5 h-5 text-blue-600 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                        <span>{{ trans('checkout.success.step_manage') }}</span>
                                    </li>
                                    <li class="flex items-start">
                                        <svg class="w-5 h-5 text-blue-600 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                        <span>{{ trans('checkout.success.step_invoices') }}</span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Actions -->
                            <div class="flex flex-col sm:flex-row gap-3 pt-4">
                                <Link
                                    :href="route('club.subscription.index', { club: club.id })"
                                    class="flex-1"
                                >
                                    <PrimaryButton type="button" class="w-full justify-center">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                        </svg>
                                        {{ trans('checkout.success.manage_subscription') }}
                                    </PrimaryButton>
                                </Link>

                                <Link
                                    :href="route('dashboard')"
                                    class="flex-1"
                                >
                                    <SecondaryButton type="button" class="w-full justify-center">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                        </svg>
                                        {{ trans('checkout.success.to_dashboard') }}
                                    </SecondaryButton>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Support Info -->
                <div class="mt-6 text-center text-sm text-gray-600">
                    <p>
                        {{ trans('checkout.success.support_question') }}
                        <a href="mailto:support@basketmanager-pro.de" class="text-blue-600 hover:text-blue-800 font-medium">
                            {{ trans('checkout.success.contact_support') }}
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
