<template>
    <div class="tactic-board-editor">
        <!-- Toolbar -->
        <TacticBoardToolbar
            :currentTool="board.currentTool.value"
            :courtType="board.courtType.value"
            :canUndo="history.canUndo.value"
            :canRedo="history.canRedo.value"
            @update:currentTool="board.currentTool.value = $event"
            @update:courtType="handleCourtTypeChange"
            @undo="handleUndo"
            @redo="handleRedo"
            @clear-all="handleClearAll"
            @add-default-players="board.addDefaultPlayers()"
            @export-png="handleExportPng"
            @export-pdf="handleExportPdf"
        />

        <!-- Canvas Container -->
        <div ref="containerRef" class="canvas-container">
            <v-stage
                ref="stageRef"
                :config="stageConfig"
                @mousedown="handleMouseDown"
                @mousemove="handleMouseMove"
                @mouseup="handleMouseUp"
                @touchstart="handleMouseDown"
                @touchmove="handleMouseMove"
                @touchend="handleMouseUp"
            >
                <!-- Court Layer -->
                <v-layer>
                    <component
                        :is="courtComponent"
                        :width="canvasWidth"
                        :height="canvasHeight"
                        :courtColor="board.courtColor.value"
                    />
                </v-layer>

                <!-- Elements Layer -->
                <v-layer>
                    <!-- Paths (movement, pass, dribble lines) -->
                    <component
                        v-for="path in board.paths.value"
                        :key="path.id"
                        :is="getPathComponent(path.type)"
                        :id="path.id"
                        :points="path.points"
                        :color="path.color"
                        :selected="board.selectedElementId.value === path.id"
                        @select="handleSelectPath(path)"
                        @update:points="handleUpdatePathPoints"
                    />

                    <!-- Current drawing path preview -->
                    <v-line
                        v-if="board.isDrawing.value && board.currentDrawingPoints.value.length > 0"
                        :config="{
                            points: flatDrawingPoints,
                            stroke: getCurrentDrawingColor(),
                            strokeWidth: 3,
                            lineCap: 'round',
                            lineJoin: 'round',
                            dash: board.currentTool.value === 'pass' ? [12, 6] : [],
                            opacity: 0.7,
                        }"
                    />

                    <!-- Shapes (screens) -->
                    <ScreenShape
                        v-for="shape in board.shapes.value"
                        :key="shape.id"
                        :id="shape.id"
                        :x="shape.x"
                        :y="shape.y"
                        :rotation="shape.rotation || 0"
                        :width="shape.width || 40"
                        :color="shape.color || '#ffffff'"
                        :selected="board.selectedElementId.value === shape.id"
                        @select="handleSelectShape(shape)"
                        @update:position="handleUpdateShapePosition"
                        @update:rotation="handleUpdateShapeRotation"
                    />

                    <!-- Annotations (text) -->
                    <TextAnnotation
                        v-for="annotation in board.annotations.value"
                        :key="annotation.id"
                        :id="annotation.id"
                        :x="annotation.x"
                        :y="annotation.y"
                        :content="annotation.content"
                        :fontSize="annotation.fontSize || 16"
                        :color="annotation.color || '#ffffff'"
                        :selected="board.selectedElementId.value === annotation.id"
                        @select="handleSelectAnnotation(annotation)"
                        @update:position="handleUpdateAnnotationPosition"
                        @edit="handleEditAnnotation"
                    />

                    <!-- Players -->
                    <PlayerToken
                        v-for="player in board.players.value"
                        :key="player.id"
                        :id="player.id"
                        :x="player.x"
                        :y="player.y"
                        :number="player.number"
                        :label="player.label"
                        :team="player.team"
                        :hasBall="player.hasBall"
                        :selected="board.selectedElementId.value === player.id"
                        @select="handleSelectPlayer(player)"
                        @update:position="handleUpdatePlayerPosition"
                    />
                </v-layer>
            </v-stage>
        </div>

        <!-- Properties Panel (when element selected) -->
        <div v-if="board.selectedElement.value" class="properties-panel">
            <div class="panel-header">
                <span>Eigenschaften</span>
                <button class="close-btn" @click="board.clearSelection()">×</button>
            </div>
            <div class="panel-content">
                <!-- Player properties -->
                <template v-if="board.selectedElementType.value === 'player'">
                    <div class="property-row">
                        <label>Nummer</label>
                        <input
                            type="text"
                            :value="board.selectedElement.value.number"
                            @input="updateSelectedPlayer('number', $event.target.value)"
                        />
                    </div>
                    <div class="property-row">
                        <label>Position</label>
                        <input
                            type="text"
                            :value="board.selectedElement.value.label"
                            @input="updateSelectedPlayer('label', $event.target.value)"
                        />
                    </div>
                    <div class="property-row">
                        <label>Team</label>
                        <select
                            :value="board.selectedElement.value.team"
                            @change="updateSelectedPlayer('team', $event.target.value)"
                        >
                            <option value="offense">Offense</option>
                            <option value="defense">Defense</option>
                        </select>
                    </div>
                    <div class="property-row">
                        <label>
                            <input
                                type="checkbox"
                                :checked="board.selectedElement.value.hasBall"
                                @change="updateSelectedPlayer('hasBall', $event.target.checked)"
                            />
                            Hat Ball
                        </label>
                    </div>
                </template>

                <!-- Annotation properties -->
                <template v-if="board.selectedElementType.value === 'annotation'">
                    <div class="property-row">
                        <label>Text</label>
                        <input
                            type="text"
                            :value="board.selectedElement.value.content"
                            @input="handleUpdateAnnotationContent($event.target.value)"
                        />
                    </div>
                </template>

                <!-- Delete button -->
                <button class="delete-btn" @click="handleDeleteSelected">
                    Element löschen
                </button>
            </div>
        </div>

        <!-- Text Input Modal -->
        <div v-if="showTextInput" class="text-input-modal">
            <div class="modal-content">
                <input
                    ref="textInputRef"
                    v-model="textInputValue"
                    type="text"
                    placeholder="Text eingeben..."
                    @keyup.enter="confirmTextInput"
                    @keyup.escape="cancelTextInput"
                />
                <div class="modal-buttons">
                    <button @click="confirmTextInput">OK</button>
                    <button @click="cancelTextInput">Abbrechen</button>
                </div>
            </div>
        </div>

        <!-- Animation Mode Toggle -->
        <div class="animation-toggle">
            <button
                :class="['toggle-btn', { active: isAnimationMode }]"
                @click="toggleAnimationMode"
            >
                <FilmIcon class="h-5 w-5" />
                <span>{{ isAnimationMode ? 'Animation-Modus' : 'Statisch' }}</span>
            </button>
        </div>

        <!-- Timeline (visible in animation mode) -->
        <TacticBoardTimeline
            v-if="isAnimationMode"
            ref="timelineRef"
            :animationData="animationData"
            :currentElements="{ players: board.players.value, shapes: board.shapes.value }"
            @preview="showAnimationPreview = true"
            @keyframe-added="handleKeyframeAdded"
            @keyframe-updated="handleKeyframeUpdated"
            @keyframe-deleted="handleKeyframeDeleted"
            @positions-changed="handleAnimationPositionsChanged"
        />

        <!-- Animation Preview Modal -->
        <AnimationPreview
            :show="showAnimationPreview"
            :playData="board.exportData()"
            :animationData="timelineRef?.exportAnimationData() || animationData"
            @close="showAnimationPreview = false"
        />
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick, markRaw } from 'vue';
import { FilmIcon } from '@heroicons/vue/24/outline';
import { useTacticBoard } from '@/composables/useTacticBoard';
import { useTacticHistory } from '@/composables/useTacticHistory';
import { useTacticExport } from '@/composables/useTacticExport';

