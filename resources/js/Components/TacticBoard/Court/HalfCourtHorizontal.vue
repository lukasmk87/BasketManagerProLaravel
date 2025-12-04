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
            <!-- Baseline (right) -->
            <v-line
                :config="{
                    points: [offsetX + actualCourtWidth, offsetY, offsetX + actualCourtWidth, offsetY + actualCourtHeight],
                    stroke: lineColor,
                    strokeWidth: lineWidth,
                }"
            />

            <!-- Sidelines (top and bottom) -->
            <v-line
                :config="{
                    points: [offsetX, offsetY, offsetX + actualCourtWidth, offsetY],
                    stroke: lineColor,
                    strokeWidth: lineWidth,
                }"
            />
            <v-line
                :config="{
                    points: [offsetX, offsetY + actualCourtHeight, offsetX + actualCourtWidth, offsetY + actualCourtHeight],
                    stroke: lineColor,
                    strokeWidth: lineWidth,
                }"
            />

            <!-- Half-court line (left) -->
            <v-line
                :config="{
                    points: [offsetX, offsetY, offsetX, offsetY + actualCourtHeight],
                    stroke: lineColor,
                    strokeWidth: lineWidth,
                }"
            />

            <!-- Paint / Key Area -->
            <v-rect
                :config="{
                    x: offsetX + actualCourtWidth - paintLength,
                    y: centerY - paintWidth / 2,
                    width: paintLength,
                    height: paintWidth,
                    stroke: lineColor,
                    strokeWidth: lineWidth,
                    fill: 'transparent',
                }"
            />

            <!-- Free Throw Circle (solid half - outside paint) -->
            <v-arc
                :config="{
                    x: offsetX + actualCourtWidth - paintLength,
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
            <!-- Free Throw Circle (dashed half - inside paint) -->
            <v-arc
                :config="{
                    x: offsetX + actualCourtWidth - paintLength,
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

            <!-- Restricted Area Lines (horizontal lines from baseline to arc) -->
            <v-line
                :config="{
                    points: [
                        offsetX + actualCourtWidth,
                        centerY - restrictedAreaRadius,
                        offsetX + actualCourtWidth - basketOffset,
                        centerY - restrictedAreaRadius,
                    ],
                    stroke: lineColor,
                    strokeWidth: lineWidth,
                }"
            />
            <v-line
                :config="{
                    points: [
                        offsetX + actualCourtWidth,
                        centerY + restrictedAreaRadius,
                        offsetX + actualCourtWidth - basketOffset,
                        centerY + restrictedAreaRadius,
                    ],
                    stroke: lineColor,
                    strokeWidth: lineWidth,
                }"
            />

            <!-- Restricted Area Arc -->
            <v-arc
                :config="{
                    x: offsetX + actualCourtWidth - basketOffset,
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
                        offsetX + actualCourtWidth - backboardOffset,
                        centerY - backboardWidth / 2,
                        offsetX + actualCourtWidth - backboardOffset,
                        centerY + backboardWidth / 2,
                    ],
                    stroke: lineColor,
                    strokeWidth: lineWidth + 1,
                }"
            />

            <!-- Basket / Rim -->
            <v-circle
                :config="{
                    x: offsetX + actualCourtWidth - basketOffset,
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
import { FIBA_COURT, calculateUniformScale, toPixels } from './constants.js';

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

// Calculate uniform scale to maintain aspect ratio
// Half court horizontal: 14m wide x 15m tall (rotated 90 degrees from vertical)
const scaling = computed(() =>
    calculateUniformScale(props.width, props.height, FIBA_COURT.halfLength, FIBA_COURT.width)
);

const scale = computed(() => scaling.value.scale);
const offsetX = computed(() => scaling.value.offsetX);
const offsetY = computed(() => scaling.value.offsetY);
const actualCourtWidth = computed(() => scaling.value.actualWidth);
const actualCourtHeight = computed(() => scaling.value.actualHeight);

// Center Y position
const centerY = computed(() => offsetY.value + actualCourtHeight.value / 2);

// Scaled dimensions (all use uniform scale)
// Note: In horizontal orientation, paint width becomes height and paint length becomes width
const paintWidth = computed(() => toPixels(FIBA_COURT.paintWidth, scale.value));
const paintLength = computed(() => toPixels(FIBA_COURT.paintLength, scale.value));
const freeThrowCircleRadius = computed(() => toPixels(FIBA_COURT.freeThrowCircleRadius, scale.value));
const restrictedAreaRadius = computed(() => toPixels(FIBA_COURT.restrictedAreaRadius, scale.value));
const basketOffset = computed(() => toPixels(FIBA_COURT.basketFromBaseline, scale.value));
const backboardOffset = computed(() => toPixels(FIBA_COURT.backboardFromBaseline, scale.value));
const backboardWidth = computed(() => toPixels(FIBA_COURT.backboardWidth, scale.value));
const rimRadius = computed(() => toPixels(FIBA_COURT.rimRadius, scale.value));
const threePointRadius = computed(() => toPixels(FIBA_COURT.threePointRadius, scale.value));

// Three point line drawing function (rotated 90 degrees)
const drawThreePointLine = (context, shape) => {
    const cy = centerY.value;
    const basketX = offsetX.value + actualCourtWidth.value - basketOffset.value;
    const radius = threePointRadius.value;

    // Corner distance from sideline (0.90m)
    const cornerDistanceFromSideline = toPixels(FIBA_COURT.threePointCornerDistance, scale.value);
    const topCornerY = offsetY.value + cornerDistanceFromSideline;
    const bottomCornerY = offsetY.value + actualCourtHeight.value - cornerDistanceFromSideline;

    // Calculate the angle where the arc meets the corner line
    const dy = topCornerY - cy; // negative (above basket center)
    const clampedCos = Math.max(-1, Math.min(1, dy / radius));
    const arcAngle = Math.acos(clampedCos); // ≈ 168° (2.93 rad)

    // Calculate where the arc intersects with the corner horizontal line
    // arcX is to the LEFT of the basket (smaller x value)
    const arcX = basketX - radius * Math.sin(arcAngle);

    context.beginPath();

    // 1. Top corner: horizontal line from baseline to arc intersection
    context.moveTo(offsetX.value + actualCourtWidth.value, topCornerY);
    context.lineTo(arcX, topCornerY);

    // 2. Arc from top to bottom (left side of the basket)
    // The actual polar angles are:
    // Top intersection: ≈ -102° (upper-left of basket)
    // Bottom intersection: ≈ +102° (lower-left of basket)
    // We need to go counterclockwise from -102° to +102° through 180° (left side)
    const complementAngle = Math.PI - arcAngle; // ≈ 12° (0.21 rad)
    const startAngle = -Math.PI / 2 - complementAngle;  // ≈ -102°
    const endAngle = Math.PI / 2 + complementAngle;     // ≈ +102°
    context.arc(basketX, cy, radius, startAngle, endAngle, true); // counterclockwise

    // 3. Bottom corner: from arc to baseline
    context.lineTo(offsetX.value + actualCourtWidth.value, bottomCornerY);

    context.fillStrokeShape(shape);
};
</script>
