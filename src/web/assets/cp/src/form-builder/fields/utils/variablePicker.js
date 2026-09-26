import { parseReference, serializeReference } from '@verbb/formie-core';
import {
    createSyntheticRepeaterSubFieldOption,
    getRepeaterBaseToken,
    isRepeaterScopedFieldToken,
    isRepeaterSubFieldOption,
    parseRepeaterRowTargeting,
    resolveRepeaterVariableDisplayLabel,
} from '@form-builder/fields/utils/repeaterRowTargeting';

/** Metadata that identifies a reference variant (kept in token body for lookup). */
const REFERENCE_METADATA_KEYS = new Set(['scope', 'index', 'rows']);

export const collectSelectableValues = (variableCategories = {}) => {
    const values = new Set(['']);

    const walk = (items = []) => {
        items.forEach((item) => {
            if (!item || typeof item !== 'object') {
                return;
            }

            if (item.value != null && item.value !== '') {
                values.add(String(item.value));
            }

            if (Array.isArray(item.children) && item.children.length) {
                walk(item.children);
            }
        });
    };

    Object.values(variableCategories).forEach((items) => {
        if (Array.isArray(items)) {
            walk(items);
        }
    });

    return values;
};

export const getComparableTokenValue = (tokenValue = '') => {
    if (!tokenValue) {
        return '';
    }

    if (typeof tokenValue !== 'string') {
        return String(tokenValue);
    }

    const expression = parseReference(tokenValue);
    return expression.isValid ? serializeReference({ ...expression, default: '' }) : tokenValue;
};

const variableValuesMatchReference = (tokenValue = '', optionValue = '') => {
    const comparableToken = getComparableTokenValue(tokenValue);
    const comparableOption = getComparableTokenValue(optionValue);

    if (!comparableToken || !comparableOption) {
        return false;
    }

    if (comparableToken === comparableOption) {
        return true;
    }

    // Repeater bases only apply to field tokens (`{field:…}`). Non-field tokens
    // (e.g. `{timestamp}`, `{form:name}`) both resolve to '', which previously
    // matched every static variable to the first catalog leaf — wiping type
    // hints like date → Date Format off Current Date/Time.
    const tokenBase = getRepeaterBaseToken(comparableToken);
    const optionBase = getRepeaterBaseToken(comparableOption);

    if (!tokenBase || !optionBase) {
        return false;
    }

    return tokenBase === optionBase;
};

export const findOptionLabelByValue = (variableCategories = {}, tokenValue = '', {
    emptyLabel = '',
    includeParentLabel = false,
    t = null,
} = {}) => {
    const comparableToken = getComparableTokenValue(tokenValue);
    if (!comparableToken) {
        return emptyLabel;
    }

    let match = null;
    let matchedOption = null;
    let repeaterMatch = null;
    let repeaterMatchedOption = null;

    const buildDisplayLabel = (parentLabel, labelBase) => {
        if (!includeParentLabel || !parentLabel) {
            return labelBase;
        }

        if (labelBase.startsWith(`${parentLabel}: `)) {
            return labelBase;
        }

        return `${parentLabel}: ${labelBase}`;
    };

    const walk = (items = [], parentLabel = '') => {
        items.forEach((item) => {
            if (!item || typeof item !== 'object') {
                return;
            }

            const labelBase = String(item.label || item.value || '');
            const label = buildDisplayLabel(parentLabel, labelBase);
            const value = item.value != null ? String(item.value) : '';
            const hydrateValues = Array.isArray(item.hydrateValues)
                ? item.hydrateValues.map((entry) => String(entry || ''))
                : [];
            const matches = [value, ...hydrateValues].some((candidate) => {
                return variableValuesMatchReference(comparableToken, candidate);
            });

            if (matches) {
                if (isRepeaterSubFieldOption(item)) {
                    repeaterMatch = label;
                    repeaterMatchedOption = item;
                } else if (!match) {
                    match = label;
                    matchedOption = item;
                }
            }

            if (Array.isArray(item.children) && item.children.length) {
                walk(item.children, labelBase);
            }
        });
    };

    Object.values(variableCategories).forEach((items) => {
        if (Array.isArray(items)) {
            walk(items);
        }
    });

    const resolvedMatch = repeaterMatch || match;
    const resolvedOption = repeaterMatchedOption || matchedOption;

    if (resolvedMatch && resolvedOption && isRepeaterSubFieldOption(resolvedOption) && typeof t === 'function') {
        return resolveRepeaterVariableDisplayLabel(comparableToken, resolvedOption, t);
    }

    return resolvedMatch;
};

