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
import { createElement, useEffect, useMemo, useRef, useState } from 'react';
import type { RefObject } from 'react';
import {
    DefinitionFormView,
    type FormieDefinitionSource,
    type FormieFieldComponentProps,
    type FormieReactComponents,
    type FormieReactEvent,
    type FormieSlotComponentProps,
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
    onEvent?: (event: FormieReactEvent) => void;
};

export type FormieClientFormProps = {
    source?: FormieDefinitionSource;
    transport?: FormTransport;
    endpoint?: string;
    formHandle?: string;
    siteId?: number;
    components?: FormieReactComponents;
    fieldComponents?: Partial<Record<FrontendFieldType, (props: FormieFieldComponentProps) => ReturnType<typeof createElement> | null>>;
    slots?: Partial<Record<string, (props: FormieSlotComponentProps) => ReturnType<typeof createElement> | null>>;
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
        throw new Error('`transport` is required for <FormieForm />.');
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
        throw new Error('React client-rendered forms require `transport="rest"` or `transport="graphql"`.');
    }

    if (!endpoint || !formHandle) {
        throw new Error('React client-rendered forms require either `source` or both `endpoint` and `formHandle`.');
    }

    return {
        transport,
        endpoint,
        formHandle,
        siteId: props.siteId,
    };
}

type HtmlFormViewProps = FormieFormProps;

function HtmlFormView({
    source,
    transport,
    endpoint,
    formHandle,
    staticCache,
    refreshTokens,
    locale,
    siteId,
    autoVisible,
    theme,
    themeConfig,
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
}: HtmlFormViewProps) {
    const rootRef = useRef<HTMLDivElement | null>(null);
    const clientRef = useRef<FormieClient | null>(null);
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
    const options = useMemo(() => {
        return buildMountOptions({
            transport,
            endpoint,
            formHandle,
            staticCache,
            refreshTokens,
            locale,
            siteId,
            autoVisible,
            theme,
            themeConfig,
            source,
        });
    }, [
        transport,
        endpoint,
        formHandle,
        staticCache,
        refreshTokens,
        locale,
        siteId,
        autoVisible,
        theme,
        themeConfig,
        source,
    ]);
    const optionsKey = useMemo(() => {
        return stableSerialize(options);
    }, [options]);
    const optionsRef = useRef(options);

    useEffect(() => {
        onMountRef.current = onMount;
        onReadyRef.current = onReady;
        onUnmountRef.current = onUnmount;
        onResultRef.current = onResult;
        onSuccessRef.current = onSuccess;
        onErrorRef.current = onError;
        onSubmitResultRef.current = onSubmitResult;
        onSubmitSuccessRef.current = onSubmitSuccess;
        onSubmitErrorRef.current = onSubmitError;
        onEventRef.current = onEvent;
    }, [onMount, onReady, onUnmount, onResult, onSuccess, onError, onSubmitResult, onSubmitSuccess, onSubmitError, onEvent]);

    useEffect(() => {
        optionsRef.current = options;
    }, [options, optionsKey]);

    if (!clientRef.current) {
        clientRef.current = createFormieClient();
    }

    useEffect(() => {
        const root = rootRef.current;
        const client = clientRef.current;

        if (!root || !client) {
            return;
        }

        let mountedInstance: FormieFormInstance | null = null;
        let isDisposed = false;
        const unsubs: FormEventUnsubscribe[] = [];

        void client.mount(root, optionsRef.current).then((instance: FormieFormInstance) => {
            if (isDisposed) {
                return;
            }

            mountedInstance = instance;
            onMountRef.current?.(instance);
            onReadyRef.current?.(instance);

            unsubs.push(instance.on('formie:submit:result', (payload: unknown) => {
                const result = payload as FormSubmitResult;
                invokeDistinctCallbacks(onSubmitResultRef.current, onResultRef.current, result);

                if (isSubmitSuccess(result)) {
                    invokeDistinctCallbacks(onSubmitSuccessRef.current, onSuccessRef.current, result);
                } else {
                    invokeDistinctCallbacks(onSubmitErrorRef.current, onErrorRef.current, result);
                }
            }));

            FORMIE_HTML_EVENT_NAMES.forEach((eventName: string) => {
                unsubs.push(instance.on(eventName, (payload: unknown) => {
                    onEventRef.current?.({
                        name: eventName,
                        payload,
                    });
                }));
            });
        });

        return () => {
            isDisposed = true;
            unsubs.forEach((unsubscribe) => unsubscribe());

            if (!root || !client) {
                return;
            }

            void client.unmount(root).finally(() => {
                onUnmountRef.current?.();
                mountedInstance = null;
            });
        };
    }, [optionsKey]);

    return createElement('div', {
        ref: rootRef,
        className,
    });
}

