<template>
    <div class="space-y-6">
        <!-- Header with Add Button -->
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900">Trainer-Verwaltung</h3>
            <button
                type="button"
                @click="showAddModal = true"
                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
            >
                + Trainer hinzufügen
            </button>
        </div>

        <!-- Coaches Table -->
        <div v-if="coaches.length > 0" class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 rounded-lg">
            <table class="min-w-full divide-y divide-gray-300">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900">Name</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Rolle</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Lizenz</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Zertifikate</th>
                        <th scope="col" class="relative py-3.5 pl-3 pr-4">
                            <span class="sr-only">Aktionen</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <tr v-for="coach in coaches" :key="coach.id">
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm">
                            <div class="flex items-center">
                                <div>
                                    <div class="font-medium text-gray-900">{{ coach.name }}</div>
                                    <div class="text-gray-500 text-xs">{{ coach.email }}</div>
                                    <!-- System Roles Badges (only if more than just 'trainer' role) -->
                                    <div v-if="coach.system_role_labels && coach.system_role_labels.length > 1" class="flex flex-wrap gap-1 mt-1">
                                        <span
                                            v-for="roleLabel in coach.system_role_labels"
                                            :key="roleLabel"
                                            :class="getRoleBadgeClass(roleLabel)"
                                            class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium"
                                        >
                                            {{ roleLabel }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                            <span
                                :class="[
                                    'inline-flex rounded-full px-2 text-xs font-semibold leading-5',
                                    coach.role === 'head_coach'
                                        ? 'bg-blue-100 text-blue-800'
                                        : 'bg-green-100 text-green-800'
                                ]"
                            >
                                {{ coach.role === 'head_coach' ? 'Haupttrainer' : 'Co-Trainer' }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            <div v-if="editingCoach !== coach.id">
                                <span v-if="coach.coaching_license">{{ coach.coaching_license }}</span>
                                <span v-else class="text-gray-400 italic">Keine Lizenz</span>
                                <button
                                    @click="startEditingCoach(coach)"
                                    class="ml-2 text-blue-600 hover:text-blue-800"
                                    type="button"
                                >
                                    ✏️
                                </button>
                            </div>
                            <div v-else>
                                <input
                                    v-model="editForm.coaching_license"
                                    type="text"
                                    class="border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm"
                                    placeholder="z.B. C-Lizenz"
                                />
                            </div>
                        </td>
                        <td class="px-3 py-4 text-sm text-gray-500">
                            <div v-if="editingCoach !== coach.id">
                                <span v-if="coach.coaching_certifications && coach.coaching_certifications.length > 0">
                                    {{ coach.coaching_certifications.join(', ') }}
                                </span>
                                <span v-else class="text-gray-400 italic">Keine Zertifikate</span>
                            </div>
                            <div v-else>
                                <textarea
                                    v-model="certificationsText"
                                    class="border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm w-full"
                                    rows="2"
                                    placeholder="Komma-getrennt, z.B. Athletiktrainer, Mentalcoach"
                                ></textarea>
                            </div>
                        </td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium space-x-2">
                            <template v-if="editingCoach === coach.id">
                                <button
                                    @click="saveCoachDetails(coach)"
                                    type="button"
                                    class="text-green-600 hover:text-green-900"
                                >
                                    💾 Speichern
                                </button>
                                <button
                                    @click="cancelEditingCoach"
                                    type="button"
                                    class="text-gray-600 hover:text-gray-900"
                                >
                                    ❌ Abbrechen
                                </button>
                            </template>
                            <template v-else>
                                <button
                                    @click="removeCoach(coach)"
                                    type="button"
                                    class="text-red-600 hover:text-red-900"
                                >
                                    Entfernen
                                </button>
                            </template>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-12 bg-gray-50 rounded-lg">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Keine Trainer zugeordnet</h3>
            <p class="mt-1 text-sm text-gray-500">Fügen Sie Trainer hinzu, um dieses Team zu betreuen.</p>
        </div>

        <!-- Add/Edit Coach Modal -->
        <CoachAssignmentModal
            v-if="showAddModal"
            :team="team"
            :available-coaches="availableCoaches"
            :existing-coaches="coaches"
            @close="showAddModal = false"
            @coach-assigned="handleCoachAssigned"
        />
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import CoachAssignmentModal from './CoachAssignmentModal.vue'

const props = defineProps({
    team: {
        type: Object,
        required: true
    },
    coaches: {
        type: Array,
        default: () => []
    }
})

const showAddModal = ref(false)
const availableCoaches = ref([])
const editingCoach = ref(null)
const editForm = ref({
    coaching_license: '',
    coaching_certifications: [],
    coaching_specialties: ''
})
const certificationsText = ref('')

// Load available coaches for this team's club
const loadAvailableCoaches = async () => {
    try {
        const response = await fetch(route('web.clubs.coaches.available', props.team.club_id))
        const data = await response.json()
        availableCoaches.value = data.coaches
    } catch (error) {
        console.error('Fehler beim Laden der Trainer:', error)
    }
}

// Start editing a coach's details
const startEditingCoach = (coach) => {
    editingCoach.value = coach.id
    editForm.value = {
        coaching_license: coach.coaching_license || '',
        coaching_certifications: coach.coaching_certifications || [],
        coaching_specialties: coach.coaching_specialties || ''
    }
    certificationsText.value = (coach.coaching_certifications || []).join(', ')
}

// Cancel editing
const cancelEditingCoach = () => {
    editingCoach.value = null
    editForm.value = {
        coaching_license: '',
        coaching_certifications: [],
        coaching_specialties: ''
    }
    certificationsText.value = ''
}

// Save coach details
const saveCoachDetails = (coach) => {
    // Parse certifications from comma-separated text
    const certifications = certificationsText.value
        .split(',')
        .map(cert => cert.trim())
        .filter(cert => cert.length > 0)

    router.put(
        route('web.teams.coaches.update', { team: props.team.id, user: coach.id }),
        {
            ...editForm.value,
            coaching_certifications: certifications
        },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                cancelEditingCoach()
            }
        }
    )
}

// Remove a coach from the team
const removeCoach = (coach) => {
    if (!confirm(`Möchten Sie ${coach.name} wirklich als Trainer entfernen?`)) {
        return
    }

    router.post(
        route('web.teams.coaches.manageAssistants', props.team.id),
        {
            action: 'remove',
            user_id: coach.id
        },
        {
            preserveState: true,
            preserveScroll: true
        }
    )
}

// Handle successful coach assignment from modal
const handleCoachAssigned = () => {
    showAddModal.value = false
    // Page will reload automatically via Inertia
}

// Get CSS class for role badge based on role label
const getRoleBadgeClass = (roleLabel) => {
    const classes = {
        'Super Admin': 'bg-red-100 text-red-800',
        'Admin': 'bg-red-100 text-red-800',
        'Club Admin': 'bg-purple-100 text-purple-800',
        'Trainer': 'bg-blue-100 text-blue-800',
        'Co-Trainer': 'bg-blue-100 text-blue-800',
        'Spieler': 'bg-green-100 text-green-800',
        'Elternteil': 'bg-yellow-100 text-yellow-800',
        'Anschreiber': 'bg-gray-100 text-gray-800',
        'Schiedsrichter': 'bg-orange-100 text-orange-800',
        'Team Manager': 'bg-indigo-100 text-indigo-800',
        'Gast': 'bg-gray-100 text-gray-600',
    };
    return classes[roleLabel] || 'bg-gray-100 text-gray-800';
};

// Load available coaches on mount
onMounted(() => {
    loadAvailableCoaches()
})
</script>
