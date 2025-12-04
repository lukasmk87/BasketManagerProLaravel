<template>
    <div class="tactic-board-editor">
        <!-- Toolbar -->
        <TacticBoardToolbar
            :currentTool="board.currentTool.value"
            :courtType="board.courtType.value"
            :canUndo="history.canUndo.value"
            :canRedo="history.canRedo.value"
            :zoomPercent="zoom.zoomPercent.value"
            :gridEnabled="board.gridEnabled.value"
            :gridSize="board.gridSize.value"
            @update:currentTool="board.currentTool.value = $event"
            @update:courtType="handleCourtTypeChange"
            @undo="handleUndo"
            @redo="handleRedo"
            @clear-all="handleClearAll"
            @add-default-players="board.addDefaultPlayers()"
            @export-png="handleExportPng"
            @export-pdf="handleExportPdf"
            @zoom-in="zoom.zoomIn"
            @zoom-out="zoom.zoomOut"
            @zoom-reset="zoom.resetZoom"
            @toggle-grid="board.toggleGrid"
            @update:gridSize="board.setGridSize"
            @toggle-team-settings="showTeamSettings = !showTeamSettings"
        />

        <!-- Canvas Container -->
        <div ref="containerRef" :class="canvasClass">
            <v-stage
                ref="stageRef"
                :config="stageConfig"
                @mousedown="handleMouseDown"
                @mousemove="handleMouseMove"
                @mouseup="handleMouseUp"
                @touchstart="handleMouseDown"
                @touchmove="handleMouseMove"
                @touchend="handleMouseUp"
                @wheel="handleWheel"
            >
                <!-- Court Layer -->
                <v-layer>
                    <component
                        :is="courtComponent"
                        :width="canvasWidth"
                        :height="canvasHeight"
                        :courtColor="board.courtColor.value"
                    />

                    <!-- Grid Overlay -->
                    <GridOverlay
                        :visible="board.gridEnabled.value"
                        :width="canvasWidth"
                        :height="canvasHeight"
                        :gridSize="board.gridSize.value"
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

                    <!-- Freehand paths -->
                    <FreehandPath
                        v-for="freehand in board.freehandPaths.value"
                        :key="freehand.id"
                        :id="freehand.id"
                        :points="freehand.points"
                        :color="freehand.color"
                        :strokeWidth="freehand.strokeWidth || 3"
                        :lineStyle="freehand.lineStyle || 'solid'"
                        :selected="board.selectedElementId.value === freehand.id"
                        @select="handleSelectFreehand(freehand)"
                        @update:points="handleUpdateFreehandPoints"
                    />

                    <!-- Circles -->
                    <CircleShape
                        v-for="circle in board.circles.value"
                        :key="circle.id"
                        :id="circle.id"
                        :x="circle.x"
                        :y="circle.y"
                        :radiusX="circle.radiusX"
                        :radiusY="circle.radiusY"
                        :fill="circle.fill"
                        :stroke="circle.stroke"
                        :strokeWidth="circle.strokeWidth"
                        :opacity="circle.opacity"
                        :selected="board.selectedElementId.value === circle.id"
                        @select="handleSelectCircle(circle)"
                        @update:position="handleUpdateCirclePosition"
                        @update:size="handleUpdateCircleSize"
                    />

                    <!-- Rectangles -->
                    <RectangleShape
                        v-for="rect in board.rectangles.value"
                        :key="rect.id"
                        :id="rect.id"
                        :x="rect.x"
                        :y="rect.y"
                        :width="rect.width"
                        :height="rect.height"
                        :rotation="rect.rotation"
                        :fill="rect.fill"
                        :stroke="rect.stroke"
                        :strokeWidth="rect.strokeWidth"
                        :selected="board.selectedElementId.value === rect.id"
                        @select="handleSelectRectangle(rect)"
                        @update:position="handleUpdateRectanglePosition"
                        @update:size="handleUpdateRectangleSize"
                        @update:rotation="handleUpdateRectangleRotation"
                    />

                    <!-- Arrows -->
                    <ArrowShape
                        v-for="arrow in board.arrows.value"
                        :key="arrow.id"
                        :id="arrow.id"
                        :points="arrow.points"
                        :color="arrow.color"
                        :strokeWidth="arrow.strokeWidth"
                        :pointerLength="arrow.pointerLength"
                        :pointerWidth="arrow.pointerWidth"
                        :selected="board.selectedElementId.value === arrow.id"
                        @select="handleSelectArrow(arrow)"
                        @update:points="handleUpdateArrowPoints"
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
                        :teamColors="board.teamColors.value"
                        :selected="board.selectedElementId.value === player.id"
                        @select="handleSelectPlayer(player)"
                        @update:position="handleUpdatePlayerPosition"
                    />

                    <!-- Ball -->
                    <BallElement
                        v-if="board.ball.value"
                        :id="board.ball.value.id"
                        :x="board.ball.value.x"
                        :y="board.ball.value.y"
                        :radius="board.ball.value.radius || 12"
                        :selected="board.selectedElementId.value === board.ball.value?.id"
                        @select="handleSelectBall"
                        @update:position="handleUpdateBallPosition"
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

                <!-- Freehand/Path properties (Line Style) -->
                <template v-if="board.selectedElementType.value === 'freehand' || board.selectedElementType.value === 'path'">
                    <LineStylePanel
                        :strokeWidth="board.selectedElement.value.strokeWidth || 3"
                        :lineStyle="board.selectedElement.value.lineStyle || 'solid'"
                        :color="board.selectedElement.value.color || '#ffffff'"
                        @update:strokeWidth="updateLineProperty('strokeWidth', $event)"
                        @update:lineStyle="updateLineProperty('lineStyle', $event)"
                        @update:color="updateLineProperty('color', $event)"
                    />
                </template>

                <!-- Delete button -->
                <button class="delete-btn" @click="handleDeleteSelected">
                    Element löschen
                </button>
            </div>
        </div>

        <!-- Team Settings Panel -->
        <TeamSettingsPanel
            v-if="showTeamSettings"
            :offenseColor="board.teamColors.value.offense"
            :defenseColor="board.teamColors.value.defense"
            :hasBall="!!board.ball.value"
            @close="showTeamSettings = false"
            @update:offenseColor="handleUpdateTeamColor('offense', $event)"
            @update:defenseColor="handleUpdateTeamColor('defense', $event)"
            @add-ball="handleAddBall"
            @remove-ball="handleRemoveBall"
        />

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
import { useTacticZoom } from '@/composables/useTacticZoom';
import { useTacticKeyboard } from '@/composables/useTacticKeyboard';

