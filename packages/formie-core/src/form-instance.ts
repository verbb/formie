import { deriveConditionState, clientActionAllowed } from './condition-state';
import { validateBrowserValue } from './validation';
import { assertClientFormBootstrap } from './contract';
import { validateCompositeDateParts } from './date-parts-validation';
import { ClientEventEmitter } from './events';
import {
    allFields,
    compositePartDefinitions,
    createRepeaterRowValue,
    defaultValueForField,
    fieldValueAsStrings,
    isBooleanField,
    isCompositeField,
    isEmailField,
    isFileField,
    isMultiValueField,
    isNumericField,
    isRepeatableField,
    findFieldByHandle,
    findFieldById,
    repeaterFieldDefinitions,
} from './schema';
import type {
    ClientFieldDefinition,
    ClientFormBootstrap,
    ClientFormInstance,
    ClientFormState,
    ClientSubmitAction,
    ClientSubmitResult,
    ClientTransport,
} from './types';

type CreateClientFormInstanceOptions = {
    envelope: ClientFormBootstrap;
    transport: ClientTransport;
};

function cloneValue<T>(value: T): T {
    if (Array.isArray(value)) {
        return value.map((item) => cloneValue(item)) as T;
    }

    if (!value || typeof value !== 'object') {
        return value;
    }

    if (typeof File !== 'undefined' && value instanceof File) {
        return value;
    }

    if (typeof Blob !== 'undefined' && value instanceof Blob) {
        return value;
    }

    return Object.fromEntries(Object.entries(value).map(([key, item]) => {
        return [key, cloneValue(item)];
    })) as T;
}

function cloneState(state: ClientFormState): ClientFormState {
    return {
        ...state,
        session: {
            ...state.session,
            tokens: { ...state.session.tokens },
            continuation: state.session.continuation ? { ...state.session.continuation } : null,
        },
        values: cloneValue(state.values),
        errors: {
            form: [...state.errors.form],
            fields: Object.fromEntries(Object.entries(state.errors.fields).map(([key, value]) => [key, [...value]])),
        },
        fieldStates: Object.fromEntries(Object.entries(state.fieldStates).map(([key, value]) => [key, { ...value }])),
        pageStates: Object.fromEntries(Object.entries(state.pageStates).map(([key, value]) => [key, { ...value }])),
        lastSubmitResult: state.lastSubmitResult ? {
            ...state.lastSubmitResult,
            errors: {
                form: [...state.lastSubmitResult.errors.form],
                fields: Object.fromEntries(Object.entries(state.lastSubmitResult.errors.fields).map(([key, value]) => [key, [...value]])),
            },
            messages: { ...state.lastSubmitResult.messages },
            session: state.lastSubmitResult.session ? {
                ...state.lastSubmitResult.session,
                tokens: { ...state.lastSubmitResult.session.tokens },
                continuation: state.lastSubmitResult.session.continuation ? { ...state.lastSubmitResult.session.continuation } : null,
            } : null,
        } : null,
    };
}

function initialValues(envelope: ClientFormBootstrap): Record<string, unknown> {
    return Object.fromEntries(allFields(envelope.definition).map((field) => {
        return [field.id, defaultValueForField(field)];
    }));
}

function initialFieldStates(definition: ClientFormBootstrap['definition']): ClientFormState['fieldStates'] {
    return Object.fromEntries(allFields(definition).map((field) => {
        return [field.id, {
            hidden: field.meta?.hidden === true,
            disabled: field.meta?.disabled === true,
        }];
    }));
}

function initialPageStates(definition: ClientFormBootstrap['definition']): ClientFormState['pageStates'] {
    return Object.fromEntries(definition.pages.map((page) => {
        return [page.id, { hidden: false }];
    }));
}

function fieldIdsForPage(state: ClientFormState, pageId: string): string[] {
    const page = state.definition.pages.find((item) => item.id === pageId);

    if (!page) {
        return [];
    }

    const output: string[] = [];

    page.rows.forEach((row) => {
        row.fields.forEach((field) => {
            output.push(field.id);
        });
    });

    return output;
}

function applyDerivedState(state: ClientFormState): ClientFormState {
    return deriveConditionState(state);
}

