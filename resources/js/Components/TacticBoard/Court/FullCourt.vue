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
            <!-- Outer boundary -->
            <v-rect
                :config="{
                    x: 0,
                    y: 0,
                    width: courtWidth,
                    height: courtHeight,
                    stroke: lineColor,
                    strokeWidth: lineWidth,
                    fill: 'transparent',
                }"
            />

            <!-- Center line -->
            <v-line
                :config="{
                    points: [0, centerY, courtWidth, centerY],
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
                        sceneFunc: (ctx, shape) => drawThreePointLine(ctx, shape, 'bottom'),
                        stroke: lineColor,
                        strokeWidth: lineWidth,
                    }"
                />

                <!-- Restricted Area -->
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

                <!-- Basket -->
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

            <!-- Top Half Court (mirrored) -->
            <v-group>
                <!-- Paint / Key Area -->
                <v-rect
                    :config="{
                        x: centerX - paintWidth / 2,
                        y: 0,
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
                        y: paintLength,
                        innerRadius: 0,
                        outerRadius: freeThrowCircleRadius,
                        angle: 180,
                        rotation: 90,
                        stroke: lineColor,
                        strokeWidth: lineWidth,
                        fill: 'transparent',
                    }"
                />
                <v-arc
                    :config="{
                        x: centerX,
                        y: paintLength,
                        innerRadius: 0,
                        outerRadius: freeThrowCircleRadius,
                        angle: 180,
                        rotation: -90,
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

                <!-- Restricted Area -->
                <v-arc
                    :config="{
                        x: centerX,
                        y: basketOffset,
                        innerRadius: 0,
                        outerRadius: restrictedAreaRadius,
                        angle: 180,
                        rotation: 90,
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
                            backboardOffset,
                            centerX + backboardWidth / 2,
                            backboardOffset,
                        ],
                        stroke: lineColor,
                        strokeWidth: lineWidth + 1,
                    }"
                />

                <!-- Basket -->
                <v-circle
                    :config="{
                        x: centerX,
                        y: basketOffset,
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

// FIBA Court Dimensions (in meters)
const COURT = {
    length: 28, // Full court length
    width: 15, // Court width
    threePointRadius: 6.75,
    threePointCorner: 0.9,
    paintWidth: 4.9,
    paintLength: 5.8,
    freeThrowCircleRadius: 1.8,
    centerCircleRadius: 1.8,
    restrictedAreaRadius: 1.25,
    basketOffset: 1.575,
    backboardOffset: 1.2,
    backboardWidth: 1.8,
    rimRadius: 0.225,
};

// Scale factors
const scaleX = computed(() => props.width / COURT.width);
const scaleY = computed(() => props.height / COURT.length);

// Computed dimensions
const courtWidth = computed(() => props.width);
const courtHeight = computed(() => props.height);
const centerX = computed(() => courtWidth.value / 2);
const centerY = computed(() => courtHeight.value / 2);

const paintWidth = computed(() => COURT.paintWidth * scaleX.value);
const paintLength = computed(() => COURT.paintLength * scaleY.value);
const freeThrowCircleRadius = computed(() => COURT.freeThrowCircleRadius * scaleX.value);
const centerCircleRadius = computed(() => COURT.centerCircleRadius * scaleX.value);
const restrictedAreaRadius = computed(() => COURT.restrictedAreaRadius * scaleX.value);
const basketOffset = computed(() => COURT.basketOffset * scaleY.value);
const backboardOffset = computed(() => COURT.backboardOffset * scaleY.value);
const backboardWidth = computed(() => COURT.backboardWidth * scaleX.value);
const rimRadius = computed(() => COURT.rimRadius * scaleX.value);
const threePointRadius = computed(() => COURT.threePointRadius * scaleX.value);
const threePointCornerY = computed(() => COURT.threePointCorner * scaleY.value);

// Three point line drawing function
const drawThreePointLine = (context, shape, side) => {
    const cx = centerX.value;
    const radius = threePointRadius.value;
    const leftCornerX = cx - (COURT.width / 2 - 0.9) * scaleX.value;
    const rightCornerX = cx + (COURT.width / 2 - 0.9) * scaleX.value;

    context.beginPath();

    if (side === 'bottom') {
        const basketY = courtHeight.value - basketOffset.value;
        const cornerY = courtHeight.value - threePointCornerY.value;

        context.moveTo(leftCornerX, courtHeight.value);
        context.lineTo(leftCornerX, cornerY);

        const startAngle = Math.PI - Math.acos((leftCornerX - cx) / radius);
        context.arc(cx, basketY, radius, startAngle, -startAngle, false);

        context.lineTo(rightCornerX, courtHeight.value);
    } else {
        const basketY = basketOffset.value;
        const cornerY = threePointCornerY.value;

        context.moveTo(leftCornerX, 0);
        context.lineTo(leftCornerX, cornerY);

        const startAngle = Math.PI + Math.acos((leftCornerX - cx) / radius);
        context.arc(cx, basketY, radius, startAngle, -startAngle + Math.PI * 2, true);

        context.lineTo(rightCornerX, 0);
    }

    context.fillStrokeShape(shape);
};
</script>
