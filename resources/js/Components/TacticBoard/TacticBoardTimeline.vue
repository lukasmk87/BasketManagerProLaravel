<template>
    <div class="tactic-board-timeline">
        <!-- Timeline Header -->
        <div class="timeline-header">
            <h3 class="timeline-title">Animation</h3>
            <div class="timeline-actions">
                <button
                    class="action-btn"
                    title="Keyframe hinzufügen"
                    @click="handleAddKeyframe"
                >
                    <PlusIcon class="h-4 w-4" />
                    <span>Keyframe</span>
                </button>
                <button
                    class="action-btn"
                    title="Vorschau"
                    @click="$emit('preview')"
                >
                    <EyeIcon class="h-4 w-4" />
                    <span>Vorschau</span>
                </button>
            </div>
        </div>

        <!-- Timeline Track -->
        <div class="timeline-track-container">
            <div class="timeline-track" ref="trackRef">
                <!-- Time markers -->
                <div class="time-markers">
                    <span
                        v-for="marker in timeMarkers"
                        :key="marker.time"
                        class="time-marker"
                        :style="{ left: `${marker.position}%` }"
                    >
                        {{ marker.label }}
                    </span>
                </div>

                <!-- Track background -->
                <div class="track-bg">
                    <!-- Current position indicator -->
                    <div
                        class="current-position"
                        :style="{ left: `${animation.progress.value}%` }"
                    ></div>

                    <!-- Keyframe markers -->
                    <KeyframeMarker
                        v-for="(keyframe, index) in animation.keyframes.value"
                        :key="index"
                        :keyframe="keyframe"
                        :index="index"
                        :duration="animation.totalDuration.value"
                        :isActive="activeKeyframeIndex === index"
                        @select="handleKeyframeSelect"
                        @move="handleKeyframeMove"
                        @edit="handleKeyframeEdit"
                        @delete="handleKeyframeDelete"
                        @update:easing="handleKeyframeEasingChange"
                    />
                </div>
            </div>
        </div>

        <!-- Timeline Controls -->
        <TimelineControl
            :isPlaying="animation.isPlaying.value"
            :isPaused="animation.isPaused.value"
            :currentTime="animation.currentTime.value"
            :duration="animation.totalDuration.value"
            :progress="animation.progress.value"
            :playbackSpeed="playbackSpeed"
            :isLooping="isLooping"
            @play="handlePlay"
            @pause="animation.pause()"
            @stop="animation.stop()"
            @seek="handleSeek"
            @update:speed="playbackSpeed = $event"
            @update:looping="isLooping = $event"
        />

        <!-- Duration Setting -->
        <div class="duration-setting">
            <label class="duration-label">Gesamtdauer:</label>
            <input
                type="number"
                :value="animation.totalDuration.value / 1000"
                @change="handleDurationChange"
                class="duration-input"
                min="1"
                max="60"
                step="0.5"
            />
            <span class="duration-suffix">Sekunden</span>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { PlusIcon, EyeIcon } from '@heroicons/vue/24/outline';
import { useTacticAnimation } from '@/Composables/tactic-board/useTacticAnimation';
import TimelineControl from './Animation/TimelineControl.vue';
import KeyframeMarker from './Animation/KeyframeMarker.vue';

const props = defineProps({
    animationData: {
        type: Object,
        default: null,
    },
    currentElements: {
        type: Object,
        default: () => ({ players: [], shapes: [], ball: null }),
    },
});

const emit = defineEmits([
    'preview',
    'keyframe-added',
    'keyframe-updated',
    'keyframe-deleted',
    'positions-changed',
]);

// Animation composable
const animation = useTacticAnimation();

// Local state
const trackRef = ref(null);
const playbackSpeed = ref(1);
const isLooping = ref(false);
const activeKeyframeIndex = ref(-1);

// Generate time markers for the timeline
const timeMarkers = computed(() => {
    const duration = animation.totalDuration.value;
    if (duration === 0) return [];

    const markers = [];
    const intervalMs = duration <= 5000 ? 1000 : 2000;
    const numMarkers = Math.floor(duration / intervalMs) + 1;

    for (let i = 0; i < numMarkers; i++) {
        const time = i * intervalMs;
        markers.push({
            time,
            position: (time / duration) * 100,
            label: `${(time / 1000).toFixed(1)}s`,
        });
    }

    return markers;
});

// Import animation data on mount or when prop changes
watch(
    () => props.animationData,
    (data) => {
        if (data) {
            animation.importAnimationData(data);
        }
    },
    { immediate: true }
);

// Watch for position changes during playback
watch(
    () => animation.currentPositions.value,
    (positions) => {
        emit('positions-changed', positions);
    },
    { deep: true }
);

// Handle play with looping support
const handlePlay = () => {
    animation.play();
};

// Watch for animation end to handle looping
watch(
    () => animation.isPlaying.value,
    (playing, wasPlaying) => {
        if (wasPlaying && !playing && isLooping.value) {
            // Animation ended and looping is enabled
            animation.seekTo(0);
            animation.play();
        }
    }
);

// Handle seek
const handleSeek = (time) => {
    animation.seekTo(time);
    updateActiveKeyframe(time);
};

