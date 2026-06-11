import {
    createManagedAddressModule,
    type AddressModuleSetupContext,
    type ManagedAddressModuleAdapter,
} from '#modules/address/factories';
import type {
    AddressHostServices,
    AddressModuleOptions as AddressModuleManifestOptions,
} from '#modules/address/host';

export {
    ADDRESS_LEGACY_SELECTORS,
    ADDRESS_SELECTORS,
    findAddressFieldInput,
    type AddressFieldInputKey,
} from '#modules/address/constants';

// This file is the intended authoring surface for address providers.
// Built-in providers import from here so third-party authors can follow the same patterns.
export const defineAddressModule = createManagedAddressModule;

export type AddressServices = AddressHostServices;
export type AddressModuleContext<TProvider extends Record<string, unknown>> = AddressModuleSetupContext<TProvider>;
export type AddressModuleOptions<TProvider extends Record<string, unknown>> = AddressModuleManifestOptions<TProvider>;
export type AddressProviderModule<
    TProvider extends Record<string, unknown>,
    TApi,
    TWidget,
> = ManagedAddressModuleAdapter<TProvider, TApi, TWidget>;
