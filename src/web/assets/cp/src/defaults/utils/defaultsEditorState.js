export const setAtPath = (values, path, value) => {
    const keys = path.split('.');
    const next = { ...values };
    let cursor = next;

    keys.forEach((key, index) => {
        if (index === keys.length - 1) {
            cursor[key] = value;
            return;
        }

        cursor[key] = { ...(cursor[key] || {}) };
        cursor = cursor[key];
    });

    return next;
};

export const getAtPath = (values, path, fallback = '') => {
    return path.split('.').reduce((carry, key) => {
        return carry && Object.prototype.hasOwnProperty.call(carry, key) ? carry[key] : undefined;
    }, values) ?? fallback;
};

export const normalizeSelectFieldDefaults = (schema, values) => {
    if (!schema?.length || !values || typeof values !== 'object') {
        return values || {};
    }

    const normalized = { ...values };

    schema.forEach((node) => {
        if (node?.$field !== 'select' || !node?.name || !Array.isArray(node.options)) {
            return;
        }

        const currentValue = normalized[node.name];

        if (currentValue === undefined || currentValue === null || currentValue === '') {
            return;
        }

        const match = node.options.find((option) => {
            return String(option?.value) === String(currentValue);
        });

        if (match) {
            normalized[node.name] = match.value;
        }
    });

    return normalized;
};
