import { useMemo } from 'react';
import { cloneDeep } from 'lodash-es';

import { useFormBuilderForm } from '@form-builder/contexts/FormBuilderFormContext';
import { createFieldReference } from '@form-builder/utils/fieldReferences';
import {
    createItem,
    normalizeCollection,
    evaluateCondition,
} from '@verbb/plugin-kit-react/utils';
import { takeAtLeast } from '@verbb/plugin-kit-react/utils';

import { getRequestErrorMessage, normalizeErrorText } from '@utils/requestError';

const getRefreshResponseErrorMessage = (data) => {
    if (!data || typeof data !== 'object') {
        return '';
    }

    const appendFromErrors = (errorsValue) => {
        if (Array.isArray(errorsValue)) {
            const first = errorsValue.find((value) => { return normalizeErrorText(value) !== ''; });
            return first ? normalizeErrorText(first) : '';
        }

        if (errorsValue && typeof errorsValue === 'object') {
            for (const value of Object.values(errorsValue)) {
                if (Array.isArray(value)) {
                    const first = value.find((item) => { return normalizeErrorText(item) !== ''; });
                    if (first) {
                        return normalizeErrorText(first);
                    }
                    continue;
                }

                if (normalizeErrorText(value) !== '') {
                    return normalizeErrorText(value);
                }
            }
        }

        if (normalizeErrorText(errorsValue) !== '') {
            return normalizeErrorText(errorsValue);
        }

        return '';
    };

    const explicitError = data.error ?? data.message ?? '';
    const normalizedExplicitError = normalizeErrorText(explicitError);
    if (normalizedExplicitError) {
        return normalizedExplicitError;
    }

    const errorsMessage = appendFromErrors(data.errors);
    if (errorsMessage) {
        return errorsMessage;
    }

    if (data.success === false || data.ok === false || data.status === false) {
        return 'Failed to refresh integration data.';
    }

    return '';
};

const normalizeRows = (rows = []) => {
    const sourceRows = Array.isArray(rows) ? rows : [];

    return sourceRows.map((row) => {
        const normalizedRow = createItem(row);
        const sourceFields = Array.isArray(row?.fields) ? row.fields : [];

        const normalizedFields = sourceFields.map((field) => {
            if (!field || typeof field !== 'object') {
                return null;
            }

            const normalizedField = createItem(field);
            const hasDirectRows = Array.isArray(field?.rows);
            const hasSettingsRows = Array.isArray(field?.settings?.rows);

            if (hasDirectRows || hasSettingsRows) {
                const nestedSourceRows = hasDirectRows ? field.rows : field.settings.rows;
                const normalizedNestedRows = normalizeRows(nestedSourceRows);

                if (normalizedNestedRows.length) {
                    normalizedField.rows = normalizedNestedRows;

                    if (normalizedField.settings && typeof normalizedField.settings === 'object') {
                        normalizedField.settings = {
                            ...normalizedField.settings,
                            rows: normalizedNestedRows,
                        };
                    }
                } else {
                    delete normalizedField.rows;

                    if (normalizedField.settings && typeof normalizedField.settings === 'object' && Array.isArray(normalizedField.settings.rows)) {
                        normalizedField.settings = { ...normalizedField.settings };
                        delete normalizedField.settings.rows;
                    }
                }
            }

            return normalizedField;
        }).filter(Boolean);

        if (!normalizedFields.length) {
            return null;
        }

        normalizedRow.fields = normalizedFields;

        return normalizedRow;
    }).filter(Boolean);
};

const assignMissingFieldReferences = (rows = []) => {
    if (!Array.isArray(rows)) {
        return;
    }

    rows.forEach((row) => {
        if (!row || typeof row !== 'object' || !Array.isArray(row.fields)) {
            return;
        }

        row.fields.forEach((field) => {
            if (!field || typeof field !== 'object') {
                return;
            }

            if (!String(field.reference || '').trim()) {
                field.reference = createFieldReference();
            }

            if (Array.isArray(field.rows)) {
                assignMissingFieldReferences(field.rows);
            }

            if (Array.isArray(field?.settings?.rows)) {
                assignMissingFieldReferences(field.settings.rows);
            }
        });
    });
};

const collectFieldReferenceMap = (rows = []) => {
    const referenceByClientId = new Map();
    const referencesByHandle = new Map();

    const walk = (sourceRows = []) => {
        if (!Array.isArray(sourceRows)) {
            return;
        }

        sourceRows.forEach((row) => {
            if (!row || typeof row !== 'object' || !Array.isArray(row.fields)) {
                return;
            }

            row.fields.forEach((field) => {
                if (!field || typeof field !== 'object') {
                    return;
                }

                const reference = String(field.reference || '').trim();
                const clientId = String(field._id || '').trim();
                const handle = String(field.handle || '').trim();

                if (reference && clientId) {
                    referenceByClientId.set(clientId, reference);
                }

                if (reference && handle) {
                    if (!referencesByHandle.has(handle)) {
                        referencesByHandle.set(handle, new Set());
                    }

                    referencesByHandle.get(handle).add(reference);
                }

                walk(field.rows);
                walk(field?.settings?.rows);
            });
        });
    };

    walk(rows);

    const referenceMap = {};
    referenceByClientId.forEach((reference, clientId) => {
        referenceMap[clientId] = reference;
    });

    referencesByHandle.forEach((references, handle) => {
        if (references.size === 1) {
            const [reference] = Array.from(references);
            referenceMap[handle] = reference;
        }
    });

    return referenceMap;
};