// Components
import TacticBoardToolbar from './TacticBoardToolbar.vue';
import TacticBoardTimeline from './TacticBoardTimeline.vue';
import AnimationPreview from './Animation/AnimationPreview.vue';
import HalfCourtHorizontal from './Court/HalfCourtHorizontal.vue';
import FullCourt from './Court/FullCourt.vue';
import HalfCourtVertical from './Court/HalfCourtVertical.vue';
import PlayerToken from './Elements/PlayerToken.vue';
import MovementPath from './Elements/MovementPath.vue';
import PassLine from './Elements/PassLine.vue';
import DribblePath from './Elements/DribblePath.vue';
import ScreenShape from './Elements/ScreenShape.vue';
import TextAnnotation from './Elements/TextAnnotation.vue';

const props = defineProps({
    initialData: {
        type: Object,
        default: null,
    },
    playId: {
        type: [Number, String],
        default: null,
    },
});

const emit = defineEmits(['save', 'change', 'animation-change']);

// Refs
const containerRef = ref(null);
const stageRef = ref(null);
const textInputRef = ref(null);
const timelineRef = ref(null);

// Animation mode state
const isAnimationMode = ref(false);
const showAnimationPreview = ref(false);
const animationData = ref(props.initialData?.animation_data || null);

// Canvas dimensions
const canvasWidth = ref(700);
const canvasHeight = ref(500);

