import { createFormieClient, FORMIE_HTML_EVENT_NAMES } from '@verbb/formie-browser';
import type {
    FormAction,
    FormEventUnsubscribe,
    FormieClient,
    FormieFormInstance,
    FormMountOptions,
    FormEndpointPayload,
    FormTransport,
    FormSubmitResult,
} from '@verbb/formie-browser';
import type {
    FrontendFieldType,
    FrontendFormInstance,
    FrontendSubmitResult,
} from '@verbb/formie-core';
import {
    computed,
    defineComponent,
    h,
    ref,
    shallowRef,
    watch,
    type Component,
    type PropType,
    type Ref,
    type ShallowRef,
} from 'vue';
import {
    DefinitionFormView,
    type FormieDefinitionSource,
    type FormieErrorSummaryProps,
    type FormieFieldComponentProps,
    type FormieFieldProps,
    type FormieFormComponentProps,
    type FormiePageComponentProps,
    type FormieSlotComponentProps,
    type FormieVueComponents,
    type FormieVueEvent,
    useFormie,
    useFormieField,
    useFormiePage,
    useFormieInstance,
    useFormieSlot,
} from './definition-form';
import { stableSerialize } from './stable';

export type FormieHtmlSource = {
    payload: FormEndpointPayload;
};

export type FormieFormProps = {
    source?: FormieHtmlSource;
    transport?: FormTransport;
    endpoint?: string;
    formHandle?: string;
    staticCache?: boolean;
    refreshTokens?: boolean;
    locale?: string;
    siteId?: number;
    autoVisible?: boolean;
    theme?: FormMountOptions['theme'];
    themeConfig?: FormMountOptions['themeConfig'];
    className?: string;
    onMount?: (instance: FormieFormInstance) => void;
    onReady?: (instance: FormieFormInstance) => void;
    onUnmount?: () => void;
    onResult?: (result: FormSubmitResult) => void;
    onSuccess?: (result: FormSubmitResult) => void;
    onError?: (result: FormSubmitResult) => void;
    onSubmitResult?: (result: FormSubmitResult) => void;
    onSubmitSuccess?: (result: FormSubmitResult) => void;
    onSubmitError?: (result: FormSubmitResult) => void;
    onEvent?: (event: FormieVueEvent) => void;
};

export type FormieClientFormProps = {
    source?: FormieDefinitionSource;
    transport?: FormTransport;
    endpoint?: string;
    formHandle?: string;
    siteId?: number;
    components?: FormieVueComponents;
    fieldComponents?: Partial<Record<FrontendFieldType, Component>>;
    slots?: Partial<Record<string, Component>>;
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
    onEvent?: (event: FormieVueEvent) => void;
};

export type FormieHtmlOptions = Omit<FormMountOptions, 'mode'>;

function isHtmlSource(source: FormieFormProps['source']): source is FormieHtmlSource {
    return !!source && 'payload' in source;
}

function isSubmitSuccess(result: FormSubmitResult | FrontendSubmitResult): boolean {
    return 'success' in result ? result.success : result.ok;
}

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

function buildMountOptions(props: FormieFormProps): FormMountOptions {
    const transport = props.transport;

    if (!transport && !isHtmlSource(props.source)) {
        throw new Error('`transport` is required for <FormieForm>.');
    }

    return {
        mode: 'server-rendered',
        transport,
        endpoint: props.endpoint,
        formHandle: props.formHandle,
        payload: isHtmlSource(props.source) ? props.source.payload : undefined,
        staticCache: props.staticCache,
        refreshTokens: props.refreshTokens,
        locale: props.locale,
        siteId: props.siteId,
        autoVisible: props.autoVisible,
        theme: props.theme,
        themeConfig: props.themeConfig,
    };
}

function resolveDefinitionSource(props: FormieClientFormProps): FormieDefinitionSource {
    if (props.source) {
        return props.source;
    }

    const transport = props.transport;
    const endpoint = props.endpoint;
    const formHandle = props.formHandle;

    if (transport !== 'rest' && transport !== 'graphql') {
        throw new Error('Vue client-rendered forms require `transport="rest"` or `transport="graphql"`.');
    }

    if (!endpoint || !formHandle) {
        throw new Error('Vue client-rendered forms require either `source` or both `endpoint` and `formHandle`.');
    }

    return {
        transport,
        endpoint,
        formHandle,
        siteId: props.siteId,
    };
}

export type UseFormieHtmlState = {
    instance: ShallowRef<FormieFormInstance | null>;
    isMounted: Ref<boolean>;
    error: Ref<Error | null>;
};

