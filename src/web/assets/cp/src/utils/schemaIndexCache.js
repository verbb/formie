import { createSchemaFieldIndex } from '@verbb/plugin-kit-forms';

const hasErrorValue = (value) => {
    if (Array.isArray(value)) {
        return value.length > 0;
    }
    if (!value) {
        return false;
    }
    if (typeof value === 'object') {
        if (Array.isArray(value.errors)) {
            return value.errors.length > 0;
        }
    }
    return Boolean(value);
};

const indexCache = new WeakMap();

export const getSchemaFieldIndex = (node) => {
    if (!node || (typeof node !== 'object' && !Array.isArray(node))) {
        return createSchemaFieldIndex([]);
    }

    const cached = indexCache.get(node);
    if (cached) {
        return cached;
    }

    const index = createSchemaFieldIndex(node);
    indexCache.set(node, index);
    return index;
};

export const hasSchemaErrorsCached = (errors, node) => {
    if (!errors || !node) {
        return false;
    }

    const { fieldNames } = getSchemaFieldIndex(node);
    return fieldNames.some((fieldName) => hasErrorValue(errors[fieldName]));
};
