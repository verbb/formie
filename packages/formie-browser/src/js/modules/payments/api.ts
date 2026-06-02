import {
    createManagedPaymentModule,
    type PaymentModuleSetupContext,
    type ManagedPaymentModuleAdapter,
} from '#modules/payments/factories';
import type {
    PaymentHostServices,
    PaymentModuleOptions as PaymentModuleManifestOptions,
} from '#modules/payments/host';

export const definePaymentModule = createManagedPaymentModule;

export type PaymentServices = PaymentHostServices;
export type PaymentModuleContext<TProvider extends Record<string, unknown>> = PaymentModuleSetupContext<TProvider>;
export type PaymentModuleOptions<TProvider extends Record<string, unknown>> = PaymentModuleManifestOptions<TProvider>;
export type PaymentProviderModule<
    TProvider extends Record<string, unknown>,
    TApi,
    TWidget,
> = ManagedPaymentModuleAdapter<TProvider, TApi, TWidget>;
