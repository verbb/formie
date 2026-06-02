import { useCallback, useMemo } from 'react';
import useAppStore from '@form-builder/hooks/useAppStore';
import { useFormBuilderForm } from '@form-builder/contexts/FormBuilderFormContext';
import { getFieldReferenceOptions } from '@form-builder/hooks/useFormTools';

const CONTENT_ANY = 'any';
const CONTENT_SINGLE_LINE = 'singleLine';
const GROUP_FIELDS = 'fieldsVariables';

const normalizeVariableConfig = (variableConfig) => {
    if (!variableConfig || typeof variableConfig !== 'object') {
        return null;
    }

    return variableConfig;
};

const expandGroupKeys = (groupKey, aliases, staticGroups) => {
    if (!groupKey || typeof groupKey !== 'string') {
        return [];
    }

    const aliasTargets = aliases[groupKey];
    if (Array.isArray(aliasTargets) && aliasTargets.length) {
        return aliasTargets.flatMap((item) => { return expandGroupKeys(item, aliases, staticGroups); });
    }

    if (groupKey === GROUP_FIELDS || staticGroups[groupKey]) {
        return [groupKey];
    }

    return [];
};

const resolveGroupSections = (variableConfig, config) => {
    const aliases = config?.groupAliases || {};
    const staticGroups = config?.staticGroups || {};
    const groups = Array.isArray(variableConfig?.groups) ? variableConfig.groups : [];

    if (!groups.length) {
        return [{ key: GROUP_FIELDS, label: GROUP_FIELDS, groups: [GROUP_FIELDS] }];
    }

    const isLayout = groups.some((item) => { return item && typeof item === 'object' && Array.isArray(item.groups); });

    if (isLayout) {
        return groups
            .map((section, index) => {
                if (!section || typeof section !== 'object' || !Array.isArray(section.groups)) {
                    return null;
                }

                const expanded = Array.from(new Set(section.groups.flatMap((groupKey) => {
                    return expandGroupKeys(groupKey, aliases, staticGroups);
                })));

                if (!expanded.length) {
                    return null;
                }

                const label = String(section.label || `Group ${index + 1}`);
                return {
                    key: label,
                    label,
                    groups: expanded,
                };
            })
            .filter(Boolean);
    }

    const sections = groups.flatMap((groupKey) => {
        const expanded = Array.from(new Set(expandGroupKeys(groupKey, aliases, staticGroups)));

        if (!expanded.length) {
            return [];
        }

        // Keep aliases (e.g. STATIC_FORM) as a single UI section.
        return [{
            key: groupKey,
            label: groupKey,
            groups: expanded,
        }];
    });

    return sections;
};

const getItemContent = (item) => {
    if (typeof item?.content === 'string' && item.content) {
        return item.content;
    }

    return CONTENT_SINGLE_LINE;
};

const getItemTypes = (item) => {
    if (Array.isArray(item?.types) && item.types.length) {
        return item.types;
    }

    return getItemContent(item) === CONTENT_SINGLE_LINE ? ['text'] : [];
};

const filterPickerItems = (items = [], variableConfig = {}) => {
    const requestedTypes = Array.isArray(variableConfig?.types) ? variableConfig.types : [];
    const requestedContent = variableConfig?.content || CONTENT_ANY;

    const itemMatches = (item) => {
        const itemMode = getItemContent(item);
        if (requestedContent && requestedContent !== CONTENT_ANY && itemMode !== requestedContent) {
            return false;
        }

        if (!requestedTypes.length) {
            return true;
        }

        const itemTypes = getItemTypes(item);
        return itemTypes.some((type) => { return requestedTypes.includes(type); });
    };

    return items.reduce((result, item) => {
        if (!item || typeof item !== 'object') {
            return result;
        }

        const children = Array.isArray(item.children) ? item.children : [];

        if (!children.length) {
            if (itemMatches(item)) {
                result.push(item);
            }

            return result;
        }

        const filteredChildren = filterPickerItems(children, variableConfig);
        if (!filteredChildren.length && !itemMatches(item)) {
            return result;
        }

        result.push({
            ...item,
            children: filteredChildren,
        });

        return result;
    }, []);
};

const getFieldTokenReference = (field) => {
    const reference = typeof field?.reference === 'string' ? field.reference.trim() : '';
    if (reference) {
        return reference;
    }

    const handle = typeof field?.handle === 'string' ? field.handle.trim() : '';
    return handle || null;
};

/**
 * Resolves variable categories from config + form values.
 *
 * @param {object} config - variable picker config from server
 * @param {object} formValues - form builder values (with pages)
 * @param {object} variableConfig - variable request config from schema
 * @param {object} options - { form: schema form for excludeSelf }
 * @returns {object} variableCategories
 */
