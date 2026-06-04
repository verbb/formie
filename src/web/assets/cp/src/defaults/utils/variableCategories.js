const CONTENT_ANY = 'any';
const CONTENT_SINGLE_LINE = 'singleLine';

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
        return aliasTargets.flatMap((item) => {
            return expandGroupKeys(item, aliases, staticGroups);
        });
    }

    if (staticGroups[groupKey]) {
        return [groupKey];
    }

    return [];
};

const resolveGroupSections = (variableConfig, config) => {
    const aliases = config?.groupAliases || {};
    const staticGroups = config?.staticGroups || {};
    const groups = Array.isArray(variableConfig?.groups) ? variableConfig.groups : [];

    if (!groups.length) {
        return [];
    }

    return groups.flatMap((groupKey) => {
        const expanded = Array.from(new Set(expandGroupKeys(groupKey, aliases, staticGroups)));

        if (!expanded.length) {
            return [];
        }

        return [{
            key: groupKey,
            label: groupKey,
            groups: expanded,
        }];
    });
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

        return itemTypes.some((type) => {
            return requestedTypes.includes(type);
        });
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

export const resolveStaticVariableCategories = (config, variableConfig) => {
    const request = normalizeVariableConfig(variableConfig);

    if (!request) {
        return {};
    }

    const staticGroups = config?.staticGroups || {};
    const sections = resolveGroupSections(request, config);
    const grouped = {};

    sections.forEach((section) => {
        const sectionItems = [];

        section.groups.forEach((groupKey) => {
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
};
