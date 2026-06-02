import {
    FRONTEND_CLIENT_EVENT_NAMES,
    compositePartDefinitions,
    createRepeaterRowValue,
    createFrontendFormInstance,
    createGraphqlFrontendTransport,
    createRestFrontendTransport,
    isCompositeField,
    isFileField,
    isKnownFrontendFieldType,
    isRepeatableField,
    loadFrontendEnvelope,
    loadGraphqlFrontendEnvelope,
    repeaterRowDefinitions,
    type FrontendFieldDefinition,
    type FrontendFormDefinition,
    type FrontendFormEnvelope,
    type FrontendFormSession,
    type FrontendFormInstance,
    type FrontendFormState,
    type FrontendSubmitResult,
} from '@verbb/formie-core';
import {
    createContext,
    createElement,
    useContext,
    useEffect,
    useMemo,
    useRef,
    useState,
    type ReactNode,
} from 'react';
import { stableSerialize } from './stable';

export type FormieDefinitionSource =
    | {
        transport: 'rest';
        endpoint: string;
        formHandle: string;
        siteId?: number;
    }
    | {
        transport: 'graphql';
        endpoint: string;
        formHandle: string;
        siteId?: number;
    }
    | {
        definition: FrontendFormEnvelope;
        transport: {
            type: 'rest';
            endpoint: string;
            formHandle: string;
            siteId?: number;
        };
    }
    | {
        definition: FrontendFormEnvelope;
        transport: {
            type: 'graphql';
            endpoint: string;
            formHandle: string;
            siteId?: number;
        };
    };

export type FormieReactEvent = {
    name: string;
    payload: unknown;
};

export type FormieFormComponentProps = {
    definition: FrontendFormDefinition;
    session: FrontendFormSession;
    state: FrontendFormState;
    children?: ReactNode;
    className?: string;
    onSubmit: () => void;
};

export type FormiePageComponentProps = {
    page: FrontendFormDefinition['pages'][number];
    state: FrontendFormState;
    children?: ReactNode;
};

export type FormieFieldProps = {
    field: FrontendFieldDefinition;
    errors: string[];
    children?: ReactNode;
};

export type FormieErrorSummaryProps = {
    errors: string[];
};

export type FormieFieldComponentProps = {
    field: FrontendFieldDefinition;
    value: unknown;
    errors: string[];
    errorKey: string;
    disabled: boolean;
    hidden: boolean;
    setValue(value: unknown): void;
};

export type FormieSlotComponentProps = {
    slotKey: string;
    children?: ReactNode;
    attributes?: Record<string, unknown>;
};

export type FormieReactComponents = {
    Form?: (props: FormieFormComponentProps) => ReactNode;
    Page?: (props: FormiePageComponentProps) => ReactNode;
    Field?: (props: FormieFieldProps) => ReactNode;
    ErrorSummary?: (props: FormieErrorSummaryProps) => ReactNode;
};

type FormieDefinitionContextValue = {
    instance: FrontendFormInstance;
    state: FrontendFormState;
    components: FormieReactComponents;
    fieldComponents: Partial<Record<string, (props: FormieFieldComponentProps) => ReactNode>>;
    slots: Partial<Record<string, (props: FormieSlotComponentProps) => ReactNode>>;
};

const FormieDefinitionContext = createContext<FormieDefinitionContextValue | null>(null);

function isInlineDefinitionSource(source: FormieDefinitionSource): source is Extract<FormieDefinitionSource, { transport: { type: 'rest' | 'graphql' } }> {
    return 'definition' in source;
}

async function resolveDefinitionEnvelope(source: FormieDefinitionSource): Promise<FrontendFormEnvelope> {
    if (isInlineDefinitionSource(source)) {
        return source.definition;
    }

    if (source.transport === 'graphql') {
        return loadGraphqlFrontendEnvelope({
            endpoint: source.endpoint,
            formHandle: source.formHandle,
            siteId: source.siteId,
        });
    }

    return loadFrontendEnvelope({
        endpoint: source.endpoint,
        formHandle: source.formHandle,
        siteId: source.siteId,
    });
}

