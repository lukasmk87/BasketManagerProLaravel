<template>
    <v-group v-if="visible" :config="{ listening: false }">
        <!-- Vertical lines -->
        <v-line
            v-for="(line, index) in verticalLines"
            :key="'v-' + index"
            :config="{
                points: line.points,
                stroke: lineColor,
                strokeWidth: line.isMajor ? 0.8 : 0.4,
                opacity: line.isMajor ? opacity : opacity * 0.6,
                listening: false,
            }"
        />

        <!-- Horizontal lines -->
        <v-line
            v-for="(line, index) in horizontalLines"
            :key="'h-' + index"
            :config="{
                points: line.points,
                stroke: lineColor,
                strokeWidth: line.isMajor ? 0.8 : 0.4,
                opacity: line.isMajor ? opacity : opacity * 0.6,
                listening: false,
            }"
        />
    </v-group>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    visible: {
        type: Boolean,
        default: true,
    },
    width: {
        type: Number,
        required: true,
    },
    height: {
        type: Number,
        required: true,
    },
    gridSize: {
        type: Number,
        default: 20,
    },
    majorGridInterval: {
        type: Number,
        default: 5, // Every 5th line is a major line (darker)
    },
    lineColor: {
        type: String,
        default: '#ffffff',
    },
    opacity: {
        type: Number,
        default: 0.15,
    },
});

// Compute vertical grid lines
const verticalLines = computed(() => {
    const lines = [];
    const numLines = Math.floor(props.width / props.gridSize);

    for (let i = 0; i <= numLines; i++) {
        const x = i * props.gridSize;
        lines.push({
            points: [x, 0, x, props.height],
            isMajor: i % props.majorGridInterval === 0,
        });
    }

    return lines;
});

// Compute horizontal grid lines
const horizontalLines = computed(() => {
    const lines = [];
    const numLines = Math.floor(props.height / props.gridSize);

    for (let i = 0; i <= numLines; i++) {
        const y = i * props.gridSize;
        lines.push({
            points: [0, y, props.width, y],
            isMajor: i % props.majorGridInterval === 0,
        });
    }

    return lines;
});
</script>
