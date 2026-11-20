import { clientActionAllowed } from '@verbb/formie-core';
import { mountClientRenderedModules } from '@verbb/formie-browser';
import {
    CLIENT_FORM_EVENT_NAMES,
    compositePartDefinitions,
    createRepeaterRowValue,
    createClientFormInstance,
    getClientErrorAriaLive,
    getClientFieldIds,
    getClientFieldLabelTarget,
    joinIdReferences,
    createGraphqlClientTransport,
    createRestClientTransport,
    createTableRowValue,
    dropdownOptions,
    fieldOptions,
    focusClientFormResult,
    getClientPageProgress,
    getClientString,
    showsClientPageTitle,
    isArrayValuedField,
    isCalculationField,
    isFieldLabelVisuallyHidden,
    isPhoneCountryEnabled,
    isRichTextField,
    checkboxToggle,
    allOptionsSelected,
    toggleAllOptions,
    moveListItem,
    surveyLikertRows,
    surveyRankOrder,
    attachPointerReorder,
    paymentFieldMarkup,
    summaryFieldMarkup,
    PHONE_COUNTRY_CHANGE_EVENT,
    phoneFieldValue,
    readPhoneFieldControls,
    resolveClientRendererType,
    tableColumns,
    VISUALLY_HIDDEN_STYLE,
    isCompositeField,
    isFileField,
    displayFieldContent,
    isFieldLabelHidden,
    isKnownClientFieldType,
    isRepeatableField,
    loadClientFormBootstrap,
    loadGraphqlClientFormBootstrap,
    repeaterRowDefinitions,
    type ClientFieldDefinition,
    type ClientErrorAriaLive,
    type ClientFormDefinition,
    type ClientFormBootstrap,
    type ClientFormSession,
    type ClientFormInstance,
    type ClientFormState,
    type ClientSubmitResult,
} from '@verbb/formie-core';
import {
    cloneElement,
    createContext,
    createElement,
    isValidElement,
    useContext,
    useEffect,
    useMemo,
    useRef,
    useState,
    type ReactElement,
    type ReactNode,
} from 'react';
import { stableSerialize } from './stable.js';

export type FormieDefinitionSource =
    | {
        transport: 'rest';
        endpoint: string;
        profile?: 'same-origin-browser' | 'cross-origin-public';
        formHandle: string;
        siteId?: number;
        query?: Record<string, string | string[]>;
    }
    | {
        transport: 'graphql';
        endpoint: string;
        profile?: 'same-origin-browser' | 'cross-origin-public';
        formHandle: string;
        siteId?: number;
        query?: Record<string, string | string[]>;
    }
    | {
        definition: ClientFormBootstrap;
        transport: {
            type: 'rest';
            endpoint: string;
        profile?: 'same-origin-browser' | 'cross-origin-public';
            formHandle: string;
            siteId?: number;
        query?: Record<string, string | string[]>;
        };
    }
    | {
        definition: ClientFormBootstrap;
        transport: {
            type: 'graphql';
            endpoint: string;
        profile?: 'same-origin-browser' | 'cross-origin-public';
            formHandle: string;
            siteId?: number;
        query?: Record<string, string | string[]>;
        };
    };

export type FormieReactEvent = {
    name: string;
    payload: unknown;
};

export type FormieFormComponentProps = {
    definition: ClientFormDefinition;
    session: ClientFormSession;
    state: ClientFormState;
    children?: ReactNode;
    className?: string;
    onSubmit: () => void;
};

export type FormiePageComponentProps = {
    page: ClientFormDefinition['pages'][number];
    state: ClientFormState;
    children?: ReactNode;
};

export type FormieFieldProps = {
    field: ClientFieldDefinition;
    errors: string[];
    errorId: string;
    errorAriaLive: ClientErrorAriaLive;
    inputId: string;
    labelId: string;
    instructionsId: string;
    children?: ReactNode;
};

export type FormieErrorSummaryProps = {
    errors: string[];
};

export type FormieFieldComponentProps = {
    field: ClientFieldDefinition;
    value: unknown;
    errors: string[];
    errorKey: string;
    errorId: string;
    errorAriaLive: ClientErrorAriaLive;
    inputId: string;
    labelId: string;
    instructionsId: string;
    disabled: boolean;
    hidden: boolean;
    setValue(value: unknown): void;
};

export type FormieSlotComponentProps = {
    slotKey: string;
    children?: ReactNode;
    attributes?: Record<string, unknown>;
    field?: ClientFieldDefinition;
};

export type FormieReactComponents = {
    Form?: (props: FormieFormComponentProps) => ReactNode;
    Page?: (props: FormiePageComponentProps) => ReactNode;
    Field?: (props: FormieFieldProps) => ReactNode;
    ErrorSummary?: (props: FormieErrorSummaryProps) => ReactNode;
};

type FormieDefinitionContextValue = {
    instance: ClientFormInstance;
    state: ClientFormState;
    components: FormieReactComponents;
    fieldComponents: Partial<Record<string, (props: FormieFieldComponentProps) => ReactNode>>;
    slots: Partial<Record<string, (props: FormieSlotComponentProps) => ReactNode>>;
};

const FormieDefinitionContext = createContext<FormieDefinitionContextValue | null>(null);

function isInlineDefinitionSource(source: FormieDefinitionSource): source is Extract<FormieDefinitionSource, { transport: { type: 'rest' | 'graphql' } }> {
    return 'definition' in source;
}

async function resolveDefinitionEnvelope(source: FormieDefinitionSource): Promise<ClientFormBootstrap> {
    if (isInlineDefinitionSource(source)) {
        return source.definition;
    }

    if (source.transport === 'graphql') {
        return loadGraphqlClientFormBootstrap({
            endpoint: source.endpoint,
        profile: source.profile,
            formHandle: source.formHandle,
            siteId: source.siteId,
        query: source.query,
        });
    }

    return loadClientFormBootstrap({
        endpoint: source.endpoint,
        profile: source.profile,
        formHandle: source.formHandle,
        siteId: source.siteId,
        query: source.query,
    });
}

function resolveDefinitionTransport(source: FormieDefinitionSource) {
    const transportSource = isInlineDefinitionSource(source)
        ? source.transport
        : {
            type: source.transport,
            endpoint: source.endpoint,
        profile: source.profile,
            formHandle: source.formHandle,
            siteId: source.siteId,
        query: source.query,
        };

    if (transportSource.type === 'graphql') {
        return createGraphqlClientTransport(transportSource);
    }

    return createRestClientTransport(transportSource);
}

function DefaultErrorSummary({ errors }: FormieErrorSummaryProps) {
    if (errors.length === 0) {
        return null;
    }

    return createElement('div', {
        className: 'formie-react-errors',
        role: 'alert',
        tabIndex: -1,
        'data-formie-form-message': true,
    }, createElement('ul', null, errors.map((error, index) => {
        return createElement('li', { key: `${error}:${index}` }, error);
    })));
}

