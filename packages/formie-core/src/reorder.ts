/**
 * Lets a visitor drag items in `list` to reorder them, and reports each move as indexes. It never moves nodes itself,
 * so the framework that renders the list stays in charge of it: call `onMove`, update the order in state, re-render.
 * Pointer presses on buttons inside an item are left alone, so items can carry move-up and move-down buttons.
 */
export function attachPointerReorder(list: HTMLElement, itemSelector: string, onMove: (from: number, to: number) => void): () => void {
    let from = -1;
    let startY = 0;
    let dragging = false;
    let target = -1;

    const items = () => Array.from(list.querySelectorAll<HTMLElement>(itemSelector)).filter((item) => item.parentElement === list);

    const targetIndex = (y: number) => {
        const all = items();
        const index = all.findIndex((item) => {
            const box = item.getBoundingClientRect();
            return y < box.top + box.height / 2;
        });

        // Below every item's middle means the end of the list. Moving down past the dragged item shifts by one.
        const slot = index === -1 ? all.length : index;
        return slot > from ? slot - 1 : slot;
    };

    const mark = (index: number) => {
        items().forEach((item, position) => {
            item.toggleAttribute('data-formie-reorder-dragging', dragging && position === from);
            item.toggleAttribute('data-formie-reorder-target', dragging && position === index && index !== from);
        });
    };

    const onPointerMove = (event: PointerEvent) => {
        if (from === -1) return;

        if (!dragging && Math.abs(event.clientY - startY) < 4) return;

        dragging = true;
        target = targetIndex(event.clientY);
        mark(target);
        event.preventDefault();
    };

    const finish = () => {
        document.removeEventListener('pointermove', onPointerMove);
        document.removeEventListener('pointerup', onPointerUp);
        document.removeEventListener('pointercancel', onPointerCancel);
        const moved = dragging && target !== -1 && target !== from ? [from, target] as const : null;
        dragging = false;
        mark(-1);
        from = -1;
        target = -1;

        return moved;
    };

    const onPointerUp = () => {
        const moved = finish();

        if (moved) onMove(moved[0], moved[1]);
    };

    const onPointerCancel = () => {
        finish();
    };

    const onPointerDown = (event: PointerEvent) => {
        if (event.button !== 0 || (event.target as Element | null)?.closest('button, input, select, textarea, a')) return;

        const item = (event.target as Element | null)?.closest<HTMLElement>(itemSelector);
        const index = item ? items().indexOf(item) : -1;

        if (index === -1) return;

        from = index;
        startY = event.clientY;
        document.addEventListener('pointermove', onPointerMove);
        document.addEventListener('pointerup', onPointerUp);
        document.addEventListener('pointercancel', onPointerCancel);
    };

    list.addEventListener('pointerdown', onPointerDown);

    return () => {
        list.removeEventListener('pointerdown', onPointerDown);
        finish();
    };
}
