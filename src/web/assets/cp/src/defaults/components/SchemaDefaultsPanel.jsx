import { useEffect, useMemo, useRef } from 'react';

import { SchemaFormEngine, useSchemaFormEngine } from '@verbb/plugin-kit-react/forms';

import { normalizeSelectFieldDefaults } from '@defaults/utils/defaultsEditorState';

export const SchemaDefaultsPanel = ({
    panelKey, schema, schemaIndex, values, onChange,
}) => {
    const hasHandledInitialChangeRef = useRef(false);
    const initialValues = useMemo(() => {
        return normalizeSelectFieldDefaults(schema, values || {});
    }, [panelKey, schema, values]);

    const form = useSchemaFormEngine({
        schema: schema || [],
        schemaIndex: schemaIndex || null,
        defaultValues: initialValues,
        onChange: (data) => {
            if (!hasHandledInitialChangeRef.current) {
                hasHandledInitialChangeRef.current = true;

                if (JSON.stringify(data || {}) === JSON.stringify(initialValues || {})) {
                    return;
                }
            }

            onChange(data || {});
        },
    });

    useEffect(() => {
        hasHandledInitialChangeRef.current = false;
        form.store.reset(initialValues);
    }, [panelKey, form, initialValues]);

    if (!schema?.length) {
        return null;
    }

    return <SchemaFormEngine form={form} withoutForm className="space-y-4" />;
};