const remapFieldReferenceTokens = (value, referenceMap = {}) => {
    if (!value || typeof value !== 'object') {
        if (typeof value !== 'string') {
            return value;
        }

        return value.replace(/\{field:[^}]+\}/g, (token) => {
            const match = token.match(/^\{field:([^}:;|]+)(.*)\}$/);
            const identifier = match?.[1] || '';
            const suffix = match?.[2] || '';
            const mappedReference = identifier ? referenceMap[identifier] : null;

            return mappedReference ? `{field:${mappedReference}${suffix}}` : token;
        });
    }

    if (Array.isArray(value)) {
        value.forEach((item, index) => {
            value[index] = remapFieldReferenceTokens(item, referenceMap);
        });
        return value;
    }

    Object.entries(value).forEach(([key, nestedValue]) => {
        value[key] = remapFieldReferenceTokens(nestedValue, referenceMap);
    });

    return value;
};

const normalizeFormData = (data = {}) => {
    const pages = (data.pages || []).map((page) => {
        const normalizedPage = createItem(page);
        normalizedPage.rows = normalizeRows(page?.rows || []);

        return normalizedPage;
    });

    const sourceSettings = (data?.settings && typeof data.settings === 'object') ? data.settings : {};
    const sourceIntegrations = sourceSettings?.integrations;
    const normalizedIntegrations = (sourceIntegrations && !Array.isArray(sourceIntegrations) && typeof sourceIntegrations === 'object')
        ? sourceIntegrations
        : {};

    return {
        ...data,
        pages,
        notifications: normalizeCollection(data?.notifications || []),
        settings: {
            ...sourceSettings,
            integrations: normalizedIntegrations,
        },
    };
};

const serializeFormData = (data = {}) => {
    const serialized = cloneDeep(data);
    const notifications = Array.isArray(serialized?.notifications) ? serialized.notifications : [];

    (serialized.pages || []).forEach((page) => {
        assignMissingFieldReferences(page?.rows || []);
    });

    const fieldReferenceMap = {};
    (serialized.pages || []).forEach((page) => {
        Object.assign(fieldReferenceMap, collectFieldReferenceMap(page?.rows || []));
    });

    if (serialized?.settings?.integrations && typeof serialized.settings.integrations === 'object') {
        remapFieldReferenceTokens(serialized.settings.integrations, fieldReferenceMap);
    }

    const stripPrivateValues = (value) => {
        if (!value || typeof value !== 'object') {
            return;
        }

        if (Array.isArray(value)) {
            value.forEach(stripPrivateValues);
            return;
        }

        Object.keys(value).forEach((key) => {
            if (key === 'errors' || key.startsWith('_')) {
                delete value[key];

            }
        });

        Object.values(value).forEach(stripPrivateValues);
    };

    stripPrivateValues(serialized);

    // Omit root-level integrations (schema/catalog). Only settings.integrations
    // is needed for save; the root one is UI state and bloats the payload.
    if (Object.prototype.hasOwnProperty.call(serialized, 'integrations')) {
        delete serialized.integrations;
    }

    notifications.forEach((notification) => {
        if (!notification || typeof notification !== 'object') {
            return;
        }

        const content = notification.content;
        if (Array.isArray(content) || (content && typeof content === 'object')) {
            notification.content = JSON.stringify(content);
        }
    });

    return serialized;
};

const prepareFormPreview = async(formValues, options = {}) => {
    if (!formValues) {
        return { ok: false, error: Craft.t('formie', 'Missing form preview data.') };
    }

    const data = serializeFormData(formValues);
    const {
        entityType = 'form',
        saveRequestData = {},
    } = options;

    try {
        const response = await Craft.sendActionRequest('POST', 'formie/forms/prepare-preview', {
            data: {
                ...saveRequestData,
                ...data,
                entityType,
                isStencil: entityType === 'stencil',
            },
        });

        if (response.data?.error) {
            return { ok: false, error: response.data.error };
        }

        if (!response.data?.token) {
            return { ok: false, error: Craft.t('formie', 'Could not prepare form preview.') };
        }

        return { ok: true, data: response.data };
    } catch (error) {
        console.error('Error preparing form preview:', error);
        return { ok: false, error: getRequestErrorMessage(error, Craft.t('formie', 'Could not prepare form preview.')) };
    }
};

const saveForm = async(formValues, options = {}) => {
    if (!formValues) {
        return { ok: false, errors: { form: ['Missing form values.'] } };
    }

    const data = serializeFormData(formValues);
    const {
        saveAsNew = false,
        action = 'formie/forms/save',
        requestData = {},
    } = options;

    if (saveAsNew) {
        data.saveAsNew = true;
    }

    try {
        const response = await takeAtLeast(500)(
            Craft.sendActionRequest('POST', action, {
                data: {
                    ...requestData,
                    ...data,
                },
            }),
        );

        if (response.data.errors) {
            return { ok: false, errors: response.data.errors };
        }

        return { ok: true, data: response.data };
    } catch (error) {
        console.error('Error saving form:', error);
        return { ok: false, errors: { form: [getRequestErrorMessage(error, 'Failed to save form.')] } };
    }
};