function DefaultField({ field, errors, errorId, errorAriaLive, inputId, labelId, instructionsId, children }: FormieFieldProps) {
    const { slots } = useDefinitionContext();

    const renderSlot = (slotKey: string, child: ReactNode, attributes?: Record<string, unknown>) => {
        const Slot = slots[slotKey];

        if (!Slot) {
            return child;
        }

        return createElement(Slot, {
            slotKey,
            children: child,
            attributes,
            field,
        });
    };

    return createElement('div', {
        className: 'formie-react-field',
        'data-formie-field-type': field.type,
        'data-formie-field-uid': field.uid,
        'data-formie-field-handle': field.handle,
        'data-formie-field-required': field.required ? 'true' : undefined,
    }, [
        field.label && !isFieldLabelHidden(field) ? renderSlot('label', createElement('label', {
            key: 'label',
            className: 'formie-react-label',
            style: isFieldLabelVisuallyHidden(field) ? VISUALLY_HIDDEN_STYLE : undefined,
            id: labelId || undefined,
            htmlFor: inputId && getClientFieldLabelTarget(field, resolveFieldRendererType(field)) === 'control' ? inputId : undefined,
        }, field.label)) : null,
        field.instructions ? renderSlot('instructions', createElement('div', {
            key: 'instructions',
            className: 'formie-react-description',
            id: instructionsId || undefined,
            dangerouslySetInnerHTML: { __html: field.instructions },
        })) : null,
        renderSlot('input', createElement('div', {
            key: 'input',
            className: 'formie-react-input',
        }, children)),
        renderSlot('errors', createElement('ul', {
            key: 'errors',
            id: errorId,
            className: 'formie-react-field-errors',
            style: errors.length === 0 ? { position: 'absolute' } : undefined,
            'data-formie-field-errors': true,
            'aria-live': errorAriaLive === 'off' ? undefined : errorAriaLive,
            'aria-atomic': errorAriaLive === 'off' ? undefined : 'true',
        }, errors.map((error, index) => {
            return createElement('li', { key: `${error}:${index}` }, error);
        })), {
            id: errorId,
            style: errors.length === 0 ? { position: 'absolute' } : undefined,
            'data-formie-field-errors': true,
            'aria-live': errorAriaLive === 'off' ? undefined : errorAriaLive,
            'aria-atomic': errorAriaLive === 'off' ? undefined : 'true',
        }),
    ]);
}

function BrowserModuleHost({ children }: { children: ReactNode }) {
    const { instance } = useDefinitionContext();
    const root = useRef<HTMLDivElement>(null);
    useEffect(() => {
        let disposed = false;
        let host: Awaited<ReturnType<typeof mountClientRenderedModules>> | undefined;
        instance.setBrowserModuleGuard(() => { throw new Error('Form features are still loading.'); });
        void mountClientRenderedModules(root.current!, instance).then((mounted: Awaited<ReturnType<typeof mountClientRenderedModules>>) => {
            if (disposed) void mounted.destroy(); else host = mounted;
        });
        return () => { disposed = true; void host?.destroy(); };
    }, [instance]);
    return createElement('div', { ref: root }, children);
}

function DefaultForm({ definition, session, state, children, className, onSubmit }: FormieFormComponentProps) {
    const root = useRef<HTMLFormElement>(null);
    const pageRef = useRef(state.currentPageId);
    const resultRef = useRef(state.lastSubmitResult);
    useEffect(() => {
        // Focus moves once per submit result, compared with the page shown before it.
        if (state.lastSubmitResult && state.lastSubmitResult !== resultRef.current) {
            focusClientFormResult(root.current, pageRef.current);
        }

        resultRef.current = state.lastSubmitResult;
        pageRef.current = state.currentPageId;
    }, [state.lastSubmitResult, state.currentPageId]);
    return createElement('form', {
        ref: root,
        className,
        onSubmit: async (event: Event) => {
            event.preventDefault();
            await onSubmit();
        },
        'data-formie-definition': definition.handle,
        'data-formie-render-id': session.tokens.render,
    }, children);
}

function DefaultPage({ page, state, children }: FormiePageComponentProps) {
    const showTitle = showsClientPageTitle(state.definition) && !!page.label;
    const titleId = `formie-${state.session.tokens.render || state.session.id}-page-${page.id}-title`;

    return createElement('section', {
        'data-page-id': page.id,
        'data-formie-page-id': page.id,
        className: 'formie-react-page',
        // Focus moves here when the page changes, and the page name says where the visitor is.
        tabIndex: -1,
        'aria-labelledby': showTitle ? titleId : undefined,
        'aria-label': !showTitle && state.definition.pages.length > 1 && page.label ? page.label : undefined,
    }, showTitle ? createElement('h3', { id: titleId, className: 'formie-react-page-title' }, page.label) : null, children);
}

// The form's progress through its pages, shown as the server-rendered progress bar is.
function renderProgress(state: ClientFormState): ReactNode {
    const progress = getClientPageProgress(state.definition, state.currentPageId);

    if (!progress) {
        return null;
    }

    const value = createElement('span', { key: 'value', className: 'formie-react-progress-value', 'aria-hidden': 'true' }, `${progress.percent}%`);
    const inside = !['left', 'right', 'hidden'].includes(progress.valuePosition);

    return createElement('div', {
        key: 'progress',
        className: 'formie-react-progress',
        'data-formie-progress-position': progress.position,
        'data-formie-progress-value-position': progress.valuePosition,
        // Themes use the state to keep the value inside the bar at 0% and 100%, as in server-rendered forms.
        'data-formie-progress-state': progress.percent <= 0 ? 'start' : (progress.percent >= 100 ? 'end' : 'middle'),
    }, [
        progress.valuePosition === 'left' ? value : null,
        createElement('div', {
            key: 'track',
            className: 'formie-react-progress-track',
            role: 'progressbar',
            'aria-label': getClientString(state.definition, 'progress'),
            'aria-valuenow': progress.percent,
            'aria-valuemin': 0,
            'aria-valuemax': 100,
        }, createElement('div', { className: 'formie-react-progress-bar', style: { width: `${progress.percent}%` } }, inside ? value : null)),
        progress.valuePosition === 'right' ? value : null,
    ]);
}

function useDefinitionContext(): FormieDefinitionContextValue {
    const context = useContext(FormieDefinitionContext);

    if (!context) {
        throw new Error('Formie definition hooks must be used within a client-rendered <FormieClientForm />.');
    }

    return context;
}

function isFieldDefinition(candidate: unknown): candidate is ClientFieldDefinition {
    return !!candidate && typeof candidate === 'object' && 'id' in candidate && 'handle' in candidate && 'type' in candidate;
}

function resolveFieldRendererType(field: ClientFieldDefinition): ClientFieldDefinition['type'] {
    return resolveClientRendererType(field);
}

function errorReferenceAttributes(errors: string[], errorId: string) {
    return errors.length > 0 ? {
        'aria-invalid': 'true',
        'aria-errormessage': errorId,
        'aria-describedby': errorId,
    } : {};
}

type ControlLabelling = {
    errors: string[];
    errorId: string;
    inputId: string;
    labelId: string;
    instructionsId: string;
    // The default Field wrapper renders the label and instructions these IDs point to. A custom wrapper
    // may not, so controls fall back to naming themselves.
    labelledByWrapper: boolean;
};

function controlLabelling(props: Pick<FormieFieldComponentProps, 'field' | 'errors' | 'errorId' | 'inputId' | 'labelId' | 'instructionsId'>, labelledByWrapper: boolean): ControlLabelling {
    return {
        errors: props.errors,
        errorId: props.errorId,
        inputId: props.inputId,
        labelId: props.labelId,
        instructionsId: props.instructionsId,
        labelledByWrapper: labelledByWrapper && Boolean(props.field.label),
    };
}

function instructionsReference(field: ClientFieldDefinition, labelling: ControlLabelling) {
    return labelling.labelledByWrapper && field.instructions ? labelling.instructionsId : null;
}

function controlAttributes(field: ClientFieldDefinition, labelling: ControlLabelling) {
    const hasErrors = labelling.errors.length > 0;

    return {
        id: labelling.inputId || undefined,
        'aria-label': labelling.labelledByWrapper ? undefined : field.label || field.handle,
        'aria-required': field.required ? 'true' as const : undefined,
        'aria-invalid': hasErrors ? 'true' as const : undefined,
        'aria-errormessage': hasErrors ? labelling.errorId : undefined,
        'aria-describedby': joinIdReferences(instructionsReference(field, labelling), hasErrors && labelling.errorId),
        autoComplete: typeof field.input.autocomplete === 'string' ? field.input.autocomplete : undefined,
    };
}

