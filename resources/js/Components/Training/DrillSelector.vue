<template>
    <div class="space-y-6">
        <!-- Header with Add Drill Button -->
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900">
                Übungen
                <span v-if="selectedDrills.length > 0" class="text-sm text-gray-500 ml-2">
                    ({{ selectedDrills.length }}) - {{ totalDuration }} Min.
                </span>
            </h3>
            <div class="flex gap-2">
                <button
                    @click="showAddDrillModal = true"
                    type="button"
                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Übung hinzufügen
                </button>
                <button
                    v-if="selectedDrills.length > 0"
                    @click="clearAllDrills"
                    type="button"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    Alle entfernen
                </button>
            </div>
        </div>

        <!-- Selected Drills List (Sortable with HTML5 Drag & Drop) -->
        <div v-if="selectedDrills.length > 0" class="space-y-3">
            <div class="text-sm text-gray-600 bg-gray-50 p-3 rounded-md">
                <div class="flex items-center">
                    <svg class="h-4 w-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                    </svg>
                    Ziehen Sie die Übungen, um ihre Reihenfolge zu ändern
                </div>
            </div>
            
            <div class="space-y-3">
                <div 
                    v-for="(drill, index) in selectedDrills" 
                    :key="drill.id"
                    :draggable="true"
                    @dragstart="handleDragStart(index, $event)"
                    @dragend="handleDragEnd"
                    @dragover.prevent="handleDragOver(index)"
                    @drop.prevent="handleDrop(index)"
                    class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-all duration-200 cursor-move"
                    :class="{ 
                        'ring-2 ring-indigo-500': drill.isEditing,
                        'opacity-50 scale-95': isDragging && draggedIndex === index,
                        'border-indigo-300 bg-indigo-50': dragOverIndex === index && draggedIndex !== index
                    }"
                >
                    <div class="flex items-start justify-between">
                        <!-- Drag Handle -->
                        <div class="drill-drag-handle flex-shrink-0 mr-3 mt-1 cursor-move">
                            <svg class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </div>

                        <!-- Drill Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <span class="inline-flex items-center justify-center w-6 h-6 bg-indigo-100 text-indigo-800 text-xs font-medium rounded-full mr-3">
                                        {{ index + 1 }}
                                    </span>
                                    <h4 class="text-sm font-medium text-gray-900">{{ drill.name }}</h4>
                                    <span class="ml-2 text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                                        {{ drill.category }}
                                    </span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm text-gray-600">
                                        {{ drill.planned_duration || drill.estimated_duration }} Min.
                                    </span>
                                    <button
                                        @click="toggleDrillEdit(drill)"
                                        type="button"
                                        class="text-indigo-600 hover:text-indigo-900 text-sm"
                                    >
                                        {{ drill.isEditing ? 'Fertig' : 'Bearbeiten' }}
                                    </button>
                                    <button
                                        @click="removeDrill(index)"
                                        type="button"
                                        class="text-red-600 hover:text-red-900 ml-2"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Drill Description -->
                            <p v-if="drill.description" class="mt-2 text-sm text-gray-600 ml-9">
                                {{ drill.description }}
                            </p>

                            <!-- Edit Form (when editing) -->
                            <div v-if="drill.isEditing" class="mt-4 ml-9 space-y-3 bg-gray-50 p-3 rounded">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700">Dauer (Minuten)</label>
                                        <input
                                            v-model.number="drill.planned_duration"
                                            type="number"
                                            min="1"
                                            max="60"
                                            class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700">Teilnehmer</label>
                                        <input
                                            v-model.number="drill.participants_count"
                                            type="number"
                                            min="1"
                                            placeholder="Optional"
                                            class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Spezielle Anweisungen</label>
                                    <textarea
                                        v-model="drill.specific_instructions"
                                        rows="2"
                                        placeholder="Spezielle Anweisungen für diese Trainingseinheit..."
                                        class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    ></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Training Timeline Summary -->
            <div class="bg-indigo-50 p-4 rounded-lg">
                <h4 class="text-sm font-medium text-indigo-900 mb-2">Trainingsablauf</h4>
                <div class="flex flex-wrap gap-2">
                    <div 
                        v-for="(drill, index) in selectedDrills" 
                        :key="drill.id"
                        class="flex items-center bg-white px-3 py-1 rounded-full text-xs"
                    >
                        <span class="font-medium">{{ index + 1 }}.</span>
                        <span class="ml-1">{{ drill.name }}</span>
                        <span class="ml-1 text-gray-500">({{ drill.planned_duration || drill.estimated_duration }}min)</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-8 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Keine Übungen ausgewählt</h3>
            <p class="mt-1 text-sm text-gray-500">
                Fügen Sie Übungen hinzu, um Ihr Training zu strukturieren.
            </p>
            <div class="mt-4">
                <button
                    @click="showAddDrillModal = true"
                    type="button"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Erste Übung hinzufügen
                </button>
            </div>
        </div>

        <!-- Add Drill Modal -->
        <div v-if="showAddDrillModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showAddDrillModal = false"></div>

                <!-- Modal panel -->
                <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Übung hinzufügen
                        </h3>
                        <div class="mt-4">
                            <!-- Search -->
                            <div class="mb-4">
                                <input
                                    v-model="drillSearchQuery"
                                    type="text"
                                    placeholder="Übung suchen..."
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                />
                            </div>

                            <!-- Category Filter -->
                            <div class="mb-4">
                                <select
                                    v-model="selectedCategory"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                >
                                    <option value="">Alle Kategorien</option>
                                    <option v-for="category in drillCategories" :key="category" :value="category">
                                        {{ formatCategory(category) }}
                                    </option>
                                </select>
                            </div>

                            <!-- Drill List -->
                            <div class="max-h-60 overflow-y-auto space-y-2">
                                <div
                                    v-for="drill in filteredDrills"
                                    :key="drill.id"
                                    @click="addDrill(drill)"
                                    class="p-3 border border-gray-200 rounded-md hover:bg-gray-50 cursor-pointer transition-colors"
                                    :class="{ 'opacity-50': isDrillSelected(drill.id) }"
                                >
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="flex items-center">
                                                <h4 class="font-medium text-gray-900">{{ drill.name }}</h4>
                                                <span 
                                                    v-if="drill.status"
                                                    :class="getStatusClasses(drill.status)"
                                                    class="ml-2 px-2 py-1 text-xs rounded-full font-medium"
                                                >
                                                    {{ getStatusLabel(drill.status) }}
                                                </span>
                                            </div>
                                            <p class="text-sm text-gray-600 mt-1">{{ drill.description }}</p>
                                            <div class="flex items-center mt-2 text-xs text-gray-500">
                                                <span class="bg-gray-100 px-2 py-1 rounded mr-2">{{ formatCategory(drill.category) }}</span>
                                                <span>{{ drill.estimated_duration }} Min.</span>
                                            </div>
                                        </div>
                                        <div v-if="isDrillSelected(drill.id)" class="text-green-600">
                                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="filteredDrills.length === 0" class="text-center py-4 text-gray-500">
                                    Keine Übungen gefunden
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-6 flex justify-end space-x-3">
                        <button
                            @click="showAddDrillModal = false"
                            type="button"
                            class="inline-flex justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
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

