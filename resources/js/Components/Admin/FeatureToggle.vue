<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: Boolean,
        required: true,
    },
    feature: {
        type: String,
        required: true,
    },
    label: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: null,
    },
    icon: {
        type: String,
        default: null,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    badge: {
        type: String,
        default: null, // 'new', 'pro', 'beta', etc.
    },
    size: {
        type: String,
        default: 'md', // 'sm', 'md', 'lg'
        validator: (value) => ['sm', 'md', 'lg'].includes(value),
    },
});

const emit = defineEmits(['update:modelValue']);

const toggle = () => {
    if (!props.disabled) {
        emit('update:modelValue', !props.modelValue);
    }
};

const badgeStyles = computed(() => {
    const styles = {
        new: 'bg-green-100 text-green-800',
        pro: 'bg-purple-100 text-purple-800',
        beta: 'bg-blue-100 text-blue-800',
        enterprise: 'bg-indigo-100 text-indigo-800',
        premium: 'bg-yellow-100 text-yellow-800',
    };
    return styles[props.badge] || 'bg-gray-100 text-gray-800';
});

const sizeClasses = computed(() => {
    const classes = {
        sm: {
            container: 'p-3',
            label: 'text-sm',
            description: 'text-xs',
            toggle: 'h-5 w-9',
            dot: 'h-4 w-4',
            icon: 'w-4 h-4',
        },
        md: {
            container: 'p-4',
            label: 'text-base',
            description: 'text-sm',
            toggle: 'h-6 w-11',
            dot: 'h-5 w-5',
            icon: 'w-5 h-5',
        },
        lg: {
            container: 'p-5',
            label: 'text-lg',
            description: 'text-base',
            toggle: 'h-7 w-14',
            dot: 'h-6 w-6',
            icon: 'w-6 h-6',
        },
    };
    return classes[props.size];
});

// Feature icons mapping
const featureIcons = {
    'live_scoring': 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
    'statistics': 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
    'training_management': 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
    'api_access': 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4',
    'custom_branding': 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01',
    'video_analysis': 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z',
    'advanced_reports': 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    'priority_support': 'M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z',
};

const displayIcon = computed(() => {
    return props.icon || featureIcons[props.feature] || 'M5 13l4 4L19 7';
});
</script>

<template>
    <div
        class="rounded-lg border transition-all duration-200"
        :class="[
            sizeClasses.container,
            modelValue
                ? 'bg-indigo-50 border-indigo-200 shadow-sm'
                : 'bg-white border-gray-200 hover:border-gray-300',
            disabled ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer hover:shadow-md'
        ]"
        @click="toggle"
    >
        <div class="flex items-start justify-between">
            <!-- Left side: Icon + Label -->
            <div class="flex items-start space-x-3">
                <!-- Icon -->
                <div
                    class="flex-shrink-0 rounded-lg p-2"
                    :class="modelValue ? 'bg-indigo-100' : 'bg-gray-100'"
                >
                    <svg
                        :class="[sizeClasses.icon, modelValue ? 'text-indigo-600' : 'text-gray-400']"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            :d="displayIcon"
                        ></path>
                    </svg>
                </div>

                <!-- Label & Description -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center space-x-2">
                        <p
                            :class="[
                                sizeClasses.label,
                                'font-medium',
                                modelValue ? 'text-indigo-900' : 'text-gray-900'
                            ]"
                        >
                            {{ label }}
                        </p>
                        <span
                            v-if="badge"
                            :class="[badgeStyles, 'px-2 py-0.5 rounded-full text-xs font-medium uppercase']"
                        >
                            {{ badge }}
                        </span>
                    </div>
                    <p
                        v-if="description"
                        :class="[
                            sizeClasses.description,
                            'mt-1',
                            modelValue ? 'text-indigo-700' : 'text-gray-500'
                        ]"
                    >
                        {{ description }}
                    </p>
                </div>
            </div>

            <!-- Right side: Toggle Switch -->
            <div class="flex-shrink-0 ml-4">
                <button
                    type="button"
                    role="switch"
                    :aria-checked="modelValue.toString()"
                    :disabled="disabled"
                    class="relative inline-flex flex-shrink-0 rounded-full border-2 border-transparent transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    :class="[
                        sizeClasses.toggle,
                        modelValue ? 'bg-indigo-600' : 'bg-gray-200',
                        disabled ? 'cursor-not-allowed' : 'cursor-pointer'
                    ]"
                    @click.stop="toggle"
                >
                    <span
                        aria-hidden="true"
                        class="pointer-events-none inline-block rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200"
                        :class="[
                            sizeClasses.dot,
                            modelValue ? 'translate-x-5' : 'translate-x-0'
                        ]"
                    ></span>
                </button>
            </div>
        </div>

        <!-- Status indicator -->
        <div v-if="modelValue && !disabled" class="mt-3 flex items-center space-x-2">
            <div class="flex-1">
                <div class="h-1 bg-indigo-200 rounded-full overflow-hidden">
                    <div class="h-full bg-indigo-600 rounded-full animate-pulse" style="width: 100%"></div>
                </div>
            </div>
            <span class="text-xs font-medium text-indigo-600">Aktiv</span>
        </div>
    </div>
</template>