function isEmptyValue(field: ClientFieldDefinition, value: unknown): boolean {
    if (field.type === 'checkboxes') {
        return !Array.isArray(value) || value.length === 0;
    }

    if (isBooleanField(field)) {
        return value !== true;
    }

    if (isFileField(field) || isRepeatableField(field) || isMultiValueField(field)) {
        return !Array.isArray(value) || value.length === 0;
    }

    if (isCompositeField(field) && value && typeof value === 'object') {
        return Object.values(value as Record<string, unknown>).every((item) => {
            return item == null || (typeof item === 'string' && item.trim() === '');
        });
    }

    if (value == null) {
        return true;
    }

    if (typeof value === 'string') {
        return value.trim() === '';
    }

    return false;
}

function fieldLabel(field: ClientFieldDefinition): string {
    return field.label?.trim() || field.handle;
}

function validateFieldValue(
    field: ClientFieldDefinition,
    value: unknown,
    state: ClientFormState,
    errorKey: string,
    output: Record<string, string[]>,
): void {
    if (state.fieldStates[errorKey]?.hidden || state.fieldStates[errorKey]?.disabled) return;
    const rules = field.validation.slice();
    if (field.required && !rules.some((rule) => rule.type === 'required')) rules.unshift({ type: 'required' });
    for (const rule of rules) {
        const source = (rule.fieldId ? findFieldById(state.definition, rule.fieldId) : undefined) || (rule.fieldHandle ? findFieldByHandle(state.definition, rule.fieldHandle) : undefined);
        const message = validateBrowserValue(value, rule, {
            label: fieldLabel(field), empty: isEmptyValue(field, value),
            comparison: source ? state.values[source.id] : undefined,
            comparisonLabel: source ? fieldLabel(source) : undefined,
        });
        if (message) { output[errorKey] = [message]; return; }
    }

    if (isCompositeField(field)) {
        const parts = compositePartDefinitions(field);
        const currentValue = value && typeof value === 'object' ? value as Record<string, unknown> : {};

        parts.forEach((part) => {
            if (part.meta?.hidden === true) {
                return;
            }

            validateFieldValue(part, currentValue[part.handle], state, `${errorKey}.${part.handle}`, output);
        });

        validateCompositeDateParts(field, currentValue, errorKey, output);

        return;
    }

    if (isRepeatableField(field)) {
        const rows = Array.isArray(value) ? value : [];
        const rowFields = repeaterFieldDefinitions(field);

        rows.forEach((rowValue, rowIndex) => {
            const currentRow = rowValue && typeof rowValue === 'object' ? rowValue as Record<string, unknown> : {};

            rowFields.forEach((rowField) => {
                validateFieldValue(
                    rowField,
                    currentRow[rowField.handle],
                    state,
                    `${errorKey}.${rowIndex}.${rowField.handle}`,
                    output,
                );
            });
        });
    }
}

function validateCurrentPage(state: ClientFormState): ClientFormState['errors'] {
    const errors: ClientFormState['errors'] = {
        form: [],
        fields: {},
    };

    fieldIdsForPage(state, state.currentPageId).forEach((fieldId) => {
        const field = findFieldById(state.definition, fieldId);

        if (!field || state.fieldStates[fieldId]?.hidden === true || state.fieldStates[fieldId]?.disabled === true) {
            return;
        }

        validateFieldValue(field, state.values[fieldId], state, fieldId, errors.fields);
    });

    if (Object.keys(errors.fields).length > 0) {
        errors.form = [state.definition.settings.validation.formErrorMessage || 'Please correct the highlighted fields.'];
    }

    return errors;
}

