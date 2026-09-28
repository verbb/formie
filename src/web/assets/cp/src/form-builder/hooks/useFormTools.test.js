import { beforeAll, describe, expect, it } from 'vitest';

import { getFieldReferenceOptions } from './useFormTools';

beforeAll(() => {
    globalThis.Craft = {
        t: (_category, message) => { return message; },
    };
});

describe('field reference options', () => {
    it('uses canonical semantic metadata and explicit scopes for Table columns', () => {
        const values = {
            pages: [{
                rows: [{
                    fields: [{
                        _id: 'table-field',
                        type: 'TableField',
                        reference: 'table-reference',
                        handle: 'attendees',
                        label: 'Attendees',
                        columns: {
                            col1: { heading: 'Email', type: 'email' },
                            col2: { heading: 'Seats', type: 'number' },
                        },
                    }],
                }],
            }],
        };
        const getFieldTypeByType = () => ({
            isTableField: true,
            referenceValues: [],
        });

        const options = getFieldReferenceOptions(values, {
            getFieldTypeByType,
            target: 'variablePicker',
            variableTypes: ['email'],
        });

        expect(options).toHaveLength(1);
        expect(options[0]).toMatchObject({
            label: 'Attendees: Email',
            value: '{field:table-reference:col1;scope=first}',
            shape: 'inline',
            types: ['email', 'text'],
        });
    });
});
