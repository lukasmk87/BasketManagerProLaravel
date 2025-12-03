<template>
    <v-group
        :config="{
            x: x,
            y: y,
            draggable: true,
        }"
        @dragend="handleDragEnd"
        @click="handleClick"
        @tap="handleClick"
    >
        <!-- Main ellipse -->
        <v-ellipse
            :config="{
                radiusX: radiusX,
                radiusY: radiusY,
                fill: fill,
                stroke: stroke,
                strokeWidth: strokeWidth,
                opacity: opacity,
            }"
        />

        <!-- Selection border when selected -->
        <v-ellipse
            v-if="selected"
            :config="{
                radiusX: radiusX + 4,
                radiusY: radiusY + 4,
                stroke: '#fbbf24',
                strokeWidth: 2,
                dash: [6, 4],
                listening: false,
            }"
        />

        <!-- Resize handles when selected -->
        <template v-if="selected">
            <!-- Right handle -->
            <v-circle
                :config="{
                    x: radiusX,
                    y: 0,
                    radius: 6,
                    fill: '#fbbf24',
                    stroke: '#ffffff',
                    strokeWidth: 2,
                    draggable: true,
                }"
                @dragmove="handleResizeX"
                @dragend="handleResizeEnd"
            />
            <!-- Bottom handle -->
            <v-circle
                :config="{
                    x: 0,
                    y: radiusY,
                    radius: 6,
                    fill: '#fbbf24',
                    stroke: '#ffffff',
                    strokeWidth: 2,
                    draggable: true,
                }"
                @dragmove="handleResizeY"
                @dragend="handleResizeEnd"
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
    radiusX: {
        type: Number,
        default: 30,
    },
    radiusY: {
        type: Number,
        default: 30,
    },
    fill: {
        type: String,
        default: 'transparent',
    },
    stroke: {
        type: String,
        default: '#ffffff',
    },
    strokeWidth: {
        type: Number,
        default: 2,
    },
    opacity: {
        type: Number,
        default: 0.7,
    },
    selected: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['select', 'update:position', 'update:size']);

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

const handleResizeX = (e) => {
    const newRadiusX = Math.max(10, Math.abs(e.target.x()));
    emit('update:size', {
        id: props.id,
        radiusX: newRadiusX,
        radiusY: props.radiusY,
    });
};

const handleResizeY = (e) => {
    const newRadiusY = Math.max(10, Math.abs(e.target.y()));
    emit('update:size', {
        id: props.id,
        radiusX: props.radiusX,
        radiusY: newRadiusY,
    });
};

const handleResizeEnd = () => {
    // Emit final size update if needed
};
</script>
