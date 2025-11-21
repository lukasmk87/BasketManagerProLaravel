<template>
    <!-- Modal Overlay -->
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity z-50" @click="close"></div>

    <!-- Modal Content -->
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div
                class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg"
                @click.stop
            >
                <form @submit.prevent="submit">
                    <!-- Modal Header -->
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg font-semibold leading-6 text-gray-900 mb-4">
                                    Trainer hinzufügen
                                </h3>

                                <div class="space-y-4">
                                    <!-- Coach Selection -->
                                    <div>
                                        <label for="coach_id" class="block text-sm font-medium text-gray-700 mb-1">
                                            Trainer auswählen *
                                        </label>
                                        <select
                                            id="coach_id"
                                            v-model="form.user_id"
                                            required
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                        >
                                            <option value="">-- Bitte wählen --</option>
                                            <option
                                                v-for="coach in availableCoachesFiltered"
                                                :key="coach.id"
                                                :value="coach.id"
                                            >
                                                {{ coach.name }} ({{ coach.email }})
                                            </option>
                                        </select>
                                        <p v-if="errors.user_id" class="mt-1 text-sm text-red-600">
                                            {{ errors.user_id }}
                                        </p>
                                    </div>

                                    <!-- Role Selection -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Rolle *
                                        </label>
                                        <div class="space-y-2">
                                            <label class="flex items-center">
                                                <input
                                                    v-model="form.role"
                                                    type="radio"
                                                    value="head_coach"
                                                    class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500"
                                                    required
                                                />
                                                <span class="ml-2 text-sm text-gray-700">
                                                    Haupttrainer
                                                    <span v-if="hasHeadCoach" class="text-red-600">(wird ersetzt)</span>
                                                </span>
                                            </label>
                                            <label class="flex items-center">
                                                <input
                                                    v-model="form.role"
                                                    type="radio"
                                                    value="assistant_coach"
                                                    class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500"
                                                />
                                                <span class="ml-2 text-sm text-gray-700">Co-Trainer</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Coaching License -->
                                    <div>
                                        <label for="coaching_license" class="block text-sm font-medium text-gray-700 mb-1">
                                            Trainerlizenz
                                        </label>
                                        <input
                                            id="coaching_license"
                                            v-model="form.coaching_license"
                                            type="text"
                                            placeholder="z.B. C-Lizenz, B-Lizenz, A-Lizenz"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                        />
                                        <p class="mt-1 text-xs text-gray-500">
                                            Optional: Geben Sie die höchste Trainerlizenz an
                                        </p>
                                    </div>

                                    <!-- Coaching Certifications -->
                                    <div>
                                        <label for="certifications" class="block text-sm font-medium text-gray-700 mb-1">
                                            Zertifikate
                                        </label>
                                        <textarea
                                            id="certifications"
                                            v-model="certificationsText"
                                            rows="2"
                                            placeholder="Komma-getrennt, z.B. Athletiktrainer, Mentalcoach, Video-Analyst"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                        ></textarea>
                                        <p class="mt-1 text-xs text-gray-500">
                                            Optional: Weitere Qualifikationen (kommagetrennt)
                                        </p>
                                    </div>

                                    <!-- Coaching Specialties -->
                                    <div>
                                        <label for="specialties" class="block text-sm font-medium text-gray-700 mb-1">
                                            Schwerpunkte
                                        </label>
                                        <textarea
                                            id="specialties"
                                            v-model="form.coaching_specialties"
                                            rows="2"
                                            placeholder="z.B. Verteidigungstraining, Wurftraining, Taktik"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                        ></textarea>
                                        <p class="mt-1 text-xs text-gray-500">
                                            Optional: Trainings-Schwerpunkte dieses Trainers
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button
                            type="submit"
                            :disabled="processing"
                            class="inline-flex w-full justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="processing">Wird hinzugefügt...</span>
                            <span v-else>Trainer hinzufügen</span>
                        </button>
                        <button
                            type="button"
                            @click="close"
                            :disabled="processing"
                            class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:mt-0 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Abbrechen
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    team: {
        type: Object,
        required: true
    },
    availableCoaches: {
        type: Array,
        default: () => []
    },
    existingCoaches: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['close', 'coach-assigned'])

const form = ref({
    user_id: '',
    role: 'assistant_coach',
    coaching_license: '',
    coaching_certifications: [],
    coaching_specialties: ''
})

const certificationsText = ref('')
const processing = ref(false)
const errors = ref({})

// Check if team already has a head coach
const hasHeadCoach = computed(() => {
    return props.existingCoaches.some(coach => coach.role === 'head_coach')
})

// Filter out coaches that are already assigned to this team
const availableCoachesFiltered = computed(() => {
    const existingCoachIds = props.existingCoaches.map(coach => coach.id)
    return props.availableCoaches.filter(coach => !existingCoachIds.includes(coach.id))
})

// Submit form
const submit = () => {
    // Parse certifications from comma-separated text
    const certifications = certificationsText.value
        .split(',')
        .map(cert => cert.trim())
        .filter(cert => cert.length > 0)

    form.value.coaching_certifications = certifications

    processing.value = true
    errors.value = {}

    // Determine which route to use based on role
    const routeName = form.value.role === 'head_coach'
        ? 'web.teams.coaches.assignHead'
        : 'web.teams.coaches.manageAssistants'

    const data = form.value.role === 'head_coach'
        ? {
            user_id: form.value.user_id,
            coaching_license: form.value.coaching_license,
            coaching_certifications: form.value.coaching_certifications,
            coaching_specialties: form.value.coaching_specialties
        }
        : {
            action: 'add',
            user_id: form.value.user_id,
            coaching_license: form.value.coaching_license,
            coaching_certifications: form.value.coaching_certifications,
            coaching_specialties: form.value.coaching_specialties
        }

    router.post(
        route(routeName, props.team.id),
        data,
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                emit('coach-assigned')
                close()
            },
            onError: (err) => {
                errors.value = err
                processing.value = false
            },
            onFinish: () => {
                processing.value = false
            }
        }
    )
}

// Close modal
const close = () => {
    if (!processing.value) {
        emit('close')
    }
}
</script>
