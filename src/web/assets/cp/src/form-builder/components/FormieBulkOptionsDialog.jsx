import { useCallback, useEffect, useState } from 'react';

import { getErrorMessage } from '@verbb/plugin-kit-core';
import { hostRequest } from '@verbb/plugin-kit-react/utils';
import { useTranslation } from '@verbb/plugin-kit-react/hooks';
import { Button, Dialog, ToggleGroup, Toggle, SelectInput, Textarea, Spinner } from '@verbb/plugin-kit-react/components';
import { FieldLayout } from '@verbb/plugin-kit-react/forms';
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
        if (!open) {
            return;
        }

        // Re-open always starts on Append (v1 default), even if the last
        // session left Replace selected while this dialog stayed mounted.
        setBulkMode('append');
    }, [open]);

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
        <Dialog
            open={open}
            label={t('Bulk Add Options')}
            description={t('Select from predefined options and customize or paste your own to bulk add options.')}
            className="formie-bulk-options-dialog"
            withoutBodyPadding
            onPkOpenChange={(event) => {
                onOpenChange(event.detail?.open ?? event.target?.open ?? false);
            }}
        >
            {/*
              v1: description lives in the dialog header; body is a padded
              two-column grid. Dialog height is fit-content (400px floor) so
              the empty state stays compact like v1 rather than a tall void.
            */}
            <div className="formie-bulk-options-dialog-body">
                <div className="grid grid-cols-2 gap-4 p-4">
                    <div className="space-y-4">
                        <FieldLayout
                            name="bulk-predefined-options"
                            label={t('Predefined Options')}
                            instructions={t('Select which predefined option set to import from.')}
                        >
                            <SelectInput
                                options={predefinedOptions}
                                value={selectedPredefinedOption}
                                onChange={(value) => {
                                    setSelectedPredefinedOption(String(value ?? ''));
                                }}
                            />
                        </FieldLayout>

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
                            <FieldLayout
                                name="bulk-option-label"
                                label={t('Option Label')}
                                instructions={t('Choose which source field is used as the label.')}
                            >
                                <SelectInput
                                    options={bulkLabelOptions}
                                    value={bulkLabelOption}
                                    onChange={(value) => {
                                        setBulkLabelOption(String(value ?? ''));
                                    }}
                                />
                            </FieldLayout>
                        )}

                        {!bulkLoading && bulkValueOptions.length > 0 && (
                            <FieldLayout
                                name="bulk-option-value"
                                label={t('Option Value')}
                                instructions={t('Choose which source field is used as the value.')}
                            >
                                <SelectInput
                                    options={bulkValueOptions}
                                    value={bulkValueOption}
                                    onChange={(value) => {
                                        setBulkValueOption(String(value ?? ''));
                                    }}
                                />
                            </FieldLayout>
                        )}

                        {!bulkLoading && bulkLabelOptions.length > 0 && (
                            <FieldLayout
                                name="bulk-import-mode"
                                label={t('Add Mode')}
                                instructions={t('Append adds to existing rows, replace overwrites them.')}
                            >
                                {/*
                                  pk-toggle-group is array-valued and uses data-value +
                                  pk-value-change (not React ToggleGroupItem / onValueChange).
                                */}
                                <ToggleGroup
                                    value={[bulkMode]}
                                    onPkValueChange={(event) => {
                                        const next = event.detail?.value;
                                        const resolvedValue = Array.isArray(next) ? next[0] : next;

                                        // Exclusive mode can emit [] when re-clicking the
                                        // active item — keep a mode selected (Append default).
                                        if (resolvedValue === 'append' || resolvedValue === 'replace') {
                                            setBulkMode(resolvedValue);
                                        } else {
                                            setBulkMode('append');
                                        }
                                    }}
                                    variant="outline"
                                    spacing={0}
                                >
                                    <Toggle data-value="append">{t('Append')}</Toggle>
                                    <Toggle data-value="replace">{t('Replace')}</Toggle>
                                </ToggleGroup>
                            </FieldLayout>
                        )}
                    </div>

                    <div className="formie-bulk-options-preview flex min-h-0 flex-col">
                        <FieldLayout
                            name="bulk-preview"
                            className="formie-bulk-options-preview-field flex min-h-0 flex-1 flex-col"
                        >
                            {/*
                              Count stays regular weight — pk-field label slot inherits bold,
                              so only the "Preview" word should pick that up.
                            */}
                            <span slot="label">
                                {t('Preview')}
                                {bulkPreview.trim() !== '' && (
                                    <span className="ml-2 font-normal text-gray-500">
                                        ({t('{count} options', { count: countBulkPreviewRows(bulkPreview) })})
                                    </span>
                                )}
                            </span>
                            <Textarea
                                className="formie-bulk-options-preview-textarea text-xs"
                                value={bulkPreview}
                                onChange={(event) => { setBulkPreview(event.target.value); }}
                            />
                        </FieldLayout>
                    </div>
                </div>
            </div>

            <Button slot="footer" type="button" onClick={() => { onOpenChange(false); }}>
                {t('Cancel')}
            </Button>
            <Button slot="footer" type="button" variant="primary" onClick={handleSave}>
                {t('Add Options')}
            </Button>
        </Dialog>
    );
}

export { FormieBulkOptionsDialog };
