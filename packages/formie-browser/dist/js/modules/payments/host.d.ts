import type { ModuleSetupContext } from '#contracts/modules';
type Cleanup = () => void;
type ResolvedValueResult<T> = {
    ok: true;
    value: T;
} | {
    ok: false;
    error: string;
};
export type PaymentModuleOptions<TProvider extends Record<string, unknown> = Record<string, unknown>> = {
    handle?: string;
    requiredInputSuffixes?: string[];
    waitForValueMs?: number;
    errorMessage?: string;
} & TProvider;
export type NormalizedPaymentModuleOptions<TProvider extends Record<string, unknown>> = {
    handle: string;
    transport: {
        requiredInputSuffixes: string[];
        waitForValueMs: number;
        errorMessage: string;
    };
    provider: TProvider;
};
export declare function normalizePaymentModuleOptions<TProvider extends Record<string, unknown>>(id: string, rawOptions: Record<string, unknown> | undefined, defaults: {
    defaultRequiredInputSuffixes?: string[];
    defaultWaitForValueMs?: number;
}): NormalizedPaymentModuleOptions<TProvider>;
export type PaymentHostServices = {
    root: Element;
    form: HTMLFormElement | null;
    field: Element;
    updateInputs: (name: string | string[], value: string) => void;
    addError: (message: string) => void;
    removeError: () => void;
    addSuccess: (message: string) => void;
    removeSuccess: () => void;
    hasToken: () => boolean;
    waitForToken: (timeoutMs?: number) => Promise<boolean>;
    getFieldValue: (handle: string, type?: 'string' | 'float' | 'int' | 'number') => string | number;
    resolveAmount: (options: {
        type?: string | null;
        fixed?: unknown;
        variable?: string | null;
        value?: unknown;
    }) => ResolvedValueResult<number>;
    resolveCurrency: (options: {
        type?: string | null;
        fixed?: unknown;
        variable?: string | null;
        value?: unknown;
        defaultCurrency?: string;
    }) => ResolvedValueResult<string>;
    watchFieldValueChanges: (handles: string[], callback: () => void, debounceMs?: number) => Cleanup;
    getBillingData: (billingDetails: {
        billingName?: string;
        billingEmail?: string;
        billingAddress?: string;
    } | null) => Record<string, unknown>;
    /** Trigger form submit (e.g. after 3DS confirmation). */
    triggerSubmit: () => void;
    releaseSubmitLoading: () => void;
    events: {
        onForm: (eventName: string, callback: EventListener) => Cleanup;
        onRoot: (eventName: string, callback: EventListener) => Cleanup;
    };
};
export declare function createPaymentHostServices(ctx: ModuleSetupContext, options: NormalizedPaymentModuleOptions<Record<string, unknown>>): PaymentHostServices;
export {};
//# sourceMappingURL=host.d.ts.map