const saveAsStencil = async(formValues) => {
    if (!formValues) {
        return { ok: false, errors: { form: ['Missing form values.'] } };
    }

    const data = serializeFormData(formValues);

    try {
        const response = await takeAtLeast(500)(
            Craft.sendActionRequest('POST', 'formie/forms/save-as-stencil', { data }),
        );
        const payload = response?.data || {};

        if (payload.success === false) {
            return { ok: false, errors: { form: ['Failed to save stencil.'] }, data: payload };
        }

        return { ok: true, data: payload };
    } catch (error) {
        console.error('Error saving stencil:', error);
        return { ok: false, errors: { form: [getRequestErrorMessage(error, 'Failed to save stencil.')] } };
    }
};

const deleteForm = async(formId, options = {}) => {
    const {
        redirect = 'formie/forms',
        action = 'formie/forms/delete-form',
        requestData = null,
    } = options;

    const payload = requestData || { formId, redirect };

    if (!payload || Object.keys(payload).length === 0) {
        return { ok: false, errors: { form: ['Missing form id.'] } };
    }

    try {
        const response = await takeAtLeast(300)(
            Craft.sendActionRequest('POST', action, { data: payload }),
        );

        if (response.data?.success === false) {
            return { ok: false, errors: { form: ['Failed to delete form.'] } };
        }

        return { ok: true, data: response.data };
    } catch (error) {
        console.error('Error deleting form:', error);
        return { ok: false, errors: { form: ['Failed to delete form.'] } };
    }
};

const useFormValues = () => {
    const { values } = useFormBuilderForm();
    return values;
};

/**
 * Get form fields from form values, filtered by type and excluded fields.
 *
 * @param {object} values - Form builder values (with pages)
 * @param {object} options - Filter options
 * @param {string[]} [options.includedTypes] - Only include these field types
 * @param {string[]} [options.excludedTypes] - Exclude these field types
 * @param {string[]} [options.excludedFields] - Exclude fields by _id
 * @param {boolean} [options.excludeSelf] - When true, excludes the field whose _id is in options.excludeSelfFieldId
 * @param {string} [options.excludeSelfFieldId] - Field _id to exclude when excludeSelf is true (e.g. field._id)
 * @param {string} [options.excludeByHandle] - Field handle to exclude (e.g. when editing that field's formula)
 * @param {number|null} [options.maxPageIndex] - Limit selection to pages up to this index (inclusive)
 * @returns {object[]} Array of form field objects
 */
const getFormFields = (values = {}, options = {}) => {
    const pages = values?.pages || [];
    const {
        includedTypes = [],
        excludedTypes = [],
        excludedFields = [],
        excludeSelf = false,
        excludeSelfFieldId = null,
        excludeByHandle = null,
        maxPageIndex = null,
    } = options;

    const excludedFieldsWithSelf = [...excludedFields];
    if (excludeSelf && excludeSelfFieldId) {
        excludedFieldsWithSelf.push(excludeSelfFieldId);
    }

    const includedTypeSet = includedTypes.length ? new Set(includedTypes) : null;
    const excludedTypeSet = excludedTypes.length ? new Set(excludedTypes) : null;
    const excludedFieldSet = new Set(excludedFieldsWithSelf);

    const allFields = [];

    pages.forEach((page, pageIndex) => {
        if (Number.isInteger(maxPageIndex) && pageIndex > maxPageIndex) {
            return;
        }

        const rows = page?.rows || [];

        rows.forEach((row, rowIndex) => {
            const fields = row?.fields || [];

            fields.forEach((field, fieldIndex) => {
                if (!field) {
                    return;
                }

                if (includedTypeSet && !includedTypeSet.has(field.type)) {
                    return;
                }

                if (excludedTypeSet && excludedTypeSet.has(field.type)) {
                    return;
                }

                if (excludedFieldSet.size && excludedFieldSet.has(field._id)) {
                    return;
                }

                if (excludeByHandle && field.handle === excludeByHandle) {
                    return;
                }

                allFields.push(field);
            });
        });
    });

    return allFields;
};

const fieldPassesFilters = (field, options = {}) => {
    if (!field) {
        return false;
    }

    const {
        includedTypeSet = null,
        excludedTypeSet = null,
        excludedFieldSet = null,
        excludeByHandle = null,
    } = options;

    if (includedTypeSet && !includedTypeSet.has(field.type)) {
        return false;
    }

    if (excludedTypeSet && excludedTypeSet.has(field.type)) {
        return false;
    }

    if (excludedFieldSet && excludedFieldSet.size && excludedFieldSet.has(field._id)) {
        return false;
    }

    if (excludeByHandle && field.handle === excludeByHandle) {
        return false;
    }

    return true;
};