// The pad can't be used without a pointer, but its name says what it's for.
function signatureCanvasAttributes(field: ClientFieldDefinition, labelling: ControlLabelling) {
    return {
        role: 'img',
        'aria-labelledby': labelling.labelledByWrapper ? labelling.labelId : undefined,
        'aria-label': labelling.labelledByWrapper ? undefined : field.label || field.handle,
    };
}

function groupAttributes(field: ClientFieldDefinition, labelling: ControlLabelling, role: 'group' | 'radiogroup') {
    return {
        role,
        'aria-labelledby': labelling.labelledByWrapper ? labelling.labelId : undefined,
        'aria-label': labelling.labelledByWrapper ? undefined : field.label || field.handle,
        // ARIA supports a required radio group, but not a required group of checkboxes.
        'aria-required': role === 'radiogroup' && field.required ? 'true' as const : undefined,
        'aria-describedby': joinIdReferences(instructionsReference(field, labelling)),
    };
}

function renderNestedFieldInput(field: ClientFieldDefinition, value: unknown, disabled: boolean, setValue: (value: unknown) => void, labelling: ControlLabelling, rendererType = resolveFieldRendererType(field)): ReactNode {
    const contract = field.input;

    if (rendererType === 'multi-line-text' && isRichTextField(field)) {
        // The editor builds itself inside the empty container and writes its HTML into the hidden textarea, then fires
        // `input`. React's onInput sees every native input event, unlike onChange, so the wrapper reads it from there.
        return createElement('div', {
            className: 'formie-react-rich-text',
            onInput: (event: { target: EventTarget }) => {
                if (event.target instanceof HTMLTextAreaElement) setValue(event.target.value);
            },
        },
            createElement('div', { key: 'editor', 'data-formie-rich-text': 'true' }),
            createElement('textarea', {
                key: 'input',
                ...controlAttributes(field, labelling),
                'data-formie-multi-line-text-input': 'true',
                style: { display: 'none' },
                value: typeof value === 'string' ? value : '',
                readOnly: true,
                disabled,
                placeholder: typeof contract.placeholder === 'string' ? contract.placeholder : undefined,
            }),
        );
    }

    if (rendererType === 'multi-line-text') {
        return createElement('textarea', {
            ...controlAttributes(field, labelling),
            value: typeof value === 'string' ? value : '',
            disabled,
            placeholder: typeof contract.placeholder === 'string' ? contract.placeholder : undefined,
            onChange: (event: Event) => {
                const target = event.target as HTMLTextAreaElement;
                setValue(target.value);
            },
        });
    }

    if (rendererType === 'dropdown') {
        const multiple = contract.multiple === true;
        // Element fields keep a list of IDs even when only one can be chosen.
        const listValue = isArrayValuedField(field);
        const selected = (Array.isArray(value) ? value : (value == null ? [] : [value])).map((item) => String(item));
        const options = multiple ? fieldOptions(field) : dropdownOptions(field);
        const current = selected[0] ?? '';
        // Without this, a dropdown whose answer was cleared would show its first option instead.
        const showEmpty = !multiple && current === '' && !options.some((option) => option.value === '');

        return createElement('select', {
            ...controlAttributes(field, labelling),
            value: multiple ? selected : current,
            disabled,
            multiple,
            onChange: (event: Event) => {
                const target = event.target as HTMLSelectElement;

                if (multiple) {
                    setValue(Array.from(target.selectedOptions).map((option) => option.value));
                    return;
                }

                setValue(listValue ? (target.value === '' ? [] : [target.value]) : target.value);
            },
        }, [
            showEmpty ? createElement('option', { key: `${field.id}:empty`, value: '', disabled: true, hidden: true }) : null,
            ...options.map((option) => {
                return createElement('option', {
                    key: `${field.id}:${option.value}`,
                    value: option.value,
                    disabled: option.disabled,
                }, option.label);
            }),
        ]);
    }

    const inputType = typeof contract.inputType === 'string'
        ? contract.inputType
        : (field.type === 'email' ? 'email' : (field.type === 'phone' ? 'tel' : (field.type === 'number' ? 'number' : 'text')));

    return createElement('input', {
        ...controlAttributes(field, labelling),
        type: inputType,
        value: typeof value === 'string' || typeof value === 'number' ? String(value) : '',
        disabled,
        // Calculations fields show an answer worked out from other fields.
        readOnly: isCalculationField(field) || undefined,
        placeholder: typeof contract.placeholder === 'string' ? contract.placeholder : undefined,
        onChange: (event: Event) => {
            const target = event.target as HTMLInputElement;
            setValue(target.value);
        },
    });
}

function renderTableCell(column: ReturnType<typeof tableColumns>[number], value: unknown, disabled: boolean, setValue: (value: unknown) => void): ReactNode {
    if (column.type === 'heading') {
        return createElement('span', null, typeof value === 'string' ? value : '');
    }

    if (column.type === 'checkbox') {
        return createElement('input', {
            type: 'checkbox',
            'aria-label': column.heading,
            checked: value === true || value === '1' || value === 1,
            disabled,
            onChange: (event: Event) => setValue((event.target as HTMLInputElement).checked),
        });
    }

    if (column.type === 'multiline') {
        return createElement('textarea', {
            'aria-label': column.heading,
            value: typeof value === 'string' ? value : '',
            disabled,
            onChange: (event: Event) => setValue((event.target as HTMLTextAreaElement).value),
        });
    }

    if (column.type === 'select' || column.type === 'dropdown') {
        const current = value == null ? '' : String(value);

        return createElement('select', {
            'aria-label': column.heading,
            value: current,
            disabled,
            onChange: (event: Event) => setValue((event.target as HTMLSelectElement).value),
        }, [
            // An answer that isn't one of the options shows as empty rather than as the first option.
            column.options.some((option) => option.value === current) ? null : createElement('option', { key: ':empty', value: current, disabled: true, hidden: true }),
            ...column.options.map((option) => createElement('option', { key: option.value, value: option.value }, option.label)),
        ]);
    }

    const inputTypes: Record<string, string> = { number: 'number', email: 'email', url: 'url', date: 'date', time: 'time', color: 'color' };

    return createElement('input', {
        type: inputTypes[column.type] ?? 'text',
        'aria-label': column.heading,
        value: typeof value === 'string' || typeof value === 'number' ? String(value) : '',
        disabled,
        onChange: (event: Event) => setValue((event.target as HTMLInputElement).value),
    });
}

// Phone answers keep the number and country together. With a country picker, the `formie:phone-country` browser
// module attaches to the marked inputs and reports the country the visitor picks.
function PhoneFieldInput({
    field,
    value,
    labelling,
    disabled,
    setValue,
}: Pick<FormieFieldComponentProps, 'field' | 'value' | 'disabled' | 'setValue'> & { labelling: ControlLabelling }) {
    const container = useRef<HTMLDivElement | null>(null);
    const latest = useRef({ value, setValue });
    const countryEnabled = isPhoneCountryEnabled(field);
    const current = phoneFieldValue(value);

    useEffect(() => {
        latest.current = { value, setValue };
    }, [value, setValue]);

    useEffect(() => {
        const element = container.current;

        if (!element) {
            return undefined;
        }

        const onCountryChange = () => {
            latest.current.setValue(readPhoneFieldControls(element, phoneFieldValue(latest.current.value)));
        };

        element.addEventListener(PHONE_COUNTRY_CHANGE_EVENT, onCountryChange);

        return () => {
            element.removeEventListener(PHONE_COUNTRY_CHANGE_EVENT, onCountryChange);
        };
    }, [countryEnabled]);

    const input = renderNestedFieldInput(field, current.number, disabled, (number) => {
        setValue({ ...readPhoneFieldControls(container.current, current), number: String(number ?? '') });
    }, labelling, 'phone');

    if (!countryEnabled || !isValidElement(input)) {
        return input;
    }

    // The picker wraps the number input, so it gets a container of its own that React never adds children to.
    return createElement('div', { ref: container, className: 'formie-react-phone' },
        cloneElement(input as ReactElement<Record<string, unknown>>, { 'data-formie-phone-input': 'true' }),
        createElement('input', { type: 'hidden', value: current.country ?? '', readOnly: true, 'data-formie-phone-country-input': 'true' }),
    );
}

