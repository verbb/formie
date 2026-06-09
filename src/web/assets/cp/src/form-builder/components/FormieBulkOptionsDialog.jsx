import { useCallback, useEffect, useState } from 'react';

import {
    Button,
    ToggleGroup,
    ToggleGroupItem,
    SelectInput,
    Textarea,
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
    Spinner,
} from '@verbb/plugin-kit-react/components';
import {
    FieldRoot,
    FieldHeader,
    FieldLabel,
    FieldInstructions,
    FieldControl,
} from '@verbb/plugin-kit-react/forms';
import { useTranslation } from '@verbb/plugin-kit-react/hooks';
import { cn, getErrorMessage, hostRequest } from '@verbb/plugin-kit-react/utils';

import { buildBulkPreview, parseBulkPreviewRows, countBulkPreviewRows } from './bulkOptions.utils';

function FormieBulkOptionsDialog({
    open,
    onOpenChange,
    predefinedOptions = [],
    bulkOptionsAction,
    onSave,
}) {
    const t = useTranslation();
    const [bulkMode, setBulkMode] = useState('append');
    const [bulkLoading, setBulkLoading] = useState(false);
    const [bulkError, setBulkError] = useState(null);
    const [selectedPredefinedOption, setSelectedPredefinedOption] = useState('');
    const [availableBulkItems, setAvailableBulkItems] = useState([]);
    const [bulkLabelOptions, setBulkLabelOptions] = useState([]);
    const [bulkValueOptions, setBulkValueOptions] = useState([]);
    const [bulkLabelOption, setBulkLabelOption] = useState('');
    const [bulkValueOption, setBulkValueOption] = useState('');
    const [bulkPreview, setBulkPreview] = useState('');

    useEffect(() => {
        if (!open || selectedPredefinedOption || predefinedOptions.length === 0) {
            return;
        }

        setSelectedPredefinedOption(String(predefinedOptions[0].value ?? ''));
    }, [open, predefinedOptions, selectedPredefinedOption]);

    const fetchBulkOptions = useCallback(async(optionValue) => {
        if (!optionValue) {
            return;
        }

        setBulkLoading(true);
        setBulkError(null);

        try {
            if (!bulkOptionsAction) {
                throw new Error('Formie bulk options require "bulkOptionsAction".');
            }

            const response = await hostRequest('POST', bulkOptionsAction, {
                data: { option: optionValue },
            });

            const payload = response?.data || {};
            if (payload.error) {
                throw new Error(String(payload.error));
            }

            const items = Array.isArray(payload.data) ? payload.data : [];
            const labelOptions = Array.isArray(payload.labelOptions) ? payload.labelOptions : [];
            const valueOptions = Array.isArray(payload.valueOptions) ? payload.valueOptions : [];
            const selectedLabel = String(payload.labelOption ?? labelOptions[0]?.value ?? '');
            const selectedValue = String(payload.valueOption ?? valueOptions[0]?.value ?? selectedLabel);

            setAvailableBulkItems(items);
            setBulkLabelOptions(labelOptions);
            setBulkValueOptions(valueOptions);
            setBulkLabelOption(selectedLabel);
            setBulkValueOption(selectedValue);
            setBulkPreview(buildBulkPreview(items, selectedLabel, selectedValue));
        } catch (error) {
            setBulkError(getErrorMessage(error));
            setAvailableBulkItems([]);
            setBulkLabelOptions([]);
            setBulkValueOptions([]);
            setBulkLabelOption('');
            setBulkValueOption('');
            setBulkPreview('');
        } finally {
            setBulkLoading(false);
        }
    }, [bulkOptionsAction]);

    useEffect(() => {
        if (!open || !selectedPredefinedOption) {
            return;
        }

        fetchBulkOptions(selectedPredefinedOption);
    }, [fetchBulkOptions, open, selectedPredefinedOption]);

    useEffect(() => {
        if (!open || availableBulkItems.length === 0 || !bulkLabelOption || !bulkValueOption) {
            return;
        }

        setBulkPreview(buildBulkPreview(availableBulkItems, bulkLabelOption, bulkValueOption));
    }, [availableBulkItems, bulkLabelOption, bulkValueOption, open]);

    const handleSave = () => {
        onSave(parseBulkPreviewRows(bulkPreview), bulkMode);
        onOpenChange(false);
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className={cn(
                'w-[66%] h-[66%]',
                'max-w-auto',
                'min-w-[600px]',
                'min-h-[400px]',
            )}>
                <DialogHeader>
                    <DialogTitle>{t('Bulk Add Options')}</DialogTitle>
                    <DialogDescription>
                        {t('Select from predefined options and customize or paste your own to bulk add options.')}
                    </DialogDescription>
                </DialogHeader>

                <div className="h-full overflow-y-auto">
                    <div className="grid grid-cols-2 gap-4 p-4">
                        <div className="space-y-4">
                            <FieldRoot name="bulk-predefined-options">
                                <FieldHeader className="space-y-0.5">
                                    <FieldLabel>{t('Predefined Options')}</FieldLabel>
                                    <FieldInstructions>
                                        {t('Select which predefined option set to import from.')}
                                    </FieldInstructions>
                                </FieldHeader>

                                <FieldControl>
                                    <SelectInput
                                        options={predefinedOptions}
                                        value={selectedPredefinedOption}
                                        onChange={(value) => {
                                            setSelectedPredefinedOption(String(value ?? ''));
                                        }}
                                    />
                                </FieldControl>
                            </FieldRoot>

                            {bulkLoading && <Spinner className="p-4 mt-8" />}

                            {bulkError && (
                                <div className="text-sm text-rose-600 space-y-1">
                                    {bulkError.heading && <div className="font-semibold">{bulkError.heading}</div>}
                                    {bulkError.text && <div>{bulkError.text}</div>}
                                    {bulkError.traceAsArray?.length > 0 && (
                                        <div className="text-[10px] font-mono mt-2 opacity-80">
                                            {bulkError.traceAsArray.map((line, index) => {
                                                return (
                                                    <div key={index} className="whitespace-pre-wrap">
                                                        {line}
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    )}
                                </div>
                            )}

                            {!bulkLoading && bulkLabelOptions.length > 0 && (
                                <FieldRoot name="bulk-option-label">
                                    <FieldHeader className="space-y-0.5">
                                        <FieldLabel>{t('Option Label')}</FieldLabel>
                                        <FieldInstructions>
                                            {t('Choose which source field is used as the label.')}
                                        </FieldInstructions>
                                    </FieldHeader>

                                    <FieldControl>
                                        <SelectInput
                                            options={bulkLabelOptions}
                                            value={bulkLabelOption}
                                            onChange={(value) => {
                                                setBulkLabelOption(String(value ?? ''));
                                            }}
                                        />
                                    </FieldControl>
                                </FieldRoot>
                            )}

                            {!bulkLoading && bulkValueOptions.length > 0 && (
                                <FieldRoot name="bulk-option-value">
                                    <FieldHeader className="space-y-0.5">
                                        <FieldLabel>{t('Option Value')}</FieldLabel>
                                        <FieldInstructions>
                                            {t('Choose which source field is used as the value.')}
                                        </FieldInstructions>
                                    </FieldHeader>

                                    <FieldControl>
                                        <SelectInput
                                            options={bulkValueOptions}
                                            value={bulkValueOption}
                                            onChange={(value) => {
                                                setBulkValueOption(String(value ?? ''));
                                            }}
                                        />
                                    </FieldControl>
                                </FieldRoot>
                            )}

                            {!bulkLoading && bulkLabelOptions.length > 0 && (
                                <FieldRoot name="bulk-import-mode">
                                    <FieldHeader className="space-y-0.5">
                                        <FieldLabel>{t('Add Mode')}</FieldLabel>
                                        <FieldInstructions>
                                            {t('Append adds to existing rows, replace overwrites them.')}
                                        </FieldInstructions>
                                    </FieldHeader>

                                    <ToggleGroup
                                        value={bulkMode}
                                        onValueChange={(value) => {
                                            const resolvedValue = Array.isArray(value) ? value[0] : value;

                                            if (resolvedValue === 'append' || resolvedValue === 'replace') {
                                                setBulkMode(resolvedValue);
                                            }
                                        }}
                                        variant="outline"
                                        spacing={0}
                                    >
                                        <ToggleGroupItem value="append">{t('Append')}</ToggleGroupItem>
                                        <ToggleGroupItem value="replace">{t('Replace')}</ToggleGroupItem>
                                    </ToggleGroup>
                                </FieldRoot>
                            )}
                        </div>

                        <div className="flex min-h-[260px] flex-col">
                            <FieldRoot name="bulk-preview">
                                <FieldHeader className="space-y-0.5">
                                    <FieldLabel>
                                        {t('Preview')}
                                        {bulkPreview.trim() !== '' && (
                                            <span className="ml-2 font-normal text-gray-500">
                                                ({t('{count} options', { count: countBulkPreviewRows(bulkPreview) })})
                                            </span>
                                        )}
                                    </FieldLabel>
                                </FieldHeader>

                                <FieldControl className="flex flex-1 flex-col">
                                    <Textarea
                                        className="min-h-[260px] flex-1 text-xs"
                                        value={bulkPreview}
                                        onChange={(event) => { setBulkPreview(event.target.value); }}
                                    />
                                </FieldControl>
                            </FieldRoot>
                        </div>
                    </div>
                </div>

                <DialogFooter className="gap-2">
                    <Button type="button" onClick={() => { onOpenChange(false); }}>
                        {t('Cancel')}
                    </Button>
                    <Button type="button" variant="primary" onClick={handleSave}>
                        {t('Add Options')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

export { FormieBulkOptionsDialog };
