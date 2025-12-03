<template>
    <v-group
        :config="{
            x: x,
            y: y,
            draggable: draggable,
        }"
        @dragstart="handleDragStart"
        @dragmove="handleDragMove"
        @dragend="handleDragEnd"
        @click="handleClick"
        @tap="handleClick"
    >
        <!-- Player Circle -->
        <v-circle
            :config="{
                x: 0,
                y: 0,
                radius: size,
                fill: fillColor,
                stroke: strokeColor,
                strokeWidth: 2,
                shadowColor: selected ? '#ffc107' : 'transparent',
                shadowBlur: selected ? 10 : 0,
                shadowOpacity: 0.8,
            }"
        />

        <!-- Ball indicator (small circle offset) -->
        <v-circle
            v-if="hasBall"
            :config="{
                x: size * 0.7,
                y: -size * 0.7,
                radius: size * 0.35,
                fill: '#ff6b35',
                stroke: '#ffffff',
                strokeWidth: 1,
            }"
        />

        <!-- Player number/label -->
        <v-text
            :config="{
                x: 0,
                y: 0,
                text: displayLabel,
                fontSize: fontSize,
                fontFamily: 'Arial',
                fontStyle: 'bold',
                fill: textColor,
                align: 'center',
                verticalAlign: 'middle',
                offsetX: textOffsetX,
                offsetY: fontSize / 2,
            }"
        />

        <!-- Selection indicator -->
        <v-ring
            v-if="selected"
            :config="{
                x: 0,
                y: 0,
                innerRadius: size + 2,
                outerRadius: size + 5,
                fill: '#ffc107',
                opacity: 0.7,
            }"
        />
    </v-group>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    id: {
        type: String,
        required: true,
    },
    x: {
        type: Number,
        default: 0,
    },
    y: {
        type: Number,
        default: 0,
    },
    number: {
        type: [String, Number],
        default: '',
    },
    label: {
        type: String,
        default: '',
    },
    team: {
        type: String,
        default: 'offense', // 'offense' or 'defense'
    },
    hasBall: {
        type: Boolean,
        default: false,
    },
    size: {
        type: Number,
        default: 20,
    },
    selected: {
        type: Boolean,
        default: false,
    },
    draggable: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['update:position', 'select', 'dragstart', 'dragend']);

// Colors based on team
const fillColor = computed(() => {
    if (props.team === 'offense') {
        return '#2563eb'; // Blue for offense
    } else if (props.team === 'defense') {
        return '#dc2626'; // Red for defense
    }
    return '#6b7280'; // Gray for neutral
});

const strokeColor = computed(() => {
    return props.selected ? '#ffc107' : '#ffffff';
});

const textColor = computed(() => '#ffffff');

// Display label (number or position label)
const displayLabel = computed(() => {
    if (props.number) {
        return String(props.number);
    }
    if (props.label) {
        return props.label.substring(0, 2).toUpperCase();
    }
    return '';
});

// Font size based on content
const fontSize = computed(() => {
    const len = displayLabel.value.length;
    if (len > 2) return props.size * 0.7;
    if (len > 1) return props.size * 0.8;
    return props.size * 0.9;
});

// Text offset for centering
const textOffsetX = computed(() => {
    const len = displayLabel.value.length;
    return (fontSize.value * len * 0.3);
});

// Event handlers
const handleDragStart = (e) => {
    emit('dragstart', { id: props.id, event: e });
};

const handleDragMove = (e) => {
    const node = e.target;
    emit('update:position', {
        id: props.id,
        x: node.x(),
        y: node.y(),
    });
};

const handleDragEnd = (e) => {
    const node = e.target;
    emit('update:position', {
        id: props.id,
        x: node.x(),
        y: node.y(),
    });
    emit('dragend', { id: props.id, event: e });
};

const handleClick = () => {
    emit('select', { id: props.id });
};
</script>
