import { ref, computed, watch } from 'vue';

/**
 * Composable for managing undo/redo history in the Tactic Board
 */
export function useTacticHistory(maxHistorySize = 50) {
    // History stacks
    const undoStack = ref([]);
    const redoStack = ref([]);

    // Current state snapshot
    const currentState = ref(null);

    // Flag to prevent recording during undo/redo
    const isRestoring = ref(false);

    /**
     * Check if undo is available
     */
    const canUndo = computed(() => undoStack.value.length > 0);

    /**
     * Check if redo is available
     */
    const canRedo = computed(() => redoStack.value.length > 0);

    /**
     * Deep clone an object
     */
    const deepClone = (obj) => {
        return JSON.parse(JSON.stringify(obj));
    };

    /**
     * Record a state change
     */
    const recordState = (state) => {
        if (isRestoring.value) return;

        // Push current state to undo stack before updating
        if (currentState.value !== null) {
            undoStack.value.push(deepClone(currentState.value));

            // Limit history size
            if (undoStack.value.length > maxHistorySize) {
                undoStack.value.shift();
            }
        }

        // Update current state
        currentState.value = deepClone(state);

        // Clear redo stack on new action
        redoStack.value = [];
    };

    /**
     * Undo the last action
     * Returns the previous state to restore
     */
    const undo = () => {
        if (!canUndo.value) return null;

        isRestoring.value = true;

        // Push current state to redo stack
        if (currentState.value !== null) {
            redoStack.value.push(deepClone(currentState.value));
        }

        // Pop from undo stack
        const previousState = undoStack.value.pop();
        currentState.value = deepClone(previousState);

        isRestoring.value = false;

        return previousState;
    };

    /**
     * Redo the last undone action
     * Returns the state to restore
     */
    const redo = () => {
        if (!canRedo.value) return null;

        isRestoring.value = true;

        // Push current state to undo stack
        if (currentState.value !== null) {
            undoStack.value.push(deepClone(currentState.value));
        }

        // Pop from redo stack
        const nextState = redoStack.value.pop();
        currentState.value = deepClone(nextState);

        isRestoring.value = false;

        return nextState;
    };

    /**
     * Clear all history
     */
    const clearHistory = () => {
        undoStack.value = [];
        redoStack.value = [];
        currentState.value = null;
    };

    /**
     * Initialize with a state (doesn't add to undo stack)
     */
    const initializeState = (state) => {
        currentState.value = deepClone(state);
        undoStack.value = [];
        redoStack.value = [];
    };

    /**
     * Get history info for debugging/display
     */
    const historyInfo = computed(() => ({
        undoCount: undoStack.value.length,
        redoCount: redoStack.value.length,
        canUndo: canUndo.value,
        canRedo: canRedo.value,
    }));

    return {
        canUndo,
        canRedo,
        recordState,
        undo,
        redo,
        clearHistory,
        initializeState,
        historyInfo,
        isRestoring,
    };
}
