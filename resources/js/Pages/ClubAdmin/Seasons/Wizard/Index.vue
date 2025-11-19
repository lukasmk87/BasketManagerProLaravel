<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import WizardProgressBar from '@/Components/Wizard/WizardProgressBar.vue';
import BasicInfoStep from './Steps/BasicInfoStep.vue';
import TeamSelectionStep from './Steps/TeamSelectionStep.vue';
import RosterManagementStep from './Steps/RosterManagementStep.vue';
import SettingsStep from './Steps/SettingsStep.vue';
import PreviewStep from './Steps/PreviewStep.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { ArrowLeftIcon, ArrowRightIcon, CheckIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    club: {
        type: Object,
        required: true
    },
    clubTeams: {
        type: Array,
        default: () => []
    },
    previousSeasons: {
        type: Array,
        default: () => []
    },
    availablePlayers: {
        type: Object,
        default: () => ({})
    },
    permissions: {
        type: Object,
        default: () => ({})
    }
});

const currentStep = ref(1);
const completedSteps = ref([]);

const WIZARD_DRAFT_KEY = `season_wizard_draft_${props.club.id}`;

const form = useForm({
    // Step 1: Basic Info
    name: '',
    description: '',
    start_date: '',
    end_date: '',

    // Step 2: Team Selection
    selected_teams: [],
    copy_from_season: null,

    // Step 3: Roster Management
    team_rosters: {},

    // Step 4: Settings
    auto_activate: false,
    create_schedule: false,
    schedule_type: 'double',
    consider_home_advantage: true,
    notify_teams: true,
    notify_players: false,
    snapshot_interval: 'weekly',
    roster_lock_date: null
});

const steps = [
    { number: 1, label: 'Basis-Info', description: 'Name & Zeitraum' },
    { number: 2, label: 'Teams', description: 'Team-Auswahl' },
    { number: 3, label: 'Kader', description: 'Spieler zuweisen' },
    { number: 4, label: 'Einstellungen', description: 'Optionen' },
    { number: 5, label: 'Vorschau', description: 'Überprüfen' }
];

const selectedTeams = computed(() => {
    if (!form.selected_teams || form.selected_teams.length === 0) {
        return [];
    }
    return props.clubTeams.filter(team => form.selected_teams.includes(team.id));
});

const canGoNext = computed(() => {
    switch (currentStep.value) {
        case 1:
            return form.name && form.start_date && form.end_date;
        case 2:
            return form.selected_teams && form.selected_teams.length > 0;
        case 3:
            return true; // Kader ist optional
        case 4:
            return true; // Settings sind alle optional
        case 5:
            return true; // Preview hat keine Validierung
        default:
            return false;
    }
});

const canGoBack = computed(() => {
    return currentStep.value > 1;
});

const isLastStep = computed(() => {
    return currentStep.value === steps.length;
});

// Load draft from localStorage
const loadDraft = () => {
    try {
        const draft = localStorage.getItem(WIZARD_DRAFT_KEY);
        if (draft) {
            const data = JSON.parse(draft);
            Object.assign(form, data);
        }
    } catch (error) {
        console.error('Error loading draft:', error);
    }
};

// Save draft to localStorage
const saveDraft = () => {
    try {
        localStorage.setItem(WIZARD_DRAFT_KEY, JSON.stringify(form.data()));
    } catch (error) {
        console.error('Error saving draft:', error);
    }
};

// Clear draft from localStorage
const clearDraft = () => {
    try {
        localStorage.removeItem(WIZARD_DRAFT_KEY);
    } catch (error) {
        console.error('Error clearing draft:', error);
    }
};

// Auto-save draft when form changes
watch(() => form.data(), () => {
    saveDraft();
}, { deep: true });

