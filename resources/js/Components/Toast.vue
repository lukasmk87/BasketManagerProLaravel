<script setup>
import { ref, onMounted, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    type: {
        type: String,
        default: 'success',
        validator: (value) => ['success', 'error', 'warning', 'info'].includes(value)
    },
    message: String,
    duration: {
        type: Number,
        default: 5000
    },
    show: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['close']);

const visible = ref(props.show);
const page = usePage();

// Watch for changes to show prop
watch(() => props.show, (newValue) => {
    visible.value = newValue;
    if (newValue && props.duration > 0) {
        setTimeout(() => {
            close();
        }, props.duration);
    }
});

// Watch for flash messages from backend
watch(() => page.props.flash, (flash) => {
    if (flash) {
        if (flash.success) {
            visible.value = true;
            setTimeout(() => close(), props.duration);
        }
        if (flash.error) {
            visible.value = true;
            setTimeout(() => close(), props.duration);
        }
    }
}, { deep: true });

const close = () => {
    visible.value = false;
    emit('close');
};

const getTypeClasses = () => {
    const baseClasses = 'rounded-lg shadow-lg p-4 flex items-start space-x-3 max-w-md';
    const typeClasses = {
        success: 'bg-green-50 border border-green-200',
        error: 'bg-red-50 border border-red-200',
        warning: 'bg-yellow-50 border border-yellow-200',
        info: 'bg-blue-50 border border-blue-200'
    };
    return `${baseClasses} ${typeClasses[props.type]}`;
};

const getIconClasses = () => {
    const typeClasses = {
        success: 'text-green-600',
        error: 'text-red-600',
        warning: 'text-yellow-600',
        info: 'text-blue-600'
    };
    return `w-6 h-6 ${typeClasses[props.type]}`;
};

const getTextClasses = () => {
    const typeClasses = {
        success: 'text-green-800',
        error: 'text-red-800',
        warning: 'text-yellow-800',
        info: 'text-blue-800'
    };
    return `text-sm font-medium ${typeClasses[props.type]}`;
};

onMounted(() => {
    if (props.show && props.duration > 0) {
        setTimeout(() => {
            close();
        }, props.duration);
    }
});
</script>

<template>
    <Transition
        enter-active-class="transform ease-out duration-300 transition"
        enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
        leave-active-class="transition ease-in duration-100"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="visible" :class="getTypeClasses()">
            <!-- Icon -->
            <div class="flex-shrink-0">
                <!-- Success Icon -->
                <svg v-if="type === 'success'" :class="getIconClasses()" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>

                <!-- Error Icon -->
                <svg v-else-if="type === 'error'" :class="getIconClasses()" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>

                <!-- Warning Icon -->
                <svg v-else-if="type === 'warning'" :class="getIconClasses()" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>

                <!-- Info Icon -->
                <svg v-else :class="getIconClasses()" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>

            <!-- Message -->
            <div class="flex-1">
                <p :class="getTextClasses()">
                    <slot>{{ message }}</slot>
                </p>
            </div>

            <!-- Close Button -->
            <div class="flex-shrink-0">
                <button
                    type="button"
                    @click="close"
                    :class="[getIconClasses(), 'hover:opacity-75 focus:outline-none']"
                >
                    <span class="sr-only">Schließen</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    </Transition>
</template>
