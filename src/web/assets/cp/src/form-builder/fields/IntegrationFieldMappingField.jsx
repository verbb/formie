import {
    memo, useCallback, useEffect, useMemo, useRef, useState,
} from 'react';

import {
    Button,
    DropdownMenuItem,
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
    TiptapInput,
} from '@verbb/plugin-kit-react/components';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
    faArrowsRotate, faCheck, faAsterisk,
} from '@fortawesome/pro-solid-svg-icons';
import { FieldControl, FieldLayout } from '@verbb/plugin-kit-react/forms/Field';
import { useEngineField } from '@verbb/plugin-kit-react/forms/useEngineField';

import { cn, getErrorMessage } from '@verbb/plugin-kit-react/utils';
import { IntegrationErrorMessage } from './IntegrationErrorMessage';

import { useVariableCategories } from '@form-builder/hooks/useVariableCategories';
import { useFormBuilderApp } from '@form-builder/contexts/FormBuilderAppContext';
import { useFormBuilderForm } from '@form-builder/contexts/FormBuilderFormContext';
import { refreshIntegrationFormSettings } from '@form-builder/hooks/useFormTools';
import useAppStore from '@form-builder/hooks/useAppStore';
import { FormBuilderVariablePickerControl } from '@form-builder/fields/components/FormBuilderVariablePickerControl';
import { VariablePickerActionsMenu } from '@form-builder/fields/components/VariablePickerActionsMenu';
import {
    buildVariableOptionIndex,
    collectSelectableValues,
    getComparableTokenValue,
} from '@form-builder/fields/utils/variablePicker';

const MAPPING_VARIABLE_CONFIG = {
    content: 'singleLine',
    types: ['text'],
    groupFieldsByPage: true,
    groups: [
        'fieldsVariables',
        'staticFormVariables',
        'staticGeneralVariables',
        'staticSiteVariables',
    ],
};
const PROVIDER_OPTIONS_GROUP = 'providerOptions';
const MODE_FIELD = 'field';
const MODE_CUSTOM = 'custom';
const SUCCESS_FEEDBACK_DURATION = 2200;
const INITIAL_VISIBLE_MAPPING_ROWS = 20;
const MAPPING_ROW_RENDER_BATCH_SIZE = 40;

const waitForNextFrame = () => {
    return new Promise((resolve) => {
        if (typeof window === 'undefined' || typeof window.requestAnimationFrame !== 'function') {
            resolve();
            return;
        }

        window.requestAnimationFrame(() => {
            resolve();
        });
    });
};

const normalizeIntegrationOptions = (options) => {
    if (!options) {
        return [];
    }

    if (Array.isArray(options)) {
        return options;
    }

    if (Array.isArray(options.options)) {
        return options.options;
    }

    return [];
};

const normalizeMappingValue = (rawValue) => {
    if (rawValue && typeof rawValue === 'object' && !Array.isArray(rawValue)) {
        const type = rawValue.type ?? null;
        if (type === 'none') {
            return '';
        }

        const nestedValue = rawValue.value ?? '';
        return typeof nestedValue === 'string' ? nestedValue : String(nestedValue || '');
    }

    if (rawValue == null) {
        return '';
    }

    return typeof rawValue === 'string' ? rawValue : String(rawValue);
};

const normalizeIntegrationField = (integrationField, fallbackHandle) => {
    const handle = String(integrationField?.handle || fallbackHandle || '');
    if (!handle) {
        return null;
    }

    return {
        handle,
        name: integrationField?.name || handle,
        required: Boolean(integrationField?.required),
        options: normalizeIntegrationOptions(integrationField?.options),
    };
};

const resolveRefreshDataKey = (field = {}) => {
    const explicitDataKey = String(field?.dataKey || '').trim();
    if (explicitDataKey) {
        return explicitDataKey;
    }

    const name = String(field?.name || '').trim();
    const match = name.match(/^(.*)FieldMapping$/);
    if (match && match[1]) {
        return match[1];
    }

    return '';
};