export function createClientFormInstance({ envelope, transport }: CreateClientFormInstanceOptions): ClientFormInstance {
    assertClientFormBootstrap(envelope);
    const emitter = new ClientEventEmitter();
    const subscribers = new Set<(state: ClientFormState) => void>();
    const defaults = initialValues(envelope);

    // Bumped on destroy so stale async completions cannot revive state.
    let operationGeneration = 0;

    let state: ClientFormState = {
        status: 'ready',
        definition: envelope.definition,
        session: envelope.session,
        values: defaults,
        errors: {
            form: [],
            fields: {},
        },
        fieldStates: initialFieldStates(envelope.definition),
        pageStates: initialPageStates(envelope.definition),
        currentPageId: envelope.session.currentPageId || envelope.definition.settings.initialPageId,
        lastSubmitResult: null,
    };
    state = applyDerivedState(state);

    const publish = () => {
        if (state.status === 'destroyed') {
            return;
        }

        const snapshot = cloneState(state);
        subscribers.forEach((listener) => {
            listener(snapshot);
        });
    };

    const setState = (updater: (current: ClientFormState) => ClientFormState) => {
        // Destruction is terminal — ignore late transport completions.
        if (state.status === 'destroyed') {
            return;
        }

        state = updater(state);
        publish();
    };

    const isStale = (generation: number) => {
        return state.status === 'destroyed' || generation !== operationGeneration;
    };

    let browserModuleGuard: () => void = () => undefined;
    let prepareBrowserModules: (action: ClientSubmitAction) => Promise<Record<string, unknown>> = async() => ({});
    const instance: ClientFormInstance = {
        setBrowserModuleGuard(guard) { browserModuleGuard = guard; },
        getBrowserRequestOptions() { return { profile: 'same-origin-browser', ...transport.browserRequestOptions }; },
        setBrowserModulePreparation(prepare) { prepareBrowserModules = prepare; },
        id: envelope.session.id,
        getState() {
            return cloneState(state);
        },
        subscribe(listener) {
            subscribers.add(listener);
            listener(cloneState(state));

            return () => {
                subscribers.delete(listener);
            };
        },
        setValue(fieldId, value) {
            setState((current) => {
                const nextFieldErrors = Object.fromEntries(Object.entries(current.errors.fields).filter(([key]) => {
                    return key !== fieldId && !key.startsWith(`${fieldId}.`);
                }));

                nextFieldErrors[fieldId] = [];

                return applyDerivedState({
                    ...current,
                    values: {
                        ...current.values,
                        [fieldId]: value,
                    },
                    errors: {
                        ...current.errors,
                        fields: nextFieldErrors,
                    },
                });
            });
        },
        patchValues(values) {
            setState((current) => applyDerivedState({
                ...current,
                values: {
                    ...current.values,
                    ...values,
                },
            }));
        },
        async submit(action) {
            browserModuleGuard();
            if (state.status === 'destroyed') {
                return {
                    success: false,
                    isFinalPage: false,
                    errors: {
                        form: ['Form instance has been destroyed.'],
                        fields: {},
                    },
                    messages: {
                        error: 'Form instance has been destroyed.',
                    },
                    session: state.session,
                } satisfies ClientSubmitResult;
            }

            // Reject overlapping submits on the shared imperative API.
            if (state.status === 'submitting') {
                return {
                    success: false,
                    isFinalPage: false,
                    errors: {
                        form: ['A submission is already in progress.'],
                        fields: {},
                    },
                    messages: {
                        error: 'A submission is already in progress.',
                    },
                    session: state.session,
                } satisfies ClientSubmitResult;
            }

            const page = state.definition.pages.find((item) => item.id === state.currentPageId);
            const requestedAction: ClientSubmitAction = action || page?.actions.primary.type || 'submit';
            const transportAction = requestedAction === 'next' ? 'submit' : requestedAction;

            if (transportAction !== 'back' && transportAction !== 'save' && state.definition.settings.validation.onSubmit) {
                const errors = validateCurrentPage(state);

                if (errors.form.length > 0 || Object.keys(errors.fields).length > 0) {
                    const result: ClientSubmitResult = {
                        success: false,
                        isFinalPage: false,
                        errors,
                        messages: {
                            error: errors.form[0] || null,
                        },
                        session: state.session,
                    };

                    setState((current) => ({
                        ...current,
                        errors,
                        lastSubmitResult: result,
                    }));
                    emitter.emit('formie:submit:result', result);

                    return result;
                }
            }

            const generation = ++operationGeneration;

            setState((current) => ({
                ...current,
                status: 'submitting',
                errors: {
                    form: [],
                    fields: {},
                },
            }));

            try {
                const browserData = await prepareBrowserModules(transportAction);
                if (isStale(generation)) throw new Error('Submission was cancelled before sending.');
                const result = await transport.submit({
                    browserData,
                    definition: state.definition,
                    session: state.session,
                    values: state.values,
                    action: transportAction,
                });

                if (isStale(generation)) {
                    return result;
                }

                setState((current) => applyDerivedState({
                    ...current,
                    status: 'ready',
                    session: result.session ?? current.session,
                    currentPageId: result.session?.currentPageId || result.currentPageId || current.currentPageId,
                    errors: result.errors,
                    lastSubmitResult: result,
                }));
                emitter.emit('formie:submit:result', result);

                if (result.currentPageId || result.nextPageId) {
                    emitter.emit('formie:page:navigate', {
                        currentPageId: state.currentPageId,
                        nextPageId: result.nextPageId || result.currentPageId,
                    });
                }

                return result;
            } catch (error) {
                const message = error instanceof Error ? error.message : 'Submission failed.';
                const result: ClientSubmitResult = {
                    success: false,
                    isFinalPage: false,
                    errors: {
                        form: [message],
                        fields: {},
                    },
                    messages: {
                        error: message,
                    },
                    session: state.session,
                };

                if (!isStale(generation)) {
                    setState((current) => ({
                        ...current,
                        status: 'ready',
                        errors: result.errors,
                        lastSubmitResult: result,
                    }));
                    emitter.emit('formie:submit:result', result);
                }

                return result;
            }
        },
        async setPage(pageId) {
            if (state.status === 'destroyed' || state.status === 'submitting') {
                return;
            }

            const generation = ++operationGeneration;

            if (!transport.setPage) {
                setState((current) => applyDerivedState({
                    ...current,
                    status: 'ready',
                    currentPageId: pageId,
                    session: {
                        ...current.session,
                        currentPageId: pageId,
                    },
                }));

                return;
            }

            setState((current) => ({
                ...current,
                status: 'refreshing',
            }));

            try {
                const result = await transport.setPage({
                    definition: state.definition,
                    session: state.session,
                    values: state.values,
                    currentPageId: state.currentPageId,
                    targetPageId: pageId,
                });

                if (isStale(generation)) {
                    return;
                }

                if (!result.success || !result.session) {
                    setState((current) => ({ ...current, status: 'ready', errors: result.errors, lastSubmitResult: result }));
                    emitter.emit('formie:submit:result', result);
                    return;
                }
                const session = result.session;
                setState((current) => applyDerivedState({
                    ...current,
                    status: 'ready',
                    session,
                    currentPageId: session.currentPageId,
                }));
                emitter.emit('formie:page:navigate', {
                    currentPageId: state.currentPageId,
                    nextPageId: pageId,
                });
            } catch (error) {
                const message = error instanceof Error ? error.message : 'Unable to change page.';

                if (!isStale(generation)) {
                    setState((current) => ({
                        ...current,
                        status: 'ready',
                    }));
                    emitter.emit('formie:page:navigate:error', {
                        currentPageId: state.currentPageId,
                        nextPageId: pageId,
                        error: message,
                    });
                }
            }
        },
        async refreshSession() {
            if (state.status === 'destroyed' || state.status === 'submitting') {
                return;
            }

            const generation = ++operationGeneration;

            setState((current) => ({
                ...current,
                status: 'refreshing',
            }));

            try {
                const session = await transport.refreshSession({
                    formHandle: state.definition.handle,
                    siteId: state.definition.siteId ?? undefined,
                    session: state.session,
                });

                if (isStale(generation)) {
                    return;
                }

                setState((current) => applyDerivedState({
                    ...current,
                    status: 'ready',
                    session,
                    currentPageId: session.currentPageId || current.currentPageId,
                }));
                emitter.emit('formie:session:refreshed', session);
            } catch (error) {
                const message = error instanceof Error ? error.message : 'Unable to refresh session.';

                if (!isStale(generation)) {
                    setState((current) => ({
                        ...current,
                        status: 'ready',
                    }));
                    emitter.emit('formie:session:refresh:error', {
                        error: message,
                    });
                }
            }
        },
        reset() {
            if (state.status === 'destroyed') {
                return;
            }

            operationGeneration += 1;
            setState((current) => applyDerivedState({
                ...current,
                status: 'ready',
                session: envelope.session,
                values: cloneValue(defaults),
                errors: {
                    form: [],
                    fields: {},
                },
                currentPageId: envelope.session.currentPageId || envelope.definition.settings.initialPageId,
                lastSubmitResult: null,
            }));
            emitter.emit('formie:state:reset', null);
        },
        async destroy() {
            operationGeneration += 1;
            state = {
                ...state,
                status: 'destroyed',
            };
            subscribers.clear();
        },
        on(eventName, callback) {
            return emitter.on(eventName, callback);
        },
    };

    queueMicrotask(() => {
        emitter.emit('formie:client:ready', instance.getState());
    });

    return instance;
}
