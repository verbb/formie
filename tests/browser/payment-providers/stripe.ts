import { expect } from '@playwright/test';
import type { PaymentProvider } from './types';

const FAKE_STRIPE = `
window.Stripe = function () {
    return {
        elements: function () {
            return {
                create: function (type) {
                    return {
                        mount: function (element) {
                            var fake = document.createElement('div');
                            fake.setAttribute('data-fake-stripe', type);
                            element.appendChild(fake);
                        },
                        destroy: function () {},
                    };
                },
                submit: function () { return Promise.resolve({}); },
                update: function () {},
            };
        },
        confirmPayment: function (options) {
            window.__paymentObserved = options.clientSecret;
            return Promise.resolve({ paymentIntent: { id: 'pi_parity', status: 'succeeded' } });
        },
        confirmSetup: function () { return Promise.resolve({ setupIntent: { id: 'seti_parity' } }); },
    };
};`;

// The Payment Element mounts, the server asks for confirmation, and the module confirms and resubmits with the intent.
export const stripe: PaymentProvider = {
    name: 'Stripe',
    scripts: { 'https://js.stripe.com/**': FAKE_STRIPE },
    mounted: async (field) => {
        await expect(field.locator('[data-formie-stripe-elements] [data-fake-stripe="payment"]')).toHaveCount(1, { timeout: 15000 });
    },
    decision: { status: 'requiresAction', message: 'Confirm the payment.', action: { type: 'confirm', event: 'formie:payment:stripe:confirm', payload: { clientSecret: 'pi_parity_secret' } } },
    submits: 2,
    observe: (page) => page.evaluate(() => (window as any).__paymentObserved ?? null),
};
