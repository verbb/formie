import { useMemo, useState } from 'react';

import {
    Button,
    Combobox,
    ComboboxCollection,
    ComboboxContent,
    ComboboxEmpty,
    ComboboxGroup,
    ComboboxHighlightedText,
    ComboboxItem,
    ComboboxLabel,
    ComboboxList,
    ComboboxPrimitiveInput,
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@verbb/plugin-kit-react/components';
import { useTranslation } from '@verbb/plugin-kit-react/hooks';

import {
    collectMappableFields,
    getTemplateFieldSlots,
} from '@form-builder/utils/clientEventTemplates';

function buildGroupedFieldOptions(pages, fieldTypes, t) {
    const groups = [{
        value: '__empty',
        label: '',
        items: [{
            label: t('Select a field'),
            value: '',
            displayLabel: t('Select a field'),
        }],
    }];

    (Array.isArray(pages) ? pages : []).forEach((page) => {
        const pageLabel = String(page?.label || page?.name || '').trim() || t('Fields');
        const fields = collectMappableFields([page], fieldTypes).map((field) => ({
            ...field,
            displayLabel: field.label,
        }));

        if (!fields.length) {
            return;
        }

        groups.push({
            value: pageLabel,
            label: pageLabel,
            items: fields,
        });
    });

    return groups;
}

function FieldMappingCombobox({
    options,
    value,
    onChange,
    placeholder,
}) {
    const t = useTranslation();
    const [searchValue, setSearchValue] = useState('');
    const selectedOption = useMemo(() => {
        if (!value) {
            return options[0]?.items?.[0] ?? null;
        }

        for (const group of options) {
            const match = group.items.find((option) => option.value === value);

            if (match) {
                return match;
            }
        }

        return null;
    }, [options, value]);

    return (
        <Combobox
            items={options}
            value={selectedOption}
            onValueChange={(nextOption) => {
                onChange(nextOption?.value ?? '');
            }}
            onInputValueChange={(nextValue) => {
                setSearchValue(nextValue);
            }}
            onOpenChange={(open) => {
                if (!open) {
                    setSearchValue('');
                }
            }}
            itemToStringLabel={(item) => item?.displayLabel ?? item?.label ?? ''}
            itemToStringValue={(item) => String(item?.value ?? '')}
        >
            <ComboboxPrimitiveInput
                placeholder={placeholder || t('Select a field')}
                showClear={false}
            />

            <ComboboxContent>
                <ComboboxEmpty>{t('No fields found.')}</ComboboxEmpty>

                <ComboboxList>
                    <ComboboxCollection>
                        {(group) => (
                            <ComboboxGroup key={group.value}>
                                {group.label ? (
                                    <ComboboxLabel>{group.label}</ComboboxLabel>
                                ) : null}

                                {group.items.map((option) => (
                                    <ComboboxItem key={option.value || '__empty'} value={option}>
                                        <ComboboxHighlightedText
                                            text={option.displayLabel}
                                            search={searchValue}
                                        />
                                    </ComboboxItem>
                                ))}
                            </ComboboxGroup>
                        )}
                    </ComboboxCollection>
                </ComboboxList>
            </ComboboxContent>
        </Combobox>
    );
}

function ClientEventTemplateDialog({
    open,
    onOpenChange,
    template,
    pages,
    onConfirm,
}) {
    const t = useTranslation();
    const fieldSlots = useMemo(() => {
        return getTemplateFieldSlots(template);
    }, [template]);

    const [mappings, setMappings] = useState({});

    const fieldOptionsBySlot = useMemo(() => {
        const optionsBySlot = {};

        fieldSlots.forEach((slot) => {
            optionsBySlot[slot.key] = buildGroupedFieldOptions(pages, slot.fieldTypes, t);
        });

        return optionsBySlot;
    }, [fieldSlots, pages, t]);

    const requiredSlotsMissing = fieldSlots.some((slot) => {
        if (!slot.required) {
            return false;
        }

        return !String(mappings[slot.key] || '').trim();
    });

    const handleOpenChange = (nextOpen) => {
        if (!nextOpen) {
            setMappings({});
        }

        onOpenChange(nextOpen);
    };

    const handleConfirm = () => {
        onConfirm(mappings);
        setMappings({});
        onOpenChange(false);
    };

    if (!template) {
        return null;
    }

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="max-w-[520px]">
                <DialogHeader>
                    <DialogTitle>{template.label}</DialogTitle>
                    <DialogDescription>{template.description}</DialogDescription>
                </DialogHeader>

                <div className="p-4">
                    {fieldSlots.length > 0 ? (
                        <div className="space-y-4">
                            <p className="text-sm text-gray-600">
                                {t('Map form fields to the template properties below. Use the variable picker in the event editor to adjust values later.')}
                            </p>

                            {fieldSlots.map((slot) => (
                                <div key={slot.key} className="space-y-1.5">
                                    <label className="text-sm font-bold text-gray-800">
                                        {slot.mappingLabel || slot.key}
                                        {slot.required ? <span className="ml-1 text-rose-600">*</span> : null}
                                    </label>
                                    <FieldMappingCombobox
                                        options={fieldOptionsBySlot[slot.key] || []}
                                        value={mappings[slot.key] || ''}
                                        onChange={(nextValue) => {
                                            setMappings((current) => ({
                                                ...current,
                                                [slot.key]: nextValue,
                                            }));
                                        }}
                                    />
                                </div>
                            ))}
                        </div>
                    ) : (
                        <p className="text-sm text-gray-600">
                            {t('This template is ready to insert as-is.')}
                        </p>
                    )}
                </div>

                <DialogFooter className="flex flex-row justify-end gap-2">
                    <Button type="button" onClick={() => handleOpenChange(false)}>
                        {t('Cancel')}
                    </Button>
                    <Button
                        type="button"
                        variant="primary"
                        disabled={requiredSlotsMissing}
                        onClick={handleConfirm}
                    >
                        {t('Add event')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

export { ClientEventTemplateDialog };
