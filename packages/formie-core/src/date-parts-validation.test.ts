import { describe, expect, it } from 'vitest';
import { isValidCalendarDate, validateCompositeDateParts } from './date-parts-validation';
import type { FrontendFieldDefinition } from './types';

function createDateField(overrides: Partial<FrontendFieldDefinition> = {}): FrontendFieldDefinition {
    return {
        id: 'date-1',
        key: 'field-eventDate',
        handle: 'eventDate',
        label: 'Event Date',
        type: 'date',
        required: false,
        validation: [{ type: 'dateParts' }],
        input: {
            dateEnabled: true,
            timeEnabled: true,
            parts: [
                {
                    id: 'year',
                    key: 'year',
                    handle: 'year',
                    type: 'number',
                    required: false,
                    validation: [{ type: 'number', min: 1924, max: 2124 }],
                    input: { inputType: 'number', min: 1924, max: 2124 },
                    meta: null,
                },
                {
                    id: 'month',
                    key: 'month',
                    handle: 'month',
                    type: 'number',
                    required: false,
                    validation: [{ type: 'number', min: 1, max: 12 }],
                    input: { inputType: 'number', min: 1, max: 12 },
                    meta: null,
                },
                {
                    id: 'day',
                    key: 'day',
                    handle: 'day',
                    type: 'number',
                    required: false,
                    validation: [{ type: 'number', min: 1, max: 31 }],
                    input: { inputType: 'number', min: 1, max: 31 },
                    meta: null,
                },
            ],
        },
        meta: null,
        ...overrides,
    };
}

describe('date-parts-validation', () => {
    it('detects impossible calendar dates', () => {
        expect(isValidCalendarDate(2024, 2, 29)).toBe(true);
        expect(isValidCalendarDate(2024, 2, 31)).toBe(false);
    });

    it('adds a day-level error for impossible calendar dates', () => {
        const output: Record<string, string[]> = {};

        validateCompositeDateParts(createDateField(), {
            year: '2024',
            month: '2',
            day: '31',
        }, 'date-1', output);

        expect(output['date-1.day']).toEqual(['Day is invalid.']);
    });

    it('adds a field-level error when the date is before the minimum', () => {
        const output: Record<string, string[]> = {};

        validateCompositeDateParts(createDateField({
            validation: [{
                type: 'dateParts',
                minDate: '2020-01-01T00:00:00',
            }],
        }), {
            year: '2019',
            month: '12',
            day: '31',
            hour: '0',
            minute: '0',
        }, 'date-1', output);

        expect(output['date-1']?.[0]).toMatch(/on or after/i);
    });
});
