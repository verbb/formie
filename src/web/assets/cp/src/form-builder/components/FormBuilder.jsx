import { useRef } from 'react';

import { FormBuilderHeader } from './FormBuilderHeader.jsx';
import { FormBuilderContent } from './FormBuilderContent.jsx';
import { FormBuilderErrorBoundary } from './FormBuilderErrorBoundary.jsx';
import { useKeyboardShortcuts } from '@utils/useKeyboardShortcuts';
import { FormBuilderAppProvider } from '@form-builder/contexts/FormBuilderAppContext';
import { useFormBuilderDocumentTitle } from '@form-builder/hooks/useFormBuilderDocumentTitle';
import { useFormBuilderSiteCrumb } from '@form-builder/hooks/useFormBuilderSiteCrumb';
import useAppStore from '@form-builder/hooks/useAppStore';

function FormBuilderInner({
    initialData,
    schema = [],
    schemaIndex = null,
}) {
    const formRef = useRef(null);
    const setSaveAction = useAppStore((state) => { return state.setSaveAction; });
    const canEdit = useAppStore((state) => { return state.canEdit; });
    useFormBuilderDocumentTitle();
    useFormBuilderSiteCrumb(formRef);

    // Set up keyboard shortcuts
    useKeyboardShortcuts({
        onSave: canEdit
            ? () => {
                setSaveAction('save');
                formRef.current?.handleSubmit?.();
            }
            : undefined,
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