// Update active keyframe based on current time
const updateActiveKeyframe = (time) => {
    const keyframes = animation.keyframes.value;
    for (let i = keyframes.length - 1; i >= 0; i--) {
        if (keyframes[i].time <= time) {
            activeKeyframeIndex.value = i;
            return;
        }
    }
    activeKeyframeIndex.value = -1;
};

// Handle keyframe selection
const handleKeyframeSelect = (index) => {
    activeKeyframeIndex.value = index;
    const keyframe = animation.keyframes.value[index];
    if (keyframe) {
        animation.seekTo(keyframe.time);
    }
};

// Handle keyframe move
const handleKeyframeMove = ({ index, newTime }) => {
    animation.updateKeyframe(index, { time: newTime });
    emit('keyframe-updated', { index, keyframe: animation.keyframes.value[index] });
};

// Handle keyframe edit
const handleKeyframeEdit = (index) => {
    // Capture current element positions for this keyframe
    const elements = captureCurrentElements();
    animation.updateKeyframe(index, { elements });
    emit('keyframe-updated', { index, keyframe: animation.keyframes.value[index] });
};

// Handle keyframe delete
const handleKeyframeDelete = (index) => {
    animation.removeKeyframe(index);
    emit('keyframe-deleted', index);

    if (activeKeyframeIndex.value === index) {
        activeKeyframeIndex.value = -1;
    } else if (activeKeyframeIndex.value > index) {
        activeKeyframeIndex.value--;
    }
};

// Handle keyframe easing change (Phase 11.2)
const handleKeyframeEasingChange = ({ index, easing }) => {
    animation.updateKeyframe(index, { easing });
    emit('keyframe-updated', { index, keyframe: animation.keyframes.value[index] });
};

// Handle adding a new keyframe
const handleAddKeyframe = () => {
    const currentTime = animation.currentTime.value;
    const elements = captureCurrentElements();

    animation.addKeyframe(currentTime, elements);

    // Find the new keyframe's index
    const newIndex = animation.keyframes.value.findIndex(k => k.time === currentTime);
    activeKeyframeIndex.value = newIndex;

    emit('keyframe-added', { time: currentTime, elements });
};

// Capture current element positions
const captureCurrentElements = () => {
    const elements = {};

    if (props.currentElements.players) {
        props.currentElements.players.forEach(player => {
            elements[player.id] = { x: player.x, y: player.y };
        });
    }

    if (props.currentElements.shapes) {
        props.currentElements.shapes.forEach(shape => {
            elements[shape.id] = {
                x: shape.x,
                y: shape.y,
                rotation: shape.rotation || 0,
            };
        });
    }

    // Capture ball position (Phase 11.1)
    if (props.currentElements.ball) {
        elements[props.currentElements.ball.id] = {
            x: props.currentElements.ball.x,
            y: props.currentElements.ball.y,
        };
    }

    return elements;
};

// Handle duration change
const handleDurationChange = (event) => {
    const seconds = parseFloat(event.target.value);
    if (!isNaN(seconds) && seconds > 0) {
        animation.duration.value = seconds * 1000;
    }
};

// Expose methods for parent
defineExpose({
    exportAnimationData: () => animation.exportAnimationData(),
    importAnimationData: (data) => animation.importAnimationData(data),
    addKeyframe: handleAddKeyframe,
    play: animation.play,
    pause: animation.pause,
    stop: animation.stop,
    seekTo: animation.seekTo,
    getAnimation: () => animation,
});
</script>

<style scoped>
.tactic-board-timeline {
    background: var(--tb-bg-darkest);
    border-radius: 8px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.timeline-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.timeline-title {
    font-size: 14px;
    font-weight: 600;
    color: var(--tb-text-heading);
    margin: 0;
}

.timeline-actions {
    display: flex;
    gap: 8px;
}

.action-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: var(--tb-bg-button);
    border: none;
    border-radius: 6px;
    color: var(--tb-text-primary);
    font-size: 12px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.action-btn:hover {
    background: var(--tb-bg-hover);
    color: var(--tb-text-heading);
}

.timeline-track-container {
    background: var(--tb-bg-canvas);
    border-radius: 6px;
    padding: 12px 16px;
}

.timeline-track {
    position: relative;
    height: 60px;
}

.time-markers {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 20px;
}

.time-marker {
    position: absolute;
    transform: translateX(-50%);
    font-size: 10px;
    color: #6b7280;
}

.track-bg {
    position: absolute;
    bottom: 10px;
    left: 0;
    right: 0;
    height: 24px;
    background: var(--tb-bg-panel);
    border-radius: 4px;
}

.current-position {
    position: absolute;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e25822;
    transform: translateX(-50%);
    z-index: 5;
}

.current-position::before {
    content: '';
    position: absolute;
    top: -4px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 6px solid transparent;
    border-right: 6px solid transparent;
    border-top: 6px solid #e25822;
}

.duration-setting {
    display: flex;
    align-items: center;
    gap: 8px;
    padding-top: 8px;
    border-top: 1px solid #374151;
}

.duration-label {
    font-size: 12px;
    color: #9ca3af;
}

.duration-input {
    width: 60px;
    padding: 4px 8px;
    background: #374151;
    border: 1px solid #4b5563;
    border-radius: 4px;
    color: #ffffff;
    font-size: 12px;
    text-align: center;
}

.duration-input:focus {
    outline: none;
    border-color: #e25822;
}

.duration-suffix {
    font-size: 12px;
    color: #9ca3af;
}
</style>
