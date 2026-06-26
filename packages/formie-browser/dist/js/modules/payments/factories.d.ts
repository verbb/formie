import type { FormieModuleDefinition, ModuleSetupContext } from '#contracts/modules';
import { type NormalizedPaymentModuleOptions, type PaymentHostServices } from '#modules/payments/host';
export type PaymentModuleSetupContext<TProvider extends Record<string, unknown>> = Omit<ModuleSetupContext, 'options'> & {
    options: NormalizedPaymentModuleOptions<TProvider>;
    services: PaymentHostServices;
};
type PaymentSetupResult = {
    destroy?: () => void | Promise<void>;
    onBeforeStage?: (stageCtx: import('#contracts/modules').SubmitHookContext) => void | Promise<void>;
};
export type PaymentAfterSubmitResult = {
    /** Tear down the current widget and mount again (for example after a failed payment). */
    remount?: boolean;
};
export type ManagedPaymentModuleAdapter<TProvider extends Record<string, unknown>, TApi, TWidget> = {
    id: string;
    defaultRequiredInputSuffixes?: string[];
    load: (ctx: PaymentModuleSetupContext<TProvider>) => Promise<TApi>;
    /** Redirect-only providers (Mollie, GoCardless): attach listeners, return destroy. No mount needed. */
    setup?: (ctx: PaymentModuleSetupContext<TProvider> & {
        root: HTMLElement;
    }) => Promise<PaymentSetupResult>;
    mount?: (args: {
        api: TApi;
        field: Element;
        services: PaymentHostServices;
        options: NormalizedPaymentModuleOptions<TProvider>;
        provider: TProvider;
    }) => Promise<TWidget> | TWidget;
    unmount?: (args: {
        api: TApi;
        widget: TWidget;
        field: Element;
        services: PaymentHostServices;
        options: NormalizedPaymentModuleOptions<TProvider>;
        provider: TProvider;
    }) => Promise<void> | void;
    onBeforeAuthorize?: (args: {
        api: TApi;
        widget: TWidget | null;
        field: Element;
        services: PaymentHostServices;
        options: NormalizedPaymentModuleOptions<TProvider>;
        provider: TProvider;
        stageCtx: import('#contracts/modules').SubmitHookContext;
    }) => Promise<boolean> | boolean;
    /** Called after dispatch (on any result) to reset hidden inputs, clear UI, etc. */
    onAfterSubmit?: (args: {
        field: Element;
        services: PaymentHostServices;
        options: NormalizedPaymentModuleOptions<TProvider>;
        provider: TProvider;
        result?: import('#contracts/schema').FormSubmitResult;
    }) => void | Promise<void> | PaymentAfterSubmitResult | Promise<PaymentAfterSubmitResult>;
};
export declare function createManagedPaymentModule<TProvider extends Record<string, unknown>, TApi, TWidget>(adapter: ManagedPaymentModuleAdapter<TProvider, TApi, TWidget>): FormieModuleDefinition;
export {};
//# sourceMappingURL=factories.d.ts.map