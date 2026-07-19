import { Fragment, useMemo } from 'react';

import { Combobox, Option, OptionGroup } from '@verbb/plugin-kit-react/components';
import { FieldLayout, useEngineField } from '@verbb/plugin-kit-react/forms';
import useAppStore from '@form-builder/hooks/useAppStore';
import { useFormValues, getFieldReferenceOptions } from '@form-builder/hooks/useFormTools';

const getFirstConfiguredArray = (...values) => {
    return values.find((value) => { return Array.isArray(value) && value.length; }) || [];
};

function FieldSelectField({ field, form }) {
    const {
        value, setValue, setTouched, errors, isInvalid,
    } = useEngineField(form, field.name);
    const formValues = useFormValues();
    const getFieldTypeByType = useAppStore((state) => { return state.getFieldTypeByType; });
    const editingFieldId = form?.getFieldValue?.('_id') || form?.getFieldValue?.('id') || null;
    const includedTypes = getFirstConfiguredArray(field.includedTypes, field.fieldTypes);
    const variableTypes = getFirstConfiguredArray(field.variableConfig?.types, field.types);

    const fieldOptions = useMemo(() => {
        const options = getFieldReferenceOptions(formValues, {
            getFieldTypeByType,
            target: 'fieldSelect',
            includedTypes,
            excludedTypes: field.excludedTypes || [],
            excludedFields: Array.isArray(field.excludedFields) ? field.excludedFields : [],
            excludeSelf: field.excludeSelf || false,
            excludeSelfFieldId: field.excludeSelf ? editingFieldId : null,
            includeSelectors: field.includeSelectors,
            topLevelOnly: field.topLevelOnly,
            fieldSelectGroupByPage: true,
            variableTypes,
            referenceContext: field.referenceContext || null,
        });

        if (field.emptyOptionLabel) {
            options.unshift({
                label: field.emptyOptionLabel,
                value: '',
            });
        }

        return options;
    }, [
        formValues,
        field.excludeSelf,
        field.excludedFields,
        field.excludedTypes,
        field.includeSelectors,
        field.referenceContext,
        field.topLevelOnly,
        includedTypes,
        variableTypes,
        field.emptyOptionLabel,
        getFieldTypeByType,
        editingFieldId,
    ]);
    const displayFieldOptions = useMemo(() => {
        const primaryLabelCounts = fieldOptions.reduce((acc, option) => {
            if (!option?.isPrimaryFieldReference || !option.fieldLabel) {
                return acc;
            }

            acc.set(option.fieldLabel, (acc.get(option.fieldLabel) || 0) + 1);
            return acc;
        }, new Map());

        return fieldOptions.map((option) => {
            const shouldShowHandle = Boolean(
                option?.isPrimaryFieldReference
                && option.fieldHandle
                && primaryLabelCounts.get(option.fieldLabel) > 1,
            );

            return {
                ...option,
                displayLabel: shouldShowHandle ? `${option.label} (${option.fieldHandle})` : option.label,
            };
        });
    }, [fieldOptions]);
    const groupedFieldOptions = useMemo(() => {
        const groups = [];
        const pageGroups = new Map();

        displayFieldOptions.forEach((option) => {
            if (option.value === '') {
                groups.push({
                    value: '__field-select-default',
                    label: '',
                    items: [option],
                });
                return;
            }

            const pageLabel = option.pageLabel || Craft.t('formie', 'Fields');
            if (!pageGroups.has(pageLabel)) {
                pageGroups.set(pageLabel, {
                    value: pageLabel,
                    label: pageLabel,
                    items: [],
                });
            }

            pageGroups.get(pageLabel).items.push(option);
        });

        groups.push(...pageGroups.values());

        return groups;
    }, [displayFieldOptions]);
    const placeholder = field.placeholder || field.emptyOptionLabel || Craft.t('formie', 'Select an option');

    return (
        <FieldLayout
            name={field.name}
            label={field.label}
            instructions={field.instructions}
            required={field.required}
            errors={errors}
        >
            {/* pk-combobox is string-valued and owns filtering/highlighting internally. */}
            <Combobox
                className="max-w-full"
                value={value == null ? '' : String(value)}
                placeholder={placeholder}
                invalid={isInvalid}
                emptyMessage={Craft.t('formie', 'No fields found.')}
                onPkChange={(event) => {
                    setValue(event.detail?.value ?? '');
                    setTouched();
                }}
            >
                {groupedFieldOptions.map((group) => {
                    const optionNodes = group.items.map((option) => {
                        const optionValue = String(option.value ?? '');

                        return (
                            <Option key={optionValue || '__empty'} value={optionValue}>
                                {option.displayLabel ?? option.label}
                            </Option>
                        );
                    });

                    // Groups without a label (e.g. the leading empty option) render flat.
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
        </FieldLayout>
    );
}

export { FieldSelectField };
