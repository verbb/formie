import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
    Button,
    DropdownMenuItem,
    Input,
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@verbb/plugin-kit-react/components';
import { useVariablePicker } from '@verbb/plugin-kit-react/components/tiptap/useVariablePicker';
import { VariableCommandList } from '@verbb/plugin-kit-react/components/tiptap/VariableCommandList';
import { VariableTransformControls } from '@verbb/plugin-kit-react/components/tiptap/VariableTransformControls';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faChevronDown } from '@fortawesome/pro-solid-svg-icons';
import { cn } from '@verbb/plugin-kit-react/utils';
import { useTranslation } from '@verbb/plugin-kit-react/hooks';
import {
    buildTransformOptions,
    buildVariablePickerGroups,
    findOptionLabelByValue,
    findVariableOptionByValue,
    getComparableTokenValue,
    parseVariableTokenMetadata,
    resolveRepeaterConfigureOption,
    serializeVariableTokenMetadata,
} from '@form-builder/fields/utils/variablePicker';
import { VariablePickerActionsMenu } from './VariablePickerActionsMenu';
import { RepeaterRowTargetingControls } from './RepeaterRowTargetingControls';
import {
    applyRepeaterRowTargetingToToken,
    isRepeaterSubFieldOption,
    parseRepeaterRowTargeting,
    resolveRepeaterVariableDisplayLabel,
    shouldShowRepeaterRowTargeting,
} from '@form-builder/fields/utils/repeaterRowTargeting';