function resolveDefinitionTransport(source: FormieDefinitionSource) {
    const transportSource = isInlineDefinitionSource(source)
        ? source.transport
        : {
            type: source.transport,
            endpoint: source.endpoint,
            formHandle: source.formHandle,
            siteId: source.siteId,
        };

    if (transportSource.type === 'graphql') {
        return createGraphqlFrontendTransport(transportSource);
    }

    return createRestFrontendTransport(transportSource);
}

function DefaultErrorSummary({ errors }: FormieErrorSummaryProps) {
    if (errors.length === 0) {
        return null;
    }

    return createElement('div', {
        className: 'formie-react-errors',
    }, createElement('ul', null, errors.map((error, index) => {
        return createElement('li', { key: `${error}:${index}` }, error);
    })));
}

function DefaultField({ field, errors, children }: FormieFieldProps) {
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
        });
    };

    return createElement('div', {
        className: 'formie-react-field',
        'data-field-type': field.type,
    }, [
        field.label ? renderSlot('label', createElement('label', {
            key: 'label',
            className: 'formie-react-label',
        }, field.label)) : null,
        field.instructions ? renderSlot('instructions', createElement('div', {
            key: 'instructions',
            className: 'formie-react-description',
        }, field.instructions)) : null,
        renderSlot('input', createElement('div', {
            key: 'input',
            className: 'formie-react-input',
        }, children)),
        errors.length > 0 ? renderSlot('errors', createElement('ul', {
            key: 'errors',
            className: 'formie-react-field-errors',
        }, errors.map((error, index) => {
            return createElement('li', { key: `${error}:${index}` }, error);
        }))) : null,
    ]);
}

function DefaultForm({ definition, session, state, children, className, onSubmit }: FormieFormComponentProps) {
    return createElement('form', {
        className,
        onSubmit: (event: Event) => {
            event.preventDefault();
            onSubmit();
        },
        'data-formie-definition': definition.handle,
        'data-formie-render-id': session.tokens.render,
    }, children);
}

function DefaultPage({ page, children }: FormiePageComponentProps) {
    return createElement('section', {
        'data-page-id': page.id,
        className: 'formie-react-page',
    }, children);
}

function useDefinitionContext(): FormieDefinitionContextValue {
    const context = useContext(FormieDefinitionContext);

    if (!context) {
        throw new Error('Formie definition hooks must be used within a client-rendered <FormieForm />.');
    }

    return context;
}

function isFieldDefinition(candidate: unknown): candidate is FrontendFieldDefinition {
    return !!candidate && typeof candidate === 'object' && 'id' in candidate && 'handle' in candidate && 'type' in candidate;
}

function resolveFieldRendererType(field: FrontendFieldDefinition): FrontendFieldDefinition['type'] {
    if (isKnownFrontendFieldType(field.type)) {
        return field.type;
    }

    const fieldKind = typeof field.input.fieldKind === 'string' ? field.input.fieldKind : null;

    if (fieldKind === 'text') {
        return 'single-line-text';
    }

    if (fieldKind === 'textarea') {
        return 'multi-line-text';
    }

    if (fieldKind === 'boolean') {
        return 'agree';
    }

    if (fieldKind === 'file') {
        return 'file';
    }

    return field.type;
}

