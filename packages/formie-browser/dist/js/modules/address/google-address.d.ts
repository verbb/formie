import type { AddressHostServices } from '#modules/address/host';
type GoogleAddressProviderOptions = {
    apiKey?: string;
    geocodeEndpoint?: string;
    options?: Record<string, unknown>;
    countryDefaultValue?: string;
};
export declare function setFieldValues(services: AddressHostServices, data: {
    address1?: string;
    city?: string;
    state?: string;
    zip?: string;
    country?: string;
    formattedAddress?: string;
}): void;
type GooglePostalAddress = {
    addressLines?: unknown[];
};
export declare function buildAddressFromComponents(formData: Record<string, string>, postalAddress?: GooglePostalAddress): {
    address1: string;
    city: string;
    state: string;
    zip: string;
    country: string;
};
export declare function buildGoogleAutocompleteOptions(provider: GoogleAddressProviderOptions): Record<string, unknown>;
export declare const googleAddressModule: import("../../..").BrowserModuleDefinition;
export {};
//# sourceMappingURL=google-address.d.ts.map