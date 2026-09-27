import { type ClientFieldDefinition, type ClientErrorAriaLive, type ClientFormDefinition, type ClientFormBootstrap, type ClientFormSession, type ClientFormInstance, type ClientFormState, type ClientSubmitResult } from '@verbb/formie-core';
import { type ReactNode } from 'react';
export type FormieDefinitionSource = {
    transport: 'rest';
    endpoint: string;
    profile?: 'same-origin-browser' | 'cross-origin-public';
    formHandle: string;
    siteId?: number;
    query?: Record<string, string | string[]>;
} | {
    transport: 'graphql';
    endpoint: string;
    profile?: 'same-origin-browser' | 'cross-origin-public';
    formHandle: string;
    siteId?: number;
    query?: Record<string, string | string[]>;
} | {
    definition: ClientFormBootstrap;
    transport: {
        type: 'rest';
        endpoint: string;
        profile?: 'same-origin-browser' | 'cross-origin-public';
        formHandle: string;
        siteId?: number;
        query?: Record<string, string | string[]>;
    };
} | {
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
    instance: ClientFormInstance;
    state: ClientFormState;
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
export declare function DefinitionFormView({ source, components, fieldComponents, slots, className, onMount, onReady, onUnmount, onResult, onSuccess, onError, onSubmitResult, onSubmitSuccess, onSubmitError, onEvent, }: DefinitionFormViewProps): import("react").DetailedReactHTMLElement<{
    className: string;
}, HTMLElement> | import("react").FunctionComponentElement<import("react").ProviderProps<FormieDefinitionContextValue | null>>;
export declare function useFormie(): {
    definition: ClientFormDefinition;
    session: ClientFormSession;
    state: ClientFormState;
    instance: ClientFormInstance;
};
export declare function useFormieField(fieldId: string): {
    field: ClientFieldDefinition | undefined;
    value: unknown;
    errors: string[];
    hidden: boolean;
    disabled: boolean;
    setValue(value: unknown): void;
};
export declare function useFormiePage(pageId: string): {
    page: import("@verbb/formie-core").ClientPageDefinition | null;
    isCurrent: boolean;
    hidden: boolean;
};
export declare function useFormieInstance(): ClientFormInstance;
export declare function useFormieSlot(key: string): ((props: FormieSlotComponentProps) => ReactNode) | null;
export {};
//# sourceMappingURL=definition-form.d.ts.map