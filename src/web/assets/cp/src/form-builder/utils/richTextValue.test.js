import { describe, expect, it } from 'vitest';
import { FormStateStore } from '@verbb/plugin-kit-forms';

import { normalizeFieldEditorValues } from './richTextValue';

describe('field editor validation messages', () => {
    it('preserves new message overrides through the form store and JSON transport', () => {
        const field = { label: 'Contact email', validationMessages: [] };
        const store = new FormStateStore(normalizeFieldEditorValues(field));

        store.setValue('validationMessages.required', 'Your email is required.');
        store.setValue('validationMessages.email', 'Please enter a valid email.');

        expect(JSON.parse(JSON.stringify(store.state.values)).validationMessages).toEqual({
            required: 'Your email is required.',
            email: 'Please enter a valid email.',
        });
        expect(field.validationMessages).toEqual([]);
    });

    it('edits existing overrides without changing the field until Apply', () => {
        const field = { validationMessages: { required: 'Original message', email: 'Existing email message' } };
        const store = new FormStateStore(normalizeFieldEditorValues(field));

        store.setValue('validationMessages.required', '');

        expect(JSON.parse(JSON.stringify(store.state.values)).validationMessages).toEqual({
            required: '',
            email: 'Existing email message',
        });
        expect(field.validationMessages.required).toBe('Original message');
    });
});
