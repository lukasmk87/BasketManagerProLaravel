import { ref, computed, watch, onUnmounted } from 'vue';

/**
 * Composable for managing animations in the Tactic Board
 */
export function useTacticAnimation() {
    // Animation state
    const isPlaying = ref(false);
    const isPaused = ref(false);
    const currentTime = ref(0);
    const duration = ref(5000); // Default 5 seconds

    // Keyframes
    const keyframes = ref([]);

    // Animation frame ID for cleanup
    let animationFrameId = null;
    let startTimestamp = null;
    let pausedTime = 0;

    // Current element positions (for interpolation)
    const currentPositions = ref({});

    /**
     * Total duration in milliseconds
     */
    const totalDuration = computed(() => {
        if (keyframes.value.length === 0) return duration.value;
        const lastKeyframe = keyframes.value[keyframes.value.length - 1];
        return lastKeyframe.time;
    });

    /**
     * Progress as percentage (0-100)
     */
    const progress = computed(() => {
        if (totalDuration.value === 0) return 0;
        return (currentTime.value / totalDuration.value) * 100;
    });

    /**
     * Add a keyframe at the specified time
     */
    const addKeyframe = (time, elements, events = []) => {
        const keyframe = {
            time,
            elements: JSON.parse(JSON.stringify(elements)),
            events,
        };

        // Insert in sorted order
        const index = keyframes.value.findIndex(k => k.time > time);
        if (index === -1) {
            keyframes.value.push(keyframe);
        } else {
            keyframes.value.splice(index, 0, keyframe);
        }

        return keyframe;
    };

    /**
     * Remove a keyframe at the specified index
     */
    const removeKeyframe = (index) => {
        if (index >= 0 && index < keyframes.value.length) {
            keyframes.value.splice(index, 1);
        }
    };

    /**
     * Update a keyframe
     */
    const updateKeyframe = (index, updates) => {
        if (index >= 0 && index < keyframes.value.length) {
            keyframes.value[index] = {
                ...keyframes.value[index],
                ...updates,
            };
        }
    };

    /**
     * Get the two keyframes surrounding a given time
     */
    const getSurroundingKeyframes = (time) => {
        if (keyframes.value.length === 0) return { before: null, after: null };
        if (keyframes.value.length === 1) {
            return { before: keyframes.value[0], after: keyframes.value[0] };
        }

        let before = null;
        let after = null;

        for (let i = 0; i < keyframes.value.length; i++) {
            if (keyframes.value[i].time <= time) {
                before = keyframes.value[i];
            }
            if (keyframes.value[i].time >= time && after === null) {
                after = keyframes.value[i];
            }
        }

        return { before, after };
    };

    /**
     * Linear interpolation between two values
     */
    const lerp = (a, b, t) => {
        return a + (b - a) * t;
    };

    /**
     * Interpolate element positions at a given time
     */
    const interpolatePositions = (time) => {
        const { before, after } = getSurroundingKeyframes(time);

        if (!before || !after) return {};

        if (before === after) {
            return JSON.parse(JSON.stringify(before.elements));
        }

        const timeDiff = after.time - before.time;
        const t = timeDiff === 0 ? 0 : (time - before.time) / timeDiff;

        const interpolated = {};

        // Interpolate each element
        Object.keys(before.elements).forEach(elementId => {
            const beforeEl = before.elements[elementId];
            const afterEl = after.elements[elementId];

            if (!afterEl) {
                interpolated[elementId] = { ...beforeEl };
                return;
            }

            interpolated[elementId] = {
                x: lerp(beforeEl.x, afterEl.x, t),
                y: lerp(beforeEl.y, afterEl.y, t),
            };

            // Include other properties from before state
            if (beforeEl.rotation !== undefined) {
                interpolated[elementId].rotation = lerp(
                    beforeEl.rotation,
                    afterEl.rotation || beforeEl.rotation,
                    t
                );
            }
        });

        return interpolated;
    };

    /**
     * Animation loop
     */
    const animate = (timestamp) => {
        if (!isPlaying.value || isPaused.value) return;

        if (startTimestamp === null) {
            startTimestamp = timestamp;
        }

        const elapsed = timestamp - startTimestamp + pausedTime;
        currentTime.value = Math.min(elapsed, totalDuration.value);

        // Update interpolated positions
        currentPositions.value = interpolatePositions(currentTime.value);

        // Check if animation is complete
        if (currentTime.value >= totalDuration.value) {
            stop();
            return;
        }

        animationFrameId = requestAnimationFrame(animate);
    };

    /**
     * Play the animation
     */
    const play = () => {
        if (keyframes.value.length === 0) return;

        if (isPaused.value) {
            // Resume from paused position
            isPaused.value = false;
            startTimestamp = null;
            pausedTime = currentTime.value;
        } else {
            // Start from beginning
            currentTime.value = 0;
            pausedTime = 0;
            startTimestamp = null;
        }

        isPlaying.value = true;
        animationFrameId = requestAnimationFrame(animate);
    };

    /**
     * Pause the animation
     */
    const pause = () => {
        isPaused.value = true;
        pausedTime = currentTime.value;

        if (animationFrameId) {
            cancelAnimationFrame(animationFrameId);
            animationFrameId = null;
        }
    };

    /**
     * Stop the animation
     */
    const stop = () => {
        isPlaying.value = false;
        isPaused.value = false;
        currentTime.value = 0;
        pausedTime = 0;
        startTimestamp = null;

        if (animationFrameId) {
            cancelAnimationFrame(animationFrameId);
            animationFrameId = null;
        }
    };

    /**
     * Seek to a specific time
     */
    const seekTo = (time) => {
        currentTime.value = Math.max(0, Math.min(time, totalDuration.value));
        pausedTime = currentTime.value;
        currentPositions.value = interpolatePositions(currentTime.value);

        if (isPlaying.value && !isPaused.value) {
            startTimestamp = null;
        }
    };

    /**
     * Seek to a specific progress percentage
     */
    const seekToProgress = (percent) => {
        const time = (percent / 100) * totalDuration.value;
        seekTo(time);
    };

    /**
     * Export animation data
     */
    const exportAnimationData = () => {
        return {
            version: '1.0',
            duration: totalDuration.value,
            keyframes: JSON.parse(JSON.stringify(keyframes.value)),
        };
    };

    /**
     * Import animation data
     */
    const importAnimationData = (data) => {
        if (!data) return;

        if (data.keyframes) {
            keyframes.value = data.keyframes;
        }
        if (data.duration) {
            duration.value = data.duration;
        }

        stop();
    };

    /**
     * Create initial keyframe from current elements
     */
    const createInitialKeyframe = (elements) => {
        const elementPositions = {};

        // Extract positions from all elements
        if (elements.players) {
            elements.players.forEach(player => {
                elementPositions[player.id] = { x: player.x, y: player.y };
            });
        }
        if (elements.shapes) {
            elements.shapes.forEach(shape => {
                elementPositions[shape.id] = {
                    x: shape.x,
                    y: shape.y,
                    rotation: shape.rotation || 0,
                };
            });
        }

        addKeyframe(0, elementPositions);
    };

    /**
     * Clear all keyframes
     */
    const clearKeyframes = () => {
        keyframes.value = [];
        stop();
    };

    // Cleanup on unmount
    onUnmounted(() => {
        if (animationFrameId) {
            cancelAnimationFrame(animationFrameId);
        }
    });

    return {
        // State
        isPlaying,
        isPaused,
        currentTime,
        duration,
        keyframes,
        totalDuration,
        progress,
        currentPositions,

        // Methods
        play,
        pause,
        stop,
        seekTo,
        seekToProgress,
        addKeyframe,
        removeKeyframe,
        updateKeyframe,
        createInitialKeyframe,
        clearKeyframes,
        interpolatePositions,
        exportAnimationData,
        importAnimationData,
    };
}