export function FormBuilderVariablePickerControl({
    value = '',
    onChange,
    isInvalid = false,
    variableCategories = {},
    variableCategoryLabels = {},
    variableCategoryOrder = [],
    variableOptionIndex = null,
    variableTransformerRegistry = {},
    noneOptionLabel,
    pickerSearchPlaceholder,
    includeParentLabel = false,
    pickerContentClassName = 'min-w-[260px] max-w-[360px] p-0 overflow-hidden flex flex-col',
    triggerClassName = 'min-w-0 text-[11px] flex-1 py-[5px] justify-between',
    wrapperClassName = '',
    alwaysShowActionsMenu = true,
    showActionsMenu = true,
    includeNoneOptionInPicker = true,
    renderActionItems = null,
    settingsPopoverClassName = 'min-w-[260px] max-w-[360px] p-2',
}) {
    const t = useTranslation();
    const [pickerOpen, setPickerOpen] = useState(false);
    const [settingsOpen, setSettingsOpen] = useState(false);
    const [defaultIfEmpty, setDefaultIfEmpty] = useState('');
    const [transformerId, setTransformerId] = useState('');
    const [transformerParams, setTransformerParams] = useState({});
    const [settingsSessionKey, setSettingsSessionKey] = useState(0);
    const [targetingRevision, setTargetingRevision] = useState(0);
    const settingsWasOpenRef = useRef(false);
    const rowTargetingRef = useRef(parseRepeaterRowTargeting(String(value || '')));
    const handleRowTargetingChange = useCallback((nextTargeting) => {
        rowTargetingRef.current = nextTargeting;
        setTargetingRevision((current) => current + 1);
    }, []);
    const comparableValue = useMemo(() => { return getComparableTokenValue(String(value || '')); }, [value]);
    const selectedTokenMeta = useMemo(() => { return parseVariableTokenMetadata(String(value || '')); }, [value]);
    const resolvedNoneOptionLabel = noneOptionLabel || t('Select an option');
    const resolvedSearchPlaceholder = pickerSearchPlaceholder || t('Search values');

    const picker = useVariablePicker({
        variableCategories,
        variableCategoryLabels,
        variableCategoryOrder,
        isOpen: pickerOpen,
        deferUntilOpen: true,
        onApply: (_baseVariable, variable) => {
            onChange(String(variable?.value || ''));
            setPickerOpen(false);
        },
    });

    useEffect(() => {
        if (settingsOpen && !settingsWasOpenRef.current) {
            setSettingsSessionKey((current) => current + 1);
        }

        settingsWasOpenRef.current = settingsOpen;
    }, [settingsOpen]);

    useEffect(() => {
        setDefaultIfEmpty(String(selectedTokenMeta.defaultIfEmpty || ''));
        setTransformerId(String(selectedTokenMeta.transformerId || ''));
        setTransformerParams(
            selectedTokenMeta.transformerParams && typeof selectedTokenMeta.transformerParams === 'object'
                ? selectedTokenMeta.transformerParams
                : {},
        );
        rowTargetingRef.current = parseRepeaterRowTargeting(String(value || ''));
    }, [selectedTokenMeta.defaultIfEmpty, selectedTokenMeta.transformerId, selectedTokenMeta.transformerParams, value]);

    const selectedVariableOption = useMemo(() => {
        const indexedOption = variableOptionIndex?.optionByValue?.get?.(comparableValue);
        const resolvedOption = resolveRepeaterConfigureOption(variableCategories, comparableValue, {
            fallbackLabel: indexedOption?.label || '',
            variableOption: indexedOption || null,
        });

        if (resolvedOption) {
            return resolvedOption;
        }

        if (indexedOption) {
            return indexedOption;
        }

        return findVariableOptionByValue(variableCategories, comparableValue);
    }, [variableCategories, variableOptionIndex, comparableValue]);

    const selectedLabel = useMemo(() => {
        if (isRepeaterSubFieldOption(selectedVariableOption)) {
            if (settingsOpen && shouldShowRepeaterRowTargeting(comparableValue, selectedVariableOption)) {
                const previewToken = applyRepeaterRowTargetingToToken(comparableValue, rowTargetingRef.current);

                return resolveRepeaterVariableDisplayLabel(previewToken, selectedVariableOption, t) || resolvedNoneOptionLabel;
            }

            return resolveRepeaterVariableDisplayLabel(comparableValue, selectedVariableOption, t) || resolvedNoneOptionLabel;
        }

        const indexedLabel = variableOptionIndex?.labelByValue?.get?.(comparableValue);
        if (indexedLabel) {
            return indexedLabel;
        }

        return findOptionLabelByValue(variableCategories, comparableValue, {
            emptyLabel: resolvedNoneOptionLabel,
            includeParentLabel,
            t,
        }) || resolvedNoneOptionLabel;
    }, [variableCategories, variableOptionIndex, comparableValue, resolvedNoneOptionLabel, includeParentLabel, selectedVariableOption, settingsOpen, targetingRevision, t]);

    const transformOptions = useMemo(() => {
        return buildTransformOptions(selectedVariableOption, variableTransformerRegistry || {});
    }, [selectedVariableOption, variableTransformerRegistry]);

    const selectedTransformer = useMemo(() => {
        return transformOptions.find((option) => { return option.value === transformerId; }) || null;
    }, [transformOptions, transformerId]);

    const hasIncompatibleTransformerSelection = useMemo(() => {
        const current = String(transformerId || '').trim();
        return current !== '' && !selectedTransformer;
    }, [selectedTransformer, transformerId]);

    useEffect(() => {
        if (!selectedTransformer) {
            return;
        }

        setTransformerParams((current) => {
            const next = { ...current };
            (selectedTransformer.params || []).forEach((param) => {
                if (next[param.name] == null || next[param.name] === '') {
                    next[param.name] = param.default == null ? '' : String(param.default);
                }
            });

            return next;
        });
    }, [selectedTransformer]);

    const pickerGroups = useMemo(() => {
        const hasSearchQuery = Boolean(String(picker.search || '').trim());

        return buildVariablePickerGroups({
            groups: Array.isArray(picker.groups) ? picker.groups : [],
            pickerPage: picker.page,
            noneOption: includeNoneOptionInPicker && !hasSearchQuery ? { label: resolvedNoneOptionLabel, value: '__none__' } : null,
            fallbackFieldsLabel: t('Fields'),
        });
    }, [includeNoneOptionInPicker, resolvedNoneOptionLabel, picker.groups, picker.page, picker.search, t]);

    const canShowSettings = Boolean(comparableValue && selectedVariableOption);
    const settingsTokenValue = useMemo(() => {
        if (!settingsOpen) {
            return comparableValue;
        }

        if (!shouldShowRepeaterRowTargeting(comparableValue, selectedVariableOption)) {
            return comparableValue;
        }

        return applyRepeaterRowTargetingToToken(comparableValue, rowTargetingRef.current);
    }, [comparableValue, selectedVariableOption, settingsOpen, targetingRevision]);
    const hasDefaultIndicator = Boolean(String(selectedTokenMeta.defaultIfEmpty || '').trim());
    const hasTransformIndicator = Boolean(String(selectedTokenMeta.transformerId || '').trim());
    const hasSelectedValue = Boolean(String(comparableValue || '').trim());
    const shouldShowActionsMenu = Boolean(showActionsMenu && (alwaysShowActionsMenu || hasSelectedValue));

    useEffect(() => {
        if (!canShowSettings && settingsOpen) {
            setSettingsOpen(false);
        }
    }, [canShowSettings, settingsOpen]);

    const saveConfiguration = () => {
        let baseToken = selectedTokenMeta.tokenWithoutDefault || comparableValue;
        if (!baseToken) {
            setSettingsOpen(false);
            return;
        }

        if (shouldShowRepeaterRowTargeting(comparableValue, selectedVariableOption)) {
            baseToken = applyRepeaterRowTargetingToToken(baseToken, rowTargetingRef.current);
        }

        onChange(serializeVariableTokenMetadata(baseToken, {
            defaultIfEmpty,
            transformerId,
            transformerParams,
        }));
        setSettingsOpen(false);
    };

    const defaultActionItems = (
        <>
            <DropdownMenuItem
                disabled={!canShowSettings}
                onClick={() => {
                    if (canShowSettings) {
                        setSettingsOpen(true);
                    }
                }}
            >
                {t('Configure Value')}
            </DropdownMenuItem>
            <DropdownMenuItem onClick={() => { onChange(''); }}>
                {resolvedNoneOptionLabel}
            </DropdownMenuItem>
        </>
    );

    const actionItems = renderActionItems
        ? renderActionItems({
            t,
            hasSelectedValue,
            canShowSettings,
            openSettings: () => { if (canShowSettings) { setSettingsOpen(true); } },
            clearValue: () => { onChange(''); },
            setPickerOpen,
        })
        : defaultActionItems;

    return (
        <div className={cn('flex items-center', wrapperClassName)}>
            <Popover modal={false} open={pickerOpen} onOpenChange={setPickerOpen}>
                <PopoverTrigger
                    nativeButton={true}
                    render={(
                        <Button
                            size="sm"
                            variant="default"
                            className={cn('min-w-0 flex-1 py-[6px] justify-between', isInvalid && 'border-error', triggerClassName)}
                        >
                            <span className="truncate flex-1 text-left">{selectedLabel}</span>
                            <span className="ml-2 inline-flex items-center gap-1 shrink-0">
                                {hasDefaultIndicator && (
                                    <span className="shrink-0 rounded-[2px] bg-gray-50 px-1.5 py-[1px] text-[10px] text-gray-500 user-select-none">
                                        {t('default')}
                                    </span>
                                )}
                                {hasTransformIndicator && (
                                    <span className="shrink-0 rounded-[2px] bg-gray-50 px-1.5 py-[1px] text-[10px] text-gray-500 user-select-none">
                                        {selectedTokenMeta.transformerId}
                                    </span>
                                )}
                                <FontAwesomeIcon icon={faChevronDown} className="size-2.5 pointer-events-none shrink-0" />
                            </span>
                        </Button>
                    )}
                />
                <PopoverContent
                    align="start"
                    side="bottom"
                    sideOffset={6}
                    positionMethod="fixed"
                    collisionAvoidance={{
                        side: 'flip',
                        align: 'shift',
                        fallbackAxisSide: 'none',
                    }}
                    className={pickerContentClassName}
                    portalClassName="z-250"
                >
                    <VariableCommandList
                        search={picker.search}
                        onSearchChange={picker.setSearch}
                        groups={pickerGroups}
                        options={picker.options}
                        onSelect={(item, baseVariable) => {
                            if (item?.value === '__none__') {
                                onChange('');
                                setPickerOpen(false);
                                return;
                            }

                            picker.handleSelect(item, baseVariable);
                        }}
                        placeholder={resolvedSearchPlaceholder}
                        showSearch
                        shouldFilter={false}
                        onBack={picker.page ? picker.handleBack : undefined}
                        isChildMode={!!picker.page}
                        selectFirstItem
                        autoFocusSearchInput={true}
                    />
                </PopoverContent>
            </Popover>

            {shouldShowActionsMenu && (
                <VariablePickerActionsMenu label={t('More actions')}>
                    {actionItems}
                </VariablePickerActionsMenu>
            )}

            <Popover modal={false} open={settingsOpen} onOpenChange={setSettingsOpen}>
                <PopoverTrigger
                    nativeButton={true}
                    render={(
                        <Button
                            type="button"
                            size="xs"
                            variant="none"
                            aria-hidden={true}
                            tabIndex={-1}
                            className="pointer-events-none h-0 w-0 overflow-hidden p-0 opacity-0"
                        />
                    )}
                />
                <PopoverContent
                    align="end"
                    side="bottom"
                    sideOffset={6}
                    positionMethod="fixed"
                    collisionAvoidance={{
                        side: 'flip',
                        align: 'shift',
                        fallbackAxisSide: 'none',
                    }}
                    className={settingsPopoverClassName}
                    portalClassName="z-250"
                >
                    <RepeaterRowTargetingControls
                        key={settingsOpen ? String(settingsSessionKey) : 'closed'}
                        tokenValue={settingsTokenValue}
                        variableOption={selectedVariableOption}
                        targetingRef={rowTargetingRef}
                        resetKey={settingsOpen ? String(settingsSessionKey) : 'closed'}
                        onTargetingChange={handleRowTargetingChange}
                    />
                    <label className="mb-1 block text-[11px] text-gray-500">
                        {t('Default if empty (optional)')}
                    </label>
                    <Input
                        type="text"
                        value={defaultIfEmpty}
                        onChange={(event) => { setDefaultIfEmpty(event.target.value); }}
                        onKeyDown={(event) => {
                            if (event.key === 'Enter') {
                                event.preventDefault();
                                event.stopPropagation();
                                saveConfiguration();
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
                            setTransformerParams((current) => {
                                return {
                                    ...current,
                                    [paramName]: nextValue,
                                };
                            });
                        }}
                    />

                    <div className="mt-3 -mx-2 -mb-2 flex items-center justify-between gap-2 border-t border-slate-200 bg-[#f3f7fd] px-2 py-2">
                        <Button
                            type="button"
                            variant="default"
                            size="sm"
                            className="text-[11px]"
                            onClick={() => {
                                setSettingsOpen(false);
                                setPickerOpen(true);
                            }}
                        >
                            {t('Change variable')}
                        </Button>
                        <Button type="button" variant="primary" size="sm" className="text-[11px]" onClick={saveConfiguration}>
                            {t('Save')}
                        </Button>
                    </div>
                </PopoverContent>
            </Popover>
        </div>
    );
}
