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

const getDefaultRows = (field) => {
    if (Array.isArray(field?.rows)) {
        return field.rows;
    }

    if (Array.isArray(field?.settings?.rows)) {
        return field.settings.rows;
    }

    return [];
};

const resolveContainerRows = (field, fieldType = null) => {
    const displayType = getDisplayType(field);
    const collectMode = getCollectMode(field);
    const variantKey = getVariantKeyForDisplayType(displayType, collectMode);
    const fieldRows = getVariantRows(getLayouts(field), variantKey);

    if (Array.isArray(fieldRows)) {
        return fieldRows;
    }

    const typeRows = getVariantRows(fieldType?.data?.nestedLayoutBuilder?.layouts, variantKey);
    if (Array.isArray(typeRows)) {
        return typeRows;
    }

    return getDefaultRows(field);
};

const syncContainerRowsFromVariant = (field, fieldType = null) => {
    const resolvedRows = resolveContainerRows(field, fieldType);

    if (!Array.isArray(resolvedRows) || !resolvedRows.length) {
        return field;
    }

    const nextField = {
        ...field,
        rows: resolvedRows,
    };

    if (nextField.settings && typeof nextField.settings === 'object') {
        nextField.settings = {
            ...nextField.settings,
            rows: resolvedRows,
        };
    }

    return nextField;
};

export {
    resolveContainerRows,
    syncContainerRowsFromVariant,
};
