import { useMemo } from 'react';
import { cloneDeep } from 'lodash-es';

import { useFormBuilderForm } from '@form-builder/contexts/FormBuilderFormContext';
import {
    createFieldReference,
    ensureUniqueFieldReferencesInForm,
    forEachFieldInLayoutMaps,
    forEachFieldInRows,
} from '@form-builder/utils/fieldReferences';
import { createItem, normalizeCollection, takeAtLeast } from '@verbb/plugin-kit-core';

import { getRequestErrorMessage, normalizeErrorText } from '@utils/requestError';
import { extractSiteTranslationsFromFormData, stripTranslatableValuesToCanonical } from '@form-builder/utils/siteOverrides';

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
        const normalizedRow = row?._id
            ? { ...row, _id: row._id }
            : createItem(row);
        const sourceFields = Array.isArray(row?.fields) ? row.fields : [];

        const normalizedFields = sourceFields.map((field) => {
            if (!field || typeof field !== 'object') {
                return null;
            }

            const normalizedField = field?._id
                ? { ...field, _id: field._id }
                : createItem(field);
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
    forEachFieldInRows(rows, (field) => {
        if (!String(field.reference || '').trim()) {
            field.reference = createFieldReference();
        }

        forEachFieldInLayoutMaps(field.layouts, (layoutField) => {
            if (!String(layoutField.reference || '').trim()) {
                layoutField.reference = createFieldReference();
            }
        });
        forEachFieldInLayoutMaps(field?.settings?.layouts, (layoutField) => {
            if (!String(layoutField.reference || '').trim()) {
                layoutField.reference = createFieldReference();
            }
        });
    });
};

const collectFieldReferenceMap = (rows = []) => {
    const referenceByClientId = new Map();
    const referencesByHandle = new Map();

    const registerFieldReference = (field) => {
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
    };

    const walk = (sourceRows = []) => {
        forEachFieldInRows(sourceRows, (field) => {
            registerFieldReference(field);
            forEachFieldInLayoutMaps(field.layouts, registerFieldReference);
            forEachFieldInLayoutMaps(field?.settings?.layouts, registerFieldReference);
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

const prepareFormSnapshotPayload = (data = {}) => {
    const serialized = cloneDeep(data);
    const notifications = Array.isArray(serialized?.notifications) ? serialized.notifications : [];

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

// Dirty-state comparisons must not mutate field references. Date/Time fields intentionally
// share sub-field references across active rows and per-display layout variants.
const serializeFormDataForDirtyCheck = (data = {}) => {
    return prepareFormSnapshotPayload(data);
};

const serializeFormData = (data = {}) => {
    const serialized = prepareFormSnapshotPayload(data);

    (serialized.pages || []).forEach((page) => {
        assignMissingFieldReferences(page?.rows || []);
    });

    ensureUniqueFieldReferencesInForm(serialized.pages || []);

    const fieldReferenceMap = {};
    (serialized.pages || []).forEach((page) => {
        Object.assign(fieldReferenceMap, collectFieldReferenceMap(page?.rows || []));
    });

    if (serialized?.settings?.integrations && typeof serialized.settings.integrations === 'object') {
        remapFieldReferenceTokens(serialized.settings.integrations, fieldReferenceMap);
    }

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

    const {
        saveAsNew = false,
        action = 'formie/forms/save',
        requestData = {},
        canonicalData = null,
        sourceSiteId = null,
    } = options;
    const activeSiteId = Number(requestData?.siteId);
    const shouldIncludeTranslations = Boolean(
        canonicalData
        && sourceSiteId
        && activeSiteId
        && activeSiteId !== Number(sourceSiteId),
    );
    const payloadSource = shouldIncludeTranslations
        ? stripTranslatableValuesToCanonical(formValues, canonicalData)
        : formValues;
    const data = serializeFormData(payloadSource);
    const translations = shouldIncludeTranslations
        ? extractSiteTranslationsFromFormData(canonicalData, formValues)
        : null;

    if (saveAsNew) {
        data.saveAsNew = true;
    }

    try {
        const response = await takeAtLeast(500)(
            Craft.sendActionRequest('POST', action, {
                data: {
                    ...requestData,
                    ...data,
                    ...(translations !== null ? { translations } : {}),
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

const getFieldTypeReferenceDeclaration = (fieldTypeConfig = {}) => {
    const values = Array.isArray(fieldTypeConfig?.referenceValues) ? fieldTypeConfig.referenceValues : [];

    return {
        primary: values.find((value) => { return value?.kind === 'primary'; }) || null,
        selectors: values.filter((value) => { return value?.kind === 'selector' && value?.selector; }),
    };
};

const matchesReferenceCondition = (condition, settings) => {
    if (!condition || typeof condition !== 'object') {
        return true;
    }

    if (condition.operator === 'all' || condition.operator === 'any') {
        const conditions = Array.isArray(condition.conditions) ? condition.conditions : [];
        return condition.operator === 'all'
            ? conditions.every((child) => { return matchesReferenceCondition(child, settings); })
            : conditions.some((child) => { return matchesReferenceCondition(child, settings); });
    }

    const value = String(condition.property || '').split('.').reduce((current, part) => {
        return current && typeof current === 'object' ? current[part] : undefined;
    }, settings);

    return condition.operator === 'notEquals' ? value !== condition.value : value === condition.value;
};

const getReferenceValueBySelector = (fieldTypeConfig = {}, selector = '') => {
    const declaration = getFieldTypeReferenceDeclaration(fieldTypeConfig);
    return selector ? declaration.selectors.find((value) => { return value.selector === selector; }) || null : declaration.primary;
};

const shouldIncludeVariableSource = (source, field, config = {}) => {
    if (!source || typeof source !== 'object') {
        return false;
    }

    if (config.target === 'variablePicker' && source.supportsVariablePicker === false) {
        return false;
    }

    if (config.referenceContext === 'client' && source.supportsBrowser === false) {
        return false;
    }

    if (source.when && !matchesReferenceCondition(source.when, field || {})) {
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
    const shape = source?.shape === 'block' ? 'block' : 'inline';
    const types = Array.isArray(source?.types) ? source.types : [];

    return {
        ...option,
        shape,
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

const isTableFieldType = (field, config) => {
    const fieldTypeConfig = config.getFieldTypeByType?.(field?.type) || {};
    return Boolean(fieldTypeConfig.isTableField);
};

const getTableColumns = (field) => {
    const rawColumns = field?.columns ?? field?.settings?.columns ?? {};

    if (Array.isArray(rawColumns)) {
        return rawColumns.flatMap((column, index) => {
            if (!column || typeof column !== 'object') {
                return [];
            }

            const id = String(column.id || column.handle || `col${index + 1}`).trim();

            if (!id) {
                return [];
            }

            return [{
                id,
                heading: String(column.heading || column.label || column.handle || id).trim() || id,
                type: String(column.type || 'singleline').trim() || 'singleline',
            }];
        });
    }

    return Object.entries(rawColumns).flatMap(([id, column]) => {
        if (!column || typeof column !== 'object') {
            return [];
        }

        const columnId = String(id || column.id || '').trim();

        if (!columnId) {
            return [];
        }

        return [{
            id: columnId,
            heading: String(column.heading || column.handle || columnId).trim() || columnId,
            type: String(column.type || 'singleline').trim() || 'singleline',
        }];
    });
};

const getTableColumnVariableSource = (column) => {
    const type = String(column?.type || 'singleline').trim();
    const types = {
        number: ['number', 'text'],
        email: ['email', 'text'],
        url: ['url', 'text'],
        date: ['date', 'text'],
    }[type] || ['text'];

    return {
        kind: 'selector',
        selector: column?.id || '',
        label: column?.heading || column?.id || '',
        types,
        shape: 'inline',
        supportsFieldSelect: true,
        supportsVariablePicker: true,
        supportsBrowser: true,
        allowTransforms: true,
        meta: { rowScoped: true },
    };
};

const getFieldReferenceDeclaration = (field, fieldTypeConfig, config) => {
    if (!isTableFieldType(field, config)) {
        return getFieldTypeReferenceDeclaration(fieldTypeConfig);
    }

    return {
        primary: null,
        selectors: getTableColumns(field).map(getTableColumnVariableSource),
    };
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

const pushRowScopedReferenceOptions = (targetOptions, {
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
    }, source));
};

const pushRepeaterScopedReferenceOptions = (targetOptions, options) => {
    pushRowScopedReferenceOptions(targetOptions, options);
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

const shouldIncludeSelector = (selector, target, sourceField, config = {}) => {
    if (!selector || typeof selector !== 'object') {
        return false;
    }

    if (!selector.selector) {
        return false;
    }

    if (target === 'fieldSelect' && selector.supportsFieldSelect === false) {
        return false;
    }

    if (target === 'variablePicker' && selector.supportsVariablePicker === false) {
        return false;
    }

    if (config.referenceContext === 'client' && selector.supportsBrowser === false) {
        return false;
    }

    if (selector.when && !matchesReferenceCondition(selector.when, sourceField || {})) {
        return false;
    }

    // If a selector maps directly to a child field handle (eg Name:prefix),
    // only include it when that child sub-field is enabled.
    const nestedChildField = getNestedChildFieldByHandle(sourceField, selector.selector);
    if (nestedChildField && !getFieldEnabled(nestedChildField)) {
        return false;
    }

    return true;
};

const buildVariablePickerSecondaryOptions = (field, referenceDeclaration, config) => {
    const options = [];
    const fieldReference = getFieldTokenReference(field);
    const fieldLabel = field?.label || field?.handle || '';
    const primarySource = referenceDeclaration.primary;

    if (!fieldReference) {
        return options;
    }

    const buildToken = (source, selector = '') => {
        const scope = source?.meta?.rowScoped ? 'first' : '';

        return scope
            ? buildRepeaterReferenceToken(fieldReference, selector, scope)
            : `{field:${fieldReference}${selector ? `:${selector}` : ''}}`;
    };

    if (shouldIncludeVariableSource(primarySource, field, config)) {
        const primaryLabel = primarySource?.label || Craft.t('formie', 'Value');
        options.push(applyVariableSourceMetadata({
            label: fieldLabel ? `${fieldLabel}: ${primaryLabel}` : primaryLabel,
            value: buildToken(primarySource),
        }, primarySource));
    }

    referenceDeclaration.selectors
        .filter((selector) => {
            return shouldIncludeSelector(selector, config.target, field, config);
        })
        .forEach((selector) => {
            if (!shouldIncludeVariableSource(selector, field, config)) {
                return;
            }

            const selectorLabel = selector.label || selector.selector;
            options.push(applyVariableSourceMetadata({
                label: fieldLabel ? `${fieldLabel}: ${selectorLabel}` : selectorLabel,
                value: buildToken(selector, selector.selector),
            }, selector));
        });

    const appendNestedOptions = (sourceField, labelPrefix = '') => {
        const nestedFields = getNestedFields(sourceField);
        const parentIsRepeater = isRepeatableParentFieldType(sourceField, config);

        nestedFields.forEach((nestedField) => {
            const nestedReference = getFieldTokenReference(nestedField);
            if (!getFieldEnabled(nestedField) || !nestedReference) {
                return;
            }

            const nestedTypeConfig = config.getFieldTypeByType?.(nestedField.type) || {};
            const nestedDeclaration = getFieldReferenceDeclaration(nestedField, nestedTypeConfig, config);
            const nestedLabelBase = nestedField.label || nestedField.handle || '';
            const nestedLabel = labelPrefix ? `${labelPrefix}${nestedLabelBase}` : nestedLabelBase;
            const nestedPrimary = nestedDeclaration.primary;

            if (parentIsRepeater) {
                if (shouldIncludeVariableSource(nestedPrimary, nestedField, config)) {
                    pushRepeaterScopedReferenceOptions(options, {
                        parentReference: nestedReference,
                        nestedLabel,
                        selectorHandle: '',
                        selectorLabel: '',
                        source: nestedPrimary,
                    });
                }

                nestedDeclaration.selectors
                    .filter((selector) => {
                        return shouldIncludeSelector(selector, config.target, nestedField, config);
                    })
                    .forEach((selector) => {
                        if (!shouldIncludeVariableSource(selector, nestedField, config)) {
                            return;
                        }

                        pushRepeaterScopedReferenceOptions(options, {
                            parentReference: nestedReference,
                            nestedLabel,
                            selectorHandle: selector.selector,
                            selectorLabel: selector.label || selector.selector,
                            source: selector,
                        });
                    });
            } else {
                if (shouldIncludeVariableSource(nestedPrimary, nestedField, config)) {
                    options.push(applyVariableSourceMetadata({
                        label: nestedLabel,
                        value: `{field:${nestedReference}}`,
                    }, nestedPrimary));
                }

                nestedDeclaration.selectors
                    .filter((selector) => {
                        return shouldIncludeSelector(selector, config.target, nestedField, config);
                    })
                    .forEach((selector) => {
                        if (!shouldIncludeVariableSource(selector, nestedField, config)) {
                            return;
                        }

                        options.push(applyVariableSourceMetadata({
                            label: `${nestedLabel}: ${selector.label || selector.selector}`,
                            value: `{field:${nestedReference}:${selector.selector}}`,
                        }, selector));
                    });
            }

            if (getNestedFields(nestedField).length) {
                appendNestedOptions(nestedField, `${nestedLabel}: `);
            }
        });
    };

    if (getNestedFields(field).length) {
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
    const referenceDeclaration = getFieldReferenceDeclaration(field, fieldTypeConfig, config);
    const primarySource = referenceDeclaration.primary;
    const fieldLabel = field?.label || field?.handle || '';
    const label = config.labelPrefix ? `${config.labelPrefix}${fieldLabel}` : fieldLabel;
    const isChildField = Boolean(config.isChildField);
    const buildFieldToken = (selectorHandle = '', source = null) => {
        if (!fieldReference) {
            return null;
        }

        const parts = [fieldReference];
        if (selectorHandle) {
            parts.push(selectorHandle);
        }

        const rowScope = config.rowScope || (source?.meta?.rowScoped ? 'first' : '');
        const scope = rowScope ? `;scope=${rowScope}` : '';
        return `{field:${parts.join(':')}${scope}}`;
    };

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
        const secondaryOptions = buildVariablePickerSecondaryOptions(field, referenceDeclaration, config);

        if (!secondaryOptions.length) {
            return [];
        }

        const [primaryOption, ...restSecondaryOptions] = secondaryOptions;
        const hasParentPrimary = shouldIncludeVariableSource(primarySource, field, config);
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

    if (primarySource && fieldReference && shouldIncludePrimaryFieldReference()) {
        const option = {
            label,
            value: buildFieldToken('', primarySource) || `{field:${fieldReference}}`,
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
        referenceDeclaration.selectors
            .filter((selector) => {
                return shouldIncludeSelector(selector, config.target, field, config);
            })
            .forEach((selector) => {
                const option = {
                    label: `${label}: ${selector.label || selector.selector}`,
                    value: buildFieldToken(selector.selector, selector) || `{field:${fieldReference}:${selector.selector}}`,
                    fieldLabel,
                    fieldHandle: field?.handle || '',
                    fieldReference,
                    selectorHandle: selector.selector,
                    selectorLabel: selector.label || selector.selector,
                };

                if (config.includeColumnMeta && (selector.selector === 'label' || selector.selector === 'value')) {
                    const column = getConditionColumnOptions(field, selector.selector);

                    if (column) {
                        option.column = column;
                    }
                }

                if (config.target !== 'variablePicker') {
                    if (config.target === 'fieldSelect') {
                        if (!shouldIncludeVariableSource(selector, field, config)) {
                            return;
                        }
                    }

                    options.push(option);
                    return;
                }

                if (shouldIncludeVariableSource(selector, field, config)) {
                    options.push(applyVariableSourceMetadata(option, selector));
                }
            });
    }

    if (!config.topLevelOnly && getNestedFields(field).length) {
        const children = getNestedFields(field);
        const parentIsRepeater = isRepeatableParentFieldType(field, config);

        children.forEach((childField) => {
            options.push(...buildFieldReferenceOptions(childField, {
                ...config,
                labelPrefix: `${label}: `,
                isChildField: true,
                rowScope: parentIsRepeater ? 'first' : config.rowScope,
            }, nextVisited));
        });
    }

    return options.map((option) => ({ ...option, conditionOperators: option.conditionOperators ?? fieldTypeConfig.conditionOperators, conditionValueType: option.conditionValueType ?? fieldTypeConfig.conditionValueType }));
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

    const formId = options?.formId;
    if (!formId) {
        return { ok: false, error: 'Missing formId' };
    }

    const dataKey = String(options?.dataKey || '').trim();
    const refreshParams = options?.refreshParams && typeof options.refreshParams === 'object' && !Array.isArray(options.refreshParams)
        ? options.refreshParams
        : {};

    try {
        const response = await Craft.sendActionRequest('POST', 'formie/integrations/form-settings', {
            data: {
                integration: handle,
                formId,
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
    serializeFormDataForDirtyCheck,
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
