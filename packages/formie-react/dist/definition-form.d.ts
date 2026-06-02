import { type FrontendFieldDefinition, type FrontendFormDefinition, type FrontendFormEnvelope, type FrontendFormSession, type FrontendFormInstance, type FrontendFormState, type FrontendSubmitResult } from '@verbb/formie-core';
import { type ReactNode } from 'react';
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
export declare function DefinitionFormView({ source, components, fieldComponents, slots, className, onMount, onReady, onUnmount, onResult, onSuccess, onError, onSubmitResult, onSubmitSuccess, onSubmitError, onEvent, }: DefinitionFormViewProps): import("react").DetailedReactHTMLElement<{
    className: string;
}, HTMLElement> | import("react").FunctionComponentElement<import("react").ProviderProps<FormieDefinitionContextValue | null>>;
export declare function useFormie(): {
    definition: FrontendFormDefinition;
    session: FrontendFormSession;
    state: FrontendFormState;
    instance: FrontendFormInstance;
};
export declare function useFormieField(fieldId: string): {
    field: FrontendFieldDefinition | undefined;
    value: unknown;
    errors: string[];
    hidden: boolean;
    disabled: boolean;
    setValue(value: unknown): void;
};
export declare function useFormiePage(pageId: string): {
    page: import("@verbb/formie-core").FrontendPageDefinition | null;
    isCurrent: boolean;
    hidden: boolean;
};
export declare function useFormieInstance(): FrontendFormInstance;
export declare function useFormieSlot(key: string): ((props: FormieSlotComponentProps) => ReactNode) | null;
export {};
//# sourceMappingURL=definition-form.d.ts.map