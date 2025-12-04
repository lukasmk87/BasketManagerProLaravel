/**
 * Composable for alignment and distribution operations in the Tactic Board
 * Phase 12.3: Alignment and distribution tools
 */
export function useTacticAlignment(board) {
    /**
     * Get position info for an element (handles different element types)
     */
    const getElementBounds = (element) => {
        const type = element._type;

        switch (type) {
            case 'player':
            case 'ball':
                // Circle-based elements
                const radius = element.radius || 20;
                return {
                    x: element.x,
                    y: element.y,
                    left: element.x - radius,
                    right: element.x + radius,
                    top: element.y - radius,
                    bottom: element.y + radius,
                    width: radius * 2,
                    height: radius * 2,
                    centerX: element.x,
                    centerY: element.y,
                };

            case 'circle':
                return {
                    x: element.x,
                    y: element.y,
                    left: element.x - (element.radiusX || 30),
                    right: element.x + (element.radiusX || 30),
                    top: element.y - (element.radiusY || 30),
                    bottom: element.y + (element.radiusY || 30),
                    width: (element.radiusX || 30) * 2,
                    height: (element.radiusY || 30) * 2,
                    centerX: element.x,
                    centerY: element.y,
                };

            case 'rectangle':
            case 'shape':
                const width = element.width || 60;
                const height = element.height || 40;
                return {
                    x: element.x,
                    y: element.y,
                    left: element.x - width / 2,
                    right: element.x + width / 2,
                    top: element.y - height / 2,
                    bottom: element.y + height / 2,
                    width: width,
                    height: height,
                    centerX: element.x,
                    centerY: element.y,
                };

            case 'annotation':
                // Text elements - estimate width based on content
                const fontSize = element.fontSize || 16;
                const textWidth = (element.content?.length || 4) * fontSize * 0.6;
                const textHeight = fontSize * 1.2;
                return {
                    x: element.x,
                    y: element.y,
                    left: element.x,
                    right: element.x + textWidth,
                    top: element.y - textHeight,
                    bottom: element.y,
                    width: textWidth,
                    height: textHeight,
                    centerX: element.x + textWidth / 2,
                    centerY: element.y - textHeight / 2,
                };

            case 'path':
            case 'freehand':
            case 'arrow':
                // Path-based elements - calculate bounding box from points
                if (!element.points || element.points.length === 0) {
                    return null;
                }
                const xs = element.points.map(p => p.x);
                const ys = element.points.map(p => p.y);
                const minX = Math.min(...xs);
                const maxX = Math.max(...xs);
                const minY = Math.min(...ys);
                const maxY = Math.max(...ys);
                return {
                    x: (minX + maxX) / 2,
                    y: (minY + maxY) / 2,
                    left: minX,
                    right: maxX,
                    top: minY,
                    bottom: maxY,
                    width: maxX - minX,
                    height: maxY - minY,
                    centerX: (minX + maxX) / 2,
                    centerY: (minY + maxY) / 2,
                };

            default:
                return null;
        }
    };

    /**
     * Update element position based on type
     */
    const updateElementPosition = (element, newX, newY) => {
        const type = element._type;

        switch (type) {
            case 'player':
                board.updatePlayerPosition(element.id, newX, newY);
                break;
            case 'ball':
                board.updateBallPosition(newX, newY);
                break;
            case 'circle':
                board.updateCirclePosition(element.id, newX, newY);
                break;
            case 'rectangle':
                board.updateRectanglePosition(element.id, newX, newY);
                break;
            case 'shape':
                board.updateShapePosition(element.id, newX, newY);
                break;
            case 'annotation':
                board.updateAnnotationPosition(element.id, newX, newY);
                break;
            case 'path':
            case 'freehand':
            case 'arrow':
                // For path-based elements, move all points
                if (element.points && element.points.length > 0) {
                    const bounds = getElementBounds(element);
                    if (bounds) {
                        const dx = newX - bounds.centerX;
                        const dy = newY - bounds.centerY;
                        const newPoints = element.points.map(p => ({
                            x: p.x + dx,
                            y: p.y + dy,
                        }));

                        if (type === 'path') {
                            board.updatePathPoints(element.id, newPoints);
                        } else if (type === 'freehand') {
                            board.updateFreehandPoints(element.id, newPoints);
                        } else if (type === 'arrow') {
                            board.updateArrowPoints(element.id, newPoints);
                        }
                    }
                }
                break;
        }
    };

    // ============================================
    // Alignment Operations
    // ============================================

    /**
     * Align selected elements to left edge
     */
    const alignLeft = () => {
        const elements = board.getSelectedElements();
        if (elements.length < 2) return;

        const bounds = elements.map(e => getElementBounds(e)).filter(b => b !== null);
        if (bounds.length < 2) return;

        const minLeft = Math.min(...bounds.map(b => b.left));

        elements.forEach((element, index) => {
            const b = bounds[index];
            if (b) {
                const newX = minLeft + (b.centerX - b.left);
                updateElementPosition(element, newX, b.centerY);
            }
        });
    };

    /**
     * Align selected elements to horizontal center
     */
    const alignCenter = () => {
        const elements = board.getSelectedElements();
        if (elements.length < 2) return;

        const bounds = elements.map(e => getElementBounds(e)).filter(b => b !== null);
        if (bounds.length < 2) return;

        const minLeft = Math.min(...bounds.map(b => b.left));
        const maxRight = Math.max(...bounds.map(b => b.right));
        const center = (minLeft + maxRight) / 2;

        elements.forEach((element, index) => {
            const b = bounds[index];
            if (b) {
                updateElementPosition(element, center, b.centerY);
            }
        });
    };

    /**
     * Align selected elements to right edge
     */
    const alignRight = () => {
        const elements = board.getSelectedElements();
        if (elements.length < 2) return;

        const bounds = elements.map(e => getElementBounds(e)).filter(b => b !== null);
        if (bounds.length < 2) return;

        const maxRight = Math.max(...bounds.map(b => b.right));

        elements.forEach((element, index) => {
            const b = bounds[index];
            if (b) {
                const newX = maxRight - (b.right - b.centerX);
                updateElementPosition(element, newX, b.centerY);
            }
        });
    };

    /**
     * Align selected elements to top edge
     */
    const alignTop = () => {
        const elements = board.getSelectedElements();
        if (elements.length < 2) return;

        const bounds = elements.map(e => getElementBounds(e)).filter(b => b !== null);
        if (bounds.length < 2) return;

        const minTop = Math.min(...bounds.map(b => b.top));

        elements.forEach((element, index) => {
            const b = bounds[index];
            if (b) {
                const newY = minTop + (b.centerY - b.top);
                updateElementPosition(element, b.centerX, newY);
            }
        });
    };

    /**
     * Align selected elements to vertical middle
     */
    const alignMiddle = () => {
        const elements = board.getSelectedElements();
        if (elements.length < 2) return;

        const bounds = elements.map(e => getElementBounds(e)).filter(b => b !== null);
        if (bounds.length < 2) return;

        const minTop = Math.min(...bounds.map(b => b.top));
        const maxBottom = Math.max(...bounds.map(b => b.bottom));
        const middle = (minTop + maxBottom) / 2;

        elements.forEach((element, index) => {
            const b = bounds[index];
            if (b) {
                updateElementPosition(element, b.centerX, middle);
            }
        });
    };

    /**
     * Align selected elements to bottom edge
     */
    const alignBottom = () => {
        const elements = board.getSelectedElements();
        if (elements.length < 2) return;

        const bounds = elements.map(e => getElementBounds(e)).filter(b => b !== null);
        if (bounds.length < 2) return;

        const maxBottom = Math.max(...bounds.map(b => b.bottom));

        elements.forEach((element, index) => {
            const b = bounds[index];
            if (b) {
                const newY = maxBottom - (b.bottom - b.centerY);
                updateElementPosition(element, b.centerX, newY);
            }
        });
    };

    // ============================================
    // Distribution Operations
    // ============================================

    /**
     * Distribute selected elements horizontally (equal spacing)
     */
    const distributeHorizontally = () => {
        const elements = board.getSelectedElements();
        if (elements.length < 3) return;

        const bounds = elements.map(e => getElementBounds(e)).filter(b => b !== null);
        if (bounds.length < 3) return;

        // Sort by center X position
        const sorted = elements
            .map((e, i) => ({ element: e, bounds: bounds[i] }))
            .filter(item => item.bounds !== null)
            .sort((a, b) => a.bounds.centerX - b.bounds.centerX);

        const first = sorted[0].bounds;
        const last = sorted[sorted.length - 1].bounds;
        const totalWidth = last.centerX - first.centerX;
        const spacing = totalWidth / (sorted.length - 1);

        sorted.forEach((item, index) => {
            if (index === 0 || index === sorted.length - 1) return; // Skip first and last
            const newX = first.centerX + spacing * index;
            updateElementPosition(item.element, newX, item.bounds.centerY);
        });
    };

    /**
     * Distribute selected elements vertically (equal spacing)
     */
    const distributeVertically = () => {
        const elements = board.getSelectedElements();
        if (elements.length < 3) return;

        const bounds = elements.map(e => getElementBounds(e)).filter(b => b !== null);
        if (bounds.length < 3) return;

        // Sort by center Y position
        const sorted = elements
            .map((e, i) => ({ element: e, bounds: bounds[i] }))
            .filter(item => item.bounds !== null)
            .sort((a, b) => a.bounds.centerY - b.bounds.centerY);

        const first = sorted[0].bounds;
        const last = sorted[sorted.length - 1].bounds;
        const totalHeight = last.centerY - first.centerY;
        const spacing = totalHeight / (sorted.length - 1);

        sorted.forEach((item, index) => {
            if (index === 0 || index === sorted.length - 1) return; // Skip first and last
            const newY = first.centerY + spacing * index;
            updateElementPosition(item.element, item.bounds.centerX, newY);
        });
    };

    /**
     * Check if alignment operations are available
     */
    const canAlign = () => {
        return board.selectedIds.value.size >= 2;
    };

    /**
     * Check if distribution operations are available
     */
    const canDistribute = () => {
        return board.selectedIds.value.size >= 3;
    };

    return {
        // Alignment
        alignLeft,
        alignCenter,
        alignRight,
        alignTop,
        alignMiddle,
        alignBottom,

        // Distribution
        distributeHorizontally,
        distributeVertically,

        // Helpers
        canAlign,
        canDistribute,
        getElementBounds,
    };
}
