import { type FrontendFieldDefinition, type FrontendFormDefinition, type FrontendFormEnvelope, type FrontendFormSession, type FrontendFormInstance, type FrontendFormState, type FrontendSubmitResult } from '@verbb/formie-core';
import { type Component, type ComputedRef, type PropType, type ShallowRef, type VNode } from 'vue';
export type FormieDefinitionSource = {
    transport: 'rest';
    endpoint: string;
    formHandle: string;
    siteId?: number;
} | {
    transport: 'graphql';
    endpoint: string;
    formHandle: string;
    siteId?: number;
} | {
    definition: FrontendFormEnvelope;
    transport: {
        type: 'rest';
        endpoint: string;
        formHandle: string;
        siteId?: number;
    };
} | {
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
export declare const DefinitionFormView: import("vue").DefineComponent<import("vue").ExtractPropTypes<{
    readonly source: {
        readonly type: PropType<FormieDefinitionSource>;
        readonly required: true;
    };
    readonly components: {
        readonly type: PropType<FormieVueComponents>;
        readonly default: () => {};
    };
    readonly fieldComponents: {
        readonly type: PropType<Partial<Record<string, Component>>>;
        readonly default: () => {};
    };
    readonly slots: {
        readonly type: PropType<Partial<Record<string, Component>>>;
        readonly default: () => {};
    };
    readonly className: {
        readonly type: StringConstructor;
        readonly default: any;
    };
    readonly onMount: {
        readonly type: PropType<(instance: FrontendFormInstance) => void>;
        readonly default: any;
    };
    readonly onReady: {
        readonly type: PropType<(instance: FrontendFormInstance) => void>;
        readonly default: any;
    };
    readonly onUnmount: {
        readonly type: PropType<() => void>;
        readonly default: any;
    };
    readonly onResult: {
        readonly type: PropType<(result: FrontendSubmitResult) => void>;
        readonly default: any;
    };
    readonly onSuccess: {
        readonly type: PropType<(result: FrontendSubmitResult) => void>;
        readonly default: any;
    };
    readonly onError: {
        readonly type: PropType<(result: FrontendSubmitResult) => void>;
        readonly default: any;
    };
    readonly onSubmitResult: {
        readonly type: PropType<(result: FrontendSubmitResult) => void>;
        readonly default: any;
    };
    readonly onSubmitSuccess: {
        readonly type: PropType<(result: FrontendSubmitResult) => void>;
        readonly default: any;
    };
    readonly onSubmitError: {
        readonly type: PropType<(result: FrontendSubmitResult) => void>;
        readonly default: any;
    };
    readonly onEvent: {
        readonly type: PropType<(event: FormieVueEvent) => void>;
        readonly default: any;
    };
}>, () => VNode<import("vue").RendererNode, import("vue").RendererElement, {
    [key: string]: any;
}>, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, ("error" | "mount" | "ready" | "unmount" | "result" | "success" | "submit-result" | "submit-success" | "submit-error" | "event")[], "error" | "mount" | "ready" | "unmount" | "result" | "success" | "submit-result" | "submit-success" | "submit-error" | "event", import("vue").PublicProps, Readonly<import("vue").ExtractPropTypes<{
    readonly source: {
        readonly type: PropType<FormieDefinitionSource>;
        readonly required: true;
    };
    readonly components: {
        readonly type: PropType<FormieVueComponents>;
        readonly default: () => {};
    };
    readonly fieldComponents: {
        readonly type: PropType<Partial<Record<string, Component>>>;
        readonly default: () => {};
    };
    readonly slots: {
        readonly type: PropType<Partial<Record<string, Component>>>;
        readonly default: () => {};
    };
    readonly className: {
        readonly type: StringConstructor;
        readonly default: any;
    };
    readonly onMount: {
        readonly type: PropType<(instance: FrontendFormInstance) => void>;
        readonly default: any;
    };
    readonly onReady: {
        readonly type: PropType<(instance: FrontendFormInstance) => void>;
        readonly default: any;
    };
    readonly onUnmount: {
        readonly type: PropType<() => void>;
        readonly default: any;
    };
    readonly onResult: {
        readonly type: PropType<(result: FrontendSubmitResult) => void>;
        readonly default: any;
    };
    readonly onSuccess: {
        readonly type: PropType<(result: FrontendSubmitResult) => void>;
        readonly default: any;
    };
    readonly onError: {
        readonly type: PropType<(result: FrontendSubmitResult) => void>;
        readonly default: any;
    };
    readonly onSubmitResult: {
        readonly type: PropType<(result: FrontendSubmitResult) => void>;
        readonly default: any;
    };
    readonly onSubmitSuccess: {
        readonly type: PropType<(result: FrontendSubmitResult) => void>;
        readonly default: any;
    };
    readonly onSubmitError: {
        readonly type: PropType<(result: FrontendSubmitResult) => void>;
        readonly default: any;
    };
    readonly onEvent: {
        readonly type: PropType<(event: FormieVueEvent) => void>;
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
    readonly components: FormieVueComponents;
    readonly slots: Partial<Record<string, Component>>;
    readonly className: string;
    readonly fieldComponents: Partial<Record<string, Component>>;
    readonly onMount: (instance: FrontendFormInstance) => void;
    readonly onReady: (instance: FrontendFormInstance) => void;
    readonly onUnmount: () => void;
    readonly onResult: (result: FrontendSubmitResult) => void;
    readonly onSuccess: (result: FrontendSubmitResult) => void;
    readonly onSubmitResult: (result: FrontendSubmitResult) => void;
    readonly onSubmitSuccess: (result: FrontendSubmitResult) => void;
    readonly onSubmitError: (result: FrontendSubmitResult) => void;
    readonly onEvent: (event: FormieVueEvent) => void;
}, {}, {}, {}, string, import("vue").ComponentProvideOptions, true, {}, any>;
export declare function useFormie(): {
    definition: ComputedRef<FrontendFormDefinition>;
    session: ComputedRef<FrontendFormSession>;
    state: ShallowRef<FrontendFormState>;
    instance: ShallowRef<FrontendFormInstance>;
};
export declare function useFormieField(fieldId: string): {
    field: ComputedRef<FrontendFieldDefinition>;
    value: ComputedRef<unknown>;
    errors: ComputedRef<string[]>;
    hidden: ComputedRef<boolean>;
    disabled: ComputedRef<boolean>;
    setValue(value: unknown): void;
};
export declare function useFormiePage(pageId: string): {
    page: ComputedRef<import("@verbb/formie-core").FrontendPageDefinition>;
    isCurrent: ComputedRef<boolean>;
    hidden: ComputedRef<boolean>;
};
export declare function useFormieInstance(): ShallowRef<FrontendFormInstance>;
export declare function useFormieSlot(key: string): ComputedRef<Component>;
//# sourceMappingURL=definition-form.d.ts.map