const getFieldTypeReferenceConfig = (fieldTypeConfig = {}) => {
    const selectors = fieldTypeConfig?.referenceSelectors
        || fieldTypeConfig?.referenceConfig?.selectors
        || fieldTypeConfig?.fieldSelection?.options
        || fieldTypeConfig?.fieldSelectOptions
        || [];

    return {
        allowPrimary: fieldTypeConfig?.referenceConfig?.allowPrimary ?? fieldTypeConfig?.fieldSelection?.allowPrimary ?? true,
        allowNested: fieldTypeConfig?.referenceConfig?.allowNested ?? fieldTypeConfig?.fieldSelection?.allowNested ?? false,
        nestedMode: fieldTypeConfig?.referenceConfig?.nestedMode ?? fieldTypeConfig?.fieldSelection?.nestedMode ?? 'none',
        primaryTokenSuffix: fieldTypeConfig?.referenceConfig?.primaryTokenSuffix ?? fieldTypeConfig?.fieldSelection?.primaryTokenSuffix ?? null,
        selectors: Array.isArray(selectors) ? selectors : [],
    };
};

const getFieldTypeVariableSourceConfig = (fieldTypeConfig = {}) => {
    const sources = fieldTypeConfig?.variableSourceConfig || [];
    return Array.isArray(sources) ? sources : [];
};

const getVariableSourceBySelector = (fieldTypeConfig = {}, selector = '') => {
    const normalizedSelector = typeof selector === 'string' ? selector : '';
    return getFieldTypeVariableSourceConfig(fieldTypeConfig).find((source) => {
        return source && typeof source === 'object' && (source.selector || '') === normalizedSelector;
    }) || null;
};

const shouldIncludeVariableSource = (source, field, config = {}) => {
    if (!source || typeof source !== 'object') {
        return false;
    }

    if (config.target === 'variablePicker' && source.supportsVariablePicker === false) {
        return false;
    }

    if (config.referenceContext === 'client' && (source.supportsClient ?? source.supportsRuntime) === false) {
        return false;
    }

    if (source.condition && !evaluateCondition(source.condition, field || {})) {
        return false;
    }

    const types = Array.isArray(source?.types) ? source.types : [];
    if (!types.length) {
        return false;
    }

    const requestedTypes = Array.isArray(config.variableTypes) ? config.variableTypes : [];
    if (!requestedTypes.length) {
        return true;
    }

    return types.some((type) => { return requestedTypes.includes(type); });
};

const applyVariableSourceMetadata = (option, source) => {
    const content = typeof source?.content === 'string' && source.content
        ? source.content
        : 'singleLine';
    const types = Array.isArray(source?.types) ? source.types : [];

    return {
        ...option,
        content,
        types,
        ...(source.allowTransforms === false ? { allowTransforms: false } : {}),
    };
};

const getNestedFields = (field) => {
    const rows = Array.isArray(field?.rows)
        ? field.rows
        : (Array.isArray(field?.settings?.rows) ? field.settings.rows : []);

    if (!rows.length) {
        return [];
    }

    return rows.flatMap((row) => {
        return Array.isArray(row?.fields) ? row.fields : [];
    });
};

const getNestedChildFieldByHandle = (field, handle) => {
    if (!handle) {
        return null;
    }

    return getNestedFields(field).find((childField) => {
        return childField?.handle === handle;
    }) || null;
};

const getFieldEnabled = (field) => {
    if (typeof field?.enabled === 'boolean') {
        return field.enabled;
    }

    if (typeof field?.settings?.enabled === 'boolean') {
        return field.settings.enabled;
    }

    return true;
};

const getFieldTokenReference = (field) => {
    const reference = typeof field?.reference === 'string' ? field.reference.trim() : '';
    if (reference) {
        return reference;
    }

    const handle = typeof field?.handle === 'string' ? field.handle.trim() : '';
    return handle || null;
};

const isRepeatableParentFieldType = (field, config) => {
    const fieldTypeConfig = config.getFieldTypeByType?.(field?.type) || {};
    return Boolean(fieldTypeConfig.isRepeatableParentField);
};

const buildRepeaterReferenceToken = (parentReference, selectorHandle, scope, extraParams = {}) => {
    const selectorPart = selectorHandle ? `:${selectorHandle}` : '';
    const segments = [`field:${parentReference}${selectorPart}`, `scope=${scope}`];

    Object.entries(extraParams || {}).forEach(([key, value]) => {
        if (value == null || String(value).trim() === '') {
            return;
        }

        segments.push(`${key}=${encodeURIComponent(String(value))}`);
    });

    return `{${segments.join(';')}}`;
};

const pushRepeaterScopedReferenceOptions = (targetOptions, {
    parentReference,
    nestedLabel,
    selectorHandle,
    selectorLabel,
    source,
}) => {
    const suffix = selectorLabel ? `: ${selectorLabel}` : '';
    const baseLabel = `${nestedLabel}${suffix}`;

    targetOptions.push(applyVariableSourceMetadata({
        label: baseLabel,
        value: buildRepeaterReferenceToken(parentReference, selectorHandle, 'first'),
        repeaterSubField: true,
        repeaterBaseLabel: baseLabel,
        types: ['text', 'email', 'array'],
    }, source));
};

