import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Button, DropdownItem, Icon, Input, Popover } from '@verbb/plugin-kit-react/components';
import { useVariablePicker } from '@form-builder/fields/variable-picker/useVariablePicker';
import { VariableCommandList } from '@form-builder/fields/variable-picker/VariableCommandList';
import { VariableTransformControls } from '@form-builder/fields/variable-picker/VariableTransformControls';
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

const syncPopoverOpen = (event, setOpen) => {
    setOpen(Boolean(event.detail?.open ?? event.target?.open));
};

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
    // Default xs matches EditableTable Condition/Value `pk-select` chips.
    triggerSize = 'xs',
    triggerClassName = 'min-w-0 flex-1 justify-between',
    // Extra classes on the trigger Popover host (e.g. conditions override align-self).
    popoverClassName = 'min-w-0 flex-1',
    wrapperClassName = '',
    // Extra classes on the inner flex row (ET cells need an explicit row height to center).
    rowClassName = '',
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
            <DropdownItem
                disabled={!canShowSettings}
                onPkSelect={() => {
                    if (canShowSettings) {
                        setSettingsOpen(true);
                    }
                }}
            >
                {t('Configure Value')}
            </DropdownItem>
            <DropdownItem onPkSelect={() => { onChange(''); }}>
                {resolvedNoneOptionLabel}
            </DropdownItem>
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
        <div className={cn('relative box-border w-full', wrapperClassName)}>
            {/*
             * Keep trigger + ⋯ on an inner flex row. Conditions pass an explicit
             * `h-[34px]` (ET cell) + `items-center`. Note: pk-popover :host sets
             * `align-self: flex-start`, which would otherwise pin the Field chip to
             * the top of the row — override via `popoverClassName` / CSS.
             */}
            <div className={cn('flex w-full items-center', rowClassName)}>
                {/*
                 * Popover host defaults to flex:none — conditions CSS grows it so the
                 * trigger fills the row. Flush panel keeps its own min/max width.
                 */}
                <Popover
                    open={pickerOpen}
                    flush
                    placement="bottom-start"
                    sideOffset={6}
                    className={popoverClassName}
                    onPkOpenChange={(event) => { syncPopoverOpen(event, setPickerOpen); }}
                >
                    <Button
                        slot="trigger"
                        size={triggerSize}
                        variant="default"
                        className={cn(
                            'w-full min-w-0 justify-between',
                            '[&::part(base)]:w-full [&::part(base)]:justify-between',
                            isInvalid && 'border-error',
                            triggerClassName,
                        )}
                    >
                        <span className="truncate flex-1 text-left">{selectedLabel}</span>
                        <span slot="end" className="inline-flex items-center gap-1 shrink-0">
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
                            <Icon icon="chevron-down" className="size-2.5 pointer-events-none shrink-0" />
                        </span>
                    </Button>
                    <div className={pickerContentClassName}>
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
                            open={pickerOpen}
                        />
                    </div>
                </Popover>

                {shouldShowActionsMenu && (
                    <VariablePickerActionsMenu label={t('More actions')}>
                        {actionItems}
                    </VariablePickerActionsMenu>
                )}
            </div>

            {/*
              Settings opens programmatically from the actions menu. Anchor must exist
              for pk-popover placement, but the host defaults to ~21px inline-block even
              with an h-0 trigger — that alone stretched ET Field cells to ~56px and
              dropped Condition/Value (middle-aligned) below the Field chip. Pull the
              host out of flow.
             */}
            <Popover
                open={settingsOpen}
                flush
                placement="bottom-end"
                sideOffset={6}
                className="pointer-events-none absolute right-0 top-0 h-0 w-0 overflow-hidden opacity-0"
                onPkOpenChange={(event) => { syncPopoverOpen(event, setSettingsOpen); }}
            >
                <Button
                    slot="trigger"
                    type="button"
                    size="xs"
                    variant="none"
                    aria-hidden={true}
                    tabIndex={-1}
                    className="pointer-events-none h-0 w-0 overflow-hidden p-0 opacity-0"
                />
                <div className={settingsPopoverClassName}>
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
                </div>
            </Popover>
        </div>
    );
}
