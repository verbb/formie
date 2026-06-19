import surveyPresentationsCss from '#theme-css/fields/_survey-presentations.css?inline';

import type { FormieModuleDefinition } from '#contracts/modules';
import { dispatchFieldEvent } from '#modules/fields/shared';
import { ensureModuleStyles } from '#modules/styles';
import { createDebug } from '#utils/debug';

const FIELD_SELECTOR = '[data-formie-survey-rank]';
const LIST_SELECTOR = '[data-formie-survey-rank-list]';
const ITEM_SELECTOR = '[data-formie-survey-rank-item]';
const HANDLE_SELECTOR = '[data-formie-rank-handle]';
const PLACEHOLDER_CLASS = 'formie-rank-placeholder';
/** Insert before a sibling once the ghost centre crosses this fraction of its height (below 0.5 swaps earlier). */
const PLACEHOLDER_SWAP_RATIO = 0.42;
const MODULE_ID = 'survey-rank';
const debug = createDebug('fields', 'survey-rank');

ensureModuleStyles(MODULE_ID, [surveyPresentationsCss]);

type PointerOffset = {
    x: number;
    y: number;
};

type GhostStyleSnapshot = {
    position: string;
    left: string;
    top: string;
    width: string;
    zIndex: string;
    pointerEvents: string;
    margin: string;
};

function syncRankInputs(list: HTMLElement): void {
    list.querySelectorAll(ITEM_SELECTOR).forEach((item, index) => {
        if (!(item instanceof HTMLElement)) {
            return;
        }

        item.querySelectorAll('input[data-formie-rank-input]').forEach((input) => {
            if (input instanceof HTMLInputElement) {
                input.dataset.formieRankOrder = String(index);
            }
        });
    });
}

function getRankItems(list: HTMLElement): HTMLElement[] {
    return Array.from(list.querySelectorAll(ITEM_SELECTOR)).filter((item): item is HTMLElement => {
        return item instanceof HTMLElement;
    });
}

function getPlaceholderIndex(list: HTMLElement, placeholder: HTMLElement): number {
    return Array.from(list.children).indexOf(placeholder);
}

function getRankListSiblings(
    list: HTMLElement,
    placeholder: HTMLElement,
    draggedItem: HTMLElement,
): HTMLElement[] {
    return Array.from(list.children).filter((child): child is HTMLElement => {
        return child instanceof HTMLElement
            && child !== placeholder
            && child !== draggedItem;
    });
}

function getVerticalOverlapHeight(ghostRect: DOMRect, targetRect: DOMRect): number {
    return Math.max(0, Math.min(ghostRect.bottom, targetRect.bottom) - Math.max(ghostRect.top, targetRect.top));
}

function getGhostReferenceY(draggedItem: HTMLElement): number {
    const rect = draggedItem.getBoundingClientRect();

    return rect.top + (rect.height / 2);
}

function getPlaceholderTargetIndex(
    draggedItem: HTMLElement,
    list: HTMLElement,
    placeholder: HTMLElement,
    draggingDown: boolean,
): number {
    const ghostRect = draggedItem.getBoundingClientRect();
    const referenceY = getGhostReferenceY(draggedItem);
    const siblings = getRankListSiblings(list, placeholder, draggedItem);

    for (let index = 0; index < siblings.length; index += 1) {
        const rect = siblings[index].getBoundingClientRect();
        const swapLine = rect.top + (rect.height * PLACEHOLDER_SWAP_RATIO);
        const overlapRatio = getVerticalOverlapHeight(ghostRect, rect) / rect.height;

        if (overlapRatio >= PLACEHOLDER_SWAP_RATIO) {
            if (draggingDown) {
                return Math.min(index + 1, siblings.length);
            }

            return index;
        }

        if (referenceY < swapLine) {
            return index;
        }
    }

    return siblings.length;
}

function movePlaceholderToIndex(
    list: HTMLElement,
    placeholder: HTMLElement,
    draggedItem: HTMLElement,
    targetIndex: number,
): void {
    const siblings = getRankListSiblings(list, placeholder, draggedItem);
    const reference = siblings[targetIndex] ?? null;

    if (reference) {
        list.insertBefore(placeholder, reference);
        return;
    }

    list.appendChild(placeholder);
}