const getConditionColumnOptions = (field, selectorHandle = '') => {
    const rawOptions = Array.isArray(field?.options) ? field.options : [];

    if (!rawOptions.length) {
        return null;
    }

    const options = rawOptions.flatMap((option) => {
        if (!option || typeof option !== 'object') {
            return [];
        }

        const optionLabel = option.label == null ? '' : String(option.label);
        const optionValue = option.value == null ? '' : String(option.value);
        const label = selectorHandle === 'value'
            ? (optionValue || optionLabel)
            : (optionLabel || optionValue);
        const value = selectorHandle === 'label'
            ? optionLabel
            : optionValue;

        // Skip optgroup headings or malformed option rows.
        if (!label && !value) {
            return [];
        }

        return [{
            label,
            value,
        }];
    });

    if (!options.length) {
        return null;
    }

    return {
        type: 'select',
        options,
    };
};

const shouldIncludeSelector = (selector, target, sourceField, referenceConfig, config = {}) => {
    if (!selector || typeof selector !== 'object') {
        return false;
    }

    if (!selector.handle) {
        return false;
    }

    if (target === 'fieldSelect' && selector.supportsFieldSelect === false) {
        return false;
    }

    if (target === 'variablePicker' && selector.supportsVariablePicker === false) {
        return false;
    }

    if (config.referenceContext === 'client' && (selector.supportsClient ?? selector.supportsRuntime) === false) {
        return false;
    }

    if (selector.condition && !evaluateCondition(selector.condition, sourceField || {})) {
        return false;
    }

    // If a selector maps directly to a child field handle (eg Name:prefix),
    // only include it when that child sub-field is enabled.
    const nestedChildField = getNestedChildFieldByHandle(sourceField, selector.handle);
    if (nestedChildField && !getFieldEnabled(nestedChildField)) {
        return false;
    }

    if (referenceConfig.allowPrimary !== false && selector.handle === referenceConfig.primaryTokenSuffix) {
        return false;
    }

    return true;
};

const buildVariablePickerSecondaryOptions = (field, referenceConfig, config) => {
    const options = [];
    const fieldReference = getFieldTokenReference(field);
    const fieldLabel = field?.label || field?.handle || '';
    const fieldTypeConfig = config.getFieldTypeByType?.(field.type) || {};
    const primarySource = getVariableSourceBySelector(fieldTypeConfig, '');
    const primarySelector = referenceConfig.selectors.find((selector) => {
        return referenceConfig.allowPrimary !== false && selector.handle === referenceConfig.primaryTokenSuffix;
    });
    const primarySelectorSource = primarySelector
        ? getVariableSourceBySelector(fieldTypeConfig, primarySelector.handle)
        : null;
    const aggregateSource = primarySelectorSource || primarySource;

    if (!fieldReference) {
        return options;
    }

    if (referenceConfig.allowPrimary !== false && shouldIncludeVariableSource(aggregateSource, field, config)) {
        const primaryLabel = primarySelector?.label || Craft.t('formie', 'Value');
        options.push(applyVariableSourceMetadata({
            label: fieldLabel ? `${fieldLabel}: ${primaryLabel}` : primaryLabel,
            value: `{field:${fieldReference}}`,
        }, aggregateSource));
    }

    referenceConfig.selectors
        .filter((selector) => {
            if (primarySelector && selector.handle === primarySelector.handle) {
                return false;
            }

            return shouldIncludeSelector(selector, config.target, field, referenceConfig, config);
        })
        .forEach((selector) => {
            const source = getVariableSourceBySelector(fieldTypeConfig, selector.handle);
            if (!shouldIncludeVariableSource(source, field, config)) {
                return;
            }

            const selectorLabel = selector.label || selector.handle;
            options.push(applyVariableSourceMetadata({
                label: fieldLabel ? `${fieldLabel}: ${selectorLabel}` : selectorLabel,
                value: `{field:${fieldReference}:${selector.handle}}`,
            }, source));
        });

    const appendNestedOptions = (sourceField, labelPrefix = '') => {
        const nestedFields = getNestedFields(sourceField);
        const parentIsRepeater = isRepeatableParentFieldType(sourceField, config);
        const parentReference = parentIsRepeater ? getFieldTokenReference(sourceField) : null;

        nestedFields.forEach((nestedField) => {
            const nestedReference = getFieldTokenReference(nestedField);
            if (!getFieldEnabled(nestedField) || !nestedReference) {
                return;
            }

            const nestedTypeConfig = config.getFieldTypeByType?.(nestedField.type) || {};
            const nestedReferenceConfig = getFieldTypeReferenceConfig(nestedTypeConfig);
            const nestedLabelBase = nestedField.label || nestedField.handle || '';
            const nestedLabel = labelPrefix ? `${labelPrefix}${nestedLabelBase}` : nestedLabelBase;
            const nestedPrimarySource = getVariableSourceBySelector(nestedTypeConfig, '');

            if (parentIsRepeater && parentReference) {
                if (nestedReferenceConfig.allowPrimary !== false && shouldIncludeVariableSource(nestedPrimarySource, nestedField, config)) {
                    pushRepeaterScopedReferenceOptions(options, {
                        parentReference,
                        nestedLabel,
                        selectorHandle: nestedField.handle || '',
                        selectorLabel: '',
                        source: nestedPrimarySource,
                    });
                }

                nestedReferenceConfig.selectors
                    .filter((selector) => {
                        return shouldIncludeSelector(selector, config.target, nestedField, nestedReferenceConfig, config);
                    })
                    .forEach((selector) => {
                        const source = getVariableSourceBySelector(nestedTypeConfig, selector.handle);
                        if (!shouldIncludeVariableSource(source, nestedField, config)) {
                            return;
                        }

                        pushRepeaterScopedReferenceOptions(options, {
                            parentReference,
                            nestedLabel,
                            selectorHandle: selector.handle,
                            selectorLabel: selector.label || selector.handle,
                            source,
                        });
                    });
            } else if (nestedReferenceConfig.allowPrimary !== false && shouldIncludeVariableSource(nestedPrimarySource, nestedField, config)) {
                options.push(applyVariableSourceMetadata({
                    label: nestedLabel,
                    value: `{field:${nestedReference}}`,
                }, nestedPrimarySource));
            }

            if (!parentIsRepeater) {
                nestedReferenceConfig.selectors
                    .filter((selector) => {
                        return shouldIncludeSelector(selector, config.target, nestedField, nestedReferenceConfig, config);
                    })
                    .forEach((selector) => {
                        const source = getVariableSourceBySelector(nestedTypeConfig, selector.handle);
                        if (!shouldIncludeVariableSource(source, nestedField, config)) {
                            return;
                        }

                        options.push(applyVariableSourceMetadata({
                            label: `${nestedLabel}: ${selector.label || selector.handle}`,
                            value: `{field:${nestedReference}:${selector.handle}}`,
                        }, source));
                    });
            }

            if (nestedReferenceConfig.allowNested && nestedReferenceConfig.nestedMode === 'childrenOnly') {
                appendNestedOptions(nestedField, `${nestedLabel}: `);
            }
        });
    };

    if (referenceConfig.allowNested && referenceConfig.nestedMode === 'childrenOnly') {
        appendNestedOptions(field, fieldLabel ? `${fieldLabel}: ` : '');
    }

    const seen = new Set();
    return options.filter((option) => {
        if (!option?.value || seen.has(option.value)) {
            return false;
        }

        seen.add(option.value);
        return true;
    });
};