function renderNestedFieldInput(field: FrontendFieldDefinition, value: unknown, disabled: boolean, setValue: (value: unknown) => void): ReactNode {
    const contract = field.input;

    if (field.type === 'multi-line-text') {
        return createElement('textarea', {
            value: typeof value === 'string' ? value : '',
            disabled,
            placeholder: typeof contract.placeholder === 'string' ? contract.placeholder : undefined,
            onChange: (event: Event) => {
                const target = event.target as HTMLTextAreaElement;
                setValue(target.value);
            },
        });
    }

    if (field.type === 'dropdown') {
        const options = Array.isArray(contract.options) ? contract.options as Array<Record<string, unknown>> : [];
        const multiple = contract.multiple === true;

        return createElement('select', {
            value: multiple ? undefined : (typeof value === 'string' ? value : ''),
            disabled,
            multiple,
            onChange: (event: Event) => {
                const target = event.target as HTMLSelectElement;

                if (multiple) {
                    setValue(Array.from(target.selectedOptions).map((option) => option.value));
                    return;
                }

                setValue(target.value);
            },
        }, options.map((option) => {
            const optionValue = String(option.value ?? '');

            return createElement('option', {
                key: `${field.id}:${optionValue}`,
                value: optionValue,
                disabled: option.disabled === true,
            }, String(option.label ?? optionValue));
        }));
    }

    const inputType = typeof contract.inputType === 'string'
        ? contract.inputType
        : (field.type === 'email' ? 'email' : (field.type === 'phone' ? 'tel' : (field.type === 'number' ? 'number' : 'text')));

    return createElement('input', {
        type: inputType,
        value: typeof value === 'string' ? value : '',
        disabled,
        placeholder: typeof contract.placeholder === 'string' ? contract.placeholder : undefined,
        onChange: (event: Event) => {
            const target = event.target as HTMLInputElement;
            setValue(target.value);
        },
    });
}

function resolveFieldModule(field: FrontendFieldDefinition, definition: FrontendFormDefinition, capability: string) {
    const refs = new Set(field.moduleRefs || []);

    return definition.modules.find((module) => {
        return refs.has(module.id) && module.capability === capability;
    }) || null;
}

function SignatureFieldInput({
    field,
    value,
    errorKey: _errorKey,
    disabled,
    setValue,
}: Pick<FormieFieldComponentProps, 'field' | 'value' | 'errorKey' | 'disabled' | 'setValue'>) {
    const { state } = useDefinitionContext();
    const canvasRef = useRef<HTMLCanvasElement | null>(null);
    const padRef = useRef<{
        clear: () => void;
        isEmpty: () => boolean;
        toDataURL: () => string;
        fromDataURL: (value: string) => void;
    } | null>(null);
    const [loadError, setLoadError] = useState<string | null>(null);
    const moduleConfig = resolveFieldModule(field, state.definition, 'draw-signature')?.config;
    const backgroundColor = typeof moduleConfig?.options === 'object' && moduleConfig.options && typeof (moduleConfig.options as Record<string, unknown>).backgroundColor === 'string'
        ? String((moduleConfig.options as Record<string, unknown>).backgroundColor)
        : '#ffffff';
    const penColor = typeof moduleConfig?.options === 'object' && moduleConfig.options && typeof (moduleConfig.options as Record<string, unknown>).penColor === 'string'
        ? String((moduleConfig.options as Record<string, unknown>).penColor)
        : '#000000';
    const penWeight = typeof moduleConfig?.options === 'object' && moduleConfig.options
        ? Number((moduleConfig.options as Record<string, unknown>).penWeight ?? 2) || 2
        : 2;
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
            style: disabled ? { pointerEvents: 'none' } : undefined,
        }),
        createElement('button', {
            key: 'clear',
            type: 'button',
            disabled,
            'data-formie-signature-clear': true,
            onClick: () => {
                padRef.current?.clear();
                setValue('');
            },
        }, 'Clear'),
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
    disabled,
    setValue,
}: Pick<FormieFieldComponentProps, 'field' | 'value' | 'errorKey' | 'disabled' | 'setValue'>) {
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
    disabled,
    setValue,
}: Pick<FormieFieldComponentProps, 'field' | 'value' | 'disabled' | 'setValue'>) {
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
            disabled,
            multiple,
            onChange: (event: Event) => {
                const target = event.target as HTMLInputElement;
                setValue(Array.from(target.files || []));
            },
        }),
        items.length > 0 ? createElement('ul', {
            key: 'summary',
            className: 'formie-react-field-errors',
        }, items.map((item, index) => {
            return createElement('li', { key: `${item}:${index}` }, item);
        })) : null,
    ]);
}

