/** Default selector for the autocomplete input within an address field. */
export const DEFAULT_AUTOCOMPLETE_SELECTOR = '[data-formie-address-autocomplete-input]';
export const ADDRESS_LOCATION_SELECTOR = '[data-formie-address-location]';

/** Keys for address sub-field inputs targeted by provider modules. */
export type AddressFieldInputKey =
    | 'autoComplete'
    | 'address1'
    | 'address2'
    | 'address3'
    | 'city'
    | 'state'
    | 'zip'
    | 'country';

/**
 * Canonical address sub-field selectors (PHP output, UI reference, provider modules).
 * All address-owned runtime hooks use the `data-formie-address-*` namespace.
 */
export const ADDRESS_SELECTORS = {
    autoComplete: '[data-formie-address-autocomplete-input]',
    address1: '[data-formie-address-line1-input]',
    address2: '[data-formie-address-line2-input]',
    address3: '[data-formie-address-line3-input]',
    city: '[data-formie-address-city-input]',
    state: '[data-formie-address-state-input]',
    zip: '[data-formie-address-zip-input]',
    country: '[data-formie-address-country-input]',
} as const satisfies Record<AddressFieldInputKey, string>;

/**
 * Legacy bare hooks still present on forms saved before the selector normalization.
 * `findAddressFieldInput()` checks canonical selectors first, then these.
 */
export const ADDRESS_LEGACY_SELECTORS = {
    autoComplete: '[data-formie-address-autocomplete-input]',
    address1: '[data-address1]',
    address2: '[data-address2]',
    address3: '[data-address3]',
    city: '[data-city]',
    state: '[data-state]',
    zip: '[data-zip]',
    country: '[data-country]',
} as const satisfies Record<AddressFieldInputKey, string>;

const ADDRESS_FIELD_INPUT_SELECTOR_ORDER: Record<AddressFieldInputKey, readonly string[]> = {
    autoComplete: [
        ADDRESS_SELECTORS.autoComplete,
        ADDRESS_LEGACY_SELECTORS.autoComplete,
    ],
    address1: [
        ADDRESS_SELECTORS.address1,
        ADDRESS_LEGACY_SELECTORS.address1,
    ],
    address2: [
        ADDRESS_SELECTORS.address2,
        ADDRESS_LEGACY_SELECTORS.address2,
    ],
    address3: [
        ADDRESS_SELECTORS.address3,
        ADDRESS_LEGACY_SELECTORS.address3,
    ],
    city: [
        ADDRESS_SELECTORS.city,
        ADDRESS_LEGACY_SELECTORS.city,
    ],
    state: [
        ADDRESS_SELECTORS.state,
        ADDRESS_LEGACY_SELECTORS.state,
    ],
    zip: [
        ADDRESS_SELECTORS.zip,
        ADDRESS_LEGACY_SELECTORS.zip,
    ],
    country: [
        ADDRESS_SELECTORS.country,
        ADDRESS_LEGACY_SELECTORS.country,
    ],
};

/** Resolve an address sub-field input against canonical and legacy selectors. */
export function findAddressFieldInput(
    scope: ParentNode,
    key: AddressFieldInputKey,
): HTMLInputElement | HTMLSelectElement | null {
    for (const selector of ADDRESS_FIELD_INPUT_SELECTOR_ORDER[key]) {
        const candidate = scope.querySelector(selector);

        if (candidate instanceof HTMLInputElement || candidate instanceof HTMLSelectElement) {
            return candidate;
        }
    }

    return null;
}
