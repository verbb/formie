import { PointerSensor } from '@dnd-kit/dom';

export const preventFieldDragActivation = (event, source) => {
    // Nested editors remain inside the parent draggable. Inspect the composed path
    // because shadow DOM retargets their input events to custom-element hosts.
    if (event.composedPath().some((element) => { return element?.matches?.('.formie-field-edit-dialog'); })) {
        return true;
    }

    return PointerSensor.defaults.preventActivation(event, source);
};
