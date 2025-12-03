<template>
    <div class="tactic-board-viewer">
        <!-- Canvas Container -->
        <div ref="containerRef" class="canvas-container">
            <v-stage
                ref="stageRef"
                :config="stageConfig"
            >
                <!-- Court Layer -->
                <v-layer>
                    <component
                        :is="courtComponent"
                        :width="canvasWidth"
                        :height="canvasHeight"
                        :courtColor="playData?.court?.backgroundColor || '#1a5f2a'"
                    />
                </v-layer>

                <!-- Elements Layer -->
                <v-layer>
                    <!-- Paths (movement, pass, dribble lines) -->
                    <component
                        v-for="path in elements.paths"
                        :key="path.id"
                        :is="getPathComponent(path.type)"
                        :id="path.id"
                        :points="path.points"
                        :color="path.color"
                        :selected="false"
                        :editable="false"
                    />

                    <!-- Shapes (screens) -->
                    <ScreenShape
                        v-for="shape in elements.shapes"
                        :key="shape.id"
                        :id="shape.id"
                        :x="shape.x"
                        :y="shape.y"
                        :rotation="shape.rotation || 0"
                        :width="shape.width || 40"
                        :color="shape.color || '#ffffff'"
                        :selected="false"
                        :draggable="false"
                    />

                    <!-- Annotations (text) -->
                    <TextAnnotation
                        v-for="annotation in elements.annotations"
                        :key="annotation.id"
                        :id="annotation.id"
                        :x="annotation.x"
                        :y="annotation.y"
                        :content="annotation.content"
                        :fontSize="annotation.fontSize || 16"
                        :color="annotation.color || '#ffffff'"
                        :selected="false"
                        :draggable="false"
                    />

                    <!-- Players -->
                    <PlayerToken
                        v-for="player in elements.players"
                        :key="player.id"
                        :id="player.id"
                        :x="player.x"
                        :y="player.y"
                        :number="player.number"
                        :label="player.label"
                        :team="player.team"
                        :hasBall="player.hasBall"
                        :selected="false"
                        :draggable="false"
                    />
                </v-layer>
            </v-stage>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, markRaw } from 'vue';
import { useTacticExport } from '@/composables/useTacticExport';

// Components
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
    playData: {
        type: Object,
        default: null,
    },
    courtType: {
        type: String,
        default: 'half_horizontal',
    },
});

// Refs
const containerRef = ref(null);
const stageRef = ref(null);

// Canvas dimensions
const canvasWidth = ref(700);
const canvasHeight = ref(500);

// Export utility
const exportUtil = useTacticExport();

// Stage configuration
const stageConfig = computed(() => ({
    width: canvasWidth.value,
    height: canvasHeight.value,
}));

// Court component based on type
const courtComponent = computed(() => {
    const type = props.playData?.court?.type || props.courtType;
    switch (type) {
        case 'full':
            return markRaw(FullCourt);
        case 'half_vertical':
            return markRaw(HalfCourtVertical);
        case 'half_horizontal':
        default:
            return markRaw(HalfCourtHorizontal);
    }
});

// Elements from play data
const elements = computed(() => {
    if (!props.playData?.elements) {
        return { players: [], paths: [], shapes: [], annotations: [] };
    }
    return {
        players: props.playData.elements.players || [],
        paths: props.playData.elements.paths || [],
        shapes: props.playData.elements.shapes || [],
        annotations: props.playData.elements.annotations || [],
    };
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

// Initialize canvas dimensions
onMounted(() => {
    updateCanvasDimensions();
    window.addEventListener('resize', updateCanvasDimensions);
});

// Update canvas dimensions based on container and court type
const updateCanvasDimensions = () => {
    if (!containerRef.value) return;

    const container = containerRef.value;
    const maxWidth = container.clientWidth - 20;
    const maxHeight = Math.min(container.clientHeight - 20, 600);

    const type = props.playData?.court?.type || props.courtType;

    // Aspect ratios for different court types
    let aspectRatio;
    switch (type) {
        case 'full':
            aspectRatio = 15 / 28;
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
};

// Watch for court type changes
watch(() => props.courtType, () => {
    updateCanvasDimensions();
});

watch(() => props.playData, () => {
    updateCanvasDimensions();
}, { deep: true });

// Export as PNG
const exportPng = async () => {
    if (stageRef.value) {
        await exportUtil.downloadPng(stageRef.value.getStage(), 'spielzug');
    }
};

// Expose methods for parent
defineExpose({
    exportPng,
    getStage: () => stageRef.value?.getStage(),
});
</script>

<style scoped>
.tactic-board-viewer {
    padding: 16px;
    background: #111827;
}

.canvas-container {
    display: flex;
    align-items: center;
    justify-content: center;
    background: #0f172a;
    border-radius: 8px;
    padding: 10px;
    min-height: 300px;
}
</style>
