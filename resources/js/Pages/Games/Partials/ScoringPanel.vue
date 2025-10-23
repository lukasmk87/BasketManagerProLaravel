<template>
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
        <div class="p-6 lg:p-8">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ team?.name || 'Team' }}
                </h3>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ side === 'home' ? 'Heim' : 'Gast' }}
                </div>
            </div>

            <!-- Player Selection -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Spieler auswählen
                </label>
                <select
                    v-model="selectedPlayerId"
                    class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    :disabled="disabled"
                >
                    <option value="">Spieler auswählen...</option>
                    <option
                        v-for="player in players"
                        :key="player.id"
                        :value="player.id"
                    >
                        #{{ player.jersey_number || '?' }} {{ player.name }}
                    </option>
                </select>
            </div>

            <!-- Quick Score Buttons -->
            <div class="mb-6">
                <h4 class="text-md font-medium text-gray-800 dark:text-gray-200 mb-3">Quick Score</h4>
                <div class="grid grid-cols-3 gap-2">
                    <button
                        @click="quickScore('free_throw_made', 1)"
                        :disabled="!selectedPlayerId || disabled"
                        class="bg-green-500 hover:bg-green-600 disabled:bg-gray-300 text-white py-2 px-3 rounded text-sm font-medium"
                    >
                        Freiwurf +1
                    </button>
                    <button
                        @click="quickScore('field_goal_made', 2)"
                        :disabled="!selectedPlayerId || disabled"
                        class="bg-blue-500 hover:bg-blue-600 disabled:bg-gray-300 text-white py-2 px-3 rounded text-sm font-medium"
                    >
                        2-Punkte +2
                    </button>
                    <button
                        @click="quickScore('three_point_made', 3)"
                        :disabled="!selectedPlayerId || disabled"
                        class="bg-purple-500 hover:bg-purple-600 disabled:bg-gray-300 text-white py-2 px-3 rounded text-sm font-medium"
                    >
                        3-Punkte +3
                    </button>
                </div>
            </div>

            <!-- Detailed Actions -->
            <div class="space-y-4">
                <!-- Scoring Actions -->
                <div>
                    <h4 class="text-md font-medium text-gray-800 dark:text-gray-200 mb-2">Würfe</h4>
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            @click="addAction('field_goal_made', { points: 2, is_successful: true })"
                            :disabled="!selectedPlayerId || disabled"
                            class="bg-green-100 hover:bg-green-200 text-green-800 py-2 px-3 rounded text-sm border border-green-300"
                        >
                            ✅ 2P Treffer
                        </button>
                        <button
                            @click="addAction('field_goal_missed', { points: 0, is_successful: false })"
                            :disabled="!selectedPlayerId || disabled"
                            class="bg-red-100 hover:bg-red-200 text-red-800 py-2 px-3 rounded text-sm border border-red-300"
                        >
                            ❌ 2P Fehlwurf
                        </button>
                        <button
                            @click="addAction('three_point_made', { points: 3, is_successful: true })"
                            :disabled="!selectedPlayerId || disabled"
                            class="bg-green-100 hover:bg-green-200 text-green-800 py-2 px-3 rounded text-sm border border-green-300"
                        >
                            ✅ 3P Treffer
                        </button>
                        <button
                            @click="addAction('three_point_missed', { points: 0, is_successful: false })"
                            :disabled="!selectedPlayerId || disabled"
                            class="bg-red-100 hover:bg-red-200 text-red-800 py-2 px-3 rounded text-sm border border-red-300"
                        >
                            ❌ 3P Fehlwurf
                        </button>
                        <button
                            @click="addAction('free_throw_made', { points: 1, is_successful: true })"
                            :disabled="!selectedPlayerId || disabled"
                            class="bg-green-100 hover:bg-green-200 text-green-800 py-2 px-3 rounded text-sm border border-green-300"
                        >
                            ✅ Freiwurf
                        </button>
                        <button
                            @click="addAction('free_throw_missed', { points: 0, is_successful: false })"
                            :disabled="!selectedPlayerId || disabled"
                            class="bg-red-100 hover:bg-red-200 text-red-800 py-2 px-3 rounded text-sm border border-red-300"
                        >
                            ❌ FW Fehlwurf
                        </button>
                    </div>
                </div>

                <!-- Rebounds -->
                <div>
                    <h4 class="text-md font-medium text-gray-800 dark:text-gray-200 mb-2">Rebounds</h4>
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            @click="addAction('rebound_offensive')"
                            :disabled="!selectedPlayerId || disabled"
                            class="bg-orange-100 hover:bg-orange-200 text-orange-800 py-2 px-3 rounded text-sm border border-orange-300"
                        >
                            📈 Off. Rebound
                        </button>
                        <button
                            @click="addAction('rebound_defensive')"
                            :disabled="!selectedPlayerId || disabled"
                            class="bg-blue-100 hover:bg-blue-200 text-blue-800 py-2 px-3 rounded text-sm border border-blue-300"
                        >
                            📉 Def. Rebound
                        </button>
                    </div>
                </div>

                <!-- Assists & Defense -->
                <div>
                    <h4 class="text-md font-medium text-gray-800 dark:text-gray-200 mb-2">Assists & Defense</h4>
                    <div class="grid grid-cols-3 gap-2">
                        <button
                            @click="addAction('assist')"
                            :disabled="!selectedPlayerId || disabled"
                            class="bg-indigo-100 hover:bg-indigo-200 text-indigo-800 py-2 px-3 rounded text-sm border border-indigo-300"
                        >
                            🎯 Assist
                        </button>
                        <button
                            @click="addAction('steal')"
                            :disabled="!selectedPlayerId || disabled"
                            class="bg-teal-100 hover:bg-teal-200 text-teal-800 py-2 px-3 rounded text-sm border border-teal-300"
                        >
                            🏃 Steal
                        </button>
                        <button
                            @click="addAction('block')"
                            :disabled="!selectedPlayerId || disabled"
                            class="bg-cyan-100 hover:bg-cyan-200 text-cyan-800 py-2 px-3 rounded text-sm border border-cyan-300"
                        >
                            🚫 Block
                        </button>
                    </div>
                </div>

                <!-- Negative Actions -->
                <div>
                    <h4 class="text-md font-medium text-gray-800 dark:text-gray-200 mb-2">Ballverluste & Fouls</h4>
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            @click="addAction('turnover')"
                            :disabled="!selectedPlayerId || disabled"
                            class="bg-yellow-100 hover:bg-yellow-200 text-yellow-800 py-2 px-3 rounded text-sm border border-yellow-300"
                        >
                            ⚠️ Ballverlust
                        </button>
                        <button
                            @click="openFoulDialog"
                            :disabled="!selectedPlayerId || disabled"
                            class="bg-red-100 hover:bg-red-200 text-red-800 py-2 px-3 rounded text-sm border border-red-300"
                        >
                            🟥 Foul
                        </button>
                    </div>
                </div>

                <!-- Substitutions -->
                <div>
                    <h4 class="text-md font-medium text-gray-800 dark:text-gray-200 mb-2">Auswechslung</h4>
                    <button
                        @click="openSubstitutionDialog"
                        :disabled="disabled"
                        class="w-full bg-gray-100 hover:bg-gray-200 text-gray-800 py-2 px-3 rounded text-sm border border-gray-300"
                    >
                        🔄 Auswechslung
                    </button>
                </div>
            </div>
        </div>

        <!-- Foul Type Dialog -->
        <div v-if="showFoulDialog" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">Foul-Art auswählen</h3>
                <div class="space-y-2">
                    <button
                        @click="addFoul('foul_personal')"
                        class="w-full text-left bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 p-3 rounded"
                    >
                        Persönliches Foul
                    </button>
                    <button
                        @click="addFoul('foul_technical')"
                        class="w-full text-left bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 p-3 rounded"
                    >
                        Technisches Foul
                    </button>
                    <button
                        @click="addFoul('foul_flagrant')"
                        class="w-full text-left bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 p-3 rounded"
                    >
                        Flagrant Foul
                    </button>
                    <button
                        @click="addFoul('foul_offensive')"
                        class="w-full text-left bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 p-3 rounded"
                    >
                        Offensiv Foul
                    </button>
                </div>
                <div class="flex justify-end mt-4">
                    <button
                        @click="showFoulDialog = false"
                        class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded mr-2"
                    >
                        Abbrechen
                    </button>
                </div>
            </div>
        </div>

        <!-- Substitution Dialog -->
        <div v-if="showSubstitutionDialog" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">Auswechslung</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Spieler raus
                        </label>
                        <select
                            v-model="substitution.playerOutId"
                            class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md"
                        >
                            <option value="">Spieler auswählen...</option>
                            <option
                                v-for="player in players"
                                :key="player.id"
                                :value="player.id"
                            >
                                #{{ player.jersey_number || '?' }} {{ player.name }}
                            </option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Spieler rein
                        </label>
                        <select
                            v-model="substitution.playerInId"
                            class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md"
                        >
                            <option value="">Spieler auswählen...</option>
                            <option
                                v-for="player in players"
                                :key="player.id"
                                :value="player.id"
                            >
                                #{{ player.jersey_number || '?' }} {{ player.name }}
                            </option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Grund (optional)
                        </label>
                        <input
                            v-model="substitution.reason"
                            type="text"
                            placeholder="z.B. Verletzung, Taktik..."
                            class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md"
                        >
                    </div>
                </div>
                
                <div class="flex justify-end mt-6">
                    <button
                        @click="showSubstitutionDialog = false"
                        class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded mr-2"
                    >
                        Abbrechen
                    </button>
                    <button
                        @click="performSubstitution"
                        :disabled="!substitution.playerOutId || !substitution.playerInId"
                        class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 text-white px-4 py-2 rounded"
                    >
                        Auswechslung
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'

