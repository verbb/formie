import { expect, type Page } from '@playwright/test';
import type { PaymentProvider } from './types';

const ACS = 'https://parity-acs.example.test/opayo';
const CARD_NUMBER = '4929000000006';

// Own-form tokenisation, which records what the module handed it.
const FAKE_SAGEPAY = `
window.sagepayOwnForm = function (options) {
    return {
        tokeniseCardDetails: function (request) {
            window.__paymentObserved = { merchantSessionKey: options.merchantSessionKey, cardDetails: request.cardDetails };
            request.onTokenised({ success: true, cardIdentifier: 'card-parity' });
        },
    };
};`;

// What reached the merchant session endpoint and the challenge page, per page.
const seen = new WeakMap<Page, { sessions: { fields: string[]; carriesCard: boolean }[]; challenge: Record<string, string> | null }>();

// Submitting requests a merchant session key from Formie, tokenises the card sub-fields with it, and posts the card
// identifier. The server asks for a 3D Secure challenge, the challenge page reports the transaction back, and the
// module resubmits with it.
export const opayo: PaymentProvider = {
    name: 'Opayo',
    scripts: { 'https://live.opayo.eu.elavon.com/api/v1/js/sagepay.js': FAKE_SAGEPAY },
    mounted: async (field, page) => {
        const record = { sessions: [] as { fields: string[]; carriesCard: boolean }[], challenge: null as Record<string, string> | null };
        seen.set(page, record);

        // Formie's own session endpoint talks to Opayo, which the test app can't reach.
        await page.route(/payment-sessions(%2F|\/)initialize/, async (route) => {
            const body = route.request().postDataBuffer()?.toString('utf8') ?? '';
            const fields = [...body.matchAll(/name="([^"]+)"/g)].map(([, name]) => name).sort();
            record.sessions.push({ fields, carriesCard: body.includes(CARD_NUMBER) });
            await route.fulfill({ contentType: 'application/json', body: JSON.stringify({ merchantSessionKey: 'msk-parity' }) });
        });

        // The issuer's challenge page, answering like Formie's challenge return does.
        await page.route(`${ACS}**`, async (route) => {
            record.challenge = Object.fromEntries(new URLSearchParams(route.request().postData() ?? ''));
            await route.fulfill({
                contentType: 'text/html',
                body: '<script>window.parent.postMessage({ message: "formie:payment:opayo:challenge:response", value: { transactionId: "tx-parity" } }, "*");</script>',
            });
        });

        await expect(field.locator('[data-opayo-card]')).toHaveCount(4, { timeout: 15000 });
    },
    interact: async (field) => {
        await field.locator('[data-opayo-card="cardholder-name"]').fill('Ada Lovelace');
        await field.locator('[data-opayo-card="card-number"]').fill(CARD_NUMBER);
        await field.locator('[data-opayo-card="expiry-date"]').fill('12/30');
        await field.locator('[data-opayo-card="security-code"]').fill('123');
    },
    decision: {
        status: 'requiresAction',
        message: 'This payment requires 3D Secure authentication.',
        action: { type: 'challenge', url: ACS, event: 'formie:payment:opayo:challenge', payload: { acsUrl: ACS, creq: 'creq-parity', returnUrl: 'https://parity-return.example.test/', threeDSSessionData: 'challenge-parity' } },
    },
    submits: 2,
    observe: async (page) => ({
        tokenised: await page.evaluate(() => (window as any).__paymentObserved ?? null),
        ...seen.get(page),
    }),
};
