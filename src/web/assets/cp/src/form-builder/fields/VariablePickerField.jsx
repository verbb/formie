import { useMemo, useRef, useCallback } from 'react';
import { TiptapInput } from '@verbb/plugin-kit-react/components';
import { useEngineField, FieldLayout } from '@verbb/plugin-kit-react/forms';
import { useVariableCategoriesContext } from '@utils/VariableCategoriesProvider';
import { VariableDropdown } from '@form-builder/fields/variable-picker';
import {
    useVariableTagConfigureSession,
    VariableTagConfigureOverlay,
} from '@form-builder/fields/variable-picker/VariableTagConfigureOverlay';
import { expandVariableHydrateAliases } from '@form-builder/fields/variable-picker/variablePickerUtils';
import { readPkTiptapChangeValue, normalizePkTiptapStoreValue, usePkTiptapEditor } from '@form-builder/fields/utils/pkTiptapField';

/**
 * Formie-owned SchemaForm `$field: 'variablePicker'` — was a kit v1 builtin.
 * Stock `pk-tiptap-input` owns editing; Formie owns insert + tag configure chrome.
 */
export function VariablePickerField({ form, field }) {
    const { value, setValue, errors } = useEngineField(form, field.name);
    const hostRef = useRef(null);
    const editor = usePkTiptapEditor(hostRef);
    // Document capture of `pk-variable-tag-configure` — more reliable than the Lit
    // property callback when @lit/react / chunk boundaries skip function props.
    const { session, setSession, closeSession } = useVariableTagConfigureSession(hostRef, editor);
    const {
        getVariableCategories,
        variableCategoryLabels,
        variableCategoryOrder,
        variableTransformerRegistry,
        renderVariableConfigureSection,
        resolveVariableTagLabel,
    } = useVariableCategoriesContext();
    const { variableCategories, variableConfig } = field;

    const resolvedVariableCategories = useMemo(() => {
        if (variableCategories) {
            return variableCategories;
        }

        if (!variableConfig || !getVariableCategories) {
            return undefined;
        }

        return getVariableCategories(variableConfig, { form });
    }, [variableCategories, variableConfig, form, getVariableCategories]);

    // TipTap chip hydrate only — picker UI keeps canonical parent-scoped values.
    const editorVariableCategories = useMemo(
        () => expandVariableHydrateAliases(resolvedVariableCategories ?? {}),
        [resolvedVariableCategories],
    );

    const hasVariables = Object.values(resolvedVariableCategories ?? {}).some(
        (items) => Array.isArray(items) && items.length > 0,
    );

    const openConfigureSession = useCallback((detail) => {
        if (!detail?.updateAttributes || !detail?.anchor) {
            return;
        }

        setSession({
            attrs: { ...(detail.attrs ?? {}) },
            anchor: detail.anchor,
            updateAttributes: detail.updateAttributes,
            deleteNode: detail.deleteNode,
        });
    }, [setSession]);

    return (
        <FieldLayout
            name={field.name}
            label={field.label}
            instructions={field.instructions}
            warning={field.warning}
            required={field.required}
            errors={errors}
        >
            <div className="relative">
                <TiptapInput
                    ref={hostRef}
                    value={normalizePkTiptapStoreValue(value)}
                    variableCategories={editorVariableCategories}
                    variableTagConfigure={openConfigureSession}
                    onPkVariableTagConfigure={(event) => {
                        openConfigureSession(event?.detail);
                    }}
                    onPkChange={(event) => {
                        const next = readPkTiptapChangeValue(event);
                        if (next === normalizePkTiptapStoreValue(value)) {
                            return;
                        }
                        setValue(next);
                    }}
                    placeholder={field.placeholder}
                    // Host CSS var reaches shadow ProseMirror (light `[&_.shell]` cannot).
                    className={hasVariables ? '[--pk-tiptap-input-padding-inline-end:38px]' : ''}
                    {...(field.disabled ? { disabled: true } : {})}
                    invalid={errors.length > 0}
                />
                {hasVariables ? (
                    <VariableDropdown
                        editor={editor}
                        variableCategories={resolvedVariableCategories}
                        variableCategoryLabels={variableCategoryLabels}
                        variableCategoryOrder={variableCategoryOrder}
                        triggerMode="input"
                    />
                ) : null}
                {session ? (
                    <VariableTagConfigureOverlay
                        session={session}
                        onClose={closeSession}
                        variableCategories={resolvedVariableCategories}
                        variableCategoryLabels={variableCategoryLabels}
                        variableCategoryOrder={variableCategoryOrder}
                        variableTransformerRegistry={variableTransformerRegistry}
                        renderVariableConfigureSection={renderVariableConfigureSection}
                        resolveVariableTagLabel={resolveVariableTagLabel}
                    />
                ) : null}
            </div>
        </FieldLayout>
    );
}