const goToStep = (step) => {
    if (step >= 1 && step <= steps.length) {
        currentStep.value = step;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const nextStep = () => {
    if (canGoNext.value && currentStep.value < steps.length) {
        // Mark current step as completed
        if (!completedSteps.value.includes(currentStep.value)) {
            completedSteps.value.push(currentStep.value);
        }
        currentStep.value++;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const previousStep = () => {
    if (canGoBack.value) {
        currentStep.value--;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const handleSubmit = () => {
    if (!canGoNext.value) {
        alert('Bitte füllen Sie alle erforderlichen Felder aus.');
        return;
    }

    form.post(route('club.seasons.wizard.complete', { club: props.club.id }), {
        onSuccess: () => {
            clearDraft();
        },
        onError: (errors) => {
            console.error('Validation errors:', errors);
            // Navigate to first step with errors
            if (errors.name || errors.start_date || errors.end_date) {
                goToStep(1);
            } else if (errors.selected_teams) {
                goToStep(2);
            }
        }
    });
};

const handleCancel = () => {
    if (confirm('Möchten Sie den Wizard wirklich abbrechen? Alle Änderungen werden gespeichert und können später fortgesetzt werden.')) {
        router.visit(route('club.seasons.dashboard', { club: props.club.id }));
    }
};

const handleEditStep = (step) => {
    goToStep(step);
};

onMounted(() => {
    loadDraft();
});
</script>

<template>
    <AppLayout title="Neue Saison erstellen">
        <Head :title="`Neue Saison erstellen - ${club.name}`" />

        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">
                                Neue Saison erstellen
                            </h1>
                            <p class="mt-1 text-sm text-gray-500">
                                Folgen Sie dem Wizard, um eine neue Saison für {{ club.name }} zu erstellen
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="handleCancel"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            <ArrowLeftIcon class="h-4 w-4 mr-2" />
                            Abbrechen
                        </button>
                    </div>
                </div>

                <!-- Progress Bar -->
                <WizardProgressBar
                    :current-step="currentStep"
                    :steps="steps"
                    :completed-steps="completedSteps"
                    :allow-navigation="true"
                    @step-click="goToStep"
                />

                <!-- Step Content -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-8 min-h-[600px]">
                        <!-- Step 1: Basic Info -->
                        <BasicInfoStep
                            v-show="currentStep === 1"
                            v-model:form="form"
                        />

                        <!-- Step 2: Team Selection -->
                        <TeamSelectionStep
                            v-show="currentStep === 2"
                            v-model:form="form"
                            :club-teams="clubTeams"
                            :previous-seasons="previousSeasons"
                        />

                        <!-- Step 3: Roster Management -->
                        <RosterManagementStep
                            v-show="currentStep === 3"
                            v-model:form="form"
                            :selected-teams="selectedTeams"
                            :available-players="availablePlayers"
                        />

                        <!-- Step 4: Settings -->
                        <SettingsStep
                            v-show="currentStep === 4"
                            v-model:form="form"
                            :permissions="permissions"
                        />

                        <!-- Step 5: Preview -->
                        <PreviewStep
                            v-show="currentStep === 5"
                            :form="form"
                            :selected-teams="selectedTeams"
                            @edit-step="handleEditStep"
                        />
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between rounded-b-lg">
                        <div>
                            <SecondaryButton
                                v-if="canGoBack"
                                @click="previousStep"
                                :disabled="form.processing"
                            >
                                <ArrowLeftIcon class="h-4 w-4 mr-2" />
                                Zurück
                            </SecondaryButton>
                        </div>

                        <div class="flex items-center space-x-3">
                            <!-- Step indicator -->
                            <span class="text-sm text-gray-600">
                                Schritt {{ currentStep }} von {{ steps.length }}
                            </span>

                            <!-- Next/Submit Button -->
                            <PrimaryButton
                                v-if="!isLastStep"
                                @click="nextStep"
                                :disabled="!canGoNext || form.processing"
                            >
                                Weiter
                                <ArrowRightIcon class="h-4 w-4 ml-2" />
                            </PrimaryButton>

                            <PrimaryButton
                                v-else
                                @click="handleSubmit"
                                :disabled="!canGoNext || form.processing"
                                :class="{ 'opacity-25': form.processing }"
                            >
                                <CheckIcon class="h-4 w-4 mr-2" />
                                <span v-if="form.processing">Erstelle Saison...</span>
                                <span v-else>Saison erstellen</span>
                            </PrimaryButton>
                        </div>
                    </div>
                </div>

                <!-- Help Text -->
                <div class="mt-6 text-center">
                    <p class="text-xs text-gray-500">
                        Ihr Fortschritt wird automatisch gespeichert. Sie können den Wizard jederzeit fortsetzen.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