function createPlaceholder(list: HTMLElement, item: HTMLElement): HTMLElement {
    const placeholder = document.createElement('li');

    placeholder.className = PLACEHOLDER_CLASS;
    placeholder.setAttribute('data-formie-rank-placeholder', 'true');
    placeholder.setAttribute('aria-hidden', 'true');
    placeholder.style.height = `${item.offsetHeight}px`;
    list.insertBefore(placeholder, item);

    return placeholder;
}

function captureGhostStyles(item: HTMLElement): GhostStyleSnapshot {
    return {
        position: item.style.position,
        left: item.style.left,
        top: item.style.top,
        width: item.style.width,
        zIndex: item.style.zIndex,
        pointerEvents: item.style.pointerEvents,
        margin: item.style.margin,
    };
}

function applyGhostStyles(item: HTMLElement, rect: DOMRect): void {
    item.style.position = 'fixed';
    item.style.left = `${rect.left}px`;
    item.style.top = `${rect.top}px`;
    item.style.width = `${rect.width}px`;
    item.style.zIndex = '1000';
    item.style.pointerEvents = 'none';
    item.style.margin = '0';
}

function updateGhostPosition(item: HTMLElement, event: PointerEvent, offset: PointerOffset): void {
    item.style.left = `${event.clientX - offset.x}px`;
    item.style.top = `${event.clientY - offset.y}px`;
}

function restoreGhostStyles(item: HTMLElement, snapshot: GhostStyleSnapshot): void {
    item.style.position = snapshot.position;
    item.style.left = snapshot.left;
    item.style.top = snapshot.top;
    item.style.width = snapshot.width;
    item.style.zIndex = snapshot.zIndex;
    item.style.pointerEvents = snapshot.pointerEvents;
    item.style.margin = snapshot.margin;
}

