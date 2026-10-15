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

describe('integration actions', () => {
    it('refreshes metadata and sends test payloads through distinct endpoints', async() => {
        const calls = [];
        Craft.sendActionRequest = async(method, action, options) => {
            calls.push({ method, action, data: options.data });
            return { data: action.endsWith('send-test-payload') ? { success: true, status: 'succeeded' } : { fields: [] } };
        };
        const { refreshIntegrationFormSettings, sendIntegrationTestPayload } = await import('./useFormTools');
        expect(await refreshIntegrationFormSettings('webhook', { enabled: true }, { formId: 42 })).toEqual({ ok: true, data: { fields: [] } });
        expect(await sendIntegrationTestPayload('webhook', { enabled: true }, { formId: 42 })).toEqual({ ok: true, data: { success: true, status: 'succeeded' } });
        expect(calls).toEqual([
            { method: 'POST', action: 'formie/integrations/form-settings', data: { integration: 'webhook', formId: 42, settings: { enabled: true } } },
            { method: 'POST', action: 'formie/integrations/send-test-payload', data: { integration: 'webhook', formId: 42, settings: { enabled: true } } },
        ]);
    });

    it('does not show successful delivery for a rejected test send', async() => {
        Craft.sendActionRequest = async() => { return { data: { success: false, status: 'rejected', message: 'The test payload was not delivered.' } }; };
        const { sendIntegrationTestPayload } = await import('./useFormTools');
        expect(await sendIntegrationTestPayload('webhook', {}, { formId: 42 })).toMatchObject({ ok: false, error: 'The test payload was not delivered.' });
        Craft.sendActionRequest = async() => { return { data: { success: false } }; };
        expect(await sendIntegrationTestPayload('webhook', {}, { formId: 42 })).toMatchObject({ ok: false, error: 'Failed to send test payload.' });
    });
});
