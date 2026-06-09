import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faArrowsRotate, faChevronDown, faLinkSlash } from '@fortawesome/pro-solid-svg-icons';

import {
    Button,
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
    SelectInput,
    Spinner,
} from '@verbb/plugin-kit-react/components';
import {
    FieldControl,
    FieldHeader,
    FieldInstructions,
    FieldLabel,
    FieldRoot,
} from '@verbb/plugin-kit-react/forms/Field';
import { useEngineField } from '@verbb/plugin-kit-react/forms/useEngineField';
import { refreshIntegrationFormSettings } from '@form-builder/hooks/useFormTools';

const PREVIEW_LIMIT = 100;

function SettingSelectField({
    name,
    label,
    instructions,
    value,
    options,
    onChange,
    disabled = false,
    placeholder,
}) {
    return (
        <FieldRoot name={name}>
            <FieldHeader className="space-y-0.5">
                <FieldLabel>{label}</FieldLabel>
                {instructions ? (
                    <FieldInstructions>{instructions}</FieldInstructions>
                ) : null}
            </FieldHeader>
            <FieldControl>
                <SelectInput
                    options={options}
                    value={value}
                    placeholder={placeholder}
                    disabled={disabled}
                    onChange={onChange}
                />
            </FieldControl>
        </FieldRoot>
    );
}

function formatPreviewRows(rows, { showValues = true } = {}) {
    const limited = rows.slice(0, PREVIEW_LIMIT);
    const suffix = rows.length > PREVIEW_LIMIT
        ? `\n${Craft.t('formie', '… and {count} more', { count: rows.length - PREVIEW_LIMIT })}`
        : '';

    return limited.map((row) => {
        const label = String(row?.label ?? '');
        const value = String(row?.value ?? '');

        return showValues && value !== '' && value !== label ? `${label} (${value})` : label;
    }).join('\n') + suffix;
}

function LoadingOptionControls({ message }) {
    return (
        <div className="rounded-sm border border-[rgba(96,125,159,0.25)] bg-[rgba(96,125,159,0.04)] px-3 py-4">
            <div className="flex items-center gap-2 text-sm text-gray-600">
                <Spinner size="xs" className="mx-0" />
                <span>{message}</span>
            </div>
        </div>
    );
}

function isSameFormValue(a, b) {
    try {
        return JSON.stringify(a ?? null) === JSON.stringify(b ?? null);
    } catch {
        return a === b;
    }
}

function OptionPreviewPanel({
    value,
    placeholder,
    loading = false,
    loadingMessage = Craft.t('formie', 'Loading options…'),
}) {
    const hasValue = String(value || '').trim() !== '';
    const lines = hasValue ? String(value).split('\n') : [loading ? loadingMessage : placeholder];

    return (
        <div
            style={{
                minHeight: '260px',
                position: 'relative',
            }}
        >
            <div
                style={{
                    minHeight: '260px',
                    maxHeight: '260px',
                    overflowY: 'auto',
                    width: '100%',
                    border: '1px solid rgba(96, 125, 159, 0.4)',
                    borderRadius: '3px',
                    background: 'rgb(251, 252, 254)',
                    color: hasValue ? 'rgb(31, 41, 51)' : '#7c8793',
                    fontFamily: 'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace',
                    fontSize: '12px',
                    lineHeight: '18px',
                    padding: '6px 8px',
                    boxSizing: 'border-box',
                }}
                aria-readonly="true"
                aria-label={Craft.t('formie', 'Preview')}
                tabIndex={0}
            >
                {lines.map((line, index) => (
                    <div key={`${index}-${line}`}>
                        {line || '\u00a0'}
                    </div>
                ))}
            </div>

            {loading && (
                <div
                    className="pointer-events-none flex items-center gap-2 text-xs text-gray-600"
                    style={{
                        position: 'absolute',
                        right: '8px',
                        top: '8px',
                        borderRadius: '3px',
                        background: 'rgba(255, 255, 255, 0.8)',
                        padding: '4px',
                    }}
                >
                    <Spinner size="xs" className="mx-0" />
                    <span>{Craft.t('formie', 'Loading')}</span>
                </div>
            )}
        </div>
    );
}

