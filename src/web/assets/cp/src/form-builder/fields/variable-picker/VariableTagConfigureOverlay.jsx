import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Button, Input, Popover } from '@verbb/plugin-kit-react/components';
import { useTranslation } from '@verbb/plugin-kit-react/hooks';
import { resolveVariableTagLabel } from '@verbb/plugin-kit-tiptap-core';
import { VariableCommandList } from '@form-builder/fields/variable-picker/VariableCommandList';
import { VariableTransformControls } from '@form-builder/fields/variable-picker/VariableTransformControls';
import { useVariablePicker } from '@form-builder/fields/variable-picker/useVariablePicker';
import {
    buildTransformOptions,
    buildVariablePickerGroups,
    findVariableOptionByValue,
} from '@form-builder/fields/utils/variablePicker';

/** Matches `PK_VARIABLE_TAG_CONFIGURE_EVENT` from plugin-kit-web variable-tag-node-view. */
export const PK_VARIABLE_TAG_CONFIGURE_EVENT = 'pk-variable-tag-configure';

/**
 * Listen for WC TipTap chip configure clicks.
 * Listen on `document` (capture) and filter by host — lit/react ref / shadow dispatch
 * timing is unreliable if we only attach to hostRef.
 */
export function useVariableTagConfigureSession(hostRef, editor = null) {
    const [session, setSession] = useState(null);

    useEffect(() => {
        const onConfigure = (event) => {
            const host = hostRef.current;
            if (!host) {
                return;
            }

            const path = typeof event.composedPath === 'function' ? event.composedPath() : [];
            if (event.target !== host && !path.includes(host)) {
                return;
            }

            const detail = event.detail;
            if (!detail?.updateAttributes || !detail?.anchor) {
                return;
            }

            setSession({
                attrs: { ...(detail.attrs ?? {}) },
                anchor: detail.anchor,
                updateAttributes: detail.updateAttributes,
                deleteNode: detail.deleteNode,
            });
        };

        document.addEventListener(PK_VARIABLE_TAG_CONFIGURE_EVENT, onConfigure, true);
        return () => {
            document.removeEventListener(PK_VARIABLE_TAG_CONFIGURE_EVENT, onConfigure, true);
        };
    }, [hostRef, editor]);

    const closeSession = useCallback(() => {
        setSession(null);
    }, []);

    return { session, setSession, closeSession };
}

/**
 * Configure UI for a placed variable chip — kit `Popover` anchored to the chip.
 * Stays in the React tree (same as VariableDropdown) so modal dialogs stay
 * interactive; pk-popup owns top-layer + light-dismiss. No createPortal / dialog
 * special-casing.
 */