const buildFieldReferenceOptions = (field, config, visited = new Set()) => {
    if (!field || typeof field !== 'object') {
        return [];
    }

    const fieldKey = field._id || field.reference || field.handle;
    if (fieldKey && visited.has(fieldKey)) {
        return [];
    }

    const nextVisited = new Set(visited);
    if (fieldKey) {
        nextVisited.add(fieldKey);
    }

    const fieldReference = getFieldTokenReference(field);
    const fieldTypeConfig = config.getFieldTypeByType?.(field.type) || {};
    const referenceConfig = getFieldTypeReferenceConfig(fieldTypeConfig);
    const primarySource = getVariableSourceBySelector(fieldTypeConfig, '');
    const fieldLabel = field?.label || field?.handle || '';
    const label = config.labelPrefix ? `${config.labelPrefix}${fieldLabel}` : fieldLabel;
    const isChildField = Boolean(config.isChildField);

    // Only apply enabled checks for nested child fields. Top-level fields are always considered selectable.
    if (isChildField && !getFieldEnabled(field)) {
        return [];
    }

    const options = [];
    const preferTopLevelForVariablePicker = config.target === 'variablePicker' && config.variablePickerMode === 'topLevel';
    const shouldIncludePrimaryFieldReference = () => {
        if (config.target === 'variablePicker' || config.target === 'fieldSelect' || config.referenceContext === 'client') {
            return shouldIncludeVariableSource(primarySource, field, config);
        }

        return true;
    };

    if (preferTopLevelForVariablePicker && !isChildField && fieldReference) {
        const secondaryOptions = buildVariablePickerSecondaryOptions(field, referenceConfig, config);

        if (!secondaryOptions.length) {
            return [];
        }

        const [primaryOption, ...restSecondaryOptions] = secondaryOptions;
        const hasParentPrimary = referenceConfig.allowPrimary !== false && shouldIncludeVariableSource(primarySource, field, config);
        const topLevelOption = {
            label,
            value: hasParentPrimary ? (primaryOption?.value || `{field:${fieldReference}}`) : `{field:${fieldReference}}`,
        };

        const childOptions = secondaryOptions;
        const hasSelectorOptions = hasParentPrimary ? restSecondaryOptions.length > 0 : childOptions.length > 0;
        if (hasSelectorOptions) {
            topLevelOption.children = childOptions;
        }

        return [hasParentPrimary ? applyVariableSourceMetadata(topLevelOption, primarySource) : topLevelOption];
    }

    if (referenceConfig.allowPrimary !== false && fieldReference && shouldIncludePrimaryFieldReference()) {
        const option = {
            label,
            value: `{field:${fieldReference}}`,
            fieldLabel,
            fieldHandle: field?.handle || '',
            fieldReference,
            isPrimaryFieldReference: true,
        };

        if (config.includeColumnMeta) {
            const column = getConditionColumnOptions(field);

            if (column) {
                option.column = column;
            }
        }

        if (config.target !== 'variablePicker') {
            options.push(option);
        } else {
            options.push(applyVariableSourceMetadata(option, primarySource));
        }
    }

    if (config.includeSelectors !== false && !config.topLevelOnly && fieldReference) {
        referenceConfig.selectors
            .filter((selector) => {
                return shouldIncludeSelector(selector, config.target, field, referenceConfig, config);
            })
            .forEach((selector) => {
                const option = {
                    label: `${label}: ${selector.label || selector.handle}`,
                    value: `{field:${fieldReference}:${selector.handle}}`,
                    fieldLabel,
                    fieldHandle: field?.handle || '',
                    fieldReference,
                    selectorHandle: selector.handle,
                    selectorLabel: selector.label || selector.handle,
                };

                if (config.includeColumnMeta && (selector.handle === 'label' || selector.handle === 'value')) {
                    const column = getConditionColumnOptions(field, selector.handle);

                    if (column) {
                        option.column = column;
                    }
                }

                if (config.target !== 'variablePicker') {
                    if (config.target === 'fieldSelect') {
                        const source = getVariableSourceBySelector(fieldTypeConfig, selector.handle);
                        if (!shouldIncludeVariableSource(source, field, config)) {
                            return;
                        }
                    }

                    options.push(option);
                    return;
                }

                const source = getVariableSourceBySelector(fieldTypeConfig, selector.handle);
                if (shouldIncludeVariableSource(source, field, config)) {
                    options.push(applyVariableSourceMetadata(option, source));
                }
            });
    }

    if (!config.topLevelOnly && referenceConfig.allowNested && referenceConfig.nestedMode === 'childrenOnly') {
        const children = getNestedFields(field);

        children.forEach((childField) => {
            options.push(...buildFieldReferenceOptions(childField, {
                ...config,
                labelPrefix: `${label}: `,
                isChildField: true,
            }, nextVisited));
        });
    }

    return options;
};

