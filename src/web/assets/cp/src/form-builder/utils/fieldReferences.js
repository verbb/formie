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

    return nextField;
};

export {
    assignFieldReferences,
    createFieldReference,
    remapFieldReferencesInField,
};
