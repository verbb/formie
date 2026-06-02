import type { FormieModuleDefinition, ModuleSetupContext } from '#contracts/modules';
import { type AddressHostServices, type NormalizedAddressModuleOptions } from '#modules/address/host';
export type AddressModuleSetupContext<TProvider extends Record<string, unknown>> = Omit<ModuleSetupContext, 'options'> & {
    options: NormalizedAddressModuleOptions<TProvider>;
    services: AddressHostServices;
};
export type ManagedAddressModuleAdapter<TProvider extends Record<string, unknown>, TApi, TWidget> = {
    id: string;
    load: (ctx: AddressModuleSetupContext<TProvider>) => Promise<TApi>;
    mount: (args: {
        api: TApi;
        field: Element;
        services: AddressHostServices;
        options: NormalizedAddressModuleOptions<TProvider>;
        provider: TProvider;
    }) => Promise<TWidget | null> | TWidget | null;
    unmount?: (args: {
        api: TApi;
        widget: TWidget;
        field: Element;
        services: AddressHostServices;
        options: NormalizedAddressModuleOptions<TProvider>;
        provider: TProvider;
    }) => Promise<void> | void;
    onCurrentLocation?: (position: GeolocationPosition, args: {
        api: TApi;
        widget: TWidget;
        field: Element;
        services: AddressHostServices;
        options: NormalizedAddressModuleOptions<TProvider>;
        provider: TProvider;
    }) => void | Promise<void>;
};
export declare function createManagedAddressModule<TProvider extends Record<string, unknown>, TApi, TWidget>(adapter: ManagedAddressModuleAdapter<TProvider, TApi, TWidget>): FormieModuleDefinition;
//# sourceMappingURL=factories.d.ts.map