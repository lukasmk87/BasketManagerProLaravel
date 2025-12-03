<template>
    <v-group>
        <!-- Main freehand path -->
        <v-line
            :config="{
                points: flatPoints,
                stroke: color,
                strokeWidth: strokeWidth,
                lineCap: 'round',
                lineJoin: 'round',
                tension: 0,
                dash: getDash,
                opacity: selected ? 1 : 0.9,
                hitStrokeWidth: 20,
            }"
            @click="handleClick"
            @tap="handleClick"
        />

        <!-- Selection highlight -->
        <v-line
            v-if="selected"
            :config="{
                points: flatPoints,
                stroke: '#fbbf24',
                strokeWidth: strokeWidth + 4,
                lineCap: 'round',
                lineJoin: 'round',
                tension: 0,
                opacity: 0.3,
                listening: false,
            }"
        />

        <!-- Control points when selected -->
        <template v-if="selected && isEditing">
            <v-circle
                v-for="(point, index) in points"
                :key="`control-${index}`"
                :config="{
                    x: point.x,
                    y: point.y,
                    radius: 6,
                    fill: '#fbbf24',
                    stroke: '#ffffff',
                    strokeWidth: 2,
                    draggable: true,
                }"
                @dragmove="(e) => handleControlPointDrag(e, index)"
                @dragend="handleControlPointDragEnd"
            />
        </template>
    </v-group>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    id: {
        type: String,
        required: true,
    },
    points: {
        type: Array,
        required: true,
        default: () => [],
    },
    color: {
        type: String,
        default: '#ffffff',
    },
    strokeWidth: {
        type: Number,
        default: 3,
    },
    lineStyle: {
        type: String,
        default: 'solid', // 'solid', 'dashed', 'dotted'
    },
    selected: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['select', 'update:points']);

const isEditing = ref(false);
const localPoints = ref([...props.points]);

// Flatten points for Konva v-line
const flatPoints = computed(() => {
    return props.points.flatMap(p => [p.x, p.y]);
});

// Get dash pattern based on line style
const getDash = computed(() => {
    switch (props.lineStyle) {
        case 'dashed':
            return [12, 6];
        case 'dotted':
            return [4, 4];
        default:
            return [];
    }
});

// Handle click to select
const handleClick = (e) => {
    e.cancelBubble = true;
    emit('select', { id: props.id });
    isEditing.value = true;
};

// Handle control point drag
const handleControlPointDrag = (e, index) => {
    const newPoints = [...props.points];
    newPoints[index] = {
        x: e.target.x(),
        y: e.target.y(),
    };
    localPoints.value = newPoints;
};

// Handle control point drag end - emit final position
const handleControlPointDragEnd = () => {
    emit('update:points', {
        id: props.id,
        points: localPoints.value,
    });
};

// Simplify points (reduce number of points for smoother performance)
const simplifyPoints = (points, tolerance = 2) => {
    if (points.length < 3) return points;

    const result = [points[0]];

    for (let i = 1; i < points.length - 1; i++) {
        const prev = result[result.length - 1];
        const curr = points[i];

        const distance = Math.sqrt(
            Math.pow(curr.x - prev.x, 2) + Math.pow(curr.y - prev.y, 2)
        );

        if (distance >= tolerance) {
            result.push(curr);
        }
    }

    result.push(points[points.length - 1]);
    return result;
};
</script>
