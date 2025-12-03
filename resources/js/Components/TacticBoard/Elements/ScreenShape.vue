<template>
    <v-group
        :config="{
            x: x,
            y: y,
            rotation: rotation,
            draggable: draggable,
        }"
        @dragstart="handleDragStart"
        @dragmove="handleDragMove"
        @dragend="handleDragEnd"
        @click="handleClick"
        @tap="handleClick"
    >
        <!-- Screen shape (thick horizontal line with endpoints) -->
        <v-line
            :config="{
                points: [-width / 2, 0, width / 2, 0],
                stroke: strokeColor,
                strokeWidth: thickness,
                lineCap: 'round',
                shadowColor: selected ? '#ffc107' : 'transparent',
                shadowBlur: selected ? 8 : 0,
            }"
        />

        <!-- Left endpoint marker -->
        <v-circle
            :config="{
                x: -width / 2,
                y: 0,
                radius: thickness / 2 + 2,
                fill: strokeColor,
            }"
        />

        <!-- Right endpoint marker -->
        <v-circle
            :config="{
                x: width / 2,
                y: 0,
                radius: thickness / 2 + 2,
                fill: strokeColor,
            }"
        />

        <!-- Rotation handle (when selected) -->
        <template v-if="selected && editable">
            <v-line
                :config="{
                    points: [0, 0, 0, -rotationHandleDistance],
                    stroke: '#ffc107',
                    strokeWidth: 2,
                    dash: [4, 4],
                }"
            />
            <v-circle
                :config="{
                    x: 0,
                    y: -rotationHandleDistance,
                    radius: 8,
                    fill: '#ffc107',
                    stroke: '#ffffff',
                    strokeWidth: 1,
                    draggable: true,
                }"
                @dragmove="handleRotationDrag"
                @dragend="handleRotationDragEnd"
            />
        </template>

        <!-- Selection indicator -->
        <v-rect
            v-if="selected"
            :config="{
                x: -width / 2 - 4,
                y: -thickness / 2 - 4,
                width: width + 8,
                height: thickness + 8,
                stroke: '#ffc107',
                strokeWidth: 2,
                dash: [6, 3],
                fill: 'transparent',
            }"
        />
    </v-group>
</template>

<script setup>
import { computed, ref } from 'vue';

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
    rotation: {
        type: Number,
        default: 0, // Degrees
    },
    width: {
        type: Number,
        default: 40,
    },
    thickness: {
        type: Number,
        default: 8,
    },
    color: {
        type: String,
        default: '#ffffff',
    },
    selected: {
        type: Boolean,
        default: false,
    },
    draggable: {
        type: Boolean,
        default: true,
    },
    editable: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['update:position', 'update:rotation', 'select', 'dragstart', 'dragend']);

const rotationHandleDistance = 40;

const strokeColor = computed(() => props.color);

// Store initial position for rotation calculation
const dragStartCenter = ref({ x: 0, y: 0 });

// Event handlers
const handleDragStart = (e) => {
    emit('dragstart', { id: props.id, event: e });
};

const handleDragMove = (e) => {
    const node = e.target;
    emit('update:position', {
        id: props.id,
        x: node.x(),
        y: node.y(),
    });
};

const handleDragEnd = (e) => {
    const node = e.target;
    emit('update:position', {
        id: props.id,
        x: node.x(),
        y: node.y(),
    });
    emit('dragend', { id: props.id, event: e });
};

const handleClick = () => {
    emit('select', { id: props.id });
};

const handleRotationDrag = (e) => {
    const stage = e.target.getStage();
    const group = e.target.getParent();
    const pointer = stage.getPointerPosition();

    if (!pointer || !group) return;

    // Get group absolute position
    const groupPos = group.absolutePosition();

    // Calculate angle from center to pointer
    const dx = pointer.x - groupPos.x;
    const dy = pointer.y - groupPos.y;
    let angle = Math.atan2(dy, dx) * (180 / Math.PI);

    // Adjust because handle is at top (90 degrees offset)
    angle = angle + 90;

    // Keep rotation handle at fixed distance
    e.target.x(0);
    e.target.y(-rotationHandleDistance);

    emit('update:rotation', {
        id: props.id,
        rotation: angle,
    });
};

const handleRotationDragEnd = (e) => {
    // Ensure handle snaps back to correct position
    e.target.x(0);
    e.target.y(-rotationHandleDistance);
};
</script>