// Components
import TacticBoardToolbar from './TacticBoardToolbar.vue';
import TacticBoardTimeline from './TacticBoardTimeline.vue';
import AnimationPreview from './Animation/AnimationPreview.vue';
import HalfCourtHorizontal from './Court/HalfCourtHorizontal.vue';
import FullCourt from './Court/FullCourt.vue';
import HalfCourtVertical from './Court/HalfCourtVertical.vue';
import GridOverlay from './Court/GridOverlay.vue';
import PlayerToken from './Elements/PlayerToken.vue';
import MovementPath from './Elements/MovementPath.vue';
import PassLine from './Elements/PassLine.vue';
import DribblePath from './Elements/DribblePath.vue';
import ScreenShape from './Elements/ScreenShape.vue';
import TextAnnotation from './Elements/TextAnnotation.vue';
import FreehandPath from './Elements/FreehandPath.vue';
import CircleShape from './Elements/CircleShape.vue';
import RectangleShape from './Elements/RectangleShape.vue';
import ArrowShape from './Elements/ArrowShape.vue';
import BallElement from './Elements/BallElement.vue';
import LineStylePanel from './Panels/LineStylePanel.vue';
import TeamSettingsPanel from './Panels/TeamSettingsPanel.vue';

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
const zoom = useTacticZoom();

// Text input modal
const showTextInput = ref(false);
const textInputValue = ref('');
const pendingTextPosition = ref({ x: 0, y: 0 });
const editingAnnotationId = ref(null);

// Team settings panel
const showTeamSettings = ref(false);