export function createVueFormieClient(): FormieClient {
    return createFormieClient();
}

export function useFormieClient(): FormieClient {
    return createFormieClient();
}

export function useFormieHtml(options: FormieHtmlOptions): {
    rootRef: Ref<HTMLElement | null>;
    state: UseFormieHtmlState;
    submit: (action?: FormAction) => Promise<FormSubmitResult | null>;
} {
    const client = useFormieClient();
    const rootRef = ref<HTMLElement | null>(null);
    const instance = shallowRef<FormieFormInstance | null>(null);
    const error = ref<Error | null>(null);
    const optionsKey = computed(() => stableSerialize(options));

    watch([rootRef, optionsKey], ([root], _previousValue, onCleanup) => {
        if (!root) {
            return;
        }

        let disposed = false;
        let didUnmount = false;

        const ensureUnmount = async() => {
            if (didUnmount) {
                return;
            }

            didUnmount = true;
            await client.unmount(root);
        };

        const mountPromise = Promise.resolve().then(async() => {
            if (disposed) {
                return;
            }

            try {
                const mountedInstance = await client.mount(root, {
                    ...options,
                    mode: 'server-rendered',
                });

                if (disposed) {
                    await ensureUnmount();
                    return;
                }

                instance.value = mountedInstance;
                error.value = null;
            } catch (mountError) {
                if (!disposed) {
                    error.value = mountError as Error;
                }
            }
        });

        onCleanup(() => {
            disposed = true;
            instance.value = null;
            void mountPromise.finally(ensureUnmount);
        });
    }, { immediate: true });

    return {
        rootRef,
        state: {
            instance,
            isMounted: computed(() => !!instance.value),
            error,
        },
        submit: async(action = 'submit') => {
            if (!instance.value) {
                return null;
            }

            return instance.value.submit(action);
        },
    };
}

const htmlFormViewProps = {
    options: {
        type: Object as PropType<FormieFormProps>,
        required: true,
    },
} as const;

const HtmlFormView = defineComponent({
    name: 'FormieVueHtmlFormView',
    props: htmlFormViewProps,
    emits: ['mount', 'ready', 'unmount', 'result', 'success', 'error', 'submit-result', 'submit-success', 'submit-error', 'event'],
    setup(props, { emit }) {
        const rootRef = ref<HTMLDivElement | null>(null);
        const client = createFormieClient();
        const options = computed(() => buildMountOptions(props.options));
        const optionsKey = computed(() => stableSerialize(options.value));

        watch([rootRef, optionsKey], ([root], _previousValue, onCleanup) => {
            if (!root) {
                return;
            }

            let disposed = false;
            let mountedInstance: FormieFormInstance | null = null;
            const unsubs: FormEventUnsubscribe[] = [];

            const mountPromise = Promise.resolve().then(async() => {
                const instance = await client.mount(root, options.value);

                if (disposed) {
                    await client.unmount(root);
                    return;
                }

                mountedInstance = instance;
                props.options.onMount?.(instance);
                props.options.onReady?.(instance);
                emit('mount', instance);
                emit('ready', instance);

                unsubs.push(instance.on('formie:submit:result', (payload) => {
                    const result = payload as FormSubmitResult;
                    invokeDistinctCallbacks(props.options.onSubmitResult, props.options.onResult, result);
                    emit('result', result);
                    emit('submit-result', result);

                    if (isSubmitSuccess(result)) {
                        invokeDistinctCallbacks(props.options.onSubmitSuccess, props.options.onSuccess, result);
                        emit('success', result);
                        emit('submit-success', result);
                    } else {
                        invokeDistinctCallbacks(props.options.onSubmitError, props.options.onError, result);
                        emit('error', result);
                        emit('submit-error', result);
                    }
                }));

                FORMIE_HTML_EVENT_NAMES.forEach((eventName) => {
                    unsubs.push(instance.on(eventName, (payload) => {
                        const event = {
                            name: eventName,
                            payload,
                        };

                        props.options.onEvent?.(event);
                        emit('event', event);
                    }));
                });
            });

            onCleanup(() => {
                disposed = true;
                unsubs.forEach((unsubscribe) => unsubscribe());

                void mountPromise.finally(async() => {
                    await client.unmount(root);

                    if (mountedInstance) {
                        props.options.onUnmount?.();
                        emit('unmount');
                        mountedInstance = null;
                    }
                });
            });
        }, { immediate: true });

        return () => h('div', {
            ref: rootRef,
            class: props.options.className,
        });
    },
});

