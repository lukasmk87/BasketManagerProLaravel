<template>
    <v-group
        :config="{
            x: x,
            y: y,
            rotation: rotation,
            draggable: true,
            offsetX: width / 2,
            offsetY: height / 2,
        }"
        @dragend="handleDragEnd"
        @click="handleClick"
        @tap="handleClick"
    >
        <!-- Main rectangle -->
        <v-rect
            :config="{
                width: width,
                height: height,
                fill: fill,
                stroke: stroke,
                strokeWidth: strokeWidth,
                cornerRadius: 4,
            }"
        />

        <!-- Selection border when selected -->
        <v-rect
            v-if="selected"
            :config="{
                x: -4,
                y: -4,
                width: width + 8,
                height: height + 8,
                stroke: '#fbbf24',
                strokeWidth: 2,
                dash: [6, 4],
                listening: false,
            }"
        />

        <!-- Resize handles when selected -->
        <template v-if="selected">
            <!-- Bottom-right corner resize -->
            <v-circle
                :config="{
                    x: width,
                    y: height,
                    radius: 6,
                    fill: '#fbbf24',
                    stroke: '#ffffff',
                    strokeWidth: 2,
                    draggable: true,
                }"
                @dragmove="handleResize"
                @dragend="handleResizeEnd"
            />

            <!-- Rotation handle -->
            <v-line
                :config="{
                    points: [width / 2, -10, width / 2, -30],
                    stroke: '#fbbf24',
                    strokeWidth: 2,
                }"
            />
            <v-circle
                :config="{
                    x: width / 2,
                    y: -30,
                    radius: 8,
                    fill: '#fbbf24',
                    stroke: '#ffffff',
                    strokeWidth: 2,
                    draggable: true,
                }"
                @dragmove="handleRotate"
                @dragend="handleRotateEnd"
            />
        </template>
    </v-group>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
    id: {
        type: String,
        required: true,
    },
    x: {
        type: Number,
        default: 0,
    },
    y: {
        type: Number,
        default: 0,
    },
    width: {
        type: Number,
        default: 60,
    },
    height: {
        type: Number,
        default: 40,
    },
    rotation: {
        type: Number,
        default: 0,
    },
    fill: {
        type: String,
        default: 'rgba(255, 255, 255, 0.2)',
    },
    stroke: {
        type: String,
        default: '#ffffff',
    },
    strokeWidth: {
        type: Number,
        default: 2,
    },
    selected: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['select', 'update:position', 'update:size', 'update:rotation']);

const handleClick = (e) => {
    e.cancelBubble = true;
    emit('select', { id: props.id });
};

const handleDragEnd = (e) => {
    emit('update:position', {
        id: props.id,
        x: e.target.x(),
        y: e.target.y(),
    });
};

const handleResize = (e) => {
    const newWidth = Math.max(20, e.target.x());
    const newHeight = Math.max(20, e.target.y());
    emit('update:size', {
        id: props.id,
        width: newWidth,
        height: newHeight,
    });
};

const handleResizeEnd = () => {
    // Emit final size if needed
};

const handleRotate = (e) => {
    const stage = e.target.getStage();
    const centerX = props.width / 2;
    const centerY = props.height / 2;

    // Get pointer position relative to center
    const pointerPos = stage.getPointerPosition();
    const groupPos = e.target.getParent().getAbsolutePosition();

    const dx = pointerPos.x - groupPos.x;
    const dy = pointerPos.y - groupPos.y;

    // Calculate angle
    let angle = Math.atan2(dy, dx) * (180 / Math.PI) + 90;

    emit('update:rotation', {
        id: props.id,
        rotation: angle,
    });
};

const handleRotateEnd = () => {
    // Emit final rotation if needed
};
</script>
