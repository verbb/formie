import {
    FRONTEND_CLIENT_EVENT_NAMES,
    compositePartDefinitions,
    createFrontendFormInstance,
    createGraphqlFrontendTransport,
    createRepeaterRowValue,
    createRestFrontendTransport,
    isCompositeField,
    isFileField,
    isKnownFrontendFieldType,
    isRepeatableField,
    loadFrontendEnvelope,
    loadGraphqlFrontendEnvelope,
    repeaterRowDefinitions,
    type FrontendFieldDefinition,
    type FrontendFieldType,
    type FrontendFormDefinition,
    type FrontendFormEnvelope,
    type FrontendFormSession,
    type FrontendFormInstance,
    type FrontendFormState,
    type FrontendSubmitResult,
} from '@verbb/formie-core';
import {
    computed,
    defineComponent,
    h,
    inject,
    onBeforeUnmount,
    onMounted,
    provide,
    ref,
    shallowRef,
    watch,
    type Component,
    type ComputedRef,
    type PropType,
    type Ref,
    type ShallowRef,
    type VNode,
} from 'vue';
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

export type FormieVueEvent = {
    name: string;
    payload: unknown;
};

export type FormieFormComponentProps = {
    definition: FrontendFormDefinition;
    session: FrontendFormSession;
    state: FrontendFormState;
    className?: string;
    onSubmit: () => void;
};

export type FormiePageComponentProps = {
    page: FrontendFormDefinition['pages'][number];
    state: FrontendFormState;
};

export type FormieFieldProps = {
    field: FrontendFieldDefinition;
    errors: string[];
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
    setValue: (value: unknown) => void;
};

export type FormieSlotComponentProps = {
    slotKey: string;
    attributes?: Record<string, unknown>;
};

export type FormieVueComponents = {
    Form?: Component;
    Page?: Component;
    Field?: Component;
    ErrorSummary?: Component;
};

type FormieDefinitionContextValue = {
    instance: ShallowRef<FrontendFormInstance | null>;
    state: ShallowRef<FrontendFormState | null>;
    components: ComputedRef<FormieVueComponents>;
    fieldComponents: ComputedRef<Partial<Record<string, Component>>>;
    slots: ComputedRef<Partial<Record<string, Component>>>;
};

const FORMIE_DEFINITION_CONTEXT = Symbol('formie-definition-context');

