<template>
    <v-group
        :config="{
            draggable: false,
        }"
        @click="handleClick"
        @tap="handleClick"
    >
        <!-- Wavy dribble line -->
        <v-shape
            :config="{
                sceneFunc: drawWavyLine,
                stroke: strokeColor,
                strokeWidth: strokeWidth,
                lineCap: 'round',
                lineJoin: 'round',
                shadowColor: selected ? '#ffc107' : 'transparent',
                shadowBlur: selected ? 8 : 0,
            }"
        />

        <!-- Arrow head at the end -->
        <v-shape
            v-if="showArrow && points.length >= 2"
            :config="{
                sceneFunc: drawArrowHead,
                fill: strokeColor,
                stroke: strokeColor,
                strokeWidth: 1,
            }"
        />

        <!-- Control points for editing -->
        <template v-if="editable && selected">
            <v-circle
                v-for="(point, index) in points"
                :key="'control-' + index"
                :config="{
                    x: point.x,
                    y: point.y,
                    radius: 6,
                    fill: '#ffc107',
                    stroke: '#ffffff',
                    strokeWidth: 1,
                    draggable: true,
                }"
                @dragmove="(e) => handleControlPointDrag(e, index)"
                @dragend="(e) => handleControlPointDragEnd(e, index)"
            />
        </template>
    </v-group>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    id: {
        type: String,
        required: true,
    },
    points: {
        type: Array,
        required: true, // Array of {x, y} objects
    },
    color: {
        type: String,
        default: '#f59e0b', // Orange for dribble
    },
    strokeWidth: {
        type: Number,
        default: 3,
    },
    waveAmplitude: {
        type: Number,
        default: 6,
    },
    waveFrequency: {
        type: Number,
        default: 0.15,
    },
    showArrow: {
        type: Boolean,
        default: true,
    },
    selected: {
        type: Boolean,
        default: false,
    },
    editable: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['update:points', 'select']);

const strokeColor = computed(() => props.color);

// Draw wavy line between points
const drawWavyLine = (context, shape) => {
    if (props.points.length < 2) return;

    context.beginPath();

    for (let i = 0; i < props.points.length - 1; i++) {
        const start = props.points[i];
        const end = props.points[i + 1];

        // Calculate direction and perpendicular
        const dx = end.x - start.x;
        const dy = end.y - start.y;
        const length = Math.sqrt(dx * dx + dy * dy);

        if (length === 0) continue;

        // Unit vectors
        const ux = dx / length;
        const uy = dy / length;

        // Perpendicular unit vectors
        const px = -uy;
        const py = ux;

        // Number of wave segments
        const segments = Math.max(10, Math.floor(length * props.waveFrequency));

        if (i === 0) {
            context.moveTo(start.x, start.y);
        }

        // Draw wavy path
        for (let j = 1; j <= segments; j++) {
            const t = j / segments;
            const x = start.x + dx * t;
            const y = start.y + dy * t;

            // Sine wave offset
            const waveOffset = Math.sin(t * Math.PI * 4) * props.waveAmplitude;

            context.lineTo(
                x + px * waveOffset,
                y + py * waveOffset
            );
        }
    }

    context.fillStrokeShape(shape);
};

// Arrow head drawing function
const drawArrowHead = (context, shape) => {
    if (props.points.length < 2) return;

    const lastPoint = props.points[props.points.length - 1];
    const prevPoint = props.points[props.points.length - 2];

    // Calculate angle
    const angle = Math.atan2(lastPoint.y - prevPoint.y, lastPoint.x - prevPoint.x);
    const arrowSize = 12;

    context.beginPath();
    context.moveTo(lastPoint.x, lastPoint.y);
    context.lineTo(
        lastPoint.x - arrowSize * Math.cos(angle - Math.PI / 6),
        lastPoint.y - arrowSize * Math.sin(angle - Math.PI / 6)
    );
    context.lineTo(
        lastPoint.x - arrowSize * Math.cos(angle + Math.PI / 6),
        lastPoint.y - arrowSize * Math.sin(angle + Math.PI / 6)
    );
    context.closePath();
    context.fillStrokeShape(shape);
};

// Event handlers
const handleClick = () => {
    emit('select', { id: props.id });
};

const handleControlPointDrag = (e, index) => {
    const node = e.target;
    const newPoints = [...props.points];
    newPoints[index] = { x: node.x(), y: node.y() };
    emit('update:points', { id: props.id, points: newPoints });
};

const handleControlPointDragEnd = (e, index) => {
    const node = e.target;
    const newPoints = [...props.points];
    newPoints[index] = { x: node.x(), y: node.y() };
    emit('update:points', { id: props.id, points: newPoints });
};
</script>
