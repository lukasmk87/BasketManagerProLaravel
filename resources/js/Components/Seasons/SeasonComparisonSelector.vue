<script setup>
import { ref, computed } from 'vue';
import Dropdown from '@/Components/Dropdown.vue';
import { CheckIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    availableSeasons: {
        type: Array,
        default: () => []
    },
    selectedSeasons: {
        type: Array,
        default: () => []
    },
    maxSelections: {
        type: Number,
        default: 4
    }
});

const emit = defineEmits(['update:selectedSeasons']);

const seasonColors = [
    { bg: 'bg-blue-100', text: 'text-blue-800', border: 'border-blue-500', hex: '#3B82F6' },
    { bg: 'bg-green-100', text: 'text-green-800', border: 'border-green-500', hex: '#10B981' },
    { bg: 'bg-orange-100', text: 'text-orange-800', border: 'border-orange-500', hex: '#F59E0B' },
    { bg: 'bg-purple-100', text: 'text-purple-800', border: 'border-purple-500', hex: '#8B5CF6' }
];

const isSelected = (seasonId) => {
    return props.selectedSeasons.includes(seasonId);
};

const getSeasonColorIndex = (seasonId) => {
    const index = props.selectedSeasons.indexOf(seasonId);
    return index >= 0 ? index % seasonColors.length : 0;
};

const getSeasonColor = (seasonId) => {
    return seasonColors[getSeasonColorIndex(seasonId)];
};

const toggleSeason = (seasonId) => {
    let newSelected = [...props.selectedSeasons];

    if (isSelected(seasonId)) {
        // Remove
        newSelected = newSelected.filter(id => id !== seasonId);
    } else {
        // Add (if under max limit)
        if (newSelected.length < props.maxSelections) {
            newSelected.push(seasonId);
        } else {
            alert(`Sie können maximal ${props.maxSelections} Saisons gleichzeitig vergleichen.`);
            return;
        }
    }

    emit('update:selectedSeasons', newSelected);
};

const selectLastTwo = () => {
    if (props.availableSeasons.length >= 2) {
        const sorted = [...props.availableSeasons].sort((a, b) =>
            new Date(b.start_date) - new Date(a.start_date)
        );
        emit('update:selectedSeasons', sorted.slice(0, 2).map(s => s.id));
    }
};

const selectLastThree = () => {
    if (props.availableSeasons.length >= 3) {
        const sorted = [...props.availableSeasons].sort((a, b) =>
            new Date(b.start_date) - new Date(a.start_date)
        );
        emit('update:selectedSeasons', sorted.slice(0, 3).map(s => s.id));
    }
};

const selectAll = () => {
    const limit = Math.min(props.availableSeasons.length, props.maxSelections);
    const sorted = [...props.availableSeasons].sort((a, b) =>
        new Date(b.start_date) - new Date(a.start_date)
    );
    emit('update:selectedSeasons', sorted.slice(0, limit).map(s => s.id));
};

const clearAll = () => {
    emit('update:selectedSeasons', []);
};

const removeSeason = (seasonId) => {
    const newSelected = props.selectedSeasons.filter(id => id !== seasonId);
    emit('update:selectedSeasons', newSelected);
};

const selectedSeasonObjects = computed(() => {
    return props.selectedSeasons
        .map(id => props.availableSeasons.find(s => s.id === id))
        .filter(Boolean);
});
</script>