type SurveyInputProps = FormieFieldComponentProps & { labelling: ControlLabelling };

// A Likert scale: one radio per column in each row. With multiple rows the answer is `{ row: column }`.
function SurveyLikertFieldInput({ field, value, disabled, setValue, labelling, inputId, errors, errorId }: SurveyInputProps) {
    const columns = fieldOptions(field);
    const rows = surveyLikertRows(field);
    const multiple = rows.length > 0;
    const answers = multiple && value && typeof value === 'object' ? value as Record<string, unknown> : {};
    const base = inputId || `${field.id}-likert`;

    return createElement('div', { className: 'formie-react-likert', ...groupAttributes(field, labelling, 'group') },
        createElement('table', null,
            createElement('thead', null, createElement('tr', null,
                createElement('td', { key: 'corner' }),
                ...columns.map((column, index) => createElement('th', { key: column.value, id: `${base}-col-${index}`, scope: 'col' }, column.label)),
            )),
            createElement('tbody', null, (multiple ? rows : [{ label: '', value: '' }]).map((row, rowIndex) => {
                const current = multiple ? String(answers[row.value] ?? '') : (value == null ? '' : String(value));
                const rowId = `${base}-row-${rowIndex}`;

                return createElement('tr', { key: row.value || 'answer' },
                    multiple ? createElement('th', { id: rowId, scope: 'row' }, row.label) : createElement('td'),
                    ...columns.map((column, index) => createElement('td', { key: column.value }, createElement('input', {
                        type: 'radio',
                        // One name per row groups its radios for keyboard use.
                        name: `${base}-${rowIndex}`,
                        value: column.value,
                        checked: current === column.value,
                        disabled: disabled || column.disabled,
                        'aria-labelledby': multiple ? `${base}-col-${index} ${rowId}` : `${base}-col-${index}`,
                        ...errorReferenceAttributes(errors, errorId),
                        onChange: () => setValue(multiple ? { ...answers, [row.value]: column.value } : column.value),
                    }))),
                );
            })),
        ),
    );
}

// A Rank answer lists every option in order. Items reorder by dragging or with their move buttons, through state.
function SurveyRankFieldInput({ field, value, disabled, setValue, labelling }: SurveyInputProps) {
    const { state } = useDefinitionContext();
    const list = useRef<HTMLOListElement | null>(null);
    const latest = useRef({ field, value, setValue });
    const labels = Object.fromEntries(fieldOptions(field).map((option) => [option.value, option.label]));
    const order = surveyRankOrder(field, value);

    useEffect(() => {
        latest.current = { field, value, setValue };
    }, [field, value, setValue]);

    useEffect(() => {
        if (!list.current) return undefined;

        return attachPointerReorder(list.current, 'li', (from, to) => {
            const { field: currentField, value: currentValue, setValue: update } = latest.current;
            update(moveListItem(surveyRankOrder(currentField, currentValue), from, to));
        });
    }, []);

    const move = (from: number, to: number) => setValue(moveListItem(order, from, to));

    return createElement('ol', { ref: list, className: 'formie-react-rank', ...groupAttributes(field, labelling, 'group') }, order.map((item, index) => createElement('li', { key: item },
        createElement('span', null, labels[item] ?? item),
        createElement('button', {
            type: 'button',
            disabled: disabled || index === 0,
            'aria-label': `${getClientString(state.definition, 'moveUp')}: ${labels[item] ?? item}`,
            onClick: () => move(index, index - 1),
        }, getClientString(state.definition, 'moveUp')),
        createElement('button', {
            type: 'button',
            disabled: disabled || index === order.length - 1,
            'aria-label': `${getClientString(state.definition, 'moveDown')}: ${labels[item] ?? item}`,
            onClick: () => move(index, index + 1),
        }, getClientString(state.definition, 'moveDown')),
    )));
}

// Each rating radio covers its star but can't be seen, so the star is what's clicked and the radio keeps focus.
const RATING_INPUT_STYLE = { position: 'absolute', inset: 0, width: '100%', height: '100%', margin: 0, opacity: 0, cursor: 'pointer' } as const;

// A star rating: radios shown as stars, filled up to the answer. Choosing the current star again clears it, as in
// server-rendered forms.
function SurveyRatingFieldInput({ field, value, disabled, setValue, labelling, inputId, errors, errorId }: SurveyInputProps) {
    const options = fieldOptions(field);
    const current = value == null ? '' : String(value);
    const answered = options.findIndex((option) => option.value === current);
    const color = typeof field.input.starColor === 'string' && field.input.starColor ? field.input.starColor : undefined;

    return createElement('div', { className: 'formie-react-rating', style: color ? { color } : undefined, ...groupAttributes(field, labelling, 'radiogroup') }, options.map((option, index) => createElement('label', {
        key: option.value,
        style: { position: 'relative', display: 'inline-block' },
        'data-formie-rating-filled': index <= answered ? 'true' : undefined,
    },
        createElement('input', {
            type: 'radio',
            name: inputId || `${field.id}-rating`,
            value: option.value,
            checked: current === option.value,
            disabled: disabled || option.disabled,
            style: RATING_INPUT_STYLE,
            ...errorReferenceAttributes(errors, errorId),
            onChange: () => setValue(option.value),
            onClick: () => {
                if (current === option.value) setValue(null);
            },
        }),
        createElement('span', { 'aria-hidden': 'true' }, index <= answered ? '★' : '☆'),
        createElement('span', { style: VISUALLY_HIDDEN_STYLE }, option.label),
    )));
}

// The summary module replaces the markup inside this container with the server's summary. React leaves it alone
// while the HTML string, which only changes with the token, stays the same.
function SummaryFieldInput({ field }: Pick<FormieFieldComponentProps, 'field'>) {
    const { state } = useDefinitionContext();

    return createElement('div', { className: 'formie-react-summary', dangerouslySetInnerHTML: { __html: summaryFieldMarkup(field, state.lastSubmitResult) ?? '' } });
}

function TableFieldInput({
    field,
    value,
    labelling,
    disabled,
    setValue,
}: Pick<FormieFieldComponentProps, 'field' | 'value' | 'disabled' | 'setValue'> & { labelling: ControlLabelling }) {
    const { state } = useDefinitionContext();
    const columns = tableColumns(field);
    const rows = Array.isArray(value) ? value as Array<Record<string, unknown>> : [];
    const contract = field.input;
    const fixedRows = contract.static === true || contract.staticRows === true;
    const minRows = Number(contract.minRows ?? 0) || 0;
    const maxRows = Number(contract.maxRows ?? 0) || 0;
    const canAddRow = !disabled && !fixedRows && (maxRows <= 0 || rows.length < maxRows);
    const setCell = (rowIndex: number, handle: string, nextValue: unknown) => {
        setValue(rows.map((row, index) => index === rowIndex ? { ...row, [handle]: nextValue } : row));
    };

    return createElement('div', {
        className: 'formie-react-table',
        ...groupAttributes(field, labelling, 'group'),
    }, [
        createElement('table', { key: 'table' }, [
            createElement('thead', { key: 'head' }, createElement('tr', null, [
                ...columns.map((column) => createElement('th', { key: column.id, scope: 'col' }, column.heading)),
                fixedRows ? null : createElement('td', { key: 'actions' }),
            ])),
            createElement('tbody', { key: 'body' }, rows.map((row, rowIndex) => createElement('tr', { key: rowIndex }, [
                ...columns.map((column) => createElement('td', { key: column.id }, renderTableCell(column, row[column.handle], disabled, (nextValue) => setCell(rowIndex, column.handle, nextValue)))),
                fixedRows ? null : createElement('td', { key: 'actions' }, createElement('button', {
                    type: 'button',
                    disabled: disabled || rows.length <= minRows,
                    'aria-label': `${getClientString(state.definition, 'removeRow')} ${rowIndex + 1}`,
                    onClick: () => setValue(rows.filter((_, index) => index !== rowIndex)),
                }, getClientString(state.definition, 'remove'))),
            ]))),
        ]),
        fixedRows ? null : createElement('button', {
            key: 'add',
            type: 'button',
            disabled: !canAddRow,
            onClick: () => setValue([...rows, createTableRowValue(field)]),
        }, String(contract.addRowLabel || getClientString(state.definition, 'addRow'))),
    ]);
}

