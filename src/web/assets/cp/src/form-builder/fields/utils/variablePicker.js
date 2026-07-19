import { parseTokenWithDefault } from '@verbb/plugin-kit-tiptap-core';
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

    const [tokenWithoutDefault] = parseTokenWithDefault(tokenValue);
    return tokenWithoutDefault || tokenValue;
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

            if (variableValuesMatchReference(comparableToken, value)) {
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
            if (variableValuesMatchReference(comparableToken, value)) {
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
            if (!variableValuesMatchReference(comparableToken, value)) {
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

            if (value && !optionByValue.has(value)) {
                optionByValue.set(value, item);
                labelByValue.set(value, label);
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

    return {
        labelByValue,
        optionByValue,
    };
};

export const parseVariableTokenMetadata = (tokenValue = '') => {
    const raw = String(tokenValue || '');
    const match = raw.match(/^\{([^}]*)\}$/);

    if (!match) {
        return {
            tokenWithoutDefault: raw,
            defaultIfEmpty: '',
            transformerId: '',
            transformerParams: {},
        };
    }

    let body = match[1] ?? '';
    let defaultIfEmpty = '';

    if (body.includes('|')) {
        const split = body.split('|');
        body = split.shift() ?? '';
        defaultIfEmpty = split.join('|').trim();
    }

    const segments = body.split(';').map((part) => { return part.trim(); }).filter(Boolean);
    const cleanSegments = [];
    let transformerId = '';
    const transformerParams = {};
    const referenceParams = {};

    segments.forEach((segment) => {
        if (segment.startsWith('transform=')) {
            transformerId = decodeURIComponent(segment.slice('transform='.length)).trim();
            return;
        }

        if (segment.includes('=')) {
            const [keyRaw, ...valueParts] = segment.split('=');
            const key = String(keyRaw || '').trim().toLowerCase();
            if (!key) {
                return;
            }

            const value = decodeURIComponent(valueParts.join('=').trim());

            if (REFERENCE_METADATA_KEYS.has(key)) {
                referenceParams[key] = value;
                cleanSegments.push(`${key}=${encodeURIComponent(value)}`);
                return;
            }

            transformerParams[key] = value;
            return;
        }

        cleanSegments.push(segment);
    });

    return {
        tokenWithoutDefault: `{${cleanSegments.join(';')}}`,
        defaultIfEmpty,
        transformerId,
        transformerParams,
        referenceParams,
    };
};

export const serializeVariableTokenMetadata = (baseToken, {
    defaultIfEmpty = '',
    transformerId = '',
    transformerParams = {},
} = {}) => {
    const match = String(baseToken || '').match(/^\{([^}]*)\}$/);
    if (!match) {
        return String(baseToken || '');
    }

    const parts = [match[1]];
    const cleanedTransformerId = String(transformerId || '').trim();

    Object.entries(transformerParams || {}).forEach(([key, value]) => {
        const cleanedKey = String(key || '').trim();
        if (!cleanedKey || cleanedKey === 'transform' || !REFERENCE_METADATA_KEYS.has(cleanedKey)) {
            return;
        }

        if (parts.some((part) => {
            return part.startsWith(`${cleanedKey}=`);
        })) {
            return;
        }

        parts.push(`${cleanedKey}=${encodeURIComponent(String(value ?? ''))}`);
    });

    if (cleanedTransformerId) {
        parts.push(`transform=${encodeURIComponent(cleanedTransformerId)}`);

        Object.entries(transformerParams || {}).forEach(([key, value]) => {
            const cleanedKey = String(key || '').trim();
            if (!cleanedKey || cleanedKey === 'transform' || REFERENCE_METADATA_KEYS.has(cleanedKey)) {
                return;
            }

            parts.push(`${cleanedKey}=${encodeURIComponent(String(value ?? ''))}`);
        });
    }

    const body = parts.filter(Boolean).join(';');
    const cleanedDefault = String(defaultIfEmpty || '').trim();

    return cleanedDefault ? `{${body}|${cleanedDefault}}` : `{${body}}`;
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
    if (!selectedVariableOption) {
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