const formieFormProps = {
    source: {
        type: Object as PropType<FormieFormProps['source']>,
        default: undefined,
    },
    transport: {
        type: String as PropType<FormTransport | undefined>,
        default: undefined,
    },
    endpoint: {
        type: String,
        default: undefined,
    },
    formHandle: {
        type: String,
        default: undefined,
    },
    staticCache: {
        type: Boolean,
        default: undefined,
    },
    refreshTokens: {
        type: Boolean,
        default: undefined,
    },
    locale: {
        type: String,
        default: undefined,
    },
    siteId: {
        type: Number,
        default: undefined,
    },
    autoVisible: {
        type: Boolean,
        default: undefined,
    },
    theme: {
        type: String as PropType<FormMountOptions['theme'] | undefined>,
        default: undefined,
    },
    themeConfig: {
        type: Object as PropType<FormMountOptions['themeConfig'] | undefined>,
        default: undefined,
    },
    className: {
        type: String,
        default: undefined,
    },
    onMount: {
        type: Function as PropType<FormieFormProps['onMount']>,
        default: undefined,
    },
    onReady: {
        type: Function as PropType<FormieFormProps['onReady']>,
        default: undefined,
    },
    onUnmount: {
        type: Function as PropType<FormieFormProps['onUnmount']>,
        default: undefined,
    },
    onResult: {
        type: Function as PropType<FormieFormProps['onResult']>,
        default: undefined,
    },
    onSuccess: {
        type: Function as PropType<FormieFormProps['onSuccess']>,
        default: undefined,
    },
    onError: {
        type: Function as PropType<FormieFormProps['onError']>,
        default: undefined,
    },
    onSubmitResult: {
        type: Function as PropType<FormieFormProps['onSubmitResult']>,
        default: undefined,
    },
    onSubmitSuccess: {
        type: Function as PropType<FormieFormProps['onSubmitSuccess']>,
        default: undefined,
    },
    onSubmitError: {
        type: Function as PropType<FormieFormProps['onSubmitError']>,
        default: undefined,
    },
    onEvent: {
        type: Function as PropType<FormieFormProps['onEvent']>,
        default: undefined,
    },
} as const;

const formieClientFormProps = {
    source: {
        type: Object as PropType<FormieClientFormProps['source']>,
        default: undefined,
    },
    transport: {
        type: String as PropType<FormTransport | undefined>,
        default: undefined,
    },
    endpoint: {
        type: String,
        default: undefined,
    },
    formHandle: {
        type: String,
        default: undefined,
    },
    siteId: {
        type: Number,
        default: undefined,
    },
    components: {
        type: Object as PropType<FormieVueComponents | undefined>,
        default: undefined,
    },
    fieldComponents: {
        type: Object as PropType<Partial<Record<FrontendFieldType, Component>> | undefined>,
        default: undefined,
    },
    slots: {
        type: Object as PropType<Partial<Record<string, Component>> | undefined>,
        default: undefined,
    },
    className: {
        type: String,
        default: undefined,
    },
    onMount: {
        type: Function as PropType<FormieClientFormProps['onMount']>,
        default: undefined,
    },
    onReady: {
        type: Function as PropType<FormieClientFormProps['onReady']>,
        default: undefined,
    },
    onUnmount: {
        type: Function as PropType<FormieClientFormProps['onUnmount']>,
        default: undefined,
    },
    onResult: {
        type: Function as PropType<FormieClientFormProps['onResult']>,
        default: undefined,
    },
    onSuccess: {
        type: Function as PropType<FormieClientFormProps['onSuccess']>,
        default: undefined,
    },
    onError: {
        type: Function as PropType<FormieClientFormProps['onError']>,
        default: undefined,
    },
    onSubmitResult: {
        type: Function as PropType<FormieClientFormProps['onSubmitResult']>,
        default: undefined,
    },
    onSubmitSuccess: {
        type: Function as PropType<FormieClientFormProps['onSubmitSuccess']>,
        default: undefined,
    },
    onSubmitError: {
        type: Function as PropType<FormieClientFormProps['onSubmitError']>,
        default: undefined,
    },
    onEvent: {
        type: Function as PropType<FormieClientFormProps['onEvent']>,
        default: undefined,
    },
} as const;