function RepeaterFieldInput({
    field,
    value,
    errorKey,
    disabled,
    setValue,
}: Pick<FormieFieldComponentProps, 'field' | 'value' | 'errorKey' | 'disabled' | 'setValue'>) {
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
                    'data-formie-repeater-remove': true,
                    onClick: () => {
                        setValue(currentRows.filter((_, candidateIndex) => candidateIndex !== rowIndex));
                    },
                }, 'Remove'),
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
        }, String(contract.addLabel ?? 'Add another row')),
        state.errors.fields[errorKey] && state.errors.fields[errorKey].length > 0 ? createElement('ul', {
            key: 'errors',
            className: 'formie-react-field-errors',
        }, state.errors.fields[errorKey].map((message, index) => {
            return createElement('li', { key: `${message}:${index}` }, message);
        })) : null,
    ]);
}

function defaultFieldRenderer(props: FormieFieldComponentProps): ReactNode {
    const { field, value, errorKey, disabled, setValue } = props;
    const contract = field.input;
    const rendererType = resolveFieldRendererType(field);

    if (isCompositeField(field)) {
        return createElement(CompositeFieldInput, {
            field,
            value,
            errorKey,
            disabled,
            setValue,
        });
    }

    if (isRepeatableField(field)) {
        return createElement(RepeaterFieldInput, {
            field,
            value,
            errorKey,
            disabled,
            setValue,
        });
    }

    if (isFileField(field)) {
        return createElement(FileFieldInput, {
            field,
            value,
            disabled,
            setValue,
        });
    }

    if (rendererType === 'signature') {
        return createElement(SignatureFieldInput, {
            field,
            value,
            errorKey,
            disabled,
            setValue,
        });
    }

    if (rendererType === 'multi-line-text') {
        return renderNestedFieldInput(field, value, disabled, setValue);
    }

    if (rendererType === 'dropdown') {
        return renderNestedFieldInput(field, value, disabled, setValue);
    }

    if (rendererType === 'radio') {
        const options = Array.isArray(contract.options) ? contract.options as Array<Record<string, unknown>> : [];

        return createElement('div', {
            className: 'formie-react-choices',
        }, options.map((option) => {
            const optionValue = String(option.value ?? '');

            return createElement('label', {
                key: `${field.id}:${optionValue}`,
            }, [
                createElement('input', {
                    key: 'input',
                    type: 'radio',
                    checked: value === optionValue,
                    disabled,
                    onChange: () => {
                        setValue(optionValue);
                    },
                }),
                createElement('span', { key: 'label' }, String(option.label ?? optionValue)),
            ]);
        }));
    }

    if (rendererType === 'checkboxes') {
        const options = Array.isArray(contract.options) ? contract.options as Array<Record<string, unknown>> : [];
        const selectedValues = Array.isArray(value) ? value.map((item) => String(item)) : [];

        return createElement('div', {
            className: 'formie-react-choices',
        }, options.map((option) => {
            const optionValue = String(option.value ?? '');
            const checked = selectedValues.includes(optionValue);

            return createElement('label', {
                key: `${field.id}:${optionValue}`,
            }, [
                createElement('input', {
                    key: 'input',
                    type: 'checkbox',
                    checked,
                    disabled,
                    onChange: () => {
                        const nextValues = checked
                            ? selectedValues.filter((item) => item !== optionValue)
                            : [...selectedValues, optionValue];

                        setValue(nextValues);
                    },
                }),
                createElement('span', { key: 'label' }, String(option.label ?? optionValue)),
            ]);
        }));
    }

    if (rendererType === 'agree') {
        const descriptionHtml = typeof contract.descriptionHtml === 'string' ? contract.descriptionHtml : null;

        return createElement('label', {
            className: 'formie-react-boolean',
        }, [
            createElement('input', {
                key: 'input',
                type: 'checkbox',
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
        ]);
    }

    if (!isKnownFrontendFieldType(rendererType)) {
        return createElement('div', {
            className: 'formie-react-unsupported',
        }, `Unsupported field type: ${String(field.meta?.fieldType ?? field.type)}`);
    }

    return renderNestedFieldInput(field, value, disabled, setValue);
}

function ConfigFieldNode({
    field,
    value,
    errors,
    errorKey,
    disabled,
    setValue,
}: {
    field: FrontendFieldDefinition;
    value: unknown;
    errors: string[];
    errorKey: string;
    disabled: boolean;
    setValue(nextValue: unknown): void;
}) {
    const { components, fieldComponents, state } = useDefinitionContext();
    const fieldState = state.fieldStates[field.id];
    const hidden = fieldState?.hidden === true;

    if (hidden) {
        return null;
    }

    const rendererType = resolveFieldRendererType(field);
    const renderer = fieldComponents[field.type] || fieldComponents[rendererType] || defaultFieldRenderer;
    const Field = components.Field || DefaultField;

    return createElement(Field, {
        field,
        errors,
        children: renderer({
            field,
            value,
            errors,
            errorKey,
            disabled,
            hidden,
            setValue,
        }),
    });
}

function ConfigField({ field }: { field: FrontendFieldDefinition }) {
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
    row: FrontendFormDefinition['pages'][number]['rows'][number];
    rowIndex: number;
    values?: Record<string, unknown>;
    errorPrefix?: string;
    disabled?: boolean;
    setFieldValue?: (field: FrontendFieldDefinition, nextValue: unknown) => void;
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
            disabled: disabled === true || state.fieldStates[field.id]?.disabled === true,
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

    if (!page) {
        return null;
    }

    return createElement(FormComponent, {
        definition: state.definition,
        session: state.session,
        state,
        className,
        onSubmit: () => {
            void instance.submit();
        },
        children: [
            createElement(ErrorSummary, {
                key: 'errors',
                errors: state.errors.form,
            }),
            state.lastSubmitResult?.messages.notice ? createElement('div', {
                key: 'notice',
                className: 'formie-react-notice',
            }, state.lastSubmitResult.messages.notice) : null,
            shouldRenderStandaloneError ? createElement('div', {
                key: 'error',
                className: 'formie-react-error',
            }, errorMessage) : null,
            createElement(PageComponent, {
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
            }),
        ],
    });
}

export type DefinitionFormViewProps = {
    source: FormieDefinitionSource;
    components?: FormieReactComponents;
    fieldComponents?: Partial<Record<string, (props: FormieFieldComponentProps) => ReactNode>>;
    slots?: Partial<Record<string, (props: FormieSlotComponentProps) => ReactNode>>;
    className?: string;
    onMount?: (instance: FrontendFormInstance) => void;
    onReady?: (instance: FrontendFormInstance) => void;
    onUnmount?: () => void;
    onResult?: (result: FrontendSubmitResult) => void;
    onSuccess?: (result: FrontendSubmitResult) => void;
    onError?: (result: FrontendSubmitResult) => void;
    onSubmitResult?: (result: FrontendSubmitResult) => void;
    onSubmitSuccess?: (result: FrontendSubmitResult) => void;
    onSubmitError?: (result: FrontendSubmitResult) => void;
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
    const [instance, setInstance] = useState<FrontendFormInstance | null>(null);
    const [state, setState] = useState<FrontendFormState | null>(null);
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

                const nextInstance = createFrontendFormInstance({
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
                        const result = payload as FrontendSubmitResult;
                        invokeDistinctCallbacks(onSubmitResultRef.current, onResultRef.current, result);

                        if (result.success) {
                            invokeDistinctCallbacks(onSubmitSuccessRef.current, onSuccessRef.current, result);
                        } else {
                            invokeDistinctCallbacks(onSubmitErrorRef.current, onErrorRef.current, result);
                        }
                    }),
                    ...FRONTEND_CLIENT_EVENT_NAMES.map((eventName) => {
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
        children: createElement(ConfigRenderer, {
            className,
        }),
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
