import type { FrontendFieldDefinition } from './types';
import { compositePartDefinitions } from './schema';

function partValue(value: unknown): string {
    if (value == null) {
        return '';
    }

    return String(value).trim();
}

function enabledDatePartHandles(field: FrontendFieldDefinition): string[] {
    return compositePartDefinitions(field)
        .filter((part) => part.meta?.hidden !== true)
        .map((part) => part.handle)
        .filter((handle) => ['year', 'month', 'day'].includes(handle));
}

function hasCompleteDateParts(values: Record<string, unknown>, field: FrontendFieldDefinition): boolean {
    const handles = enabledDatePartHandles(field);

    if (handles.length === 0) {
        return false;
    }

    return handles.every((handle) => partValue(values[handle]) !== '');
}

export function isValidCalendarDate(year: number, month: number, day: number): boolean {
    if (!Number.isInteger(year) || !Number.isInteger(month) || !Number.isInteger(day)) {
        return false;
    }

    const date = new Date(year, month - 1, day);

    return date.getFullYear() === year
        && date.getMonth() === month - 1
        && date.getDate() === day;
}

function buildDateTime(values: Record<string, unknown>): Date {
    const year = Number.parseInt(partValue(values.year), 10);
    const month = Number.parseInt(partValue(values.month), 10);
    const day = Number.parseInt(partValue(values.day), 10);
    const hour = partValue(values.hour) !== '' ? Number.parseInt(partValue(values.hour), 10) : 0;
    const minute = partValue(values.minute) !== '' ? Number.parseInt(partValue(values.minute), 10) : 0;
    const second = partValue(values.second) !== '' ? Number.parseInt(partValue(values.second), 10) : 0;

    return new Date(year, month - 1, day, hour, minute, second);
}

export function validateCompositeDateParts(
    field: FrontendFieldDefinition,
    value: unknown,
    errorKey: string,
    output: Record<string, string[]>,
): void {
    const datePartsRule = field.validation.find((rule) => rule.type === 'dateParts');

    if (!datePartsRule) {
        return;
    }

    if (field.input.dateEnabled === false) {
        return;
    }

    const currentValue = value && typeof value === 'object' ? value as Record<string, unknown> : {};

    if (!hasCompleteDateParts(currentValue, field)) {
        return;
    }

    const year = Number.parseInt(partValue(currentValue.year), 10);
    const month = Number.parseInt(partValue(currentValue.month), 10);
    const day = Number.parseInt(partValue(currentValue.day), 10);

    if (!isValidCalendarDate(year, month, day)) {
        const dayErrorKey = `${errorKey}.day`;

        if (!output[dayErrorKey]) {
            output[dayErrorKey] = ['Day is invalid.'];
        }

        return;
    }

    const dateTime = buildDateTime(currentValue);

    if (datePartsRule.minDate) {
        const minDate = new Date(datePartsRule.minDate);

        if (Number.isFinite(minDate.getTime()) && dateTime < minDate) {
            output[errorKey] = [`The date must be on or after ${minDate.toLocaleDateString()}.`];
            return;
        }
    }

    if (datePartsRule.maxDate) {
        const maxDate = new Date(datePartsRule.maxDate);

        if (Number.isFinite(maxDate.getTime()) && dateTime > maxDate) {
            output[errorKey] = [`The date must be on or before ${maxDate.toLocaleDateString()}.`];
        }
    }
}