export const FormieForm = defineComponent({
    name: 'FormieVueForm',
    props: formieFormProps,
    emits: ['mount', 'ready', 'unmount', 'result', 'success', 'error', 'submit-result', 'submit-success', 'submit-error', 'event'],
    setup(props, { emit }) {
        return () => {
            const sharedOptions: FormieFormProps = {
                source: props.source,
                transport: props.transport,
                endpoint: props.endpoint,
                formHandle: props.formHandle,
                staticCache: props.staticCache,
                refreshTokens: props.refreshTokens,
                locale: props.locale,
                siteId: props.siteId,
                autoVisible: props.autoVisible,
                theme: props.theme,
                themeConfig: props.themeConfig,
                className: props.className,
                onMount: props.onMount,
                onReady: props.onReady,
                onUnmount: props.onUnmount,
                onResult: props.onResult,
                onSuccess: props.onSuccess,
                onError: props.onError,
                onSubmitResult: props.onSubmitResult,
                onSubmitSuccess: props.onSubmitSuccess,
                onSubmitError: props.onSubmitError,
                onEvent: props.onEvent,
            };

            return h(HtmlFormView, {
                options: sharedOptions,
                onMount: (instance: FormieFormInstance) => emit('mount', instance),
                onReady: (instance: FormieFormInstance) => emit('ready', instance),
                onUnmount: () => emit('unmount'),
                onResult: (result: FormSubmitResult) => emit('result', result),
                onSuccess: (result: FormSubmitResult) => emit('success', result),
                onError: (result: FormSubmitResult) => emit('error', result),
                onSubmitResult: (result: FormSubmitResult) => emit('submit-result', result),
                onSubmitSuccess: (result: FormSubmitResult) => emit('submit-success', result),
                onSubmitError: (result: FormSubmitResult) => emit('submit-error', result),
                onEvent: (event: FormieVueEvent) => emit('event', event),
            });
        };
    },
});

export const FormieClientForm = defineComponent({
    name: 'FormieVueClientForm',
    props: formieClientFormProps,
    emits: ['mount', 'ready', 'unmount', 'result', 'success', 'error', 'submit-result', 'submit-success', 'submit-error', 'event'],
    setup(props, { emit }) {
        return () => h(DefinitionFormView, {
            source: resolveDefinitionSource({
                source: props.source,
                transport: props.transport,
                endpoint: props.endpoint,
                formHandle: props.formHandle,
                siteId: props.siteId,
                components: props.components,
                fieldComponents: props.fieldComponents,
                slots: props.slots,
                className: props.className,
                onMount: props.onMount,
                onReady: props.onReady,
                onUnmount: props.onUnmount,
                onResult: props.onResult,
                onSuccess: props.onSuccess,
                onError: props.onError,
                onSubmitResult: props.onSubmitResult,
                onSubmitSuccess: props.onSubmitSuccess,
                onSubmitError: props.onSubmitError,
                onEvent: props.onEvent,
            }),
            components: props.components,
            fieldComponents: props.fieldComponents,
            slots: props.slots,
            className: props.className,
            onMount: (instance: FrontendFormInstance) => {
                props.onMount?.(instance);
                emit('mount', instance);
            },
            onReady: (instance: FrontendFormInstance) => {
                props.onReady?.(instance);
                emit('ready', instance);
            },
            onUnmount: () => {
                props.onUnmount?.();
                emit('unmount');
            },
            onSubmitResult: (result: FrontendSubmitResult) => {
                props.onSubmitResult?.(result);
                props.onResult?.(result);
                emit('result', result);
                emit('submit-result', result);
            },
            onSubmitSuccess: (result: FrontendSubmitResult) => {
                props.onSubmitSuccess?.(result);
                props.onSuccess?.(result);
                emit('success', result);
                emit('submit-success', result);
            },
            onSubmitError: (result: FrontendSubmitResult) => {
                props.onSubmitError?.(result);
                props.onError?.(result);
                emit('error', result);
                emit('submit-error', result);
            },
            onEvent: (event: FormieVueEvent) => {
                props.onEvent?.(event);
                emit('event', event);
            },
        });
    },
});

export type {
    FormAction,
    FormEventUnsubscribe,
    FormDefinitionPayload,
    FormEndpointPayload,
    FormieClient,
    FormieFormInstance,
    FormMountOptions,
    FormSubmitResult,
} from '@verbb/formie-browser';
export type {
    FrontendFieldDefinition,
    FrontendFieldType,
    FrontendFormDefinition,
    FrontendFormEnvelope,
    FrontendFormSession,
    FrontendFormInstance,
    FrontendFormState,
    FrontendSubmitResult,
} from '@verbb/formie-core';
export type {
    FormieDefinitionSource,
    FormieErrorSummaryProps,
    FormieFieldComponentProps,
    FormieFieldProps,
    FormieFormComponentProps,
    FormiePageComponentProps,
    FormieSlotComponentProps,
    FormieVueComponents,
    FormieVueEvent,
} from './definition-form';
export {
    useFormie,
    useFormieField,
    useFormiePage,
    useFormieInstance,
    useFormieSlot,
};
