import { createManagedAddressModule, type AddressModuleSetupContext, type ManagedAddressModuleAdapter } from '#modules/address/factories';
import type { AddressHostServices, AddressModuleOptions as AddressModuleManifestOptions } from '#modules/address/host';
export declare const defineAddressModule: typeof createManagedAddressModule;
export type AddressServices = AddressHostServices;
export type AddressModuleContext<TProvider extends Record<string, unknown>> = AddressModuleSetupContext<TProvider>;
export type AddressModuleOptions<TProvider extends Record<string, unknown>> = AddressModuleManifestOptions<TProvider>;
export type AddressProviderModule<TProvider extends Record<string, unknown>, TApi, TWidget> = ManagedAddressModuleAdapter<TProvider, TApi, TWidget>;
//# sourceMappingURL=api.d.ts.map