// Keyboard shortcuts (must be after showTextInput is defined)
useTacticKeyboard({
    onToolChange: (tool) => {
        board.currentTool.value = tool;
    },
    onUndo: () => handleUndo(),
    onRedo: () => handleRedo(),
    onDelete: () => handleDeleteSelected(),
    onClearSelection: () => board.clearSelection(),
    onZoomIn: () => zoom.zoomIn(),
    onZoomOut: () => zoom.zoomOut(),
    onZoomReset: () => zoom.resetZoom(),
    onToggleGrid: () => board.toggleGrid(),
    isEnabled: () => !showTextInput.value, // Disable when text input is open
});

// Stage configuration with zoom
const stageConfig = computed(() => ({
    width: canvasWidth.value,
    height: canvasHeight.value,
    scaleX: zoom.scale.value,
    scaleY: zoom.scale.value,
    x: zoom.positionX.value,
    y: zoom.positionY.value,
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

// Canvas class based on current tool
const canvasClass = computed(() => {
    return {
        'canvas-container': true,
        'eraser-mode': board.currentTool.value === 'eraser',
    };
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

    // Apply snap-to-grid
    const snappedX = board.snapToGrid(pos.x);
    const snappedY = board.snapToGrid(pos.y);

    // Check if clicked on empty area
    const clickedOnEmpty = e.target === stage || e.target.getClassName() === 'Rect';

    if (clickedOnEmpty) {
        board.clearSelection();

        switch (board.currentTool.value) {
            case 'player':
                recordHistory();
                board.addPlayer({ x: snappedX, y: snappedY });
                break;

            case 'movement':
            case 'pass':
            case 'dribble':
                board.startDrawing(snappedX, snappedY);
                break;

            case 'freehand':
                board.startFreehandDrawing(pos.x, pos.y); // Freehand does not use grid snap
                break;

            case 'screen':
                recordHistory();
                board.addScreen({ x: snappedX, y: snappedY });
                break;

            case 'circle':
                recordHistory();
                board.addCircle({ x: snappedX, y: snappedY });
                break;

            case 'rectangle':
                recordHistory();
                board.addRectangle({ x: snappedX, y: snappedY });
                break;

            case 'arrow':
                recordHistory();
                board.addArrow({ x: snappedX, y: snappedY });
                break;

            case 'text':
                pendingTextPosition.value = { x: snappedX, y: snappedY };
                editingAnnotationId.value = null;
                textInputValue.value = '';
                showTextInput.value = true;
                nextTick(() => {
                    textInputRef.value?.focus();
                });
                break;

            case 'eraser':
                recordHistory();
                board.startErasing();
                board.eraseAtPosition(pos.x, pos.y);
                break;
        }
    }
};

const handleMouseMove = (e) => {
    const stage = e.target.getStage();
    const pos = stage.getPointerPosition();

    // Handle eraser tool - continuous erasing while dragging
    if (board.currentTool.value === 'eraser' && board.isErasing.value) {
        board.eraseAtPosition(pos.x, pos.y);
        return;
    }

    if (!board.isDrawing.value) return;

    // Handle based on current tool
    if (board.currentTool.value === 'freehand') {
        board.continueFreehandDrawing(pos.x, pos.y); // No snapping for freehand
    } else {
        // Apply snap-to-grid for path drawing
        const snappedX = board.snapToGrid(pos.x);
        const snappedY = board.snapToGrid(pos.y);
        board.continueDrawing(snappedX, snappedY);
    }
};

const handleMouseUp = () => {
    // Finish eraser mode
    if (board.currentTool.value === 'eraser' && board.isErasing.value) {
        board.finishErasing();
        return;
    }

    if (board.isDrawing.value) {
        recordHistory();

        // Handle based on current tool
        if (board.currentTool.value === 'freehand') {
            board.finishFreehandDrawing();
        } else {
            board.finishDrawing();
        }
    }
};

// Zoom event handler
const handleWheel = (e) => {
    zoom.handleWheel(e, canvasWidth.value, canvasHeight.value);
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

// New element selection handlers
const handleSelectFreehand = (freehand) => {
    board.selectElement(freehand.id, 'freehand');
};

const handleSelectCircle = (circle) => {
    board.selectElement(circle.id, 'circle');
};

const handleSelectRectangle = (rect) => {
    board.selectElement(rect.id, 'rectangle');
};

const handleSelectArrow = (arrow) => {
    board.selectElement(arrow.id, 'arrow');
};

// Update handlers with snap-to-grid support
const handleUpdatePlayerPosition = ({ id, x, y }) => {
    const snappedX = board.snapToGrid(x);
    const snappedY = board.snapToGrid(y);
    board.updatePlayerPosition(id, snappedX, snappedY);
};

const handleUpdatePathPoints = ({ id, points }) => {
    board.updatePathPoints(id, points);
};

const handleUpdateShapePosition = ({ id, x, y }) => {
    const snappedX = board.snapToGrid(x);
    const snappedY = board.snapToGrid(y);
    board.updateShapePosition(id, snappedX, snappedY);
};

const handleUpdateShapeRotation = ({ id, rotation }) => {
    board.updateShapeRotation(id, rotation);
};

const handleUpdateAnnotationPosition = ({ id, x, y }) => {
    const snappedX = board.snapToGrid(x);
    const snappedY = board.snapToGrid(y);
    board.updateAnnotationPosition(id, snappedX, snappedY);
};

const handleUpdateAnnotationContent = (content) => {
    if (board.selectedElement.value) {
        board.updateAnnotationContent(board.selectedElementId.value, content);
    }
};

// New element update handlers
const handleUpdateFreehandPoints = ({ id, points }) => {
    board.updateFreehandPoints(id, points);
};

const handleUpdateCirclePosition = ({ id, x, y }) => {
    const snappedX = board.snapToGrid(x);
    const snappedY = board.snapToGrid(y);
    board.updateCirclePosition(id, snappedX, snappedY);
};

const handleUpdateCircleSize = ({ id, radiusX, radiusY }) => {
    board.updateCircleSize(id, radiusX, radiusY);
};

const handleUpdateRectanglePosition = ({ id, x, y }) => {
    const snappedX = board.snapToGrid(x);
    const snappedY = board.snapToGrid(y);
    board.updateRectanglePosition(id, snappedX, snappedY);
};

const handleUpdateRectangleSize = ({ id, width, height }) => {
    board.updateRectangleSize(id, width, height);
};

const handleUpdateRectangleRotation = ({ id, rotation }) => {
    board.updateRectangleRotation(id, rotation);
};

const handleUpdateArrowPoints = ({ id, points }) => {
    board.updateArrowPoints(id, points);
};

// Ball handlers
const handleSelectBall = ({ id }) => {
    board.selectElement(id, 'ball');
};

const handleUpdateBallPosition = ({ id, x, y }) => {
    const snappedX = board.snapToGrid(x);
    const snappedY = board.snapToGrid(y);
    board.updateBallPosition(snappedX, snappedY);
};

const handleAddBall = () => {
    recordHistory();
    board.addBall({ x: canvasWidth.value / 2, y: canvasHeight.value / 2 });
};

const handleRemoveBall = () => {
    recordHistory();
    board.removeBall();
};

// Team color handlers
const handleUpdateTeamColor = (team, color) => {
    board.teamColors.value[team] = color;
};

// Update line properties (for freehand and path elements)
const updateLineProperty = (property, value) => {
    if (!board.selectedElement.value) return;

    const id = board.selectedElementId.value;
    const type = board.selectedElementType.value;

    if (type === 'freehand') {
        board.updateFreehand(id, { [property]: value });
    } else if (type === 'path') {
        // Update path element
        const path = board.paths.value.find(p => p.id === id);
        if (path) {
            path[property] = value;
        }
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

/* Eraser mode cursor */
.canvas-container.eraser-mode {
    cursor: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23ef4444' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M20 5H9l-7 7 7 7h11a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2Z'/%3E%3Cline x1='18' y1='9' x2='12' y2='15'/%3E%3Cline x1='12' y1='9' x2='18' y2='15'/%3E%3C/svg%3E") 12 12, crosshair;
}
</style>
