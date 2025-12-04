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

    // Z-Index counter for layer ordering (Phase 12.2)
    const zIndexCounter = ref(0);

    // Multi-selection state (Phase 12.3)
    const selectedIds = ref(new Set());
    const selectedTypes = ref(new Map()); // id -> type

    // Eraser state (Phase 8.3)
    const isErasing = ref(false);
    const erasedElementIds = ref(new Set());

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
        selectedIds.value = new Set();
        selectedTypes.value = new Map();
    };

    /**
     * Select an element (supports multi-selection with additive flag)
     */
    const selectElement = (id, type, additive = false) => {
        if (!additive) {
            selectedIds.value = new Set();
            selectedTypes.value = new Map();
        }
        selectedIds.value.add(id);
        selectedTypes.value.set(id, type);
        // Keep backward compatibility
        selectedElementId.value = id;
        selectedElementType.value = type;
    };

    /**
     * Toggle element in selection (Phase 12.3)
     */
    const toggleSelection = (id, type) => {
        if (selectedIds.value.has(id)) {
            selectedIds.value.delete(id);
            selectedTypes.value.delete(id);
            // Update single selection refs
            if (selectedIds.value.size === 0) {
                selectedElementId.value = null;
                selectedElementType.value = null;
            } else {
                const lastId = [...selectedIds.value].pop();
                selectedElementId.value = lastId;
                selectedElementType.value = selectedTypes.value.get(lastId);
            }
        } else {
            selectedIds.value.add(id);
            selectedTypes.value.set(id, type);
            selectedElementId.value = id;
            selectedElementType.value = type;
        }
    };

    /**
     * Check if element is selected (Phase 12.3)
     */
    const isSelected = (id) => {
        return selectedIds.value.has(id);
    };

    /**
     * Get count of selected elements (Phase 12.3)
     */
    const selectedCount = computed(() => selectedIds.value.size);

    /**
     * Select all elements (Phase 12.3)
     */
    const selectAll = () => {
        selectedIds.value = new Set();
        selectedTypes.value = new Map();

        players.value.forEach(p => {
            selectedIds.value.add(p.id);
            selectedTypes.value.set(p.id, 'player');
        });
        paths.value.forEach(p => {
            selectedIds.value.add(p.id);
            selectedTypes.value.set(p.id, 'path');
        });
        shapes.value.forEach(s => {
            selectedIds.value.add(s.id);
            selectedTypes.value.set(s.id, 'shape');
        });
        annotations.value.forEach(a => {
            selectedIds.value.add(a.id);
            selectedTypes.value.set(a.id, 'annotation');
        });
        freehandPaths.value.forEach(f => {
            selectedIds.value.add(f.id);
            selectedTypes.value.set(f.id, 'freehand');
        });
        circles.value.forEach(c => {
            selectedIds.value.add(c.id);
            selectedTypes.value.set(c.id, 'circle');
        });
        rectangles.value.forEach(r => {
            selectedIds.value.add(r.id);
            selectedTypes.value.set(r.id, 'rectangle');
        });
        arrows.value.forEach(a => {
            selectedIds.value.add(a.id);
            selectedTypes.value.set(a.id, 'arrow');
        });
        if (ball.value) {
            selectedIds.value.add(ball.value.id);
            selectedTypes.value.set(ball.value.id, 'ball');
        }

        // Update single selection to first element
        if (selectedIds.value.size > 0) {
            const firstId = [...selectedIds.value][0];
            selectedElementId.value = firstId;
            selectedElementType.value = selectedTypes.value.get(firstId);
        }
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
            zIndex: options.zIndex ?? zIndexCounter.value++,
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
            zIndex: zIndexCounter.value++,
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
            zIndex: zIndexCounter.value++,
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
            zIndex: options.zIndex ?? zIndexCounter.value++,
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
            zIndex: options.zIndex ?? zIndexCounter.value++,
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
            zIndex: options.zIndex ?? zIndexCounter.value++,
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
            zIndex: options.zIndex ?? zIndexCounter.value++,
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

    // ============================================
    // Eraser Methods (Phase 8.3)
    // ============================================

    /**
     * Calculate distance from point to line segment
     */
    const pointToLineDistance = (px, py, x1, y1, x2, y2) => {
        const A = px - x1;
        const B = py - y1;
        const C = x2 - x1;
        const D = y2 - y1;

        const dot = A * C + B * D;
        const lenSq = C * C + D * D;
        let param = -1;

        if (lenSq !== 0) {
            param = dot / lenSq;
        }

        let xx, yy;

        if (param < 0) {
            xx = x1;
            yy = y1;
        } else if (param > 1) {
            xx = x2;
            yy = y2;
        } else {
            xx = x1 + param * C;
            yy = y1 + param * D;
        }

        const dx = px - xx;
        const dy = py - yy;
        return Math.sqrt(dx * dx + dy * dy);
    };

    /**
     * Check if point is near a path (array of points)
     */
    const isPointNearPath = (px, py, points, tolerance = 15) => {
        if (!points || points.length < 2) return false;

        for (let i = 0; i < points.length - 1; i++) {
            const p1 = points[i];
            const p2 = points[i + 1];
            const distance = pointToLineDistance(px, py, p1.x, p1.y, p2.x, p2.y);
            if (distance <= tolerance) {
                return true;
            }
        }
        return false;
    };

    /**
     * Get element at position for eraser
     */
    const getElementAtPosition = (x, y, tolerance = 15) => {
        // Check ball (highest priority - small target)
        if (ball.value) {
            const dx = ball.value.x - x;
            const dy = ball.value.y - y;
            if (Math.sqrt(dx * dx + dy * dy) <= (ball.value.radius || 12) + tolerance) {
                return { id: ball.value.id, type: 'ball' };
            }
        }

        // Check players
        for (const player of players.value) {
            const dx = player.x - x;
            const dy = player.y - y;
            const playerSize = 20; // Default player size
            if (Math.sqrt(dx * dx + dy * dy) <= playerSize + tolerance) {
                return { id: player.id, type: 'player' };
            }
        }

        // Check circles
        for (const circle of circles.value) {
            const dx = (circle.x - x) / (circle.radiusX || 30);
            const dy = (circle.y - y) / (circle.radiusY || 30);
            // Check if point is near the ellipse border
            const normalizedDist = Math.sqrt(dx * dx + dy * dy);
            if (normalizedDist <= 1.3) { // Allow some tolerance for the fill area
                return { id: circle.id, type: 'circle' };
            }
        }

        // Check rectangles
        for (const rect of rectangles.value) {
            const halfW = (rect.width || 60) / 2 + tolerance;
            const halfH = (rect.height || 40) / 2 + tolerance;
            if (Math.abs(x - rect.x) <= halfW && Math.abs(y - rect.y) <= halfH) {
                return { id: rect.id, type: 'rectangle' };
            }
        }

        // Check arrows
        for (const arrow of arrows.value) {
            if (isPointNearPath(x, y, arrow.points, tolerance)) {
                return { id: arrow.id, type: 'arrow' };
            }
        }

        // Check freehand paths
        for (const freehand of freehandPaths.value) {
            if (isPointNearPath(x, y, freehand.points, tolerance)) {
                return { id: freehand.id, type: 'freehand' };
            }
        }

        // Check paths (movement, pass, dribble)
        for (const path of paths.value) {
            if (isPointNearPath(x, y, path.points, tolerance)) {
                return { id: path.id, type: 'path' };
            }
        }

        // Check shapes (screens)
        for (const shape of shapes.value) {
            const shapeWidth = shape.width || 40;
            const shapeHeight = 10; // Screen height
            const halfW = shapeWidth / 2 + tolerance;
            const halfH = shapeHeight / 2 + tolerance;
            if (Math.abs(x - shape.x) <= halfW && Math.abs(y - shape.y) <= halfH) {
                return { id: shape.id, type: 'shape' };
            }
        }

        // Check annotations
        for (const annotation of annotations.value) {
            const textWidth = (annotation.content?.length || 4) * (annotation.fontSize || 16) * 0.6;
            const textHeight = (annotation.fontSize || 16) * 1.2;
            if (x >= annotation.x - tolerance &&
                x <= annotation.x + textWidth + tolerance &&
                y >= annotation.y - textHeight - tolerance &&
                y <= annotation.y + tolerance) {
                return { id: annotation.id, type: 'annotation' };
            }
        }

        return null;
    };

    /**
     * Start erasing mode
     */
    const startErasing = () => {
        isErasing.value = true;
        erasedElementIds.value = new Set();
    };

    /**
     * Erase element at position
     */
    const eraseAtPosition = (x, y) => {
        const element = getElementAtPosition(x, y);
        if (element && !erasedElementIds.value.has(element.id)) {
            erasedElementIds.value.add(element.id);
            deleteElement(element.id, element.type);
            return true;
        }
        return false;
    };

    /**
     * Finish erasing mode
     */
    const finishErasing = () => {
        isErasing.value = false;
        erasedElementIds.value = new Set();
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
            zIndex: options.zIndex ?? zIndexCounter.value++,
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
            zIndex: options.zIndex ?? zIndexCounter.value++,
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
            version: '1.3', // Updated version for canvas dimensions
            court: {
                type: courtType.value,
                backgroundColor: courtColor.value,
                width: courtWidth.value,
                height: courtHeight.value,
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
     * Ensure all elements have zIndex (backward compatibility)
     */
    const ensureZIndex = (elements) => {
        if (!elements) return [];
        return elements.map((e, index) => ({
            ...e,
            zIndex: e.zIndex ?? zIndexCounter.value++,
        }));
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
            // Use ensureZIndex for backward compatibility with older saves
            players.value = ensureZIndex(data.elements.players);
            paths.value = ensureZIndex(data.elements.paths);
            shapes.value = ensureZIndex(data.elements.shapes);
            annotations.value = ensureZIndex(data.elements.annotations);
            freehandPaths.value = ensureZIndex(data.elements.freehandPaths);
            circles.value = ensureZIndex(data.elements.circles);
            rectangles.value = ensureZIndex(data.elements.rectangles);
            arrows.value = ensureZIndex(data.elements.arrows);
            // Handle ball separately
            if (data.elements.ball) {
                ball.value = {
                    ...data.elements.ball,
                    zIndex: data.elements.ball.zIndex ?? zIndexCounter.value++,
                };
            } else {
                ball.value = null;
            }
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

        let offensePositions, defensePositions;

        if (courtType.value === 'half_horizontal') {
            // Horizontales Feld - Korb rechts, um -90° gedreht
            offensePositions = [
                { x: courtWidth.value * 0.7, y: courtHeight.value * 0.5, label: 'PG' },
                { x: courtWidth.value * 0.6, y: courtHeight.value * 0.25, label: 'SG' },
                { x: courtWidth.value * 0.6, y: courtHeight.value * 0.75, label: 'SF' },
                { x: courtWidth.value * 0.4, y: courtHeight.value * 0.3, label: 'PF' },
                { x: courtWidth.value * 0.4, y: courtHeight.value * 0.7, label: 'C' },
            ];
            defensePositions = [
                { x: courtWidth.value * 0.65, y: courtHeight.value * 0.5 },
                { x: courtWidth.value * 0.55, y: courtHeight.value * 0.25 },
                { x: courtWidth.value * 0.55, y: courtHeight.value * 0.75 },
                { x: courtWidth.value * 0.35, y: courtHeight.value * 0.3 },
                { x: courtWidth.value * 0.35, y: courtHeight.value * 0.7 },
            ];
        } else {
            // Vertikales Feld / Vollfeld - Korb unten
            offensePositions = [
                { x: courtWidth.value * 0.5, y: courtHeight.value * 0.3, label: 'PG' },
                { x: courtWidth.value * 0.25, y: courtHeight.value * 0.4, label: 'SG' },
                { x: courtWidth.value * 0.75, y: courtHeight.value * 0.4, label: 'SF' },
                { x: courtWidth.value * 0.3, y: courtHeight.value * 0.6, label: 'PF' },
                { x: courtWidth.value * 0.7, y: courtHeight.value * 0.6, label: 'C' },
            ];
            defensePositions = [
                { x: courtWidth.value * 0.5, y: courtHeight.value * 0.35 },
                { x: courtWidth.value * 0.25, y: courtHeight.value * 0.45 },
                { x: courtWidth.value * 0.75, y: courtHeight.value * 0.45 },
                { x: courtWidth.value * 0.3, y: courtHeight.value * 0.65 },
                { x: courtWidth.value * 0.7, y: courtHeight.value * 0.65 },
            ];
        }

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
                zIndex: zIndexCounter.value++,
            });
        });

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
                zIndex: zIndexCounter.value++,
            });
        });
    };

    // ============================================
    // All Elements Sorted (Phase 12.2)
    // ============================================

    /**
     * Get all elements sorted by zIndex
     */
    const allElementsSorted = computed(() => {
        const all = [
            ...players.value.map(e => ({ ...e, _type: 'player' })),
            ...paths.value.map(e => ({ ...e, _type: 'path' })),
            ...shapes.value.map(e => ({ ...e, _type: 'shape' })),
            ...annotations.value.map(e => ({ ...e, _type: 'annotation' })),
            ...freehandPaths.value.map(e => ({ ...e, _type: 'freehand' })),
            ...circles.value.map(e => ({ ...e, _type: 'circle' })),
            ...rectangles.value.map(e => ({ ...e, _type: 'rectangle' })),
            ...arrows.value.map(e => ({ ...e, _type: 'arrow' })),
            ...(ball.value ? [{ ...ball.value, _type: 'ball' }] : []),
        ];
        return all.sort((a, b) => (a.zIndex ?? 0) - (b.zIndex ?? 0));
    });

    /**
     * Get selected elements as array (Phase 12.3)
     */
    const getSelectedElements = () => {
        const elements = [];
        for (const [id, type] of selectedTypes.value) {
            let element = null;
            switch (type) {
                case 'player':
                    element = players.value.find(p => p.id === id);
                    break;
                case 'path':
                    element = paths.value.find(p => p.id === id);
                    break;
                case 'shape':
                    element = shapes.value.find(s => s.id === id);
                    break;
                case 'annotation':
                    element = annotations.value.find(a => a.id === id);
                    break;
                case 'freehand':
                    element = freehandPaths.value.find(f => f.id === id);
                    break;
                case 'circle':
                    element = circles.value.find(c => c.id === id);
                    break;
                case 'rectangle':
                    element = rectangles.value.find(r => r.id === id);
                    break;
                case 'arrow':
                    element = arrows.value.find(a => a.id === id);
                    break;
                case 'ball':
                    element = ball.value;
                    break;
            }
            if (element) {
                elements.push({ ...element, _type: type });
            }
        }
        return elements;
    };

    /**
     * Get maximum zIndex value
     */
    const getMaxZIndex = () => {
        return Math.max(
            ...players.value.map(e => e.zIndex ?? 0),
            ...paths.value.map(e => e.zIndex ?? 0),
            ...shapes.value.map(e => e.zIndex ?? 0),
            ...annotations.value.map(e => e.zIndex ?? 0),
            ...freehandPaths.value.map(e => e.zIndex ?? 0),
            ...circles.value.map(e => e.zIndex ?? 0),
            ...rectangles.value.map(e => e.zIndex ?? 0),
            ...arrows.value.map(e => e.zIndex ?? 0),
            ball.value?.zIndex ?? 0,
            -1
        );
    };

    /**
     * Get minimum zIndex value
     */
    const getMinZIndex = () => {
        const values = [
            ...players.value.map(e => e.zIndex ?? 0),
            ...paths.value.map(e => e.zIndex ?? 0),
            ...shapes.value.map(e => e.zIndex ?? 0),
            ...annotations.value.map(e => e.zIndex ?? 0),
            ...freehandPaths.value.map(e => e.zIndex ?? 0),
            ...circles.value.map(e => e.zIndex ?? 0),
            ...rectangles.value.map(e => e.zIndex ?? 0),
            ...arrows.value.map(e => e.zIndex ?? 0),
        ];
        if (ball.value) values.push(ball.value.zIndex ?? 0);
        return values.length > 0 ? Math.min(...values) : 0;
    };

    /**
     * Update zIndex for an element
     */
    const updateElementZIndex = (id, type, newZIndex) => {
        let element = null;
        switch (type) {
            case 'player':
                element = players.value.find(p => p.id === id);
                break;
            case 'path':
                element = paths.value.find(p => p.id === id);
                break;
            case 'shape':
                element = shapes.value.find(s => s.id === id);
                break;
            case 'annotation':
                element = annotations.value.find(a => a.id === id);
                break;
            case 'freehand':
                element = freehandPaths.value.find(f => f.id === id);
                break;
            case 'circle':
                element = circles.value.find(c => c.id === id);
                break;
            case 'rectangle':
                element = rectangles.value.find(r => r.id === id);
                break;
            case 'arrow':
                element = arrows.value.find(a => a.id === id);
                break;
            case 'ball':
                element = ball.value;
                break;
        }
        if (element) {
            element.zIndex = newZIndex;
        }
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

        // Eraser state and methods (Phase 8.3)
        isErasing,
        getElementAtPosition,
        startErasing,
        eraseAtPosition,
        finishErasing,

        // Multi-selection state and methods (Phase 12.3)
        selectedIds,
        selectedTypes,
        selectedCount,
        toggleSelection,
        isSelected,
        selectAll,
        getSelectedElements,

        // Layer management (Phase 12.2)
        allElementsSorted,
        getMaxZIndex,
        getMinZIndex,
        updateElementZIndex,
    };
}
