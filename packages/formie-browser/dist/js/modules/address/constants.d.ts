/** Default selector for the autocomplete input within an address field. */
export declare const DEFAULT_AUTOCOMPLETE_SELECTOR = "[data-formie-address-autocomplete-input]";
export declare const ADDRESS_LOCATION_SELECTOR = "[data-formie-address-location]";
/** Keys for address sub-field inputs targeted by provider modules. */
export type AddressFieldInputKey = 'autoComplete' | 'address1' | 'address2' | 'address3' | 'city' | 'state' | 'zip' | 'country';
/**
 * Canonical address sub-field selectors (PHP output, UI reference, provider modules).
 * All address-owned runtime hooks use the `data-formie-address-*` namespace.
 */
export declare const ADDRESS_SELECTORS: {
    readonly autoComplete: "[data-formie-address-autocomplete-input]";
    readonly address1: "[data-formie-address-line1-input]";
    readonly address2: "[data-formie-address-line2-input]";
    readonly address3: "[data-formie-address-line3-input]";
    readonly city: "[data-formie-address-city-input]";
    readonly state: "[data-formie-address-state-input]";
    readonly zip: "[data-formie-address-zip-input]";
    readonly country: "[data-formie-address-country-input]";
};
/**
 * Legacy bare hooks still present on forms saved before the selector normalization.
 * `findAddressFieldInput()` checks canonical selectors first, then these.
 */
export declare const ADDRESS_LEGACY_SELECTORS: {
    readonly autoComplete: "[data-formie-address-autocomplete-input]";
    readonly address1: "[data-address1]";
    readonly address2: "[data-address2]";
    readonly address3: "[data-address3]";
    readonly city: "[data-city]";
    readonly state: "[data-state]";
    readonly zip: "[data-zip]";
    readonly country: "[data-country]";
};
/** Resolve an address sub-field input against canonical and legacy selectors. */
export declare function findAddressFieldInput(scope: ParentNode, key: AddressFieldInputKey): HTMLInputElement | HTMLSelectElement | null;
//# sourceMappingURL=constants.d.ts.map