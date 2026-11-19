import { expect } from '@playwright/test';
import type { PaymentProvider } from './types';

// Client-side encryption marks each value with the key and keeps only the last four digits, so the posted token
// data shows what was encrypted without carrying the card number.
const FAKE_ECRYPT = `
window.eCrypt = {
    encryptValue: function (value, key) {
        return 'eCrypted:' + key + ':' + value.length + ':' + value.slice(-4);
    },
};`;

// The card sub-fields render without names. Submitting loads eCrypt, encrypts the card number and CVC with the
// client-side encryption key, and posts them as token data.
export const eway: PaymentProvider = {
    name: 'Eway',
    scripts: { 'https://secure.ewaypayments.com/scripts/eCrypt*.js': FAKE_ECRYPT },
    mounted: async (field) => {
        await expect(field.locator('[data-eway-card]')).toHaveCount(4, { timeout: 15000 });
    },
    interact: async (field) => {
        await field.locator('[data-eway-card="cardholder-name"]').fill('Ada Lovelace');
        await field.locator('[data-eway-card="card-number"]').fill('4444333322221111');
        await field.locator('[data-eway-card="expiry-date"]').fill('1230');
        await field.locator('[data-eway-card="security-code"]').fill('123');
    },
    submits: 1,
};
