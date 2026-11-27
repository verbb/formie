import type { ValidationContext, ValidationInput, ValidationRule, ValidationRuleDefinition } from '#validation/types';

type DatePartsField = {
    field: HTMLElement;
    rule: ValidationRule;
};

type DateLimit = 'dateMin' | 'dateMax';

const DATE_PARTS = ['year', 'month', 'day'];

function parseDatePartsRule(field: Element): ValidationRule | null {
    try {
        const rules: unknown = JSON.parse(field.getAttribute('data-formie-validation') || '[]');

        if (!Array.isArray(rules)) {
            return null;
        }

        return rules.find((rule) => rule && typeof rule === 'object' && rule.type === 'dateParts') ?? null;
    } catch {
        return null;
    }
}

/**
 * Date fields put the rule on their own wrapper, while each part (year, month, day and time) is a subfield with its own
 * input. Returns the Date field an input belongs to when that field has date limits.
 */
export function getDatePartsField(input: Element | null): DatePartsField | null {
    const partField = input?.closest('[data-formie-field-handle]');
    const field = partField?.parentElement?.closest<HTMLElement>('[data-formie-field-handle]');

    if (!field) {
        return null;
    }

    const rule = parseDatePartsRule(field);

    return rule ? { field, rule } : null;
}

function getPartInput(field: HTMLElement, part: string): ValidationInput | null {
    return field.querySelector<ValidationInput>(`input[name$="[${part}]"]:not([disabled]), select[name$="[${part}]"]:not([disabled])`);
}

function getDefaultValue(input: ValidationInput): string {
    if (input instanceof HTMLSelectElement) {
        const options = Array.from(input.options);

        return (options.find((option) => option.defaultSelected) ?? options[0])?.value ?? '';
    }

    return input.defaultValue;
}

// Report once per field, on its last date part, rather than under every part.
function getAnchorInput(field: HTMLElement): ValidationInput | null {
    const inputs = DATE_PARTS.map((part) => getPartInput(field, part)).filter((input): input is ValidationInput => !!input);

    return inputs.sort((a, b) => (a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1)).pop() ?? null;
}

function partNumber(field: HTMLElement, part: string): number | null {
    const value = getPartInput(field, part)?.value.trim() ?? '';

    return value === '' ? null : Number.parseInt(value, 10);
}

function getDateTime(field: HTMLElement): Date | null {
    const [year, month, day] = DATE_PARTS.map((part) => partNumber(field, part));

    if (year === null || month === null || day === null) {
        return null;
    }

    let hour = partNumber(field, 'hour') ?? 0;
    const ampm = getPartInput(field, 'ampm')?.value.toUpperCase();

    if (ampm === 'AM' && hour === 12) {
        hour = 0;
    } else if (ampm === 'PM' && hour < 12) {
        hour += 12;
    }

    const date = new Date(year, month - 1, day, hour, partNumber(field, 'minute') ?? 0, partNumber(field, 'second') ?? 0);

    // An impossible date such as 31 February is left to the server, which reports it.
    if (date.getFullYear() !== year || date.getMonth() !== month - 1 || date.getDate() !== day) {
        return null;
    }

    return date;
}

/**
 * The server doesn't check a date a saved submission already has, as limits such as today's date move, so a front-end
 * edit that leaves it alone isn't checked here either.
 */
function isUnchangedSavedDate(field: HTMLElement, form: HTMLFormElement): boolean {
    const submissionId = form.querySelector<HTMLInputElement>('input[name="submissionId"]');

    if (!submissionId?.value) {
        return false;
    }

    return Array.from(field.querySelectorAll<ValidationInput>('input[name], select[name]')).every((input) => input.value === getDefaultValue(input));
}

function getFailedLimit({ field, rule }: DatePartsField, form: HTMLFormElement): DateLimit | null {
    const dateTime = getDateTime(field);

    if (!dateTime || isUnchangedSavedDate(field, form)) {
        return null;
    }

    // Limits are the site's wall-clock values, so they're read in the browser's time zone like the entered parts.
    const minDate = typeof rule.minDate === 'string' ? new Date(rule.minDate) : null;
    const maxDate = typeof rule.maxDate === 'string' ? new Date(rule.maxDate) : null;

    if (minDate && Number.isFinite(minDate.getTime()) && dateTime < minDate) {
        return 'dateMin';
    }

    if (maxDate && Number.isFinite(maxDate.getTime()) && dateTime > maxDate) {
        return 'dateMax';
    }

    return null;
}

function getAnchoredDateField(ctx: ValidationContext): DatePartsField | null {
    const date = getDatePartsField(ctx.input);

    return date && getAnchorInput(date.field) === ctx.input ? date : null;
}

const dateParts: ValidationRuleDefinition = {
    rule: (ctx) => {
        const date = getAnchoredDateField(ctx);

        return !date || getFailedLimit(date, ctx.form) === null;
    },
    message: (ctx) => {
        const date = getAnchoredDateField(ctx);
        const limit = date ? getFailedLimit(date, ctx.form) : null;
        const messages = (date?.rule.messages ?? {}) as Record<string, string>;
        const boundary = limit === 'dateMax' ? date?.rule.maxDate : date?.rule.minDate;

        if (limit && messages[limit]) {
            return messages[limit];
        }

        return ctx.t(limit === 'dateMax' ? 'The date must be on or before {date}.' : 'The date must be on or after {date}.', {
            date: typeof boundary === 'string' ? new Date(boundary).toLocaleDateString() : '',
        });
    },
};

export default dateParts;
