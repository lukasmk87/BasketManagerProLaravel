import { ref, computed } from 'vue';

/**
 * Composable for managing zoom and pan in the Tactic Board
 */
export function useTacticZoom() {
    // Zoom state
    const scale = ref(1);
    const positionX = ref(0);
    const positionY = ref(0);

    // Zoom limits
    const minScale = 0.5;
    const maxScale = 3.0;
    const zoomStep = 0.1;

    // Pan state
    const isPanning = ref(false);
    const lastPanPosition = ref({ x: 0, y: 0 });

    /**
     * Get current zoom percentage
     */
    const zoomPercent = computed(() => {
        return Math.round(scale.value * 100);
    });

    /**
     * Zoom in by one step
     */
    const zoomIn = () => {
        const newScale = Math.min(maxScale, scale.value + zoomStep);
        scale.value = Math.round(newScale * 10) / 10;
    };

    /**
     * Zoom out by one step
     */
    const zoomOut = () => {
        const newScale = Math.max(minScale, scale.value - zoomStep);
        scale.value = Math.round(newScale * 10) / 10;
    };

    /**
     * Reset zoom to 100%
     */
    const resetZoom = () => {
        scale.value = 1;
        positionX.value = 0;
        positionY.value = 0;
    };

    /**
     * Set zoom to specific value
     */
    const setZoom = (value) => {
        scale.value = Math.max(minScale, Math.min(maxScale, value));
    };

    /**
     * Handle mouse wheel zoom
     * Zooms toward pointer position
     */
    const handleWheel = (e, stageWidth, stageHeight) => {
        e.evt.preventDefault();

        const stage = e.target.getStage();
        const pointer = stage.getPointerPosition();

        if (!pointer) return;

        const oldScale = scale.value;
        const direction = e.evt.deltaY > 0 ? -1 : 1;
        const newScale = Math.max(
            minScale,
            Math.min(maxScale, oldScale + direction * zoomStep)
        );

        // Calculate new position to zoom toward pointer
        const mousePointTo = {
            x: (pointer.x - positionX.value) / oldScale,
            y: (pointer.y - positionY.value) / oldScale,
        };

        scale.value = Math.round(newScale * 10) / 10;

        positionX.value = pointer.x - mousePointTo.x * newScale;
        positionY.value = pointer.y - mousePointTo.y * newScale;
    };

    /**
     * Handle pinch zoom for touch devices
     */
    const handlePinch = (e) => {
        if (e.evt.touches.length !== 2) return;

        e.evt.preventDefault();

        const touch1 = e.evt.touches[0];
        const touch2 = e.evt.touches[1];

        // Calculate distance between touches
        const distance = Math.sqrt(
            Math.pow(touch2.clientX - touch1.clientX, 2) +
            Math.pow(touch2.clientY - touch1.clientY, 2)
        );

        // Store initial distance on first pinch
        if (!e.target._pinchDistance) {
            e.target._pinchDistance = distance;
            e.target._pinchScale = scale.value;
            return;
        }

        // Calculate new scale
        const pinchScale = distance / e.target._pinchDistance;
        const newScale = Math.max(
            minScale,
            Math.min(maxScale, e.target._pinchScale * pinchScale)
        );

        scale.value = Math.round(newScale * 10) / 10;
    };

    /**
     * Handle pinch end
     */
    const handlePinchEnd = (e) => {
        if (e.target._pinchDistance) {
            delete e.target._pinchDistance;
            delete e.target._pinchScale;
        }
    };

    /**
     * Start panning
     */
    const startPan = (x, y) => {
        isPanning.value = true;
        lastPanPosition.value = { x, y };
    };

    /**
     * Continue panning
     */
    const continuePan = (x, y) => {
        if (!isPanning.value) return;

        const dx = x - lastPanPosition.value.x;
        const dy = y - lastPanPosition.value.y;

        positionX.value += dx;
        positionY.value += dy;

        lastPanPosition.value = { x, y };
    };

    /**
     * End panning
     */
    const endPan = () => {
        isPanning.value = false;
    };

    /**
     * Get stage config with current zoom/pan values
     */
    const getStageConfig = (baseWidth, baseHeight) => {
        return {
            width: baseWidth,
            height: baseHeight,
            scaleX: scale.value,
            scaleY: scale.value,
            x: positionX.value,
            y: positionY.value,
        };
    };

    /**
     * Transform screen coordinates to canvas coordinates
     */
    const screenToCanvas = (x, y) => {
        return {
            x: (x - positionX.value) / scale.value,
            y: (y - positionY.value) / scale.value,
        };
    };

    /**
     * Transform canvas coordinates to screen coordinates
     */
    const canvasToScreen = (x, y) => {
        return {
            x: x * scale.value + positionX.value,
            y: y * scale.value + positionY.value,
        };
    };

    /**
     * Fit content to view
     */
    const fitToView = (contentWidth, contentHeight, containerWidth, containerHeight, padding = 20) => {
        const scaleX = (containerWidth - padding * 2) / contentWidth;
        const scaleY = (containerHeight - padding * 2) / contentHeight;
        const fitScale = Math.min(scaleX, scaleY, 1);

        scale.value = Math.round(fitScale * 10) / 10;
        positionX.value = (containerWidth - contentWidth * fitScale) / 2;
        positionY.value = (containerHeight - contentHeight * fitScale) / 2;
    };

    return {
        // State
        scale,
        positionX,
        positionY,
        isPanning,
        zoomPercent,

        // Constants
        minScale,
        maxScale,

        // Methods
        zoomIn,
        zoomOut,
        resetZoom,
        setZoom,
        handleWheel,
        handlePinch,
        handlePinchEnd,
        startPan,
        continuePan,
        endPan,
        getStageConfig,
        screenToCanvas,
        canvasToScreen,
        fitToView,
    };
}
