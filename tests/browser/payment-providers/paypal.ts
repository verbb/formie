import { expect } from '@playwright/test';
import type { PaymentProvider } from './types';

const FAKE_PAYPAL = `
window.paypal = {
    Buttons: function (options) {
        return {
            render: function (element) {
                var button = document.createElement('button');
                button.type = 'button';
                button.setAttribute('data-fake-paypal', 'button');
                button.textContent = 'PayPal';
                button.addEventListener('click', function () {
                    var actions = {
                        order: {
                            create: function (config) {
                                window.__paymentObserved = config;
                                return Promise.resolve('ORDER-PARITY');
                            },
                            authorize: function () {
                                return Promise.resolve({ purchase_units: [{ payments: { authorizations: [{ id: 'AUTH-PARITY' }] } }] });
                            },
                        },
                    };
                    Promise.resolve(options.createOrder({}, actions)).then(function (orderID) {
                        return options.onApprove({ orderID: orderID }, actions);
                    }).catch(function (error) { options.onError(error); });
                });
                element.appendChild(button);
                return { close: function () {} };
            },
        };
    },
};`;

// The buttons render, the visitor approves the order in PayPal, and the module posts the order and authorization ids.
export const paypal: PaymentProvider = {
    name: 'PayPal',
    scripts: { 'https://www.paypal.com/sdk/js**': FAKE_PAYPAL },
    mounted: async (field) => {
        await expect(field.locator('[data-formie-paypal-button] [data-fake-paypal="button"]')).toHaveCount(1, { timeout: 15000 });
    },
    interact: async (field) => {
        await field.locator('[data-fake-paypal="button"]').click();
        await expect(field.locator('input[name="fields[pay][paypalAuthId]"]')).toHaveValue('AUTH-PARITY');
    },
    submits: 1,
    // The order PayPal was asked to create, including the resolved amount and currency.
    observe: (page) => page.evaluate(() => (window as any).__paymentObserved ?? null),
};