const buildProviderOptionVariables = (options = []) => {
    return options.reduce((acc, option) => {
        if (!option || option.value == null || option.value === '') {
            return acc;
        }

        const encoded = encodeURIComponent(String(option.value));
        acc.push({
            label: String(option.label ?? option.value),
            value: `{providerOption:${encoded}}`,
        });

        return acc;
    }, []);
};

const getMappingMode = (mappingValue, fieldOptionValues) => {
    if (!String(mappingValue || '').trim()) {
        return MODE_FIELD;
    }

    // Keep unresolved/deleted field references in field-picker mode.
    // This preserves intent when a previously selected field no longer exists.
    if (/^\{field:[^}]+\}$/.test(String(mappingValue))) {
        return MODE_FIELD;
    }

    if (mappingValue && fieldOptionValues.has(mappingValue)) {
        return MODE_FIELD;
    }

    return MODE_CUSTOM;
};

const MappingValueControl = ({
    mappingValue,
    onChange,
    variableCategories,
    baseVariableOptionIndex,
    baseFieldOptionValues,
    integrationFieldOptions,
    variableTransformerRegistry,
    globalVariableCategoryLabels,
    variableCategoryOrder,
}) => {
    const providerOptionVariables = useMemo(() => {
        return buildProviderOptionVariables(integrationFieldOptions || []);
    }, [integrationFieldOptions]);

    const mergedVariableCategories = useMemo(() => {
        if (!providerOptionVariables.length) {
            return variableCategories;
        }

        return {
            ...variableCategories,
            [PROVIDER_OPTIONS_GROUP]: providerOptionVariables,
        };
    }, [providerOptionVariables, variableCategories]);

    const mergedVariableCategoryLabels = useMemo(() => {
        return {
            ...globalVariableCategoryLabels,
            [PROVIDER_OPTIONS_GROUP]: Craft.t('formie', 'Provider Options'),
        };
    }, [globalVariableCategoryLabels]);
    const fieldPickerCategories = useMemo(() => {
        return mergedVariableCategories;
    }, [mergedVariableCategories]);
    const variableOptionIndex = useMemo(() => {
        if (!providerOptionVariables.length) {
            return baseVariableOptionIndex;
        }

        const labelByValue = new Map(baseVariableOptionIndex?.labelByValue || []);
        const optionByValue = new Map(baseVariableOptionIndex?.optionByValue || []);

        providerOptionVariables.forEach((option) => {
            if (!option?.value) {
                return;
            }

            const value = String(option.value);
            labelByValue.set(value, String(option.label || value));
            optionByValue.set(value, option);
        });

        return {
            labelByValue,
            optionByValue,
        };
    }, [baseVariableOptionIndex, providerOptionVariables]);

    const fieldOptionValues = useMemo(() => {
        if (!providerOptionVariables.length) {
            return baseFieldOptionValues;
        }

        const nextValues = new Set(baseFieldOptionValues);
        providerOptionVariables.forEach((option) => {
            if (option?.value) {
                nextValues.add(String(option.value));
            }
        });

        return nextValues;
    }, [baseFieldOptionValues, providerOptionVariables]);

    const comparableMappingValue = useMemo(() => {
        return getComparableTokenValue(mappingValue);
    }, [mappingValue]);

    const [mode, setMode] = useState(() => {
        return getMappingMode(comparableMappingValue, fieldOptionValues);
    });

    const handleModeChange = (nextModeValue) => {
        const nextMode = Array.isArray(nextModeValue) ? nextModeValue[0] : nextModeValue;
        if (nextMode !== MODE_FIELD && nextMode !== MODE_CUSTOM) {
            return;
        }

        if (nextMode === mode) {
            return;
        }

        setMode(nextMode);

        if (nextMode === MODE_FIELD && !fieldOptionValues.has(comparableMappingValue)) {
            onChange('');
        }
    };

    const modeSwitchTarget = mode === MODE_FIELD ? MODE_CUSTOM : MODE_FIELD;
    const modeSwitchLabel = mode === MODE_FIELD
        ? Craft.t('formie', 'Switch to Custom Value')
        : Craft.t('formie', 'Switch to Field Value');

    return (
        <div className="space-y-2">
            <div className="flex items-center">
                {mode === MODE_FIELD ? (
                    <FormBuilderVariablePickerControl
                        value={mappingValue}
                        onChange={(nextValue) => { onChange(String(nextValue || '')); }}
                        variableCategories={fieldPickerCategories}
                        variableCategoryLabels={mergedVariableCategoryLabels}
                        variableCategoryOrder={variableCategoryOrder}
                        variableOptionIndex={variableOptionIndex}
                        variableTransformerRegistry={variableTransformerRegistry}
                        noneOptionLabel={Craft.t('formie', 'Don\'t Include')}
                        pickerSearchPlaceholder={Craft.t('formie', 'Search values')}
                        includeParentLabel={true}
                        pickerContentClassName="min-w-[260px] max-w-[360px] p-0 overflow-hidden flex flex-col"
                        wrapperClassName="w-full"
                        renderActionItems={({ canShowSettings, openSettings, t }) => {
                            return (
                                <>
                                    <DropdownMenuItem onClick={() => { handleModeChange(modeSwitchTarget); }}>
                                        {modeSwitchLabel}
                                    </DropdownMenuItem>
                                    {canShowSettings && (
                                        <DropdownMenuItem onClick={openSettings}>
                                            {t('Configure Value')}
                                        </DropdownMenuItem>
                                    )}
                                </>
                            );
                        }}
                    />
                ) : (
                    <>
                        <TiptapInput
                            value={mappingValue}
                            onChange={(nextValue) => {
                                onChange(String(nextValue || ''));
                            }}
                            className={cn([
                                'w-full',
                                '[&_.ProseMirror]:h-[30px]! [&_.ProseMirror]:pr-10 [&_.ProseMirror]:pl-[4px]! [&_.ProseMirror]:pt-[6px] [&_.ProseMirror]:text-xs',
                            ])}
                            placeholder={Craft.t('formie', 'Type text or use variables')}
                            variableCategories={mergedVariableCategories}
                            variableCategoryLabels={mergedVariableCategoryLabels}
                            variableCategoryOrder={variableCategoryOrder}
                            variableTransformerRegistry={variableTransformerRegistry}
                            variablePickerTriggerCharacters={['@', '{']}
                        />
                        <VariablePickerActionsMenu label={Craft.t('formie', 'More actions')} align="start">
                            <DropdownMenuItem onClick={() => { handleModeChange(modeSwitchTarget); }}>
                                {modeSwitchLabel}
                            </DropdownMenuItem>
                        </VariablePickerActionsMenu>
                    </>
                )}
            </div>
        </div>
    );
};

