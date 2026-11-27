import { describe, expect, it, vi } from 'vitest';
import { createClientFormInstance } from './form-instance';
import type { ClientFieldDefinition, ClientFormBootstrap, ClientFormSession, ClientTransport } from './types';

function createTextField(id: string, handle: string, overrides: Partial<ClientFieldDefinition> = {}): ClientFieldDefinition {
    return {
        id,
        uid: id,
        key: id,
        handle,
        label: handle,
        type: 'single-line-text',
        required: false,
        validation: [],
        client: {
            children: { model: 'scalar' },
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

function createEnvelope(fields: ClientFieldDefinition[], session: Partial<ClientFormSession> = {}): ClientFormBootstrap {
    return {
        contractVersion: 1,
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
            modules: { contractVersion: 2, surface: 'client-rendered', entries: [] },
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

function createTransport(overrides: Partial<ClientTransport> = {}): ClientTransport {
    return {
        submit: async({ session }) => ({
            success: true,
            isFinalPage: false,
            currentPageId: 'page-2',
            nextPageId: 'page-2',
            errors: {
                form: [],
                fields: {},
            },
            messages: {},
            session: {
                ...session,
                currentPageId: 'page-2',
            },
        }),
        refreshSession: async({ session }) => session,
        setPage: async({ session, targetPageId }) => ({
            success: true, isFinalPage: false, currentPageId: targetPageId,
            errors: { form: [], fields: {} }, messages: {},
            session: { ...session, currentPageId: targetPageId },
        }),
        ...overrides,
    };
}

describe('createClientFormInstance', () => {
    it('derives field and page visibility from conditions', () => {
        const runtime = createClientFormInstance({
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

    it('compares a date picker’s answer by its date in conditions', () => {
        const runtime = createClientFormInstance({
            envelope: createEnvelope([
                createTextField('when', 'when', {
                    type: 'date',
                    input: { kind: 'date', fieldKind: 'date', displayType: 'datePicker', collectMode: 'single', defaultValue: null },
                }),
                createTextField('conditional', 'conditional', {
                    condition: { mode: 'all', effect: 'show', rules: [{ fieldId: 'when', operator: '=', value: '2026-11-15', valueType: 'date' }] },
                }),
            ]),
            transport: createTransport(),
        });

        expect(runtime.getState().fieldStates.conditional.hidden).toBe(true);
        runtime.setValue('when', { datetime: '2026-11-15 12:00:00' });
        expect(runtime.getState().fieldStates.conditional.hidden).toBe(false);
    });

    it('clears newly hidden values when conditions hide a field', () => {
        const runtime = createClientFormInstance({
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
        expect(runtime.getState().values.conditional).toBeNull();
    });

    it('compares the title of a selected entry, which the rule sends by entry ID', () => {
        const titleRule = (operator: string, value: string) => ({
            mode: 'all' as const,
            effect: 'show' as const,
            rules: [{
                fieldId: 'scheme',
                operator,
                value,
                valueType: 'text' as const,
                source: { target: 'field', handle: 'scheme', selector: 'title', elementValues: { 407: 'Property 1', 409: 'Graduate Scheme' } },
            }],
        });
        const runtime = createClientFormInstance({
            envelope: createEnvelope([
                createTextField('scheme', 'scheme'),
                createTextField('exact', 'exact', { condition: titleRule('=', 'Graduate Scheme') }),
                createTextField('partial', 'partial', { condition: titleRule('contains', 'Graduate') }),
            ]),
            transport: createTransport(),
        });

        expect(runtime.getState().fieldStates.exact.hidden).toBe(true);

        runtime.setValue('scheme', '409');
        expect(runtime.getState().fieldStates.exact.hidden).toBe(false);
        expect(runtime.getState().fieldStates.partial.hidden).toBe(false);

        runtime.setValue('scheme', '407');
        expect(runtime.getState().fieldStates.exact.hidden).toBe(true);

        // Several selected entries compare as a collection, as the server reads them.
        runtime.setValue('scheme', ['407', '409']);
        expect(runtime.getState().fieldStates.exact.hidden).toBe(false);
    });

    it('supports notContains condition operators', () => {
        const runtime = createClientFormInstance({
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
        const runtime = createClientFormInstance({
            envelope: createEnvelope([
                {
                    id: 'items',
                    key: 'items',
                    handle: 'items',
                    label: 'Items',
                    type: 'repeater',
                    required: false,
                    validation: [],
                    client: {
                        children: { model: 'repeatable-parent', mode: 'rows' },
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
                            value: 'Widget A',
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
        const submit = vi.fn<ClientTransport['submit']>(async({ session, action }) => ({
            success: true,
            isFinalPage: false,
            currentPageId: 'page-2',
            nextPageId: 'page-2',
            errors: {
                form: [],
                fields: {},
            },
            messages: {},
            session: {
                ...session,
                currentPageId: 'page-2',
            },
        }));
        const envelope = createEnvelope([createTextField('trigger', 'trigger')]);
        envelope.definition.pages[0].actions.primary.type = 'next';
        const runtime = createClientFormInstance({
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
        const runtime = createClientFormInstance({
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
        const runtime = createClientFormInstance({
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
        expect(result.errors.fields.confirm).toEqual(['confirm must match email.']);
        expect(result.errors.fields.quantity).toEqual(['quantity must be no greater than 4.']);
        expect(result.errors.fields.website).toEqual(['website is not a valid URL.']);
        expect(result.errors.fields.topics).toEqual(['Please select no more than 2 options.']);
    });

    it('skips client-side submit validation when onSubmit is disabled', async() => {
        const submit = vi.fn<ClientTransport['submit']>(async({ session }) => ({
            success: true,
            isFinalPage: false,
            currentPageId: 'page-2',
            nextPageId: 'page-2',
            errors: {
                form: [],
                fields: {},
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
        const runtime = createClientFormInstance({
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

    it('treats a required Phone field with a country and no number as unanswered', async() => {
        const submit = vi.fn<ClientTransport['submit']>(async() => ({
            success: true,
            outcome: 'completed',
            currentPageId: 'page-1',
            errors: { form: [], fields: {} },
            messages: {},
            session: {},
        }) as Awaited<ReturnType<ClientTransport['submit']>>);
        const runtime = createClientFormInstance({
            envelope: createEnvelope([createTextField('phone', 'phone', {
                type: 'phone',
                required: true,
                input: { kind: 'text', inputType: 'tel', countryEnabled: true, defaultValue: { number: '', country: 'AU' } },
            })]),
            transport: createTransport({ submit }),
        });

        await runtime.submit('submit');
        expect(submit).not.toHaveBeenCalled();
        expect(runtime.getState().errors.fields.phone).toHaveLength(1);

        runtime.setValue('phone', { number: '412345678', country: 'AU' });
        await runtime.submit('submit');
        expect(submit).toHaveBeenCalledTimes(1);
    });

    it('treats a required Likert scale with rows as unanswered until every row is', async() => {
        const submit = vi.fn<ClientTransport['submit']>(async() => ({
            success: true, outcome: 'completed', currentPageId: 'page-1', errors: { form: [], fields: {} }, messages: {}, session: {},
        }) as Awaited<ReturnType<ClientTransport['submit']>>);
        const runtime = createClientFormInstance({
            envelope: createEnvelope([createTextField('likert', 'likert', {
                type: 'survey',
                required: true,
                input: { kind: 'select', fieldKind: 'select', displayType: 'likert', likertRows: [{ label: 'One', value: 'one' }, { label: 'Two', value: 'two' }], options: [{ label: 'Yes', value: 'yes' }], defaultValue: null },
            })]),
            transport: createTransport({ submit }),
        });

        runtime.setValue('likert', { one: 'yes' });
        await runtime.submit('submit');
        expect(submit).not.toHaveBeenCalled();

        runtime.setValue('likert', { one: 'yes', two: 'yes' });
        await runtime.submit('submit');
        expect(submit).toHaveBeenCalledTimes(1);
    });

    it('checks a required date picker as one answer', async() => {
        const submit = vi.fn<ClientTransport['submit']>(async() => ({
            success: true, outcome: 'completed', currentPageId: 'page-1', errors: { form: [], fields: {} }, messages: {}, session: {},
        }) as Awaited<ReturnType<ClientTransport['submit']>>);
        const runtime = createClientFormInstance({
            envelope: createEnvelope([createTextField('when', 'when', {
                type: 'date',
                required: true,
                input: { kind: 'date', fieldKind: 'date', displayType: 'datePicker', collectMode: 'single', defaultValue: null },
            })]),
            transport: createTransport({ submit }),
        });

        await runtime.submit('submit');
        expect(submit).not.toHaveBeenCalled();
        expect(runtime.getState().errors.fields.when).toHaveLength(1);

        runtime.setValue('when', { datetime: '2026-11-15 12:00:00' });
        await runtime.submit('submit');
        expect(submit).toHaveBeenCalledTimes(1);
    });

    it('treats a required date range as unanswered until it has both ends', async() => {
        const submit = vi.fn<ClientTransport['submit']>(async() => ({
            success: true, outcome: 'completed', currentPageId: 'page-1', errors: { form: [], fields: {} }, messages: {}, session: {},
        }) as Awaited<ReturnType<ClientTransport['submit']>>);
        const runtime = createClientFormInstance({
            envelope: createEnvelope([createTextField('stay', 'stay', {
                type: 'date',
                required: true,
                input: { kind: 'date', fieldKind: 'date', displayType: 'datePicker', collectMode: 'range', defaultValue: null },
            })]),
            transport: createTransport({ submit }),
        });

        runtime.setValue('stay', { startDatetime: '2026-11-10 00:00:00', endDatetime: '' });
        await runtime.submit('submit');
        expect(submit).not.toHaveBeenCalled();

        runtime.setValue('stay', { startDatetime: '2026-11-10 00:00:00', endDatetime: '2026-11-20 00:00:00' });
        await runtime.submit('submit');
        expect(submit).toHaveBeenCalledTimes(1);
    });

    it('resets values and navigation back to the initial session state', async() => {
        const runtime = createClientFormInstance({
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

    it.each(['reset', 'message'] as const)('uses the renewed completion session after a %s form is reset again', async(behavior) => {
        const envelope = createEnvelope([createTextField('answer', 'answer')]);
        const sent: Array<{ session: ClientFormSession; answer: unknown }> = [];
        const transport = createTransport({
            submit: async({ session, values }) => {
                sent.push({ session, answer: values.answer });

                return {
                    success: true,
                    isFinalPage: true,
                    outcome: 'completed',
                    errors: { form: [], fields: {} },
                    messages: {},
                    completion: { behavior, url: null, target: 'same-tab', message: null, hideForm: false },
                    session: {
                        ...envelope.session,
                        id: `fresh-${sent.length}`,
                        tokens: { request: `request-${sent.length}`, render: `render-${sent.length}` },
                    },
                };
            },
        });
        const runtime = createClientFormInstance({ envelope, transport });

        for (let i = 0; i < 3; i += 1) {
            runtime.setValue('answer', `Visitor ${i}`);
            await runtime.submit();
            runtime.reset();
            expect(runtime.getState().values.answer).toBe('');
            expect(runtime.getState().session.id).toBe(`fresh-${i + 1}`);
            expect(runtime.getState().currentPageId).toBe('page-1');
        }

        expect(sent.map(({ session }) => session.id)).toEqual(['session-1', 'fresh-1', 'fresh-2']);
        expect(sent.map(({ answer }) => answer)).toEqual(['Visitor 0', 'Visitor 1', 'Visitor 2']);
        expect(envelope.session.id).toBe('session-1');
    });

    it('keeps the renewed reset baseline after a later nonfinal response', async() => {
        const envelope = createEnvelope([createTextField('answer', 'answer')]);
        let calls = 0;
        const runtime = createClientFormInstance({
            envelope,
            transport: createTransport({
                submit: async({ session }) => {
                    calls += 1;

                    return {
                        success: true,
                        isFinalPage: calls === 1,
                        completion: calls === 1 ? { behavior: 'reset', url: null, target: 'same-tab', message: null, hideForm: false } : null,
                        errors: { form: [], fields: {} },
                        messages: {},
                        session: calls === 1
                            ? { ...session, id: 'fresh', currentPageId: 'page-1', tokens: { request: 'new-request', render: 'new-render' } }
                            : { ...session, currentPageId: 'page-2' },
                    };
                },
            }),
        });

        await runtime.submit();
        runtime.reset();
        await runtime.submit();
        runtime.reset();
        expect(runtime.getState().session.id).toBe('fresh');
        expect(runtime.getState().currentPageId).toBe('page-1');
    });

    it('keeps ordinary reset behaviour for a saved draft on the final page', async() => {
        const envelope = createEnvelope([createTextField('answer', 'answer')]);
        const runtime = createClientFormInstance({
            envelope,
            transport: createTransport({
                submit: async({ session }) => ({
                    success: true,
                    isFinalPage: true,
                    outcome: 'draftSaved',
                    completion: null,
                    errors: { form: [], fields: {} },
                    messages: {},
                    session: { ...session, id: 'draft-session', currentPageId: 'page-2' },
                }),
            }),
        });

        await runtime.submit('save');
        expect(runtime.getState().session.id).toBe('draft-session');
        runtime.reset();
        expect(runtime.getState().session).toMatchObject(envelope.session);
        expect(runtime.getState().currentPageId).toBe('page-1');
    });

    it('emits the client ready event after subscribers can attach', async() => {
        const runtime = createClientFormInstance({
            envelope: createEnvelope([createTextField('trigger', 'trigger')]),
            transport: createTransport(),
        });
        const callback = vi.fn();

        runtime.on('formie:client:ready', callback);
        await Promise.resolve();

        expect(callback).toHaveBeenCalledTimes(1);
    });

    it('rejects concurrent submit calls and keeps destroy terminal', async() => {
        const pending: Array<(value: any) => void> = [];
        const transport: ClientTransport = {
            ...createTransport(),
            submit: vi.fn(() => new Promise((resolve) => {
                pending.push(resolve);
            })),
        };
        const envelope = createEnvelope([createTextField('trigger', 'trigger')]);
        envelope.definition.settings.validation.onSubmit = false;

        const runtime = createClientFormInstance({
            envelope,
            transport,
        });

        const first = runtime.submit('submit');
        const second = await runtime.submit('submit');

        expect(transport.submit).toHaveBeenCalledTimes(1);
        expect(second.success).toBe(false);
        expect(second.errors.form[0]).toContain('already in progress');

        const sessionSnapshot = runtime.getState().session;
        await runtime.destroy();
        expect(runtime.getState().status).toBe('destroyed');

        pending.forEach((resolve) => resolve({
            success: true,
            isFinalPage: true,
            errors: { form: [], fields: {} },
            messages: {},
            session: sessionSnapshot,
        }));
        await first;

        expect(runtime.getState().status).toBe('destroyed');
    });
    it('ignores a pending submission after reset and allows a fresh submission', async() => {
        const pending: Array<(value: any) => void> = [];
        const transport = createTransport({ submit: vi.fn(() => new Promise((resolve) => pending.push(resolve))) });
        const runtime = createClientFormInstance({
            envelope: createEnvelope([createTextField('trigger', 'trigger')]),
            transport,
        });
        const listener = vi.fn();
        runtime.on('formie:submit:result', listener);
        const old = runtime.submit('submit');
        await vi.waitFor(() => expect(pending).toHaveLength(1));
        runtime.reset();
        expect(runtime.getState().status).toBe('ready');
        runtime.setValue('trigger', 'yes');
        const fresh = runtime.submit('submit');
        await vi.waitFor(() => expect(pending).toHaveLength(2));
        const response = (page: string) => ({
            success: true, isFinalPage: true, currentPageId: page,
            errors: { form: [], fields: {} }, messages: {},
        });
        pending[0](response('obsolete-page'));
        await old;
        expect(runtime.getState().status).toBe('submitting');
        expect(runtime.getState().currentPageId).toBe('page-1');
        expect(listener).not.toHaveBeenCalled();
        pending[1](response('page-2'));
        await fresh;
        expect(runtime.getState().status).toBe('ready');
        expect(runtime.getState().currentPageId).toBe('page-2');
        expect(listener).toHaveBeenCalledTimes(1);
    });

    it.each([true, false])('keeps newer navigation when an older refresh finishes (remote: %s)', async(remote) => {
        let finishRefresh!: (session: ClientFormSession) => void;
        const transport = createTransport({
            refreshSession: vi.fn(() => new Promise((resolve) => { finishRefresh = resolve; })),
        });
        if (!remote) transport.setPage = undefined;
        const runtime = createClientFormInstance({
            envelope: createEnvelope([createTextField('trigger', 'trigger')]), transport,
        });
        runtime.setValue('trigger', 'yes');
        const oldSession = runtime.getState().session;
        const pending = runtime.refreshSession();
        await runtime.setPage('page-2');
        finishRefresh(oldSession);
        await pending;
        expect(runtime.getState().currentPageId).toBe('page-2');
        expect(runtime.getState().status).toBe('ready');
    });

});
