import { useMemo, useState } from 'react';

import {
    Combobox,
    ComboboxPrimitiveInput,
    ComboboxContent,
    ComboboxEmpty,
    ComboboxList,
    ComboboxCollection,
    ComboboxGroup,
    ComboboxLabel,
    ComboboxItem,
    ComboboxHighlightedText,
} from '@verbb/plugin-kit-react/components';
import { FieldControl, FieldLayout } from '@verbb/plugin-kit-react/forms/Field';
import { useEngineField } from '@verbb/plugin-kit-react/forms/useEngineField';
import { cn } from '@verbb/plugin-kit-react/utils';

import useAppStore from '@form-builder/hooks/useAppStore';
import { useFormValues, getFieldReferenceOptions } from '@form-builder/hooks/useFormTools';

const getFirstConfiguredArray = (...values) => {
    return values.find((value) => { return Array.isArray(value) && value.length; }) || [];
};

function FieldSelectField({ field, form }) {
    const {
        value, setValue, setTouched, errors, isInvalid,
    } = useEngineField(form, field.name);
    const [searchValue, setSearchValue] = useState('');
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
    const selectedOption = useMemo(() => {
        if (value == null || value === '') {
            return null;
        }

        return displayFieldOptions.find((option) => {
            return String(option.value ?? '') === String(value ?? '');
        }) || null;
    }, [displayFieldOptions, value]);
    const placeholder = field.placeholder || field.emptyOptionLabel || Craft.t('formie', 'Select an option');

    return (
        <FieldLayout
            name={field.name}
            label={field.label}
            instructions={field.instructions}
            required={field.required}
            errors={errors}
            withControl={false}
        >
            <Combobox
                items={groupedFieldOptions}
                value={selectedOption}
                onValueChange={(nextOption) => {
                    setValue(nextOption?.value ?? '');
                    setTouched();
                }}
                onInputValueChange={(nextValue) => {
                    setSearchValue(nextValue);
                }}
                onOpenChange={(open) => {
                    if (!open) {
                        setSearchValue('');
                    }
                }}
                itemToStringLabel={(item) => { return item?.displayLabel ?? item?.label ?? ''; }}
                itemToStringValue={(item) => { return String(item?.value ?? ''); }}
            >
                <FieldControl>
                    <ComboboxPrimitiveInput
                        placeholder={placeholder}
                        showClear={false}
                        className={cn(
                            'w-fit max-w-full',
                            isInvalid && 'border-error',
                        )}
                    />
                </FieldControl>

                <ComboboxContent>
                    <ComboboxEmpty>{Craft.t('formie', 'No fields found.')}</ComboboxEmpty>

                    <ComboboxList>
                        <ComboboxCollection>
                            {(group) => {
                                return (
                                    <ComboboxGroup key={group.value}>
                                        {group.label ? (
                                            <ComboboxLabel>{group.label}</ComboboxLabel>
                                        ) : null}

                                        {group.items.map((option) => {
                                            return (
                                                <ComboboxItem key={option.value} value={option}>
                                                    <ComboboxHighlightedText
                                                        text={option.displayLabel}
                                                        search={searchValue}
                                                    />
                                                </ComboboxItem>
                                            );
                                        })}
                                    </ComboboxGroup>
                                );
                            }}
                        </ComboboxCollection>
                    </ComboboxList>
                </ComboboxContent>
            </Combobox>
        </FieldLayout>
    );
}

export { FieldSelectField };
