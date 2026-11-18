import { clientActionAllowed } from '@verbb/formie-core';
import { mountClientRenderedModules } from '@verbb/formie-browser';
import {
    CLIENT_FORM_EVENT_NAMES,
    compositePartDefinitions,
    createClientFormInstance,
    getClientErrorAriaLive,
    getClientFieldIds,
    getClientFieldLabelTarget,
    joinIdReferences,
    createGraphqlClientTransport,
    createRepeaterRowValue,
    createRestClientTransport,
    createTableRowValue,
    dropdownOptions,
    fieldOptions,
    focusClientFormResult,
    getClientString,
    isArrayValuedField,
    isCalculationField,
    isFieldLabelVisuallyHidden,
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
    type ClientFieldType,
    type ClientFormDefinition,
    type ClientFormBootstrap,
    type ClientFormSession,
    type ClientFormInstance,
    type ClientFormState,
    type ClientSubmitResult,
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

export type FormieVueEvent = {
    name: string;
    payload: unknown;
};

export type FormieFormComponentProps = {
    definition: ClientFormDefinition;
    session: ClientFormSession;
    state: ClientFormState;
    className?: string;
    onSubmit: () => void;
};

export type FormiePageComponentProps = {
    page: ClientFormDefinition['pages'][number];
    state: ClientFormState;
};

export type FormieFieldProps = {
    field: ClientFieldDefinition;
    errors: string[];
    errorId: string;
    errorAriaLive: ClientErrorAriaLive;
    inputId: string;
    labelId: string;
    instructionsId: string;
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
    setValue: (value: unknown) => void;
};

export type FormieSlotComponentProps = {
    slotKey: string;
    attributes?: Record<string, unknown>;
    field?: ClientFieldDefinition;
};

export type FormieVueComponents = {
    Form?: Component;
    Page?: Component;
    Field?: Component;
    ErrorSummary?: Component;
};

type FormieDefinitionContextValue = {
    instance: ShallowRef<ClientFormInstance | null>;
    state: ShallowRef<ClientFormState | null>;
    components: ComputedRef<FormieVueComponents>;
    fieldComponents: ComputedRef<Partial<Record<string, Component>>>;
    slots: ComputedRef<Partial<Record<string, Component>>>;
};

const FORMIE_DEFINITION_CONTEXT = Symbol('formie-definition-context');

const fieldComponentProps = {
    field: {
        type: Object as PropType<ClientFieldDefinition>,
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
    errorId: {
        type: String,
        default: '',
    },
    errorAriaLive: {
        type: String as PropType<ClientErrorAriaLive>,
        default: 'polite',
    },
    inputId: {
        type: String,
        default: '',
    },
    labelId: {
        type: String,
        default: '',
    },
    instructionsId: {
        type: String,
        default: '',
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

function isInlineDefinitionSource(source: FormieDefinitionSource): source is Extract<FormieDefinitionSource, { definition: ClientFormBootstrap }> {
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

function flattenFields(definition: ClientFormDefinition): ClientFieldDefinition[] {
    return definition.pages.flatMap((page) => page.rows).flatMap((row) => row.fields);
}

function resolveFieldRendererType(field: ClientFieldDefinition): ClientFieldDefinition['type'] {
    return resolveClientRendererType(field);
}

function resolveFieldModule(field: ClientFieldDefinition, definition: ClientFormDefinition, moduleId: string) {
    const refs = new Set(field.moduleRefs || []);

    return definition.modules.entries.find((module) => refs.has(module.key) && module.moduleId === moduleId) || null;
}

function isDefaultFieldWrapper(context: FormieDefinitionContextValue): boolean {
    return !context.components.value.Field;
}

function renderSlotWrapper(context: FormieDefinitionContextValue, slotKey: string, child: VNode | null, attributes?: Record<string, unknown>, field?: ClientFieldDefinition) {
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
        field,
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
                role: 'alert',
                tabindex: -1,
                'data-formie-form-message': true,
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
            type: Object as PropType<ClientFieldDefinition>,
            required: true,
        },
        errors: {
            type: Array as PropType<string[]>,
            required: true,
        },
        errorId: {
            type: String,
            required: true,
        },
        errorAriaLive: {
            type: String as PropType<ClientErrorAriaLive>,
            required: true,
        },
        inputId: {
            type: String,
            default: '',
        },
        labelId: {
            type: String,
            default: '',
        },
        instructionsId: {
            type: String,
            default: '',
        },
    },
    setup(props, componentContext) {
        const context = useDefinitionContext();

        return () => {
            const children = componentContext.slots.default?.() || [];
            const labelAttributes = {
                class: 'formie-vue-label',
                style: isFieldLabelVisuallyHidden(props.field) ? VISUALLY_HIDDEN_STYLE : undefined,
                id: props.labelId || undefined,
                for: props.inputId && getClientFieldLabelTarget(props.field, resolveFieldRendererType(props.field)) === 'control' ? props.inputId : undefined,
            };
            const instructionsAttributes = {
                class: 'formie-vue-description',
                id: props.instructionsId || undefined,
            };

            return h('div', {
                class: 'formie-vue-field',
                'data-formie-field-uid': props.field.uid,
                'data-formie-field-handle': props.field.handle,
                'data-formie-field-type': props.field.type,
                'data-formie-field-required': props.field.required ? 'true' : undefined,
            }, [
                props.field.label && !isFieldLabelHidden(props.field)
                    ? renderSlotWrapper(context, 'label', h('label', labelAttributes, props.field.label), labelAttributes, props.field)
                    : null,
                props.field.instructions
                    ? renderSlotWrapper(context, 'instructions', h('div', {
                        ...instructionsAttributes,
                        innerHTML: props.field.instructions,
                    }), instructionsAttributes, props.field)
                    : null,
                renderSlotWrapper(context, 'input', h('div', {
                    class: 'formie-vue-input',
                }, children), {
                    class: 'formie-vue-input',
                }, props.field),
                renderSlotWrapper(context, 'errors', h('ul', {
                        id: props.errorId,
                        class: 'formie-vue-field-errors',
                        style: props.errors.length === 0 ? { position: 'absolute' } : undefined,
                        'data-formie-field-errors': true,
                        'aria-live': props.errorAriaLive === 'off' ? undefined : props.errorAriaLive,
                        'aria-atomic': props.errorAriaLive === 'off' ? undefined : 'true',
                    }, props.errors.map((error, index) => {
                        return h('li', {
                            key: `${error}:${index}`,
                        }, error);
                    })), {
                        id: props.errorId,
                        class: 'formie-vue-field-errors',
                        style: props.errors.length === 0 ? { position: 'absolute' } : undefined,
                        'data-formie-field-errors': true,
                        'aria-live': props.errorAriaLive === 'off' ? undefined : props.errorAriaLive,
                        'aria-atomic': props.errorAriaLive === 'off' ? undefined : 'true',
                    }, props.field),
            ]);
        };
    },
});

const BrowserModuleHost = defineComponent({
    setup(_props, { slots }) {
        const context = useDefinitionContext();
        const root = ref<HTMLDivElement | null>(null);
        let disposed = false;
        let host: Awaited<ReturnType<typeof mountClientRenderedModules>> | undefined;
        onMounted(async() => {
            const instance = context.instance.value!;
            instance.setBrowserModuleGuard(() => { throw new Error('Form features are still loading.'); });
            const mounted = await mountClientRenderedModules(root.value!, instance);
            if (disposed) void mounted.destroy(); else host = mounted;
        });
        onBeforeUnmount(() => { disposed = true; void host?.destroy(); });
        return () => h('div', { ref: root }, slots.default?.());
    },
});

const DefaultForm = defineComponent({
    name: 'FormieVueDefaultForm',
    props: {
        definition: {
            type: Object as PropType<ClientFormDefinition>,
            required: true,
        },
        session: {
            type: Object as PropType<ClientFormSession>,
            required: true,
        },
        state: {
            type: Object as PropType<ClientFormState>,
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
            onSubmit: async (event: Event) => {
                event.preventDefault();
                const form = event.currentTarget as HTMLFormElement;
                const previousPageId = props.state.currentPageId;
                await props.onSubmit();
                requestAnimationFrame(() => focusClientFormResult(form, previousPageId));
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
            type: Object as PropType<ClientFormDefinition['pages'][number]>,
            required: true,
        },
        state: {
            type: Object as PropType<ClientFormState>,
            required: true,
        },
    },
    setup(props, componentContext) {
        return () => h('section', {
            'data-page-id': props.page.id,
            class: 'formie-vue-page',
            // Focus moves here when the page changes, and the page name says where the visitor is.
            tabindex: -1,
            'aria-label': props.state.definition.pages.length > 1 && props.page.label ? props.page.label : undefined,
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
            modules: { contractVersion: 2, surface: 'client-rendered', entries: [] },
        } as ClientFormDefinition, 'formie:signature')?.config);
        const backgroundColor = computed(() => {
            return typeof moduleConfig.value?.backgroundColor === 'string' ? moduleConfig.value.backgroundColor : '#ffffff';
        });
        const penColor = computed(() => {
            return typeof moduleConfig.value?.penColor === 'string' ? moduleConfig.value.penColor : '#000000';
        });
        const penWeight = computed(() => {
            return Number(moduleConfig.value?.penWeight ?? 2) || 2;
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
                ...signatureCanvasAttributes(props.field, controlLabelling(props, isDefaultFieldWrapper(context))),
                style: props.disabled ? { pointerEvents: 'none' } : undefined,
            }),
            h('button', {
                key: 'clear',
                type: 'button',
                disabled: props.disabled,
                'aria-label': getClientString(context.state.value?.definition, 'clearSignature'),
                'data-formie-signature-clear': true,
                onClick: () => {
                    padRef.value?.clear();
                    props.setValue('');
                },
            }, getClientString(context.state.value?.definition, 'clear')),
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
                ...groupAttributes(props.field, controlLabelling(props, isDefaultFieldWrapper(context)), 'group'),
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
        const context = useDefinitionContext();

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
                    ...controlAttributes(props.field, controlLabelling(props, isDefaultFieldWrapper(context))),
                    disabled: props.disabled,
                    multiple,
                    onChange: (event: Event) => {
                        const target = event.target as HTMLInputElement;
                        props.setValue(Array.from(target.files || []));
                    },
                }),
                items.length > 0 ? h('ul', {
                    key: 'summary',
                    class: 'formie-vue-file-list',
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

            // Fields in Repeater rows and composite parts have their own state, keyed by their path.
            const fieldState = state.fieldStates[props.errorKey];
            const hidden = fieldState?.hidden === true || props.hidden;

            if (hidden) {
                return null;
            }

            const rendererType = resolveFieldRendererType(props.field);
            const renderer = context.fieldComponents.value[props.field.type] || context.fieldComponents.value[rendererType] || DefaultFieldInput;
            const FieldComponent = context.components.value.Field || DefaultField;
            const ids = getClientFieldIds(state.session, props.errorKey);
            const errorId = ids.errors;
            const errorAriaLive = getClientErrorAriaLive(state.definition);

            return h(FieldComponent, {
                field: props.field,
                errors: props.errors,
                errorId,
                errorAriaLive,
                inputId: ids.input,
                labelId: ids.label,
                instructionsId: ids.instructions,
            }, {
                default: () => [
                    h(renderer, {
                        field: props.field,
                        value: props.value,
                        errors: props.errors,
                        errorKey: props.errorKey,
                        errorId,
                        errorAriaLive,
                        inputId: ids.input,
                        labelId: ids.label,
                        instructionsId: ids.instructions,
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
            type: Object as PropType<ClientFieldDefinition>,
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
            type: Object as PropType<ClientFormDefinition['pages'][number]['rows'][number]>,
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
            type: Function as PropType<((field: ClientFieldDefinition, nextValue: unknown) => void) | undefined>,
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
                    disabled: props.disabled === true || state.fieldStates[errorKey]?.disabled === true,
                    hidden: state.fieldStates[errorKey]?.hidden === true,
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

            const labelling = controlLabelling(props, isDefaultFieldWrapper(context));
            const definition = state.definition;

            return h('div', {
                class: 'formie-vue-repeater',
                'data-formie-repeater-container': true,
                ...groupAttributes(props.field, labelling, 'group'),
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
                                setFieldValue(rowField: ClientFieldDefinition, nextValue: unknown) {
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
                            'aria-label': `${getClientString(definition, 'removeRow')} ${rowIndex + 1}`,
                            'data-formie-repeater-remove': true,
                            onClick: () => {
                                props.setValue(currentRows.filter((_, candidateIndex) => candidateIndex !== rowIndex));
                            },
                        }, getClientString(definition, 'remove')),
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
                }, String(contract.addRowLabel || getClientString(definition, 'addRow'))),
            ]);
        };
    },
});

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

function controlLabelling(props: FormieFieldComponentProps, labelledByWrapper: boolean): ControlLabelling {
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
        'aria-required': field.required ? 'true' : undefined,
        'aria-invalid': hasErrors ? 'true' : undefined,
        'aria-errormessage': hasErrors ? labelling.errorId : undefined,
        'aria-describedby': joinIdReferences(instructionsReference(field, labelling), hasErrors && labelling.errorId),
        autocomplete: typeof field.input.autocomplete === 'string' ? field.input.autocomplete : undefined,
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
        'aria-required': role === 'radiogroup' && field.required ? 'true' : undefined,
        'aria-describedby': joinIdReferences(instructionsReference(field, labelling)),
    };
}

function renderNestedFieldInput(field: ClientFieldDefinition, value: unknown, disabled: boolean, setValue: (value: unknown) => void, labelling: ControlLabelling, rendererType = resolveFieldRendererType(field)) {
    const contract = field.input;

    if (rendererType === 'multi-line-text') {
        return h('textarea', {
            ...controlAttributes(field, labelling),
            value: typeof value === 'string' ? value : '',
            disabled,
            placeholder: typeof contract.placeholder === 'string' ? contract.placeholder : undefined,
            onInput: (event: Event) => {
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
        const current = multiple ? '' : (selected[0] ?? '');
        // Without this, a dropdown whose answer was cleared would show its first option instead.
        const showEmpty = !multiple && current === '' && !options.some((option) => option.value === '');

        return h('select', {
            ...controlAttributes(field, labelling),
            // Setting a value on a multi-select clears the options' own selection, so only single selects get one.
            ...(multiple ? {} : { value: current }),
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
            showEmpty ? h('option', { key: `${field.id}:empty`, value: '', disabled: true, hidden: true }) : null,
            ...options.map((option) => {
                return h('option', {
                    key: `${field.id}:${option.value}`,
                    value: option.value,
                    disabled: option.disabled,
                    selected: multiple ? selected.includes(option.value) : undefined,
                }, option.label);
            }),
        ]);
    }

    const inputType = typeof contract.inputType === 'string'
        ? contract.inputType
        : (field.type === 'email' ? 'email' : (field.type === 'phone' ? 'tel' : (field.type === 'number' ? 'number' : 'text')));

    return h('input', {
        ...controlAttributes(field, labelling),
        type: inputType,
        value: typeof value === 'string' || typeof value === 'number' ? String(value) : '',
        disabled,
        // Calculations fields show an answer worked out from other fields.
        readonly: isCalculationField(field) || undefined,
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

const TableFieldInput = defineComponent({
    name: 'FormieVueTableFieldInput',
    props: fieldComponentProps,
    setup(props) {
        const context = useDefinitionContext();

        return () => {
            const definition = context.state.value?.definition;
            const columns = tableColumns(props.field);
            const rows = Array.isArray(props.value) ? props.value as Array<Record<string, unknown>> : [];
            const contract = props.field.input;
            const fixedRows = contract.static === true || contract.staticRows === true;
            const minRows = Number(contract.minRows ?? 0) || 0;
            const maxRows = Number(contract.maxRows ?? 0) || 0;
            const canAddRow = !props.disabled && !fixedRows && (maxRows <= 0 || rows.length < maxRows);
            const setCell = (rowIndex: number, handle: string, nextValue: unknown) => {
                props.setValue(rows.map((row, index) => index === rowIndex ? { ...row, [handle]: nextValue } : row));
            };

            return h('div', {
                class: 'formie-vue-table',
                ...groupAttributes(props.field, controlLabelling(props, isDefaultFieldWrapper(context)), 'group'),
            }, [
                h('table', null, [
                    h('thead', null, h('tr', null, [
                        ...columns.map((column) => h('th', { key: column.id, scope: 'col' }, column.heading)),
                        fixedRows ? null : h('td', { key: 'actions' }),
                    ])),
                    h('tbody', null, rows.map((row, rowIndex) => h('tr', { key: rowIndex }, [
                        ...columns.map((column) => h('td', { key: column.id }, renderTableCell(column, row[column.handle], props.disabled, (nextValue) => setCell(rowIndex, column.handle, nextValue)))),
                        fixedRows ? null : h('td', { key: 'actions' }, h('button', {
                            type: 'button',
                            disabled: props.disabled || rows.length <= minRows,
                            'aria-label': `${getClientString(definition, 'removeRow')} ${rowIndex + 1}`,
                            onClick: () => props.setValue(rows.filter((_, index) => index !== rowIndex)),
                        }, getClientString(definition, 'remove'))),
                    ]))),
                ]),
                fixedRows ? null : h('button', {
                    type: 'button',
                    disabled: !canAddRow,
                    onClick: () => props.setValue([...rows, createTableRowValue(props.field)]),
                }, String(contract.addRowLabel || getClientString(definition, 'addRow'))),
            ]);
        };
    },
});

function renderTableCell(column: ReturnType<typeof tableColumns>[number], value: unknown, disabled: boolean, setValue: (value: unknown) => void) {
    if (column.type === 'heading') {
        return h('span', null, typeof value === 'string' ? value : '');
    }

    if (column.type === 'checkbox') {
        return h('input', {
            type: 'checkbox',
            'aria-label': column.heading,
            checked: value === true || value === '1' || value === 1,
            disabled,
            onChange: (event: Event) => setValue((event.target as HTMLInputElement).checked),
        });
    }

    if (column.type === 'multiline') {
        return h('textarea', {
            'aria-label': column.heading,
            value: typeof value === 'string' ? value : '',
            disabled,
            onInput: (event: Event) => setValue((event.target as HTMLTextAreaElement).value),
        });
    }

    if (column.type === 'select' || column.type === 'dropdown') {
        const current = value == null ? '' : String(value);

        return h('select', {
            'aria-label': column.heading,
            value: current,
            disabled,
            onChange: (event: Event) => setValue((event.target as HTMLSelectElement).value),
        }, [
            // An answer that isn't one of the options shows as empty rather than as the first option.
            column.options.some((option) => option.value === current) ? null : h('option', { key: ':empty', value: current, disabled: true, hidden: true }),
            ...column.options.map((option) => h('option', { key: option.value, value: option.value }, option.label)),
        ]);
    }

    const inputTypes: Record<string, string> = { number: 'number', email: 'email', url: 'url', date: 'date', time: 'time', color: 'color' };

    return h('input', {
        type: inputTypes[column.type] ?? 'text',
        'aria-label': column.heading,
        value: typeof value === 'string' || typeof value === 'number' ? String(value) : '',
        disabled,
        onInput: (event: Event) => setValue((event.target as HTMLInputElement).value),
    });
}

const DefaultFieldInput = defineComponent({
    name: 'FormieVueDefaultFieldInput',
    props: fieldComponentProps,
    setup(props) {
        const context = useDefinitionContext();

        return () => {
            const contract = props.field.input;
            const rendererType = resolveFieldRendererType(props.field);
            const labelling = controlLabelling(props, isDefaultFieldWrapper(context));

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
                return renderNestedFieldInput(props.field, props.value, props.disabled, props.setValue, labelling, rendererType);
            }

            if (rendererType === 'table') {
                return h(TableFieldInput, props);
            }

            if (rendererType === 'section') {
                return h('hr', { class: 'formie-vue-section' });
            }

            if (rendererType === 'radio') {
                const options = fieldOptions(props.field);
                // Element fields shown as radios keep a list of one ID.
                const listValue = isArrayValuedField(props.field);
                const current = Array.isArray(props.value) ? String(props.value[0] ?? '') : (props.value == null ? '' : String(props.value));

                return h('div', {
                    class: 'formie-vue-choices',
                    ...groupAttributes(props.field, labelling, 'radiogroup'),
                }, options.map((option) => {
                    const optionValue = option.value;
                    const optionDisabled = props.disabled || option.disabled;

                    return h('label', {
                        key: `${props.field.id}:${optionValue}`,
                    }, [
                        h('input', {
                            key: 'input',
                            type: 'radio',
                            // One name per field occurrence groups the radios for keyboard use, including in Repeater rows.
                            name: props.inputId || `${props.field.id}-radio`,
                            value: optionValue,
                            ...errorReferenceAttributes(props.errors, props.errorId),
                            checked: current === optionValue,
                            disabled: optionDisabled,
                            onChange: () => {
                                props.setValue(listValue ? [optionValue] : optionValue);
                            },
                        }),
                        h('span', {
                            key: 'label',
                        }, option.label),
                    ]);
                }));
            }

            if (rendererType === 'checkboxes') {
                const options = fieldOptions(props.field);
                const selectedValues = Array.isArray(props.value) ? props.value.map((item) => String(item)) : [];

                return h('div', {
                    class: 'formie-vue-choices',
                    ...groupAttributes(props.field, labelling, 'group'),
                }, options.map((option) => {
                    const optionValue = option.value;
                    const checked = selectedValues.includes(optionValue);
                    const optionDisabled = props.disabled || option.disabled;

                    return h('label', {
                        key: `${props.field.id}:${optionValue}`,
                    }, [
                        h('input', {
                            key: 'input',
                            type: 'checkbox',
                            ...errorReferenceAttributes(props.errors, props.errorId),
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
                        }, option.label),
                    ]);
                }));
            }

            if (rendererType === 'agree') {
                const descriptionHtml = typeof contract.descriptionHtml === 'string' ? contract.descriptionHtml : null;

                // As in server-rendered forms, the field label names the group and the description labels the checkbox.
                return h('div', {
                    class: 'formie-vue-agree',
                    ...groupAttributes(props.field, labelling, 'group'),
                }, [
                    h('label', {
                        class: 'formie-vue-boolean',
                    }, [
                        h('input', {
                            key: 'input',
                            type: 'checkbox',
                            id: props.inputId || undefined,
                            'aria-required': props.field.required ? 'true' : undefined,
                            ...errorReferenceAttributes(props.errors, props.errorId),
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
                    ]),
                ]);
            }

            const display = displayFieldContent(props.field);

            if (display) {
                return display.kind === 'heading'
                    ? h(display.level, { class: 'formie-vue-heading' }, display.text)
                    : h('div', { class: 'formie-vue-content', innerHTML: display.html });
            }

            if (!isKnownClientFieldType(rendererType)) {
                return h('div', {
                    class: 'formie-vue-unsupported',
                }, `Unsupported field type: ${String(props.field.meta?.fieldType ?? props.field.type)}`);
            }

            return renderNestedFieldInput(props.field, props.value, props.disabled, props.setValue, labelling, rendererType);
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
                    onClick: async (event: Event) => {
                        const form = (event.currentTarget as HTMLElement).closest('form');
                        const previousPageId = state.currentPageId;
                        await instance.submit(action.type);
                        requestAnimationFrame(() => focusClientFormResult(form, previousPageId));
                    },
                }, action.label));
            });

            buttons.push(h('button', {
                key: page.actions.primary.type,
                type: 'submit',
                disabled: !clientActionAllowed(state),
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
                    return instance.submit();
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
                        role: 'status',
                        tabindex: -1,
                        'data-formie-form-message': true,
                        // Formie sanitizes rich completion messages before placing them in the client contract.
                        innerHTML: state.lastSubmitResult.messages.notice,
                    }) : null,
                    shouldRenderStandaloneError ? h('div', {
                        key: 'error',
                        class: 'formie-vue-error',
                        role: 'alert',
                        tabindex: -1,
                        'data-formie-form-message': true,
                    }, errorMessage) : null,
                    !(state.lastSubmitResult?.completion?.behavior === 'message' && state.lastSubmitResult.completion.hideForm) ? h(PageComponent, {
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
                    }) : null,
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
        type: Function as PropType<(instance: ClientFormInstance) => void>,
        default: undefined,
    },
    onReady: {
        type: Function as PropType<(instance: ClientFormInstance) => void>,
        default: undefined,
    },
    onUnmount: {
        type: Function as PropType<() => void>,
        default: undefined,
    },
    onResult: {
        type: Function as PropType<(result: ClientSubmitResult) => void>,
        default: undefined,
    },
    onSuccess: {
        type: Function as PropType<(result: ClientSubmitResult) => void>,
        default: undefined,
    },
    onError: {
        type: Function as PropType<(result: ClientSubmitResult) => void>,
        default: undefined,
    },
    onSubmitResult: {
        type: Function as PropType<(result: ClientSubmitResult) => void>,
        default: undefined,
    },
    onSubmitSuccess: {
        type: Function as PropType<(result: ClientSubmitResult) => void>,
        default: undefined,
    },
    onSubmitError: {
        type: Function as PropType<(result: ClientSubmitResult) => void>,
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
        const instance = shallowRef<ClientFormInstance | null>(null);
        const state = shallowRef<ClientFormState | null>(null);
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
            let currentInstance: ClientFormInstance | null = null;
            let cleanup = () => undefined;

            const load = async() => {
                try {
                    const envelope = await resolveDefinitionEnvelope(props.source);
                    const transport = resolveDefinitionTransport(props.source);
                    const nextInstance = createClientFormInstance({
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
                            const result = payload as ClientSubmitResult;
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
                        ...CLIENT_FORM_EVENT_NAMES.map((eventName) => {
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

            return h(BrowserModuleHost, {}, { default: () => h(ConfigRenderer, { className: props.className }) });
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
