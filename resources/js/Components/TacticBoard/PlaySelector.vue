<template>
    <div class="space-y-6">
        <!-- Header with Add Play Button -->
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900">
                Spielzüge
                <span v-if="selectedPlays.length > 0" class="text-sm text-gray-500 ml-2">
                    ({{ selectedPlays.length }})
                </span>
            </h3>
            <div class="flex gap-2">
                <button
                    @click="showAddPlayModal = true"
                    type="button"
                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-orange-700 bg-orange-100 hover:bg-orange-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
                >
                    <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Spielzug hinzufügen
                </button>
                <button
                    v-if="selectedPlays.length > 0"
                    @click="clearAllPlays"
                    type="button"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
                >
                    Alle entfernen
                </button>
            </div>
        </div>

        <!-- Selected Plays List (Sortable with HTML5 Drag & Drop) -->
        <div v-if="selectedPlays.length > 0" class="space-y-3">
            <div class="text-sm text-gray-600 bg-gray-50 p-3 rounded-md">
                <div class="flex items-center">
                    <svg class="h-4 w-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                    </svg>
                    Ziehen Sie die Spielzüge, um ihre Reihenfolge zu ändern
                </div>
            </div>

            <div class="space-y-3">
                <div
                    v-for="(play, index) in selectedPlays"
                    :key="play.id"
                    :draggable="true"
                    @dragstart="handleDragStart(index, $event)"
                    @dragend="handleDragEnd"
                    @dragover.prevent="handleDragOver(index)"
                    @drop.prevent="handleDrop(index)"
                    class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-all duration-200 cursor-move"
                    :class="{
                        'ring-2 ring-orange-500': play.isEditing,
                        'opacity-50 scale-95': isDragging && draggedIndex === index,
                        'border-orange-300 bg-orange-50': dragOverIndex === index && draggedIndex !== index
                    }"
                >
                    <div class="flex items-start justify-between">
                        <!-- Drag Handle -->
                        <div class="play-drag-handle flex-shrink-0 mr-3 mt-1 cursor-move">
                            <svg class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </div>

                        <!-- Play Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <span class="inline-flex items-center justify-center w-6 h-6 bg-orange-100 text-orange-800 text-xs font-medium rounded-full mr-3">
                                        {{ index + 1 }}
                                    </span>
                                    <h4 class="text-sm font-medium text-gray-900">{{ play.name }}</h4>
                                    <span v-if="play.category" class="ml-2 text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                                        {{ formatCategory(play.category) }}
                                    </span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button
                                        @click="togglePlayEdit(play)"
                                        type="button"
                                        class="text-orange-600 hover:text-orange-900 text-sm"
                                    >
                                        {{ play.isEditing ? 'Fertig' : 'Bearbeiten' }}
                                    </button>
                                    <button
                                        v-if="showPreview"
                                        @click="previewPlay(play)"
                                        type="button"
                                        class="text-blue-600 hover:text-blue-900 text-sm"
                                    >
                                        Vorschau
                                    </button>
                                    <button
                                        @click="removePlay(index)"
                                        type="button"
                                        class="text-red-600 hover:text-red-900 ml-2"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Play Description -->
                            <p v-if="play.description" class="mt-2 text-sm text-gray-600 ml-9">
                                {{ play.description }}
                            </p>

                            <!-- Thumbnail Preview -->
                            <div v-if="play.thumbnail_path && showPreview" class="mt-2 ml-9">
                                <img
                                    :src="play.thumbnail_path"
                                    :alt="play.name"
                                    class="h-20 w-auto rounded border border-gray-200"
                                />
                            </div>

                            <!-- Edit Form (when editing) -->
                            <div v-if="play.isEditing" class="mt-4 ml-9 space-y-3 bg-gray-50 p-3 rounded">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Notizen</label>
                                    <textarea
                                        v-model="play.notes"
                                        rows="2"
                                        placeholder="Notizen zu diesem Spielzug..."
                                        class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-orange-300 focus:ring focus:ring-orange-200 focus:ring-opacity-50"
                                    ></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Play Summary -->
            <div class="bg-orange-50 p-4 rounded-lg">
                <h4 class="text-sm font-medium text-orange-900 mb-2">Spielzug-Übersicht</h4>
                <div class="flex flex-wrap gap-2">
                    <div
                        v-for="(play, index) in selectedPlays"
                        :key="play.id"
                        class="flex items-center bg-white px-3 py-1 rounded-full text-xs"
                    >
                        <span class="font-medium">{{ index + 1 }}.</span>
                        <span class="ml-1">{{ play.name }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-8 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Keine Spielzüge verknüpft</h3>
            <p class="mt-1 text-sm text-gray-500">
                Fügen Sie Spielzüge hinzu, um sie mit dieser Übung zu verknüpfen.
            </p>
            <div class="mt-4">
                <button
                    @click="showAddPlayModal = true"
                    type="button"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-orange-700 bg-orange-100 hover:bg-orange-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
                >
                    <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Ersten Spielzug hinzufügen
                </button>
            </div>
        </div>

        <!-- Add Play Modal -->
        <div v-if="showAddPlayModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showAddPlayModal = false"></div>

                <!-- Modal panel -->
                <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Spielzug hinzufügen
                        </h3>
                        <div class="mt-4">
                            <!-- Search -->
                            <div class="mb-4">
                                <input
                                    v-model="playSearchQuery"
                                    type="text"
                                    placeholder="Spielzug suchen..."
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-orange-300 focus:ring focus:ring-orange-200 focus:ring-opacity-50"
                                />
                            </div>

                            <!-- Category Filter -->
                            <div class="mb-4">
                                <select
                                    v-model="selectedCategory"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-orange-300 focus:ring focus:ring-orange-200 focus:ring-opacity-50"
                                >
                                    <option value="">Alle Kategorien</option>
                                    <option v-for="category in playCategories" :key="category" :value="category">
                                        {{ formatCategory(category) }}
                                    </option>
                                </select>
                            </div>

                            <!-- Play List -->
                            <div class="max-h-96 overflow-y-auto space-y-2">
                                <div
                                    v-for="play in filteredPlays"
                                    :key="play.id"
                                    @click="addPlay(play)"
                                    class="p-3 border border-gray-200 rounded-md hover:bg-gray-50 cursor-pointer transition-colors"
                                    :class="{ 'opacity-50 bg-gray-100': isPlaySelected(play.id) }"
                                >
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <div class="flex items-center">
                                                <h4 class="font-medium text-gray-900">{{ play.name }}</h4>
                                                <span
                                                    v-if="play.category"
                                                    class="ml-2 px-2 py-1 text-xs rounded-full font-medium bg-orange-100 text-orange-800"
                                                >
                                                    {{ formatCategory(play.category) }}
                                                </span>
                                            </div>
                                            <p v-if="play.description" class="text-sm text-gray-600 mt-1">{{ play.description }}</p>
                                            <div v-if="play.tags && play.tags.length > 0" class="flex flex-wrap gap-1 mt-2">
                                                <span
                                                    v-for="tag in play.tags.slice(0, 3)"
                                                    :key="tag"
                                                    class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded"
                                                >
                                                    {{ tag }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex items-center ml-4">
                                            <!-- Thumbnail -->
                                            <div v-if="play.thumbnail_path" class="mr-3">
                                                <img
                                                    :src="play.thumbnail_path"
                                                    :alt="play.name"
                                                    class="h-16 w-auto rounded border border-gray-200"
                                                />
                                            </div>
                                            <div v-if="isPlaySelected(play.id)" class="text-green-600">
                                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="filteredPlays.length === 0" class="text-center py-4 text-gray-500">
                                    Keine Spielzüge gefunden
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-6 flex justify-end space-x-3">
                        <button
                            @click="showAddPlayModal = false"
                            type="button"
                            class="inline-flex justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
                        >
                            Schließen
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview Modal -->
        <div v-if="showPreviewModal && previewingPlay" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="preview-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closePreview"></div>

                <!-- Modal panel -->
                <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full sm:p-6">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="preview-title">
                            {{ previewingPlay.name }}
                        </h3>
                        <TacticBoardViewer
                            v-if="previewingPlay.play_data"
                            :play-data="previewingPlay.play_data"
                            :court-type="previewingPlay.court_type || 'half_horizontal'"
                        />
                        <p v-else class="text-gray-500 text-center py-8">
                            Keine Spielfeld-Daten verfügbar
                        </p>
                    </div>
                    <div class="mt-5 sm:mt-6 flex justify-end">
                        <button
                            @click="closePreview"
                            type="button"
                            class="inline-flex justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
                        >
                            Schließen
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import TacticBoardViewer from './TacticBoardViewer.vue'

const props = defineProps({
    plays: {
        type: Array,
        default: () => []
    },
    initialSelectedPlays: {
        type: Array,
        default: () => []
    },
    showPreview: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['update:selectedPlays'])

// State
const selectedPlays = ref([...props.initialSelectedPlays].map(p => ({ ...p, isEditing: false })))
const showAddPlayModal = ref(false)
const showPreviewModal = ref(false)
const previewingPlay = ref(null)
const playSearchQuery = ref('')
const selectedCategory = ref('')

// Drag & Drop state
const isDragging = ref(false)
const draggedIndex = ref(-1)
const dragOverIndex = ref(-1)

// Computed
const playCategories = computed(() => {
    const categories = [...new Set(props.plays.map(play => play.category))]
    return categories.filter(Boolean)
})

const filteredPlays = computed(() => {
    let filtered = props.plays

    // Filter by search query
    if (playSearchQuery.value) {
        const query = playSearchQuery.value.toLowerCase()
        filtered = filtered.filter(play =>
            play.name.toLowerCase().includes(query) ||
            (play.description && play.description.toLowerCase().includes(query))
        )
    }

    // Filter by category
    if (selectedCategory.value) {
        filtered = filtered.filter(play => play.category === selectedCategory.value)
    }

    return filtered
})

// Watch for changes and emit
watch(selectedPlays, (newValue) => {
    emit('update:selectedPlays', newValue.map(p => ({
        id: p.id,
        play_id: p.id,
        order: p.order,
        notes: p.notes || ''
    })))
}, { deep: true })

// Watch for initialSelectedPlays changes
watch(() => props.initialSelectedPlays, (newValue) => {
    selectedPlays.value = [...newValue].map(p => ({ ...p, isEditing: false }))
}, { deep: true })

// HTML5 Drag & Drop Methods
function handleDragStart(index, event) {
    isDragging.value = true
    draggedIndex.value = index
    event.dataTransfer.effectAllowed = 'move'
    event.dataTransfer.setData('text/html', index.toString())
    event.target.style.opacity = '0.5'
}

function handleDragEnd(event) {
    isDragging.value = false
    draggedIndex.value = -1
    dragOverIndex.value = -1
    event.target.style.opacity = '1'
}

function handleDragOver(index) {
    if (isDragging.value && draggedIndex.value !== index) {
        dragOverIndex.value = index
    }
}

function handleDrop(dropIndex) {
    if (isDragging.value && draggedIndex.value !== dropIndex) {
        const draggedItem = selectedPlays.value[draggedIndex.value]
        const newPlays = [...selectedPlays.value]

        newPlays.splice(draggedIndex.value, 1)
        const insertIndex = draggedIndex.value < dropIndex ? dropIndex - 1 : dropIndex
        newPlays.splice(insertIndex, 0, draggedItem)

        selectedPlays.value = newPlays
        updatePlayOrder()
    }

    dragOverIndex.value = -1
}

// Other Methods
function addPlay(play) {
    if (!isPlaySelected(play.id)) {
        const playCopy = {
            ...play,
            notes: '',
            order: selectedPlays.value.length + 1,
            isEditing: false
        }
        selectedPlays.value.push(playCopy)
        updatePlayOrder()
    }
}

function removePlay(index) {
    selectedPlays.value.splice(index, 1)
    updatePlayOrder()
}

function isPlaySelected(playId) {
    return selectedPlays.value.some(play => play.id === playId)
}

function clearAllPlays() {
    if (confirm('Möchten Sie wirklich alle Spielzüge entfernen?')) {
        selectedPlays.value = []
    }
}

function togglePlayEdit(play) {
    play.isEditing = !play.isEditing
}

function updatePlayOrder() {
    selectedPlays.value.forEach((play, index) => {
        play.order = index + 1
    })
}

function previewPlay(play) {
    previewingPlay.value = play
    showPreviewModal.value = true
}

function closePreview() {
    showPreviewModal.value = false
    previewingPlay.value = null
}

function formatCategory(category) {
    const categoryMap = {
        'offense': 'Angriff',
        'defense': 'Verteidigung',
        'transition': 'Transition',
        'out_of_bounds': 'Einwurf',
        'press_break': 'Press-Breaker',
        'set_play': 'Spielzug',
        'quick_hitter': 'Schnellangriff',
        'motion': 'Motion Offense',
        'zone_offense': 'Zone Offense',
        'man_defense': 'Mann-Verteidigung',
        'zone_defense': 'Zonen-Verteidigung'
    }
    return categoryMap[category] || category
}
</script>

<style scoped>
.play-drag-handle {
    touch-action: none;
}

.transition-all {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes dragPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.02); }
}

.cursor-move:active {
    animation: dragPulse 0.5s ease-in-out infinite;
}
</style>