// Composables
const board = useTacticBoard(props.initialData?.play_data);
const history = useTacticHistory();
const exportUtil = useTacticExport();

// Text input modal
const showTextInput = ref(false);
const textInputValue = ref('');
const pendingTextPosition = ref({ x: 0, y: 0 });
const editingAnnotationId = ref(null);

// Stage configuration
const stageConfig = computed(() => ({
    width: canvasWidth.value,
    height: canvasHeight.value,
}));

// Court component based on type
const courtComponent = computed(() => {
    switch (board.courtType.value) {
        case 'full':
            return markRaw(FullCourt);
        case 'half_vertical':
            return markRaw(HalfCourtVertical);
        case 'half_horizontal':
        default:
            return markRaw(HalfCourtHorizontal);
    }
});

// Get path component based on type
const getPathComponent = (type) => {
    switch (type) {
        case 'pass':
            return markRaw(PassLine);
        case 'dribble':
            return markRaw(DribblePath);
        case 'movement':
        default:
            return markRaw(MovementPath);
    }
};

// Flatten drawing points for Konva line
const flatDrawingPoints = computed(() => {
    return board.currentDrawingPoints.value.flatMap(p => [p.x, p.y]);
});

// Get current drawing color based on tool
const getCurrentDrawingColor = () => {
    switch (board.currentTool.value) {
        case 'pass': return '#22c55e';
        case 'dribble': return '#f59e0b';
        default: return '#ffffff';
    }
};

// Initialize canvas dimensions
onMounted(() => {
    updateCanvasDimensions();
    window.addEventListener('resize', updateCanvasDimensions);

    // Initialize history with current state
    history.initializeState(board.exportData());
});

// Update canvas dimensions based on container and court type
const updateCanvasDimensions = () => {
    if (!containerRef.value) return;

    const container = containerRef.value;
    const maxWidth = container.clientWidth - 20;
    const maxHeight = container.clientHeight - 20;

    // Aspect ratios for different court types
    let aspectRatio;
    switch (board.courtType.value) {
        case 'full':
            aspectRatio = 15 / 28; // width / length
            break;
        case 'half_vertical':
            aspectRatio = 14 / 15;
            break;
        case 'half_horizontal':
        default:
            aspectRatio = 15 / 14;
            break;
    }

    // Calculate dimensions maintaining aspect ratio
    let width = maxWidth;
    let height = width / aspectRatio;

    if (height > maxHeight) {
        height = maxHeight;
        width = height * aspectRatio;
    }

    canvasWidth.value = Math.floor(width);
    canvasHeight.value = Math.floor(height);

    board.setCourtDimensions(canvasWidth.value, canvasHeight.value);
};

// Handle court type change
const handleCourtTypeChange = (newType) => {
    board.courtType.value = newType;
    nextTick(() => {
        updateCanvasDimensions();
    });
};

// Mouse/touch event handlers
const handleMouseDown = (e) => {
    const stage = e.target.getStage();
    const pos = stage.getPointerPosition();

    // Check if clicked on empty area
    const clickedOnEmpty = e.target === stage || e.target.getClassName() === 'Rect';

    if (clickedOnEmpty) {
        board.clearSelection();

        switch (board.currentTool.value) {
            case 'player':
                recordHistory();
                board.addPlayer({ x: pos.x, y: pos.y });
                break;

            case 'movement':
            case 'pass':
            case 'dribble':
                board.startDrawing(pos.x, pos.y);
                break;

            case 'screen':
                recordHistory();
                board.addScreen({ x: pos.x, y: pos.y });
                break;

            case 'text':
                pendingTextPosition.value = { x: pos.x, y: pos.y };
                editingAnnotationId.value = null;
                textInputValue.value = '';
                showTextInput.value = true;
                nextTick(() => {
                    textInputRef.value?.focus();
                });
                break;
        }
    }
};

