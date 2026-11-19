import { expect } from '@playwright/test';
import type { PaymentProvider } from './types';

// Paddle opens an overlay checkout, so the fake marks the page instead of the field. Completing the checkout reports
// the transaction back through the event callback the module registered.
const FAKE_PADDLE = `
(function () {
    var eventCallback = null;
    window.Paddle = {
        Environment: { set: function () {} },
        Initialize: function (options) {
            eventCallback = options.eventCallback;
            document.body.setAttribute('data-fake-paddle', 'initialized');
        },
        Checkout: {
            open: function (data) {
                window.__paymentObserved = data;
                var overlay = document.createElement('div');
                overlay.setAttribute('data-fake-paddle-checkout', '');
                document.body.appendChild(overlay);
                setTimeout(function () {
                    eventCallback({ name: 'checkout.completed', data: { transaction_id: data.transactionId, status: 'completed' } });
                }, 0);
            },
            close: function () {
                var overlay = document.querySelector('[data-fake-paddle-checkout]');
                if (overlay) overlay.remove();
            },
        },
    };
})();`;

// The server creates a transaction and asks the module to open checkout. The module posts the completed checkout data.
export const paddle: PaymentProvider = {
    name: 'Paddle',
    scripts: { 'https://cdn.paddle.com/paddle/v2/paddle.js': FAKE_PADDLE },
    mounted: async (_field, page) => {
        await expect(page.locator('body[data-fake-paddle="initialized"]')).toHaveCount(1, { timeout: 15000 });
    },
    decision: { status: 'requiresAction', message: 'Complete checkout.', action: { type: 'confirm', event: 'formie:payment:paddle:initialize', payload: { transactionId: 'txn_parity' } } },
    submits: 2,
    observe: (page) => page.evaluate(() => (window as any).__paymentObserved ?? null),
};