export const findInitialPickerPageForValue = (variableCategories = {}, tokenValue = '') => {
    const comparableToken = getComparableTokenValue(tokenValue);
    if (!comparableToken) {
        return null;
    }

    const findInItems = (items = [], parent = null) => {
        for (const item of items) {
            if (!item || typeof item !== 'object') {
                continue;
            }

            const value = item.value != null ? String(item.value) : '';
            const hydrateValues = Array.isArray(item.hydrateValues)
                ? item.hydrateValues.map((entry) => String(entry || ''))
                : [];
            const matches = [value, ...hydrateValues].some((candidate) => {
                return variableValuesMatchReference(comparableToken, candidate);
            });

            if (matches) {
                return parent;
            }

            const children = Array.isArray(item.children) ? item.children : [];
            if (children.length) {
                const found = findInItems(children, item);
                if (found) {
                    return found;
                }
            }
        }

        return null;
    };

    for (const items of Object.values(variableCategories)) {
        if (!Array.isArray(items)) {
            continue;
        }

        const foundParent = findInItems(items, null);
        if (foundParent) {
            return foundParent;
        }
    }

    return null;
};

export const findRepeaterSubFieldOption = (variableCategories = {}, tokenValue = '') => {
    const comparableToken = getComparableTokenValue(tokenValue);
    if (!comparableToken) {
        return null;
    }

    let repeaterMatch = null;

    const walk = (items = []) => {
        items.forEach((item) => {
            if (repeaterMatch || !item || typeof item !== 'object') {
                return;
            }

            const children = Array.isArray(item.children) ? item.children : [];
            if (children.length) {
                walk(children);
            }

            const value = item.value != null ? String(item.value) : '';
            if (variableValuesMatchReference(comparableToken, value) && isRepeaterSubFieldOption(item)) {
                repeaterMatch = item;
            }
        });
    };

    Object.values(variableCategories).forEach((items) => {
        if (Array.isArray(items)) {
            walk(items);
        }
    });

    return repeaterMatch;
};

export const resolveRepeaterConfigureOption = (variableCategories = {}, tokenValue = '', {
    fallbackLabel = '',
    variableOption = null,
} = {}) => {
    if (isRepeaterSubFieldOption(variableOption)) {
        return variableOption;
    }

    const repeaterOption = findRepeaterSubFieldOption(variableCategories, tokenValue);
    if (repeaterOption) {
        return repeaterOption;
    }

    if (!isRepeaterScopedFieldToken(tokenValue)) {
        return null;
    }

    return createSyntheticRepeaterSubFieldOption(tokenValue, fallbackLabel || variableOption?.label || '');
};

export const findVariableOptionByValue = (variableCategories = {}, tokenValue = '') => {
    const comparableToken = getComparableTokenValue(tokenValue);
    if (!comparableToken) {
        return null;
    }

    let repeaterMatch = null;
    let fallbackMatch = null;

    const walk = (items = []) => {
        items.forEach((item) => {
            if (!item || typeof item !== 'object') {
                return;
            }

            const children = Array.isArray(item.children) ? item.children : [];
            if (children.length) {
                walk(children);
            }

            const value = item.value != null ? String(item.value) : '';
            const hydrateValues = Array.isArray(item.hydrateValues)
                ? item.hydrateValues.map((entry) => String(entry || ''))
                : [];
            const matches = [value, ...hydrateValues].some((candidate) => {
                return variableValuesMatchReference(comparableToken, candidate);
            });

            if (!matches) {
                return;
            }

            if (isRepeaterSubFieldOption(item)) {
                repeaterMatch = item;
                return;
            }

            if (!fallbackMatch) {
                fallbackMatch = item;
            }
        });
    };

    Object.values(variableCategories).forEach((items) => {
        if (Array.isArray(items)) {
            walk(items);
        }
    });

    return repeaterMatch || fallbackMatch;
};

export const buildVariableOptionIndex = (variableCategories = {}, {
    includeParentLabel = false,
} = {}) => {
    const labelByValue = new Map();
    const optionByValue = new Map();

    const buildDisplayLabel = (parentLabel, labelBase) => {
        if (!includeParentLabel || !parentLabel) {
            return labelBase;
        }

        if (labelBase.startsWith(`${parentLabel}: `)) {
            return labelBase;
        }

        return `${parentLabel}: ${labelBase}`;
    };

    const walk = (items = [], parentLabel = '') => {
        items.forEach((item) => {
            if (!item || typeof item !== 'object') {
                return;
            }

            const labelBase = String(item.label || item.value || '');
            const label = buildDisplayLabel(parentLabel, labelBase);
            const value = item.value != null ? String(item.value) : '';
            const indexValues = [
                value,
                ...(Array.isArray(item.hydrateValues)
                    ? item.hydrateValues.map((entry) => String(entry || ''))
                    : []),
            ].filter(Boolean);

            indexValues.forEach((indexValue) => {
                if (!optionByValue.has(indexValue)) {
                    optionByValue.set(indexValue, item);
                    labelByValue.set(indexValue, label);
                }
            });

            if (Array.isArray(item.children) && item.children.length) {
                walk(item.children, labelBase);
            }
        });
    };

    Object.values(variableCategories).forEach((items) => {
        if (Array.isArray(items)) {
            walk(items);
        }
    });

    return {
        labelByValue,
        optionByValue,
    };
};

