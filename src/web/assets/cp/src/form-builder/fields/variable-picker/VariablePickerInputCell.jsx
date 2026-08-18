import { useCallback, useMemo, useRef } from 'react';
import { TiptapInput } from '@verbb/plugin-kit-react/components';
import { cn } from '@verbb/plugin-kit-react/utils';
import { useVariableCategoriesContext } from '@utils/VariableCategoriesProvider';
import { VariableDropdown } from '@form-builder/fields/variable-picker';
import {
    useVariableTagConfigureSession,
    VariableTagConfigureOverlay,
} from '@form-builder/fields/variable-picker/VariableTagConfigureOverlay';
import { expandVariableHydrateAliases } from '@form-builder/fields/variable-picker/variablePickerUtils';
import {
    readPkTiptapChangeValue,
    normalizePkTiptapStoreValue,
    usePkTiptapEditor,
} from '@form-builder/fields/utils/pkTiptapField';

/**
 * Compact TipTap + Formie variable chrome for editable-table cells
 * (chips, insert +, configure overlay) — same ownership as VariablePickerField.
 *
 * `fitCell` (default true) flush-fills EditableTable rows. Pass `fitCell={false}`
 * for bordered single-line fields (e.g. integration mapping custom value).
 */
export function VariablePickerInputCell({
    value,
    onChange,
    isInvalid = false,
    variableCategories = {},
    variableCategoryLabels,
    variableCategoryOrder,
    variableTransformerRegistry,
    placeholder,
    fitCell = true,
    className = '',
    inputClassName = '',
}) {
    const hostRef = useRef(null);
    const editor = usePkTiptapEditor(hostRef);
    const { session, setSession, closeSession } = useVariableTagConfigureSession(hostRef, editor);
    const {
        renderVariableConfigureSection,
        resolveVariableTagLabel,
        variableCategoryLabels: contextLabels,
        variableCategoryOrder: contextOrder,
        variableTransformerRegistry: contextRegistry,
    } = useVariableCategoriesContext() || {};

    const resolvedLabels = variableCategoryLabels ?? contextLabels;
    const resolvedOrder = variableCategoryOrder ?? contextOrder;
    const resolvedRegistry = variableTransformerRegistry ?? contextRegistry ?? {};

    // TipTap chip hydrate only — picker UI keeps canonical parent-scoped values.
    const editorVariableCategories = useMemo(
        () => expandVariableHydrateAliases(variableCategories ?? {}),
        [variableCategories],
    );

    const hasVariables = Object.values(variableCategories ?? {}).some(
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

    const stored = normalizePkTiptapStoreValue(value);

    return (
        <div className={cn(
            fitCell ? 'relative h-full w-full' : 'relative w-full min-w-0',
            className,
        )}>
            <TiptapInput
                ref={hostRef}
                value={stored}
                variableCategories={editorVariableCategories}
                variableTagConfigure={openConfigureSession}
                onPkVariableTagConfigure={(event) => {
                    openConfigureSession(event?.detail);
                }}
                onPkChange={(event) => {
                    const next = readPkTiptapChangeValue(event);
                    if (next === stored) {
                        return;
                    }
                    onChange?.(next);
                }}
                placeholder={placeholder}
                // Flush + fill EditableTable cells (reflected Lit `fit-cell`).
                {...(fitCell ? { fitCell: true } : {})}
                // Host CSS var reaches shadow ProseMirror for the insert rail.
                className={cn(
                    fitCell ? 'h-full w-full' : 'w-full',
                    hasVariables && '[--pk-tiptap-input-padding-inline-end:38px]',
                    isInvalid && 'invalid',
                    inputClassName,
                )}
                invalid={isInvalid}
            />
            {hasVariables ? (
                <VariableDropdown
                    editor={editor}
                    variableCategories={variableCategories}
                    variableCategoryLabels={resolvedLabels}
                    variableCategoryOrder={resolvedOrder}
                    triggerMode="input"
                />
            ) : null}
            {session ? (
                <VariableTagConfigureOverlay
                    session={session}
                    onClose={closeSession}
                    variableCategories={variableCategories}
                    variableCategoryLabels={resolvedLabels}
                    variableCategoryOrder={resolvedOrder}
                    variableTransformerRegistry={resolvedRegistry}
                    renderVariableConfigureSection={renderVariableConfigureSection}
                    resolveVariableTagLabel={resolveVariableTagLabel}
                />
            ) : null}
        </div>
    );
}
