import { evaluateConditionDefinition, finalizeConditionEvaluation } from './conditions';
import { FrontendEventEmitter } from './events';
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
    FrontendFieldDefinition,
    FrontendFormEnvelope,
    FrontendFormInstance,
    FrontendFormState,
    FrontendSubmitAction,
    FrontendSubmitResult,
    FrontendTransport,
} from './types';

type CreateFrontendFormInstanceOptions = {
    envelope: FrontendFormEnvelope;
    transport: FrontendTransport;
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

function cloneState(state: FrontendFormState): FrontendFormState {
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
            pages: Object.fromEntries(Object.entries(state.errors.pages).map(([key, value]) => [key, [...value]])),
        },
        fieldStates: Object.fromEntries(Object.entries(state.fieldStates).map(([key, value]) => [key, { ...value }])),
        pageStates: Object.fromEntries(Object.entries(state.pageStates).map(([key, value]) => [key, { ...value }])),
        lastSubmitResult: state.lastSubmitResult ? {
            ...state.lastSubmitResult,
            errors: {
                form: [...state.lastSubmitResult.errors.form],
                fields: Object.fromEntries(Object.entries(state.lastSubmitResult.errors.fields).map(([key, value]) => [key, [...value]])),
                pages: Object.fromEntries(Object.entries(state.lastSubmitResult.errors.pages).map(([key, value]) => [key, [...value]])),
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

function initialValues(envelope: FrontendFormEnvelope): Record<string, unknown> {
    return Object.fromEntries(allFields(envelope.definition).map((field) => {
        return [field.id, defaultValueForField(field)];
    }));
}

function initialFieldStates(definition: FrontendFormEnvelope['definition']): FrontendFormState['fieldStates'] {
    return Object.fromEntries(allFields(definition).map((field) => {
        return [field.id, {
            hidden: field.meta?.hidden === true,
            disabled: field.meta?.disabled === true,
        }];
    }));
}

function initialPageStates(definition: FrontendFormEnvelope['definition']): FrontendFormState['pageStates'] {
    return Object.fromEntries(definition.pages.map((page) => {
        return [page.id, { hidden: false }];
    }));
}

function fieldIdsForPage(state: FrontendFormState, pageId: string): string[] {
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

function resolveConditionField(
    definition: FrontendFormState['definition'],
    rule: NonNullable<FrontendFieldDefinition['condition']>['rules'][number],
): FrontendFieldDefinition | undefined {
    return findFieldById(definition, rule.fieldId) || findFieldByHandle(definition, rule.fieldId);
}

function evaluateFieldStates(state: FrontendFormState): FrontendFormState['fieldStates'] {
    const nextFieldStates = initialFieldStates(state.definition);

    allFields(state.definition).forEach((field) => {
        const condition = field.condition;

        if (!condition || condition.rules.length === 0) {
            return;
        }

        const results = condition.rules.map((rule) => {
            const sourceField = resolveConditionField(state.definition, rule);
            const visibility = sourceField ? nextFieldStates[sourceField.id]?.hidden !== true : null;

            return evaluateConditionDefinition({
                condition: rule.operator,
                value: rule.value,
            }, sourceField ? fieldValueAsStrings(sourceField, state.values[sourceField.id]) : [], {
                visibility,
            });
        });

        if (condition.effect === 'show' || condition.effect === 'hide') {
            const { shouldHide } = finalizeConditionEvaluation({
                conditionRule: condition.mode,
                showRule: condition.effect === 'show' ? 'show' : 'hide',
            }, results);

            nextFieldStates[field.id] = {
                ...nextFieldStates[field.id],
                hidden: nextFieldStates[field.id].hidden || shouldHide,
            };

            return;
        }

        const matches = condition.mode === 'any'
            ? results.includes(true)
            : results.every((result) => result === true);

        nextFieldStates[field.id] = {
            ...nextFieldStates[field.id],
            disabled: nextFieldStates[field.id].disabled || (condition.effect === 'disable' ? matches : !matches),
        };
    });

    return nextFieldStates;
}

function clearValueForHiddenField(field: FrontendFieldDefinition): unknown {
    return defaultValueForField(field);
}

function clearHiddenFieldValues(
    state: FrontendFormState,
    previousFieldStates: FrontendFormState['fieldStates'],
    nextFieldStates: FrontendFormState['fieldStates'],
): Record<string, unknown> {
    let nextValues = state.values;

    allFields(state.definition).forEach((field) => {
        const condition = field.condition;
        const wasHidden = previousFieldStates[field.id]?.hidden === true;
        const isHidden = nextFieldStates[field.id]?.hidden === true;
        const shouldClearOnHide = condition?.clearOnHide !== false;

        if (!isHidden || wasHidden || !shouldClearOnHide) {
            return;
        }

        const clearedValue = clearValueForHiddenField(field);

        if (nextValues[field.id] === clearedValue) {
            return;
        }

        nextValues = {
            ...nextValues,
            [field.id]: clearedValue,
        };
    });

    return nextValues;
}

function evaluatePageStates(
    state: FrontendFormState,
    fieldStates: FrontendFormState['fieldStates'],
): FrontendFormState['pageStates'] {
    return Object.fromEntries(state.definition.pages.map((page) => {
        const condition = page.condition;

        if (!condition || condition.rules.length === 0) {
            return [page.id, { hidden: false }];
        }

        const results = condition.rules.map((rule) => {
            const sourceField = resolveConditionField(state.definition, rule);
            const visibility = sourceField ? fieldStates[sourceField.id]?.hidden !== true : null;

            return evaluateConditionDefinition({
                condition: rule.operator,
                value: rule.value,
            }, sourceField ? fieldValueAsStrings(sourceField, state.values[sourceField.id]) : [], {
                visibility,
            });
        });
        const { shouldHide } = finalizeConditionEvaluation({
            conditionRule: condition.mode,
            showRule: condition.effect === 'show' ? 'show' : 'hide',
        }, results);

        return [page.id, { hidden: shouldHide }];
    }));
}

function resolveCurrentPageId(
    definition: FrontendFormState['definition'],
    pageStates: FrontendFormState['pageStates'],
    preferredPageId?: string | null,
): string {
    const fallbackPageId = definition.pages[0]?.id || '';
    const firstVisiblePageId = definition.pages.find((page) => pageStates[page.id]?.hidden !== true)?.id || fallbackPageId;

    if (!preferredPageId) {
        return firstVisiblePageId;
    }

    return pageStates[preferredPageId]?.hidden === true
        ? firstVisiblePageId
        : preferredPageId;
}

function applyDerivedState(current: FrontendFormState): FrontendFormState {
    let nextState = current;

    for (let iteration = 0; iteration < 3; iteration += 1) {
        const fieldStates = evaluateFieldStates(nextState);
        const nextValues = clearHiddenFieldValues(nextState, nextState.fieldStates, fieldStates);

        if (nextValues !== nextState.values) {
            nextState = {
                ...nextState,
                values: nextValues,
                fieldStates,
            };
            continue;
        }

        const pageStates = evaluatePageStates(nextState, fieldStates);

        return {
            ...nextState,
            fieldStates,
            pageStates,
            currentPageId: resolveCurrentPageId(nextState.definition, pageStates, nextState.currentPageId),
        };
    }

    const fieldStates = evaluateFieldStates(nextState);
    const pageStates = evaluatePageStates(nextState, fieldStates);

    return {
        ...nextState,
        fieldStates,
        pageStates,
        currentPageId: resolveCurrentPageId(nextState.definition, pageStates, nextState.currentPageId),
    };
}

function isEmptyValue(field: FrontendFieldDefinition, value: unknown): boolean {
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

function validateFieldValue(
    field: FrontendFieldDefinition,
    value: unknown,
    state: FrontendFormState,
    errorKey: string,
    output: Record<string, string[]>,
): void {
    const ruleTypes = new Set(field.validation.map((rule) => rule.type));
    const contract = field.input;

    if ((field.required || ruleTypes.has('required')) && isEmptyValue(field, value)) {
        output[errorKey] = ['This field is required.'];
        return;
    }

    if ((isEmailField(field) || ruleTypes.has('email')) && typeof value === 'string' && value.trim() !== '') {
        const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);

        if (!isValid) {
            output[errorKey] = ['Please enter a valid email address.'];
            return;
        }
    }

    if ((isNumericField(field) || ruleTypes.has('number')) && typeof value === 'string' && value.trim() !== '') {
        const numericValue = Number.parseFloat(value);

        if (!Number.isFinite(numericValue)) {
            output[errorKey] = ['Please enter a valid number.'];
            return;
        }

        const numberRule = field.validation.find((rule) => rule.type === 'number');
        const min = Number(contract.min ?? numberRule?.min ?? Number.NaN);
        const max = Number(contract.max ?? numberRule?.max ?? Number.NaN);

        if (Number.isFinite(min) && numericValue < min) {
            output[errorKey] = [`Please enter a value greater than or equal to ${min}.`];
            return;
        }

        if (Number.isFinite(max) && numericValue > max) {
            output[errorKey] = [`Please enter a value less than or equal to ${max}.`];
            return;
        }
    }

    if (ruleTypes.has('url') && typeof value === 'string' && value.trim() !== '') {
        try {
            new URL(value);
        } catch {
            output[errorKey] = ['Please enter a valid URL.'];
            return;
        }
    }

    const matchRule = field.validation.find((rule) => rule.type === 'match');

    if (matchRule && typeof value === 'string' && value.trim() !== '') {
        const sourceField = (matchRule.fieldId ? findFieldById(state.definition, matchRule.fieldId) : undefined)
            || (matchRule.fieldHandle ? findFieldByHandle(state.definition, matchRule.fieldHandle) : undefined);
        const sourceValue = sourceField ? state.values[sourceField.id] : undefined;

        if (typeof sourceValue === 'string' && sourceValue !== value) {
            output[errorKey] = ['This value must match the related field.'];
            return;
        }
    }

    if (ruleTypes.has('minmaxOptions') && Array.isArray(value)) {
        const optionsRule = field.validation.find((rule) => rule.type === 'minmaxOptions');
        const min = Number(contract.min ?? optionsRule?.min ?? Number.NaN);
        const max = Number(contract.max ?? optionsRule?.max ?? Number.NaN);

        if (Number.isFinite(min) && value.length < min) {
            output[errorKey] = [`Please select at least ${min} option${min === 1 ? '' : 's'}.`];
            return;
        }

        if (Number.isFinite(max) && value.length > max) {
            output[errorKey] = [`Please select no more than ${max} option${max === 1 ? '' : 's'}.`];
            return;
        }
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

function validateCurrentPage(state: FrontendFormState): FrontendFormState['errors'] {
    const errors: FrontendFormState['errors'] = {
        form: [],
        fields: {},
        pages: {},
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

export function createFrontendFormInstance({ envelope, transport }: CreateFrontendFormInstanceOptions): FrontendFormInstance {
    const emitter = new FrontendEventEmitter();
    const subscribers = new Set<(state: FrontendFormState) => void>();
    const defaults = initialValues(envelope);

    let state: FrontendFormState = {
        status: 'ready',
        definition: envelope.definition,
        session: envelope.session,
        values: defaults,
        errors: {
            form: [],
            fields: {},
            pages: {},
        },
        fieldStates: initialFieldStates(envelope.definition),
        pageStates: initialPageStates(envelope.definition),
        currentPageId: envelope.session.currentPageId || envelope.definition.settings.initialPageId,
        lastSubmitResult: null,
    };
    state = applyDerivedState(state);

    const publish = () => {
        const snapshot = cloneState(state);
        subscribers.forEach((listener) => {
            listener(snapshot);
        });
    };

    const setState = (updater: (current: FrontendFormState) => FrontendFormState) => {
        state = updater(state);
        publish();
    };

    const instance: FrontendFormInstance = {
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
            const page = state.definition.pages.find((item) => item.id === state.currentPageId);
            const requestedAction: FrontendSubmitAction = action || page?.actions.primary.type || 'submit';
            const transportAction = requestedAction === 'next' ? 'submit' : requestedAction;

            if (transportAction !== 'back' && transportAction !== 'save' && state.definition.settings.validation.onSubmit) {
                const errors = validateCurrentPage(state);

                if (errors.form.length > 0 || Object.keys(errors.fields).length > 0) {
                    const result: FrontendSubmitResult = {
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

            setState((current) => ({
                ...current,
                status: 'submitting',
                errors: {
                    form: [],
                    fields: {},
                    pages: {},
                },
            }));

            try {
                const result = await transport.submit({
                    definition: state.definition,
                    session: state.session,
                    values: state.values,
                    action: transportAction,
                });

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
                const result: FrontendSubmitResult = {
                    success: false,
                    isFinalPage: false,
                    errors: {
                        form: [message],
                        fields: {},
                        pages: {},
                    },
                    messages: {
                        error: message,
                    },
                    session: state.session,
                };

                setState((current) => ({
                    ...current,
                    status: 'ready',
                    errors: result.errors,
                    lastSubmitResult: result,
                }));
                emitter.emit('formie:submit:result', result);

                return result;
            }
        },
        async setPage(pageId) {
            if (!transport.setPage) {
                setState((current) => applyDerivedState({
                    ...current,
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
                const session = await transport.setPage({
                    definition: state.definition,
                    session: state.session,
                    values: state.values,
                    currentPageId: state.currentPageId,
                    targetPageId: pageId,
                });

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
        },
        async refreshSession() {
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

                setState((current) => applyDerivedState({
                    ...current,
                    status: 'ready',
                    session,
                    currentPageId: session.currentPageId || current.currentPageId,
                }));
                emitter.emit('formie:session:refreshed', session);
            } catch (error) {
                const message = error instanceof Error ? error.message : 'Unable to refresh session.';

                setState((current) => ({
                    ...current,
                    status: 'ready',
                }));
                emitter.emit('formie:session:refresh:error', {
                    error: message,
                });
            }
        },
        reset() {
            setState((current) => applyDerivedState({
                ...current,
                session: envelope.session,
                values: { ...defaults },
                errors: {
                    form: [],
                    fields: {},
                    pages: {},
                },
                currentPageId: envelope.session.currentPageId || envelope.definition.settings.initialPageId,
                lastSubmitResult: null,
            }));
            emitter.emit('formie:state:reset', null);
        },
        async destroy() {
            setState((current) => ({
                ...current,
                status: 'destroyed',
            }));
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
