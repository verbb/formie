const slugifyHandle = (value) => {
    return String(value || '')
        .trim()
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .slice(0, 50) || 'group';
};

export const createUniqueGroupHandle = (name, groups) => {
    const base = slugifyHandle(name);
    const handles = new Set((groups || []).map((group) => { return group.handle; }));

    if (!handles.has(base)) {
        return base;
    }

    let index = 2;

    while (handles.has(`${base}-${index}`)) {
        index += 1;
    }

    return `${base}-${index}`;
};

export const createGroup = (name, groups) => {
    const trimmedName = String(name || '').trim() || Craft.t('formie', 'New Group');

    return {
        uid: crypto.randomUUID(),
        handle: createUniqueGroupHandle(trimmedName, groups),
        name: trimmedName,
        fields: [],
    };
};

export const findFieldLocation = (palette, fieldClass) => {
    for (const group of palette.groups || []) {
        const index = (group.fields || []).findIndex((field) => { return field.fieldClass === fieldClass; });

        if (index >= 0) {
            return { container: 'group', groupUid: group.uid, index };
        }
    }

    const unassignedIndex = (palette.unassigned || []).findIndex((field) => { return field.fieldClass === fieldClass; });

    if (unassignedIndex >= 0) {
        return { container: 'unassigned', groupUid: null, index: unassignedIndex };
    }

    return null;
};

export const removeFieldFromPalette = (palette, fieldClass) => {
    const next = {
        groups: (palette.groups || []).map((group) => {
            return {
                ...group,
                fields: (group.fields || []).filter((field) => { return field.fieldClass !== fieldClass; }),
            };
        }),
        unassigned: (palette.unassigned || []).filter((field) => { return field.fieldClass !== fieldClass; }),
    };

    return next;
};

export const insertField = (palette, field, target) => {
    const cleaned = removeFieldFromPalette(palette, field.fieldClass);

    if (target.container === 'unassigned') {
        const unassigned = [...(cleaned.unassigned || [])];
        unassigned.splice(target.index, 0, field);

        return {
            ...cleaned,
            unassigned,
        };
    }

    return {
        ...cleaned,
        groups: (cleaned.groups || []).map((group) => {
            if (group.uid !== target.groupUid) {
                return group;
            }

            const fields = [...(group.fields || [])];
            fields.splice(target.index, 0, field);

            return {
                ...group,
                fields,
            };
        }),
    };
};

export const moveFieldToGroup = (palette, fieldClass, targetGroupUid, targetIndex = null) => {
    const location = findFieldLocation(palette, fieldClass);

    if (!location) {
        return palette;
    }

    const sourceList = location.container === 'unassigned'
        ? palette.unassigned
        : (palette.groups || []).find((group) => { return group.uid === location.groupUid; })?.fields;

    const field = sourceList?.[location.index];

    if (!field) {
        return palette;
    }

    if (targetGroupUid === null) {
        const index = targetIndex ?? palette.unassigned?.length ?? 0;

        return insertField(palette, field, { container: 'unassigned', groupUid: null, index });
    }

    const targetGroup = (palette.groups || []).find((group) => { return group.uid === targetGroupUid; });

    if (!targetGroup) {
        return palette;
    }

    const index = targetIndex ?? targetGroup.fields?.length ?? 0;

    return insertField(palette, field, { container: 'group', groupUid: targetGroupUid, index });
};

export const reorderCollection = (items, fromIndex, toIndex) => {
    if (fromIndex === toIndex || fromIndex < 0 || toIndex < 0 || fromIndex >= items.length || toIndex >= items.length) {
        return items;
    }

    const next = [...items];
    const [moved] = next.splice(fromIndex, 1);
    next.splice(toIndex, 0, moved);

    return next;
};

export const serializePaletteForSave = (palette) => {
    return {
        groups: (palette.groups || []).map((group) => {
            return {
                uid: group.uid,
                handle: group.handle,
                name: group.name,
                fields: (group.fields || []).map((field) => {
                    return {
                        fieldClass: field.fieldClass,
                        enabled: field.enabled !== false,
                        label: field.label?.trim() ? field.label.trim() : null,
                    };
                }),
            };
        }),
        unassigned: (palette.unassigned || []).map((field) => {
            return {
                fieldClass: field.fieldClass,
                enabled: field.enabled !== false,
                label: field.label?.trim() ? field.label.trim() : null,
            };
        }),
    };
};

export const moveGroupByOffset = (palette, groupUid, offset) => {
    const groupIndex = (palette.groups || []).findIndex((group) => { return group.uid === groupUid; });

    if (groupIndex < 0) {
        return palette;
    }

    const targetIndex = groupIndex + offset;

    if (targetIndex < 0 || targetIndex >= (palette.groups || []).length) {
        return palette;
    }

    return {
        ...palette,
        groups: reorderCollection(palette.groups || [], groupIndex, targetIndex),
    };
};

export const moveFieldByOffset = (palette, fieldClass, offset) => {
    const location = findFieldLocation(palette, fieldClass);

    if (!location) {
        return palette;
    }

    const sourceList = location.container === 'unassigned'
        ? palette.unassigned || []
        : (palette.groups || []).find((group) => { return group.uid === location.groupUid; })?.fields || [];

    const targetIndex = location.index + offset;

    if (targetIndex < 0 || targetIndex >= sourceList.length) {
        return palette;
    }

    if (location.container === 'unassigned') {
        return {
            ...palette,
            unassigned: reorderCollection(sourceList, location.index, targetIndex),
        };
    }

    return {
        ...palette,
        groups: (palette.groups || []).map((group) => {
            if (group.uid !== location.groupUid) {
                return group;
            }

            return {
                ...group,
                fields: reorderCollection(group.fields || [], location.index, targetIndex),
            };
        }),
    };
};

export const getFieldListForLocation = (palette, location) => {
    if (!location) {
        return [];
    }

    if (location.container === 'unassigned') {
        return palette.unassigned || [];
    }

    return (palette.groups || []).find((group) => { return group.uid === location.groupUid; })?.fields || [];
};

export const UNASSIGNED_SORTABLE_GROUP = '__unassigned__';

export const sortableFieldGroupKey = (groupUid) => {
    return groupUid ?? UNASSIGNED_SORTABLE_GROUP;
};

export const fieldId = (fieldClass) => { return `field:${fieldClass}`; };

const sanitizePaletteDomId = (value) => {
    return String(value).replace(/\\/g, '--').replace(/[^a-zA-Z0-9_-]/g, '-');
};

export const groupNameInputId = (groupUid) => { return `formie-palette-group-name-${groupUid}`; };
export const fieldLabelInputId = (fieldClass) => {
    return `formie-palette-field-label-${sanitizePaletteDomId(fieldClass)}`;
};
export const fieldEnabledInputId = (fieldClass) => {
    return `formie-palette-field-enabled-${sanitizePaletteDomId(fieldClass)}`;
};
