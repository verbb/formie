import { describe, expect, it, vi } from 'vitest';
import { createFrontendFormInstance } from './form-instance';
import type { FrontendFieldDefinition, FrontendFormEnvelope, FrontendFormSession, FrontendTransport } from './types';

function createTextField(id: string, handle: string, overrides: Partial<FrontendFieldDefinition> = {}): FrontendFieldDefinition {
    return {
        id,
        key: id,
        handle,
        label: handle,
        type: 'single-line-text',
        required: false,
        validation: [],
        runtime: {
            structure: 'scalar',
        },
        input: {
            kind: 'text',
            inputType: 'text',
            defaultValue: '',
        },
        meta: null,
        ...overrides,
    };
}

function createEnvelope(fields: FrontendFieldDefinition[], session: Partial<FrontendFormSession> = {}): FrontendFormEnvelope {
    return {
        schemaVersion: 1,
        definition: {
            id: 'form-1',
            handle: 'contactForm',
            settings: {
                initialPageId: 'page-1',
                submitMethod: 'ajax',
                validation: {
                    onBlur: false,
                    onSubmit: true,
                },
            },
            pages: [
                {
                    id: 'page-1',
                    key: 'page-1',
                    label: 'Page 1',
                    rows: [{
                        fields,
                    }],
                    actions: {
                        primary: {
                            type: 'submit',
                            label: 'Submit',
                        },
                        secondary: [],
                    },
                },
                {
                    id: 'page-2',
                    key: 'page-2',
                    label: 'Page 2',
                    condition: {
                        mode: 'all',
                        effect: 'show',
                        rules: [{
                            fieldId: 'trigger',
                            operator: '=',
                            value: 'yes',
                        }],
                    },
                    rows: [{
                        fields: [],
                    }],
                    actions: {
                        primary: {
                            type: 'submit',
                            label: 'Submit',
                        },
                        secondary: [{
                            type: 'back',
                            label: 'Back',
                        }],
                    },
                },
            ],
            modules: [],
            submission: {
                endpoint: '/actions/formie/client/submissions/submit',
                method: 'POST',
                encoding: 'application/json',
                actions: ['back', 'save', 'submit'],
                response: {
                    successMessageMode: 'inline',
                    redirectMode: 'same-tab',
                },
            },
        },
        session: {
            id: 'session-1',
            currentPageId: 'page-1',
            tokens: {
                request: 'request-token',
                render: 'render-token',
            },
            ...session,
        },
    };
}

function createTransport(overrides: Partial<FrontendTransport> = {}): FrontendTransport {
    return {
        submit: async({ session }) => ({
            success: true,
            isFinalPage: false,
            currentPageId: 'page-2',
            nextPageId: 'page-2',
            errors: {
                form: [],
                fields: {},
                pages: {},
            },
            messages: {},
            session: {
                ...session,
                currentPageId: 'page-2',
            },
        }),
        refreshSession: async({ session }) => session,
        setPage: async({ session, targetPageId }) => ({
            ...session,
            currentPageId: targetPageId,
        }),
        ...overrides,
    };
}

