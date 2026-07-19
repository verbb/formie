import { useEffect, useMemo, useRef } from 'react';

import { SchemaFormEngine, useSchemaFormEngine } from '@verbb/plugin-kit-react/forms';

import { normalizeSelectFieldDefaults } from '@defaults/utils/defaultsEditorState';

export const SchemaDefaultsPanel = ({
    panelKey, schema, schemaIndex, values, onChange,
}) => {
    const onChangeRef = useRef(onChange);
    onChangeRef.current = onChange;

    // Re-seed only when the panel identity changes — not when parent echoes our onChange
    // back as `values`. `useSchemaFormEngine` returns a new `form` object every render;
    // putting it (or live `values`) in a reset effect causes reset→notify→setState (#185).
    const seedValues = useMemo(() => {
        return normalizeSelectFieldDefaults(schema, values || {});
        // eslint-disable-next-line react-hooks/exhaustive-deps -- intentional: panelKey/schema only
    }, [panelKey, schema]);

    const form = useSchemaFormEngine({
        schema: schema || [],
        schemaIndex: schemaIndex || null,
        defaultValues: seedValues,
        onChange: (data) => {
            onChangeRef.current(data || {});
        },
    });

    useEffect(() => {
        form.store.reset(seedValues);
    }, [panelKey, seedValues, form.store]);

    if (!schema?.length) {
        return null;
    }

    return <SchemaFormEngine form={form} withoutForm className="space-y-4" />;
};
