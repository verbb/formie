import { describe, expect, it } from 'vitest';

import { customFieldMarkup, readCustomFieldControls } from './schema.js';
import type { ClientFieldDefinition } from './types.js';

function customField(input: Record<string, unknown>): ClientFieldDefinition {
    return { id: 'c', uid: 'c', handle: 'website', type: 'custom', label: 'Website', required: false, validation: [], input: { fieldKind: 'custom', ...input } } as unknown as ClientFieldDefinition;
}

describe('custom link and map fields', () => {
    it('shows the server markup only for link and map fields', () => {
        expect(customFieldMarkup(customField({ inputType: 'link', html: '<div></div>' }))).toBe('<div></div>');
        expect(customFieldMarkup(customField({ inputType: 'map', html: '<div></div>' }))).toBe('<div></div>');
        expect(customFieldMarkup(customField({ inputType: 'text', html: '<div></div>' }))).toBeNull();
        expect(customFieldMarkup(customField({ inputType: 'link' }))).toBeNull();
        expect(customFieldMarkup(customField({ inputType: 'link', html: '<input data-formie-custom-field-input name="a"><input data-formie-custom-field-input>' }), 'label-1'))
            .toBe('<input data-formie-custom-field-input aria-labelledby="label-1" name="a"><input data-formie-custom-field-input aria-labelledby="label-1">');
    });

    it('reads the answer from the inputs named under the field', () => {
        const control = (name: string, value: string, type = 'text', checked = false) => ({ name, value, type, checked, disabled: false });
        const controls = [
            control('fields[website][type]', 'url', 'select-one'),
            control('fields[website][value]', 'https://example.test'),
            control('fields[website][target]', '_blank', 'checkbox', true),
            control('fields[website][download]', '1', 'checkbox'),
            control('fields[website][parts][city]', 'Melbourne', 'hidden'),
            control('fields[other][value]', 'ignored'),
        ];
        const container = { querySelectorAll: () => controls } as unknown as Element;

        expect(readCustomFieldControls(container, customField({ htmlName: 'fields[website]' }))).toEqual({
            type: 'url',
            value: 'https://example.test',
            target: '_blank',
            parts: { city: 'Melbourne' },
        });
        expect(readCustomFieldControls(container, customField({}))).toEqual({});
    });
});
