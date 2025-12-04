import { onMounted, onUnmounted } from 'vue';

/**
 * Composable for managing keyboard shortcuts in the Tactic Board
 *
 * Keyboard shortcuts:
 * | Key          | Action               |
 * |--------------|----------------------|
 * | V            | Select tool          |
 * | P            | Player tool          |
 * | M            | Movement line        |
 * | A            | Pass line            |
 * | D            | Dribble line         |
 * | S            | Screen tool          |
 * | T            | Text tool            |
 * | F            | Freehand             |
 * | C            | Circle tool          |
 * | R            | Rectangle tool       |
 * | W            | Arrow tool           |
 * | E            | Eraser tool          |
 * | G            | Toggle grid          |
 * | Ctrl+Z       | Undo                 |
 * | Ctrl+Shift+Z | Redo                 |
 * | Ctrl+Y       | Redo (alternative)   |
 * | Delete       | Delete selected      |
 * | Backspace    | Delete selected      |
 * | Escape       | Clear selection      |
 * | Ctrl++       | Zoom in              |
 * | Ctrl+-       | Zoom out             |
 * | Ctrl+0       | Reset zoom           |
 */
export function useTacticKeyboard({
    onToolChange,
    onUndo,
    onRedo,
    onDelete,
    onClearSelection,
    onZoomIn,
    onZoomOut,
    onZoomReset,
    onToggleGrid,
    isEnabled = () => true,
}) {
    const toolShortcuts = {
        v: 'select',
        p: 'player',
        m: 'movement',
        a: 'pass',
        d: 'dribble',
        s: 'screen',
        t: 'text',
        f: 'freehand',
        c: 'circle',
        r: 'rectangle',
        w: 'arrow',
        e: 'eraser',
    };

    const handleKeyDown = (e) => {
        // Don't handle if disabled
        if (!isEnabled()) return;

        // Don't handle if user is typing in an input
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.isContentEditable) {
            return;
        }

        const key = e.key.toLowerCase();
        const isCtrl = e.ctrlKey || e.metaKey;
        const isShift = e.shiftKey;

        // Ctrl+Z: Undo
        if (isCtrl && !isShift && key === 'z') {
            e.preventDefault();
            onUndo?.();
            return;
        }

        // Ctrl+Shift+Z or Ctrl+Y: Redo
        if ((isCtrl && isShift && key === 'z') || (isCtrl && key === 'y')) {
            e.preventDefault();
            onRedo?.();
            return;
        }

        // Ctrl++ / Ctrl+=: Zoom in
        if (isCtrl && (key === '+' || key === '=')) {
            e.preventDefault();
            onZoomIn?.();
            return;
        }

        // Ctrl+-: Zoom out
        if (isCtrl && key === '-') {
            e.preventDefault();
            onZoomOut?.();
            return;
        }

        // Ctrl+0: Reset zoom
        if (isCtrl && key === '0') {
            e.preventDefault();
            onZoomReset?.();
            return;
        }

        // Don't process other shortcuts if Ctrl is pressed
        if (isCtrl) return;

        // Delete or Backspace: Delete selected
        if (key === 'delete' || key === 'backspace') {
            e.preventDefault();
            onDelete?.();
            return;
        }

        // Escape: Clear selection
        if (key === 'escape') {
            e.preventDefault();
            onClearSelection?.();
            return;
        }

        // G: Toggle grid
        if (key === 'g') {
            e.preventDefault();
            onToggleGrid?.();
            return;
        }

        // Tool shortcuts
        if (toolShortcuts[key]) {
            e.preventDefault();
            onToolChange?.(toolShortcuts[key]);
            return;
        }
    };

    onMounted(() => {
        window.addEventListener('keydown', handleKeyDown);
    });

    onUnmounted(() => {
        window.removeEventListener('keydown', handleKeyDown);
    });

    return {
        toolShortcuts,
    };
}
