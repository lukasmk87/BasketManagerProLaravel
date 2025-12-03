<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="show" class="animation-preview-overlay" @click.self="close">
                <div class="animation-preview-modal">
                    <!-- Header -->
                    <div class="modal-header">
                        <h2 class="modal-title">Animation Vorschau</h2>
                        <button class="close-btn" @click="close" title="Schließen (ESC)">
                            <XMarkIcon class="h-6 w-6" />
                        </button>
                    </div>

                    <!-- Viewer Container -->
                    <div class="viewer-container">
                        <div class="viewer-wrapper">
                            <v-stage
                                ref="stageRef"
                                :config="stageConfig"
                            >
                                <!-- Court Layer -->
                                <v-layer>
                                    <component
                                        :is="courtComponent"
                                        :width="canvasWidth"
                                        :height="canvasHeight"
                                        :courtColor="playData?.court?.backgroundColor || '#1a5f2a'"
                                    />
                                </v-layer>

                                <!-- Elements Layer -->
                                <v-layer>
                                    <!-- Paths -->
                                    <component
                                        v-for="path in playData?.elements?.paths || []"
                                        :key="path.id"
                                        :is="getPathComponent(path.type)"
                                        :id="path.id"
                                        :points="path.points"
                                        :color="path.color"
                                        :selected="false"
                                        :draggable="false"
                                    />

                                    <!-- Shapes -->
                                    <ScreenShape
                                        v-for="shape in animatedShapes"
                                        :key="shape.id"
                                        :id="shape.id"
                                        :x="shape.x"
                                        :y="shape.y"
                                        :rotation="shape.rotation || 0"
                                        :width="shape.width || 40"
                                        :color="shape.color || '#ffffff'"
                                        :selected="false"
                                        :draggable="false"
                                    />

                                    <!-- Annotations -->
                                    <TextAnnotation
                                        v-for="annotation in playData?.elements?.annotations || []"
                                        :key="annotation.id"
                                        :id="annotation.id"
                                        :x="annotation.x"
                                        :y="annotation.y"
                                        :content="annotation.content"
                                        :fontSize="annotation.fontSize || 16"
                                        :color="annotation.color || '#ffffff'"
                                        :selected="false"
                                        :draggable="false"
                                    />

                                    <!-- Players (animated) -->
                                    <PlayerToken
                                        v-for="player in animatedPlayers"
                                        :key="player.id"
                                        :id="player.id"
                                        :x="player.x"
                                        :y="player.y"
                                        :number="player.number"
                                        :label="player.label"
                                        :team="player.team"
                                        :hasBall="player.hasBall"
                                        :selected="false"
                                        :draggable="false"
                                    />
                                </v-layer>
                            </v-stage>
                        </div>
                    </div>

                    <!-- Controls -->
                    <div class="modal-controls">
                        <TimelineControl
                            :isPlaying="animation.isPlaying.value"
                            :isPaused="animation.isPaused.value"
                            :currentTime="animation.currentTime.value"
                            :duration="animation.totalDuration.value"
                            :progress="animation.progress.value"
                            :playbackSpeed="playbackSpeed"
                            :isLooping="isLooping"
                            @play="animation.play()"
                            @pause="animation.pause()"
                            @stop="animation.stop()"
                            @seek="animation.seekTo($event)"
                            @update:speed="playbackSpeed = $event"
                            @update:looping="isLooping = $event"
                        />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, markRaw } from 'vue';
import { XMarkIcon } from '@heroicons/vue/24/outline';
import { useTacticAnimation } from '@/composables/useTacticAnimation';

// Components
import TimelineControl from './TimelineControl.vue';
import HalfCourtHorizontal from '../Court/HalfCourtHorizontal.vue';
import FullCourt from '../Court/FullCourt.vue';
import HalfCourtVertical from '../Court/HalfCourtVertical.vue';
import PlayerToken from '../Elements/PlayerToken.vue';
import MovementPath from '../Elements/MovementPath.vue';
import PassLine from '../Elements/PassLine.vue';
import DribblePath from '../Elements/DribblePath.vue';
import ScreenShape from '../Elements/ScreenShape.vue';
import TextAnnotation from '../Elements/TextAnnotation.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    playData: {
        type: Object,
        default: null,
    },
    animationData: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close']);