const handleMouseMove = (e) => {
    if (!board.isDrawing.value) return;

    const stage = e.target.getStage();
    const pos = stage.getPointerPosition();
    board.continueDrawing(pos.x, pos.y);
};

const handleMouseUp = () => {
    if (board.isDrawing.value) {
        recordHistory();
        board.finishDrawing();
    }
};

// Selection handlers
const handleSelectPlayer = (player) => {
    board.selectElement(player.id, 'player');
};

const handleSelectPath = (path) => {
    board.selectElement(path.id, 'path');
};

const handleSelectShape = (shape) => {
    board.selectElement(shape.id, 'shape');
};

const handleSelectAnnotation = (annotation) => {
    board.selectElement(annotation.id, 'annotation');
};

// Update handlers
const handleUpdatePlayerPosition = ({ id, x, y }) => {
    board.updatePlayerPosition(id, x, y);
};

const handleUpdatePathPoints = ({ id, points }) => {
    board.updatePathPoints(id, points);
};

const handleUpdateShapePosition = ({ id, x, y }) => {
    board.updateShapePosition(id, x, y);
};

const handleUpdateShapeRotation = ({ id, rotation }) => {
    board.updateShapeRotation(id, rotation);
};

const handleUpdateAnnotationPosition = ({ id, x, y }) => {
    board.updateAnnotationPosition(id, x, y);
};

const handleUpdateAnnotationContent = (content) => {
    if (board.selectedElement.value) {
        board.updateAnnotationContent(board.selectedElementId.value, content);
    }
};

const handleEditAnnotation = ({ id }) => {
    const annotation = board.annotations.value.find(a => a.id === id);
    if (annotation) {
        editingAnnotationId.value = id;
        textInputValue.value = annotation.content;
        showTextInput.value = true;
        nextTick(() => {
            textInputRef.value?.focus();
        });
    }
};

// Update selected player property
const updateSelectedPlayer = (property, value) => {
    if (board.selectedElement.value) {
        recordHistory();
        board.updatePlayer(board.selectedElementId.value, { [property]: value });
    }
};

// Delete selected element
const handleDeleteSelected = () => {
    recordHistory();
    board.deleteSelected();
};

// Text input modal handlers
const confirmTextInput = () => {
    if (!textInputValue.value.trim()) {
        cancelTextInput();
        return;
    }

    recordHistory();

    if (editingAnnotationId.value) {
        board.updateAnnotationContent(editingAnnotationId.value, textInputValue.value);
    } else {
        board.addAnnotation({
            x: pendingTextPosition.value.x,
            y: pendingTextPosition.value.y,
            content: textInputValue.value,
        });
    }

    showTextInput.value = false;
    textInputValue.value = '';
    editingAnnotationId.value = null;
};

const cancelTextInput = () => {
    showTextInput.value = false;
    textInputValue.value = '';
    editingAnnotationId.value = null;
};

// History management
const recordHistory = () => {
    history.recordState(board.exportData());
};

const handleUndo = () => {
    const previousState = history.undo();
    if (previousState) {
        board.importData(previousState);
    }
};

const handleRedo = () => {
    const nextState = history.redo();
    if (nextState) {
        board.importData(nextState);
    }
};

const handleClearAll = () => {
    if (confirm('Möchten Sie wirklich alle Elemente löschen?')) {
        recordHistory();
        board.clearAll();
    }
};

// Export handlers
const handleExportPng = async () => {
    if (stageRef.value) {
        await exportUtil.downloadPng(stageRef.value.getStage(), 'spielzug');
    }
};

const handleExportPdf = () => {
    if (props.playId) {
        exportUtil.downloadPdf(props.playId);
    }
};

// Watch for changes and emit
watch(
    () => board.exportData(),
    (newData) => {
        emit('change', newData);
    },
    { deep: true }
);

// Animation mode toggle
const toggleAnimationMode = () => {
    isAnimationMode.value = !isAnimationMode.value;
};