function resolveFieldModule(field: ClientFieldDefinition, definition: ClientFormDefinition, moduleId: string) {
    const refs = new Set(field.moduleRefs || []);

    return definition.modules.entries.find((module) => refs.has(module.key) && module.moduleId === moduleId) || null;
}

function SignatureFieldInput({
    field,
    value,
    labelling,
    disabled,
    setValue,
}: Pick<FormieFieldComponentProps, 'field' | 'value' | 'disabled' | 'setValue'> & { labelling: ControlLabelling }) {
    const { state } = useDefinitionContext();
    const canvasRef = useRef<HTMLCanvasElement | null>(null);
    const padRef = useRef<{
        clear: () => void;
        isEmpty: () => boolean;
        toDataURL: () => string;
        fromDataURL: (value: string) => void;
    } | null>(null);
    const [loadError, setLoadError] = useState<string | null>(null);
    const moduleConfig = resolveFieldModule(field, state.definition, 'formie:signature')?.config;
    const backgroundColor = typeof moduleConfig?.backgroundColor === 'string'
        ? String(moduleConfig.backgroundColor)
        : '#ffffff';
    const penColor = typeof moduleConfig?.penColor === 'string'
        ? String(moduleConfig.penColor)
        : '#000000';
    const penWeight = Number(moduleConfig?.penWeight ?? 2) || 2;
    const serializedValue = typeof value === 'string' ? value : '';

    useEffect(() => {
        let disposed = false;
        let removeStrokeListener = () => undefined;
        let removeResizeListener = () => undefined;

        const setup = async() => {
            try {
                const canvas = canvasRef.current;

                if (!canvas) {
                    return;
                }

                const { default: SignaturePad } = await import('signature_pad');

                if (disposed) {
                    return;
                }

                const pad = new SignaturePad(canvas, {
                    backgroundColor,
                    penColor,
                    minWidth: penWeight,
                    maxWidth: penWeight,
                }) as unknown as {
                    clear: () => void;
                    isEmpty: () => boolean;
                    toDataURL: () => string;
                    fromDataURL: (value: string) => void;
                    addEventListener?: (eventName: string, callback: () => void) => void;
                    removeEventListener?: (eventName: string, callback: () => void) => void;
                };

                const resizeCanvas = () => {
                    const ratio = typeof window === 'undefined' ? 1 : Math.max(window.devicePixelRatio || 1, 1);
                    const width = Math.max(1, Math.floor(canvas.clientWidth || 480));
                    const height = 192;
                    const context = canvas.getContext('2d');

                    canvas.width = width * ratio;
                    canvas.height = height * ratio;
                    canvas.style.height = `${height}px`;

                    if (context) {
                        context.setTransform(1, 0, 0, 1, 0, 0);
                        context.scale(ratio, ratio);
                    }

                    pad.clear();
                };

                const handleEnd = () => {
                    setValue(pad.isEmpty() ? '' : pad.toDataURL());
                };

                resizeCanvas();
                pad.addEventListener?.('endStroke', handleEnd);
                removeStrokeListener = () => {
                    pad.removeEventListener?.('endStroke', handleEnd);
                };

                if (typeof window !== 'undefined') {
                    window.addEventListener('resize', resizeCanvas);
                    removeResizeListener = () => {
                        window.removeEventListener('resize', resizeCanvas);
                    };
                }

                padRef.current = pad;
                setLoadError(null);
            } catch (error) {
                if (!disposed) {
                    setLoadError((error as Error).message || 'Unable to load signature support.');
                }
            }
        };

        void setup();

        return () => {
            disposed = true;
            removeStrokeListener();
            removeResizeListener();
            padRef.current = null;
        };
    }, [backgroundColor, penColor, penWeight]);

    useEffect(() => {
        const pad = padRef.current;

        if (!pad) {
            return;
        }

        if (!serializedValue) {
            if (!pad.isEmpty()) {
                pad.clear();
            }

            return;
        }

        try {
            pad.fromDataURL(serializedValue);
        } catch {
            // Ignore invalid persisted data; the field still remains interactive.
        }
    }, [serializedValue]);

    return createElement('div', {
        className: 'formie-react-signature',
    }, [
        createElement('canvas', {
            key: 'canvas',
            ref: canvasRef,
            'data-formie-signature-canvas': true,
            ...signatureCanvasAttributes(field, labelling),
            style: disabled ? { pointerEvents: 'none' } : undefined,
        }),
        createElement('button', {
            key: 'clear',
            type: 'button',
            disabled,
            'aria-label': getClientString(state.definition, 'clearSignature'),
            'data-formie-signature-clear': true,
            onClick: () => {
                padRef.current?.clear();
                setValue('');
            },
        }, getClientString(state.definition, 'clear')),
        loadError ? createElement('div', {
            key: 'error',
            className: 'formie-react-unsupported',
        }, loadError) : null,
    ]);
}

function CompositeFieldInput({
    field,
    value,
    errorKey,
    labelling,
    disabled,
    setValue,
}: Pick<FormieFieldComponentProps, 'field' | 'value' | 'errorKey' | 'disabled' | 'setValue'> & { labelling: ControlLabelling }) {
    const { state } = useDefinitionContext();
    const parts = compositePartDefinitions(field);
    const currentValue = value && typeof value === 'object' ? value as Record<string, unknown> : {};

    if (parts.length === 0) {
        return createElement('div', {
            className: 'formie-react-unsupported',
        }, `Unsupported field type: ${field.type}`);
    }

    return createElement('div', {
        className: 'formie-react-name-grid',
        ...groupAttributes(field, labelling, 'group'),
    }, parts.filter((part) => part.meta?.hidden !== true).map((part) => {
        const partErrorKey = `${errorKey}.${part.handle}`;

        return createElement(ConfigFieldNode, {
            key: `${field.id}:${part.handle}`,
            field: part,
            value: currentValue[part.handle],
            errors: state.errors.fields[partErrorKey] || [],
            errorKey: partErrorKey,
            disabled: disabled || part.meta?.disabled === true,
            setValue(nextValue) {
                setValue({
                    ...currentValue,
                    [part.handle]: nextValue,
                });
            },
        });
    }));
}

function FileFieldInput({
    field,
    value,
    labelling,
    disabled,
    setValue,
}: Pick<FormieFieldComponentProps, 'field' | 'value' | 'disabled' | 'setValue'> & { labelling: ControlLabelling }) {
    const contract = field.input;
    const files = Array.isArray(value) ? value : [];
    const multiple = contract.multiple === true;
    const items = files.map((entry, index) => {
        if (entry && typeof entry === 'object' && 'name' in entry && typeof entry.name === 'string') {
            return entry.name;
        }

        if (entry && typeof entry === 'object' && 'filename' in entry && typeof entry.filename === 'string') {
            return entry.filename;
        }

        if (entry && typeof entry === 'object' && 'assetId' in entry && typeof entry.assetId === 'number') {
            return `Asset #${entry.assetId}`;
        }

        return `File ${index + 1}`;
    });

    return createElement('div', {
        className: 'formie-react-file',
    }, [
        createElement('input', {
            key: 'input',
            type: 'file',
            ...controlAttributes(field, labelling),
            disabled,
            multiple,
            onChange: (event: Event) => {
                const target = event.target as HTMLInputElement;
                setValue(Array.from(target.files || []));
            },
        }),
        items.length > 0 ? createElement('ul', {
            key: 'summary',
            className: 'formie-react-file-list',
        }, items.map((item, index) => {
            return createElement('li', { key: `${item}:${index}` }, item);
        })) : null,
    ]);
}

