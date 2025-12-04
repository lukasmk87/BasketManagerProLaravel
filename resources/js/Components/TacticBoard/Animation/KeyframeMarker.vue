<template>
    <div
        class="keyframe-marker"
        :class="{ active: isActive, dragging: isDragging }"
        :style="{ left: `${position}%` }"
        @mousedown="handleMouseDown"
        @click.stop="handleClick"
        @contextmenu.prevent="showContextMenu"
    >
        <div class="marker-dot"></div>
        <div class="marker-time">{{ formatTime(keyframe.time) }}</div>

        <!-- Context Menu -->
        <Teleport to="body">
            <div
                v-if="showMenu"
                class="context-menu"
                :style="menuPosition"
                @click.stop
            >
                <!-- Easing Selection (Phase 11.2) -->
                <div class="menu-item easing-item">
                    <SparklesIcon class="h-4 w-4" />
                    <span>Easing</span>
                    <select
                        class="easing-select"
                        :value="keyframe.easing || 'linear'"
                        @change="handleEasingChange"
                        @click.stop
                    >
                        <option
                            v-for="opt in easingOptions"
                            :key="opt.value"
                            :value="opt.value"
                        >
                            {{ opt.label }}
                        </option>
                    </select>
                </div>
                <div class="menu-divider"></div>
                <button class="menu-item" @click="handleEdit">
                    <PencilIcon class="h-4 w-4" />
                    <span>Bearbeiten</span>
                </button>
                <button class="menu-item danger" @click="handleDelete">
                    <TrashIcon class="h-4 w-4" />
                    <span>Löschen</span>
                </button>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { PencilIcon, TrashIcon, SparklesIcon } from '@heroicons/vue/24/outline';
import { easingOptions } from '@/utils/easingFunctions';

const props = defineProps({
    keyframe: {
        type: Object,
        required: true,
    },
    index: {
        type: Number,
        required: true,
    },
    duration: {
        type: Number,
        required: true,
    },
    isActive: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['select', 'move', 'edit', 'delete', 'update:easing']);

const isDragging = ref(false);
const showMenu = ref(false);
const menuPosition = ref({ top: '0px', left: '0px' });

// Calculate position as percentage
const position = computed(() => {
    if (props.duration === 0) return 0;
    return (props.keyframe.time / props.duration) * 100;
});

// Format time in milliseconds to seconds
const formatTime = (ms) => {
    const seconds = (ms / 1000).toFixed(1);
    return `${seconds}s`;
};

// Handle click to select/jump to keyframe
const handleClick = () => {
    emit('select', props.index);
};

// Handle mouse down for dragging
const handleMouseDown = (event) => {
    if (event.button !== 0) return; // Only left click
    event.preventDefault();
    isDragging.value = true;

    const startX = event.clientX;
    const startTime = props.keyframe.time;
    const container = event.target.closest('.timeline-track');

    if (!container) return;

    const containerRect = container.getBoundingClientRect();

    const handleMove = (moveEvent) => {
        if (!isDragging.value) return;

        const deltaX = moveEvent.clientX - startX;
        const deltaPercent = (deltaX / containerRect.width) * 100;
        const deltaTime = (deltaPercent / 100) * props.duration;

        let newTime = startTime + deltaTime;
        newTime = Math.max(0, Math.min(newTime, props.duration));

        emit('move', { index: props.index, newTime });
    };

    const handleUp = () => {
        isDragging.value = false;
        document.removeEventListener('mousemove', handleMove);
        document.removeEventListener('mouseup', handleUp);
    };

    document.addEventListener('mousemove', handleMove);
    document.addEventListener('mouseup', handleUp);
};

// Show context menu
const showContextMenu = (event) => {
    menuPosition.value = {
        top: `${event.clientY}px`,
        left: `${event.clientX}px`,
    };
    showMenu.value = true;
};

// Handle edit
const handleEdit = () => {
    showMenu.value = false;
    emit('edit', props.index);
};

// Handle delete
const handleDelete = () => {
    showMenu.value = false;
    emit('delete', props.index);
};

// Handle easing change (Phase 11.2)
const handleEasingChange = (event) => {
    const newEasing = event.target.value;
    emit('update:easing', { index: props.index, easing: newEasing });
};

// Close context menu on outside click
const closeMenu = () => {
    showMenu.value = false;
};

onMounted(() => {
    document.addEventListener('click', closeMenu);
});

onUnmounted(() => {
    document.removeEventListener('click', closeMenu);
});
</script>

<style scoped>
.keyframe-marker {
    position: absolute;
    top: 50%;
    transform: translate(-50%, -50%);
    cursor: pointer;
    z-index: 10;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.marker-dot {
    width: 16px;
    height: 16px;
    background: #ffffff;
    border: 3px solid #6b7280;
    border-radius: 50%;
    transition: all 0.15s ease;
}

.keyframe-marker:hover .marker-dot {
    border-color: #e25822;
    transform: scale(1.2);
}

.keyframe-marker.active .marker-dot {
    background: #e25822;
    border-color: #e25822;
    box-shadow: 0 0 0 4px rgba(226, 88, 34, 0.3);
}

.keyframe-marker.dragging .marker-dot {
    cursor: grabbing;
    transform: scale(1.3);
}

.marker-time {
    position: absolute;
    top: 100%;
    margin-top: 4px;
    font-size: 10px;
    color: #9ca3af;
    white-space: nowrap;
    opacity: 0;
    transition: opacity 0.15s ease;
}

.keyframe-marker:hover .marker-time,
.keyframe-marker.active .marker-time {
    opacity: 1;
}

.context-menu {
    position: fixed;
    background: #1f2937;
    border: 1px solid #374151;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
    z-index: 1000;
    min-width: 150px;
    overflow: hidden;
}

.menu-item {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    padding: 10px 14px;
    background: none;
    border: none;
    color: #d1d5db;
    font-size: 13px;
    cursor: pointer;
    transition: background 0.15s ease;
    text-align: left;
}

.menu-item:hover {
    background: #374151;
    color: #ffffff;
}

.menu-item.danger:hover {
    background: #dc2626;
}

/* Easing Item Styles (Phase 11.2) */
.menu-item.easing-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    cursor: default;
}

.easing-select {
    flex: 1;
    padding: 4px 8px;
    background: #374151;
    border: 1px solid #4b5563;
    border-radius: 4px;
    color: #ffffff;
    font-size: 12px;
    cursor: pointer;
}

.easing-select:focus {
    outline: none;
    border-color: #e25822;
}

.menu-divider {
    height: 1px;
    background: #374151;
    margin: 4px 0;
}
</style>
