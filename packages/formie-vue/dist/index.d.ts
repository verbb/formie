import type { FormAction, FormieClient, FormieFormInstance, FormMountOptions, FormEndpointPayload, FormTransport, FormSubmitResult } from '@verbb/formie-browser';
import type { FrontendFieldType, FrontendFormInstance, FrontendSubmitResult } from '@verbb/formie-core';
import { type Component, type PropType, type Ref, type ShallowRef } from 'vue';
import { type FormieDefinitionSource, type FormieVueComponents, type FormieVueEvent, useFormie, useFormieField, useFormiePage, useFormieInstance, useFormieSlot } from './definition-form';
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
export type UseFormieHtmlState = {
    instance: ShallowRef<FormieFormInstance | null>;
    isMounted: Ref<boolean>;
    error: Ref<Error | null>;
};
export declare function createVueFormieClient(): FormieClient;
export declare function useFormieClient(): FormieClient;
export declare function useFormieHtml(options: FormieHtmlOptions): {
    rootRef: Ref<HTMLElement | null>;
    state: UseFormieHtmlState;
    submit: (action?: FormAction) => Promise<FormSubmitResult | null>;
};
export declare const FormieForm: import("vue").DefineComponent<import("vue").ExtractPropTypes<{
    readonly source: {
        readonly type: PropType<FormieFormProps["source"]>;
        readonly default: any;
    };
    readonly transport: {
        readonly type: PropType<FormTransport | undefined>;
        readonly default: any;
    };
    readonly endpoint: {
        readonly type: StringConstructor;
        readonly default: any;
    };
    readonly formHandle: {
        readonly type: StringConstructor;
        readonly default: any;
    };
    readonly staticCache: {
        readonly type: BooleanConstructor;
        readonly default: any;
    };
    readonly refreshTokens: {
        readonly type: BooleanConstructor;
        readonly default: any;
    };
    readonly locale: {
        readonly type: StringConstructor;
        readonly default: any;
    };
    readonly siteId: {
        readonly type: NumberConstructor;
        readonly default: any;
    };
    readonly autoVisible: {
        readonly type: BooleanConstructor;
        readonly default: any;
    };
    readonly theme: {
        readonly type: PropType<FormMountOptions["theme"] | undefined>;
        readonly default: any;
    };
    readonly themeConfig: {
        readonly type: PropType<FormMountOptions["themeConfig"] | undefined>;
        readonly default: any;
    };
    readonly className: {
        readonly type: StringConstructor;
        readonly default: any;
    };
    readonly onMount: {
        readonly type: PropType<FormieFormProps["onMount"]>;
        readonly default: any;
    };
    readonly onReady: {
        readonly type: PropType<FormieFormProps["onReady"]>;
        readonly default: any;
    };
    readonly onUnmount: {
        readonly type: PropType<FormieFormProps["onUnmount"]>;
        readonly default: any;
    };
    readonly onResult: {
        readonly type: PropType<FormieFormProps["onResult"]>;
        readonly default: any;
    };
    readonly onSuccess: {
        readonly type: PropType<FormieFormProps["onSuccess"]>;
        readonly default: any;
    };
    readonly onError: {
        readonly type: PropType<FormieFormProps["onError"]>;
        readonly default: any;
    };
    readonly onSubmitResult: {
        readonly type: PropType<FormieFormProps["onSubmitResult"]>;
        readonly default: any;
    };
    readonly onSubmitSuccess: {
        readonly type: PropType<FormieFormProps["onSubmitSuccess"]>;
        readonly default: any;
    };
    readonly onSubmitError: {
        readonly type: PropType<FormieFormProps["onSubmitError"]>;
        readonly default: any;
    };
    readonly onEvent: {
        readonly type: PropType<FormieFormProps["onEvent"]>;
        readonly default: any;
    };
}>, () => import("vue").VNode<import("vue").RendererNode, import("vue").RendererElement, {
    [key: string]: any;
}>, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, ("error" | "mount" | "ready" | "unmount" | "result" | "success" | "submit-result" | "submit-success" | "submit-error" | "event")[], "error" | "mount" | "ready" | "unmount" | "result" | "success" | "submit-result" | "submit-success" | "submit-error" | "event", import("vue").PublicProps, Readonly<import("vue").ExtractPropTypes<{
    readonly source: {
        readonly type: PropType<FormieFormProps["source"]>;
        readonly default: any;
    };
    readonly transport: {
        readonly type: PropType<FormTransport | undefined>;
        readonly default: any;
    };
    readonly endpoint: {
        readonly type: StringConstructor;
        readonly default: any;
    };
    readonly formHandle: {
        readonly type: StringConstructor;
        readonly default: any;
    };
    readonly staticCache: {
        readonly type: BooleanConstructor;
        readonly default: any;
    };
    readonly refreshTokens: {
        readonly type: BooleanConstructor;
        readonly default: any;
    };
    readonly locale: {
        readonly type: StringConstructor;
        readonly default: any;
    };
    readonly siteId: {
        readonly type: NumberConstructor;
        readonly default: any;
    };
    readonly autoVisible: {
        readonly type: BooleanConstructor;
        readonly default: any;
    };
    readonly theme: {
        readonly type: PropType<FormMountOptions["theme"] | undefined>;
        readonly default: any;
    };
    readonly themeConfig: {
        readonly type: PropType<FormMountOptions["themeConfig"] | undefined>;
        readonly default: any;
    };
    readonly className: {
        readonly type: StringConstructor;
        readonly default: any;
    };
    readonly onMount: {
        readonly type: PropType<FormieFormProps["onMount"]>;
        readonly default: any;
    };
    readonly onReady: {
        readonly type: PropType<FormieFormProps["onReady"]>;
        readonly default: any;
    };
    readonly onUnmount: {
        readonly type: PropType<FormieFormProps["onUnmount"]>;
        readonly default: any;
    };
    readonly onResult: {
        readonly type: PropType<FormieFormProps["onResult"]>;
        readonly default: any;
    };
    readonly onSuccess: {
        readonly type: PropType<FormieFormProps["onSuccess"]>;
        readonly default: any;
    };
    readonly onError: {
        readonly type: PropType<FormieFormProps["onError"]>;
        readonly default: any;
    };
    readonly onSubmitResult: {
        readonly type: PropType<FormieFormProps["onSubmitResult"]>;
        readonly default: any;
    };
    readonly onSubmitSuccess: {
        readonly type: PropType<FormieFormProps["onSubmitSuccess"]>;
        readonly default: any;
    };
    readonly onSubmitError: {
        readonly type: PropType<FormieFormProps["onSubmitError"]>;
        readonly default: any;
    };
    readonly onEvent: {
        readonly type: PropType<FormieFormProps["onEvent"]>;
        readonly default: any;
    };
}>> & Readonly<{
    onError?: (...args: any[]) => any;
    onMount?: (...args: any[]) => any;
    onReady?: (...args: any[]) => any;
    onUnmount?: (...args: any[]) => any;
    onResult?: (...args: any[]) => any;
    onSuccess?: (...args: any[]) => any;
    onEvent?: (...args: any[]) => any;
    "onSubmit-result"?: (...args: any[]) => any;
    "onSubmit-success"?: (...args: any[]) => any;
    "onSubmit-error"?: (...args: any[]) => any;
}>, {
    readonly onError: (result: FormSubmitResult) => void;
    readonly source: FormieHtmlSource;
    readonly className: string;
    readonly onMount: (instance: FormieFormInstance) => void;
    readonly onReady: (instance: FormieFormInstance) => void;
    readonly onUnmount: () => void;
    readonly onResult: (result: FormSubmitResult) => void;
    readonly onSuccess: (result: FormSubmitResult) => void;
    readonly onSubmitResult: (result: FormSubmitResult) => void;
    readonly onSubmitSuccess: (result: FormSubmitResult) => void;
    readonly onSubmitError: (result: FormSubmitResult) => void;
    readonly onEvent: (event: FormieVueEvent) => void;
    readonly theme: any;
    readonly themeConfig: any;
    readonly endpoint: string;
    readonly formHandle: string;
    readonly siteId: number;
    readonly transport: any;
    readonly staticCache: boolean;
    readonly refreshTokens: boolean;
    readonly locale: string;
    readonly autoVisible: boolean;
}, {}, {}, {}, string, import("vue").ComponentProvideOptions, true, {}, any>;
export declare const FormieClientForm: import("vue").DefineComponent<import("vue").ExtractPropTypes<{
    readonly source: {
        readonly type: PropType<FormieClientFormProps["source"]>;
        readonly default: any;
    };
    readonly transport: {
        readonly type: PropType<FormTransport | undefined>;
        readonly default: any;
    };
    readonly endpoint: {
        readonly type: StringConstructor;
        readonly default: any;
    };
    readonly formHandle: {
        readonly type: StringConstructor;
        readonly default: any;
    };
    readonly siteId: {
        readonly type: NumberConstructor;
        readonly default: any;
    };
    readonly components: {
        readonly type: PropType<FormieVueComponents | undefined>;
        readonly default: any;
    };
    readonly fieldComponents: {
        readonly type: PropType<Partial<Record<FrontendFieldType, Component>> | undefined>;
        readonly default: any;
    };
    readonly slots: {
        readonly type: PropType<Partial<Record<string, Component>> | undefined>;
        readonly default: any;
    };
    readonly className: {
        readonly type: StringConstructor;
        readonly default: any;
    };
    readonly onMount: {
        readonly type: PropType<FormieClientFormProps["onMount"]>;
        readonly default: any;
    };
    readonly onReady: {
        readonly type: PropType<FormieClientFormProps["onReady"]>;
        readonly default: any;
    };
    readonly onUnmount: {
        readonly type: PropType<FormieClientFormProps["onUnmount"]>;
        readonly default: any;
    };
    readonly onResult: {
        readonly type: PropType<FormieClientFormProps["onResult"]>;
        readonly default: any;
    };
    readonly onSuccess: {
        readonly type: PropType<FormieClientFormProps["onSuccess"]>;
        readonly default: any;
    };
    readonly onError: {
        readonly type: PropType<FormieClientFormProps["onError"]>;
        readonly default: any;
    };
    readonly onSubmitResult: {
        readonly type: PropType<FormieClientFormProps["onSubmitResult"]>;
        readonly default: any;
    };
    readonly onSubmitSuccess: {
        readonly type: PropType<FormieClientFormProps["onSubmitSuccess"]>;
        readonly default: any;
    };
    readonly onSubmitError: {
        readonly type: PropType<FormieClientFormProps["onSubmitError"]>;
        readonly default: any;
    };
    readonly onEvent: {
        readonly type: PropType<FormieClientFormProps["onEvent"]>;
        readonly default: any;
    };
}>, () => import("vue").VNode<import("vue").RendererNode, import("vue").RendererElement, {
    [key: string]: any;
}>, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, ("error" | "mount" | "ready" | "unmount" | "result" | "success" | "submit-result" | "submit-success" | "submit-error" | "event")[], "error" | "mount" | "ready" | "unmount" | "result" | "success" | "submit-result" | "submit-success" | "submit-error" | "event", import("vue").PublicProps, Readonly<import("vue").ExtractPropTypes<{
    readonly source: {
        readonly type: PropType<FormieClientFormProps["source"]>;
        readonly default: any;
    };
    readonly transport: {
        readonly type: PropType<FormTransport | undefined>;
        readonly default: any;
    };
    readonly endpoint: {
        readonly type: StringConstructor;
        readonly default: any;
    };
    readonly formHandle: {
        readonly type: StringConstructor;
        readonly default: any;
    };
    readonly siteId: {
        readonly type: NumberConstructor;
        readonly default: any;
    };
    readonly components: {
        readonly type: PropType<FormieVueComponents | undefined>;
        readonly default: any;
    };
    readonly fieldComponents: {
        readonly type: PropType<Partial<Record<FrontendFieldType, Component>> | undefined>;
        readonly default: any;
    };
    readonly slots: {
        readonly type: PropType<Partial<Record<string, Component>> | undefined>;
        readonly default: any;
    };
    readonly className: {
        readonly type: StringConstructor;
        readonly default: any;
    };
    readonly onMount: {
        readonly type: PropType<FormieClientFormProps["onMount"]>;
        readonly default: any;
    };
    readonly onReady: {
        readonly type: PropType<FormieClientFormProps["onReady"]>;
        readonly default: any;
    };
    readonly onUnmount: {
        readonly type: PropType<FormieClientFormProps["onUnmount"]>;
        readonly default: any;
    };
    readonly onResult: {
        readonly type: PropType<FormieClientFormProps["onResult"]>;
        readonly default: any;
    };
    readonly onSuccess: {
        readonly type: PropType<FormieClientFormProps["onSuccess"]>;
        readonly default: any;
    };
    readonly onError: {
        readonly type: PropType<FormieClientFormProps["onError"]>;
        readonly default: any;
    };
    readonly onSubmitResult: {
        readonly type: PropType<FormieClientFormProps["onSubmitResult"]>;
        readonly default: any;
    };
    readonly onSubmitSuccess: {
        readonly type: PropType<FormieClientFormProps["onSubmitSuccess"]>;
        readonly default: any;
    };
    readonly onSubmitError: {
        readonly type: PropType<FormieClientFormProps["onSubmitError"]>;
        readonly default: any;
    };
    readonly onEvent: {
        readonly type: PropType<FormieClientFormProps["onEvent"]>;
        readonly default: any;
    };
}>> & Readonly<{
    onError?: (...args: any[]) => any;
    onMount?: (...args: any[]) => any;
    onReady?: (...args: any[]) => any;
    onUnmount?: (...args: any[]) => any;
    onResult?: (...args: any[]) => any;
    onSuccess?: (...args: any[]) => any;
    onEvent?: (...args: any[]) => any;
    "onSubmit-result"?: (...args: any[]) => any;
    "onSubmit-success"?: (...args: any[]) => any;
    "onSubmit-error"?: (...args: any[]) => any;
}>, {
    readonly onError: (result: FrontendSubmitResult) => void;
    readonly source: FormieDefinitionSource;
    readonly components: FormieVueComponents;
    readonly slots: Partial<Record<string, Component>>;
    readonly className: string;
    readonly fieldComponents: Partial<Record<FrontendFieldType, Component>>;
    readonly onMount: (instance: FrontendFormInstance) => void;
    readonly onReady: (instance: FrontendFormInstance) => void;
    readonly onUnmount: () => void;
    readonly onResult: (result: FrontendSubmitResult) => void;
    readonly onSuccess: (result: FrontendSubmitResult) => void;
    readonly onSubmitResult: (result: FrontendSubmitResult) => void;
    readonly onSubmitSuccess: (result: FrontendSubmitResult) => void;
    readonly onSubmitError: (result: FrontendSubmitResult) => void;
    readonly onEvent: (event: FormieVueEvent) => void;
    readonly endpoint: string;
    readonly formHandle: string;
    readonly siteId: number;
    readonly transport: any;
}, {}, {}, {}, string, import("vue").ComponentProvideOptions, true, {}, any>;
export type { FormAction, FormEventUnsubscribe, FormDefinitionPayload, FormEndpointPayload, FormieClient, FormieFormInstance, FormMountOptions, FormSubmitResult, } from '@verbb/formie-browser';
export type { FrontendFieldDefinition, FrontendFieldType, FrontendFormDefinition, FrontendFormEnvelope, FrontendFormSession, FrontendFormInstance, FrontendFormState, FrontendSubmitResult, } from '@verbb/formie-core';
export type { FormieDefinitionSource, FormieErrorSummaryProps, FormieFieldComponentProps, FormieFieldProps, FormieFormComponentProps, FormiePageComponentProps, FormieSlotComponentProps, FormieVueComponents, FormieVueEvent, } from './definition-form';
export { useFormie, useFormieField, useFormiePage, useFormieInstance, useFormieSlot, };
//# sourceMappingURL=index.d.ts.map