function OptionDynamicSettingsField({ field, form }) {
    const { value: optionsMode, setValue: setOptionsMode } = useEngineField(form, 'optionsMode');
    const { value: optionSource, setValue: setOptionSourceValue } = useEngineField(form, 'optionSource');
    const { value: optionsValue, setValue: setOptionsValue } = useEngineField(form, 'options');
    const [busy, setBusy] = useState(false);
    const [previewError, setPreviewError] = useState(null);
    const [previewText, setPreviewText] = useState('');
    const [previewTotal, setPreviewTotal] = useState(null);
    const [predefinedConfig, setPredefinedConfig] = useState(null);
    const [predefinedConfigProvider, setPredefinedConfigProvider] = useState(null);
    const [loadingPredefinedConfig, setLoadingPredefinedConfig] = useState(false);
    const [predefinedReloadToken, setPredefinedReloadToken] = useState(0);
    const [integrationConfig, setIntegrationConfig] = useState(null);
    const [loadingIntegrationList, setLoadingIntegrationList] = useState(false);
    const [loadingIntegrationDetails, setLoadingIntegrationDetails] = useState(false);
    const [integrationConfigIntegrationId, setIntegrationConfigIntegrationId] = useState(null);
    const [integrationConfigProvider, setIntegrationConfigProvider] = useState(null);
    const [integrationConfigError, setIntegrationConfigError] = useState(null);
    const [integrationSetupPending, setIntegrationSetupPending] = useState(false);
    const [refreshingIntegrationData, setRefreshingIntegrationData] = useState(false);
    const [integrationListReloadToken, setIntegrationListReloadToken] = useState(0);
    const [integrationConfigReloadToken, setIntegrationConfigReloadToken] = useState(0);
    const integrationListLoadedRef = useRef(false);
    const integrationConfigLoadKeyRef = useRef('');
    const pendingPredefinedDefaultsRef = useRef(false);
    const pendingIntegrationDefaultsRef = useRef(false);
    const optionSourceValueRef = useRef(optionSource);
    const optionsValueRef = useRef(optionsValue);
    const setOptionSourceValueRef = useRef(setOptionSourceValue);
    const setOptionsValueRef = useRef(setOptionsValue);
    const setOptionSourceRef = useRef(setOptionSourceValue);
    const staticOptionsBackupRef = useRef([]);
    const previewRequestIdRef = useRef(0);
    const sourceParamsRef = useRef(null);

    optionSourceValueRef.current = optionSource;
    optionsValueRef.current = optionsValue;
    setOptionSourceValueRef.current = setOptionSourceValue;
    setOptionsValueRef.current = setOptionsValue;

    const setOptionSource = useCallback((nextOptionSource) => {
        if (isSameFormValue(optionSourceValueRef.current, nextOptionSource)) {
            return;
        }

        optionSourceValueRef.current = nextOptionSource;
        setOptionSourceValueRef.current(nextOptionSource);
    }, []);

    const setOptions = useCallback((nextOptions) => {
        const normalizedOptions = Array.isArray(nextOptions) ? nextOptions : [];

        if (isSameFormValue(optionsValueRef.current, normalizedOptions)) {
            return;
        }

        optionsValueRef.current = normalizedOptions;
        setOptionsValueRef.current(normalizedOptions);
    }, []);

    setOptionSourceRef.current = setOptionSource;

    const mode = String(optionsMode || 'static');
    const source = (optionSource && typeof optionSource === 'object' && !Array.isArray(optionSource))
        ? optionSource
        : {};
    const hasLegacyElementSource = source.type === 'element';
    const isTemplate = mode === 'template';
    const isDynamic = mode === 'dynamic' && !hasLegacyElementSource;
    const isStatic = !isDynamic && !isTemplate;
    sourceParamsRef.current = source.params;
    const sourceType = source.type === 'integration' ? 'integration' : 'predefined';
    const predefinedProviders = Array.isArray(field.predefinedProviders) ? field.predefinedProviders : [];
    const hasIntegrationOptionSources = Boolean(field.hasIntegrationOptionSources);
    const resolveAction = field.resolveAction || 'formie/fields/resolve-option-source';
    const detachAction = field.detachAction || 'formie/fields/detach-option-source';
    const predefinedOptionsAction = field.predefinedOptionsAction || 'formie/fields/get-predefined-options';
    const integrationConfigAction = field.integrationConfigAction
        || 'formie/fields/get-integration-option-source-config';
    const fieldType = field.fieldType || form?.getFieldValue?.('type') || '';
    const predefinedProvider = source.provider || String(predefinedProviders[0]?.value || 'countries');
    const integrationProvider = source.provider || '';
    const labelKey = source.params?.labelKey ?? '';
    const valueKey = source.params?.valueKey ?? '';
    const integrationId = String(source.params?.integrationId ?? '');
    const hasCurrentPredefinedConfig = sourceType === 'predefined'
        && predefinedConfigProvider === predefinedProvider
        && Boolean(predefinedConfig);
    const currentPredefinedConfig = hasCurrentPredefinedConfig ? predefinedConfig : null;
    const labelOptions = Array.isArray(currentPredefinedConfig?.labelOptions)
        ? currentPredefinedConfig.labelOptions
        : [];
    const valueOptions = Array.isArray(currentPredefinedConfig?.valueOptions)
        ? currentPredefinedConfig.valueOptions
        : [];
    const integrationOptions = Array.isArray(integrationConfig?.integrationOptions)
        ? integrationConfig.integrationOptions
        : [];
    const selectedIntegrationOption = integrationOptions.find(
        (option) => String(option.value) === String(integrationId),
    );
    const selectedIntegrationHandle = String(selectedIntegrationOption?.handle || '');
    const selectedIntegrationLabel = selectedIntegrationOption?.label || '';
    const integrationProviderOptions = Array.isArray(integrationConfig?.providerOptions)
        ? integrationConfig.providerOptions
        : [];
    const effectiveIntegrationProvider = integrationProvider;
    const resolvedIntegrationProvider = effectiveIntegrationProvider
        || String(integrationConfigProvider || '');
    const hasCurrentIntegrationDetails = sourceType === 'integration'
        && String(integrationConfigIntegrationId || '') === String(integrationId)
        && Boolean(integrationConfig)
        && (
            !resolvedIntegrationProvider
            || !String(integrationConfigProvider || '')
            || String(integrationConfigProvider || '') === String(resolvedIntegrationProvider)
        );
    const integrationParamFields = Array.isArray(integrationConfig?.paramFields) && hasCurrentIntegrationDetails
        ? integrationConfig.paramFields
        : [];
    const integrationParamValues = source.params && typeof source.params === 'object' && !Array.isArray(source.params)
        ? source.params
        : {};
    const getIntegrationParamOptions = (paramField, params = integrationParamValues) => {
        const optionsByParam = paramField?.optionsByParam;
        const dependsOn = String(paramField?.dependsOn || '');

        if (dependsOn && optionsByParam && typeof optionsByParam === 'object') {
            const groupedOptions = optionsByParam[dependsOn] || {};
            const dependencyValue = String(params[dependsOn] ?? '');

            return Array.isArray(groupedOptions[dependencyValue]) ? groupedOptions[dependencyValue] : [];
        }

        return Array.isArray(paramField?.options) ? paramField.options : [];
    };
    const requiredIntegrationParamFields = integrationParamFields.filter(
        (paramField) => paramField?.required !== false,
    );
    const integrationConfigComplete = Boolean(
        integrationId
        && resolvedIntegrationProvider
        && hasCurrentIntegrationDetails
        && requiredIntegrationParamFields.every((paramField) => {
            const value = String(integrationParamValues[paramField.handle] ?? '');

            if (!value) {
                return false;
            }

            const options = getIntegrationParamOptions(paramField);

            return options.length === 0 || options.some((option) => String(option.value) === value);
        })
    );
    const effectiveLabelKey = labelKey || String(currentPredefinedConfig?.labelOption ?? labelOptions[0]?.value ?? '');
    const effectiveValueKey = valueKey || String(
        currentPredefinedConfig?.valueOption
            ?? valueOptions[0]?.value
            ?? effectiveLabelKey,
    );

    const resolveSelectValue = (currentValue, selectOptions) => {
        if (currentValue === '' || currentValue === null || currentValue === undefined) {
            return undefined;
        }

        const match = selectOptions.find(
            (option) => String(option.value) === String(currentValue),
        );

        return match?.value;
    };

    const integrationSelectValue = resolveSelectValue(integrationId, integrationOptions);
    const integrationProviderSelectValue = resolveSelectValue(
        integrationProvider,
        integrationProviderOptions,
    );

    const updateSource = useCallback((patch) => {
        setOptionSource({
            type: sourceType,
            ...source,
            ...patch,
        });
    }, [setOptionSource, source, sourceType]);

    const updateParams = useCallback((patch) => {
        updateSource({
            params: {
                ...(source.params || {}),
                ...patch,
            },
        });
    }, [source.params, updateSource]);

    useEffect(() => {
        if (!isStatic || !Array.isArray(optionsValue)) {
            return;
        }

        staticOptionsBackupRef.current = optionsValue;
    }, [isStatic, optionsValue]);

    const setOptionsModeIfChanged = useCallback((nextMode) => {
        if (String(optionsMode || 'static') === String(nextMode)) {
            return;
        }

        setOptionsMode(nextMode);
    }, [optionsMode, setOptionsMode]);

    const captureStaticOptionsForRestore = useCallback(() => {
        if (isStatic && Array.isArray(optionsValueRef.current)) {
            staticOptionsBackupRef.current = optionsValueRef.current;
        }
    }, [isStatic]);

    const resolvedOptionSourceForPayload = useMemo(() => {
        if (sourceType === 'integration') {
            return {
                type: 'integration',
                provider: resolvedIntegrationProvider,
                params: {
                    ...(source.params || {}),
                    integrationId: integrationId ? Number(integrationId) : undefined,
                },
            };
        }

        return {
            type: 'predefined',
            provider: predefinedProvider,
            params: {
                ...(source.params || {}),
                labelKey: effectiveLabelKey,
                valueKey: effectiveValueKey,
            },
        };
    }, [
        predefinedProvider,
        resolvedIntegrationProvider,
        effectiveLabelKey,
        effectiveValueKey,
        integrationId,
        source.params,
        sourceType,
    ]);

    const fieldSettingsPayload = useMemo(() => ({
        ...(typeof form?.getValues === 'function' ? form.getValues() : {}),
        optionsMode: 'dynamic',
        optionSource: resolvedOptionSourceForPayload,
    }), [form, resolvedOptionSourceForPayload]);

    const predefinedPreviewRows = useMemo(() => {
        if (!isDynamic || sourceType !== 'predefined' || !hasCurrentPredefinedConfig) {
            return [];
        }

        if (!currentPredefinedConfig?.data?.length || !effectiveLabelKey || !effectiveValueKey) {
            return [];
        }

        return currentPredefinedConfig.data
            .map((item) => ({
                label: String(item?.[effectiveLabelKey] ?? ''),
                value: String(item?.[effectiveValueKey] ?? ''),
            }))
            .filter((row) => row.label !== '' || row.value !== '');
    }, [
        effectiveLabelKey,
        effectiveValueKey,
        hasCurrentPredefinedConfig,
        isDynamic,
        currentPredefinedConfig,
        sourceType,
    ]);

    useEffect(() => {
        if (!form) {
            return;
        }

        if (sourceType === 'predefined') {
            form.__formiePreviewOptions = predefinedPreviewRows;
        }
    }, [form, predefinedPreviewRows, sourceType]);

    const displayPreviewText = sourceType === 'predefined'
        ? formatPreviewRows(predefinedPreviewRows)
        : previewText;
    const displayPreviewCount = sourceType === 'predefined'
        ? predefinedPreviewRows.length
        : previewTotal;
    const predefinedProviderLabel = predefinedProviders.find(
        (option) => String(option.value) === String(predefinedProvider),
    )?.label || predefinedProvider;
    const loadingCurrentPredefinedConfig = sourceType === 'predefined'
        && loadingPredefinedConfig
        && !hasCurrentPredefinedConfig;
    const refreshingCurrentPredefinedConfig = sourceType === 'predefined'
        && loadingPredefinedConfig
        && hasCurrentPredefinedConfig;
    const predefinedControlsReady = sourceType === 'predefined'
        && hasCurrentPredefinedConfig
        && labelOptions.length > 0
        && effectiveLabelKey
        && effectiveValueKey;
    const loadingIntegrationConfig = loadingIntegrationList || loadingIntegrationDetails || refreshingIntegrationData;
    const loadingIntegrationPreview = sourceType === 'integration'
        && (
            refreshingIntegrationData
            || (loadingIntegrationDetails && !hasCurrentIntegrationDetails)
        );
    const refreshingIntegrationDetails = sourceType === 'integration'
        && loadingIntegrationDetails
        && hasCurrentIntegrationDetails;
    const initialIntegrationSetupPending = sourceType === 'integration' && integrationSetupPending;

    useEffect(() => {
        if (!isDynamic || sourceType !== 'predefined' || !predefinedProvider) {
            return undefined;
        }

        let cancelled = false;

        const load = async() => {
            setLoadingPredefinedConfig(true);
            setPreviewError(null);

            try {
                const response = await Craft.sendActionRequest('POST', predefinedOptionsAction, {
                    data: { option: predefinedProvider },
                });

                const data = response?.data || {};

                if (!Array.isArray(data.data)) {
                    throw new Error(Craft.t('formie', 'Unable to load predefined options.'));
                }

                if (cancelled) {
                    return;
                }

                setPredefinedConfig(data);
                setPredefinedConfigProvider(predefinedProvider);

                const labelOptionsForProvider = Array.isArray(data.labelOptions) ? data.labelOptions : [];
                const valueOptionsForProvider = Array.isArray(data.valueOptions) ? data.valueOptions : [];
                const nextLabelKey = String(data.labelOption ?? labelOptionsForProvider[0]?.value ?? '');
                const nextValueKey = String(data.valueOption ?? valueOptionsForProvider[0]?.value ?? nextLabelKey);
                if (pendingPredefinedDefaultsRef.current) {
                    setOptionSourceRef.current({
                        type: 'predefined',
                        provider: predefinedProvider,
                        params: {
                            labelKey: nextLabelKey,
                            valueKey: nextValueKey,
                        },
                    });
                    pendingPredefinedDefaultsRef.current = false;
                }
            } catch (error) {
                if (!cancelled) {
                    setPredefinedConfig(null);
                    setPredefinedConfigProvider(null);
                    setPreviewError(error?.message || Craft.t('formie', 'Unable to load predefined options.'));
                }
            } finally {
                if (!cancelled) {
                    setLoadingPredefinedConfig(false);
                }
            }
        };

        load();

        return () => {
            cancelled = true;
        };
    }, [predefinedProvider, isDynamic, predefinedOptionsAction, predefinedReloadToken, sourceType]);

    const applyIntegrationInstance = useCallback((option, applyDefaults = true) => {
        if (!option) {
            return;
        }

        pendingIntegrationDefaultsRef.current = applyDefaults;
        setIntegrationSetupPending(applyDefaults);
        setIntegrationConfigError(null);
        setPreviewText('');
        setPreviewTotal(null);
        setPreviewError(null);
        setOptions([]);
        const currentParams = sourceParamsRef.current || {};
        const sameIntegration = String(currentParams.integrationId ?? '') === String(option.value);

        setOptionSourceRef.current({
            type: 'integration',
            provider: sameIntegration ? String(currentParams.provider || '') : '',
            params: {
                ...(sameIntegration ? currentParams : {}),
                integrationId: Number(option.value),
            },
        });
    }, [setOptions]);

    useEffect(() => {
        if (!isDynamic || sourceType !== 'integration') {
            integrationListLoadedRef.current = false;
            return undefined;
        }

        if (integrationId) {
            return undefined;
        }

        let cancelled = false;

        const load = async() => {
            setLoadingIntegrationList(true);
            setIntegrationConfigError(null);

            try {
                const response = await Craft.sendActionRequest('POST', integrationConfigAction, {
                    data: {},
                });

                const data = response?.data || {};
                const options = Array.isArray(data.integrationOptions) ? data.integrationOptions : [];

                if (cancelled) {
                    return;
                }

                integrationListLoadedRef.current = true;
                setIntegrationConfig((prev) => ({
                    ...(prev || {}),
                    integrationOptions: options,
                }));

                const currentParams = sourceParamsRef.current || {};
                const currentIntegrationId = String(currentParams.integrationId ?? '');
                const matchedOption = options.find(
                    (option) => String(option.value) === currentIntegrationId,
                );

                if (matchedOption) {
                    return;
                }

                if (options.length === 0) {
                    setIntegrationSetupPending(false);
                    return;
                }

                if (pendingIntegrationDefaultsRef.current) {
                    pendingIntegrationDefaultsRef.current = false;
                    setIntegrationSetupPending(false);
                }
            } catch (error) {
                if (!cancelled) {
                    setIntegrationConfig(null);
                    setIntegrationConfigIntegrationId(null);
                    setIntegrationConfigProvider(null);
                    setIntegrationSetupPending(false);
                    setIntegrationConfigError(error?.message || Craft.t('formie', 'Unable to load integration options.'));
                }
            } finally {
                if (!cancelled) {
                    setLoadingIntegrationList(false);
                }
            }
        };

        load();

        return () => {
            cancelled = true;
        };
    }, [
        applyIntegrationInstance,
        integrationId,
        integrationListReloadToken,
        integrationConfigAction,
        integrationProvider,
        isDynamic,
        sourceType,
    ]);

    useEffect(() => {
        if (!isDynamic || sourceType !== 'integration' || !integrationId) {
            return undefined;
        }

        const provider = effectiveIntegrationProvider || undefined;
        const loadKey = `${integrationId}:${provider || '__default__'}:${integrationConfigReloadToken}`;

        if (
            integrationConfigLoadKeyRef.current === loadKey
            && hasCurrentIntegrationDetails
        ) {
            return undefined;
        }

        let cancelled = false;

        const load = async() => {
            setLoadingIntegrationDetails(true);
            setIntegrationConfigError(null);

            try {
                const response = await Craft.sendActionRequest('POST', integrationConfigAction, {
                    data: {
                        integrationId: Number(integrationId),
                        ...(provider ? { provider } : {}),
                    },
                });

                const data = response?.data || {};

                if (data.error) {
                    throw new Error(data.error);
                }

                if (cancelled) {
                    return;
                }

                const resolvedProvider = String(data.provider || provider || '');

                setIntegrationConfig((prev) => ({
                    ...(prev || {}),
                    ...data,
                    integrationOptions: Array.isArray(data.integrationOptions)
                        ? data.integrationOptions
                        : (prev?.integrationOptions || []),
                }));
                setIntegrationConfigIntegrationId(String(data.integrationId || integrationId));
                setIntegrationConfigProvider(resolvedProvider);
                integrationConfigLoadKeyRef.current = loadKey;

                const defaults = data.defaults || {};
                const currentParams = sourceParamsRef.current || {};
                const currentSourceProvider = String(optionSourceValueRef.current?.provider || '');
                const nextParamFields = Array.isArray(data.paramFields) ? data.paramFields : [];
                const nextParams = { ...currentParams };

                nextParamFields.forEach((paramField) => {
                    const handle = String(paramField?.handle || '');

                    if (!handle || nextParams[handle]) {
                        return;
                    }

                    if (defaults[handle] !== undefined && defaults[handle] !== null) {
                        nextParams[handle] = defaults[handle];
                    }
                });

                const shouldApplyDefaults = pendingIntegrationDefaultsRef.current;
                const shouldSyncProvider = resolvedProvider
                    && currentSourceProvider !== resolvedProvider;

                if (shouldApplyDefaults || shouldSyncProvider) {
                    setOptionSourceRef.current({
                        type: 'integration',
                        provider: resolvedProvider,
                        params: shouldApplyDefaults
                            ? {
                                ...nextParams,
                                integrationId: Number(integrationId),
                            }
                            : {
                                ...currentParams,
                                integrationId: Number(integrationId),
                            },
                    });
                    pendingIntegrationDefaultsRef.current = false;
                }

            } catch (error) {
                if (!cancelled) {
                    setIntegrationConfig((prev) => ({
                        integrationOptions: prev?.integrationOptions || [],
                    }));
                    setIntegrationConfigIntegrationId(null);
                    setIntegrationConfigProvider(null);
                    integrationConfigLoadKeyRef.current = '';
                    setIntegrationSetupPending(false);
                    setIntegrationConfigError(error?.message || Craft.t('formie', 'Unable to load integration options.'));
                }
            } finally {
                if (!cancelled) {
                    setLoadingIntegrationDetails(false);
                    setIntegrationSetupPending(false);
                }
            }
        };

        load();

        return () => {
            cancelled = true;
        };
    }, [
        integrationConfigReloadToken,
        effectiveIntegrationProvider,
        hasCurrentIntegrationDetails,
        integrationId,
        integrationConfigAction,
        isDynamic,
        sourceType,
    ]);

    useEffect(() => {
        if (!isDynamic) {
            previewRequestIdRef.current += 1;
            if (form) {
                form.__formiePreviewOptions = [];
            }
            setPreviewText('');
            setPreviewTotal(null);
            setPreviewError(null);
        }
    }, [form, isDynamic]);

    const resolveDynamicPreview = useCallback(async() => {
        if (!isDynamic || sourceType === 'predefined') {
            return;
        }

        setBusy(true);
        setPreviewError(null);
        const requestId = previewRequestIdRef.current + 1;
        previewRequestIdRef.current = requestId;

        try {
            const response = await Craft.sendActionRequest('POST', resolveAction, {
                data: {
                    fieldType,
                    fieldSettings: fieldSettingsPayload,
                },
            });

            const data = response?.data || {};

            if (data.error) {
                throw new Error(data.error);
            }

            const rows = Array.isArray(data.options) ? data.options : [];

            if (requestId !== previewRequestIdRef.current) {
                return;
            }

            if (form) {
                form.__formiePreviewOptions = rows;
            }

            setPreviewText(formatPreviewRows(rows, {
                showValues: sourceType !== 'integration',
            }));
            setPreviewTotal(typeof data.count === 'number' ? data.count : rows.length);
        } catch (error) {
            if (requestId !== previewRequestIdRef.current) {
                return;
            }

            setPreviewError(error?.message || Craft.t('formie', 'Unable to resolve dynamic options.'));
            setPreviewText('');
            setPreviewTotal(null);
        } finally {
            if (requestId === previewRequestIdRef.current) {
                setBusy(false);
                if (sourceType === 'integration') {
                    setIntegrationSetupPending(false);
                }
            }
        }
    }, [
        fieldSettingsPayload,
        fieldType,
        form,
        isDynamic,
        resolveAction,
        setOptions,
        sourceType,
    ]);

    useEffect(() => {
        if (!isDynamic || sourceType !== 'integration') {
            previewRequestIdRef.current += 1;
            return;
        }

        if (!integrationConfigComplete || loadingIntegrationPreview) {
            previewRequestIdRef.current += 1;
            if (form) {
                form.__formiePreviewOptions = [];
            }
            setPreviewText('');
            setPreviewTotal(null);
            setPreviewError(null);
            return;
        }

        resolveDynamicPreview();
    }, [
        integrationConfigComplete,
        integrationId,
        form,
        isDynamic,
        loadingIntegrationPreview,
        resolveDynamicPreview,
        setOptions,
        source.params,
        sourceType,
    ]);

    const enablePredefined = () => {
        const provider = String(predefinedProviders[0]?.value || 'countries');

        captureStaticOptionsForRestore();
        pendingPredefinedDefaultsRef.current = true;
        setOptionsModeIfChanged('dynamic');
        setOptions([]);
        setOptionSource({
            type: 'predefined',
            provider,
            params: {},
        });
        setIntegrationConfig(null);
        setPredefinedReloadToken((token) => token + 1);
    };

    const enableIntegration = () => {
        captureStaticOptionsForRestore();
        pendingPredefinedDefaultsRef.current = false;
        pendingIntegrationDefaultsRef.current = true;
        setIntegrationSetupPending(true);
        setOptionsModeIfChanged('dynamic');
        setOptions([]);
        setOptionSource({
            type: 'integration',
            provider: '',
            params: {},
        });
        setPredefinedConfig(null);
        setPredefinedConfigProvider(null);
        setIntegrationConfig(null);
        setIntegrationConfigError(null);
        integrationListLoadedRef.current = false;
        setIntegrationListReloadToken((token) => token + 1);
    };

    const disableDynamic = () => {
        pendingPredefinedDefaultsRef.current = false;
        pendingIntegrationDefaultsRef.current = false;
        setIntegrationSetupPending(false);
        setOptionsModeIfChanged('static');
        setOptions(staticOptionsBackupRef.current);
        setOptionSource(null);
        setPredefinedConfig(null);
        setPredefinedConfigProvider(null);
        setIntegrationConfig(null);
        setIntegrationConfigError(null);
        setPreviewText('');
        setPreviewTotal(null);
        setPreviewError(null);
    };

    const enableTemplate = () => {
        captureStaticOptionsForRestore();
        pendingPredefinedDefaultsRef.current = false;
        pendingIntegrationDefaultsRef.current = false;
        setIntegrationSetupPending(false);
        setOptionsModeIfChanged('template');
        setOptions([]);
        setOptionSource(null);
        setPredefinedConfig(null);
        setPredefinedConfigProvider(null);
        setIntegrationConfig(null);
        setIntegrationConfigError(null);
        setPreviewText('');
        setPreviewTotal(null);
        setPreviewError(null);
    };

    const handleOptionsTypeChange = (nextType) => {
        if (nextType === 'static') {
            disableDynamic();
            return;
        }

        if (nextType === 'template') {
            enableTemplate();
            return;
        }

        if (nextType === 'integration') {
            enableIntegration();
            return;
        }

        enablePredefined();
    };

    const handlePredefinedProviderChange = (provider) => {
        pendingPredefinedDefaultsRef.current = true;
        setPreviewError(null);

        if (String(provider) !== String(predefinedProvider)) {
            setOptions([]);
            setOptionSource({
                type: 'predefined',
                provider,
                params: {},
            });
        }

        setPredefinedReloadToken((token) => token + 1);
    };

    const handleIntegrationChange = (nextIntegrationId) => {
        const selected = integrationOptions.find(
            (option) => String(option.value) === String(nextIntegrationId),
        );

        applyIntegrationInstance(selected, true);
        setIntegrationConfigReloadToken((token) => token + 1);
    };

    const handleIntegrationProviderChange = (nextProvider) => {
        if (!integrationId || String(nextProvider) === String(integrationProvider)) {
            return;
        }

        pendingIntegrationDefaultsRef.current = true;
        setIntegrationSetupPending(true);
        setIntegrationConfigError(null);
        setPreviewText('');
        setPreviewTotal(null);
        setPreviewError(null);
        setOptions([]);
        setOptionSource({
            type: 'integration',
            provider: String(nextProvider),
            params: {
                integrationId: Number(integrationId),
            },
        });
        setIntegrationConfigReloadToken((token) => token + 1);
    };

    const handleIntegrationParamChange = (paramField, nextValue) => {
        const handle = String(paramField?.handle || '');

        if (!handle) {
            return;
        }

        const nextParams = {
            ...integrationParamValues,
            [handle]: nextValue,
        };
        const patch = {
            [handle]: nextValue,
        };

        integrationParamFields.forEach((candidateField) => {
            if (String(candidateField?.dependsOn || '') !== handle) {
                return;
            }

            const candidateHandle = String(candidateField?.handle || '');
            const candidateOptions = getIntegrationParamOptions(candidateField, nextParams);

            if (candidateHandle) {
                patch[candidateHandle] = candidateOptions[0]?.value || '';
                nextParams[candidateHandle] = patch[candidateHandle];
            }
        });

        updateParams(patch);
    };

    const handleRefreshIntegrationData = async() => {
        if (!selectedIntegrationHandle || refreshingIntegrationData) {
            return;
        }

        setRefreshingIntegrationData(true);
        setIntegrationSetupPending(true);
        setIntegrationConfigError(null);
        setPreviewError(null);
        setPreviewText('');
        setPreviewTotal(null);
        setOptions([]);

        const refreshParams = integrationConfig?.refreshParams && typeof integrationConfig.refreshParams === 'object'
            ? integrationConfig.refreshParams
            : {};

        try {
            const result = await refreshIntegrationFormSettings(selectedIntegrationHandle, {}, {
                refreshParams,
            });

            if (result?.ok !== true) {
                throw new Error(result?.error || Craft.t('formie', 'Failed to refresh integration data.'));
            }

            setIntegrationConfig((prev) => ({
                integrationOptions: prev?.integrationOptions || integrationOptions,
            }));
            setIntegrationConfigIntegrationId(null);
            setIntegrationConfigProvider(null);
            integrationConfigLoadKeyRef.current = '';
            setIntegrationConfigReloadToken((token) => token + 1);
        } catch (error) {
            setIntegrationSetupPending(false);
            setIntegrationConfigError(error?.message || Craft.t('formie', 'Failed to refresh integration data.'));
        } finally {
            setRefreshingIntegrationData(false);
        }
    };

    const handleConvertToStatic = async() => {
        if (busy) {
            return;
        }

        setBusy(true);
        setPreviewError(null);

        try {
            const response = await Craft.sendActionRequest('POST', detachAction, {
                data: {
                    fieldType,
                    fieldSettings: fieldSettingsPayload,
                },
            });

            const data = response?.data || {};
            const nextOptions = Array.isArray(data.options)
                ? data.options.map((row) => ({
                    ...row,
                    default: Boolean(row.default),
                }))
                : [];

            if (nextOptions.length === 0) {
                setPreviewError(Craft.t('formie', 'No options could be resolved. Check your dynamic source settings.'));
                return;
            }

            setOptions(nextOptions);
            staticOptionsBackupRef.current = nextOptions;
            setOptionsModeIfChanged('static');
            setOptionSource(null);
            setPreviewText('');
            setPreviewTotal(null);
        } catch (error) {
            setPreviewError(error?.message || Craft.t('formie', 'Unable to convert to static options.'));
        } finally {
            setBusy(false);
        }
    };

    const optionsType = isTemplate
        ? 'template'
        : (!isDynamic ? 'static' : (sourceType === 'integration' ? 'integration' : 'predefined'));

    const optionsTypeOptions = [
        {
            label: Craft.t('formie', 'Static'),
            value: 'static',
        },
        {
            label: Craft.t('formie', 'Predefined'),
            value: 'predefined',
        },
    ];

    if (hasIntegrationOptionSources) {
        optionsTypeOptions.push({
            label: Craft.t('formie', 'Integration'),
            value: 'integration',
        });
    }

    optionsTypeOptions.push({
        label: Craft.t('formie', 'Template'),
        value: 'template',
    });

    return (
        <div className="space-y-4">
                <SettingSelectField
                    name="optionsType"
                    label={field.label || Craft.t('formie', 'Options')}
                    instructions={field.instructions || Craft.t('formie', 'Define the available options for users to select from.')}
                    value={optionsType}
                    options={optionsTypeOptions}
                    onChange={handleOptionsTypeChange}
                />

                {isTemplate && (
                    <div className="rounded-sm border border-[rgba(96,125,159,0.25)] bg-[rgba(96,125,159,0.04)] px-3 py-4 text-sm text-gray-600">
                        {Craft.t('formie', 'Template options are supplied by your template at render time. Formie will not store or strictly validate an option list for this field.')}
                    </div>
                )}

                {isDynamic && (
                    <>
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-4">
                                {sourceType === 'predefined' && (
                                    <>
                                        <SettingSelectField
                                            name="predefinedOptionsProvider"
                                            label={Craft.t('formie', 'List')}
                                            instructions={Craft.t('formie', 'Select which predefined option set to use.')}
                                            value={predefinedProvider}
                                            options={predefinedProviders}
                                            onChange={handlePredefinedProviderChange}
                                        />

                                        {loadingCurrentPredefinedConfig && (
                                            <LoadingOptionControls
                                                message={Craft.t('formie', 'Loading fields for {name}…', { name: predefinedProviderLabel })}
                                            />
                                        )}

                                        {predefinedControlsReady && (
                                            <>
                                                <SettingSelectField
                                                    name="predefinedOptionLabel"
                                                    label={Craft.t('formie', 'Option Label')}
                                                    instructions={Craft.t('formie', 'Choose which source field is used as the label.')}
                                                    value={effectiveLabelKey}
                                                    options={labelOptions}
                                                    disabled={loadingPredefinedConfig}
                                                    onChange={(nextLabelKey) => updateParams({ labelKey: nextLabelKey })}
                                                />
                                                <SettingSelectField
                                                    name="predefinedOptionValue"
                                                    label={Craft.t('formie', 'Option Value')}
                                                    instructions={Craft.t('formie', 'Choose which source field is used as the value.')}
                                                    value={effectiveValueKey}
                                                    options={valueOptions}
                                                    disabled={loadingPredefinedConfig}
                                                    onChange={(nextValueKey) => updateParams({ valueKey: nextValueKey })}
                                                />
                                                {refreshingCurrentPredefinedConfig && (
                                                    <div className="flex items-center gap-2 text-xs text-gray-500">
                                                        <Spinner size="xxs" className="mx-0" />
                                                        <span>{Craft.t('formie', 'Refreshing {name} options…', { name: predefinedProviderLabel })}</span>
                                                    </div>
                                                )}
                                            </>
                                        )}
                                    </>
                                )}

                                {sourceType === 'integration' && (
                                    <>
                                        {initialIntegrationSetupPending ? (
                                            <LoadingOptionControls
                                                message={
                                                    integrationOptions.length === 0
                                                        ? Craft.t('formie', 'Loading integrations…')
                                                        : Craft.t('formie', 'Preparing options for {name}…', { name: selectedIntegrationLabel || Craft.t('formie', 'integration') })
                                                }
                                            />
                                        ) : (
                                            <>
                                                {loadingIntegrationList && integrationOptions.length === 0 && !integrationId ? (
                                                    <LoadingOptionControls
                                                        message={Craft.t('formie', 'Loading integrations…')}
                                                    />
                                                ) : (
                                                    <SettingSelectField
                                                        name="integrationIntegration"
                                                        label={Craft.t('formie', 'Integration')}
                                                        instructions={Craft.t('formie', 'Choose the connected integration to pull options from.')}
                                                        value={integrationSelectValue}
                                                        options={integrationOptions}
                                                        placeholder={Craft.t('formie', 'Select an integration')}
                                                        disabled={loadingIntegrationList && !integrationSelectValue}
                                                        onChange={handleIntegrationChange}
                                                    />
                                                )}

                                                {integrationProviderOptions.length > 1 && integrationId && (
                                                    <SettingSelectField
                                                        name="integrationProvider"
                                                        label={Craft.t('formie', 'Source')}
                                                        instructions={Craft.t('formie', 'Choose which integration data supplies the options.')}
                                                        value={integrationProviderSelectValue}
                                                        options={integrationProviderOptions}
                                                        placeholder={Craft.t('formie', 'Select a source')}
                                                        disabled={loadingIntegrationDetails}
                                                        onChange={handleIntegrationProviderChange}
                                                    />
                                                )}

                                                {integrationConfigError && !loadingIntegrationConfig && (
                                                    <p className="text-sm text-red-600">{integrationConfigError}</p>
                                                )}

                                                {hasCurrentIntegrationDetails && (
                                                    <>
                                                        {integrationId && integrationParamFields.map((paramField) => {
                                                            const handle = String(paramField?.handle || '');
                                                            const options = getIntegrationParamOptions(paramField);
                                                            const value = resolveSelectValue(
                                                                integrationParamValues[handle],
                                                                options,
                                                            );

                                                            if (!handle || String(paramField?.type || 'select') !== 'select') {
                                                                return null;
                                                            }

                                                            if (options.length === 0 && paramField?.hideWhenEmpty !== false) {
                                                                return null;
                                                            }

                                                            return (
                                                                <SettingSelectField
                                                                    key={handle}
                                                                    name={`integrationParam-${handle}`}
                                                                    label={paramField.label || handle}
                                                                    instructions={paramField.instructions || ''}
                                                                    value={value}
                                                                    options={options}
                                                                    placeholder={paramField.placeholder || Craft.t('formie', 'Select an option')}
                                                                    disabled={loadingIntegrationDetails}
                                                                    onChange={(nextValue) => handleIntegrationParamChange(paramField, nextValue)}
                                                                />
                                                            );
                                                        })}

                                                        {refreshingIntegrationDetails && (
                                                            <div className="flex items-center gap-2 text-xs text-gray-500">
                                                                <Spinner size="xxs" className="mx-0" />
                                                                <span>{Craft.t('formie', 'Refreshing {name} options…', { name: selectedIntegrationLabel })}</span>
                                                            </div>
                                                        )}
                                                    </>
                                                )}

                                                {!loadingIntegrationList && integrationOptions.length === 0 && !integrationId && (
                                                    <p className="text-sm text-amber-700">
                                                        {Craft.t('formie', 'No enabled integrations are available for dynamic options.')}
                                                    </p>
                                                )}

                                                {integrationConfig?.warning && !loadingIntegrationDetails && (
                                                    <p className="text-sm text-amber-700">{integrationConfig.warning}</p>
                                                )}
                                            </>
                                        )}
                                    </>
                                )}

                                <div className="border-t border-[rgba(96,125,159,0.25)] pt-4">
                                    <FieldRoot name="convertToStaticOptions">
                                        <FieldHeader className="space-y-0.5">
                                            <FieldLabel>{Craft.t('formie', 'Convert to Static Options')}</FieldLabel>
                                            <FieldInstructions>
                                                {Craft.t('formie', 'Resolve the current source and copy the options into the static options table.')}
                                            </FieldInstructions>
                                        </FieldHeader>
                                        <FieldControl>
                                            <Button
                                                type="button"
                                                variant="default"
                                                disabled={busy || loadingPredefinedConfig || loadingIntegrationConfig}
                                                onClick={handleConvertToStatic}
                                            >
                                                <FontAwesomeIcon icon={faLinkSlash} className="mr-1" />
                                                {Craft.t('formie', 'Convert')}
                                            </Button>
                                        </FieldControl>
                                    </FieldRoot>
                                </div>
                            </div>

                            <div className="flex min-h-[260px] flex-col">
                                <FieldRoot name="dynamicOptionsPreview">
                                    <FieldHeader className="space-y-0.5">
                                        <div className="flex items-center justify-between gap-2">
                                            <FieldLabel>
                                                {Craft.t('formie', 'Preview')}
                                                {displayPreviewCount > 0 && (
                                                    <span className="ml-2 font-normal text-gray-500">
                                                        ({Craft.t('formie', '{count} options', { count: displayPreviewCount })})
                                                    </span>
                                                )}
                                            </FieldLabel>
                                            {sourceType === 'integration' && (
                                                <DropdownMenu size="sm">
                                                    <DropdownMenuTrigger
                                                        render={(
                                                            <Button
                                                                type="button"
                                                                variant="ghost"
                                                                size="sm"
                                                                disabled={busy || loadingIntegrationConfig}
                                                            />
                                                        )}
                                                    >
                                                        <FontAwesomeIcon icon={faArrowsRotate} className="mr-1" />
                                                        {Craft.t('formie', 'Refresh')}
                                                        <FontAwesomeIcon icon={faChevronDown} className="ml-1" />
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end">
                                                        <DropdownMenuItem
                                                            disabled={busy || loadingIntegrationConfig}
                                                            onClick={resolveDynamicPreview}
                                                        >
                                                            <FontAwesomeIcon icon={faArrowsRotate} />
                                                            {Craft.t('formie', 'Refresh Preview')}
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem
                                                            disabled={busy || loadingIntegrationConfig || !selectedIntegrationHandle}
                                                            onClick={handleRefreshIntegrationData}
                                                        >
                                                            <FontAwesomeIcon icon={faArrowsRotate} />
                                                            {Craft.t('formie', 'Refresh Integration Data')}
                                                        </DropdownMenuItem>
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            )}
                                        </div>
                                    </FieldHeader>
                                    <OptionPreviewPanel
                                        value={displayPreviewText}
                                        loading={
                                            busy
                                            || (loadingPredefinedConfig && sourceType === 'predefined')
                                            || loadingIntegrationPreview
                                        }
                                        loadingMessage={
                                            sourceType === 'predefined'
                                                ? Craft.t('formie', 'Loading options for {name}…', { name: predefinedProviderLabel })
                                                : Craft.t('formie', 'Loading options for {name}…', { name: selectedIntegrationLabel || Craft.t('formie', 'integration') })
                                        }
                                        placeholder={
                                            sourceType === 'integration' && !integrationConfigComplete
                                                ? (
                                                    integrationConfig?.warning
                                                        || Craft.t('formie', 'Complete the integration settings to preview options.')
                                                )
                                                : Craft.t('formie', 'Resolved options will appear here.')
                                        }
                                    />
                                    {previewError && (
                                        <p className="mt-2 text-sm text-red-600">{previewError}</p>
                                    )}
                                </FieldRoot>
                            </div>
                        </div>
                    </>
                )}

        </div>
    );
}

export { OptionDynamicSettingsField };
export { OptionDynamicSettingsField as OptionSourceSettingsField };
