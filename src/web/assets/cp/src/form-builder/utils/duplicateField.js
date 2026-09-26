import { cloneDeep } from 'lodash-es';

import { generateHandle } from '@verbb/plugin-kit-core';

import { assignFieldReferences } from './fieldReferences';

const FIELD_IDENTITY_KEYS = ['id', 'definitionId', 'layoutId', 'pageId', 'rowId', 'uid', 'definitionUid', 'reference', 'fieldId', 'syncId', 'definitionToken', 'nestedLayoutId'];
const BUILDER_IDENTITY_RANDOM_LENGTH = 15;

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

            if (Array.isArray(field?.settings?.rows)) {
                collectFieldHandlesFromRows(field.settings.rows, handles);
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

const randomAlphanumeric = (length) => {
    const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let result = '';

    for (let index = 0; index < length; index += 1) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
    }

    return result;
};

const getBuilderHandlePrefix = (fieldType) => {
    const templateHandle = getFieldHandle(fieldType?.newField);

    if (templateHandle) {
        return templateHandle.replace(/[a-zA-Z0-9]{15}$/, '') || templateHandle;
    }

    return generateHandle(fieldType?.label || Craft.t('formie', 'Field'));
};

const generateBuilderFieldHandle = (fieldType, existingHandles = []) => {
    const prefix = getBuilderHandlePrefix(fieldType);

    for (let attempt = 0; attempt < 50; attempt += 1) {
        const candidate = `${prefix}${randomAlphanumeric(BUILDER_IDENTITY_RANDOM_LENGTH)}`;
        const uniqueHandle = getCaseInsensitiveUniqueHandle(candidate, existingHandles);

        if (uniqueHandle.toLowerCase() === candidate.toLowerCase()) {
            return uniqueHandle;
        }
    }

    return getCaseInsensitiveUniqueHandle(
        `${prefix}${randomAlphanumeric(BUILDER_IDENTITY_RANDOM_LENGTH)}`,
        existingHandles,
    );
};

const generateBuilderFieldLabel = (fieldType) => {
    const prefix = `${fieldType?.label || Craft.t('formie', 'Field')} `;

    return `${prefix}${randomAlphanumeric(BUILDER_IDENTITY_RANDOM_LENGTH)}`;
};

const assignFieldHandle = (field, handle) => {
    const nextField = {
        ...field,
        handle,
    };

    if (nextField.settings && typeof nextField.settings === 'object') {
        nextField.settings = {
            ...nextField.settings,
            handle,
        };
    }

    return nextField;
};

const assignUniqueFieldHandle = (field, existingHandles = []) => {
    const sourceHandle = getFieldHandle(field);
    const trimmedLabel = String(field?.label || '').trim();
    const baseHandle = sourceHandle || (trimmedLabel ? generateHandle(trimmedLabel) : '');

    if (!baseHandle) {
        return field;
    }

    const uniqueHandle = getCaseInsensitiveUniqueHandle(baseHandle, existingHandles);

    return assignFieldHandle(field, uniqueHandle);
};

const assignBuilderFieldIdentity = (field, existingHandles = [], fieldType = null) => {
    const handle = generateBuilderFieldHandle(fieldType, existingHandles);
    const nextField = assignFieldHandle(field, handle);

    nextField.label = generateBuilderFieldLabel(fieldType);
    existingHandles.push(handle);

    return nextField;
};

const prepareNewFieldForInsert = (fieldData, existingHandles = [], fieldType = null) => {
    const clonedField = cloneDeep(fieldData);

    if (fieldType?.isBuilderField) {
        return assignBuilderFieldIdentity(clonedField, existingHandles, fieldType);
    }

    const nextField = assignUniqueFieldHandle(clonedField, existingHandles);
    const handle = getFieldHandle(nextField);

    if (handle) {
        existingHandles.push(handle);
    }

    return nextField;
};

const buildDuplicatedFieldData = (field, existingHandles = [], options = {}) => {
    const { fieldType = null } = options;
    let duplicatedField = cloneDeep(field);

    const clearIdentities = (value) => {
        if (!value || typeof value !== 'object') return;
        FIELD_IDENTITY_KEYS.forEach((key) => { delete value[key]; });
        value.isSynced = false;
        if (value.settings) clearIdentities(value.settings);
        const clearRows = (rows) => {
            (rows || []).forEach((row) => {
                ['id', 'uid', 'layoutId', 'pageId'].forEach((key) => { delete row[key]; });
                (row.fields || []).forEach(clearIdentities);
            });
        };
        clearRows(value.rows);
        Object.values(value.layouts || {}).forEach(clearRows);
    };
    clearIdentities(duplicatedField);

    if (fieldType?.isBuilderField) {
        duplicatedField = assignBuilderFieldIdentity(duplicatedField, existingHandles, fieldType);
    } else {
        const sourceHandle = getFieldHandle(field);
        const baseHandle = sourceHandle || generateHandle(field?.label || Craft.t('formie', 'Field'));
        const uniqueHandle = getCaseInsensitiveUniqueHandle(baseHandle, existingHandles);

        duplicatedField = assignFieldHandle(duplicatedField, uniqueHandle);
    }

    duplicatedField._isNew = false;

    duplicatedField = assignFieldReferences(duplicatedField, { forceNew: true });
    const references = {};
    const collect = (source, copy) => {
        if (!source || !copy || typeof source !== 'object' || typeof copy !== 'object') return;
        if (source.reference && copy.reference && !references[source.reference]) references[source.reference] = copy.reference;
        Object.keys(source).forEach((key) => collect(source[key], copy[key]));
    };
    collect(field, duplicatedField);
    const remap = (value) => {
        if (typeof value === 'string') {
            return references[value] || value.replace(/\{field:([^:;}|]+)(?=[:;}|])/g,
                (token, reference) => references[reference] ? `{field:${references[reference]}` : token);
        }
        if (Array.isArray(value)) return value.map(remap);
        if (value && typeof value === 'object') return Object.fromEntries(Object.entries(value).map(([key, item]) => [references[key] || key, remap(item)]));
        return value;
    };
    return remap(duplicatedField);
};

const detachSyncedFieldData = (field) => {
    if (!field || typeof field !== 'object') {
        return field;
    }

    const detachedField = {
        ...field,
        definitionId: null,
        definitionUid: null,
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
    assignUniqueFieldHandle,
    assignBuilderFieldIdentity,
    prepareNewFieldForInsert,
    buildDuplicatedFieldData,
    detachSyncedFieldData,
};
