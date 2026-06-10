import { cloneDeep } from 'lodash-es';

import { assignFieldReferences } from './fieldReferences';

const FIELD_IDENTITY_KEYS = ['id', 'fieldId', 'layoutId', 'pageId', 'rowId', 'uid', 'syncId', 'reference'];

const getFieldHandle = (field) => {
    return field?.handle || field?.settings?.handle || null;
};

const collectFieldHandlesFromRows = (rows = [], handles = []) => {
    (rows || []).forEach((row) => {
        (row?.fields || []).forEach((field) => {
            const handle = getFieldHandle(field);

            if (handle) {
                handles.push(handle);
            }

            if (Array.isArray(field?.rows)) {
                collectFieldHandlesFromRows(field.rows, handles);
            }
        });
    });

    return handles;
};

const getCaseInsensitiveUniqueHandle = (baseHandle, existingHandles = []) => {
    const normalizedExistingHandles = new Set((existingHandles || [])
        .filter(Boolean)
        .map((handle) => { return String(handle).toLowerCase(); }));

    const trimmedBaseHandle = String(baseHandle || '').trim();
    const resolvedBaseHandle = trimmedBaseHandle || 'field';

    let candidateHandle = resolvedBaseHandle;
    let suffix = 1;

    while (normalizedExistingHandles.has(candidateHandle.toLowerCase())) {
        candidateHandle = `${resolvedBaseHandle}${suffix}`;
        suffix += 1;
    }

    return candidateHandle;
};

const buildDuplicatedFieldData = (field, handle) => {
    const duplicatedField = cloneDeep(field);

    FIELD_IDENTITY_KEYS.forEach((key) => {
        delete duplicatedField[key];
    });

    if (duplicatedField.settings && typeof duplicatedField.settings === 'object') {
        duplicatedField.settings = { ...duplicatedField.settings };

        FIELD_IDENTITY_KEYS.forEach((key) => {
            delete duplicatedField.settings[key];
        });

        duplicatedField.settings.handle = handle;
    }

    duplicatedField.handle = handle;
    duplicatedField._isNew = false;

    return assignFieldReferences(duplicatedField, { forceNew: true });
};

const detachSyncedFieldData = (field) => {
    if (!field || typeof field !== 'object') {
        return field;
    }

    const detachedField = {
        ...field,
        fieldId: null,
        syncId: null,
        isSynced: false,
        usageCount: 1,
    };

    if (Array.isArray(field.rows)) {
        detachedField.rows = field.rows.map((row) => {
            return {
                ...row,
                fields: Array.isArray(row?.fields)
                    ? row.fields.map((nestedField) => { return detachSyncedFieldData(nestedField); })
                    : row?.fields,
            };
        });
    }

    return detachedField;
};

export {
    getFieldHandle,
    collectFieldHandlesFromRows,
    getCaseInsensitiveUniqueHandle,
    buildDuplicatedFieldData,
    detachSyncedFieldData,
};
