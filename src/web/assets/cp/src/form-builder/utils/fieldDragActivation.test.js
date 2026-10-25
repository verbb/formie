import { afterEach, expect, it, vi } from 'vitest';
import { PointerSensor } from '@dnd-kit/dom';

import { preventFieldDragActivation } from './fieldDragActivation';

vi.mock('@dnd-kit/dom', () => {
    return { PointerSensor: { defaults: { preventActivation: vi.fn() } } };
});

afterEach(() => {
    vi.resetAllMocks();
});

const editor = { matches: (selector) => { return selector === '.formie-field-edit-dialog'; } };
const canvas = { matches: () => { return false; } };

it.each(['mouse', 'touch', 'pen'])('keeps %s gestures inside a nested editor out of the canvas sensor', (pointerType) => {
    const event = {
        pointerType,
        target: canvas,
        composedPath: () => { return [{}, editor, {}, canvas, {}]; },
    };

    expect(preventFieldDragActivation(event, { element: canvas })).toBe(true);
    expect(PointerSensor.defaults.preventActivation).not.toHaveBeenCalled();
});

it.each([true, false])('preserves the default activation decision (%s) for canvas gestures', (prevented) => {
    PointerSensor.defaults.preventActivation.mockReturnValue(prevented);
    const event = { composedPath: () => { return [canvas, {}]; } };
    const source = { element: canvas };

    expect(preventFieldDragActivation(event, source)).toBe(prevented);
    expect(PointerSensor.defaults.preventActivation).toHaveBeenCalledWith(event, source);
});