const props = defineProps({
    drills: {
        type: Array,
        default: () => []
    },
    initialSelectedDrills: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['update:selectedDrills'])

// State
const selectedDrills = ref([...props.initialSelectedDrills])
const showAddDrillModal = ref(false)
const drillSearchQuery = ref('')
const selectedCategory = ref('')

// Drag & Drop state
const isDragging = ref(false)
const draggedIndex = ref(-1)
const dragOverIndex = ref(-1)

// Computed
const totalDuration = computed(() => {
    return selectedDrills.value.reduce((total, drill) => {
        return total + (drill.planned_duration || drill.estimated_duration || 0)
    }, 0)
})

const drillCategories = computed(() => {
    const categories = [...new Set(props.drills.map(drill => drill.category))]
    return categories.filter(Boolean)
})

const filteredDrills = computed(() => {
    let filtered = props.drills

    // Filter by search query
    if (drillSearchQuery.value) {
        const query = drillSearchQuery.value.toLowerCase()
        filtered = filtered.filter(drill => 
            drill.name.toLowerCase().includes(query) ||
            (drill.description && drill.description.toLowerCase().includes(query))
        )
    }

    // Filter by category
    if (selectedCategory.value) {
        filtered = filtered.filter(drill => drill.category === selectedCategory.value)
    }

    return filtered
})

// Watch for changes and emit
watch(selectedDrills, (newValue) => {
    emit('update:selectedDrills', newValue)
}, { deep: true })

// HTML5 Drag & Drop Methods
function handleDragStart(index, event) {
    isDragging.value = true
    draggedIndex.value = index
    event.dataTransfer.effectAllowed = 'move'
    event.dataTransfer.setData('text/html', index.toString())
    
    // Add some visual feedback
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
        // Reorder the drills array
        const draggedItem = selectedDrills.value[draggedIndex.value]
        const newDrills = [...selectedDrills.value]
        
        // Remove the dragged item
        newDrills.splice(draggedIndex.value, 1)
        
        // Insert at new position
        const insertIndex = draggedIndex.value < dropIndex ? dropIndex - 1 : dropIndex
        newDrills.splice(insertIndex, 0, draggedItem)
        
        selectedDrills.value = newDrills
        updateDrillOrder()
    }
    
    dragOverIndex.value = -1
}

