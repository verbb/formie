import { getFieldHandle } from './duplicateField';

const getMergedHandleField = (field, reservedHandles = [], handleOverrides = null) => {
    const mergedReservedHandles = [...new Set([...(field?.reservedHandles || []), ...(reservedHandles || [])])];

    return {
        ...field,
        ...(handleOverrides || {}),
        reservedHandles: mergedReservedHandles,
    };
};

const injectReservedHandlesIntoSchema = (schema, reservedHandles = [], handleOverrides = null) => {
    if (Array.isArray(schema)) {
        return schema.map((node) => {
            return injectReservedHandlesIntoSchema(node, reservedHandles, handleOverrides);
        });
    }

    if (!schema || typeof schema !== 'object') {
        return schema;
    }

    const nextSchema = { ...schema };

    if (nextSchema.$field === 'handle') {
        return getMergedHandleField(nextSchema, reservedHandles, handleOverrides);
    }

    if (Array.isArray(nextSchema.children)) {
        nextSchema.children = injectReservedHandlesIntoSchema(nextSchema.children, reservedHandles, handleOverrides);
    }

    if (Array.isArray(nextSchema.schema)) {
        nextSchema.schema = injectReservedHandlesIntoSchema(nextSchema.schema, reservedHandles, handleOverrides);
    }

    return nextSchema;
};

const injectReservedHandlesIntoSchemaIndex = (schemaIndex, reservedHandles = [], handleOverrides = null) => {
    if (!schemaIndex || typeof schemaIndex !== 'object') {
        return schemaIndex;
    }

    return {
        ...schemaIndex,
        schema: injectReservedHandlesIntoSchema(schemaIndex.schema || [], reservedHandles, handleOverrides),
        fieldEntries: (schemaIndex.fieldEntries || []).map((entry) => {
            if (entry?.field?.$field !== 'handle') {
                return entry;
            }

            return {
                ...entry,
                field: getMergedHandleField(entry.field, reservedHandles, handleOverrides),
            };
        }),
    };
};

const collectTopLevelReservedHandles = (pages = [], excludedPath = {}) => {
    const {
        pageIndex: excludedPageIndex,
        rowIndex: excludedRowIndex,
        fieldIndex: excludedFieldIndex,
    } = excludedPath;

    const handles = [];

    (pages || []).forEach((page, pageIndex) => {
        (page?.rows || []).forEach((row, rowIndex) => {
            (row?.fields || []).forEach((field, fieldIndex) => {
                if (
                    pageIndex === excludedPageIndex
                    && rowIndex === excludedRowIndex
                    && fieldIndex === excludedFieldIndex
                ) {
                    return;
                }

                const handle = getFieldHandle(field);
                if (handle) {
                    handles.push(handle);
                }
            });
        });
    });

    return handles;
};

const collectNestedReservedHandles = (parentRows = [], excludedPath = {}) => {
    const {
        rowIndex: excludedRowIndex,
        fieldIndex: excludedFieldIndex,
    } = excludedPath;

    const handles = [];

    (parentRows || []).forEach((row, rowIndex) => {
        (row?.fields || []).forEach((field, fieldIndex) => {
            if (rowIndex === excludedRowIndex && fieldIndex === excludedFieldIndex) {
                return;
            }

            const handle = getFieldHandle(field);
            if (handle) {
                handles.push(handle);
            }
        });
    });

    return handles;
};

const collectNotificationReservedHandles = (notifications = [], excludedNotificationId = null) => {
    return (notifications || [])
        .filter((notification) => {
            return excludedNotificationId ? notification?._id !== excludedNotificationId : true;
        })
        .map((notification) => { return notification?.handle; })
        .filter(Boolean);
};

export {
    injectReservedHandlesIntoSchema,
    injectReservedHandlesIntoSchemaIndex,
    collectTopLevelReservedHandles,
    collectNestedReservedHandles,
    collectNotificationReservedHandles,
};