// Animation event handlers
const handleKeyframeAdded = (data) => {
    animationData.value = timelineRef.value?.exportAnimationData() || null;
    emit('animation-change', animationData.value);
};

const handleKeyframeUpdated = (data) => {
    animationData.value = timelineRef.value?.exportAnimationData() || null;
    emit('animation-change', animationData.value);
};

const handleKeyframeDeleted = (index) => {
    animationData.value = timelineRef.value?.exportAnimationData() || null;
    emit('animation-change', animationData.value);
};

const handleAnimationPositionsChanged = (positions) => {
    // Update element positions during animation playback
    Object.entries(positions).forEach(([elementId, pos]) => {
        // Check if it's a player
        const player = board.players.value.find(p => p.id === elementId);
        if (player) {
            board.updatePlayerPosition(elementId, pos.x, pos.y);
            return;
        }

        // Check if it's a shape
        const shape = board.shapes.value.find(s => s.id === elementId);
        if (shape) {
            board.updateShapePosition(elementId, pos.x, pos.y);
            if (pos.rotation !== undefined) {
                board.updateShapeRotation(elementId, pos.rotation);
            }
        }
    });
};

// Export animation data
const exportAnimationData = () => {
    return timelineRef.value?.exportAnimationData() || animationData.value;
};

// Expose methods for parent components
defineExpose({
    exportData: () => board.exportData(),
    exportAnimationData,
    importData: (data) => board.importData(data),
    importAnimationData: (data) => {
        animationData.value = data;
        if (timelineRef.value) {
            timelineRef.value.importAnimationData(data);
        }
    },
    getStage: () => stageRef.value?.getStage(),
    isAnimationMode,
    toggleAnimationMode,
});
</script>

<style scoped>
.tactic-board-editor {
    display: flex;
    flex-direction: column;
    gap: 12px;
    background: #111827;
    padding: 16px;
    border-radius: 12px;
}

.canvas-container {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #0f172a;
    border-radius: 8px;
    padding: 10px;
    min-height: 400px;
    position: relative;
}

.properties-panel {
    position: absolute;
    top: 60px;
    right: 20px;
    width: 220px;
    background: #1f2937;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    z-index: 100;
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    border-bottom: 1px solid #374151;
    color: #ffffff;
    font-weight: 500;
}

.close-btn {
    background: none;
    border: none;
    color: #9ca3af;
    font-size: 20px;
    cursor: pointer;
    padding: 0;
    line-height: 1;
}

.close-btn:hover {
    color: #ffffff;
}

.panel-content {
    padding: 12px;
}

.property-row {
    margin-bottom: 12px;
}

.property-row label {
    display: block;
    font-size: 12px;
    color: #9ca3af;
    margin-bottom: 4px;
}

.property-row input[type="text"],
.property-row select {
    width: 100%;
    padding: 8px;
    background: #374151;
    border: 1px solid #4b5563;
    border-radius: 4px;
    color: #ffffff;
    font-size: 13px;
}

.property-row input[type="checkbox"] {
    margin-right: 8px;
}

.delete-btn {
    width: 100%;
    padding: 10px;
    background: #dc2626;
    border: none;
    border-radius: 4px;
    color: white;
    font-size: 13px;
    cursor: pointer;
    margin-top: 8px;
}

.delete-btn:hover {
    background: #b91c1c;
}

.text-input-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: #1f2937;
    padding: 20px;
    border-radius: 8px;
    min-width: 300px;
}

.modal-content input {
    width: 100%;
    padding: 10px;
    background: #374151;
    border: 1px solid #4b5563;
    border-radius: 4px;
    color: #ffffff;
    font-size: 14px;
    margin-bottom: 12px;
}

.modal-buttons {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
}

.modal-buttons button {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
}

.modal-buttons button:first-child {
    background: #2563eb;
    color: white;
}

.modal-buttons button:last-child {
    background: #374151;
    color: #d1d5db;
}

.animation-toggle {
    display: flex;
    justify-content: center;
    margin-top: 8px;
}

.toggle-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: #374151;
    border: none;
    border-radius: 8px;
    color: #d1d5db;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.toggle-btn:hover {
    background: #4b5563;
    color: #ffffff;
}

.toggle-btn.active {
    background: #e25822;
    color: #ffffff;
}
</style>
