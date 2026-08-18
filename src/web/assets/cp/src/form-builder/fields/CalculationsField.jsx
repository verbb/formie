import { useCallback, useMemo, useRef, useState } from 'react';
import { contentToValue } from '@verbb/plugin-kit-tiptap-core';
import { Icon, TiptapEditor } from '@verbb/plugin-kit-react/components';
import { FieldLayout, useEngineField } from '@verbb/plugin-kit-react/forms';
import { useTranslation } from '@verbb/plugin-kit-react/hooks';
import { cn, hostRequest } from '@verbb/plugin-kit-react/utils';
import { useVariableCategoriesContext } from '@utils/VariableCategoriesProvider';
import {
    CalculationsSyntaxGuideDialog,
    CalculationsToolbar,
} from '@form-builder/fields/CalculationsToolbar';
import {
    useVariableTagConfigureSession,
    VariableTagConfigureOverlay,
} from '@form-builder/fields/variable-picker/VariableTagConfigureOverlay';
import { readPkTiptapChangeValue, normalizePkTiptapStoreValue, usePkTiptapEditor } from '@form-builder/fields/utils/pkTiptapField';

const flattenVariableOptions = (options = []) => {
    const flat = [];

    const visit = (nodes) => {
        nodes.forEach((node) => {
            flat.push(node);
            if (Array.isArray(node?.children) && node.children.length) {
                visit(node.children);
            }
        });
    };

    visit(options);
    return flat;
};

const getAvailableVariableTokens = (variableCategories) => {
    const categoryOptions = Object.values(variableCategories ?? {}).flatMap((items) => {
        return Array.isArray(items) ? items : [];
    });

    return Array.from(new Set(flattenVariableOptions(categoryOptions)
        .map((item) => (typeof item?.value === 'string' ? item.value.trim() : ''))
        .filter(Boolean)));
};

const getVariableTokenLabelMap = (variableCategories) => {
    const categoryOptions = Object.values(variableCategories ?? {}).flatMap((items) => {
        return Array.isArray(items) ? items : [];
    });

    return flattenVariableOptions(categoryOptions).reduce((map, item) => {
        const token = typeof item?.value === 'string' ? item.value.trim() : '';
        if (!token) {
            return map;
        }

        const label = typeof item?.label === 'string' ? item.label.trim() : '';
        map[token] = label || token;
        return map;
    }, {});
};

/** TipTap JSON / content array → plain formula string with `{field:…}` tokens. */
const getFormulaFromValue = (value) => {
    if (typeof value === 'string') {
        const trimmed = value.trim();
        if (!trimmed) {
            return '';
        }

        // Stored TipTap document JSON — extract tokens; otherwise treat as already plain.
        if (trimmed[0] === '[' || trimmed[0] === '{') {
            try {
                const parsed = JSON.parse(trimmed);
                if (Array.isArray(parsed)) {
                    return contentToValue(parsed);
                }
                if (parsed?.type === 'doc' && Array.isArray(parsed.content)) {
                    return contentToValue(parsed.content);
                }
            } catch {
                return value;
            }
        }

        return value;
    }

    if (Array.isArray(value)) {
        return contentToValue(value);
    }

    if (value && typeof value === 'object' && Array.isArray(value.content)) {
        return contentToValue(value.content);
    }

    return '';
};

const getPageIndexFromScopePath = (scopePathValue) => {
    const scopePath = typeof scopePathValue === 'string' ? scopePathValue : '';
    if (!scopePath) {
        return null;
    }

    const match = scopePath.match(/(?:^|\.)pages\.(\d+)(?:\.|$)/);
    if (!match) {
        return null;
    }

    const pageIndex = Number.parseInt(match[1] || '', 10);
    return Number.isInteger(pageIndex) ? pageIndex : null;
};

const getActivePageIndex = (pages, activePage) => {
    if (!Array.isArray(pages) || !pages.length || typeof activePage !== 'string' || !activePage) {
        return null;
    }

    const pageIndex = pages.findIndex((page) => {
        const resolved = (page && typeof page === 'object') ? page : {};
        return resolved._handle === activePage || resolved.handle === activePage;
    });

    return pageIndex >= 0 ? pageIndex : null;
};

/**
 * Formie-owned SchemaForm `$field: 'calculations'` — was a kit v1 builtin.
 * Stock TipTap editor + Formie variable chrome / syntax guide / test formula.
 */