const MappingRow = memo(({
    integrationField,
    mappingValue,
    onMappingValueChange,
    variableCategories,
    baseVariableOptionIndex,
    baseFieldOptionValues,
    variableTransformerRegistry,
    globalVariableCategoryLabels,
    variableCategoryOrder,
}) => {
    const handleChange = useCallback((nextValue) => {
        onMappingValueChange(integrationField.handle, String(nextValue || ''));
    }, [integrationField.handle, onMappingValueChange]);

    return (
        <TableRow>
            <TableCell className="w-1/2 px-2">
                <div className="flex items-center gap-1">
                    <span className="font-normal">{integrationField.name}</span>
                    {integrationField.required && (
                        <FontAwesomeIcon icon={faAsterisk} className="size-[9px] text-rose-600" title={Craft.t('formie', 'Required')} />
                    )}
                </div>
            </TableCell>

            <TableCell className="w-1/2 px-2 py-1">
                <MappingValueControl
                    mappingValue={mappingValue}
                    onChange={handleChange}
                    variableCategories={variableCategories}
                    baseVariableOptionIndex={baseVariableOptionIndex}
                    baseFieldOptionValues={baseFieldOptionValues}
                    integrationFieldOptions={integrationField.options || []}
                    variableTransformerRegistry={variableTransformerRegistry}
                    globalVariableCategoryLabels={globalVariableCategoryLabels}
                    variableCategoryOrder={variableCategoryOrder}
                />
            </TableCell>
        </TableRow>
    );
});

MappingRow.displayName = 'MappingRow';

