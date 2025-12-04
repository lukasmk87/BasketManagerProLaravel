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
            <!-- Outer boundary -->
            <v-rect
                :config="{
                    x: offsetX,
                    y: offsetY,
                    width: actualCourtWidth,
                    height: actualCourtHeight,
                    stroke: lineColor,
                    strokeWidth: lineWidth,
                    fill: 'transparent',
                }"
            />

            <!-- Center line -->
            <v-line
                :config="{
                    points: [offsetX, centerY, offsetX + actualCourtWidth, centerY],
                    stroke: lineColor,
                    strokeWidth: lineWidth,
                }"
            />

            <!-- Center circle -->
            <v-circle
                :config="{
                    x: centerX,
                    y: centerY,
                    radius: centerCircleRadius,
                    stroke: lineColor,
                    strokeWidth: lineWidth,
                    fill: 'transparent',
                }"
            />

            <!-- Bottom Half Court -->
            <v-group>
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
                        rotation: 180,
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
                        sceneFunc: (ctx, shape) => drawThreePointLine(ctx, shape, 'bottom'),
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
                            centerX - backboardWidth / 2,
                            offsetY + actualCourtHeight - backboardOffset,
                            centerX + backboardWidth / 2,
                            offsetY + actualCourtHeight - backboardOffset,
                        ],
                        stroke: lineColor,
                        strokeWidth: lineWidth + 1,
                    }"
                />

                <!-- Basket -->
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

            <!-- Top Half Court (mirrored) -->
            <v-group>
                <!-- Paint / Key Area -->
                <v-rect
                    :config="{
                        x: centerX - paintWidth / 2,
                        y: offsetY,
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
                        y: offsetY + paintLength,
                        innerRadius: 0,
                        outerRadius: freeThrowCircleRadius,
                        angle: 180,
                        rotation: 0,
                        stroke: lineColor,
                        strokeWidth: lineWidth,
                        fill: 'transparent',
                    }"
                />
                <!-- Free Throw Circle (dashed half - inside paint) -->
                <v-arc
                    :config="{
                        x: centerX,
                        y: offsetY + paintLength,
                        innerRadius: 0,
                        outerRadius: freeThrowCircleRadius,
                        angle: 180,
                        rotation: 180,
                        stroke: lineColor,
                        strokeWidth: lineWidth,
                        dash: [10, 5],
                        fill: 'transparent',
                    }"
                />

                <!-- Three Point Line -->
                <v-shape
                    :config="{
                        sceneFunc: (ctx, shape) => drawThreePointLine(ctx, shape, 'top'),
                        stroke: lineColor,
                        strokeWidth: lineWidth,
                    }"
                />

                <!-- Restricted Area Arc -->
                <v-arc
                    :config="{
                        x: centerX,
                        y: offsetY + basketOffset,
                        innerRadius: 0,
                        outerRadius: restrictedAreaRadius,
                        angle: 180,
                        rotation: 0,
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
                            offsetY + backboardOffset,
                            centerX + backboardWidth / 2,
                            offsetY + backboardOffset,
                        ],
                        stroke: lineColor,
                        strokeWidth: lineWidth + 1,
                    }"
                />

                <!-- Basket -->
                <v-circle
                    :config="{
                        x: centerX,
                        y: offsetY + basketOffset,
                        radius: rimRadius,
                        stroke: rimColor,
                        strokeWidth: lineWidth + 1,
                        fill: 'transparent',
                    }"
                />
            </v-group>
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
        default: 800,
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
// Full court is 15m wide x 28m long (width x height in vertical orientation)
const scaling = computed(() =>
    calculateUniformScale(props.width, props.height, FIBA_COURT.width, FIBA_COURT.fullLength)
);

const scale = computed(() => scaling.value.scale);
const offsetX = computed(() => scaling.value.offsetX);
const offsetY = computed(() => scaling.value.offsetY);
const actualCourtWidth = computed(() => scaling.value.actualWidth);
const actualCourtHeight = computed(() => scaling.value.actualHeight);

// Center positions
const centerX = computed(() => offsetX.value + actualCourtWidth.value / 2);
const centerY = computed(() => offsetY.value + actualCourtHeight.value / 2);

// Scaled dimensions (all use uniform scale)
const paintWidth = computed(() => toPixels(FIBA_COURT.paintWidth, scale.value));
const paintLength = computed(() => toPixels(FIBA_COURT.paintLength, scale.value));
const freeThrowCircleRadius = computed(() => toPixels(FIBA_COURT.freeThrowCircleRadius, scale.value));
const centerCircleRadius = computed(() => toPixels(FIBA_COURT.centerCircleRadius, scale.value));
const restrictedAreaRadius = computed(() => toPixels(FIBA_COURT.restrictedAreaRadius, scale.value));
const basketOffset = computed(() => toPixels(FIBA_COURT.basketFromBaseline, scale.value));
const backboardOffset = computed(() => toPixels(FIBA_COURT.backboardFromBaseline, scale.value));
const backboardWidth = computed(() => toPixels(FIBA_COURT.backboardWidth, scale.value));
const rimRadius = computed(() => toPixels(FIBA_COURT.rimRadius, scale.value));
const threePointRadius = computed(() => toPixels(FIBA_COURT.threePointRadius, scale.value));

// Three point line drawing function
const drawThreePointLine = (context, shape, side) => {
    const cx = centerX.value;
    const radius = threePointRadius.value;

    // Corner distance from sideline (0.90m)
    const cornerDistanceFromSideline = toPixels(FIBA_COURT.threePointCornerDistance, scale.value);
    const leftCornerX = offsetX.value + cornerDistanceFromSideline;
    const rightCornerX = offsetX.value + actualCourtWidth.value - cornerDistanceFromSideline;

    context.beginPath();

    if (side === 'bottom') {
        const basketY = offsetY.value + actualCourtHeight.value - basketOffset.value;

        // Calculate the angle where the arc meets the corner line
        const dx = leftCornerX - cx; // negative (left of basket)
        const clampedCos = Math.max(-1, Math.min(1, dx / radius));
        const arcAngle = Math.acos(clampedCos); // ≈ 168° (2.93 rad)

        // Y-Position of intersection (above the basket)
        const arcY = basketY - radius * Math.sin(arcAngle);

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
    } else {
        // Top half (basket at top, arc below)
        const basketY = offsetY.value + basketOffset.value;

        const dx = leftCornerX - cx;
        const clampedCos = Math.max(-1, Math.min(1, dx / radius));
        const arcAngle = Math.acos(clampedCos);

        // Y-Position of intersection (below the basket)
        const arcY = basketY + radius * Math.sin(arcAngle);

        // 1. Left corner: vertical line from baseline to arc intersection
        context.moveTo(leftCornerX, offsetY.value);
        context.lineTo(leftCornerX, arcY);

        // 2. Arc from left to right
        // Use positive angles for the lower half-circle (below basket)
        const startAngle = arcAngle;            // ≈ 168° (left intersection)
        const endAngle = Math.PI - arcAngle;    // ≈ 12° (right intersection)
        context.arc(cx, basketY, radius, startAngle, endAngle, true); // counterclockwise

        // 3. Right corner: from arc to baseline
        context.lineTo(rightCornerX, offsetY.value);
    }

    context.fillStrokeShape(shape);
};
</script>
