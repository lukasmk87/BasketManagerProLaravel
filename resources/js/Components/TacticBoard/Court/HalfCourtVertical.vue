<template>
    <v-group>
        <!-- Container Background (for letterboxing) -->
        <v-rect
            :config="{
                x: 0,
                y: 0,
                width: width,
                height: height,
                fill: '#1a472a',
            }"
        />

        <!-- Court Background -->
        <v-rect
            :config="{
                x: offsetX,
                y: offsetY,
                width: actualCourtWidth,
                height: actualCourtHeight,
                fill: courtColor,
            }"
        />

        <!-- Court Lines Group -->
        <v-group :config="{ listening: false }">
            <!-- Baseline (bottom) -->
            <v-line
                :config="{
                    points: [offsetX, offsetY + actualCourtHeight, offsetX + actualCourtWidth, offsetY + actualCourtHeight],
                    stroke: lineColor,
                    strokeWidth: lineWidth,
                }"
            />

            <!-- Sidelines -->
            <v-line
                :config="{
                    points: [offsetX, offsetY, offsetX, offsetY + actualCourtHeight],
                    stroke: lineColor,
                    strokeWidth: lineWidth,
                }"
            />
            <v-line
                :config="{
                    points: [offsetX + actualCourtWidth, offsetY, offsetX + actualCourtWidth, offsetY + actualCourtHeight],
                    stroke: lineColor,
                    strokeWidth: lineWidth,
                }"
            />

            <!-- Half-court line (top) -->
            <v-line
                :config="{
                    points: [offsetX, offsetY, offsetX + actualCourtWidth, offsetY],
                    stroke: lineColor,
                    strokeWidth: lineWidth,
                }"
            />

            <!-- Paint / Key Area -->
            <v-rect
                :config="{
                    x: centerX - paintWidth / 2,
                    y: offsetY + actualCourtHeight - paintLength,
                    width: paintWidth,
                    height: paintLength,
                    stroke: lineColor,
                    strokeWidth: lineWidth,
                    fill: 'transparent',
                }"
            />

            <!-- Free Throw Circle (solid half - outside paint) -->
            <v-arc
                :config="{
                    x: centerX,
                    y: offsetY + actualCourtHeight - paintLength,
                    innerRadius: 0,
                    outerRadius: freeThrowCircleRadius,
                    angle: 180,
                    rotation: -90,
                    stroke: lineColor,
                    strokeWidth: lineWidth,
                    fill: 'transparent',
                }"
            />
            <!-- Free Throw Circle (dashed half - inside paint) -->
            <v-arc
                :config="{
                    x: centerX,
                    y: offsetY + actualCourtHeight - paintLength,
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

            <!-- Restricted Area Lines (vertical lines from baseline to arc) -->
            <v-line
                :config="{
                    points: [
                        centerX - restrictedAreaRadius,
                        offsetY + actualCourtHeight,
                        centerX - restrictedAreaRadius,
                        offsetY + actualCourtHeight - basketOffset,
                    ],
                    stroke: lineColor,
                    strokeWidth: lineWidth,
                }"
            />
            <v-line
                :config="{
                    points: [
                        centerX + restrictedAreaRadius,
                        offsetY + actualCourtHeight,
                        centerX + restrictedAreaRadius,
                        offsetY + actualCourtHeight - basketOffset,
                    ],
                    stroke: lineColor,
                    strokeWidth: lineWidth,
                }"
            />

            <!-- Restricted Area Arc -->
            <v-arc
                :config="{
                    x: centerX,
                    y: offsetY + actualCourtHeight - basketOffset,
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
                        offsetY + actualCourtHeight - backboardOffset,
                        centerX + backboardWidth / 2,
                        offsetY + actualCourtHeight - backboardOffset,
                    ],
                    stroke: lineColor,
                    strokeWidth: lineWidth + 1,
                }"
            />

            <!-- Basket / Rim -->
            <v-circle
                :config="{
                    x: centerX,
                    y: offsetY + actualCourtHeight - basketOffset,
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
import { FIBA_COURT, calculateUniformScale, toPixels } from './constants.js';

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

// Calculate uniform scale to maintain aspect ratio
// Half court vertical: 15m wide x 14m long
const scaling = computed(() =>
    calculateUniformScale(props.width, props.height, FIBA_COURT.width, FIBA_COURT.halfLength)
);

const scale = computed(() => scaling.value.scale);
const offsetX = computed(() => scaling.value.offsetX);
const offsetY = computed(() => scaling.value.offsetY);
const actualCourtWidth = computed(() => scaling.value.actualWidth);
const actualCourtHeight = computed(() => scaling.value.actualHeight);

// Center X position
const centerX = computed(() => offsetX.value + actualCourtWidth.value / 2);

// Scaled dimensions (all use uniform scale)
const paintWidth = computed(() => toPixels(FIBA_COURT.paintWidth, scale.value));
const paintLength = computed(() => toPixels(FIBA_COURT.paintLength, scale.value));
const freeThrowCircleRadius = computed(() => toPixels(FIBA_COURT.freeThrowCircleRadius, scale.value));
const restrictedAreaRadius = computed(() => toPixels(FIBA_COURT.restrictedAreaRadius, scale.value));
const basketOffset = computed(() => toPixels(FIBA_COURT.basketFromBaseline, scale.value));
const backboardOffset = computed(() => toPixels(FIBA_COURT.backboardFromBaseline, scale.value));
const backboardWidth = computed(() => toPixels(FIBA_COURT.backboardWidth, scale.value));
const rimRadius = computed(() => toPixels(FIBA_COURT.rimRadius, scale.value));
const threePointRadius = computed(() => toPixels(FIBA_COURT.threePointRadius, scale.value));

// Three point line drawing function
const drawThreePointLine = (context, shape) => {
    const cx = centerX.value;
    const basketY = offsetY.value + actualCourtHeight.value - basketOffset.value;
    const radius = threePointRadius.value;

    // Corner distance from sideline (0.90m)
    const cornerDistanceFromSideline = toPixels(FIBA_COURT.threePointCornerDistance, scale.value);
    const leftCornerX = offsetX.value + cornerDistanceFromSideline;
    const rightCornerX = offsetX.value + actualCourtWidth.value - cornerDistanceFromSideline;

    // Calculate the angle where the arc meets the corner line
    const dx = leftCornerX - cx; // negative (left of basket)
    const clampedCos = Math.max(-1, Math.min(1, dx / radius));
    const arcAngle = Math.acos(clampedCos); // ≈ 168° (2.93 rad)

    // Y-Position of intersection (above the basket)
    const arcY = basketY - radius * Math.sin(arcAngle);

    context.beginPath();

    // 1. Left corner: vertical line from baseline to arc intersection
    context.moveTo(leftCornerX, offsetY.value + actualCourtHeight.value);
    context.lineTo(leftCornerX, arcY);

    // 2. Arc from left to right
    // Use negative angles for the upper half-circle (above basket)
    const startAngle = -arcAngle;           // ≈ -168° (left intersection)
    const endAngle = arcAngle - Math.PI;    // ≈ -12° (right intersection)
    context.arc(cx, basketY, radius, startAngle, endAngle, false); // clockwise

    // 3. Right corner: from arc to baseline
    context.lineTo(rightCornerX, offsetY.value + actualCourtHeight.value);

    context.fillStrokeShape(shape);
};
</script>
