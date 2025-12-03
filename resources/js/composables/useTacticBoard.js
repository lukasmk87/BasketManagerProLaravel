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
            default: return '#ffffff';
        }
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
        clearSelection();
    };

    /**
     * Export current state as JSON
     */
    const exportData = () => {
        return {
            version: '1.0',
            court: {
                type: courtType.value,
                backgroundColor: courtColor.value,
            },
            elements: {
                players: players.value.map(p => ({ ...p })),
                paths: paths.value.map(p => ({ ...p })),
                shapes: shapes.value.map(s => ({ ...s })),
                annotations: annotations.value.map(a => ({ ...a })),
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
    };
}
