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
        @dblclick="handleDoubleClick"
        @dbltap="handleDoubleClick"
    >
        <!-- Background (optional) -->
        <v-rect
            v-if="showBackground"
            :config="{
                x: -padding,
                y: -padding,
                width: textWidth + padding * 2,
                height: fontSize + padding * 2,
                fill: backgroundColor,
                cornerRadius: 4,
                opacity: backgroundOpacity,
            }"
        />

        <!-- Text -->
        <v-text
            ref="textNode"
            :config="{
                text: content,
                fontSize: fontSize,
                fontFamily: fontFamily,
                fontStyle: fontStyle,
                fill: textColor,
                align: 'left',
                shadowColor: selected ? '#ffc107' : 'transparent',
                shadowBlur: selected ? 4 : 0,
            }"
        />

        <!-- Selection indicator -->
        <v-rect
            v-if="selected"
            :config="{
                x: -padding - 2,
                y: -padding - 2,
                width: textWidth + padding * 2 + 4,
                height: fontSize + padding * 2 + 4,
                stroke: '#ffc107',
                strokeWidth: 2,
                dash: [6, 3],
                fill: 'transparent',
            }"
        />
    </v-group>
</template>

<script setup>
import { computed, ref, onMounted, watch } from 'vue';

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
    content: {
        type: String,
        default: 'Text',
    },
    fontSize: {
        type: Number,
        default: 16,
    },
    fontFamily: {
        type: String,
        default: 'Arial',
    },
    fontStyle: {
        type: String,
        default: 'normal', // 'normal', 'bold', 'italic', 'bold italic'
    },
    color: {
        type: String,
        default: '#ffffff',
    },
    showBackground: {
        type: Boolean,
        default: false,
    },
    backgroundColor: {
        type: String,
        default: '#000000',
    },
    backgroundOpacity: {
        type: Number,
        default: 0.5,
    },
    padding: {
        type: Number,
        default: 4,
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

const emit = defineEmits(['update:position', 'update:content', 'select', 'edit', 'dragstart', 'dragend']);

const textNode = ref(null);
const textWidth = ref(50);

const textColor = computed(() => props.color);

// Calculate text width for background
onMounted(() => {
    updateTextWidth();
});

watch(() => props.content, () => {
    updateTextWidth();
});

watch(() => props.fontSize, () => {
    updateTextWidth();
});

const updateTextWidth = () => {
    if (textNode.value) {
        const node = textNode.value.getNode();
        if (node) {
            textWidth.value = node.width();
        }
    }
};

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

const handleDoubleClick = () => {
    emit('edit', { id: props.id });
};
</script>