export function resolveVariableCategories(config, formValues, variableConfig, options = {}) {
    const request = normalizeVariableConfig(variableConfig);
    if (!request) {
        return {};
    }

    const staticGroups = config?.staticGroups || {};
    const sections = resolveGroupSections(request, config);

    const {
        excludeSelf = false,
        excludeSelfFieldId = null,
        excludedTypes = [],
        groupFieldsByPage = true,
        fieldSelectionPageScope = 'all',
        currentPageIndex = null,
        maxPageIndex: requestedMaxPageIndex = null,
    } = request;

    const excludeByHandle = excludeSelf && options.form
        ? options.form.getFieldValue?.('handle') ?? null
        : null;

    const getFieldTypeByType = options.getFieldTypeByType || (() => { return null; });

    const maxPageIndex = Number.isInteger(requestedMaxPageIndex)
        ? requestedMaxPageIndex
        : (
            fieldSelectionPageScope === 'currentAndPrevious' && Number.isInteger(currentPageIndex)
                ? currentPageIndex
                : (
                    fieldSelectionPageScope === 'previousOnly' && Number.isInteger(currentPageIndex)
                        ? currentPageIndex - 1
                        : null
                )
        );

    const formFieldOptions = getFieldReferenceOptions(formValues, {
        getFieldTypeByType,
        target: 'variablePicker',
        referenceContext: request.referenceContext || null,
        variablePickerMode: 'topLevel',
        variablePickerGroupByPage: groupFieldsByPage,
        excludedTypes: excludedTypes.length ? excludedTypes : undefined,
        excludedFields: [],
        excludeSelf,
        excludeSelfFieldId: excludeSelfFieldId || undefined,
        excludeByHandle: excludeByHandle || undefined,
        maxPageIndex,
    });

    const parseFieldReference = (tokenValue = '') => {
        const match = String(tokenValue).match(/^\{field:([^}:]+)(?::[^}]+)?\}$/);
        return match ? match[1] : null;
    };

    const fieldMetaByReference = new Map();
    (formValues?.pages || []).forEach((page) => {
        (page?.rows || []).forEach((row) => {
            (row?.fields || []).forEach((field) => {
                const tokenReference = getFieldTokenReference(field);
                if (!tokenReference) {
                    return;
                }

                const fieldMeta = {
                    handle: field.handle || '',
                    type: field.type || '',
                };

                // Index by both reference and handle so legacy handle-based tokens
                // still get transform/type metadata while being migrated.
                fieldMetaByReference.set(tokenReference, fieldMeta);
                if (field?.reference && field?.handle) {
                    fieldMetaByReference.set(field.handle, fieldMeta);
                }
            });
        });
    });

    const disambiguateFieldLabels = (items = []) => {
        const labelCount = new Map();
        items.forEach((item) => {
            const key = String(item?.label || '');
            labelCount.set(key, (labelCount.get(key) || 0) + 1);
        });

        return items.map((item) => {
            const baseLabel = String(item?.label || '');
            if ((labelCount.get(baseLabel) || 0) <= 1) {
                return item;
            }

            const reference = parseFieldReference(item?.value);
            const meta = reference ? fieldMetaByReference.get(reference) : null;
            const handle = meta?.handle || '';

            if (!handle) {
                return item;
            }

            return {
                ...item,
                label: `${baseLabel} (${handle})`,
            };
        });
    };

    const dedupeByValue = (items = []) => {
        const seen = new Set();
        return items.filter((item) => {
            const key = item?.value || item?.label;
            if (!key || seen.has(key)) {
                return false;
            }
            seen.add(key);
            return true;
        });
    };

    const dynamicFieldOptions = filterPickerItems(dedupeByValue(disambiguateFieldLabels(formFieldOptions)), request);
    const grouped = {};

    sections.forEach((section) => {
        const sectionItems = [];

        section.groups.forEach((groupKey) => {
            if (groupKey === GROUP_FIELDS) {
                sectionItems.push(...dynamicFieldOptions);
                return;
            }

            const groupItems = staticGroups[groupKey];
            if (!Array.isArray(groupItems) || !groupItems.length) {
                return;
            }

            sectionItems.push(...filterPickerItems(groupItems, request));
        });

        const deduped = dedupeByValue(sectionItems);
        if (deduped.length) {
            grouped[section.key] = deduped;
        }
    });

    return grouped;
}

/**
 * Resolves variable categories for a rich text / variable picker field.
 */
export function useVariableCategories(variableConfig, options = {}) {
    const config = useAppStore((state) => { return state.variableCategoriesConfig; }) ?? {};
    const getFieldTypeByType = useAppStore((state) => { return state.getFieldTypeByType; });
    const formBuilderForm = useFormBuilderForm();
    const formValues = formBuilderForm?.values ?? {};

    return useMemo(() => {
        return resolveVariableCategories(config, formValues, variableConfig, { ...options, getFieldTypeByType });
    }, [config, formValues, variableConfig, options.form, getFieldTypeByType]);
}

/**
 * Returns a getter function for use in context. Memoized by config and formValues.
 */
export function useVariableCategoriesResolver() {
    const config = useAppStore((state) => { return state.variableCategoriesConfig; }) ?? {};
    const getFieldTypeByType = useAppStore((state) => { return state.getFieldTypeByType; });
    const formBuilderForm = useFormBuilderForm();
    const formValues = formBuilderForm?.values ?? {};

    return useCallback(
        (variableConfig, options = {}) => {
            return resolveVariableCategories(config, formValues, variableConfig, { ...options, getFieldTypeByType });
        },
        [config, formValues, getFieldTypeByType],
    );
}
