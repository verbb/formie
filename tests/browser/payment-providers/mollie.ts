import type { PaymentProvider } from './types';

const CHECKOUT = 'https://parity-checkout.example.test/mollie';

// No SDK. The server answers with a checkout redirect, which the module follows from its action event.
export const mollie: PaymentProvider = {
    name: 'Mollie',
    scripts: { 'https://parity-checkout.example.test/**': '<!doctype html><title>Checkout</title>' },
    mounted: async () => {},
    decision: { status: 'requiresAction', message: 'Please wait while you are redirected to complete payment.', action: { type: 'redirect', url: CHECKOUT, event: 'formie:payment:mollie:redirect', payload: { checkoutUrl: CHECKOUT } } },
    submits: 1,
    observe: async (page) => {
        await page.waitForURL(CHECKOUT, { timeout: 15000 });
        return page.url();
    },
};
