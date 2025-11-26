<script setup>
import { computed } from 'vue';

const props = defineProps({
    currentStep: {
        type: Number,
        required: true,
    },
    totalSteps: {
        type: Number,
        default: 3,
    },
});

const steps = computed(() => [
    { number: 1, title: 'Club erstellen', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' },
    { number: 2, title: 'Team erstellen', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
    { number: 3, title: 'Plan wählen', icon: 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z' },
]);

const progressPercentage = computed(() => {
    return ((props.currentStep - 1) / (props.totalSteps - 1)) * 100;
});
</script>

<template>
    <div class="w-full mb-8">
        <!-- Progress Bar -->
        <div class="relative">
            <!-- Background Line -->
            <div class="absolute top-5 left-0 w-full h-1 bg-gray-200 rounded"></div>

            <!-- Progress Line -->
            <div
                class="absolute top-5 left-0 h-1 bg-orange-500 rounded transition-all duration-500"
                :style="{ width: `${progressPercentage}%` }"
            ></div>

            <!-- Steps -->
            <div class="relative flex justify-between">
                <div
                    v-for="step in steps"
                    :key="step.number"
                    class="flex flex-col items-center"
                >
                    <!-- Step Circle -->
                    <div
                        :class="[
                            'w-10 h-10 rounded-full flex items-center justify-center border-2 transition-all duration-300',
                            currentStep >= step.number
                                ? 'bg-orange-500 border-orange-500 text-white'
                                : 'bg-white border-gray-300 text-gray-400'
                        ]"
                    >
                        <svg
                            v-if="currentStep > step.number"
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <svg
                            v-else
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="step.icon" />
                        </svg>
                    </div>

                    <!-- Step Title -->
                    <span
                        :class="[
                            'mt-2 text-sm font-medium transition-colors duration-300',
                            currentStep >= step.number ? 'text-orange-600' : 'text-gray-500'
                        ]"
                    >
                        {{ step.title }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
