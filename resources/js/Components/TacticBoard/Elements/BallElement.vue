<template>
    <v-group
        :config="{
            x: x,
            y: y,
            draggable: true,
        }"
        @click="handleClick"
        @tap="handleClick"
        @dragstart="handleDragStart"
        @dragmove="handleDragMove"
        @dragend="handleDragEnd"
    >
        <!-- Ball outer circle (orange) -->
        <v-circle
            :config="{
                radius: radius,
                fill: ballColor,
                stroke: selected ? '#ffc107' : '#8B4513',
                strokeWidth: selected ? 3 : 2,
                shadowColor: selected ? '#ffc107' : 'rgba(0,0,0,0.3)',
                shadowBlur: selected ? 10 : 5,
                shadowOffset: { x: 2, y: 2 },
                shadowOpacity: 0.5,
            }"
        />

        <!-- Ball seams (horizontal) -->
        <v-line
            :config="{
                points: [-radius * 0.7, 0, radius * 0.7, 0],
                stroke: '#8B4513',
                strokeWidth: 1.5,
                lineCap: 'round',
            }"
        />

        <!-- Ball seams (vertical) -->
        <v-line
            :config="{
                points: [0, -radius * 0.7, 0, radius * 0.7],
                stroke: '#8B4513',
                strokeWidth: 1.5,
                lineCap: 'round',
            }"
        />

        <!-- Ball seams (curved left) -->
        <v-arc
            :config="{
                x: -radius * 0.3,
                innerRadius: radius * 0.5,
                outerRadius: radius * 0.5,
                angle: 120,
                rotation: -60,
                stroke: '#8B4513',
                strokeWidth: 1.5,
            }"
        />

        <!-- Ball seams (curved right) -->
        <v-arc
            :config="{
                x: radius * 0.3,
                innerRadius: radius * 0.5,
                outerRadius: radius * 0.5,
                angle: 120,
                rotation: 120,
                stroke: '#8B4513',
                strokeWidth: 1.5,
            }"
        />

        <!-- Highlight for 3D effect -->
        <v-circle
            :config="{
                x: -radius * 0.3,
                y: -radius * 0.3,
                radius: radius * 0.25,
                fill: 'rgba(255, 255, 255, 0.3)',
            }"
        />
    </v-group>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    id: {
        type: String,
        required: true,
    },
    x: {
        type: Number,
        required: true,
    },
    y: {
        type: Number,
        required: true,
    },
    radius: {
        type: Number,
        default: 12,
    },
    selected: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['select', 'update:position']);

// Basketball orange color
const ballColor = computed(() => '#FF6B35');

// Event handlers
const handleClick = (e) => {
    e.cancelBubble = true;
    emit('select', { id: props.id });
};

const handleDragStart = (e) => {
    e.cancelBubble = true;
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
};
</script>
