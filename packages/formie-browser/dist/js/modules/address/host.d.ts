import type { ModuleSetupContext } from '#contracts/modules';
import { type AddressFieldInputKey } from '#modules/address/constants';
type Cleanup = () => void;
export type AddressModuleOptions<TProvider extends Record<string, unknown> = Record<string, unknown>> = {
    handle?: string;
} & TProvider;
export type NormalizedAddressModuleOptions<TProvider extends Record<string, unknown>> = {
    handle: string;
    provider: TProvider;
};
export type AddressHostServices = {
    root: Element;
    field: Element;
    form: HTMLFormElement | null;
    input: {
        getAutocomplete: () => HTMLInputElement | null;
        setValue: (selector: AddressFieldInputKey, value: string, fallback?: string) => void;
    };
    location: {
        getButton: () => HTMLElement | null;
        onUseLocation: (callback: (position: GeolocationPosition) => void) => Cleanup;
    };
    events: {
        onField: (eventName: string, callback: EventListener) => Cleanup;
    };
};
export declare function normalizeAddressModuleOptions<TProvider extends Record<string, unknown>>(id: string, rawOptions: Record<string, unknown> | undefined): NormalizedAddressModuleOptions<TProvider>;
export declare function createAddressHostServices(ctx: ModuleSetupContext): AddressHostServices;
export {};
//# sourceMappingURL=host.d.ts.map