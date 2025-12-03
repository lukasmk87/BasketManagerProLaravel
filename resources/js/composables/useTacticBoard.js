import { ref, computed, reactive, watch } from 'vue';

/**
 * Main composable for managing the Tactic Board editor state
 */
export function useTacticBoard(initialData = null) {
    // Current tool selection
    const currentTool = ref('select'); // 'select', 'player', 'movement', 'pass', 'dribble', 'screen', 'text'

    // Court type
    const courtType = ref(initialData?.court?.type || 'half_horizontal');

    // Court dimensions (will be set based on court type and container)
    const courtWidth = ref(700);
    const courtHeight = ref(500);

    // Court background color
    const courtColor = ref(initialData?.court?.backgroundColor || '#1a5f2a');

    // Elements on the board
    const players = ref(initialData?.elements?.players || []);
    const paths = ref(initialData?.elements?.paths || []);
    const shapes = ref(initialData?.elements?.shapes || []);
    const annotations = ref(initialData?.elements?.annotations || []);

    // Extended element types (Phase 8)
    const freehandPaths = ref(initialData?.elements?.freehandPaths || []);
    const circles = ref(initialData?.elements?.circles || []);
    const rectangles = ref(initialData?.elements?.rectangles || []);
    const arrows = ref(initialData?.elements?.arrows || []);

    // Ball element (Phase 10)
    const ball = ref(initialData?.elements?.ball || null);

    // Grid settings (Phase 9)
    const gridEnabled = ref(false);
    const gridSize = ref(20);

    // Team colors (Phase 10)
    const teamColors = ref({
        offense: initialData?.teamColors?.offense || '#2563eb',
        defense: initialData?.teamColors?.defense || '#dc2626',
    });

    // Selected element
    const selectedElementId = ref(null);
    const selectedElementType = ref(null);

    // Drawing state (for paths)
    const isDrawing = ref(false);
    const currentDrawingPoints = ref([]);

    // Counter for generating unique IDs
    const idCounter = ref(1);

    /**
     * Generate a unique ID for elements
     */
    const generateId = (prefix = 'el') => {
        return `${prefix}_${Date.now()}_${idCounter.value++}`;
    };

    /**
     * Get the currently selected element
     */
    const selectedElement = computed(() => {
        if (!selectedElementId.value) return null;

        switch (selectedElementType.value) {
            case 'player':
                return players.value.find(p => p.id === selectedElementId.value);
            case 'path':
                return paths.value.find(p => p.id === selectedElementId.value);
            case 'shape':
                return shapes.value.find(s => s.id === selectedElementId.value);
            case 'annotation':
                return annotations.value.find(a => a.id === selectedElementId.value);
            case 'freehand':
                return freehandPaths.value.find(f => f.id === selectedElementId.value);
            case 'circle':
                return circles.value.find(c => c.id === selectedElementId.value);
            case 'rectangle':
                return rectangles.value.find(r => r.id === selectedElementId.value);
            case 'arrow':
                return arrows.value.find(a => a.id === selectedElementId.value);
            case 'ball':
                return ball.value;
            default:
                return null;
        }
    });

    /**
     * Clear selection
     */
    const clearSelection = () => {
        selectedElementId.value = null;
        selectedElementType.value = null;
    };

    /**
     * Select an element
     */
    const selectElement = (id, type) => {
        selectedElementId.value = id;
        selectedElementType.value = type;
    };

    /**
     * Add a new player token
     */
    const addPlayer = (options = {}) => {
        const newPlayer = {
            id: generateId('player'),
            x: options.x || courtWidth.value / 2,
            y: options.y || courtHeight.value / 2,
            number: options.number || String(players.value.length + 1),
            label: options.label || '',
            team: options.team || 'offense',
            hasBall: options.hasBall || false,
        };
        players.value.push(newPlayer);
        selectElement(newPlayer.id, 'player');
        return newPlayer;
    };

    /**
     * Update player position
     */
    const updatePlayerPosition = (id, x, y) => {
        const player = players.value.find(p => p.id === id);
        if (player) {
            player.x = x;
            player.y = y;
        }
    };

    /**
     * Update player properties
     */
    const updatePlayer = (id, updates) => {
        const index = players.value.findIndex(p => p.id === id);
        if (index !== -1) {
            players.value[index] = { ...players.value[index], ...updates };
        }
    };

    /**
     * Start drawing a path
     */
    const startDrawing = (x, y) => {
        isDrawing.value = true;
        currentDrawingPoints.value = [{ x, y }];
    };

    /**
     * Continue drawing a path
     */
    const continueDrawing = (x, y) => {
        if (!isDrawing.value) return;
        currentDrawingPoints.value.push({ x, y });
    };

    /**
     * Finish drawing a path
     */
    const finishDrawing = () => {
        if (!isDrawing.value || currentDrawingPoints.value.length < 2) {
            isDrawing.value = false;
            currentDrawingPoints.value = [];
            return null;
        }

        const pathType = currentTool.value; // 'movement', 'pass', or 'dribble'
        const newPath = {
            id: generateId('path'),
            type: pathType,
            points: [...currentDrawingPoints.value],
            color: getPathColor(pathType),
        };

        paths.value.push(newPath);

        isDrawing.value = false;
        currentDrawingPoints.value = [];

        selectElement(newPath.id, 'path');
        return newPath;
    };

    /**
     * Get default color for path type
     */
    const getPathColor = (type) => {
        switch (type) {
            case 'movement': return '#ffffff';
            case 'pass': return '#22c55e';
            case 'dribble': return '#f59e0b';
            case 'freehand': return '#ffffff';
            default: return '#ffffff';
        }
    };

    // ============================================
    // Freehand Drawing Methods (Phase 8.1)
    // ============================================

    /**
     * Start freehand drawing
     */
    const startFreehandDrawing = (x, y, options = {}) => {
        isDrawing.value = true;
        currentDrawingPoints.value = [{ x, y }];
    };

    /**
     * Continue freehand drawing
     */
    const continueFreehandDrawing = (x, y) => {
        if (!isDrawing.value) return;
        currentDrawingPoints.value.push({ x, y });
    };

    /**
     * Finish freehand drawing and create path
     */
    const finishFreehandDrawing = (options = {}) => {
        if (!isDrawing.value || currentDrawingPoints.value.length < 2) {
            isDrawing.value = false;
            currentDrawingPoints.value = [];
            return null;
        }

        // Simplify points for better performance
        const simplifiedPoints = simplifyPoints(currentDrawingPoints.value, 2);

        const newFreehand = {
            id: generateId('freehand'),
            type: 'freehand',
            points: simplifiedPoints,
            color: options.color || '#ffffff',
            strokeWidth: options.strokeWidth || 3,
            lineStyle: options.lineStyle || 'solid', // 'solid', 'dashed', 'dotted'
        };

        freehandPaths.value.push(newFreehand);

        isDrawing.value = false;
        currentDrawingPoints.value = [];

        selectElement(newFreehand.id, 'freehand');
        return newFreehand;
    };

    /**
     * Simplify points using Douglas-Peucker algorithm variant
     */
    const simplifyPoints = (points, tolerance = 2) => {
        if (points.length < 3) return points;

        const result = [points[0]];

        for (let i = 1; i < points.length - 1; i++) {
            const prev = result[result.length - 1];
            const curr = points[i];

            const distance = Math.sqrt(
                Math.pow(curr.x - prev.x, 2) + Math.pow(curr.y - prev.y, 2)
            );

            if (distance >= tolerance) {
                result.push(curr);
            }
        }

        result.push(points[points.length - 1]);
        return result;
    };

    /**
     * Update freehand path points
     */
    const updateFreehandPoints = (id, points) => {
        const freehand = freehandPaths.value.find(f => f.id === id);
        if (freehand) {
            freehand.points = points;
        }
    };

    /**
     * Update freehand path properties
     */
    const updateFreehand = (id, updates) => {
        const index = freehandPaths.value.findIndex(f => f.id === id);
        if (index !== -1) {
            freehandPaths.value[index] = { ...freehandPaths.value[index], ...updates };
        }
    };

    // ============================================
    // Shape Methods (Phase 8.2)
    // ============================================

    /**
     * Add a circle shape
     */
    const addCircle = (options = {}) => {
        const newCircle = {
            id: generateId('circle'),
            type: 'circle',
            x: options.x || courtWidth.value / 2,
            y: options.y || courtHeight.value / 2,
            radiusX: options.radiusX || 30,
            radiusY: options.radiusY || 30,
            fill: options.fill || 'transparent',
            stroke: options.stroke || '#ffffff',
            strokeWidth: options.strokeWidth || 2,
            opacity: options.opacity || 0.7,
        };
        circles.value.push(newCircle);
        selectElement(newCircle.id, 'circle');
        return newCircle;
    };

    /**
     * Update circle position
     */
    const updateCirclePosition = (id, x, y) => {
        const circle = circles.value.find(c => c.id === id);
        if (circle) {
            circle.x = x;
            circle.y = y;
        }
    };

    /**
     * Update circle size
     */
    const updateCircleSize = (id, radiusX, radiusY) => {
        const circle = circles.value.find(c => c.id === id);
        if (circle) {
            circle.radiusX = radiusX;
            circle.radiusY = radiusY;
        }
    };

    /**
     * Add a rectangle shape
     */
    const addRectangle = (options = {}) => {
        const newRect = {
            id: generateId('rect'),
            type: 'rectangle',
            x: options.x || courtWidth.value / 2,
            y: options.y || courtHeight.value / 2,
            width: options.width || 60,
            height: options.height || 40,
            rotation: options.rotation || 0,
            fill: options.fill || 'rgba(255, 255, 255, 0.2)',
            stroke: options.stroke || '#ffffff',
            strokeWidth: options.strokeWidth || 2,
        };
        rectangles.value.push(newRect);
        selectElement(newRect.id, 'rectangle');
        return newRect;
    };

    /**
     * Update rectangle position
     */
    const updateRectanglePosition = (id, x, y) => {
        const rect = rectangles.value.find(r => r.id === id);
        if (rect) {
            rect.x = x;
            rect.y = y;
        }
    };

    /**
     * Update rectangle size
     */
    const updateRectangleSize = (id, width, height) => {
        const rect = rectangles.value.find(r => r.id === id);
        if (rect) {
            rect.width = width;
            rect.height = height;
        }
    };

    /**
     * Update rectangle rotation
     */
    const updateRectangleRotation = (id, rotation) => {
        const rect = rectangles.value.find(r => r.id === id);
        if (rect) {
            rect.rotation = rotation;
        }
    };

    /**
     * Add an arrow shape
     */
    const addArrow = (options = {}) => {
        const newArrow = {
            id: generateId('arrow'),
            type: 'arrow',
            points: options.points || [
                { x: options.x || courtWidth.value / 2 - 30, y: options.y || courtHeight.value / 2 },
                { x: (options.x || courtWidth.value / 2) + 30, y: options.y || courtHeight.value / 2 },
            ],
            color: options.color || '#ffffff',
            strokeWidth: options.strokeWidth || 3,
            pointerLength: options.pointerLength || 10,
            pointerWidth: options.pointerWidth || 10,
        };
        arrows.value.push(newArrow);
        selectElement(newArrow.id, 'arrow');
        return newArrow;
    };

    /**
     * Update arrow points
     */
    const updateArrowPoints = (id, points) => {
        const arrow = arrows.value.find(a => a.id === id);
        if (arrow) {
            arrow.points = points;
        }
    };

    // ============================================
    // Ball Methods (Phase 10.2)
    // ============================================

    /**
     * Add ball to the court
     */
    const addBall = (options = {}) => {
        ball.value = {
            id: generateId('ball'),
            type: 'ball',
            x: options.x || courtWidth.value / 2,
            y: options.y || courtHeight.value / 2,
            radius: options.radius || 12,
        };
        selectElement(ball.value.id, 'ball');
        return ball.value;
    };

    /**
     * Update ball position
     */
    const updateBallPosition = (x, y) => {
        if (ball.value) {
            ball.value.x = x;
            ball.value.y = y;
        }
    };

    /**
     * Remove ball from court
     */
    const removeBall = () => {
        if (selectedElementId.value === ball.value?.id) {
            clearSelection();
        }
        ball.value = null;
    };

    // ============================================
    // Grid Methods (Phase 9.2)
    // ============================================

    /**
     * Snap value to grid
     */
    const snapToGrid = (value) => {
        if (!gridEnabled.value) return value;
        return Math.round(value / gridSize.value) * gridSize.value;
    };

    /**
     * Toggle grid
     */
    const toggleGrid = () => {
        gridEnabled.value = !gridEnabled.value;
    };

    /**
     * Set grid size
     */
    const setGridSize = (size) => {
        gridSize.value = size;
    };

    /**
     * Update path points
     */
    const updatePathPoints = (id, points) => {
        const path = paths.value.find(p => p.id === id);
        if (path) {
            path.points = points;
        }
    };

    /**
     * Add a screen shape
     */
    const addScreen = (options = {}) => {
        const newScreen = {
            id: generateId('screen'),
            type: 'screen',
            x: options.x || courtWidth.value / 2,
            y: options.y || courtHeight.value / 2,
            rotation: options.rotation || 0,
            width: options.width || 40,
            color: options.color || '#ffffff',
        };
        shapes.value.push(newScreen);
        selectElement(newScreen.id, 'shape');
        return newScreen;
    };

    /**
     * Update shape position
     */
    const updateShapePosition = (id, x, y) => {
        const shape = shapes.value.find(s => s.id === id);
        if (shape) {
            shape.x = x;
            shape.y = y;
        }
    };

    /**
     * Update shape rotation
     */
    const updateShapeRotation = (id, rotation) => {
        const shape = shapes.value.find(s => s.id === id);
        if (shape) {
            shape.rotation = rotation;
        }
    };

    /**
     * Add a text annotation
     */
    const addAnnotation = (options = {}) => {
        const newAnnotation = {
            id: generateId('text'),
            type: 'text',
            x: options.x || courtWidth.value / 2,
            y: options.y || courtHeight.value / 2,
            content: options.content || 'Text',
            fontSize: options.fontSize || 16,
            color: options.color || '#ffffff',
        };
        annotations.value.push(newAnnotation);
        selectElement(newAnnotation.id, 'annotation');
        return newAnnotation;
    };

    /**
     * Update annotation position
     */
    const updateAnnotationPosition = (id, x, y) => {
        const annotation = annotations.value.find(a => a.id === id);
        if (annotation) {
            annotation.x = x;
            annotation.y = y;
        }
    };

    /**
     * Update annotation content
     */
    const updateAnnotationContent = (id, content) => {
        const annotation = annotations.value.find(a => a.id === id);
        if (annotation) {
            annotation.content = content;
        }
    };

    /**
     * Delete selected element
     */
    const deleteSelected = () => {
        if (!selectedElementId.value) return;

        switch (selectedElementType.value) {
            case 'player':
                players.value = players.value.filter(p => p.id !== selectedElementId.value);
                break;
            case 'path':
                paths.value = paths.value.filter(p => p.id !== selectedElementId.value);
                break;
            case 'shape':
                shapes.value = shapes.value.filter(s => s.id !== selectedElementId.value);
                break;
            case 'annotation':
                annotations.value = annotations.value.filter(a => a.id !== selectedElementId.value);
                break;
            case 'freehand':
                freehandPaths.value = freehandPaths.value.filter(f => f.id !== selectedElementId.value);
                break;
            case 'circle':
                circles.value = circles.value.filter(c => c.id !== selectedElementId.value);
                break;
            case 'rectangle':
                rectangles.value = rectangles.value.filter(r => r.id !== selectedElementId.value);
                break;
            case 'arrow':
                arrows.value = arrows.value.filter(a => a.id !== selectedElementId.value);
                break;
            case 'ball':
                ball.value = null;
                break;
        }

        clearSelection();
    };

    /**
     * Delete element by ID
     */
    const deleteElement = (id, type) => {
        switch (type) {
            case 'player':
                players.value = players.value.filter(p => p.id !== id);
                break;
            case 'path':
                paths.value = paths.value.filter(p => p.id !== id);
                break;
            case 'shape':
                shapes.value = shapes.value.filter(s => s.id !== id);
                break;
            case 'annotation':
                annotations.value = annotations.value.filter(a => a.id !== id);
                break;
            case 'freehand':
                freehandPaths.value = freehandPaths.value.filter(f => f.id !== id);
                break;
            case 'circle':
                circles.value = circles.value.filter(c => c.id !== id);
                break;
            case 'rectangle':
                rectangles.value = rectangles.value.filter(r => r.id !== id);
                break;
            case 'arrow':
                arrows.value = arrows.value.filter(a => a.id !== id);
                break;
            case 'ball':
                ball.value = null;
                break;
        }

        if (selectedElementId.value === id) {
            clearSelection();
        }
    };

    /**
     * Clear all elements
     */
    const clearAll = () => {
        players.value = [];
        paths.value = [];
        shapes.value = [];
        annotations.value = [];
        freehandPaths.value = [];
        circles.value = [];
        rectangles.value = [];
        arrows.value = [];
        ball.value = null;
        clearSelection();
    };

    /**
     * Export current state as JSON
     */
    const exportData = () => {
        return {
            version: '1.1', // Updated version for new features
            court: {
                type: courtType.value,
                backgroundColor: courtColor.value,
            },
            elements: {
                players: players.value.map(p => ({ ...p })),
                paths: paths.value.map(p => ({ ...p })),
                shapes: shapes.value.map(s => ({ ...s })),
                annotations: annotations.value.map(a => ({ ...a })),
                freehandPaths: freehandPaths.value.map(f => ({ ...f })),
                circles: circles.value.map(c => ({ ...c })),
                rectangles: rectangles.value.map(r => ({ ...r })),
                arrows: arrows.value.map(a => ({ ...a })),
                ball: ball.value ? { ...ball.value } : null,
            },
            teamColors: { ...teamColors.value },
            grid: {
                enabled: gridEnabled.value,
                size: gridSize.value,
            },
        };
    };

    /**
     * Import data from JSON
     */
    const importData = (data) => {
        if (!data) return;

        if (data.court) {
            courtType.value = data.court.type || 'half_horizontal';
            courtColor.value = data.court.backgroundColor || '#1a5f2a';
        }

        if (data.elements) {
            players.value = data.elements.players || [];
            paths.value = data.elements.paths || [];
            shapes.value = data.elements.shapes || [];
            annotations.value = data.elements.annotations || [];
            freehandPaths.value = data.elements.freehandPaths || [];
            circles.value = data.elements.circles || [];
            rectangles.value = data.elements.rectangles || [];
            arrows.value = data.elements.arrows || [];
            ball.value = data.elements.ball || null;
        }

        if (data.teamColors) {
            teamColors.value = { ...teamColors.value, ...data.teamColors };
        }

        if (data.grid) {
            gridEnabled.value = data.grid.enabled || false;
            gridSize.value = data.grid.size || 20;
        }

        clearSelection();
    };

    /**
     * Set court dimensions based on type
     */
    const setCourtDimensions = (width, height) => {
        courtWidth.value = width;
        courtHeight.value = height;
    };

    /**
     * Add default players (5 offense, 5 defense)
     */
    const addDefaultPlayers = () => {
        // Clear existing players
        players.value = [];

        // Default positions for half court (offense)
        const offensePositions = [
            { x: courtWidth.value * 0.5, y: courtHeight.value * 0.3, label: 'PG' },
            { x: courtWidth.value * 0.25, y: courtHeight.value * 0.4, label: 'SG' },
            { x: courtWidth.value * 0.75, y: courtHeight.value * 0.4, label: 'SF' },
            { x: courtWidth.value * 0.3, y: courtHeight.value * 0.6, label: 'PF' },
            { x: courtWidth.value * 0.7, y: courtHeight.value * 0.6, label: 'C' },
        ];

        // Add offense players
        offensePositions.forEach((pos, index) => {
            players.value.push({
                id: generateId('player'),
                x: pos.x,
                y: pos.y,
                number: String(index + 1),
                label: pos.label,
                team: 'offense',
                hasBall: index === 0, // PG has ball
            });
        });

        // Default positions for defense
        const defensePositions = [
            { x: courtWidth.value * 0.5, y: courtHeight.value * 0.35 },
            { x: courtWidth.value * 0.25, y: courtHeight.value * 0.45 },
            { x: courtWidth.value * 0.75, y: courtHeight.value * 0.45 },
            { x: courtWidth.value * 0.3, y: courtHeight.value * 0.65 },
            { x: courtWidth.value * 0.7, y: courtHeight.value * 0.65 },
        ];

        // Add defense players
        defensePositions.forEach((pos, index) => {
            players.value.push({
                id: generateId('player'),
                x: pos.x,
                y: pos.y,
                number: '',
                label: 'X',
                team: 'defense',
                hasBall: false,
            });
        });
    };

    // Initialize with provided data
    if (initialData) {
        importData(initialData);
    }

    return {
        // State
        currentTool,
        courtType,
        courtWidth,
        courtHeight,
        courtColor,
        players,
        paths,
        shapes,
        annotations,
        selectedElementId,
        selectedElementType,
        selectedElement,
        isDrawing,
        currentDrawingPoints,

        // Extended element state (Phase 8)
        freehandPaths,
        circles,
        rectangles,
        arrows,

        // Ball state (Phase 10)
        ball,

        // Grid state (Phase 9)
        gridEnabled,
        gridSize,

        // Team colors (Phase 10)
        teamColors,

        // Methods
        generateId,
        clearSelection,
        selectElement,
        addPlayer,
        updatePlayerPosition,
        updatePlayer,
        startDrawing,
        continueDrawing,
        finishDrawing,
        updatePathPoints,
        addScreen,
        updateShapePosition,
        updateShapeRotation,
        addAnnotation,
        updateAnnotationPosition,
        updateAnnotationContent,
        deleteSelected,
        deleteElement,
        clearAll,
        exportData,
        importData,
        setCourtDimensions,
        addDefaultPlayers,

        // Freehand methods (Phase 8.1)
        startFreehandDrawing,
        continueFreehandDrawing,
        finishFreehandDrawing,
        updateFreehandPoints,
        updateFreehand,

        // Shape methods (Phase 8.2)
        addCircle,
        updateCirclePosition,
        updateCircleSize,
        addRectangle,
        updateRectanglePosition,
        updateRectangleSize,
        updateRectangleRotation,
        addArrow,
        updateArrowPoints,

        // Ball methods (Phase 10.2)
        addBall,
        updateBallPosition,
        removeBall,

        // Grid methods (Phase 9.2)
        snapToGrid,
        toggleGrid,
        setGridSize,
    };
}
