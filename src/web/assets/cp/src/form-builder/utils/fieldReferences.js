const createFieldReference = () => {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID();
    }

    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (char) => {
        const random = Math.floor(Math.random() * 16);
        const value = char === 'x' ? random : ((random & 0x3) | 0x8);
        return value.toString(16);
    });
};

const isLayoutRowsMap = (value) => {
    if (!value || typeof value !== 'object' || Array.isArray(value)) {
        return false;
    }

    return Object.values(value).some((entry) => Array.isArray(entry));
};

const mapNestedRows = (rows, callback) => {
    return rows.map((row) => {
        return {
            ...row,
            fields: Array.isArray(row?.fields)
                ? row.fields.map(callback)
                : row?.fields,
        };
    });
};

const syncNestedRows = (field, nestedRows) => {
    const nextField = {
        ...field,
        rows: nestedRows,
    };

    if (nextField.settings && typeof nextField.settings === 'object') {
        nextField.settings = {
            ...nextField.settings,
            rows: nestedRows,
        };
    }

    return nextField;
};

const syncLayoutMaps = (field, layouts) => {
    const nextField = {
        ...field,
        layouts,
    };

    if (nextField.settings && typeof nextField.settings === 'object') {
        nextField.settings = {
            ...nextField.settings,
            layouts,
        };
    }

    return nextField;
};

const mapLayoutRowsMap = (layouts, callback) => {
    if (!isLayoutRowsMap(layouts)) {
        return layouts;
    }

    const nextLayouts = { ...layouts };

    Object.keys(nextLayouts).forEach((layoutKey) => {
        if (Array.isArray(nextLayouts[layoutKey])) {
            nextLayouts[layoutKey] = mapNestedRows(nextLayouts[layoutKey], callback);
        }
    });

    return nextLayouts;
};

const forEachFieldInRows = (rows, callback) => {
    if (!Array.isArray(rows)) {
        return;
    }

    rows.forEach((row) => {
        if (!row || typeof row !== 'object' || !Array.isArray(row.fields)) {
            return;
        }

        row.fields.forEach((field) => {
            if (!field || typeof field !== 'object') {
                return;
            }

            callback(field);

            forEachFieldInRows(field.rows, callback);
            forEachFieldInRows(field?.settings?.rows, callback);
        });
    });
};

const forEachFieldInLayoutMaps = (layouts, callback) => {
    if (!isLayoutRowsMap(layouts)) {
        return;
    }

    Object.values(layouts).forEach((rows) => {
        forEachFieldInRows(rows, callback);
    });
};

const getFieldHandle = (field) => {
    return String(field?.handle || field?.settings?.handle || '').trim();
};

const getActiveSubFieldRows = (field) => {
    if (Array.isArray(field?.rows)) {
        return field.rows;
    }

    if (Array.isArray(field?.settings?.rows)) {
        return field.settings.rows;
    }

    return [];
};

const getActiveSubFieldReferenceMap = (field) => {
    const referencesByHandle = new Map();

    forEachFieldInRows(getActiveSubFieldRows(field), (subField) => {
        const handle = getFieldHandle(subField);

        if (!handle) {
            return;
        }

        const reference = String(subField?.reference || '').trim();

        if (reference) {
            referencesByHandle.set(handle, reference);
        }
    });

    return referencesByHandle;
};

const mergeActiveSubFieldReferences = (rows, referencesByHandle, options = {}) => {
    const { forceNew = false } = options;

    if (!Array.isArray(rows)) {
        return rows;
    }

    return mapNestedRows(rows, (subField) => {
        const handle = getFieldHandle(subField);
        const activeReference = handle ? referencesByHandle.get(handle) : null;

        if (activeReference) {
            return {
                ...subField,
                reference: activeReference,
            };
        }

        return assignFieldReferences(subField, { forceNew });
    });
};

const ensureUniqueFieldReferencesInForm = (pages = []) => {
    const seenReferences = new Set();

    const ensureFieldReference = (field) => {
        if (!field || typeof field !== 'object') {
            return;
        }

        const existingReference = String(field.reference || '').trim();

        if (!existingReference || seenReferences.has(existingReference)) {
            field.reference = createFieldReference();
        }

        seenReferences.add(String(field.reference || '').trim());

        forEachFieldInLayoutMaps(field.layouts, ensureFieldReference);
        forEachFieldInLayoutMaps(field?.settings?.layouts, ensureFieldReference);
    };

    (pages || []).forEach((page) => {
        forEachFieldInRows(page?.rows, ensureFieldReference);
    });
};

const assignFieldReferences = (field, options = {}) => {
    if (!field || typeof field !== 'object') {
        return field;
    }

    const { forceNew = false } = options;
    let nextField = { ...field };
    const existingReference = String(nextField.reference || '').trim();

    if (forceNew || !existingReference) {
        nextField.reference = createFieldReference();
    }

    if (Array.isArray(nextField.rows)) {
        nextField = syncNestedRows(nextField, mapNestedRows(nextField.rows, (childField) => {
            return assignFieldReferences(childField, options);
        }));
    } else if (Array.isArray(nextField?.settings?.rows)) {
        nextField = syncNestedRows(nextField, mapNestedRows(nextField.settings.rows, (childField) => {
            return assignFieldReferences(childField, options);
        }));
    }

    // Date/Time and other fixed parent fields keep per-display-type sub-field rows in `layouts`.
    // Duplication only refreshed active `rows` previously, so saving field settings could
    // restore stale references from the copied layout variants.
    if (isLayoutRowsMap(nextField.layouts)) {
        nextField = syncLayoutMaps(nextField, mapLayoutRowsMap(nextField.layouts, (childField) => {
            return assignFieldReferences(childField, options);
        }));
    } else if (isLayoutRowsMap(nextField?.settings?.layouts)) {
        nextField = syncLayoutMaps(nextField, mapLayoutRowsMap(nextField.settings.layouts, (childField) => {
            return assignFieldReferences(childField, options);
        }));
    }

    return nextField;
};

const remapFieldReferencesInField = (field, referenceMap = {}) => {
    if (!field || typeof field !== 'object') {
        return field;
    }

    let nextField = { ...field };
    const reference = String(nextField.reference || '').trim();

    if (reference && referenceMap[reference]) {
        nextField.reference = referenceMap[reference];
    }

    if (Array.isArray(nextField.rows)) {
        nextField = syncNestedRows(nextField, mapNestedRows(nextField.rows, (childField) => {
            return remapFieldReferencesInField(childField, referenceMap);
        }));
    } else if (Array.isArray(nextField?.settings?.rows)) {
        nextField = syncNestedRows(nextField, mapNestedRows(nextField.settings.rows, (childField) => {
            return remapFieldReferencesInField(childField, referenceMap);
        }));
    }

    if (isLayoutRowsMap(nextField.layouts)) {
        nextField = syncLayoutMaps(nextField, mapLayoutRowsMap(nextField.layouts, (childField) => {
            return remapFieldReferencesInField(childField, referenceMap);
        }));
    } else if (isLayoutRowsMap(nextField?.settings?.layouts)) {
        nextField = syncLayoutMaps(nextField, mapLayoutRowsMap(nextField.settings.layouts, (childField) => {
            return remapFieldReferencesInField(childField, referenceMap);
        }));
    }

    return nextField;
};

export {
    assignFieldReferences,
    createFieldReference,
    ensureUniqueFieldReferencesInForm,
    forEachFieldInLayoutMaps,
    forEachFieldInRows,
    getActiveSubFieldReferenceMap,
    mergeActiveSubFieldReferences,
    remapFieldReferencesInField,
};
