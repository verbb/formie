import type { PaymentProvider } from './types';

const CHECKOUT = 'https://parity-checkout.example.test/go-cardless';

// No SDK. The server answers with an authorisation redirect, which the module follows from its action event.
export const goCardless: PaymentProvider = {
    name: 'GoCardless',
    scripts: { 'https://parity-checkout.example.test/**': '<!doctype html><title>Checkout</title>' },
    mounted: async () => {},
    decision: { status: 'requiresAction', message: 'Please wait while you are redirected to GoCardless.', action: { type: 'redirect', url: CHECKOUT, event: 'formie:payment:go-cardless:redirect', payload: { redirectUrl: CHECKOUT } } },
    submits: 1,
    observe: async (page) => {
        await page.waitForURL(CHECKOUT, { timeout: 15000 });
        return page.url();
    },
};