// Other Methods (unchanged)
function addDrill(drill) {
    if (!isDrillSelected(drill.id)) {
        const drillCopy = {
            ...drill,
            planned_duration: drill.estimated_duration,
            specific_instructions: '',
            participants_count: null,
            order_in_session: selectedDrills.value.length + 1,
            isEditing: false
        }
        selectedDrills.value.push(drillCopy)
        updateDrillOrder()
    }
}

function removeDrill(index) {
    selectedDrills.value.splice(index, 1)
    updateDrillOrder()
}

function isDrillSelected(drillId) {
    return selectedDrills.value.some(drill => drill.id === drillId)
}

function clearAllDrills() {
    if (confirm('Möchten Sie wirklich alle Übungen entfernen?')) {
        selectedDrills.value = []
    }
}

function toggleDrillEdit(drill) {
    drill.isEditing = !drill.isEditing
}

function updateDrillOrder() {
    selectedDrills.value.forEach((drill, index) => {
        drill.order_in_session = index + 1
    })
}

function formatCategory(category) {
    const categoryMap = {
        'warm_up': 'Aufwärmen',
        'ball_handling': 'Ballhandling',
        'shooting': 'Wurf',
        'passing': 'Pass',
        'defense': 'Verteidigung',
        'conditioning': 'Kondition',
        'scrimmage': 'Spielformen',
        'cool_down': 'Abwärmen',
        'team_offense': 'Team Angriff',
        'team_defense': 'Team Verteidigung',
        'individual': 'Einzeltraining'
    }
    return categoryMap[category] || category
}

function getStatusLabel(status) {
    const labels = {
        'draft': 'Entwurf',
        'pending_review': 'Zur Prüfung',
        'approved': 'Genehmigt',
        'rejected': 'Abgelehnt',
        'archived': 'Archiviert'
    }
    return labels[status] || status
}

function getStatusClasses(status) {
    const classes = {
        'draft': 'bg-orange-100 text-orange-800',
        'pending_review': 'bg-yellow-100 text-yellow-800',
        'approved': 'bg-blue-100 text-blue-800',
        'rejected': 'bg-red-100 text-red-800',
        'archived': 'bg-gray-100 text-gray-800'
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}
</script>

<style scoped>
.drill-drag-handle {
    touch-action: none;
}

/* Smooth transitions for drag and drop */
.transition-all {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

/* Dragging animation */
@keyframes dragPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.02); }
}

.cursor-move:active {
    animation: dragPulse 0.5s ease-in-out infinite;
}
</style>