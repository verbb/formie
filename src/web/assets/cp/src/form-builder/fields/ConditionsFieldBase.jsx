import { useEffect, useMemo, useState } from 'react';

import { useTranslation } from '@verbb/plugin-kit-react/hooks';
import { useEngineField, FieldLayout } from '@verbb/plugin-kit-react/forms';
import { EditableTable, SelectInput } from '@verbb/plugin-kit-react/components';
import useAppStore from '@form-builder/hooks/useAppStore';
import { useFormValues } from '@form-builder/hooks/useFormTools';
import { buildConditionFieldPicker } from '@form-builder/fields/utils/conditionFieldPicker';
import { resolveVariableCategories } from '@form-builder/hooks/useVariableCategories';
import { ConditionVariablePickerCell } from '@form-builder/fields/components/ConditionVariablePickerCell';

const CONDITION_FIELD_VARIABLE_CONFIG = {
    content: 'singleLine',
    types: ['text'],
    groupFieldsByPage: true,
    showFieldHandle: true,
    groups: [
        'fieldsVariables',
        'staticFormVariables',
        'staticGeneralVariables',
        'staticSiteVariables',
    ],
};

const getCurrentFieldPageIndex = (formValues = {}, currentFieldId = null) => {
    if (!currentFieldId) {
        return null;
    }

    const pages = Array.isArray(formValues?.pages) ? formValues.pages : [];

    const containsFieldId = (rows = []) => {
        return rows.some((row) => {
            const fields = Array.isArray(row?.fields) ? row.fields : [];

            return fields.some((candidateField) => {
                if (!candidateField || typeof candidateField !== 'object') {
                    return false;
                }

                if (candidateField._id === currentFieldId || candidateField.id === currentFieldId) {
                    return true;
                }

                const nestedRows = Array.isArray(candidateField?.rows)
                    ? candidateField.rows
                    : (Array.isArray(candidateField?.settings?.rows) ? candidateField.settings.rows : []);

                return nestedRows.length ? containsFieldId(nestedRows) : false;
            });
        });
    };

    const pageIndex = pages.findIndex((page) => {
        return containsFieldId(Array.isArray(page?.rows) ? page.rows : []);
    });

    return pageIndex >= 0 ? pageIndex : null;
};

const getActivePageIndex = (formValues = {}) => {
    const pages = Array.isArray(formValues?.pages) ? formValues.pages : [];
    if (!pages.length) {
        return null;
    }

    const activePageHandle = formValues?.activePage;
    if (!activePageHandle) {
        return null;
    }

    const pageIndex = pages.findIndex((page) => {
        return page?._handle === activePageHandle || page?.handle === activePageHandle;
    });

    return pageIndex >= 0 ? pageIndex : null;
};

const getPageIndexFromScopePath = (field = null) => {
    const scopePath = typeof field?._scopePath === 'string' ? field._scopePath : '';
    if (!scopePath) {
        return null;
    }

    const match = scopePath.match(/(?:^|\\.)pages\\.(\\d+)(?:\\.|$)/);
    if (!match) {
        return null;
    }

    const pageIndex = Number.parseInt(match[1], 10);
    return Number.isInteger(pageIndex) ? pageIndex : null;
};

const resolveMaxPageIndex = (scope, currentPageIndex) => {
    if (!Number.isInteger(currentPageIndex)) {
        return null;
    }

    if (scope === 'currentAndPrevious') {
        return currentPageIndex;
    }

    if (scope === 'previousOnly') {
        return currentPageIndex - 1;
    }

    return null;
};

