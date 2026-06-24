import { Fragment, useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
    faArrowsRotate,
    faChevronDown,
    faChevronRight,
    faChevronUp,
    faLinkSlash,
    faSliders,
} from '@fortawesome/pro-solid-svg-icons';

import {
    Button,
    ComboboxInput,
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
    Popover,
    PopoverContent,
    PopoverTrigger,
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
import { cn } from '@verbb/plugin-kit-react/utils';
import { refreshIntegrationFormSettings } from '@form-builder/hooks/useFormTools';

const PREVIEW_LIMIT = 100;
const PREVIEW_SUMMARY_CHIP_LIMIT = 6;
const PREVIEW_EXPANDED_STORAGE_KEY = 'formie:dynamic-options-preview-expanded';

function readPreviewExpandedPreference() {
    try {
        return localStorage.getItem(PREVIEW_EXPANDED_STORAGE_KEY) === '1';
    } catch {
        return false;
    }
}

function writePreviewExpandedPreference(expanded) {
    try {
        localStorage.setItem(PREVIEW_EXPANDED_STORAGE_KEY, expanded ? '1' : '0');
    } catch {
        // Ignore storage failures in restricted environments.
    }
}

function SettingSelectField({
    name,
    label,
    instructions,
    value,
    options,
    onChange,
    disabled = false,
    placeholder,
    useCombobox = false,
    emptyMessage,
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
                {useCombobox ? (
                    <ComboboxInput
                        options={options}
                        value={value ?? ''}
                        placeholder={placeholder || Craft.t('formie', 'Select an option')}
                        emptyMessage={emptyMessage || Craft.t('formie', 'No options found.')}
                        disabled={disabled}
                        className="w-full"
                        onValueChange={onChange}
                    />
                ) : (
                    <SelectInput
                        options={options}
                        value={value}
                        placeholder={placeholder}
                        disabled={disabled}
                        onChange={onChange}
                    />
                )}
            </FieldControl>
        </FieldRoot>
    );
}

const CHAIN_FIELD_WIDTH_DEFAULT = 'w-[200px]';
const CHAIN_DROPDOWN_CONTENT_CLASS = '!w-max !min-w-[var(--anchor-width)] max-w-[min(520px,var(--available-width))]';

function getIntegrationChainFieldWidthClass(step) {
    const labels = [
        String(step.label ?? ''),
        ...step.options.map((option) => String(option.label ?? '')),
    ];
    const longest = labels.reduce((max, label) => Math.max(max, label.length), 0);

    if (longest > 34) {
        return 'w-[320px]';
    }

    if (longest > 28) {
        return 'w-[280px]';
    }

    if (longest > 22) {
        return 'w-[240px]';
    }

    if (longest > 16) {
        return 'w-[220px]';
    }

    return CHAIN_FIELD_WIDTH_DEFAULT;
}

function PathSeparator() {
    return (
        <span
            className="inline-flex h-9 w-3.5 shrink-0 items-center justify-center text-gray-400"
            aria-hidden="true"
        >
            <FontAwesomeIcon icon={faChevronRight} className="size-2.5" />
        </span>
    );
}

function PathSeparatorLabelSpacer() {
    return <span className="inline-block w-3.5 shrink-0" aria-hidden="true" />;
}

function IntegrationSourceChain({ steps }) {
    if (!steps.length) {
        return null;
    }

    return (
        <div
            className="space-y-1"
            role="group"
            aria-label={Craft.t('formie', 'Integration option source settings')}
        >
            <div className="flex flex-wrap items-center gap-x-1">
                {steps.map((step, index) => {
                    const fieldWidthClass = getIntegrationChainFieldWidthClass(step);

                    return (
                        <Fragment key={`${step.key}-label`}>
                            {index > 0 && <PathSeparatorLabelSpacer />}
                            <div className={cn(fieldWidthClass, 'shrink-0')}>
                                <span className="text-xs font-medium text-gray-700">{step.label}</span>
                            </div>
                        </Fragment>
                    );
                })}
            </div>
            <div className="flex flex-wrap items-center gap-x-1 gap-y-1">
                {steps.map((step, index) => {
                    const fieldWidthClass = getIntegrationChainFieldWidthClass(step);

                    return (
                        <Fragment key={step.key}>
                            {index > 0 && <PathSeparator />}
                            <div className={cn(fieldWidthClass, 'shrink-0')}>
                                <ComboboxInput
                                    options={step.options}
                                    value={step.value ?? ''}
                                    placeholder={step.placeholder || Craft.t('formie', 'Select…')}
                                    emptyMessage={Craft.t('formie', 'No options found.')}
                                    disabled={step.disabled}
                                    className="w-full"
                                    contentClassName={CHAIN_DROPDOWN_CONTENT_CLASS}
                                    onValueChange={step.onChange}
                                />
                                {step.errorMessage ? (
                                    <p className="mt-1 text-xs text-red-600">{step.errorMessage}</p>
                                ) : null}
                            </div>
                        </Fragment>
                    );
                })}
            </div>
        </div>
    );
}

function IntegrationChainSkeleton({ message }) {
    const skeletonWidths = ['w-40', 'w-36', 'w-32'];

    return (
        <div className="flex flex-wrap items-center gap-x-1 gap-y-2">
            {skeletonWidths.map((widthClass, index) => (
                <Fragment key={widthClass}>
                    {index > 0 && <PathSeparator />}
                    <InlineSelectSkeleton className={cn('h-9', widthClass)} />
                </Fragment>
            ))}
            <span className="flex items-center gap-1.5 text-xs text-gray-500">
                <Spinner size="xxs" className="mx-0" />
                {message}
            </span>
        </div>
    );
}

function InlineSelectSkeleton({ className }) {
    return (
        <div
            className={cn(
                'h-9 animate-pulse rounded-sm bg-[rgba(96,125,159,0.15)]',
                className,
            )}
        />
    );
}

function PreviewOptionChip({ children, muted = false }) {
    return (
        <span
            className={cn(
                'inline-flex max-w-full items-center truncate rounded-full border px-2 py-0.5 text-xs',
                muted
                    ? 'border-[rgba(96,125,159,0.25)] bg-[rgba(96,125,159,0.08)] text-gray-600'
                    : 'border-[rgba(96,125,159,0.3)] bg-white text-gray-800',
            )}
        >
            {children}
        </span>
    );
}

function PreviewOptionIndicator({ displayType, compact = false }) {
    const normalizedType = String(displayType || '');
    const sizeClass = compact ? 'size-2.5' : 'size-3';

    if (normalizedType === 'checkboxes') {
        return (
            <span
                className={cn(
                    'mt-px shrink-0 rounded-[2px] border border-[rgba(96,125,159,0.45)] bg-white',
                    sizeClass,
                )}
                aria-hidden="true"
            />
        );
    }

    if (normalizedType === 'radio') {
        return (
            <span
                className={cn(
                    'mt-px shrink-0 rounded-full border border-[rgba(96,125,159,0.45)] bg-white',
                    sizeClass,
                )}
                aria-hidden="true"
            />
        );
    }

    return null;
}

function formatPreviewRowLabel(row, { showValues = true } = {}) {
    const label = String(row?.label ?? '');
    const value = String(row?.value ?? '');

    return showValues && value !== '' && value !== label ? `${label} (${value})` : label;
}

function PredefinedMappingPopover({
    labelKey,
    valueKey,
    labelOptions,
    valueOptions,
    disabled,
    onLabelChange,
    onValueChange,
}) {
    return (
        <Popover modal={false}>
            <PopoverTrigger
                render={(
                    <Button type="button" variant="ghost" size="sm">
                        <FontAwesomeIcon icon={faSliders} className="mr-1" />
                        {Craft.t('formie', 'Mapping')}
                    </Button>
                )}
            />
            <PopoverContent align="end" className="w-[min(92vw,320px)] space-y-3 p-4">
                <div className="space-y-1">
                    <p className="text-sm font-medium text-gray-800">
                        {Craft.t('formie', 'Label & value mapping')}
                    </p>
                    <p className="text-xs text-gray-500">
                        {Craft.t('formie', 'Choose which source fields are used for option labels and stored values.')}
                    </p>
                </div>
                <div className="space-y-3">
                    <div className="space-y-1">
                        <label className="text-xs font-medium text-gray-700">
                            {Craft.t('formie', 'Option Label')}
                        </label>
                        <SelectInput
                            options={labelOptions}
                            value={labelKey}
                            placeholder={Craft.t('formie', 'Label')}
                            disabled={disabled}
                            triggerClassName="w-full"
                            onChange={onLabelChange}
                        />
                        <p className="text-xs text-gray-500">
                            {Craft.t('formie', 'Shown to users in the field.')}
                        </p>
                    </div>
                    <div className="space-y-1">
                        <label className="text-xs font-medium text-gray-700">
                            {Craft.t('formie', 'Option Value')}
                        </label>
                        <SelectInput
                            options={valueOptions}
                            value={valueKey}
                            placeholder={Craft.t('formie', 'Value')}
                            disabled={disabled}
                            triggerClassName="w-full"
                            onChange={onValueChange}
                        />
                        <p className="text-xs text-gray-500">
                            {Craft.t('formie', 'Stored when the option is submitted.')}
                        </p>
                    </div>
                </div>
            </PopoverContent>
        </Popover>
    );
}

function DynamicOptionsPreview({
    rows,
    totalCount,
    displayType,
    loading,
    loadingMessage,
    placeholder,
    previewError,
    expanded,
    onExpandedChange,
    sourceType,
    busy,
    loadingIntegrationConfig,
    controlsLoading = false,
    selectedIntegrationHandle,
    onRefreshPreview,
    onRefreshIntegrationData,
    onConvertClick,
    showValuesInPreview = true,
    mappingSettings = null,
}) {
    const count = typeof totalCount === 'number' ? totalCount : rows.length;
    const summaryRows = rows.slice(0, PREVIEW_SUMMARY_CHIP_LIMIT);
    const remainingCount = Math.max(0, count - summaryRows.length);
    const expandedRows = rows.slice(0, PREVIEW_LIMIT);
    const expandedOverflow = count > PREVIEW_LIMIT ? count - PREVIEW_LIMIT : 0;
    const showSummary = !expanded;

    return (
        <div className="rounded-sm border border-[rgba(96,125,159,0.25)] bg-[rgba(96,125,159,0.03)]">
            <div className="flex flex-wrap items-center justify-between gap-2 border-b border-[rgba(96,125,159,0.2)] px-3 py-2">
                <div className="text-sm font-medium text-gray-800">
                    {Craft.t('formie', 'Preview')}
                    {count > 0 && (
                        <span className="ml-2 font-normal text-gray-500">
                            ({Craft.t('formie', '{count} options', { count })})
                        </span>
                    )}
                </div>
                <div className="flex flex-wrap items-center gap-1">
                    {mappingSettings && (
                        <PredefinedMappingPopover
                            labelKey={mappingSettings.labelKey}
                            valueKey={mappingSettings.valueKey}
                            labelOptions={mappingSettings.labelOptions}
                            valueOptions={mappingSettings.valueOptions}
                            disabled={mappingSettings.disabled}
                            onLabelChange={mappingSettings.onLabelChange}
                            onValueChange={mappingSettings.onValueChange}
                        />
                    )}
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
                                    onClick={onRefreshPreview}
                                >
                                    <FontAwesomeIcon icon={faArrowsRotate} />
                                    {Craft.t('formie', 'Refresh Preview')}
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    disabled={busy || loadingIntegrationConfig || !selectedIntegrationHandle}
                                    onClick={onRefreshIntegrationData}
                                >
                                    <FontAwesomeIcon icon={faArrowsRotate} />
                                    {Craft.t('formie', 'Refresh Integration Data')}
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    )}
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        disabled={busy || loadingIntegrationConfig || controlsLoading}
                        onClick={onConvertClick}
                    >
                        <FontAwesomeIcon icon={faLinkSlash} className="mr-1" />
                        {Craft.t('formie', 'Convert')}
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        aria-expanded={expanded}
                        onClick={() => { onExpandedChange(!expanded); }}
                    >
                        {expanded ? (
                            <>
                                <FontAwesomeIcon icon={faChevronUp} className="mr-1" />
                                {Craft.t('formie', 'Collapse')}
                            </>
                        ) : (
                            <>
                                <FontAwesomeIcon icon={faChevronDown} className="mr-1" />
                                {Craft.t('formie', 'Expand')}
                            </>
                        )}
                    </Button>
                </div>
            </div>

            {showSummary && (
                <div className="px-3 py-2">
                    {loading ? (
                        <div className="flex flex-wrap gap-1.5">
                            {Array.from({ length: 4 }).map((_, index) => (
                                <InlineSelectSkeleton key={index} className="h-5 w-20 rounded-full" />
                            ))}
                            <span className="flex items-center gap-1.5 text-xs text-gray-500">
                                <Spinner size="xxs" className="mx-0" />
                                {loadingMessage}
                            </span>
                        </div>
                    ) : rows.length > 0 ? (
                        <div className="flex flex-wrap items-center gap-1">
                            {summaryRows.map((row, index) => (
                                <PreviewOptionChip key={`${index}-${row.label}-${row.value}`}>
                                    {formatPreviewRowLabel(row, { showValues: showValuesInPreview })}
                                </PreviewOptionChip>
                            ))}
                            {remainingCount > 0 && (
                                <PreviewOptionChip muted>
                                    {Craft.t('formie', '+{count} more', { count: remainingCount })}
                                </PreviewOptionChip>
                            )}
                        </div>
                    ) : (
                        <p className="text-sm text-gray-500">{placeholder}</p>
                    )}

                    {previewError && (
                        <p className="mt-2 text-sm text-red-600">{previewError}</p>
                    )}
                </div>
            )}

            {expanded && (
                <div className={cn('px-3 py-2', showSummary && 'border-t border-[rgba(96,125,159,0.2)]')}>
                    {loading ? (
                        <div className="flex items-center gap-1.5 text-xs text-gray-500">
                            <Spinner size="xxs" className="mx-0" />
                            {loadingMessage}
                        </div>
                    ) : rows.length > 0 ? (
                        <div
                            className="max-h-[180px] overflow-y-auto rounded-sm border border-[rgba(96,125,159,0.2)] bg-white px-1 py-0.5"
                            role="list"
                            aria-label={Craft.t('formie', 'Expanded option preview')}
                        >
                            {expandedRows.map((row, index) => (
                                <div
                                    key={`expanded-${index}-${row.label}-${row.value}`}
                                    className="flex items-center gap-1.5 px-1.5 py-0.5 text-xs leading-tight text-gray-800"
                                    role="listitem"
                                >
                                    <PreviewOptionIndicator displayType={displayType} compact />
                                    <span className="min-w-0 truncate">
                                        {formatPreviewRowLabel(row, { showValues: showValuesInPreview })}
                                    </span>
                                </div>
                            ))}
                            {expandedOverflow > 0 && (
                                <p className="px-1.5 py-0.5 text-[11px] text-gray-500">
                                    {Craft.t('formie', '… and {count} more', { count: expandedOverflow })}
                                </p>
                            )}
                        </div>
                    ) : (
                        <p className="text-sm text-gray-500">{placeholder}</p>
                    )}

                    {previewError && (
                        <p className="mt-2 text-sm text-red-600">{previewError}</p>
                    )}
                </div>
            )}
        </div>
    );
}

