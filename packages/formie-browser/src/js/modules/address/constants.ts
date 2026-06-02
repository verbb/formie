/** Default selector for the autocomplete input within an address field. */
export const DEFAULT_AUTOCOMPLETE_SELECTOR = '[data-formie-address-autocomplete-input]';
export const ADDRESS_LOCATION_SELECTOR = '[data-formie-address-location]';

/** Selector for address sub-field inputs used when populating from provider. */
export const ADDRESS_SELECTORS = {
    autoComplete: '[data-formie-address-autocomplete-input]',
    address1: '[data-formie-address-line1-input]',
    address2: '[data-formie-address-line2-input]',
    address3: '[data-formie-address-line3-input]',
    city: '[data-formie-address-city-input]',
    state: '[data-formie-address-state-input]',
    zip: '[data-formie-address-zip-input]',
    country: '[data-formie-address-country-input]',
} as const;
