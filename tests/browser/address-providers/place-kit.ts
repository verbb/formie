import { expect } from '@playwright/test';
import type { AddressProvider } from './types';

// PlaceKit's autocomplete library is bundled with Formie, so only its API is faked. It looks up the visitor's country
// first (none found here), then searches as the visitor types.
const RESULTS = {
    results: [{
        type: 'street',
        name: '10 Smith St',
        highlight: '10 Smith St',
        street: { number: '10', name: 'Smith St' },
        city: 'Collingwood',
        county: 'Melbourne',
        administrative: 'Victoria',
        zipcode: ['3066'],
        country: 'Australia',
        countrycode: 'au',
        coordinates: '-37.8,144.98',
    }],
};

// The library renders its suggestion panel on the auto-complete input, and the module fills the parts from the pick.
export const placeKit: AddressProvider = {
    name: 'PlaceKit',
    routes: {
        'https://api.placekit.co/reverse': { contentType: 'application/json', body: JSON.stringify({ results: [] }) },
        'https://api.placekit.co/search': { contentType: 'application/json', body: JSON.stringify(RESULTS) },
    },
    mounted: async (field) => {
        await expect(field.locator('[data-formie-address-autocomplete-input]')).toHaveAttribute('role', 'combobox', { timeout: 15000 });
    },
    pick: async (field, page) => {
        await field.locator('[data-formie-address-autocomplete-input]').pressSequentially('10 Smith');
        await page.locator('.pka-panel.pka-open [role="option"]', { hasText: '10 Smith St' }).click();
    },
};