function RepeaterFieldInput({
    field,
    value,
    errorKey,
    labelling,
    disabled,
    setValue,
}: Pick<FormieFieldComponentProps, 'field' | 'value' | 'errorKey' | 'disabled' | 'setValue'> & { labelling: ControlLabelling }) {
    const { state } = useDefinitionContext();
    const rows = repeaterRowDefinitions(field);
    const currentRows = Array.isArray(value) ? value as Array<Record<string, unknown>> : [];
    const contract = field.input;
    const minRows = Number(contract.minRows ?? 0) || 0;
    const maxRows = Number(contract.maxRows ?? 0) || 0;
    const canAddRow = !disabled && (maxRows <= 0 || currentRows.length < maxRows);

    if (rows.length === 0) {
        return createElement('div', {
            className: 'formie-react-unsupported',
        }, 'Unsupported repeater field.');
    }

    return createElement('div', {
        className: 'formie-react-repeater',
        'data-formie-repeater-container': true,
        ...groupAttributes(field, labelling, 'group'),
    }, [
        ...currentRows.map((rowValue, rowIndex) => {
            const itemKey = `${field.id}:${rowIndex}`;

            return createElement('div', {
                key: itemKey,
                className: 'formie-react-repeater-item',
                'data-formie-repeater-item': true,
            }, [
                ...rows.map((row, nestedRowIndex) => {
                    return createElement(ConfigRow, {
                        key: `${itemKey}:${nestedRowIndex}`,
                        row,
                        rowIndex: nestedRowIndex,
                        values: rowValue,
                        errorPrefix: `${errorKey}.${rowIndex}`,
                        disabled,
                        setFieldValue(rowField, nextValue) {
                            const nextRows = currentRows.map((candidate, candidateIndex) => {
                                if (candidateIndex !== rowIndex) {
                                    return candidate;
                                }

                                return {
                                    ...candidate,
                                    [rowField.handle]: nextValue,
                                };
                            });

                            setValue(nextRows);
                        },
                    });
                }),
                createElement('button', {
                    key: 'remove',
                    type: 'button',
                    disabled: disabled || (minRows > 0 && currentRows.length <= minRows),
                    'aria-label': `${getClientString(state.definition, 'removeRow')} ${rowIndex + 1}`,
                    'data-formie-repeater-remove': true,
                    onClick: () => {
                        setValue(currentRows.filter((_, candidateIndex) => candidateIndex !== rowIndex));
                    },
                }, getClientString(state.definition, 'remove')),
            ]);
        }),
        createElement('button', {
            key: 'add',
            type: 'button',
            disabled: !canAddRow,
            'data-formie-repeater-add': field.handle,
            onClick: () => {
                setValue([...currentRows, createRepeaterRowValue(field)]);
            },
        }, String(contract.addRowLabel || getClientString(state.definition, 'addRow'))),
    ]);
}

function defaultFieldRenderer(props: FormieFieldComponentProps, labelledByWrapper = true): ReactNode {
    const { field, value, errorKey, errorId, disabled, setValue } = props;
    const contract = field.input;
    const rendererType = resolveFieldRendererType(field);
    const labelling = controlLabelling(props, labelledByWrapper);

    if (isCompositeField(field)) {
        return createElement(CompositeFieldInput, {
            field,
            value,
            errorKey,
            labelling,
            disabled,
            setValue,
        });
    }

    if (isRepeatableField(field)) {
        return createElement(RepeaterFieldInput, {
            field,
            value,
            errorKey,
            labelling,
            disabled,
            setValue,
        });
    }

    if (rendererType === 'phone') {
        return createElement(PhoneFieldInput, {
            field,
            value,
            labelling,
            disabled,
            setValue,
        });
    }

    if (rendererType === 'survey-likert') {
        return createElement(SurveyLikertFieldInput, { ...props, labelling });
    }

    if (rendererType === 'survey-rank') {
        return createElement(SurveyRankFieldInput, { ...props, labelling });
    }

    if (rendererType === 'survey-rating') {
        return createElement(SurveyRatingFieldInput, { ...props, labelling });
    }

    if (rendererType === 'table') {
        return createElement(TableFieldInput, {
            field,
            value,
            labelling,
            disabled,
            setValue,
        });
    }

    if (rendererType === 'section') {
        return createElement('hr', { className: 'formie-react-section' });
    }

    if (isFileField(field)) {
        return createElement(FileFieldInput, {
            field,
            value,
            labelling,
            disabled,
            setValue,
        });
    }

    if (rendererType === 'signature') {
        return createElement(SignatureFieldInput, {
            field,
            value,
            labelling,
            disabled,
            setValue,
        });
    }

    if (rendererType === 'multi-line-text' || rendererType === 'dropdown') {
        return renderNestedFieldInput(field, value, disabled, setValue, labelling, rendererType);
    }

    if (rendererType === 'radio') {
        const options = fieldOptions(field);
        // Element fields shown as radios keep a list of one ID.
        const listValue = isArrayValuedField(field);
        const current = Array.isArray(value) ? String(value[0] ?? '') : (value == null ? '' : String(value));

        return createElement('div', {
            className: 'formie-react-choices',
            ...groupAttributes(field, labelling, 'radiogroup'),
        }, options.map((option) => {
            const optionValue = option.value;
            const optionDisabled = disabled || option.disabled;

            return createElement('label', {
                key: `${field.id}:${optionValue}`,
            }, [
                createElement('input', {
                    key: 'input',
                    type: 'radio',
                    // One name per field occurrence groups the radios for keyboard use, including in Repeater rows.
                    name: props.inputId || `${field.id}-radio`,
                    value: optionValue,
                    ...errorReferenceAttributes(props.errors, errorId),
                    checked: current === optionValue,
                    disabled: optionDisabled,
                    onChange: () => {
                        setValue(listValue ? [optionValue] : optionValue);
                    },
                }),
                createElement('span', { key: 'label' }, option.label),
            ]);
        }));
    }

    if (rendererType === 'checkboxes') {
        const options = fieldOptions(field);
        const selectedValues = Array.isArray(value) ? value.map((item) => String(item)) : [];
        const toggle = checkboxToggle(field);
        // "Select all" isn't an answer of its own, so it has no value and isn't validated.
        const toggleControl = toggle ? createElement('label', { key: 'toggle', className: 'formie-react-checkbox-toggle' },
            createElement('input', {
                type: 'checkbox',
                checked: allOptionsSelected(field, selectedValues),
                disabled,
                onChange: (event: { target: HTMLInputElement }) => setValue(toggleAllOptions(field, selectedValues, event.target.checked)),
            }),
            createElement('span', null, toggle.label),
        ) : null;

        return createElement('div', {
            className: 'formie-react-choices',
            ...groupAttributes(field, labelling, 'group'),
        }, toggle?.position === 'top' ? toggleControl : null, ...options.map((option) => {
            const optionValue = option.value;
            const checked = selectedValues.includes(optionValue);
            const optionDisabled = disabled || option.disabled;

            return createElement('label', {
                key: `${field.id}:${optionValue}`,
            }, [
                createElement('input', {
                    key: 'input',
                    type: 'checkbox',
                    ...errorReferenceAttributes(props.errors, errorId),
                    checked,
                    disabled: optionDisabled,
                    onChange: () => {
                        const nextValues = checked
                            ? selectedValues.filter((item) => item !== optionValue)
                            : [...selectedValues, optionValue];

                        setValue(nextValues);
                    },
                }),
                createElement('span', { key: 'label' }, option.label),
            ]);
        }), toggle?.position === 'bottom' ? toggleControl : null);
    }

    if (rendererType === 'agree') {
        const descriptionHtml = typeof contract.descriptionHtml === 'string' ? contract.descriptionHtml : null;

        // As in server-rendered forms, the field label names the group and the description labels the checkbox.
        return createElement('div', {
            className: 'formie-react-agree',
            ...groupAttributes(field, labelling, 'group'),
        }, createElement('label', {
            className: 'formie-react-boolean',
        }, [
            createElement('input', {
                key: 'input',
                type: 'checkbox',
                id: props.inputId || undefined,
                'aria-required': field.required ? 'true' : undefined,
                ...errorReferenceAttributes(props.errors, errorId),
                checked: value === true,
                disabled,
                onChange: (event: Event) => {
                    const target = event.target as HTMLInputElement;
                    setValue(target.checked);
                },
            }),
            descriptionHtml
                ? createElement('span', {
                    key: 'description',
                    dangerouslySetInnerHTML: {
                        __html: descriptionHtml,
                    },
                })
                : createElement('span', { key: 'description' }, field.label),
        ]));
    }

    const display = displayFieldContent(field);

    if (display) {
        return display.kind === 'heading'
            ? createElement(display.level, { className: 'formie-react-heading' }, display.text)
            : createElement('div', { className: 'formie-react-content', dangerouslySetInnerHTML: { __html: display.html } });
    }

    if (field.type === 'summary') {
        return createElement(SummaryFieldInput, { field });
    }

    const payment = paymentFieldMarkup(field);

    // The payment provider owns this markup. React leaves it alone while the HTML string stays the same.
    if (payment !== null) {
        return createElement('div', { className: 'formie-react-payment', dangerouslySetInnerHTML: { __html: payment } });
    }

    if (!isKnownClientFieldType(rendererType)) {
        return createElement('div', {
            className: 'formie-react-unsupported',
        }, `Unsupported field type: ${String(field.meta?.fieldType ?? field.type)}`);
    }

    return renderNestedFieldInput(field, value, disabled, setValue, labelling, rendererType);
}