function ConditionsFieldBase({
    field,
    form,
    ruleKey,
    defaultRuleValue,
    ruleOptions,
    subjectLabel,
    hideRuleSelector = false,
    fieldSelectionPageScope = null,
    excludeSelfInFieldOptions = false,
    referenceContext = null,
}) {
    const {
        value, setValue, setTouched, errors,
    } = useEngineField(form, field.name);
    const builderFormValues = useFormValues();
    const localPages = form?.getFieldValue?.('pages');
    const localActivePage = form?.getFieldValue?.('activePage');
    const formValues = useMemo(() => {
        if (!Array.isArray(localPages)) {
            return builderFormValues;
        }

        return {
            ...(builderFormValues || {}),
            pages: localPages,
            activePage: localActivePage ?? builderFormValues?.activePage ?? null,
        };
    }, [builderFormValues, localPages, localActivePage]);
    const currentFieldId = form?.getFieldValue?.('_id') || form?.getFieldValue?.('id') || null;
    const currentPageIndex = useMemo(() => {
        const fromScopePath = getPageIndexFromScopePath(field);
        if (Number.isInteger(fromScopePath)) {
            return fromScopePath;
        }

        const fromFieldLocation = getCurrentFieldPageIndex(formValues, currentFieldId);
        if (Number.isInteger(fromFieldLocation)) {
            return fromFieldLocation;
        }

        return getActivePageIndex(formValues);
    }, [field, formValues, currentFieldId]);
    const getFieldTypeByType = useAppStore((state) => { return state.getFieldTypeByType; });
    const variableCategoriesConfig = useAppStore((state) => { return state.variableCategoriesConfig; });
    const variableCategoryLabels = useAppStore((state) => { return state.variableCategoryLabels || {}; });
    const variableCategoryOrder = useAppStore((state) => { return state.variableCategoryOrder || []; });
    const variableTransformerRegistry = useAppStore((state) => { return state.variableCategoriesConfig?.transformerRegistry || {}; });
    const resolvedFieldSelectionPageScope = fieldSelectionPageScope || field?.fieldSelectionPageScope || 'all';
    const resolvedExcludeSelfInFieldOptions = Boolean(excludeSelfInFieldOptions || field?.excludeSelfInFieldOptions);
    const maxPageIndex = useMemo(() => {
        return resolveMaxPageIndex(resolvedFieldSelectionPageScope, currentPageIndex);
    }, [resolvedFieldSelectionPageScope, currentPageIndex]);
    const conditionVariableConfig = useMemo(() => {
        return {
            ...CONDITION_FIELD_VARIABLE_CONFIG,
            fieldSelectionPageScope: resolvedFieldSelectionPageScope,
            currentPageIndex,
            maxPageIndex,
            excludeSelf: resolvedExcludeSelfInFieldOptions,
            excludeSelfFieldId: resolvedExcludeSelfInFieldOptions ? currentFieldId : null,
            referenceContext,
        };
    }, [resolvedFieldSelectionPageScope, currentPageIndex, maxPageIndex, resolvedExcludeSelfInFieldOptions, currentFieldId, referenceContext]);
    const variableCategories = useMemo(() => {
        return resolveVariableCategories(variableCategoriesConfig || {}, formValues, conditionVariableConfig, {
            getFieldTypeByType,
            form,
        });
    }, [variableCategoriesConfig, formValues, conditionVariableConfig, getFieldTypeByType, form]);

    const t = useTranslation();

    const emptySettings = useMemo(() => {
        return {
            [ruleKey]: defaultRuleValue,
            conditionRule: 'all',
            conditions: [],
        };
    }, [defaultRuleValue, ruleKey]);

    const [settings, setSettings] = useState(() => {
        if (value && typeof value === 'object') {
            return { ...emptySettings, ...value };
        }
        return emptySettings;
    });

    useEffect(() => {
        if (value && typeof value === 'object') {
            setSettings({ ...emptySettings, ...value });
            return;
        }
        if (!value) {
            setSettings(emptySettings);
        }
    }, [emptySettings, value]);

    const { conditionOptions, fieldOptions } = field;

    const { modifyValueColumn } = useMemo(() => {
        return buildConditionFieldPicker({
            baseFieldOptions: fieldOptions,
            formValues,
            getFieldTypeByType,
            t,
            fieldReferenceOptions: {
                maxPageIndex,
                excludeSelf: resolvedExcludeSelfInFieldOptions,
                excludeSelfFieldId: resolvedExcludeSelfInFieldOptions ? currentFieldId : null,
                referenceContext,
            },
        });
    }, [fieldOptions, formValues, getFieldTypeByType, t, maxPageIndex, resolvedExcludeSelfInFieldOptions, currentFieldId, referenceContext]);

    return (
        <FieldLayout
            name={field.name}
            label={field.label}
            instructions={field.instructions}
            required={field.required}
            errors={errors}
        >
            <div className="space-y-4">
                <div className="flex items-center gap-2 text-sm">
                    {!hideRuleSelector && (
                        <>
                            <span>{t('I want to')}</span>

                            <SelectInput
                                size="sm"
                                value={settings[ruleKey]}
                                options={ruleOptions}
                                onChange={(nextValue) => {
                                    const nextSettings = { ...settings, [ruleKey]: nextValue };
                                    setSettings(nextSettings);
                                    setValue(nextSettings);
                                    setTouched();
                                }}
                            />
                        </>
                    )}

                    <span>{hideRuleSelector ? t('Apply when') : t(subjectLabel)}</span>

                    <SelectInput
                        size="sm"
                        value={settings.conditionRule}
                        options={[
                            { label: t('All'), value: 'all' },
                            { label: t('Any'), value: 'any' },
                        ]}
                        onChange={(nextValue) => {
                            const nextSettings = { ...settings, conditionRule: nextValue };
                            setSettings(nextSettings);
                            setValue(nextSettings);
                            setTouched();
                        }}
                    />

                    <span>{t('of the following rules match.')}</span>
                </div>

                <EditableTable
                    columns={[
                        {
                            name: 'field',
                            label: t('Field'),
                            type: 'variablePicker',
                            variableCategories,
                            variableCategoryLabels,
                            variableCategoryOrder,
                            variableTransformerRegistry,
                            noneOptionLabel: t('Select an option'),
                            renderCell: ({
                                column, value, isInvalid, updateValue,
                            }) => {
                                return (
                                    <ConditionVariablePickerCell
                                        column={column}
                                        value={value}
                                        isInvalid={isInvalid}
                                        updateValue={updateValue}
                                    />
                                );
                            },
                            width: '42%',
                            // Popup panel only — do not set contentClassName (cell projection).
                            pickerContentClassName: 'min-w-[320px] max-w-[480px] p-0 overflow-hidden flex flex-col',
                        },
                        {
                            name: 'condition',
                            label: t('Condition'),
                            type: 'select',
                            options: conditionOptions,
                            width: '28%',
                        },
                        {
                            name: 'value',
                            label: t('Value'),
                            type: 'text',
                        },
                    ]}
                    rows={settings.conditions}
                    onChange={(data) => {
                        const nextSettings = { ...settings, conditions: data };
                        setSettings(nextSettings);
                        setValue(nextSettings);
                        setTouched();
                    }}
                    modifyColumn={modifyValueColumn}
                    addRowLabel={t('Add a condition')}
                    allowReorder={false}
                    allowAdd={true}
                    allowDelete={true}
                />
            </div>
        </FieldLayout>
    );
}

export { ConditionsFieldBase };