function bindRankField(field: HTMLElement): () => void {
    const list = field.querySelector(LIST_SELECTOR);

    if (!(list instanceof HTMLElement)) {
        debug.warn('Missing rank list; skipping field.');
        return () => {};
    }

    let draggedItem: HTMLElement | null = null;
    let placeholder: HTMLElement | null = null;
    let ghostStyleSnapshot: GhostStyleSnapshot | null = null;
    let pointerOffset: PointerOffset | null = null;
    let activeCaptureTarget: HTMLElement | null = null;
    let activePointerId: number | null = null;
    let lastTargetIndex: number | null = null;
    let startPlaceholderIndex: number | null = null;
    let lastGhostReferenceY: number | null = null;
    let didReorder = false;
    const cleanups: Array<() => void> = [];

    const finishDrag = () => {
        if (draggedItem && placeholder) {
            list.insertBefore(draggedItem, placeholder);
            placeholder.remove();
        } else if (placeholder) {
            placeholder.remove();
        }

        if (draggedItem && ghostStyleSnapshot) {
            restoreGhostStyles(draggedItem, ghostStyleSnapshot);
            draggedItem.removeAttribute('data-formie-rank-dragging');
        }

        if (activeCaptureTarget && activePointerId !== null) {
            try {
                activeCaptureTarget.releasePointerCapture(activePointerId);
            } catch {
                // Pointer capture may already be released.
            }
        }

        draggedItem = null;
        placeholder = null;
        ghostStyleSnapshot = null;
        pointerOffset = null;
        activeCaptureTarget = null;
        activePointerId = null;
        lastTargetIndex = null;
        startPlaceholderIndex = null;
        lastGhostReferenceY = null;
        list.removeAttribute('data-formie-rank-sorting');

        if (didReorder) {
            syncRankInputs(list);
            dispatchFieldEvent(field, MODULE_ID, 'reorder', {
                rankField: field,
            });
        }

        didReorder = false;
    };

    const onPointerMove = (event: PointerEvent) => {
        if (!draggedItem || !placeholder || !pointerOffset || event.pointerId !== activePointerId) {
            return;
        }

        event.preventDefault();

        updateGhostPosition(draggedItem, event, pointerOffset);

        const referenceY = getGhostReferenceY(draggedItem);
        const draggingDown = lastGhostReferenceY === null || referenceY >= lastGhostReferenceY;

        lastGhostReferenceY = referenceY;

        const targetIndex = getPlaceholderTargetIndex(draggedItem, list, placeholder, draggingDown);

        if (targetIndex === lastTargetIndex) {
            return;
        }

        lastTargetIndex = targetIndex;
        movePlaceholderToIndex(list, placeholder, draggedItem, targetIndex);
    };

    const onPointerUp = (event: PointerEvent) => {
        if (event.pointerId !== activePointerId) {
            return;
        }

        document.removeEventListener('pointermove', onPointerMove);
        document.removeEventListener('pointerup', onPointerUp);
        document.removeEventListener('pointercancel', onPointerUp);

        if (placeholder && startPlaceholderIndex !== null) {
            didReorder = getPlaceholderIndex(list, placeholder) !== startPlaceholderIndex;
        }

        finishDrag();
    };

    getRankItems(list).forEach((item) => {
        if (!item.querySelector(HANDLE_SELECTOR)) {
            return;
        }

        const onPointerDown = (event: PointerEvent) => {
            if (event.button !== 0) {
                return;
            }

            if (event.target instanceof HTMLInputElement) {
                return;
            }

            event.preventDefault();

            const rect = item.getBoundingClientRect();

            draggedItem = item;
            activeCaptureTarget = item;
            activePointerId = event.pointerId;
            pointerOffset = {
                x: event.clientX - rect.left,
                y: event.clientY - rect.top,
            };
            ghostStyleSnapshot = captureGhostStyles(item);
            placeholder = createPlaceholder(list, item);
            startPlaceholderIndex = getPlaceholderIndex(list, placeholder);
            didReorder = false;

            applyGhostStyles(item, rect);
            item.setAttribute('data-formie-rank-dragging', 'true');
            list.setAttribute('data-formie-rank-sorting', 'true');
            item.setPointerCapture(event.pointerId);
            lastGhostReferenceY = getGhostReferenceY(item);
            lastTargetIndex = getPlaceholderTargetIndex(item, list, placeholder, true);

            document.addEventListener('pointermove', onPointerMove);
            document.addEventListener('pointerup', onPointerUp);
            document.addEventListener('pointercancel', onPointerUp);
        };

        item.addEventListener('pointerdown', onPointerDown);
        cleanups.push(() => {
            item.removeEventListener('pointerdown', onPointerDown);
        });
    });

    syncRankInputs(list);

    return () => {
        document.removeEventListener('pointermove', onPointerMove);
        document.removeEventListener('pointerup', onPointerUp);
        document.removeEventListener('pointercancel', onPointerUp);
        cleanups.forEach((cleanup) => {
            cleanup();
        });
        finishDrag();
    };
}

export const surveyRankModule: FormieModuleDefinition = {
    id: MODULE_ID,
    kind: 'field',
    match: (ctx) => {
        return ctx.target instanceof HTMLElement && (
            ctx.target.matches(FIELD_SELECTOR) ||
            !!ctx.target.querySelector(FIELD_SELECTOR)
        );
    },
    setup: async(ctx) => {
        if (!(ctx.target instanceof HTMLElement)) {
            return;
        }

        const fields = ctx.target.matches(FIELD_SELECTOR)
            ? [ctx.target]
            : Array.from(ctx.target.querySelectorAll(FIELD_SELECTOR)).filter((field): field is HTMLElement => {
                return field instanceof HTMLElement;
            });

        debug.log('Module setup.', {
            fieldCount: fields.length,
        });

        const destroyBindings = fields.map((field) => {
            return bindRankField(field);
        });

        await ctx.emit('formie:module:survey-rank:init', {
            count: fields.length,
        });

        return {
            destroy: () => {
                destroyBindings.forEach((destroyBinding) => {
                    destroyBinding();
                });
                debug.log('Module destroy.', {
                    fieldCount: fields.length,
                });
                void ctx.emit('formie:module:survey-rank:destroy', {});
            },
        };
    },
};
