<template>
    <div class="tactic-board-toolbar">
        <!-- Tool Selection -->
        <div class="toolbar-section">
            <span class="section-label">Werkzeuge</span>
            <div class="tool-buttons">
                <button
                    v-for="tool in tools"
                    :key="tool.id"
                    :class="['tool-btn', { active: currentTool === tool.id }]"
                    :title="tool.label"
                    @click="selectTool(tool.id)"
                >
                    <component :is="tool.icon" class="h-5 w-5" />
                </button>
            </div>
        </div>

        <!-- Divider -->
        <div class="toolbar-divider"></div>

        <!-- Team Selection (for player tool) -->
        <div v-if="currentTool === 'player'" class="toolbar-section">
            <span class="section-label">Team</span>
            <div class="tool-buttons">
                <button
                    :class="['tool-btn', { active: selectedTeam === 'offense' }]"
                    title="Offense"
                    @click="selectedTeam = 'offense'"
                >
                    <span class="team-indicator offense">O</span>
                </button>
                <button
                    :class="['tool-btn', { active: selectedTeam === 'defense' }]"
                    title="Defense"
                    @click="selectedTeam = 'defense'"
                >
                    <span class="team-indicator defense">D</span>
                </button>
            </div>
        </div>

        <!-- Path Type (for line tools) -->
        <div v-if="isPathTool" class="toolbar-section">
            <span class="section-label">Linientyp</span>
            <div class="color-picker">
                <input
                    type="color"
                    v-model="lineColor"
                    title="Linienfarbe"
                />
            </div>
        </div>

        <!-- Divider -->
        <div class="toolbar-divider"></div>

        <!-- Court Type Selection -->
        <div class="toolbar-section">
            <span class="section-label">Spielfeld</span>
            <select v-model="courtTypeLocal" class="court-select">
                <option value="half_horizontal">Halbes Feld (horizontal)</option>
                <option value="full">Ganzes Feld</option>
                <option value="half_vertical">Halbes Feld (vertikal)</option>
            </select>
        </div>

        <!-- Divider -->
        <div class="toolbar-divider"></div>

        <!-- Actions -->
        <div class="toolbar-section">
            <span class="section-label">Aktionen</span>
            <div class="tool-buttons">
                <button
                    class="tool-btn"
                    title="Team-Einstellungen"
                    @click="$emit('toggle-team-settings')"
                >
                    <Cog6ToothIcon class="h-5 w-5" />
                </button>
                <button
                    class="tool-btn"
                    title="Standard-Aufstellung"
                    @click="$emit('add-default-players')"
                >
                    <UsersIcon class="h-5 w-5" />
                </button>
                <button
                    class="tool-btn"
                    title="Rückgängig"
                    :disabled="!canUndo"
                    @click="$emit('undo')"
                >
                    <ArrowUturnLeftIcon class="h-5 w-5" />
                </button>
                <button
                    class="tool-btn"
                    title="Wiederholen"
                    :disabled="!canRedo"
                    @click="$emit('redo')"
                >
                    <ArrowUturnRightIcon class="h-5 w-5" />
                </button>
                <button
                    class="tool-btn danger"
                    title="Alles löschen"
                    @click="$emit('clear-all')"
                >
                    <TrashIcon class="h-5 w-5" />
                </button>
            </div>
        </div>

        <!-- Zoom Controls -->
        <div class="toolbar-section">
            <span class="section-label">Zoom</span>
            <div class="zoom-controls">
                <button
                    class="tool-btn small"
                    title="Verkleinern"
                    @click="$emit('zoom-out')"
                >
                    <span class="zoom-icon">-</span>
                </button>
                <span class="zoom-value">{{ zoomPercent }}%</span>
                <button
                    class="tool-btn small"
                    title="Vergrößern"
                    @click="$emit('zoom-in')"
                >
                    <span class="zoom-icon">+</span>
                </button>
                <button
                    class="tool-btn small"
                    title="Zurücksetzen"
                    @click="$emit('zoom-reset')"
                    :disabled="zoomPercent === 100"
                >
                    <span class="zoom-reset">1:1</span>
                </button>
            </div>
        </div>

        <!-- Divider -->
        <div class="toolbar-divider"></div>

        <!-- Grid Controls -->
        <div class="toolbar-section">
            <span class="section-label">Raster</span>
            <div class="grid-controls">
                <button
                    :class="['tool-btn', { active: gridEnabled }]"
                    title="Raster ein/aus"
                    @click="$emit('toggle-grid')"
                >
                    <TableCellsIcon class="h-5 w-5" />
                </button>
                <select
                    v-if="gridEnabled"
                    :value="gridSize"
                    class="grid-size-select"
                    title="Rastergröße"
                    @change="$emit('update:gridSize', Number($event.target.value))"
                >
                    <option value="10">10px</option>
                    <option value="20">20px</option>
                    <option value="25">25px</option>
                    <option value="50">50px</option>
                </select>
            </div>
        </div>

        <!-- Export Actions -->
        <div class="toolbar-section ml-auto">
            <span class="section-label">Export</span>
            <div class="tool-buttons">
                <button
                    class="tool-btn"
                    title="Als PNG herunterladen"
                    @click="$emit('export-png')"
                >
                    <PhotoIcon class="h-5 w-5" />
                </button>
                <button
                    class="tool-btn"
                    title="Als PDF herunterladen"
                    @click="$emit('export-pdf')"
                >
                    <DocumentIcon class="h-5 w-5" />
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import {
    CursorArrowRaysIcon,
    UserIcon,
    ArrowLongRightIcon,
    ArrowTrendingUpIcon,
    PencilIcon,
    StopIcon,
    ChatBubbleLeftIcon,
    ArrowUturnLeftIcon,
    ArrowUturnRightIcon,
    TrashIcon,
    PhotoIcon,
    DocumentIcon,
    UsersIcon,
    PencilSquareIcon,
    ArrowUpRightIcon,
    Square2StackIcon,
    EllipsisHorizontalCircleIcon,
    TableCellsIcon,
    Cog6ToothIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    currentTool: {
        type: String,
        default: 'select',
    },
    courtType: {
        type: String,
        default: 'half_horizontal',
    },
    canUndo: {
        type: Boolean,
        default: false,
    },
    canRedo: {
        type: Boolean,
        default: false,
    },
    zoomPercent: {
        type: Number,
        default: 100,
    },
    gridEnabled: {
        type: Boolean,
        default: false,
    },
    gridSize: {
        type: Number,
        default: 20,
    },
});

