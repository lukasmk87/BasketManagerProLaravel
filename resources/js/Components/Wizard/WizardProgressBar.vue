<script setup>
import { computed } from 'vue';
import { CheckIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    currentStep: {
        type: Number,
        required: true,
        validator: (value) => value >= 1 && value <= 5
    },
    steps: {
        type: Array,
        required: true,
        validator: (value) => value.length === 5
    },
    completedSteps: {
        type: Array,
        default: () => []
    },
    allowNavigation: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['step-click']);

const getStepStatus = (stepNumber) => {
    if (stepNumber < props.currentStep || props.completedSteps.includes(stepNumber)) {
        return 'completed';
    } else if (stepNumber === props.currentStep) {
        return 'active';
    } else {
        return 'pending';
    }
};

const isStepClickable = (stepNumber) => {
    if (!props.allowNavigation) return false;

    // Can navigate to completed steps or current step
    return stepNumber <= props.currentStep || props.completedSteps.includes(stepNumber);
};

const handleStepClick = (stepNumber) => {
    if (isStepClickable(stepNumber)) {
        emit('step-click', stepNumber);
    }
};

const getStepClasses = (stepNumber) => {
    const status = getStepStatus(stepNumber);
    const base = 'relative flex items-center justify-center w-10 h-10 rounded-full border-2 transition-all duration-200';

    if (status === 'completed') {
        return `${base} bg-blue-600 border-blue-600 text-white`;
    } else if (status === 'active') {
        return `${base} bg-white border-blue-600 text-blue-600 ring-4 ring-blue-100`;
    } else {
        return `${base} bg-white border-gray-300 text-gray-400`;
    }
};

const getConnectorClasses = (stepNumber) => {
    const nextStatus = getStepStatus(stepNumber + 1);
    const currentStatus = getStepStatus(stepNumber);

    const base = 'flex-1 h-0.5 mx-2 transition-all duration-300';

    if (currentStatus === 'completed' && (nextStatus === 'completed' || nextStatus === 'active')) {
        return `${base} bg-blue-600`;
    } else {
        return `${base} bg-gray-300`;
    }
};
</script>

<template>
    <div class="w-full py-6">
        <!-- Progress Bar -->
        <div class="flex items-center justify-between max-w-4xl mx-auto px-4">
            <template v-for="(step, index) in steps" :key="step.number">
                <!-- Step Circle -->
                <div class="flex flex-col items-center">
                    <button
                        type="button"
                        :class="[
                            getStepClasses(step.number),
                            isStepClickable(step.number)
                                ? 'cursor-pointer hover:scale-110'
                                : 'cursor-not-allowed'
                        ]"
                        :disabled="!isStepClickable(step.number)"
                        @click="handleStepClick(step.number)"
                        :aria-current="step.number === currentStep ? 'step' : undefined"
                        :aria-label="`Schritt ${step.number}: ${step.label}`"
                    >
                        <!-- Completed: Checkmark -->
                        <CheckIcon
                            v-if="getStepStatus(step.number) === 'completed'"
                            class="w-6 h-6"
                        />

                        <!-- Active or Pending: Step Number -->
                        <span
                            v-else
                            class="text-sm font-semibold"
                        >
                            {{ step.number }}
                        </span>
                    </button>

                    <!-- Step Label -->
                    <div class="mt-2 text-center">
                        <p
                            :class="[
                                'text-xs font-medium',
                                getStepStatus(step.number) === 'active'
                                    ? 'text-blue-600'
                                    : getStepStatus(step.number) === 'completed'
                                        ? 'text-gray-900'
                                        : 'text-gray-500'
                            ]"
                        >
                            {{ step.label }}
                        </p>
                        <p
                            v-if="step.description"
                            class="text-xs text-gray-400 mt-0.5 max-w-[100px] line-clamp-1"
                        >
                            {{ step.description }}
                        </p>
                    </div>
                </div>

                <!-- Connector Line (not after last step) -->
                <div
                    v-if="index < steps.length - 1"
                    :class="getConnectorClasses(step.number)"
                    class="hidden sm:block"
                ></div>
            </template>
        </div>

        <!-- Mobile: Step Counter -->
        <div class="sm:hidden mt-4 text-center">
            <p class="text-sm text-gray-600">
                Schritt {{ currentStep }} von {{ steps.length }}
            </p>
            <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                <div
                    class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                    :style="{ width: `${(currentStep / steps.length) * 100}%` }"
                ></div>
            </div>
        </div>
    </div>
</template>
