import { expect } from '@playwright/test';
import type { PaymentProvider } from './types';

// No SDK. The card sub-fields render without names, and submitting posts the card details as JSON in the
// `bpointToken` input, which the server sends to BPoint.
export const bpoint: PaymentProvider = {
    name: 'Bpoint',
    scripts: {},
    mounted: async (field) => {
        await expect(field.locator('[data-bpoint-card]')).toHaveCount(4, { timeout: 15000 });
    },
    interact: async (field) => {
        await field.locator('[data-bpoint-card="cardholder-name"]').fill('Ada Lovelace');
        await field.locator('[data-bpoint-card="card-number"]').fill('4444 3333 2222 1111');
        await field.locator('[data-bpoint-card="expiry-date"]').fill('12/30');
        await field.locator('[data-bpoint-card="security-code"]').fill('123');
    },
    submits: 1,
};
