import {
    createManagedCaptchaModule,
    createPassiveCaptchaModule,
    type CaptchaModuleSetupContext,
    type ManagedCaptchaModuleAdapter,
} from '#modules/captchas/factories';
import type {
    CaptchaHostServices,
    CaptchaModuleOptions as CaptchaModuleManifestOptions,
} from '#modules/captchas/host';

// This file is the intended authoring surface for captcha providers.
// Built-in providers import from here so third-party authors can follow the
// same patterns we use internally.
export const defineCaptchaModule = createManagedCaptchaModule;
export const definePassiveCaptchaModule = createPassiveCaptchaModule;

export type CaptchaServices = CaptchaHostServices;
export type CaptchaModuleContext<TProvider extends Record<string, unknown>> = CaptchaModuleSetupContext<TProvider>;
export type CaptchaModuleOptions<TProvider extends Record<string, unknown>> = CaptchaModuleManifestOptions<TProvider>;
export type CaptchaProviderModule<
    TProvider extends Record<string, unknown>,
    TApi,
    TWidget,
> = ManagedCaptchaModuleAdapter<TProvider, TApi, TWidget>;
