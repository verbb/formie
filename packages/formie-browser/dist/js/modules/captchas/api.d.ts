import { createManagedCaptchaModule, createPassiveCaptchaModule, type CaptchaModuleSetupContext, type ManagedCaptchaModuleAdapter } from '#modules/captchas/factories';
import type { CaptchaHostServices, CaptchaModuleOptions as CaptchaModuleManifestOptions } from '#modules/captchas/host';
export declare const defineCaptchaModule: typeof createManagedCaptchaModule;
export declare const definePassiveCaptchaModule: typeof createPassiveCaptchaModule;
export type CaptchaServices = CaptchaHostServices;
export type CaptchaModuleContext<TProvider extends Record<string, unknown>> = CaptchaModuleSetupContext<TProvider>;
export type CaptchaModuleOptions<TProvider extends Record<string, unknown>> = CaptchaModuleManifestOptions<TProvider>;
export type CaptchaProviderModule<TProvider extends Record<string, unknown>, TApi, TWidget> = ManagedCaptchaModuleAdapter<TProvider, TApi, TWidget>;
//# sourceMappingURL=api.d.ts.map