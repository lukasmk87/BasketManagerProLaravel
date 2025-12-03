<template>
    <v-group>
        <!-- Court Background -->
        <v-rect
            :config="{
                x: 0,
                y: 0,
                width: courtWidth,
                height: courtHeight,
                fill: courtColor,
            }"
        />

        <!-- Court Lines Group -->
        <v-group :config="{ listening: false }">
            <!-- Baseline (right) -->
            <v-line
                :config="{
                    points: [courtWidth, 0, courtWidth, courtHeight],
                    stroke: lineColor,
                    strokeWidth: lineWidth,
                }"
            />

            <!-- Sidelines (top and bottom) -->
            <v-line
                :config="{
                    points: [0, 0, courtWidth, 0],
                    stroke: lineColor,
                    strokeWidth: lineWidth,
                }"
            />
            <v-line
                :config="{
                    points: [0, courtHeight, courtWidth, courtHeight],
                    stroke: lineColor,
                    strokeWidth: lineWidth,
                }"
            />

            <!-- Half-court line (left) -->
            <v-line
                :config="{
                    points: [0, 0, 0, courtHeight],
                    stroke: lineColor,
                    strokeWidth: lineWidth,
                }"
            />

            <!-- Paint / Key Area -->
            <v-rect
                :config="{
                    x: courtWidth - paintLength,
                    y: centerY - paintWidth / 2,
                    width: paintLength,
                    height: paintWidth,
                    stroke: lineColor,
                    strokeWidth: lineWidth,
                    fill: 'transparent',
                }"
            />

            <!-- Free Throw Circle -->
            <v-arc
                :config="{
                    x: courtWidth - paintLength,
                    y: centerY,
                    innerRadius: 0,
                    outerRadius: freeThrowCircleRadius,
                    angle: 180,
                    rotation: 180,
                    stroke: lineColor,
                    strokeWidth: lineWidth,
                    fill: 'transparent',
                }"
            />
            <!-- Dashed part of free throw circle (inside paint) -->
            <v-arc
                :config="{
                    x: courtWidth - paintLength,
                    y: centerY,
                    innerRadius: 0,
                    outerRadius: freeThrowCircleRadius,
                    angle: 180,
                    rotation: 0,
                    stroke: lineColor,
                    strokeWidth: lineWidth,
                    dash: [10, 5],
                    fill: 'transparent',
                }"
            />

            <!-- Three Point Line -->
            <v-shape
                :config="{
                    sceneFunc: drawThreePointLine,
                    stroke: lineColor,
                    strokeWidth: lineWidth,
                }"
            />

            <!-- Restricted Area (Semicircle) -->
            <v-arc
                :config="{
                    x: courtWidth - basketOffset,
                    y: centerY,
                    innerRadius: 0,
                    outerRadius: restrictedAreaRadius,
                    angle: 180,
                    rotation: 180,
                    stroke: lineColor,
                    strokeWidth: lineWidth,
                    fill: 'transparent',
                }"
            />

            <!-- Backboard -->
            <v-line
                :config="{
                    points: [
                        courtWidth - backboardOffset,
                        centerY - backboardWidth / 2,
                        courtWidth - backboardOffset,
                        centerY + backboardWidth / 2,
                    ],
                    stroke: lineColor,
                    strokeWidth: lineWidth + 1,
                }"
            />

            <!-- Basket / Rim -->
            <v-circle
                :config="{
                    x: courtWidth - basketOffset,
                    y: centerY,
                    radius: rimRadius,
                    stroke: rimColor,
                    strokeWidth: lineWidth + 1,
                    fill: 'transparent',
                }"
            />
        </v-group>
    </v-group>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    width: {
        type: Number,
        default: 500,
    },
    height: {
        type: Number,
        default: 700,
    },
    courtColor: {
        type: String,
        default: '#1a5f2a',
    },
    lineColor: {
        type: String,
        default: '#ffffff',
    },
    rimColor: {
        type: String,
        default: '#ff6b35',
    },
    lineWidth: {
        type: Number,
        default: 2,
    },
});

// FIBA Court Dimensions (in meters)
const COURT = {
    halfLength: 14, // Half court length (horizontal in vertical view)
    width: 15, // Court width (vertical in vertical view)
    threePointRadius: 6.75,
    threePointCorner: 0.9,
    paintWidth: 4.9,
    paintLength: 5.8,
    freeThrowCircleRadius: 1.8,
    restrictedAreaRadius: 1.25,
    basketOffset: 1.575,
    backboardOffset: 1.2,
    backboardWidth: 1.8,
    rimRadius: 0.225,
};

// Scale factors (note: rotated 90 degrees)
const scaleX = computed(() => props.width / COURT.halfLength);
const scaleY = computed(() => props.height / COURT.width);

// Computed dimensions
const courtWidth = computed(() => props.width);
const courtHeight = computed(() => props.height);
const centerY = computed(() => courtHeight.value / 2);

const paintWidth = computed(() => COURT.paintWidth * scaleY.value);
const paintLength = computed(() => COURT.paintLength * scaleX.value);
const freeThrowCircleRadius = computed(() => COURT.freeThrowCircleRadius * scaleY.value);
const restrictedAreaRadius = computed(() => COURT.restrictedAreaRadius * scaleY.value);
const basketOffset = computed(() => COURT.basketOffset * scaleX.value);
const backboardOffset = computed(() => COURT.backboardOffset * scaleX.value);
const backboardWidth = computed(() => COURT.backboardWidth * scaleY.value);
const rimRadius = computed(() => COURT.rimRadius * scaleY.value);
const threePointRadius = computed(() => COURT.threePointRadius * scaleY.value);
const threePointCornerX = computed(() => COURT.threePointCorner * scaleX.value);

// Three point line drawing function
const drawThreePointLine = (context, shape) => {
    const cy = centerY.value;
    const basketX = courtWidth.value - basketOffset.value;
    const radius = threePointRadius.value;
    const cornerX = courtWidth.value - threePointCornerX.value;

    context.beginPath();

    // Top corner straight line
    const topCornerY = cy - (COURT.width / 2 - 0.9) * scaleY.value;
    context.moveTo(courtWidth.value, topCornerY);
    context.lineTo(cornerX, topCornerY);

    // Arc (rotated 90 degrees)
    const startAngle = -Math.PI / 2 - Math.acos((topCornerY - cy) / radius);
    context.arc(basketX, cy, radius, startAngle, -startAngle, true);

    // Bottom corner straight line
    const bottomCornerY = cy + (COURT.width / 2 - 0.9) * scaleY.value;
    context.lineTo(courtWidth.value, bottomCornerY);

    context.fillStrokeShape(shape);
};
</script>