<template>
    <div>
        <!-- Selected Seasons Badges -->
        <div v-if="selectedSeasons.length > 0" class="flex flex-wrap gap-2 mb-4">
            <div
                v-for="season in selectedSeasonObjects"
                :key="season.id"
                :class="[
                    'inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium border-2',
                    getSeasonColor(season.id).bg,
                    getSeasonColor(season.id).text,
                    getSeasonColor(season.id).border
                ]"
            >
                <span>{{ season.name }}</span>
                <button
                    type="button"
                    @click="removeSeason(season.id)"
                    class="ml-2 inline-flex items-center justify-center w-4 h-4 rounded-full hover:bg-black hover:bg-opacity-10 transition-colors"
                >
                    <XMarkIcon class="w-3 h-3" />
                </button>
            </div>
        </div>

        <!-- Dropdown Selector -->
        <Dropdown align="left" width="96">
            <template #trigger>
                <button
                    type="button"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition"
                >
                    <span v-if="selectedSeasons.length === 0">
                        Saisons auswählen
                    </span>
                    <span v-else>
                        {{ selectedSeasons.length }} {{ selectedSeasons.length === 1 ? 'Saison' : 'Saisons' }} ausgewählt
                    </span>
                    <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </template>

            <template #content>
                <!-- Quick Actions -->
                <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                    <p class="text-xs font-medium text-gray-700 mb-2">Schnellauswahl</p>
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            @click="selectLastTwo"
                            :disabled="availableSeasons.length < 2"
                            class="px-2.5 py-1 text-xs font-medium text-blue-700 bg-blue-100 rounded hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition"
                        >
                            Letzte 2
                        </button>
                        <button
                            type="button"
                            @click="selectLastThree"
                            :disabled="availableSeasons.length < 3"
                            class="px-2.5 py-1 text-xs font-medium text-green-700 bg-green-100 rounded hover:bg-green-200 focus:outline-none focus:ring-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed transition"
                        >
                            Letzte 3
                        </button>
                        <button
                            type="button"
                            @click="selectAll"
                            class="px-2.5 py-1 text-xs font-medium text-gray-700 bg-gray-200 rounded hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 transition"
                        >
                            Alle (max {{ maxSelections }})
                        </button>
                    </div>
                </div>

                <!-- Season List -->
                <div class="max-h-96 overflow-y-auto">
                    <label
                        v-for="season in availableSeasons"
                        :key="season.id"
                        class="flex items-center px-4 py-3 hover:bg-gray-50 cursor-pointer transition-colors border-b border-gray-100 last:border-b-0"
                    >
                        <input
                            type="checkbox"
                            :checked="isSelected(season.id)"
                            @change="toggleSeason(season.id)"
                            :disabled="!isSelected(season.id) && selectedSeasons.length >= maxSelections"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded disabled:opacity-50"
                        />
                        <div class="ml-3 flex-1">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ season.name }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ new Date(season.start_date).toLocaleDateString('de-DE') }} -
                                        {{ new Date(season.end_date).toLocaleDateString('de-DE') }}
                                    </p>
                                </div>
                                <div v-if="isSelected(season.id)" class="ml-2">
                                    <div
                                        :class="[
                                            'w-3 h-3 rounded-full border-2',
                                            getSeasonColor(season.id).border
                                        ]"
                                        :style="{ backgroundColor: getSeasonColor(season.id).hex }"
                                    ></div>
                                </div>
                            </div>
                            <div class="mt-1 flex items-center space-x-3 text-xs text-gray-500">
                                <span>{{ season.teams_count || 0 }} Teams</span>
                                <span>•</span>
                                <span>{{ season.games_count || 0 }} Spiele</span>
                                <span>•</span>
                                <span>{{ season.players_count || 0 }} Spieler</span>
                            </div>
                        </div>
                    </label>
                </div>

                <!-- Empty State -->
                <div v-if="availableSeasons.length === 0" class="px-4 py-8 text-center">
                    <p class="text-sm text-gray-500">
                        Keine Saisons verfügbar
                    </p>
                </div>

                <!-- Footer Actions -->
                <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <p class="text-xs text-gray-600">
                            {{ selectedSeasons.length }} / {{ maxSelections }} ausgewählt
                        </p>
                        <button
                            v-if="selectedSeasons.length > 0"
                            type="button"
                            @click="clearAll"
                            class="text-xs font-medium text-red-600 hover:text-red-700 focus:outline-none"
                        >
                            Alle abwählen
                        </button>
                    </div>
                </div>
            </template>
        </Dropdown>

        <!-- Validation Message -->
        <p v-if="selectedSeasons.length < 2" class="mt-2 text-sm text-yellow-600">
            Wählen Sie mindestens 2 Saisons zum Vergleichen aus.
        </p>
    </div>
</template>
