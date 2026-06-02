import type { FormieModuleDefinition, FormieModuleInstance, ModuleSetupContext, SubmitHookContext } from '#contracts/modules';
import { type CaptchaHostServices, type NormalizedCaptchaModuleOptions } from '#modules/captchas/host';
type CaptchaModuleFactory<TProvider extends Record<string, unknown>> = {
    id: string;
    defaultPlaceholderSelector: string;
    defaultTokenFieldNames?: string[];
    defaultWaitForValueMs?: number;
    setup: (ctx: CaptchaModuleSetupContext<TProvider>) => Promise<FormieModuleInstance | void>;
};
export type ManagedCaptchaModuleAdapter<TProvider extends Record<string, unknown>, TApi, TWidget> = {
    id: string;
    defaultPlaceholderSelector: string;
    defaultTokenFieldNames?: string[];
    load: (ctx: CaptchaModuleSetupContext<TProvider>) => Promise<TApi>;
    mount: (args: {
        api: TApi;
        placeholder: HTMLElement;
        container: HTMLElement;
        services: CaptchaHostServices;
        options: NormalizedCaptchaModuleOptions<TProvider>;
        provider: TProvider;
    }) => Promise<TWidget> | TWidget;
    screen: (args: {
        api: TApi;
        widget: TWidget;
        placeholder: HTMLElement;
        services: CaptchaHostServices;
        options: NormalizedCaptchaModuleOptions<TProvider>;
        provider: TProvider;
        stageCtx: SubmitHookContext;
    }) => Promise<void> | void;
    unmount?: (args: {
        api: TApi;
        widget: TWidget;
        placeholder: HTMLElement;
        services: CaptchaHostServices;
        options: NormalizedCaptchaModuleOptions<TProvider>;
        provider: TProvider;
    }) => Promise<void> | void;
    reset?: (args: {
        api: TApi;
        widget: TWidget;
        placeholder: HTMLElement;
        services: CaptchaHostServices;
        options: NormalizedCaptchaModuleOptions<TProvider>;
        provider: TProvider;
        reason: 'submit-result' | 'reset-state';
    }) => Promise<void> | void;
};
export type CaptchaModuleSetupContext<TProvider extends Record<string, unknown>> = Omit<ModuleSetupContext, 'options'> & {
    options: NormalizedCaptchaModuleOptions<TProvider>;
    services: CaptchaHostServices;
};
export declare function createCaptchaModule<TProvider extends Record<string, unknown> = Record<string, unknown>>({ id, defaultPlaceholderSelector, defaultTokenFieldNames, defaultWaitForValueMs, setup, }: CaptchaModuleFactory<TProvider>): FormieModuleDefinition;
export declare function createPassiveCaptchaModule({ id, defaultPlaceholderSelector, defaultTokenFieldNames, defaultWaitForValueMs, }: {
    id: string;
    defaultPlaceholderSelector: string;
    defaultTokenFieldNames?: string[];
    defaultWaitForValueMs?: number;
}): FormieModuleDefinition;
export declare function createManagedCaptchaModule<TProvider extends Record<string, unknown>, TApi, TWidget>(adapter: ManagedCaptchaModuleAdapter<TProvider, TApi, TWidget>): FormieModuleDefinition;
export {};
//# sourceMappingURL=factories.d.ts.map