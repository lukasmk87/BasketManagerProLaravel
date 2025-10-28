<script setup>
import { computed } from 'vue';
import { useTranslations } from '@/composables/useTranslations';

const { trans } = useTranslations();

const props = defineProps({
    modelValue: {
        type: String,
        default: 'monthly',
        validator: (value) => ['monthly', 'yearly'].includes(value),
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    showSavings: {
        type: Boolean,
        default: true,
    },
    savingsPercentage: {
        type: Number,
        default: 10,
    },
});

const emit = defineEmits(['update:modelValue']);

const isMonthly = computed(() => props.modelValue === 'monthly');
const isYearly = computed(() => props.modelValue === 'yearly');

const toggleInterval = () => {
    if (props.disabled) return;
    emit('update:modelValue', isMonthly.value ? 'yearly' : 'monthly');
};
</script>

<template>
    <div class="inline-flex items-center space-x-3">
        <!-- Toggle Switch -->
        <button
            type="button"
            :disabled="disabled"
            @click="toggleInterval"
            class="relative inline-flex h-10 w-20 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            :class="[
                isMonthly ? 'bg-gray-300' : 'bg-blue-600',
                disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'
            ]"
            role="switch"
            :aria-checked="isYearly"
            :aria-label="trans('subscription.billing.toggle_interval')"
        >
            <span
                class="inline-block h-8 w-8 transform rounded-full bg-white shadow-lg transition-transform duration-200 ease-in-out"
                :class="isYearly ? 'translate-x-11' : 'translate-x-1'"
            />
        </button>

        <!-- Labels -->
        <div class="flex items-center space-x-4">
            <button
                type="button"
                :disabled="disabled"
                @click="emit('update:modelValue', 'monthly')"
                class="text-sm font-medium transition-colors"
                :class="[
                    isMonthly ? 'text-gray-900' : 'text-gray-500 hover:text-gray-700',
                    disabled && 'cursor-not-allowed opacity-50'
                ]"
            >
                {{ trans('subscription.billing.monthly') }}
            </button>

            <button
                type="button"
                :disabled="disabled"
                @click="emit('update:modelValue', 'yearly')"
                class="flex items-center text-sm font-medium transition-colors"
                :class="[
                    isYearly ? 'text-blue-600' : 'text-gray-500 hover:text-gray-700',
                    disabled && 'cursor-not-allowed opacity-50'
                ]"
            >
                <span>{{ trans('subscription.billing.yearly') }}</span>
                <span
                    v-if="showSavings"
                    class="ml-2 inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800"
                >
                    -{{ savingsPercentage }}%
                </span>
            </button>
        </div>
    </div>
</template>
