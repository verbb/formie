/** Format a category key for display, preferring server-provided labels. */
export function formatVariableCategoryLabel(key, labels) {
    return labels?.[key] ?? key;
}

export function getVariableCategoryEntries(
    variableCategories,
    variableCategoryLabels,
    variableCategoryOrder,
) {
    const entries = Object.entries(variableCategories ?? {})
        .map(([key, options]) => {
            return {
                key,
                label: formatVariableCategoryLabel(key, variableCategoryLabels),
                options: Array.isArray(options) ? options : [],
            };
        })
        .filter((entry) => {
            return entry.options.length > 0;
        });

    const order = variableCategoryOrder ?? Object.keys(variableCategories ?? {});

    entries.sort((a, b) => {
        const aOrderIndex = order.indexOf(a.key);
        const bOrderIndex = order.indexOf(b.key);
        const aOrder = aOrderIndex === -1 ? Number.MAX_SAFE_INTEGER : aOrderIndex;
        const bOrder = bOrderIndex === -1 ? Number.MAX_SAFE_INTEGER : bOrderIndex;

        if (aOrder !== bOrder) {
            return aOrder - bOrder;
        }

        return a.label.localeCompare(b.label);
    });

    return entries;
}

export function toTopLevelGroups(entries, t) {
    const groups = [];

    entries.forEach((entry) => {
        if (entry.key === 'fieldsVariables') {
            const pageBuckets = new Map();
            let hasPageBuckets = false;

            entry.options.forEach((item) => {
                const pageLabel = String(item?.pageLabel || '').trim();
                if (!pageLabel) {
                    return;
                }

                hasPageBuckets = true;
                if (!pageBuckets.has(pageLabel)) {
                    pageBuckets.set(pageLabel, []);
                }
                pageBuckets.get(pageLabel).push(item);
            });

            if (hasPageBuckets) {
                pageBuckets.forEach((items, pageLabel) => {
                    groups.push({
                        label: t(pageLabel),
                        value: `${entry.key}:${pageLabel}`,
                        items,
                    });
                });

                return;
            }
        }

        groups.push({
            label: t(entry.label),
            value: entry.key,
            items: entry.options,
        });
    });

    return groups;
}

export function matchesVariableQuery(item, normalizedQuery) {
    if (!normalizedQuery) {
        return true;
    }

    return (
        (item.label ?? '').toLowerCase().includes(normalizedQuery)
        || String(item.value ?? '').toLowerCase().includes(normalizedQuery)
    );
}
