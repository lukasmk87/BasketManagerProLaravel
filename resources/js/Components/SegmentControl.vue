<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        required: true,
    },
    options: {
        type: Array,
        required: true,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value),
    },
});

const emit = defineEmits(['update:modelValue']);

const selectOption = (value) => {
    if (!props.disabled) {
        emit('update:modelValue', value);
    }
};

const sizeClasses = computed(() => {
    const classes = {
        sm: { container: 'p-0.5', button: 'px-2 py-1 text-xs', icon: 'w-3.5 h-3.5' },
        md: { container: 'p-1', button: 'px-3 py-1.5 text-sm', icon: 'w-4 h-4' },
        lg: { container: 'p-1', button: 'px-4 py-2 text-base', icon: 'w-5 h-5' },
    };
    return classes[props.size];
});
</script>

<template>
    <div
        class="inline-flex rounded-lg bg-gray-100 dark:bg-gray-800"
        :class="[sizeClasses.container, disabled && 'opacity-50']"
        role="radiogroup"
    >
        <button
            v-for="option in options"
            :key="option.value"
            type="button"
            role="radio"
            :aria-checked="modelValue === option.value"
            :aria-label="option.label"
            :disabled="disabled"
            @click="selectOption(option.value)"
            class="relative inline-flex items-center justify-center rounded-md font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
            :class="[
                sizeClasses.button,
                modelValue === option.value
                    ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                    : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300',
                disabled ? 'cursor-not-allowed' : 'cursor-pointer'
            ]"
        >
            <svg
                v-if="option.iconPath"
                :class="[sizeClasses.icon, option.label ? 'mr-1.5' : '']"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    :d="option.iconPath"
                />
            </svg>
            <span v-if="option.label">{{ option.label }}</span>
        </button>
    </div>
</template>