export const parseVariableTokenMetadata = (tokenValue = '') => {
    const expression = parseReference(String(tokenValue || ''));
    if (!expression.isValid) return { tokenWithoutDefault: String(tokenValue || ''), defaultIfEmpty: '', transformerId: '', transformerParams: {}, referenceParams: {}, diagnostic: expression.diagnostic };
    const referenceParams = Object.fromEntries(Object.entries(expression.transformerParams).filter(([key]) => REFERENCE_METADATA_KEYS.has(key)));
    const transformerParams = Object.fromEntries(Object.entries(expression.transformerParams).filter(([key]) => !REFERENCE_METADATA_KEYS.has(key)));
    return {
        tokenWithoutDefault: serializeReference({ ...expression, default: '', transformerId: '', transformerParams: referenceParams }),
        defaultIfEmpty: expression.default,
        transformerId: expression.transformerId,
        transformerParams,
        referenceParams,
    };
};

export const serializeVariableTokenMetadata = (baseToken, { defaultIfEmpty = '', transformerId = '', transformerParams = {} } = {}) => {
    const expression = parseReference(String(baseToken || ''));
    if (!expression.isValid) return String(baseToken || '');
    return serializeReference({ ...expression, default: defaultIfEmpty, transformerId, transformerParams: { ...expression.transformerParams, ...transformerParams } });
};

export const buildVariablePickerGroups = ({
    groups = [],
    pickerPage = null,
    noneOption = null,
    fieldsGroupKey = 'fieldsVariables',
    fallbackFieldsLabel = 'Fields',
}) => {
    const groupedByPage = [];

    groups.forEach((group) => {
        if (group?.value !== fieldsGroupKey || !Array.isArray(group.items)) {
            groupedByPage.push(group);
            return;
        }

        const pageBuckets = new Map();
        group.items.forEach((item) => {
            const pageLabel = String(item?.pageLabel || '').trim() || fallbackFieldsLabel;
            if (!pageBuckets.has(pageLabel)) {
                pageBuckets.set(pageLabel, []);
            }
            const bucket = pageBuckets.get(pageLabel);
            if (bucket) {
                bucket.push(item);
            }
        });

        pageBuckets.forEach((items, pageLabel) => {
            groupedByPage.push({
                label: pageLabel,
                value: `${fieldsGroupKey}:${pageLabel}`,
                items,
            });
        });
    });

    if (pickerPage) {
        return groupedByPage;
    }

    if (!noneOption) {
        return groupedByPage;
    }

    return [{
        label: '',
        value: 'none',
        items: [noneOption],
    }, ...groupedByPage];
};

export const buildTransformOptions = (selectedVariableOption, registry = {}) => {
    if (!selectedVariableOption || selectedVariableOption.allowTransforms === false) {
        return [];
    }

    const optionTypes = Array.isArray(selectedVariableOption?.types) ? selectedVariableOption.types : [];
    const allowedTypes = new Set();

    optionTypes.forEach((type) => {
        if (typeof type === 'string' && type.trim() !== '') {
            allowedTypes.add(type);
        }
    });
    const hasHints = optionTypes.length > 0;
    const byId = new Map();

    Object.entries(registry || {}).forEach(([valueType, transformers]) => {
        if (hasHints && allowedTypes.size > 0 && !allowedTypes.has(valueType)) {
            return;
        }

        (Array.isArray(transformers) ? transformers : []).forEach((transformer) => {
            if (Array.isArray(selectedVariableOption.transforms) && !selectedVariableOption.transforms.includes(transformer.id)) {
                return;
            }
            if (transformer.availability?.server === false) {
                return;
            }
            const appliesTo = Array.isArray(transformer.appliesTo) && transformer.appliesTo.length
                ? transformer.appliesTo
                : [valueType];

            if (hasHints && allowedTypes.size > 0 && !appliesTo.some((type) => { return allowedTypes.has(type); })) {
                return;
            }

            if (!byId.has(transformer.id)) {
                byId.set(transformer.id, {
                    value: transformer.id,
                    label: transformer.label,
                    description: transformer.description,
                    params: Array.isArray(transformer.params) ? transformer.params : [],
                    appliesTo,
                });
            }
        });
    });

    return Array.from(byId.values());
};