export function CalculationsField({ form, field }) {
    const t = useTranslation();
    const { value, setValue, errors } = useEngineField(form, field.name);
    const hostRef = useRef(null);
    const editor = usePkTiptapEditor(hostRef);
    const { session, setSession, closeSession } = useVariableTagConfigureSession(hostRef, editor);
    const [guideOpen, setGuideOpen] = useState(false);
    const [validating, setValidating] = useState(false);
    const [validation, setValidation] = useState({ type: 'idle', message: '' });

    const {
        getVariableCategories,
        variableCategoryLabels,
        variableCategoryOrder,
        variableTransformerRegistry,
        renderVariableConfigureSection,
        resolveVariableTagLabel,
    } = useVariableCategoriesContext();

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

    const resolvedVariableCategories = useMemo(() => {
        if (field.variableCategories) {
            return field.variableCategories;
        }

        const { variableConfig } = field;
        if (!variableConfig || !getVariableCategories) {
            return undefined;
        }

        // Scope field pickers to the page that owns this calculations field when possible.
        const pages = form?.getFieldValue?.('pages');
        const activePage = form?.getFieldValue?.('activePage');
        const scopePageIndex = getPageIndexFromScopePath(field._scopePath);
        const activePageIndex = getActivePageIndex(pages, activePage);
        const currentPageIndex = Number.isInteger(scopePageIndex) ? scopePageIndex : activePageIndex;
        const scopedVariableConfig = {
            ...variableConfig,
            ...(Number.isInteger(currentPageIndex) ? { currentPageIndex } : {}),
        };

        return getVariableCategories(scopedVariableConfig, { form });
    }, [field, form, getVariableCategories]);

    const availableVariableTokens = useMemo(() => {
        return getAvailableVariableTokens(resolvedVariableCategories);
    }, [resolvedVariableCategories]);

    const tokenLabels = useMemo(() => {
        return getVariableTokenLabelMap(resolvedVariableCategories);
    }, [resolvedVariableCategories]);

    const runValidation = async () => {
        const formula = getFormulaFromValue(value).trim();
        if (!formula) {
            setValidation({ type: 'error', message: t('Enter a formula to test.') });
            return;
        }

        setValidating(true);
        setValidation({ type: 'idle', message: '', technicalMessage: '' });

        try {
            const action = field.validationAction || 'formie/fields/validate-calculations-formula';
            const response = await hostRequest('POST', action, {
                data: {
                    formula,
                    availableTokens: availableVariableTokens,
                    tokenLabels,
                },
            });

            const payload = response?.data ?? response;
            const isValid = Boolean(payload?.valid);
            const message = String(payload?.message ?? '');
            const technicalMessage = String(payload?.technicalMessage ?? '');

            setValidation({
                type: isValid ? 'success' : 'error',
                message: message || (isValid ? t('Formula is valid.') : t('Formula is invalid.')),
                technicalMessage,
            });
        } catch (error) {
            const responseData = error?.response?.data;
            const message = responseData?.message || error?.message || t('Unable to validate formula.');
            setValidation({
                type: 'error',
                message: String(message),
                technicalMessage: String(responseData?.technicalMessage ?? ''),
            });
        } finally {
            setValidating(false);
        }
    };

    return (
        <FieldLayout
            name={field.name}
            label={field.label}
            instructions={field.instructions}
            warning={field.warning}
            required={field.required}
            errors={errors}
        >
            <div className="relative space-y-2">
                <TiptapEditor
                    ref={hostRef}
                    value={normalizePkTiptapStoreValue(value)}
                    // Empty stock button list — CalculationsToolbar owns the chrome via slot="toolbar".
                    buttons=""
                    rows={field.rows ?? 8}
                    placeholder={field.placeholder}
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
                    disabled={field.disabled}
                    invalid={errors.length > 0}
                >
                    {/* Toolbar slot must contain only the bar — Dialog as a sibling
                     * inflated the slot (~67px) and made the formula area look over-padded. */}
                    <div slot="toolbar">
                        <CalculationsToolbar
                            editor={editor}
                            variableCategories={resolvedVariableCategories}
                            variableCategoryLabels={variableCategoryLabels}
                            variableCategoryOrder={variableCategoryOrder}
                            onGuideOpenChange={setGuideOpen}
                            validating={validating}
                            onValidate={runValidation}
                        />
                    </div>
                </TiptapEditor>

                <CalculationsSyntaxGuideDialog
                    open={guideOpen}
                    onOpenChange={setGuideOpen}
                />

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

                {validation.type !== 'idle' && (
                    <div className="space-y-1">
                        <p className={cn(
                            'flex items-center gap-1 text-sm',
                            validation.type === 'success' ? 'text-emerald-700' : 'text-rose-700',
                        )}>
                            {validation.type === 'success' ? (
                                <Icon icon="check" className="size-3" />
                            ) : null}
                            {validation.message}
                        </p>

                        {validation.type === 'error' && validation.technicalMessage
                            && validation.technicalMessage !== validation.message && (
                            <details className="text-xs text-[color:var(--pk-color-gray-500)]">
                                <summary className="cursor-pointer select-none">{t('Show technical details')}</summary>
                                <p className="mt-1 text-rose-700">{validation.technicalMessage}</p>
                            </details>
                        )}
                    </div>
                )}
            </div>
        </FieldLayout>
    );
}