// Props
const props = defineProps({
    team: Object,
    players: Array,
    side: String, // 'home' or 'away'
    disabled: Boolean,
})

// Emits
const emit = defineEmits(['add-action'])

// Reactive data
const selectedPlayerId = ref('')
const showFoulDialog = ref(false)
const showSubstitutionDialog = ref(false)
const substitution = ref({
    playerOutId: '',
    playerInId: '',
    reason: ''
})

// Computed properties
const selectedPlayer = computed(() => {
    return props.players.find(p => p.id === selectedPlayerId.value)
})

// Methods
const quickScore = (actionType, points) => {
    if (!selectedPlayerId.value) return
    
    addAction(actionType, {
        points: points,
        is_successful: true
    })
}

const addAction = (actionType, additionalData = {}) => {
    if (!selectedPlayerId.value && !['timeout_team', 'timeout_official'].includes(actionType)) {
        return
    }

    const actionData = {
        player_id: selectedPlayerId.value,
        team_id: props.team?.id || null,
        action_type: actionType,
        ...additionalData
    }

    emit('add-action', actionData)
}

const openFoulDialog = () => {
    showFoulDialog.value = true
}

const addFoul = (foulType) => {
    addAction(foulType, {
        foul_type: foulType.replace('foul_', ''),
        points: 0
    })
    showFoulDialog.value = false
}

const openSubstitutionDialog = () => {
    showSubstitutionDialog.value = true
}

const performSubstitution = () => {
    if (!substitution.value.playerOutId || !substitution.value.playerInId) return
    
    // Emit substitution event to parent
    emit('add-action', {
        action_type: 'substitution',
        team: props.side,
        player_in_id: substitution.value.playerInId,
        player_out_id: substitution.value.playerOutId,
        reason: substitution.value.reason
    })
    
    // Reset form
    substitution.value = {
        playerOutId: '',
        playerInId: '',
        reason: ''
    }
    showSubstitutionDialog.value = false
}
</script>