const getFieldReferenceOptions = (values = {}, options = {}) => {
    const {
        getFieldTypeByType,
        target = 'fieldSelect',
        includeColumnMeta = false,
        includeSelectors = true,
        topLevelOnly = false,
        includedTypes = [],
        excludedTypes = [],
        excludedFields = [],
        excludeSelf = false,
        excludeSelfFieldId = null,
        excludeByHandle = null,
        maxPageIndex = null,
        variablePickerMode = 'flat',
        variablePickerGroupByPage = false,
        fieldSelectGroupByPage = false,
        variableTypes = [],
        referenceContext = null,
    } = options;

    const excludedFieldsWithSelf = [...excludedFields];
    if (excludeSelf && excludeSelfFieldId) {
        excludedFieldsWithSelf.push(excludeSelfFieldId);
    }

    const includedTypeSet = includedTypes.length ? new Set(includedTypes) : null;
    const excludedTypeSet = excludedTypes.length ? new Set(excludedTypes) : null;
    const excludedFieldSet = new Set(excludedFieldsWithSelf);

    if ((target === 'variablePicker' && variablePickerMode === 'topLevel' && variablePickerGroupByPage) || (target === 'fieldSelect' && fieldSelectGroupByPage)) {
        const pages = values?.pages || [];

        return pages.reduce((acc, page, pageIndex) => {
            if (Number.isInteger(maxPageIndex) && pageIndex > maxPageIndex) {
                return acc;
            }

            const rows = page?.rows || [];
            const pageFields = [];

            rows.forEach((row) => {
                const fields = row?.fields || [];
                fields.forEach((field) => {
                    if (!fieldPassesFilters(field, {
                        includedTypeSet,
                        excludedTypeSet,
                        excludedFieldSet,
                        excludeByHandle,
                    })) {
                        return;
                    }

                    pageFields.push(field);
                });
            });

            const pageOptions = pageFields.flatMap((field) => {
                return buildFieldReferenceOptions(field, {
                    getFieldTypeByType,
                    target,
                    includeColumnMeta,
                    includeSelectors,
                    topLevelOnly,
                    referenceContext,
                    variableTypes,
                    labelPrefix: '',
                    isChildField: false,
                    variablePickerMode,
                });
            });

            if (!pageOptions.length) {
                return acc;
            }

            const pageLabel = page?.label || page?.name || page?.title || Craft.t('formie', 'Page {num}', { num: pageIndex + 1 });
            const decoratedOptions = pageOptions.map((option) => {
                return {
                    ...option,
                    pageLabel: String(pageLabel),
                };
            });
            acc.push(...decoratedOptions);

            return acc;
        }, []);
    }

    const fields = getFormFields(values, {
        includedTypes,
        excludedTypes,
        excludedFields,
        excludeSelf,
        excludeSelfFieldId,
        excludeByHandle,
        maxPageIndex,
    });

    return fields.flatMap((field) => {
        return buildFieldReferenceOptions(field, {
            getFieldTypeByType,
            target,
            includeColumnMeta,
            includeSelectors,
            topLevelOnly,
            referenceContext,
            variableTypes,
            labelPrefix: '',
            isChildField: false,
            variablePickerMode,
        });
    });
};

const useFormValue = (path, fallback = undefined) => {
    const { getValueAtPath } = useFormBuilderForm();
    return getValueAtPath(path, fallback);
};


