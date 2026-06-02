import type { ConditionSource } from '#modules/fields/conditions/types';

function stringifyValue(value: unknown): string {
    if (value == null) {
        return '';
    }

    return String(value);
}

function toBoolean(value: unknown): boolean {
    if (typeof value === 'boolean') {
        return value;
    }

    if (typeof value === 'number') {
        return value !== 0;
    }

    const normalized = stringifyValue(value).trim().toLowerCase();

    if (!normalized || ['0', 'false', 'no', 'off'].includes(normalized)) {
        return false;
    }

    return true;
}

function toTitleCase(value: string): string {
    return value.toLowerCase().replace(/\b\w/g, (match) => {
        return match.toUpperCase();
    });
}

function formatNumber(value: number, params: Record<string, string>): string {
    const decimals = Number.isFinite(Number(params.decimals)) ? Number(params.decimals) : 0;
    const decimalPoint = params.decimalPoint ?? '.';
    const thousandsSeparator = params.thousandsSeparator ?? ',';
    const fixed = value.toFixed(decimals);
    const [integerPart, decimalPart = ''] = fixed.split('.');
    const groupedInteger = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, thousandsSeparator);

    if (decimals === 0) {
        return groupedInteger;
    }

    return `${groupedInteger}${decimalPoint}${decimalPart}`;
}

function pad(value: number): string {
    return String(value).padStart(2, '0');
}

function formatDate(value: Date, pattern: string): string {
    const replacements: Array<[string, string]> = [
        ['Y', String(value.getFullYear())],
        ['m', pad(value.getMonth() + 1)],
        ['d', pad(value.getDate())],
        ['j', String(value.getDate())],
        ['H', pad(value.getHours())],
        ['h', pad(((value.getHours() + 11) % 12) + 1)],
        ['i', pad(value.getMinutes())],
        ['A', value.getHours() >= 12 ? 'PM' : 'AM'],
        ['F', value.toLocaleString(undefined, { month: 'long' })],
    ];

    return replacements.reduce((formatted, [token, replacement]) => {
        return formatted.replaceAll(token, replacement);
    }, pattern);
}

function resolveDateFormatPattern(preset: string): string {
    switch (preset) {
        case 'datetimeUs12':
            return 'm/d/Y h:i A';
        case 'datetimeEu12':
            return 'd/m/Y h:i A';
        case 'datetimeEu24':
            return 'd/m/Y H:i';
        case 'datetimeIso24':
            return 'Y-m-d H:i';
        case 'dateUs':
            return 'm/d/Y';
        case 'dateEu':
            return 'd/m/Y';
        case 'isoDate':
            return 'Y-m-d';
        case 'dateLong':
            return 'F j, Y';
        case 'time12':
            return 'h:i A';
        case 'time24':
            return 'H:i';
        default:
            return '';
    }
}

function applyTransformer(value: string, source: ConditionSource): string {
    const transformerId = source.transformerId;
    const params = source.transformerParams;

    switch (transformerId) {
        case 'round':
        case 'floor':
        case 'ceil': {
            const number = Number(value);

            if (!Number.isFinite(number)) {
                return value;
            }

            if (transformerId === 'round') {
                return String(Math.round(number));
            }

            if (transformerId === 'floor') {
                return String(Math.floor(number));
            }

            return String(Math.ceil(number));
        }

        case 'format': {
            const number = Number(value);

            if (Number.isFinite(number) && value.trim() !== '') {
                return formatNumber(number, params);
            }

            const preset = params.preset || '';
            const pattern = preset === 'custom' ? (params.pattern || '') : resolveDateFormatPattern(preset);

            if (!pattern) {
                return value;
            }

            const date = new Date(value);

            if (Number.isNaN(date.getTime())) {
                return value;
            }

            return formatDate(date, pattern);
        }

        case 'lower':
            return value.toLowerCase();

        case 'upper':
            return value.toUpperCase();

        case 'title':
            return toTitleCase(value);

        case 'capitalize':
            return value ? value.charAt(0).toUpperCase() + value.slice(1) : value;

        case 'replace': {
            const search = params.search || '';

            if (!search) {
                return value;
            }

            return value.split(search).join(params.replace || '');
        }

        case 'truncate': {
            const length = Math.max(1, Number.parseInt(params.length || '50', 10) || 50);
            const suffix = params.suffix || '...';

            if (value.length <= length) {
                return value;
            }

            return `${value.slice(0, Math.max(0, length - suffix.length))}${suffix}`;
        }

        case 'map':
            return toBoolean(value) ? (params.trueLabel || 'Yes') : (params.falseLabel || 'No');

        default:
            return value;
    }
}

export function applyConditionSource(values: string[], source: ConditionSource | null): string[] {
    if (!source) {
        return values;
    }

    const transformedValues = source.transformerId
        ? values.map((value) => {
            return applyTransformer(value, source);
        })
        : values;

    if ((transformedValues.length === 0 || transformedValues.every((value) => {
        return value.trim() === '';
    })) && source.defaultValue) {
        return [source.defaultValue];
    }

    return transformedValues;
}
