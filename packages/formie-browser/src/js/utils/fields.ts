function normalizeHandle(handle: string): string {
    // Handles can come from token-like references or nested names. Normalize them
    // once so DOM lookups keep using the field name syntax the form actually renders.
    return handle
        .replace('{field:', '')
        .replace('{', '')
        .replace('}', '')
        .replace(']', '')
        .split('[')
        .join('][');
}

export function getFieldName(handle: string): string {
    return `fields[${normalizeHandle(handle)}]`;
}

export function getFormFields(form: ParentNode, handle: string): HTMLElement[] {
    const fieldName = getFieldName(handle);
    const direct = Array.from(form.querySelectorAll(`[name="${fieldName}"]`));
    const multi = Array.from(form.querySelectorAll(`[name="${fieldName}[]"]`));

    return (multi.length ? multi : direct).filter((element): element is HTMLElement => {
        return element instanceof HTMLElement;
    });
}

export function getFieldLabel(form: ParentNode, handle: string): string {
    const fields = getFormFields(form, handle);

    for (const field of fields) {
        const fieldContainer = field.closest('[data-formie-field-handle]') as HTMLElement | null;
        const label = fieldContainer?.querySelector('[data-formie-field-label]')?.childNodes[0]?.textContent?.trim();

        if (label) {
            return label;
        }
    }

    return '';
}

export function currencyToFloat(currencyString: string): number {
    let sanitized = currencyString.replace(/[^\d.,-]/g, '');
    const hasComma = sanitized.includes(',');
    const hasDot = sanitized.includes('.');

    // Accept both "1,234.56" and "1.234,56" style inputs so calculations can
    // operate on locale-formatted values emitted by other field modules.
    if (hasComma && hasDot) {
        sanitized = sanitized.replace(/\./g, '').replace(/,/, '.');
    } else if (hasComma && !hasDot) {
        sanitized = sanitized.replace(/,/, '.');
    } else {
        sanitized = sanitized.replace(/,/g, '');
    }

    return parseFloat(sanitized);
}
