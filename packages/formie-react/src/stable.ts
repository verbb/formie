function serializeStableValue(value: unknown, seen: WeakSet<object>): string {
    if (value == null) {
        return String(value);
    }

    if (typeof value === 'string') {
        return JSON.stringify(value);
    }

    if (typeof value === 'number' || typeof value === 'boolean') {
        return String(value);
    }

    if (typeof value === 'function') {
        return '[function]';
    }

    if (typeof File !== 'undefined' && value instanceof File) {
        return `[file:${value.name}:${value.size}:${value.type}]`;
    }

    if (typeof Blob !== 'undefined' && value instanceof Blob) {
        return `[blob:${value.size}:${value.type}]`;
    }

    if (Array.isArray(value)) {
        return `[${value.map((item) => serializeStableValue(item, seen)).join(',')}]`;
    }

    if (typeof value === 'object') {
        if (seen.has(value as object)) {
            return '[circular]';
        }

        seen.add(value as object);
        const entries = Object.entries(value as Record<string, unknown>)
            .sort(([left], [right]) => left.localeCompare(right))
            .map(([key, item]) => {
                return `${JSON.stringify(key)}:${serializeStableValue(item, seen)}`;
            });
        seen.delete(value as object);

        return `{${entries.join(',')}}`;
    }

    return JSON.stringify(String(value));
}

export function stableSerialize(value: unknown): string {
    return serializeStableValue(value, new WeakSet<object>());
}
