import { cloneDeep } from 'lodash-es';

import {
    getActiveSubFieldReferenceMap,
    mergeActiveSubFieldReferences,
} from './fieldReferences';

const getByPath = (source, path) => {
    if (!source || typeof source !== 'object' || !path) {
        return undefined;
    }

    if (Object.prototype.hasOwnProperty.call(source, path)) {
        return source[path];
    }

    return path.split('.').reduce((acc, key) => {
        if (!acc || typeof acc !== 'object') {
            return undefined;
        }

        return acc[key];
    }, source);
};

const setByPath = (source, path, value) => {
    const nextSource = { ...source };
    const segments = String(path || '').split('.').filter(Boolean);

    if (!segments.length) {
        return nextSource;
    }

    let cursor = nextSource;

    segments.slice(0, -1).forEach((segment) => {
        cursor[segment] = {
            ...(cursor[segment] && typeof cursor[segment] === 'object' ? cursor[segment] : {}),
        };
        cursor = cursor[segment];
    });

    cursor[segments[segments.length - 1]] = value;

    return nextSource;
};

const getDisplayType = (field) => {
    if (!field || typeof field !== 'object') {
        return null;
    }

    if (typeof field.displayType === 'string') {
        return field.displayType;
    }

    if (typeof field?.settings?.displayType === 'string') {
        return field.settings.displayType;
    }

    return null;
};

const getCollectMode = (field) => {
    if (!field || typeof field !== 'object') {
        return null;
    }

    if (typeof field.collectMode === 'string') {
        return field.collectMode;
    }

    if (typeof field?.settings?.collectMode === 'string') {
        return field.settings.collectMode;
    }

    return null;
};

const getVariantKeyForDisplayType = (displayType, collectMode = null) => {
    if (!displayType) {
        return null;
    }

    if (displayType === 'datePicker') {
        return collectMode === 'range' ? 'calendarRange' : 'calendar';
    }

    return displayType;
};

const getLayouts = (field) => {
    if (!field || typeof field !== 'object') {
        return null;
    }

    if (field.layouts && typeof field.layouts === 'object') {
        return field.layouts;
    }

    if (field?.settings?.layouts && typeof field.settings.layouts === 'object') {
        return field.settings.layouts;
    }

    return null;
};

const getVariantRows = (layouts, variantKey) => {
    if (!layouts || !variantKey) {
        return null;
    }

    return getByPath(layouts, variantKey)
        ?? getByPath(layouts, `layouts.${variantKey}`)
        ?? null;
};

const getFieldLayoutVariantRows = (field, variantKey) => {
    if (!field || !variantKey) {
        return null;
    }

    const layoutRows = getVariantRows(getLayouts(field), variantKey);

    if (Array.isArray(layoutRows)) {
        return layoutRows;
    }

    return getByPath(field, `layouts.${variantKey}`)
        ?? getByPath(field?.settings, `layouts.${variantKey}`)
        ?? null;
};

const getDefaultRows = (field) => {
    if (Array.isArray(field?.rows)) {
        return field.rows;
    }

    if (Array.isArray(field?.settings?.rows)) {
        return field.settings.rows;
    }

    return [];
};

const applySyncedRows = (field, syncedRows) => {
    const nextField = {
        ...field,
        rows: syncedRows,
    };

    if (nextField.settings && typeof nextField.settings === 'object') {
        nextField.settings = {
            ...nextField.settings,
            rows: syncedRows,
        };
    }

    return nextField;
};

const syncLayoutVariantRows = (field, variantKey, syncedRows) => {
    if (!variantKey) {
        return field;
    }

    let nextField = { ...field };
    const clonedRows = cloneDeep(syncedRows);
    const candidateKeys = [variantKey, `layouts.${variantKey}`];
    const layouts = getLayouts(nextField);

    if (layouts && typeof layouts === 'object') {
        const nextLayouts = { ...layouts };
        let updated = false;

        candidateKeys.forEach((layoutKey) => {
            if (Array.isArray(nextLayouts[layoutKey])) {
                nextLayouts[layoutKey] = cloneDeep(clonedRows);
                updated = true;
            }
        });

        if (updated) {
            nextField.layouts = nextLayouts;

            if (nextField.settings && typeof nextField.settings === 'object') {
                nextField.settings = {
                    ...nextField.settings,
                    layouts: nextLayouts,
                };
            }
        }
    }

    candidateKeys.forEach((layoutKey) => {
        const dottedPath = layoutKey.includes('.') ? layoutKey : `layouts.${layoutKey}`;

        if (Array.isArray(getByPath(nextField?.settings, dottedPath))) {
            nextField.settings = setByPath(nextField.settings || {}, dottedPath, cloneDeep(clonedRows));
        }
    });

    return nextField;
};

const resolveContainerRows = (field, fieldType = null) => {
    const displayType = getDisplayType(field);
    const collectMode = getCollectMode(field);
    const variantKey = getVariantKeyForDisplayType(displayType, collectMode);
    const fieldRows = getFieldLayoutVariantRows(field, variantKey);

    if (Array.isArray(fieldRows)) {
        return {
            rows: fieldRows,
            source: 'field',
        };
    }

    const typeRows = getVariantRows(fieldType?.data?.nestedLayoutBuilder?.layouts, variantKey);
    if (Array.isArray(typeRows)) {
        return {
            rows: typeRows,
            source: 'template',
        };
    }

    return {
        rows: getDefaultRows(field),
        source: 'field',
    };
};

const syncContainerRowsFromVariant = (field, fieldType = null) => {
    const displayType = getDisplayType(field);
    const collectMode = getCollectMode(field);
    const variantKey = getVariantKeyForDisplayType(displayType, collectMode);
    const { rows: resolvedRows, source } = resolveContainerRows(field, fieldType);

    if (!Array.isArray(resolvedRows) || !resolvedRows.length) {
        return field;
    }

    const activeSubFieldReferences = getActiveSubFieldReferenceMap(field);
    const syncedRows = mergeActiveSubFieldReferences(
        cloneDeep(resolvedRows),
        activeSubFieldReferences,
        { forceNew: source === 'template' },
    );

    let nextField = applySyncedRows(field, syncedRows);

    if (source === 'field') {
        nextField = syncLayoutVariantRows(nextField, variantKey, syncedRows);
    }

    return nextField;
};

export {
    resolveContainerRows,
    syncContainerRowsFromVariant,
};
