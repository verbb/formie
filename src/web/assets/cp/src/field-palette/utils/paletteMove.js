import { move } from '@dnd-kit/helpers';

import {
    fieldId,
    sortableFieldGroupKey,
    UNASSIGNED_SORTABLE_GROUP,
} from '@field-palette/utils/paletteState';

const resolveItemId = (item) => {
    if (typeof item === 'object' && item !== null && 'id' in item) {
        return item.id;
    }

    return item;
};

export const paletteToSortableItems = (palette) => {
    const items = {};

    for (const group of palette.groups || []) {
        items[group.uid] = (group.fields || []).map((field) => {
            return { id: fieldId(field.fieldClass) };
        });
    }

    items[UNASSIGNED_SORTABLE_GROUP] = (palette.unassigned || []).map((field) => {
        return { id: fieldId(field.fieldClass) };
    });

    return items;
};

const buildFieldLookup = (palette) => {
    const fieldById = new Map();

    for (const group of palette.groups || []) {
        for (const field of group.fields || []) {
            fieldById.set(fieldId(field.fieldClass), field);
        }
    }

    for (const field of palette.unassigned || []) {
        fieldById.set(fieldId(field.fieldClass), field);
    }

    return fieldById;
};

const mapSortableListToFields = (list, fieldById) => {
    return (list || []).map((item) => {
        return fieldById.get(resolveItemId(item));
    }).filter(Boolean);
};

/** Cheap compare — skips React commits when drag-over did not change field order. */
export const paletteFieldOrderSignature = (palette) => {
    const parts = [];

    for (const group of palette.groups || []) {
        parts.push(group.uid);

        for (const field of group.fields || []) {
            parts.push(field.fieldClass);
        }
    }

    parts.push(UNASSIGNED_SORTABLE_GROUP);

    for (const field of palette.unassigned || []) {
        parts.push(field.fieldClass);
    }

    return parts.join('\0');
};

export const applyMoveEventToPalette = (palette, event) => {
    const { source, canceled } = event.operation ?? {};

    if (!source || canceled) {
        return palette;
    }

    const sourceData = source?.data?.current ?? source?.data;

    if (sourceData?.type !== 'field') {
        return palette;
    }

    const fieldById = buildFieldLookup(palette);
    const sortableItems = paletteToSortableItems(palette);
    const nextItems = move(sortableItems, event);

    if (nextItems === sortableItems) {
        return palette;
    }

    const next = {
        groups: (palette.groups || []).map((group) => {
            return {
                ...group,
                fields: mapSortableListToFields(nextItems[group.uid], fieldById),
            };
        }),
        unassigned: mapSortableListToFields(nextItems[UNASSIGNED_SORTABLE_GROUP], fieldById),
    };

    if (paletteFieldOrderSignature(next) === paletteFieldOrderSignature(palette)) {
        return palette;
    }

    return next;
};

export const sortableDropZoneId = (groupUid) => {
    return sortableFieldGroupKey(groupUid);
};
