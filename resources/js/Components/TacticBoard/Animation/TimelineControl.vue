<template>
    <div class="timeline-control">
        <!-- Playback Buttons -->
        <div class="playback-buttons">
            <button
                class="control-btn"
                :class="{ active: !isPlaying && !isPaused }"
                title="Stopp"
                @click="$emit('stop')"
            >
                <StopIcon class="h-5 w-5" />
            </button>
            <button
                class="control-btn play-btn"
                :title="isPlaying && !isPaused ? 'Pause' : 'Abspielen'"
                @click="togglePlayPause"
            >
                <PauseIcon v-if="isPlaying && !isPaused" class="h-6 w-6" />
                <PlayIcon v-else class="h-6 w-6" />
            </button>
        </div>

        <!-- Progress Bar -->
        <div class="progress-section">
            <span class="time-display">{{ formatTime(currentTime) }}</span>
            <div class="progress-bar-container" @click="handleProgressClick" ref="progressBarRef">
                <div class="progress-bar">
                    <div
                        class="progress-fill"
                        :style="{ width: `${progress}%` }"
                    ></div>
                    <div
                        class="progress-handle"
                        :style="{ left: `${progress}%` }"
                        @mousedown="startDragging"
                    ></div>
                </div>
            </div>
            <span class="time-display">{{ formatTime(duration) }}</span>
        </div>

        <!-- Speed Control -->
        <div class="speed-section">
            <span class="section-label">Tempo</span>
            <select
                :value="playbackSpeed"
                @change="$emit('update:speed', parseFloat($event.target.value))"
                class="speed-select"
            >
                <option value="0.5">0.5x</option>
                <option value="1">1x</option>
                <option value="1.5">1.5x</option>
                <option value="2">2x</option>
            </select>
        </div>

        <!-- Loop Toggle -->
        <div class="loop-section">
            <button
                class="control-btn"
                :class="{ active: isLooping }"
                title="Wiederholen"
                @click="$emit('update:looping', !isLooping)"
            >
                <ArrowPathIcon class="h-5 w-5" />
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import {
    PlayIcon,
    PauseIcon,
    StopIcon,
    ArrowPathIcon,
} from '@heroicons/vue/24/solid';

const props = defineProps({
    isPlaying: {
        type: Boolean,
        default: false,
    },
    isPaused: {
        type: Boolean,
        default: false,
    },
    currentTime: {
        type: Number,
        default: 0,
    },
    duration: {
        type: Number,
        default: 5000,
    },
    progress: {
        type: Number,
        default: 0,
    },
    playbackSpeed: {
        type: Number,
        default: 1,
    },
    isLooping: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits([
    'play',
    'pause',
    'stop',
    'seek',
    'update:speed',
    'update:looping',
]);

const progressBarRef = ref(null);
const isDragging = ref(false);

// Format time in milliseconds to mm:ss.ms
const formatTime = (ms) => {
    const totalSeconds = Math.floor(ms / 1000);
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;
    const milliseconds = Math.floor((ms % 1000) / 100);
    return `${minutes}:${seconds.toString().padStart(2, '0')}.${milliseconds}`;
};

// Toggle play/pause
const togglePlayPause = () => {
    if (props.isPlaying && !props.isPaused) {
        emit('pause');
    } else {
        emit('play');
    }
};

// Handle click on progress bar
const handleProgressClick = (event) => {
    if (!progressBarRef.value) return;

    const rect = progressBarRef.value.getBoundingClientRect();
    const percentage = (event.clientX - rect.left) / rect.width;
    const newTime = Math.max(0, Math.min(percentage * props.duration, props.duration));
    emit('seek', newTime);
};

// Start dragging the progress handle
const startDragging = (event) => {
    event.preventDefault();
    isDragging.value = true;

    const handleMove = (moveEvent) => {
        if (!progressBarRef.value || !isDragging.value) return;

        const rect = progressBarRef.value.getBoundingClientRect();
        const percentage = (moveEvent.clientX - rect.left) / rect.width;
        const newTime = Math.max(0, Math.min(percentage * props.duration, props.duration));
        emit('seek', newTime);
    };

    const handleUp = () => {
        isDragging.value = false;
        document.removeEventListener('mousemove', handleMove);
        document.removeEventListener('mouseup', handleUp);
    };

    document.addEventListener('mousemove', handleMove);
    document.addEventListener('mouseup', handleUp);
};
</script>

<style scoped>
.timeline-control {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 12px 16px;
    background: #1f2937;
    border-radius: 8px;
}

.playback-buttons {
    display: flex;
    align-items: center;
    gap: 4px;
}

.control-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border: none;
    border-radius: 6px;
    background: #374151;
    color: #d1d5db;
    cursor: pointer;
    transition: all 0.15s ease;
}

.control-btn:hover {
    background: #4b5563;
    color: #ffffff;
}

.control-btn.active {
    background: #2563eb;
    color: #ffffff;
}

.control-btn.play-btn {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #e25822;
    color: #ffffff;
}

.control-btn.play-btn:hover {
    background: #c94d1c;
}

.progress-section {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 12px;
}

.time-display {
    font-size: 12px;
    font-family: monospace;
    color: #9ca3af;
    min-width: 50px;
}

.progress-bar-container {
    flex: 1;
    padding: 8px 0;
    cursor: pointer;
}

.progress-bar {
    position: relative;
    height: 6px;
    background: #374151;
    border-radius: 3px;
}

.progress-fill {
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    background: #e25822;
    border-radius: 3px;
    transition: width 0.05s linear;
}

.progress-handle {
    position: absolute;
    top: 50%;
    transform: translate(-50%, -50%);
    width: 14px;
    height: 14px;
    background: #ffffff;
    border: 2px solid #e25822;
    border-radius: 50%;
    cursor: grab;
    transition: transform 0.1s ease;
}

.progress-handle:hover {
    transform: translate(-50%, -50%) scale(1.2);
}

.progress-handle:active {
    cursor: grabbing;
}

.speed-section {
    display: flex;
    align-items: center;
    gap: 8px;
}

.section-label {
    font-size: 11px;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.speed-select {
    padding: 6px 10px;
    background: #374151;
    border: 1px solid #4b5563;
    border-radius: 6px;
    color: #d1d5db;
    font-size: 12px;
    cursor: pointer;
}

.speed-select:hover {
    border-color: #6b7280;
}

.speed-select:focus {
    outline: none;
    border-color: #e25822;
}

.loop-section {
    display: flex;
}
</style>
