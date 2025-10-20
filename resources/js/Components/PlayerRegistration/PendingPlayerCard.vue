<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    player: {
        type: Object,
        required: true,
    },
    teams: {
        type: Array,
        default: () => [],
    },
    bulkMode: {
        type: Boolean,
        default: false,
    },
    selected: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['assign', 'reject', 'toggleSelect']);

const expanded = ref(false);
const selectedTeamId = ref(null);
const jerseyNumber = ref(null);
const selectedPosition = ref(null);
const processing = ref(false);

// Basketball positions
const positions = [
    { value: 'PG', label: 'Point Guard (PG)' },
    { value: 'SG', label: 'Shooting Guard (SG)' },
    { value: 'SF', label: 'Small Forward (SF)' },
    { value: 'PF', label: 'Power Forward (PF)' },
    { value: 'C', label: 'Center (C)' },
];

// Compute player age
const playerAge = computed(() => {
    if (!props.player.birth_date) return null;
    const birthDate = new Date(props.player.birth_date);
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    return age;
});

// Compute registration date
const registrationDate = computed(() => {
    if (!props.player.registration_completed_at) return null;
    return new Date(props.player.registration_completed_at).toLocaleDateString('de-DE');
});

// Compute club name
const clubName = computed(() => {
    return props.player.registered_via_invitation?.club?.name || 'Unbekannt';
});

// Check if form is valid for assignment
const canAssign = computed(() => {
    return selectedTeamId.value !== null;
});

// Handle assign action
const handleAssign = () => {
    if (!canAssign.value) {
        alert('Bitte wählen Sie zuerst ein Team aus.');
        return;
    }

    const teamData = {};
    if (jerseyNumber.value) {
        teamData.jersey_number = jerseyNumber.value;
    }
    if (selectedPosition.value) {
        teamData.position = selectedPosition.value;
    }

    emit('assign', {
        playerId: props.player.id,
        teamId: selectedTeamId.value,
        teamData,
    });
};

// Handle reject action
const handleReject = () => {
    if (!confirm(`Möchten Sie die Registrierung von ${props.player.user?.name} wirklich ablehnen?`)) {
        return;
    }

    emit('reject', props.player.id);
};

// Handle bulk selection toggle
const toggleSelect = () => {
    emit('toggleSelect', props.player.id);
};
</script>

<template>
    <div
        class="bg-white rounded-lg border transition-all"
        :class="{
            'border-blue-500 ring-2 ring-blue-200': selected && bulkMode,
            'border-gray-200': !selected || !bulkMode,
        }"
    >
        <div class="p-4">
            <!-- Header Row -->
            <div class="flex items-start gap-4">
                <!-- Bulk Selection Checkbox -->
                <div v-if="bulkMode" class="pt-1">
                    <input
                        type="checkbox"
                        :checked="selected"
                        @change="toggleSelect"
                        class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                    />
                </div>

                <!-- Player Avatar -->
                <div class="flex-shrink-0">
                    <div
                        class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-bold text-lg"
                    >
                        {{ player.user?.name?.charAt(0) || '?' }}
                    </div>
                </div>

                <!-- Player Info -->
                <div class="flex-1 min-w-0">
                    <h3 class="text-lg font-semibold text-gray-900 truncate">
                        {{ player.user?.name || 'Unbekannt' }}
                    </h3>
                    <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-600">
                        <span v-if="player.user?.email" class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                />
                            </svg>
                            {{ player.user.email }}
                        </span>
                        <span v-if="playerAge" class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                />
                            </svg>
                            {{ playerAge }} Jahre
                        </span>
                        <span v-if="registrationDate" class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                            {{ registrationDate }}
                        </span>
                    </div>
                    <div v-if="clubName" class="mt-1">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ clubName }}
                        </span>
                    </div>
                </div>

                <!-- Toggle Button -->
                <button
                    @click="expanded = !expanded"
                    class="flex-shrink-0 p-2 text-gray-400 hover:text-gray-600 focus:outline-none"
                >
                    <svg
                        class="w-5 h-5 transition-transform"
                        :class="{ 'rotate-180': expanded }"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 9l-7 7-7-7"
                        />
                    </svg>
                </button>
            </div>

            <!-- Expanded Details -->
            <div v-if="expanded" class="mt-4 pt-4 border-t border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <!-- Additional Player Info -->
                    <div class="space-y-2 text-sm">
                        <div v-if="player.phone">
                            <span class="font-medium text-gray-700">Telefon:</span>
                            <span class="ml-2 text-gray-900">{{ player.phone }}</span>
                        </div>
                        <div v-if="player.height">
                            <span class="font-medium text-gray-700">Größe:</span>
                            <span class="ml-2 text-gray-900">{{ player.height }} cm</span>
                        </div>
                        <div v-if="player.position">
                            <span class="font-medium text-gray-700">Bevorzugte Position:</span>
                            <span class="ml-2 text-gray-900">{{ player.position }}</span>
                        </div>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div v-if="player.city">
                            <span class="font-medium text-gray-700">Ort:</span>
                            <span class="ml-2 text-gray-900">{{ player.city }}</span>
                        </div>
                        <div v-if="player.registered_via_invitation?.target_team">
                            <span class="font-medium text-gray-700">Ziel-Team:</span>
                            <span class="ml-2 text-gray-900">
                                {{ player.registered_via_invitation.target_team.name }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Experience -->
                <div v-if="player.experience" class="mb-4 text-sm">
                    <span class="font-medium text-gray-700">Erfahrung:</span>
                    <p class="mt-1 text-gray-900 whitespace-pre-line">{{ player.experience }}</p>
                </div>

                <!-- Assignment Form (only in non-bulk mode) -->
                <div v-if="!bulkMode" class="border-t border-gray-200 pt-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Team Selection -->
                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Team auswählen *
                            </label>
                            <select
                                v-model="selectedTeamId"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option :value="null">-- Team auswählen --</option>
                                <option v-for="team in teams" :key="team.id" :value="team.id">
                                    {{ team.name }}
                                    <template v-if="team.age_group || team.gender">
                                        ({{ team.age_group }} {{ team.gender }})
                                    </template>
                                </option>
                            </select>
                        </div>

                        <!-- Jersey Number -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Trikotnummer
                            </label>
                            <input
                                v-model.number="jerseyNumber"
                                type="number"
                                min="0"
                                max="99"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                placeholder="z.B. 23"
                            />
                        </div>

                        <!-- Position -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Position
                            </label>
                            <select
                                v-model="selectedPosition"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option :value="null">-- Position auswählen --</option>
                                <option v-for="pos in positions" :key="pos.value" :value="pos.value">
                                    {{ pos.label }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-4 flex gap-2">
                        <button
                            @click="handleAssign"
                            :disabled="!canAssign || processing"
                            class="flex-1 px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            Team zuweisen
                        </button>
                        <button
                            @click="handleReject"
                            :disabled="processing"
                            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            Ablehnen
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
