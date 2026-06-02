function stripFieldPrefix(value: string): string {
    return value
        .replace(/^\{field:/, '')
        .replace(/^\{/, '')
        .replace(/\}$/, '')
        .trim();
}

export function normalizeFieldKey(value: string): string {
    return stripFieldPrefix(value)
        .replace(/\]/g, '')
        .split('[')
        .join('.')
        .replace(/\.+/g, '.')
        .replace(/^\./, '')
        .replace(/\.$/, '');
}

export function fieldKeyToInputName(key: string): string {
    const normalized = normalizeFieldKey(key);
    const parts = normalized.split('.').filter(Boolean);

    if (!parts.length) {
        return '';
    }

    const [head, ...rest] = parts;

    return `fields[${head}]${rest.map((part) => `[${part}]`).join('')}`;
}

export function inputNameToFieldKey(name: string): string {
    const trimmedName = String(name || '').trim();
    const match = trimmedName.match(/^fields\[([^\]]+)\](.*)$/);

    if (!match) {
        return '';
    }

    const first = match[1] || '';
    const tail = match[2] || '';
    const suffix = Array.from(tail.matchAll(/\[([^\]]+)\]/g)).map((part) => {
        return part[1] || '';
    }).filter(Boolean);

    return [first, ...suffix].join('.');
}