function ConvertToStaticDialog({
    open,
    onOpenChange,
    sourceLabel,
    busy,
    onConfirm,
}) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-md">
                <DialogHeader>
                    <DialogTitle>{Craft.t('formie', 'Convert to static options?')}</DialogTitle>
                </DialogHeader>
                <div className="px-4 py-4 text-sm leading-relaxed text-gray-600">
                    {Craft.t(
                        'formie',
                        'This will resolve the current source and copy all options into the static options table. The field will no longer update from {source}.',
                        { source: sourceLabel || Craft.t('formie', 'this source') },
                    )}
                </div>
                <DialogFooter className="gap-2 sm:justify-end">
                    <Button
                        type="button"
                        variant="ghost"
                        disabled={busy}
                        onClick={() => { onOpenChange(false); }}
                    >
                        {Craft.t('formie', 'Cancel')}
                    </Button>
                    <Button
                        type="button"
                        variant="primary"
                        disabled={busy}
                        onClick={onConfirm}
                    >
                        <FontAwesomeIcon icon={faLinkSlash} className="mr-1" />
                        {Craft.t('formie', 'Convert')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function isSimplePredefinedData(data) {
    if (!Array.isArray(data) || data.length === 0) {
        return false;
    }

    return data.every((item) => typeof item === 'string' || typeof item === 'number');
}

function normalizePredefinedPreviewRows(data, labelKey, valueKey) {
    if (!Array.isArray(data)) {
        return [];
    }

    if (isSimplePredefinedData(data)) {
        return data
            .map((item) => {
                const text = String(item ?? '').trim();

                if (!text) {
                    return null;
                }

                return { label: text, value: text };
            })
            .filter(Boolean);
    }

    if (!labelKey || !valueKey) {
        return [];
    }

    return data
        .map((item) => ({
            label: String(item?.[labelKey] ?? ''),
            value: String(item?.[valueKey] ?? ''),
        }))
        .filter((row) => row.label !== '' || row.value !== '');
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

function isSameFormValue(a, b) {
    try {
        return JSON.stringify(a ?? null) === JSON.stringify(b ?? null);
    } catch {
        return a === b;
    }
}

function OptionDynamicSettingsField({ field, form }) {
    const { value: optionsMode, setValue: setOptionsMode } = useEngineField(form, 'optionsMode');
    const { value: displayType } = useEngineField(form, 'displayType');
    const { value: optionSource, setValue: setOptionSourceValue } = useEngineField(form, 'optionSource');
    const { value: optionsValue, setValue: setOptionsValue } = useEngineField(form, 'options');
    const [busy, setBusy] = useState(false);
    const [previewError, setPreviewError] = useState(null);
    const [previewText, setPreviewText] = useState('');
    const [previewTotal, setPreviewTotal] = useState(null);
    const [integrationPreviewRows, setIntegrationPreviewRows] = useState([]);
    const [previewExpanded, setPreviewExpanded] = useState(() => readPreviewExpandedPreference());
    const [convertDialogOpen, setConvertDialogOpen] = useState(false);
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
    const [registeredConfig, setRegisteredConfig] = useState(null);
    const [loadingRegisteredList, setLoadingRegisteredList] = useState(false);
    const [loadingRegisteredDetails, setLoadingRegisteredDetails] = useState(false);
    const [registeredConfigProvider, setRegisteredConfigProvider] = useState(null);
    const [registeredConfigError, setRegisteredConfigError] = useState(null);
    const [registeredListReloadToken, setRegisteredListReloadToken] = useState(0);
    const [registeredConfigReloadToken, setRegisteredConfigReloadToken] = useState(0);
    const integrationListLoadedRef = useRef(false);
    const integrationConfigLoadKeyRef = useRef('');
    const registeredConfigLoadKeyRef = useRef('');
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
    const sourceType = source.type === 'integration'
        ? 'integration'
        : (source.type === 'provider' ? 'provider' : 'predefined');
    const predefinedProviders = Array.isArray(field.predefinedProviders) ? field.predefinedProviders : [];
    const sourceTypes = Array.isArray(field.sourceTypes) && field.sourceTypes.length > 0
        ? field.sourceTypes.map((type) => String(type))
        : ['static', 'predefined', 'provider', 'integration', 'template'];
    const allowsSourceType = useCallback((type) => sourceTypes.includes(type), [sourceTypes]);
    const hasPredefinedOptionSources = allowsSourceType('predefined') && predefinedProviders.length > 0;
    const hasRegisteredOptionSources = Boolean(field.hasRegisteredOptionSources);
    const hasIntegrationOptionSources = Boolean(field.hasIntegrationOptionSources);
    const resolveAction = field.resolveAction || 'formie/fields/resolve-option-source';
    const detachAction = field.detachAction || 'formie/fields/detach-option-source';
    const predefinedOptionsAction = field.predefinedOptionsAction || 'formie/fields/get-predefined-options';
    const registeredConfigAction = field.registeredConfigAction
        || 'formie/fields/get-registered-option-source-config';
    const integrationConfigAction = field.integrationConfigAction
        || 'formie/fields/get-integration-option-source-config';
    const fieldType = field.fieldType || form?.getFieldValue?.('type') || '';
    const sourceUsage = field.sourceUsage || '';
    const predefinedProvider = source.provider || String(predefinedProviders[0]?.value || 'countries');
    const integrationProvider = source.provider || '';
    const registeredProvider = sourceType === 'provider' ? (source.provider || '') : '';
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
    const registeredProviderOptions = Array.isArray(registeredConfig?.providerOptions)
        ? registeredConfig.providerOptions
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
    const hasCurrentRegisteredDetails = sourceType === 'provider'
        && String(registeredConfigProvider || '') === String(registeredProvider || '')
        && Boolean(registeredConfig)
        && !registeredConfig?.error;
    const registeredParamFields = Array.isArray(registeredConfig?.paramFields) && hasCurrentRegisteredDetails
        ? registeredConfig.paramFields
        : [];
    const registeredParamValues = sourceType === 'provider' && source.params && typeof source.params === 'object' && !Array.isArray(source.params)
        ? source.params
        : {};
    const getRegisteredParamOptions = (paramField, params = registeredParamValues) => {
        const optionsByParam = paramField?.optionsByParam;
        const dependsOn = String(paramField?.dependsOn || '');

        if (dependsOn && optionsByParam && typeof optionsByParam === 'object') {
            const groupedOptions = optionsByParam[dependsOn] || {};
            const dependencyValue = String(params[dependsOn] ?? '');

            return Array.isArray(groupedOptions[dependencyValue]) ? groupedOptions[dependencyValue] : [];
        }

        return Array.isArray(paramField?.options) ? paramField.options : [];
    };
    const requiredRegisteredParamFields = registeredParamFields.filter(
        (paramField) => paramField?.required !== false,
    );
    const registeredConfigComplete = Boolean(
        registeredProvider
        && hasCurrentRegisteredDetails
        && requiredRegisteredParamFields.every((paramField) => {
            const value = String(registeredParamValues[paramField.handle] ?? '');

            if (!value) {
                return false;
            }

            const options = getRegisteredParamOptions(paramField);

            return options.length === 0 || options.some((option) => String(option.value) === value);
        }),
    );
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

        if (sourceType === 'provider') {
            return {
                type: 'provider',
                provider: registeredProvider,
                params: {
                    ...(source.params || {}),
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
        registeredProvider,
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

    const isSimplePredefinedList = useMemo(
        () => isSimplePredefinedData(currentPredefinedConfig?.data),
        [currentPredefinedConfig?.data],
    );

    const predefinedPreviewRows = useMemo(() => {
        if (!isDynamic || sourceType !== 'predefined' || !hasCurrentPredefinedConfig) {
            return [];
        }

        const data = currentPredefinedConfig?.data;

        if (!Array.isArray(data) || data.length === 0) {
            return [];
        }

        return normalizePredefinedPreviewRows(data, effectiveLabelKey, effectiveValueKey);
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

    const displayPreviewRows = sourceType === 'predefined'
        ? predefinedPreviewRows
        : integrationPreviewRows;
    const displayPreviewCount = sourceType === 'predefined'
        ? predefinedPreviewRows.length
        : (typeof previewTotal === 'number' ? previewTotal : integrationPreviewRows.length);
    const hidePreviewValues = sourceType === 'integration'
        || (sourceType === 'provider' && sourceUsage === 'recipients');
    const predefinedProviderLabel = predefinedProviders.find(
        (option) => String(option.value) === String(predefinedProvider),
    )?.label || predefinedProvider;
    const registeredProviderLabel = registeredProviderOptions.find(
        (option) => String(option.value) === String(registeredProvider),
    )?.label || registeredConfig?.label || registeredProvider;
    const registeredProviderSelectValue = resolveSelectValue(
        registeredProvider,
        registeredProviderOptions,
    );
    const loadingCurrentPredefinedConfig = sourceType === 'predefined'
        && loadingPredefinedConfig
        && !hasCurrentPredefinedConfig;
    const refreshingCurrentPredefinedConfig = sourceType === 'predefined'
        && loadingPredefinedConfig
        && hasCurrentPredefinedConfig;
    const predefinedHasConfigurableKeys = labelOptions.length > 0;
    const predefinedControlsReady = sourceType === 'predefined'
        && hasCurrentPredefinedConfig
        && (
            isSimplePredefinedList
            || (predefinedHasConfigurableKeys && effectiveLabelKey && effectiveValueKey)
        );
    const loadingIntegrationConfig = loadingIntegrationList || loadingIntegrationDetails || refreshingIntegrationData;
    const loadingIntegrationPreview = sourceType === 'integration'
        && (
            refreshingIntegrationData
            || (loadingIntegrationDetails && !hasCurrentIntegrationDetails)
        );
    const loadingRegisteredPreview = sourceType === 'provider'
        && (loadingRegisteredDetails && !hasCurrentRegisteredDetails);
    const loadingRegisteredConfig = loadingRegisteredList || loadingRegisteredDetails;
    const refreshingIntegrationDetails = sourceType === 'integration'
        && loadingIntegrationDetails
        && hasCurrentIntegrationDetails;
    const initialIntegrationSetupPending = sourceType === 'integration' && integrationSetupPending;

    useEffect(() => {
        writePreviewExpandedPreference(previewExpanded);
    }, [previewExpanded]);

    const isIntegrationParamInvalid = useCallback((paramField) => {
        if (!integrationId || loadingIntegrationDetails) {
            return false;
        }

        if (paramField?.required === false) {
            return false;
        }

        const handle = String(paramField?.handle || '');
        const value = String(integrationParamValues[handle] ?? '');
        const options = getIntegrationParamOptions(paramField);

        return options.length > 0 && !value;
    }, [getIntegrationParamOptions, integrationId, integrationParamValues, loadingIntegrationDetails]);

    const isRegisteredParamInvalid = useCallback((paramField) => {
        if (!registeredProvider || loadingRegisteredDetails) {
            return false;
        }

        if (paramField?.required === false) {
            return false;
        }

        const handle = String(paramField?.handle || '');
        const value = String(registeredParamValues[handle] ?? '');
        const options = getRegisteredParamOptions(paramField);

        return options.length > 0 && !value;
    }, [getRegisteredParamOptions, loadingRegisteredDetails, registeredParamValues, registeredProvider]);

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
                    data: {
                        ...(sourceUsage ? { sourceUsage } : {}),
                    },
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
        sourceUsage,
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
                        ...(sourceUsage ? { sourceUsage } : {}),
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
        sourceUsage,
        sourceType,
    ]);

    useEffect(() => {
        if (!isDynamic) {
            previewRequestIdRef.current += 1;
            if (form) {
                form.__formiePreviewOptions = [];
            }
            setIntegrationPreviewRows([]);
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

            setIntegrationPreviewRows(rows.map((row) => ({
                label: String(row?.label ?? ''),
                value: String(row?.value ?? ''),
            })));
            setPreviewText(formatPreviewRows(rows, {
                showValues: !hidePreviewValues,
            }));
            setPreviewTotal(typeof data.count === 'number' ? data.count : rows.length);
        } catch (error) {
            if (requestId !== previewRequestIdRef.current) {
                return;
            }

            setPreviewError(error?.message || Craft.t('formie', 'Unable to resolve dynamic options.'));
            setIntegrationPreviewRows([]);
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
        hidePreviewValues,
        setOptions,
        sourceType,
    ]);

    useEffect(() => {
        if (!isDynamic || sourceType !== 'provider') {
            return undefined;
        }

        let cancelled = false;

        const load = async() => {
            setLoadingRegisteredList(true);
            setRegisteredConfigError(null);

            try {
                const response = await Craft.sendActionRequest('POST', registeredConfigAction, {
                    data: {
                        ...(sourceUsage ? { sourceUsage } : {}),
                    },
                });

                const data = response?.data || {};
                const options = Array.isArray(data.providerOptions) ? data.providerOptions : [];

                if (cancelled) {
                    return;
                }

                setRegisteredConfig((prev) => ({
                    ...(prev || {}),
                    providerOptions: options,
                }));

                if (registeredProvider || options.length === 0) {
                    return;
                }

                setOptionSource({
                    type: 'provider',
                    provider: String(options[0]?.value || ''),
                    params: {},
                });
                setRegisteredConfigReloadToken((token) => token + 1);
            } catch (error) {
                if (!cancelled) {
                    setRegisteredConfig(null);
                    setRegisteredConfigProvider(null);
                    setRegisteredConfigError(error?.message || Craft.t('formie', 'Unable to load registered option sources.'));
                }
            } finally {
                if (!cancelled) {
                    setLoadingRegisteredList(false);
                }
            }
        };

        load();

        return () => {
            cancelled = true;
        };
    }, [
        isDynamic,
        registeredConfigAction,
        registeredListReloadToken,
        registeredProvider,
        setOptionSource,
        sourceType,
        sourceUsage,
    ]);

    useEffect(() => {
        if (!isDynamic || sourceType !== 'provider' || !registeredProvider) {
            return undefined;
        }

        const loadKey = `${registeredProvider}:${registeredConfigReloadToken}`;

        if (registeredConfigLoadKeyRef.current === loadKey && hasCurrentRegisteredDetails) {
            return undefined;
        }

        let cancelled = false;

        const load = async() => {
            setLoadingRegisteredDetails(true);
            setRegisteredConfigError(null);

            try {
                const response = await Craft.sendActionRequest('POST', registeredConfigAction, {
                    data: {
                        provider: registeredProvider,
                        ...(sourceUsage ? { sourceUsage } : {}),
                    },
                });

                const data = response?.data || {};

                if (data.error) {
                    throw new Error(data.error);
                }

                if (cancelled) {
                    return;
                }

                setRegisteredConfig((prev) => ({
                    ...(prev || {}),
                    ...data,
                    providerOptions: Array.isArray(data.providerOptions)
                        ? data.providerOptions
                        : (prev?.providerOptions || []),
                }));
                setRegisteredConfigProvider(String(data.provider || registeredProvider));
                registeredConfigLoadKeyRef.current = loadKey;

                const defaults = data.defaults || {};
                const currentParams = sourceParamsRef.current || {};
                const nextParamFields = Array.isArray(data.paramFields) ? data.paramFields : [];
                const nextParams = { ...currentParams };

                nextParamFields.forEach((paramField) => {
                    const handle = String(paramField?.handle || '');

                    if (!handle || nextParams[handle]) {
                        return;
                    }

                    if (defaults[handle] !== undefined && defaults[handle] !== null && defaults[handle] !== '') {
                        nextParams[handle] = defaults[handle];
                    }
                });

                if (!isSameFormValue(currentParams, nextParams)) {
                    updateSource({
                        type: 'provider',
                        provider: registeredProvider,
                        params: nextParams,
                    });
                }
            } catch (error) {
                if (!cancelled) {
                    setRegisteredConfigProvider(null);
                    registeredConfigLoadKeyRef.current = '';
                    setRegisteredConfigError(error?.message || Craft.t('formie', 'Unable to load registered option source settings.'));
                }
            } finally {
                if (!cancelled) {
                    setLoadingRegisteredDetails(false);
                }
            }
        };

        load();

        return () => {
            cancelled = true;
        };
    }, [
        hasCurrentRegisteredDetails,
        isDynamic,
        registeredConfigAction,
        registeredConfigReloadToken,
        registeredProvider,
        sourceType,
        sourceUsage,
        updateSource,
    ]);

    useEffect(() => {
        if (!isDynamic || (sourceType !== 'integration' && sourceType !== 'provider')) {
            previewRequestIdRef.current += 1;
            return;
        }

        const configComplete = sourceType === 'integration'
            ? integrationConfigComplete
            : registeredConfigComplete;
        const loadingPreview = sourceType === 'integration'
            ? loadingIntegrationPreview
            : loadingRegisteredDetails;

        if (!configComplete || loadingPreview) {
            previewRequestIdRef.current += 1;
            if (form) {
                form.__formiePreviewOptions = [];
            }
            setIntegrationPreviewRows([]);
            setPreviewText('');
            setPreviewTotal(null);
            setPreviewError(null);
            return;
        }

        resolveDynamicPreview();
    }, [
        integrationConfigComplete,
        registeredConfigComplete,
        form,
        isDynamic,
        loadingIntegrationPreview,
        loadingRegisteredDetails,
        resolveDynamicPreview,
        setOptions,
        source.params,
        sourceType,
    ]);

    const enablePredefined = () => {
        if (!hasPredefinedOptionSources) {
            disableDynamic();
            return;
        }

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
        setRegisteredConfig(null);
        setRegisteredConfigProvider(null);
        setRegisteredConfigError(null);
        registeredConfigLoadKeyRef.current = '';
        setPredefinedReloadToken((token) => token + 1);
    };

    const enableIntegration = () => {
        if (!allowsSourceType('integration')) {
            disableDynamic();
            return;
        }

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
        setRegisteredConfig(null);
        setRegisteredConfigProvider(null);
        setRegisteredConfigError(null);
        registeredConfigLoadKeyRef.current = '';
        setIntegrationConfig(null);
        setIntegrationConfigError(null);
        integrationListLoadedRef.current = false;
        setIntegrationListReloadToken((token) => token + 1);
    };

    const enableProvider = () => {
        if (!allowsSourceType('provider') || !hasRegisteredOptionSources) {
            disableDynamic();
            return;
        }

        captureStaticOptionsForRestore();
        pendingPredefinedDefaultsRef.current = false;
        pendingIntegrationDefaultsRef.current = false;
        setIntegrationSetupPending(false);
        setOptionsModeIfChanged('dynamic');
        setOptions([]);
        setOptionSource({
            type: 'provider',
            provider: '',
            params: {},
        });
        setPredefinedConfig(null);
        setPredefinedConfigProvider(null);
        setIntegrationConfig(null);
        setIntegrationConfigError(null);
        setRegisteredConfig(null);
        setRegisteredConfigProvider(null);
        setRegisteredConfigError(null);
        registeredConfigLoadKeyRef.current = '';
        setRegisteredListReloadToken((token) => token + 1);
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
        setRegisteredConfig(null);
        setRegisteredConfigProvider(null);
        setRegisteredConfigError(null);
        registeredConfigLoadKeyRef.current = '';
        setPreviewText('');
        setPreviewTotal(null);
        setPreviewError(null);
    };

    const enableTemplate = () => {
        if (!allowsSourceType('template')) {
            disableDynamic();
            return;
        }

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

        if (nextType === 'provider') {
            enableProvider();
            return;
        }

        enablePredefined();
    };

    const handleRegisteredProviderChange = (nextProvider) => {
        pendingPredefinedDefaultsRef.current = false;
        pendingIntegrationDefaultsRef.current = false;
        setPreviewError(null);

        if (String(nextProvider) !== String(registeredProvider)) {
            setOptions([]);
            setOptionSource({
                type: 'provider',
                provider: String(nextProvider),
                params: {},
            });
        }

        setRegisteredConfigReloadToken((token) => token + 1);
    };

    const handleRegisteredParamChange = (paramField, nextValue) => {
        const handle = String(paramField?.handle || '');

        if (!handle) {
            return;
        }

        const nextParams = {
            ...registeredParamValues,
            [handle]: nextValue,
        };
        const patch = {
            [handle]: nextValue,
        };

        registeredParamFields.forEach((candidateField) => {
            if (String(candidateField?.dependsOn || '') !== handle) {
                return;
            }

            const candidateHandle = String(candidateField?.handle || '');
            const candidateOptions = getRegisteredParamOptions(candidateField, nextParams);

            if (candidateHandle) {
                patch[candidateHandle] = candidateOptions[0]?.value || '';
                nextParams[candidateHandle] = patch[candidateHandle];
            }
        });

        updateParams(patch);
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
            setIntegrationPreviewRows([]);
            setPreviewText('');
            setPreviewTotal(null);
            setConvertDialogOpen(false);
        } catch (error) {
            setPreviewError(error?.message || Craft.t('formie', 'Unable to convert to static options.'));
        } finally {
            setBusy(false);
        }
    };

    const optionsType = isTemplate
        ? 'template'
        : (!isDynamic ? 'static' : (
            sourceType === 'integration'
                ? 'integration'
                : (sourceType === 'provider' ? 'provider' : 'predefined')
        ));

    const optionsTypeOptions = [
        {
            label: Craft.t('formie', 'Static'),
            value: 'static',
        },
    ];

    if (hasPredefinedOptionSources) {
        optionsTypeOptions.push({
            label: Craft.t('formie', 'Predefined'),
            value: 'predefined',
        });
    }

    if (allowsSourceType('provider') && hasRegisteredOptionSources) {
        optionsTypeOptions.push({
            label: Craft.t('formie', 'Custom Provider'),
            value: 'provider',
        });
    }

    if (allowsSourceType('integration') && hasIntegrationOptionSources) {
        optionsTypeOptions.push({
            label: Craft.t('formie', 'Integration'),
            value: 'integration',
        });
    }

    if (allowsSourceType('template')) {
        optionsTypeOptions.push({
            label: Craft.t('formie', 'Template'),
            value: 'template',
        });
    }

    const isLikertColumns = String(displayType || '') === 'likert';
    const optionsFieldLabel = isLikertColumns
        ? Craft.t('formie', 'Columns')
        : (field.label || Craft.t('formie', 'Options'));
    const optionsFieldInstructions = isLikertColumns
        ? Craft.t('formie', 'Define the available scale columns users can select from.')
        : (field.instructions || Craft.t('formie', 'Define the available options for users to select from.'));

    const convertSourceLabel = sourceType === 'integration'
        ? selectedIntegrationLabel
        : (sourceType === 'provider' ? registeredProviderLabel : predefinedProviderLabel);

    const previewLoading = sourceType === 'predefined'
        ? loadingPredefinedConfig && displayPreviewRows.length === 0
        : (busy || loadingIntegrationPreview || loadingRegisteredPreview);

    const previewLoadingMessage = sourceType === 'predefined'
        ? Craft.t('formie', 'Loading options for {name}…', { name: predefinedProviderLabel })
        : (sourceType === 'provider'
            ? Craft.t('formie', 'Loading options for {name}…', { name: registeredProviderLabel || Craft.t('formie', 'custom provider') })
            : Craft.t('formie', 'Loading options for {name}…', { name: selectedIntegrationLabel || Craft.t('formie', 'integration') }));

    const previewPlaceholder = sourceType === 'integration' && !integrationConfigComplete
        ? (integrationConfig?.warning || Craft.t('formie', 'Complete the integration settings to preview options.'))
        : (sourceType === 'provider' && !registeredConfigComplete
            ? (registeredConfig?.warning || Craft.t('formie', 'Complete the custom provider settings to preview options.'))
            : Craft.t('formie', 'Resolved options will appear here.'));

    const integrationChainSteps = useMemo(() => {
        if (sourceType !== 'integration') {
            return [];
        }

        const steps = [{
            key: 'integration',
            name: 'integrationIntegration',
            label: Craft.t('formie', 'Integration'),
            placeholder: Craft.t('formie', 'Select an integration'),
            value: integrationSelectValue,
            options: integrationOptions,
            disabled: loadingIntegrationList && !integrationSelectValue,
            onChange: handleIntegrationChange,
            isInvalid: Boolean(integrationConfigError && !integrationId),
            errorMessage: integrationConfigError && !integrationId ? integrationConfigError : '',
        }];

        if (integrationProviderOptions.length > 1 && integrationId) {
            steps.push({
                key: 'provider',
                name: 'integrationProvider',
                label: Craft.t('formie', 'Source'),
                placeholder: Craft.t('formie', 'Select a source'),
                value: integrationProviderSelectValue,
                options: integrationProviderOptions,
                disabled: loadingIntegrationDetails,
                onChange: handleIntegrationProviderChange,
                isInvalid: Boolean(integrationConfigError && integrationId && !integrationProviderSelectValue),
                errorMessage: integrationConfigError && integrationId && !integrationProviderSelectValue
                    ? integrationConfigError
                    : '',
            });
        }

        if (hasCurrentIntegrationDetails && integrationId) {
            integrationParamFields.forEach((paramField) => {
                const handle = String(paramField?.handle || '');
                const options = getIntegrationParamOptions(paramField);
                const value = resolveSelectValue(
                    integrationParamValues[handle],
                    options,
                );

                if (!handle || String(paramField?.type || 'select') !== 'select') {
                    return;
                }

                if (options.length === 0 && paramField?.hideWhenEmpty !== false) {
                    return;
                }

                const paramInvalid = isIntegrationParamInvalid(paramField);

                steps.push({
                    key: handle,
                    name: `integrationParam-${handle}`,
                    label: paramField.label || handle,
                    placeholder: paramField.placeholder || Craft.t('formie', 'Select…'),
                    value,
                    options,
                    disabled: loadingIntegrationDetails,
                    onChange: (nextValue) => handleIntegrationParamChange(paramField, nextValue),
                    isInvalid: paramInvalid,
                    errorMessage: paramInvalid
                        ? Craft.t('formie', 'Select a {name}.', { name: (paramField.label || handle).toLowerCase() })
                        : '',
                });
            });
        }

        return steps;
    }, [
        getIntegrationParamOptions,
        handleIntegrationChange,
        handleIntegrationParamChange,
        handleIntegrationProviderChange,
        hasCurrentIntegrationDetails,
        integrationConfigError,
        integrationId,
        integrationOptions,
        integrationParamFields,
        integrationParamValues,
        integrationProviderOptions.length,
        integrationProviderSelectValue,
        integrationSelectValue,
        isIntegrationParamInvalid,
        loadingIntegrationDetails,
        loadingIntegrationList,
        resolveSelectValue,
        sourceType,
    ]);

    const showIntegrationChainSkeleton = initialIntegrationSetupPending
        || (loadingIntegrationList && integrationOptions.length === 0 && !integrationId);

    const showRegisteredChainSkeleton = sourceType === 'provider'
        && loadingRegisteredList
        && registeredProviderOptions.length === 0
        && !registeredProvider;

    const registeredChainSteps = useMemo(() => {
        if (sourceType !== 'provider') {
            return [];
        }

        const steps = [];

        if (registeredProviderOptions.length > 1 || !registeredProvider) {
            steps.push({
                key: 'provider',
                name: 'registeredProvider',
                label: Craft.t('formie', 'Provider'),
                placeholder: Craft.t('formie', 'Select a provider'),
                value: registeredProviderSelectValue,
                options: registeredProviderOptions,
                disabled: loadingRegisteredList && !registeredProviderSelectValue,
                onChange: handleRegisteredProviderChange,
                isInvalid: Boolean(registeredConfigError && !registeredProviderSelectValue),
                errorMessage: registeredConfigError && !registeredProviderSelectValue ? registeredConfigError : '',
            });
        }

        if (hasCurrentRegisteredDetails && registeredProvider) {
            registeredParamFields.forEach((paramField) => {
                const handle = String(paramField?.handle || '');
                const options = getRegisteredParamOptions(paramField);
                const value = resolveSelectValue(
                    registeredParamValues[handle],
                    options,
                );

                if (!handle || String(paramField?.type || 'select') !== 'select') {
                    return;
                }

                if (options.length === 0 && paramField?.hideWhenEmpty !== false) {
                    return;
                }

                const paramInvalid = isRegisteredParamInvalid(paramField);

                steps.push({
                    key: handle,
                    name: `registeredParam-${handle}`,
                    label: paramField.label || handle,
                    placeholder: paramField.placeholder || Craft.t('formie', 'Select…'),
                    value,
                    options,
                    disabled: loadingRegisteredDetails,
                    onChange: (nextValue) => handleRegisteredParamChange(paramField, nextValue),
                    isInvalid: paramInvalid,
                    errorMessage: paramInvalid
                        ? Craft.t('formie', 'Select a {name}.', { name: (paramField.label || handle).toLowerCase() })
                        : '',
                });
            });
        }

        return steps;
    }, [
        getRegisteredParamOptions,
        handleRegisteredParamChange,
        handleRegisteredProviderChange,
        hasCurrentRegisteredDetails,
        isRegisteredParamInvalid,
        loadingRegisteredDetails,
        loadingRegisteredList,
        registeredConfigError,
        registeredParamFields,
        registeredParamValues,
        registeredProvider,
        registeredProviderOptions,
        registeredProviderSelectValue,
        resolveSelectValue,
        sourceType,
    ]);

    return (
        <div className="space-y-4">
                <SettingSelectField
                    name="optionsType"
                    label={optionsFieldLabel}
                    instructions={optionsFieldInstructions}
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
                    <div className="space-y-3">
                        {sourceType === 'predefined' && (
                            <div className="space-y-2">
                                <SettingSelectField
                                    name="predefinedOptionsProvider"
                                    label={Craft.t('formie', 'List')}
                                    instructions={Craft.t('formie', 'Select which predefined option set to use.')}
                                    value={predefinedProvider}
                                    options={predefinedProviders}
                                    placeholder={Craft.t('formie', 'Select a list')}
                                    useCombobox
                                    disabled={loadingCurrentPredefinedConfig}
                                    onChange={handlePredefinedProviderChange}
                                />

                                {predefinedControlsReady && refreshingCurrentPredefinedConfig && (
                                    <div className="flex items-center gap-2 text-xs text-gray-500">
                                        <Spinner size="xxs" className="mx-0" />
                                        <span>{Craft.t('formie', 'Refreshing {name} options…', { name: predefinedProviderLabel })}</span>
                                    </div>
                                )}
                            </div>
                        )}

                        {sourceType === 'integration' && (
                            <div className="space-y-2">
                                <FieldRoot name="integrationSourceSettings">
                                    <FieldHeader className="space-y-0.5">
                                        <FieldLabel>{Craft.t('formie', 'Source')}</FieldLabel>
                                        <FieldInstructions>
                                            {Craft.t('formie', 'Choose the connected integration and settings to pull options from.')}
                                        </FieldInstructions>
                                    </FieldHeader>
                                </FieldRoot>
                                {showIntegrationChainSkeleton ? (
                                    <IntegrationChainSkeleton
                                        message={
                                            integrationOptions.length === 0
                                                ? Craft.t('formie', 'Loading integrations…')
                                                : Craft.t('formie', 'Preparing options for {name}…', {
                                                    name: selectedIntegrationLabel || Craft.t('formie', 'integration'),
                                                })
                                        }
                                    />
                                ) : (
                                    <>
                                        <IntegrationSourceChain steps={integrationChainSteps} />

                                        {integrationConfigError && !loadingIntegrationConfig && integrationId && (
                                            <p className="text-sm text-red-600">{integrationConfigError}</p>
                                        )}

                                        {refreshingIntegrationDetails && (
                                            <div className="flex items-center gap-2 text-xs text-gray-500">
                                                <Spinner size="xxs" className="mx-0" />
                                                <span>{Craft.t('formie', 'Refreshing {name} options…', { name: selectedIntegrationLabel })}</span>
                                            </div>
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
                            </div>
                        )}

                        {sourceType === 'provider' && (
                            <div className="space-y-2">
                                <FieldRoot name="registeredSourceSettings">
                                    <FieldHeader className="space-y-0.5">
                                        <FieldLabel>{Craft.t('formie', 'Source')}</FieldLabel>
                                        <FieldInstructions>
                                            {Craft.t('formie', 'Choose a registered custom provider and configure its settings.')}
                                        </FieldInstructions>
                                    </FieldHeader>
                                </FieldRoot>
                                {showRegisteredChainSkeleton ? (
                                    <IntegrationChainSkeleton
                                        message={Craft.t('formie', 'Loading custom providers…')}
                                    />
                                ) : (
                                    <>
                                        {registeredChainSteps.length > 0 ? (
                                            <IntegrationSourceChain steps={registeredChainSteps} />
                                        ) : registeredProvider ? (
                                            <p className="text-sm text-gray-600">{registeredProviderLabel}</p>
                                        ) : null}

                                        {registeredConfigError && !loadingRegisteredConfig && registeredProvider && (
                                            <p className="text-sm text-red-600">{registeredConfigError}</p>
                                        )}

                                        {loadingRegisteredDetails && hasCurrentRegisteredDetails && (
                                            <div className="flex items-center gap-2 text-xs text-gray-500">
                                                <Spinner size="xxs" className="mx-0" />
                                                <span>{Craft.t('formie', 'Refreshing {name} options…', { name: registeredProviderLabel })}</span>
                                            </div>
                                        )}

                                        {!loadingRegisteredList && registeredProviderOptions.length === 0 && (
                                            <p className="text-sm text-amber-700">
                                                {Craft.t('formie', 'No custom providers are registered for this field type.')}
                                            </p>
                                        )}

                                        {registeredConfig?.warning && !loadingRegisteredDetails && (
                                            <p className="text-sm text-amber-700">{registeredConfig.warning}</p>
                                        )}
                                    </>
                                )}
                            </div>
                        )}

                        <DynamicOptionsPreview
                            rows={displayPreviewRows}
                            totalCount={displayPreviewCount}
                            displayType={displayType}
                            loading={previewLoading}
                            loadingMessage={previewLoadingMessage}
                            placeholder={previewPlaceholder}
                            previewError={previewError}
                            expanded={previewExpanded}
                            sourceType={sourceType}
                            busy={busy}
                            loadingIntegrationConfig={loadingIntegrationConfig}
                            controlsLoading={loadingPredefinedConfig || loadingIntegrationConfig || loadingRegisteredConfig}
                            selectedIntegrationHandle={selectedIntegrationHandle}
                            showValuesInPreview={!hidePreviewValues}
                            mappingSettings={
                                sourceType === 'predefined'
                                && predefinedControlsReady
                                && predefinedHasConfigurableKeys
                                    ? {
                                        labelKey: effectiveLabelKey,
                                        valueKey: effectiveValueKey,
                                        labelOptions,
                                        valueOptions,
                                        disabled: loadingPredefinedConfig,
                                        onLabelChange: (nextLabelKey) => updateParams({ labelKey: nextLabelKey }),
                                        onValueChange: (nextValueKey) => updateParams({ valueKey: nextValueKey }),
                                    }
                                    : null
                            }
                            onExpandedChange={setPreviewExpanded}
                            onRefreshPreview={resolveDynamicPreview}
                            onRefreshIntegrationData={handleRefreshIntegrationData}
                            onConvertClick={() => { setConvertDialogOpen(true); }}
                        />

                        <ConvertToStaticDialog
                            open={convertDialogOpen}
                            onOpenChange={setConvertDialogOpen}
                            sourceLabel={convertSourceLabel}
                            busy={busy}
                            onConfirm={handleConvertToStatic}
                        />
                    </div>
                )}

        </div>
    );
}

export { OptionDynamicSettingsField };
export { OptionDynamicSettingsField as OptionSourceSettingsField };