function ConfigFieldNode({
    field,
    value,
    errors,
    errorKey,
    disabled,
    setValue,
}: {
    field: ClientFieldDefinition;
    value: unknown;
    errors: string[];
    errorKey: string;
    disabled: boolean;
    setValue(nextValue: unknown): void;
}) {
    const { components, fieldComponents, state } = useDefinitionContext();
    const fieldState = state.fieldStates[errorKey];
    const hidden = fieldState?.hidden === true;

    if (hidden) {
        return null;
    }

    const rendererType = resolveFieldRendererType(field);
    const renderer = fieldComponents[field.type] || fieldComponents[rendererType] || defaultFieldRenderer;
    const Field = components.Field || DefaultField;
    const ids = getClientFieldIds(state.session, errorKey);
    const errorId = ids.errors;
    const errorAriaLive = getClientErrorAriaLive(state.definition);
    const rendererProps: FormieFieldComponentProps = {
        field,
        value,
        errors,
        errorKey,
        errorId,
        errorAriaLive,
        inputId: ids.input,
        labelId: ids.label,
        instructionsId: ids.instructions,
        disabled,
        hidden,
        setValue,
    };

    return createElement(Field, {
        field,
        errors,
        errorId,
        errorAriaLive,
        inputId: ids.input,
        labelId: ids.label,
        instructionsId: ids.instructions,
        // Custom field components are React components, so they can use hooks.
        children: renderer === defaultFieldRenderer ? defaultFieldRenderer(rendererProps, !components.Field) : createElement(renderer, rendererProps),
    });
}

function ConfigField({ field }: { field: ClientFieldDefinition }) {
    const { state, instance } = useDefinitionContext();
    const fieldState = state.fieldStates[field.id];

    return createElement(ConfigFieldNode, {
        field,
        value: state.values[field.id],
        errors: state.errors.fields[field.id] || [],
        errorKey: field.id,
        disabled: fieldState?.disabled === true,
        setValue(nextValue) {
            instance.setValue(field.id, nextValue);
        },
    });
}

function ConfigRow({
    row,
    rowIndex,
    values,
    errorPrefix,
    disabled,
    setFieldValue,
}: {
    row: ClientFormDefinition['pages'][number]['rows'][number];
    rowIndex: number;
    values?: Record<string, unknown>;
    errorPrefix?: string;
    disabled?: boolean;
    setFieldValue?: (field: ClientFieldDefinition, nextValue: unknown) => void;
}) {
    const { state } = useDefinitionContext();

    return createElement('div', {
        className: 'formie-react-row',
    }, row.fields.map((field, fieldIndex) => {
        if (!values || !setFieldValue) {
            return createElement(ConfigField, {
                key: field.id || `${rowIndex}:${fieldIndex}`,
                field,
            });
        }

        const errorKey = `${errorPrefix}.${field.handle}`;

        return createElement(ConfigFieldNode, {
            key: field.id || `${rowIndex}:${fieldIndex}`,
            field,
            value: values[field.handle],
            errors: state.errors.fields[errorKey] || [],
            errorKey,
            disabled: disabled === true || state.fieldStates[errorKey]?.disabled === true,
            setValue(nextValue) {
                setFieldValue(field, nextValue);
            },
        });
    }));
}

function ConfigPageActions() {
    const { state, instance } = useDefinitionContext();
    const page = state.definition.pages.find((item) => item.id === state.currentPageId);

    if (!page) {
        return null;
    }

    const buttons: ReactNode[] = [];

    page.actions.secondary.forEach((action) => {
        // The form moves focus once the result renders, as it does after a submit.
        buttons.push(createElement('button', {
            key: action.type,
            type: 'button',
            onClick: () => {
                void instance.submit(action.type);
            },
        }, action.label));
    });

    buttons.push(createElement('button', {
        key: page.actions.primary.type,
        type: 'submit',
        disabled: !clientActionAllowed(state),
    }, page.actions.primary.label));

    return createElement('div', {
        className: 'formie-page-actions',
    }, buttons);
}

function ConfigRenderer({ className }: { className?: string }) {
    const { instance, state, components } = useDefinitionContext();
    const FormComponent = components.Form || DefaultForm;
    const PageComponent = components.Page || DefaultPage;
    const ErrorSummary = components.ErrorSummary || DefaultErrorSummary;
    const page = state.definition.pages.find((item) => {
        return item.id === state.currentPageId && state.pageStates[item.id]?.hidden !== true;
    }) || state.definition.pages.find((item) => state.pageStates[item.id]?.hidden !== true) || state.definition.pages[0];
    const errorMessage = state.lastSubmitResult?.messages.error;
    const shouldRenderStandaloneError = !!errorMessage && !state.errors.form.includes(errorMessage);
    const pageShown = !(state.lastSubmitResult?.completion?.behavior === 'message' && state.lastSubmitResult.completion.hideForm);
    const progressPosition = pageShown ? getClientPageProgress(state.definition, state.currentPageId)?.position : undefined;

    if (!page) {
        return null;
    }

    return createElement(FormComponent, {
        definition: state.definition,
        session: state.session,
        state,
        className,
        onSubmit: () => {
            return instance.submit();
        },
        children: [
            createElement(ErrorSummary, {
                key: 'errors',
                errors: state.errors.form,
            }),
            state.lastSubmitResult?.messages.notice ? createElement('div', {
                key: 'notice',
                className: 'formie-react-notice',
                role: 'status',
                tabIndex: -1,
                'data-formie-form-message': true,
                // Formie sanitizes rich completion messages before placing them in the client contract.
                dangerouslySetInnerHTML: { __html: state.lastSubmitResult.messages.notice },
            }) : null,
            shouldRenderStandaloneError ? createElement('div', {
                key: 'error',
                className: 'formie-react-error',
                role: 'alert',
                tabIndex: -1,
                'data-formie-form-message': true,
            }, errorMessage) : null,
            progressPosition === 'start' ? renderProgress(state) : null,
            pageShown ? createElement(PageComponent, {
                key: page.id,
                page,
                state,
                children: [
                    ...page.rows.map((row, index) => {
                        return createElement(ConfigRow, {
                            key: `${page.id}:${index}`,
                            row,
                            rowIndex: index,
                        });
                    }),
                    createElement(ConfigPageActions, { key: 'actions' }),
                ],
            }) : null,
            progressPosition === 'end' ? renderProgress(state) : null,
        ],
    });
}

