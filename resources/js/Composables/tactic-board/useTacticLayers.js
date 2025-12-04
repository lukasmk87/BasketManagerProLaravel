/**
 * Composable for layer management operations in the Tactic Board
 * Phase 12.2: Z-Index and layer ordering
 */
export function useTacticLayers(board) {
    /**
     * Bring selected element(s) to front (highest z-index)
     */
    const bringToFront = () => {
        if (board.selectedIds.value.size === 0) return;

        const maxZ = board.getMaxZIndex();

        for (const [id, type] of board.selectedTypes.value) {
            board.updateElementZIndex(id, type, maxZ + 1 + [...board.selectedIds.value].indexOf(id));
        }
    };

    /**
     * Send selected element(s) to back (lowest z-index)
     */
    const sendToBack = () => {
        if (board.selectedIds.value.size === 0) return;

        const minZ = board.getMinZIndex();
        const selectedCount = board.selectedIds.value.size;

        let offset = 0;
        for (const [id, type] of board.selectedTypes.value) {
            board.updateElementZIndex(id, type, minZ - selectedCount + offset);
            offset++;
        }
    };

    /**
     * Bring selected element(s) one layer forward
     */
    const bringForward = () => {
        if (board.selectedIds.value.size === 0) return;

        const allElements = board.allElementsSorted.value;
        const selectedIds = [...board.selectedIds.value];

        // Find elements just above the selected ones and swap
        for (const selectedId of selectedIds) {
            const currentIndex = allElements.findIndex(e => e.id === selectedId);
            if (currentIndex === -1 || currentIndex === allElements.length - 1) continue;

            const current = allElements[currentIndex];
            const above = allElements[currentIndex + 1];

            // Skip if the element above is also selected
            if (selectedIds.includes(above.id)) continue;

            const currentType = board.selectedTypes.value.get(selectedId);
            const aboveType = above._type;

            // Swap z-indices
            const tempZ = current.zIndex ?? 0;
            board.updateElementZIndex(selectedId, currentType, above.zIndex ?? 0);
            board.updateElementZIndex(above.id, aboveType, tempZ);
        }
    };

    /**
     * Send selected element(s) one layer backward
     */
    const sendBackward = () => {
        if (board.selectedIds.value.size === 0) return;

        const allElements = board.allElementsSorted.value;
        const selectedIds = [...board.selectedIds.value];

        // Process in reverse order to avoid conflicts
        for (const selectedId of selectedIds.reverse()) {
            const currentIndex = allElements.findIndex(e => e.id === selectedId);
            if (currentIndex === -1 || currentIndex === 0) continue;

            const current = allElements[currentIndex];
            const below = allElements[currentIndex - 1];

            // Skip if the element below is also selected
            if (selectedIds.includes(below.id)) continue;

            const currentType = board.selectedTypes.value.get(selectedId);
            const belowType = below._type;

            // Swap z-indices
            const tempZ = current.zIndex ?? 0;
            board.updateElementZIndex(selectedId, currentType, below.zIndex ?? 0);
            board.updateElementZIndex(below.id, belowType, tempZ);
        }
    };

    /**
     * Check if selected element can be brought forward
     */
    const canBringForward = () => {
        if (board.selectedIds.value.size === 0) return false;

        const allElements = board.allElementsSorted.value;
        const selectedIds = [...board.selectedIds.value];

        // Check if any selected element is not at the top
        for (const selectedId of selectedIds) {
            const currentIndex = allElements.findIndex(e => e.id === selectedId);
            if (currentIndex < allElements.length - 1) {
                const above = allElements[currentIndex + 1];
                if (!selectedIds.includes(above.id)) {
                    return true;
                }
            }
        }
        return false;
    };

    /**
     * Check if selected element can be sent backward
     */
    const canSendBackward = () => {
        if (board.selectedIds.value.size === 0) return false;

        const allElements = board.allElementsSorted.value;
        const selectedIds = [...board.selectedIds.value];

        // Check if any selected element is not at the bottom
        for (const selectedId of selectedIds) {
            const currentIndex = allElements.findIndex(e => e.id === selectedId);
            if (currentIndex > 0) {
                const below = allElements[currentIndex - 1];
                if (!selectedIds.includes(below.id)) {
                    return true;
                }
            }
        }
        return false;
    };

    return {
        bringToFront,
        sendToBack,
        bringForward,
        sendBackward,
        canBringForward,
        canSendBackward,
    };
}