export function VariableTagConfigureOverlay({
    session,
    onClose,
    variableCategories,
    variableCategoryLabels,
    variableCategoryOrder,
    variableTransformerRegistry = {},
    renderVariableConfigureSection,
    resolveVariableTagLabel: resolveLabelOverride,
}) {
    const t = useTranslation();
    const originalAttrsRef = useRef(null);
    const [isPickerMode, setIsPickerMode] = useState(false);
    const [defaultIfEmpty, setDefaultIfEmpty] = useState('');
    const [transformerId, setTransformerId] = useState('');
    const [transformerParams, setTransformerParams] = useState({});
    const [pendingTokenValue, setPendingTokenValue] = useState('');
    const [configureSessionKey, setConfigureSessionKey] = useState(0);
    const pendingTokenValueRef = useRef('');
    const configureStateRef = useRef(null);
    const prepareSaveRef = useRef(null);

    const attrs = session?.attrs ?? {};
    const updateAttributes = session?.updateAttributes;

    useEffect(() => {
        if (!session) {
            return;
        }

        originalAttrsRef.current = {
            value: attrs.value ?? null,
            label: attrs.label ?? null,
            default: attrs.default ?? null,
            transformerId: attrs.transformerId ?? null,
            transformerParams: attrs.transformerParams ?? null,
        };
        const nextValue = String(attrs.value ?? '');
        pendingTokenValueRef.current = nextValue;
        setPendingTokenValue(nextValue);
        setDefaultIfEmpty(String(attrs.default ?? ''));
        setTransformerId(String(attrs.transformerId ?? ''));
        setTransformerParams(
            attrs.transformerParams && typeof attrs.transformerParams === 'object'
                ? Object.entries(attrs.transformerParams).reduce((acc, [key, value]) => {
                    acc[key] = value == null ? '' : String(value);
                    return acc;
                }, {})
                : {},
        );
        setIsPickerMode(false);
        setConfigureSessionKey((current) => current + 1);
        // Only re-seed when a new configure session opens (anchor identity).
        // eslint-disable-next-line react-hooks/exhaustive-deps -- intentional session open gate
    }, [session?.anchor]);

    const closeConfig = useCallback((preservedValue = null) => {
        if (preservedValue == null && originalAttrsRef.current) {
            updateAttributes?.(originalAttrsRef.current);
        }

        originalAttrsRef.current = null;
        setIsPickerMode(false);
        onClose?.();
    }, [onClose, updateAttributes]);

    const resolveVariableMetaForToken = useCallback((lookupValue = '') => {
        return findVariableOptionByValue(
            variableCategories,
            String(lookupValue || ''),
        );
    }, [variableCategories]);

    const selectedVariableMeta = useMemo(() => {
        return resolveVariableMetaForToken(pendingTokenValue);
    }, [pendingTokenValue, resolveVariableMetaForToken]);

    const transformOptions = useMemo(() => {
        return buildTransformOptions(selectedVariableMeta, variableTransformerRegistry || {});
    }, [selectedVariableMeta, variableTransformerRegistry]);

    const selectedTransformer = useMemo(() => {
        return transformOptions.find((option) => option.value === transformerId) || null;
    }, [transformOptions, transformerId]);

    const hasIncompatibleTransformerSelection = useMemo(() => {
        if (!transformerId || transformOptions.some((option) => option.value === transformerId)) {
            return false;
        }
        return Boolean(transformerId);
    }, [transformerId, transformOptions]);

    const commitPendingToken = useCallback((nextToken) => {
        const normalizedToken = String(nextToken || '');
        const meta = resolveVariableMetaForToken(normalizedToken);
        const defaultLabel = resolveVariableTagLabel(normalizedToken, meta);
        const label = resolveLabelOverride?.({
            tokenValue: normalizedToken,
            variableOption: meta,
            defaultLabel,
            storedLabel: String(attrs.label ?? ''),
        }) || defaultLabel;

        pendingTokenValueRef.current = normalizedToken;
        setPendingTokenValue(normalizedToken);
        updateAttributes?.({
            value: normalizedToken,
            label,
            default: attrs.default ?? null,
            transformerId: attrs.transformerId ?? null,
            transformerParams: attrs.transformerParams ?? null,
        });
    }, [
        attrs.default,
        attrs.label,
        attrs.transformerId,
        attrs.transformerParams,
        resolveLabelOverride,
        resolveVariableMetaForToken,
        updateAttributes,
    ]);

    const picker = useVariablePicker({
        variableCategories,
        variableCategoryLabels,
        variableCategoryOrder,
        isOpen: Boolean(session) && isPickerMode,
        onApply: (_baseVariable, variable) => {
            commitPendingToken(String(variable?.value || ''));
            setIsPickerMode(false);
        },
    });

    // Same shaping as FormBuilderVariablePickerControl — page-bucket fields +
    // use picker.groups (not raw categories) or the list stays empty.
    const pickerGroups = useMemo(() => {
        return buildVariablePickerGroups({
            groups: Array.isArray(picker.groups) ? picker.groups : [],
            pickerPage: picker.page,
            fallbackFieldsLabel: t('Fields'),
        });
    }, [picker.groups, picker.page, t]);

    const configureSection = renderVariableConfigureSection?.({
        tokenValue: pendingTokenValue,
        variableOption: selectedVariableMeta,
        onPendingTokenChange: commitPendingToken,
        configureResetKey: String(configureSessionKey),
        configureStateRef,
        prepareSaveRef,
        getPendingTokenValue: () => pendingTokenValueRef.current || String(attrs.value ?? ''),
    });

    const handleSave = () => {
        prepareSaveRef.current?.();

        const trimmedDefault = defaultIfEmpty.trim();
        const trimmedTransformerId = transformerId.trim();
        const normalizedParams = trimmedTransformerId
            ? Object.entries(transformerParams).reduce((acc, [key, value]) => {
                const trimmed = String(value ?? '').trim();
                if (trimmed !== '') {
                    acc[key] = trimmed;
                }
                return acc;
            }, {})
            : null;
        const savedValue = pendingTokenValueRef.current || String(attrs.value ?? '');
        const savedMeta = resolveVariableMetaForToken(savedValue);
        const defaultSavedLabel = resolveVariableTagLabel(savedValue, savedMeta);
        const savedLabel = resolveLabelOverride?.({
            tokenValue: savedValue,
            variableOption: savedMeta,
            defaultLabel: defaultSavedLabel,
            storedLabel: String(attrs.label ?? ''),
        }) || defaultSavedLabel;

        updateAttributes?.({
            value: savedValue,
            label: savedLabel,
            default: trimmedDefault || null,
            transformerId: trimmedTransformerId || null,
            transformerParams: trimmedTransformerId ? normalizedParams : null,
        });
        originalAttrsRef.current = null;
        setIsPickerMode(false);
        onClose?.();
    };

    if (!session?.anchor) {
        return null;
    }

    return (
        <Popover
            open
            flush
            placement="bottom-start"
            sideOffset={6}
            // Cross-shadow chip inside pk-tiptap — element anchor, not `for` id.
            anchor={session.anchor}
            onPkOpenChange={(event) => {
                if (!(event.detail?.open ?? event.target?.open ?? false)) {
                    closeConfig();
                }
            }}
        >
            <div
                data-variable-config-popover=""
                className="min-w-[260px] max-w-[360px] overflow-hidden"
                role="dialog"
                aria-label={t('Configure variable')}
            >
                {isPickerMode ? (
                    <VariableCommandList
                        search={picker.search}
                        onSearchChange={picker.setSearch}
                        groups={pickerGroups}
                        options={picker.options}
                        selectedValue={pendingTokenValue}
                        onSelect={picker.handleSelect}
                        placeholder={t('Search variables')}
                        showSearch
                        shouldFilter={false}
                        onBack={picker.page ? picker.handleBack : () => setIsPickerMode(false)}
                        isChildMode={!!picker.page}
                        autoFocusSearchInput
                        open
                    />
                ) : (
                    <div className="p-2">
                        {configureSection}
                        <label className="mb-1 block text-[11px] text-gray-500">
                            {t('Default if empty (optional)')}
                        </label>
                        <Input
                            type="text"
                            value={defaultIfEmpty}
                            onChange={(event) => {
                                setDefaultIfEmpty(event.target.value);
                            }}
                            onKeyDown={(event) => {
                                if (event.key === 'Enter') {
                                    event.preventDefault();
                                    event.stopPropagation();
                                    handleSave();
                                }
                            }}
                        />
                        <VariableTransformControls
                            transformerId={transformerId}
                            onTransformerIdChange={(nextId) => {
                                setTransformerId(nextId);
                                if (!nextId) {
                                    setTransformerParams({});
                                }
                            }}
                            transformOptions={transformOptions}
                            hasIncompatibleTransformerSelection={hasIncompatibleTransformerSelection}
                            selectedTransformer={selectedTransformer}
                            transformerParams={transformerParams}
                            onTransformerParamChange={(paramName, nextValue) => {
                                setTransformerParams((current) => ({
                                    ...current,
                                    [paramName]: nextValue,
                                }));
                            }}
                        />
                        <div className="mt-3 -mx-2 -mb-2 flex items-center justify-between gap-2 border-t border-slate-200 bg-[#f3f7fd] px-2 py-2">
                            <Button
                                type="button"
                                variant="default"
                                size="sm"
                                className="text-[11px]"
                                onClick={() => {
                                    setIsPickerMode(true);
                                }}
                            >
                                {t('Change variable')}
                            </Button>
                            <Button
                                type="button"
                                variant="primary"
                                size="sm"
                                className="text-[11px]"
                                onClick={handleSave}
                            >
                                {t('Save')}
                            </Button>
                        </div>
                    </div>
                )}
            </div>
        </Popover>
    );
}
