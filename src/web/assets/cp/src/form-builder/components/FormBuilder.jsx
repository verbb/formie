import { useRef } from 'react';

import { FormBuilderHeader } from './FormBuilderHeader.jsx';
import { FormBuilderContent } from './FormBuilderContent.jsx';
import { FormBuilderErrorBoundary } from './FormBuilderErrorBoundary.jsx';
import { useKeyboardShortcuts } from '@verbb/plugin-kit-react/hooks/useKeyboardShortcuts';
import { FormBuilderAppProvider } from '@form-builder/contexts/FormBuilderAppContext';
import { useFormBuilderDocumentTitle } from '@form-builder/hooks/useFormBuilderDocumentTitle';
import useAppStore from '@form-builder/hooks/useAppStore';

function FormBuilderInner({
    initialData,
    schema = [],
    schemaIndex = null,
}) {
    const formRef = useRef(null);
    const setSaveAction = useAppStore((state) => { return state.setSaveAction; });
    useFormBuilderDocumentTitle();

    // Set up keyboard shortcuts
    useKeyboardShortcuts({
        onSave: () => {
            setSaveAction('save');
            formRef.current?.handleSubmit?.();
        },
    });

    return (
        <FormBuilderAppProvider>
            <FormBuilderHeader formRef={formRef} />

            <FormBuilderContent
                formRef={formRef}
                initialData={initialData}
                schema={schema}
                schemaIndex={schemaIndex}
            />
        </FormBuilderAppProvider>
    );
}

function FormBuilder(props) {
    return (
        <FormBuilderErrorBoundary>
            <FormBuilderInner {...props} />
        </FormBuilderErrorBoundary>
    );
}

export { FormBuilder };
