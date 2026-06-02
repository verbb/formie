import { useCallback, useMemo, useRef } from 'react';
import { get as getValue } from 'lodash-es';

import { extractFields } from '@verbb/plugin-kit-react/utils/schema';
import { buildUniqueHandleFromSource } from '@verbb/plugin-kit-react/utils';

const useHandleSyncOnChange = (schema) => {
    const lastSourceValuesRef = useRef({});
    const lastGeneratedHandlesRef = useRef({});

    const handleFields = useMemo(() => {
        if (!schema) {
            return [];
        }

        return extractFields(schema).filter((field) => {
            return field.$field === 'handle'
                && field.name
                && field.source
                && field.syncFromSource !== false;
        });
    }, [schema]);

    return useCallback((values, form) => {
        if (!form?.setFieldValue || handleFields.length === 0) {
            return;
        }

        handleFields.forEach((handleField) => {
            const persistedIdPath = handleField.persistedIdPath ?? 'id';
            const persistedId = getValue(values, persistedIdPath);

            if (persistedId !== null && persistedId !== undefined) {
                return;
            }

            const sourceValue = getValue(values, handleField.source);
            const lastSourceValue = lastSourceValuesRef.current[handleField.name];

            if (sourceValue === lastSourceValue) {
                return;
            }

            lastSourceValuesRef.current[handleField.name] = sourceValue;
            const currentHandle = getValue(values, handleField.name);
            const nextHandle = buildUniqueHandleFromSource({
                sourceValue,
                values,
                reservedHandles: handleField.reservedHandles || [],
                reservedFieldValues: handleField.reservedFieldValues || [],
                maxLength: handleField.maxLength,
            });

            const lastGenerated = lastGeneratedHandlesRef.current[handleField.name];
            const shouldUpdate = !currentHandle || currentHandle === lastGenerated || currentHandle === nextHandle;

            if (!shouldUpdate) {
                return;
            }

            lastGeneratedHandlesRef.current[handleField.name] = nextHandle;
            form.setFieldValue(handleField.name, nextHandle);
        });
    }, [handleFields]);
};

export { useHandleSyncOnChange };
