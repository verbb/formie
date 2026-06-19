export function createLikertKey(usedValues, prefix) {
    let value = '';

    do {
        value = `${prefix}-${Math.random().toString(36).slice(2, 12)}`;
    } while (usedValues.includes(value));

    usedValues.push(value);

    return value;
}

export function syncLikertKeys(items, prefix) {
    if (!Array.isArray(items)) {
        return items;
    }

    const usedValues = [];

    return items.map((item) => {
        if (!item || typeof item !== 'object' || item.optgroup) {
            return item;
        }

        const label = String(item.label ?? '').trim();

        if (label === '') {
            return item;
        }

        const existingValue = String(item.value ?? '').trim();

        if (existingValue !== '') {
            usedValues.push(existingValue);
            return item;
        }

        const { _id, ...rest } = item;

        return {
            ...rest,
            ...(_id ? { _id } : {}),
            value: createLikertKey(usedValues, prefix),
        };
    });
}
