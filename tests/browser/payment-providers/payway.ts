import { expect } from '@playwright/test';
import type { PaymentProvider } from './types';

// PayWay renders its card frame into the element with the `payway-credit-card` id.
const FAKE_PAYWAY = `
window.payway = {
    createCreditCardFrame: function (options, callback) {
        window.__paymentObserved = options;
        var container = document.getElementById('payway-credit-card');
        var fake = document.createElement('div');
        fake.setAttribute('data-fake-payway', 'frame');
        container.appendChild(fake);
        callback(null, {
            getToken: function (done) { done(null, { singleUseTokenId: 'tok-parity' }); },
            destroy: function () { fake.remove(); },
        });
    },
};`;

// The card frame mounts, and submitting asks it for a single-use token and posts it.
export const payway: PaymentProvider = {
    name: 'PayWay',
    scripts: { 'https://api.payway.com.au/rest/v1/payway.js': FAKE_PAYWAY },
    mounted: async (field) => {
        await expect(field.locator('[data-formie-payway-button] [data-fake-payway="frame"]')).toHaveCount(1, { timeout: 15000 });
    },
    submits: 1,
    observe: (page) => page.evaluate(() => (window as any).__paymentObserved ?? null),
};