export type DefinitionFormViewProps = {
    source: FormieDefinitionSource;
    components?: FormieReactComponents;
    fieldComponents?: Partial<Record<string, (props: FormieFieldComponentProps) => ReactNode>>;
    slots?: Partial<Record<string, (props: FormieSlotComponentProps) => ReactNode>>;
    className?: string;
    onMount?: (instance: ClientFormInstance) => void;
    onReady?: (instance: ClientFormInstance) => void;
    onUnmount?: () => void;
    onResult?: (result: ClientSubmitResult) => void;
    onSuccess?: (result: ClientSubmitResult) => void;
    onError?: (result: ClientSubmitResult) => void;
    onSubmitResult?: (result: ClientSubmitResult) => void;
    onSubmitSuccess?: (result: ClientSubmitResult) => void;
    onSubmitError?: (result: ClientSubmitResult) => void;
    onEvent?: (event: FormieReactEvent) => void;
};

function invokeDistinctCallbacks<Args extends unknown[]>(
    primary: ((...args: Args) => void) | undefined,
    alias: ((...args: Args) => void) | undefined,
    ...args: Args
): void {
    primary?.(...args);

    if (alias && alias !== primary) {
        alias(...args);
    }
}

export function DefinitionFormView({
    source,
    components = {},
    fieldComponents = {},
    slots = {},
    className,
    onMount,
    onReady,
    onUnmount,
    onResult,
    onSuccess,
    onError,
    onSubmitResult,
    onSubmitSuccess,
    onSubmitError,
    onEvent,
}: DefinitionFormViewProps) {
    const [instance, setInstance] = useState<ClientFormInstance | null>(null);
    const [state, setState] = useState<ClientFormState | null>(null);
    const [error, setError] = useState<Error | null>(null);
    const onMountRef = useRef(onMount);
    const onReadyRef = useRef(onReady);
    const onUnmountRef = useRef(onUnmount);
    const onResultRef = useRef(onResult);
    const onSuccessRef = useRef(onSuccess);
    const onErrorRef = useRef(onError);
    const onSubmitResultRef = useRef(onSubmitResult);
    const onSubmitSuccessRef = useRef(onSubmitSuccess);
    const onSubmitErrorRef = useRef(onSubmitError);
    const onEventRef = useRef(onEvent);
    const sourceKey = useMemo(() => {
        return stableSerialize(source);
    }, [source]);
    const sourceRef = useRef(source);
    useEffect(() => {
        onMountRef.current = onMount;
    }, [onMount]);
    useEffect(() => {
        onReadyRef.current = onReady;
    }, [onReady]);

    useEffect(() => {
        onUnmountRef.current = onUnmount;
    }, [onUnmount]);

    useEffect(() => {
        onResultRef.current = onResult;
    }, [onResult]);

    useEffect(() => {
        onSuccessRef.current = onSuccess;
    }, [onSuccess]);

    useEffect(() => {
        onErrorRef.current = onError;
    }, [onError]);

    useEffect(() => {
        onSubmitResultRef.current = onSubmitResult;
    }, [onSubmitResult]);

    useEffect(() => {
        onSubmitSuccessRef.current = onSubmitSuccess;
    }, [onSubmitSuccess]);

    useEffect(() => {
        onSubmitErrorRef.current = onSubmitError;
    }, [onSubmitError]);

    useEffect(() => {
        onEventRef.current = onEvent;
    }, [onEvent]);

    useEffect(() => {
        sourceRef.current = source;
    }, [source, sourceKey]);

    useEffect(() => {
        let disposed = false;
        let cleanup = () => undefined;

        const load = async() => {
            try {
                const envelope = await resolveDefinitionEnvelope(sourceRef.current);
                const transport = resolveDefinitionTransport(sourceRef.current);

                const nextInstance = createClientFormInstance({
                    envelope,
                    transport,
                });

                if (disposed) {
                    await nextInstance.destroy();
                    return;
                }

                setError(null);
                setInstance(nextInstance);
                setState(nextInstance.getState());
                onMountRef.current?.(nextInstance);
                onReadyRef.current?.(nextInstance);

                const unsubs = [
                    nextInstance.subscribe((nextState) => {
                        setState(nextState);
                    }),
                    nextInstance.on('formie:submit:result', (payload) => {
                        const result = payload as ClientSubmitResult;
                        invokeDistinctCallbacks(onSubmitResultRef.current, onResultRef.current, result);

                        if (result.success) {
                            invokeDistinctCallbacks(onSubmitSuccessRef.current, onSuccessRef.current, result);
                        } else {
                            invokeDistinctCallbacks(onSubmitErrorRef.current, onErrorRef.current, result);
                        }
                    }),
                    ...CLIENT_FORM_EVENT_NAMES.map((eventName) => {
                        return nextInstance.on(eventName, (payload) => {
                            onEventRef.current?.({
                                name: eventName,
                                payload,
                            });
                        });
                    }),
                ];

                cleanup = () => {
                    unsubs.forEach((unsubscribe) => unsubscribe());
                    void nextInstance.destroy();
                    onUnmountRef.current?.();
                };
            } catch (loadError) {
                if (!disposed) {
                    setError(loadError as Error);
                }
            }
        };

        void load();

        return () => {
            disposed = true;
            cleanup();
        };
    }, [sourceKey]);

    const contextValue = useMemo<FormieDefinitionContextValue | null>(() => {
        if (!instance || !state) {
            return null;
        }

        return {
            instance,
            state,
            components,
            fieldComponents,
            slots,
        };
    }, [components, fieldComponents, instance, slots, state]);

    if (error) {
        return createElement('div', {
            className: 'formie-react-error',
        }, error.message);
    }

    if (!contextValue) {
        return createElement('div', {
            className: 'formie-react-loading',
        }, 'Loading form...');
    }

    return createElement(FormieDefinitionContext.Provider, {
        value: contextValue,
        children: createElement(BrowserModuleHost, { children: createElement(ConfigRenderer, { className }) }),
    });
}

export function useFormie() {
    const context = useDefinitionContext();

    return {
        definition: context.state.definition,
        session: context.state.session,
        state: context.state,
        instance: context.instance,
    };
}

export function useFormieField(fieldId: string) {
    const context = useDefinitionContext();
    const field = context.state.definition.pages
        .flatMap((page) => page.rows)
        .flatMap((row) => row.fields)
        .find((candidate) => candidate.id === fieldId);

    return {
        field,
        value: context.state.values[fieldId],
        errors: context.state.errors.fields[fieldId] || [],
        hidden: context.state.fieldStates[fieldId]?.hidden === true,
        disabled: context.state.fieldStates[fieldId]?.disabled === true,
        setValue(value: unknown) {
            if (!field) {
                return;
            }

            context.instance.setValue(field.id, value);
        },
    };
}

export function useFormiePage(pageId: string) {
    const context = useDefinitionContext();
    const page = context.state.definition.pages.find((item) => item.id === pageId) || null;

    return {
        page,
        isCurrent: context.state.currentPageId === pageId,
        hidden: context.state.pageStates[pageId]?.hidden === true,
    };
}

export function useFormieInstance() {
    return useDefinitionContext().instance;
}

export function useFormieSlot(key: string) {
    const context = useDefinitionContext();

    return context.slots[key] || null;
}
