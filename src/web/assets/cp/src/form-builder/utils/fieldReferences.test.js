import { describe, expect, it, vi } from 'vitest';

import { assignFieldReferences } from './fieldReferences';

describe('assignFieldReferences', () => {
    it('regenerates references in layout variants when duplicating fixed parent fields', () => {
        const sharedDateReference = 'a16c2d4b-33ad-436c-90a8-4a4b1dd95401';
        const sharedTimeReference = '277a9abd-6b91-4add-92d6-92f401b31d15';

        const sourceField = {
            type: 'verbb\\formie\\fields\\Date',
            reference: 'parent-reference',
            handle: 'dateField',
            rows: [{
                fields: [{
                    type: 'verbb\\formie\\fields\\subfields\\DateDate',
                    handle: 'date',
                    reference: sharedDateReference,
                }, {
                    type: 'verbb\\formie\\fields\\subfields\\DateTime',
                    handle: 'time',
                    reference: sharedTimeReference,
                }],
            }],
            settings: {
                displayType: 'calendar',
                layouts: {
                    calendar: [{
                        fields: [{
                            type: 'verbb\\formie\\fields\\subfields\\DateDate',
                            handle: 'date',
                            reference: sharedDateReference,
                        }, {
                            type: 'verbb\\formie\\fields\\subfields\\DateTime',
                            handle: 'time',
                            reference: sharedTimeReference,
                        }],
                    }],
                    dropdowns: [{
                        fields: [{
                            type: 'verbb\\formie\\fields\\subfields\\DateYearDropdown',
                            handle: 'year',
                            reference: 'year-reference',
                        }],
                    }],
                },
            },
        };

        vi.spyOn(crypto, 'randomUUID')
            .mockReturnValueOnce('new-parent-reference')
            .mockReturnValueOnce('new-date-reference')
            .mockReturnValueOnce('new-time-reference')
            .mockReturnValueOnce('new-layout-date-reference')
            .mockReturnValueOnce('new-layout-time-reference')
            .mockReturnValueOnce('new-year-reference');

        const duplicatedField = assignFieldReferences(structuredClone(sourceField), { forceNew: true });

        expect(duplicatedField.reference).toBe('new-parent-reference');
        expect(duplicatedField.rows[0].fields[0].reference).toBe('new-date-reference');
        expect(duplicatedField.rows[0].fields[1].reference).toBe('new-time-reference');
        expect(duplicatedField.settings.layouts.calendar[0].fields[0].reference).toBe('new-layout-date-reference');
        expect(duplicatedField.settings.layouts.calendar[0].fields[1].reference).toBe('new-layout-time-reference');
        expect(duplicatedField.settings.layouts.dropdowns[0].fields[0].reference).toBe('new-year-reference');

        const allReferences = new Set([
            duplicatedField.reference,
            duplicatedField.rows[0].fields[0].reference,
            duplicatedField.rows[0].fields[1].reference,
            duplicatedField.settings.layouts.calendar[0].fields[0].reference,
            duplicatedField.settings.layouts.calendar[0].fields[1].reference,
            duplicatedField.settings.layouts.dropdowns[0].fields[0].reference,
        ]);

        expect(allReferences.size).toBe(6);
        expect(allReferences.has(sharedDateReference)).toBe(false);
        expect(allReferences.has(sharedTimeReference)).toBe(false);
    });
});