const fieldComponentProps = {
    field: {
        type: Object as PropType<FrontendFieldDefinition>,
        required: true,
    },
    value: {
        type: null as unknown as PropType<unknown>,
        default: undefined,
    },
    errors: {
        type: Array as PropType<string[]>,
        default: () => [],
    },
    errorKey: {
        type: String,
        required: true,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    hidden: {
        type: Boolean,
        default: false,
    },
    setValue: {
        type: Function as PropType<(value: unknown) => void>,
        required: true,
    },
} as const;

function useDefinitionContext(): FormieDefinitionContextValue {
    const context = inject<FormieDefinitionContextValue>(FORMIE_DEFINITION_CONTEXT);

    if (!context) {
        throw new Error('Formie definition composables must be used within a client-rendered <FormieForm>.');
    }

    return context;
}

function isInlineDefinitionSource(source: FormieDefinitionSource): source is Extract<FormieDefinitionSource, { definition: FrontendFormEnvelope }> {
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

function flattenFields(definition: FrontendFormDefinition): FrontendFieldDefinition[] {
    return definition.pages.flatMap((page) => page.rows).flatMap((row) => row.fields);
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

function resolveFieldModule(field: FrontendFieldDefinition, definition: FrontendFormDefinition, capability: string) {
    const refs = new Set(field.moduleRefs || []);

    return definition.modules.find((module) => {
        return refs.has(module.id) && module.capability === capability;
    }) || null;
}

function renderSlotWrapper(context: FormieDefinitionContextValue, slotKey: string, child: VNode | null, attributes?: Record<string, unknown>) {
    if (!child) {
        return null;
    }

    const SlotComponent = context.slots.value[slotKey];

    if (!SlotComponent) {
        return child;
    }

    return h(SlotComponent, {
        slotKey,
        attributes,
    }, {
        default: () => [child],
    });
}

const DefaultErrorSummary = defineComponent({
    name: 'FormieVueDefaultErrorSummary',
    props: {
        errors: {
            type: Array as PropType<string[]>,
            required: true,
        },
    },
    setup(props) {
        return () => {
            if (props.errors.length === 0) {
                return null;
            }

            return h('div', {
                class: 'formie-vue-errors',
            }, [
                h('ul', null, props.errors.map((error, index) => {
                    return h('li', {
                        key: `${error}:${index}`,
                    }, error);
                })),
            ]);
        };
    },
});

const DefaultField = defineComponent({
    name: 'FormieVueDefaultField',
    props: {
        field: {
            type: Object as PropType<FrontendFieldDefinition>,
            required: true,
        },
        errors: {
            type: Array as PropType<string[]>,
            required: true,
        },
    },
    setup(props, componentContext) {
        const context = useDefinitionContext();

        return () => {
            const children = componentContext.slots.default?.() || [];

            return h('div', {
                class: 'formie-vue-field',
                'data-field-type': props.field.type,
            }, [
                props.field.label
                    ? renderSlotWrapper(context, 'label', h('label', {
                        class: 'formie-vue-label',
                    }, props.field.label), {
                        class: 'formie-vue-label',
                    })
                    : null,
                props.field.instructions
                    ? renderSlotWrapper(context, 'instructions', h('div', {
                        class: 'formie-vue-description',
                    }, props.field.instructions), {
                        class: 'formie-vue-description',
                    })
                    : null,
                renderSlotWrapper(context, 'input', h('div', {
                    class: 'formie-vue-input',
                }, children), {
                    class: 'formie-vue-input',
                }),
                props.errors.length > 0
                    ? renderSlotWrapper(context, 'errors', h('ul', {
                        class: 'formie-vue-field-errors',
                    }, props.errors.map((error, index) => {
                        return h('li', {
                            key: `${error}:${index}`,
                        }, error);
                    })), {
                        class: 'formie-vue-field-errors',
                    })
                    : null,
            ]);
        };
    },
});

const DefaultForm = defineComponent({
    name: 'FormieVueDefaultForm',
    props: {
        definition: {
            type: Object as PropType<FrontendFormDefinition>,
            required: true,
        },
        session: {
            type: Object as PropType<FrontendFormSession>,
            required: true,
        },
        state: {
            type: Object as PropType<FrontendFormState>,
            required: true,
        },
        className: {
            type: String,
            default: undefined,
        },
        onSubmit: {
            type: Function as PropType<() => void>,
            required: true,
        },
    },
    setup(props, componentContext) {
        return () => h('form', {
            class: props.className,
            onSubmit: (event: Event) => {
                event.preventDefault();
                props.onSubmit();
            },
            'data-formie-definition': props.definition.handle,
            'data-formie-render-id': props.session.tokens.render,
        }, componentContext.slots.default?.() || []);
    },
});

const DefaultPage = defineComponent({
    name: 'FormieVueDefaultPage',
    props: {
        page: {
            type: Object as PropType<FrontendFormDefinition['pages'][number]>,
            required: true,
        },
        state: {
            type: Object as PropType<FrontendFormState>,
            required: true,
        },
    },
    setup(props, componentContext) {
        return () => h('section', {
            'data-page-id': props.page.id,
            class: 'formie-vue-page',
        }, componentContext.slots.default?.() || []);
    },
});

const SignatureFieldInput = defineComponent({
    name: 'FormieVueSignatureFieldInput',
    props: fieldComponentProps,
    setup(props) {
        const context = useDefinitionContext();
        const canvasRef = ref<HTMLCanvasElement | null>(null);
        const padRef = shallowRef<{
            clear: () => void;
            isEmpty: () => boolean;
            toDataURL: () => string;
            fromDataURL: (value: string) => void;
            addEventListener?: (eventName: string, callback: () => void) => void;
            removeEventListener?: (eventName: string, callback: () => void) => void;
        } | null>(null);
        const loadError = ref<string | null>(null);
        const moduleConfig = computed(() => resolveFieldModule(props.field, context.state.value?.definition || {
            modules: [],
        } as FrontendFormDefinition, 'draw-signature')?.config);
        const backgroundColor = computed(() => {
            const options = moduleConfig.value?.options as Record<string, unknown> | undefined;

            return typeof options?.backgroundColor === 'string' ? options.backgroundColor : '#ffffff';
        });
        const penColor = computed(() => {
            const options = moduleConfig.value?.options as Record<string, unknown> | undefined;

            return typeof options?.penColor === 'string' ? options.penColor : '#000000';
        });
        const penWeight = computed(() => {
            const options = moduleConfig.value?.options as Record<string, unknown> | undefined;

            return Number(options?.penWeight ?? 2) || 2;
        });
        const serializedValue = computed(() => {
            return typeof props.value === 'string' ? props.value : '';
        });

        let disposed = false;
        let removeStrokeListener = () => undefined;
        let removeResizeListener = () => undefined;

        onMounted(() => {
            const setup = async() => {
                try {
                    const canvas = canvasRef.value;

                    if (!canvas) {
                        return;
                    }

                    const { default: SignaturePad } = await import('signature_pad');

                    if (disposed) {
                        return;
                    }

                    const pad = new SignaturePad(canvas, {
                        backgroundColor: backgroundColor.value,
                        penColor: penColor.value,
                        minWidth: penWeight.value,
                        maxWidth: penWeight.value,
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
                        const renderContext = canvas.getContext('2d');

                        canvas.width = width * ratio;
                        canvas.height = height * ratio;
                        canvas.style.height = `${height}px`;

                        if (renderContext) {
                            renderContext.setTransform(1, 0, 0, 1, 0, 0);
                            renderContext.scale(ratio, ratio);
                        }

                        pad.clear();
                    };

                    const handleEnd = () => {
                        props.setValue(pad.isEmpty() ? '' : pad.toDataURL());
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

                    padRef.value = pad;
                    loadError.value = null;
                } catch (error) {
                    if (!disposed) {
                        loadError.value = (error as Error).message || 'Unable to load signature support.';
                    }
                }
            };

            void setup();
        });

        onBeforeUnmount(() => {
            disposed = true;
            removeStrokeListener();
            removeResizeListener();
            padRef.value = null;
        });

        watch(serializedValue, (nextValue) => {
            const pad = padRef.value;

            if (!pad) {
                return;
            }

            if (!nextValue) {
                if (!pad.isEmpty()) {
                    pad.clear();
                }

                return;
            }

            try {
                pad.fromDataURL(nextValue);
            } catch {
                // Ignore invalid persisted data; the field still remains interactive.
            }
        }, { immediate: true });

        return () => h('div', {
            class: 'formie-vue-signature',
        }, [
            h('canvas', {
                key: 'canvas',
                ref: canvasRef,
                'data-formie-signature-canvas': true,
                style: props.disabled ? { pointerEvents: 'none' } : undefined,
            }),
            h('button', {
                key: 'clear',
                type: 'button',
                disabled: props.disabled,
                'data-formie-signature-clear': true,
                onClick: () => {
                    padRef.value?.clear();
                    props.setValue('');
                },
            }, 'Clear'),
            loadError.value ? h('div', {
                key: 'error',
                class: 'formie-vue-unsupported',
            }, loadError.value) : null,
        ]);
    },
});

const CompositeFieldInput = defineComponent({
    name: 'FormieVueCompositeFieldInput',
    props: fieldComponentProps,
    setup(props) {
        const context = useDefinitionContext();

        return () => {
            const state = context.state.value;

            if (!state) {
                return null;
            }

            const parts = compositePartDefinitions(props.field);
            const currentValue = props.value && typeof props.value === 'object' ? props.value as Record<string, unknown> : {};

            if (parts.length === 0) {
                return h('div', {
                    class: 'formie-vue-unsupported',
                }, `Unsupported field type: ${props.field.type}`);
            }

            return h('div', {
                class: 'formie-vue-name-grid',
            }, parts.filter((part) => part.meta?.hidden !== true).map((part) => {
                const partErrorKey = `${props.errorKey}.${part.handle}`;

                return h(ConfigFieldNode, {
                    key: `${props.field.id}:${part.handle}`,
                    field: part,
                    value: currentValue[part.handle],
                    errors: state.errors.fields[partErrorKey] || [],
                    errorKey: partErrorKey,
                    disabled: props.disabled || part.meta?.disabled === true,
                    hidden: false,
                    setValue(nextValue: unknown) {
                        props.setValue({
                            ...currentValue,
                            [part.handle]: nextValue,
                        });
                    },
                });
            }));
        };
    },
});

const FileFieldInput = defineComponent({
    name: 'FormieVueFileFieldInput',
    props: fieldComponentProps,
    setup(props) {
        return () => {
            const contract = props.field.input;
            const files = Array.isArray(props.value) ? props.value : [];
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

            return h('div', {
                class: 'formie-vue-file',
            }, [
                h('input', {
                    key: 'input',
                    type: 'file',
                    disabled: props.disabled,
                    multiple,
                    onChange: (event: Event) => {
                        const target = event.target as HTMLInputElement;
                        props.setValue(Array.from(target.files || []));
                    },
                }),
                items.length > 0 ? h('ul', {
                    key: 'summary',
                    class: 'formie-vue-field-errors',
                }, items.map((item, index) => {
                    return h('li', {
                        key: `${item}:${index}`,
                    }, item);
                })) : null,
            ]);
        };
    },
});

const ConfigFieldNode = defineComponent({
    name: 'FormieVueConfigFieldNode',
    props: fieldComponentProps,
    setup(props) {
        const context = useDefinitionContext();

        return () => {
            const state = context.state.value;

            if (!state) {
                return null;
            }

            const fieldState = state.fieldStates[props.field.id];
            const hidden = fieldState?.hidden === true;

            if (hidden) {
                return null;
            }

            const rendererType = resolveFieldRendererType(props.field);
            const renderer = context.fieldComponents.value[props.field.type] || context.fieldComponents.value[rendererType] || DefaultFieldInput;
            const FieldComponent = context.components.value.Field || DefaultField;

            return h(FieldComponent, {
                field: props.field,
                errors: props.errors,
            }, {
                default: () => [
                    h(renderer, {
                        field: props.field,
                        value: props.value,
                        errors: props.errors,
                        errorKey: props.errorKey,
                        disabled: props.disabled,
                        hidden,
                        setValue: props.setValue,
                    }),
                ],
            });
        };
    },
});

const ConfigField = defineComponent({
    name: 'FormieVueConfigField',
    props: {
        field: {
            type: Object as PropType<FrontendFieldDefinition>,
            required: true,
        },
    },
    setup(props) {
        const context = useDefinitionContext();

        return () => {
            const state = context.state.value;
            const instance = context.instance.value;

            if (!state || !instance) {
                return null;
            }

            const fieldState = state.fieldStates[props.field.id];

            return h(ConfigFieldNode, {
                field: props.field,
                value: state.values[props.field.id],
                errors: state.errors.fields[props.field.id] || [],
                errorKey: props.field.id,
                disabled: fieldState?.disabled === true,
                hidden: fieldState?.hidden === true,
                setValue(nextValue: unknown) {
                    instance.setValue(props.field.id, nextValue);
                },
            });
        };
    },
});

const ConfigRow = defineComponent({
    name: 'FormieVueConfigRow',
    props: {
        row: {
            type: Object as PropType<FrontendFormDefinition['pages'][number]['rows'][number]>,
            required: true,
        },
        rowIndex: {
            type: Number,
            required: true,
        },
        values: {
            type: Object as PropType<Record<string, unknown> | undefined>,
            default: undefined,
        },
        errorPrefix: {
            type: String,
            default: undefined,
        },
        disabled: {
            type: Boolean,
            default: false,
        },
        setFieldValue: {
            type: Function as PropType<((field: FrontendFieldDefinition, nextValue: unknown) => void) | undefined>,
            default: undefined,
        },
    },
    setup(props) {
        const context = useDefinitionContext();

        return () => {
            const state = context.state.value;

            if (!state) {
                return null;
            }

            return h('div', {
                class: 'formie-vue-row',
            }, props.row.fields.map((field, fieldIndex) => {
                if (!props.values || !props.setFieldValue) {
                    return h(ConfigField, {
                        key: field.id || `${props.rowIndex}:${fieldIndex}`,
                        field,
                    });
                }

                const errorKey = `${props.errorPrefix}.${field.handle}`;

                return h(ConfigFieldNode, {
                    key: field.id || `${props.rowIndex}:${fieldIndex}`,
                    field,
                    value: props.values[field.handle],
                    errors: state.errors.fields[errorKey] || [],
                    errorKey,
                    disabled: props.disabled === true || state.fieldStates[field.id]?.disabled === true,
                    hidden: state.fieldStates[field.id]?.hidden === true,
                    setValue(nextValue: unknown) {
                        props.setFieldValue?.(field, nextValue);
                    },
                });
            }));
        };
    },
});

const RepeaterFieldInput = defineComponent({
    name: 'FormieVueRepeaterFieldInput',
    props: fieldComponentProps,
    setup(props) {
        const context = useDefinitionContext();

        return () => {
            const state = context.state.value;

            if (!state) {
                return null;
            }

            const rows = repeaterRowDefinitions(props.field);
            const currentRows = Array.isArray(props.value) ? props.value as Array<Record<string, unknown>> : [];
            const contract = props.field.input;
            const minRows = Number(contract.minRows ?? 0) || 0;
            const maxRows = Number(contract.maxRows ?? 0) || 0;
            const canAddRow = !props.disabled && (maxRows <= 0 || currentRows.length < maxRows);

            if (rows.length === 0) {
                return h('div', {
                    class: 'formie-vue-unsupported',
                }, 'Unsupported repeater field.');
            }

            return h('div', {
                class: 'formie-vue-repeater',
                'data-formie-repeater-container': true,
            }, [
                ...currentRows.map((rowValue, rowIndex) => {
                    const itemKey = `${props.field.id}:${rowIndex}`;

                    return h('div', {
                        key: itemKey,
                        class: 'formie-vue-repeater-item',
                        'data-formie-repeater-item': true,
                    }, [
                        ...rows.map((row, nestedRowIndex) => {
                            return h(ConfigRow, {
                                key: `${itemKey}:${nestedRowIndex}`,
                                row,
                                rowIndex: nestedRowIndex,
                                values: rowValue,
                                errorPrefix: `${props.errorKey}.${rowIndex}`,
                                disabled: props.disabled,
                                setFieldValue(rowField: FrontendFieldDefinition, nextValue: unknown) {
                                    const nextRows = currentRows.map((candidate, candidateIndex) => {
                                        if (candidateIndex !== rowIndex) {
                                            return candidate;
                                        }

                                        return {
                                            ...candidate,
                                            [rowField.handle]: nextValue,
                                        };
                                    });

                                    props.setValue(nextRows);
                                },
                            });
                        }),
                        h('button', {
                            key: 'remove',
                            type: 'button',
                            disabled: props.disabled || (minRows > 0 && currentRows.length <= minRows),
                            'data-formie-repeater-remove': true,
                            onClick: () => {
                                props.setValue(currentRows.filter((_, candidateIndex) => candidateIndex !== rowIndex));
                            },
                        }, 'Remove'),
                    ]);
                }),
                h('button', {
                    key: 'add',
                    type: 'button',
                    disabled: !canAddRow,
                    'data-formie-repeater-add': props.field.handle,
                    onClick: () => {
                        props.setValue([...currentRows, createRepeaterRowValue(props.field)]);
                    },
                }, String(contract.addLabel ?? 'Add another row')),
                state.errors.fields[props.errorKey] && state.errors.fields[props.errorKey].length > 0
                    ? h('ul', {
                        key: 'errors',
                        class: 'formie-vue-field-errors',
                    }, state.errors.fields[props.errorKey].map((message, index) => {
                        return h('li', {
                            key: `${message}:${index}`,
                        }, message);
                    }))
                    : null,
            ]);
        };
    },
});

function renderNestedFieldInput(field: FrontendFieldDefinition, value: unknown, disabled: boolean, setValue: (value: unknown) => void) {
    const contract = field.input;

    if (field.type === 'multi-line-text') {
        return h('textarea', {
            value: typeof value === 'string' ? value : '',
            disabled,
            placeholder: typeof contract.placeholder === 'string' ? contract.placeholder : undefined,
            onInput: (event: Event) => {
                const target = event.target as HTMLTextAreaElement;
                setValue(target.value);
            },
        });
    }

    if (field.type === 'dropdown') {
        const options = Array.isArray(contract.options) ? contract.options as Array<Record<string, unknown>> : [];
        const multiple = contract.multiple === true;

        return h('select', {
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

            return h('option', {
                key: `${field.id}:${optionValue}`,
                value: optionValue,
                disabled: option.disabled === true,
            }, String(option.label ?? optionValue));
        }));
    }

    const inputType = typeof contract.inputType === 'string'
        ? contract.inputType
        : (field.type === 'email' ? 'email' : (field.type === 'phone' ? 'tel' : (field.type === 'number' ? 'number' : 'text')));

    return h('input', {
        type: inputType,
        value: typeof value === 'string' || typeof value === 'number' ? String(value) : '',
        disabled,
        placeholder: typeof contract.placeholder === 'string' ? contract.placeholder : undefined,
        onInput: (event: Event) => {
            const target = event.target as HTMLInputElement;

            if (inputType === 'number') {
                const nextValue = target.valueAsNumber;
                setValue(Number.isFinite(nextValue) ? nextValue : '');
                return;
            }

            setValue(target.value);
        },
    });
}

const DefaultFieldInput = defineComponent({
    name: 'FormieVueDefaultFieldInput',
    props: fieldComponentProps,
    setup(props) {
        return () => {
            const contract = props.field.input;
            const rendererType = resolveFieldRendererType(props.field);

            if (isCompositeField(props.field)) {
                return h(CompositeFieldInput, props);
            }

            if (isRepeatableField(props.field)) {
                return h(RepeaterFieldInput, props);
            }

            if (isFileField(props.field)) {
                return h(FileFieldInput, props);
            }

            if (rendererType === 'signature') {
                return h(SignatureFieldInput, props);
            }

            if (rendererType === 'multi-line-text' || rendererType === 'dropdown') {
                return renderNestedFieldInput(props.field, props.value, props.disabled, props.setValue);
            }

            if (rendererType === 'radio') {
                const options = Array.isArray(contract.options) ? contract.options as Array<Record<string, unknown>> : [];

                return h('div', {
                    class: 'formie-vue-choices',
                }, options.map((option) => {
                    const optionValue = String(option.value ?? '');
                    const optionDisabled = props.disabled || option.disabled === true;

                    return h('label', {
                        key: `${props.field.id}:${optionValue}`,
                    }, [
                        h('input', {
                            key: 'input',
                            type: 'radio',
                            checked: props.value === optionValue,
                            disabled: optionDisabled,
                            onChange: () => {
                                props.setValue(optionValue);
                            },
                        }),
                        h('span', {
                            key: 'label',
                        }, String(option.label ?? optionValue)),
                    ]);
                }));
            }

            if (rendererType === 'checkboxes') {
                const options = Array.isArray(contract.options) ? contract.options as Array<Record<string, unknown>> : [];
                const selectedValues = Array.isArray(props.value) ? props.value.map((item) => String(item)) : [];

                return h('div', {
                    class: 'formie-vue-choices',
                }, options.map((option) => {
                    const optionValue = String(option.value ?? '');
                    const checked = selectedValues.includes(optionValue);
                    const optionDisabled = props.disabled || option.disabled === true;

                    return h('label', {
                        key: `${props.field.id}:${optionValue}`,
                    }, [
                        h('input', {
                            key: 'input',
                            type: 'checkbox',
                            checked,
                            disabled: optionDisabled,
                            onChange: () => {
                                const nextValues = checked
                                    ? selectedValues.filter((item) => item !== optionValue)
                                    : [...selectedValues, optionValue];

                                props.setValue(nextValues);
                            },
                        }),
                        h('span', {
                            key: 'label',
                        }, String(option.label ?? optionValue)),
                    ]);
                }));
            }

            if (rendererType === 'agree') {
                const descriptionHtml = typeof contract.descriptionHtml === 'string' ? contract.descriptionHtml : null;

                return h('label', {
                    class: 'formie-vue-boolean',
                }, [
                    h('input', {
                        key: 'input',
                        type: 'checkbox',
                        checked: props.value === true,
                        disabled: props.disabled,
                        onChange: (event: Event) => {
                            const target = event.target as HTMLInputElement;
                            props.setValue(target.checked);
                        },
                    }),
                    descriptionHtml
                        ? h('span', {
                            key: 'description',
                            innerHTML: descriptionHtml,
                        })
                        : h('span', {
                            key: 'description',
                        }, props.field.label),
                ]);
            }

            if (!isKnownFrontendFieldType(rendererType)) {
                return h('div', {
                    class: 'formie-vue-unsupported',
                }, `Unsupported field type: ${String(props.field.meta?.fieldType ?? props.field.type)}`);
            }

            return renderNestedFieldInput(props.field, props.value, props.disabled, props.setValue);
        };
    },
});

const ConfigPageActions = defineComponent({
    name: 'FormieVueConfigPageActions',
    setup() {
        const context = useDefinitionContext();

        return () => {
            const state = context.state.value;
            const instance = context.instance.value;

            if (!state || !instance) {
                return null;
            }

            const page = state.definition.pages.find((item) => item.id === state.currentPageId);

            if (!page) {
                return null;
            }

            const buttons: VNode[] = [];

            page.actions.secondary.forEach((action) => {
                buttons.push(h('button', {
                    key: action.type,
                    type: 'button',
                    onClick: () => {
                        void instance.submit(action.type);
                    },
                }, action.label));
            });

            buttons.push(h('button', {
                key: page.actions.primary.type,
                type: 'submit',
            }, page.actions.primary.label));

            return h('div', {
                class: 'formie-page-actions',
            }, buttons);
        };
    },
});

const ConfigRenderer = defineComponent({
    name: 'FormieVueConfigRenderer',
    props: {
        className: {
            type: String,
            default: undefined,
        },
    },
    setup(props) {
        const context = useDefinitionContext();

        return () => {
            const instance = context.instance.value;
            const state = context.state.value;

            if (!instance || !state) {
                return null;
            }

            const FormComponent = context.components.value.Form || DefaultForm;
            const PageComponent = context.components.value.Page || DefaultPage;
            const ErrorSummary = context.components.value.ErrorSummary || DefaultErrorSummary;
            const page = state.definition.pages.find((item) => {
                return item.id === state.currentPageId && state.pageStates[item.id]?.hidden !== true;
            }) || state.definition.pages.find((item) => state.pageStates[item.id]?.hidden !== true) || state.definition.pages[0];
            const errorMessage = state.lastSubmitResult?.messages.error;
            const shouldRenderStandaloneError = !!errorMessage && !state.errors.form.includes(errorMessage);

            if (!page) {
                return null;
            }

            return h(FormComponent, {
                definition: state.definition,
                session: state.session,
                state,
                className: props.className,
                onSubmit: () => {
                    void instance.submit();
                },
            }, {
                default: () => [
                    h(ErrorSummary, {
                        key: 'errors',
                        errors: state.errors.form,
                    }),
                    state.lastSubmitResult?.messages.notice ? h('div', {
                        key: 'notice',
                        class: 'formie-vue-notice',
                    }, state.lastSubmitResult.messages.notice) : null,
                    shouldRenderStandaloneError ? h('div', {
                        key: 'error',
                        class: 'formie-vue-error',
                    }, errorMessage) : null,
                    h(PageComponent, {
                        key: page.id,
                        page,
                        state,
                    }, {
                        default: () => [
                            ...page.rows.map((row, rowIndex) => h(ConfigRow, {
                                key: `${page.id}:${rowIndex}`,
                                row,
                                rowIndex,
                            })),
                            h(ConfigPageActions, {
                                key: 'actions',
                            }),
                        ],
                    }),
                ],
            });
        };
    },
});

const definitionFormViewProps = {
    source: {
        type: Object as PropType<FormieDefinitionSource>,
        required: true,
    },
    components: {
        type: Object as PropType<FormieVueComponents>,
        default: () => ({}),
    },
    fieldComponents: {
        type: Object as PropType<Partial<Record<string, Component>>>,
        default: () => ({}),
    },
    slots: {
        type: Object as PropType<Partial<Record<string, Component>>>,
        default: () => ({}),
    },
    className: {
        type: String,
        default: undefined,
    },
    onMount: {
        type: Function as PropType<(instance: FrontendFormInstance) => void>,
        default: undefined,
    },
    onReady: {
        type: Function as PropType<(instance: FrontendFormInstance) => void>,
        default: undefined,
    },
    onUnmount: {
        type: Function as PropType<() => void>,
        default: undefined,
    },
    onResult: {
        type: Function as PropType<(result: FrontendSubmitResult) => void>,
        default: undefined,
    },
    onSuccess: {
        type: Function as PropType<(result: FrontendSubmitResult) => void>,
        default: undefined,
    },
    onError: {
        type: Function as PropType<(result: FrontendSubmitResult) => void>,
        default: undefined,
    },
    onSubmitResult: {
        type: Function as PropType<(result: FrontendSubmitResult) => void>,
        default: undefined,
    },
    onSubmitSuccess: {
        type: Function as PropType<(result: FrontendSubmitResult) => void>,
        default: undefined,
    },
    onSubmitError: {
        type: Function as PropType<(result: FrontendSubmitResult) => void>,
        default: undefined,
    },
    onEvent: {
        type: Function as PropType<(event: FormieVueEvent) => void>,
        default: undefined,
    },
} as const;

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

export const DefinitionFormView = defineComponent({
    name: 'FormieVueDefinitionFormView',
    props: definitionFormViewProps,
    emits: ['mount', 'ready', 'unmount', 'result', 'success', 'error', 'submit-result', 'submit-success', 'submit-error', 'event'],
    setup(props, { emit }) {
        const instance = shallowRef<FrontendFormInstance | null>(null);
        const state = shallowRef<FrontendFormState | null>(null);
        const error = ref<Error | null>(null);
        const componentsRef = computed(() => props.components || {});
        const fieldComponentsRef = computed(() => props.fieldComponents || {});
        const slotsRef = computed(() => props.slots || {});
        const contextValue: FormieDefinitionContextValue = {
            instance,
            state,
            components: componentsRef,
            fieldComponents: fieldComponentsRef,
            slots: slotsRef,
        };
        const sourceKey = computed(() => stableSerialize(props.source));

        provide(FORMIE_DEFINITION_CONTEXT, contextValue);

        watch(sourceKey, (_nextValue, _previousValue, onCleanup) => {
            let disposed = false;
            let currentInstance: FrontendFormInstance | null = null;
            let cleanup = () => undefined;

            const load = async() => {
                try {
                    const envelope = await resolveDefinitionEnvelope(props.source);
                    const transport = resolveDefinitionTransport(props.source);
                    const nextInstance = createFrontendFormInstance({
                        envelope,
                        transport,
                    });

                    if (disposed) {
                        await nextInstance.destroy();
                        return;
                    }

                    currentInstance = nextInstance;
                    error.value = null;
                    instance.value = nextInstance;
                    state.value = nextInstance.getState();
                    props.onMount?.(nextInstance);
                    props.onReady?.(nextInstance);
                    emit('mount', nextInstance);
                    emit('ready', nextInstance);

                    const unsubs = [
                        nextInstance.subscribe((nextState) => {
                            state.value = nextState;
                        }),
                        nextInstance.on('formie:submit:result', (payload) => {
                            const result = payload as FrontendSubmitResult;
                            invokeDistinctCallbacks(props.onSubmitResult, props.onResult, result);
                            emit('result', result);
                            emit('submit-result', result);

                            if (result.success) {
                                invokeDistinctCallbacks(props.onSubmitSuccess, props.onSuccess, result);
                                emit('success', result);
                                emit('submit-success', result);
                            } else {
                                invokeDistinctCallbacks(props.onSubmitError, props.onError, result);
                                emit('error', result);
                                emit('submit-error', result);
                            }
                        }),
                        ...FRONTEND_CLIENT_EVENT_NAMES.map((eventName) => {
                            return nextInstance.on(eventName, (payload) => {
                                const event = {
                                    name: eventName,
                                    payload,
                                };

                                props.onEvent?.(event);
                                emit('event', event);
                            });
                        }),
                    ];

                    cleanup = () => {
                        unsubs.forEach((unsubscribe) => unsubscribe());
                        void nextInstance.destroy();
                        if (instance.value === nextInstance) {
                            instance.value = null;
                            state.value = null;
                        }
                        props.onUnmount?.();
                        emit('unmount');
                    };
                } catch (loadError) {
                    if (!disposed) {
                        error.value = loadError as Error;
                    }
                }
            };

            void load();

            onCleanup(() => {
                disposed = true;
                cleanup();
            });
        }, { immediate: true });

        return () => {
            if (error.value) {
                return h('div', {
                    class: 'formie-vue-error',
                }, error.value.message);
            }

            if (!instance.value || !state.value) {
                return h('div', {
                    class: 'formie-vue-loading',
                }, 'Loading form...');
            }

            return h(ConfigRenderer, {
                className: props.className,
            });
        };
    },
});

export function useFormie() {
    const context = useDefinitionContext();

    return {
        definition: computed(() => context.state.value?.definition || null),
        session: computed(() => context.state.value?.session || null),
        state: context.state,
        instance: context.instance,
    };
}

export function useFormieField(fieldId: string) {
    const context = useDefinitionContext();

    const field = computed(() => {
        const definition = context.state.value?.definition;

        if (!definition) {
            return null;
        }

        return flattenFields(definition).find((candidate) => candidate.id === fieldId) || null;
    });

    return {
        field,
        value: computed(() => context.state.value?.values[fieldId]),
        errors: computed(() => context.state.value?.errors.fields[fieldId] || []),
        hidden: computed(() => context.state.value?.fieldStates[fieldId]?.hidden === true),
        disabled: computed(() => context.state.value?.fieldStates[fieldId]?.disabled === true),
        setValue(value: unknown) {
            if (!field.value || !context.instance.value) {
                return;
            }

            context.instance.value.setValue(field.value.id, value);
        },
    };
}

export function useFormiePage(pageId: string) {
    const context = useDefinitionContext();

    return {
        page: computed(() => {
            return context.state.value?.definition.pages.find((item) => item.id === pageId) || null;
        }),
        isCurrent: computed(() => context.state.value?.currentPageId === pageId),
        hidden: computed(() => context.state.value?.pageStates[pageId]?.hidden === true),
    };
}

export function useFormieInstance() {
    return useDefinitionContext().instance;
}

export function useFormieSlot(key: string) {
    const context = useDefinitionContext();

    return computed(() => context.slots.value[key] || null);
}
