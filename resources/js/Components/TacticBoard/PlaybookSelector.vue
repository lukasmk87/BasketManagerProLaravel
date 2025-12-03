<template>
    <div class="space-y-6">
        <!-- Header with Add Playbook Button -->
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900">
                Playbooks
                <span v-if="selectedPlaybooks.length > 0" class="text-sm text-gray-500 ml-2">
                    ({{ selectedPlaybooks.length }})
                </span>
            </h3>
            <button
                @click="showAddModal = true"
                type="button"
                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-orange-700 bg-orange-100 hover:bg-orange-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
            >
                <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Playbook hinzufügen
            </button>
        </div>

        <!-- Selected Playbooks List -->
        <div v-if="selectedPlaybooks.length > 0" class="space-y-4">
            <div
                v-for="playbook in selectedPlaybooks"
                :key="playbook.id"
                class="bg-white border border-gray-200 rounded-lg overflow-hidden"
            >
                <!-- Playbook Header -->
                <div
                    class="flex items-center justify-between p-4 bg-gray-50 cursor-pointer"
                    @click="toggleExpand(playbook.id)"
                >
                    <div class="flex items-center">
                        <svg
                            class="h-5 w-5 text-gray-400 mr-3 transition-transform duration-200"
                            :class="{ 'rotate-90': expandedPlaybooks.includes(playbook.id) }"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                        <div>
                            <h4 class="font-medium text-gray-900">{{ playbook.name }}</h4>
                            <p v-if="playbook.description" class="text-sm text-gray-500">{{ playbook.description }}</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-500">
                            {{ playbook.plays?.length || 0 }} Spielzüge
                        </span>
                        <button
                            @click.stop="detachPlaybook(playbook.id)"
                            type="button"
                            class="text-red-600 hover:text-red-900"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Playbook Plays (Expandable) -->
                <div v-if="expandedPlaybooks.includes(playbook.id)" class="p-4 border-t">
                    <div v-if="playbook.plays && playbook.plays.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        <div
                            v-for="play in playbook.plays"
                            :key="play.id"
                            class="bg-gray-50 rounded-lg p-3 cursor-pointer hover:bg-gray-100 transition-colors"
                            @click="previewPlay(play)"
                        >
                            <!-- Thumbnail or Placeholder -->
                            <div class="aspect-video bg-gray-800 rounded mb-2 overflow-hidden flex items-center justify-center">
                                <img
                                    v-if="play.thumbnail_path"
                                    :src="play.thumbnail_path"
                                    :alt="play.name"
                                    class="w-full h-full object-cover"
                                />
                                <svg v-else class="h-8 w-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                                </svg>
                            </div>
                            <h5 class="text-sm font-medium text-gray-900 truncate">{{ play.name }}</h5>
                            <p v-if="play.category" class="text-xs text-gray-500">{{ formatCategory(play.category) }}</p>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-500 text-center py-4">
                        Keine Spielzüge in diesem Playbook
                    </p>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-8 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Keine Playbooks verknüpft</h3>
            <p class="mt-1 text-sm text-gray-500">
                Fügen Sie Playbooks hinzu, um Ihre Spielvorbereitung zu organisieren.
            </p>
            <div class="mt-4">
                <button
                    @click="showAddModal = true"
                    type="button"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-orange-700 bg-orange-100 hover:bg-orange-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
                >
                    <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Erstes Playbook hinzufügen
                </button>
            </div>
        </div>

        <!-- Add Playbook Modal -->
        <div v-if="showAddModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showAddModal = false"></div>

                <!-- Modal panel -->
                <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Playbook hinzufügen
                        </h3>
                        <div class="mt-4">
                            <!-- Search -->
                            <div class="mb-4">
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Playbook suchen..."
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-orange-300 focus:ring focus:ring-orange-200 focus:ring-opacity-50"
                                />
                            </div>

                            <!-- Playbook List -->
                            <div class="max-h-80 overflow-y-auto space-y-2">
                                <div
                                    v-for="playbook in filteredPlaybooks"
                                    :key="playbook.id"
                                    @click="attachPlaybook(playbook.id)"
                                    class="p-3 border border-gray-200 rounded-md hover:bg-gray-50 cursor-pointer transition-colors"
                                    :class="{ 'opacity-50 bg-gray-100': isPlaybookSelected(playbook.id) }"
                                >
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h4 class="font-medium text-gray-900">{{ playbook.name }}</h4>
                                            <p v-if="playbook.description" class="text-sm text-gray-600 mt-1">{{ playbook.description }}</p>
                                            <div class="flex items-center mt-2 text-xs text-gray-500">
                                                <span class="bg-gray-100 px-2 py-1 rounded">
                                                    {{ playbook.plays?.length || 0 }} Spielzüge
                                                </span>
                                                <span v-if="playbook.category" class="ml-2 bg-orange-100 text-orange-800 px-2 py-1 rounded">
                                                    {{ playbook.category }}
                                                </span>
                                            </div>
                                        </div>
                                        <div v-if="isPlaybookSelected(playbook.id)" class="text-green-600">
                                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="filteredPlaybooks.length === 0" class="text-center py-4 text-gray-500">
                                    Keine Playbooks gefunden
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-6 flex justify-end space-x-3">
                        <button
                            @click="showAddModal = false"
                            type="button"
                            class="inline-flex justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
                        >
                            Schließen
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Play Preview Modal -->
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
import { ref, computed } from 'vue'
import TacticBoardViewer from './TacticBoardViewer.vue'

const props = defineProps({
    playbooks: {
        type: Array,
        default: () => []
    },
    selectedPlaybooks: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['attach', 'detach'])

// State
const showAddModal = ref(false)
const showPreviewModal = ref(false)
const previewingPlay = ref(null)
const searchQuery = ref('')
const expandedPlaybooks = ref([])

// Computed
const filteredPlaybooks = computed(() => {
    let filtered = props.playbooks

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        filtered = filtered.filter(playbook =>
            playbook.name.toLowerCase().includes(query) ||
            (playbook.description && playbook.description.toLowerCase().includes(query))
        )
    }

    return filtered
})

// Methods
function isPlaybookSelected(playbookId) {
    return props.selectedPlaybooks.some(pb => pb.id === playbookId)
}

function attachPlaybook(playbookId) {
    if (!isPlaybookSelected(playbookId)) {
        emit('attach', playbookId)
        showAddModal.value = false
    }
}

function detachPlaybook(playbookId) {
    if (confirm('Möchten Sie dieses Playbook wirklich entfernen?')) {
        emit('detach', playbookId)
    }
}

function toggleExpand(playbookId) {
    const index = expandedPlaybooks.value.indexOf(playbookId)
    if (index === -1) {
        expandedPlaybooks.value.push(playbookId)
    } else {
        expandedPlaybooks.value.splice(index, 1)
    }
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
.rotate-90 {
    transform: rotate(90deg);
}
</style>
