import {
    useCallback, useEffect, useRef,
} from 'react';

import {
    confirmFieldEditorDismiss,
    getFieldEditorValues,
    isFieldEditorDirty,
    serializeFieldEditorState,
} from '@form-builder/utils/fieldEditorClose';

function getFieldEditorSessionKey(field) {
    return field?._id ?? field?.id ?? `${field?.type || 'field'}-new`;
}

/**
 * Dirty/new-field confirm for field-edit dismiss.
 *
 * `attemptDismiss` only returns whether close may proceed — it must NOT unmount
 * the dialog. Callers run side effects (onDismiss / onSave / onDelete) from
 * `pk-after-hide` so the kit exit animation can finish first.
 */
function useFieldEditorDismiss({
    field,
    fieldDisplayLabel,
    form,
    dismissAttemptRef,
    isBaselineReady = true,
}) {
    const baselineSnapshotRef = useRef(null);
    const formRef = useRef(form);
    const sessionKey = getFieldEditorSessionKey(field);

    formRef.current = form;

    useEffect(() => {
        baselineSnapshotRef.current = null;
    }, [sessionKey]);

    useEffect(() => {
        if (!isBaselineReady || !formRef.current?.store) {
            return undefined;
        }

        let cancelled = false;

        // Capture after child field mount effects (e.g. SelectField default normalization).
        const timer = window.setTimeout(() => {
            if (cancelled || !formRef.current?.store) {
                return;
            }

            baselineSnapshotRef.current = serializeFieldEditorState(
                getFieldEditorValues(formRef.current, field),
            );
        }, 0);

        return () => {
            cancelled = true;
            window.clearTimeout(timer);
        };
    }, [sessionKey, isBaselineReady, field]);

    const attemptDismiss = useCallback(() => {
        const isNew = Boolean(field?._isNew);
        const currentValues = getFieldEditorValues(formRef.current, field);
        const isDirty = isFieldEditorDirty(
            baselineSnapshotRef.current,
            currentValues,
        );

        return confirmFieldEditorDismiss({
            isNew,
            isDirty,
            fieldLabel: fieldDisplayLabel,
        });
    }, [field, fieldDisplayLabel]);

    useEffect(() => {
        if (!dismissAttemptRef) {
            return undefined;
        }

        dismissAttemptRef.current = attemptDismiss;

        return () => {
            dismissAttemptRef.current = null;
        };
    }, [attemptDismiss, dismissAttemptRef]);

    return {
        attemptDismiss,
    };
}

export { useFieldEditorDismiss };