// Animation composable
const animation = useTacticAnimation();

// Refs
const stageRef = ref(null);

// Local state
const playbackSpeed = ref(1);
const isLooping = ref(true);
const canvasWidth = ref(600);
const canvasHeight = ref(450);

// Stage configuration
const stageConfig = computed(() => ({
    width: canvasWidth.value,
    height: canvasHeight.value,
}));

// Court component based on type
const courtComponent = computed(() => {
    const courtType = props.playData?.court?.type || 'half_horizontal';
    switch (courtType) {
        case 'full':
            return markRaw(FullCourt);
        case 'half_vertical':
            return markRaw(HalfCourtVertical);
        case 'half_horizontal':
        default:
            return markRaw(HalfCourtHorizontal);
    }
});

// Get path component based on type
const getPathComponent = (type) => {
    switch (type) {
        case 'pass':
            return markRaw(PassLine);
        case 'dribble':
            return markRaw(DribblePath);
        case 'movement':
        default:
            return markRaw(MovementPath);
    }
};

// Animated players with interpolated positions
const animatedPlayers = computed(() => {
    const players = props.playData?.elements?.players || [];
    const positions = animation.currentPositions.value;

    return players.map(player => {
        const animatedPos = positions[player.id];
        return {
            ...player,
            x: animatedPos?.x ?? player.x,
            y: animatedPos?.y ?? player.y,
        };
    });
});

// Animated shapes with interpolated positions
const animatedShapes = computed(() => {
    const shapes = props.playData?.elements?.shapes || [];
    const positions = animation.currentPositions.value;

    return shapes.map(shape => {
        const animatedPos = positions[shape.id];
        return {
            ...shape,
            x: animatedPos?.x ?? shape.x,
            y: animatedPos?.y ?? shape.y,
            rotation: animatedPos?.rotation ?? shape.rotation ?? 0,
        };
    });
});

// Watch for show changes
watch(
    () => props.show,
    (showing) => {
        if (showing) {
            loadAndPlay();
        } else {
            animation.stop();
        }
    }
);

// Watch for animation data changes
watch(
    () => props.animationData,
    (data) => {
        if (data && props.show) {
            animation.importAnimationData(data);
        }
    }
);

// Load animation data and auto-play
const loadAndPlay = () => {
    if (props.animationData) {
        animation.importAnimationData(props.animationData);
        // Small delay to ensure everything is loaded
        setTimeout(() => {
            animation.play();
        }, 100);
    }
};

// Close the modal
const close = () => {
    animation.stop();
    emit('close');
};

// Handle keyboard events
const handleKeydown = (event) => {
    if (!props.show) return;

    switch (event.key) {
        case 'Escape':
            close();
            break;
        case ' ':
            event.preventDefault();
            if (animation.isPlaying.value && !animation.isPaused.value) {
                animation.pause();
            } else {
                animation.play();
            }
            break;
        case 'ArrowLeft':
            event.preventDefault();
            animation.seekTo(Math.max(0, animation.currentTime.value - 500));
            break;
        case 'ArrowRight':
            event.preventDefault();
            animation.seekTo(Math.min(animation.totalDuration.value, animation.currentTime.value + 500));
            break;
    }
};

// Watch for animation end to handle looping
watch(
    () => animation.isPlaying.value,
    (playing, wasPlaying) => {
        if (wasPlaying && !playing && isLooping.value && props.show) {
            animation.seekTo(0);
            animation.play();
        }
    }
);

onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
    animation.stop();
});
</script>

<style scoped>
.animation-preview-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.85);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.animation-preview-modal {
    background: #111827;
    border-radius: 12px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    max-width: 90vw;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid #374151;
}

.modal-title {
    font-size: 18px;
    font-weight: 600;
    color: #ffffff;
    margin: 0;
}

.close-btn {
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

.close-btn:hover {
    background: #4b5563;
    color: #ffffff;
}

.viewer-container {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: #0f172a;
    min-height: 400px;
}

.viewer-wrapper {
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.modal-controls {
    padding: 16px 20px;
    border-top: 1px solid #374151;
}

/* Modal transitions */
.modal-enter-active,
.modal-leave-active {
    transition: all 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-from .animation-preview-modal,
.modal-leave-to .animation-preview-modal {
    transform: scale(0.95);
}
</style>
