<template>
    <v-group
        :config="{
            draggable: false,
        }"
        @click="handleClick"
        @tap="handleClick"
    >
        <!-- Dashed pass line -->
        <v-line
            :config="{
                points: flatPoints,
                stroke: strokeColor,
                strokeWidth: strokeWidth,
                lineCap: 'round',
                lineJoin: 'round',
                dash: [12, 6],
                tension: 0,
                shadowColor: selected ? '#ffc107' : 'transparent',
                shadowBlur: selected ? 8 : 0,
            }"
        />

        <!-- Arrow head at the end -->
        <v-shape
            v-if="showArrow && flatPoints.length >= 4"
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
                v-for="(point, index) in controlPoints"
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
        default: '#22c55e', // Green for passes
    },
    strokeWidth: {
        type: Number,
        default: 3,
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

// Flatten points array for Konva Line
const flatPoints = computed(() => {
    return props.points.flatMap(p => [p.x, p.y]);
});

// Control points for editing
const controlPoints = computed(() => props.points);

const strokeColor = computed(() => props.color);

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