const IntegrationFieldMappingField = ({ form, field }) => {
    const {
        value,
        setValue,
        setTouched,
        errors,
    } = useEngineField(form, field.name);
    const { activeIntegrationHandle } = useFormBuilderApp();
    const { form: parentForm, getValueAtPath } = useFormBuilderForm();
    const [refreshLoading, setRefreshLoading] = useState(false);
    const [refreshError, setRefreshError] = useState(null);
    const [showRefreshSuccess, setShowRefreshSuccess] = useState(false);
    const showRefreshButton = field.showRefreshButton !== false;
    const refreshDataKey = resolveRefreshDataKey(field);
    const successTimeoutRef = useRef(null);
    const renderBatchTimeoutRef = useRef(null);
    const touchTimeoutRef = useRef(null);
    const valueRef = useRef(value);
    const selectedCollectionFieldName = field.selectedCollectionField || `${field.name}.__selectedCollection`;
    const { value: selectedCollectionValue } = useEngineField(form, selectedCollectionFieldName);
    const baseVariableCategories = useVariableCategories(MAPPING_VARIABLE_CONFIG, { form });
    const baseVariableOptionIndex = useMemo(() => {
        return buildVariableOptionIndex(baseVariableCategories, { includeParentLabel: true });
    }, [baseVariableCategories]);
    const baseFieldOptionValues = useMemo(() => {
        return collectSelectableValues(baseVariableCategories);
    }, [baseVariableCategories]);
    const variableTransformerRegistry = useAppStore((state) => { return state.variableCategoriesConfig?.transformerRegistry || {}; });
    const globalVariableCategoryLabels = useAppStore((state) => { return state.variableCategoryLabels || {}; });
    const variableCategoryOrder = useAppStore((state) => { return state.variableCategoryOrder || []; });

    const getIntegrationSettingsPath = useCallback(() => {
        const integrationHandle = String(activeIntegrationHandle || '').trim();
        return integrationHandle ? `settings.integrations.${integrationHandle}` : '';
    }, [activeIntegrationHandle]);

    const getLiveIntegrationSettings = useCallback((settingsPath) => {
        const liveSettings = parentForm?.getFieldValue?.(settingsPath);
        if (liveSettings && typeof liveSettings === 'object' && !Array.isArray(liveSettings)) {
            return liveSettings;
        }

        const contextSettings = getValueAtPath(settingsPath, {});
        return (contextSettings && typeof contextSettings === 'object' && !Array.isArray(contextSettings)) ? contextSettings : {};
    }, [getValueAtPath, parentForm]);

    const syncIntegrationFieldMapping = useCallback((handle, nextValue, fallbackMapping) => {
        const settingsPath = getIntegrationSettingsPath();
        if (!settingsPath || !parentForm?.setFieldValue || !field.name) {
            return;
        }

        const currentSettings = getLiveIntegrationSettings(settingsPath);
        const currentMapping = (currentSettings[field.name] && typeof currentSettings[field.name] === 'object' && !Array.isArray(currentSettings[field.name]))
            ? currentSettings[field.name]
            : fallbackMapping;

        parentForm.setFieldValue(settingsPath, {
            ...currentSettings,
            [field.name]: {
                ...(currentMapping || {}),
                [handle]: nextValue,
            },
        });
    }, [field.name, getIntegrationSettingsPath, getLiveIntegrationSettings, parentForm]);

    const integrationFields = useMemo(() => {
        const fallbackFields = Array.isArray(field.integrationFields) ? field.integrationFields : [];
        const collections = Array.isArray(field.integrationFieldCollections) ? field.integrationFieldCollections : [];

        if (!field.selectedCollectionField || !collections.length) {
            return fallbackFields
                .map((integrationField, index) => {
                    return normalizeIntegrationField(integrationField, `field${index + 1}`);
                })
                .filter(Boolean);
        }

        const selectedId = selectedCollectionValue == null ? '' : String(selectedCollectionValue);
        const selectedCollection = collections.find((collection) => {
            return String(collection?.id ?? '') === selectedId;
        });

        const selectedFields = Array.isArray(selectedCollection?.fields) ? selectedCollection.fields : [];
        const sourceFields = selectedFields.length ? selectedFields : fallbackFields;

        return sourceFields
            .map((integrationField, index) => {
                return normalizeIntegrationField(integrationField, `field${index + 1}`);
            })
            .filter(Boolean);
    }, [field.integrationFields, field.integrationFieldCollections, field.selectedCollectionField, selectedCollectionValue]);
    const [visibleRowCount, setVisibleRowCount] = useState(() => {
        return Math.min(INITIAL_VISIBLE_MAPPING_ROWS, integrationFields.length);
    });
    const visibleIntegrationFields = useMemo(() => {
        return integrationFields.slice(0, visibleRowCount);
    }, [integrationFields, visibleRowCount]);
    const isRenderingRemainingRows = visibleRowCount < integrationFields.length;

    useEffect(() => {
        setVisibleRowCount(Math.min(INITIAL_VISIBLE_MAPPING_ROWS, integrationFields.length));
    }, [integrationFields]);

    useEffect(() => {
        if (renderBatchTimeoutRef.current) {
            clearTimeout(renderBatchTimeoutRef.current);
            renderBatchTimeoutRef.current = null;
        }

        if (visibleRowCount >= integrationFields.length) {
            return undefined;
        }

        // Large provider schemas (HubSpot, etc.) can contain hundreds of mapping rows.
        // Yield between batches so toggling a conditional section can paint quickly.
        renderBatchTimeoutRef.current = setTimeout(() => {
            setVisibleRowCount((currentCount) => {
                return Math.min(currentCount + MAPPING_ROW_RENDER_BATCH_SIZE, integrationFields.length);
            });
            renderBatchTimeoutRef.current = null;
        }, 0);

        return () => {
            if (renderBatchTimeoutRef.current) {
                clearTimeout(renderBatchTimeoutRef.current);
                renderBatchTimeoutRef.current = null;
            }
        };
    }, [integrationFields.length, visibleRowCount]);

    useEffect(() => {
        valueRef.current = value;
    }, [value]);

    useEffect(() => {
        return () => {
            if (successTimeoutRef.current) {
                clearTimeout(successTimeoutRef.current);
            }

            if (renderBatchTimeoutRef.current) {
                clearTimeout(renderBatchTimeoutRef.current);
            }

            if (touchTimeoutRef.current) {
                clearTimeout(touchTimeoutRef.current);
            }
        };
    }, []);

    const updateMappingValue = useCallback((handle, nextValue) => {
        const currentValue = (valueRef.current && typeof valueRef.current === 'object') ? valueRef.current : {};
        const nextMapping = {
            ...currentValue,
            [handle]: nextValue,
        };

        setValue(nextMapping);
        syncIntegrationFieldMapping(handle, nextValue, nextMapping);

        // Mark touched outside the selection-critical path. FormStateStore skips
        // duplicate touched writes, so only the first edit causes this extra notify.
        if (touchTimeoutRef.current) {
            clearTimeout(touchTimeoutRef.current);
        }

        touchTimeoutRef.current = setTimeout(() => {
            setTouched();
            touchTimeoutRef.current = null;
        }, 0);
    }, [setTouched, setValue, syncIntegrationFieldMapping]);

    const handleRefreshMappingFields = async() => {
        const integrationHandle = String(activeIntegrationHandle || '').trim();
        if (refreshLoading) {
            return;
        }

        setShowRefreshSuccess(false);

        if (!integrationHandle) {
            const errorMessage = Craft.t('formie', 'Unable to refresh integration data: missing integration handle.');
            setRefreshError({
                heading: Craft.t('formie', 'Configuration error'),
                text: errorMessage,
                traceAsString: '',
            });
            return;
        }

        setRefreshError(null);

        const settingsPath = `settings.integrations.${integrationHandle}`;
        const currentSettings = getLiveIntegrationSettings(settingsPath);

        setRefreshLoading(true);
        await waitForNextFrame();

        const result = await refreshIntegrationFormSettings(integrationHandle, currentSettings, {
            dataKey: refreshDataKey,
        });
        setRefreshLoading(false);

        if (result?.ok === true && parentForm?.setFieldValue && result?.data && typeof result.data === 'object' && !Array.isArray(result.data)) {
            // Keep refreshed provider schemas in the saved integration settings,
            // while preserving the user's unsaved mapping selections.
            const latestSettings = getLiveIntegrationSettings(settingsPath);
            parentForm.setFieldValue(settingsPath, {
                ...latestSettings,
                ...result.data,
            });
        }

        if (typeof window !== 'undefined') {
            window.dispatchEvent(new CustomEvent('formie:integration-settings-refreshed', {
                detail: {
                    handle: integrationHandle,
                    ok: result?.ok === true,
                    error: result?.error || null,
                    actionType: 'refresh',
                },
            }));
        }

        if (result?.ok !== true) {
            const errorMessage = result?.error || Craft.t('formie', 'Failed to refresh integration data.');
            const parsedError = result?.errorObject ? getErrorMessage(result.errorObject) : null;
            setRefreshError(parsedError?.text ? parsedError : {
                heading: Craft.t('formie', 'Internal Server Error'),
                text: errorMessage,
                traceAsString: '',
            });
            return;
        }

        setShowRefreshSuccess(true);

        if (successTimeoutRef.current) {
            clearTimeout(successTimeoutRef.current);
        }

        successTimeoutRef.current = setTimeout(() => {
            setShowRefreshSuccess(false);
            successTimeoutRef.current = null;
        }, SUCCESS_FEEDBACK_DURATION);
    };

    return (
        <FieldLayout
            name={field.name}
            label={field.label}
            instructions={field.instructions}
            required={field.required}
            errors={errors}
            withControl={false}
            headerEnd={showRefreshButton ? (
                <Button
                    type="button"
                    size="sm"
                    className={cn('relative shrink-0', showRefreshSuccess && 'text-green-600')}
                    onClick={handleRefreshMappingFields}
                    loading={refreshLoading}
                    aria-label={Craft.t('formie', 'Refresh Data')}
                >
                    <span className={cn('inline-flex items-center gap-1', showRefreshSuccess && 'text-transparent')}>
                        <FontAwesomeIcon icon={faArrowsRotate} className="size-3" />
                        {Craft.t('formie', 'Refresh')}
                    </span>

                    {showRefreshSuccess && (
                        <FontAwesomeIcon
                            icon={faCheck}
                            className="absolute left-1/2 top-1/2 size-3 -translate-x-1/2 -translate-y-1/2"
                        />
                    )}
                </Button>
            ) : null}
        >
            <FieldControl>
                <div role="group">
                    <IntegrationErrorMessage error={refreshError} className="-mt-1 mb-2" />

                    {!integrationFields.length && (
                        <div className="rounded-md border border-gray-200 p-6 text-center text-sm text-gray-500">
                            <div className="space-y-2">
                                <p>{Craft.t('formie', 'No fields available.')}</p>

                            </div>
                        </div>
                    )}

                    {!!integrationFields.length && (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-1/2">{Craft.t('formie', field.integrationLabel || 'Integration Field')}</TableHead>
                                    <TableHead className="w-1/2">{Craft.t('formie', 'Value')}</TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                {visibleIntegrationFields.map((integrationField) => {
                                    const mappingValue = normalizeMappingValue(value?.[integrationField.handle]);
                                    return (
                                        <MappingRow
                                            key={integrationField.handle}
                                            integrationField={integrationField}
                                            mappingValue={mappingValue}
                                            onMappingValueChange={updateMappingValue}
                                            variableCategories={baseVariableCategories}
                                            baseVariableOptionIndex={baseVariableOptionIndex}
                                            baseFieldOptionValues={baseFieldOptionValues}
                                            variableTransformerRegistry={variableTransformerRegistry}
                                            globalVariableCategoryLabels={globalVariableCategoryLabels}
                                            variableCategoryOrder={variableCategoryOrder}
                                        />
                                    );
                                })}

                                {isRenderingRemainingRows && (
                                    <TableRow>
                                        <TableCell colSpan={2} className="px-2 py-3 text-center text-xs text-gray-500">
                                            {Craft.t('formie', 'Loading remaining fields…')}
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    )}
                </div>
            </FieldControl>
        </FieldLayout>
    );
};

export { IntegrationFieldMappingField };
