<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import OnboardingProgressBar from '@/Components/Onboarding/OnboardingProgressBar.vue';
import ClubStep from './Steps/ClubStep.vue';
import TeamStep from './Steps/TeamStep.vue';
import PlanStep from './Steps/PlanStep.vue';

const props = defineProps({
    progress: {
        type: Object,
        required: true,
    },
    availablePlans: {
        type: Array,
        default: () => [],
    },
    ageGroups: {
        type: Object,
        default: () => ({}),
    },
    freePlanId: {
        type: String,
        default: null,
    },
    club: {
        type: Object,
        default: null,
    },
});

const page = usePage();

// Initialize current step from progress
const currentStep = ref(props.progress.current_step || 1);

// Watch for flash messages to advance steps
onMounted(() => {
    // If there's a success flash, we might have completed a step
    if (page.props.flash?.success) {
        currentStep.value = props.progress.current_step;
    }
});

const handleClubCreated = () => {
    currentStep.value = 2;
};

const handleTeamCreated = () => {
    currentStep.value = 3;
};

const handlePlanSelected = () => {
    // This will redirect to complete or Stripe
};

const goBack = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};
</script>

<template>
    <Head title="Onboarding" />

    <div class="min-h-screen bg-gradient-to-b from-orange-50 to-white">
        <!-- Header -->
        <header class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-center">
                    <a href="/" class="flex items-center space-x-2">
                        <img
                            src="/images/logo.svg"
                            alt="BasketManager Pro"
                            class="h-10 w-auto"
                            onerror="this.style.display='none'"
                        />
                        <span class="text-xl font-bold text-gray-900">BasketManager Pro</span>
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="py-8">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Progress Bar -->
                <OnboardingProgressBar
                    :current-step="currentStep"
                    :total-steps="3"
                />

                <!-- Flash Messages -->
                <div
                    v-if="$page.props.flash?.success"
                    class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700"
                >
                    {{ $page.props.flash.success }}
                </div>

                <div
                    v-if="$page.props.flash?.error || $page.props.errors?.error"
                    class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700"
                >
                    {{ $page.props.flash?.error || $page.props.errors?.error }}
                </div>

                <!-- Step Content -->
                <div class="bg-white rounded-xl shadow-lg p-6 md:p-8">
                    <transition
                        mode="out-in"
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="opacity-0 translate-y-2"
                        enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="opacity-100 translate-y-0"
                        leave-to-class="opacity-0 -translate-y-2"
                    >
                        <!-- Step 1: Club -->
                        <ClubStep
                            v-if="currentStep === 1"
                            @next="handleClubCreated"
                        />

                        <!-- Step 2: Team -->
                        <TeamStep
                            v-else-if="currentStep === 2"
                            :age-groups="ageGroups"
                            @next="handleTeamCreated"
                            @back="goBack"
                        />

                        <!-- Step 3: Plan -->
                        <PlanStep
                            v-else-if="currentStep === 3"
                            :available-plans="availablePlans"
                            :free-plan-id="freePlanId"
                            @complete="handlePlanSelected"
                            @back="goBack"
                        />
                    </transition>
                </div>

                <!-- Help Text -->
                <div class="mt-8 text-center text-sm text-gray-500">
                    <p>
                        Brauchst du Hilfe?
                        <a href="mailto:support@basketmanager.pro" class="text-orange-600 hover:text-orange-700">
                            Kontaktiere uns
                        </a>
                    </p>
                </div>
            </div>
        </main>
    </div>
</template>