const fetchIntegrationFormSettingsConfig = async(handle, formId) => {
    if (!handle || !formId) {
        const errorMessage = 'Missing handle or formId';

        return {
            ok: false,
            error: errorMessage,
            errorObject: {
                message: errorMessage,
            },
        };
    }

    try {
        const response = await Craft.sendActionRequest('POST', 'formie/integrations/get-integration-form-settings-config', {
            data: { handle, formId },
        });

        const config = response?.data;
        if (config && (config.schema || config.schemaIndex)) {
            return { ok: true, data: config };
        }

        const errorMessage = response?.data?.message || 'Invalid config';

        return {
            ok: false,
            error: errorMessage,
            errorObject: {
                response: {
                    statusText: response?.statusText || response?.data?.name || 'Request failed',
                    data: response?.data || {},
                },
                message: errorMessage,
            },
        };
    } catch (error) {
        console.error('Error fetching integration form settings config:', error);

        return {
            ok: false,
            error: getRequestErrorMessage(error, 'Request failed.'),
            errorObject: error,
        };
    }
};

const refreshIntegrationFormSettings = async(handle, settings = {}, options = {}) => {
    if (!handle) {
        return { ok: false, error: 'Missing integration handle' };
    }

    const dataKey = String(options?.dataKey || '').trim();
    const refreshParams = options?.refreshParams && typeof options.refreshParams === 'object' && !Array.isArray(options.refreshParams)
        ? options.refreshParams
        : {};

    try {
        const response = await Craft.sendActionRequest('POST', 'formie/integrations/form-settings', {
            data: {
                integration: handle,
                settings,
                ...(dataKey ? { dataKey } : {}),
                ...refreshParams,
            },
        });

        const data = response?.data || {};
        const responseError = getRefreshResponseErrorMessage(data);
        if (responseError) {
            return {
                ok: false,
                error: responseError,
                data,
                errorObject: {
                    response: {
                        statusText: data?.name || 'Request failed',
                        data,
                    },
                    message: responseError,
                },
            };
        }

        return { ok: true, data };
    } catch (error) {
        console.error('Error refreshing integration form settings:', error);

        return {
            ok: false,
            error: getRequestErrorMessage(error, 'Failed to refresh integration data.'),
            errorObject: error,
        };
    }
};

const fetchFieldTypeConfig = async(type, options = {}) => {
    if (!type) {
        return { ok: false, errors: { fieldType: ['Missing field type.'] } };
    }

    const hydrateOnly = options.hydrateOnly !== false;

    try {
        const response = await Craft.sendActionRequest('POST', 'formie/fields/get-field-type-config', {
            data: {
                type,
                hydrateOnly,
            },
        });

        if (response?.data?.fieldType) {
            return { ok: true, data: response.data };
        }

        const responseMessage = response?.data?.message;

        return {
            ok: false,
            errors: {
                fieldType: [responseMessage || 'Failed to fetch field type config.'],
            },
        };
    } catch (error) {
        console.error('Error fetching field type config:', error);
        const responseMessage = error?.response?.data?.message;
        const errorMessage = error?.message;

        return {
            ok: false,
            error,
            errors: {
                fieldType: [responseMessage || errorMessage || 'Failed to fetch field type config.'],
            },
        };
    }
};

const fetchPaymentProviderSettingsSchema = async(providerHandle, options = {}) => {
    if (!providerHandle) {
        return { ok: false, errors: { provider: ['Missing payment provider handle.'] } };
    }

    const schemaGroup = String(options?.schemaGroup || 'defineFormBuilderGeneralSchema').trim() || 'defineFormBuilderGeneralSchema';
    const fieldType = String(options?.fieldType || 'verbb\\formie\\fields\\Payment').trim() || 'verbb\\formie\\fields\\Payment';

    try {
        const response = await Craft.sendActionRequest('POST', 'formie/fields/get-payment-provider-settings-schema', {
            data: {
                providerHandle,
                schemaGroup,
                fieldType,
            },
        });

        return {
            ok: true,
            data: response?.data || {},
        };
    } catch (error) {
        console.error('Error fetching payment provider settings schema:', error);
        const responseMessage = error?.response?.data?.message;
        const errorMessage = error?.message;

        return {
            ok: false,
            error,
            errors: {
                provider: [responseMessage || errorMessage || 'Failed to fetch payment provider settings schema.'],
            },
        };
    }
};

const useIntegrations = () => {
    const integrationsMap = useFormValue('integrations', {});

    return useMemo(() => {
        const integrationGroups = Object.entries(integrationsMap || {}).map(([groupName, integrations]) => {
            return {
                label: groupName,
                handle: groupName.toLowerCase().replace(/\s+/g, '-'),
                integrations: integrations || [],
            };
        });

        const integrations = integrationGroups.reduce((acc, group) => {
            const groupIntegrations = (group.integrations || []).map((integration) => {
                return {
                    ...integration,
                    groupHandle: group.handle,
                    groupLabel: group.label,
                };
            });

            return [...acc, ...groupIntegrations];
        }, []);

        return {
            integrationGroups,
            integrations,
        };
    }, [integrationsMap]);
};


export {
    normalizeFormData,
    serializeFormData,
    prepareFormPreview,
    saveForm,
    saveAsStencil,
    deleteForm,
    fetchIntegrationFormSettingsConfig,
    refreshIntegrationFormSettings,
    fetchFieldTypeConfig,
    fetchPaymentProviderSettingsSchema,
    useFormValues,
    getFormFields,
    getFieldReferenceOptions,
    useFormValue,
    useIntegrations,
};