describe('createFrontendFormInstance', () => {
    it('derives field and page visibility from conditions', () => {
        const runtime = createFrontendFormInstance({
            envelope: createEnvelope([
                createTextField('trigger', 'trigger'),
                createTextField('conditional', 'conditional', {
                    condition: {
                        mode: 'all',
                        effect: 'show',
                        rules: [{
                            fieldId: 'trigger',
                            operator: '=',
                            value: 'yes',
                        }],
                    },
                }),
            ], {
                currentPageId: 'page-2',
            }),
            transport: createTransport(),
        });

        expect(runtime.getState().fieldStates.conditional.hidden).toBe(true);
        expect(runtime.getState().pageStates['page-2'].hidden).toBe(true);
        expect(runtime.getState().currentPageId).toBe('page-1');

        runtime.setValue('trigger', 'yes');

        expect(runtime.getState().fieldStates.conditional.hidden).toBe(false);
        expect(runtime.getState().pageStates['page-2'].hidden).toBe(false);
    });

    it('clears newly hidden values when conditions hide a field', () => {
        const runtime = createFrontendFormInstance({
            envelope: createEnvelope([
                createTextField('trigger', 'trigger'),
                createTextField('conditional', 'conditional', {
                    condition: {
                        mode: 'all',
                        effect: 'show',
                        clearOnHide: true,
                        rules: [{
                            fieldId: 'trigger',
                            operator: '=',
                            value: 'yes',
                        }],
                    },
                }),
            ]),
            transport: createTransport(),
        });

        runtime.setValue('trigger', 'yes');
        runtime.setValue('conditional', 'keep me');
        expect(runtime.getState().values.conditional).toBe('keep me');

        runtime.setValue('trigger', 'no');

        expect(runtime.getState().fieldStates.conditional.hidden).toBe(true);
        expect(runtime.getState().values.conditional).toBe('');
    });

    it('supports notContains condition operators', () => {
        const runtime = createFrontendFormInstance({
            envelope: createEnvelope([
                createTextField('trigger', 'trigger'),
                createTextField('conditional', 'conditional', {
                    condition: {
                        mode: 'all',
                        effect: 'show',
                        rules: [{
                            fieldId: 'trigger',
                            operator: 'notContains',
                            value: 'skip',
                        }],
                    },
                }),
            ]),
            transport: createTransport(),
        });

        runtime.setValue('trigger', 'show this');
        expect(runtime.getState().fieldStates.conditional.hidden).toBe(false);

        runtime.setValue('trigger', 'please skip this');
        expect(runtime.getState().fieldStates.conditional.hidden).toBe(true);
    });

    it('evaluates repeater values in conditions', () => {
        const runtime = createFrontendFormInstance({
            envelope: createEnvelope([
                {
                    id: 'items',
                    key: 'items',
                    handle: 'items',
                    label: 'Items',
                    type: 'repeater',
                    required: false,
                    validation: [],
                    runtime: {
                        structure: 'repeatable-parent',
                    },
                    input: {
                        kind: 'repeater',
                        defaultValue: [],
                        rowSchema: {
                            id: 'items-row',
                            key: 'items-row',
                            rows: [{
                                fields: [
                                    createTextField('item-name', 'itemName'),
                                ],
                            }],
                        },
                    },
                    meta: null,
                },
                createTextField('conditional', 'conditional', {
                    condition: {
                        mode: 'all',
                        effect: 'show',
                        rules: [{
                            fieldId: 'items',
                            operator: 'contains',
                            value: 'Widget',
                        }],
                    },
                }),
            ]),
            transport: createTransport(),
        });

        expect(runtime.getState().fieldStates.conditional.hidden).toBe(true);

        runtime.setValue('items', [{
            itemName: 'Widget A',
        }]);

        expect(runtime.getState().fieldStates.conditional.hidden).toBe(false);
    });

    it('uses the page primary action when submit is called without an explicit action', async() => {
        const submit = vi.fn<FrontendTransport['submit']>(async({ session, action }) => ({
            success: true,
            isFinalPage: false,
            currentPageId: 'page-2',
            nextPageId: 'page-2',
            errors: {
                form: [],
                fields: {},
                pages: {},
            },
            messages: {},
            session: {
                ...session,
                currentPageId: 'page-2',
            },
        }));
        const envelope = createEnvelope([createTextField('trigger', 'trigger')]);
        envelope.definition.pages[0].actions.primary.type = 'next';
        const runtime = createFrontendFormInstance({
            envelope,
            transport: createTransport({
                submit,
            }),
        });

        runtime.setValue('trigger', 'yes');
        await runtime.submit();

        expect(submit).toHaveBeenCalledWith(expect.objectContaining({
            action: 'submit',
        }));
        expect(runtime.getState().currentPageId).toBe('page-2');
    });

    it('recovers cleanly from submit transport errors', async() => {
        const runtime = createFrontendFormInstance({
            envelope: createEnvelope([createTextField('trigger', 'trigger')]),
            transport: createTransport({
                submit: async() => {
                    throw new Error('Network down');
                },
            }),
        });

        const result = await runtime.submit('submit');

        expect(result.success).toBe(false);
        expect(result.messages.error).toBe('Network down');
        expect(runtime.getState().status).toBe('ready');
        expect(runtime.getState().lastSubmitResult?.messages.error).toBe('Network down');
    });

    it('validates match, number, url, and min/max checkbox rules', async() => {
        const runtime = createFrontendFormInstance({
            envelope: createEnvelope([
                createTextField('email', 'email'),
                createTextField('confirm', 'confirm', {
                    validation: [{
                        type: 'match',
                        fieldHandle: 'email',
                    }],
                }),
                createTextField('quantity', 'quantity', {
                    type: 'number',
                    validation: [{
                        type: 'number',
                        min: 2,
                        max: 4,
                    }],
                    input: {
                        kind: 'text',
                        inputType: 'number',
                        defaultValue: '',
                        min: 2,
                        max: 4,
                    },
                }),
                createTextField('website', 'website', {
                    validation: [{
                        type: 'url',
                    }],
                }),
                {
                    ...createTextField('topics', 'topics', {
                        type: 'checkboxes',
                        validation: [{
                            type: 'minmaxOptions',
                            min: 1,
                            max: 2,
                        }],
                        input: {
                            kind: 'checkbox-group',
                            options: [
                                { label: 'One', value: 'one' },
                                { label: 'Two', value: 'two' },
                                { label: 'Three', value: 'three' },
                            ],
                            min: 1,
                            max: 2,
                        },
                    }),
                },
            ]),
            transport: createTransport(),
        });

        runtime.patchValues({
            email: 'first@example.com',
            confirm: 'second@example.com',
            quantity: '5',
            website: 'not-a-url',
            topics: ['one', 'two', 'three'],
        });

        const result = await runtime.submit('submit');

        expect(result.success).toBe(false);
        expect(result.errors.fields.confirm).toEqual(['This value must match the related field.']);
        expect(result.errors.fields.quantity).toEqual(['Please enter a value less than or equal to 4.']);
        expect(result.errors.fields.website).toEqual(['Please enter a valid URL.']);
        expect(result.errors.fields.topics).toEqual(['Please select no more than 2 options.']);
    });

    it('skips client-side submit validation when onSubmit is disabled', async() => {
        const submit = vi.fn<FrontendTransport['submit']>(async({ session }) => ({
            success: true,
            isFinalPage: false,
            currentPageId: 'page-2',
            nextPageId: 'page-2',
            errors: {
                form: [],
                fields: {},
                pages: {},
            },
            messages: {},
            session: {
                ...session,
                currentPageId: 'page-2',
            },
        }));
        const envelope = createEnvelope([
            createTextField('email', 'email', {
                required: true,
            }),
        ]);
        envelope.definition.settings.validation.onSubmit = false;
        const runtime = createFrontendFormInstance({
            envelope,
            transport: createTransport({
                submit,
            }),
        });

        const result = await runtime.submit('submit');

        expect(result.success).toBe(true);
        expect(submit).toHaveBeenCalledTimes(1);
        expect(runtime.getState().errors.form).toEqual([]);
    });

    it('resets values and navigation back to the initial session state', async() => {
        const runtime = createFrontendFormInstance({
            envelope: createEnvelope([createTextField('trigger', 'trigger')]),
            transport: createTransport(),
        });

        runtime.setValue('trigger', 'yes');
        await runtime.submit('submit');
        expect(runtime.getState().currentPageId).toBe('page-2');

        runtime.reset();

        expect(runtime.getState().currentPageId).toBe('page-1');
        expect(runtime.getState().values.trigger).toBe('');
        expect(runtime.getState().errors.form).toEqual([]);
    });

    it('emits the client ready event after subscribers can attach', async() => {
        const runtime = createFrontendFormInstance({
            envelope: createEnvelope([createTextField('trigger', 'trigger')]),
            transport: createTransport(),
        });
        const callback = vi.fn();

        runtime.on('formie:client:ready', callback);
        await Promise.resolve();

        expect(callback).toHaveBeenCalledTimes(1);
    });
});
