import { useMemo, useRef, useCallback } from 'react';
import { TiptapEditor } from '@verbb/plugin-kit-react/components';
import { useEngineField, FieldLayout } from '@verbb/plugin-kit-react/forms';
import { useVariableCategoriesContext } from '@utils/VariableCategoriesProvider';
import { VariableDropdown } from '@form-builder/fields/variable-picker';
import {
    useVariableTagConfigureSession,
    VariableTagConfigureOverlay,
} from '@form-builder/fields/variable-picker/VariableTagConfigureOverlay';
import { readPkTiptapChangeValue, normalizePkTiptapStoreValue, usePkTiptapEditor } from '@form-builder/fields/utils/pkTiptapField';
import { isRichTextEmpty } from '@utils/tiptapUtils';

/**
 * Formie-owned SchemaForm `$field: 'richText'` — was a kit v1 builtin.
 * Kit WC filters `variableTag` out of the built-in toolbar button list (no kit picker UI);
 * Formie registers insert chrome via `slot="toolbar-end"` + owns tag configure overlay.
 */
export function RichTextField({ form, field }) {
    const { value, setValue, errors } = useEngineField(form, field.name);
    const hostRef = useRef(null);
    const editor = usePkTiptapEditor(hostRef);
    const { session, setSession, closeSession } = useVariableTagConfigureSession(hostRef, editor);

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
    const buttons = field.buttons ?? ['bold', 'italic'];
    const {
        getVariableCategories,
        variableCategoryLabels,
        variableCategoryOrder,
        variableTransformerRegistry,
        renderVariableConfigureSection,
        resolveVariableTagLabel,
    } = useVariableCategoriesContext();
    const { variableConfig } = field;

    const variableCategories = useMemo(() => {
        if (!variableConfig || !getVariableCategories) {
            return undefined;
        }
        return getVariableCategories(variableConfig, { form });
    }, [variableConfig, form, getVariableCategories]);

    const hasVariables = Object.values(variableCategories ?? {}).some(
        (items) => Array.isArray(items) && items.length > 0,
    );

    // Lit attribute expects a CSV list; React may also assign a string property.
    const buttonsAttr = Array.isArray(buttons) ? buttons.join(',') : String(buttons || 'bold,italic');

    return (
        <FieldLayout
            name={field.name}
            label={field.label}
            instructions={field.instructions}
            warning={field.warning}
            required={field.required}
            translatable={field.translatable}
            errors={errors}
        >
            <div className="relative">
                <TiptapEditor
                    ref={hostRef}
                    value={normalizePkTiptapStoreValue(value)}
                    // Preferred bridge (parity with TiptapInput) + composed event fallback.
                    variableTagConfigure={openConfigureSession}
                    onPkVariableTagConfigure={(event) => {
                        openConfigureSession(event?.detail);
                    }}
                    onPkChange={(event) => {
                        const raw = readPkTiptapChangeValue(event);
                        // TipTap emits JSON for empty docs (`[]` / empty paragraph). Store ''
                        // so required validation and isEmptyValue treat them as blank.
                        const next = isRichTextEmpty(raw) ? '' : raw;
                        const current = isRichTextEmpty(value) ? '' : normalizePkTiptapStoreValue(value);
                        // Guard against programmatic TipTap sync re-entering the store.
                        if (next === current) {
                            return;
                        }
                        setValue(next);
                    }}
                    placeholder={field.placeholder}
                    rows={field.rows}
                    // Lit property is `buttonsAttr` (`buttons` is a getter-only CSV parse).
                    buttonsAttr={buttonsAttr}
                    {...(field.linkOptions && { linkOptionsAttr: typeof field.linkOptions === 'string'
                        ? field.linkOptions
                        : JSON.stringify(field.linkOptions) })}
                    {...(field.linkSelectorStorageKeyPrefix && {
                        linkSelectorStorageKeyPrefix: field.linkSelectorStorageKeyPrefix,
                    })}
                    disabled={field.disabled}
                    invalid={errors.length > 0}
                >
                    {hasVariables ? (
                        // Joins stock toolbar flex/gap via slot="toolbar-end" (not an overlay).
                        <div slot="toolbar-end">
                            <VariableDropdown
                                editor={editor}
                                variableCategories={variableCategories}
                                variableCategoryLabels={variableCategoryLabels}
                                variableCategoryOrder={variableCategoryOrder}
                                triggerMode="toolbar"
                                title={Craft.t('formie', 'Variables')}
                            />
                        </div>
                    ) : null}
                </TiptapEditor>
                {session ? (
                    <VariableTagConfigureOverlay
                        session={session}
                        onClose={closeSession}
                        variableCategories={variableCategories}
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
