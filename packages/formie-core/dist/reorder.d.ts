/**
 * Lets a visitor drag items in `list` to reorder them, and reports each move as indexes. It never moves nodes itself,
 * so the framework that renders the list stays in charge of it: call `onMove`, update the order in state, re-render.
 * Pointer presses on buttons inside an item are left alone, so items can carry move-up and move-down buttons.
 */
export declare function attachPointerReorder(list: HTMLElement, itemSelector: string, onMove: (from: number, to: number) => void): () => void;
//# sourceMappingURL=reorder.d.ts.map