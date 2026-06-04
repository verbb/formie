import { stableSerialize } from '@form-builder/hooks/useUnloadWarning';

function normalizeFieldEditorValues(values) {
    if (!values || typeof values !== 'object' || Array.isArray(values)) {
        return values;
    }

    const normalized = { ...values };
    delete normalized._isNew;

    return normalized;
}

function serializeFieldEditorState(values) {
    return stableSerialize(normalizeFieldEditorValues(values));
}

function getFieldEditorValues(form, fallbackField) {
    if (form?.store?.state?.values) {
        return form.store.state.values;
    }

    return fallbackField;
}

function fieldEditorHasUnsavedChanges(baselineSnapshot, currentValues) {
    if (!baselineSnapshot) {
        return false;
    }

    return serializeFieldEditorState(currentValues) !== baselineSnapshot;
}

function isFieldEditorDirty(baselineSnapshot, currentValues) {
    return fieldEditorHasUnsavedChanges(baselineSnapshot, currentValues);
}

function confirmFieldEditorDismiss({
    isNew = false,
    isDirty = false,
    fieldLabel = '',
} = {}) {
    if (!isDirty) {
        return true;
    }

    if (isNew) {
        return window.confirm(Craft.t('formie', 'Close without saving? This new field will be removed.'));
    }

    return window.confirm(Craft.t('formie', 'Discard unsaved changes to "{name}"?', {
        name: fieldLabel,
    }));
}

export {
    fieldEditorHasUnsavedChanges,
    getFieldEditorValues,
    isFieldEditorDirty,
    confirmFieldEditorDismiss,
    normalizeFieldEditorValues,
    serializeFieldEditorState,
    stableSerialize,
};