const emit = defineEmits([
    'update:currentTool',
    'update:courtType',
    'undo',
    'redo',
    'clear-all',
    'add-default-players',
    'export-png',
    'export-pdf',
    'zoom-in',
    'zoom-out',
    'zoom-reset',
    'toggle-grid',
    'update:gridSize',
    'toggle-team-settings',
]);

// Local state
const selectedTeam = ref('offense');
const lineColor = ref('#ffffff');

// Tool definitions
const tools = [
    { id: 'select', label: 'Auswählen', icon: CursorArrowRaysIcon },
    { id: 'player', label: 'Spieler hinzufügen', icon: UserIcon },
    { id: 'movement', label: 'Bewegungslinie', icon: ArrowLongRightIcon },
    { id: 'pass', label: 'Passlinie', icon: ArrowTrendingUpIcon },
    { id: 'dribble', label: 'Dribbellinie', icon: PencilIcon },
    { id: 'freehand', label: 'Freihand zeichnen', icon: PencilSquareIcon },
    { id: 'screen', label: 'Screen/Block', icon: StopIcon },
    { id: 'circle', label: 'Kreis/Zone', icon: EllipsisHorizontalCircleIcon },
    { id: 'rectangle', label: 'Rechteck/Zone', icon: Square2StackIcon },
    { id: 'arrow', label: 'Pfeil', icon: ArrowUpRightIcon },
    { id: 'text', label: 'Text', icon: ChatBubbleLeftIcon },
];

// Check if current tool is a path tool (shows line color picker)
const isPathTool = computed(() => {
    return ['movement', 'pass', 'dribble', 'freehand'].includes(props.currentTool);
});

// Court type local model
const courtTypeLocal = computed({
    get: () => props.courtType,
    set: (value) => emit('update:courtType', value),
});

// Select tool
const selectTool = (toolId) => {
    emit('update:currentTool', toolId);
};

// Watch for team changes and emit
watch(selectedTeam, (value) => {
    // This would be used when adding players
});

// Watch for line color changes
watch(lineColor, (value) => {
    // This would be used when drawing lines
});
</script>

<style scoped>
.tactic-board-toolbar {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 12px;
    background: #1f2937;
    border-radius: 8px;
    flex-wrap: wrap;
}

.toolbar-section {
    display: flex;
    align-items: center;
    gap: 8px;
}

.section-label {
    font-size: 11px;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.tool-buttons {
    display: flex;
    gap: 4px;
}

.tool-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border: none;
    border-radius: 6px;
    background: #374151;
    color: #d1d5db;
    cursor: pointer;
    transition: all 0.15s ease;
}

.tool-btn:hover:not(:disabled) {
    background: #4b5563;
    color: #ffffff;
}

.tool-btn.active {
    background: #2563eb;
    color: #ffffff;
}

.tool-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.tool-btn.danger:hover:not(:disabled) {
    background: #dc2626;
    color: #ffffff;
}

.toolbar-divider {
    width: 1px;
    height: 28px;
    background: #374151;
}

.team-indicator {
    font-weight: bold;
    font-size: 14px;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.team-indicator.offense {
    background: #2563eb;
    color: white;
}

.team-indicator.defense {
    background: #dc2626;
    color: white;
}

.color-picker input[type="color"] {
    width: 32px;
    height: 32px;
    padding: 0;
    border: 2px solid #374151;
    border-radius: 6px;
    cursor: pointer;
}

.color-picker input[type="color"]::-webkit-color-swatch-wrapper {
    padding: 2px;
}

.color-picker input[type="color"]::-webkit-color-swatch {
    border: none;
    border-radius: 4px;
}

.court-select {
    padding: 6px 10px;
    background: #374151;
    border: 1px solid #4b5563;
    border-radius: 6px;
    color: #d1d5db;
    font-size: 13px;
    cursor: pointer;
}

.court-select:hover {
    border-color: #6b7280;
}

.court-select:focus {
    outline: none;
    border-color: #2563eb;
}

.ml-auto {
    margin-left: auto;
}

/* Zoom Controls */
.zoom-controls {
    display: flex;
    align-items: center;
    gap: 4px;
}

.tool-btn.small {
    width: 28px;
    height: 28px;
    font-size: 16px;
    font-weight: bold;
}

.zoom-icon {
    font-size: 18px;
    line-height: 1;
}

.zoom-value {
    font-size: 12px;
    color: #d1d5db;
    min-width: 40px;
    text-align: center;
}

.zoom-reset {
    font-size: 10px;
    font-weight: 600;
}

/* Grid Controls */
.grid-controls {
    display: flex;
    align-items: center;
    gap: 6px;
}

.grid-size-select {
    padding: 4px 8px;
    background: #374151;
    border: 1px solid #4b5563;
    border-radius: 4px;
    color: #d1d5db;
    font-size: 12px;
    cursor: pointer;
}

.grid-size-select:hover {
    border-color: #6b7280;
}

.grid-size-select:focus {
    outline: none;
    border-color: #2563eb;
}
</style>
