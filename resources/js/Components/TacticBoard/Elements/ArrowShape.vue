<template>
    <v-group>
        <!-- Main arrow -->
        <v-arrow
            :config="{
                points: flatPoints,
                stroke: color,
                strokeWidth: strokeWidth,
                fill: color,
                pointerLength: pointerLength,
                pointerWidth: pointerWidth,
                lineCap: 'round',
                lineJoin: 'round',
                hitStrokeWidth: 20,
            }"
            @click="handleClick"
            @tap="handleClick"
        />

        <!-- Selection highlight -->
        <v-arrow
            v-if="selected"
            :config="{
                points: flatPoints,
                stroke: '#fbbf24',
                strokeWidth: strokeWidth + 4,
                fill: '#fbbf24',
                pointerLength: pointerLength + 4,
                pointerWidth: pointerWidth + 4,
                opacity: 0.3,
                listening: false,
            }"
        />

        <!-- Control points when selected -->
        <template v-if="selected">
            <v-circle
                v-for="(point, index) in points"
                :key="`control-${index}`"
                :config="{
                    x: point.x,
                    y: point.y,
                    radius: 8,
                    fill: index === 0 ? '#22c55e' : '#ef4444',
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
        default: () => [{ x: 0, y: 0 }, { x: 60, y: 0 }],
    },
    color: {
        type: String,
        default: '#ffffff',
    },
    strokeWidth: {
        type: Number,
        default: 3,
    },
    pointerLength: {
        type: Number,
        default: 10,
    },
    pointerWidth: {
        type: Number,
        default: 10,
    },
    selected: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['select', 'update:points']);

const localPoints = ref([...props.points]);

// Flatten points for Konva v-arrow
const flatPoints = computed(() => {
    return props.points.flatMap(p => [p.x, p.y]);
});

const handleClick = (e) => {
    e.cancelBubble = true;
    emit('select', { id: props.id });
};

const handleControlPointDrag = (e, index) => {
    const newPoints = [...props.points];
    newPoints[index] = {
        x: e.target.x(),
        y: e.target.y(),
    };
    localPoints.value = newPoints;
};

const handleControlPointDragEnd = () => {
    emit('update:points', {
        id: props.id,
        points: localPoints.value,
    });
};
</script>
