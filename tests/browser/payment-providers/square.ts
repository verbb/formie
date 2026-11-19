import { expect } from '@playwright/test';
import type { PaymentProvider } from './types';

const FAKE_SQUARE = `
window.Square = {
    payments: function (applicationId, locationId) {
        window.__paymentObserved = { applicationId: applicationId, locationId: locationId };
        return {
            card: function () {
                return Promise.resolve({
                    attach: function (element) {
                        var fake = document.createElement('div');
                        fake.setAttribute('data-fake-square', 'card');
                        element.appendChild(fake);
                        return Promise.resolve();
                    },
                    tokenize: function () {
                        return Promise.resolve({ status: 'OK', token: 'cnon:parity' });
                    },
                });
            },
        };
    },
};`;

// The card form attaches, and submitting tokenizes the card and posts the nonce.
export const square: PaymentProvider = {
    name: 'Square',
    scripts: { 'https://*squarecdn.com/v1/square.js': FAKE_SQUARE },
    mounted: async (field) => {
        await expect(field.locator('[data-formie-square-button] [data-fake-square="card"]')).toHaveCount(1, { timeout: 15000 });
    },
    submits: 1,
    observe: (page) => page.evaluate(() => (window as any).__paymentObserved ?? null),
};
