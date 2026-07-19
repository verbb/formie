import { Fragment, useMemo, useState } from 'react';

import { useTranslation } from '@verbb/plugin-kit-react/hooks';
import { Button, Combobox, Dialog, Option, OptionGroup } from '@verbb/plugin-kit-react/components';

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

    return (
        <Combobox
            value={value ? String(value) : ''}
            placeholder={placeholder || t('Select a field')}
            emptyMessage={t('No fields found.')}
            onPkChange={(event) => {
                onChange(event.detail?.value ?? '');
            }}
        >
            {options.map((group) => {
                const optionNodes = group.items.map((option) => {
                    const optionValue = String(option.value ?? '');

                    return (
                        <Option key={optionValue || '__empty'} value={optionValue}>
                            {option.displayLabel ?? option.label}
                        </Option>
                    );
                });

                // The leading empty option group has no label — render it flat.
                if (!group.label) {
                    return <Fragment key={group.value}>{optionNodes}</Fragment>;
                }

                return (
                    <OptionGroup key={group.value} label={group.label}>
                        {optionNodes}
                    </OptionGroup>
                );
            })}
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
        <Dialog
            open={open}
            label={template.label}
            description={template.description || ''}
            className="formie-client-event-template-dialog"
            onPkOpenChange={(event) => { handleOpenChange(event.detail?.open ?? event.target?.open ?? false); }}
        >
            {fieldSlots.length > 0 ? (
                <div className="space-y-4">
                    <p className="text-sm text-gray-600">
                        {t('Map form fields to the template properties below. Use the variable picker in the event editor to adjust values later.')}
                    </p>

                    {fieldSlots.map((slot) => (
                        <div key={slot.key} className="space-y-1.5">
                            <label className="block text-sm font-bold text-gray-800">
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

            <Button slot="footer" type="button" onClick={() => handleOpenChange(false)}>
                {t('Cancel')}
            </Button>
            <Button
                slot="footer"
                type="button"
                variant="primary"
                disabled={requiredSlotsMissing}
                onClick={handleConfirm}
            >
                {t('Add event')}
            </Button>
        </Dialog>
    );
}

export { ClientEventTemplateDialog };