export function FormieForm({
    source,
    transport,
    endpoint,
    formHandle,
    staticCache,
    refreshTokens,
    locale,
    siteId,
    autoVisible,
    theme,
    themeConfig,
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
}: FormieFormProps) {
    return createElement(HtmlFormView, {
        source,
        transport,
        endpoint,
        formHandle,
        staticCache,
        refreshTokens,
        locale,
        siteId,
        autoVisible,
        theme,
        themeConfig,
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
    });
}

export function FormieClientForm({
    source,
    transport,
    endpoint,
    formHandle,
    siteId,
    components,
    fieldComponents,
    slots,
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
}: FormieClientFormProps) {
    return createElement(DefinitionFormView, {
        source: resolveDefinitionSource({
            source,
            transport,
            endpoint,
            formHandle,
            siteId,
            components,
            fieldComponents,
            slots,
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
        }),
        components,
        fieldComponents,
        slots,
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
    });
}

export function useFormieClient(): FormieClient {
    return useMemo(() => {
        return createFormieClient();
    }, []);
}

export type UseFormieHtmlState = {
    instance: FormieFormInstance | null;
    isMounted: boolean;
    error: Error | null;
};

export function useFormieHtml(options: FormieHtmlOptions): {
    rootRef: RefObject<HTMLDivElement | null>;
    state: UseFormieHtmlState;
    submit: (action?: FormAction) => Promise<FormSubmitResult | null>;
} {
    const rootRef = useRef<HTMLDivElement>(null);
    const client = useFormieClient();
    const optionsKey = useMemo(() => {
        return stableSerialize(options);
    }, [options]);
    const optionsRef = useRef(options);
    const [instance, setInstance] = useState<FormieFormInstance | null>(null);
    const [error, setError] = useState<Error | null>(null);

    useEffect(() => {
        optionsRef.current = options;
    }, [options, optionsKey]);

    useEffect(() => {
        const root = rootRef.current;
        if (!root) {
            return;
        }

        let isDisposed = false;
        let didUnmount = false;

        const ensureUnmount = async() => {
            if (didUnmount) {
                return;
            }

            didUnmount = true;
            await client.unmount(root);
        };

        const mountPromise = new Promise<void>((resolve) => {
            queueMicrotask(() => {
                if (isDisposed) {
                    resolve();
                    return;
                }

                void client.mount(root, {
                    ...optionsRef.current,
                    mode: 'server-rendered',
                }).then(async(mountedInstance: FormieFormInstance) => {
                    if (isDisposed) {
                        await ensureUnmount();
                        resolve();
                        return;
                    }

                    setInstance(mountedInstance);
                    setError(null);
                    resolve();
                }).catch((mountError: unknown) => {
                    if (!isDisposed) {
                        setError(mountError as Error);
                    }

                    resolve();
                });
            });
        });

        return () => {
            isDisposed = true;
            setInstance(null);
            void mountPromise.finally(ensureUnmount);
        };
    }, [client, optionsKey]);

    return {
        rootRef,
        state: {
            instance,
            isMounted: !!instance,
            error,
        },
        submit: async(action = 'submit') => {
            if (!instance) {
                return null;
            }

            return instance.submit(action);
        },
    };
}


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
    FormieFieldComponentProps,
    FormieFormComponentProps,
    FormiePageComponentProps,
    FormieFieldProps,
    FormieErrorSummaryProps,
    FormieReactComponents,
    FormieReactEvent,
    FormieSlotComponentProps,
} from './definition-form';
export {
    useFormie,
    useFormieField,
    useFormiePage,
    useFormieInstance,
    useFormieSlot,
} from './definition-form';
