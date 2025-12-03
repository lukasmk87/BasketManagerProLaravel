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
            <!-- Baseline (bottom) -->
            <v-line
                :config="{
                    points: [0, courtHeight, courtWidth, courtHeight],
                    stroke: lineColor,
                    strokeWidth: lineWidth,
                }"
            />

            <!-- Sidelines -->
            <v-line
                :config="{
                    points: [0, 0, 0, courtHeight],
                    stroke: lineColor,
                    strokeWidth: lineWidth,
                }"
            />
            <v-line
                :config="{
                    points: [courtWidth, 0, courtWidth, courtHeight],
                    stroke: lineColor,
                    strokeWidth: lineWidth,
                }"
            />

            <!-- Half-court line -->
            <v-line
                :config="{
                    points: [0, 0, courtWidth, 0],
                    stroke: lineColor,
                    strokeWidth: lineWidth,
                }"
            />

            <!-- Paint / Key Area -->
            <v-rect
                :config="{
                    x: centerX - paintWidth / 2,
                    y: courtHeight - paintLength,
                    width: paintWidth,
                    height: paintLength,
                    stroke: lineColor,
                    strokeWidth: lineWidth,
                    fill: 'transparent',
                }"
            />

            <!-- Free Throw Circle -->
            <v-arc
                :config="{
                    x: centerX,
                    y: courtHeight - paintLength,
                    innerRadius: 0,
                    outerRadius: freeThrowCircleRadius,
                    angle: 180,
                    rotation: -90,
                    stroke: lineColor,
                    strokeWidth: lineWidth,
                    fill: 'transparent',
                }"
            />
            <!-- Dashed part of free throw circle (inside paint) -->
            <v-arc
                :config="{
                    x: centerX,
                    y: courtHeight - paintLength,
                    innerRadius: 0,
                    outerRadius: freeThrowCircleRadius,
                    angle: 180,
                    rotation: 90,
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
                    x: centerX,
                    y: courtHeight - basketOffset,
                    innerRadius: 0,
                    outerRadius: restrictedAreaRadius,
                    angle: 180,
                    rotation: -90,
                    stroke: lineColor,
                    strokeWidth: lineWidth,
                    fill: 'transparent',
                }"
            />

            <!-- Backboard -->
            <v-line
                :config="{
                    points: [
                        centerX - backboardWidth / 2,
                        courtHeight - backboardOffset,
                        centerX + backboardWidth / 2,
                        courtHeight - backboardOffset,
                    ],
                    stroke: lineColor,
                    strokeWidth: lineWidth + 1,
                }"
            />

            <!-- Basket / Rim -->
            <v-circle
                :config="{
                    x: centerX,
                    y: courtHeight - basketOffset,
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
        default: 700,
    },
    height: {
        type: Number,
        default: 500,
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
    halfLength: 14, // Half court length
    width: 15, // Court width
    threePointRadius: 6.75, // 3-point line radius
    threePointCorner: 0.9, // Distance from baseline for corner 3
    paintWidth: 4.9, // Paint width
    paintLength: 5.8, // Paint length (to free throw line)
    freeThrowCircleRadius: 1.8, // Free throw circle radius
    restrictedAreaRadius: 1.25, // Restricted area radius
    basketOffset: 1.575, // Basket distance from baseline
    backboardOffset: 1.2, // Backboard distance from baseline
    backboardWidth: 1.8, // Backboard width
    rimRadius: 0.225, // Rim radius (45cm diameter)
};

// Scale factors
const scaleX = computed(() => props.width / COURT.width);
const scaleY = computed(() => props.height / COURT.halfLength);

// Computed dimensions
const courtWidth = computed(() => props.width);
const courtHeight = computed(() => props.height);
const centerX = computed(() => courtWidth.value / 2);

const paintWidth = computed(() => COURT.paintWidth * scaleX.value);
const paintLength = computed(() => COURT.paintLength * scaleY.value);
const freeThrowCircleRadius = computed(() => COURT.freeThrowCircleRadius * scaleX.value);
const restrictedAreaRadius = computed(() => COURT.restrictedAreaRadius * scaleX.value);
const basketOffset = computed(() => COURT.basketOffset * scaleY.value);
const backboardOffset = computed(() => COURT.backboardOffset * scaleY.value);
const backboardWidth = computed(() => COURT.backboardWidth * scaleX.value);
const rimRadius = computed(() => COURT.rimRadius * scaleX.value);
const threePointRadius = computed(() => COURT.threePointRadius * scaleX.value);
const threePointCornerY = computed(() => COURT.threePointCorner * scaleY.value);

// Three point line drawing function
const drawThreePointLine = (context, shape) => {
    const cx = centerX.value;
    const basketY = courtHeight.value - basketOffset.value;
    const radius = threePointRadius.value;
    const cornerY = courtHeight.value - threePointCornerY.value;

    // Calculate the angle where the arc meets the corner
    const cornerX = COURT.threePointRadius * scaleX.value;
    const angle = Math.asin((COURT.width / 2 - (COURT.width / 2 - 0.9 * scaleX.value / scaleY.value)) / COURT.threePointRadius);

    context.beginPath();

    // Left corner straight line
    const leftCornerX = cx - (COURT.width / 2 - 0.9) * scaleX.value;
    context.moveTo(leftCornerX, courtHeight.value);
    context.lineTo(leftCornerX, cornerY);

    // Arc
    const startAngle = Math.PI - Math.acos((leftCornerX - cx) / radius);
    const endAngle = Math.acos((courtWidth.value - leftCornerX - cx) / radius);

    context.arc(cx, basketY, radius, startAngle, -startAngle, false);

    // Right corner straight line
    const rightCornerX = cx + (COURT.width / 2 - 0.9) * scaleX.value;
    context.lineTo(rightCornerX, courtHeight.value);

    context.fillStrokeShape(shape);
};
</script>
