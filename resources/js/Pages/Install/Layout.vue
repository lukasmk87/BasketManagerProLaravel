<template>
    <div class="min-h-screen bg-gradient-to-br from-orange-50 via-white to-blue-50">
        <div class="container mx-auto px-4 py-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-gray-900 mb-2">
                    🏀 {{ appName }}
                </h1>
                <p class="text-lg text-gray-600">{{ subtitle }}</p>
            </div>

            <!-- Progress Bar -->
            <div v-if="currentStep > 0" class="max-w-4xl mx-auto mb-8">
                <div class="flex items-center justify-between">
                    <div
                        v-for="step in steps"
                        :key="step.number"
                        class="flex-1 flex items-center"
                        :class="{ 'pr-4': step.number < steps.length }"
                    >
                        <!-- Step Circle -->
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div
                                class="w-10 h-10 rounded-full flex items-center justify-center font-semibold transition-all"
                                :class="{
                                    'bg-orange-600 text-white': step.number < currentStep,
                                    'bg-orange-500 text-white ring-4 ring-orange-200': step.number === currentStep,
                                    'bg-gray-200 text-gray-500': step.number > currentStep
                                }"
                            >
                                <svg v-if="step.number < currentStep" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span v-else>{{ step.number }}</span>
                            </div>
                            <span
                                class="text-xs mt-2 text-center hidden sm:block"
                                :class="{
                                    'text-orange-600 font-semibold': step.number === currentStep,
                                    'text-gray-600': step.number !== currentStep
                                }"
                            >
                                {{ step.label }}
                            </span>
                        </div>

                        <!-- Connector Line -->
                        <div
                            v-if="step.number < steps.length"
                            class="flex-1 h-1 mx-2"
                            :class="{
                                'bg-orange-600': step.number < currentStep,
                                'bg-gray-200': step.number >= currentStep
                            }"
                        />
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="max-w-4xl mx-auto">
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <slot />
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-8 text-sm text-gray-500">
                <p>© {{ currentYear }} {{ appName }}. All rights reserved.</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    appName: {
        type: String,
        default: 'BasketManager Pro'
    },
    subtitle: {
        type: String,
        default: 'Installation Wizard'
    },
    currentStep: {
        type: Number,
        default: 0
    },
    language: {
        type: String,
        default: 'de'
    }
});

const steps = computed(() => {
    const labels = {
        de: [
            'Willkommen',
            'Anforderungen',
            'Berechtigungen',
            'Konfiguration',
            'Datenbank',
            'Admin',
            'Fertig'
        ],
        en: [
            'Welcome',
            'Requirements',
            'Permissions',
            'Configuration',
            'Database',
            'Admin',
            'Complete'
        ]
    };

    const stepLabels = labels[props.language] || labels.de;

    return stepLabels.map((label, index) => ({
        number: index + 1,
        label
    }));
});

const currentYear = new Date().getFullYear();
</script>
